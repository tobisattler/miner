<?php

use controller\WorkController;
use controller\RegistrationController;
use controller\SubmitController;

require_once 'Config.php';
require_once 'external/Medoo.php';
require_once 'controller/ApiController.php';
require_once 'controller/AuthenticationController.php';
require_once 'controller/WorkController.php';
require_once 'controller/SubmitController.php';
require_once 'controller/RegistrationController.php';


// Get the request url
switch($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        $url = isset($_GET['_url']) ? $_GET['_url'] : '';
        break;
    case 'POST':
        $url = isset($_GET['_url']) ? $_GET['_url'] : '';
        break;
    default:
        $url = '';
        http_response_code(404);
        echo 'Undefined request method: ' . $_SERVER['REQUEST_METHOD'];
        exit();
}

$urlParts = explode("/", $url);
if (sizeof($urlParts) != 2) {
    http_response_code(404);
    echo 'URL does not match the expected format: ' . $url;
    exit();
}

// Call the controller, that is responsible for the request
switch($urlParts[0]) {
    case 'v1':
        switch($urlParts[1]) {
            case 'work':
                $workController = new WorkController();
                $workController->getWork();
                break;
            case 'submit':
                $submitController = new SubmitController();
                $submitController->submitWork();
                break;
            case 'register':
                $registrationController = new RegistrationController();
                $registrationController->registerClient();
                break;
            default:
                // endpoint not defined. Respond with 404
                http_response_code(404);
                echo 'URL not found: ' . $url;
                exit();
        }
        break;
    default:
        http_response_code(404);
        echo 'specified version of the API does not exist';
        exit();
}