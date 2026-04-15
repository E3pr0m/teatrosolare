# CHANGELOG — Teatro Solare Plugin Suite

Piattaforma: WordPress / WooCommerce — prenotazione.teatrosolare.it
Plugin: `teatro-discounts`, `teatro-courses-buses`, `teatro-gestione-pulmini`, `teatro-ripianificazioni`
Sviluppatore: E3pr0m

---

## [1.3.0] — Aprile 2026

### Nuovo plugin standalone

#### FEAT-03 — Teatro Ripianificazioni (`teatro-ripianificazioni/teatro-ripianificazioni.php`) v1.0.0

Nuovo plugin standalone per la **ripianificazione di una singola settimana** all'interno
di un ordine WooCommerce esistente, senza annullare l'ordine né toccare prezzi o pagamenti.

**Problema risolto:** un cliente che ha acquistato N settimane e ha necessità di spostarne
una sola era costretto ad annullare l'intero ordine e rifarlo da zero — pessima UX.
Con questo plugin l'operatore admin può modificare la settimana/bus/fermata dal pannello
ordine in pochi secondi, con traccia completa nella nota ordine.

**Struttura:**
```
wp-content/plugins/
└── teatro-ripianificazioni/
    └── teatro-ripianificazioni.php   ← plugin standalone, classe Teatro_Ripianificazioni
```

**Funzionalità:**

- **Meta box "Ripianifica Settimane"** sulla pagina ordine WooCommerce
  (compatibile HPOS `woocommerce_page_wc-orders` + legacy `shop_order`)
- Per ogni item con settimane prenotate: tabella con indice, date settimana,
  pulmino assegnato, fermata e pulsante "Ripianifica" per riga
- Click "Ripianifica" → modal con:
  - **Settimane disponibili** dal campo ACF `weeks` del prodotto (con posti residui)
  - **Pulmini disponibili** per la nuova settimana selezionata (con posti liberi)
  - **Fermate** del pulmino scelto, con propagazione automatica di orari start/end
  - Caricamento cascata: cambio settimana → ricarica bus; cambio bus → ricarica fermate
- **Operazione atomica** alla conferma:
  1. Parse degli array `@@`-separati dell'item
  2. Sostituzione del solo indice target (week, bus, stop, start_time, end_time)
  3. `update_meta_data()` + `$item->save()` + `$order->save()`
  4. Release posto su ex-bus per la vecchia settimana (filtro per `order_id + week_id`)
  5. Book posto su nuovo bus con `bookBusSeat()` (riuso plugin principale)
  6. Nota ordine con: admin operante, settimana vecchia → nuova, bus vecchio → nuovo, fermata
- Reload automatico della pagina ordine dopo operazione riuscita

**Sicurezza:**
- Tutti gli endpoint AJAX protetti da `check_ajax_referer('ripia_nonce')` + `manage_woocommerce`
- Input sanificati con `sanitize_text_field` e `intval`
- Nonce generato a runtime nel `admin_enqueue_scripts` e passato via `wp_add_inline_script`

**Dipendenze:** `teatro-courses-buses` 1.1.1+ (riusa `getBusesDataByWeek`,
`getAvailableSeatsbyWeek`, `getForamttedDate`, `bookBusSeat`)

**Cosa NON fa:**
- Non crea nuovi ordini, non tocca prezzi o pagamenti
- Non modifica altre settimane dello stesso ordine
- Non interferisce con checkout, carrello, goto_addtocart, goto_2step, goto_3step

> **Nota per il deploy:** attivare dopo `teatro-courses-buses` — dipende dalla variabile
> globale `$WC_custom_teatro_attributes` esposta da quel plugin.

---

*Aggiornato il 15/04/2026 (teatro-ripianificazioni v1.0.0) — E3pr0m*

---

## [1.2.0] — Aprile 2026

### Nuovo plugin standalone

#### FEAT-02 — Teatro Gestione Pulmini (`teatro-gestione-pulmini.php`) v1.0.0

La funzionalità "Giacenza Pulmini" è stata estratta da `teatro-courses-buses.php` e
rilasciata come **plugin WordPress standalone** autonomo e indipendente.

