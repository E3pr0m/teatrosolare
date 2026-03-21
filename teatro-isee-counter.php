<?php
/**
 * Teatro ISEE Counter – modulo contatori sconto ISEE
 *
 * Caricato automaticamente da Teatro_discounts::init() tramite require_once.
 * Non va attivato come plugin separato.
 *
 * Responsabilità:
 *  – Un contatore separato per ogni scaglione ISEE configurato in ACF
 *  – Blocca lo sconto nel carrello se il tetto massimo è raggiunto
 *  – Incrementa il contatore quando un ordine diventa "Completato"
 *  – Decrementa il contatore su "Annullato" o "Rimborsato"
 *  – Espone un pannello admin dedicato: WP Admin → "Sconti ISEE"
 *
 * Dati salvati in wp_options (nessun campo ACF aggiuntivo necessario):
 *  teatro_isee_usage_{certificato}  → numero utilizzi correnti
 *  teatro_isee_max_{certificato}    → tetto massimo (0 = illimitato)
 *
 * Dati salvati nei meta dell'ordine:
 *  _teatro_isee_certificate  → quale certificato è stato usato
 *  _teatro_isee_counted      → flag: questo ordine è già nel contatore
 *
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/* =============================================================================
 * COSTANTI CHIAVI WP_OPTIONS
 * ============================================================================= */

if ( ! defined( 'TEATRO_ISEE_USAGE_PREFIX' ) ) {
	define( 'TEATRO_ISEE_USAGE_PREFIX', 'teatro_isee_usage_' );
}
if ( ! defined( 'TEATRO_ISEE_MAX_PREFIX' ) ) {
	define( 'TEATRO_ISEE_MAX_PREFIX', 'teatro_isee_max_' );
}

/* =============================================================================
 * 1. FUNZIONI DI SUPPORTO (helpers)
 * ============================================================================= */

/**
 * Chiave wp_option per il contatore utilizzi di uno scaglione.
 * Es. certificato "A" → "teatro_isee_usage_a"
 */
function teatro_isee_usage_key( $certificate ) {
	return TEATRO_ISEE_USAGE_PREFIX . sanitize_key( strtolower( trim( $certificate ) ) );
}

/**
 * Chiave wp_option per il limite massimo di uno scaglione.
 * Es. certificato "A" → "teatro_isee_max_a"
 */
function teatro_isee_max_key( $certificate ) {
	return TEATRO_ISEE_MAX_PREFIX . sanitize_key( strtolower( trim( $certificate ) ) );
}

/** Legge il numero di utilizzi correnti. */
function teatro_get_isee_usage( $certificate ) {
	return (int) get_option( teatro_isee_usage_key( $certificate ), 0 );
}

/** Legge il limite massimo (0 = illimitato). */
function teatro_get_isee_max( $certificate ) {
	return (int) get_option( teatro_isee_max_key( $certificate ), 0 );
}

/** Salva il limite massimo. */
function teatro_set_isee_max( $certificate, $value ) {
	update_option( teatro_isee_max_key( $certificate ), max( 0, (int) $value ), false );
}

/**
 * Controlla se uno scaglione ha ancora posti disponibili.
 * Se max = 0 restituisce sempre true (nessun tetto).
 */
function teatro_is_isee_available( $certificate ) {
	$max = teatro_get_isee_max( $certificate );
	if ( $max <= 0 ) return true;
	return teatro_get_isee_usage( $certificate ) < $max;
}

/** Restituisce tutti gli scaglioni ISEE configurati in ACF. */
function teatro_get_all_isee_tiers() {
	$isees = get_field( 'isee_settings', 'option' );
	return is_array( $isees ) ? $isees : [];
}

/* =============================================================================
 * 2. SALVATAGGIO DEL CERTIFICATO USATO NELL'ORDINE
 *
 * QUANDO: woocommerce_checkout_create_order (creazione ordine al checkout)
 * COSA FA: salva nel meta dell'ordine quale certificato ISEE è stato usato,
 *           ma solo se ha effettivamente generato uno sconto > 0.
 *           Questo meta (_teatro_isee_certificate) è la "memoria" che permette
 *           agli hook successivi di sapere a quale scaglione appartiene l'ordine.
 * ============================================================================= */

