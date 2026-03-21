<?php
defined( 'ABSPATH' ) || exit;

/*--------------------------------------
*
* DEBUG SETTINGS
*
*--------------------------------------*/

@ini_set('error_reporting', E_ALL & ~(E_NOTICE|E_WARNING|E_DEPRECATED));
@ini_set('log_errors', true);

if(current_user_can('administrator')) {

	//define('WP_DEBUG', true);
	define('WP_DEBUG_DISPLAY', false);
	//define('SAVEQUERIES', false);
	//define('SCRIPT_DEBUG', false);
	@ini_set('display_errors', false);
	@ini_set('display_startup_errors', false);

} else {
	//define('WP_DEBUG', false);
	define('WP_DEBUG_DISPLAY', false);
	//define('SAVEQUERIES', false);
	//define('SCRIPT_DEBUG', false);
	@ini_set('display_errors', false);
	@ini_set('display_startup_errors', false);
}

//add theme css/js
function my_load_scripts($hook) {
	define('THEMEVER', time());
	wp_enqueue_script( 'custom_js', get_template_directory_uri().'/assets/js/custom.js', array(), THEMEVER );
	wp_enqueue_style( 'my_css', 	get_template_directory_uri().'/assets/css/styles.css', false,   THEMEVER );
}
add_action('wp_enqueue_scripts', 'my_load_scripts');

function teatrosolare_setup() {
	/*
		* Make theme available for translation.
		* Translations can be filed in the /languages/ directory.
		* If you're building a theme based on teatrosolare, use a find and replace
		* to change 'teatrosolare' to the name of your theme in all the template files.
		*/
	load_theme_textdomain( 'teatrosolare', get_template_directory() . '/languages' );

	// Add default posts and comments RSS feed links to head.
	add_theme_support( 'automatic-feed-links' );

	/*
		* Let WordPress manage the document title.
		* By adding theme support, we declare that this theme does not use a
		* hard-coded <title> tag in the document head, and expect WordPress to
		* provide it for us.
		*/
	add_theme_support( 'title-tag' );

	/*
		* Enable support for Post Thumbnails on posts and pages.
		*
		* @link https://developer.wordpress.org/themes/functionality/featured-images-post-thumbnails/
		*/
	add_theme_support( 'post-thumbnails' );

	// This theme uses wp_nav_menu() in one location.
	register_nav_menus(
		array(
			'menu-1' => esc_html__( 'Primary', 'teatrosolare' ),
			'Footer Menu' => esc_html__( 'Footer Menu', 'teatrosolare' ),
		)
	);

	/*
		* Switch default core markup for search form, comment form, and comments
		* to output valid HTML5.
		*/
	add_theme_support(
		'html5',
		array(
			'search-form',
			'comment-form',
			'comment-list',
			'gallery',
			'caption',
			'style',
			'script',
		)
	);

	// Set up the WordPress core custom background feature.
	add_theme_support(
		'custom-background',
		apply_filters(
			'teatrosolare_custom_background_args',
			array(
				'default-color' => 'ffffff',
				'default-image' => '',
			)
		)
	);

	// Add theme support for selective refresh for widgets.
	add_theme_support( 'customize-selective-refresh-widgets' );

	/**
	 * Add support for core custom logo.
	 *
	 * @link https://codex.wordpress.org/Theme_Logo
	 */
	add_theme_support(
		'custom-logo',
		array(
			'height'      => 250,
			'width'       => 250,
			'flex-width'  => true,
			'flex-height' => true,
		)
	);
}
add_action( 'after_setup_theme', 'teatrosolare_setup' );


// adding acf option page
if(function_exists('acf_add_options_page')){
    acf_add_options_page(array(
        'page_title'    => 'Theme General Settings',
        'menu_title'    => 'Theme Settings',
        'menu_slug'     => 'theme-general-settings',
        'capability'    => 'edit_posts',
        'redirect'      => false
    ));
    acf_add_options_sub_page(array(
        'page_title'    => 'Theme Header Settings',
        'menu_title'    => 'Header',
        'parent_slug'   => 'theme-general-settings',
    ));
    acf_add_options_sub_page(array(
        'page_title'    => 'Theme Footer Settings',
        'menu_title'    => 'Footer',
        'parent_slug'   => 'theme-general-settings',
    ));
}
// adding acf option page



function banner($title = false){
    if(!$title){
        $title = get_the_title();
    }
    $html = '
        <section class="siteBanner">
			<div class="bannerParallax"></div>
            <div class="container">
				<h1>'.$title.'</h1>
            </div>
        </section>
    ';
    return $html;
}
add_action( 'after_setup_theme', 'woocommerce_support' );
function woocommerce_support() {
   	add_theme_support( 'woocommerce' );
	// add_theme_support( 'wc-product-gallery-zoom' );
	add_theme_support( 'wc-product-gallery-lightbox' );
	add_theme_support( 'wc-product-gallery-slider' ); 
}
add_filter(
    'template_include',
    static function ( $template ) {
        if ( is_singular( 'tribe_event_series' ) ) {
            $template = locate_template( 'single-series.php' );
        }
 
        return $template;
    }
);
add_filter('woocommerce_enable_order_notes_field', '__return_false');

remove_action( 'woocommerce_before_checkout_form', 'woocommerce_checkout_login_form', 10 );


function checkout_login_prompts($checkout){
	if(!is_user_logged_in()){
		echo '<div class="checkoutCustomPromps">';		
			echo '<div class="checkLoginWrap row">
				<div class="contentInner"><strong>'.esc_html__('IMPORTANT!', 'teatrosolare').'</strong>'. esc_html__('
				If you already have an account, log in to save the tickets in your profile', 'teatrosolare').'</div>
				<a href="'.get_permalink( get_option('woocommerce_myaccount_page_id') ).'" class="siteButtonDark buttonSmall text-lw">'.esc_html__('login', 'teatrosolare').'</a>
			</div>';		
			echo '<div class="checkRegisterWrap row">
				<div><strong>'.esc_html__('Don\'t have an account?', 'teatrosolare').'</strong></div>
				<a href="'.get_the_permalink(25).'" class="siteButtonDark buttonSmall">'.esc_html__('create account', 'teatrosolare').'</a>
			</div>';
		echo '</div>';
	}
}

add_action( 'woocommerce_review_order_before_submit', 'teatro_add_checkout_privacy_policy', 9 );
    
function teatro_add_checkout_privacy_policy() {   
	woocommerce_form_field( 'privacy_policy', array(
	'type'          => 'checkbox',
	'class'         => array('form-row privacy'),
	'label_class'   => array('woocommerce-form__label woocommerce-form__label-for-checkbox checkbox'),
	'input_class'   => array('woocommerce-form__input woocommerce-form__input-checkbox input-checkbox'),
	'required'      => true,
	'label'         => ''.esc_html__('Ho letto e accetto le condizioni della ', 'teatrosolare').' <a href="https://teatrosolare.it/privacy-policy/" target="_blank">Privacy Policy</a>',
	));    
}
   
// Show notice if customer does not tick    
add_action( 'woocommerce_after_checkout_validation', 'teatro_not_approved_privacy' );   
function teatro_not_approved_privacy() {
    if ( ! (int) isset( $_POST['privacy_policy'] ) ) {
        wc_add_notice( __( 'Please accept the Privacy Policy' ), 'error' );
    }
}

function remove_dashboard_and_account_details( $items ) {
    // Remove the 'Dashboard' and 'Account details' items from the menu
    unset( $items['dashboard'] );
    unset( $items['dati-personali'] ); // Remove custom Dati Personali page
    //unset( $items['edit-account'] );

    return $items;
}
add_filter( 'woocommerce_account_menu_items', 'remove_dashboard_and_account_details', 10, 999 );

add_filter('woocommerce_my_account_my_orders_columns', 'custom_removing_order_status', 10, 1);

function custom_removing_order_status( $order ){
    unset($order['order-date']);
    return $order;
}

// Advanced Order Export for WooCommerce PRO - custom fields to export 
add_filter('woe_get_order_product_fields', function($fields,$format) {
	$fields['selected_child_lastname'] = array( 'label' => 'COGNOME', 'colname' => 'COGNOME', 'checked' => 1 );
	$fields['selected_child_firstname'] = array( 'label' => 'NOME', 'colname' => 'NOME', 'checked' => 1 );
	$fields['selected_child_telephone'] = array( 'label' => 'telefono', 'colname' => 'telefono', 'checked' => 1 );
	$fields['selected_child_dob'] = array( 'label' => 'anno di nascita', 'colname' => 'anno di nascita', 'checked' => 1 );
    $fields['selected_child_cf'] = array( 'label' => 'codice fiscale', 'colname' => 'codice fiscale', 'checked' => 1 );
	$fields['selected_child_trasporto'] = array( 'label' => 'trasporto', 'colname' => 'trasporto', 'checked' => 1 );
	//$fields['selected_child_meta'] = array( 'label' => 'note / allergie / bes', 'colname' => 'noteallergiebes', 'checked' => 1 );	
	$fields['selected_child_meta_bes'] = array( 'label' => 'bes', 'colname' => 'bes', 'checked' => 1 );
	$fields['selected_child_meta_allergie'] = array( 'label' => 'allergie', 'colname' => 'allergie', 'checked' => 1 );	
	$fields['selected_child_meta_note'] = array( 'label' => 'note', 'colname' => 'note', 'checked' => 1 );	
	return $fields;
},10, 2);

add_filter('woe_get_order_product_value_selected_child_firstname', function($value,$order, $item, $product,$item_meta) {
	$user_id_others = $item->get_meta('parent_childs_selected'); $user_id_amf = $item->get_meta('selected_child_id'); 
	$user_id = !empty($user_id_others)?$user_id_others:$user_id_amf;	
	return get_user_meta( $user_id, 'first_name', true ); //print_r($item->get_meta('selected_child_id'));
}, 10, 5);

add_filter('woe_get_order_product_value_selected_child_lastname', function($value,$order, $item, $product,$item_meta) {
	$user_id_others = $item->get_meta('parent_childs_selected'); $user_id_amf = $item->get_meta('selected_child_id'); 
	$user_id = !empty($user_id_others)?$user_id_others:$user_id_amf;	
	return get_user_meta( $user_id, 'last_name', true );
}, 10, 5);

add_filter('woe_get_order_product_value_selected_child_telephone', function($value,$order, $item, $product,$item_meta) {
	$user_id = $item->get_meta('parent_childs_selected');
	if(!empty(get_user_meta( $user_id, 'billing_phone', true ))){
		return get_user_meta( $user_id, 'billing_phone', true );
	}
	return get_user_meta( $user_id, 'telefono', true );
}, 10, 5);

add_filter('woe_get_order_product_value_selected_child_trasporto', function($value,$order, $item, $product,$item_meta) {
	global $WC_custom_teatro_attributes; 
	$busID = $item->get_meta('product_buses_selected');
	$busID = $WC_custom_teatro_attributes->getBusNameById($busID);
	$parts = explode(",", $busID);
	foreach ($parts as &$part) {
		$part = trim(str_replace("-", "None", $part));
	}
	$newString = implode(",\n", $parts);
	return $newString;
}, 10, 5);

add_filter('woe_get_order_product_value_selected_child_dob', function($value,$order, $item, $product, $item_meta) {
	$user_id = $item->get_meta('parent_childs_selected');
	$dob = get_user_meta($user_id, 'child_dob', true );
	if(!empty($dob)){
		$dob = date("Y", strtotime(get_user_meta( $user_id, 'child_dob', true )));
	}
	return $dob;

}, 10, 5);

/* Code added on 12012026 == start */
add_filter('woe_get_order_value_USER_child_dob', function( $value, $order, $fieldname ) { //print_rdata($order->get_items());
	$order_items = $order->get_items(); $dob=''; //print_rdata($order_items->get_meta('parent_children_selected'));
	foreach($order_items as $item_id => $item){ 
		$user_id_others = $item->get_meta('parent_childs_selected'); $user_id_amf = $item->get_meta('selected_child_id'); 
		$user_id = !empty($user_id_others)?$user_id_others:$user_id_amf;	$dob = get_user_meta($user_id, 'child_dob', true );
		if(!empty($dob)){
			$dob = date("Y-m-d", strtotime(get_user_meta( $user_id, 'child_dob', true )));
		}
	}
  return $dob;
}, 10, 3 );
/* Code added on 12012026 == end */
add_filter('woe_get_order_product_value_selected_child_cf', function($value,$order, $item, $product, $item_meta) {
	$user_id_others = $item->get_meta('parent_childs_selected'); $user_id_amf = $item->get_meta('selected_child_id'); 
	$user_id = !empty($user_id_others)?$user_id_others:$user_id_amf;	
	$cf = get_user_meta($user_id, 'codice_fiscale', true );
	if(!empty($cf)){
		$cf = get_user_meta( $user_id, 'codice_fiscale', true );
	}
	return $cf;

}, 10, 5);

function print_rdata($args=false){
	echo '<pre>', print_r($args, 1), '</pre>';
}

//Parent ID
add_filter('woe_get_order_value_USER_parent',function($value,$order, $fieldname) {  //parent_id
	$user_id=$order->get_user_id();
	return !empty($user_id)?$user_id:$value;
}, 10, 3 );

//Parent Name & Surname
add_filter('woe_get_order_value_USER_first_name',function( $value,$order, $fieldname ) {
	$user_id=$order->get_user_id();
	if(!empty($user_id)){
		$parentInfo = get_user_by('ID', $user_id);
		return !empty($parentInfo)?$parentInfo->display_name:$value;
	} else {
		return $value;
	}	
}, 10, 3 );

// BES / Allergie / Note - 1 column
add_filter('woe_get_order_product_value_selected_child_meta', function($value,$order, $item, $product,$item_meta) {
	$user_id = $item->get_meta('parent_childs_selected');
	$usermeta = [];
	if(!empty(get_user_meta( $user_id, 'bes_quali', true ))){
		$usermeta[] = get_user_meta( $user_id, 'bes_quali', true );
	}
	if(!empty(get_user_meta( $user_id, 'allergie_o_intolleranze_quali', true ))){
		$usermeta[] = get_user_meta( $user_id, 'allergie_o_intolleranze_quali', true );
	}
	if ( $order->get_customer_note() ){
		$usermeta[] = wp_kses_post( nl2br( wptexturize( $order->get_customer_note() ) ) );
	}
	$meta = implode(', ', $usermeta);
	return $meta;
}, 10, 5);

/* BES new column code added on 14-01-2026 */
add_filter('woe_get_order_value_USER_bes',function( $value,$order, $fieldname ) {
	$order_items = $order->get_items(); $bes_quali=[]; //print_rdata($order_items->get_meta('parent_children_selected'));
	foreach($order_items as $item_id => $item){ 
		$user_id_others = $item->get_meta('parent_childs_selected'); $user_id_amf = $item->get_meta('selected_child_id'); 
		$user_id = !empty($user_id_others)?$user_id_others:$user_id_amf;	$bes_quali[] = get_user_meta($user_id, 'bes_quali', true );		
	}  
	///print_rdata($bes_quali);
	return !empty($bes_quali)?implode(', ', $bes_quali):'';
	//return $value;
}, 10, 3 );
/* Allergie new column code added on 14-01-2026 */
add_filter('woe_get_order_value_USER_allergie_o_intolleranze',function( $value,$order, $fieldname ) {
	$order_items = $order->get_items(); $allergie_o_intolleranze_quali=[]; //print_rdata($order_items->get_meta('parent_children_selected'));
	foreach($order_items as $item_id => $item){ 
		$user_id_others = $item->get_meta('parent_childs_selected'); $user_id_amf = $item->get_meta('selected_child_id'); 
		$user_id = !empty($user_id_others)?$user_id_others:$user_id_amf;	
		$allergie_o_intolleranze_quali[] = get_user_meta($user_id, 'allergie_o_intolleranze_quali', true );		
	}  
	return !empty($allergie_o_intolleranze_quali)?implode(', ', $allergie_o_intolleranze_quali):'';
	//return $value;
}, 10, 3 );


/****Added on 15 Jan 2026 - 001 ***/
add_filter('woe_get_order_value_USER_codice_fiscale_courses',function( $value,$order, $fieldname ) {
    $value = 'NA';
    $meta_data = $order->get_meta_data();
    foreach ( $order->get_items() as $item_id => $item ) {
        $user_id_others = $item->get_meta('parent_childs_selected'); $user_id_amf = $item->get_meta('selected_child_id'); 
		$childID = !empty($user_id_others)?$user_id_others:$user_id_amf;	
        // get user meta of this child ID
        $value = get_user_meta( $childID, 'codice_fiscale', true );
        if(!empty($value)){
            break; // exit loop if value found
        }
    }
  return $value;
}, 10, 3 );

add_filter('woe_get_order_product_value_selected_child_trasporto', function ($value, $order, $item, $product,$item_meta) {
    //return 'abbcc';
  return $value ?: 'N/D';
}, 10, 5 );

add_filter('woe_get_order_product_value_product_bus_stops_selected', function ($value, $order, $item, $product,$item_meta) {
  return $value ?: 'N/D';
}, 10, 5 );

add_filter('woe_get_order_product_value_product_bus_stop_start_time', function ($value, $order, $item, $product,$item_meta) {
  return $value ?: 'N/D';
}, 10, 5 );

add_filter('woe_get_order_product_value_product_bus_stop_end_time', function ($value, $order, $item, $product,$item_meta) {
  return $value ?: 'N/D';
}, 10, 5 );

add_filter('woe_get_order_value_USER_Comune di residenza',function( $value,$order, $fieldname ) {
	$order_items = $order->get_items(); $allergie_o_intolleranze_quali=[]; //print_rdata($order_items->get_meta('parent_children_selected'));
	foreach($order_items as $item_id => $item){ 
		$user_id_others = $item->get_meta('parent_childs_selected'); $user_id_amf = $item->get_meta('selected_child_id'); 
		$user_id = !empty($user_id_others)?$user_id_others:$user_id_amf;	
		$comune_di_residenza = get_user_meta($user_id, 'comune_di_residenza', true );		
        if(!empty($comune_di_residenza)){
            break; // exit loop if value found
        }
	}  
	return $comune_di_residenza ?: 'N/D';
	//return $value;
}, 10, 3 );


/****Added on 15 Jan 2026 - 001 ***/




// BES
add_filter('woe_get_order_product_value_selected_child_meta_bes', function($value,$order, $item, $product,$item_meta) {
	$user_id = $item->get_meta('parent_childs_selected');
	$usermeta = [];
	if(!empty(get_user_meta( $user_id, 'bes_quali', true ))){
		$usermeta[] = get_user_meta( $user_id, 'bes_quali', true );
	}
	$meta = implode(', ', $usermeta);
	return $meta;
}, 10, 5);

// Allergie
add_filter('woe_get_order_product_value_selected_child_meta_allergie', function($value,$order, $item, $product,$item_meta) {
	$user_id = $item->get_meta('parent_childs_selected');
	$usermeta = [];
	if(!empty(get_user_meta( $user_id, 'allergie_o_intolleranze_quali', true ))){
		$usermeta[] = get_user_meta( $user_id, 'allergie_o_intolleranze_quali', true );
	}
	$meta = implode(', ', $usermeta);
	return $meta;
}, 10, 5);