**Motivazione:** separazione delle responsabilità — il plugin di booking gestisce il
flusso di acquisto, il plugin di gestione pulmini è uno strumento operativo per gli
amministratori. Possono essere aggiornati, disabilitati e venduti indipendentemente.

**Struttura:**
```
wp-content/plugins/
└── teatro-gestione-pulmini.php   ← plugin standalone, classe Teatro_Gestione_Pulmini
```

**Funzionalità del pannello (WooCommerce → Gestione Pulmini):**

- **Tabella riepilogativa** per ogni pulmino: nome, capacità, totale prenotati,
  dettaglio prenotazioni suddiviso per settimana
- **Reset globale** — azzera `seats_booked` su tutti i pulmini (con confirm dialog)
- **Reset per-bus** — azzera tutte le settimane di un singolo pulmino (con confirm dialog)
- **Reset per-settimana** *(nuovo)* — rimuove solo le prenotazioni della settimana
  selezionata da un bus specifico, lasciando intatte le altre settimane;
  mostra quante prenotazioni sono state rimosse
- Formattazione date robusta: gestisce il formato corrente `"ts1-ts2"` e il vecchio
  formato aggregato `"ts1-ts2,ts3-ts4"` (prenotazioni pregresse)
- Tutti i reset protetti da WordPress nonce

**Metodo chiave aggiunto: `reset_bus_week_bookings($bus_id, $week_id)`**

Filtra l'array `seats_booked` del bus mantenendo solo le entry con `week_id` diverso
da quello target. Restituisce il numero di prenotazioni rimosse.

**Documentazione:**
- `teatro-gestione-pulmini-readme.txt` — readme in formato standard WordPress con
  descrizione, funzionalità, struttura del dato `seats_booked`, note di sicurezza,
  dipendenze, istruzioni di deploy e changelog versioni.

#### Modifiche a `teatro-courses-buses.php` (v1.1.0 → v1.1.1)

Le seguenti funzioni sono state **rimosse** perché ora gestite dal plugin dedicato:
- `add_bus_inventory_menu()`
- `bus_inventory_page()`
- `reset_all_bus_bookings()`
- `reset_bus_bookings($bus_id)`
- `add_action('admin_menu', ...)` relativo nel costruttore

Il plugin `teatro-courses-buses` rimane responsabile esclusivamente della logica
di booking (prenotazione e rilascio posti), mentre `teatro-gestione-pulmini` gestisce
l'interfaccia operativa admin.

> **Nota per il deploy:** attivare `teatro-gestione-pulmini` prima di aggiornare
> `teatro-courses-buses` per evitare che la voce di menu "Gestione Pulmini"
> scompaia temporaneamente dal backend.

---

*Aggiornato il 15/04/2026 (teatro-gestione-pulmini v1.0.0, readme, teatro-courses-buses v1.1.1, doc tecnica v1.2.0) — E3pr0m*

---

## [1.1.0] — Marzo 2026 (release 26/03/2026)

### Nuovi file
- **teatro-isee-counter.php** (v1.3.0) — Sistema contatori ISEE a doppio livello:
  pool globale per scaglione + limite settimane per singolo figlio.
  Flusso checkout bifasico (riserva pool al `checkout_create_order`,
  assegnazione per-figlio al `checkout_order_created`).
  Decremento automatico su annullamento/rimborso.
  Pannello admin "Sconti ISEE" con card per scaglione, barre di progresso,
  storico ordini e reset individuale per figlio.
  Avvisi frontend non bloccanti in carrello, checkout e dashboard account.

### Modifiche a file esistenti

#### teatro-discounts.php
- Versione bumped da 1.0.0 a 1.1.0
- `init()`: aggiunto `require_once 'teatro-isee-counter.php'`
- `getFeeAppliedArray()`: riscritta da switch/case statico (ISEE vs fratelli)
  a loop dinamico su array `$discounts[]` — pattern winner-takes-all
- `getFeeAppliedArray()`: aggiunto sconto fedeltà come terzo candidato
- `getFeeAppliedArray()`: aggiunto filtro `teatro_isee_supplementary_discount`
  per sconto fedeltà supplementare quando ISEE è parziale
- `validateUserProductEligibility()`: aggiunto filtro `teatro_isee_item_eligibility`
  per applicare controlli pool globale + limite figlio su ogni item del carrello
