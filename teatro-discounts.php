<?php
/*
* Plugin Name:  Teatro Discounts
* Description: Teatro Discounts
* Version:           1.0.1
* Author: Shambix
* Author URI: https://www.shambix.com
@package           Woocommerce
*
* Uprade by E3pr0m - https://www.e3pr0m.com
* Upgrade log: 
* MODIFICA v1.0.1 – Contatori ISEE
* Aggiunto il file teatro-isee-counter.php che gestisce i contatori per ogni scaglione ISEE.
* Il file teatro-isee-counter.php:
 - registra un contatore per ogni scaglione ISEE quando viene applicato lo sconto
 - blocca lo sconto quando il contatore raggiunge il tetto massimo
 - aggiunge un pannello admin "Sconti ISEE" nel menu di WordPress

if(!class_exists('Teatro_discounts')):
class Teatro_discounts
{
	private $mypath='';
	public function __construct() {
		$this->mypath = plugin_dir_url(__DIR__).'teatro-discounts/';
		add_action('plugins_loaded', array($this, 'init'));
		add_action('wp_enqueue_scripts', array($this, 'enqueue_teatro_script'));
	}

	public function init(){		
		if(class_exists('WC_Integration')){		
			require_once 'teatro-discounts-wc.php';
			/*
			 * ---------------------------------------------------------------
			 * MODIFICA v1.0.1 – Carica il modulo contatori ISEE
			 * ---------------------------------------------------------------
			 * Il file teatro-isee-counter.php gestisce:
			 *  - un contatore separato per ogni scaglione ISEE
			 *  - il blocco automatico dello sconto quando si raggiunge il tetto
			 *  - il pannello admin "Sconti ISEE" nel menu di WordPress
			 * ---------------------------------------------------------------
			 */
			require_once 'teatro-isee-counter.php';
		}

		// Translation
		load_plugin_textdomain( 'teatro-discounts', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' ); 
	}

	public function printRData($args=false){
		  echo '<pre>'; print_r($args); echo '</pre>';
	}

	public function enqueue_teatro_script(){
		wp_register_script('teatro-discounts-ajax-call', $this->mypath.'teatro-discounts.js', ['jquery']);
		wp_localize_script('teatro-discounts-ajax-call', 'teatro_discounts_ajax_call', ['ajaxurl'=>admin_url('admin-ajax.php')]);        
		wp_enqueue_script('teatro-discounts-ajax-call');		
	 }

	/* get fees apply array  */
	public function getAppliedCouponsIfAny($cart){
		if(!empty($cart->get_coupons())){ $return=[];
			foreach($cart->get_coupons() as $cp): 
				array_push($return, [
					'discount_amount'=>$this->calculateCouponDiscountAmount($cp),
					'discount_label'=>$cp->get_code()
				]);
			endforeach;
			return $return;
		}		 
	}

	/* Nuova logica: Sceglie il migliore tra ISEE, Fratelli e Settimane Consecutive */
	public function getFeeAppliedArray(){ 
	    $isee_discount       = $this->validateUserProductEligibility(WC()->cart); 
	    $sibiling_discount   = $this->checkSD_EligibilityFromCart(WC()->cart); 
	    $consecutive_discount = $this->checkConsecutiveDiscount(WC()->cart); 

	    $discounts = [];

	    if(!empty($isee_discount['discount_amount']) && $isee_discount['discount_amount'] > 0) {
	        $discounts['isee'] = [
	            'label'  => $isee_discount['discount_label'],
	            'amount' => (float)$isee_discount['discount_amount']
	        ];
	    }

	    if(!empty($sibiling_discount['discount_amount']) && $sibiling_discount['discount_amount'] > 0) {
	        $discounts['sibiling'] = [
	            'label'  => $sibiling_discount['discount_label'],
	            'amount' => (float)$sibiling_discount['discount_amount']
	        ];
	    }

	    if(!empty($consecutive_discount['discount_amount']) && $consecutive_discount['discount_amount'] > 0) {
	        $discounts['consecutive'] = [
	            'label'  => $consecutive_discount['discount_label'],
	            'amount' => (float)$consecutive_discount['discount_amount']
	        ];
	    }

	    $return = [];

	    if (!empty($discounts)) {
	        $best_key   = '';
	        $max_amount = -1;

	        foreach ($discounts as $key => $data) {
	            if (($data['amount'] * 100) > ($max_amount * 100)) {
	                $max_amount = $data['amount'];
	                $best_key   = $key;
	            }
	        }

	        if ($best_key !== '') {
	            $return[$best_key] = [
	                'label'  => $discounts[$best_key]['label'],
	                'amount' => $discounts[$best_key]['amount'],
	                'status' => true
	            ];
	        }
	    }

	    return $return;
	}

	/* validate if user eligible for ISEE discount */
	public function validateUserEligibility(){
		$current_user=wp_get_current_user();  
		if(isset($current_user->roles) && is_array($current_user->roles) && in_array('parent', (array) $current_user->roles)){
			return get_user_meta($current_user->ID, 'isee_certificate', true);
		}
		return false;
	}

	/*
	 * -----------------------------------------------------------------------
	 * MODIFICA v1.0.1 – validateProductEligibilty
	 * -----------------------------------------------------------------------
	 * Aggiunto apply_filters('teatro_isee_eligibility_result', $result, $isee)
	 * prima del return. Questo permette a teatro-isee-counter.php di:
	 *   1. Leggere quale certificato ISEE è in uso ($isee)
	 *   2. Verificare se il contatore di quel scaglione ha raggiunto il tetto
	 *   3. Se sì: azzerare discount_amount così lo sconto non viene applicato
	 *   4. Se no: restituire il risultato invariato
	 *
	 * Senza il filtro il contatore verrebbe registrato ma lo sconto
	 * non potrebbe mai essere bloccato automaticamente.
	 * -----------------------------------------------------------------------
	 */
	public function validateProductEligibilty($product_id=false, $subtotal=false){
		if(!empty($product_id)):
			$isee      = $this->validateUserEligibility();
			$isee_data = $this->getISEECertificateTeatro($isee); 
			$product   = wc_get_product($product_id);

			if(!empty($isee_data['product_types']) && in_array($product->get_type(), $isee_data['product_types'])){
				if($isee_data['expire_date_timestamp'] > time()){
					$result = [
						'discount_amount' => ($subtotal * $isee_data['discount']) / 100,
						'discount_label'  => $isee_data['discount_label']
					];
					/*
					 * Il filtro è il punto di aggancio per il modulo contatori.
					 * Se il tetto è raggiunto, il filtro restituisce discount_amount = 0
					 * e il messaggio viene mostrato nel carrello.
					 * Se il file teatro-isee-counter.php non è caricato il filtro
					 * non ha listener e restituisce $result invariato: nessun side effect.
					 */
					return apply_filters('teatro_isee_eligibility_result', $result, $isee);
				}
			} 
		endif;
	}

	public function validateUserProductEligibility($cart=false){
		if(!empty($cart)): $discount_amount=0; $discount_label='';
			foreach($cart->get_cart() as $cart_item_key => $cart_item){
				$subtotal    = $this->get_product_subtotal($cart_item['data'], $cart_item['quantity']);				
				$is_eligible = $this->validateProductEligibilty($cart_item['product_id'], $subtotal);
				if(!empty($is_eligible['discount_amount']) and !empty($is_eligible['discount_label'])){
					$discount_amount += $is_eligible['discount_amount'];
					$discount_label   = $is_eligible['discount_label'];
				}
			}
			return ['discount_amount'=>$discount_amount, 'discount_label'=>$discount_label];
		endif;
	}

	/* check if user eligible for sibiling discount */	 	
	private function getRepeatedValueFromArray($array){		
		$comparisonResults = array();		
		foreach ($array as $key => $value){			
			if(is_array($value)){				
				$subComparisonResults = $this->getRepeatedValueFromArray($value);	
				$comparisonResults    = array_merge($comparisonResults, $subComparisonResults);
			} else {				
				foreach ($array as $otherKey => $otherValue){				
					if ($key !== $otherKey){	
						$comparisonResults[] = ($value === $otherValue) ? $otherValue : '';
					}
				}
			}
		}
		return $comparisonResults;
	}

	private function getArrayValueNotEmpty($args=false){
		if(!empty($args)){ $return='';
			foreach($args as $argsa){
				if(!empty($argsa)){
					$return=$argsa; break;
				}
			}
			return $return;
		}
	}		

	public function getSibilingDiscountAmount($subtotal=0){
		if(!empty($subtotal)){ 
			$discount=get_field('sibling_discount','option'); 
			return ($subtotal*$discount)/100;
		}
	}	

	public function getWeekDetailsFromOrders(){
		$args=['customer_id'=>get_current_user_id(),'limit'=>-1,'status'=>'completed'];	
		$orders = wc_get_orders($args); $return_weeks=$return_childs=[];
		if(!empty($orders)){ global $WC_custom_teatro_attributes;
			foreach($orders as $order):
				foreach($order->get_items() as $order_items):
					$s_week  = $order_items->get_meta('product_weeks_selected');
					$s_child = $order_items->get_meta('parent_childs_selected');
					$s_child_array = $WC_custom_teatro_attributes->extractMultipleSelections(!empty($s_child)?$s_child:[]);
					if(!empty($s_week)): 
						foreach($WC_custom_teatro_attributes->extractMultipleSelections($s_week) as $k => $weeks):
							array_push($return_weeks, $WC_custom_teatro_attributes->getReadableWeekString($weeks));
							array_push($return_childs, !empty($s_child_array[$k])?$s_child_array[$k]:'');
						endforeach;
					endif;
				endforeach;
			endforeach;
		}
		return ['weeks'=>array_unique($return_weeks),'schds'=>array_unique($return_childs)];
	}

	public function checkSD_EligibilityFromCart($cart=false){
		if(!empty($cart)): global $WC_custom_teatro_attributes;
			$discount_amount=$subtotal_courses=0; $discount_label='Sconto fratelli '; 
			$repeated_woc = $this->getWeekDetailsFromOrders();
			foreach($cart->get_cart() as $cart_item_key => $cart_item){ 			
				if(!empty($cart_item['product_weeks_selected'])){ 
					$repeated_week_orders_completed  = !empty($repeated_woc['weeks']) ? $repeated_woc['weeks'] : [];
					$repeated_child_orders_completed = !empty($repeated_woc['schds']) ? $repeated_woc['schds'] : []; 
					$pcs = $WC_custom_teatro_attributes->extractMultipleSelections($cart_item['parent_childs_selected']);
					foreach($WC_custom_teatro_attributes->extractMultipleSelections($cart_item['product_weeks_selected']) as $k => $week): 
						if(
							in_array($WC_custom_teatro_attributes->getReadableWeekString($week), $repeated_week_orders_completed) &&
							!in_array($pcs[$k], $repeated_child_orders_completed)
						){
							if($cart_item['data']->get_type() == 'courses'){
								$subtotal_courses = $subtotal_courses + $cart_item['data']->get_regular_price();
							}
						}
					endforeach;				
				}
			}			
			$discount_amount = !empty($subtotal_courses) ? $this->getSibilingDiscountAmount($subtotal_courses) : 0;
			return ['discount_amount'=>$discount_amount, 'discount_label'=>$discount_label];
		endif;
	}

	public function checkConsecutiveDiscount($cart) {
	    if (empty($cart)) return ['discount_amount' => 0, 'discount_label' => ''];

	    global $WC_custom_teatro_attributes;
	    
	    $history            = $this->getWeekDetailsFromOrders();
	    $weeks_already_bought = count($history['weeks']); 

	    $discount_amount    = 0;
	    $all_items_in_cart  = [];

	    foreach ($cart->get_cart() as $item) {
	        if (!empty($item['product_weeks_selected'])) {
	            $weeks         = $WC_custom_teatro_attributes->extractMultipleSelections($item['product_weeks_selected']);
	            $regular_price = (float)$item['data']->get_regular_price();
	            foreach ($weeks as $w) {
	                $all_items_in_cart[] = ['price' => $regular_price, 'label' => $w];
	            }
	        }
	    }

	    if (empty($all_items_in_cart)) return ['discount_amount' => 0, 'discount_label' => ''];

	    foreach ($all_items_in_cart as $index => $item) {
	        $current_position = $weeks_already_bought + $index + 1;

	        if      ($current_position == 1) { continue; }
	        elseif  ($current_position == 2) { $percentage = 15; }
	        elseif  ($current_position == 3) { $percentage = 20; }
	        elseif  ($current_position == 4) { $percentage = 25; }
	        else                             { $percentage = 30; }

	        $discount_amount += ($item['price'] * $percentage) / 100;
	    }

	    return [
	        'discount_amount' => $discount_amount,
	        'discount_label'  => 'Sconto fedeltà settimane'
	    ];
	}

	/* Woocommerce function to get raw price */
	public function get_product_subtotal($product, $quantity){
		$price = $product->get_price(); 
		return sprintf("%.2f", (int)$price * $quantity);
	}
	
	/* Get ISEE certificate data === start */
	public function getISEECertificateTeatro($id=false){
		$isees=get_field('isee_settings','option');  
		if(!empty($isees)){ $all_isee_array=[];
			foreach($isees as $iseesa){
				if(!empty($id) and strtolower(trim($id)) == strtolower(trim($iseesa['certificate']))):
					$all_isee['certificate']          = $iseesa['certificate'];
					$all_isee['discount']             = $iseesa['discount'];
					$all_isee['discount_label']       = $iseesa['discount_label'];
					$all_isee['product_types']        = $this->getISEE_producttypes($iseesa['product_type']);
					$all_isee['expire_date_timestamp'] = $this->getExpireDateISEE($iseesa['expire']);
					$all_isee['expire_date']          = $this->getExpireDateISEE($iseesa['expire'],1);
					return $all_isee;
				else:
					array_push($all_isee_array, [
						'certificate'          => $iseesa['certificate'],
						'discount'             => $iseesa['discount'],
						'discount_label'       => $iseesa['discount_label'],
						'product_types'        => $this->getISEE_producttypes($iseesa['product_type']),
						'expire_date_timestamp'=> $this->getExpireDateISEE($iseesa['expire']),
						'expire_date'          => $this->getExpireDateISEE($iseesa['expire'],1)
					]);
				endif;
			}
			return $all_isee_array;
		}
	}	

	private function getISEE_producttypes($pt=false){
		if(!empty($pt)){ $return=[];
			foreach($pt as $pta):
				array_push($return, $pta->name);
			endforeach;
			return $return;
		}
	}
	
	private function getExpireDateISEE($month=false, $return=0){
		if(!empty($month)){ $month_num=date('m', strtotime($month));
			$year  = $this->getYearByMonth($month_num);
			$date  = $this->getMonthLastDate($month_num, $year);
			$timestamp = mktime(0,0,0, $month_num, $date, $year);
			return !empty($return) ? date_i18n(get_option('date_format'), $timestamp) : $timestamp;
		}
	}

	private function getYearByMonth($month=false){
		return (!empty($month) and $month <= date('m')) ? date('Y')+1 : date('Y');
	}

	private function getMonthLastDate($month=false, $year=false){
		if(!empty($month) and !empty($year)):
			switch($month):
				case 4: case 6: case 9: case 11: $days=30; break;
				case 2: $days=(date('L') == 1)?29:28; break;
				default: $days=31;
			endswitch;
			return $days;
		endif;
	}
	/* Get ISEE certificate data === end */
	
	/* Calculate coupon amount */
	public function calculateCouponDiscountAmount($coupon=false){	
		if(!empty($coupon->get_code())){ $amount=0;
			switch($coupon->get_discount_type()):
				case 'percent': 
					$cart_subtotal = WC()->cart->subtotal; 
					$amount        = ($cart_subtotal*$coupon->get_amount())/100;
				break;				
				default: $amount=$coupon->get_amount();
			endswitch;
			return $amount;
		}
	}	

	/* Choose max discount coupon from multiple coupons */
	public function getAppliedCouponDetails($cart=false){
		$applied_coupon=WC()->cart->get_applied_coupons(); $return=[];
		if(!empty($applied_coupon)){ 
			foreach($applied_coupon as $applied_coupona):
				$ac_object = new WC_Coupon($applied_coupona);
				$ac_amount = $this->calculateCouponDiscountAmount($ac_object); 	
				array_push($return, ['coupon_amount'=>$ac_amount,'coupon_code'=>$applied_coupona]);
			endforeach;		
		}
		return $return;
	}
	
	/* get applied coupon  */
	public function getAlreadyApplliedCoupons(){
		if(count(WC()->cart->get_coupons()) > 0){ $return=[];
			foreach(WC()->cart->get_coupons() as $coupon) {
				$coupon_code[]   = $coupon->get_code();
				$coupon_amount[] = $coupon->get_amount();
				$return[] = [
					'coupon_amount' => WC()->cart->get_coupon_discount_amount($coupon->get_code()),
					'coupon_code'   => $coupon->get_code(),
				];
			}	
			return $return;		
		}	
	}
	
}
global $teatro_discounts;
$teatro_discounts=new Teatro_discounts();
endif;
