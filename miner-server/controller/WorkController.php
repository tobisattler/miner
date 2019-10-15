<?php
namespace controller;

use model\BlockHeader;
use model\MiningJob;
use Config;

require_once 'model/iModelClass.php';
require_once 'model/BlockHeader.php';
require_once 'model/MiningJob.php';

/**
 * Controller for the work endpoint of the Api
 * @author Tobias Sattler
 *
 */
class WorkController extends AuthenticationController {
    
    /**
     * getWork is being called by the index.php file to process requests for work data
     */
    public function getWork() {
        
        // Get the latest puzzle (with highest puzzleId)
        $latestPuzzle = $this->database->get(Config::TABLE_PUZZLES, [
            "puzzleId [Int]",
            "bitcoinBlockId [Int]",
            "version [Int]",
            "prevBlockHash",
            "merkleRoot",
            "timestamp",
            "nbits [Int]",
            "difficultyTarget [Int]"
        ], [
            "ORDER" => ["puzzleId" => "DESC"]
        ]);
        
        // Check if the latest puzzle has already been solved
        if ($this->database->has(Config::TABLE_SOLUTIONS, [
            "puzzleId [Int]" => $latestPuzzle["puzzleId"]    
        ])) {
         
            // puzzle has already been solved. Generate new puzzle from Blockchain or Fallback
            $puzzle = $this->createPuzzle();
            
            $puzzleHeader = $puzzle["puzzleHeader"];
            $puzzleId = $puzzle["puzzleId"];
            
            $miningJob = $this->createMiningJob($puzzleHeader, $puzzleId, 0);
        } else {
            // puzzle has not yet been solved.
            
            $utcTimetamp =  intval((new \DateTime($latestPuzzle["timestamp"], new \DateTimeZone("utc")))->format("U"));
            
            // create BlockHeader blueprint for latest puzzle
            $latestPuzzleHeader = new BlockHeader(
                $latestPuzzle["version"],
                $latestPuzzle["prevBlockHash"],
                $latestPuzzle["merkleRoot"],
                $utcTimetamp,
                $latestPuzzle["difficultyTarget"],
                $latestPuzzle["nbits"]);
            
            // check if the user still has an unsolved job for the current puzzle. If so, resend that job to the user
            $oldJob = $this->database->get(Config::TABLE_JOBS, [
                "jobId [Int]",
                "clientId [Int]",
                "puzzleId [Int]",
                "startNonce [Int]",
                "endNonce [Int]",
                "finished [Bool]"
            ], [
                "puzzleId" => $latestPuzzle["puzzleId"],
                "clientId" => $this->clientId,
                "finished" => false,
                "ORDER" => ["jobId" => "ASC"]
            ]);
            
            if (!is_array($oldJob) || !isset($oldJob["jobId"])) {
                // no unfinished job for this user. Create a new one
                
                // get endNonce of last job for the current puzzle
                $latestJob = $this->database->get(Config::TABLE_JOBS, [
                    "endNonce [Int]"
                ], [
                    "puzzleId" => $latestPuzzle["puzzleId"],
                    "ORDER" => ["endNonce" => "DESC"]
                ]);
                
                if (isset($latestJob["endNonce"])) {
                    $lastNonce = $latestJob["endNonce"];
                    
                    if ($lastNonce < Config::NONCE_MAX_VALUE) {
                        // the highest possible nonce value has not yet been reached. Create new puzzle starting with $lastnonce + 1
                        $miningJob = $this->createMiningJob($latestPuzzleHeader, $latestPuzzle["puzzleId"], $lastNonce+1);
                    } else {
                        // The highest nonce has already been reached, but puzzle has not yet been solved. Duplicate random open job of other users for that puzzle.
                        
                        // Check if there is still an unfinished job for this puzzle. If not, create new puzzle.
                        if ($this->database->has(Config::TABLE_JOBS, [
                           "puzzleId" => $latestPuzzle["puzzleId"],
                            "finished" => false
                        ])) {
                            $miningJob = $this->duplicateRandomUnfinishedOpenJob($latestPuzzle["puzzleId"], $latestPuzzleHeader);
                        } else {
                            $puzzle = $this->createPuzzle();
                            
                            $puzzleHeader = $puzzle["puzzleHeader"];
                            $puzzleId = $puzzle["puzzleId"];
                            
                            $miningJob = $this->createMiningJob($puzzleHeader, $puzzleId, 0);
                        }
                       
                    }
                } else {
                    // no job for the puzzle yet. Create first job
                    $miningJob = $this->createMiningJob($latestPuzzleHeader, $latestPuzzle["puzzleId"], 0);
                    
                }
            } else {
                // there is still an unfinished job for the user. Resend that one
                
                $miningJob = new MiningJob($oldJob["jobId"], $this->clientId, $latestPuzzleHeader, $oldJob["startNonce"], $oldJob["endNonce"]);
            }
            
        }
        
        // check if a new MiningJob has been successfully created in the code above. If that's the case, send it to the client. Otherwise send error to the client.
        if ($miningJob !== null) {
            $this->sendJSONResponse($miningJob->toJSON());
        } else {
            $this->exitWith500Error("unable to create new Job. Sorry!");
        }

    }
    