add_action( 'woocommerce_checkout_create_order', 'teatro_isee_save_cert_to_order', 10, 2 );
function teatro_isee_save_cert_to_order( $order, $data ) {
	if ( ! is_user_logged_in() ) return;

	$user_id     = get_current_user_id();
	$certificate = get_user_meta( $user_id, 'isee_certificate', true );
	if ( empty( $certificate ) ) return;

	// Verifichiamo che lo sconto ISEE abbia davvero inciso sul totale
	global $teatro_discounts;
	if ( ! isset( $teatro_discounts ) ) return;

	$isee_discount = $teatro_discounts->validateUserProductEligibility( WC()->cart );
	if ( empty( $isee_discount['discount_amount'] ) || $isee_discount['discount_amount'] <= 0 ) return;

	$order->update_meta_data( '_teatro_isee_certificate', sanitize_text_field( $certificate ) );
}

/* =============================================================================
 * 3. INCREMENTO CONTATORE AL COMPLETAMENTO DELL'ORDINE
 *
 * QUANDO: woocommerce_order_status_completed
 * COSA FA: incrementa di 1 il contatore dello scaglione usato in quell'ordine
 *           e segna l'ordine con il flag _teatro_isee_counted = 1 per evitare
 *           di contare lo stesso ordine due volte (es. se lo stato viene
 *           rimosso e riapplicato).
 *           Aggiunge una nota visibile nel backend dell'ordine.
 * ============================================================================= */

add_action( 'woocommerce_order_status_completed', 'teatro_isee_increment', 20, 1 );
function teatro_isee_increment( $order_id ) {
	$order = wc_get_order( $order_id );
	if ( ! $order ) return;

	// Se già conteggiato, non farlo di nuovo
	if ( $order->get_meta( '_teatro_isee_counted' ) ) return;

	$certificate = $order->get_meta( '_teatro_isee_certificate' );
	if ( empty( $certificate ) ) return;

	$new_count = teatro_get_isee_usage( $certificate ) + 1;
	update_option( teatro_isee_usage_key( $certificate ), $new_count, false );

	// Segna l'ordine come conteggiato
	$order->update_meta_data( '_teatro_isee_counted', '1' );
	$order->save();

	$max  = teatro_get_isee_max( $certificate );
	$note = sprintf(
		'Sconto ISEE (%s) registrato nel contatore. Utilizzi: %d%s.',
		strtoupper( $certificate ),
		$new_count,
		$max > 0 ? ' / ' . $max : ' (nessun limite impostato)'
	);
	$order->add_order_note( $note );
}

/* =============================================================================
 * 4. DECREMENTO CONTATORE SU ANNULLAMENTO O RIMBORSO
 *
 * QUANDO: woocommerce_order_status_cancelled / woocommerce_order_status_refunded
 * COSA FA: se l'ordine era già stato conteggiato (flag = 1), sottrae 1 al
 *           contatore e rimuove il flag così il posto torna disponibile.
 *           Aggiunge una nota nell'ordine.
 * ============================================================================= */

add_action( 'woocommerce_order_status_cancelled', 'teatro_isee_decrement', 20, 1 );
add_action( 'woocommerce_order_status_refunded',  'teatro_isee_decrement', 20, 1 );
function teatro_isee_decrement( $order_id ) {
	$order = wc_get_order( $order_id );
	if ( ! $order ) return;

	// Decrementiamo solo se era stato conteggiato
	if ( ! $order->get_meta( '_teatro_isee_counted' ) ) return;

	$certificate = $order->get_meta( '_teatro_isee_certificate' );
	if ( empty( $certificate ) ) return;

	$new_count = max( 0, teatro_get_isee_usage( $certificate ) - 1 );
	update_option( teatro_isee_usage_key( $certificate ), $new_count, false );

	// Rimuove il flag così l'ordine non interferisce più
	$order->delete_meta_data( '_teatro_isee_counted' );
	$order->save();

	$order->add_order_note( sprintf(
		'Sconto ISEE (%s) liberato (annullamento/rimborso). Utilizzi correnti: %d.',
		strtoupper( $certificate ),
		$new_count
	) );
}

/* =============================================================================
 * 5. FILTRO SUL CALCOLO SCONTO – BLOCCA SE IL TETTO È RAGGIUNTO
 *
 * QUANDO: viene richiamato da validateProductEligibilty() in teatro-discounts.php
 *          tramite apply_filters('teatro_isee_eligibility_result', $result, $isee)
 * COSA FA: riceve il risultato del calcolo dello sconto già pronto e
 *          controlla se quello scaglione ha ancora utilizzi disponibili.
 *          - Se sì  → restituisce il risultato invariato
 *          - Se no  → azzera discount_amount (sconto = 0) e mostra
 *                     un avviso nel carrello al cliente
 *
 * PERCHÉ UN FILTRO E NON UN CONTROLLO DIRETTO:
 *  Il calcolo dello sconto avviene dentro teatro-discounts.php che non
 *  conosce i contatori. Il filtro è il "ponte" che collega i due file
 *  senza modificare la logica principale del plugin sconti.
 * ============================================================================= */

