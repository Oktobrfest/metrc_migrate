<?php

namespace Drupal\metrc\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class ApplicationSettings.
 *
 * @package Drupal\metrc\Form
 */
class ApplicationSettings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'metrc_application_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['metrc.application_settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('metrc.application_settings');

    $instructions = 'Provide Metrcs Connection Information';

    $form['instructions'] = [
      '#markup' => $instructions,
    ];

    $form['vendor_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Vendor Key'),
      '#description' => $this->t('The Vendor Key.'),
      '#default_value' => $config->get('vendor_key'),
    ];

    $form['user_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('User Key'),
      '#description' => $this->t('The User Key.'),
      '#default_value' => $config->get('user_key'),
    ];

    $form['base_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('The base url for the api'),
      '#description' => $this->t('Base url for the api.  Development: https://sandbox-api-co.metrc.com'),
      '#default_value' => $config->get('base_url'),
    ];

    $form['license_numbers'] = [
      '#type' => 'textarea',
      '#title' => $this->t('License Numbers'),
      '#description' => $this->t('Add multiple license number. One license per line'),
      '#default_value' => $config->get('license_numbers'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('metrc.application_settings')
      ->set('vendor_key', $form_state->getValue('vendor_key'))
      ->set('user_key', $form_state->getValue('user_key'))
      ->set('base_url', $form_state->getValue('base_url'))
      ->set('license_numbers', $form_state->getValue('license_numbers'))
      ->save();

    parent::submitForm($form, $form_state);
  }
}
