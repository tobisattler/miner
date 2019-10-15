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
    /**
     * stores the Medoo database connection object
     * @var Medoo
     */
    protected $database;
    
    /**
     * Constructor of ApiController. Establishes the database connection.
     */
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
     * @param String $json JSON-encoded data that the client requested.
     */
    public function sendJSONResponse($json) {
        header('HTTP/1.0 200 OK');
        header('Content-Type: application/json');
        echo $json;
        exit();
    }
    
    /**
     * Returns a 400 error code to the client with customizable text
     * @param string $errorText description of the error
     */
    public function exitWith400Error($errorText) {
        http_response_code(400);
        echo $errorText;
        exit();
    }
    
    /**
     * Returns a 404 error code to the client with customizable text
     * @param string $errorText description of the error
     */
    public function exitWith404Error($errorText) {
        http_response_code(404);
        echo $errorText;
        exit();
    }
    
    /**
     * Returns a 403 error code to the client with customizable text 
     * @param string $errorText description of the error
     */
    public function exitWith403Error($errorText) {
        http_response_code(403);
        echo $errorText;
        exit();
    }
    
    /**
     * Sends an internal Server Error (Code 500) back to the client
     * @param string $errorText Information about the error.
     */
    public function exitWith500Error($errorText) {
        http_response_code(500);
        echo $errorText;
        exit();
    }
}