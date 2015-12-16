<?php
namespace Recognize\ApiBundle\Validator;

use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\HttpFoundation\Request;

class JsonApiSchemaValidator extends AbstractJsonSchemaValidator {

    protected $definitions = array();

    public function __construct( $config ){
        parent::__construct( $config );

        // Initialize the jsonapi definitions
        $jsonapi = json_decode( file_get_contents( realpath( dirname( __DIR__ ) . "/Resources/schemas/jsonapi.json" ) ) );
        $this->jsonapi = $jsonapi->jsonapi;

        if( array_key_exists( "definitions", $config) ){
            $this->addCustomDefinitions( $config );
        }
    }

    /**
     * Check if the data sent from a request is valid
     *
     * @param Request $request
     * @param null $schema_uri
     * @return bool
     */
    public function isValidJsonApiRequest( Request $request, $schema_uri = null ){
        $data = json_decode( $request->getContent() );

        $retriever = new \JsonSchema\Uri\UriRetriever();
        $request_schema_path = realpath( dirname( __DIR__ ) . "/Resources/schemas/request.json");
        $baseschema = $retriever->retrieve('file://' . $request_schema_path );

        $valid = false;
        if( $this->dataPassesSchema( $data, $baseschema ) ){
            $valid = true;

            if( $schema_uri !== null ){
                $valid = $this->dataPassesSchemaFromUri( $data, $schema_uri );
            }
        }

        return $valid;
    }

    /**
     * Check if the data is a valid JSONAPI ( v1.0 ) response
     *
     * @param \stdClass $data
     * @param null $schema_uri
     * @return bool
     */
    public function isValidJsonApiResponse( $data, $schema_uri = null ){

        if( $schema_uri !== null ) {
            $valid = $this->dataPassesSchemaFromUri( $data, $schema_uri );

        } else {
            $retriever = new \JsonSchema\Uri\UriRetriever();
            $request_schema_path = realpath( dirname( __DIR__ ) . "/Resources/schemas/response.json");
            $baseschema = $retriever->retrieve('file://' . $request_schema_path );

            $valid = $this->dataPassesSchema( $data, $baseschema );
        }

        return $valid;
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
    protected function connectDefinitions(\stdClass $schema){
        $schema->jsonapi = $this->jsonapi;

        // Add the definitions defined in the configuration
        foreach( $this->definitions as $definition ){
            $schema->{ $definition['property'] } = $definition['contents'];
        }

        return $schema;
    }

    /**
     * Save the custom JSONSchema definitions in memory
     * So that they can be added to every json schema that needs to be validated
     *
     * @param $config
     * @throws FileNotFoundException
     */
    protected function addCustomDefinitions( array $config){
        foreach( $config['definitions'] as $definition ){
            $path_to_definitions = $this->schemafolder . "/" . $definition['path'];
            if( file_exists( $path_to_definitions ) !== false ){
                $definition_file = json_decode( file_get_contents( $path_to_definitions ) );
                $this->definitions[] = array( "property" => $definition["property"],
                    "contents" => $definition_file->{ $definition['property'] } );
            } else {
                throw new FileNotFoundException( "JSONSchema definition file: " . $path_to_definitions . " not found!" );
            }
        }
    }
}