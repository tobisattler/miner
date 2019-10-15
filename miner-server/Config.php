<?php

/**
 * Global configurations for the server are set here.
 * @author Tobias Sattler
 *
 */
class Config
{
    /**
     * hostname of the mariadb/mysql database server.
     * @var string
     */
    const DB_SERVER = 'localhost';
    
    /**
     * database that should be connected to on the database server.
     * @var string
     */
    const DB_DATABASE = '';
    
    /**
     * username of the database user
     * @var string
     */
    const DB_USER = '';
    
    /**
     * password of the database user
     * @var string
     */
    const DB_PASS = '';
    
    
    /**
     * database table name for the table holding the clients
     * @var string
     */
    const TABLE_CLIENTS = 'clients';
    
    /**
     * database table name for the table holding the work jobs for the miners
     * @var string
     */
    const TABLE_JOBS = 'jobs';
    
    /**
     * database table name for the generated puzzles
     * @var string
     */
    const TABLE_PUZZLES = 'puzzles';
    
    /**
     * database table name for valid solutions of puzzles, generated by the miners
     * @var string
     */
    const TABLE_SOLUTIONS = 'solutions';
    
    
    /**
     * defines how many nonces are being calculated. The set value will split up the nonce in 1024 jobs
     * @var integer
     */
    const NONCES_PER_JOB = 4194304;
    
    /**
     * the maximum value, the nonce-field can have. This is the max. value of an unsigned Int32
     * @var integer
     */
    const NONCE_MAX_VALUE = 4294967295;
}

