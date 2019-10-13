<?php

use model\BlockHeader;
use model\MiningJob;

require_once 'model/iModelClass.php';
require_once 'model/BlockHeader.php';
require_once 'model/MiningJob.php';

/**
 * Controller for the work endpoint of the Api
 * @author Tobias Sattler
 *
 */
class WorkController extends ApiController {
    public function getWork() {        
        /*$blockHeader = new BlockHeader(
            2,
            hex2bin("00000000000008a3a41b85b8b29ad444def299fee21793cd8b9e567eab02cd81"),
            hex2bin("2b12fcf1b09288fcaff797d71e950e71ae42b91e8bdb2304758dfcffc2b620e3"),
            time(),
            17, 440711666,
            2504433986);
        $miningJob = new MiningJob(1, 5, $blockHeader);
        
        $blockHeader599197 = new BlockHeader(4,
            hex2bin("000000000000000000102d45ebfa03cfe54e630d19ef4ffac88a1bc4e146805d"),
            hex2bin("65df5ef0946129d9a1541ce04a93c0735b1df7b77d87ebff8d2517e6df3c5cab"),
            1570966208, 18, 440711666, 1811931143);
        $miningJob2 = new MiningJob(2, 5, $blockHeader599197);
        
        $blockHeader125552 = new BlockHeader(1,
            hex2bin("00000000000008a3a41b85b8b29ad444def299fee21793cd8b9e567eab02cd81"),
            hex2bin("2b12fcf1b09288fcaff797d71e950e71ae42b91e8bdb2304758dfcffc2b620e3"),
            1305998791, 17, 440711666, 2504433986);
        $miningJob3 = new MiningJob(3, 5, $blockHeader125552);*
      
        $this->sendJSONResponse($miningJob3->toJSON());*/
        
        $this->sendJSONResponse($this->createWorkData()->toJSON());
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
            hex2bin($bcArray["previous_hash"]),
            hex2bin($merkleRoot),
            $timestamp,
            $difficultyTarget,
            $nbits,
            0);
        
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
            hex2bin(hash("sha256", rand())),
            hex2bin("2b12fcf1b09288fcaff797d71e950e71ae42b91e8bdb2304758dfcffc2b620e3"),
            1305998791, 17, 440711666, 2504433986);
        $miningJob3 = new MiningJob(3, 5, $blockHeader125552);
        return $miningJob3;
    }
}