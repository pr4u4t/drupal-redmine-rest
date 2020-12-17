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
class AddCart extends FieldPluginBase {

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
    
    $options['alter']['contains']['alter_text'] = ['default' => TRUE];
    $options['hide_alter_empty'] = ['default' => FALSE];
    
    return $options;
  }

  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    // Remove the checkbox
    unset($form['alter']['alter_text']);
    unset($form['alter']['text']['#states']);
    unset($form['alter']['help']['#states']);
    $form['#pre_render'][] = [$this, 'preRenderCustomForm'];
  }
 
  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() {
    $callbacks = parent::trustedCallbacks();
    $callbacks[] = 'preRenderCustomForm';
    return $callbacks;
  }
 
  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $tag = '<a href="#" class="btn btn-primary" onclick="(function(){
        $.ajax({
         url: "/robco_rest/addCartItem/",
         context: document.body
       }).done(function() {
         
     });
     })();">Add to cart</a>';
     
    return $tag;
  }

  /**
   * Prerender function to move the textarea to the top of a form.
   *
   * @param array $form
   *   The form build array.
   *
   * @return array
   *   The modified form build array.
   */
  public function preRenderCustomForm($form) {
    $form['text'] = $form['alter']['text'];
    $form['help'] = $form['alter']['help'];
    unset($form['alter']['text']);
    unset($form['alter']['help']);

    return $form;
  }
  
   /**
   * The current display.
   *
   * @var string
   *   The current display of the view.
   */
  //protected $currentDisplay;

  /**
   * {@inheritdoc}
   */
  //public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
  //  parent::init($view, $display, $options);
  //  $this->currentDisplay = $view->current_display;
  //}
}
