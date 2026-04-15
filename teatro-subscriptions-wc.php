<?php
/* Create new product type for Courses */
add_action('init', 'create_teatrosubscription_product_type');
function create_teatrosubscription_product_type(){
	class WC_Product_Teatrosubscription extends WC_Product {
		public function get_type() {
			return 'teatrosubscription';
		}
    }
}
/* Add product type to dropdown */
add_filter('product_type_selector', 'add_teatrosubscription_product_type');
function add_teatrosubscription_product_type($types){
	$types['teatrosubscription'] = __('Teatro Subscription', 'teatro-subscriptions');
    return $types;
}
/* Load Course product class */
add_filter('woocommerce_product_class', 'teatrosubscription_woocommerce_product_class', 10, 2);
function teatrosubscription_woocommerce_product_class($classname, $product_type){
	if($product_type == 'teatrosubscription') {
        $classname = 'WC_Product_Teatrosubscription';
    }
    return $classname;
}
/* Add Tabs to course product type */
add_filter('woocommerce_product_data_tabs', 'teatrosubscription_product_tabs');
function teatrosubscription_product_tabs($tabs){
	$tabs['teatro_subscription_options'] = array(
        'label'    => __('Teatro Subscriptions', 'teatro-subscriptions'),
        'target'   => 'teatro_subscription_options_data',
        'class'    => array('show_if_teatrosubscription'),
    );
	$tabs['teatro_subscription_expires'] = array(
        'label'    => __('Teatro Subscriptions', 'teatro-subscriptions'),
        'target'   => 'teatro_subscription_expires_data',
        'class'    => array('show_if_teatrosubscription'),
    );
	/* Hide all other tabs for this custom product type */
	$tabs['general']['class'][] = 'show_if_teatrosubscription';
	$tabs['attribute']['class'][] = 'hide_if_teatrosubscription';
	$tabs['shipping']['class'][] = 'hide_if_teatrosubscription';
	$tabs['inventory']['class'][] = 'hide_if_teatrosubscription';
	$tabs['advanced']['class'][] = 'hide_if_teatrosubscription';
	$tabs['linked_product']['class'][] = 'hide_if_teatrosubscription';
	/* Hide and show newly create tab  */
	$tabs['teatro_subscription_options']['class'][] = 'hide_if_teatrosubscription';
	$tabs['teatro_subscription_options']['class'][] = 'show_if_simple';
	$tabs['teatro_subscription_options']['class'][] = 'show_if_variable';
	$tabs['teatro_subscription_options']['class'][] = 'show_if_grouped';
	$tabs['teatro_subscription_options']['class'][] = 'show_if_external';
	$tabs['teatro_subscription_options']['class'][] = 'show_if_courses';
	return $tabs;
}
/* Show general price tab for subscription product type */
add_action('admin_footer', 'show_price_tab_for_teatroevents');
function show_price_tab_for_teatroevents(){
	if('product' != get_post_type()):
		return;
	endif;
	?>
	<script type='text/javascript'>
	  jQuery( document ).ready( function() {
		  jQuery( '.options_group.pricing' ).addClass( 'show_if_teatrosubscription' ).show();
		  jQuery( '.general_options' ).show();
	 });
	</script>
	<?php
}
/* saving custom product type settings & content */
add_action('woocommerce_process_product_meta', 'save_teatrosubscription_product_data');
function save_teatrosubscription_product_data($post_id) {
    if(isset($_POST['teatrosubscription_expire_month'])) {
        $subscription_month = sanitize_text_field($_POST['teatrosubscription_expire_month']);
        update_post_meta($post_id, 'teatrosubscription_expire_month', $subscription_month);
    }
    if(isset($_POST['teatrosubscription_expire_date'])) {
        $subscription_date = sanitize_text_field($_POST['teatrosubscription_expire_date']);
        update_post_meta($post_id, 'teatrosubscription_expire_date', $subscription_date);
    }
	if(isset($_POST['teatrosubscription_type'])) {
        $subscription_type = sanitize_text_field($_POST['teatrosubscription_type']);
        update_post_meta($post_id, 'teatrosubscription_type', $subscription_type);
    }
	if(isset($_POST['teatrosubscription_required'])) {
        $subscription_req = sanitize_text_field($_POST['teatrosubscription_required']);
        update_post_meta($post_id, 'teatrosubscription_required', $subscription_req);
    } else {
		update_post_meta($post_id, 'teatrosubscription_required', '');
	}
}
/* Add custom subscription tab data */
add_action('woocommerce_product_data_panels', 'teatrosubscription_product_tab_content');
function teatrosubscription_product_tab_content() {
	 global $post; global $teatro_subscriptions;
	 $teatro_sub_id = get_post_meta($post->ID, 'teatrosubscription_type', true);
	 $ts_expire_month = get_post_meta($post->ID, 'teatrosubscription_expire_month', true);
	 $ts_expire_date = get_post_meta($post->ID, 'teatrosubscription_expire_date', true);
	 $teatro_sub_st=get_post_meta($post->ID, 'teatrosubscription_required', true);
	$primary_subscription_id=$teatro_subscriptions->getPrimarySubscription();
	 $sub_product=!empty($primary_subscription_id)?wc_get_product($primary_subscription_id):'';
	 $sub_product_name=!empty($sub_product)?$sub_product->get_title():'';
	 $tsub_checked=($teatro_sub_st == strtolower(trim('tsreq')))?'checked':'';
	 ?>
	 <!-- Data for Teatro subscrion expire date & month data -->
	 <div id='teatro_subscription_expires_data' class='panel woocommerce_options_panel'>
		<div class='options_group'>
			<p class="form-field teatrosubscription_date_field">
				<label for="product_teatrosubscription_expire_month_date_class"><?php _e('Expire Date & Month','teatro-subscriptions'); ?> </label>
				<select id="teatrosubscription_expire_date" name="teatrosubscription_expire_date" class="select">
					<?php foreach(range(1, 31) as $tsexpdate){  ?>
					<option value="<?=$tsexpdate?>" <?=($tsexpdate == $ts_expire_date)?'selected':'';?>><?=$tsexpdate?></option>
					<?php } ?>
				</select>
				<select id="teatrosubscription_expire_month" name="teatrosubscription_expire_month" class="select">
					<?php foreach(range(1, 12) as $tsexpmonth){ ?>
					<option value="<?=$tsexpmonth?>" <?=($tsexpmonth == $ts_expire_month)?'selected':'';?>><?=date('F', mktime(0, 0, 0, $tsexpmonth, 10));?></option>
					<?php } ?>
				</select>
			</p>
			<p class="description"><?php _e('Select the teatro subscription expire date & month','teatro-subscriptions'); ?> </p>
		</div>
	 </div>
	 <!-- Data for Teatro Subscription  -->
	 <div id='teatro_subscription_options_data' class='panel woocommerce_options_panel'>
		<div class='options_group'>
			<?php if(!empty($sub_product_name)): ?>
			<p class="form-field teatrosubscription_date_field">
				<label for="product_teatrosubscription_required_class" style="width:275px;"><?php _e('Is Teatro Subscription Required ?','teatro-subscriptions'); ?> </label>
				<input type="checkbox" id="teatrosubscription_required" name="teatrosubscription_required" value="tsreq" <?=$tsub_checked?>  />
				<?=$sub_product_name?>
			</p>
			<?php else: ?>
			<p><?php _e('Teatro Subscription list is empty','teatro-subscriptions'); ?></p>
			<?php endif; ?>
		</div>
	 </div>
	 <?php
}
/* Show custom data on the single page */
add_action( 'woocommerce_product_meta_start', 'get_custom_data_teatrosubscription');
function get_custom_data_teatrosubscription(){
	global $teatro_subscriptions; global $product; $pid = $product->get_id();
	$current_user = wp_get_current_user(); //echo $current_user->ID;
	if($product->get_type() == 'teatrosubscription'){
		//$childusers=$teatro_subscriptions->subscriptionExpireUpdateStatus(); //$teatro_subscriptions->printRData($childusers);
		if($pid == $teatro_subscriptions->getPrimarySubscription()){ $childs=getSubAccounts(); //$teatro_subscriptions->printRData($childs);
			if(!empty($current_user->ID) && in_array('parent', $current_user->roles)){
				?><input type="hidden" name="teatrosubscription_id" value="<?=$pid?>" />
					<div class="grid">
						<p><?php echo esc_html__('Subscription Expires on:', 'teatro-subscriptions'); ?> <?=$teatro_subscriptions->createSubscriptionExpireDate($pid)?></p>
						<?php if(!empty($childs) && count($childs) > 0): ?>
						<div class="row teatrosubscription_dropdown">
							<label for="" class="col-sm-6 col-form-label">Seleziona un utente</label>
							<select class="select" id="selected_child_teatrosubscription">
								<option value=""><?php echo esc_html__('Please select child', 'teatro-subscriptions'); ?></option>
								<?php foreach($childs as $child): $ss=$teatro_subscriptions->getSubscriptionStatus($child->ID); ?>
								<?php if(!empty($_GET['sc']) and $_GET['sc'] == md5($child->ID)): ?>
								<option value="<?=$child->ID?>" <?=(strtolower($ss) == 'active')?'disabled':''?> selected>
									<?=get_user_by('id', $child->ID)->display_name?>
								</option>
								<?php else: //$teatro_subscriptions->getSubscriptionExpireDate($child->ID, 1); ?>
								<option value="<?=$child->ID?>" <?=(strtolower($ss) == 'active')?'disabled':''?>><?=get_user_by('id', $child->ID)->display_name?></option>
								<?php endif; endforeach;?>
							</select>
						</div>
						<button type="button" name="add-to-cart" class="siteButton single_add_to_cart_button_teatrosubscription"><?php echo esc_html__('Buy', 'teatro-subscriptions'); ?></button>
						<?php
							$redirect_slink=add_query_arg('redirect_to', urlencode(get_permalink()), wc_get_account_endpoint_url('add-child'));
							echo '<div class="newProf hasChild">'.esc_html__('Do you want to add a new profile?', 'teatro-subscriptions').'<a href="'.$redirect_slink.'">Clicca qui</a></div>';
						?>
						<?php else: $redirect_slink=add_query_arg('redirect_to', urlencode(get_permalink()), wc_get_account_endpoint_url('add-child')); ?>
						<div class="newProf"><?php echo esc_html__('Do you want to add a new profile?', 'teatro-subscriptions'); ?> <a href="<?=$redirect_slink?>"><?php echo esc_html__('Click here', 'teatro-subscriptions'); ?></a></div>
						<?php endif; ?>
					</div>
				<?php
			} else {
				$redirect_plink = add_query_arg('redirect_to', urlencode(get_permalink($pid)),home_url('mio-account'));
				echo ''.esc_html__('Please login as parent to purchase this subscription product', 'teatro-subscriptions').', <a href="'.$redirect_plink.'" class="siteButton courses_cta">'.esc_html__('click here to login', 'teatro-subscriptions').'</a>';
			}
		} else {
			echo esc_html__('selected subscription not available for purchase', 'teatro-subscriptions');
		}
	}
}
/* Get Cart item data */
add_filter('woocommerce_get_item_data', 'display_cart_item_teatrosubscription_meta_data', 10, 2);
function display_cart_item_teatrosubscription_meta_data($item_data, $cart_item) {
	global $teatro_subscriptions; ///$teatro_subscriptions->printRData($cart_item);
    if(isset($cart_item['selected_child_id'])) {
        $item_data[] = array(
            'key'       => 'Bambino ',
            'value'     => get_user_by('id', $cart_item['selected_child_id'])->display_name,
        );
    }
	if(isset($cart_item['subscription_expired_on'])) {
        $item_data[] = array(
            'key'       => 'Scadenza ',
            'value'     => $cart_item['subscription_expired_on'], // $teatro_subscriptions->getForamttedDate($cart_item['subscription_expired_on']),
        );
    }
    return $item_data;
}
/* Add custom data to order line items */
add_action('woocommerce_checkout_create_order_line_item', 'save_cart_item_teatrosubscription_meta_as_order_item_meta', 10, 4);
function save_cart_item_teatrosubscription_meta_as_order_item_meta($item, $cart_item_key, $values, $order) {
    if(isset($values['selected_child_id'])){
        $item->update_meta_data('selected_child_id', $values['selected_child_id']);
    }
	if(isset($values['subscription_expired_on'])){
        $item->update_meta_data('subscription_expired_on', $values['subscription_expired_on']);
    };
}
/* Change meta labels in admin and order details page  */
add_filter('woocommerce_order_item_display_meta_key', 'filter_teatrosubscription_order_item_display_meta_key', 20, 3);
function filter_teatrosubscription_order_item_display_meta_key($display_key, $meta, $item){
    if($item->get_type() === 'line_item' && $meta->key === 'selected_child_id'){
        $display_key = __("Selected Child ", "teatro-subscriptions");
    }
	if($item->get_type() === 'line_item' && $meta->key === 'subscription_expired_on'){
        $display_key = __("Expire Date ", "teatro-subscriptions" );
    }
    return $display_key;
}
/* Change meta values in admin and order details page  */
add_filter('woocommerce_order_item_display_meta_value', 'filter_teatrosubscription_order_item_display_meta_value', 20, 3);
function filter_teatrosubscription_order_item_display_meta_value($display_value, $meta, $item){
	global $teatro_subscriptions, $WC_custom_teatro_attributes;
    if($item->get_type() === 'line_item' && $meta->key === 'selected_child_id'){
		$child_user = get_user_by('id', $meta->value);
		$child_cf = get_user_meta($meta->value, 'codice_fiscale', true);
		$display_value = $child_user->display_name;
		if(!empty($child_cf)){
			$display_value .= '<br><strong>CF: ' . esc_html($child_cf) . '</strong>';
		}
    }
	if($item->get_type() === 'line_item' && $meta->key === 'subscription_expired_on'){
        $display_value = $meta->value; //$WC_custom_teatro_attributes->getTranslatedDateString($meta->value);
    }
    return $display_value;
}
/* update subscription details to user when order completed */
add_action('woocommerce_order_status_completed', 'update_child_subscription_order_completed');
function update_child_subscription_order_completed($order_id){
	global $teatro_subscriptions; $order = new WC_Order($order_id);
	foreach($order->get_items() as $item){		 //$teatro_subscriptions->printRData($item);
		if($item->get_product_id() == $teatro_subscriptions->getPrimarySubscription()){
			$selected_child_user = $item->get_meta('selected_child_id');
			$subscription_expire_date = $item->get_meta('subscription_expired_on');
			$old_orders=get_user_meta($selected_child_user, 'child_subscription_order_ids', true);
			$old_orders_array = !empty($old_orders)?unserialize($old_orders):[];
			array_push($old_orders_array, $order_id);  //$teatro_subscriptions->printRData($old_orders_array);
			update_user_meta($selected_child_user, 'child_subscription_order_ids', serialize($old_orders_array));
			update_user_meta($selected_child_user, 'child_subscription_expire_date', $subscription_expire_date);
			update_user_meta($selected_child_user, 'child_subscription_status', 'active');
		}
	}
}

