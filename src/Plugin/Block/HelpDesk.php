<?php

namespace Drupal\robco_rest\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'Help Desk' Block.
 *
 * @Block(
 *   id = "help_desk_block",
 *   admin_label = @Translation("Help desk block"),
 *   category = @Translation("Help Desk"),
 * )
 */
class HelpDesk extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $tag = '<span>
        <div id="helpdesk_widget"></div>
        <script type="text/javascript" src="https://office.robco.pl/helpdesk_widget/widget.js"></script>
      	</span>';
    
    	
    return [
      '#markup' => $tag,
      '#allowed_tags' => ['script', 'div', 'span'],
    ];
  } 
}
