<?php

use Drupal\Component\Utility\Html;

/**
 * @file
 * Contains \Drupal\robco_rest\Controller\RobcoRestController.
 */

namespace Drupal\robco_rest\Controller;

class RobcoRestController extends ControllerBase {
  public function command($command, Request $request) {
    return $this->handle($request, $command);
  }
  
  public function command_with_sub($command, $sub, Request $request) {
    return $this->handle($request, $command, $sub);
  }
  
  private function handle(Request $request, $command, $sub = null){
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
    $ret = $handler->handle();
  
    return new Response($ret['content'], $ret['status'], $ret['content_type']);
  }
  
}
