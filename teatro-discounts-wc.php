<?php
/* Add ISEE discount if product & user eligible for discount  */
add_action('woocommerce_cart_calculate_fees', 'add_auto_isee_discount');
/* COMMENTO PER MODIFICA per sconto settimane consecutive 
	global $teatro_discounts; 
	$isee_discount=$teatro_discounts->validateUserProductEligibility($cart); //$teatro_discounts->printRData($isee_discount); 
	$sibiling_discount=$teatro_discounts->checkSD_EligibilityFromCart($cart); //$teatro_discounts->printRData($sibiling_discount); 
	switch(true):
		case (!empty($isee_discount['discount_amount']) and !empty($sibiling_discount['discount_amount'])): 
			if(($isee_discount['discount_amount']*100) >= ($sibiling_discount['discount_amount']*100)){ 
				$cart->add_fee(strtoupper($isee_discount['discount_label']), -$isee_discount['discount_amount'], true);
			} else { 
				$cart->add_fee(strtoupper($sibiling_discount['discount_label']), -$sibiling_discount['discount_amount'], true);
			}
		break;
		case (!empty($isee_discount['discount_amount']) and empty($sibiling_discount['discount_amount'])):  
			$cart->add_fee(strtoupper($isee_discount['discount_label']), -$isee_discount['discount_amount'], true);
		break;
		case (empty($isee_discount['discount_amount']) and !empty($sibiling_discount['discount_amount'])):  
			$cart->add_fee(strtoupper($sibiling_discount['discount_label']), -$sibiling_discount['discount_amount'], true);
		break;
		default: 
	endswitch;		
}
	/* Fine COMMENTO PER MODIFICA per sconto settimane consecutive */

/* inizio commento per sostituzione logica confronto tra isee e fratelli con aggiunta sconto settimane consecutive */

/*
function add_auto_isee_discount($cart) {
	global $teatro_discounts; 
	
	// 1. Calcolo degli sconti esistenti (ISEE e Fratelli)
	$isee_discount = $teatro_discounts->validateUserProductEligibility($cart); 
	$sibiling_discount = $teatro_discounts->checkSD_EligibilityFromCart($cart); 
	
	// 2. Calcolo del nuovo sconto SEMPRE attivo
	$consecutive_discount = $teatro_discounts->checkConsecutiveDiscount($cart);

	// Logica originale: Applica il migliore tra ISEE e Fratelli
	switch(true):
		case (!empty($isee_discount['discount_amount']) and !empty($sibiling_discount['discount_amount'])): 
			if(($isee_discount['discount_amount']*100) >= ($sibiling_discount['discount_amount']*100)){ 
				$cart->add_fee(strtoupper($isee_discount['discount_label']), -$isee_discount['discount_amount'], true);
			} else { 
				$cart->add_fee(strtoupper($sibiling_discount['discount_label']), -$sibiling_discount['discount_amount'], true);
			}
		break;
		case (!empty($isee_discount['discount_amount']) and empty($sibiling_discount['discount_amount'])):  
			$cart->add_fee(strtoupper($isee_discount['discount_label']), -$isee_discount['discount_amount'], true);
		break;
		case (empty($isee_discount['discount_amount']) and !empty($sibiling_discount['discount_amount'])):  
			$cart->add_fee(strtoupper($sibiling_discount['discount_label']), -$sibiling_discount['discount_amount'], true);
		break;
	endswitch;

	// 3. APPLICAZIONE SCONTO CONSECUTIVO (Fuori dallo switch per essere cumulabile)
	if (!empty($consecutive_discount['discount_amount']) && $consecutive_discount['discount_amount'] > 0) {
		$cart->add_fee(
			strtoupper($consecutive_discount['discount_label']), 
			-$consecutive_discount['discount_amount'], 
			true
		);
	}
}
*/ 
/* Fine commento per sostituzione logica confronto tra isee e fratelli con aggiunta sconto settimane consecutive  */
/* change invalid coupon message */
///add_filter('woocommerce_coupon_error', 'custom_coupon_error_message', 10, 3);

function add_auto_isee_discount($cart) {
    if (is_admin() && !defined('DOING_AJAX')) return;
    
    global $teatro_discounts; 
    
    // Recuperiamo l'array che contiene SOLO lo sconto migliore (logica già presente nel file 1)
    $applied_discounts = $teatro_discounts->getFeeAppliedArray(); 

    if (!empty($applied_discounts)) {
        foreach ($applied_discounts as $discount) {
            // Se lo sconto è attivo (status true) e ha un importo
            if ($discount['status'] && $discount['amount'] > 0) {
                $cart->add_fee(
                    strtoupper($discount['label']), 
                    -$discount['amount'], 
                    true
                );
            }
        }
    }
}

