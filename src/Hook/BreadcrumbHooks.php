<?php

namespace Drupal\keryx\Hook;

use Drupal\keryx\Helper\Helper;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\taxonomy\Entity\Term;
use Drupal\Core\Url;

/**
 * Gestisce gli hooks della breadcrumb
 */
class BreadcrumbHooks {

  /**
   *  Viene costruita una breadcrumb con la seguente struttura:
   *  /home/A.T./macro-famiglia/tipologia-dati/obbligo
   *
   * @param $variables
   * @return void
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  #[Hook('preprocess_breadcrumb')]
  public function preprocessBreadcrumb(&$variables): void {
    // Assegno e controllo se è un termine
    if ($term = Helper::getObbligoTerm()) {

      // Trovo la tipologia di dato di amministrazione trasparente
      // a cui è associato l'obbligo di pubblicazione.
      $term_ids = Helper::getTipologiaDato($term);

      if (!empty($term_ids)) {
        // Costruisco una nuova breadcrumb
        $new_breadcrumb = [];

        // Salvo i primi due elementi
        $new_breadcrumb[] = $variables['breadcrumb'][0];
        $new_breadcrumb[] = $variables['breadcrumb'][1];

        // Abbrevio il link di amministrazione trasparente
        $new_breadcrumb[1]['text'] = 'A.T.';

        // Recupero le associazioni (dovrebbe esserci un solo elemento nell'array)
        $tipologie_dati = Term::loadMultiple($term_ids);

        // Aggiungo gli elementi trovati dalla query
        foreach ($tipologie_dati as $tipologia_dato) {
          // Recupero il termine genitore (macrofamiglia) dal campo 'parent'.
          $parent_target = $tipologia_dato->get('parent')->getValue();
          if (!empty($parent_target)) {
            $parent_tid = $parent_target[0]['target_id'];
            $parent_term = Term::load($parent_tid);

            if ($parent_term) {
              // Aggiungo la macrofamiglia alla breadcrumb.
              $new_breadcrumb[] = [
                'text' => $parent_term->label(),
                'url' => Url::fromRoute('entity.taxonomy_term.canonical', ['taxonomy_term' => $parent_term->id()])->toString(),
              ];
            }
          }

          // Aggiungi il link alla breadcrumb.
          $new_breadcrumb[] = [
            'text' => $tipologia_dato->label(),
            'url' => Url::fromRoute('entity.taxonomy_term.canonical', ['taxonomy_term' => $tipologia_dato->id()])->toString(),
          ];

          // Aggiungo l'ultimo elemento (che è la pagina corrente)
          $new_breadcrumb[] = end($variables['breadcrumb']);

          // Aggiungo l'ancora che porta direttamente l'utente al menu di AT
          $new_breadcrumb[1]['url'] = $new_breadcrumb[1]['url'] . '#sezioni-liv-1-macro-famiglie';

          // Sovrascrivo con la nuova breadcrumb
          $variables['breadcrumb'] = $new_breadcrumb;
        }
      }
    }
  }
}
