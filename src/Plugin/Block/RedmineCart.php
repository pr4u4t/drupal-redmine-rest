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
    $tag = '
    <ul class="menu menu--cart nav navbar-nav">
        <li class="expanded dropdown first last">
            <a href="/" class="dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
                <i class="fas fa-shopping-cart"></i>
                <span class="redmine-cart-label"> Cart </span>
                <span id="redmine-cart-item-count">0</span>
                <span class="caret"></span>
            </a>
            <ul id="redmine-cart" data-region="navigation_collapsible" class="dropdown-menu">
                 <li class="first last">
                    <a style="pointer-events: none;">Your cart is empty.</a>
                 </li>
            </ul>
        </li>
    </ul>
    <script type="text/javascript"> 
        console.log("GET CART");
        jQuery.ajax({
         url: "/robco_rest/showCart",
         context: document.body,
         method: "POST"
       }).done(function(data) {
         console.log(data);
     });
    </script>';
    
    	
    return [
      '#markup' => $tag,
      '#allowed_tags' => ['script', 'div', 'span', 'ul', 'li', 'a', 'i'],
    ];
  } 
}
