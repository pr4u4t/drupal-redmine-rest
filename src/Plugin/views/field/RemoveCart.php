<?php

namespace Drupal\robco_rest\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\ViewExecutable;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Render\ViewsRenderPipelineMarkup;

/**
 * A handler to provide a field that is completely custom by the administrator.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("add_cart")
 */
class RemoveCart extends FieldPluginBase {

  /**
  * {@inheritdoc}
  */
  public function usesGroupBy() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Do nothing -- to override the parent query.
  }
  
  /**
   * {@inheritdoc}
   */
   protected function defineOptions() {
    $options = parent::defineOptions();
    $options['field_radios'] = [ 'default' => [] ];
    return $options;
  }

  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $fields = $this->displayHandler->getFieldLabels(TRUE);
    $pos = array_search('dropdown_list', $fields);
    unset($fields[$pos]);

    $form['field_radios'] = array(
        '#type' => 'radios',
        '#title' => $this->t('Which field contains product ID?'),
        '#default_value' => $this->options['field_radios'],
        '#options' => $fields
    );

    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {

    $value = 0;
    if($this->options['field_radios'] && isset($this->view->field[$this->options['field_radios']])){
        $value = $this->view->field[$this->options['field_radios']]->advancedRender($values);
    }

    $tag = '<a href="#" class="btn btn-primary" onclick=\'(function(){
        console.log("REMOVE CLICKED");
        $.ajax({
         url: "/robco_rest/delCartItem/'.$value.'",
         context: document.body
       }).done(function() {
         console.log("REMOVE FINISHED");
     });
     })();\'>Remove from cart</a>';

    return [
        '#type' => 'inline_template',
        '#template' => $tag
    ];
  }
}