// Note
add_filter('woe_get_order_product_value_selected_child_meta_note', function($value,$order, $item, $product,$item_meta) {
	$user_id = $item->get_meta('parent_childs_selected');
	$usermeta = [];
	if ( $order->get_customer_note() ){
		$usermeta[] = wp_kses_post( nl2br( wptexturize( $order->get_customer_note() ) ) );
	}
	$meta = implode(', ', $usermeta);
	return $meta;
}, 10, 5);

add_filter('woe_get_order_product_value_product_bus_stops_selected', function ($value, $order, $item, $product,$Item_meta) {
	global $WC_custom_teatro_attributes;
	$parts = explode(",", $WC_custom_teatro_attributes->replaceSignWithComma($value));
	foreach ($parts as &$part) {
		$part = trim(str_replace("-", "None", $part));
	}
	$newString = implode(",\n", $parts);
	return $newString;
}, 10, 5);

add_filter('woe_get_order_product_value_product_bus_stop_start_time', function ($value, $order, $item, $product,$Item_meta) {
	global $WC_custom_teatro_attributes;
	$parts = explode(",", $WC_custom_teatro_attributes->replaceSignWithComma($value));
	foreach ($parts as &$part) {
		$part = trim(str_replace("-", "None", $part));
		if($part != 'None' && !empty($part)){
			$part = date('H:i', strtotime($part));
		}
	}
	$newString = implode(",\n", $parts);
	return $newString;
}, 10, 5);

add_filter('woe_get_order_product_value_product_bus_stop_end_time', function ($value, $order, $item, $product,$Item_meta) {
	global $WC_custom_teatro_attributes;
	$parts = explode(",", $WC_custom_teatro_attributes->replaceSignWithComma($value));
	foreach ($parts as &$part) {
		$part = trim(str_replace("-", "None", $part));
		if($part != 'None' && !empty($part)){
			$part = date('H:i', strtotime($part));
		}
	}
	$newString = implode(",\n", $parts);
	return $newString;
}, 10, 5);

add_filter('woe_get_order_product_value_product_weeks_selected', function ($value, $order, $item, $product,$Item_meta) {
	global $WC_custom_teatro_attributes;
	$parts = str_replace("@@", ',', $WC_custom_teatro_attributes->replaceSignWithComma($value));
	$parts = explode(',', $parts);

	foreach ($parts as &$part) {
		$part = trim($part);
		$dates = explode("-", $part);
		$formatted_dates = array_map(function($date) {
			$date = trim($date);
			$date_object = DateTime::createFromFormat('j F Y', $date);
			
			if ($date_object) {
				return $date_object->format('d/m/y');
			} else {
				error_log("Invalid date: " . $date);
				return $date;
			}
		}, $dates);
		$part = implode(" - ", $formatted_dates);
	}
	$newString = implode(",\n", $parts);
	return $newString;
}, 10, 5);

add_filter( 'manage_users_columns', 'add_user_isee' );
  
function add_user_isee( $columns ) {
    $columns['isee_certificate'] = 'ISEE';
    return $columns;
}
  
add_filter( 'manage_users_custom_column', 'add_user_isee_column_content', 10, 3 );
  
function add_user_isee_column_content( $content, $column, $user_id ) {
    
    if ( 'isee_certificate' === $column ) {
		
		$certType = get_user_meta($user_id, 'isee_certificate', true);
		$cert = get_user_meta($user_id, 'isee_certificate_file', true);
		
		if (!empty($certType)) {
			$content = '<a href="'.$cert.'" target="_blank" download><strong>'.strtoupper($certType).'</strong></a>';
		}
    }
    
    return $content;
}

add_action('show_user_profile', 'display_isee_field');;
add_action( 'edit_user_profile', 'display_isee_field' );
function display_isee_field($user) {
    $custom_field = get_user_meta($user->ID, 'isee_certificate_file', true);
    ?>
    <table class="form-table">
        <tr>
            <th><?php _e('ISEE Certificate', 'teatrosolare'); ?></th>
            <td>
				<a href="<?php echo esc_html($custom_field); ?>" target="_blank" download><button type="button" class="button"><?php echo _e('View Certificate', 'teatrosolare');?></button></a>
			</td>
        </tr>
    </table>
    <?php
}

add_action('template_redirect', 'redirect_courses_for_guests');
function redirect_courses_for_guests() {
    if (is_product()) {
        global $post;
		$_product = wc_get_product( $post->ID );
        if ( $_product->is_type('courses') && !is_user_logged_in()) {
			$args = array(
				'post_type' => 'campo-solare',
				'posts_per_page' => 1,
				'meta_query' => array(
					array(
						'key' => 'product',
						'value' => '"' . $post->ID . '"',
						'compare' => 'LIKE'
					)
				),
				'fields'=>'ids'
			);
			$query = get_posts($args);

			if (!empty($query)) {
				wp_redirect(get_the_permalink($query[0]));
				exit();
			}
        }
    }
}


// Sync WordPress First/Last Name with Billing First/Last Name

add_filter( 'pre_user_first_name', 'teatro_user_edit_profile_edit_billing_first_name' );
 
function teatro_user_edit_profile_edit_billing_first_name( $first_name ) {
    // Prevent account first name from changing when using an alternate checkout address
    if ( is_checkout() ) {
        $selected = isset( $_POST['teatro_alt_billing_select'] ) ? sanitize_text_field( $_POST['teatro_alt_billing_select'] ) : '';
        $is_default_address = empty( $selected ) || $selected === 'add_new';

        if ( ! $is_default_address ) {
            $user_id = get_current_user_id();
            if ( $user_id ) {
                return get_user_meta( $user_id, 'first_name', true );
            }
        }
    }

    if ( isset( $_POST['billing_first_name'] ) ) {
        $first_name = sanitize_text_field( $_POST['billing_first_name'] );
    }
    return $first_name;
}
 
add_filter( 'pre_user_last_name', 'teatro_user_edit_profile_edit_billing_last_name' );
 
function teatro_user_edit_profile_edit_billing_last_name( $last_name ) {
    // Prevent account last name from changing when using an alternate checkout address
    if ( is_checkout() ) {
        $selected = isset( $_POST['teatro_alt_billing_select'] ) ? sanitize_text_field( $_POST['teatro_alt_billing_select'] ) : '';
        $is_default_address = empty( $selected ) || $selected === 'add_new';

        if ( ! $is_default_address ) {
            $user_id = get_current_user_id();
            if ( $user_id ) {
                return get_user_meta( $user_id, 'last_name', true );
            }
        }
    }

    if ( isset( $_POST['billing_last_name'] ) ) {
        $last_name = sanitize_text_field( $_POST['billing_last_name'] );
    }
    return $last_name;
}

/**
 * Map Gravity Forms registration fields to WooCommerce billing fields
 * This runs after a user is created via GF User Registration
 */
add_action( 'gform_user_registered', 'teatro_map_gf_fields_to_billing', 10, 4 );
function teatro_map_gf_fields_to_billing( $user_id, $feed, $entry, $user_pass ) {
    // Map codice_fiscale to billing_cf2
    $codice_fiscale = get_user_meta( $user_id, 'codice_fiscale', true );
    if ( ! empty( $codice_fiscale ) ) {
        update_user_meta( $user_id, 'billing_cf2', $codice_fiscale );
    }
    
    // Map indirizzo_di_residenza to billing_address_1
    $indirizzo = get_user_meta( $user_id, 'indirizzo_di_residenza', true );
    if ( ! empty( $indirizzo ) ) {
        update_user_meta( $user_id, 'billing_address_1', $indirizzo );
    }
    
    // Map cap to billing_postcode
    $cap = get_user_meta( $user_id, 'cap', true );
    if ( ! empty( $cap ) ) {
        update_user_meta( $user_id, 'billing_postcode', $cap );
    }
    
    // Map comune_di_residenza to billing_city
    $comune = get_user_meta( $user_id, 'comune_di_residenza', true );
    if ( ! empty( $comune ) ) {
        update_user_meta( $user_id, 'billing_city', $comune );
    }
    
    // Map first_name and last_name to billing fields
    $first_name = get_user_meta( $user_id, 'first_name', true );
    if ( ! empty( $first_name ) ) {
        update_user_meta( $user_id, 'billing_first_name', $first_name );
    }
    
    $last_name = get_user_meta( $user_id, 'last_name', true );
    if ( ! empty( $last_name ) ) {
        update_user_meta( $user_id, 'billing_last_name', $last_name );
    }
    
    // Map user email to billing_email
    $user = get_userdata( $user_id );
    if ( $user ) {
        update_user_meta( $user_id, 'billing_email', $user->user_email );
    }

    /**
     * Normalise country and state coming from Gravity Forms address field
     * GF usually stores full country / state names, while WooCommerce expects ISO codes.
     * Here we convert them to the codes WooCommerce uses for billing_country / billing_state.
     */
    if ( function_exists( 'WC' ) ) {
        $wc_countries = WC()->countries;

        // Normalise billing_country.
        $raw_country = get_user_meta( $user_id, 'billing_country', true );
        if ( ! empty( $raw_country ) && $wc_countries ) {
            $countries    = $wc_countries->get_countries(); // [ 'IT' => 'Italia', ... ]
            $country_code = $raw_country;

            // If the stored value is not already a valid country code, try to find it by name.
            if ( ! isset( $countries[ $country_code ] ) ) {
                $country_code = '';
                foreach ( $countries as $code => $name ) {
                    if ( strcasecmp( $raw_country, $name ) === 0 ) {
                        $country_code = $code;
                        break;
                    }
                }
            }

            if ( $country_code && isset( $countries[ $country_code ] ) ) {
                update_user_meta( $user_id, 'billing_country', $country_code );
            }
        }

        // Normalise billing_state using the (possibly updated) billing_country.
        $raw_state = get_user_meta( $user_id, 'billing_state', true );
        $country   = get_user_meta( $user_id, 'billing_country', true );

        if ( ! empty( $raw_state ) && ! empty( $country ) && $wc_countries ) {
            $states = $wc_countries->get_states( $country ); // [ 'FI' => 'Firenze', ... ] for IT.

            if ( ! empty( $states ) && is_array( $states ) ) {
                $state_code = $raw_state;

                // If the stored value is not already a valid state code, try to find it by name.
                if ( ! isset( $states[ $state_code ] ) ) {
                    $state_code = '';
                    foreach ( $states as $code => $name ) {
                        if ( strcasecmp( $raw_state, $name ) === 0 ) {
                            $state_code = $code;
                            break;
                        }
                    }
                }

                if ( $state_code && isset( $states[ $state_code ] ) ) {
                    update_user_meta( $user_id, 'billing_state', $state_code );
                }
            }
        }
    }
}

add_filter("woe_get_products_itemmeta_values", function($key, $metas){

    if($key == "product_buses_selected") {
        $args = array(
			'post_type' => 'bus',
			'posts_per_page' => -1, 
			'post_status' => 'publish',
		);
		$posts = get_posts($args);
		$metas = [];
		foreach ($posts as $post) {
			$metas[] = $post->ID;
		}
    }
	if($key == 'product_weeks_selected'){
		global $wpdb, $WC_custom_teatro_attributes;
		$metas = [];
		$limit = apply_filters( 'woe_itemmeta_values_max_records', 300 );
		$meta_key_ent = esc_html( $key );

		$metas = $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT meta_value FROM {$wpdb->prefix}woocommerce_order_itemmeta where (meta_key = '%s' OR meta_key='%s') LIMIT $limit", $key, $meta_key_ent ) );
		sort( $metas );

		$metas = $WC_custom_teatro_attributes->replaceSignWithComma($metas);

		$modified_date_ranges = array_map(function($string) {
			$parts = explode(',', $string);
			$parts = array_map('trim', $parts);
			$parts = array_unique($parts);
			return $parts;
		}, $metas);
		
		$metas = array_merge(...$modified_date_ranges);
		return array_unique($metas);
	}
    return $metas;
}, 10, 2);

add_filter( 'woe_settings_validate_defaults',  function($settings ){
	foreach($settings['product_itemmeta'] as $k=>$v)
		$settings['product_itemmeta'][$k] = str_replace(" = ", " LIKE ", $v);
	return $settings ;
});

// GRAVITY - Localize jQuery Datepicker

/*function add_datepicker_regional() {
    if ( wp_script_is( 'gform_datepicker_init' ) ) {
        wp_enqueue_script( 'datepicker-regional', get_stylesheet_directory_uri() . '/assets/js/datepicker-it.js', array( 'gform_datepicker_init' ), false, true );
        remove_action( 'wp_enqueue_scripts', 'wp_localize_jquery_ui_datepicker', 1000 );
    }
}
add_action( 'gform_enqueue_scripts', 'add_datepicker_regional', 11 );*/


add_filter('gettext', 'change_proceed_to_checkout_text', 20, 3);
function change_proceed_to_checkout_text($translated_text, $text, $domain) {
    if ($translated_text === 'Proceed to checkout' && $domain === 'woocommerce') {
        $translated_text = 'Vai alla cassa'; // Sostituisci con il testo desiderato
    }
    return $translated_text;
}

/* */
///add_action('admin_head', 'my_custom_css_admin_only'); 
function my_custom_css_admin_only() {
  echo '<style> #woocommerce-order-items .woocommerce_order_items_wrapper table.woocommerce_order_items table.display_meta { display:none; } </style>';
}
/* WooCommerce Password Strength === added on 12-04-2025 */
add_filter( 'woocommerce_min_password_strength', 'wpglorify_woocommerce_password_filter', 10);
function wpglorify_woocommerce_password_filter() { return 1; } 

/* remove order again button from myaccount => orders => order details  */
remove_action('woocommerce_order_details_after_order_table', 'woocommerce_order_again_button'); 


/* Code added on 210820252 == start */
add_filter('woe_get_order_product_value_product_buses_selected', function ($value, $order, $item, $product, $item_meta) {
	global $WC_custom_teatro_attributes;
	$parts = str_replace("@@", ',', $WC_custom_teatro_attributes->replaceSignWithComma($value));
	$parts = explode(',', $parts); $return=[];
	foreach($parts as $part){
		if(!empty($part)):
			array_push($return, get_the_title($part));
		else:
			array_push($return, 'None');
		endif;
	}
	return !empty($return)?implode(', ', $return):'';
	//return $value;
}, 10, 5 );


// Rimuove il conteggio dei risultati
remove_action( 'woocommerce_before_shop_loop', 'woocommerce_result_count', 20 );

