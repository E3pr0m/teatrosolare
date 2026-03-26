<?php
/**
 * Teatro ISEE Counter v1.3.0
 *
 * Due contatori indipendenti per ogni scaglione ISEE:
 *
 * ┌─────────────────────────────────────────────────────────────────────┐
 * │ LIVELLO 1 – POOL GLOBALE PER SCAGLIONE                              │
 * │   Settimane totali disponibili per lo scaglione.                    │
 * ├─────────────────────────────────────────────────────────────────────┤
 * │ LIVELLO 2 – LIMITE PER SINGOLO FIGLIO                               │
 * │   Settimane massime per ogni singolo figlio.                        │
 * └─────────────────────────────────────────────────────────────────────┘
 *
 * PRINCIPIO FONDAMENTALE v1.3.0:
 *   I child_id vengono usati ESATTAMENTE come li restituisce
 *   extractMultipleSelections() su parent_childs_selected — senza nessuna
 *   validazione o conversione. Sono gli stessi identificatori usati dal
 *   sistema esistente (es. checkSD_EligibilityFromCart), quindi coerenti.
 *
 *   La risoluzione del nome è separata dal contatore:
 *   se il nome non si trova, si mostra l'ID — ma il contatore funziona sempre.
 *
 * FLUSSO:
 *   CHECKOUT CREATE ORDER  → riserva pool globale (totale settimane)
 *   CHECKOUT ORDER CREATED → assegna per-figlio leggendo order items
 *   ANNULLATO / RIMBORSATO → restituisce settimane a pool e per-figlio
 *   CARRELLO / CHECKOUT    → blocco effettivo se limite raggiunto
 *
 * @version 1.3.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/* =============================================================================
 * COSTANTI
 * ============================================================================= */

if ( ! defined( 'TEATRO_ISEE_POOL_MAX'  ) ) define( 'TEATRO_ISEE_POOL_MAX',  'teatro_isee_pool_max_'  );
if ( ! defined( 'TEATRO_ISEE_POOL_USED' ) ) define( 'TEATRO_ISEE_POOL_USED', 'teatro_isee_pool_used_' );
if ( ! defined( 'TEATRO_ISEE_CHILD_MAX' ) ) define( 'TEATRO_ISEE_CHILD_MAX', 'teatro_isee_child_max_' );
if ( ! defined( 'TEATRO_ISEE_CHILD_META') ) define( 'TEATRO_ISEE_CHILD_META','teatro_isee_child_'     );

/* =============================================================================
 * 1. HELPERS – POOL GLOBALE
 * ============================================================================= */

function teatro_pool_max_key( $cert )  { return TEATRO_ISEE_POOL_MAX  . sanitize_key( strtolower( trim($cert) ) ); }
function teatro_pool_used_key( $cert ) { return TEATRO_ISEE_POOL_USED . sanitize_key( strtolower( trim($cert) ) ); }

function teatro_get_pool_max( $cert )  { return (int) get_option( teatro_pool_max_key($cert),  0 ); }
function teatro_get_pool_used( $cert ) { return (int) get_option( teatro_pool_used_key($cert), 0 ); }

function teatro_get_pool_remaining( $cert ) {
	$max = teatro_get_pool_max( $cert );
	if ( $max <= 0 ) return null;
	return max( 0, $max - teatro_get_pool_used($cert) );
}

function teatro_pool_is_exhausted( $cert ) {
	$max = teatro_get_pool_max( $cert );
	if ( $max <= 0 ) return false;
	return teatro_get_pool_used($cert) >= $max;
}

/* =============================================================================
 * 2. HELPERS – LIMITE PER FIGLIO
 * ============================================================================= */

function teatro_child_max_key( $cert )  { return TEATRO_ISEE_CHILD_MAX  . sanitize_key( strtolower( trim($cert) ) ); }
function teatro_child_meta_key( $cert ) { return TEATRO_ISEE_CHILD_META . sanitize_key( strtolower( trim($cert) ) ); }

function teatro_get_child_max( $cert ) { return (int) get_option( teatro_child_max_key($cert), 0 ); }

function teatro_get_child_used( $user_id, $cert, $child_id ) {
	$data = get_user_meta( $user_id, teatro_child_meta_key($cert), true );
	return (int)( ( is_array($data) ? $data : [] )[ $child_id ] ?? 0 );
}

function teatro_get_all_children_usage( $user_id, $cert ) {
	$data = get_user_meta( $user_id, teatro_child_meta_key($cert), true );
	return is_array($data) ? $data : [];
}

function teatro_get_child_remaining( $user_id, $cert, $child_id ) {
	$max = teatro_get_child_max( $cert );
	if ( $max <= 0 ) return null;
	return max( 0, $max - teatro_get_child_used($user_id, $cert, $child_id) );
}

function teatro_update_child_used( $user_id, $cert, $child_id, $delta ) {
	$key  = teatro_child_meta_key( $cert );
	$data = teatro_get_all_children_usage( $user_id, $cert );
	$new  = max( 0, (int)( $data[$child_id] ?? 0 ) + (int)$delta );
	if ( $new === 0 ) unset($data[$child_id]); else $data[$child_id] = $new;
	update_user_meta( $user_id, $key, $data );
}

/* =============================================================================
 * 3. HELPERS – GENERALI
 * ============================================================================= */

function teatro_get_all_isee_tiers() {
	$isees = get_field( 'isee_settings', 'option' );
	return is_array($isees) ? $isees : [];
}

/**
 * Estrae coppie [ child_id => weeks ] da un item (cart o order).
 *
 * @param  string $weeks_raw    valore grezzo di product_weeks_selected
 * @param  string $children_raw valore grezzo di parent_childs_selected
 * @return array  [ ['child_id'=>string, 'weeks'=>int], ... ]
 */
function teatro_extract_child_weeks_from_raw( $weeks_raw, $children_raw ) {
	global $WC_custom_teatro_attributes;

	if ( ! isset($WC_custom_teatro_attributes) || empty($weeks_raw) ) {
		// Fallback: 1 settimana, usa il primo valore di children_raw o 'unknown'
		$cid = !empty($children_raw) ? trim((string)$children_raw) : 'unknown';
		return [ ['child_id' => $cid ?: 'unknown', 'weeks' => 1] ];
	}

	$weeks    = $WC_custom_teatro_attributes->extractMultipleSelections($weeks_raw);
	$children = !empty($children_raw)
		? $WC_custom_teatro_attributes->extractMultipleSelections($children_raw)
		: [];

	if ( empty($weeks) ) return [];

	/*
	 * Identifica il primo child_id non vuoto come fallback.
	 * Non validiamo il tipo: usiamo i valori esattamente come
	 * li restituisce extractMultipleSelections (stessa logica di
	 * checkSD_EligibilityFromCart nel plugin principale).
	 */
	$first_valid = 'unknown';
	foreach ($children as $c) {
		$c = trim((string)$c);
		if ( $c !== '' && $c !== '0' ) { $first_valid = $c; break; }
	}

	$grouped = [];
	foreach ($weeks as $k => $week) {
		$raw = isset($children[$k]) ? trim((string)$children[$k]) : '';
		$cid = ( $raw !== '' && $raw !== '0' ) ? $raw : $first_valid;
		$grouped[$cid] = ($grouped[$cid] ?? 0) + 1;
	}

	$result = [];
	foreach ($grouped as $cid => $count)
		$result[] = ['child_id' => $cid, 'weeks' => $count];

	return $result;
}

