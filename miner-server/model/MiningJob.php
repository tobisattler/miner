<?php
namespace model;

/**
 * Data model for a MiningJob
 * @author Tobias Sattler
 *
 */
class MiningJob implements iModelClass {
    /**
     * id of the job
     * @var int
     */
    private $jobId;
    
    /**
     * id of the client, the job is meant for
     * @var int
     */
    private $clientId;
    
    /**
     * BlockHeader for the job
     * @var BlockHeader
     */
    private $blockHeader;
    
    /**
     * first nonce, that the miner should process
     * @var int
     */
    private $startNonce;
    
    /**
     * last nonce, that the miner should process
     * @var int
     */
    private $endNonce;
    
    /**
     * Creates a new MiningJob Object
     * @param int $jobId id of the job
     * @param int $clientId id of the client that the job is created for
     * @param BlockHeader $blockHeader BlockHeader of the job
     * @param int $startNonce first nonce, that the miner should process
     * @param int $endNonce last nonce, that the miner should process
     */
    public function __construct($jobId, $clientId, $blockHeader, $startNonce, $endNonce) {
        $this->jobId = $jobId;
        $this->clientId = $clientId;
        $this->blockHeader = $blockHeader;
        $this->startNonce = $startNonce;
        $this->endNonce = $endNonce;
    }
    
    /**
     * Returns the MiningJob Object as JSON string
     * @return string json-encoded version of the MiningJob object
     */
    public function toJSON() {
        $array = $this->toArray();
        return json_encode($array);
    }
    
    /**
     * Returns the MiningJob Object as Array
     * @return array array version of the MiningJob object
     */
    public function toArray() {
        $array = get_object_vars($this);
        unset($array["blockHeader"]);
        $array["blockHeader"] = $this->blockHeader->toArray();
        return $array;
    }
}