add_filter( 'teatro_isee_eligibility_result', 'teatro_isee_check_cap', 10, 2 );
function teatro_isee_check_cap( $eligibility, $certificate ) {
	if ( empty( $eligibility['discount_amount'] ) ) return $eligibility;
	if ( empty( $certificate ) ) return $eligibility;

	if ( ! teatro_is_isee_available( $certificate ) ) {
		$max = teatro_get_isee_max( $certificate );

		// Azzera lo sconto: il carrello non riceverà la fee negativa
		$eligibility['discount_amount'] = 0;
		$eligibility['discount_label']  = '';

		// Avvisa il cliente una sola volta per sessione
		$session_key = 'teatro_isee_cap_' . sanitize_key( $certificate );
		if ( WC()->session && ! WC()->session->get( $session_key ) ) {
			wc_add_notice(
				sprintf(
					__(
						'Lo sconto ISEE (%s) ha raggiunto il numero massimo di utilizzi (%d) e non è al momento applicabile.',
						'teatro-discounts'
					),
					strtoupper( $certificate ),
					$max
				),
				'notice'
			);
			WC()->session->set( $session_key, true );
		}
	}

	return $eligibility;
}

/* =============================================================================
 * 6. REGISTRAZIONE MENU ADMIN
 * ============================================================================= */

add_action( 'admin_menu', 'teatro_isee_register_menu' );
function teatro_isee_register_menu() {
	add_menu_page(
		'Sconti ISEE – Gestione contatori',
		'Sconti ISEE',
		'manage_options',
		'teatro-isee-counter',
		'teatro_isee_render_page',
		'dashicons-id-alt',
		56
	);
}

/* =============================================================================
 * 7. STILI PANNELLO ADMIN
 * ============================================================================= */

