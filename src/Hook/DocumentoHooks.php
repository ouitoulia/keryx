<?php

namespace Drupal\keryx\Hook;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Gestisce alcune funzioni di amministrazione trasparente
 * che sono presenti nel content type documento
 */
class DocumentoHooks {

  #[Hook('form_node_form_alter')]
  public function documentoFormAlter(array &$form, FormStateInterface $form_state) {
    if (isset($form['#form_id']) && $form['#form_id'] == 'node_documento_edit_form') {
      // TODO: check CIG with ANAC API
      //$form['#validate'][] = '\Drupal\keryx\Hook\DocumentoHooks::validateCIG';
    }
  }

  public static function validateCIG() {}

}