// Rimuove il selettore di ordinamento
remove_action( 'woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 30 );

// (Opzionale) — rimuove anche dal fondo della pagina se il tema li mostra anche lì
remove_action( 'woocommerce_after_shop_loop', 'woocommerce_result_count', 20 );
remove_action( 'woocommerce_after_shop_loop', 'woocommerce_catalog_ordering', 10 );

// Rimuove il metodo di spedizione dalla thank you page (pagina ordine completato)
add_filter( 'woocommerce_order_shipping_to_display', '__return_empty_string' );

// Rimuove la riga "Metodo di spedizione" dalle email di WooCommerce
add_filter( 'woocommerce_get_order_item_totals', function( $total_rows, $order, $tax_display ) {
    unset( $total_rows['shipping'] ); // Rimuove la voce di spedizione
    return $total_rows;
}, 10, 3 );


add_filter( 'gettext', function( $translated, $original, $domain ) {
    if ( $original === 'Billing Details' ) {
        $translated = 'Dettagli di fatturazione';
    }
    if ( $original === 'Additional Details' ) {
        $translated = 'Informazioni aggiuntive';
    }
    return $translated;
}, 10, 3 );

add_action( 'woocommerce_email_header', function() {
    switch_to_locale( 'it_IT' );
}, 1 );

add_action( 'woocommerce_email_footer', function() {
    restore_previous_locale();
}, 999 );

// Imposta gli ordini pagati come "Completato" di default
add_action( 'woocommerce_thankyou', 'imposta_ordine_completato', 10, 1 );

function imposta_ordine_completato( $order_id ) {
    if ( ! $order_id ) return;

    $order = wc_get_order( $order_id );

    // Controllo che esista l'ordine
    if ( ! $order instanceof WC_Order ) return;

    // Se l'ordine è pagato, cambio lo stato
    if ( $order->is_paid() && $order->get_status() !== 'completed' ) {
        $order->update_status( 'completed' );
    }
}

// Populate billing_cf2 with codice_fiscale user meta.
add_filter( 'woocommerce_checkout_fields', 'prepoulate_billing_cf2' );
function prepoulate_billing_cf2( $fields ) {
    $user_id = get_current_user_id();
    if ( $user_id && isset( $fields['billing']['billing_cf2'] ) ) {
        $codice_fiscale = get_user_meta( $user_id, 'codice_fiscale', true );
        if ( ! empty( $codice_fiscale ) ) {
            $fields['billing']['billing_cf2']['default'] = $codice_fiscale;
        }
    }
    return $fields;
}

// Save/update billing_cf2 to user meta 'codice_fiscale' on checkout.
// IMPORTANT: Only update if using DEFAULT address AND field is EMPTY in default profile
add_action( 'woocommerce_checkout_update_user_meta', 'save_billing_cf2_to_user_meta' );
function save_billing_cf2_to_user_meta( $user_id ) {
    // Check if using default address or alternate address
    $selected = '';
    if ( isset( $_POST['teatro_alt_billing_select'] ) ) {
        $selected = sanitize_text_field( $_POST['teatro_alt_billing_select'] );
    } elseif ( WC()->session && WC()->session->get( 'teatro_using_alternate_billing' ) ) {
        $selected = 'alternate';
    }
    
    // Only process for default address - block if using alternate
    if ( $selected === 'alternate' ) {
        return; // Don't update default profile when using alternate address
    }
    
    // Only update if billing_cf2 is empty in default profile
    if ( isset( $_POST['billing_cf2'] ) ) {
        $current_codice_fiscale = get_user_meta( $user_id, 'codice_fiscale', true );
        $current_billing_cf2 = get_user_meta( $user_id, 'billing_cf2', true );
        
        // Only update if both are empty
        if ( empty( $current_codice_fiscale ) && empty( $current_billing_cf2 ) && ! empty( $_POST['billing_cf2'] ) ) {
            $billing_cf2 = sanitize_text_field( $_POST['billing_cf2'] );
            update_user_meta( $user_id, 'codice_fiscale', $billing_cf2 );
            update_user_meta( $user_id, 'billing_cf2', $billing_cf2 );
        }
    }
}

// AJAX handler to update user meta
// IMPORTANT: Only update if field is EMPTY in default profile
add_action( 'wp_ajax_update_codice_fiscale_meta', 'ajax_update_codice_fiscale_meta' );
function ajax_update_codice_fiscale_meta() {
    check_ajax_referer( 'update_codice_fiscale_nonce', 'security' );

    $user_id = get_current_user_id();
    if ( $user_id && isset( $_POST['codice_fiscale'] ) ) {
        // Only update if codice_fiscale is currently empty
        $current_codice_fiscale = get_user_meta( $user_id, 'codice_fiscale', true );
        $current_billing_cf2 = get_user_meta( $user_id, 'billing_cf2', true );
        
        if ( empty( $current_codice_fiscale ) && empty( $current_billing_cf2 ) ) {
        $codice_fiscale = sanitize_text_field( $_POST['codice_fiscale'] );
            if ( ! empty( $codice_fiscale ) ) {
        update_user_meta( $user_id, 'codice_fiscale', $codice_fiscale );
                update_user_meta( $user_id, 'billing_cf2', $codice_fiscale );
        wp_send_json_success();
            } else {
                wp_send_json_error( array( 'message' => 'Empty value not allowed' ) );
            }
        } else {
            // Field already has a value, don't overwrite
            wp_send_json_error( array( 'message' => 'Field already has a value, cannot update' ) );
        }
    } else {
        wp_send_json_error();
    }
}

/**
 * ============================================================================
 * SIMPLE CUSTOM MULTIPLE BILLING ADDRESSES SOLUTION
 * ============================================================================
 * 
 * Key principles:
 * 1. Store alternate addresses separately (never touch default WooCommerce billing)
 * 2. Populate checkout fields from alternate address when selected
 * 3. Save alternate address to custom storage after order
 * 4. Restore original billing data immediately after checkout
 * ============================================================================
 */

/**
 * Debug Mode Constant
 * Set to true to enable debug logging (PHP error_log and JavaScript console.log)
 * Set to false to disable all debug output
 */
if ( ! defined( 'TEATRO_DEBUG_MODE' ) ) {
    define( 'TEATRO_DEBUG_MODE', false ); // Temporarily enabled for debugging
}

/**
 * Debug logging wrapper function
 * Only logs if TEATRO_DEBUG_MODE is true
 * OPTIMIZED: Limits message length to prevent memory issues
 */
function teatro_debug_log( $message ) {
    if ( ! TEATRO_DEBUG_MODE ) {
        return;
    }
    
    // Limit message length to prevent memory exhaustion (max 10KB per log entry)
    if ( is_string( $message ) && strlen( $message ) > 10240 ) {
        $message = substr( $message, 0, 10240 ) . '... [truncated]';
    } elseif ( is_array( $message ) || is_object( $message ) ) {
        $serialized = print_r( $message, true );
        if ( strlen( $serialized ) > 10240 ) {
            $message = substr( $serialized, 0, 10240 ) . '... [truncated]';
        } else {
            $message = $serialized;
        }
    }
    
    error_log( $message );
}

/**
 * Add alternate billing address dropdown to checkout
 * Only runs on frontend checkout page
 */
add_action( 'woocommerce_before_checkout_billing_form', 'teatro_add_alternate_address_dropdown' );
function teatro_add_alternate_address_dropdown() {
    // Only run on frontend checkout page
    if ( is_admin() || ! is_checkout() || ! is_user_logged_in() ) {
        return;
    }
    
    $user_id = get_current_user_id();
    $alternate_address = get_user_meta( $user_id, 'teatro_alt_billing_address', true );
    $has_alternate = ! empty( $alternate_address ) && is_array( $alternate_address );
    $edit_account_url = esc_url( wc_get_page_permalink( 'myaccount' ) . 'edit-account/' );
    
    // DEBUG: Log what's loaded from database
    teatro_debug_log( 'Teatro DEBUG [CHECKOUT]: Loaded alternate address from DB: ' . print_r( $alternate_address, true ) );
    if ( $has_alternate && isset( $alternate_address['billing_state'] ) ) {
        teatro_debug_log( 'Teatro DEBUG [CHECKOUT]: billing_state value: ' . $alternate_address['billing_state'] );
    } else {
        teatro_debug_log( 'Teatro DEBUG [CHECKOUT]: billing_state NOT found in alternate address' );
    }
    
    ?>
    <div class="teatro-alternate-billing-address" style="margin-bottom: 20px; padding: 15px; background: #f9f9f9; border: 1px solid #ddd;">
        <!-- <h3><?php esc_html_e( 'Seleziona indirizzo di fatturazione', 'teatrosolare' ); ?></h3> -->
        <?php if ( ! $has_alternate ) : ?>
            <p class="infotext">
                <?php esc_html_e( 'Vuoi creare un nuovo indirizzo di fatturazione alternativo o modificarne uno esistente?', 'teatrosolare' ); ?> 
                <a href="<?php echo esc_url( add_query_arg( 'from_checkout', '1', $edit_account_url ) ); ?>" target="_blank">Clicca sul link</a><br><?php esc_html_e( 'e aggiorna i campi relativi all’INDIRIZZO DI FATTURAZIONE ALTERNATIVO. Ricorda di non modificare i dati personali inseriti in fase di registrazione.', 'teatrosolare' ); ?>
            </p>
        <?php else : ?>
            <p class="infotext">
                <?php esc_html_e( 'Vuoi creare un nuovo indirizzo di fatturazione alternativo o modificarne uno esistente?', 'teatrosolare' ); ?> 
                <a href="<?php echo esc_url( add_query_arg( 'from_checkout', '1', $edit_account_url ) ); ?>" target="_blank">Clicca sul link</a><br><?php esc_html_e( 'e aggiorna i campi relativi all’INDIRIZZO DI FATTURAZIONE ALTERNATIVO. Ricorda di non modificare i dati personali inseriti in fase di registrazione.', 'teatrosolare' ); ?>
            </p>
        <?php endif; ?>			
        <p class="form-row form-row-wide">
            <label for="teatro_alt_billing_select"><?php esc_html_e( 'Indirizzo di fatturazione', 'teatrosolare' ); ?></label>
            <select name="teatro_alt_billing_select" id="teatro_alt_billing_select" class="select">
                <option value="" selected><?php esc_html_e( 'Usa indirizzo predefinito', 'teatrosolare' ); ?></option>
                <?php if ( $has_alternate ) : ?>
                    <option value="alternate">
                        <?php 
                        $name = trim( ( $alternate_address['billing_first_name'] ?? '' ) . ' ' . ( $alternate_address['billing_last_name'] ?? '' ) );
                        $addr = $alternate_address['billing_address_1'] ?? '';
                        echo esc_html( $name . ( $name && $addr ? ' - ' : '' ) . $addr );
                        ?>
                    </option>
                <?php endif; ?>
            </select>
        </p>

    </div>
    
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        // Debug Mode (from PHP constant)
        var TEATRO_DEBUG_MODE = <?php echo TEATRO_DEBUG_MODE ? 'true' : 'false'; ?>;
        
        // Debug logging wrapper function
        function teatro_debug_log() {
            if ( TEATRO_DEBUG_MODE ) {
                console.log.apply( console, arguments );
            }
        }
        
        // DEBUG: Log alternate address data
        var alternateAddressData = <?php echo json_encode( $has_alternate ? $alternate_address : array() ); ?>;
        teatro_debug_log('Teatro DEBUG: Alternate address data loaded (for potential use):', alternateAddressData);
        if ( alternateAddressData && alternateAddressData.billing_state ) {
            teatro_debug_log('Teatro DEBUG: billing_state in alternate address data:', alternateAddressData.billing_state);
        } else {
            teatro_debug_log('Teatro DEBUG: billing_state NOT found in alternate address data or is empty');
        }
        
        // Flag to prevent recursive updates
        var isUpdatingFields = false;
        var isCheckoutUpdating = false;
        
        // Track if user has manually edited the state field (so we don't overwrite their edits)
        var stateFieldManuallyEdited = false;

        // Map of billing field IDs to data keys.
        var fieldMap = {
            'billing_first_name': 'billing_first_name',
            'billing_last_name':  'billing_last_name',
            'billing_company':    'billing_company',
            'billing_address_1':  'billing_address_1',
            'billing_address_2':  'billing_address_2',
            'billing_city':       'billing_city',
            'billing_state':      'billing_state',
            'billing_postcode':   'billing_postcode',
            'billing_country':    'billing_country',
            'billing_email':      'billing_email',
            'billing_phone':      'billing_phone',
            'billing_cf':         'billing_cf',
            'billing_cf2':        'billing_cf2'
        };

        // Snapshot of the original default billing address values when the
        // checkout page first loads. Used to restore fields when the user
        // switches back from the alternate address to the default one.
        var defaultAddressData = {};
        $.each(fieldMap, function(fieldId, key) {
            var $field = $('#' + fieldId);
            if ( $field.length ) {
                defaultAddressData[key] = $field.val();
            }
        });
        
        // Function to toggle billing_email readonly state
        function toggleBillingEmailReadonly() {
            var selected = $('#teatro_alt_billing_select').val();
            var $billingEmail = $('#billing_email');
            
            if ( ! selected || selected === '' ) {
                // Default address selected - make email readonly
                $billingEmail.prop('readonly', true).css('background-color', '#f5f5f5');
            } else if ( selected === 'alternate' ) {
                // Alternate address selected - make email editable
                $billingEmail.prop('readonly', false).css('background-color', '');
            }
        }
        
        // Generic function to populate billing fields from a given address object.
        function populateFormFieldsFromData(addressData, silent) {
            if ( isUpdatingFields ) {
                teatro_debug_log('Teatro DEBUG: Already updating fields, skipping to prevent loop');
                return;
            }
            
            if ( ! addressData || Object.keys(addressData).length === 0 ) {
                teatro_debug_log('Teatro DEBUG: No address data available for population');
                return;
            }
            
            isUpdatingFields = true;
            teatro_debug_log('Teatro DEBUG: Populating form fields from address data (silent: ' + (silent || false) + ')');
            
            var populatedFields = [];
            
            // Special handling: Set country first, then state (WooCommerce requirement)
            // WooCommerce's country field triggers AJAX to populate states, so we need to handle this carefully
            var countryValue = addressData['billing_country'];
            var stateValue   = addressData['billing_state'];
            
            teatro_debug_log('Teatro DEBUG: populateFormFieldsFromData - countryValue:', countryValue, 'stateValue:', stateValue);
            teatro_debug_log('Teatro DEBUG: Full addressData object:', addressData);
            
            if ( countryValue ) {
                var $countryField = $('#billing_country');
                if ( $countryField.length ) {
                    var oldCountry = $countryField.val();
                    if ( oldCountry !== countryValue ) {
                        teatro_debug_log('Teatro DEBUG: Setting country from "' + oldCountry + '" to "' + countryValue + '"');
                        $countryField.val(countryValue);
                        populatedFields.push('billing_country: "' + oldCountry + '" -> "' + countryValue + '"');
                        
                        // Trigger country change to populate states (WooCommerce requirement)
                        // This will trigger WooCommerce's AJAX to reload state options
                        $countryField.trigger('change');
                        
                        // Wait for WooCommerce to populate states, then set state (only if country has states)
                        if ( stateValue ) {
                            // Use a longer timeout and check if state field is ready and visible
                            var stateCheckAttempts = 0;
                            var maxAttempts = 10;
                            
                            var setStateAfterCountry = function() {
                                stateCheckAttempts++;
                                var $stateField = $('#billing_state');
                                var $stateWrapper = $stateField.closest('.form-row');
                                
                                teatro_debug_log('Teatro DEBUG: setStateAfterCountry attempt', stateCheckAttempts, '- stateValue:', stateValue, '- field exists:', $stateField.length, '- wrapper visible:', ($stateWrapper.length ? $stateWrapper.is(':visible') : false));
                                
                                // Check if state field exists and is visible (not hidden by WooCommerce)
                                // WooCommerce hides the state field for countries without states
                                if ( $stateField.length ) {
                                    if ( $stateWrapper.is(':visible') ) {
                                        if ( ! $stateWrapper.hasClass('woocommerce-invalid') ) {
                                            // For select dropdowns, check if options are loaded
                                            if ( $stateField.is('select') ) {
                                                var hasOptions = $stateField.find('option').length > 0;
                                                var optionCount = $stateField.find('option').length;
                                                teatro_debug_log('Teatro DEBUG: State field is select, hasOptions:', hasOptions, 'optionCount:', optionCount);
                                                if ( ! hasOptions && stateCheckAttempts < maxAttempts ) {
                                                    teatro_debug_log('Teatro DEBUG: Options not loaded yet, retrying...');
                                                    setTimeout(setStateAfterCountry, 200);
                                                    return;
                                                }
                                            } else {
                                                teatro_debug_log('Teatro DEBUG: State field is NOT a select, it is:', $stateField.prop('tagName'));
                                            }
                                            
                                            // Set the state value
                                            var oldState = $stateField.val();
                                            teatro_debug_log('Teatro DEBUG: Attempting to set state - oldState:', oldState, 'stateValue:', stateValue);
                                            if ( oldState !== stateValue ) {
                                                teatro_debug_log('Teatro DEBUG: Setting state from "' + oldState + '" to "' + stateValue + '"');
                                                $stateField.val(stateValue);
                                                
                                                // If Select2 is being used, trigger its update
                                                if ( $stateField.hasClass('select2-hidden-accessible') || $stateField.data('select2') ) {
                                                    teatro_debug_log('Teatro DEBUG: Field uses Select2, triggering Select2 change');
                                                    $stateField.trigger('change.select2');
                                                    // Also try to update Select2 directly if available
                                                    if ( $stateField.data('select2') ) {
                                                        $stateField.trigger('select2:select');
                                                    }
                                                }
                                                
                                                var newStateAfterSet = $stateField.val();
                                                teatro_debug_log('Teatro DEBUG: After setting, field value is now:', newStateAfterSet);
                                                populatedFields.push('billing_state: "' + oldState + '" -> "' + stateValue + '"');
                                                
                                                // Trigger state change (but not if silent mode)
                                                if ( ! silent ) {
                                                    $stateField.trigger('change');
                                                }
                                            } else {
                                                teatro_debug_log('Teatro DEBUG: State already set to correct value, but triggering Select2 update anyway');
                                                // Even if value is already set, trigger Select2 to update display
                                                if ( $stateField.hasClass('select2-hidden-accessible') || $stateField.data('select2') ) {
                                                    $stateField.trigger('change.select2');
                                                    if ( $stateField.data('select2') ) {
                                                        $stateField.trigger('select2:select');
                                                    }
                                                }
                                            }
                                        } else {
                                            teatro_debug_log('Teatro DEBUG: State wrapper has woocommerce-invalid class, skipping');
                                        }
                                    } else {
                                        teatro_debug_log('Teatro DEBUG: State wrapper is not visible');
                                    }
                                } else if ( stateCheckAttempts < maxAttempts ) {
                                    // State field not ready yet or country doesn't have states, try again
                                    teatro_debug_log('Teatro DEBUG: State field not found, retrying (attempt', stateCheckAttempts, 'of', maxAttempts, ')');
                                    setTimeout(setStateAfterCountry, 200);
                                } else {
                                    // Max attempts reached - country probably doesn't have states
                                    teatro_debug_log('Teatro DEBUG: State field not available for country "' + countryValue + '", skipping state');
                                }
                            };
                            
                            // Start checking after a short delay
                            setTimeout(setStateAfterCountry, 400);
                        }
                    } else if ( stateValue ) {
                        // Country already correct, check if state field is visible before setting
                        var $stateField = $('#billing_state');
                        var $stateWrapper = $stateField.closest('.form-row');
                        
                        // Only set state if field is visible (country has states)
                        if ( $stateField.length ) {
                            if ( $stateWrapper.is(':visible') ) {
                                if ( ! $stateWrapper.hasClass('woocommerce-invalid') ) {
                                    var oldState = $stateField.val();
                                    if ( oldState !== stateValue ) {
                                        teatro_debug_log('Teatro DEBUG: Setting state (country unchanged) from "' + oldState + '" to "' + stateValue + '"');
                                        $stateField.val(stateValue);
                                        
                                        // If Select2 is being used, trigger its update
                                        if ( $stateField.hasClass('select2-hidden-accessible') || $stateField.data('select2') ) {
                                            $stateField.trigger('change.select2');
                                            if ( $stateField.data('select2') ) {
                                                $stateField.trigger('select2:select');
                                            }
                                        }
                                        
                                        populatedFields.push('billing_state: "' + oldState + '" -> "' + stateValue + '"');
                                        
                                        if ( ! silent ) {
                                            $stateField.trigger('change');
                                        }
                                    }
                                }
                            }
                        } else {
                            teatro_debug_log('Teatro DEBUG: State field not available for country "' + countryValue + '", skipping state');
                        }
                    }
                }
            } else if ( stateValue ) {
                // No country change, check if state field is visible before setting
                var $stateField = $('#billing_state');
                var $stateWrapper = $stateField.closest('.form-row');
                
                // Only set state if field is visible (country has states)
                // WooCommerce hides the state field for countries without states
                if ( $stateField.length ) {
                    if ( $stateWrapper.is(':visible') ) {
                        if ( ! $stateWrapper.hasClass('woocommerce-invalid') ) {
                            var oldState = $stateField.val();
                            if ( oldState !== stateValue ) {
                                teatro_debug_log('Teatro DEBUG: Setting state (no country) from "' + oldState + '" to "' + stateValue + '"');
                                $stateField.val(stateValue);
                                
                                // If Select2 is being used, trigger its update
                                if ( $stateField.hasClass('select2-hidden-accessible') || $stateField.data('select2') ) {
                                    $stateField.trigger('change.select2');
                                    if ( $stateField.data('select2') ) {
                                        $stateField.trigger('select2:select');
                                    }
                                }
                                
                                populatedFields.push('billing_state: "' + oldState + '" -> "' + stateValue + '"');
                                
                                if ( ! silent ) {
                                    $stateField.trigger('change');
                                }
                            }
                        }
                    }
                } else {
                    teatro_debug_log('Teatro DEBUG: State field not available, skipping state');
                }
            }
            
            // Handle all other fields normally.
            // NOTE: When switching addresses, we always want the selected address
            // to fully control the field value. If the selected address has an
            // empty value for a field, we overwrite any previous value with the
            // empty string (instead of falling back to the old/default value).
            $.each(fieldMap, function(fieldId, key) {
                // Skip country and state - already handled above.
                if ( fieldId === 'billing_country' || fieldId === 'billing_state' ) {
                    return;
                }

                if ( addressData.hasOwnProperty( key ) ) {
                    var $field = $('#' + fieldId);
                    if ( $field.length ) {
                        var newValue = addressData[key] !== null && addressData[key] !== undefined ? addressData[key] : '';
                        var oldValue = $field.val();

                        if ( oldValue !== newValue ) {
                            $field.val( newValue );
                            populatedFields.push( fieldId + ': "' + oldValue + '" -> "' + newValue + '"');

                            // Only trigger change if not silent (to prevent loops).
                            if ( ! silent ) {
                                $field.trigger( 'change' );
                            }
                        }
                    }
                }
            });
            
            teatro_debug_log('Teatro DEBUG: Populated fields:', populatedFields);
            
            // Reset flag after a short delay
            setTimeout(function() {
                isUpdatingFields = false;
            }, 500);
        }

        // Convenience wrappers.
        function populateFormFieldsWithAlternate(silent) {
            populateFormFieldsFromData(alternateAddressData, silent);
        }

        function populateFormFieldsWithDefault(silent) {
            populateFormFieldsFromData(defaultAddressData, silent);
        }
        
        // Clear any stale session data on page load if default is selected
        var initialValue = $('#teatro_alt_billing_select').val();
        teatro_debug_log('Teatro DEBUG: Initial dropdown value:', initialValue);
        if ( ! initialValue || initialValue === '' ) {
            $('#teatro_alt_billing_hidden').val('');
        } else {
            $('#teatro_alt_billing_hidden').val(initialValue);
        }
        
        // Set initial readonly state
        toggleBillingEmailReadonly();
        
        // When alternate address is selected, populate checkout fields
        $('#teatro_alt_billing_select').on('change', function() {
            if ( isCheckoutUpdating ) {
                teatro_debug_log('Teatro DEBUG: Checkout is updating, ignoring dropdown change to prevent loop');
                return;
            }
            
            var selected = $(this).val();
            teatro_debug_log('Teatro DEBUG: Dropdown changed to:', selected);
            
            // Reset manual edit flag when switching addresses (so we can re-apply saved values)
            stateFieldManuallyEdited = false;
            teatro_debug_log('Teatro DEBUG: Reset stateFieldManuallyEdited flag (address switch)');
            
            // Store in hidden field for form submission
            $('#teatro_alt_billing_hidden').val(selected);
            
            // Toggle billing_email readonly state
            toggleBillingEmailReadonly();
            
            if ( selected === 'alternate' ) {
                // If alternate is selected, manually populate fields immediately (silent to prevent loops)
                teatro_debug_log('Teatro DEBUG: Alternate address selected - populating fields silently');
                populateFormFieldsWithAlternate(true); // Silent mode - no change events
            } else {
                // Default address selected - restore original default values captured on page load.
                teatro_debug_log('Teatro DEBUG: Default address selected - restoring original default billing values (silent)');
                populateFormFieldsWithDefault(true); // Silent mode - no change events
            }
            
            // Set flag and trigger checkout update
            isCheckoutUpdating = true;
            $('body').trigger('update_checkout');
            
            // Reset flag after update completes
            setTimeout(function() {
                isCheckoutUpdating = false;
            }, 1000);
        });
        
        // Track when user manually edits the state field
        $(document.body).on('change', '#billing_state', function() {
            // Only set flag if we're not currently updating fields programmatically
            if ( ! isUpdatingFields ) {
                stateFieldManuallyEdited = true;
                teatro_debug_log('Teatro DEBUG: User manually edited state field, setting stateFieldManuallyEdited = true');
            }
        });
        
        // Ensure our hidden field is included in checkout AJAX data
        $(document.body).on('checkout_place_order', function() {
            // Make sure hidden field value is set before form submission
            var selected = $('#teatro_alt_billing_select').val();
            $('#teatro_alt_billing_hidden').val(selected || '');
        });
        
        // Also toggle when checkout is updated (AJAX).
        // IMPORTANT: Do NOT re-populate fields here, otherwise any manual edits
        // the user makes (e.g. changing CAP for this specific order) would be
        // overwritten after each AJAX refresh.
        // EXCEPTION: We need to re-apply the state field if the alternate address
        // is selected, because WooCommerce's AJAX may reset it when the country changes.
        $(document.body).on('updated_checkout', function() {
            teatro_debug_log('Teatro DEBUG: Checkout updated via AJAX');
            toggleBillingEmailReadonly();

            // If alternate address is selected, re-apply the state field
            // (WooCommerce's AJAX may have reset it after country change)
            var selected = $('#teatro_alt_billing_select').val();
            teatro_debug_log('Teatro DEBUG: updated_checkout - selected:', selected, 'alternateAddressData:', alternateAddressData);
            
            if ( selected === 'alternate' && alternateAddressData && alternateAddressData['billing_state'] ) {
                var stateValue = alternateAddressData['billing_state'];
                var $stateField = $('#billing_state');
                var $stateWrapper = $stateField.closest('.form-row');
                
                teatro_debug_log('Teatro DEBUG: updated_checkout - stateValue:', stateValue, 'field exists:', $stateField.length, 'wrapper visible:', ($stateWrapper.length ? $stateWrapper.is(':visible') : false), 'stateFieldManuallyEdited:', stateFieldManuallyEdited);
                
                // Only re-apply state if user hasn't manually edited it
                if ( ! stateFieldManuallyEdited ) {
                    // Only set if field is visible and has options loaded
                    if ( $stateField.length && $stateWrapper.is(':visible') ) {
                        if ( $stateField.is('select') ) {
                        // Check if options are loaded
                        var hasOptions = $stateField.find('option').length > 0;
                        var optionCount = $stateField.find('option').length;
                        teatro_debug_log('Teatro DEBUG: updated_checkout - State field is select, hasOptions:', hasOptions, 'optionCount:', optionCount);
                        
                        if ( hasOptions ) {
                            var currentState = $stateField.val();
                            teatro_debug_log('Teatro DEBUG: updated_checkout - currentState:', currentState, 'stateValue:', stateValue);
                            if ( currentState !== stateValue ) {
                                teatro_debug_log('Teatro DEBUG: Re-applying state after AJAX update: "' + currentState + '" -> "' + stateValue + '"');
                                $stateField.val(stateValue);
                                
                                // If Select2 is being used, trigger its update
                                if ( $stateField.hasClass('select2-hidden-accessible') || $stateField.data('select2') ) {
                                    teatro_debug_log('Teatro DEBUG: Field uses Select2, triggering Select2 change');
                                    $stateField.trigger('change.select2');
                                    if ( $stateField.data('select2') ) {
                                        $stateField.trigger('select2:select');
                                    }
                                }
                                
                                var newStateAfterSet = $stateField.val();
                                teatro_debug_log('Teatro DEBUG: After re-applying, field value is now:', newStateAfterSet);
                            } else {
                                teatro_debug_log('Teatro DEBUG: State already correct, but triggering Select2 update to refresh display');
                                // Even if value is already set, trigger Select2 to update display
                                if ( $stateField.hasClass('select2-hidden-accessible') || $stateField.data('select2') ) {
                                    $stateField.trigger('change.select2');
                                    if ( $stateField.data('select2') ) {
                                        $stateField.trigger('select2:select');
                                    }
                                }
                            }
                        } else {
                            // Options not loaded yet, try again after a short delay
                            teatro_debug_log('Teatro DEBUG: Options not loaded yet, will retry in 300ms');
                            setTimeout(function() {
                                // Check flag again before retrying (user might have edited in the meantime)
                                if ( stateFieldManuallyEdited ) {
                                    teatro_debug_log('Teatro DEBUG: State field was manually edited, skipping delayed re-application');
                                    return;
                                }
                                
                                var $st = $('#billing_state');
                                if ( $st.length && $st.find('option').length > 0 ) {
                                    var curr = $st.val();
                                    if ( curr !== stateValue ) {
                                        teatro_debug_log('Teatro DEBUG: Re-applying state after delayed AJAX update: "' + curr + '" -> "' + stateValue + '"');
                                        $st.val(stateValue);
                                        
                                        // If Select2 is being used, trigger its update
                                        if ( $st.hasClass('select2-hidden-accessible') || $st.data('select2') ) {
                                            $st.trigger('change.select2');
                                            if ( $st.data('select2') ) {
                                                $st.trigger('select2:select');
                                            }
                                        }
                                        
                                        var newStateAfterDelayedSet = $st.val();
                                        teatro_debug_log('Teatro DEBUG: After delayed re-applying, field value is now:', newStateAfterDelayedSet);
                                    }
                                } else {
                                    teatro_debug_log('Teatro DEBUG: State field still not ready after delay');
                                }
                            }, 300);
                        }
                    } else {
                        // Text input field
                        var currentState = $stateField.val();
                        teatro_debug_log('Teatro DEBUG: State field is text input, currentState:', currentState, 'stateValue:', stateValue);
                        if ( currentState !== stateValue ) {
                            teatro_debug_log('Teatro DEBUG: Re-applying state (text field) after AJAX update: "' + currentState + '" -> "' + stateValue + '"');
                            $stateField.val(stateValue);
                        }
                    }
                    } else {
                        teatro_debug_log('Teatro DEBUG: State field not visible or not found, cannot re-apply');
                    }
                } else {
                    teatro_debug_log('Teatro DEBUG: State field was manually edited by user, skipping re-application to preserve user edits');
                }
            } else {
                teatro_debug_log('Teatro DEBUG: Not re-applying state - selected:', selected, 'has alternateAddressData:', !!alternateAddressData, 'has billing_state:', !!(alternateAddressData && alternateAddressData['billing_state']));
            }

            // Reset checkout updating flag.
            isCheckoutUpdating = false;
        });
    });
    </script>
    <input type="hidden" name="teatro_alt_billing_select" id="teatro_alt_billing_hidden" value="" />
    <?php
}

