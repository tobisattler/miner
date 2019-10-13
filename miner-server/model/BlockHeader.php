<?php
namespace model;

/**
 * Data model for BlockHeader
 * @author Tobias Sattler
 *
 */
class BlockHeader implements iModelClass {
    private $version;
    private $prevBlockHash;
    private $merkleRoot;
    private $timestamp;
    private $difficultyTarget;
    private $nbits;
    private $nonce;
    
    public function __construct($version, $prevBlockHash, $merkleRoot, $timestamp, $difficultyTarget, $nbits, $nonce) {
        $this->version = $version;
        $this->prevBlockHash = $prevBlockHash;
        $this->merkleRoot = $merkleRoot;
        $this->timestamp = $timestamp;
        $this->merkleRoot = $merkleRoot;
        $this->timestamp = $timestamp;
        $this->difficultyTarget = $difficultyTarget;
        $this->nbits = $nbits;
        $this->nonce = $nonce;
    }
    
    /**
     * Returns the BlockHeader Object as JSON string
     * @return string
     */
    public function toJSON() {
        $array = $this->toArray();
        return json_encode($array);
    }
    
    /**
     * Returns the BlockHeader Object as array
     * @return array
     */
    public function toArray() {
        $array = get_object_vars($this);
        $array["prevBlockHash"] = bin2hex($this->prevBlockHash);
        $array["merkleRoot"] = bin2hex($this->merkleRoot);
        return $array;
    }
}