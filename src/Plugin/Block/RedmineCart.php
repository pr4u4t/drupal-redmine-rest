<?php

namespace Drupal\robco_rest\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'Redmine Cart' Block.
 *
 * @Block(
 *   id = "redmine_cart_block",
 *   admin_label = @Translation("Redmine cart block"),
 *   category = @Translation("Redmine Cart"),
 * )
 */
class RedmineCart extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $tag = '<div class="dropdown">
	<button class="btn btn-secondary dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
		<i class="fas fa-shopping-cart"></i>
	</button>
	<div id="redmine-cart" class="dropdown-menu" aria-labelledby="dropdownMenuButton">
    		<script type="text/javascript"> </script>
	</div>
    </div>';
    
    	
    return [
      '#markup' => $tag,
      '#allowed_tags' => ['script', 'div', 'span', 'ul', 'li'],
    ];
  } 
}
