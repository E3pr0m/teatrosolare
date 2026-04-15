=== Teatro Discounts Report ===
Contributors:      e3pr0m
Author:            E3pr0m
Author URI:        https://www.shambix.com
Plugin URI:        https://prenotazione.teatrosolare.it
Requires at least: 6.0
Tested up to:      6.7
Requires PHP:      8.0
WC requires at least: 7.0
WC tested up to:      9.x
Version:           1.0.4
License:           Proprietario — uso riservato Teatro Solare
License URI:       https://prenotazione.teatrosolare.it

Pannello di reportistica sconti per la piattaforma Teatro Solare.
Sviluppato su misura — non distribuire.

== Descrizione ==

Teatro Discounts Report aggiunge alla bacheca WooCommerce un pannello dedicato
alla visualizzazione e all'analisi degli sconti applicati sugli ordini della
piattaforma Teatro Solare (prenotazione.teatrosolare.it).

Il pannello è accessibile da WooCommerce → Report Sconti ed è visibile agli
utenti con ruolo Amministratore o Shop Manager.

=== Funzionalità ===

* Filtro per intervallo di date (dal / al)
* Filtro per tipo di sconto: Tutti / ISEE / Fedeltà / Coupon
* Filtro per stato ordine: Tutti / Completato / In lavorazione / Rimborsato / Annullato
* Card di riepilogo: numero ordini con sconto, totale scontato, breakdown per tipo
* Tabella dettaglio con colonne: Ordine (link clickable), Data, Cliente, Tipo sconto
  (pill colorato), Descrizione fee/coupon, Importo sconto, Totale ordine, Stato
* Identificazione automatica del tipo di sconto dal nome della fee WooCommerce:
  - contiene "ISEE"   → sconto ISEE (viola)
  - contiene "fedelt" → sconto Fedeltà (ambra)
  - coupon WooCommerce → Coupon (rosso)

=== Integrazione con il plugin Teatro Discounts ===

Questo plugin è il modulo di reportistica di `teatro-discounts` (v1.1.0).
Legge i dati direttamente dagli ordini WooCommerce tramite le API native
`get_fees()` e `get_coupons()`: non richiede tabelle proprie né configurazione
aggiuntiva.

Compatibile con WooCommerce HPOS (High-Performance Order Storage).

== Installazione ==

1. Caricare la cartella `teatro-discounts-report` nella directory
   `wp-content/plugins/` del sito.
2. Attivare il plugin dalla bacheca WordPress → Plugin.
3. Il pannello sarà disponibile in WooCommerce → Report Sconti.

In alternativa, il file può essere incluso direttamente da `teatro-discounts.php`
tramite `require_once 'teatro-discounts-report.php'` nel metodo `init()`.

== Dipendenze ==

* WordPress 6.0 o superiore
* WooCommerce 7.0 o superiore
* Plugin `teatro-discounts` v1.1.0 (per la generazione delle fee sugli ordini)

== Changelog ==

= 1.0.4 — Aprile 2026 =
* Fix cosmético: il nome della fee viene normalizzato in sentence case prima della
  visualizzazione (`mb_strtolower` + prima lettera maiuscola, UTF-8 safe).
  Esempio: "SCONTO FEDELTà SETTIMANE" → "Sconto fedeltà settimane".
  Il rilevamento del tipo sconto non è impattato (avviene prima della normalizzazione).

= 1.0.3 — Aprile 2026 =
* Aggiunta card **Totale incassato**: somma i totali degli ordini unici che hanno
  ricevuto uno sconto (escluso doppio conteggio per ordini con fee + coupon).

= 1.0.2 — Aprile 2026 =
* Fix: aggiunto `'type' => 'shop_order'` alla query `wc_get_orders()` per escludere
  gli ordini di tipo `shop_order_refund`. Con HPOS attivo, `wc_get_orders` con stato
  `wc-refunded` restituiva anche gli ordini-figlio di rimborso (`OrderRefund`) che
  non espongono i metodi billing — causando fatal error su `get_billing_first_name()`.

= 1.0.1 — Aprile 2026 =
* Fix: sostituito `$order->get_coupon_discount_amount()` con `$coupon->get_discount()`
  per compatibilità con WooCommerce HPOS (`Automattic\WooCommerce\Admin\Overrides\Order`
  non espone il metodo legacy).

= 1.0.0 — Aprile 2026 =
* Prima release. Pannello Report Sconti con filtri, card riepilogo e tabella dettaglio.
* Supporto a sconti ISEE, Fedeltà e Coupon WooCommerce.
* Compatibilità WooCommerce HPOS.

== Note tecniche ==

Il tipo di sconto viene determinato in fase di lettura dell'ordine analizzando
il nome delle fee negative (`get_fees()`). Non vengono scritti dati aggiuntivi
sul database: il plugin è completamente read-only rispetto agli ordini.

Sviluppato da E3pr0m per Teatro Solare — uso riservato al cliente.
Non distribuire senza autorizzazione scritta.
