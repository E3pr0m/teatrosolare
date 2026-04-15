=== Teatro Gestione Pulmini ===
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

Pannello admin per la gestione operativa delle prenotazioni pulmini.
Sviluppato su misura — non distribuire.

== Descrizione ==

Teatro Gestione Pulmini è il modulo amministrativo dedicato alla visualizzazione
e alla gestione delle prenotazioni dei pulmini sulla piattaforma Teatro Solare
(prenotazione.teatrosolare.it).

Il pannello è accessibile da WooCommerce → Gestione Pulmini ed è riservato agli
utenti con ruolo Amministratore.

Questo plugin è stato estratto da `teatro-courses-buses` (v1.1.1) e rilasciato
come plugin standalone per separare la responsabilità operativa (gestione admin)
dalla logica di booking (flusso acquisto frontend).

=== Funzionalità ===

* Tabella riepilogativa per ogni pulmino configurato: nome, capacità totale,
  numero prenotazioni attive, dettaglio suddiviso per settimana
* Visualizzazione leggibile delle date: conversione automatica dei timestamp
  interni nel formato data configurato in WordPress (es. d/m/Y)
* Supporto ai formati storici: gestisce sia il formato corrente (una prenotazione
  per settimana, "ts1-ts2") sia il vecchio formato aggregato ("ts1-ts2,ts3-ts4")
* Reset globale — azzera `seats_booked` su tutti i pulmini
* Reset per-bus — azzera tutte le settimane di un singolo pulmino
* Reset per-settimana — rimuove solo le prenotazioni della settimana selezionata
  su un bus specifico, lasciando intatte le altre settimane dello stesso pulmino;
  mostra il numero esatto di prenotazioni rimosse a operazione completata
* Tutti i reset protetti da WordPress nonce (CSRF protection)
* Confirm dialog su ogni azione distruttiva

=== Funzionalità di reset in dettaglio ===

Il reset opera esclusivamente sul campo `seats_booked` dei post pulmino.
Non modifica né cancella alcun ordine WooCommerce.

Il reset per singola settimana permette di correggere, ad esempio:

* Prenotazioni "fantasma" su una settimana specifica (es. ordini con pagamento
  fallito che non hanno attraversato l'hook di liberazione automatica)
* Bonifica selettiva di dati pre-fix su una sola settimana, senza toccare
  le settimane con dati corretti
* Annullamento manuale delle prenotazioni di una settimana soppressa
  senza dover resettare l'intero pulmino

=== Relazione con gli altri plugin Teatro Solare ===

* `teatro-courses-buses` — scrive i dati di prenotazione in `seats_booked`
  e li legge per calcolare la disponibilità frontend. Questo plugin NON tocca
  quella logica: non interferisce con il flusso di acquisto.
* `teatro-gestione-pulmini` — legge e resetta `seats_booked`. È uno strumento
  operativo puro: non contiene hook sul checkout né sulla gestione ordini.

La fonte di verità per la disponibilità frontend rimane `teatro-courses-buses`,
che legge direttamente dagli ordini WooCommerce tramite `countBookedBusSeatsByWeek`.
La voce `seats_booked` è usata come cache operativa e può essere resettata
senza perdita di dati sugli ordini.

== Installazione ==

1. Copiare il file `teatro-gestione-pulmini.php` nella directory
   `wp-content/plugins/` del sito (il plugin è un file singolo, nessuna cartella
   aggiuntiva necessaria).
2. Attivare il plugin dalla bacheca WordPress → Plugin.
3. Il pannello sarà disponibile in WooCommerce → Gestione Pulmini.

=== Note per il deploy ===

Attivare `teatro-gestione-pulmini` **prima** di aggiornare `teatro-courses-buses`
alla versione 1.1.1 (che rimuove la vecchia Giacenza Pulmini). In questo modo
la voce di menu rimane disponibile in backend senza interruzioni.

== Dipendenze ==

* WordPress 6.0 o superiore
* WooCommerce 7.0 o superiore
* Advanced Custom Fields Pro (per `get_field('seats_capacity', $bus_id)`)
* Plugin `teatro-courses-buses` v1.1.1 o superiore (che popola `seats_booked`)

== Struttura del dato `seats_booked` ==

Il plugin legge il campo `seats_booked` dal post meta di ogni pulmino.
Il dato è un array PHP serializzato, dove ogni elemento rappresenta una
prenotazione singola:

  array(
    'order_id'   => 123,
    'product_id' => 456,
    'parent_id'  => 789,
    'child_id'   => 101,
    'week_id'    => '1748822400-1749427200',  // timestamp_inizio - timestamp_fine
    'booked_at'  => 1713129600,
  )

Il campo `week_id` è prodotto da `getReadableWeekString()` in `teatro-courses-buses`
e usato come chiave di raggruppamento e target per il reset per-settimana.

== Changelog ==

= 1.0.0 — Aprile 2026 =
* Prima release come plugin standalone.
* Estratto da `teatro-courses-buses.php` (v1.1.0): le funzioni
  `add_bus_inventory_menu`, `bus_inventory_page`, `reset_all_bus_bookings`,
  `reset_bus_bookings` sono state spostate in questo plugin.
* Aggiunto reset per singola settimana (`reset_bus_week_bookings`):
  rimuove solo le prenotazioni della settimana target, conserva le altre.
* Aggiunto conteggio prenotazioni rimosse nel messaggio di conferma.
* Rinnovo interfaccia tabella: colonne ridefinite, layout dettaglio settimane
  con link "Resetta settimana" inline su ogni riga.
* Refactoring formato date estratto in metodo privato `format_week_id`:
  gestisce formato corrente e formato storico aggregato.

== Note tecniche ==

=== Sicurezza ===

Ogni azione di reset verifica il nonce WordPress prima di procedere:
* Reset globale: nonce `reset_bus_bookings` (metodo POST)
* Reset per-bus: nonce `reset_bus_action` (metodo GET)
* Reset per-settimana: nonce `reset_bus_week_action` (metodo GET)

Il `week_id` passato via URL viene processato con `sanitize_text_field` e
`urldecode` prima dell'uso. Il confronto per il reset è una stretta uguaglianza
di stringhe sull'array in memoria: non ci sono query SQL con input utente.

=== Compatibilità ===

Il plugin usa esclusivamente funzioni WordPress e WooCommerce standard
(`get_post_meta`, `update_post_meta`, `delete_post_meta`, `maybe_unserialize`,
`maybe_serialize`, `wc_get_order_statuses`). Non introduce dipendenze esterne.

Compatibile con WooCommerce HPOS: non accede direttamente alle tabelle SQL
degli ordini.

---

Sviluppato da E3pr0m per Teatro Solare — uso riservato al cliente.
Non distribuire senza autorizzazione scritta.
