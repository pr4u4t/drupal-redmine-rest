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
  
  
  public function command($command, Request $request, $arg1, $arg2, $arg3, $arg4) {
    
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
        'api_key'       => $config->get('robco_rest.api_key'),
        'host'          => $config->get('robco_rest.host'),
        'project_id'    => $config->get('robco_rest.commerce_project_id'),
        'site'          => $request->getSchemeAndHttpHost(),
        'method'        => $request->getMethod()
    );
  
    if(!($handler = new XsltHandler($xsltOpts))){
        return new Response('Internal server error',500,array('content-type' => 'text/plain'));
    }
    
    if(!($ret = $handler->handle($command,$args)) || !is_array($ret) || !isset($ret['status']) 
        || !isset($ret['content']) || !isset($ret['content_type'])){
        return new Response('Internal server error',500,array('content-type' => 'text/plain'));
    }
    
    return new Response($ret['content'], $ret['status'], array('content-type' => $ret['content_type']));
  }
}
