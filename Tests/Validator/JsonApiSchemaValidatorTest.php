<?php

/*
 * This file is part of the qandidate/symfony-json-request-transformer package.
 *
 * (c) Qandidate.com <opensource@qandidate.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Recognize\ApiBundle\EventListener;

use PHPUnit_Framework_TestCase;
use Recognize\ApiBundle\Validator\JsonApiSchemaValidator;
use Symfony\Component\HttpFoundation\Request;

class JsonApiSchemaValidatorTest extends PHPUnit_Framework_TestCase
{
    public function testValidJsonApiRequest(){
        $validator = $this->getJsonApiSchemaValidator();

        $valid = $validator->isValidJsonApiRequest( $this->getValidJsonApiRequest() );

        $this->assertEquals( 0, count( $validator->getErrors() ) );
        $this->assertTrue( $valid );
    }

    public function testValidJsonApiRequestWithSchema(){
        $validator = $this->getJsonApiSchemaValidator();

        $valid = $validator->isValidJsonApiRequest( $this->getValidJsonApiRequest(), "testrequest.json" );

        $this->assertEquals( 0, count( $validator->getErrors() ) );
        $this->assertTrue( $valid );
    }

    public function testInvalidJsonApiRequestWithSchema(){
        $validator = $this->getJsonApiSchemaValidator();

        $valid = $validator->isValidJsonApiRequest( $this->getInvalidJsonApiRequest(), "testrequest.json" );

        $this->assertGreaterThan( 0, count( $validator->getErrors() ) );
        $this->assertFalse( $valid );
    }

    public function testValidJsonApiResponse(){
        $validator = $this->getJsonApiSchemaValidator();

        $data = $this->getValidJsonResponse();
        $valid = $validator->isValidJsonApiResponse( json_decode( $data ) );

        $this->assertEquals( 0, count( $validator->getErrors() ) );
        $this->assertTrue( $valid );
    }

    public function testValidJsonApiResponseWithSchema(){
        $validator = $this->getJsonApiSchemaValidator();

        $data = $this->getValidJsonResponse();
        $valid = $validator->isValidJsonApiResponse( json_decode( $data ), "testresponse.json" );

        $this->assertEquals( 0, count( $validator->getErrors() ) );
        $this->assertTrue( $valid );
    }

    public function testInvalidJsonApiResponseWithSchema(){
        $validator = $this->getJsonApiSchemaValidator();

        $data = $this->getInvalidJsonResponse();
        $valid = $validator->isValidJsonApiResponse( json_decode( $data ), "testresponse.json" );

        $this->assertGreaterThan( 0, count( $validator->getErrors() ) );
        $this->assertFalse( $valid );
    }

    /**
     * @expectedException \Symfony\Component\Filesystem\Exception\FileNotFoundException
     */
    public function testInvalidSchemaFile(){
        new JsonApiSchemaValidator( array( "schema_directory" => dirname(__DIR__) . "/schemas/",
            "definitions" => array( array( "path" => "asdf.json", "property" => "fdas") ) ) );

    }

    protected function getValidJsonResponse(){
        return '{
            "data": {
                "id": "1",
                "type": "test",
                "attributes": {
                    "name": "test"
                }
            }
        }';
    }

    protected function getInvalidJsonResponse(){
        return '{
            "data": {
                "id": "1",
                "type": "test",
                "attributes": {
                    "no_attribute": 0
                }
            }
        }';
    }


    protected function getValidJsonApiRequest(){
        $json = '{
            "data": {
                "type": "test",
                "attributes": {
                    "name": "test"
                }
            }
        }';

        return new Request(
            array(),
            array(),
            array(),
            array(),
            array(),
            array(),
            $json
        );
    }

    protected function getInvalidJsonApiRequest(){
        $json = '{
            "data": {
                "type": "test",
                "attributes": {
                    "test": 12
                }
            }
        }';

        return new Request(
            array(),
            array(),
            array(),
            array(),
            array(),
            array(),
            $json
        );

    }


    protected function getJsonApiSchemaValidator(){
        return new JsonApiSchemaValidator( array( "schema_directory" => dirname(__DIR__) . "/schemas/",
            "definitions" => array( array( "path" => "names.json", "property" => "names") ) ) );
    }
}
