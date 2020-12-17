<?php

/**
* Implements hook_views_data().
*/
function robco_rest_views_data() {

    $data['views']['table']['group'] = t('Custom Global');
    $data['views']['table']['join'] = [
      // #global is a special flag which allows a table to appear all the time.
      '#global' => [],
    ];


    $data['views']['robco_rest']['add_cart'] = [
        'title' => t('Add to cart field'),
        'help' => t('Button for adding product to redmine cart'),
        'field' => [
            'id' => 'add_cart',
        ]
    ];

    $data['views']['robco_rest']['remove_cart'] = [ 
        'title' => t('Remove from cart field'),
        'help' => t('Button for removing product to redmine cart'),
        'field' => [
            'id' => 'remove_cart',
        ]   
    ];

    return $data;
}
