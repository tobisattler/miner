<?php

require_once 'ApiController.php';
require_once 'WorkController.php';


// Get the request url
switch($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        $url = isset($_GET['_url']) ? $_GET['_url'] : '';
        break;
    case 'POST':
        $url = isset($_POST['url']) ? $_POST['_url'] : '';
        break;
    default:
        $url = '';
}

// Call the controller, that is responsible for the request
switch($url) {
    case 'work':
        $workController = new WorkController();
        $workController->getWork();
        break;
    default:
        // endpoint not defined. Respond with 404
        http_response_code(404);
        echo 'URL not found: ' . $url;
}