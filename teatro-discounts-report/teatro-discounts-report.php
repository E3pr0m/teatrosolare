<?php
/*
 * Plugin Name:  Teatro Discounts Report
 * Description:  Report sconti applicati — WooCommerce → Report Sconti
 * Version:      1.0.4
 * Author:       E3pr0m
 * Author URI:   https://www.e3pr0m.com
 *
 * Da attivare come plugin standalone oppure includere con require_once
 * da teatro-discounts.php quando pronto per la produzione.
 *
 * @package Woocommerce
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/* =============================================================================
 * REPORT SCONTI – Pannello admin WooCommerce
 * ============================================================================= */

add_action( 'admin_menu', 'teatro_discounts_report_menu' );
function teatro_discounts_report_menu() {
	add_submenu_page(
		'woocommerce',
		'Report Sconti',
		'Report Sconti',
		'manage_woocommerce',
		'teatro-discounts-report',
		'teatro_discounts_report_page'
	);
}

function teatro_discounts_report_page() {

	// ── Filtri ──────────────────────────────────────────────────────────────
	$date_from     = ! empty( $_GET['date_from'] )     ? sanitize_text_field( $_GET['date_from'] )     : date( 'Y-m-01' );
	$date_to       = ! empty( $_GET['date_to'] )       ? sanitize_text_field( $_GET['date_to'] )       : date( 'Y-m-d' );
	$filter_type   = ! empty( $_GET['discount_type'] ) ? sanitize_text_field( $_GET['discount_type'] ) : 'all';
	$filter_status = ! empty( $_GET['order_status'] )  ? sanitize_text_field( $_GET['order_status'] )  : 'all';

	$order_statuses = [ 'wc-completed', 'wc-processing', 'wc-refunded', 'wc-cancelled' ];
	$query_statuses = ( $filter_status === 'all' ) ? $order_statuses : [ 'wc-' . ltrim( $filter_status, 'wc-' ) ];

	// ── Query ordini ─────────────────────────────────────────────────────────
	$orders = wc_get_orders( [
		'limit'        => -1,
		'orderby'      => 'date',
		'order'        => 'DESC',
		'status'       => $query_statuses,
		'date_created' => $date_from . '...' . $date_to,
		'type'         => 'shop_order', // esclude shop_order_refund: OrderRefund non ha metodi billing
	] );

	// ── Elabora righe ────────────────────────────────────────────────────────
	$rows          = [];
	$total_isee    = 0.0;
	$total_loyalty = 0.0;
	$total_coupon  = 0.0;

	foreach ( $orders as $order ) {

		$customer = trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() )
		            ?: $order->get_billing_email();
		$date     = $order->get_date_created()
		            ? $order->get_date_created()->date_i18n( get_option( 'date_format' ) )
		            : '—';
		$status      = wc_get_order_status_name( $order->get_status() );
		$order_total = (float) $order->get_total();

		// Fee negative = sconti automatici (ISEE / Fedeltà)
		foreach ( $order->get_fees() as $fee ) {
			$amount = (float) $fee->get_total();
			if ( $amount >= 0 ) continue;

			$label      = $fee->get_name();
			$abs_amount = abs( $amount );

			if ( stripos( $label, 'isee' ) !== false ) {
				$type       = 'isee';
				$type_label = 'ISEE';
				$total_isee += $abs_amount;
			} elseif ( stripos( $label, 'fedelt' ) !== false ) {
				$type          = 'loyalty';
				$type_label    = 'Fedeltà';
				$total_loyalty += $abs_amount;
			} else {
				$type       = 'other';
				$type_label = 'Altro';
			}

			if ( $filter_type !== 'all' && $filter_type !== $type ) continue;

			// Sentence case: "SCONTO FEDELTà SETTIMANE" → "Sconto fedeltà settimane"
			$lc_label    = mb_strtolower( $label, 'UTF-8' );
			$label_clean = mb_strtoupper( mb_substr( $lc_label, 0, 1, 'UTF-8' ), 'UTF-8' ) . mb_substr( $lc_label, 1, null, 'UTF-8' );

			$rows[] = [
				'order_id'    => $order->get_id(),
				'date'        => $date,
				'customer'    => $customer,
				'type'        => $type,
				'type_label'  => $type_label,
				'label'       => $label_clean,
				'amount'      => $abs_amount,
				'order_total' => $order_total,
				'status'      => $status,
			];
		}

		// Coupon WooCommerce
		foreach ( $order->get_coupons() as $coupon ) {
			$amount = (float) $coupon->get_discount(); // HPOS-compatible (get_coupon_discount_amount non esiste su HPOS)
			if ( $amount <= 0 ) continue;
			$total_coupon += $amount;

			if ( $filter_type !== 'all' && $filter_type !== 'coupon' ) continue;

			$rows[] = [
				'order_id'    => $order->get_id(),
				'date'        => $date,
				'customer'    => $customer,
				'type'        => 'coupon',
				'type_label'  => 'Coupon',
				'label'       => strtoupper( $coupon->get_code() ),
				'amount'      => $amount,
				'order_total' => $order_total,
				'status'      => $status,
			];
		}
	}

	$total_all              = $total_isee + $total_loyalty + $total_coupon;
	$unique_order_ids       = array_unique( array_column( $rows, 'order_id' ) );
	$n_orders_with_discount = count( $unique_order_ids );

	// Somma i totali degli ordini unici (evita doppio conteggio se un ordine ha fee + coupon)
	$total_orders_revenue = 0.0;
	$seen_for_revenue     = [];
	foreach ( $rows as $row ) {
		if ( isset( $seen_for_revenue[ $row['order_id'] ] ) ) continue;
		$seen_for_revenue[ $row['order_id'] ] = true;
		$total_orders_revenue += $row['order_total'];
	}

	$type_colors = [
		'isee'    => '#8b5cf6',
		'loyalty' => '#f59e0b',
		'coupon'  => '#ef4444',
		'other'   => '#6b7280',
	];

	// ── Render ───────────────────────────────────────────────────────────────
	?>
	<div class="wrap">
	<h1 style="margin-bottom:20px">Report Sconti</h1>

	<style>
	.ts-report-filters{display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap;background:#fff;padding:16px 20px;border:1px solid #e0e0e0;border-radius:8px;margin-bottom:24px}
	.ts-report-filters label{display:block;font-weight:600;font-size:12px;margin-bottom:4px;color:#374151}
	.ts-report-filters input[type=date],.ts-report-filters select{width:auto}
	.ts-cards{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:16px;margin-bottom:28px}
	.ts-card{background:#fff;border:1px solid #e5e7eb;border-radius:8px;padding:16px 20px}
	.ts-card-label{font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:#6b7280;margin-bottom:6px}
	.ts-card-value{font-size:26px;font-weight:700;color:#111}
	.ts-pill{display:inline-block;padding:2px 9px;border-radius:4px;font-size:11px;font-weight:600}
	.ts-report-table{background:#fff;border:1px solid #e5e7eb;border-radius:8px;overflow:hidden}
	.ts-report-table table{border:none;margin:0}
	.ts-amount{font-weight:600;color:#059669}
	</style>

	<!-- Filtri -->
	<form method="get" class="ts-report-filters">
		<input type="hidden" name="page" value="teatro-discounts-report">
		<div>
			<label>Dal</label>
			<input type="date" name="date_from" value="<?php echo esc_attr( $date_from ); ?>">
		</div>
		<div>
			<label>Al</label>
			<input type="date" name="date_to" value="<?php echo esc_attr( $date_to ); ?>">
		</div>
		<div>
			<label>Tipo sconto</label>
			<select name="discount_type">
				<option value="all"     <?php selected( $filter_type, 'all' ); ?>>Tutti</option>
				<option value="isee"    <?php selected( $filter_type, 'isee' ); ?>>ISEE</option>
				<option value="loyalty" <?php selected( $filter_type, 'loyalty' ); ?>>Fedeltà</option>
				<option value="coupon"  <?php selected( $filter_type, 'coupon' ); ?>>Coupon</option>
			</select>
		</div>
		<div>
			<label>Stato ordine</label>
			<select name="order_status">
				<option value="all"        <?php selected( $filter_status, 'all' ); ?>>Tutti</option>
				<option value="completed"  <?php selected( $filter_status, 'completed' ); ?>>Completato</option>
				<option value="processing" <?php selected( $filter_status, 'processing' ); ?>>In lavorazione</option>
				<option value="refunded"   <?php selected( $filter_status, 'refunded' ); ?>>Rimborsato</option>
				<option value="cancelled"  <?php selected( $filter_status, 'cancelled' ); ?>>Annullato</option>
			</select>
		</div>
		<div>
			<button type="submit" class="button button-primary">Filtra</button>
		</div>
	</form>

	<!-- Cards riepilogo -->
	<div class="ts-cards">
		<?php
		$cards = [
			[ 'Ordini con sconto',  $n_orders_with_discount, '#3b82f6', false ],
			[ 'Totale incassato',   $total_orders_revenue,   '#0f766e', true  ],
			[ 'Totale scontato',    $total_all,              '#10b981', true  ],
			[ 'Sconto ISEE',        $total_isee,             '#8b5cf6', true  ],
			[ 'Sconto Fedeltà',     $total_loyalty,          '#f59e0b', true  ],
			[ 'Coupon',             $total_coupon,           '#ef4444', true  ],
		];
		foreach ( $cards as [ $lbl, $val, $color, $is_money ] ) : ?>
		<div class="ts-card" style="border-top:4px solid <?php echo esc_attr( $color ); ?>">
			<div class="ts-card-label"><?php echo esc_html( $lbl ); ?></div>
			<div class="ts-card-value">
				<?php echo $is_money
					? esc_html( number_format( $val, 2, ',', '.' ) ) . '&nbsp;€'
					: esc_html( $val ); ?>
			</div>
		</div>
		<?php endforeach; ?>
	</div>

	<!-- Tabella dettaglio -->
	<div class="ts-report-table">
		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th style="width:80px">Ordine</th>
					<th style="width:95px">Data</th>
					<th>Cliente</th>
					<th style="width:110px">Tipo</th>
					<th>Descrizione</th>
					<th style="width:110px">Sconto</th>
					<th style="width:115px">Tot. ordine</th>
					<th style="width:110px">Stato</th>
				</tr>
			</thead>
			<tbody>
			<?php if ( empty( $rows ) ) : ?>
				<tr>
					<td colspan="8" style="text-align:center;color:#888;padding:28px">
						<em>Nessuno sconto trovato per il periodo e i filtri selezionati.</em>
					</td>
				</tr>
			<?php else : ?>
				<?php foreach ( $rows as $row ) :
					$color = $type_colors[ $row['type'] ] ?? '#6b7280'; ?>
				<tr>
					<td>
						<a href="<?php echo esc_url( admin_url( 'post.php?post=' . $row['order_id'] . '&action=edit' ) ); ?>">
							#<?php echo esc_html( $row['order_id'] ); ?>
						</a>
					</td>
					<td><?php echo esc_html( $row['date'] ); ?></td>
					<td><?php echo esc_html( $row['customer'] ); ?></td>
					<td>
						<span class="ts-pill" style="background:<?php echo esc_attr( $color ); ?>20;color:<?php echo esc_attr( $color ); ?>">
							<?php echo esc_html( $row['type_label'] ); ?>
						</span>
					</td>
					<td><?php echo esc_html( $row['label'] ); ?></td>
					<td class="ts-amount">-<?php echo esc_html( number_format( $row['amount'], 2, ',', '.' ) ); ?>&nbsp;€</td>
					<td><?php echo esc_html( number_format( $row['order_total'], 2, ',', '.' ) ); ?>&nbsp;€</td>
					<td><?php echo esc_html( $row['status'] ); ?></td>
				</tr>
				<?php endforeach; ?>
			<?php endif; ?>
			</tbody>
		</table>
	</div>

	</div><!-- .wrap -->
	<?php
}
