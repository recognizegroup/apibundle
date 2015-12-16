<?php
namespace Recognize\ApiBundle\JsonApi;

use Symfony\Component\HttpKernel\Exception\HttpException;

class JsonApiResponseBody implements \JsonSerializable {

    /** @var ResourceObjectInterface[] */
    protected $resources;

    protected $is_single_resource = false;

    protected $meta = null;

    public function __construct( $resource, $metadata = array() ){
        if( $resource instanceof ResourceObjectInterface ) {
            $this->is_single_resource = true;

            $this->resources = array( $resource );
        } else {
            $this->resources = $resource;
        }

        if( !empty($metadata) ){
            $this->meta = $metadata;
        }
    }

    /**
     * Get the resource identifier objects as defined by the JSON API spec
     *
     * @url http://jsonapi.org/format/#document-resource-identifier-objects
     */
    public function getRelationshipIdentifiers(){
        $relationships = $this->getRelationships();

        if( !empty( $relationships ) ) {
            $rids = array();
            foreach( $relationships as $key => $value ){
                if( is_array( $value ) ){
                    $resource_identifiers = array();
                    foreach( $value as $resource ){
                        $resource_identifiers[] = $this->getResourceIdentifier( $resource );
                    }
                    $rids[ $key ] = array( "data" => $resource_identifiers );
                } elseif( $value instanceof ResourceObjectInterface ){
                    $rids[ $key ] = array( "data" => $this->getResourceIdentifier( $value ) );
                }
            }

            return $rids;
        } else {
            return false;
        }
    }

    /**
     * Get all the included resources from this resource - Including the nested resources
     *
     * @return array
     */
    public function getIncludedResources(){
        $relationships = $this->getRelationships();

        $resources = array();
        if( !empty( $relationships ) ) {
            foreach( $relationships as $key => $value ){
                if( is_array( $value ) ){

                    /** @var ResourceObjectInterface $resource */
                    foreach( $value as $resource ){
                        $response = new JsonApiResponseBody( $resource );
                        $resources[] = $response->getSimpleResourceObject();

                        $nested_resources = $response->getIncludedResources();
                        foreach( $nested_resources as $nested_resource ){
                            $resources[] = $nested_resource;
                        }
                    }

                } elseif( $value instanceof ResourceObjectInterface ){
                    $response = new JsonApiResponseBody( $value );
                    $resources[] = $response->getSimpleResourceObject();

                    $nested_resources = $response->getIncludedResources();
                    foreach( $nested_resources as $nested_resource ){
                        $resources[] = $nested_resource;
                    }
                }
            }
        }

        return $resources;
    }

    /**
     * Specify data which should be serialized to JSON
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4.0
     */
    function jsonSerialize( $with_included_resources = true ) {
        $responsedata = array();

        // Add the optional metadata
        if( $this->meta !== null ){
            $responsedata['meta'] = $this->meta;
        }

        $responsedata["data"] = $this->getSimpleResourceObject();

        if( $with_included_resources == true ){
            $includedResources = $this->getIncludedResources();
            if( !empty( $includedResources ) ){

                // As PHPs implementations array unique doesnt handle nested arrays well,
                // we have to serialize the arrays first before getting the unique values
                $responsedata['included'] =
                    array_map("unserialize", array_unique(array_map("serialize", $includedResources)));
            }
        }

        return $responsedata;
    }

    /**
     * Turn a list of resourceobject into a json api resource objects or just one
     *
     * @return array
     */
    public function getSimpleResourceObject( ){

        $resourceobjects = array();
        foreach( $this->resources as $resource ){
            $data = array(
                "id" => "" . $resource->getId(),
                "type" => $resource->getResourceType(),
                "attributes" => $resource->getAttributes()
            );

            $relationships = $this->getRelationshipIdentifiers();
            if( $relationships !== false ){
                $data['relationships'] = $relationships;
            }

            $resourceobjects[] = $data;
        }

        if( $this->is_single_resource ){
            $resourceobjects = $resourceobjects[0];
        }

        return $resourceobjects;
    }

    /**
     * Turn a resourceobject into its identifier
     *
     * @return array
     */
    public function getResourceIdentifier( ResourceObjectInterface $resource ){
        return array(
            "id" => "" . $resource->getId(),
            "type" => $resource->getResourceType()
        );
    }

    /**
     * @return array
     */
    protected function getRelationships(){
        if( $this->is_single_resource ){
            $relationships = $this->resources[0]->getRelationships();
        } else {
            $relationships  = array();
            foreach( $this->resources as $resource ){
                foreach( $resource->getRelationships() as $key => $relationship ){
                    $relationships[ $key ] = $relationship;
                }
            }
        }

        return $relationships;
    }
}