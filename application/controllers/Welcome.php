<?php

defined('BASEPATH') OR exit('No direct script access allowed');

use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\MapType;
use Doctrine\DBAL\Schema\CrateSchemaManager;

class Welcome extends CI_Controller {

    public function index() {
//        $table = new Table('test_table');
//        $objDefinition = array(
//            'type' => MapType::STRICT,
//            'fields' => array(
//                new Column('id', Type::getType('integer'), array()),
//                new Column('name', Type::getType('string'), array()),
//            ),
//        );
//        $table->addColumn(
//                'object_column', MapType::NAME, array('platformOptions' => $objDefinition));
//        $schemaManager =  new CrateSchemaManager($this->doctrine->em->getConnection());
//        $schemaManager->createTable($table);
        $this->load->view('welcome_message');
        $this->load->model('Virendra');
        $v = new Entity\Virendra();
        $v->setId(1);
        $v->setName("Virendra Jadeja");
        try {
            $this->Virendra->save($v);
        } catch (Exception $exc) {
            echo $exc->getTraceAsString();
        }
        }

}
