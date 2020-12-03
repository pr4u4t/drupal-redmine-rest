<?php

namespace Drupal\robco_rest\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class RobcoRestForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'robco_rest_basic_form';
  }
  
  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Form constructor.
    $form = parent::buildForm($form, $form_state);
    // Default settings.
    $config = $this->config('robco_rest.settings');
    
    // Page title field.
    $form['host'] = [
      '#type' => 'textfield',
      '#title' => $this->t('REST host address:'),
      '#default_value' => $config->get('robco_rest.host'),
      '#description' => $this->t('Set address of REST endpoint.'),
    ];
    // Source text field.
    $form['api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API key:'),
      '#default_value' => $config->get('robco_rest.api_key'),
      '#description' => $this->t('Set API key for REST endpoint'),
    ];

    return $form;
  }
  
  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }
  
  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('robco_rest.settings');
    $config->set('robco_rest.host', $form_state->getValue('host'));
    $config->set('robco_rest.api_key', $form_state->getValue('api_key'));
    $config->save();
    return parent::submitForm($form, $form_state);
  }
  
  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'robco_rest.settings',
    ];
  }
}
