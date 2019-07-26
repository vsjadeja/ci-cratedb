<?php

defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Contains common functionality for CRUD Operation
 *
 * @final MY_Model
 * @category models 
 * @author Virendra Jadeja
 */
class MY_Model extends CI_Model {

    /**
     * @var \Doctrine\ORM\EntityManager $em
     */
    var $em;
    var $entity;

    /**
     *
     * @param int $id
     * @return Entity\<EntityClass>
     */
    function __construct() {
        parent::__construct();
        $this->load->driver('cache', array('adapter' => 'dummy', 'backup' => 'file', 'key_prefix' => 'ts_'));
        //$this->cache->clean();
    }

    /**
     * Initialize with entity name and entity manager
     * @param Entity\<EntityClass> $entity Docrine Entity object
     * @param \Doctrine\ORM\EntityManager $em Doctrine Entity Manager
     */
    function init($entity, $em) {
        $this->entity = $entity;
        $this->em = $em;
    }

    /**
     * Retrieve a single record according to given identifer
     * @param type $id identifier of the record
     * @return type 
     */
    function get($id) {
        try {
            $entity = $this->em->find($this->entity, $id);
            return $entity;
        } catch (Exception $err) {
            log_message("error", $err->getMessage());
            return NULL;
        }
    }

    /**
     * Return list of recors according to given start index and length
     * @param int $start the start index number for the city list
     * @param int $length Determines how many records to fetch
     * @return mix 
     */
    function get_by_range($start = 1, $length = 10, $criteria = array(), $orderBy = NULL, $status = 'all') {
        try {
            $updatedCriteria = $this->addStatusToCriteria($criteria, $status);
            return $this->em->getRepository($this->entity)->findBy($updatedCriteria, $orderBy, $length, $start);
        } catch (Exception $err) {
            log_message("error", $err->getMessage());
            return NULL;
        }
    }

    /**
     * Return the number of records
     * @return integer 
     */
    function get_count($status = 'all') {
        try {
            $qb = $this->em->createQueryBuilder();
            $qb->select("count(e)");
            $qb->from($this->entity, "e");
            switch ($status) {
                case 'all':
                    break;
                default:
                    $qb->where('e.status = :STATUS')->setParameter(":STATUS", $status);
                    break;
            }
            $query = $qb->getQuery();
            return $query->getSingleScalarResult();
        } catch (Exception $err) {
            log_message("error", $err->getMessage());
            return 0;
        }
    }

    /**
     * Save an enitity(insert for new one)
     * @param object $entity Docrine Entity object
     * @return boolean 
     */
    function save($entity) {
        try {
            if (method_exists($entity, 'setIsDeleted') && method_exists($entity, 'getIsDeleted')):
                $isDeleted = $entity->getIsDeleted();
                if (is_null($isDeleted)):
                    $entity->setIsDeleted(FALSE);
                endif;
            endif;
            $this->em->persist($entity);
            $this->em->flush();
            return true;
        } catch (Exception $err) {
            log_message("error", $err->getMessage());
            return FALSE;
        }
    }

    /**
     * Delete an Entity according to given (list of) id(s)
     * @param type $ids array/single
     * @return boolean
     */
    function delete($ids) {
        try {
            if (!is_array($ids)) {
                $ids = array($ids);
            }
            foreach ($ids as $id) {
                $entity = $this->em->getPartialReference($this->entity, $id);
                $this->em->remove($entity);
            }
            $this->em->flush();
            return TRUE;
        } catch (Exception $err) {
            log_message("error", $err->getMessage());
            return FALSE;
        }
    }

    /**
     * Activate an Entity according to given (list of) id(s)
     * @param type $ids array/single
     * @return boolean
     */
    function activate($ids) {
        try {
            if (!is_array($ids)) {
                $ids = array($ids);
            }
            foreach ($ids as $id) {
                $entity = $this->em->find($this->entity, $id);
                $entity->setStatus(TRUE);
                $this->em->persist($entity);
            }
            $this->em->flush();
            return true;
        } catch (Exception $err) {
            log_message("error", $err->getMessage());
            return FALSE;
        }
    }

