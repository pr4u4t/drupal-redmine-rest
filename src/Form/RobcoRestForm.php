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
    
    // REST endpoint address.
    $form['host'] = [
      '#type' => 'textfield',
      '#title' => $this->t('REST host address:'),
      '#default_value' => $config->get('robco_rest.host'),
      '#description' => $this->t('Set address of REST endpoint.'),
    ];
    
    // REST API key.
    $form['api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API key:'),
      '#default_value' => $config->get('robco_rest.api_key'),
      '#description' => $this->t('Set API key for REST endpoint'),
    ];

    // Commerce project ID.
    $form['commerce_project_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Commerce project ID:'),
      '#default_value' => $config->get('robco_rest.commerce_project_id'),
      '#description' => $this->t('Redmine e-commerce project ID'),
    ];
    
    // Commerce project ID.
    $form['anonymous_user_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Anonymous user ID:'),
      '#default_value' => $config->get('robco_rest.anonymous_user_id'),
      '#description' => $this->t('Redmine e-commerce anonymous user ID used to store anonymous cart'),
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
    $config->set('robco_rest.commerce_project_id', $form_state->getValue('commerce_project_id'));
    $config->set('robco_rest.anonymous_user_id', $form_state->getValue('anonymous_user_id'));
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
