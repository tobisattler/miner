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
    /**
     * version, as it was set in the original Bitcoin Block Header. Note: this can be higher than the current version number, as some ASIC miners seem to be using this field as extra nonce.
     * @var int
     */
    private $version;
    
    /**
     * SHA-256 hash of the previous Block Header as hex-encoded string
     * @var string
     */
    private $prevBlockHash;
    
    /**
     * SHA-256 hash of the merkle tree (pairwise hashed transaction-ids that are validated with this block). Hex-encoded String
     * @var string
     */
    private $merkleRoot;
    
    /**
     * timestamp of the time, the block was orignally mined
     * @var int
     */
    private $timestamp;
    
    /**
     * defines the amount of leading Bytes with zeros the solution needs to have in order to be accepted. This is a simplified version of what is used in the original Bitcoin protocol.
     * @var int
     */
    private $difficultyTarget;
    
    /**
     * short version of the difficulty, that is being hashed as part of the Block Header while mining
     * @var int
     */
    private $nbits;
    
    /**
     * Creates a new BlockHeader blueprint for calculation by the mining client
     * @param int $version version as in the originating Bitcoin block header
     * @param string $prevBlockHash previous block hash as in the originating Bitcoin Block header
     * @param string $merkleRoot merkle root as in the originating Bitcoin Block header
     * @param int $timestamp timestamp as in the originating Bitcoin Block header
     * @param int $difficultyTarget difficulty target, defining the amount of leading zero bytes needed for the BlockHeader to be accepted
     * @param int $nbits nbits as in the originating Bitcoin Block header
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
     * @return string json_encoded BlockHeader object
     */
    public function toJSON() {
        $array = $this->toArray();
        return json_encode($array);
    }
    
    /**
     * Returns the BlockHeader Object as array
     * @return array BlockHeader object as array
     */
    public function toArray() {
        $array = get_object_vars($this);
        return $array;
    }
}