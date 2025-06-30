<?php

namespace Drupal\keryx\Hook;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Database\Database;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\keryx\Helper\Helper;
use Drupal\skenografia\Helper\Helper as SkenografiaHelper;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\taxonomy\Entity\Term;
use Drupal\views\ViewExecutable;

/**
 * Gestisce le voci di tipo "Obbligo di pubblicazione"
 */
class ObbligoHooks {

  /**
   * Elenco di termini a cui vanno associate delle specifiche viste
   *
   * @var array[]
   */
  private array $viewsToPage = [
    // Il termine è 9403 = "Atti amministrativi generali"
    9403 => [
      '#type' => 'view',
      '#name' => 'primo_livello_novita',
      '#display_id' => 'circolari',
      '#arguments' => [],
    ],
    // Il termine è 9445 = "Organigramma - Illustrazione in forma semplificata"
    9445 => [
      '#type' => 'view',
      '#name' => 'secondo_livello_persone',
      '#display_id' => 'page_1',
      '#arguments' => [],
    ],
    // Il termine è 9446 = "Organigramma - Nomi dei dirigenti"
    9446 => [
      '#type' => 'view',
      '#name' => 'amministrazione_trasparente_obblighi',
      '#display_id' => 'dirigenti',
      '#arguments' => [],
    ],
    // Il termine è 9447 = "Telefono e posta elettronica"
    9447 => [
      '#type' => 'view',
      '#name' => 'amministrazione_trasparente_obblighi',
      '#display_id' => 'sede_legale',
      '#arguments' => [],
    ],
    // Il termine è 9448 = "Consulenti e collaboratori"
    9448 => [
      '#type' => 'view',
      '#name' => 'amministrazione_trasparente_obblighi',
      '#display_id' => '9448_consulenti_collaboratori',
      '#arguments' => [],
    ],
    // Il termine è 9462 = "Incarichi conferiti e autorizzati ai dipendenti (dirigenti e non dirigenti)"
    9462 => [
      '#type' => 'view',
      '#name' => 'amministrazione_trasparente_obblighi',
      '#display_id' => '9462_incarichi_dipendenti',
      '#arguments' => [],
    ],
    // Il termine è 9483 = "Recapiti dell'ufficio responsabile"
    9483 => [
      '#type' => 'view',
      '#name' => 'amministrazione_trasparente_obblighi',
      '#display_id' => '9483_recapiti_ufficio_responsabile',
      '#arguments' => [],
    ],
    // Il termine è 9578 = "Elenco annuale dei progetti finanziati, con indicazione del CUP, importo totale del finanziamento, le fonti finanziarie, la data di avvio del progetto e lo stato di attuazione finanziario e procedurale"
    9578 => [
      '#type' => 'view',
      '#name' => 'amministrazione_trasparente_obblighi',
      '#display_id' => '9568_elenco_annuale_progetti',
      '#arguments' => [],
    ],
    // Il termine è 9580 = "Documenti di gara"
    9580 => [
      '#type' => 'view',
      '#name' => 'amministrazione_trasparente_obblighi',
      '#display_id' => '9580_documenti_gara',
      '#arguments' => [],
    ],
  ];

  /**
   * Lista di termini in cui nascondere i risultati della view
   * predefinita taxonomy_term
   *
   * @var array
   */
  private array $hideViews = [9445, 9446, 9447, 9448, 9462, 9483, 9578, 9580];

  /**
   * Messaggi personalizzati nel caso in cui una particolare
   * vista non dia risultati.
   *
   * @var array
   */
  private array $emptyMessages = [
    0 => ["title" => "Nessun dato", "content" => "L'istituto non possiede dati da pubblicare in merito."],
    9441 => ["title" => "Nessuna sanzione", "content" => "L'istituto non ha ricevuto sanzioni."],
    9455 => ["title" => "Nessuna sanzione", "content" => "L'istituto non ha ricevuto sanzioni."],
    9527 => ["title" => "Nessun immobile", "content" => "L'istituto non possiede e/o detiene immobili."],
    9528 => ["title" => "Nessun canone", "content" => "L'istituto non versa o percepisce alcun canone di affitto o locazione."],
    9531 => ["title" => "Nessun rilievo", "content" => "L'istituto non ha ricevuto alcun rilievo dalla Corte dei conti."],
    9563 => ["title" => "Nessun provvedimento", "content" => "L'istituto non è stato oggetto di provvedimenti da parte di ANAC."],
    9564 => ["title" => "Nessun atto di accertamento", "content" => "L'istituto non ha ricevuto alcun atto di accertamento."],
    9581 => ["title" => "Nessuna commissione nominata", "content" => "Negli anni 2024 e 2025 non ci sono stati affidamenti tramite bandi di gara."],
    9585 => ["title" => "Nessun relazione o certificazione", "content" => "L'istituto non ha stipulato contratti con ditte aventi un numero superiore di 15 dipendenti soggette agli obblighi"],
    9586 => ["title" => "Nessuna sponsorizzazione", "content" => "L'istituto non ha stipulato alcun contratto di sponsorizzazione."],
  ];

