<?php

defined('BASEPATH') OR exit('No direct script access allowed');
require_once(APPPATH . "models/Entity/Virendra.php");

/**
 * Description of Virendra
 *
 * @author Virendra Jadeja
 */
class Virendra extends MY_Model {

    public function __construct() {
        parent::__construct();
        $this->init("Entity\Virendra", $this->doctrine->em);
    }

}

?>