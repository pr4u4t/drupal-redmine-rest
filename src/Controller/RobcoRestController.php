<?php

use Drupal\Component\Utility\Html;

/**
 * @file
 * Contains \Drupal\robco_rest\Controller\RobcoRestController.
 */

namespace Drupal\robco_rest\Controller;



class RobcoRestController extends ControllerBase {
  public function command($command, Request $request) {
    // Default settings.
    $config = \Drupal::config('robco_rest.settings');
    
    $host = $config->get('robco_rest.host');
    $apiKey = $config->get('robco_rest.api_key');
  
    $handler = new XsltHandler($apiKey,$host);
    $handler->handle($_SERVER['REQUEST_METHOD']);
  
    
  
  
    return array(
      '#type' => 'markup',
      '#markup' => t('Hello, World!'),
    );
  }
  
  public function command_with_sub($command, $sub, Request $request) {
  
  }
  
  public function image($image, Request $request){
  
  }
  
}
