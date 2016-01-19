<?php
namespace Recognize\ApiBundle\Doctrine;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Recognize\ApiBundle\Utils\JsonApiQueryBuilder;
use Recognize\ApiBundle\Utils\JsonApiQueryParser;
use Symfony\Component\HttpFoundation\Request;

/**
 * Trait JsonApiRepositoryTrait
 *
 * @package Recognize\ApiBundle\Repository
 */
trait JsonApiRepositoryTrait {

    /**
     * A list of field names which can be queried from the outside
     *
     * By default, this is turned on for all fields for security reasons
     * If you want to allow any kind of query on a specific field, simply add the field name
     * as it is defined in doctrine to the array below
     *
     * @var array
     */
    protected $exposed_query_fields = array();


    /**
     * Parse the request to execute a proper DQL expression
     *
     * @param Request $request
     * @param array $exposed_fields                     The exposed fields that the client can query
     * @param array $additional_criteria                Optional additional criteria to add to the query
     * @return mixed
     */
    public function jsonApiQuery( Request $request, $exposed_fields = array(), $additional_criteria = array() ){
        $container = JsonApiQueryParser::parseParameterBag( $request->query );

        // Add the additional criteria - Works like the findBy method
        foreach( $additional_criteria as $field => $value ){
            if( is_array( $value ) ){
                if( array_key_exists("in", $container ) == false ){
                    $container['in'] = array();
                }

                $container['in'][ $field ] = $value;
            } else {
                if( array_key_exists("eq", $container ) == false ){
                    $container['eq'] = array();
                }

                $container['eq'][ $field ] = $value;
            }
        }

        if( empty($exposed_fields ) ){
            $exposed_fields = $this->exposed_query_fields;
        }

        $jqb = new JsonApiQueryBuilder( $this->_em, $this->_entityName );
        $dqb = $jqb->generateDoctrineQueryBuilder( $container, $exposed_fields );

        return $dqb->getQuery()->getResult();
    }

}