function custom_coupon_error_message($err, $err_code, $WC_Coupon){    
    switch($err_code){
		case $WC_Coupon::E_WC_COUPON_INVALID_FILTERED:
        $err = __('Max discount already applied', 'teatro');
    }
    return $err;
}
/* Validate if coupon is valid  */
add_filter('woocommerce_coupon_is_valid', 'custom_validate_coupon', 10, 2);
function custom_validate_coupon($is_valid, $coupon){    
	global $teatro_discounts; //$teatro_discounts->printRData($coupon); $teatro_discounts->printRData($is_valid);
	$coupon_discount=$teatro_discounts->calculateCouponDiscountAmount($coupon); //$teatro_discounts->printRData($coupon_discount);		
	$isee_discount=$teatro_discounts->validateUserProductEligibility(WC()->cart); 
	$sibiling_discount=$teatro_discounts->checkSD_EligibilityFromCart(WC()->cart);
	$cd=!empty($coupon_discount)?$coupon_discount*100:0; 
	$fd=!empty($isee_discount['discount_amount'])?$isee_discount['discount_amount']*100:0;		 
	$sd=!empty($sibiling_discount['discount_amount'])?$sibiling_discount['discount_amount']*100:0;	
	//$teatro_discounts->printRData($cd);
	//$teatro_discounts->printRData($fd);
	//	$teatro_discounts->printRData($sd);
	switch(true):
		case ($cd >= $fd && $cd >= $sd):  
			WC()->cart->add_fee(strtoupper($isee_discount['discount_label']), 0, true);
			WC()->cart->add_fee(strtoupper($sibiling_discount['discount_label']), 0, true);
			return true;
		break;
		case ($cd <= $fd && $fd <= $sd): 
			WC()->cart->add_fee(strtoupper($isee_discount['discount_label']), 0, true);
			WC()->cart->add_fee(strtoupper($sibiling_discount['discount_label']), $sibiling_discount['discount_amount'], true);
			return false;
		break;
		case ($cd <= $fd && $sd <= $fd): 
			WC()->cart->add_fee(strtoupper($isee_discount['discount_label']), $isee_discount['discount_amount'], true);
			WC()->cart->add_fee(strtoupper($sibiling_discount['discount_label']), 0, true);
			return false;
		break;
		case (empty($cd) && $sd <= $fd): 
			WC()->cart->add_fee(strtoupper($isee_discount['discount_label']), $isee_discount['discount_amount'], true);
			WC()->cart->add_fee(strtoupper($sibiling_discount['discount_label']), 0, true);
			return false;
		break;
		case (empty($cd) && $sd >= $fd): 
			WC()->cart->add_fee(strtoupper($isee_discount['discount_label']), 0, true);
			WC()->cart->add_fee(strtoupper($sibiling_discount['discount_label']), $sibiling_discount['discount_amount'], true);
			return false;
		break;
		case (empty($sd) && empty($fd) && !empty($cd)): 
			return true;
		break;
		default: return false;
	endswitch;
	/*if($cd > $fd){
		WC()->cart->add_fee(strtoupper($isee_discount['discount_label']), 0, true);
		WC()->cart->add_fee(strtoupper($sibiling_discount['discount_label']), 0, true);
		return true;
	} else {
		return false;
	}*/
}

/* Only apply one coupon at time */
add_action('woocommerce_before_calculate_totals', 'restrict_multiple_coupons');
function restrict_multiple_coupons($cart) {
    if (is_admin() && !defined('DOING_AJAX')){ return; }   
    $applied_coupons = $cart->get_applied_coupons();    
    if (count($applied_coupons) > 1) {       
        $first_coupon = array_shift($applied_coupons);
        foreach($applied_coupons as $coupon) {
            $cart->remove_coupon($coupon);
        }
        wc_add_notice(__('Only one coupon can be applied at a time.', 'teatro-discounts'), 'notice');
    }
}

/* remove deprecation waring or notice  */
// class WC_Custom_Coupon extends WC_Coupon {
    // public $sort;
    // public function __construct( $data = '' ) {
        // parent::__construct( $data );
        // $this->sort = ''; // Initialize the property
    // }    
// }
