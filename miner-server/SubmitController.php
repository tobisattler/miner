<?php

class SubmitController extends ApiController
{
    public function submitWork() {
        // check if all the required post fields are populated and exit with 404 error if not.
        if (!isset($_POST["jobId"]) || !isset($_POST["clientId"]) || !isset($_POST["solutionFound"])) {
            $this->exitWith404Error("data fields missing");
        }
       
        if ($_POST["solutionFound"]) {
            
        }
        
        header('HTTP/1.0 200 OK');
    }
}

