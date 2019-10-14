<?php
namespace model;

/**
 * Data model for BlockHeader blueprint, that is sent to the mining client for calculation
 * Note: This blueprint for the BlockHeader does not contain the nonce, as the nonce needs to be
 * changed by the miners to solve the puzzle.
 * 
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
    
    /**
     * Creates a new BlockHeader blueprint for calculation by the mining client
     * @param int $version
     * @param string $prevBlockHash
     * @param string $merkleRoot
     * @param int $timestamp
     * @param int $difficultyTarget
     * @param int $nbits
     */
    public function __construct($version, $prevBlockHash, $merkleRoot, $timestamp, $difficultyTarget, $nbits) {
        $this->version = $version;
        $this->prevBlockHash = $prevBlockHash;
        $this->merkleRoot = $merkleRoot;
        $this->timestamp = $timestamp;
        $this->merkleRoot = $merkleRoot;
        $this->timestamp = $timestamp;
        // difficultyTarget defines how many of the leading Bytes need to be zero to be accepted
        $this->difficultyTarget = $difficultyTarget;
        $this->nbits = $nbits;
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
        return $array;
    }
}