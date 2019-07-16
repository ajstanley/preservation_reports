<?php

namespace Drupal\preservation_reports\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class AdminForm.
 */
class AdminForm extends ConfigFormBase {


  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'preservation_reports_admin_form';
  }

  protected function getEditableConfigNames() {
    return [
      'preservation_reports.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('preservation_reports.settings');
    $default_endpoint = $config->get('sparql_endpoint') ? $config->get('sparql_endpoint') : 'http://localhost:8080/bigdata/namespace/islandora/sparql';
    $form['sparql_endpoint'] = [
      '#type' => 'textfield',
      '#title' => $this->t('SPARQL endpoint'),
      '#description' => $this->t('URL to which queries will be sent.  
            Address will normally take the form of [PROTOCOL]://[server]:[PORT]/bigdata/namespace/[your namespace (usually Islandora)]/sparql'),
      '#maxlength' => 64,
      '#size' => 120,
      '#default_value' => $default_endpoint,
      '#weight' => '0',
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Display result.
    $values = $form_state->getValues();
    $this->config('preservation_reports.settings')
      ->set('sparql_endpoint', $values['sparql_endpoint'])
      ->save();

  }

}
