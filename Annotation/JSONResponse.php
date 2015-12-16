<?php
namespace Recognize\ApiBundle\Annotation;

use Doctrine\Common\Annotations\Annotation;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ConfigurationAnnotation;

/**
 * Class JSONResponse
 *
 * Simple JSON response that transforms the data returned from a controller into a JsonResponse
 *
 * @package Recognize\ApiBundle\Annotation
 * @author Willem Slaghekke <w.slaghekke@recognize.nl>
 * @author Kevin te Raa <k.teraa@recognize.nl>
 *
 * @Annotation
 */
class JSONResponse extends ConfigurationAnnotation {

    public function getAliasName() {
        return "jsonresponse";
    }

    public function allowArray() {
        return false;
    }
}