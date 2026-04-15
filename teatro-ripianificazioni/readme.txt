=== Teatro Ripianificazioni ===
Contributors:      e3pr0m
Author:            E3pr0m
Author URI:        https://www.e3pr0m.com
Plugin URI:        https://prenotazione.teatrosolare.it
Requires at least: 6.0
Tested up to:      6.7
Requires PHP:      8.0
WC requires at least: 7.0
WC tested up to:      9.x
Version:           1.0.0
License:           Proprietario — uso riservato Teatro Solare
License URI:       https://prenotazione.teatrosolare.it

Ripianifica una singola settimana di un ordine WooCommerce senza annullarlo.
Sviluppato su misura — non distribuire.

== Descrizione ==

Teatro Ripianificazioni risolve una delle esigenze operative più frequenti di
Teatro Solare: un genitore che ha acquistato più settimane di campo estivo ha
bisogno di spostarne una sola — ad esempio per una coincidenza con una gita
scolastica o un viaggio — senza dover annullare e rifare l'intero ordine.

Prima di questo plugin l'unica procedura possibile era:
annullare l'ordine → rimborsare → guidare il cliente a rifarlo da zero.
Questa procedura causa perdita della cronologia d'ordine, possibile perdita di
sconti ISEE o fedeltà già applicati, e una pessima esperienza utente.

Con Teatro Ripianificazioni l'operatore admin apre l'ordine WooCommerce,
individua la settimana da spostare, seleziona la nuova settimana (con pulmino e
fermata), conferma: l'operazione è completata in pochi secondi, l'ordine rimane
intatto, e una nota nel backend traccia chi ha fatto cosa e quando.

=== Funzionalità ===

* Meta box "Ripianifica Settimane" nella pagina di dettaglio ordine WooCommerce,
  compatibile sia con WooCommerce HPOS (High-Performance Order Storage) sia con
  il sistema legacy basato su post
* Per ogni prodotto con settimane prenotate: tabella con indice, date settimana,
  pulmino assegnato e fermata, con pulsante "Ripianifica" per ogni riga
* Modal di selezione con tre livelli a cascata:
  — Nuova settimana: elenco settimane del prodotto (da ACF) con posti disponibili
  — Pulmino: elenco pulmini disponibili per la settimana selezionata, con
    indicazione dei posti liberi per ciascuno
  — Fermata: elenco fermate del pulmino selezionato, con propagazione automatica
    degli orari di salita e discesa (start_time / end_time)
* Selezione a cascata reattiva: cambiando settimana si aggiorna l'elenco bus;
  cambiando bus si aggiorna l'elenco fermate
* Operazione atomica: sostituzione del solo indice target negli array meta
  dell'item, release del posto sul pulmino precedente per quella settimana,
  prenotazione del posto sul nuovo pulmino — tutto in un'unica transazione
* La disponibilità dei posti è calcolata in tempo reale dagli ordini WooCommerce
  (stessa logica di `teatro-courses-buses`), non da dati in cache
* Nota ordine automatica con: operatore admin, settimana precedente → nuova,
  pulmino precedente → nuovo, fermata scelta
* Reload automatico della pagina ordine al termine dell'operazione
* Messaggi di errore inline nel modal in caso di parametri mancanti o problemi
  di connessione

=== Caso d'uso tipico ===

Scenario: la signora Rossi ha prenotato per suo figlio le settimane
"02/06/2025 - 08/06/2025" e "09/06/2025 - 15/06/2025". Ha bisogno di
spostare la prima settimana a "16/06/2025 - 22/06/2025".

Procedura:
1. Admin apre l'ordine dalla bacheca WooCommerce → Ordini
2. Nella meta box "Ripianifica Settimane" individua la riga con
   "02/06/2025 - 08/06/2025" e clicca "Ripianifica"
3. Nel modal seleziona "16/06/2025 - 22/06/2025" come nuova settimana
4. Sceglie il pulmino disponibile e la fermata
5. Clicca "Conferma Ripianificazione"
6. La pagina si ricarica: l'ordine ora mostra la settimana aggiornata.
   La nota ordine riporta: "Ripianificazione manuale — operatore: admin |
   settimana: [02/06/2025 - 08/06/2025 → 16/06/2025 - 22/06/2025] |
   pulmino: [Bus Nord → Bus Nord] | fermata: Fermata A"

L'ordine non è stato annullato. Il prezzo rimane invariato. Le altre settimane
prenotate restano inalterate.

=== Operazioni che questo plugin NON esegue ===