/**
 * Capture alternate address selection during AJAX checkout updates
 * This runs when WooCommerce processes the checkout update AJAX request
 * OPTIMIZED: Uses static flag to prevent multiple executions and excessive logging
 * Only runs on frontend checkout AJAX requests
 */
add_action( 'woocommerce_checkout_update_order_review', 'teatro_capture_alt_billing_selection_ajax', 1, 1 );
function teatro_capture_alt_billing_selection_ajax( $post_data ) {
    // Only run on frontend checkout AJAX requests
    if ( is_admin() || ! is_checkout() || ! is_user_logged_in() || ! WC()->session ) {
        return;
    }
    
    // Static flag to prevent multiple executions in the same request
    static $ajax_capture_done = false;
    if ( $ajax_capture_done ) {
        return;
    }
    
    // Parse the post data string (only once)
    parse_str( $post_data, $posted_data );
    
    if ( TEATRO_DEBUG_MODE ) {
        teatro_debug_log( 'Teatro DEBUG AJAX: POST data received: ' . print_r( $posted_data, true ) );
    }
    
    if ( isset( $posted_data['teatro_alt_billing_select'] ) ) {
        $selected = sanitize_text_field( $posted_data['teatro_alt_billing_select'] );
        if ( TEATRO_DEBUG_MODE ) {
            teatro_debug_log( 'Teatro DEBUG AJAX: Found teatro_alt_billing_select in parsed data: ' . $selected );
        }
        if ( $selected === 'alternate' ) {
            WC()->session->set( 'teatro_selected_alt_billing', $selected );
        } else {
            WC()->session->__unset( 'teatro_selected_alt_billing' );
        }
        $ajax_capture_done = true;
    } elseif ( isset( $_POST['teatro_alt_billing_select'] ) ) {
        // Also check direct POST (fallback)
        $selected = sanitize_text_field( $_POST['teatro_alt_billing_select'] );
        if ( TEATRO_DEBUG_MODE ) {
            teatro_debug_log( 'Teatro DEBUG AJAX: Found teatro_alt_billing_select in $_POST: ' . $selected );
        }
        if ( $selected === 'alternate' ) {
            WC()->session->set( 'teatro_selected_alt_billing', $selected );
        } else {
            WC()->session->__unset( 'teatro_selected_alt_billing' );
        }
        $ajax_capture_done = true;
    }
}

/**
 * Also capture during form submission
 * Only runs on frontend checkout
 */
add_filter( 'woocommerce_checkout_posted_data', 'teatro_capture_alt_billing_selection', 1, 1 );
function teatro_capture_alt_billing_selection( $data ) {
    // Only run on frontend checkout
    if ( is_admin() || ! is_checkout() || ! is_user_logged_in() || ! WC()->session ) {
        return $data;
    }
    
    if ( isset( $data['teatro_alt_billing_select'] ) ) {
        $selected = sanitize_text_field( $data['teatro_alt_billing_select'] );
        if ( $selected === 'alternate' ) {
            WC()->session->set( 'teatro_selected_alt_billing', $selected );
        } else {
            WC()->session->__unset( 'teatro_selected_alt_billing' );
        }
    }
    
    return $data;
}

/**
 * Clear stale session data when checkout page loads
 * Only runs on frontend checkout
 */
add_action( 'woocommerce_checkout_init', 'teatro_clear_stale_alt_billing_session', 1 );
function teatro_clear_stale_alt_billing_session() {
    // Only run on frontend checkout
    if ( is_admin() || ! is_checkout() || ! is_user_logged_in() || ! WC()->session ) {
        return;
    }
    
    // If no POST data and dropdown would show default, clear session
    if ( ! isset( $_POST['teatro_alt_billing_select'] ) ) {
        // Check if we have stale session data
        $stale_selection = WC()->session->get( 'teatro_selected_alt_billing' );
        if ( $stale_selection === 'alternate' ) {
            // Clear it - default should be used
            WC()->session->__unset( 'teatro_selected_alt_billing' );
        }
    }
}

/**
 * Populate checkout fields from alternate address when selected
 * CRITICAL: When default is selected, explicitly return default WooCommerce billing data
 * OPTIMIZED: Uses static caching to prevent memory leaks during AJAX updates
 * Only runs on frontend checkout
 */
add_filter( 'woocommerce_checkout_get_value', 'teatro_populate_checkout_from_alternate', 10, 2 );
function teatro_populate_checkout_from_alternate( $value, $input ) {
    // Early return for admin panel
    if ( is_admin() ) {
        return $value;
    }
    
    // Early return for non-checkout pages
    if ( ! is_checkout() ) {
        return $value;
    }
    
    // Early return for non-logged-in users
    if ( ! is_user_logged_in() ) {
        return $value;
    }
    
    // Only process billing fields
    if ( strpos( $input, 'billing_' ) !== 0 ) {
        return $value;
    }
    
    // Static cache to prevent repeated database queries during the same request
    static $cache = array();
    static $user_id_cache = null;
    static $alternate_address_cache = null;
    static $user_email_cache = null;
    static $selected_cache = null;
    
    // Cache user ID (only get once per request)
    if ( $user_id_cache === null ) {
        $user_id_cache = get_current_user_id();
    }
    $user_id = $user_id_cache;
    
    // Check if we have a cached value for this field
    $cache_key = $user_id . '_' . $input;
    if ( isset( $cache[ $cache_key ] ) ) {
        return $cache[ $cache_key ];
    }
    
    // Determine selected address (cache this to avoid repeated session/POST checks)
    if ( $selected_cache === null ) {
        $selected_cache = '';
        
        // Check POST data first (from form submission or AJAX) - this takes precedence
        if ( isset( $_POST['teatro_alt_billing_select'] ) ) {
            $selected_cache = sanitize_text_field( $_POST['teatro_alt_billing_select'] );
            // Store in session for subsequent AJAX updates
            if ( WC()->session ) {
                if ( $selected_cache === 'alternate' ) {
                    WC()->session->set( 'teatro_selected_alt_billing', $selected_cache );
                } else {
                    WC()->session->__unset( 'teatro_selected_alt_billing' );
                }
            }
        } else {
            // Check session (set by woocommerce_checkout_update_order_review during AJAX)
            $session_value = WC()->session ? WC()->session->get( 'teatro_selected_alt_billing' ) : '';
            if ( $session_value === 'alternate' ) {
                $selected_cache = 'alternate';
            }
        }
    }
    $selected = $selected_cache;
    
    // CRITICAL: Only use alternate address if explicitly selected
    if ( $selected !== 'alternate' ) {
        // Clear any stale session data (only once)
        if ( WC()->session && WC()->session->get( 'teatro_selected_alt_billing' ) ) {
            WC()->session->__unset( 'teatro_selected_alt_billing' );
        }
        
        // Get default value from user meta (cached per request)
        $default_value = get_user_meta( $user_id, $input, true );
        
        // For billing_email, always use user account email for default address
        if ( $input === 'billing_email' ) {
            // Cache user email (only get once per request)
            if ( $user_email_cache === null ) {
                $user = get_userdata( $user_id );
                $user_email_cache = $user ? $user->user_email : '';
            }
            if ( empty( $default_value ) && $user_email_cache ) {
                $default_value = $user_email_cache;
            }
        }
        
        // Cache the result
        $cache[ $cache_key ] = $default_value;
        return $default_value;
    }
    
    // Using alternate address - get from alternate address data (cache this)
    if ( $alternate_address_cache === null ) {
        $alternate_address_cache = get_user_meta( $user_id, 'teatro_alt_billing_address', true );
        if ( ! is_array( $alternate_address_cache ) ) {
            $alternate_address_cache = array();
        }
    }
    $alternate_address = $alternate_address_cache;
    
    if ( empty( $alternate_address ) ) {
        // Fallback to default if alternate doesn't exist at all
        $default_value = get_user_meta( $user_id, $input, true );
        if ( $input === 'billing_email' && empty( $default_value ) ) {
            if ( $user_email_cache === null ) {
                $user = get_userdata( $user_id );
                $user_email_cache = $user ? $user->user_email : '';
            }
            if ( $user_email_cache ) {
                $default_value = $user_email_cache;
            }
        }
        $cache[ $cache_key ] = $default_value;
        return $default_value;
    }

    // If the alternate address explicitly has this field (even if it's empty),
    // always use that value so that selecting the alternate address fully
    // overrides the default address for that field.
    if ( array_key_exists( $input, $alternate_address ) ) {
        $cache[ $cache_key ] = $alternate_address[ $input ];
        return $alternate_address[ $input ];
    }

    // Otherwise, fall back to the default billing data
    $default_value = get_user_meta( $user_id, $input, true );
    if ( $input === 'billing_email' && empty( $default_value ) ) {
        if ( $user_email_cache === null ) {
            $user = get_userdata( $user_id );
            $user_email_cache = $user ? $user->user_email : '';
        }
        if ( $user_email_cache ) {
            $default_value = $user_email_cache;
        }
    }
    
    $cache[ $cache_key ] = $default_value;
    return $default_value;
}

/**
 * Backup original billing data on checkout page load (before any updates)
 * CRITICAL: This must happen before any checkout processing or AJAX updates
 * OPTIMIZED: Only hook to template_redirect to prevent double execution
 * Only runs on frontend checkout
 */
add_action( 'template_redirect', 'teatro_backup_original_billing_on_load', 1 );
function teatro_backup_original_billing_on_load() {
    // Early return for admin panel
    if ( is_admin() ) {
        return;
    }
    
    // Early return if not checkout page
    if ( ! is_checkout() ) {
        return;
    }
    if ( ! is_user_logged_in() || ! WC()->session || ! is_checkout() ) {
        return;
    }
    
    $user_id = get_current_user_id();
    
    // Check if we already have a valid backup (don't overwrite if it exists and looks correct)
    // Use static flag to prevent multiple executions in the same request
    static $backup_done = false;
    if ( $backup_done ) {
        return;
    }
    
    $existing_backup = WC()->session->get( 'teatro_original_billing_backup' );
    if ( $existing_backup && is_array( $existing_backup ) ) {
        // Verify backup doesn't match alternate address (which would indicate it's corrupted)
        $alternate_address = get_user_meta( $user_id, 'teatro_alt_billing_address', true );
        if ( is_array( $alternate_address ) && isset( $alternate_address['billing_cf2'] ) && isset( $existing_backup['billing_cf2'] ) ) {
            if ( $existing_backup['billing_cf2'] === $alternate_address['billing_cf2'] ) {
                // Backup appears corrupted, refresh it
                teatro_debug_log( 'Teatro DEBUG: Existing backup appears corrupted (matches alternate), refreshing...' );
            } else {
                // Backup looks valid, keep it
                $backup_done = true;
                return;
            }
        } else {
            // No alternate address to compare, backup looks fine
            $backup_done = true;
            return;
        }
    }
    
    // Backup original billing data from user meta (before any checkout updates)
    $backup = array(
        'billing_first_name', 'billing_last_name', 'billing_company',
        'billing_address_1', 'billing_address_2', 'billing_city',
        'billing_state', 'billing_postcode', 'billing_country',
        'billing_email', 'billing_phone', 'billing_cf', 'billing_cf2',
    );
    
    $original = array();
    foreach ( $backup as $field ) {
        $original[ $field ] = get_user_meta( $user_id, $field, true );
    }
    
    // Verify backup doesn't match alternate address (safety check)
    $alternate_address = get_user_meta( $user_id, 'teatro_alt_billing_address', true );
    if ( is_array( $alternate_address ) && isset( $alternate_address['billing_cf2'] ) && isset( $original['billing_cf2'] ) ) {
        if ( $original['billing_cf2'] === $alternate_address['billing_cf2'] ) {
            teatro_debug_log( 'Teatro DEBUG: WARNING - Backup billing_cf2 matches alternate address. User meta may have been updated before backup. Original value may be lost.' );
        }
    }
    
    // Store backup in session
    WC()->session->set( 'teatro_original_billing_backup', $original );
    teatro_debug_log( 'Teatro DEBUG: Backed up original billing_cf2 on checkout init: ' . ( $original['billing_cf2'] ? $original['billing_cf2'] : '[empty]' ) );
    
    // Mark backup as done
    $backup_done = true;
}

