<?php

/**
 * @file
 * Contains \Drupal\robco_rest\Controller\RobcoRestController.
 */

namespace Drupal\robco_rest\Controller;

use Drupal\Component\Utility\Html;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;
use Drupal\robco_rest\Utils\XsltHandler;
use Symfony\Component\HttpFoundation\Response;

class RobcoRestController extends ControllerBase {
  
  
  public function command($command, Request $request, $arg1 = null, $arg2 = null, $arg3 = null, $arg4 = null) {
    
    $args = array(
        $arg1,
        $arg2,
        $arg3,
        $arg4
    );
    
    return $this->handle($request, $command, $args);
  }
  
  private function handle(Request $request, $command, array $args = array()){
    // Default settings.
    $config = \Drupal::config('robco_rest.settings');
  
    $xsltOpts = array(
        'api_key'               => $config->get('robco_rest.api_key'),
        'host'                  => $config->get('robco_rest.host'),
        'project_id'            => $config->get('robco_rest.commerce_project_id'),
        'default_cart_owner'    => $config->get('robco_rest.anonymous_user_id'),
        'site'                  => $request->getSchemeAndHttpHost(),
        'method'                => $request->getMethod()
    );
  
    if(!($handler = new XsltHandler($xsltOpts))){
        return new Response('Internal server error',500,array('content-type' => 'text/plain'));
    }
    
    if(!($ret = $handler->handle($command,$args)) || !is_array($ret) || !isset($ret['status']) 
        || !isset($ret['content']) || !isset($ret['content_type'])){
        
        if(isset($ret['redirect'])) {
            return $this->redirect($ret['redirect']);
        }
        
        return new Response('Internal server error',500,array('content-type' => 'text/plain'));
    }
    
    $headerBag = array( 'content-type' => $ret['content_type']);
    
    if(isset($ret['content_length']) && $ret['content_length'] > 0){
        $headerBag['content-length'] = $ret['content_length'];
    }
    
    return new Response($ret['content'], $ret['status'], $headerBag);
  }
}
