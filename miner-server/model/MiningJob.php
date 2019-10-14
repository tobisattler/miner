<?php
namespace model;

/**
 * Data model for a MiningJob
 * @author Tobias Sattler
 *
 */
class MiningJob implements iModelClass {
    private $jobId;
    private $clientId;
    private $blockHeader;
    private $startNonce;
    private $endNonce;
    
    /**
     * /**
     * Creates a new MiningJob Object
     * @param int $jobId
     * @param int $clientId
     * @param BlockHeader $blockHeader
     * @param int $startNonce
     * @param int $endNonce
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
     * @return string
     */
    public function toJSON() {
        $array = $this->toArray();
        return json_encode($array);
    }
    
    /**
     * Returns the MiningJob Object as Array
     * @return array
     */
    public function toArray() {
        $array = get_object_vars($this);
        unset($array["blockHeader"]);
        $array["blockHeader"] = $this->blockHeader->toArray();
        return $array;
    }
}