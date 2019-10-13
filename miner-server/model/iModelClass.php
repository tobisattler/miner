<?php
namespace model;

/**
 * Interface for functions, every class of the model has to implement.
 * @author Tobias Sattler
 *
 */
interface iModelClass
{

    public function toJSON();
    public function toArray();
    
}

