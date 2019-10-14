<?php
namespace controller;

class AuthenticationController extends ApiController
{

    protected $clientId;
    
    public function __construct()
    {
        parent::__construct();
        
        // TODO - perform authentication
        $this->clientId = 1;
    }
}

