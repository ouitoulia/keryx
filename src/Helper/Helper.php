<?php

namespace Drupal\keryx\Helper;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\taxonomy\Entity\Term;
use Drupal\node\Entity\Node;

class Helper {

  /**
   * Restituisce la Tipologia di dato (è una categoria di AT) a cui
   * è associato un obbligo di pubblicazione.
   *
   * @param Term $term
   *   Termine di tassonomia di tipo obbligo di pubblicazione.
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

  /**
   * @return array
   */
  static function getDatiSedeLegale(): array {
    // Il nodo di tipo 'luogo' con 'field_sede_legale' true
    $query = \Drupal::entityQuery('node')
      ->accessCheck(TRUE)
      ->condition('type', 'luogo')
      ->condition('field_sede_legale', true)
      ->sort('changed', 'DESC') // in cima quello più aggiornato
      ->range(0, 1); // ne prendo solo uno
    $nids = $query->execute();

    $dati = [];

    if (!empty($nids)) {
      $sede_legale = Node::load(reset($nids));

      // Codice fiscale
      if ($sede_legale->hasField('field_codice_fiscale') && !$sede_legale->get('field_codice_fiscale')->isEmpty()) {
        $dati['codice_fiscale'] = $sede_legale->get('field_codice_fiscale')->value;
      }

      // Codice meccanografico
      if ($sede_legale->hasField('field_codice_meccanografico') && !$sede_legale->get('field_codice_meccanografico')->isEmpty()) {
        $dati['codice_meccanografico'] = $sede_legale->get('field_codice_meccanografico')->value;
      }

      // Codice cuf
      if ($sede_legale->hasField('field_codice_cuf') && !$sede_legale->get('field_codice_cuf')->isEmpty()) {
        $dati['codice_cuf'] = $sede_legale->get('field_codice_cuf')->value;
      }

      // Codice ipa
      if ($sede_legale->hasField('field_ipa') && !$sede_legale->get('field_ipa')->isEmpty()) {
        $dati['codice_ipa'] = $sede_legale->get('field_ipa')->value;
      }
    }

    return $dati;
  }
}
