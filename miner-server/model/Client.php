<?php
namespace model;

class Client implements iModelClass
{
    private $clientId;
    private $token;
    
    public function __construct($clientId, $token) {
        $this->clientId = $clientId;
        $this->token = $token;
    }
    
    /**
     * Returns the Client Object as JSON string
     * @return string
     */
    public function toJSON() {
        $array = $this->toArray();
        return json_encode($array);
    }
    
    /**
     * Returns the Client Object as array
     * @return array
     */
    public function toArray() {
        $array = get_object_vars($this);
        return $array;
    }
}