/**
 * Wrapper per cart item.
 */
function teatro_extract_child_weeks( $cart_item ) {
	return teatro_extract_child_weeks_from_raw(
		$cart_item['product_weeks_selected'] ?? '',
		$cart_item['parent_childs_selected'] ?? ''
	);
}

/**
 * Wrapper per order item.
 */
function teatro_extract_child_weeks_from_order_item( $order_item ) {
	return teatro_extract_child_weeks_from_raw(
		$order_item->get_meta('product_weeks_selected'),
		$order_item->get_meta('parent_childs_selected')
	);
}

/**
 * Restituisce il nome leggibile di un figlio.
 *
 * I figli sono WP users con ruolo 'child', creati tramite GF User Registration.
 * Il child_id è il WP user ID del figlio (valore in parent_childs_selected).
 * GF User Registration popola first_name e last_name come standard WP user meta.
 *
 * Cache statica per evitare query ripetute nella stessa request.
 */
function teatro_get_child_name( $child_id ) {
	if ( empty($child_id) || $child_id === 'unknown' ) return __('Figlio', 'teatro-discounts');
	if ( ! is_numeric($child_id) ) return (string)$child_id;

	static $cache = [];
	if ( isset($cache[$child_id]) ) return $cache[$child_id];

	$pid  = (int)$child_id;
	$user = get_user_by('id', $pid);

	if ( ! $user ) {
		$cache[$child_id] = '#' . $child_id;
		return $cache[$child_id];
	}

	// GF User Registration popola first_name e last_name come standard WP user meta
	$fn = get_user_meta($pid, 'first_name', true);
	$ln = get_user_meta($pid, 'last_name',  true);
	if ( !empty($fn) || !empty($ln) ) {
		$cache[$child_id] = trim("$fn $ln");
		return $cache[$child_id];
	}

	// Fallback: display_name WP
	if ( !empty($user->display_name) ) {
		$cache[$child_id] = $user->display_name;
		return $cache[$child_id];
	}

	$cache[$child_id] = '#' . $child_id;
	return $cache[$child_id];
}

/* =============================================================================
 * 4. CALCOLO SETTIMANE AMMESSE
 *
 * Per ogni figlio nel cart item calcola quante settimane ottengono lo
 * sconto, rispettando limite-figlio e pool globale.
 *
 * Restituisce:
 * [
 *   'per_child' => [ child_id => ['requested'=>int, 'allowed'=>int, 'blocked'=>int] ],
 *   'total_requested' => int,
 *   'total_allowed'   => int,
 *   'total_blocked'   => int,
 * ]
 * ============================================================================= */

function teatro_isee_calc_allowed_weeks( $cart_item, $certificate, $user_id ) {
	$child_weeks = teatro_extract_child_weeks($cart_item);
	$child_max   = teatro_get_child_max($certificate);
	$pool_rem    = teatro_get_pool_remaining($certificate);

	$per_child     = [];
	$total_allowed = 0;

	foreach ($child_weeks as $entry) {
		$cid       = $entry['child_id'];
		$requested = (int)$entry['weeks'];

		if ( $child_max > 0 && $user_id ) {
			$used      = teatro_get_child_used($user_id, $certificate, $cid);
			$remaining = max(0, $child_max - $used);
			$allowed   = min($requested, $remaining);
		} else {
			$allowed = $requested;
		}

		$per_child[$cid] = [
			'requested' => $requested,
			'allowed'   => $allowed,
			'blocked'   => $requested - $allowed,
		];
		$total_allowed += $allowed;
	}

	// Tetto pool globale: taglia dall'ultimo figlio se necessario
	if ( $pool_rem !== null && $total_allowed > $pool_rem ) {
		$overflow = $total_allowed - $pool_rem;
		foreach ( array_reverse(array_keys($per_child)) as $cid ) {
			if ( $overflow <= 0 ) break;
			$cut = min($overflow, $per_child[$cid]['allowed']);
			$per_child[$cid]['allowed']  -= $cut;
			$per_child[$cid]['blocked']  += $cut;
			$overflow      -= $cut;
			$total_allowed -= $cut;
		}
	}

	$total_requested = array_sum(array_column($child_weeks, 'weeks'));

	return [
		'per_child'       => $per_child,
		'total_requested' => $total_requested,
		'total_allowed'   => $total_allowed,
		'total_blocked'   => $total_requested - $total_allowed,
	];
}

/* =============================================================================
 * 5. BLOCCO CARRELLO E CHECKOUT
 *
 * Usa wc_add_notice('error') che è BLOCCANTE: impedisce fisicamente
 * di procedere al checkout o completare l'ordine.
 * ============================================================================= */

function teatro_isee_get_cart_errors( $user_id = null, $cart = null ) {
	if ( $user_id === null ) $user_id = get_current_user_id();
	if ( ! $user_id ) return [];

	$certificate = get_user_meta($user_id, 'isee_certificate', true);
	if ( empty($certificate) ) return [];

	// Verifica certificato non scaduto
	global $teatro_discounts;
	if ( isset($teatro_discounts) ) {
		$isee_data = $teatro_discounts->getISEECertificateTeatro($certificate);
		if ( empty($isee_data) || ( isset($isee_data['expire_date_timestamp']) && $isee_data['expire_date_timestamp'] < time() ) )
			return [];
	}

	if ( $cart === null ) $cart = WC()->cart;
	$errors = [];

	// Controllo 1: pool esaurito
	if ( teatro_pool_is_exhausted($certificate) ) {
		$label = $certificate;
		foreach (teatro_get_all_isee_tiers() as $t) {
			if ( strtolower(trim($t['certificate'])) === strtolower(trim($certificate)) ) {
				$label = $t['discount_label'] ?? $certificate; break;
			}
		}
		$errors[] = sprintf(
			__('Sconti ISEE esauriti. Le settimane in sconto per la fascia <strong>%s</strong> sono terminate per questa stagione.', 'teatro-discounts'),
			esc_html($label)
		);
		return $errors;
	}

	// Controllo 2: limite per figlio
	$child_max = teatro_get_child_max($certificate);
	if ( $child_max <= 0 || !$cart ) return $errors;

	foreach ($cart->get_cart() as $cart_item) {
		if ( isset($teatro_discounts) ) {
			$subtotal    = $teatro_discounts->get_product_subtotal($cart_item['data'], $cart_item['quantity']);
			$is_eligible = $teatro_discounts->validateProductEligibilty($cart_item['product_id'], $subtotal);
			if ( empty($is_eligible['discount_amount']) || $is_eligible['discount_amount'] <= 0 ) continue;
		}

		$calc = teatro_isee_calc_allowed_weeks($cart_item, $certificate, $user_id);

		foreach ($calc['per_child'] as $cid => $data) {
			if ( $data['blocked'] <= 0 ) continue;

			$name      = teatro_get_child_name($cid);
			$remaining = teatro_get_child_remaining($user_id, $certificate, $cid);

			if ( $remaining !== null && $remaining <= 0 ) {
				$errors[] = sprintf(
					__('<strong>%s</strong> ha già utilizzato tutte le %d settimane disponibili con sconto ISEE (%s). L\'acquisto procede senza sconto ISEE per questo figlio.', 'teatro-discounts'),
					esc_html($name), $child_max, strtoupper($certificate)
				);
			} else {
				$errors[] = sprintf(
					__('<strong>%s</strong>: richieste %d settimane con sconto ISEE (%s) ma ne sono disponibili solo %d.', 'teatro-discounts'),
					esc_html($name), $data['requested'], strtoupper($certificate), max(0, $remaining ?? 0)
				);
			}
		}
	}

	return array_unique($errors);
}