/* Mostra e permette il reset dello stato abbonamento nella scheda "Modifica utente" (backend WP) */
add_action('show_user_profile', 'teatro_show_child_subscription_admin_field');
add_action('edit_user_profile', 'teatro_show_child_subscription_admin_field');
function teatro_show_child_subscription_admin_field($user) {
	if (!current_user_can('manage_options')) return;
	$user_roles = (array) $user->roles;
	if (!in_array('child', $user_roles)) return;
	$status      = get_user_meta($user->ID, 'child_subscription_status', true);
	$expire_date = get_user_meta($user->ID, 'child_subscription_expire_date', true);
	$status_label = !empty($status) ? esc_html($status) : '— non impostato —';
	$status_color = (strtolower($status) === 'active') ? '#2ecc71' : '#e74c3c';
	?>
	<h2>Quota Associativa</h2>
	<table class="form-table">
		<tr>
			<th><label>Stato abbonamento</label></th>
			<td>
				<strong style="color:<?php echo $status_color; ?>;"><?php echo $status_label; ?></strong>
				<?php if (!empty($expire_date)): ?>
					&nbsp;&mdash;&nbsp; Scadenza: <strong><?php echo esc_html($expire_date); ?></strong>
				<?php endif; ?>
			</td>
		</tr>
		<?php if (strtolower($status) === 'active'): ?>
		<tr>
			<th></th>
			<td>
				<?php
				$reset_url = wp_nonce_url(
					add_query_arg([
						'teatro_reset_sub' => $user->ID,
						'user_id'          => $user->ID,
					], admin_url('user-edit.php')),
					'teatro_reset_sub_' . $user->ID
				);
				?>
				<a href="<?php echo esc_url($reset_url); ?>"
				   class="button button-secondary"
				   onclick="return confirm('Confermi il reset dello stato abbonamento per questo figlio?');">
					Reimposta stato abbonamento (annulla quota)
				</a>
				<p class="description">Azzera <code>child_subscription_status</code> e <code>child_subscription_expire_date</code>. Usare solo se l'ordine risulta annullato ma lo stato è rimasto &quot;active&quot;.</p>
			</td>
		</tr>
		<?php endif; ?>
	</table>
	<?php
}
add_action('admin_init', 'teatro_handle_child_subscription_reset');
function teatro_handle_child_subscription_reset() {
	if (empty($_GET['teatro_reset_sub']) || empty($_GET['user_id'])) return;
	if (!current_user_can('manage_options')) return;
	$child_id = intval($_GET['teatro_reset_sub']);
	check_admin_referer('teatro_reset_sub_' . $child_id);
	update_user_meta($child_id, 'child_subscription_status', '');
	update_user_meta($child_id, 'child_subscription_expire_date', '');
	wp_redirect(add_query_arg(
		['user_id' => $child_id, 'teatro_reset_done' => 1],
		admin_url('user-edit.php')
	));
	exit;
}
add_action('admin_notices', 'teatro_subscription_reset_notice');
function teatro_subscription_reset_notice() {
	if (!empty($_GET['teatro_reset_done'])) {
		echo '<div class="notice notice-success is-dismissible"><p>Stato abbonamento reimpostato correttamente.</p></div>';
	}
}