/**
 * Also backup when alternate address is selected (via AJAX)
 * This ensures we have the original values even if checkout init backup was missed
 * OPTIMIZED: Uses static flag to prevent multiple executions
 * Only runs on frontend checkout AJAX requests
 */
add_action( 'woocommerce_checkout_update_order_review', 'teatro_backup_original_billing_on_alt_selection', 1, 1 );
function teatro_backup_original_billing_on_alt_selection( $post_data ) {
    // Only run on frontend checkout AJAX requests
    if ( is_admin() || ! is_checkout() || ! is_user_logged_in() || ! WC()->session ) {
        return;
    }
    
    // Static flag to prevent multiple executions
    static $ajax_backup_done = false;
    if ( $ajax_backup_done ) {
        return;
    }
    
    // Parse the post data string
    parse_str( $post_data, $posted_data );
    
    // Only backup if alternate address is being selected
    if ( ! isset( $posted_data['teatro_alt_billing_select'] ) || $posted_data['teatro_alt_billing_select'] !== 'alternate' ) {
        return;
    }
    
    // Only backup if not already backed up (to preserve original values)
    if ( WC()->session->get( 'teatro_original_billing_backup' ) ) {
        $ajax_backup_done = true;
        return;
    }
    
    $user_id = get_current_user_id();
    
    // Backup original billing data from user meta (before any updates)
    $backup = array(
        'billing_first_name', 'billing_last_name', 'billing_company',
        'billing_address_1', 'billing_address_2', 'billing_city',
        'billing_state', 'billing_postcode', 'billing_country',
        'billing_email', 'billing_phone', 'billing_cf', 'billing_cf2',
    );
    
    $original = array();
    foreach ( $backup as $field ) {
        $original[ $field ] = get_user_meta( $user_id, $field, true );
    }
    
    // Store backup in session
    WC()->session->set( 'teatro_original_billing_backup', $original );
    teatro_debug_log( 'Teatro DEBUG: Backed up original billing_cf2 on alt selection: ' . ( $original['billing_cf2'] ? $original['billing_cf2'] : '[empty]' ) );
    
    // Mark backup as done
    $ajax_backup_done = true;
}

/**
 * Set flag when alternate address is selected during checkout process
 * Only runs on frontend checkout
 */
add_action( 'woocommerce_checkout_process', 'teatro_set_alternate_billing_flag', 1 );
function teatro_set_alternate_billing_flag() {
    // Only run on frontend checkout
    if ( is_admin() || ! is_checkout() || ! is_user_logged_in() || ! WC()->session ) {
        return;
    }
    
    $selected = isset( $_POST['teatro_alt_billing_select'] ) ? sanitize_text_field( $_POST['teatro_alt_billing_select'] ) : '';
    
    // Only set flag if using alternate address
    if ( $selected === 'alternate' ) {
        WC()->session->set( 'teatro_using_alternate_billing', true );
        teatro_debug_log( 'Teatro DEBUG: Set alternate billing flag, original billing_cf2 backup: ' . ( WC()->session->get( 'teatro_original_billing_backup' )['billing_cf2'] ?? '[not set]' ) );
    }
}

/**
 * Internal function to restore default billing address
 */
function teatro_restore_default_billing_address( $user_id ) {
    if ( ! WC()->session || ! WC()->session->get( 'teatro_using_alternate_billing' ) ) {
        return;
    }
    
    $original = WC()->session->get( 'teatro_original_billing_backup' );
    if ( ! is_array( $original ) ) {
        return;
    }
    
    // Temporarily remove the filter to allow restore
    remove_filter( 'update_user_metadata', 'teatro_prevent_billing_update_for_alternate', 10 );
    
    // CRITICAL: Verify backup contains original values, not alternate address values
    // If backup seems wrong (contains alternate address values), try to get original from order meta or skip restore
    $alternate_address = get_user_meta( $user_id, 'teatro_alt_billing_address', true );
    if ( is_array( $alternate_address ) && isset( $alternate_address['billing_cf2'] ) ) {
        // If backup billing_cf2 matches alternate address billing_cf2, the backup is wrong
        // In this case, we should NOT restore (to avoid overwriting with wrong value)
        if ( isset( $original['billing_cf2'] ) && $original['billing_cf2'] === $alternate_address['billing_cf2'] ) {
            teatro_debug_log( 'Teatro DEBUG: WARNING - Backup billing_cf2 matches alternate address billing_cf2. Backup appears corrupted. Skipping restore for billing_cf2.' );
            // Remove billing_cf2 from restore to prevent overwriting with wrong value
            unset( $original['billing_cf2'] );
        }
    }
    
    // Log what we're about to restore
    teatro_debug_log( 'Teatro DEBUG: About to restore billing_cf2 from backup: ' . ( isset( $original['billing_cf2'] ) ? ( $original['billing_cf2'] ? $original['billing_cf2'] : '[empty]' ) : '[not in backup]' ) );
    teatro_debug_log( 'Teatro DEBUG: Current billing_cf2 in user meta before restore: ' . ( get_user_meta( $user_id, 'billing_cf2', true ) ? get_user_meta( $user_id, 'billing_cf2', true ) : '[empty]' ) );
    
    foreach ( $original as $field => $value ) {
        update_user_meta( $user_id, $field, $value );
        // Debug logging for billing_cf2
        if ( $field === 'billing_cf2' ) {
            teatro_debug_log( 'Teatro DEBUG: Restored billing_cf2 to: ' . ( $value ? $value : '[empty]' ) );
            teatro_debug_log( 'Teatro DEBUG: billing_cf2 in user meta after restore: ' . ( get_user_meta( $user_id, 'billing_cf2', true ) ? get_user_meta( $user_id, 'billing_cf2', true ) : '[empty]' ) );
        }
    }
    
    // Re-add the filter
    add_filter( 'update_user_metadata', 'teatro_prevent_billing_update_for_alternate', 10, 5 );
    
    // Update customer object to sync restored data
    $customer = new WC_Customer( $user_id );
    $customer->save();
    
    // Clean up session
    WC()->session->__unset( 'teatro_original_billing_backup' );
    WC()->session->__unset( 'teatro_using_alternate_billing' );
    WC()->session->__unset( 'teatro_selected_alt_billing' );
}

/**
 * Save new alternate address and restore original billing data after order
 * CRITICAL: This must run AFTER WooCommerce has finished all customer updates
 * Runs on both frontend and admin (for order processing)
 */
add_action( 'woocommerce_checkout_order_processed', 'teatro_handle_alternate_address_after_order', 999, 3 );
function teatro_handle_alternate_address_after_order( $order_id, $posted_data, $order ) {
    // Only process for logged-in users (runs on both frontend and admin)
    if ( ! is_user_logged_in() ) {
        return;
    }
    
    $user_id = get_current_user_id();
    $selected = isset( $_POST['teatro_alt_billing_select'] ) ? sanitize_text_field( $_POST['teatro_alt_billing_select'] ) : '';
    
    // Only process if using alternate address
    if ( $selected !== 'alternate' ) {
        return;
    }
    
    // Restore original billing data
    teatro_restore_default_billing_address( $user_id );
}

/**
 * Additional restore hook after payment completes (for payment gateways)
 * Runs on both frontend and admin (for payment processing)
 */
add_action( 'woocommerce_payment_complete', 'teatro_restore_default_address_after_payment', 999, 1 );
function teatro_restore_default_address_after_payment( $order_id ) {
    // Only process for logged-in users (runs on both frontend and admin)
    if ( ! is_user_logged_in() ) {
        return;
    }
    
    $order = wc_get_order( $order_id );
    if ( ! $order ) {
        return;
    }
    
    $user_id = $order->get_user_id();
    if ( ! $user_id ) {
        return;
    }
    
    // Check if alternate address was used (from order meta or session)
    $selected = '';
    if ( WC()->session && WC()->session->get( 'teatro_using_alternate_billing' ) ) {
        $selected = 'alternate';
    }
    
    if ( $selected === 'alternate' ) {
        teatro_restore_default_billing_address( $user_id );
    }
}

/**
 * After successful payment, sync the final billing details from the order
 * back into the stored alternate billing address (when it was used).
 *
 * This lets the user:
 * - Start from the saved alternate address.
 * - Tweak fields on the checkout form for a specific order.
 * - Have those successful edits become the new saved alternate address.
 * Runs on both frontend and admin (for payment processing)
 */
add_action( 'woocommerce_payment_complete', 'teatro_update_alt_billing_from_order', 900, 1 );
function teatro_update_alt_billing_from_order( $order_id ) {
    if ( ! $order_id ) {
        return;
    }

    $order = wc_get_order( $order_id );
    if ( ! $order ) {
        return;
    }

    $user_id = $order->get_user_id();
    if ( ! $user_id ) {
        return; // Guests cannot have a stored alternate address.
    }

    // Only proceed if this order actually used the alternate billing address.
    $used_alt = $order->get_meta( 'teatro_alt_billing_used' );
    if ( 'yes' !== $used_alt ) {
        return;
    }

    // Build new alternate address from the FINAL billing data on the order.
    $alternate_address = array(
        'billing_first_name' => $order->get_billing_first_name(),
        'billing_last_name'  => $order->get_billing_last_name(),
        'billing_company'    => $order->get_billing_company(),
        'billing_address_1'  => $order->get_billing_address_1(),
        'billing_address_2'  => $order->get_billing_address_2(),
        'billing_city'       => $order->get_billing_city(),
        'billing_state'      => $order->get_billing_state(),
        'billing_postcode'   => $order->get_billing_postcode(),
        'billing_country'    => $order->get_billing_country(),
        'billing_email'      => $order->get_billing_email(),
        'billing_phone'      => $order->get_billing_phone(),
        // Custom fields stored on the order.
        'billing_cf'         => $order->get_meta( 'billing_cf' ),
        'billing_cf2'        => $order->get_meta( 'billing_cf2' ),
    );

    // If all fields are empty for some reason, don't wipe the stored alt address.
    $has_data = false;
    foreach ( $alternate_address as $value ) {
        if ( ! empty( $value ) ) {
            $has_data = true;
            break;
        }
    }

    if ( ! $has_data ) {
        return;
    }

    update_user_meta( $user_id, 'teatro_alt_billing_address', $alternate_address );
}

/**
 * Additional restore hook on thankyou page (catches any late updates)
 * Only runs on frontend thank you page
 */
add_action( 'woocommerce_thankyou', 'teatro_restore_default_address_on_thankyou', 999, 1 );
function teatro_restore_default_address_on_thankyou( $order_id ) {
    // Only run on frontend thank you page
    if ( is_admin() || ! is_user_logged_in() ) {
        return;
    }
    
    $order = wc_get_order( $order_id );
    if ( ! $order ) {
        return;
    }
    
    $user_id = $order->get_user_id();
    if ( ! $user_id || $user_id !== get_current_user_id() ) {
        return;
    }
    
    // Check if alternate address was used (from session)
    if ( WC()->session && WC()->session->get( 'teatro_using_alternate_billing' ) ) {
        teatro_restore_default_billing_address( $user_id );
    }
}

/**
 * Ensure order has correct billing data from alternate address
 * Also save custom billing fields (billing_cf2 and billing_cf) to order meta
 * Runs on both frontend and admin (for order creation)
 */
add_action( 'woocommerce_checkout_create_order', 'teatro_ensure_order_has_alternate_billing', 10, 2 );
function teatro_ensure_order_has_alternate_billing( $order, $data ) {
    // IMPORTANT:
    // - We no longer override the order's billing fields from the saved
    //   alternate address. WooCommerce will use the final checkout form
    //   values (including any manual edits) for the order.
    // - Here we only mark the order when the alternate address was used so
    //   we can later sync the FINAL billing data back into the stored
    //   alternate address once the order is successfully paid.
    // - Process for both logged-in users and guests (runs on both frontend and admin)
    
    // Mark order if alternate address was used (only for logged-in users)
    if ( is_user_logged_in() ) {
        $selected = isset( $_POST['teatro_alt_billing_select'] ) ? sanitize_text_field( wp_unslash( $_POST['teatro_alt_billing_select'] ) ) : '';

        if ( $selected === 'alternate' ) {
            $order->update_meta_data( 'teatro_alt_billing_used', 'yes' );
        }
    }
    
    // Save custom billing fields to order meta (for ALL orders, including guests)
    // Save even if empty to ensure we capture the value (empty string is valid)
    if ( isset( $_POST['billing_cf2'] ) ) {
        $cf2_value = sanitize_text_field( wp_unslash( $_POST['billing_cf2'] ) );
        $order->update_meta_data( 'billing_cf2', $cf2_value );
        // Debug logging
        if ( TEATRO_DEBUG_MODE ) {
            teatro_debug_log( 'Teatro DEBUG [ORDER CREATE]: Saving billing_cf2 to order meta: ' . $cf2_value );
        }
    }
    
    if ( isset( $_POST['billing_cf'] ) ) {
        $cf_value = sanitize_text_field( wp_unslash( $_POST['billing_cf'] ) );
        $order->update_meta_data( 'billing_cf', $cf_value );
        // Debug logging
        if ( TEATRO_DEBUG_MODE ) {
            teatro_debug_log( 'Teatro DEBUG [ORDER CREATE]: Saving billing_cf to order meta: ' . $cf_value );
        }
    }
}

/**
 * Fallback: Also save custom billing fields after order is created
 * This ensures fields are saved even if the create_order hook fails
 * Runs on both frontend and admin (for order processing)
 */
add_action( 'woocommerce_checkout_order_processed', 'teatro_save_custom_billing_fields_fallback', 5, 3 );
function teatro_save_custom_billing_fields_fallback( $order_id, $posted_data, $order ) {
    // Only run if fields weren't already saved
    $existing_cf2 = get_post_meta( $order_id, 'billing_cf2', true );
    $existing_cf = get_post_meta( $order_id, 'billing_cf', true );
    
    // Save billing_cf2 if not already saved
    if ( empty( $existing_cf2 ) && isset( $_POST['billing_cf2'] ) && ! empty( $_POST['billing_cf2'] ) ) {
        update_post_meta( $order_id, 'billing_cf2', sanitize_text_field( wp_unslash( $_POST['billing_cf2'] ) ) );
        if ( TEATRO_DEBUG_MODE ) {
            teatro_debug_log( 'Teatro DEBUG [ORDER PROCESSED]: Fallback saved billing_cf2: ' . sanitize_text_field( wp_unslash( $_POST['billing_cf2'] ) ) );
        }
    }
    
    // Save billing_cf if not already saved
    if ( empty( $existing_cf ) && isset( $_POST['billing_cf'] ) && ! empty( $_POST['billing_cf'] ) ) {
        update_post_meta( $order_id, 'billing_cf', sanitize_text_field( wp_unslash( $_POST['billing_cf'] ) ) );
        if ( TEATRO_DEBUG_MODE ) {
            teatro_debug_log( 'Teatro DEBUG [ORDER PROCESSED]: Fallback saved billing_cf: ' . sanitize_text_field( wp_unslash( $_POST['billing_cf'] ) ) );
        }
    }
}

/**
 * Ensure customer object has alternate billing data for payment gateways
 * Only runs on frontend checkout
 */
add_action( 'woocommerce_before_checkout_process', 'teatro_update_customer_for_alternate', 5 );
function teatro_update_customer_for_alternate() {
    // Only run on frontend checkout
    if ( is_admin() || ! is_checkout() || ! is_user_logged_in() ) {
        return;
    }
    
    $selected = isset( $_POST['teatro_alt_billing_select'] ) ? sanitize_text_field( $_POST['teatro_alt_billing_select'] ) : '';
    if ( $selected !== 'alternate' ) {
        return;
    }
    
    $user_id  = get_current_user_id();
    $customer = new WC_Customer( $user_id );
    
    // Use the values the customer actually submitted on the checkout form
    // (which may be based on the saved alternate address, but can include
    // per-order edits) so that payment gateways see the correct data.
    $billing_fields = array(
        'billing_first_name', 'billing_last_name', 'billing_company',
        'billing_address_1', 'billing_address_2', 'billing_city',
        'billing_state', 'billing_postcode', 'billing_country',
        'billing_email', 'billing_phone',
    );
    
    foreach ( $billing_fields as $field ) {
        if ( isset( $_POST[ $field ] ) && $_POST[ $field ] !== '' ) {
            $setter = 'set_' . $field;
            if ( method_exists( $customer, $setter ) ) {
                $customer->$setter( wc_clean( wp_unslash( $_POST[ $field ] ) ) );
            }
        }
    }
    
    // Handle custom fields
    if ( isset( $_POST['billing_cf'] ) ) {
        $customer->update_meta_data( 'billing_cf', wc_clean( wp_unslash( $_POST['billing_cf'] ) ) );
    }
    if ( isset( $_POST['billing_cf2'] ) ) {
        $customer->update_meta_data( 'billing_cf2', wc_clean( wp_unslash( $_POST['billing_cf2'] ) ) );
    }
    
    $customer->save();
    WC()->customer = $customer;
}

/**
 * Enqueue WooCommerce address-i18n script on edit account page for dynamic country/state updates
 * Only runs on frontend account page
 */
add_action( 'wp_enqueue_scripts', 'teatro_enqueue_address_i18n_on_edit_account' );
function teatro_enqueue_address_i18n_on_edit_account() {
    // Only on frontend edit account page
    if ( is_admin() || ! is_account_page() || ! is_wc_endpoint_url( 'edit-account' ) ) {
        return;
    }
    
    // Enqueue WooCommerce's address-i18n script (handles dynamic state updates)
    if ( function_exists( 'WC' ) && class_exists( 'WC_Countries' ) ) {
        $suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
        wp_enqueue_script(
            'wc-address-i18n',
            WC()->plugin_url() . '/assets/js/frontend/address-i18n' . $suffix . '.js',
            array( 'jquery', 'woocommerce' ),
            WC()->version,
            true
        );
        
        // Localize script with country/state data
        $countries_obj = new WC_Countries();
        wp_localize_script(
            'wc-address-i18n',
            'wc_address_i18n_params',
            array(
                'locale'             => wp_json_encode( $countries_obj->get_country_locale() ),
                'locale_fields'      => wp_json_encode( $countries_obj->get_country_locale_field_selectors() ),
                'i18n_required_text' => esc_attr__( 'required', 'woocommerce' ),
                'i18n_optional_text' => esc_attr__( 'optional', 'woocommerce' ),
            )
        );
    }
}

/**
 * Store "from_checkout" flag in WooCommerce session when user arrives from checkout
 * Only runs on frontend account page
 */
add_action( 'template_redirect', 'teatro_store_from_checkout_flag' );
function teatro_store_from_checkout_flag() {
    // Only on frontend edit account page
    if ( is_admin() || ! is_account_page() || ! is_wc_endpoint_url( 'edit-account' ) ) {
        return;
    }
    
    // Check if user came from checkout
    if ( isset( $_GET['from_checkout'] ) && $_GET['from_checkout'] === '1' ) {
        if ( function_exists( 'WC' ) && WC()->session ) {
            WC()->session->set( 'teatro_from_checkout', '1' );
        }
    }
}

/**
 * Set flag after successful account save to show return to checkout message
 * This hook runs after validation passes, so if it runs, the save was successful
 * Only runs on frontend account page
 */