  /**
   * Questo metodo gestisce l'hook preprocess_page
   *
   * @param $variables
   * @return void
   */
  #[Hook('preprocess_page')]
  public function preprocessPage(&$variables): void {
    $term = Helper::getObbligoTerm();
    if ($term) {
      // Aggiungo una vista custom subito dopo la view taxonomy_term,
      // in seguito taxonomy_term verrà nascosta (vedi preprocessViewsView)
      $this->addViewsToPage($variables, $term);

      try {
        $this->addBackLinkToTipologiaDati($variables, $term);
      } catch (InvalidPluginDefinitionException|PluginNotFoundException $e) {
        \Drupal::messenger()->addError($e->getMessage());
      }
    }
  }

  /**
   * Questo metodo gestisce l'hook preprocess_taxonomy_term
   *
   * @param $variables
   * @return void
   */
  #[Hook('preprocess_taxonomy_term')]
  public function preprocessTaxonomyTerm(&$variables): void {
    $term = Helper::getObbligoTerm();
    if ($term) {
      $this->addLinksToObbligo($variables, $term);
    }
  }

  /**
   * Questo metodo gestisce l'hook preprocess_view
   *
   * @param $variables
   * @return void
   * @throws \Exception
   */
  #[Hook('preprocess_views_view')]
  public function preprocessViewsView(&$variables): void {
    $term = Helper::getObbligoTerm();
    if ($term) {
      // Nel caso in cui la vista è riscritta, rimuovo i risultati da taxonomy_term
      if ($variables['id'] == 'taxonomy_term' && in_array($term->id(), $this->hideViews)) {
        $variables['rows'] = $variables['pager'] = [];
      }

      if ($term->id() == 9578) {
        $this->addVolumeFinanziatoTo9578ViewsTerm($variables);
      }

      $this->setEmptyMessage($variables, $term);
    }
  }

  #[Hook('preprocess_views_view_table')]
  public function preprocessViewsViewTable(&$variables): void {
    $term = Helper::getObbligoTerm();
    if ($term) {
      if (in_array($term->id(), [9948, 9462])) {
        $this->addARANLinkToVariables($variables);
      }
    }
  }

  /**
   * Aggiunge delle viste ad dei specifici obblighi
   *
   * @param $variables
   * @param Term $term
   * @return void
   */
  private function addViewsToPage(&$variables, Term $term): void {
    if (array_key_exists($term->id(), $this->viewsToPage)) {
      $variables['page']['content']['custom_view'] = $this->viewsToPage[$term->id()];
    }
  }

  /**
   * Aggiunge a tutti i termini di tassonomia di tipo obbligo un link per
   * tornare alla relativa tipologia di dati
   *
   * @param $variables
   * @param Term $term
   * @return void
   * @throws InvalidPluginDefinitionException
   * @throws PluginNotFoundException
   */
  private function addBackLinkToTipologiaDati(&$variables, Term $term): void {
    $term_ids = Helper::getTipologiaDato($term);
    if (!empty($term_ids)) {
      // Recupero le associazioni (dovrebbe esserci un solo elemento nell'array)
      $tipologie_dati = Term::loadMultiple($term_ids);

      // Aggiungo gli elementi trovati dalla query
      foreach ($tipologie_dati as $tipologia_dato) {
        $variables['page']['content']['back_to_tipologia_dati'] = [
          '#type' => 'markup',
          '#markup' => trim(sprintf(
            '<div class="container-xxl py-5 text-align-center"><a class="btn btn-link fw-bold text-dark text-underline" href="%s" title="Torna alla tipologia dati: %s">Torna ad %s</a></div>',
            Url::fromRoute('entity.taxonomy_term.canonical', ['taxonomy_term' => $tipologia_dato->id()])->toString(),
            $tipologia_dato->label(),
            $tipologia_dato->label()
          )),
          '#weight' => 100,
        ];

      }
    }
  }