/* Reset subscription status when order is cancelled or refunded */
add_action('woocommerce_order_status_cancelled', 'reset_child_subscription_on_cancel');
add_action('woocommerce_order_status_refunded',  'reset_child_subscription_on_cancel');
function reset_child_subscription_on_cancel($order_id) {
	global $teatro_subscriptions;
	$order = wc_get_order($order_id);
	if (empty($order)) return;
	foreach ($order->get_items() as $item) {
		if ($item->get_product_id() == $teatro_subscriptions->getPrimarySubscription()) {
			$child_id = $item->get_meta('selected_child_id');
			if (!empty($child_id)) {
				update_user_meta($child_id, 'child_subscription_status', '');
				update_user_meta($child_id, 'child_subscription_expire_date', '');
				$order->add_order_note(
					sprintf(
						'Quota associativa annullata: stato abbonamento del figlio (ID: %s) reimpostato.',
						esc_html($child_id)
					)
				);
			}
		}
	}
}

function get_completed_order_ids_with_product_type($target_type = 'simple') {
    $orders = wc_get_orders([
        'status' => 'completed',
        'limit'  => -1, // retrieve all
        'return' => 'objects',
    ]);
    $matching_order_ids = [];
    foreach ($orders as $order) {
        foreach ($order->get_items() as $item) {
            $product = $item->get_product();
            if ($product && $product->get_type() === $target_type) {
                $matching_order_ids[] = $order->get_id();
                break; // no need to check further items in this order
            }
        }
    }
    return $matching_order_ids;
}
add_action('wp_head', 'updateMemberShipTemp');
function updateMemberShipTemp(){
	if(isset($_GET['update_membership'])){ global $teatro_subscriptions;
		$orders = get_completed_order_ids_with_product_type('teatrosubscription');	//$teatro_subscriptions->printRData($orders);
		foreach($orders as $orderID){
			update_child_subscription_order_completed($orderID);
		}
	}
}

