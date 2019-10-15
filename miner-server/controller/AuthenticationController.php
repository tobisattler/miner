<?php
namespace controller;
use Config;

class AuthenticationController extends ApiController
{

    protected $clientId;
    
    public function __construct()
    {
        parent::__construct();
        
        if (!isset($_GET["token"])) {
            $this->exitWith403Error("You need to provide your access token in the URL.");
        }
        
        $client = $this->database->get(Config::TABLE_CLIENTS,[
            "clientId [Int]"
        ], [
            "token" => $_GET["token"]
        ]);
        
        if (!is_array($client) || !isset($client["clientId"])) {
            $this->exitWith403Error("Invalid token.");
        }
        
        $this->clientId = $client["clientId"];
    }
}