/*
 * I controlli ISEE NON bloccano l'acquisto.
 * Il filtro teatro_isee_dual_cap_check azzera già lo sconto per gli item
 * che superano il limite: l'acquisto procede normalmente ma senza sconto ISEE,
 * lasciando spazio agli altri sconti (fedeltà settimane, fratelli, ecc.).
 *
 * Mostriamo solo notice informative (non errori bloccanti) nel carrello.
 */
add_action( 'woocommerce_before_cart', 'teatro_isee_inform_cart' );
function teatro_isee_inform_cart() {
	if ( ! is_user_logged_in() ) return;
	teatro_isee_add_notices_once();
}

add_action( 'woocommerce_before_checkout_form', 'teatro_isee_inform_checkout' );
function teatro_isee_inform_checkout() {
	if ( ! is_user_logged_in() ) return;
	teatro_isee_add_notices_once();
}

/**
 * Aggiunge le notice ISEE una sola volta per request, evitando duplicati.
 */
function teatro_isee_add_notices_once() {
	static $done = false;
	if ( $done ) return;
	$done = true;

	$notices = teatro_isee_get_cart_notices();
	// Ulteriore deduplicazione: confronta con le notice già presenti in sessione WC
	$existing = wc_get_notices('notice');
	$existing_texts = array_column( $existing ?: [], 'notice' );

	foreach ( $notices as $notice ) {
		// Evita di aggiungere la stessa stringa due volte
		$already = false;
		foreach ( $existing_texts as $ex ) {
			if ( strip_tags($ex) === strip_tags($notice) ) { $already = true; break; }
		}
		if ( ! $already ) {
			wc_add_notice( $notice, 'notice' );
			$existing_texts[] = $notice;
		}
	}
}

/**
 * Genera messaggi informativi (non bloccanti) sullo stato dello sconto ISEE.
 * Usato da carrello e checkout per informare il cliente senza bloccare l'acquisto.
 */
function teatro_isee_get_cart_notices( $user_id = null, $cart = null ) {
	if ( $user_id === null ) $user_id = get_current_user_id();
	if ( ! $user_id ) return [];

	$certificate = get_user_meta($user_id, 'isee_certificate', true);
	if ( empty($certificate) ) return [];

	global $teatro_discounts;
	if ( isset($teatro_discounts) ) {
		$isee_data = $teatro_discounts->getISEECertificateTeatro($certificate);
		if ( empty($isee_data) || ( isset($isee_data['expire_date_timestamp']) && $isee_data['expire_date_timestamp'] < time() ) )
			return [];
	}

	if ( $cart === null ) $cart = WC()->cart;
	$notices = [];

	// Pool globale esaurito
	if ( teatro_pool_is_exhausted($certificate) ) {
		$label = $certificate;
		foreach (teatro_get_all_isee_tiers() as $t) {
			if ( strtolower(trim($t['certificate'])) === strtolower(trim($certificate)) ) {
				$label = $t['discount_label'] ?? $certificate; break;
			}
		}
		$notices[] = sprintf(
			__('Sconti ISEE esauriti. Le settimane in sconto per la fascia <strong>%s</strong> sono terminate. L\'acquisto è comunque possibile.', 'teatro-discounts'),
			esc_html($label)
		);
		return $notices;
	}

	// Limite per figlio parzialmente o totalmente raggiunto
	$child_max = teatro_get_child_max($certificate);
	if ( $child_max <= 0 || ! $cart ) return $notices;

	foreach ($cart->get_cart() as $cart_item) {
		if ( isset($teatro_discounts) ) {
			$subtotal    = $teatro_discounts->get_product_subtotal($cart_item['data'], $cart_item['quantity']);
			$is_eligible = $teatro_discounts->validateProductEligibilty($cart_item['product_id'], $subtotal);
			if ( empty($is_eligible['discount_amount']) || $is_eligible['discount_amount'] <= 0 ) continue;
		}

		$calc = teatro_isee_calc_allowed_weeks($cart_item, $certificate, $user_id);

		foreach ($calc['per_child'] as $cid => $data) {
			if ( $data['blocked'] <= 0 ) continue;

			$name      = teatro_get_child_name($cid);
			$remaining = teatro_get_child_remaining($user_id, $certificate, $cid);

			if ( $remaining !== null && $remaining <= 0 ) {
				$notices[] = sprintf(
					__('<strong>%s</strong> ha già utilizzato tutte le %d settimane con sconto ISEE (%s). L\'acquisto procede senza sconto ISEE per questo figlio.', 'teatro-discounts'),
					esc_html($name), $child_max, strtoupper($certificate)
				);
			} else {
				$notices[] = sprintf(
					__('<strong>%s</strong>: solo %d settimane su %d riceveranno lo sconto ISEE (%s). Le restanti procedono senza sconto ISEE.', 'teatro-discounts'),
					esc_html($name), $data['allowed'], $data['requested'], strtoupper($certificate)
				);
			}
		}
	}

	return array_unique($notices);
}

/* =============================================================================
 * 6. FILTRO SCONTO PARZIALE
 * ============================================================================= */

