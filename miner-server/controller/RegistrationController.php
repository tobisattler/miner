<?php
namespace controller;
use Config;
use model\Client;

require_once 'model/Client.php';

class RegistrationController extends ApiController
{  
    
    public function registerClient() {
        // generate API token for the new client
        $token = hash("sha256", microtime() . rand());
        
        $this->database->insert(Config::TABLE_CLIENTS, [
            "token" => $token
        ]);
        
        $clientId = intval($this->database->id());
        
        $client = new Client($clientId, $token);
        
        $this->sendJSONResponse($client->toJSON());
    }
}

