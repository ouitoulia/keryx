<?php

namespace Drupal\keryx\Helper;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\taxonomy\Entity\Term;
use Drupal\node\Entity\Node;
use Drupal\skenografia\Helper\Helper as SkenografiaHelper;

class Helper {

  private static $url_unica_scuola_in_chiaro = 'https://unica.istruzione.gov.it/cercalatuascuola';

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
   * Restituisce alcuni dati del luogo impostato come sede legale.
   *
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
        $dati['url_unica_scuola_in_chiaro'] =
          self::$url_unica_scuola_in_chiaro . '/istituti/'
          . strtoupper($sede_legale->get('field_codice_meccanografico')->value) . '/'
          . SkenografiaHelper::cleanTitleForUrl(\Drupal::config('system.site')->get('name'));
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

  /**
   * @return array
   */
  static function getPlessi(): array {
    // ID del termine di tassonomia "Scuola/istituto"
    $target_tid = 1306;

    // Array per risultati
    $plessi = [];

    $query = \Drupal::entityQuery('node')
      ->accessCheck(TRUE)
      ->condition('type', 'struttura_organizzativa')
      ->condition('field_tipologia_struttura.target_id', $target_tid)
      ->sort('nid', 'ASC');

    $nids = $query->execute();

    if (!empty($nids)) {
      $nodes = Node::loadMultiple($nids);

      foreach ($nodes as $node) {
        $codice_meccanografico_field = $node->get('field_codice_meccanografico');
        $codice_meccanografico = ($codice_meccanografico_field && !$codice_meccanografico_field->isEmpty())
          ? $codice_meccanografico_field->value
          : NULL;

        $url_unica_scuola_in_chiaro = $codice_meccanografico
          ? self::$url_unica_scuola_in_chiaro . '/istituti/'
              . strtoupper($codice_meccanografico) . '/'
              . SkenografiaHelper::cleanTitleForUrl($node->getTitle())
          : NULL;

        $plessi[$node->id()] = [
          'nome' => $node->getTitle(),
          'codice_meccanografico' => $codice_meccanografico,
          'url_unica_scuola_in_chiaro' => $url_unica_scuola_in_chiaro,
        ];
      }
    }

    return $plessi;
  }
}
