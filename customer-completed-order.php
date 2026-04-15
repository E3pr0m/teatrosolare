<?php
/**
 * Customer completed order email
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/customer-completed-order.php.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates\Emails
 * @version 9.9.0
 */

use Automattic\WooCommerce\Utilities\FeaturesUtil;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$email_improvements_enabled = FeaturesUtil::feature_is_enabled( 'email_improvements' );
$first_name = $order->get_billing_first_name();

/*
 * @hooked WC_Emails::email_header() Output the email header
 */
do_action( 'woocommerce_email_header', $email_heading, $email );
?>

<?php echo $email_improvements_enabled ? '<div class="email-introduction">' : ''; ?>

<!-- Hero: conferma iscrizione -->
<table border="0" cellpadding="0" cellspacing="0" width="100%" style="margin-bottom: 28px;">
	<tr>
		<td align="center" style="padding: 28px 0 20px;">
			<div style="
				display: inline-block;
				background-color: #f5f0ea;
				border-radius: 50%;
				width: 64px;
				height: 64px;
				line-height: 64px;
				text-align: center;
				font-size: 30px;
				margin-bottom: 16px;
			">✓</div>
			<p style="
				margin: 0;
				font-size: 22px;
				font-weight: 700;
				color: #2c2c2c;
				letter-spacing: -0.3px;
			">
				<?php
				if ( ! empty( $first_name ) ) {
					/* translators: %s: Customer first name */
					printf( esc_html__( 'Ci vediamo presto, %s!', 'woocommerce' ), esc_html( $first_name ) );
				} else {
					esc_html_e( 'Ci vediamo presto!', 'woocommerce' );
				}
				?>
			</p>
			<p style="
				margin: 10px 0 0;
				font-size: 15px;
				color: #666666;
				line-height: 1.5;
			">
				<?php esc_html_e( 'La tua iscrizione è confermata. Siamo felici di averti con noi.', 'woocommerce' ); ?>
			</p>
		</td>
	</tr>
</table>

<?php echo $email_improvements_enabled ? '</div>' : ''; ?>

<?php
/*
 * @hooked WC_Emails::order_details() Shows the order details table.
 * @hooked WC_Structured_Data::generate_order_data() Generates structured data.
 * @hooked WC_Structured_Data::output_structured_data() Outputs structured data.
 * @since 2.5.0
 */
do_action( 'woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email );

/*
 * @hooked WC_Emails::order_meta() Shows order meta data.
 */
do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email );

/*
 * @hooked WC_Emails::customer_details() Shows customer details
 * @hooked WC_Emails::email_address() Shows email address
 */
do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email );
?>