- `checkConsecutiveDiscount()` (nuovo): sconto fedeltà progressivo per settimane
  consecutive (2ª=15%, 3ª=20%, 4ª e oltre=30%), applicato solo ai prodotti
  con categoria `campi-solari-primaria`
- Sconto fratelli: commentato/disabilitato su richiesta (codice conservato)

#### teatro-discounts-wc.php
- `add_auto_isee_discount()`: riscritta — delega tutto il calcolo a
  `getFeeAppliedArray()` (semplice applicatore di fee)
- Aggiunto guard `is_admin()` per evitare esecuzione inutile in backend

#### teatro-wc-single.php
- Estensione tipo prodotto `courses-noisee` nel frontend:
  `getCBPageHTML_teatro()`, `teatro_bc_add_steps_counter()`,
  `teatrocourses_content()` ora riconoscono entrambi i tipi
- `show_price_tab_for_courses()`: aggiunta classe CSS `show_if_courses-noisee`
- Aggiunto blocco backend completo per `courses-noisee`:
  classe `WC_Product_Courses_Noisee`, voce menu a tendina, mapping WC,
  tab General/Inventory, JS admin_footer
- `update_busseats()`: hookata su `woocommerce_thankyou` per prenotare
  i posti pulmino al completamento dell'ordine

#### teatro-courses-buses.php
- `teatro_release_bus_seats()` (nuova): hookata su
  `woocommerce_order_status_cancelled` e `woocommerce_order_status_refunded` —
  libera automaticamente i posti pulmino e aggiunge nota all'ordine
- `add_bus_inventory_menu()` + `bus_inventory_page()` (nuove):
  pannello admin "Giacenza Pulmini" (sotto-menu WooCommerce) con tabella
  riepilogativa, dettaglio per settimana, reset globale e per-bus con nonce
- `reset_all_bus_bookings()` / `reset_bus_bookings($bus_id)` (nuove)

#### teatro-courses-buses-it_IT.l10n.php
- Aggiunta stringa `'Courses Noisee Product' => 'Attività No ISEE'`
- Riformattazione da minified a pretty-print

#### review-order.php (template WooCommerce)
- Aggiunto testo informativo checkout:
  "Il sistema applica lo sconto spettante maggiore tra sconto ISEE e sconto Fedeltà"

#### functions.php (tema WordPress)
- Label bottone calcolo sconti aggiornata:
  → "Calcola il totale con eventuali sconti"

#### customer-completed-order.php (template email WooCommerce)
- Ridisegno UX email conferma ordine completato:
  hero block con segno di spunta e nome cliente dinamico,
  sezione "Cosa succede ora" (3 step), blocco contatto con mailto,
  footer legale con P.IVA e C.F., tutti gli stili inline (Gmail/Outlook/Apple Mail)
- Creato file di anteprima `preview-completed-order.html` (solo sviluppo, non deployare)

### Configurazione backend (WordPress / ACF / GF)
- CSS sbloccato: rimossi workaround che nascondevano voce ISEE nel menu account
  e fee ISEE nel riepilogo ordine al checkout
- Categoria `campi-solari-primaria` creata e assegnata al prodotto Campi Solari
- Prodotti non Campi Solari esclusi da ISEE (configurazione ACF product_types)
- Campo GF `isee2` aggiunto al form "Edit ISEE" → scrive `isee_certificate = 'isee2'`
- Regola ACF "Courses Product Fields" estesa a `courses-noisee`
- Secondo scaglione ISEE configurato in ACF Theme Settings
  (certificate: `isee2`, label: "Scaglione2 (<10k)")
- Bus 3 messo in bozza

---

## [1.1.0 — post-release] — Aprile 2026 (bug fix 10-12/04/2026)

### Bug fix

#### BUG-01 — Sconto ISEE calcolato su tutto il carrello invece che sulle sole settimane ammesse
**File:** `teatro-discounts.php`, `teatro-isee-counter.php`

Con più item per lo stesso bambino nel carrello (es. 2 item × 2 settimane,
`child_max = 2`), `teatro_isee_dual_cap_check` processava ogni item
indipendentemente leggendo solo il DB (ordini completati). Gli altri item
del carrello non sono nel DB → entrambi gli item ricevevano lo sconto pieno
→ 4 settimane scontate invece di 2 → 60 € invece di 90 €.

