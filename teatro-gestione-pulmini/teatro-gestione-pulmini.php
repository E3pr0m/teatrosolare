<?php
/*
 * Plugin Name:  Teatro Gestione Pulmini
 * Description:  Pannello admin per la visualizzazione e il reset delle prenotazioni pulmini — con reset globale, per-bus e per-singola-settimana
 * Text Domain:  teatro-gestione-pulmini
 * Version:      1.0.0
 * Author:       E3pr0m
 * Author URI:   https://www.e3pr0m.com
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'Teatro_Gestione_Pulmini' ) ) :

class Teatro_Gestione_Pulmini {

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_bus_inventory_menu' ) );
	}

	/* ------------------------------------------------------------------ */
	/* Menu admin                                                           */
	/* ------------------------------------------------------------------ */

	public function add_bus_inventory_menu() {
		add_submenu_page(
			'woocommerce',
			'Gestione Pulmini',
			'Gestione Pulmini',
			'manage_options',
			'bus-inventory',
			array( $this, 'bus_inventory_page' )
		);
	}

	/* ------------------------------------------------------------------ */
	/* Pagina principale                                                    */
	/* ------------------------------------------------------------------ */

	public function bus_inventory_page() {
		global $wpdb;

		/* ---- Azioni reset ---- */

		// Reset tutte le prenotazioni
		if ( isset( $_POST['reset_bookings'] ) && check_admin_referer( 'reset_bus_bookings' ) ) {
			$this->reset_all_bus_bookings();
			echo '<div class="notice notice-success"><p>Tutte le prenotazioni dei pulmini sono state resettate.</p></div>';
		}

		// Reset per singolo bus (tutte le settimane)
		if (
			isset( $_GET['action'] ) && $_GET['action'] === 'reset_bus' &&
			isset( $_GET['bus_id'] ) &&
			check_admin_referer( 'reset_bus_action' )
		) {
			$bus_id = intval( $_GET['bus_id'] );
			$this->reset_bus_bookings( $bus_id );
			echo '<div class="notice notice-success"><p>Prenotazioni per il bus <strong>' . esc_html( get_the_title( $bus_id ) ) . '</strong> resettate.</p></div>';
		}

		// Reset per singola settimana di un bus
		if (
			isset( $_GET['action'] ) && $_GET['action'] === 'reset_bus_week' &&
			isset( $_GET['bus_id'] ) && isset( $_GET['week_id'] ) &&
			check_admin_referer( 'reset_bus_week_action' )
		) {
			$bus_id  = intval( $_GET['bus_id'] );
			$week_id = sanitize_text_field( urldecode( $_GET['week_id'] ) );
			$removed = $this->reset_bus_week_bookings( $bus_id, $week_id );
			echo '<div class="notice notice-success"><p>Resettate <strong>' . intval( $removed ) . '</strong> prenotazioni per la settimana selezionata sul bus <strong>' . esc_html( get_the_title( $bus_id ) ) . '</strong>.</p></div>';
		}

		/* ---- Lettura bus ---- */

		$bus_ids = $wpdb->get_col(
			"SELECT DISTINCT post_id FROM {$wpdb->postmeta} WHERE meta_key = 'seats_capacity'"
		);

		echo '<div class="wrap">';
		echo '<h1>Gestione Pulmini</h1>';
		echo '<p>Panoramica delle prenotazioni per ciascun pulmino, suddivisa per settimana. Il reset per settimana rimuove solo le prenotazioni di quella settimana, lasciando intatte le altre.</p>';

		// Reset tutto
		echo '<form method="post" style="margin-bottom: 20px;">';
		wp_nonce_field( 'reset_bus_bookings' );
		echo '<input type="submit" name="reset_bookings" value="Resetta Tutte le Prenotazioni" class="button button-secondary"
			onclick="return confirm(\'Sei sicuro di voler resettare TUTTE le prenotazioni di TUTTI i pulmini? Questa azione non può essere annullata.\');">';
		echo '</form>';

		if ( ! empty( $bus_ids ) ) {

			echo '<table class="wp-list-table widefat fixed striped">';
			echo '<thead><tr>
				<th style="width:18%">Nome Pulmino</th>
				<th style="width:8%">Capacità</th>
				<th style="width:8%">Prenotati</th>
				<th style="width:46%">Dettaglio per Settimana</th>
				<th style="width:20%">Azioni</th>
			</tr></thead><tbody>';

			foreach ( $bus_ids as $bus_id ) {

				$bus_title    = get_the_title( $bus_id );
				$capacity     = get_field( 'seats_capacity', $bus_id ) ?: 0;
				$booked_raw   = get_post_meta( $bus_id, 'seats_booked', true );
				$booked_array = ! empty( $booked_raw ) ? maybe_unserialize( $booked_raw ) : [];
				$total_booked = is_array( $booked_array ) ? count( $booked_array ) : 0;

				// Raggruppa prenotazioni per week_id
				$weeks_data = [];
				if ( is_array( $booked_array ) ) {
					foreach ( $booked_array as $booking ) {
						$wid = isset( $booking['week_id'] ) ? $booking['week_id'] : 'Sconosciuto';
						if ( ! isset( $weeks_data[ $wid ] ) ) {
							$weeks_data[ $wid ] = 0;
						}
						$weeks_data[ $wid ]++;
					}
				}

				/* Costruisce il dettaglio per settimana con link reset */
				$week_details = '';
				if ( ! empty( $weeks_data ) ) {
					$week_details .= '<ul style="margin:0;padding-left:16px;">';
					foreach ( $weeks_data as $wid => $count ) {
						$readable = $this->format_week_id( $wid );
						$reset_week_url = wp_nonce_url(
							admin_url(
								'admin.php?page=bus-inventory&action=reset_bus_week'
								. '&bus_id=' . intval( $bus_id )
								. '&week_id=' . urlencode( $wid )
							),
							'reset_bus_week_action'
						);
						$week_details .= '<li style="display:flex;align-items:center;gap:8px;margin-bottom:4px;">'
							. '<span style="flex:1">' . esc_html( $readable ) . ' — <strong>' . intval( $count ) . '</strong> pren.</span>'
							. '<a href="' . esc_url( $reset_week_url ) . '" class="button button-small" style="white-space:nowrap;"'
							. ' onclick="return confirm(\'Resettare le prenotazioni per questa settimana su ' . esc_js( $bus_title ) . '?\');">'
							. 'Resetta settimana</a>'
							. '</li>';
					}
					$week_details .= '</ul>';
				} else {
					$week_details = '<em>Nessuna prenotazione</em>';
				}

				// Link reset intero bus
				$reset_bus_url = wp_nonce_url(
					admin_url( 'admin.php?page=bus-inventory&action=reset_bus&bus_id=' . intval( $bus_id ) ),
					'reset_bus_action'
				);
				$reset_bus_link = '<a href="' . esc_url( $reset_bus_url ) . '" class="button button-small"'
					. ' onclick="return confirm(\'Resettare TUTTE le prenotazioni per ' . esc_js( $bus_title ) . '?\');">'
					. 'Resetta tutto il bus</a>';

				echo '<tr>';
				echo '<td><strong>' . esc_html( $bus_title ) . '</strong></td>';
				echo '<td>' . esc_html( $capacity ) . '</td>';
				echo '<td>' . esc_html( $total_booked ) . '</td>';
				echo '<td>' . $week_details . '</td>';
				echo '<td>' . $reset_bus_link . '</td>';
				echo '</tr>';
			}

			echo '</tbody></table>';

		} else {
			echo '<p>Nessun pulmino con capacità configurata trovato.</p>';
		}

		echo '</div>';
	}

	/* ------------------------------------------------------------------ */
	/* Formattazione week_id → stringa leggibile                           */
	/* ------------------------------------------------------------------ */

	/**
	 * Converte un week_id (es. "1748822400-1749427200" oppure il vecchio
	 * formato aggregato "ts1-ts2,ts3-ts4") in una stringa leggibile.
	 */
	private function format_week_id( $week_id ) {
		$fmt          = get_option( 'date_format' );
		$single_weeks = explode( ',', $week_id );
		$parts        = [];

		foreach ( $single_weeks as $sw ) {
			$sw       = trim( $sw );
			$ts_parts = explode( '-', $sw );
			if ( count( $ts_parts ) === 2 && is_numeric( $ts_parts[0] ) && is_numeric( $ts_parts[1] ) ) {
				$parts[] = date_i18n( $fmt, intval( $ts_parts[0] ) ) . ' - ' . date_i18n( $fmt, intval( $ts_parts[1] ) );
			} else {
				$parts[] = $sw;
			}
		}

		return implode( ' | ', $parts );
	}

	/* ------------------------------------------------------------------ */
	/* Funzioni di reset                                                    */
	/* ------------------------------------------------------------------ */

	/**
	 * Resetta tutte le prenotazioni di tutti i bus.
	 */
	public function reset_all_bus_bookings() {
		global $wpdb;
		$bus_ids = $wpdb->get_col(
			"SELECT DISTINCT post_id FROM {$wpdb->postmeta} WHERE meta_key = 'seats_booked'"
		);
		foreach ( $bus_ids as $bus_id ) {
			delete_post_meta( $bus_id, 'seats_booked' );
		}
	}

	/**
	 * Resetta tutte le prenotazioni di un singolo bus.
	 *
	 * @param int $bus_id ID del post pulmino.
	 */
	public function reset_bus_bookings( $bus_id ) {
		delete_post_meta( intval( $bus_id ), 'seats_booked' );
	}

	/**
	 * Resetta le prenotazioni di una singola settimana per un bus.
	 * Le prenotazioni delle altre settimane rimangono intatte.
	 *
	 * @param int    $bus_id  ID del post pulmino.
	 * @param string $week_id week_id da rimuovere (formato "ts1-ts2").
	 * @return int   Numero di prenotazioni rimosse.
	 */
	public function reset_bus_week_bookings( $bus_id, $week_id ) {
		$bus_id     = intval( $bus_id );
		$booked_raw = get_post_meta( $bus_id, 'seats_booked', true );
		$booked     = ! empty( $booked_raw ) ? maybe_unserialize( $booked_raw ) : [];

		if ( empty( $booked ) || ! is_array( $booked ) ) {
			return 0;
		}

		$before  = count( $booked );
		$updated = array_values(
			array_filter( $booked, function ( $booking ) use ( $week_id ) {
				return ! isset( $booking['week_id'] ) || $booking['week_id'] !== $week_id;
			} )
		);
		$removed = $before - count( $updated );

		if ( empty( $updated ) ) {
			delete_post_meta( $bus_id, 'seats_booked' );
		} else {
			update_post_meta( $bus_id, 'seats_booked', maybe_serialize( $updated ) );
		}

		return $removed;
	}
}

new Teatro_Gestione_Pulmini();

endif;