add_action( 'woocommerce_save_account_details', 'teatro_set_account_saved_flag', 999, 1 );
function teatro_set_account_saved_flag( $user_id ) {
    // Only run on frontend account page
    if ( is_admin() || ! is_account_page() || ! function_exists( 'WC' ) || ! WC()->session ) {
        return;
    }
    
    $from_checkout = WC()->session->get( 'teatro_from_checkout' );
    if ( $from_checkout === '1' ) {
        // Set flag that account was just saved successfully
        // This hook only runs if validation passed, so it's safe to set the flag
        WC()->session->set( 'teatro_account_just_saved', '1' );
    }
}

/**
 * Show "return to checkout" message after successful save
 * Only runs on frontend account page
 */
add_action( 'woocommerce_before_edit_account_form', 'teatro_show_return_to_checkout_message', 5 );
function teatro_show_return_to_checkout_message() {
    // Only run on frontend account page
    if ( is_admin() || ! is_account_page() || ! function_exists( 'WC' ) || ! WC()->session ) {
        return;
    }
    
    $from_checkout = WC()->session->get( 'teatro_from_checkout' );
    $just_saved = WC()->session->get( 'teatro_account_just_saved' );
    
    // Only show if user came from checkout AND just saved
    if ( $from_checkout !== '1' || $just_saved !== '1' ) {
        return;
    }
    
    // The woocommerce_save_account_details hook only runs on successful save,
    // so if the flag is set, the save was successful
    // Show the message with button to return to checkout
    $checkout_url = wc_get_checkout_url();
    ?>
    <div class="woocommerce-message woocommerce-success">
        <p style="margin: 0 0 10px 0; display:inline;">
            <?php esc_html_e( 'Tutto pronto!', 'teatrosolare' ); ?>
        </p>
        <p style="margin: 0; display:inline">
            <a href="<?php echo esc_url( $checkout_url ); ?>" class="button" style="display: inline-block;">
                <?php esc_html_e( 'Torna al checkout', 'teatrosolare' ); ?>
            </a>
        </p>
    </div>
    <?php
    
    // Clear both flags so it doesn't show again
    WC()->session->__unset( 'teatro_from_checkout' );
    WC()->session->__unset( 'teatro_account_just_saved' );
}

/**
 * Add default WooCommerce billing fields to My Account > Edit Account page
 * Only runs on frontend account page
 */
add_action( 'woocommerce_edit_account_form', 'teatro_add_billing_fields_to_edit_account' );
function teatro_add_billing_fields_to_edit_account() {
    // Only run on frontend account page
    if ( is_admin() || ! is_account_page() ) {
        return;
    }
    
    $user_id = get_current_user_id();

    // Use submitted values after validation errors, otherwise fallback to saved meta.
    $submitted = isset( $_POST['save_account_details'] );

    $billing_keys = array(
        'billing_first_name',
        'billing_last_name',
        'billing_company',
        'billing_address_1',
        'billing_address_2',
        'billing_city',
        'billing_postcode',
        'billing_state',
        'billing_country',
        'billing_phone',
        'billing_cf2',
        'billing_cf',
    );

    $billing_values = array();
    foreach ( $billing_keys as $key ) {
        if ( isset( $_POST[ $key ] ) ) {
            $billing_values[ $key ] = wc_clean( wp_unslash( $_POST[ $key ] ) );
        } else {
            $billing_values[ $key ] = get_user_meta( $user_id, $key, true );
        }
    }

    // Required fields for inline error messages.
    $required_fields = array(
        'billing_first_name' => __( 'Nome', 'teatrosolare' ),
        'billing_last_name'  => __( 'Cognome', 'teatrosolare' ),
        'billing_address_1'  => __( 'Indirizzo', 'teatrosolare' ),
        'billing_city'       => __( 'Città', 'teatrosolare' ),
        'billing_postcode'   => __( 'CAP', 'teatrosolare' ),
        'billing_state'      => __( 'Provincia', 'teatrosolare' ),
        'billing_country'    => __( 'Paese', 'teatrosolare' ),
        'billing_phone'      => __( 'Telefono', 'teatrosolare' ),
    );

    $inline_errors = array();
    if ( $submitted ) {
        foreach ( $required_fields as $field => $label ) {
            $value = isset( $billing_values[ $field ] ) ? trim( (string) $billing_values[ $field ] ) : '';
            if ( '' === $value ) {
                $inline_errors[ $field ] = sprintf(
                    __( '%s è un campo obbligatorio.', 'teatrosolare' ),
                    '<strong>' . $label . '</strong>'
                );
            }
        }
    }
    ?>
    <fieldset class="billing_default">
        <legend><?php esc_html_e( 'Indirizzo di Fatturazione Predefinito', 'teatrosolare' ); ?></legend>
				<p><strong>Non modificare questi campi, a meno che non sia necessario correggere un errore.</strong><br>
				Se desideri aggiungere un altro indirizzo di fatturazione alternativo, <strong>scorri verso il basso e modifica i campi in "Indirizzo di Fatturazione Alternativo"</strong>.</p>
        <p class="woocommerce-form-row woocommerce-form-row--first form-row form-row-first">
            <label for="billing_first_name"><?php esc_html_e( 'Nome', 'teatrosolare' ); ?>&nbsp;<span class="required">*</span></label>
            <input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="billing_first_name" id="billing_first_name" value="<?php echo esc_attr( $billing_values['billing_first_name'] ); ?>" />
            <?php if ( isset( $inline_errors['billing_first_name'] ) ) : ?>
                <span class="woocommerce-error" style="margin-top:4px;display:block;"><?php echo wp_kses_post( $inline_errors['billing_first_name'] ); ?></span>
            <?php endif; ?>
        </p>
        <p class="woocommerce-form-row woocommerce-form-row--last form-row form-row-last">
            <label for="billing_last_name"><?php esc_html_e( 'Cognome', 'teatrosolare' ); ?>&nbsp;<span class="required">*</span></label>
            <input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="billing_last_name" id="billing_last_name" value="<?php echo esc_attr( $billing_values['billing_last_name'] ); ?>" />
            <?php if ( isset( $inline_errors['billing_last_name'] ) ) : ?>
                <span class="woocommerce-error" style="margin-top:4px;display:block;"><?php echo wp_kses_post( $inline_errors['billing_last_name'] ); ?></span>
            <?php endif; ?>
        </p>
        <div class="clear"></div>
        
        <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
            <label for="billing_company"><?php esc_html_e( 'Azienda', 'teatrosolare' ); ?></label>
            <input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="billing_company" id="billing_company" value="<?php echo esc_attr( $billing_values['billing_company'] ); ?>" />
        </p>
        
        <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
            <label for="billing_address_1"><?php esc_html_e( 'Indirizzo', 'teatrosolare' ); ?>&nbsp;<span class="required">*</span></label>
            <input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="billing_address_1" id="billing_address_1" value="<?php echo esc_attr( $billing_values['billing_address_1'] ); ?>" />
            <?php if ( isset( $inline_errors['billing_address_1'] ) ) : ?>
                <span class="woocommerce-error" style="margin-top:4px;display:block;"><?php echo wp_kses_post( $inline_errors['billing_address_1'] ); ?></span>
            <?php endif; ?>
        </p>
        
        <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
            <label for="billing_address_2"><?php esc_html_e( 'Indirizzo 2', 'teatrosolare' ); ?></label>
            <input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="billing_address_2" id="billing_address_2" value="<?php echo esc_attr( $billing_values['billing_address_2'] ); ?>" />
        </p>
        
        <p class="woocommerce-form-row woocommerce-form-row--first form-row form-row-first">
            <label for="billing_city"><?php esc_html_e( 'Città', 'teatrosolare' ); ?>&nbsp;<span class="required">*</span></label>
            <input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="billing_city" id="billing_city" value="<?php echo esc_attr( $billing_values['billing_city'] ); ?>" />
            <?php if ( isset( $inline_errors['billing_city'] ) ) : ?>
                <span class="woocommerce-error" style="margin-top:4px;display:block;"><?php echo wp_kses_post( $inline_errors['billing_city'] ); ?></span>
            <?php endif; ?>
        </p>
        
        <p class="woocommerce-form-row woocommerce-form-row--last form-row form-row-last">
            <label for="billing_postcode"><?php esc_html_e( 'CAP', 'teatrosolare' ); ?>&nbsp;<span class="required">*</span></label>
            <input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="billing_postcode" id="billing_postcode" value="<?php echo esc_attr( $billing_values['billing_postcode'] ); ?>" />
            <?php if ( isset( $inline_errors['billing_postcode'] ) ) : ?>
                <span class="woocommerce-error" style="margin-top:4px;display:block;"><?php echo wp_kses_post( $inline_errors['billing_postcode'] ); ?></span>
            <?php endif; ?>
        </p>
        <div class="clear"></div>
        
        <?php
        // Use WooCommerce dynamic country/state fields so behaviour matches checkout
        $countries_obj  = new WC_Countries();
        $address_fields = $countries_obj->get_address_fields( '', 'billing_' );

        // Get saved country and state values
        $saved_country = isset( $billing_values['billing_country'] ) ? $billing_values['billing_country'] : '';
        $saved_state   = isset( $billing_values['billing_state'] ) ? $billing_values['billing_state'] : '';

        // Billing country (Paese) - FIRST
        if ( isset( $address_fields['billing_country'] ) ) {
            $country_args             = $address_fields['billing_country'];
            $country_args['label']    = __( 'Paese', 'teatrosolare' );
            $country_args['required'] = true;

            // Force layout: first half-width column
            if ( ! empty( $country_args['class'] ) && is_array( $country_args['class'] ) ) {
                $country_args['class'] = array_diff( $country_args['class'], array( 'form-row-wide' ) );
            }
            $country_args['class'][] = 'form-row-first';
            $country_args['clear']   = false;

            woocommerce_form_field(
                'billing_country',
                $country_args,
                $billing_values['billing_country']
            );
            if ( isset( $inline_errors['billing_country'] ) ) {
                echo '<span class="woocommerce-error" style="margin-top:4px;display:block;">' . wp_kses_post( $inline_errors['billing_country'] ) . '</span>';
            }
        }

        // Billing state (Provincia) - SECOND
        if ( isset( $address_fields['billing_state'] ) ) {
            $state_args             = $address_fields['billing_state'];
            $state_args['label']    = __( 'Provincia', 'teatrosolare' );
            $state_args['required'] = true;

            // Force layout: last half-width column
            if ( ! empty( $state_args['class'] ) && is_array( $state_args['class'] ) ) {
                $state_args['class'] = array_diff( $state_args['class'], array( 'form-row-wide' ) );
            }
            $state_args['class'][] = 'form-row-last';
            $state_args['clear']   = true;
            
            // CRITICAL: Set the 'country' parameter so woocommerce_form_field() knows which country's states to show
            // Without this, it will use the shop's base country (probably Italy) or checkout session value
            if ( $saved_country ) {
                $state_args['country'] = $saved_country;
            }

            woocommerce_form_field(
                'billing_state',
                $state_args,
                $saved_state
            );
            if ( isset( $inline_errors['billing_state'] ) ) {
                echo '<span class="woocommerce-error" style="margin-top:4px;display:block;">' . wp_kses_post( $inline_errors['billing_state'] ) . '</span>';
            }
        }
        ?>
        <div class="clear"></div>
        
        <?php
        // Add JavaScript to ensure saved state value is set on page load
        // The state field should already have the correct states from PHP, but we need to set the value
        // Also ensure the state field wrapper maintains form-row-first class after dynamic updates
        $saved_state = isset( $billing_values['billing_state'] ) ? $billing_values['billing_state'] : '';
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            var $stateField = $('#billing_state');
            var $stateWrapper = $stateField.closest('.form-row');
            var savedState = '<?php echo esc_js( $saved_state ); ?>';
            
            // Function to ensure state field wrapper has correct classes
            function ensureStateFieldLayout() {
                if ( $stateWrapper.length ) {
                    // Remove form-row-wide if present
                    $stateWrapper.removeClass('form-row-wide');
                    // Ensure form-row-last is present (state is now second, after country)
                    if ( ! $stateWrapper.hasClass('form-row-last') ) {
                        $stateWrapper.addClass('form-row-last');
                    }
                    // Remove form-row-first if present (state is no longer first)
                    $stateWrapper.removeClass('form-row-first');
                }
            }
            
            // Ensure correct layout on page load
            ensureStateFieldLayout();
            
            // Ensure correct layout after WooCommerce updates the state field (when country changes)
            // Listen for when the state field is updated by WooCommerce's address-i18n script
            if ( $stateField.length ) {
                // Watch for changes to the state field wrapper's classes
                var observer = new MutationObserver(function(mutations) {
                    ensureStateFieldLayout();
                });
                
                if ( $stateWrapper.length ) {
                    observer.observe($stateWrapper[0], {
                        attributes: true,
                        attributeFilter: ['class']
                    });
                }
                
                // Also ensure layout when country changes (WooCommerce updates state field via AJAX)
                $('#billing_country').on('change', function() {
                    // Wait for WooCommerce to update the state field, then fix layout
                    setTimeout(function() {
                        ensureStateFieldLayout();
                    }, 300);
                });
            }
            
            // Set saved state value if it exists
            if ( $stateField.length && savedState ) {
                // Wait a moment for the field to be fully rendered
                setTimeout(function() {
                    // Check if the state option exists
                    if ( $stateField.find('option[value="' + savedState + '"]').length > 0 ) {
                        $stateField.val(savedState);
                        // Trigger change to update any Select2 or other UI
                        $stateField.trigger('change');
                    }
                }, 100);
            }
        });
        </script>
        
        <p class="woocommerce-form-row woocommerce-form-row--first form-row form-row-first">
            <label for="billing_phone"><?php esc_html_e( 'Telefono', 'teatrosolare' ); ?>&nbsp;<span class="required">*</span></label>
            <input type="tel" class="woocommerce-Input woocommerce-Input--text input-text" name="billing_phone" id="billing_phone" value="<?php echo esc_attr( $billing_values['billing_phone'] ); ?>" />
            <?php if ( isset( $inline_errors['billing_phone'] ) ) : ?>
                <span class="woocommerce-error" style="margin-top:4px;display:block;"><?php echo wp_kses_post( $inline_errors['billing_phone'] ); ?></span>
            <?php endif; ?>
        </p>
        
        <p class="woocommerce-form-row woocommerce-form-row--last form-row form-row-last">
            <label for="billing_cf2"><?php esc_html_e( 'Codice Fiscale', 'teatrosolare' ); ?></label>
            <input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="billing_cf2" id="billing_cf2" value="<?php echo esc_attr( $billing_values['billing_cf2'] ); ?>" />
        </p>
        <div class="clear"></div>
        
        <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
            <label for="billing_cf"><?php esc_html_e( 'Partita IVA', 'teatrosolare' ); ?></label>
            <input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="billing_cf" id="billing_cf" value="<?php echo esc_attr( $billing_values['billing_cf'] ); ?>" />
        </p>
    </fieldset>
    <?php
}

/**
 * Save default WooCommerce billing fields from My Account > Edit Account page
 * Also sync with Gravity Forms custom user meta fields
 * Only runs on frontend account page
 */
add_action( 'woocommerce_save_account_details', 'teatro_save_billing_fields_from_edit_account', 5, 1 );
function teatro_save_billing_fields_from_edit_account( $user_id ) {
    // Only run on frontend account page
    if ( is_admin() || ! is_account_page() ) {
        return;
    }
    
    // Define billing fields to save
    $billing_fields = array(
        'billing_first_name',
        'billing_last_name',
        'billing_company',
        'billing_address_1',
        'billing_address_2',
        'billing_city',
        'billing_postcode',
        'billing_state',
        'billing_country',
        'billing_phone',
        'billing_cf',
        'billing_cf2',
    );
    
    // Save each billing field - ALWAYS allow updates from My Account page
    foreach ( $billing_fields as $field ) {
        if ( isset( $_POST[ $field ] ) ) {
            update_user_meta( $user_id, $field, sanitize_text_field( $_POST[ $field ] ) );
        }
    }
    
    // Also update WordPress first_name and last_name
    if ( isset( $_POST['billing_first_name'] ) ) {
        update_user_meta( $user_id, 'first_name', sanitize_text_field( $_POST['billing_first_name'] ) );
    }
    if ( isset( $_POST['billing_last_name'] ) ) {
        update_user_meta( $user_id, 'last_name', sanitize_text_field( $_POST['billing_last_name'] ) );
    }
    
    // Sync with Gravity Forms custom user meta fields
    // codice_fiscale <-> billing_cf2
    if ( isset( $_POST['billing_cf2'] ) ) {
        update_user_meta( $user_id, 'codice_fiscale', sanitize_text_field( $_POST['billing_cf2'] ) );
    }
    
    // indirizzo_di_residenza <-> billing_address_1
    if ( isset( $_POST['billing_address_1'] ) ) {
        update_user_meta( $user_id, 'indirizzo_di_residenza', sanitize_text_field( $_POST['billing_address_1'] ) );
    }
    
    // cap <-> billing_postcode
    if ( isset( $_POST['billing_postcode'] ) ) {
        update_user_meta( $user_id, 'cap', sanitize_text_field( $_POST['billing_postcode'] ) );
    }
    
    // comune_di_residenza <-> billing_city
    if ( isset( $_POST['billing_city'] ) ) {
        update_user_meta( $user_id, 'comune_di_residenza', sanitize_text_field( $_POST['billing_city'] ) );
    }
}

/**
 * Validate billing fields in edit account form
 * Only runs on frontend account page
 */
add_action( 'woocommerce_save_account_details_errors', 'teatro_validate_billing_fields_edit_account', 10, 1 );
function teatro_validate_billing_fields_edit_account( $args ) {
    // Only run on frontend account page
    if ( is_admin() || ! is_account_page() ) {
        return;
    }
    // Required fields
    $required_fields = array(
        'billing_first_name' => __( 'Nome', 'teatrosolare' ),
        'billing_last_name'  => __( 'Cognome', 'teatrosolare' ),
        'billing_address_1'  => __( 'Indirizzo', 'teatrosolare' ),
        'billing_city'       => __( 'Città', 'teatrosolare' ),
        'billing_postcode'   => __( 'CAP', 'teatrosolare' ),
        'billing_state'      => __( 'Provincia', 'teatrosolare' ),
        'billing_country'    => __( 'Paese', 'teatrosolare' ),
        'billing_phone'      => __( 'Telefono', 'teatrosolare' ),
    );
    
    foreach ( $required_fields as $field => $label ) {
        if ( empty( $_POST[ $field ] ) ) {
            $args->add( $field, sprintf( __( '%s è un campo obbligatorio.', 'teatrosolare' ), '<strong>' . $label . '</strong>' ) );
        }
    }
}

/**
 * Validate alternate billing fields in edit account form
 * The alternate address is optional, but if the user fills it, core fields must be present.
 * Only runs on frontend account page
 */