- `teatro-discounts.php` — `validateUserProductEligibility()`:
  aggiunto `do_action('teatro_isee_before_cart_pass')` prima del loop
- `teatro-isee-counter.php` — aggiunto globale `$_teatro_cart_child_used`
  con action handler di reset su `teatro_isee_before_cart_pass`
- `teatro-isee-counter.php` — `teatro_isee_calc_allowed_weeks()`:
  aggiunto parametro `$extra_used = []` che somma le settimane già
  assegnate nel carrello al valore DB
- `teatro-isee-counter.php` — `teatro_isee_dual_cap_check()`:
  usa e aggiorna `$_teatro_cart_child_used` ad ogni chiamata

#### BUG-02 — Label sconto scomparso dal carrello dopo BUG-01
**File:** `teatro-discounts.php`

Conseguenza del fix BUG-01: quando il secondo item veniva bloccato,
il filtro azzerava `discount_label` a `''` che sovrascriveva il label
del primo item valido → nel carrello compariva solo il totale senza etichetta.

- `validateUserProductEligibility()`: `$discount_label` aggiornato
  solo se `!empty($is_eligible['discount_label'])`

#### BUG-03 — Disponibilità posti pulmino: un solo posto scalato per N settimane
**File:** `teatro-wc-single.php`, `teatro-courses-buses.php`

`update_busseats()` creava un unico `$booking` con `week_id` = tutte le
settimane concatenate. `alreadyBookedSeats()` cercava settimane singole
nel formato `ts1-ts2` → confronto sempre falso → disponibilità mai decrementata.

- `teatro-wc-single.php` — `update_busseats()`: riscritta per iterare
  settimane e bus in parallelo; crea un booking separato per ogni coppia
  `(settimana[i], bus[i])` con `week_id = getReadableWeekString($week)`
- `teatro-courses-buses.php` — `bookBusSeat()`: semplificata,
  riceve un singolo bus ID per chiamata
- `teatro-courses-buses.php` — `validateOrderAlreadySaved()`:
  aggiunto parametro `$week_id`; il controllo duplicati usa
  la coppia `(order_id + week_id)` per permettere allo stesso ordine
  di prenotare più settimane sullo stesso bus

#### BUG-04 — Date non leggibili nel pannello Gestione Pulmini
**File:** `teatro-courses-buses.php` — `bus_inventory_page()`

Il codice convertiva `timestamp → date('d/m/Y') → stringa con slash →
strtotime()`. PHP interpreta `d/m/Y` come formato US (mese/giorno):
mese 18 non esiste → data vuota. Le prenotazioni precedenti avevano
anche il formato aggregato `ts1-ts2,ts3-ts4` che veniva mostrato grezzo.

- Conversione diretta: `date_i18n(get_option('date_format'), intval($ts))`
- Gestione formato aggregato (vecchie prenotazioni):
  split per virgola, ogni coppia convertita separatamente, mostrate con ` | `

### Modifiche UI

#### UI-01 — Ordine opzioni trasporto invertito
**File:** `teatro-wc-single.php`

Le due righe HTML dei radio button di trasporto sono state invertite:
"Con il pulmino (scelta consigliata)" ora appare prima, "Autonomo" dopo.

#### BUG-05 — Radio button trasporto con `name` identico su più settimane + `$bus_availability` sovrascritta
**File:** `teatro-wc-single.php`

Effetto collaterale di UI-01: con due settimane, il radio "Con pulmino"
della prima settimana non restava selezionato di default.
Causa 1: `$bus_count` non veniva mai incrementato (sempre 0) → tutti i
radio button di tutte le settimane avevano `name="product_bus_option_0"` →
il browser li trattava come un unico gruppo.
Causa 2: `$bus_availability` (aggregata per settimana) veniva sovrascritta
dal loop interno per singolo bus con la disponibilità dell'ultimo bus
processato → `checked_yes`/`checked_no` calcolati sul valore sbagliato.

- Aggiunto `$bus_count++` alla fine del loop esterno per settimana
- Rinominate le variabili: `$week_bus_availability` (aggregata per settimana)
  e `$single_bus_availability` (per singolo bus)
- Aggiornato il controllo finale del messaggio di indisponibilità
  a `$week_bus_availability`

