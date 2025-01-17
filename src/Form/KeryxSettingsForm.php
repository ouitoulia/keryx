<?php

namespace Drupal\keryx\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Streaming settings form.
 */
class KeryxSettingsForm extends ConfigFormBase {

  /**
   * Module config.
   *
   * @var string Config settings
   */
  const string SETTINGS = 'keryx.settings';

  /**
   * {@inheritDoc}
   */
  public function getFormId(): string {
    return 'keryx_admin_settings';
  }

  /**
   * {@inheritDoc}
   *
   * @return string[]
   *   Returns an array with names of configurations.
   */
  protected function getEditableConfigNames(): array {
    return [static::SETTINGS];
  }

  /**
   * {@inheritDoc}
   *
   * @param array $form
   *   The form array.
   * @param FormStateInterface $form_state
   *   Form state interface.
   *
   * @return array
   *   Array with form.
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config(static::SETTINGS);

    $form['info'] = [
      '#type' => 'markup',
      '#markup' => t('<p>Impostazioni di Amministrazione Trasparente e Albo Online</p>'),
    ];

    // Vertical tabs.
    $form['keryx'] = [
      '#type' => 'vertical_tabs'
    ];

    /* Video repository output options */
    $form['at'] = [
      '#type' => 'details',
      '#title' => t('Amministrazione Trasparente'),
      '#description' => $this->t('Impostazioni di amministrazione trasparente.'),
      '#open' => TRUE,
      '#group' => 'keryx',
      '#weight' => 0,
    ];
    $form['at']['usr_ptpct_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Piano Triennale della Prevenzione della Corruzione e per la Trasparenza'),
      '#description' => $this->t("Inserisci il link al Piano Triennale della Prevenzione della Corruzione e per la Trasparenza del tuo USR"),
      '#default_value' => $config->get('usr_ptpct_url'),
    ];

    /* Impostazioni per Albo online */
    $form['albo'] = [
      '#type' => 'details',
      '#title' => t('Albo online'),
      '#description' => $this->t("Impostazioni per Albo online"),
      '#open' => FALSE,
      '#group' => 'keryx',
      '#weight' => 0,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritDoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config(static::SETTINGS)
      ->set('usr_ptpct_url', $form_state->getValue('usr_ptpct_url'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}

