<?php
namespace controller;
use Config;

/**
 * Base class for all controllers, that are only available when the user sends a valid token with the request.
 * @author Tobias Sattler
 *
 */
class AuthenticationController extends ApiController
{

    /**
     * id of the client
     * @var int
     */
    protected $clientId;
    
    /**
     * Constructor for the AuthenticationController. Checks if the token is valid and retriveves the associated clientId from the database.
     */
    public function __construct()
    {
        parent::__construct();
        
        // if no token is sent by the client, return a 403 error.
        if (!isset($_GET["token"])) {
            $this->exitWith403Error("You need to provide your access token in the URL.");
        }
        
        // retrieve token from database
        $client = $this->database->get(Config::TABLE_CLIENTS,[
            "clientId [Int]"
        ], [
            "token" => $_GET["token"]
        ]);
        
        // check if token was found in database. If not, return a 403 error.
        if (!is_array($client) || !isset($client["clientId"])) {
            $this->exitWith403Error("Invalid token.");
        }
        
        $this->clientId = $client["clientId"];
    }
}