#### BUG-06 — Disponibilità posti corsi: ordini annullati/rimborsati/falliti contati come occupati
**File:** `teatro-courses-buses.php`

`getAllCompltedOrdersForCorses()` recuperava tutti gli stati ordine WooCommerce
tranne `wc-checkout-draft`. Gli ordini con stato `wc-cancelled`, `wc-refunded`
e `wc-failed` venivano quindi contati come posti occupati nel calcolo della
disponibilità settimanale → i posti disponibili mostrati erano inferiori a quelli reali.

Esempio: 40 posti configurati, 7 ordini validi + 6 ordini annullati/rimborsati/falliti
→ sistema mostrava 27 disponibili invece di 33.

- `getAllCompltedOrdersForCorses()`: aggiunti `unset` per
  `wc-cancelled`, `wc-refunded` e `wc-failed` prima della query `wc_get_orders()`

#### BUG-07 — Disponibilità posti bus: ordini con pagamento fallito occupano i posti permanentemente
**File:** `teatro-courses-buses.php`

La prenotazione bus avviene sull'hook `woocommerce_thankyou`, che scatta per qualsiasi
ordine che raggiunge la pagina di conferma — inclusi quelli con pagamento fallito.
Il hook di liberazione `teatro_release_bus_seats` gestiva solo `wc-cancelled` e
`wc-refunded`, ma non `wc-failed`. Risultato: ordini con pagamento fallito occupavano
posti bus in `seats_booked` senza mai essere rimossi.

- `__construct()`: aggiunto
  `add_action('woocommerce_order_status_failed', array($this, 'teatro_release_bus_seats'), 10, 1)`

> **Nota:** I dati storici già presenti in `seats_booked` non vengono corretti
> retroattivamente. La liberazione avviene solo al momento della transizione di stato.
> Per bonificare prenotazioni fantasma pregresse usare il reset dal pannello admin
> "Giacenza Pulmini".

---

#### BUG-08 — Quota associativa annullata: figlio risulta ancora abbonato + impossibile riacquistare
**File:** `teatro-subscriptions-wc.php` (plugin `teatro-subscriptions`)

Due sintomi con la stessa radice:

**Sintomo A:** dopo l'annullamento di un ordine di quota associativa, il sistema
permetteva comunque di procedere con l'acquisto di un corso per quel figlio,
nonostante non fosse più abbonato.

**Sintomo B:** nella pagina della quota associativa, nessun figlio poteva essere
selezionato nel dropdown.

**Causa comune:** `update_child_subscription_order_completed` imposta
`child_subscription_status = 'active'` sul user meta del figlio quando l'ordine
è completato, ma non esisteva nessun hook che resettasse quel meta in caso di
annullamento o rimborso. Di conseguenza:
- `getSubscriptionStatus()` restituiva ancora `'active'` → il figlio era selezionabile
  allo Step 1 dei corsi (Sintomo A)
- Nel dropdown della quota associativa i figli con status `'active'` vengono messi
  `disabled` (per evitare duplicati) → con il meta ancora `'active'` dopo
  l'annullamento entrambi i figli risultavano disabled → impossibile selezionare
  qualcuno (Sintomo B)

- Aggiunta funzione `reset_child_subscription_on_cancel($order_id)` hookata su
  `woocommerce_order_status_cancelled` e `woocommerce_order_status_refunded`
- Al trigger, azzera `child_subscription_status` e `child_subscription_expire_date`
  sul user meta del figlio associato all'ordine
- Aggiunge nota visibile all'ordine in backend con ID figlio coinvolto

> **Nota:** i figli con status rimasto `'active'` da annullamenti pregressi possono
> essere corretti dal pannello admin "Modifica utente" → sezione "Quota Associativa"
> → pulsante "Reimposta stato abbonamento".

#### BUG-08b — Nessuna UI per correggere manualmente lo stato abbonamento pregressi
**File:** `teatro-subscriptions-wc.php` (plugin `teatro-subscriptions`)

Non esisteva alcuna interfaccia nel backend WP per visualizzare o correggere
`child_subscription_status` sui figli con stato rimasto `'active'` da annullamenti
avvenuti prima del fix BUG-08.