add_filter( 'teatro_isee_item_eligibility', 'teatro_isee_dual_cap_check', 10, 3 );
function teatro_isee_dual_cap_check( $eligibility, $certificate, $cart_item ) {
	if ( empty($eligibility['discount_amount']) || empty($certificate) ) return $eligibility;

	$user_id = get_current_user_id();
	$calc    = teatro_isee_calc_allowed_weeks($cart_item, $certificate, $user_id);

	if ( $calc['total_allowed'] <= 0 ) {
		$eligibility['discount_amount'] = 0;
		$eligibility['discount_label']  = '';
		return $eligibility;
	}

	if ( $calc['total_blocked'] > 0 && $calc['total_requested'] > 0 ) {
		$ratio = $calc['total_allowed'] / $calc['total_requested'];
		$eligibility['discount_amount'] = round($eligibility['discount_amount'] * $ratio, 2);
	}

	return $eligibility;
}

/* =============================================================================
 * 7. FASE 1 – RISERVA POOL AL CHECKOUT (checkout_create_order)
 *
 * Aggiorna subito il pool globale con il totale settimane idonee.
 * Non tocca i per-figlio perché dal cart item i child_id possono
 * essere inaffidabili — quelli corretti arrivano in FASE 2.
 * ============================================================================= */

add_action( 'woocommerce_checkout_create_order', 'teatro_isee_reserve_pool', 20, 2 );
function teatro_isee_reserve_pool( $order, $data ) {
	if ( ! is_user_logged_in() ) return;

	$user_id     = get_current_user_id();
	$certificate = get_user_meta($user_id, 'isee_certificate', true);
	if ( empty($certificate) ) return;

	global $teatro_discounts, $WC_custom_teatro_attributes;
	if ( ! isset($teatro_discounts) ) return;

	$check = $teatro_discounts->validateUserProductEligibility(WC()->cart);
	if ( empty($check['discount_amount']) || $check['discount_amount'] <= 0 ) return;

	$total_weeks = 0;
	foreach (WC()->cart->get_cart() as $cart_item) {
		$subtotal    = $teatro_discounts->get_product_subtotal($cart_item['data'], $cart_item['quantity']);
		$is_eligible = $teatro_discounts->validateProductEligibilty($cart_item['product_id'], $subtotal);
		if ( empty($is_eligible['discount_amount']) || $is_eligible['discount_amount'] <= 0 ) continue;

		if ( isset($WC_custom_teatro_attributes) && !empty($cart_item['product_weeks_selected']) ) {
			$weeks = $WC_custom_teatro_attributes->extractMultipleSelections($cart_item['product_weeks_selected']);
			$total_weeks += count($weeks);
		} else {
			$total_weeks += 1;
		}
	}

	if ( $total_weeks <= 0 ) return;

	$order->update_meta_data('_teatro_isee_certificate',    sanitize_text_field($certificate));
	$order->update_meta_data('_teatro_isee_total_weeks',    $total_weeks);
	$order->update_meta_data('_teatro_isee_counted',        '1');
	$order->update_meta_data('_teatro_isee_detail_pending', '1');

	$new_pool = teatro_get_pool_used($certificate) + $total_weeks;
	update_option(teatro_pool_used_key($certificate), $new_pool, false);
}

/* =============================================================================
 * 8. FASE 2 – ASSEGNAZIONE PER-FIGLIO (checkout_order_created)
 *
 * L'ordine è ora nel DB con tutti gli item meta popolati.
 * Legge parent_childs_selected dagli ORDER ITEMS (affidabile)
 * e aggiorna i contatori per-figlio.
 * ============================================================================= */

add_action( 'woocommerce_checkout_order_created', 'teatro_isee_assign_to_children', 20, 1 );
function teatro_isee_assign_to_children( $order ) {
	if ( ! $order->get_meta('_teatro_isee_certificate') ) return;
	if ( ! $order->get_meta('_teatro_isee_detail_pending') ) return;

	$certificate = $order->get_meta('_teatro_isee_certificate');
	$user_id     = $order->get_customer_id();
	if ( ! $user_id ) return;

	global $WC_custom_teatro_attributes, $teatro_discounts;
	if ( ! isset($WC_custom_teatro_attributes) || ! isset($teatro_discounts) ) return;

	$weeks_detail = [];
	$total_weeks  = 0;

	foreach ($order->get_items() as $item) {
		$product = $item->get_product();
		if ( ! $product ) continue;

		$subtotal    = $product->get_price() * $item->get_quantity();
		$is_eligible = $teatro_discounts->validateProductEligibilty($product->get_id(), $subtotal);
		if ( empty($is_eligible['discount_amount']) || $is_eligible['discount_amount'] <= 0 ) continue;

		// Legge dai meta dell'order item (affidabile, già salvato da WC)
		$entries = teatro_extract_child_weeks_from_order_item($item);

		foreach ($entries as $entry) {
			$cid = $entry['child_id'];
			$weeks_detail[$cid] = ($weeks_detail[$cid] ?? 0) + $entry['weeks'];
			$total_weeks        += $entry['weeks'];
		}
	}

	if ( empty($weeks_detail) ) {
		$order->delete_meta_data('_teatro_isee_detail_pending');
		$order->save();
		return;
	}

	// Aggiorna per-figlio
	foreach ($weeks_detail as $child_id => $weeks)
		teatro_update_child_used($user_id, $certificate, (string)$child_id, (int)$weeks);

	// Corregge il pool se il totale reale differisce da quello stimato in FASE 1
	$old_total = (int)$order->get_meta('_teatro_isee_total_weeks');
	if ( $total_weeks !== $old_total ) {
		$diff     = $total_weeks - $old_total;
		$new_pool = max(0, teatro_get_pool_used($certificate) + $diff);
		update_option(teatro_pool_used_key($certificate), $new_pool, false);
	}

	$order->update_meta_data('_teatro_isee_weeks_detail',   $weeks_detail);
	$order->update_meta_data('_teatro_isee_total_weeks',    $total_weeks);
	$order->delete_meta_data('_teatro_isee_detail_pending');
	$order->save();

	$pool_max = teatro_get_pool_max($certificate);
	$order->add_order_note(sprintf(
		'ISEE (%s) – %d sett. scontate. Figli: %s. Pool: %d%s.',
		strtoupper($certificate), $total_weeks,
		implode(', ', array_map(
			fn($cid, $wks) => teatro_get_child_name($cid) . ' ×' . $wks,
			array_keys($weeks_detail), $weeks_detail
		)),
		teatro_get_pool_used($certificate),
		$pool_max > 0 ? ' / ' . $pool_max : ''
	));
}

/* =============================================================================
 * 9. DECREMENTO SU ANNULLAMENTO / RIMBORSO
 * ============================================================================= */

