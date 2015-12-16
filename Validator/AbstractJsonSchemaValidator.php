<?php
namespace Recognize\ApiBundle\Validator;

use Symfony\Component\HttpFoundation\Request;

abstract class AbstractJsonSchemaValidator {

    protected $jsonapi = null;

    protected $validator;
    protected $schema;

    /**
     * @param $apiconfig
     */
    public function __construct( $apiconfig ){
        $this->schemafolder = $apiconfig['schema_directory'];
    }

    /**
     * Add JSON schema definitions in this method
     *
     * Because the reference resolver requires an open URL to properly retrieve the definitions,
     * We have to mimic the connecting of references by adding properties to the schema object
     *
     * For example: $schema->definitions = {"id": 1}
     * To allow the schema object to resolve the #/definitions/id reference
     *
     * @param \stdClass $schema
     * @return \stdClass $schema
     */
    abstract protected function connectDefinitions( \stdClass $schema );

    /**
     *
     *
     * @param $data
     * @param $schema_uri
     * @return bool
     */
    public function dataPassesSchemaFromUri( $data, $schema_uri ){
        $retriever = new \JsonSchema\Uri\UriRetriever();
        $schema_path = realpath( $this->schemafolder . "/" . $schema_uri );
        $schema = $retriever->retrieve('file://' . $schema_path );

        return $this->dataPassesSchema( $data, $schema );
    }

    /**
     * Tests if the data passes the schema object given
     *
     * @param $data
     * @param $schema
     * @return bool
     */
    protected function dataPassesSchema( $data, $json_schema ){

        // Make sure the schema references can be resolved
        $json_schema = $this->connectDefinitions( $json_schema );

        // Retrieve and fill in the schema references
        $refResolver = new \JsonSchema\RefResolver( new \JsonSchema\Uri\UriRetriever() );
        $refResolver::$maxDepth = 200;
        $refResolver->resolve($json_schema, null );


        // Validate the returned controller data with the schema
        $this->validator = new \JsonSchema\Validator();
        $this->validator->check( $data, $json_schema );

        return $this->validator->isValid();
    }

    /**
     * Return the errors of the last validated schema
     *
     * @return array
     */
    public function getErrors( ){
        return $this->validator instanceof \JsonSchema\Validator ? $this->validator->getErrors() : array();
    }

}