    /**
     * Creates a new puzzle, using data from the last successfully mined block of the Bitcoin blockchain
     * @return boolean|mixed[] returns false if something went wrong or an array with the puzzleId and generated BlockHeader object
     */
    private function createPuzzleFromBlockchain() {
        
        // get info, which block was the last mined in the Bitcoin Blockchain. Escpecially the URL for data regarding the last block is needed.
        $blockChainInfo = $this->loadJSONFromURL("https://api.blockcypher.com/v1/btc/main");
        
        if (!isset($blockChainInfo["latest_url"])) {
            // something went wrong getting the latest data from the blockchain.
            // return false instead of data, so that fallback can be used.
            return false;
        }
        
        // load the data from the last mined Bitcoin block
        $latestBlockHeader = $this->loadJSONfromURL($blockChainInfo["latest_url"]);
        
        if (!isset($latestBlockHeader["hash"]) || !isset($latestBlockHeader["bits"]) ||
            !isset($latestBlockHeader["time"]) || !isset($latestBlockHeader["height"]) ||
            !isset($latestBlockHeader["prev_block"]) || !isset($latestBlockHeader["mrkl_root"]) ||
            !isset($latestBlockHeader["nonce"]) || !isset($latestBlockHeader["ver"]))
        {
            // something went wrong getting all the needed data from the latest Bitcoin block.
            // return false instead of data, so that fallback can be used.
            return false;
        }
        
        $hash = $latestBlockHeader["hash"];
        $nbits = $latestBlockHeader["bits"];
        $timestamp = strtotime($latestBlockHeader["time"]);
        $bitcoinBlockId = $latestBlockHeader["height"];
        $previousBlockHash = $latestBlockHeader["prev_block"];
        $merkleRoot = $latestBlockHeader["mrkl_root"];
        $nonce = $latestBlockHeader["nonce"];
        $version = $latestBlockHeader["ver"];
        
        // calculate difficulty for the puzzle - count leading zeros of hash and convert into numbers of leading bytes
        $hashArray = str_split($hash);
        $count = 0;
        foreach($hashArray as $character) {
            if ($character == "0")
                $count++;
            else break;
        }
        $difficultyBytes =  intdiv($count, 2);
        
        // insert new puzzle into database
        $this->database->insert(Config::TABLE_PUZZLES, [
            "bitcoinBlockId" => $bitcoinBlockId,
            "version" => $version,
            "prevBlockHash" => $previousBlockHash,
            "merkleRoot" => $merkleRoot,
            "timestamp" => $latestBlockHeader["time"],
            "nbits" => $nbits,
            "difficultyTarget" => $difficultyBytes
        ]);
        
        $id = intval($this->database->id());
   
        // create BlockHeader object for new puzzle and return it
        $puzzleHeader = new BlockHeader($version, $previousBlockHash, $merkleRoot, $timestamp, $difficultyBytes, $nbits);
        return array("puzzleId" => $id, "puzzleHeader" => $puzzleHeader);
    }
    
    /**
     * creates a fallback puzzle. Fallback puzzles are created by duplicating already solved puzzles.
     * @return \model\BlockHeader[]|number[] array containing the puzzleId of the new puzzle (key: puzzleId) and the BlockHeader for the new puzzle (key: puzzleHeader)
     */
    private function createFallbackPuzzle() {
        // get a random old puzzle from the database
        $puzzlesToRecycle = $this->database->rand(Config::TABLE_PUZZLES, [
            "puzzleId [Int]",
            "bitcoinBlockId [Int]",
            "version [Int]",
            "prevBlockHash",
            "merkleRoot",
            "timestamp",
            "nbits [Int]",
            "difficultyTarget [Int]"
        ]);
        
        // if there is no puzzle to recycle, return an error to the client
        if (!is_array($puzzlesToRecycle) || !isset($puzzlesToRecycle[0])) {
            $this->exitWith500Error("Could not create new puzzle.");
        }
        
        $puzzleToRecycle = $puzzlesToRecycle[0];
        
        $nbits = $puzzleToRecycle["nbits"];
        $timestamp = $puzzleToRecycle["timestamp"];
        $bitcoinBlockId = $puzzleToRecycle["bitcoinBlockId"];
        $previousBlockHash = $puzzleToRecycle["prevBlockHash"];
        $merkleRoot = $puzzleToRecycle["merkleRoot"];
        $version = $puzzleToRecycle["version"];
        $difficultyBytes = $puzzleToRecycle["difficultyTarget"];
        
        // insert recycled database to recreate it as new puzzle
        $this->database->insert(Config::TABLE_PUZZLES, [
            "bitcoinBlockId" => $bitcoinBlockId,
            "version" => $version,
            "prevBlockHash" => $previousBlockHash,
            "merkleRoot" => $merkleRoot,
            "timestamp" => $timestamp,
            "nbits" => $nbits,
            "difficultyTarget" => $puzzleToRecycle["difficultyTarget"]
        ]);
        
        $id = intval($this->database->id());
        
        // create BlockHeader object for new puzzle and return it
        $puzzleHeader = new BlockHeader($version, $previousBlockHash, $merkleRoot, $timestamp, $difficultyBytes, $nbits);
        return array("puzzleId" => $id, "puzzleHeader" => $puzzleHeader);
    }
    
