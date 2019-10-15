<?php
namespace controller;
use Config;
use model\Client;

require_once 'model/Client.php';

/**
 * Used for registration of new clients.
 * @author Tobias Sattler
 *
 */
class RegistrationController extends ApiController
{  
   
    /**
     * Used to register a new client.
     * Generates a new token and clientId, which then is being sent back to the client.
     */
    public function registerClient() {
        // generate API token for the new client
        $token = hash("sha256", microtime() . rand());
        
        // even if it's very unlikely, test if the hash already exists in the database
        if ($this->database->has(Config::TABLE_CLIENTS, [
            "token" => $token
        ])) {
            // hash collision detected. Try again
            $this->registerClient();
            return;
        }
        
        // insert new token into database and retrieve clientId
        $this->database->insert(Config::TABLE_CLIENTS, [
            "token" => $token
        ]);
        $clientId = intval($this->database->id());
        
        // generate Client object and send it back to the client
        $client = new Client($clientId, $token);
        $this->sendJSONResponse($client->toJSON());
    }
}