add_action( 'admin_head', 'teatro_isee_admin_styles' );
function teatro_isee_admin_styles() {
	$screen = get_current_screen();
	if ( ! $screen || $screen->id !== 'toplevel_page_teatro-isee-counter' ) return;
	?>
	<style>
	/* Layout generale */
	.teatro-wrap { max-width: 980px; }
	.teatro-wrap > h1 { display:flex; align-items:center; gap:10px; margin-bottom:4px; }
	.teatro-wrap > h1 .dashicons { font-size:28px; color:#2271b1; }
	.teatro-subtitle { color:#666; margin-top:0; margin-bottom:24px; }

	/* Grid di card */
	.teatro-grid {
		display: grid;
		grid-template-columns: repeat(auto-fill, minmax(290px,1fr));
		gap: 20px;
		margin-bottom: 32px;
	}

	/* Singola card scaglione */
	.teatro-card {
		background: #fff;
		border: 1px solid #ddd;
		border-radius: 8px;
		padding: 20px 22px 16px;
		box-shadow: 0 1px 4px rgba(0,0,0,.07);
	}
	.teatro-card.s-ok        { border-top: 4px solid #00a32a; }
	.teatro-card.s-warn      { border-top: 4px solid #dba617; }
	.teatro-card.s-full      { border-top: 4px solid #d63638; }
	.teatro-card.s-unlimited { border-top: 4px solid #2271b1; }

	/* Badge certificato */
	.cert-badge {
		display: inline-block;
		font-size: 11px; font-weight: 700; letter-spacing: .6px;
		background: #f0f6fc; color: #2271b1;
		border: 1px solid #c2d9f0; border-radius: 4px;
		padding: 2px 9px; margin-bottom: 8px;
		text-transform: uppercase;
	}
	.teatro-card h3  { margin: 4px 0 2px; font-size: 16px; line-height:1.3; }
	.teatro-card .meta { color:#777; font-size:12px; margin-bottom:12px; }

	/* Barra progresso */
	.prog-labels {
		display: flex; justify-content: space-between;
		font-size: 12px; color: #555; margin-bottom: 5px;
	}
	.prog-labels .big-num {
		font-size: 26px; font-weight: 700; color: #1d2327; line-height: 1;
	}
	.prog-bar {
		width: 100%; height: 11px;
		background: #e8e8e8; border-radius: 6px; overflow: hidden;
		margin-bottom: 12px;
	}
	.prog-fill           { height:100%; border-radius:6px; transition:width .4s; }
	.prog-fill.c-ok      { background:#00a32a; }
	.prog-fill.c-warn    { background:#dba617; }
	.prog-fill.c-full    { background:#d63638; }
	.prog-fill.c-unl     { background:#2271b1; width:100%!important; opacity:.2; }

	/* Pill stato */
	.status-pill {
		display: inline-block; font-size: 11px; font-weight: 700;
		border-radius: 20px; padding: 3px 11px; margin-bottom: 14px;
	}
	.sp-ok  { background:#edfaef; color:#00a32a; }
	.sp-warn{ background:#fef9e7; color:#996800; }
	.sp-full{ background:#fce8e8; color:#d63638; }
	.sp-unl { background:#e8f0fa; color:#2271b1; }

	/* Azioni dentro la card */
	.card-actions { border-top:1px solid #f0f0f0; padding-top:12px; }
	.action-label {
		display: block;
		font-size: 11px; font-weight: 700; text-transform: uppercase;
		letter-spacing: .4px; color: #888; margin-bottom: 5px;
	}
	.row-form { display:flex; gap:6px; align-items:center; margin-bottom:8px; flex-wrap:wrap; }
	.row-form input[type="number"] { width:68px; }
	.btn-danger { color:#d63638!important; border-color:#d63638!important; }
	.btn-danger:hover { background:#d63638!important; color:#fff!important; }

	/* Sezione storico / legenda */
	.teatro-section {
		background: #fff; border: 1px solid #ddd;
		border-radius: 8px; padding: 20px 22px; margin-bottom: 24px;
	}
	.teatro-section > h2 {
		margin-top: 0; font-size: 15px;
		border-bottom: 1px solid #f0f0f0;
		padding-bottom: 10px; margin-bottom: 16px;
	}
	.teatro-section ul { list-style:disc; padding-left:18px; line-height:2.1; }
	</style>
	<?php
}

/* =============================================================================
 * 8. RENDER PAGINA ADMIN PRINCIPALE
 * ============================================================================= */

function teatro_isee_render_page() {

	/* --- Gestione submit form --- */
	if (
		isset( $_POST['_tn'] ) &&
		wp_verify_nonce( $_POST['_tn'], 'teatro_isee_manage' ) &&
		current_user_can( 'manage_options' )
	) {
		$action = sanitize_text_field( $_POST['t_action'] ?? '' );
		$cert   = sanitize_text_field( $_POST['t_cert']   ?? '' );

		if ( ! empty( $cert ) ) {

			/* Azzera contatore */
			if ( $action === 'reset' ) {
				update_option( teatro_isee_usage_key( $cert ), 0, false );
				teatro_isee_notice( 'Contatore per <strong>' . esc_html( strtoupper($cert) ) . '</strong> azzerato a zero.' );

			/* Modifica manuale contatore */
			} elseif ( $action === 'set_count' ) {
				$val = max( 0, (int)( $_POST['t_count'] ?? 0 ) );
				update_option( teatro_isee_usage_key( $cert ), $val, false );
				teatro_isee_notice( 'Utilizzi per <strong>' . esc_html( strtoupper($cert) ) . '</strong> impostati a <strong>' . $val . '</strong>.' );

			/* Modifica limite massimo */
			} elseif ( $action === 'set_max' ) {
				$val = max( 0, (int)( $_POST['t_max'] ?? 0 ) );
				teatro_set_isee_max( $cert, $val );
				$disp = $val > 0 ? "<strong>$val</strong>" : "<em>illimitato (0)</em>";
				teatro_isee_notice( 'Limite per <strong>' . esc_html( strtoupper($cert) ) . '</strong> impostato a ' . $disp . '.' );
			}
		}
	}

	$tiers = teatro_get_all_isee_tiers();
	?>
	<div class="wrap teatro-wrap">

		<h1>
			<span class="dashicons dashicons-id-alt"></span>
			Gestione Sconti ISEE
		</h1>
		<p class="teatro-subtitle">
			Imposta il tetto massimo di utilizzi per ogni scaglione e monitora i contatori in tempo reale.
		</p>

		<?php if ( empty( $tiers ) ) : ?>
			<div class="notice notice-warning inline"><p>
				Nessuno scaglione ISEE trovato. Configura il campo <code>isee_settings</code>
				nelle <a href="<?php echo admin_url('admin.php?page=theme-general-settings'); ?>">impostazioni del tema (ACF)</a>.
			</p></div>

		<?php else : ?>

		<!-- =========== CARDS SCAGLIONI =========== -->
		<div class="teatro-grid">
		<?php foreach ( $tiers as $tier ) :
			$cert      = $tier['certificate'];
			$discount  = $tier['discount']       ?? 0;
			$lbl       = $tier['discount_label'] ?? $cert;
			$expire    = $tier['expire']         ?? '';
			$current   = teatro_get_isee_usage( $cert );
			$max       = teatro_get_isee_max( $cert );
			$unlimited = ( $max <= 0 );
			$remaining = $unlimited ? null : max( 0, $max - $current );
			$pct       = ( ! $unlimited && $max > 0 ) ? min( 100, round( $current / $max * 100 ) ) : 0;

			/* Stato card */
			if      ( $unlimited )       { $s='unlimited'; $pc='sp-unl';  $pt='Illimitato';               $fc='c-unl';  }
			elseif  ( $current >= $max ) { $s='full';      $pc='sp-full'; $pt='Tetto raggiunto';           $fc='c-full'; }
			elseif  ( $pct >= 80 )       { $s='warn';      $pc='sp-warn'; $pt=$remaining.' rimasti';       $fc='c-warn'; }
			else                         { $s='ok';        $pc='sp-ok';   $pt=$remaining.' disponibili';   $fc='c-ok';   }
		?>
		<div class="teatro-card s-<?php echo $s; ?>">

			<span class="cert-badge"><?php echo esc_html( strtoupper($cert) ); ?></span>
			<h3><?php echo esc_html( $lbl ); ?></h3>
			<div class="meta">
				Sconto: <strong><?php echo esc_html($discount); ?>%</strong>
				<?php if ( $expire ) echo '&nbsp;·&nbsp; Scade: <strong>' . esc_html($expire) . '</strong>'; ?>
			</div>

			<span class="status-pill <?php echo $pc; ?>"><?php echo esc_html($pt); ?></span>

			<!-- Barra progresso -->
			<div class="prog-labels">
				<span><span class="big-num"><?php echo $current; ?></span> utilizzi</span>
				<span><?php echo $unlimited ? 'nessun limite' : 'max ' . $max; ?></span>
			</div>
			<div class="prog-bar">
				<div class="prog-fill <?php echo $fc; ?>" style="width:<?php echo $unlimited ? 100 : $pct; ?>%"></div>
			</div>

			<!-- Azioni -->
			<div class="card-actions">

				<!-- 1. Imposta tetto massimo -->
				<span class="action-label">Tetto massimo <small style="text-transform:none;font-weight:400">(0 = illimitato)</small></span>
				<form method="post" class="row-form">
					<?php wp_nonce_field( 'teatro_isee_manage', '_tn' ); ?>
					<input type="hidden" name="t_action" value="set_max">
					<input type="hidden" name="t_cert"   value="<?php echo esc_attr($cert); ?>">
					<input type="number" name="t_max"    value="<?php echo esc_attr($max); ?>" min="0" class="small-text">
					<button type="submit" class="button button-primary">Salva limite</button>
				</form>

				<!-- 2. Correggi contatore manualmente -->
				<span class="action-label">Correggi contatore</span>
				<form method="post" class="row-form">
					<?php wp_nonce_field( 'teatro_isee_manage', '_tn' ); ?>
					<input type="hidden" name="t_action" value="set_count">
					<input type="hidden" name="t_cert"   value="<?php echo esc_attr($cert); ?>">
					<input type="number" name="t_count"  value="<?php echo esc_attr($current); ?>"
					       min="0" <?php if(!$unlimited) echo 'max="'.$max.'"'; ?> class="small-text">
					<button type="submit" class="button">Applica</button>
				</form>

				<!-- 3. Azzera -->
				<form method="post" class="row-form"
				      onsubmit="return confirm('Azzerare il contatore per <?php echo esc_js(strtoupper($cert)); ?>?')">
					<?php wp_nonce_field( 'teatro_isee_manage', '_tn' ); ?>
					<input type="hidden" name="t_action" value="reset">
					<input type="hidden" name="t_cert"   value="<?php echo esc_attr($cert); ?>">
					<button type="submit" class="button btn-danger">&#8635; Azzera</button>
				</form>

			</div><!-- .card-actions -->
		</div><!-- .teatro-card -->
		<?php endforeach; ?>
		</div><!-- .teatro-grid -->

		<!-- =========== STORICO ORDINI =========== -->
		<div class="teatro-section">
			<h2>📋 Storico ordini con sconto ISEE (ultimi 50)</h2>
			<?php teatro_isee_orders_table(); ?>
		</div>

		<!-- =========== LEGENDA =========== -->
		<div class="teatro-section">
			<h2>ℹ️ Come funziona il contatore</h2>
			<ul>
				<li>Il contatore si <strong>incrementa</strong> quando un ordine passa allo stato <em>Completato</em></li>
				<li>Il contatore si <strong>decrementa</strong> automaticamente su <em>Annullamento</em> o <em>Rimborso</em></li>
				<li>Se il tetto è raggiunto lo sconto viene <strong>bloccato nel carrello</strong> con un avviso al cliente</li>
				<li>La colonna <strong>Conteggiato</strong> nella tabella indica se quell'ordine è incluso nel totale</li>
				<li>Puoi <strong>correggere manualmente</strong> il contatore in qualsiasi momento (es. per aggiustamenti retroattivi)</li>
				<li>Limite = <strong>0</strong> significa utilizzi illimitati: lo sconto non viene mai bloccato</li>
			</ul>
		</div>

		<?php endif; ?>
	</div><!-- .wrap.teatro-wrap -->
	<?php
}

/* =============================================================================
 * 9. TABELLA STORICO ORDINI
 * ============================================================================= */

function teatro_isee_orders_table() {
	$orders = wc_get_orders( [
		'limit'        => 50,
		'orderby'      => 'date',
		'order'        => 'DESC',
		'meta_key'     => '_teatro_isee_certificate',
		'meta_compare' => '!=',
		'meta_value'   => '',
	] );

	if ( empty( $orders ) ) {
		echo '<p style="color:#888"><em>Nessun ordine con sconto ISEE trovato.</em></p>';
		return;
	}
	?>
	<table class="wp-list-table widefat fixed striped">
		<thead>
			<tr>
				<th style="width:80px">Ordine</th>
				<th style="width:120px">Scaglione ISEE</th>
				<th style="width:130px">Stato ordine</th>
				<th>Cliente</th>
				<th style="width:110px">Totale</th>
				<th style="width:120px">Conteggiato</th>
				<th style="width:110px">Data</th>
			</tr>
		</thead>
		<tbody>
		<?php foreach ( $orders as $order ) :
			$cert    = $order->get_meta( '_teatro_isee_certificate' );
			$counted = $order->get_meta( '_teatro_isee_counted' );
			$status  = wc_get_order_status_name( $order->get_status() );
			$cliente = trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() )
			           ?: $order->get_billing_email();
			$date    = $order->get_date_created()
			           ? $order->get_date_created()->date_i18n( get_option('date_format') )
			           : '—';
		?>
		<tr>
			<td>
				<a href="<?php echo esc_url( get_edit_post_link( $order->get_id() ) ); ?>">
					#<?php echo $order->get_id(); ?>
				</a>
			</td>
			<td><strong><?php echo esc_html( strtoupper( $cert ) ); ?></strong></td>
			<td><?php echo esc_html( $status ); ?></td>
			<td><?php echo esc_html( $cliente ); ?></td>
			<td><?php echo wp_kses_post( $order->get_formatted_order_total() ); ?></td>
			<td style="text-align:center">
				<?php if ( $counted ) : ?>
					<span style="color:#00a32a;font-weight:700">✓ Sì</span>
				<?php else : ?>
					<span style="color:#aaa">— No</span>
				<?php endif; ?>
			</td>
			<td><?php echo esc_html( $date ); ?></td>
		</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
	<p style="font-size:12px;color:#888;margin-top:6px">
		<strong>✓ Sì</strong> = l'ordine è già incluso nel contatore dello scaglione. &nbsp;
		<strong>— No</strong> = non ancora conteggiato (ordine non completato o già annullato/rimborsato).
	</p>
	<?php
}

/* =============================================================================
 * 10. HELPER NOTICE ADMIN
 * ============================================================================= */

function teatro_isee_notice( $msg, $type = 'success' ) {
	echo '<div class="notice notice-' . esc_attr($type) . ' is-dismissible inline" style="margin:12px 0 18px"><p>'
	     . wp_kses_post( $msg ) . '</p></div>';
}