    /**
     * Create a new puzzle. At first it will try to get the latest mined block header from the Bitcoin Blockchain.
     * If that fails, a Fallback puzzle will be created
     * @return \model\BlockHeader[]|number[] array containing the puzzleId of the new puzzle (key: puzzleId) and the BlockHeader for the new puzzle (key: puzzleHeader)
     */
    private function createPuzzle() {
        $puzzle = $this->createPuzzleFromBlockchain();
        
        // check if puzzle creation failed. In that case, create fallback puzzle
        if ($puzzle === false) {
            $puzzle = $this->createFallbackPuzzle();
        }
        
        if (!is_array($puzzle)) {
            // exit with internal server error. Creating Blockchain puzzle and Fallback puzzle failed
            $this->exitWith500Error("Unable to create new puzzele. I have given up.");
        }
        
        return $puzzle;
    }
    
    /**
     * Create a new MiningJob with the given data
     * @param BlockHeader $blockHeader BlockHeader data for of the new job
     * @param int $puzzleId id of the new puzzle
     * @param int $startNonce first nonce, that the client should use for mining purposes
     * @return \model\MiningJob the newly created MiningJob
     */
    private function createMiningJob($blockHeader, $puzzleId, $startNonce) {
        // calculate the highest nonce, the client should calculate in this job
        $endNonce = $startNonce + Config::NONCES_PER_JOB - 1;
        
        // make sure, that $endNonce is not higher than the highest possible nonce. If so, set it to highest possible nonce.
        if ($endNonce > Config::NONCE_MAX_VALUE) {
            $endNonce = Config::NONCE_MAX_VALUE;
        }
        
        // create new Job in database
        $this->database->insert(Config::TABLE_JOBS, [
            "clientId" => $this->clientId,
            "puzzleId" => $puzzleId,
            "startNonce" => $startNonce,
            "endNonce" => $endNonce,
            "finished" => false
        ]);
        
        $jobId = intval($this->database->id());
        
        // create new MiningJob Object
        $miningJob = new MiningJob($jobId, $this->clientId, $blockHeader, $startNonce, $endNonce);
        return $miningJob;
    }
    
    /**
     * Duplicates a job for the given puzzleId that already exists in the database.
     * This is used when all nonce values have already been distributed to the clients, but the puzzle is not yet solved.
     * The client will then get a duplicate of a job that another user already has gotten (and not yet finished).
     * @param int $puzzleId
     * @param BlockHeader $blockHeader
     * @return \model\MiningJob the duplicated MiningJob
     */
    private function duplicateRandomUnfinishedOpenJob($puzzleId, $blockHeader) {
        // get a random not yet finished mining job from the database
        $jobs = $this->database->rand(Config::TABLE_JOBS,[
            "jobId [Int]",
            "clientId [Int]",
            "puzzleId [Int]",
            "startNonce [Int]",
            "endNonce [Int]",
            "finished [Bool]"
        ], [
            "puzzleId" => $puzzleId,
            "finished" => false
        ]);
        
        if (!is_array($jobs) || !isset($jobs[0])) {
            $this->exitWith500Error("Could not duplicate an existing job");
        }
        
        $job = $jobs[0];
        
        // create Job as duplicate in database
        $this->database->insert(Config::TABLE_JOBS, [
            "clientId" => $this->clientId,
            "puzzleId" => $puzzleId,
            "startNonce" => $job["startNonce"],
            "endNonce" => $job["endNonce"],
            "finished" => false
        ]);
        $jobId = intval($this->database->id());
        
        // create new MiningJob Object
        $miningJob = new MiningJob($jobId, $this->clientId, $blockHeader, $job["startNonce"], $job["endNonce"]);
        return $miningJob;
    }
        
    /**
     * loads the defined $url and returns the result as json
     * @param string $url url that should be loaded to retrieve json data
     * @return mixed decoded json-data
     */
    private function loadJSONfromURL($url) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        $result = curl_exec($ch);
        return json_decode($result, true);
    }
    
}