  /**
   * Aggiunge nell'header del display 9568_elenco_annuale_progetti della
   * vista amministrazione_trasparente_obblighi il totale degli
   * importi
   *
   * @param $variables
   * @return void
   * @throws \Exception
   */
  private function addVolumeFinanziatoTo9578ViewsTerm(&$variables): void {
    if (
      isset($variables['id'])
      && $variables['id'] == 'amministrazione_trasparente_obblighi'
      && $variables['display_id'] == '9568_elenco_annuale_progetti'
    ) {
      $query = Database::getConnection()->select('node__field_importo_finanziamento', 'f');
      $query->addExpression('SUM(f.field_importo_finanziamento_value)', 'somma');
      $query->join('node_field_data', 'n', 'f.entity_id = n.nid');
      $query->condition('n.type', 'finanziamento'); // Ricordarsi di filtrare per tipo
      $query->condition('n.status', 1); // solo i nodi pubblicati
      $result = $query->execute()->fetchField();

      $variables['volume_finanziato'] = $result !== NULL ? (float) $result : 0;

      $variables['header']['result']['#markup'] =
        str_replace(
          '@volume_finanziato',
          number_format($variables['volume_finanziato'], 2, ',', ' '),
          $variables['header']['result']['#markup']
        );
    }
  }

  /**
   * Restituisce un messaggio personalizzato nel caso in cui
   * la vista non produce risultati
   *
   * @param $variables
   * @param Term $term
   * @return void
   */
  private function setEmptyMessage(&$variables, Term $term): void {
    if (
      $term->get('field_persona_responsabile')->value // Se la scuola ha pertinenza con l'obbligo
      && count($variables['rows']) == 0 // e le righe della vista sono vuote
      && !array_key_exists($term->id(), $this->viewsToPage) // e non è tra le viste riscritte
      && !preg_match('/\b(?:Collegamento|Link|Portale|esterno)\b/i', $term->get('field_persona_responsabile')->value) // escludo i termini con collegamento o link
    ) {
      $message = array_key_exists($term->id(), $this->emptyMessages) ? $this->emptyMessages[$term->id()] : $this->emptyMessages[0];
      $markup = '<div class="container-xxl text-center"><h2 class="mt-5 fw-lighter">'.$message["title"].'</h2><p class="lead">'.$message["content"].'</p></div>';
      $variables['empty']['message'] = ['#type' => 'markup', '#markup' => $markup];
    }
  }

