<?php
namespace controller;

use Medoo\Medoo;
use Config;

require_once 'external/Medoo.php';

/**
 * Base Class for all Controllers
 * @author Tobias Sattler
 *
 */
class ApiController {
    //stores the Medoo database connection object
    protected $database;
    
    public function __construct() {
        $this->database = new Medoo([
            "database_type" => "mysql",
            "database_name" => Config::DB_DATABASE,
            "server" => Config::DB_SERVER,
            "username" => Config::DB_USER,
            "password" => Config::DB_PASS
        ]);
    }
    
    /**
     * sends the json-string in $json back to the client
     * @param String $json
     */
    public function sendJSONResponse($json) {
        header('HTTP/1.0 200 OK');
        header('Content-Type: application/json');
        echo $json;
        exit();
    }
    
    public function exitWith404Error($errorText) {
        http_response_code(404);
        echo $errorText;
        exit();
    }
    
    public function exitWith403Error($errorText) {
        http_response_code(403);
        echo $errorText;
        exit();
    }
}