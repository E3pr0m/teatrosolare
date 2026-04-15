<?php
/*
 * Plugin Name:  Teatro Ripianificazioni
 * Description:  Ripianifica una singola settimana di un ordine WooCommerce senza annullare l'ordine.
 *               Meta box nell'ordine WC con tabella settimane, modal di selezione e operazione atomica.
 * Text Domain:  teatro-ripianificazioni
 * Version:      1.0.0
 * Author:       E3pr0m
 * Author URI:   https://www.e3pr0m.com
 * Requires:     teatro-courses-buses 1.1.1+, WooCommerce 7.0+, ACF Pro
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'Teatro_Ripianificazioni' ) ) :

class Teatro_Ripianificazioni {

	public function __construct() {
		add_action( 'add_meta_boxes',        [ $this, 'register_meta_box' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );

		// AJAX — solo admin autenticato
		add_action( 'wp_ajax_ripia_get_weeks',      [ $this, 'ajax_get_weeks' ] );
		add_action( 'wp_ajax_ripia_get_buses',      [ $this, 'ajax_get_buses' ] );
		add_action( 'wp_ajax_ripia_get_stops',      [ $this, 'ajax_get_stops' ] );
		add_action( 'wp_ajax_ripia_do_reschedule',  [ $this, 'ajax_do_reschedule' ] );
	}

	/* ------------------------------------------------------------------ */
	/* Meta Box                                                              */
	/* ------------------------------------------------------------------ */

	public function register_meta_box() {
		$args = [
			'teatro-ripianificazioni',
			'Ripianifica Settimane',
			[ $this, 'render_meta_box' ],
			'',          // screen — viene impostato sotto
			'normal',
			'default',
		];

		// HPOS (High-Performance Order Storage)
		$args[3] = 'woocommerce_page_wc-orders';
		call_user_func_array( 'add_meta_box', $args );

		// Legacy post-based orders
		$args[3] = 'shop_order';
		call_user_func_array( 'add_meta_box', $args );
	}

	/**
	 * Renderizza la meta box.
	 * Con HPOS riceve direttamente WC_Order; con legacy riceve WP_Post.
	 *
	 * @param WC_Order|WP_Post $post_or_order
	 */
	public function render_meta_box( $post_or_order ) {
		if ( $post_or_order instanceof WC_Abstract_Order ) {
			$order = $post_or_order;
		} elseif ( $post_or_order instanceof WP_Post ) {
			$order = wc_get_order( $post_or_order->ID );
		} else {
			return;
		}

		if ( empty( $order ) ) return;

		$order_id = $order->get_id();
		$items    = $order->get_items();

		echo '<div id="ripia-wrap">';

		$has_items = false;
		foreach ( $items as $item_id => $item ) {
			$weeks_meta = $item->get_meta( 'product_weeks_selected' );
			$buses_meta = $item->get_meta( 'product_buses_selected' );
			$stops_meta = $item->get_meta( 'product_bus_stops_selected' );
			$child_meta = $item->get_meta( 'parent_childs_selected' );

			if ( empty( $weeks_meta ) || empty( $child_meta ) ) continue;

			$has_items  = true;
			$weeks      = explode( '@@', $weeks_meta );
			$buses      = explode( '@@', $buses_meta ?? '' );
			$stops      = explode( '@@', $stops_meta ?? '' );
			$product_id = $item->get_product_id();
			$child_user = get_user_by( 'id', $child_meta );
			$child_name = $child_user ? $child_user->display_name : "Figlio ID {$child_meta}";

			echo '<h4 style="margin:12px 0 6px;">'
				. esc_html( $item->get_name() ) . ' — '
				. esc_html( $child_name )
				. '</h4>';

			echo '<table class="wp-list-table widefat fixed striped" style="margin-bottom:18px;">';
			echo '<thead><tr>
				<th style="width:4%">#</th>
				<th style="width:30%">Settimana</th>
				<th style="width:22%">Pulmino</th>
				<th style="width:24%">Fermata</th>
				<th style="width:20%">Azione</th>
			</tr></thead><tbody>';

			foreach ( $weeks as $idx => $week ) {
				$bus_id    = isset( $buses[ $idx ] ) ? trim( $buses[ $idx ] ) : 'empty';
				$stop      = isset( $stops[ $idx ] ) ? trim( $stops[ $idx ] ) : 'empty';
				$bus_name  = ( $bus_id !== 'empty' ) ? get_the_title( $bus_id ) : '—';
				$stop_disp = ( $stop  !== 'empty' )  ? $stop  : '—';

				echo '<tr>';
				echo '<td>' . esc_html( $idx + 1 ) . '</td>';
				echo '<td>' . esc_html( $week ) . '</td>';
				echo '<td>' . esc_html( $bus_name ) . '</td>';
				echo '<td>' . esc_html( $stop_disp ) . '</td>';
				echo '<td><button type="button" class="button ripia-btn-reschedule"
					data-order-id="'   . esc_attr( $order_id )   . '"
					data-item-id="'    . esc_attr( $item_id )    . '"
					data-week-index="' . esc_attr( $idx )        . '"
					data-current-week="' . esc_attr( $week )     . '"
					data-current-bus="'  . esc_attr( $bus_id )   . '"
					data-product-id="'   . esc_attr( $product_id ) . '"
				>Ripianifica</button></td>';
				echo '</tr>';
			}

			echo '</tbody></table>';
		}

		if ( ! $has_items ) {
			echo '<p><em>Nessun item con settimane prenotate in questo ordine.</em></p>';
		}

		echo '</div>'; // #ripia-wrap

		$this->render_modal();
	}

	/**
	 * HTML del modal di ripianificazione (nascosto, attivato da JS).
	 */
	private function render_modal() {
		?>
		<div id="ripia-modal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;
			background:rgba(0,0,0,.55);z-index:99999;align-items:center;justify-content:center;">
			<div style="background:#fff;padding:28px 24px 20px;border-radius:6px;
				min-width:480px;max-width:620px;max-height:90vh;overflow-y:auto;
				position:relative;box-shadow:0 4px 24px rgba(0,0,0,.25);">

				<h3 style="margin-top:0;margin-bottom:16px;" id="ripia-modal-title">
					Ripianifica Settimana
				</h3>

				<div id="ripia-modal-body">
					<div id="ripia-loading" style="text-align:center;padding:24px 0;">
						<span class="spinner is-active" style="float:none;vertical-align:middle;"></span>
						Caricamento settimane disponibili&hellip;
					</div>

					<div id="ripia-form" style="display:none;">
						<table class="form-table" style="width:100%;border-collapse:collapse;">
							<tr>
								<th style="width:38%;padding:8px 4px;text-align:left;font-weight:600;">
									Settimana attuale
								</th>
								<td style="padding:8px 4px;">
									<strong id="ripia-current-week-label" style="color:#666;"></strong>
								</td>
							</tr>
							<tr>
								<th style="padding:8px 4px;text-align:left;font-weight:600;">
									Nuova settimana
								</th>
								<td style="padding:8px 4px;">
									<select id="ripia-select-week" style="width:100%;min-width:260px;"></select>
								</td>
							</tr>
							<tr>
								<th style="padding:8px 4px;text-align:left;font-weight:600;">
									Pulmino
								</th>
								<td style="padding:8px 4px;">
									<select id="ripia-select-bus" style="width:100%;"></select>
								</td>
							</tr>
							<tr id="ripia-row-stop">
								<th style="padding:8px 4px;text-align:left;font-weight:600;">
									Fermata
								</th>
								<td style="padding:8px 4px;">
									<select id="ripia-select-stop" style="width:100%;"></select>
								</td>
							</tr>
						</table>

						<div id="ripia-error"
							style="color:#d63638;background:#fcf0f1;border:1px solid #d63638;
								padding:8px 12px;border-radius:3px;margin-top:12px;display:none;">
						</div>
					</div>
				</div>

				<div style="margin-top:18px;display:flex;gap:8px;justify-content:flex-end;border-top:1px solid #eee;padding-top:16px;">
					<button type="button" class="button" id="ripia-modal-cancel">Annulla</button>
					<button type="button" class="button button-primary" id="ripia-modal-confirm"
						style="display:none;">
						Conferma Ripianificazione
					</button>
				</div>
			</div>
		</div>
		<?php
	}

	/* ------------------------------------------------------------------ */
	/* Enqueue assets                                                        */
	/* ------------------------------------------------------------------ */

	public function enqueue_admin_assets( $hook ) {
		$is_order_page = false;

		// Legacy: post.php con post_type shop_order
		if ( in_array( $hook, [ 'post.php', 'post-new.php' ], true ) ) {
			$post_id   = isset( $GLOBALS['post'] ) ? $GLOBALS['post']->ID : 0;
			$post_type = $post_id ? get_post_type( $post_id ) : '';
			if ( $post_type === 'shop_order' ) {
				$is_order_page = true;
			}
		}

		// HPOS: woocommerce_page_wc-orders (lista + dettaglio singolo ordine)
		if ( $hook === 'woocommerce_page_wc-orders' ) {
			// Solo sulla pagina di dettaglio (action=edit o ID presente)
			if ( isset( $_GET['action'] ) && $_GET['action'] === 'edit' ) {
				$is_order_page = true;
			} elseif ( isset( $_GET['id'] ) && intval( $_GET['id'] ) > 0 ) {
				$is_order_page = true;
			}
		}

		if ( ! $is_order_page ) return;

		// Registra handle dummy per poter usare wp_add_inline_script
		wp_register_script( 'ripia-admin', false, [ 'jquery' ], null, true );
		wp_enqueue_script( 'ripia-admin' );

		// Inietta config globale (nonce + ajaxurl) come primo blocco
		$config = wp_json_encode( [
			'ajaxurl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'ripia_nonce' ),
		] );
		wp_add_inline_script( 'ripia-admin', 'var ripiaConfig = ' . $config . ';', 'before' );

		// Inietta il codice JS principale
		wp_add_inline_script( 'ripia-admin', $this->get_main_js() );
	}

	/**
	 * Restituisce il JavaScript del modal.
	 * Si aspetta ripiaConfig.ajaxurl e ripiaConfig.nonce già definiti.
	 */
	private function get_main_js(): string {
		return <<<'JSEOF'
jQuery(function ($) {
    'use strict';

    var state   = {};
    var ajax    = ripiaConfig.ajaxurl;
    var nonce   = ripiaConfig.nonce;

    /* -------- Apertura modal ------------------------------------------ */

    $(document).on('click', '.ripia-btn-reschedule', function () {
        state = {
            orderId:     $(this).data('order-id'),
            itemId:      $(this).data('item-id'),
            weekIndex:   $(this).data('week-index'),
            currentWeek: $(this).data('current-week'),
            currentBus:  $(this).data('current-bus'),
            productId:   $(this).data('product-id'),
        };
        openModal();
    });

    function openModal() {
        $('#ripia-modal').css('display', 'flex');
        $('#ripia-loading').show();
        $('#ripia-form').hide();
        $('#ripia-modal-confirm').hide().prop('disabled', false).text('Conferma Ripianificazione');
        $('#ripia-error').hide().text('');
        $('#ripia-current-week-label').text(state.currentWeek);
        loadWeeks();
    }

    /* -------- Carica settimane ----------------------------------------- */

    function loadWeeks() {
        $.post(ajax, {
            action:       'ripia_get_weeks',
            nonce:        nonce,
            product_id:   state.productId,
            current_week: state.currentWeek,
        }, function (resp) {
            if (resp.success && resp.data.weeks && resp.data.weeks.length > 0) {
                var sel = $('#ripia-select-week').empty();
                $.each(resp.data.weeks, function (i, w) {
                    var label = w.label + ' (posti settimana: ' + w.seats + ')';
                    sel.append($('<option>').val(w.value).text(label));
                });
                $('#ripia-loading').hide();
                $('#ripia-form').show();
                $('#ripia-modal-confirm').show();
                loadBuses();
            } else {
                var msg = (resp.data) ? resp.data : 'Nessuna settimana disponibile per questo prodotto.';
                $('#ripia-loading').html('<em>' + msg + '</em>');
            }
        }).fail(function () {
            $('#ripia-loading').html('<em>Errore di connessione. Riprova.</em>');
        });
    }

    /* -------- Carica bus ----------------------------------------------- */

    function loadBuses() {
        var week = $('#ripia-select-week').val();
        if (!week) return;

        $('#ripia-select-bus').empty().append($('<option disabled>').text('Caricamento…'));

        $.post(ajax, {
            action:     'ripia_get_buses',
            nonce:      nonce,
            product_id: state.productId,
            week:       week,
        }, function (resp) {
            var sel = $('#ripia-select-bus').empty();
            sel.append($('<option>').val('empty').text('— Nessun pulmino —'));
            if (resp.success && resp.data.buses && resp.data.buses.length > 0) {
                $.each(resp.data.buses, function (i, b) {
                    var label = b.bus_title + ' (posti liberi: ' + b.seats + ')';
                    sel.append($('<option>').val(b.bus_id).text(label));
                });
                // Pre-seleziona bus corrente se presente nella lista
                if (state.currentBus && state.currentBus !== 'empty') {
                    sel.val(state.currentBus);
                }
            }
            loadStops();
        }).fail(function () {
            $('#ripia-select-bus').empty().append($('<option>').text('Errore caricamento'));
        });
    }

    /* -------- Carica fermate ------------------------------------------- */

    function loadStops() {
        var bus = $('#ripia-select-bus').val();
        if (!bus || bus === 'empty') {
            $('#ripia-select-stop').empty().append($('<option>').val('empty').text('—'));
            return;
        }

        $('#ripia-select-stop').empty().append($('<option disabled>').text('Caricamento…'));

        $.post(ajax, {
            action:     'ripia_get_stops',
            nonce:      nonce,
            product_id: state.productId,
            bus_id:     bus,
        }, function (resp) {
            var sel = $('#ripia-select-stop').empty();
            if (resp.success && resp.data.stops && resp.data.stops.length > 0) {
                $.each(resp.data.stops, function (i, s) {
                    sel.append(
                        $('<option>')
                            .val(s.name)
                            .attr('data-start', s.start_time || 'empty')
                            .attr('data-end',   s.end_time   || 'empty')
                            .text(s.name)
                    );
                });
            } else {
                sel.append($('<option>').val('empty').text('— Nessuna fermata —'));
            }
        }).fail(function () {
            $('#ripia-select-stop').empty().append($('<option>').val('empty').text('Errore'));
        });
    }

    /* -------- Cambio cascata ------------------------------------------- */

    $(document).on('change', '#ripia-select-week', loadBuses);
    $(document).on('change', '#ripia-select-bus',  loadStops);

    /* -------- Chiudi modal --------------------------------------------- */

    $(document).on('click', '#ripia-modal-cancel', function () {
        $('#ripia-modal').hide();
    });

    // Click fuori dal contenuto
    $(document).on('click', '#ripia-modal', function (e) {
        if ($(e.target).is('#ripia-modal')) {
            $('#ripia-modal').hide();
        }
    });

    /* -------- Conferma ripianificazione --------------------------------- */

    $(document).on('click', '#ripia-modal-confirm', function () {
        var newWeek  = $('#ripia-select-week').val();
        var newBus   = $('#ripia-select-bus').val()  || 'empty';
        var newStop  = $('#ripia-select-stop').val() || 'empty';
        var stopOpt  = $('#ripia-select-stop option:selected');
        var newStart = stopOpt.data('start') || 'empty';
        var newEnd   = stopOpt.data('end')   || 'empty';

        if (!newWeek) {
            $('#ripia-error').text('Seleziona la nuova settimana.').show();
            return;
        }

        $('#ripia-error').hide();
        $(this).prop('disabled', true).text('Salvataggio…');

        $.post(ajax, {
            action:      'ripia_do_reschedule',
            nonce:       nonce,
            order_id:    state.orderId,
            item_id:     state.itemId,
            week_index:  state.weekIndex,
            new_week:    newWeek,
            new_bus:     newBus,
            new_stop:    newStop,
            new_start:   newStart,
            new_end:     newEnd,
            old_week:    state.currentWeek,
            old_bus:     state.currentBus,
        }, function (resp) {
            if (resp.success) {
                $('#ripia-modal').hide();
                location.reload();
            } else {
                var msg = (resp.data) ? resp.data : 'Errore sconosciuto. Riprova.';
                $('#ripia-error').text(msg).show();
                $('#ripia-modal-confirm').prop('disabled', false).text('Conferma Ripianificazione');
            }
        }).fail(function () {
            $('#ripia-error').text('Errore di connessione. Riprova.').show();
            $('#ripia-modal-confirm').prop('disabled', false).text('Conferma Ripianificazione');
        });
    });
});
JSEOF;
	}

	/* ------------------------------------------------------------------ */
	/* AJAX: settimane disponibili del prodotto                             */
	/* ------------------------------------------------------------------ */

	public function ajax_get_weeks() {
		check_ajax_referer( 'ripia_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( 'Permesso negato.' );
		}

		$product_id = intval( $_POST['product_id'] ?? 0 );
		if ( ! $product_id ) {
			wp_send_json_error( 'Prodotto non valido.' );
		}

		$weeks_acf = get_field( 'weeks', $product_id );
		if ( empty( $weeks_acf ) ) {
			wp_send_json_error( 'Nessuna settimana configurata per questo prodotto.' );
		}

		global $WC_custom_teatro_attributes;
		if ( empty( $WC_custom_teatro_attributes ) ) {
			wp_send_json_error( 'Plugin teatro-courses-buses non attivo.' );
		}

		$result = [];
		foreach ( $weeks_acf as $week ) {
			$start = $WC_custom_teatro_attributes->getForamttedDate( $week['start_date'] );
			$end   = $WC_custom_teatro_attributes->getForamttedDate( $week['end_date'] );
			$label = $start . ' - ' . $end;
			$seats = $WC_custom_teatro_attributes->getAvailableSeatsbyWeek( $week, false, $product_id );

			$result[] = [
				'value' => $label,
				'label' => $label,
				'seats' => intval( $seats ),
			];
		}

		wp_send_json_success( [ 'weeks' => $result ] );
	}

	/* ------------------------------------------------------------------ */
	/* AJAX: bus disponibili per settimana                                  */
	/* ------------------------------------------------------------------ */

	public function ajax_get_buses() {
		check_ajax_referer( 'ripia_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( 'Permesso negato.' );
		}

		$product_id = intval( $_POST['product_id'] ?? 0 );
		$week       = sanitize_text_field( $_POST['week'] ?? '' );

		if ( ! $product_id || ! $week ) {
			wp_send_json_error( 'Parametri mancanti.' );
		}

		global $WC_custom_teatro_attributes;
		if ( empty( $WC_custom_teatro_attributes ) ) {
			wp_send_json_error( 'Plugin teatro-courses-buses non attivo.' );
		}

		$buses = $WC_custom_teatro_attributes->getBusesDataByWeek( $product_id, $week );
		wp_send_json_success( [ 'buses' => $buses ?: [] ] );
	}

	/* ------------------------------------------------------------------ */
	/* AJAX: fermate di un bus                                             */
	/* ------------------------------------------------------------------ */

	public function ajax_get_stops() {
		check_ajax_referer( 'ripia_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( 'Permesso negato.' );
		}

		$bus_id = intval( $_POST['bus_id'] ?? 0 );
		if ( ! $bus_id ) {
			wp_send_json_error( 'Bus non valido.' );
		}

		$stops_acf = get_field( 'stops', $bus_id );
		$result    = [];

		if ( ! empty( $stops_acf ) ) {
			foreach ( $stops_acf as $stop ) {
				$result[] = [
					'name'       => $stop['stop_name']  ?? '',
					'start_time' => $stop['start_time'] ?? 'empty',
					'end_time'   => $stop['end_time']   ?? 'empty',
				];
			}
		}

		wp_send_json_success( [ 'stops' => $result ] );
	}

	/* ------------------------------------------------------------------ */
	/* AJAX: esegui ripianificazione (operazione atomica)                   */
	/* ------------------------------------------------------------------ */

	public function ajax_do_reschedule() {
		check_ajax_referer( 'ripia_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( 'Permesso negato.' );
		}

		$order_id   = intval( $_POST['order_id']   ?? 0 );
		$item_id    = intval( $_POST['item_id']     ?? 0 );
		$week_index = intval( $_POST['week_index']  ?? 0 );
		$new_week   = sanitize_text_field( $_POST['new_week']  ?? '' );
		$new_bus    = sanitize_text_field( $_POST['new_bus']   ?? 'empty' );
		$new_stop   = sanitize_text_field( $_POST['new_stop']  ?? 'empty' );
		$new_start  = sanitize_text_field( $_POST['new_start'] ?? 'empty' );
		$new_end    = sanitize_text_field( $_POST['new_end']   ?? 'empty' );
		$old_week   = sanitize_text_field( $_POST['old_week']  ?? '' );

		if ( ! $order_id || ! $item_id || ! $new_week ) {
			wp_send_json_error( 'Parametri obbligatori mancanti.' );
		}

		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			wp_send_json_error( 'Ordine non trovato.' );
		}

		$items = $order->get_items();
		if ( ! isset( $items[ $item_id ] ) ) {
			wp_send_json_error( 'Item non trovato nell\'ordine.' );
		}

		$item = $items[ $item_id ];

		// ---- Leggi tutti i meta array ----
		$weeks_meta = $item->get_meta( 'product_weeks_selected' )      ?? '';
		$buses_meta = $item->get_meta( 'product_buses_selected' )      ?? '';
		$stops_meta = $item->get_meta( 'product_bus_stops_selected' )  ?? '';
		$start_meta = $item->get_meta( 'product_bus_stop_start_time' ) ?? '';
		$end_meta   = $item->get_meta( 'product_bus_stop_end_time' )   ?? '';

		$weeks  = explode( '@@', $weeks_meta );
		$buses  = explode( '@@', $buses_meta );
		$stops  = explode( '@@', $stops_meta );
		$starts = explode( '@@', $start_meta );
		$ends   = explode( '@@', $end_meta );

		if ( ! isset( $weeks[ $week_index ] ) ) {
			wp_send_json_error( 'Indice settimana fuori range.' );
		}

		// Bus effettivamente nell'ordine per questa settimana
		$actual_old_bus = isset( $buses[ $week_index ] ) ? trim( $buses[ $week_index ] ) : 'empty';

		// ---- Sostituisci solo l'indice target ----
		$weeks[ $week_index ]  = $new_week;
		$buses[ $week_index ]  = $new_bus;
		$stops[ $week_index ]  = ( $new_stop  !== '' ) ? $new_stop  : 'empty';
		$starts[ $week_index ] = ( $new_start !== '' ) ? $new_start : 'empty';
		$ends[ $week_index ]   = ( $new_end   !== '' ) ? $new_end   : 'empty';

		// ---- Salva i meta aggiornati ----
		$item->update_meta_data( 'product_weeks_selected',       implode( '@@', $weeks ) );
		$item->update_meta_data( 'product_buses_selected',       implode( '@@', $buses ) );
		$item->update_meta_data( 'product_bus_stops_selected',   implode( '@@', $stops ) );
		$item->update_meta_data( 'product_bus_stop_start_time',  implode( '@@', $starts ) );
		$item->update_meta_data( 'product_bus_stop_end_time',    implode( '@@', $ends ) );
		$item->save();
		$order->save();

		// ---- Calcola week_id per seats_booked (formato: ts_start-ts_end) ----
		$old_week_id = $this->week_string_to_id( $old_week );
		$new_week_id = $this->week_string_to_id( $new_week );

		// ---- Release posto su ex-bus per la vecchia settimana ----
		if ( $actual_old_bus !== 'empty' && ! empty( $old_week_id ) ) {
			$this->release_seat( $actual_old_bus, $order_id, $old_week_id );
		}

		// ---- Book posto su nuovo bus ----
		if ( $new_bus !== 'empty' && ! empty( $new_week_id ) ) {
			global $WC_custom_teatro_attributes;
			if ( ! empty( $WC_custom_teatro_attributes ) ) {
				$WC_custom_teatro_attributes->bookBusSeat( [
					'bus'       => $new_bus,
					'book_data' => [
						'order_id'   => $order_id,
						'product_id' => $item->get_product_id(),
						'parent_id'  => $order->get_customer_id(),
						'child_id'   => $item->get_meta( 'parent_childs_selected' ),
						'week_id'    => $new_week_id,
						'booked_at'  => time(),
					],
				] );
			}
		}

		// ---- Nota ordine ----
		$admin        = wp_get_current_user();
		$old_bus_name = ( $actual_old_bus !== 'empty' ) ? get_the_title( $actual_old_bus ) : '—';
		$new_bus_name = ( $new_bus !== 'empty' )        ? get_the_title( $new_bus )        : '—';
		$stop_note    = ( $new_stop !== 'empty' && $new_stop !== '' ) ? $new_stop : '—';

		$order->add_order_note( sprintf(
			'Ripianificazione manuale — operatore: %s | settimana: [%s → %s] | pulmino: [%s → %s] | fermata: %s',
			esc_html( $admin->user_login ),
			esc_html( $old_week ),
			esc_html( $new_week ),
			esc_html( $old_bus_name ),
			esc_html( $new_bus_name ),
			esc_html( $stop_note )
		) );

		wp_send_json_success( [ 'message' => 'Ripianificazione completata con successo.' ] );
	}

	/* ------------------------------------------------------------------ */
	/* Helpers                                                               */
	/* ------------------------------------------------------------------ */

	/**
	 * Converte stringa settimana come "02/06/2025 - 08/06/2025"
	 * nel formato week_id usato in seats_booked: "ts_start-ts_end".
	 * Usa la stessa logica di str_replace('/','-') del plugin principale.
	 */
	private function week_string_to_id( string $week ): string {
		// Divide in max 2 parti sul separatore " - "
		$parts = preg_split( '/\s+-\s+/', trim( $week ), 2 );
		if ( count( $parts ) !== 2 ) return '';

		$ts_start = strtotime( str_replace( '/', '-', trim( $parts[0] ) ) );
		$ts_end   = strtotime( str_replace( '/', '-', trim( $parts[1] ) ) );

		if ( ! $ts_start || ! $ts_end ) return '';

		return $ts_start . '-' . $ts_end;
	}

	/**
	 * Rimuove da seats_booked la prenotazione di uno specifico ordine+settimana.
	 * Lascia intatte le prenotazioni di altre settimane sullo stesso bus.
	 *
	 * @param string $bus_id  ID del pulmino (come stringa).
	 * @param int    $order_id
	 * @param string $week_id  Formato "ts_start-ts_end".
	 */
	private function release_seat( string $bus_id, int $order_id, string $week_id ): void {
		$booked_raw = get_post_meta( $bus_id, 'seats_booked', true );
		$booked     = ! empty( $booked_raw ) ? maybe_unserialize( $booked_raw ) : [];

		if ( empty( $booked ) || ! is_array( $booked ) ) return;

		$updated = array_values( array_filter( $booked, function ( $booking ) use ( $order_id, $week_id ) {
			$same_order = isset( $booking['order_id'] ) && intval( $booking['order_id'] ) === $order_id;
			$same_week  = isset( $booking['week_id'] )  && $booking['week_id'] === $week_id;
			return ! ( $same_order && $same_week );
		} ) );

		if ( empty( $updated ) ) {
			delete_post_meta( $bus_id, 'seats_booked' );
		} else {
			update_post_meta( $bus_id, 'seats_booked', maybe_serialize( $updated ) );
		}
	}
}

new Teatro_Ripianificazioni();

endif;
