<?php

/**

* Implements hook_token_info().

*/

function robco_rest_token_info() {

    return [
        'types' => [
            'robco_rest' => [
                'name'          => t('RobCo REST'),
                'description'   => t('Tokens for custom things.'),
            ]
        ],

        'tokens' => [
            'robco_rest' => [
                'deal_id' => [
                    'name'          => t('Deal ID'),
                    'description'   => t('Actual user redmine deal ID if exists')
                    //'dynamic'       => (TRUE|FALSE)
                ],
                'cart_id' => [
                    'name'          => t('Cart ID'), 
                    'description'   => t('Actual user ')
                ]
            ]
        ]
    ];
}

/**

* Implements hook_tokens().

*/

function robco_rest_tokens($type, $tokens, array $data, array $options, \Drupal\Core\Render\BubbleableMetadata $bubbleable_metadata) {

    $replacements = [];

    if ( $type != 'robco_rest' ) {
        return $replacements;
    }
   
    if(!($tempstore = \Drupal::service('tempstore.private')->get('redmine_commerce'))){
        return null;
    }
	
    foreach ($tokens as $name => $original) {
        switch ($name) {
        
            case 'deal_id':
                $replacements[$original] = $tempstore->get('deal_id');
                break;
                
            case 'cart_id':
                $replacements[$original] = $tempstore->get('cart_id');;
                break;
           }
       }
   }

   return $replacements;

}
