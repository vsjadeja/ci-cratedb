<?php

defined('BASEPATH') OR exit('No direct script access allowed');

use Doctrine\Common\ClassLoader,
    Doctrine\ORM\Tools\Setup,
    Doctrine\ORM\EntityManager,
    Doctrine\ORM\Mapping\Driver\AnnotationDriver,
    Doctrine\Common\Annotations\AnnotationReader;

/**
 * Doctrine bootstrap library for CodeIgniter
 *
 * @category    Libraries
 * @author      Virendra Jadeja <virendrajadeja84@gmail.com>
 * @version     1.5
 */
class Doctrine {

    public $em;

    public function __construct() {
        require_once __DIR__ . '/Doctrine/ORM/Tools/Setup.php';
        Setup::registerAutoloadDirectory(__DIR__);

        require_once APPPATH . 'libraries/Doctrine/Common/ClassLoader.php';

        require APPPATH . 'config/database.php';

        $connection_options = array(
            'driverClass' => 'Doctrine\DBAL\Driver\PDOCrate\Driver',
            'user' => 'crate',
            'host' => 'localhost',
            'port' => 4200
        );

        $models_namespace = 'Entity';
        $models_path = APPPATH . 'models';
        $proxies_dir = APPPATH . 'models/Proxies';
        $metadata_paths = array(APPPATH . 'models');
        $extension_dir = APPPATH . 'libraries/Doctrine';
        $dev_mode = false;

        if (ENVIRONMENT == "development"):
            $cache = new Doctrine\Common\Cache\ArrayCache();
        else:
            $cache = new Doctrine\Common\Cache\ArrayCache();
        endif;

        $config = Setup::createAnnotationMetadataConfiguration($metadata_paths, $dev_mode, $proxies_dir, $cache);
        $config->setMetadataCacheImpl($cache);
        $config->setQueryCacheImpl($cache);
        $config->setProxyDir($proxies_dir);
        $driver = new AnnotationDriver(new AnnotationReader());
        $config->setMetadataDriverImpl($driver);

        if (ENVIRONMENT == "development"):
            $config->setAutoGenerateProxyClasses(true);
        else:
            $config->setAutoGenerateProxyClasses(false);
        endif;

        $this->em = EntityManager::create($connection_options, $config);

        $classLoader = new ClassLoader('DoctrineExtensions', $extension_dir);
        $classLoader->register();

        $loader = new ClassLoader($models_namespace, $models_path);
        $loader->register();
    }

    public function serialize($entity) {
        if (is_array($entity)):
            $result = array();
            foreach ($entity as $obj) {
                $result[] = $this->_do_serializeTopLevel($obj);
            }
            return $result;
        else:
            if (get_class($entity) == 'Doctrine\ORM\PersistentCollection'):
                return $this->serialize($entity->getValues());
            else:
                return $this->_do_serializeTopLevel($entity);
            endif;
        endif;
    }

    public function serializeWithFullDepth($entity, $level = 1) {
        if (is_array($entity)):
            $result = array();
            foreach ($entity as $obj) {
                $result[] = $this->_do_serializeWithFullDepth($obj, $level);
            }
            return $result;
        else:
            if (get_class($entity) == 'Doctrine\ORM\PersistentCollection'):
                return $this->serializeWithFullDepth($entity->getValues(), $level);
            else:
                return $this->_do_serializeWithFullDepth($entity, $level);
            endif;
        endif;
    }

    private function _do_serializeWithFullDepth($entity, $level = 1, $cLevel = 0) {
        $data = array();
        try {
            $className = str_replace("DoctrineProxies\\__CG__\\", "", get_class($entity));
            $metaData = $this->em->getClassMetadata($className);
            $hideColumns = $this->getHideColumns();
            $cLevel++;

            foreach ($metaData->fieldMappings as $field => $mapping) {
                if (!in_array($field, $hideColumns)):
                    $method = "get" . ucfirst($field);
                    $data[$field] = call_user_func(array($entity, $method));
                endif;
            }

            foreach ($metaData->associationMappings as $field => $mapping) {
                // get associated objects
                $object = $metaData->reflFields[$field]->getValue($entity);
                if ($object && strpos(get_class($object), "Doctrine\\ORM") === FALSE):
                    if ($cLevel == $level):
                        $data[$field] = $this->_do_serializeTopLevel($object);
                    else:
                        $data[$field] = $this->_do_serializeWithFullDepth($object, $cLevel, $cLevel);
                    endif;
                endif;
            }
        } catch (Exception $ex) {
            log_message("error", $ex->getMessage());
        }

        return $data;
    }

    private function _do_serializeTopLevel($entity) {
        $className = get_class($entity);

        $uow = $this->em->getUnitOfWork();
        $entityPersister = $uow->getEntityPersister($className);
        $classMetadata = $entityPersister->getClassMetadata();
        $hideColumns = $this->getHideColumns();

        $result = array();
        foreach ($uow->getOriginalEntityData($entity) as $field => $value) {

            if (isset($classMetadata->associationMappings[$field])) {
                $assoc = $classMetadata->associationMappings[$field];

                // Only owning side of x-1 associations can have a FK column.
                if (!$assoc['isOwningSide'] || !($assoc['type'] & \Doctrine\ORM\Mapping\ClassMetadata::TO_ONE)) {
                    continue;
                }

                if ($value !== null) {
                    $newValId = $uow->getEntityIdentifier($value);
                }

                $targetClass = $this->em->getClassMetadata($assoc['targetEntity']);
                $owningTable = $entityPersister->getOwningTable($field);

                foreach ($assoc['joinColumns'] as $joinColumn) {
                    $sourceColumn = $joinColumn['name'];
                    $targetColumn = $joinColumn['referencedColumnName'];

                    if ($value === null) {
                        $result[$sourceColumn] = null;
                    } else if ($targetClass->containsForeignIdentifier) {
                        $result[$sourceColumn] = $newValId[$targetClass->getFieldForColumn($targetColumn)];
                    } else {
                        $result[$sourceColumn] = $newValId[$targetClass->fieldNames[$targetColumn]];
                    }
                }
            } elseif (isset($classMetadata->columnNames[$field])) {
                if (!in_array($field, $hideColumns)):
                    $columnName = $classMetadata->columnNames[$field];
                    $result[$columnName] = $value;
                endif;
            }
        }
        return $result;
    }

    private function getHideColumns() {
        return array(
            'password'
        );
    }

    public function generate_models() {
        $this->em->getConnection()->getDatabasePlatform()->registerDoctrineTypeMapping('set', 'string');
        $this->em->getConnection()->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');
        $this->em->getConnection()->getDatabasePlatform()->registerDoctrineTypeMapping('numeric', 'integer');

        $this->em->getConfiguration()
                ->setMetadataDriverImpl(
                        new Doctrine\ORM\Mapping\Driver\DatabaseDriver(
                        $this->em->getConnection()->getSchemaManager()
                        )
        );

        $cmf = new Doctrine\ORM\Tools\DisconnectedClassMetadataFactory();
        $cmf->setEntityManager($this->em);
        $metadata = $cmf->getAllMetadata();
        $generator = new Doctrine\ORM\Tools\EntityGenerator();

        $generator->setUpdateEntityIfExists(false);
        $generator->setAddPrefixForClass(true);
        $generator->setGenerateStubMethods(true);
        $generator->setGenerateAnnotations(true);
        $generator->generate($metadata, APPPATH . "models/Entity");
    }

}