add_action( 'woocommerce_order_status_cancelled', 'teatro_isee_decrement', 20, 1 );
add_action( 'woocommerce_order_status_refunded',  'teatro_isee_decrement', 20, 1 );
function teatro_isee_decrement( $order_id ) {
	$order = wc_get_order($order_id);
	if ( !$order || !$order->get_meta('_teatro_isee_counted') ) return;

	$certificate  = $order->get_meta('_teatro_isee_certificate');
	$weeks_detail = $order->get_meta('_teatro_isee_weeks_detail');
	$total_weeks  = (int)$order->get_meta('_teatro_isee_total_weeks');
	if ( empty($certificate) ) return;

	$user_id = $order->get_customer_id();

	// Decrementa per-figlio (se il dettaglio è disponibile)
	if ( is_array($weeks_detail) && !empty($weeks_detail) ) {
		foreach ($weeks_detail as $child_id => $weeks)
			teatro_update_child_used($user_id, $certificate, (string)$child_id, -(int)$weeks);
	}

	// Decrementa pool
	if ( $total_weeks > 0 ) {
		$new_pool = max(0, teatro_get_pool_used($certificate) - $total_weeks);
		update_option(teatro_pool_used_key($certificate), $new_pool, false);
	}

	$order->delete_meta_data('_teatro_isee_counted');
	$order->save();

	$order->add_order_note(sprintf(
		'ISEE (%s) – %d sett. liberate (annullamento/rimborso). Pool: %d.',
		strtoupper($certificate), $total_weeks, teatro_get_pool_used($certificate)
	));
}

/* =============================================================================
 * 10. AVVISI FRONTEND INFORMATIVI
 * ============================================================================= */

function teatro_isee_get_user_notices( $user_id = null ) {
	if ( $user_id === null ) $user_id = get_current_user_id();
	$messages = [];
	if ( !$user_id ) return $messages;

	$certificate = get_user_meta($user_id, 'isee_certificate', true);
	if ( empty($certificate) ) return $messages;

	$label = $certificate;
	foreach (teatro_get_all_isee_tiers() as $t) {
		if ( strtolower(trim($t['certificate'])) === strtolower(trim($certificate)) ) {
			$label = $t['discount_label'] ?? $certificate; break;
		}
	}

	if ( teatro_pool_is_exhausted($certificate) ) {
		$messages[] = ['type'=>'pool', 'text' => sprintf(
			__('Sconti ISEE esauriti. Le settimane in sconto per la fascia <strong>%s</strong> sono terminate per questa stagione.', 'teatro-discounts'),
			esc_html($label)
		)];
		return $messages;
	}

	$child_max = teatro_get_child_max($certificate);
	if ( $child_max > 0 ) {
		foreach (teatro_get_all_children_usage($user_id, $certificate) as $child_id => $wks_used) {
			if ( $wks_used >= $child_max ) {
				$messages[] = ['type'=>'child', 'text' => sprintf(
					__('<strong>%s</strong> ha già utilizzato tutte le %d settimane disponibili con sconto ISEE (%s).', 'teatro-discounts'),
					esc_html(teatro_get_child_name($child_id)), $child_max, strtoupper($certificate)
				)];
			}
		}
	}

	return $messages;
}

function teatro_isee_print_notice( $html ) {
	echo '<div class="woocommerce-info teatro-isee-notice">&#9432; ' . wp_kses_post($html) . '</div>';
}

add_action( 'woocommerce_before_add_to_cart_button', 'teatro_isee_notice_product' );
function teatro_isee_notice_product() {
	if ( !is_user_logged_in() ) return;
	foreach (teatro_isee_get_user_notices() as $msg) teatro_isee_print_notice($msg['text']);
}

add_action( 'woocommerce_account_dashboard', 'teatro_isee_notice_account' );
function teatro_isee_notice_account() {
	if ( !is_user_logged_in() ) return;
	$msgs = teatro_isee_get_user_notices();
	if ( empty($msgs) ) return;
	echo '<div style="margin-bottom:20px">';
	foreach ($msgs as $msg) teatro_isee_print_notice($msg['text']);
	echo '</div>';
}

add_action( 'wp_head', 'teatro_isee_frontend_styles' );
function teatro_isee_frontend_styles() { ?>
	<style>
	.teatro-isee-notice {
		background:#fff8e1;border-left:4px solid #f59e0b;
		border-radius:4px;padding:12px 16px;
		margin:12px 0 16px;font-size:14px;line-height:1.5;color:#444;
	}
	</style>
<?php }

/* =============================================================================
 * 11. MENU ADMIN
 * ============================================================================= */

add_action( 'admin_menu', 'teatro_isee_register_menu' );
function teatro_isee_register_menu() {
	add_menu_page(
		'Sconti ISEE – Gestione', 'Sconti ISEE', 'manage_options',
		'teatro-isee-counter', 'teatro_isee_render_page',
		'dashicons-id-alt', 56
	);
}

/* =============================================================================
 * 12. STILI ADMIN
 * ============================================================================= */

