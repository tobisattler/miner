<?php
namespace controller;
use Config;

class SubmitController extends AuthenticationController
{
    public function submitWork() {
          
        $content = trim(file_get_contents("php://input"));
        $jsonData = json_decode($content, true);
        
        if (!is_array($jsonData)) {
            $this->exitWith404Error("submitted work result is no json data");
        }
        
        $jobId = $jsonData["jobId"];
        $clientId = $jsonData["clientId"];
        if ($clientId != $this->clientId) {
            // work not accepted. clientId in work result doesn't match identified user.
            $this->exitWith403Error("You are only authorized to submit work data for your own miner account");
        }
        
        $solutionFound = $jsonData["solutionFound"];
        
        // update the job with the transmitted jobId to finished
        $this->database->update(Config::TABLE_JOBS, [
            "finished" => true
        ], [
            "jobId" => $jobId
        ]);
        
        
        // if solution found, add an entry to the solutions table of the database
        if ($solutionFound) {
            $nonce = $jsonData["nonce"];
            $blockHash = $jsonData["blockHash"];
            
            // get puzzle Id for job
            $job = $this->database->get(Config::TABLE_JOBS, [
                "puzzleId [Int]"
            ], [
                "jobId" => $jobId
            ]);
            $puzzleId = $job["puzzleId"];
            
            $this->database->insert(Config::TABLE_SOLUTIONS, [
                "clientId" => $clientId,
                "jobId" => $jobId,
                "puzzleId" => $puzzleId,
                "nonce" => $nonce,
                "blockHash" => $blockHash
            ]);
        } else {
            // set other jobs with the same parameters (puzzleId, startNonce, endNonce) as finished (if exists).
            // This is important, if this solved job is a duplicate of another user's job.
            // This case happens, when the highest possible endNonce has already been reached, but there are still open jobs.
            
            // get parameters of the solved job
            $solvedJob = $this->database->get(Config::TABLE_JOBS, [
                "puzzleId [Int]",
                "startNonce [Int]",
                "endNonce [Int]"
            ], [
                
                "jobId" => $jobId
            ]);
            
            $this->database->update(Config::TABLE_JOBS, [
                "finished" => true
            ], [
                "puzzleId" => $solvedJob["puzzleId"],
                "startNonce" => $solvedJob["startNonce"],
                "endNonce" => $solvedJob["endNonce"]
            ]);
        }
    }
}

