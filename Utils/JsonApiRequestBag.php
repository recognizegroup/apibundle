<?php
namespace Recognize\ApiBundle\Utils;

use Symfony\Component\DependencyInjection\Exception\LogicException;
use Symfony\Component\DependencyInjection\Exception\ParameterNotFoundException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class JsonApiRequestBag
 * Simple wrapper for JSON Api validation
 *
 * @author Kevin te Raa <k.teraa@recognize.nl>
 * @package Recognize\ApiBundle\Utils
 */
class JsonApiRequestBag {

    protected $data_object;

    public function __construct( Request $request ){
        $this->data_object = $request->request->get("data", null);
    }

    /**
     * Return the type of the request, or false if it does not exist
     *
     * @return string
     */
    public function getType(){
        $type_exists = $this->data_object !== null
            && array_key_exists( "type", $this->data_object);

        return $type_exists ? $this->data_object['type'] : false;
    }

    /**
     * Return the id of the request, or false if it does not exist
     *
     * @return string|bool
     */
    public function getId(){
        $id_exists = $this->data_object !== null
            && array_key_exists( "id", $this->data_object);

        return $id_exists ? $this->data_object['id'] : false;
    }

    /**
     * Check if an attribute exists in the data object
     *
     * @param string $attribute
     * @return bool
     */
    public function hasAttribute( $attribute ){
        return $this->data_object !== null
            && array_key_exists( "attributes", $this->data_object)
            && array_key_exists( $attribute, $this->data_object['attributes'] );
    }

    /**
     * Get the attribute value if it exists, or NULL if it does not exist
     *
     * @param string $attribute
     * @return mixed
     */
    public function getAttribute( $attribute, $default_value = null ){
        return $this->hasAttribute( $attribute ) ? $this->data_object['attributes'][ $attribute ] : $default_value;
    }

    /**
     * Check if a relationship exists in the data object
     *
     * @param $relationship
     * @return bool
     */
    public function hasRelationship( $relationship ){
        return $this->data_object !== null
            && array_key_exists( "relationships", $this->data_object)
            && array_key_exists( $relationship, $this->data_object['relationships'] );
    }

    /**
     * Get all the identifiers in the relationship value if it exists, or an empty array if it does not exist
     *
     * @param string $relationship
     * @param string $enforced_type                     Optional - If set, the relationship type is checked for equality
     * @return array
     */
    public function getRelationshipIds( $relationship, $enforced_type = "" ){
        $ids = array();

        if( $this->hasRelationship( $relationship) ){

            // Allow single and multiple resources
            $relationships = $this->data_object['relationships'][ $relationship ];
            if( array_key_exists( "type", $this->data_object['relationships'][ $relationship ]) ){
                $relationships = array( $this->data_object['relationships'][ $relationship ]);
            }

            foreach( $relationships as $resource_identifier ){
                if( $enforced_type !== "" ){
                    if( $resource_identifier['type'] === $enforced_type ){
                        $ids[] = $resource_identifier['id'];
                    }
                } else {
                    $ids[] = $resource_identifier['id'];
                }
            }
        }

        return $ids;
    }
}