<?php

use model\BlockHeader;
use model\MiningJob;

require_once 'model/iModelClass.php';
require_once 'model/BlockHeader.php';
require_once 'model/MiningJob.php';

/**
 * Controller for the work endpoint of the Api
 * @author Tobias Sattler
 *
 */
class WorkController extends ApiController {
    public function getWork() {
        $blockHeader = new BlockHeader(
            2,
            hex2bin("00000000000008a3a41b85b8b29ad444def299fee21793cd8b9e567eab02cd81"),
            hex2bin("2b12fcf1b09288fcaff797d71e950e71ae42b91e8bdb2304758dfcffc2b620e3"),
            time(),
            17,
            2504433986);
        
        $miningJob = new MiningJob(2, 5, $blockHeader);
        
        print_r($miningJob->toJSON);
        $this->sendResponse($miningJob->toJSON());
    }
}