- Aggiunta sezione **"Quota Associativa"** nella pagina "Modifica utente" WP
  (hook `show_user_profile` / `edit_user_profile`), visibile solo per utenti
  con ruolo `child` e solo agli amministratori (`manage_options`)
- Mostra stato corrente (verde = active / rosso = non impostato) e data di scadenza
- Pulsante **"Reimposta stato abbonamento"** visibile solo quando status = `active`,
  protetto da nonce; azzera `child_subscription_status` e
  `child_subscription_expire_date`
- Funzione `teatro_handle_child_subscription_reset()` su hook `admin_init`:
  esegue il reset, redirect con parametro `teatro_reset_done`
- Funzione `teatro_subscription_reset_notice()` su hook `admin_notices`:
  mostra banner di conferma dopo il reset

#### BUG-10 — Disponibilità posti corsi: formato data ordini non parsato correttamente
**File:** `teatro-courses-buses.php`

`getAvailableSeatsbyWeek()` confrontava la settimana selezionata (da ACF) con le settimane
salvate negli ordini usando `strtotime()` direttamente sul valore grezzo estratto dall'ordine.
Il valore dell'ordine ha il formato `getForamttedDate()` → con WP date_format italiano `d/m/Y`
produce `"20/07/2026"`. PHP interpreta la barra in `strtotime()` come formato USA (M/D/Y):
mese 20 non esiste → `strtotime` restituisce `false` → la settimana non fa mai match →
posti sempre a 0.
Per `week_selected` (da ACF) invece veniva già applicato `str_replace('/','-')` prima di
`strtotime`, producendo `"20-07-2026"` (d-m-Y) che PHP parsifica correttamente.
Il disallineamento causava falsi zero per i prodotti con ordini nel nuovo formato,
e falsi positivi per ordini nel vecchio formato (date inglesi tipo "20 July 2026"
o formato USA "07/20/2026").

Effetto osservato: Campi Solari Infanzia mostrava 30/30 disponibili nonostante 2 ordini
reali; Campi Solari Primaria contava 2 prenotazioni in eccesso.

- `getAvailableSeatsbyWeek()`: applicato `str_replace('/','-', trim(...))` a entrambe
  le parti della data ordine prima di `strtotime`, speculare alla logica già usata
  per `week_selected`

#### BUG-09 — Disponibilità posti corsi: contaminazione cross-prodotto
**File:** `teatro-courses-buses.php`, `teatro-wc-single.php`

`getAllCompltedOrdersForCorses()` raccoglieva gli item di tutti i prodotti di tipo `courses`
e `courses-noisee` presenti negli ordini, senza filtrare per prodotto specifico.
`getAvailableSeatsbyWeek()` confrontava poi le settimane di tutti questi item contro
la settimana selezionata → settimane di prodotti diversi (es. Campi Solari Secondaria)
venivano conteggiate come posti occupati di Campi Solari Primaria.

Esempio: 40 posti configurati, 7 ordini validi su Campi Solari Primaria + N ordini
su altri prodotti con la stessa settimana → sistema mostrava meno posti del reale.

- `getAvailableSeatsbyWeek()`: aggiunto parametro `$pid` (product ID), passato a
  `getAllCompltedOrdersForCorses()`
- `getAllCompltedOrdersForCorses()`: aggiunto parametro `$pid`; quando valorizzato,
  salta gli item con `get_product_id() != $pid`
- `teatro-wc-single.php` — `getCBStep2HTML()`: aggiornata la chiamata a
  `getAvailableSeatsbyWeek($week, false, $pid)`

---

#### BUG-11 — Sconto ISEE e sconto fedeltà applicati contemporaneamente
**File:** `teatro-discounts.php`

In presenza di settimane bloccate dal limite per figlio (`child_max`), il blocco
supplementare di `getFeeAppliedArray()` aggiungeva lo sconto fedeltà sulle settimane
bloccate anche quando ISEE aveva già vinto come sconto maggiore — risultando in una
somma dei due sconti, in contraddizione con la regola "si applica lo sconto maggiore".

- `getFeeAppliedArray()`: commentato il blocco `teatro_isee_supplementary_discount`
  che combinava ISEE + fedeltà; ora viene applicato un solo sconto (il maggiore).

> **Nota:** il codice del blocco supplementare è conservato nei commenti per un
> eventuale ripristino. L'impatto è limitato allo scenario `child_max > 0` con
> più settimane in carrello del limite per figlio: le settimane eccedenti non
> ricevono più lo sconto fedeltà come compensazione.