* Non crea nuovi ordini WooCommerce
* Non modifica prezzi, fee, coupon o totali d'ordine
* Non tocca le altre settimane dello stesso item o di altri item nell'ordine
* Non invia email automatica al cliente (l'operatore gestisce la comunicazione)
* Non interferisce con il flusso di acquisto frontend (checkout, carrello,
  goto_addtocart, goto_2step, goto_3step)
* Non modifica la logica di calcolo sconti ISEE o fedeltà

=== Relazione con gli altri plugin Teatro Solare ===

* `teatro-courses-buses` — questo plugin riusa i metodi pubblici di
  `$WC_custom_teatro_attributes` per leggere le settimane ACF, calcolare la
  disponibilità bus e prenotare i posti. Non duplica nessuna logica.
* `teatro-gestione-pulmini` — i due plugin operano su piani distinti:
  Teatro Gestione Pulmini gestisce il reset globale dei dati `seats_booked`,
  Teatro Ripianificazioni modifica puntualmente un singolo slot per ordine.
  Non si sovrappongono.
* `teatro-discounts` / `teatro-isee-counter` — non coinvolti. La ripianificazione
  non ricalcola né modifica gli sconti applicati all'ordine originale.

== Installazione ==

1. Copiare la cartella `teatro-ripianificazioni/` (contenente questo file e
   `teatro-ripianificazioni.php`) nella directory `wp-content/plugins/` del sito.
2. Assicurarsi che il plugin `teatro-courses-buses` sia già attivo (dipendenza).
3. Attivare Teatro Ripianificazioni dalla bacheca WordPress → Plugin.
4. La meta box "Ripianifica Settimane" apparirà automaticamente in ogni pagina
   di dettaglio ordine WooCommerce.

=== Note per il deploy ===

Attivare **dopo** `teatro-courses-buses`: Teatro Ripianificazioni richiede la
variabile globale `$WC_custom_teatro_attributes` esposta da quel plugin al
caricamento di WordPress. Se `teatro-courses-buses` non è attivo, le chiamate
AJAX restituiranno un messaggio di errore invece di bloccare il sito.

== Dipendenze ==

* WordPress 6.0 o superiore
* WooCommerce 7.0 o superiore
* PHP 8.0 o superiore
* Advanced Custom Fields Pro — per `get_field('weeks', $product_id)`,
  `get_field('buses', $product_id)`, `get_field('stops', $bus_id)`
* Plugin `teatro-courses-buses` v1.1.1 o superiore

== Struttura del dato modificato ==

Il plugin modifica cinque meta array posizionali dell'item d'ordine.
Tutti usano `@@` come separatore tra i valori delle diverse settimane.
L'indice di ogni array corrisponde alla stessa settimana:

  product_weeks_selected:        "02/06/2025 - 08/06/2025@@16/06/2025 - 22/06/2025"
  product_buses_selected:        "42@@38"
  product_bus_stops_selected:    "Fermata A@@Fermata B"
  product_bus_stop_start_time:   "08:00@@08:15"
  product_bus_stop_end_time:     "17:00@@17:15"

Alla conferma, viene sostituito solo il valore all'indice della settimana
ripianificata. Tutti gli altri indici rimangono intatti.

== Struttura del dato `seats_booked` ==

Parallelamente alla modifica dei meta dell'item, il plugin aggiorna il campo
`seats_booked` del post pulmino. Il dato è un array PHP serializzato:

  array(
    'order_id'   => 123,
    'product_id' => 456,
    'parent_id'  => 789,
    'child_id'   => 101,
    'week_id'    => '1748822400-1749427200',  // timestamp_inizio-timestamp_fine
    'booked_at'  => 1713129600,
  )

Al momento della ripianificazione:
* Viene rimossa l'entry con `order_id == X AND week_id == vecchia_settimana`
  dal pulmino precedente — lasciando intatte le prenotazioni di altre settimane
  sullo stesso pulmino
* Viene aggiunta una nuova entry con i dati della nuova settimana e del nuovo
  pulmino

Il `week_id` viene calcolato convertendo la stringa data in coppia di timestamp
con la stessa logica di `str_replace('/', '-')` già usata da `teatro-courses-buses`.

== Changelog ==

= 1.0.0 — Aprile 2026 =
* Prima release come plugin standalone.
* Meta box compatibile HPOS e legacy shop_order.
* Modal con selezione a cascata: settimane → bus → fermate.
* Operazione atomica: modifica meta item, release posto ex-bus per la settimana
  specifica, prenotazione posto nuovo bus, nota ordine con dettaglio completo.
* Riuso metodi pubblici di `teatro-courses-buses` senza duplicazioni.

== Note tecniche ==

=== Sicurezza ===

Tutti e quattro gli endpoint AJAX sono protetti da doppio controllo:

1. Nonce WordPress `ripia_nonce` verificato con `check_ajax_referer()` —
   impedisce richieste CSRF da origini esterne
2. Capability check `manage_woocommerce` — solo utenti con ruolo Shop Manager
   o Amministratore possono eseguire le operazioni

Il nonce viene generato a runtime durante `admin_enqueue_scripts` e iniettato
nella pagina tramite `wp_add_inline_script` (oggetto `ripiaConfig`). Non è
presente nel DOM come campo hidden ed è legato alla sessione dell'utente.

Input sanitizzati:
* ID numerici: `intval()`
* Stringhe: `sanitize_text_field()`

Nessuna query SQL diretta con input utente. Tutte le operazioni di lettura
e scrittura avvengono tramite le API WordPress e WooCommerce (`get_post_meta`,
`update_post_meta`, `$item->update_meta_data`, `$order->save`).

=== Compatibilità ===

La meta box è registrata su entrambi gli screen ID:
* `woocommerce_page_wc-orders` — WooCommerce HPOS (schermate ordini CPT)
* `shop_order` — sistema legacy basato su post WordPress

Il callback `render_meta_box` gestisce entrambi i casi: riceve `WC_Abstract_Order`
con HPOS e `WP_Post` con il sistema legacy.

=== Compatibilità con `seats_booked` ===

La funzione `release_seat()` filtra l'array `seats_booked` mantenendo solo le
entry che NON corrispondono alla coppia `(order_id + week_id)`. Questo permette
allo stesso ordine di avere prenotazioni su settimane diverse dello stesso
pulmino: solo la settimana modificata viene rilasciata, non tutte.

---

Sviluppato da E3pr0m per Teatro Solare — uso riservato al cliente.
Non distribuire senza autorizzazione scritta.