    /**
     * Deativate an Entity according to given (list of) id(s)
     * @param type $ids array/single
     * @return boolean
     */
    function deactivate($ids) {
        try {
            if (!is_array($ids)) {
                $ids = array($ids);
            }
            foreach ($ids as $id) {
                $entity = $this->em->find($this->entity, $id);
                $entity->setStatus(FALSE);
                $this->em->persist($entity);
            }
            $this->em->flush();
            return true;
        } catch (Exception $err) {
            log_message("error", $err->getMessage());
            return FALSE;
        }
    }

    /**
     * markDelete an Entity according to given (list of) id(s)
     * @param type $ids array/single
     * @return boolean
     */
    function markDelete($ids) {
        try {
            if (!is_array($ids)) {
                $ids = array($ids);
            }
            foreach ($ids as $id) {
                $entity = $this->em->find($this->entity, $id);
                $entity->setIsDeleted(TRUE);
                $this->em->persist($entity);
            }
            $this->em->flush();
            return true;
        } catch (Exception $err) {
            log_message("error", $err->getMessage());
            return FALSE;
        }
    }

    /**
     * Get Existing record according to given criteria
     * @param array $criteria optional
     * @param array $orderBy optional
     * @return entity object array
     */
    function getBy($criteria = array(), $orderBy = NULL, $status = TRUE) {
        try {
            $updatedCriteria = $this->addStatusToCriteria($criteria, $status);
            return $this->em->getRepository($this->entity)->findBy($updatedCriteria, $orderBy);
        } catch (Exception $err) {
            log_message("error", $err->getMessage());
            return NULL;
        }
    }

    /**
     * Get Existing single record according to given criteria
     * @param array $criteria optional
     * @param array $orderBy optional
     * @return entity object
     */
    function getOneBy($criteria = array(), $orderBy = NULL, $status = 'all') {
        try {
            $updatedCriteria = $this->addStatusToCriteria($criteria, $status);
            return $this->em->getRepository($this->entity)->findOneBy($updatedCriteria, $orderBy);
        } catch (Exception $err) {
            log_message("error", $err->getMessage());
            return NULL;
        }
    }

    /**
     * Get All the records of entity.
     * @return array of entity objects
     */
    function getAll() {
        return $this->getBy();
    }

    /**
     * Add status filter to Criteria.
     * @param Mix $criteria
     * @param string $status Options: all, true, false
     */
    private function addStatusToCriteria($criteria, $status = TRUE) {
        $status = $status . "";
        switch ($status) {
            case 'all':
                $updatedCriteria = $criteria;
                break;
            case '1':
            case '0':
                $updatedCriteria = array_merge($criteria, array("status" => (bool) $status));
                break;
            default:
                $updatedCriteria = $criteria;
                break;
        }
        return $updatedCriteria;
    }

    /**
     * Get Cache Variable name
     * @return string $cacheVar
     */
    function getCacheVar($method, $args = null) {
        $cacheVar = get_called_class();
        if ($method):
            $cacheVar .= "_" . $method;
        endif;
        if ($args):
            if (is_array($args)):
                foreach ($args as $k => $v):
                    if (is_array($v)):
                        $this->getCacheVar($method, $v);
                    elseif (is_object($v)):
                        $this->getCacheVar($method, $v->getId());
                    else:
                        $cacheVar .= "_" . $k . "_" . $v;
                    endif;

                endforeach;
            else:
                $cacheVar .= "_" . $args;
            endif;
        endif;
        return $cacheVar;
    }

    /**
     * @param Aws\S3\S3Client $awsObj
     * @param string $url
     * @param integer $age age in seconds
     * @param string $bucket
     * @return string
     */
    public function getAuthenticatedURL($awsObj, $url, $age, $bucket = 'ts-elpl') {
        if ($awsObj instanceof Aws\S3\S3Client):
            $cmd = $awsObj->getCommand('GetObject', [
                'Bucket' => $bucket,
                'Key' => $url
            ]);
            if (!$age || $age <= 20):
                $age = 30;
            endif;
            $request = $awsObj->createPresignedRequest($cmd, '+' . $age . ' seconds');
            return (string) $request->getUri();
        else:
            return $url;
        endif;
    }

}