---

#### BUG-12 — Contatori ISEE incrementati anche quando vince lo sconto fedeltà
**File:** `teatro-isee-counter.php`

`teatro_isee_reserve_pool()` verificava solo che l'utente fosse eleggibile ISEE
(`validateUserProductEligibility() > 0`), senza controllare se lo sconto ISEE
avesse effettivamente vinto il confronto con la fedeltà. Risultato: anche negli
ordini in cui veniva applicato lo sconto fedeltà (maggiore dell'ISEE), il pool
globale e il contatore per-figlio venivano decrementati — consumando quota ISEE
senza che il cliente l'avesse mai usata.

In caso di rimborso il problema si auto-correggeva (decremento compensava
l'incremento errato). Ma per ordini completati e non rimborsati il figlio
risultava aver "usato" settimane ISEE inesistenti, bloccando futuri acquisti
con sconto ISEE.

- `teatro_isee_reserve_pool()`: aggiunto controllo `getFeeAppliedArray()`
  dopo la verifica di eleggibilità; se la chiave `isee` è assente (ha vinto
  la fedeltà), la funzione esce senza toccare i contatori.

---

#### BUG-13 — Disponibilità posti bus: conteggio errato per ordini senza pagina thank-you + week_id sempre "-"
**File:** `teatro-courses-buses.php`, `teatro-wc-single.php`

Due cause distinte producevano la stessa discrepanza (es. Bus1 24 posti, 8 prenotazioni, frontend mostrava 18 invece di 16):

**Causa A — `seats_booked` non aggiornato per ordini che non passano da thank-you**

`update_busseats()` era agganciato solo a `woocommerce_thankyou`, che scatta esclusivamente quando il cliente carica fisicamente la pagina di conferma. Per pagamenti con bonifico, PayPal in attesa o browser chiuso prima del redirect, i posti non venivano mai scritti in `seats_booked` → `getBusAvailability` mostrava più disponibilità rispetto al reale.

**Causa B — `getReadableWeekString` produceva sempre `"-"` su date `d/m/Y`**

`alreadyBookedSeats()` confrontava la settimana tramite `getReadableWeekString()`, che chiama `strtotime()` su date in formato `d/m/Y` (es. `"22/06/2025"`) senza prima convertire le barre in trattini. PHP interpreta le barre come M/D/Y → mese 22 non esiste → `false`. Il `week_id` salvato era sempre `"-"` → nessun filtro per settimana effettivo → si contavano i posti del bus su tutte le settimane invece che solo quella richiesta.

**Fix:**

- `teatro-courses-buses.php` — `getBusAvailability()`: riscritta per usare il nuovo metodo `countBookedBusSeatsByWeek()`
- `teatro-courses-buses.php` — nuovo metodo `countBookedBusSeatsByWeek($bus_id, $week)`: legge direttamente dagli ordini WooCommerce (stessa fonte e stessi filtri di stato di `getAvailableSeatsbyWeek()`); usa `preg_split('/\s*-\s*/', ..., 2)` + `str_replace('/', '-', ...)` prima di `strtotime` (speculare al metodo già corretto per le settimane); confronta bus e settimana come indici paralleli in `product_buses_selected` / `product_weeks_selected`
- `teatro-wc-single.php` — `update_busseats()`: aggiunto hook `woocommerce_new_order` per registrare i posti immediatamente alla creazione dell'ordine (il controllo duplicati in `validateOrderAlreadySaved` previene doppie prenotazioni)
- `teatro-wc-single.php` — Step 3: rimossa chiamata ridondante a `getBusAvailability($bus_id)` senza `$week`; `$disabled_seats` usa ora direttamente `$bus['seats']` già calcolato con la settimana corretta

> **Nota:** gli ordini pregressi con posti non registrati in `seats_booked` vengono ora contati correttamente poiché il nuovo metodo legge dagli ordini WooCommerce. Nessuna migrazione necessaria.

---

### Nuove funzionalità

#### FEAT-01b — Report Sconti: normalizzazione label fee in sentence case
**File:** `teatro-discounts-report/teatro-discounts-report.php` v1.0.4

Le fee WooCommerce vengono salvate in maiuscolo su DB (es. "SCONTO FEDELTà SETTIMANE")
perché `teatro-discounts-wc.php` applica `strtoupper()` a tutti i label prima di
chiamare `add_fee()`. Il report ora normalizza il nome della fee in sentence case
prima di visualizzarlo nella colonna "Descrizione" della tabella dettaglio.

- Aggiunta normalizzazione UTF-8-safe nel loop `get_fees()`:
  `mb_strtolower()` + `mb_strtoupper()` sul primo carattere
- La normalizzazione avviene **dopo** il rilevamento del tipo sconto
  (`stripos` è case-insensitive: non impattato)
- I codici coupon non sono toccati (`strtoupper($coupon->get_code())` resta invariato)
- Effetto limitato alla visualizzazione nel report; il valore nel DB rimane invariato

> **Nota:** la stessa normalizzazione alla sorgente (in `teatro-discounts-wc.php`)
> non è stata applicata perché il cambiamento avrebbe effetto solo sugli ordini
> futuri. Il report v1.0.4 gestisce già correttamente sia vecchi che nuovi ordini.

---

#### FEAT-01 — Report Sconti (pannello admin WooCommerce)
**Nuovo plugin:** `teatro-discounts-report/teatro-discounts-report.php` v1.0.0

Il modulo è stato sviluppato e rilasciato come **plugin WordPress standalone**
nella cartella `teatro-discounts-report/`, completo di `readme.txt` con
documentazione tecnica, versioni minime richieste, changelog e note di licenza.

Aggiunge il sottomenu **Report Sconti** in WooCommerce → Report Sconti.
Visibile ad Amministratori e Shop Manager. Completamente read-only: non scrive
nulla sul database, legge esclusivamente tramite le API WooCommerce native.
Compatibile con WooCommerce HPOS.

- **Filtri:** intervallo di date (dal/al), tipo sconto (Tutti / ISEE / Fedeltà / Coupon),
  stato ordine (Tutti / Completato / In lavorazione / Rimborsato / Annullato)
- **Cards riepilogo:** ordini con sconto, totale scontato, breakdown per tipo
  (ISEE / Fedeltà / Coupon)
- **Tabella dettaglio:** ogni riga mostra ordine (link clickable), data, cliente,
  tipo sconto con pill colorato, descrizione fee/coupon, importo sconto, totale
  ordine, stato
- Identificazione tipo sconto basata sul label della fee:
  contiene "ISEE" → ISEE (viola), contiene "fedelt" → Fedeltà (ambra),
  coupon WooCommerce → Coupon (rosso)

**Struttura cartella:**
```
wp-content/plugins/
└── teatro-discounts-report/
    ├── teatro-discounts-report.php   ← plugin principale
    └── readme.txt                    ← documentazione tecnica
```

#### BUG-14 — Contatori ISEE non decrementati dopo rimborso manuale
**File:** `teatro-isee-counter.php`

`teatro_isee_decrement()` era agganciata solo a `woocommerce_order_status_refunded`,
che è un hook di transizione di stato: si attiva **esclusivamente** quando lo stato
dell'ordine passa fisicamente a "refunded". Questo non avviene in due scenari reali:

- **Rimborso manuale** (senza gateway): WooCommerce crea il rimborso ma in alcune
  versioni/configurazioni non cambia automaticamente lo stato — l'admin deve farlo a mano.
- **Rimborso parziale**: lo stato rimane su "processing"/"completed"; il hook non scatta.

In entrambi i casi i contatori ISEE (pool globale e per-figlio) rimanevano invariati
nonostante il rimborso, bloccando futuri acquisti con sconto ISEE per quei bambini.

- Aggiunto hook su `woocommerce_order_fully_refunded` (si attiva dentro `wc_create_refund()`
  quando l'importo residuo arriva a zero, indipendentemente dalla transizione di stato)
  come meccanismo di fallback
- La guardia `_teatro_isee_counted` già presente previene doppi decrementi nel caso
  in cui entrambi gli hook scattino nello stesso flusso (rimborso completo via gateway)

---

*Documento generato il 12/04/2026 — aggiornato il 14/04/2026 (FEAT-01 plugin standalone, BUG-14, FEAT-01b sentence case label) — E3pr0m*
