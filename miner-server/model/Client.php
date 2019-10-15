<?php
namespace model;

/**
 * Data structure for newly generated client, that is used for encoding clientId and token after creating a new client.
 * @author tobia
 *
 */
class Client implements iModelClass
{
    /**
     * id of the client
     * @var int
     */
    private $clientId;
    
    /**
     * api token for the client
     * @var string
     */
    private $token;
    
    /**
     * Creates a new Client object
     * @param int $clientId id of the client
     * @param string $token api token of the client
     */
    public function __construct($clientId, $token) {
        $this->clientId = $clientId;
        $this->token = $token;
    }
    
    /**
     * Returns the Client Object as JSON string
     * @return string json-encoded Client object
     */
    public function toJSON() {
        $array = $this->toArray();
        return json_encode($array);
    }
    
    /**
     * Returns the Client Object as array
     * @return array Client object as array
     */
    public function toArray() {
        $array = get_object_vars($this);
        return $array;
    }
}