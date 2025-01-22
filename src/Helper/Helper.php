<?php

namespace Drupal\keryx\Helper;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\taxonomy\Entity\Term;

class Helper {

  /**
   * @param Term $term
   *   Termine di tassonomia
   *
   * @throws InvalidPluginDefinitionException
   * @throws PluginNotFoundException
   */
  static function getTipologiaDato(Term $term): array|int {
    $query = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->getQuery();
    $query->accessCheck(TRUE);
    $query->condition('vid', 'amministrazione_trasparente');
    $query->condition('field_obblighi_di_pubblicazione.target_id', $term->id());
    return $query->execute();
  }
}