add_action( 'woocommerce_save_account_details_errors', 'teatro_validate_alt_billing_fields_edit_account', 11, 1 );
function teatro_validate_alt_billing_fields_edit_account( $errors ) {
    // Only run on frontend account page
    if ( is_admin() || ! is_account_page() ) {
        return;
    }
    // If user requested deletion of the alt address, skip validation.
    if ( isset( $_POST['teatro_delete_alt_address'] ) && $_POST['teatro_delete_alt_address'] === '1' ) {
        return;
    }

    // Detect if user is actually trying to save an alternate address (any alt_billing_* field filled).
    $has_alt_data = false;
    foreach ( $_POST as $key => $value ) {
        if ( strpos( $key, 'alt_billing_' ) === 0 && trim( (string) $value ) !== '' ) {
            $has_alt_data = true;
            break;
        }
    }

    if ( ! $has_alt_data ) {
        return;
    }

    // Required fields for alternate address.
    $required_fields = array(
        'alt_billing_first_name' => __( 'Nome', 'teatrosolare' ),
        'alt_billing_last_name'  => __( 'Cognome', 'teatrosolare' ),
        'alt_billing_address_1'  => __( 'Indirizzo', 'teatrosolare' ),
        'alt_billing_city'       => __( 'Città', 'teatrosolare' ),
        'alt_billing_postcode'   => __( 'CAP', 'teatrosolare' ),
        'alt_billing_state'      => __( 'Provincia', 'teatrosolare' ),
        'alt_billing_country'    => __( 'Paese', 'teatrosolare' ),
        'alt_billing_email'      => __( 'Email', 'teatrosolare' ),
    );

    // Make state/province required only if the selected country actually has states (like Woo checkout).
    $alt_country = isset( $_POST['alt_billing_country'] ) ? wc_clean( wp_unslash( $_POST['alt_billing_country'] ) ) : '';
    if ( function_exists( 'WC' ) && $alt_country ) {
        $wc_countries = WC()->countries;
        if ( $wc_countries ) {
            $states_for_country = $wc_countries->get_states( $alt_country );
            if ( empty( $states_for_country ) || ! is_array( $states_for_country ) ) {
                unset( $required_fields['alt_billing_state'] );
            }
        }
    }

    foreach ( $required_fields as $field => $label ) {
        $value = isset( $_POST[ $field ] ) ? trim( (string) $_POST[ $field ] ) : '';
        if ( $value === '' ) {
            $errors->add(
                $field,
                sprintf(
                    __( '%s è un campo obbligatorio per l\'indirizzo di fatturazione alternativo.', 'teatrosolare' ),
                    '<strong>' . $label . '</strong>'
                )
            );
        }
    }
}

/**
 * Add alternate billing address section to My Account > Edit Account page
 * Only runs on frontend account page
 */