  /**
   * Aggiunge dei collegamenti o link ad alcuni obblighi
   *
   * @param $variables
   * @param false|Term $term
   * @return void
   */
  private function addLinksToObbligo(&$variables, false|Term $term): void {
    $config = \Drupal::config('keryx.settings');
    $sede = Helper::getDatiSedeLegale();

    // Se il termine è 9559 = "Piano triennale per la prevenzione della corruzione e della trasparenza"
    // Se il termine è 9560 = "Responsabile della prevenzione della corruzione e della trasparenza"
    // Se il termine è 9562 = "Relazione del responsabile della prevenzione della corruzione e della trasparenza"
    // aggiungo un pulsante con il collegamento al PTPCT dell'USR.
    if (in_array($term->id(), [9559, 9560, 9562]) && $variables['view_mode'] == 'full') {
      $usr_ptpct_url = $config->get('usr_ptpct_url') ?? 'https://www.istruzione.calabria.it/amministrazionetrasparente/anticorruzione/';

      $variables['content']['btn_usr_ptpct'] = [
        '#type' => 'markup',
        '#markup' => trim(sprintf(
          '<p class="p-5 border border-primary w-75"><a class="btn btn-xs btn-primary my-4" href="%s" target="_blank" title="Vai al Piano triennale per la prevenzione della corruzione e della trasparenza dell\'Ufficio Scolastico Regionale">
              Vai al Piano triennale per la prevenzione della corruzione e della trasparenza dell\'Ufficio Scolastico Regionale</a></p>',
          htmlspecialchars($usr_ptpct_url, ENT_QUOTES, 'UTF-8')
        )),
        '#weight' => 100,
      ];
    }

    // Se il termine è
    // 9448 = "Consulenti e collaboratori"
    // 9462 = "Incarichi conferiti e autorizzati ai dipendenti (dirigenti e non dirigenti)"
    elseif (in_array($term->id(), [9448, 9462]) && $variables['view_mode'] == 'full') {
      $tipologia_soggetto = $term->id() == 9448 ? 'CCE' : 'DIP';
      $perla_pa_url = $config->get('perla_pa_url') ?? 'https://consulentipubblici.dfp.gov.it/?ente=DFP00017973';
      $perla_pa_url .= '&tipologiasoggetto=' . $tipologia_soggetto;

      $current_year = (int) date('Y');
      $years = range($current_year, $current_year - 2);

      $links = [];
      foreach ($years as $year) {
        $uri = $perla_pa_url . '&anno=' . $year;
        $links[] = Link::fromTextAndUrl(
          t('Collegamento con la Banca Dati del sistema Perla PA anno @year', ['@year' => $year]),
          Url::fromUri($uri, [
            'attributes' => [
              'class' => ['btn', 'btn-xs', 'btn-outline', 'btn-outline-info', 'my-4'],
              'target' => '_blank',
              'title'  => t('Vai al Portale Perla PA in una nuova finestra'),
            ],
          ])
        );
      }

      $variables['content']['btn_perla_pa'] = [
        '#type'    => 'container',
        '#attributes' => ['class' => ['p-5', 'border', 'w-75']],
        'links'    => [
          '#theme' => 'item_list',
          '#items' => $links,
          '#attributes' => ['class' => ['btn-list']],
        ],
        '#weight'  => 100,
      ];
    }

    // Se il termine è 9459 = "Personale non a tempo indeterminato"
    elseif ($term->id() == 9459 && $variables['view_mode'] == 'full') {
      $plessi = Helper::getPlessi();

      // collegamenti con unica per singolo plesso
      $links_unica = '';

      foreach ($plessi as $plesso) {
        if ($plesso['url_unica_scuola_in_chiaro']) {
          $links_unica .= sprintf(
            '<a class="btn btn-xs btn-primary my-4" href="%s/personale/" title="Vai al Portale UNICA Scuola in chiaro" target="_blank">Collegamento con UNICA - Scuola in chiaro %s</a><br>',
            $plesso['url_unica_scuola_in_chiaro'],
            $plesso['nome']
          );
        }
      }

      $variables['content']['btn_unica'] = [
        '#type' => 'markup',
        '#markup' => trim(sprintf(
          '<p class="p-5 border w-75">Una volta aperto il collegamento con il portale UNICA, scorri la pagina fino ai dati interessati.<br>%s</p>',
          $links_unica
        )),
        '#weight' => 100,
      ];
    }

    // Se il termine è 9460 = "Tassi di assenza trimestrali"
    elseif ($term->id() == 9460 && $variables['view_mode'] == 'full') {
      if (isset($sede['url_unica_scuola_in_chiaro']) && $sede['url_unica_scuola_in_chiaro']) {
        $link_unica = $sede['url_unica_scuola_in_chiaro'] . '/finanza/';
        $variables['content']['btn_unica'] = [
          '#type' => 'markup',
          '#markup' => trim(sprintf(
            '<p class="p-5 border w-75"><a class="btn btn-xs btn-primary my-4" href="%s" target="_blank" title="Vai al Portale UNICA">Collegamento con il Portale UNICA - Scuola in chiaro</a></p>',
            $link_unica
          )),
          '#weight' => 100,
        ];
      }
    }

    // Se il termine è 9461 = "Tassi di assenza trimestrali"
    elseif ($term->id() == 9461 && $variables['view_mode'] == 'full' && $config->get('tassi_assenza_trimestrali_auto')) {
      if (isset($sede['codice_meccanografico']) && $sede['codice_meccanografico']) {
        $site_name = rawurlencode(\Drupal::config('system.site')->get('name'));
        $anno_scolastico_corrente = SkenografiaHelper::getAnnoScolastico([
          "formato_anno" => "yyyy-yy",
          "separatore" => "",
        ]);

        $link_miur =
          'https://oc4jesemvlas2.pubblica.istruzione.it/trasparenzaPubb/ricercaassenze.do?codScuUt=' . strtolower($sede['codice_meccanografico'])
          . '&paramDesNomScu="' . $site_name . '"'
          . '&paramDatAnnScoRil=' . $anno_scolastico_corrente
          . '&tipoRicerca=S&paramCodScuUt=' . strtoupper($sede['codice_meccanografico']);

        $link_unica = $sede['url_unica_scuola_in_chiaro'] . '/personale/assenze/';

        $variables['content']['btn_miur'] = [
          '#type' => 'markup',
          '#markup' => trim(sprintf(
            '<p class="p-5 border w-75">
                    Una volta aperto il collegamento con il portale MIUR o con il portale UNICA, seleziona il periodo per visualizzare i relativi dati.<br>
                    <a class="btn btn-xs btn-primary my-4" href="%s" target="_blank" title="Vai al Portale MIUR - Operazione Trasparenza">Collegamento con il Portale MIUR - Operazione Trasparenza</a><br>
                    <a class="btn btn-xs btn-primary my-4" href="%s" target="_blank" title="Vai al Portale Unica">Collegamento con il Portale UNICA</a>
                  </p>',
            htmlspecialchars($link_miur, ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($link_unica, ENT_QUOTES, 'UTF-8')
          )),
          '#weight' => 100,
        ];
      }
    }

    // Se il termine è 9465 = "Costi contratti integrativi"
    elseif ($term->id() == 9465 && $variables['view_mode'] == 'full') {
      if (isset($sede['codice_fiscale']) && $sede['codice_fiscale']) {
        $url_aran = 'https://www.contrattintegrativipa.it/ci/?enteCodiceFiscale=' . $sede['codice_fiscale'];
        $variables['content']['btn_aran'] = [
          '#type' => 'markup',
          '#markup' => trim(sprintf(
            '<p class="p-5 border w-75">Una volta aperto il "Collegamento con ARAN", cerca i contratti di questo istituto usando il seguente codice fiscale: <strong>%s</strong><br><a class="btn btn-xs btn-primary my-4" href="%s" target="_blank" title="Vai al Portale ARAN - Banca Dati Contratti Integrativi">Collegamento con ARAN - Banca Dati Contratti Integrativi</a></p>',
            htmlspecialchars($sede['codice_fiscale'], ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($url_aran, ENT_QUOTES, 'UTF-8')
          )),
          '#weight' => 100,
        ];
      }
    }

    // Se il termine è 9481 = "Tipologie di procedimento"
    elseif ($term->id() == 9481 && $variables['view_mode'] == 'full') {
      $url_servizi = '/servizi';

      $variables['content']['btn_perla_pa'] = [
        '#type' => 'markup',
        '#markup' => trim(sprintf(
          '<p class="p-5 border w-75"><a class="btn btn-xs btn-primary my-4" href="%s" title="Vai ai servizi">Vai ai servizi che offre la scuola</a></p>',
          htmlspecialchars($url_servizi, ENT_QUOTES, 'UTF-8')
        )),
        '#weight' => 100,
      ];
    }

    // Se il termine è 9580 = "Documenti di gara"
    elseif ($term->id() == 9580 && $variables['view_mode'] == 'full') {
      if (isset($sede['codice_fiscale']) && $sede['codice_fiscale']) {
        $url_bdncp = 'https://dati.anticorruzione.it/superset/dashboard/dettaglio_sa/?sa=' . $sede['codice_fiscale'];
        $variables['content']['btn_bdncp'] = [
          '#type' => 'markup',
          '#markup' => trim(sprintf(
            '<p class="p-5 border w-75">Una volta aperto il "Collegamento con <abbr title="Banca Dati Nazionale Contratti Pubblici">BDNCP</abbr>", è possibile analizzare e/o esportare tutti dati, compresi i dati storici.<br><a class="btn btn-xs btn-primary my-4" href="%s" target="_blank" title="Vai al Portale BDNCP - Banca Dati Nazionale dei Contratti Pubblici">Collegamento con BDNCP - Banca Dati Nazionale dei Contratti Pubblici</a></p>',
            htmlspecialchars($url_bdncp, ENT_QUOTES, 'UTF-8')
          )),
          '#weight' => 100,
        ];
      }
    }

  }

  /**
   * Aggiunge delle variabili da passare al template della vista
   *
   * @param $variables
   * @return void
   */
  private function addARANLinkToVariables(&$variables): void {
    if (isset($variables['view']) && ($variables['view'] instanceof ViewExecutable)) {
      $view_id = $variables['view']->id();
      $view_display = $variables['view']->current_display;

      if (
        $view_id == 'amministrazione_trasparente_obblighi' &&
        in_array($view_display, ['9448_consulenti_collaboratori', '9462_incarichi_dipendenti'])
      ) {
        $variables['anno'] = trim($variables['title']);

        $config = \Drupal::config('keryx.settings');
        $perla_pa_url = $config->get('perla_pa_url') ?? 'https://consulentipubblici.dfp.gov.it/?ente=DFP00017973';
        $variables['perla_pa_url'] = htmlspecialchars($perla_pa_url, ENT_QUOTES, 'UTF-8');
      }
    }
  }

}
