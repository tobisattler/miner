<?php

/**
 * Base Class for all Controllers
 * @author Tobias Sattler
 *
 */
class ApiController {
    
    /**
     * sends the json-string in $json back to the client
     * @param String $json
     */
    public function sendResponse($json) {
        header('HTTP/1.0 200 OK');
        header('Content-Type: application/json');
        echo $json;
    }
}