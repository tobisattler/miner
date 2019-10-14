<?php

class Config
{
    const DB_SERVER = 'localhost';
    const DB_DATABASE = '';
    const DB_USER = '';
    const DB_PASS = '';
    
    const TABLE_CLIENTS = 'clients';
    const TABLE_JOBS = 'jobs';
    const TABLE_PUZZLES = 'puzzles';
    const TABLE_SOLUTIONS = 'solutions';
    
    // defines how many nonces are being calculated. The set value will split up the nonce in 1024 jobs
    const NONCES_PER_JOB = 4194304;
    
    // the maximum value, the nonce-field can have. This is the max. value of an unsigned Int32
    const NONCE_MAX_VALUE = 4294967295;
}

