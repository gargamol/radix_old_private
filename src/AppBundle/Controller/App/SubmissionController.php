<?php

namespace AppBundle\Controller\App;

use AppBundle\Exception\HttpFriendlyException;
use AppBundle\Utility\ModelUtility;
use AppBundle\Utility\HelperUtility;
use AppBundle\Utility\RequestPayload;
use As3\Modlr\Models\Model;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class SubmissionController extends AbstractAppController
{
    /**
     * Processes a submission and returns the next template result.
     *
     * @todo    Need to determine if the manager should return the result or have the controller do it.
     * @todo    Likely the response handling should be done here. Also need to determine what should happen if there isn't a next step/template.
     * @param   string  $sourceKey
     * @param   Request $request
     * @return  JsonResponse
     */
    public function indexAction($sourceKey, Request $request)
    {
var_dump(__method__);
var_dump(__method__. ' - '.$sourceKey);
//var_dump($request);
var_dump(__method__.' - get app_bundle.submission.manager');
        $manager = $this->get('app_bundle.submission.manager');
//var_dump($manager);
var_dump(__method__.' - got it now, set the payload up');
        $payload = RequestPayload::createFrom($request);
//var_dump($payload);
var_dump(__method__.' - payload done now process it all');

        return $manager->processFor($sourceKey, $payload);
    }
}
