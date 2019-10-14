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
            // puzzle has already been solved. Generate new puzzle from Blockchain
            $puzzle = $this->createPuzzleFromBlockchain();
            
            if ($puzzle === false) {
                $puzzle = $this->createFallbackPuzzle();
            }
            
            if (!is_array($puzzle)) {
                // TODO: exit with internal server error. Blockchain puzzle and FallbackPuzzle failed
            }
            
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
            
            // get end nonce of last job for the current puzzle
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
                    // TODO: The highest nonce has already been reached, but puzzle has not yet been solved. Duplicate oldest open job for that puzzle.
                }
            } else {
                // no job for the puzzle yet. Create first job
                $miningJob = $this->createMiningJob($latestPuzzleHeader, $latestPuzzle["puzzleId"], 0);
                
            }
            
        }
        
        if ($miningJob !== null) {
            $this->sendJSONResponse($miningJob->toJSON());
        } else {
            $this->exitWith404Error("unable to create new Job. Sorry!");
        }
        
        
        
        //TODO: Reenable within the check logic
        /*$puzzleHeader = $this->createPuzzleFromBlockchain();
        $miningJob = new MiningJob(1, $this->clientId, $puzzleHeader, 0, Config::NONCES_PER_JOB-1);
        $this->sendJSONResponse($miningJob->toJSON());
        exit();*/
        
        /*$blockHeader = new BlockHeader(
            2,
            "00000000000008a3a41b85b8b29ad444def299fee21793cd8b9e567eab02cd81",
            "2b12fcf1b09288fcaff797d71e950e71ae42b91e8bdb2304758dfcffc2b620e3",
            time(),
            17, 440711666,
            2504433986);
        $miningJob = new MiningJob(1, 5, $blockHeader);*/
        
        $blockHeader599197 = new BlockHeader(1073725440,
            "000000000000000000102d45ebfa03cfe54e630d19ef4ffac88a1bc4e146805d",
            "65df5ef0946129d9a1541ce04a93c0735b1df7b77d87ebff8d2517e6df3c5cab",
            1570966208, 9, 387294044);
        $miningJob2 = new MiningJob(2, $this->clientId, $blockHeader599197, 0, Config::NONCES_PER_JOB-1);
        
        /*$blockHeader125552 = new BlockHeader(1,
            "00000000000008a3a41b85b8b29ad444def299fee21793cd8b9e567eab02cd81",
            "2b12fcf1b09288fcaff797d71e950e71ae42b91e8bdb2304758dfcffc2b620e3",
            1305998791, 17, 440711666, 2504433986);
        $miningJob3 = new MiningJob(3, 5, $blockHeader125552);*
      
        $this->sendJSONResponse($miningJob3->toJSON());*/
        
   
       // $this->sendJSONResponse($this->createWorkData()->toJSON());
       $this->sendJSONResponse($miningJob2->toJSON());
    }
    
    /**
     * Creates a new puzzle, using data from the last successfully mined block of the Bitcoin blockchain
     * @return boolean|mixed[] returns false if something went wrong or an array with the puzzleId and generated BlockHeader object
     */
    private function createPuzzleFromBlockchain() {
        // get info, which block was the last mined. Escpecially the URL for data regarding the last block is needed.
        $blockChainInfo = $this->loadJSONFromURL("https://api.blockcypher.com/v1/btc/main");
        
        if (!isset($blockChainInfo["previous_hash"]) || !isset($blockChainInfo["latest_url"])) {
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
        
        // calculate difficulty - count leading zeros of hash and convert into numbers of leading bytes
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
    
    private function createFallbackPuzzle() {
        // get a random old puzzle from the database
        $puzzleToRecycle = $this->database->rand(Config::TABLE_PUZZLES, [
            "puzzleId [Int]",
            "bitcoinBlockId [Int]",
            "version [Int]",
            "prevBlockHash",
            "merkleRoot",
            "timestamp",
            "nbits [Int]",
            "difficultyTarget [Int]"
        ]);
        
        $nbits = $puzzleToRecycle["nbits"];
        $timestamp = intval((new \DateTime($puzzleToRecycle["timestamp"], new \DateTimeZone("utc")))->format("U"));
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
    
    private function createMiningJob($blockHeader, $puzzleId, $startNonce) {
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
    
    private function reuseOldestMiningJob($puzzleId) {
        
    }
    
    private function createWorkData() {
        // TODO: change this
        $clientId = 2;
        $jobId = 1;
        $version = 4;
        $difficultyTarget = 16;
        
        // get latest data about the Bitcoin blockchain
        $bcArray = $this->loadJSONFromURL("https://api.blockcypher.com/v1/btc/main");
        /*print_r($bcArray);
        exit();*/
        
        // if required data fields are not populated, generate fallback data
        if (!isset($bcArray["previous_hash"]) || !isset($bcArray["previous_url"])) {
            return $this->generateFallbackData();
        }
        
        // get the last Bitcoin header
        $previousHeaderArray = $this->loadJSONfromURL($bcArray["previous_url"]);
        
        // initialize $nbits with fallback data (value is from Bitcoin block 599230)
        $nbits = 387294044;
        
        if (isset($previousHeaderArray["bits"])) {
            // populate the nbits field (i.e. difficulty representation) from last Bitcoin header
            $nbits = $previousHeaderArray["bits"];
        }
        
        // we will not create a real merkle data, but will use it to differentiate between different users (for every user the extraNonce field in the merkle tree will be different, leading to a different merkleRoot). This is simulated here
        $merkleRoot = hash("sha256", microtime() . $clientId);
        
        $timestamp = time();
        
        // create our BlockHeader
        $blockHeader = new BlockHeader(
            $version,
            $bcArray["previous_hash"],
            $merkleRoot,
            $timestamp,
            $difficultyTarget,
            $nbits,
            0, 100000);
        
        // create Job
        $miningJob = new MiningJob($jobId, $clientId, $blockHeader);
        
        return $miningJob;
    }
        
    /**
     * loads the defined $url and returns the result as json
     */
    private function loadJSONfromURL($url) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        $result = curl_exec($ch);
        return json_decode($result, true);
    }
    
    // TODO Rework to have less random data included
    private function generateFallbackData() {
        $blockHeader125552 = new BlockHeader(1,
            hash("sha256", rand()),
            "2b12fcf1b09288fcaff797d71e950e71ae42b91e8bdb2304758dfcffc2b620e3",
            1305998791, 17, 440711666, 2504433986);
        $miningJob3 = new MiningJob(3, 5, $blockHeader125552);
        return $miningJob3;
    }
}