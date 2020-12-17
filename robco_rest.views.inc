<?php

/**
* Implements hook_views_data().
*/
function robco_rest_views_data() {

    $data['robco_rest']['table']['group'] = t('Custom Global');
    $data['robco_rest']['table']['join'] = [
      // #global is a special flag which allows a table to appear all the time.
      '#global' => [],
    ];


    $data['robco_rest']['add_cart'] = [
        'title' => t('Add to cart field'),
        'help' => t('Button for adding product to redmine cart'),
        'field' => [ 'id' => 'add_cart' ]
    ];

    $data['robco_rest']['remove_cart'] = [ 
        'title' => t('Remove from cart field'),
        'help' => t('Button for removing product to redmine cart'),
        'field' => [ 'id' => 'remove_cart' ]   
    ];

    return $data;
}