add_action( 'woocommerce_edit_account_form_end', 'teatro_add_alternate_address_to_edit_account' );
function teatro_add_alternate_address_to_edit_account() {
    // Only run on frontend account page
    if ( is_admin() || ! is_account_page() || ! is_user_logged_in() ) {
        return;
    }
    
    $user_id = get_current_user_id();
    $alternate_address = get_user_meta( $user_id, 'teatro_alt_billing_address', true );
    $has_alternate = ! empty( $alternate_address ) && is_array( $alternate_address );

    // DEBUG: Log loaded alternate address
    teatro_debug_log( 'Teatro DEBUG [EDIT ACCOUNT]: Loaded alternate address from DB: ' . print_r( $alternate_address, true ) );
    if ( isset( $alternate_address['billing_state'] ) ) {
        teatro_debug_log( 'Teatro DEBUG [EDIT ACCOUNT]: Saved billing_state value: ' . $alternate_address['billing_state'] );
    } else {
        teatro_debug_log( 'Teatro DEBUG [EDIT ACCOUNT]: No billing_state in saved alternate address' );
    }

    // Preserve submitted alternate address values after validation errors.
    $alt_keys = array(
        'billing_first_name',
        'billing_last_name',
        'billing_company',
        'billing_address_1',
        'billing_address_2',
        'billing_city',
        'billing_postcode',
        'billing_state',
        'billing_country',
        'billing_phone',
        'billing_email',
        'billing_cf2',
        'billing_cf',
    );

    $alt_values = array();
    foreach ( $alt_keys as $key ) {
        $post_key = 'alt_' . $key;
        if ( isset( $_POST[ $post_key ] ) ) {
            $alt_values[ $key ] = wc_clean( wp_unslash( $_POST[ $post_key ] ) );
            teatro_debug_log( 'Teatro DEBUG [EDIT ACCOUNT]: Using POST value for ' . $key . ': ' . $alt_values[ $key ] );
        } else {
            $alt_values[ $key ] = isset( $alternate_address[ $key ] ) ? $alternate_address[ $key ] : '';
            teatro_debug_log( 'Teatro DEBUG [EDIT ACCOUNT]: Using saved value for ' . $key . ': ' . $alt_values[ $key ] );
        }
    }
    
    // DEBUG: Log final alt_values array
    teatro_debug_log( 'Teatro DEBUG [EDIT ACCOUNT]: Final alt_values array: ' . print_r( $alt_values, true ) );
    
    ?>
    <fieldset style="margin-top: 40px; padding-top: 30px; border-top: 2px solid #ddd;">
        <legend><?php esc_html_e( 'Indirizzo di Fatturazione Alternativo', 'teatrosolare' ); ?></legend>
        <p style="margin-bottom: 20px; color: #666;">
            <?php esc_html_e( 'Puoi salvare un indirizzo di fatturazione alternativo da utilizzare durante il checkout. Questo indirizzo non sostituirà mai il tuo indirizzo predefinito.', 'teatrosolare' ); ?>
        </p>
        
        <?php if ( $has_alternate ) : ?>
            <p class="form-row form-row-wide">
                <label>
                    <input type="checkbox" name="teatro_delete_alt_address" value="1" />
                    <?php esc_html_e( 'Elimina indirizzo alternativo', 'teatrosolare' ); ?>
                </label>
            </p>
        <?php endif; ?>
        
        <p class="form-row form-row-first">
            <label for="alt_billing_first_name"><?php esc_html_e( 'Nome', 'teatrosolare' ); ?>&nbsp;<span class="required">*</span></label>
            <input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="alt_billing_first_name" id="alt_billing_first_name" value="<?php echo esc_attr( $alt_values['billing_first_name'] ); ?>" />
        </p>
        <p class="form-row form-row-last">
            <label for="alt_billing_last_name"><?php esc_html_e( 'Cognome', 'teatrosolare' ); ?>&nbsp;<span class="required">*</span></label>
            <input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="alt_billing_last_name" id="alt_billing_last_name" value="<?php echo esc_attr( $alt_values['billing_last_name'] ); ?>" />
        </p>
        <div class="clear"></div>
        
        <p class="form-row form-row-wide">
            <label for="alt_billing_company"><?php esc_html_e( 'Azienda', 'teatrosolare' ); ?></label>
            <input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="alt_billing_company" id="alt_billing_company" value="<?php echo esc_attr( $alt_values['billing_company'] ); ?>" />
        </p>
        
        <p class="form-row form-row-wide">
            <label for="alt_billing_address_1"><?php esc_html_e( 'Indirizzo', 'teatrosolare' ); ?>&nbsp;<span class="required">*</span></label>
            <input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="alt_billing_address_1" id="alt_billing_address_1" value="<?php echo esc_attr( $alt_values['billing_address_1'] ); ?>" />
        </p>
        
        <p class="form-row form-row-wide">
            <label for="alt_billing_address_2"><?php esc_html_e( 'Indirizzo 2', 'teatrosolare' ); ?></label>
            <input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="alt_billing_address_2" id="alt_billing_address_2" value="<?php echo esc_attr( $alt_values['billing_address_2'] ); ?>" />
        </p>
        
        <p class="form-row form-row-first">
            <label for="alt_billing_city"><?php esc_html_e( 'Città', 'teatrosolare' ); ?>&nbsp;<span class="required">*</span></label>
            <input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="alt_billing_city" id="alt_billing_city" value="<?php echo esc_attr( $alt_values['billing_city'] ); ?>" />
        </p>
        
        <p class="form-row form-row-last">
            <label for="alt_billing_postcode"><?php esc_html_e( 'CAP', 'teatrosolare' ); ?>&nbsp;<span class="required">*</span></label>
            <input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="alt_billing_postcode" id="alt_billing_postcode" value="<?php echo esc_attr( $alt_values['billing_postcode'] ); ?>" />
        </p>
        <div class="clear"></div>
        
        <?php
        // Use WooCommerce dynamic country/state fields for alternate address (same as default billing)
        $countries_obj_alt  = new WC_Countries();
        $address_fields_alt = $countries_obj_alt->get_address_fields( '', 'billing_' );

        // Determine if the currently selected alt country has states (for required validation)
        $alt_country_code      = isset( $alt_values['billing_country'] ) ? $alt_values['billing_country'] : '';
        $alt_country_has_state = false;
        if ( $alt_country_code && $countries_obj_alt ) {
            $states_for_alt_country = $countries_obj_alt->get_states( $alt_country_code );
            if ( ! empty( $states_for_alt_country ) && is_array( $states_for_alt_country ) ) {
                $alt_country_has_state = true;
            }
        }

        // Get inline errors for alt address fields
        $alt_inline_errors = array();
        if ( isset( $_POST['save_account_details'] ) ) {
            $alt_required_fields = array(
                'alt_billing_first_name' => __( 'Nome', 'teatrosolare' ),
                'alt_billing_last_name'  => __( 'Cognome', 'teatrosolare' ),
                'alt_billing_address_1'  => __( 'Indirizzo', 'teatrosolare' ),
                'alt_billing_city'       => __( 'Città', 'teatrosolare' ),
                'alt_billing_postcode'   => __( 'CAP', 'teatrosolare' ),
                'alt_billing_country'    => __( 'Paese', 'teatrosolare' ),
                'alt_billing_email'      => __( 'Email', 'teatrosolare' ),
            );
            
            // State is only required if country has states
            if ( $alt_country_has_state ) {
                $alt_required_fields['alt_billing_state'] = __( 'Provincia', 'teatrosolare' );
            }
            
            foreach ( $alt_required_fields as $field => $label ) {
                $value = isset( $_POST[ $field ] ) ? trim( (string) $_POST[ $field ] ) : '';
                if ( '' === $value ) {
                    $alt_inline_errors[ $field ] = sprintf(
                        __( '%s è un campo obbligatorio per l\'indirizzo di fatturazione alternativo.', 'teatrosolare' ),
                        '<strong>' . $label . '</strong>'
                    );
                }
            }
        }

        // Alternate billing country (Paese) - FIRST - uses WooCommerce standard form field
        if ( isset( $address_fields_alt['billing_country'] ) ) {
            $alt_country_args             = $address_fields_alt['billing_country'];
            $alt_country_args['label']    = __( 'Paese', 'teatrosolare' );
            $alt_country_args['required'] = true;
            $alt_country_args['id']       = 'alt_billing_country';
            $alt_country_args['name']    = 'alt_billing_country';

            // Force layout: first half-width column
            if ( ! empty( $alt_country_args['class'] ) && is_array( $alt_country_args['class'] ) ) {
                $alt_country_args['class'] = array_diff( $alt_country_args['class'], array( 'form-row-wide' ) );
            }
            $alt_country_args['class'][] = 'form-row-first';
            $alt_country_args['class'][] = 'alt-billing-country';
            $alt_country_args['clear']   = false;

            woocommerce_form_field(
                'alt_billing_country',
                $alt_country_args,
                $alt_values['billing_country']
            );
            if ( isset( $alt_inline_errors['alt_billing_country'] ) ) {
                echo '<span class="woocommerce-error" style="margin-top:4px;display:block;">' . wp_kses_post( $alt_inline_errors['alt_billing_country'] ) . '</span>';
            }
        }

        // Alternate billing state (Provincia) - SECOND - uses WooCommerce standard form field
        if ( isset( $address_fields_alt['billing_state'] ) ) {
            $alt_state_args             = $address_fields_alt['billing_state'];
            $alt_state_args['label']     = __( 'Provincia', 'teatrosolare' );
            $alt_state_args['required']  = $alt_country_has_state;
            $alt_state_args['id']        = 'alt_billing_state';
            $alt_state_args['name']    = 'alt_billing_state';
            // Link to country field for dynamic updates
            $alt_state_args['country_field'] = 'alt_billing_country';

            // Force layout: last half-width column
            if ( ! empty( $alt_state_args['class'] ) && is_array( $alt_state_args['class'] ) ) {
                $alt_state_args['class'] = array_diff( $alt_state_args['class'], array( 'form-row-wide' ) );
            }
            $alt_state_args['class'][] = 'form-row-last';
            $alt_state_args['class'][] = 'alt-billing-state';
            $alt_state_args['clear']   = true;

            // DEBUG: Log state field rendering
            teatro_debug_log( 'Teatro DEBUG [EDIT ACCOUNT]: Rendering alt_billing_state field with value: ' . $alt_values['billing_state'] );
            teatro_debug_log( 'Teatro DEBUG [EDIT ACCOUNT]: Current country: ' . $alt_values['billing_country'] . ', Has states: ' . ( $alt_country_has_state ? 'yes' : 'no' ) );
            
            woocommerce_form_field(
                'alt_billing_state',
                $alt_state_args,
                $alt_values['billing_state']
            );
            if ( isset( $alt_inline_errors['alt_billing_state'] ) ) {
                echo '<span class="woocommerce-error" style="margin-top:4px;display:block;">' . wp_kses_post( $alt_inline_errors['alt_billing_state'] ) . '</span>';
            }
            
            // DEBUG: Print debug info on page
            if ( TEATRO_DEBUG_MODE ) {
                echo '<div style="background: #fff3cd; padding: 10px; margin: 10px 0; border: 1px solid #ffc107; font-size: 12px;">';
                echo '<strong>DEBUG [State Field]:</strong><br>';
                echo 'Saved state value: ' . esc_html( $alt_values['billing_state'] ) . '<br>';
                echo 'Current country: ' . esc_html( $alt_values['billing_country'] ) . '<br>';
                echo 'Country has states: ' . ( $alt_country_has_state ? 'Yes' : 'No' ) . '<br>';
                echo 'Field ID: alt_billing_state<br>';
                echo 'Field name: alt_billing_state';
                echo '</div>';
            }
        }
        ?>
        <div class="clear"></div>
        <script type="text/javascript">
        jQuery(function($) {
            // Debug Mode (from PHP constant)
            var TEATRO_DEBUG_MODE = <?php echo TEATRO_DEBUG_MODE ? 'true' : 'false'; ?>;
            
            // Debug logging wrapper function
            function teatro_debug_log() {
                if ( TEATRO_DEBUG_MODE ) {
                    console.log.apply( console, arguments );
                }
            }
            
            // Get states data from WooCommerce (same format as checkout uses)
            var altStatesData = <?php echo wp_json_encode( $countries_obj_alt->get_states() ); ?>;
            
            // Get the saved state value from PHP (so we can preserve it when rebuilding options)
            var savedStateValue = <?php echo wp_json_encode( $alt_values['billing_state'] ); ?>;
            var savedCountryValue = <?php echo wp_json_encode( $alt_values['billing_country'] ); ?>;
            
            teatro_debug_log( 'Teatro DEBUG [JS]: Initialized alt address state field handler' );
            teatro_debug_log( 'Teatro DEBUG [JS]: savedStateValue =', savedStateValue );
            teatro_debug_log( 'Teatro DEBUG [JS]: savedCountryValue =', savedCountryValue );
            
            // Function to update alt billing state field when country changes
            function updateAltBillingState(preserveValue) {
                var $countryField = $('#alt_billing_country');
                var $stateField = $('#alt_billing_state');
                var $stateWrapper = $('#alt_billing_state_field');
                
                teatro_debug_log( 'Teatro DEBUG [JS]: updateAltBillingState called, preserveValue =', preserveValue );
                
                if ( ! $countryField.length || ! $stateField.length ) {
                    teatro_debug_log( 'Teatro DEBUG [JS]: State or country field not found!' );
                    return;
                }
                
                var selectedCountry = $countryField.val();
                var states = {};
                
                teatro_debug_log( 'Teatro DEBUG [JS]: Selected country =', selectedCountry );
                
                // Get states for selected country
                if ( selectedCountry && altStatesData && altStatesData[selectedCountry] ) {
                    states = altStatesData[selectedCountry];
                    teatro_debug_log( 'Teatro DEBUG [JS]: Found', Object.keys(states).length, 'states for country', selectedCountry );
                } else {
                    teatro_debug_log( 'Teatro DEBUG [JS]: No states found for country', selectedCountry );
                }
                
                // If country has states, show field and populate options
                if ( Object.keys(states).length > 0 ) {
                    $stateWrapper.show();
                    
                    // Determine which value to preserve:
                    // - If preserveValue is true (initial load), use savedStateValue if country matches
                    // - Otherwise, use current field value
                    var valueToPreserve = '';
                    if ( preserveValue && savedStateValue && selectedCountry === savedCountryValue ) {
                        valueToPreserve = savedStateValue;
                        teatro_debug_log( 'Teatro DEBUG [JS]: Preserving saved state value:', valueToPreserve, '(country matches)' );
                    } else {
                        valueToPreserve = $stateField.val();
                        teatro_debug_log( 'Teatro DEBUG [JS]: Using current field value:', valueToPreserve, '(preserveValue =', preserveValue, ', country match =', (selectedCountry === savedCountryValue), ')' );
                    }
                    
                    // Clear and rebuild options
                    $stateField.empty();
                    $stateField.append(
                        $('<option></option>')
                            .attr('value', '')
                            .text('<?php echo esc_js( __( "Seleziona un'opzione…", 'woocommerce' ) ); ?>')
                    );
                    
                    // Add state options
                    $.each(states, function(code, name) {
                        var $option = $('<option></option>')
                            .attr('value', code)
                            .text(name);
                        
                        // Select if this matches the value we want to preserve
                        if ( code === valueToPreserve ) {
                            $option.prop('selected', true);
                            teatro_debug_log( 'Teatro DEBUG [JS]: Marking option as selected:', code, name );
                        }
                        
                        $stateField.append($option);
                    });
                    
                    // Explicitly set the value after options are added (in case selected prop didn't work)
                    if ( valueToPreserve ) {
                        $stateField.val(valueToPreserve);
                        teatro_debug_log( 'Teatro DEBUG [JS]: Explicitly set field value to:', valueToPreserve, '(current field value after set:', $stateField.val(), ')' );
                    } else {
                        teatro_debug_log( 'Teatro DEBUG [JS]: No value to preserve, leaving field empty' );
                    }
                    
                    // Make required
                    $stateField.prop('required', true);
                    
                    // Update label to show required asterisk
                    var $label = $('label[for="alt_billing_state"]');
                    if ( $label.length && $label.find('span.required').length === 0 ) {
                        $label.append('&nbsp;<span class="required">*</span>');
                    }
                } else {
                    // No states for this country - hide field and clear value
                    teatro_debug_log( 'Teatro DEBUG [JS]: No states for country, hiding field' );
                    $stateWrapper.hide();
                    $stateField.val('');
                    $stateField.prop('required', false);
                    
                    // Remove required asterisk from label
                    var $label = $('label[for="alt_billing_state"]');
                    if ( $label.length ) {
                        $label.find('span.required').remove();
                    }
                }
            }
            
            // Function to ensure alt state field wrapper has correct classes
            function ensureAltStateFieldLayout() {
                var $altStateWrapper = $('#alt_billing_state_field');
                if ( $altStateWrapper.length ) {
                    // Remove form-row-wide if present
                    $altStateWrapper.removeClass('form-row-wide');
                    // Ensure form-row-last is present (state is now second, after country)
                    if ( ! $altStateWrapper.hasClass('form-row-last') ) {
                        $altStateWrapper.addClass('form-row-last');
                    }
                    // Remove form-row-first if present (state is no longer first)
                    $altStateWrapper.removeClass('form-row-first');
                }
            }
            
            // Ensure correct layout on page load
            ensureAltStateFieldLayout();
            
            // Ensure correct layout after WooCommerce updates the state field (when country changes)
            // Watch for changes to the state field wrapper's classes
            var $altStateWrapper = $('#alt_billing_state_field');
            if ( $altStateWrapper.length ) {
                var altObserver = new MutationObserver(function(mutations) {
                    ensureAltStateFieldLayout();
                });
                
                altObserver.observe($altStateWrapper[0], {
                    attributes: true,
                    attributeFilter: ['class']
                });
            }
            
            // Update state field when country changes (don't preserve saved value on manual change)
            // Also ensure layout is maintained after WooCommerce updates the state field
            $('#alt_billing_country').on('change', function() {
                teatro_debug_log( 'Teatro DEBUG [JS]: Country field changed' );
                updateAltBillingState(false);
                // Wait for WooCommerce to update the state field, then fix layout
                setTimeout(function() {
                    ensureAltStateFieldLayout();
                }, 300);
            });
            
            // Monitor form submission to log state value
            $('form.woocommerce-EditAccountForm').on('submit', function() {
                var stateValue = $('#alt_billing_state').val();
                teatro_debug_log( 'Teatro DEBUG [JS]: Form submitting, alt_billing_state value =', stateValue );
            });
            
            // Run once on page load to set initial state (preserve saved value)
            // Use a small delay to ensure the field is fully rendered
            setTimeout(function() {
                teatro_debug_log( 'Teatro DEBUG [JS]: Running initial state update after page load' );
                updateAltBillingState(true);
            }, 100);
        });
        </script>
        
        <p class="form-row form-row-first">
            <label for="alt_billing_phone"><?php esc_html_e( 'Telefono', 'teatrosolare' ); ?></label>
            <input type="tel" class="woocommerce-Input woocommerce-Input--text input-text" name="alt_billing_phone" id="alt_billing_phone" value="<?php echo esc_attr( $alt_values['billing_phone'] ); ?>" />
        </p>
        
        <p class="form-row form-row-last">
            <label for="alt_billing_email"><?php esc_html_e( 'Email', 'teatrosolare' ); ?>&nbsp;<span class="required">*</span></label>
            <input type="email" class="woocommerce-Input woocommerce-Input--text input-text" name="alt_billing_email" id="alt_billing_email" value="<?php echo esc_attr( $alt_values['billing_email'] ); ?>" />
        </p>
        <div class="clear"></div>
        
        <p class="form-row form-row-first">
            <label for="alt_billing_cf2"><?php esc_html_e( 'Codice Fiscale', 'teatrosolare' ); ?></label>
            <input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="alt_billing_cf2" id="alt_billing_cf2" value="<?php echo esc_attr( $alt_values['billing_cf2'] ); ?>" />
        </p>
        
        <p class="form-row form-row-last">
            <label for="alt_billing_cf"><?php esc_html_e( 'Partita IVA', 'teatrosolare' ); ?></label>
            <input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="alt_billing_cf" id="alt_billing_cf" value="<?php echo esc_attr( $alt_values['billing_cf'] ); ?>" />
        </p>
        <div class="clear"></div>
        
        <p style="margin-top: 20px;">
            <button type="submit" class="woocommerce-Button button<?php echo esc_attr( wc_wp_theme_get_element_class_name( 'button' ) ? ' ' . wc_wp_theme_get_element_class_name( 'button' ) : '' ); ?>" name="save_account_details" value="<?php esc_attr_e( 'Salva cambiamenti', 'woocommerce' ); ?>"><?php esc_html_e( 'Salva cambiamenti', 'woocommerce' ); ?></button>
        </p>
    </fieldset>
    <?php
}

/**
 * Save alternate billing address from My Account > Edit Account page
 * Only runs on frontend account page
 */
add_action( 'woocommerce_save_account_details', 'teatro_save_alternate_address_from_edit_account', 15, 1 );
function teatro_save_alternate_address_from_edit_account( $user_id ) {
    // Only run on frontend account page
    if ( is_admin() || ! is_account_page() ) {
        return;
    }
    
    // DEBUG: Log save function called
    teatro_debug_log( 'Teatro DEBUG [SAVE]: Save function called for user ' . $user_id );
    teatro_debug_log( 'Teatro DEBUG [SAVE]: POST data keys: ' . print_r( array_keys( $_POST ), true ) );
    
    // Check if user wants to delete alternate address
    if ( isset( $_POST['teatro_delete_alt_address'] ) && $_POST['teatro_delete_alt_address'] == '1' ) {
        teatro_debug_log( 'Teatro DEBUG [SAVE]: User requested deletion of alt address' );
        delete_user_meta( $user_id, 'teatro_alt_billing_address' );
        return;
    }
    
    // Start from existing alternate address so we don't accidentally drop fields
    // (e.g. if a field is missing from POST for some reason).
    $existing_address  = get_user_meta( $user_id, 'teatro_alt_billing_address', true );
    $alternate_address = is_array( $existing_address ) ? $existing_address : array();
    
    // DEBUG: Log existing address
    teatro_debug_log( 'Teatro DEBUG [SAVE]: Existing alternate address: ' . print_r( $existing_address, true ) );

    // Save alternate address (only if at least one field is filled)
    $has_data = false;

    $fields = array(
        'billing_first_name', 'billing_last_name', 'billing_company',
        'billing_address_1', 'billing_address_2', 'billing_city',
        'billing_state', 'billing_postcode', 'billing_country',
        'billing_email', 'billing_phone', 'billing_cf', 'billing_cf2',
    );

    foreach ( $fields as $field ) {
        $alt_field = 'alt_' . $field;

        // If the field is present in POST, update it. Otherwise, keep the existing value.
        if ( array_key_exists( $alt_field, $_POST ) ) {
            $value = sanitize_text_field( wp_unslash( $_POST[ $alt_field ] ) );
            teatro_debug_log( 'Teatro DEBUG [SAVE]: Field ' . $alt_field . ' in POST with value: "' . $value . '"' );
            $alternate_address[ $field ] = $value;
        } else {
            teatro_debug_log( 'Teatro DEBUG [SAVE]: Field ' . $alt_field . ' NOT in POST, keeping existing: "' . ( isset( $alternate_address[ $field ] ) ? $alternate_address[ $field ] : '[not set]' ) . '"' );
        }

        // Track whether at least one field has a non-empty value.
        if ( ! empty( $alternate_address[ $field ] ) ) {
            $has_data = true;
        }
    }
    
    // DEBUG: Log final alternate address before saving
    teatro_debug_log( 'Teatro DEBUG [SAVE]: Final alternate address to save: ' . print_r( $alternate_address, true ) );
    teatro_debug_log( 'Teatro DEBUG [SAVE]: billing_state value: "' . ( isset( $alternate_address['billing_state'] ) ? $alternate_address['billing_state'] : '[not set]' ) . '"' );
    teatro_debug_log( 'Teatro DEBUG [SAVE]: Has data: ' . ( $has_data ? 'yes' : 'no' ) );
    
    // Only save if at least one field has data
    if ( $has_data ) {
        // WooCommerce form fields already output the correct state/country codes,
        // so we can save them directly without normalization
        update_user_meta( $user_id, 'teatro_alt_billing_address', $alternate_address );
        teatro_debug_log( 'Teatro DEBUG [SAVE]: Successfully saved alternate address to user meta' );
    } else {
        // If all fields are empty, delete the alternate address
        teatro_debug_log( 'Teatro DEBUG [SAVE]: No data, deleting alternate address' );
        delete_user_meta( $user_id, 'teatro_alt_billing_address' );
    }
}

/**
 * Prevent WooCommerce from updating user meta when using alternate address
 * This is critical to prevent default address from being overwritten
 * Only runs on frontend checkout (not in admin)
 */
add_filter( 'update_user_metadata', 'teatro_prevent_billing_update_for_alternate', 10, 5 );
function teatro_prevent_billing_update_for_alternate( $check, $user_id, $meta_key, $meta_value, $prev_value ) {
    // Only run on frontend (not in admin)
    if ( is_admin() ) {
        return $check;
    }
    
    // Only for logged-in users
    if ( ! is_user_logged_in() ) {
        return $check;
    }
    
    // Only for current user
    if ( $user_id !== get_current_user_id() ) {
        return $check;
    }
    
    // Only on checkout page
    if ( ! is_checkout() ) {
        return $check;
    }
    
    // Check if using alternate address (check both POST and session)
    $selected = '';
    if ( isset( $_POST['teatro_alt_billing_select'] ) ) {
        $selected = sanitize_text_field( $_POST['teatro_alt_billing_select'] );
    } elseif ( WC()->session && WC()->session->get( 'teatro_using_alternate_billing' ) ) {
        $selected = 'alternate';
    }
    
    // Also check if we're in an AJAX request and session indicates alternate
    if ( wp_doing_ajax() && WC()->session && WC()->session->get( 'teatro_selected_alt_billing' ) === 'alternate' ) {
        $selected = 'alternate';
    }
    
    if ( $selected !== 'alternate' ) {
        return $check; // Allow updates for default address
    }
    
    // Block billing field updates when using alternate address
    // This includes checkout, order processing, payment completion, and AJAX updates
    $billing_fields = array(
        'billing_first_name', 'billing_last_name', 'billing_company',
        'billing_address_1', 'billing_address_2', 'billing_city',
        'billing_state', 'billing_postcode', 'billing_country',
        'billing_email', 'billing_phone', 'billing_cf', 'billing_cf2',
    );
    
    if ( in_array( $meta_key, $billing_fields ) ) {
        // Return true to prevent the update
        teatro_debug_log( 'Teatro DEBUG: Blocked update_user_meta for ' . $meta_key . ' (alternate address in use, value: ' . ( $meta_value ? $meta_value : '[empty]' ) . ')' );
        return true;
    }
    
    return $check;
}

/**
 * ============================================================================
 * DISPLAY CUSTOM BILLING FIELDS (billing_cf2 and billing_cf)
 * ============================================================================
 * Display Codice Fiscale (billing_cf2) and Partita IVA (billing_cf) in:
 * 1. Admin order edit screen (backend)
 * 2. Thank you page (order received page)
 * ============================================================================
 */

/**
 * NOTE: WooCommerce automatically displays billing_cf2 and billing_cf in the admin order edit screen
 * when they exist in order meta (thanks to the ThemeHigh Checkout Fields Pro plugin). No custom code is needed for display.
 * We only need to save these fields during checkout and when manually edited in admin.
 */

/**
 * Save custom billing fields when order is updated in admin
 * WooCommerce saves these fields with the _billing_ prefix
 * Only runs in admin panel
 */
add_action( 'woocommerce_process_shop_order_meta', 'teatro_save_custom_billing_fields_admin', 10, 1 );
function teatro_save_custom_billing_fields_admin( $order_id ) {
    // Only run in admin panel
    if ( ! is_admin() ) {
        return;
    }
    $order = wc_get_order( $order_id );
    if ( ! $order ) {
        return;
    }
    
    // Save billing_cf2 (Codice Fiscale)
    if ( isset( $_POST['_billing_cf2'] ) ) {
        $order->update_meta_data( 'billing_cf2', sanitize_text_field( $_POST['_billing_cf2'] ) );
    }
    
    // Save billing_cf (Partita IVA)
    if ( isset( $_POST['_billing_cf'] ) ) {
        $order->update_meta_data( 'billing_cf', sanitize_text_field( $_POST['_billing_cf'] ) );
    }
    
    $order->save();
}

// -----------------------------------------------------

/**
 * Display custom billing fields on thank you page (order received page)
 * These fields will appear under the email/phone in the billing address section
 * Uses static flag to prevent duplicate display
 * Only runs on frontend thank you page
 */
add_action( 'woocommerce_order_details_after_billing_address', 'teatro_display_custom_billing_fields_thankyou', 10, 1 );
add_action( 'woocommerce_thankyou', 'teatro_display_custom_billing_fields_thankyou_fallback', 20, 1 );
function teatro_display_custom_billing_fields_thankyou( $order ) {
    // Only run on frontend (not in admin)
    if ( is_admin() ) {
        return;
    }
    
    // Static flag to prevent duplicate display (per order ID)
    static $displayed_orders = array();
    
    // Get order ID - handle both order object and order ID
    $order_id = 0;
    if ( is_numeric( $order ) ) {
        $order_id = $order;
        $order = wc_get_order( $order_id );
    } elseif ( is_a( $order, 'WC_Order' ) ) {
        $order_id = $order->get_id();
    } else {
        return;
    }
    
    if ( ! $order ) {
        return;
    }
    
    // Check if already displayed for this order
    if ( isset( $displayed_orders[ $order_id ] ) ) {
        return;
    }
    
    // Only run on thank you page or view order page
    $is_thankyou = false;
    if ( is_wc_endpoint_url( 'order-received' ) ) {
        $is_thankyou = true;
    } elseif ( is_wc_endpoint_url( 'view-order' ) ) {
        $is_thankyou = true;
    }
    
    if ( ! $is_thankyou ) {
        return;
    }
    
    // Get custom fields from order meta (use direct meta access to avoid recursion)
    $billing_cf2 = get_post_meta( $order_id, 'billing_cf2', true );
    $billing_cf  = get_post_meta( $order_id, 'billing_cf', true );
    
    // Debug logging (only if debug mode is on)
    if ( TEATRO_DEBUG_MODE ) {
        teatro_debug_log( 'Teatro DEBUG [THANK YOU - HOOK]: Hook fired - Order ID: ' . $order_id );
        teatro_debug_log( 'Teatro DEBUG [THANK YOU - HOOK]: billing_cf2: ' . ( $billing_cf2 ? $billing_cf2 : '[empty]' ) );
        teatro_debug_log( 'Teatro DEBUG [THANK YOU - HOOK]: billing_cf: ' . ( $billing_cf ? $billing_cf : '[empty]' ) );
    }
    
    // Only display if at least one field has a value
    if ( empty( $billing_cf2 ) && empty( $billing_cf ) ) {
        if ( TEATRO_DEBUG_MODE ) {
            teatro_debug_log( 'Teatro DEBUG [THANK YOU - HOOK]: Both fields empty, not displaying' );
        }
        return;
    }
    
    // Mark as displayed for this order to prevent duplicates
    $displayed_orders[ $order_id ] = true;
    
    if ( TEATRO_DEBUG_MODE ) {
        teatro_debug_log( 'Teatro DEBUG [THANK YOU - HOOK]: Displaying fields' );
    }
    
    // Display fields inline with the billing address (under phone/email)
    // Output directly without extra wrapper to ensure it's inside the existing address tag
    ?>
    <?php if ( ! empty( $billing_cf2 ) ) : ?>
        <br/><strong><?php esc_html_e( 'Codice Fiscale:', 'teatrosolare' ); ?></strong> <?php echo esc_html( $billing_cf2 ); ?>
    <?php endif; ?>
    
    <?php if ( ! empty( $billing_cf ) ) : ?>
        <br/><strong><?php esc_html_e( 'Partita IVA:', 'teatrosolare' ); ?></strong> <?php echo esc_html( $billing_cf ); ?>
    <?php endif; ?>
    <?php
}

/**
 * Fallback display function using woocommerce_thankyou hook
 * This ensures fields display even if the main hook doesn't fire
 * Uses JavaScript to inject fields into the billing address section
 * Only runs on frontend thank you page
 */
function teatro_display_custom_billing_fields_thankyou_fallback( $order_id ) {
    // Only run on frontend (not in admin)
    if ( is_admin() ) {
        return;
    }
    
    // Only run on thank you page
    if ( ! is_wc_endpoint_url( 'order-received' ) ) {
        return;
    }
    
    // Static flag to prevent duplicate display (per order ID)
    static $displayed_orders_fallback = array();
    
    // Check if already displayed for this order
    if ( isset( $displayed_orders_fallback[ $order_id ] ) ) {
        return;
    }
    
    $order = wc_get_order( $order_id );
    if ( ! $order ) {
        return;
    }
    
    // Get custom fields from order meta (use direct meta access to avoid recursion)
    $billing_cf2 = get_post_meta( $order_id, 'billing_cf2', true );
    $billing_cf  = get_post_meta( $order_id, 'billing_cf', true );
    
    // Debug logging (only if debug mode is on)
    if ( TEATRO_DEBUG_MODE ) {
        teatro_debug_log( 'Teatro DEBUG [THANK YOU - FALLBACK]: Hook fired - Order ID: ' . $order_id );
        teatro_debug_log( 'Teatro DEBUG [THANK YOU - FALLBACK]: billing_cf2: ' . ( $billing_cf2 ? $billing_cf2 : '[empty]' ) );
        teatro_debug_log( 'Teatro DEBUG [THANK YOU - FALLBACK]: billing_cf: ' . ( $billing_cf ? $billing_cf : '[empty]' ) );
    }
    
    // Only display if at least one field has a value
    if ( empty( $billing_cf2 ) && empty( $billing_cf ) ) {
        return;
    }
    
    // Check if main function already displayed (to avoid duplicates)
    static $displayed_orders = array();
    if ( isset( $displayed_orders[ $order_id ] ) ) {
        if ( TEATRO_DEBUG_MODE ) {
            teatro_debug_log( 'Teatro DEBUG [THANK YOU - FALLBACK]: Already displayed by main function, skipping' );
        }
        return;
    }
    
    // Mark as displayed for this order
    $displayed_orders_fallback[ $order_id ] = true;
    
    if ( TEATRO_DEBUG_MODE ) {
        teatro_debug_log( 'Teatro DEBUG [THANK YOU - FALLBACK]: Displaying fields via fallback' );
    }
    
    // Use JavaScript to inject fields into the billing address section
    // This ensures they appear inside the address box, after the email
    ?>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        // Find the billing address section
        var $billingSection = $('.woocommerce-customer-details .woocommerce-column--billing-address address, .woocommerce-customer-details address');
        
        if ( $billingSection.length ) {
            // Find the email or phone element to insert after
            var $email = $billingSection.find('a[href^="mailto:"]');
            var $phone = $billingSection.find('a[href^="tel:"]');
            
            // Insert after email if exists, otherwise after phone, otherwise at the end
            var $insertAfter = $email.length ? $email : ($phone.length ? $phone : null);
            
            var customFieldsHtml = '';
            <?php if ( ! empty( $billing_cf2 ) ) : ?>
                customFieldsHtml += '<br/><strong><?php esc_html_e( 'Codice Fiscale:', 'teatrosolare' ); ?></strong> <?php echo esc_js( $billing_cf2 ); ?>';
            <?php endif; ?>
            
            <?php if ( ! empty( $billing_cf ) ) : ?>
                customFieldsHtml += '<br/><strong><?php esc_html_e( 'Partita IVA:', 'teatrosolare' ); ?></strong> <?php echo esc_js( $billing_cf ); ?>';
            <?php endif; ?>
            
            if ( customFieldsHtml && $insertAfter && $insertAfter.length ) {
                $insertAfter.after( customFieldsHtml );
            } else if ( customFieldsHtml && $billingSection.length ) {
                // Fallback: append to the address tag
                $billingSection.append( customFieldsHtml );
            }
        }
    });
    </script>
    <?php
}


add_filter( 'woocommerce_add_error', function ( $error ) {

    if ( strpos( $error, 'Privacy Policy' ) !== false ) {
        $error = 'È obbligatorio accettare la Privacy Policy per completare l’ordine';
    }

    return $error;
});
