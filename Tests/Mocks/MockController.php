<?php
namespace Recognize\ApiBundle\Tests\Mocks;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Recognize\ApiBundle\Annotation\JSONResponse;
use Recognize\ApiBundle\Annotation\JSONAPIResponse;

class MockController extends Controller {

    /**
     * @JSONResponse()
     */
    public function jsonAction(){

    }

    /**
     * @JSONAPIResponse()
     */
    public function schemalessJsonApiAction(){

    }

    /**
     * @JSONAPIResponse( "testresponse.json" )
     */
    public function jsonApiAction(){

    }

}