<?php
namespace Recognize\ApiBundle\Annotation;

use Doctrine\Common\Annotations\Annotation;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ConfigurationAnnotation;

/**
 * Class JSONAPIResponse
 *
 * A more strict api standard that requires the data sent and the data returned to adhere to
 * the JSON API spec ( version 1.0 )
 *
 * @package Mapxact\ApiBundle\Annotation
 * @author Kevin te Raa <k.teraa@recognize.nl>
 *
 * @Annotation
 */
class JSONAPIResponse extends ConfigurationAnnotation {

    /**
     * Sets the HTTP methods.
     *
     * @param array|string $methods An HTTP method or an array of HTTP methods
     */
    public function setValue( $value )
    {
        if( is_string( $value ) )
            $this->setSchema( $value );
    }


    protected $schema = null;

    public function setSchema($schema) {
        $this->schema = $schema;
    }

    public function getSchema(){
        return $this->schema;
    }

    public function getAliasName() {
        return "jsonapi_response";
    }

    public function allowArray() {
        return false;
    }
}