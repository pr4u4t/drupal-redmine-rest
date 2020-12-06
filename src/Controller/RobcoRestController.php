<?php

use Drupal\Component\Utility\Html;

/**
 * @file
 * Contains \Drupal\robco_rest\Controller\RobcoRestController.
 */

namespace Drupal\robco_rest\Controller;

class RobcoRestController extends ControllerBase {
  
  /* -- POST handler -- */
  public function command($command, Request $request) {
    return $this->handle($request, $command);
  }
  
  /* -- GET handler -- */
  public function command_with_args($command, $arg1, $arg2, $arg3, $arg4, Request $request) {
    
    $args = array(
        $arg1,
        $arg2,
        $arg3,
        $arg4
    );
    
    return $this->handle($request, $command, $args);
  }
  
  /* -- Handle all type of request -- */
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
  
    $handler = new XsltHandler($xsltOpts);
    $ret = $handler->handle($command,$args);
  
    return new Response($ret['content'], $ret['status'], $ret['content_type']);
  }
}