<!-- Sezione: Cosa succede ora -->
<table border="0" cellpadding="0" cellspacing="0" width="100%" style="margin-top: 32px; margin-bottom: 8px;">
	<tr>
		<td style="
			background-color: #f9f6f1;
			border-radius: 10px;
			padding: 24px 28px;
		">
			<p style="
				margin: 0 0 16px;
				font-size: 13px;
				font-weight: 700;
				text-transform: uppercase;
				letter-spacing: 1.2px;
				color: #999999;
			"><?php esc_html_e( 'Cosa succede ora', 'woocommerce' ); ?></p>

			<!-- Step 1 -->
			<table border="0" cellpadding="0" cellspacing="0" width="100%" style="margin-bottom: 14px;">
				<tr>
					<td width="32" valign="top" style="padding-top: 1px;">
						<span style="
							display: inline-block;
							background-color: #2c2c2c;
							color: #ffffff;
							border-radius: 50%;
							width: 22px;
							height: 22px;
							line-height: 22px;
							text-align: center;
							font-size: 11px;
							font-weight: 700;
						">1</span>
					</td>
					<td valign="top">
						<p style="margin: 0; font-size: 14px; color: #2c2c2c; line-height: 1.5;">
							<?php esc_html_e( 'La settimana precedente la tua iscrizione riceverai una email con tutti i dettagli dei centri estivi: orari, indicazioni ed equipaggiamento necessario', 'woocommerce' ); ?>
						</p>
					</td>
				</tr>
			</table>

			<!-- Step 2 -->
			<table border="0" cellpadding="0" cellspacing="0" width="100%" style="margin-bottom: 14px;">
				<tr>
					<td width="32" valign="top" style="padding-top: 1px;">
						<span style="
							display: inline-block;
							background-color: #2c2c2c;
							color: #ffffff;
							border-radius: 50%;
							width: 22px;
							height: 22px;
							line-height: 22px;
							text-align: center;
							font-size: 11px;
							font-weight: 700;
						">2</span>
					</td>
					<td valign="top">
						<p style="margin: 0; font-size: 14px; color: #2c2c2c; line-height: 1.5;">
							<?php esc_html_e( 'Puoi rivedere i dettagli del tuo ordine in qualsiasi momento accedendo alla tua area personale.', 'woocommerce' ); ?>
						</p>
					</td>
				</tr>
			</table>

			<!-- Step 3 -->
			<table border="0" cellpadding="0" cellspacing="0" width="100%">
				<tr>
					<td width="32" valign="top" style="padding-top: 1px;">
						<span style="
							display: inline-block;
							background-color: #2c2c2c;
							color: #ffffff;
							border-radius: 50%;
							width: 22px;
							height: 22px;
							line-height: 22px;
							text-align: center;
							font-size: 11px;
							font-weight: 700;
						">3</span>
					</td>
					<td valign="top">
						<p style="margin: 0; font-size: 14px; color: #2c2c2c; line-height: 1.5;">
							<?php esc_html_e( 'Hai domande o hai bisogno di assistenza? Siamo qui per te.', 'woocommerce' ); ?>
						</p>
					</td>
				</tr>
			</table>
		</td>
	</tr>
</table>

<!-- Blocco contatto -->
<table border="0" cellpadding="0" cellspacing="0" width="100%" style="margin-top: 20px; margin-bottom: 24px;">
	<tr>
		<td align="center" style="
			border: 1px solid #e8e0d4;
			border-radius: 10px;
			padding: 18px 24px;
		">
			<p style="margin: 0; font-size: 14px; color: #555555; line-height: 1.6;">
				<?php esc_html_e( 'Scrivi a', 'woocommerce' ); ?>
				<a href="mailto:fiesole@teatrosolare.it" style="
					color: #2c2c2c;
					font-weight: 600;
					text-decoration: none;
					border-bottom: 1px solid #2c2c2c;
				">fiesole@teatrosolare.it</a>
				<?php esc_html_e( '— ti risponderemo al più presto.', 'woocommerce' ); ?>
				</p>
				<p style="margin: 8px 0 0; font-size: 14px; color: #555555; line-height: 1.6;">
					<?php esc_html_e( 'Oppure chiamaci al', 'woocommerce' ); ?>
					<a href="tel:+393889538836" style="
						color: #2c2c2c;
						font-weight: 600;
						text-decoration: none;
						border-bottom: 1px solid #2c2c2c;
					">388 953 8836</a>
					<?php esc_html_e( '(la segreteria è attiva lun-ven 9-13)', 'woocommerce' ); ?>
				</p>
		</td>
	</tr>
</table>

<?php
/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( $additional_content ) {
	echo $email_improvements_enabled ? '<table border="0" cellpadding="0" cellspacing="0" width="100%"><tr><td class="email-additional-content">' : '';
	echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
	echo $email_improvements_enabled ? '</td></tr></table>' : '';
}

/*
 * @hooked WC_Emails::email_footer() Output the email footer
 */
do_action( 'woocommerce_email_footer', $email );
?>

<!-- Footer legale -->
<table border="0" cellpadding="0" cellspacing="0" width="100%" style="margin-top: 24px;">
	<tr>
		<td align="center" style="padding: 0 32px 32px;">
			<p style="
				margin: 0;
				font-size: 12px;
				color: #aaaaaa;
				line-height: 1.7;
				text-align: center;
			">
				Teatro Solare - Associazione di Promozione Sociale &copy; <?php echo date('Y'); ?><br>
				Partita Iva 04838690487 &mdash; Codice Fiscale 94065840483
			</p>
		</td>
	</tr>
</table>

<?php
