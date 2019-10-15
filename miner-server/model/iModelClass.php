<?php
namespace model;

/**
 * Interface for functions, every class of the model has to implement.
 * @author Tobias Sattler
 *
 */
interface iModelClass
{

    /**
     * returns the object as json-string
     */
    public function toJSON();
    
    /**
     * returns the object as array
     */
    public function toArray();
    
}