add_action( 'admin_head', 'teatro_isee_admin_styles' );
function teatro_isee_admin_styles() {
	if ( get_current_screen()->id !== 'toplevel_page_teatro-isee-counter' ) return; ?>
	<style>
	.teatro-wrap{max-width:1080px}
	.teatro-wrap>h1{display:flex;align-items:center;gap:10px}
	.teatro-wrap>h1 .dashicons{font-size:28px;color:#2271b1}
	.teatro-sub{color:#666;margin-top:0;margin-bottom:24px}
	.teatro-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(460px,1fr));gap:22px;margin-bottom:32px}
	.teatro-card{background:#fff;border:1px solid #ddd;border-radius:8px;padding:22px 24px 18px;box-shadow:0 1px 4px rgba(0,0,0,.07)}
	.teatro-card.s-ok{border-top:4px solid #00a32a}
	.teatro-card.s-warn{border-top:4px solid #dba617}
	.teatro-card.s-full{border-top:4px solid #d63638}
	.teatro-card.s-unl{border-top:4px solid #2271b1}
	.cert-badge{display:inline-block;font-size:11px;font-weight:700;letter-spacing:.6px;background:#f0f6fc;color:#2271b1;border:1px solid #c2d9f0;border-radius:4px;padding:2px 9px;margin-bottom:8px;text-transform:uppercase}
	.teatro-card h3{margin:4px 0 2px;font-size:17px}
	.teatro-card .meta{color:#777;font-size:12px;margin-bottom:16px}
	.card-cols{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px}
	.counter-box{background:#f8f9fa;border:1px solid #e2e2e2;border-radius:6px;padding:14px 16px}
	.counter-box .cb-title{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#888;margin-bottom:8px}
	.counter-box .cb-big{font-size:28px;font-weight:700;color:#1d2327;line-height:1;display:block}
	.counter-box .cb-sub{font-size:12px;color:#777;margin-top:2px}
	.prog-bar{width:100%;height:9px;background:#e2e2e2;border-radius:5px;overflow:hidden;margin-top:5px}
	.prog-fill{height:100%;border-radius:5px;transition:width .4s}
	.f-ok{background:#00a32a}.f-warn{background:#dba617}.f-full{background:#d63638}.f-unl{background:#2271b1;width:30%!important;opacity:.3}
	.status-pill{display:inline-block;font-size:11px;font-weight:700;border-radius:20px;padding:3px 10px;margin-top:6px}
	.sp-ok{background:#edfaef;color:#00a32a}.sp-warn{background:#fef9e7;color:#996800}.sp-full{background:#fce8e8;color:#d63638}.sp-unl{background:#e8f0fa;color:#2271b1}
	.card-actions{border-top:1px solid #f0f0f0;padding-top:14px}
	.act-label{display:block;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.4px;color:#888;margin-bottom:5px}
	.act-row{display:flex;gap:8px;align-items:center;margin-bottom:10px;flex-wrap:wrap}
	.act-row input[type="number"]{width:72px}
	.btn-danger{color:#d63638!important;border-color:#d63638!important}
	.btn-danger:hover{background:#d63638!important;color:#fff!important}
	.teatro-section{background:#fff;border:1px solid #ddd;border-radius:8px;padding:20px 22px;margin-bottom:24px}
	.teatro-section>h2{margin-top:0;font-size:15px;border-bottom:1px solid #f0f0f0;padding-bottom:10px;margin-bottom:16px}
	.mini-bar{width:70px;height:7px;background:#e2e2e2;border-radius:4px;overflow:hidden;display:inline-block;vertical-align:middle;margin-left:4px}
	.mini-fill{height:100%;border-radius:4px}
	.child-pill{display:inline-block;font-size:10px;font-weight:700;border-radius:20px;padding:2px 8px;margin-left:4px}
	.cp-ok{background:#edfaef;color:#00a32a}.cp-full{background:#fce8e8;color:#d63638}.cp-unl{background:#e8f0fa;color:#2271b1}
	</style>
<?php }

/* =============================================================================
 * 13. RENDER PAGINA ADMIN
 * ============================================================================= */

function teatro_isee_render_page() {

	if ( isset($_POST['_tn']) && wp_verify_nonce($_POST['_tn'],'teatro_isee_manage') && current_user_can('manage_options') ) {
		$action = sanitize_text_field($_POST['t_action'] ?? '');
		$cert   = sanitize_text_field($_POST['t_cert']   ?? '');
		if (!empty($cert)) {
			switch ($action) {
				case 'set_pool_max':
					$val=max(0,(int)($_POST['t_val']??0));
					update_option(teatro_pool_max_key($cert),$val,false);
					teatro_isee_notice('Pool per <strong>'.esc_html(strtoupper($cert)).'</strong> → '.($val>0?"<strong>$val</strong> sett.":"<em>illimitato</em>").'.');
					break;
				case 'set_pool_used':
					$val=max(0,(int)($_POST['t_val']??0));
					update_option(teatro_pool_used_key($cert),$val,false);
					teatro_isee_notice('Utilizzi pool corretti a <strong>'.$val.'</strong>.');
					break;
				case 'reset_pool':
					update_option(teatro_pool_used_key($cert),0,false);
					teatro_isee_notice('Pool di <strong>'.esc_html(strtoupper($cert)).'</strong> azzerato.');
					break;
				case 'set_child_max':
					$val=max(0,(int)($_POST['t_val']??0));
					update_option(teatro_child_max_key($cert),$val,false);
					teatro_isee_notice('Limite figlio (<strong>'.esc_html(strtoupper($cert)).'</strong>) → '.($val>0?"<strong>$val</strong> sett.":"<em>illimitato</em>").'.');
					break;
				case 'reset_child':
					$uid=(int)($_POST['t_uid']??0);
					$cid=sanitize_text_field($_POST['t_cid']??'');
					if ($uid&&$cid!=='') {
						$freed=teatro_get_child_used($uid,$cert,$cid);
						teatro_update_child_used($uid,$cert,$cid,-$freed);
						update_option(teatro_pool_used_key($cert),max(0,teatro_get_pool_used($cert)-$freed),false);
						teatro_isee_notice('<strong>'.esc_html(teatro_get_child_name($cid)).'</strong> azzerato ('.$freed.' sett. restituite al pool).');
					}
					break;
			}
		}
	}

	$tiers = teatro_get_all_isee_tiers();
	?>
	<div class="wrap teatro-wrap">
		<h1><span class="dashicons dashicons-id-alt"></span> Gestione Sconti ISEE</h1>
		<p class="teatro-sub">Pool globale (settimane totali) · Limite per figlio (settimane max/figlio) · Blocco effettivo al checkout.</p>

		<?php if (empty($tiers)) : ?>
			<div class="notice notice-warning inline"><p>Nessuno scaglione ISEE trovato.</p></div>
		<?php else : ?>

		<div class="teatro-grid">
		<?php foreach ($tiers as $tier) :
			$cert=$tier['certificate']; $discount=$tier['discount']??0;
			$lbl=$tier['discount_label']??$cert; $expire=$tier['expire']??'';
			$pool_max=teatro_get_pool_max($cert); $pool_used=teatro_get_pool_used($cert);
			$pool_rem=teatro_get_pool_remaining($cert); $child_max=teatro_get_child_max($cert);
			$pool_pct=($pool_max>0)?min(100,round($pool_used/$pool_max*100)):0;
			$pool_unl=($pool_max<=0); $child_unl=($child_max<=0);
			$exhausted=teatro_pool_is_exhausted($cert);
			if      ($pool_unl)     {$s='unl'; $fc='f-unl'; $pc='sp-unl'; $pt='Illimitato';}
			elseif  ($exhausted)    {$s='full';$fc='f-full';$pc='sp-full';$pt='ESAURITO';}
			elseif  ($pool_pct>=80) {$s='warn';$fc='f-warn';$pc='sp-warn';$pt='Quasi esaurito';}
			else                    {$s='ok';  $fc='f-ok';  $pc='sp-ok';  $pt=$pool_rem.' rimaste';}
		?>
		<div class="teatro-card s-<?php echo $s; ?>">
			<span class="cert-badge"><?php echo esc_html(strtoupper($cert)); ?></span>
			<h3><?php echo esc_html($lbl); ?></h3>
			<div class="meta">
				Sconto: <strong><?php echo esc_html($discount); ?>%</strong>
				<?php if ($expire) echo '&nbsp;·&nbsp; Scade: <strong>'.esc_html($expire).'</strong>'; ?>
				<?php if ($exhausted) echo '&nbsp;·&nbsp; <span style="color:#d63638;font-weight:700">⚠ ESAURITO</span>'; ?>
			</div>

			<div class="card-cols">
				<div class="counter-box">
					<div class="cb-title">🏊 Pool globale</div>
					<span class="cb-big"><?php echo $pool_unl?'∞':$pool_used; ?></span>
					<?php if (!$pool_unl) : ?>
						<div class="cb-sub">usate su <strong><?php echo $pool_max; ?></strong><br>
						<strong style="color:<?php echo $exhausted?'#d63638':'#00a32a'; ?>"><?php echo $exhausted?'ESAURITO':$pool_rem.' rimanenti'; ?></strong></div>
						<div style="margin:6px 0"><div class="prog-bar"><div class="prog-fill <?php echo $fc; ?>" style="width:<?php echo $pool_pct; ?>%"></div></div></div>
					<?php else : ?>
						<div class="cb-sub">sett. usate · <em>nessun tetto</em></div>
						<div style="margin:6px 0"><div class="prog-bar"><div class="prog-fill f-unl"></div></div></div>
					<?php endif; ?>
					<span class="status-pill <?php echo $pc; ?>"><?php echo esc_html($pt); ?></span>
				</div>
				<div class="counter-box">
					<div class="cb-title">👶 Limite per figlio</div>
					<?php if ($child_unl) : ?>
						<span class="cb-big">∞</span>
						<div class="cb-sub">nessun limite</div>
						<div style="margin:6px 0"><div class="prog-bar"><div class="prog-fill f-unl"></div></div></div>
						<span class="status-pill sp-unl">Illimitato</span>
					<?php else : ?>
						<span class="cb-big"><?php echo $child_max; ?></span>
						<div class="cb-sub">sett. max per figlio</div>
						<?php
						$at_cap=0;
						foreach(get_users(['meta_key'=>teatro_child_meta_key($cert),'meta_compare'=>'EXISTS','fields'=>'ids']) as $uid)
							foreach(teatro_get_all_children_usage($uid,$cert) as $cid=>$wks)
								if($wks>0&&$wks>=$child_max) $at_cap++;
						?>
						<div class="cb-sub" style="margin-top:6px"><?php echo $at_cap>0?'<span style="color:#d63638;font-weight:700">'.$at_cap.' figli al tetto</span>':'<span style="color:#00a32a">Nessun figlio al tetto</span>'; ?></div>
						<span class="status-pill <?php echo $at_cap>0?'sp-warn':'sp-ok'; ?>"><?php echo $at_cap>0?$at_cap.' al limite':'OK'; ?></span>
					<?php endif; ?>
				</div>
			</div>

			<div class="card-actions">
				<span class="act-label">Tetto pool globale <small style="text-transform:none;font-weight:400">(0 = illimitato)</small></span>
				<div class="act-row">
					<form method="post" style="display:flex;gap:6px;align-items:center">
						<?php wp_nonce_field('teatro_isee_manage','_tn'); ?>
						<input type="hidden" name="t_action" value="set_pool_max">
						<input type="hidden" name="t_cert"   value="<?php echo esc_attr($cert); ?>">
						<input type="number" name="t_val"    value="<?php echo esc_attr($pool_max); ?>" min="0" class="small-text">
						<button type="submit" class="button button-primary">Salva</button>
					</form>
					<form method="post" style="display:flex;gap:6px;align-items:center">
						<?php wp_nonce_field('teatro_isee_manage','_tn'); ?>
						<input type="hidden" name="t_action" value="set_pool_used">
						<input type="hidden" name="t_cert"   value="<?php echo esc_attr($cert); ?>">
						<input type="number" name="t_val"    value="<?php echo esc_attr($pool_used); ?>" min="0" class="small-text" placeholder="Correggi usate">
						<button type="submit" class="button">Correggi usate</button>
					</form>
					<form method="post" onsubmit="return confirm('Azzerare?')">
						<?php wp_nonce_field('teatro_isee_manage','_tn'); ?>
						<input type="hidden" name="t_action" value="reset_pool">
						<input type="hidden" name="t_cert"   value="<?php echo esc_attr($cert); ?>">
						<button type="submit" class="button btn-danger">&#8635; Azzera pool</button>
					</form>
				</div>
				<hr style="margin:10px 0;border:none;border-top:1px solid #f0f0f0">
				<span class="act-label">Limite per figlio <small style="text-transform:none;font-weight:400">(0 = illimitato)</small></span>
				<form method="post" class="act-row">
					<?php wp_nonce_field('teatro_isee_manage','_tn'); ?>
					<input type="hidden" name="t_action" value="set_child_max">
					<input type="hidden" name="t_cert"   value="<?php echo esc_attr($cert); ?>">
					<input type="number" name="t_val"    value="<?php echo esc_attr($child_max); ?>" min="0" class="small-text">
					<button type="submit" class="button button-primary">Salva</button>
				</form>
			</div>
		</div>
		<?php endforeach; ?>
		</div>

		<?php foreach ($tiers as $tier) :
			$cert=$tier['certificate']; $lbl=$tier['discount_label']??$cert;
			$child_max=teatro_get_child_max($cert); $child_unl=($child_max<=0);
			$users_with_data=get_users(['meta_key'=>teatro_child_meta_key($cert),'meta_compare'=>'EXISTS','fields'=>'all']);
			if (empty($users_with_data)) continue;
		?>
		<div class="teatro-section">
			<h2>👶 Utilizzi per figlio – <span class="cert-badge"><?php echo esc_html(strtoupper($cert)); ?></span> <?php echo esc_html($lbl); ?><?php echo $child_unl?' <small style="color:#888;font-weight:400">nessun limite</small>':' <small style="color:#666;font-weight:400">max '.$child_max.' sett./figlio</small>'; ?></h2>
			<table class="wp-list-table widefat fixed striped">
				<thead><tr><th>Genitore</th><th>Figlio</th><th style="width:100px">Usate</th><th style="width:130px">Rimanenti</th><th style="width:150px">Progresso</th><th style="width:80px">Reset</th></tr></thead>
				<tbody>
				<?php foreach ($users_with_data as $user) :
					foreach (teatro_get_all_children_usage($user->ID,$cert) as $child_id=>$wks_used) :
						if ($wks_used<=0) continue;
						$rem=$child_unl?null:max(0,$child_max-$wks_used);
						$pct=(!$child_unl&&$child_max>0)?min(100,round($wks_used/$child_max*100)):0;
						$fcls=(!$child_unl&&$wks_used>=$child_max)?'f-full':($pct>=80?'f-warn':'f-ok');
						$pcls=$child_unl?'cp-unl':(($wks_used>=$child_max)?'cp-full':'cp-ok');
						$ptxt=$child_unl?'Illimitato':(($wks_used>=$child_max)?'Al tetto':$rem.' disp.');
				?>
				<tr>
					<td><strong><?php echo esc_html($user->display_name); ?></strong><br><small style="color:#888"><?php echo esc_html($user->user_email); ?></small></td>
					<td><?php echo esc_html(teatro_get_child_name($child_id)); ?></td>
					<td><strong><?php echo $wks_used; ?></strong> sett.</td>
					<td><?php echo $child_unl?'<em style="color:#aaa">—</em>':'<strong>'.$rem.'</strong> / '.$child_max; ?> <span class="child-pill <?php echo $pcls; ?>"><?php echo esc_html($ptxt); ?></span></td>
					<td><?php if(!$child_unl):?><div class="mini-bar"><div class="mini-fill <?php echo $fcls;?>" style="width:<?php echo $pct;?>%"></div></div><?php else:?><em style="color:#ccc;font-size:11px">n/a</em><?php endif;?></td>
					<td>
						<form method="post" onsubmit="return confirm('Azzerare <?php echo esc_js(teatro_get_child_name($child_id));?>?')">
							<?php wp_nonce_field('teatro_isee_manage','_tn');?>
							<input type="hidden" name="t_action" value="reset_child">
							<input type="hidden" name="t_cert"   value="<?php echo esc_attr($cert);?>">
							<input type="hidden" name="t_uid"    value="<?php echo esc_attr($user->ID);?>">
							<input type="hidden" name="t_cid"    value="<?php echo esc_attr($child_id);?>">
							<button type="submit" class="button button-small btn-danger">Azzera</button>
						</form>
					</td>
				</tr>
				<?php endforeach; endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php endforeach; ?>


		<div class="teatro-section">
			<h2>📋 Storico ordini ISEE (ultimi 50)</h2>
			<?php teatro_isee_orders_table(); ?>
		</div>

		<?php endif; ?>
	</div>
	<?php
}

/* =============================================================================
 * 14. TABELLA STORICO ORDINI
 * ============================================================================= */

function teatro_isee_orders_table() {
	$orders=wc_get_orders(['limit'=>50,'orderby'=>'date','order'=>'DESC','meta_key'=>'_teatro_isee_certificate','meta_compare'=>'!=','meta_value'=>'']);
	if(empty($orders)){echo '<p style="color:#888"><em>Nessun ordine trovato.</em></p>';return;}?>
	<table class="wp-list-table widefat fixed striped">
		<thead><tr><th style="width:80px">Ordine</th><th style="width:100px">Scaglione</th><th>Cliente</th><th style="width:170px">Figli (sett. scontate)</th><th style="width:110px">Totale</th><th style="width:110px">Stato</th><th style="width:90px">Cont.</th><th style="width:100px">Data</th></tr></thead>
		<tbody>
		<?php foreach($orders as $order):
			$cert=$order->get_meta('_teatro_isee_certificate');
			$wd=$order->get_meta('_teatro_isee_weeks_detail');
			$tw=$order->get_meta('_teatro_isee_total_weeks');
			$counted=$order->get_meta('_teatro_isee_counted');
			$cliente=trim($order->get_billing_first_name().' '.$order->get_billing_last_name())?:$order->get_billing_email();
			$date=$order->get_date_created()?$order->get_date_created()->date_i18n(get_option('date_format')):'—';
			$ds='—';
			if(is_array($wd)&&!empty($wd)){$p=[];foreach($wd as $cid=>$wks)$p[]=esc_html(teatro_get_child_name($cid)).' ×'.$wks;$ds=implode('<br>',$p);if($tw)$ds.='<br><small style="color:#888">tot. '.$tw.' sett.</small>';}
		?>
		<tr>
			<td><a href="<?php echo esc_url(get_edit_post_link($order->get_id()));?>">#<?php echo $order->get_id();?></a></td>
			<td><strong><?php echo esc_html(strtoupper($cert));?></strong></td>
			<td><?php echo esc_html($cliente);?></td>
			<td style="font-size:12px"><?php echo $ds;?></td>
			<td><?php echo wp_kses_post($order->get_formatted_order_total());?></td>
			<td><?php echo esc_html(wc_get_order_status_name($order->get_status()));?></td>
			<td style="text-align:center"><?php echo $counted?'<span style="color:#00a32a;font-weight:700">✓</span>':'<span style="color:#aaa">—</span>';?></td>
			<td><?php echo esc_html($date);?></td>
		</tr>
		<?php endforeach;?>
		</tbody>
	</table>
	<p style="font-size:12px;color:#888;margin-top:6px">✓ = contatori già aggiornati per questo ordine.</p>
<?php }

/* =============================================================================
 * 15. NOTICE HELPER ADMIN
 * ============================================================================= */

function teatro_isee_notice($msg,$type='success'){
	echo '<div class="notice notice-'.esc_attr($type).' is-dismissible inline" style="margin:12px 0 18px"><p>'.wp_kses_post($msg).'</p></div>';
}

/* =============================================================================
 * SCONTO SUPPLEMENTARE – FEDELTÀ SULLE SETTIMANE NON SCONTATE DA ISEE
 *
 * Quando ISEE è parziale (alcune settimane di un figlio sono bloccate
 * perché ha esaurito il suo limite), quelle settimane ricevono lo sconto
 * fedeltà in base alla loro posizione nella sequenza consecutiva.
 *
 * Agganciato a: apply_filters('teatro_isee_supplementary_discount', 0, $cart)
 * Richiamato da: getFeeAppliedArray() in teatro-discounts.php solo se ISEE vince.
 * ============================================================================= */

add_filter( 'teatro_isee_supplementary_discount', 'teatro_isee_calc_supplementary', 10, 2 );
function teatro_isee_calc_supplementary( $amount, $cart ) {
	if ( ! is_user_logged_in() ) return 0;

	$user_id     = get_current_user_id();
	$certificate = get_user_meta( $user_id, 'isee_certificate', true );
	if ( empty($certificate) ) return 0;

	global $teatro_discounts, $WC_custom_teatro_attributes;
	if ( ! isset($teatro_discounts) || ! isset($WC_custom_teatro_attributes) ) return 0;

	// Settimane già acquistate in ordini completati (base per la sequenza fedeltà)
	$history              = $teatro_discounts->getWeekDetailsFromOrders();
	$weeks_already_bought = count( $history['weeks'] );

	$isee_weeks_counted = 0; // settimane ISEE approvate nel carrello (avanzano la posizione)
	$supplementary      = 0.0;

	foreach ( $cart->get_cart() as $cart_item ) {
		$subtotal    = $teatro_discounts->get_product_subtotal( $cart_item['data'], $cart_item['quantity'] );
		$is_eligible = $teatro_discounts->validateProductEligibilty( $cart_item['product_id'], $subtotal );
		if ( empty($is_eligible['discount_amount']) || $is_eligible['discount_amount'] <= 0 ) continue;

		$calc          = teatro_isee_calc_allowed_weeks( $cart_item, $certificate, $user_id );
		$regular_price = (float) $cart_item['data']->get_regular_price();

		// Le settimane ISEE approvate avanzano la posizione nella sequenza
		$isee_weeks_counted += $calc['total_allowed'];

		// Le settimane bloccate ricevono la fedeltà
		for ( $i = 0; $i < $calc['total_blocked']; $i++ ) {
			$position = $weeks_already_bought + $isee_weeks_counted + $i + 1;

			if      ( $position <= 1 ) $pct = 0;
			elseif  ( $position == 2 ) $pct = 15;
			elseif  ( $position == 3 ) $pct = 20;
			else                       $pct = 30;

			$supplementary += ( $regular_price * $pct ) / 100;
		}
	}

	return round( $supplementary, 2 );
}
