<?php

namespace Recognize\ApiBundle\EventListener;

use Doctrine\ORM\EntityManager;
use PHPUnit_Framework_TestCase;
use Recognize\ApiBundle\Tests\Mocks\MockEntityManager;
use Recognize\ApiBundle\Tests\Mocks\MockPrimaryEntity;
use Recognize\ApiBundle\Utils\JsonApiQueryBuilder;
use Recognize\ApiBundle\Utils\JsonApiQueryParser;
use Recognize\ApiBundle\Utils\JsonApiRequestBag;
use Recognize\ApiBundle\Validator\JsonApiSchemaValidator;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;

class JsonApiQueryBuilderTest extends PHPUnit_Framework_TestCase
{

    public function testSortQuery(){
        $qb = $this->getQueryBuilder();

        $dqb = $qb->generateDoctrineQueryBuilder( array(
                "sort" => array(
                    "field1" => "ASC",
                    "field2" => "DESC"
                )
            ), array( "field1", "field2" )
        );

        $this->assertEquals(
            "SELECT entity FROM Recognize\ApiBundle\Tests\Mocks\MockPrimaryEntity entity "
            . "ORDER BY entity.field1 ASC, entity.field2 DESC", $dqb->getDQL()
        );
    }

    public function testEqualsQuery(){
        $qb = $this->getQueryBuilder();

        $dqb = $qb->generateDoctrineQueryBuilder( array(
                "eq" => array(
                    "field1" => "test",
                )
            ), array( "field1" )
        );

        $this->assertEquals(
            "SELECT entity FROM Recognize\ApiBundle\Tests\Mocks\MockPrimaryEntity entity "
            . "WHERE entity.field1 = :eq_field1", $dqb->getDQL()
        );
    }

    public function testSearchQuery(){
        $qb = $this->getQueryBuilder();

        $dqb = $qb->generateDoctrineQueryBuilder( array(
            "search" => array(
                "field1" => "test",
            )
        ), array( "field1" )
        );

        $this->assertEquals(
            "SELECT entity FROM Recognize\ApiBundle\Tests\Mocks\MockPrimaryEntity entity "
            . "WHERE entity.field1 LIKE :search_field1", $dqb->getDQL()
        );
    }


    public function testInQuery(){
        $qb = $this->getQueryBuilder();

        $dqb = $qb->generateDoctrineQueryBuilder( array(
                "in" => array(
                    "field1" => array("test","lest"),
                )
            ), array( "field1" )
        );

        $this->assertEquals(
            "SELECT entity FROM Recognize\ApiBundle\Tests\Mocks\MockPrimaryEntity entity "
            . "WHERE entity.field1 IN(:in_field1)", $dqb->getDQL()
        );
    }

    public function testRangeQuery(){
        $qb = $this->getQueryBuilder();

        $dqb = $qb->generateDoctrineQueryBuilder( array(
                "range" => array(
                    "field1" => array(0,100),
                )
            ), array( "field1" )
        );

        $this->assertEquals(
            "SELECT entity FROM Recognize\ApiBundle\Tests\Mocks\MockPrimaryEntity entity "
            . "WHERE entity.field1 >= :range_field1_min AND entity.field1 <= :range_field1_max", $dqb->getDQL()
        );
    }

    public function testJoinQuery(){
        $qb = $this->getQueryBuilder();

        $dqb = $qb->generateDoctrineQueryBuilder( array(
                "joins" => array( array("field1", "entity.field1"),
                    array("field1_field2", "entity.field1.field2") )
            ),
            array( "field1", "field1.field2" )
        );

        $this->assertEquals(
            "SELECT entity, field1, field1_field2 FROM Recognize\ApiBundle\Tests\Mocks\MockPrimaryEntity entity "
            . "LEFT JOIN entity.field1 field1 LEFT JOIN entity.field1.field2 field1_field2", $dqb->getDQL()
        );
    }

    public function testNestedJoinQuery(){
        $qb = $this->getQueryBuilder();

        $dqb = $qb->generateDoctrineQueryBuilder( array(
                "joins" => array( array("field1_field2", "entity.field1.field2") ) ),
            array( "field1.field2" )
        );

        $this->assertEquals(
            "SELECT entity, field1_field2 FROM Recognize\ApiBundle\Tests\Mocks\MockPrimaryEntity entity "
            . "LEFT JOIN entity.field1 field1 LEFT JOIN entity.field1.field2 field1_field2", $dqb->getDQL()
        );
    }

    public function testNestedRangeQuery(){
        $qb = $this->getQueryBuilder();

        $dqb = $qb->generateDoctrineQueryBuilder( array(
                "range" => array( "field1.field2.price" => array( 0, 100 ) ) ),
            array( "field1.field2.price" )
        );

        $this->assertEquals(
            "SELECT entity FROM Recognize\ApiBundle\Tests\Mocks\MockPrimaryEntity entity "
            . "LEFT JOIN entity.field1 field1 LEFT JOIN field1.field2 field1_field2 "
            . "WHERE field1_field2.price >= :range_field1_field2_price_min AND"
            . " field1_field2.price <= :range_field1_field2_price_max", $dqb->getDQL()
        );
    }


    protected function getQueryBuilder(){
        return new JsonApiQueryBuilder(
            new MockEntityManager(), new MockPrimaryEntity()
        );
    }

}