// Add custom text before the product price.
add_action( 'woocommerce_single_product_summary' , 'custom_text_teatrosubscriptions', 5);
function custom_text_teatrosubscriptions() {
	if(get_the_terms(get_the_ID(),'product_type')[0]->slug == 'teatrosubscription'){
		echo '<div class="smallHeading text-up">Quota associativa <span class="highlightText">'.get_the_title().'</span></div>';
   }
}

/* Add Courses tab to my account */
function add_teatrosubscriptions_endpoint() {
    add_rewrite_endpoint('teatrosubscriptions', EP_ROOT | EP_PAGES);
}
add_action('init', 'add_teatrosubscriptions_endpoint');
/* insert the name to menu */
function add_teatrosubscriptions_to_my_account($items) {
	$current_user = wp_get_current_user();
	if(isset($current_user->roles) && is_array($current_user->roles) && in_array( 'parent', (array) $current_user->roles)) {
	    $new_items = array(
	        'teatrosubscriptions' => __('My Subscriptions', 'teatro-subscriptions'),
	    );
	    $items = array_slice( $items, 0, 3, true ) + $new_items + array_slice( $items, 3, NULL, true );
         return $items;
	}
    return $items;
}
add_filter('woocommerce_account_menu_items', 'add_teatrosubscriptions_to_my_account');
/* Add Content to new tab */
function teatrosubscriptions_content(){
	$current_user = wp_get_current_user();
	if(isset($current_user->roles) && is_array($current_user->roles) && in_array( 'parent', (array) $current_user->roles)){
		global $teatro_subscriptions; $mysubscriptions=$teatro_subscriptions->getMySubscriptionOrders();
		echo '<div class="myAccContent--title">'.esc_html__('RENEW OR MANAGE YOUR MEMBERSHIP FEE WITH ONE CLICK', 'teatro-subscriptions').'</div>';  //$WC_custom_teatro_attributes->printRData($mysubscriptions);
		if(!empty($mysubscriptions)): ?>
		<table class="woocommerce-orders-table woocommerce-MyAccount-orders shop_table shop_table_responsive my_account_orders account-orders-table">
			<thead>
				<tr>
						<th><?php echo esc_html__('Product', 'teatro-subscriptions'); ?></th>
						<th><?php echo esc_html__('Profile', 'teatro-subscriptions'); ?></th>
						<th><?php echo esc_html__('Status', 'teatro-subscriptions'); ?></th>
						<th><?php echo esc_html__('Actions', 'teatro-subscriptions'); ?></th>
        			</tr>
				</thead>
			<tbody>
				<?php
					foreach($mysubscriptions as $sub){
						$order = wc_get_order($sub);
							foreach( $order->get_items() as $item ) {
								echo '<tr>';
									$product_id = $item->get_product_id();
									$product = wc_get_product( $product_id );
									$child = get_userdata($item->get_meta('selected_child_id') );
									echo '<td class="product-title" data-title="Prodotto">'.$product->get_title().'<span class="price">'.$order->get_total().'</span></td>';
									echo '<td data-title="Profilo">' . ($child && !is_wp_error($child) ? $child->display_name : '') . '</td>';
									echo '<td data-title="Status"><span class="order--status">'.esc_html( wc_get_order_status_name($order->get_status()) ).'</span></td>';
									echo '<td data-title="Azioni"><a href="'.esc_url( $order->get_view_order_url() ).'" class="siteButtonDark buttonSmall">'.__('view', 'teatro-subscriptions').'</a></td>';
								echo '</tr>';
							}
						}
				?>
			</tbody>
		</table>
		<?php $main_subscription_parmalink=get_permalink(162);?>
		<p><a class="siteButtonDark buttonSmall" href="<?php echo $main_subscription_parmalink;?>"><?php esc_html_e( 'Acquista un\'altra quota associativa annuale', 'teatro-subscriptions'); ?></a></p>
		<?php do_action( 'woocommerce_before_account_orders_pagination' ); ?>
		<?php else:
			$shop_permalink = wc_get_page_permalink('shop');
			if (!$shop_permalink) {
				$shop_permalink = home_url();
			}
			$shop_permalink = esc_url(apply_filters('woocommerce_return_to_shop_redirect', $shop_permalink));
			$main_subscription_parmalink=get_permalink(162);
			$notice_message = sprintf(
				/*'<span class="noticeContent">%s</span> <a class="siteButtonDark buttonSmall" href="%s">%s</a>',*/
				'<span class="noticeContent">%s</span> <a class="siteButtonDark buttonSmall" href="'.$main_subscription_parmalink.'">'.__('Versa quota associativa annuale', 'teatro-subscriptions').'</a>',
				esc_html__('No order has been made yet.', 'woocommerce'),
				$shop_permalink,
				esc_html__('Browse products', 'teatro-subscriptions')
			);
			wc_print_notice($notice_message, 'notice');
		endif;
	}
	else{
		global $wp_query;
		$wp_query->set_404();
		status_header( 404 );
		wp_redirect(wc_get_page_permalink( 'myaccount' ));
		exit();
	}
}
add_action('woocommerce_account_teatrosubscriptions_endpoint', 'teatrosubscriptions_content' );
/* Loader HTML at footer  === loaderEnabled */
add_action('wp_footer', 'loader_html_sub');
function loader_html_sub(){
	global $WC_custom_teatro_attributes; if(empty($WC_custom_teatro_attributes)){
	?><div class="site--loader-sm"><div class="loader-inner"></div></div><?php
	}
}
/* add muy custom schedule for testing cron functionality */
///add_filter('cron_schedules', 'my_plugin_add_cron_schedules_teatro');
function my_plugin_add_cron_schedules_teatro( $schedules ) {
    $schedules['every_5_minutes_teatro'] = array(
        'interval' => 60 * 5, // Every 5 minutes in seconds
        'display'  => __( 'Every 5 Minutes', 'teatro-subscriptions' )
    );
    return $schedules;
}
