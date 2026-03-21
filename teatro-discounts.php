<?php
/*
* Plugin Name:  Teatro Discounts
* Description: Teatro Discounts
* Version:           1.0.0
* Author: Shambix
* Author URI: https://www.shambix.com
@package           Woocommerce
*/

if(!class_exists('Teatro_discounts')):
class Teatro_discounts
{
	private $mypath=''; //private $user_subscription_status, $user_subscription_data;
	public function __construct() {
		$this->mypath = plugin_dir_url(__DIR__).'teatro-discounts/';
		add_action('plugins_loaded', array($this, 'init'));
		add_action('wp_enqueue_scripts', array($this, 'enqueue_teatro_script'));
		///ajax calls for subscription buy now  //isee_certificate
		//add_action('wp_ajax_teatro_subscription_buynow', array($this, 'teatro_subscription_buynow'));
		//add_action('wp_ajax_nopriv_teatro_subscription_buynow', array($this, 'teatro_subscription_buynow'));
		//add_action('wp_ajax_teatro_subscription_buyrenew', array($this, 'teatro_subscription_buyrenew'));
		//add_action('wp_ajax_nopriv_teatro_subscription_buyrenew', array($this, 'teatro_subscription_buyrenew'));
	}
	public function init(){		
		if(class_exists('WC_Integration')){		
			require_once 'teatro-discounts-wc.php';		 
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
	 /* COMMENTO per aggiunta logica sconto settimane consecutive 
	 public function getFeeAppliedArray(){ 
		$isee_discount=$this->validateUserProductEligibility(WC()->cart); //$this->printRData($isee_discount); 
		$sibiling_discount=$this->checkSD_EligibilityFromCart(WC()->cart); //$this->printRData($sibiling_discount); 
		$coupon_discount=$this->getAppliedCouponsIfAny(WC()->cart); //$this->printRData($coupon_discount); 
		switch(true):
			case (!empty($isee_discount['discount_amount']) and !empty($sibiling_discount['discount_amount'])):  
				if(($isee_discount['discount_amount']*100) >= ($sibiling_discount['discount_amount']*100)){ 						
					$return=[
						'isee'=>['label'=>$isee_discount['discount_label'],'amount'=>$isee_discount['discount_amount'],'status'=>true],
						'sibiling'=>['label'=>$sibiling_discount['discount_label'],'amount'=>$sibiling_discount['discount_amount'],'status'=>false]
					];
				} else { 				
					$return=[
						'isee'=>['label'=>$isee_discount['discount_label'],'amount'=>$isee_discount['discount_amount'],'status'=>false],
						'sibiling'=>['label'=>$sibiling_discount['discount_label'],'amount'=>$sibiling_discount['discount_amount'],'status'=>true]
					];
				}
			break;
			case (!empty($isee_discount['discount_amount']) and empty($sibiling_discount['discount_amount'])):  		
				$return=[
					'isee'=>['label'=>$isee_discount['discount_label'],'amount'=>$isee_discount['discount_amount'],'status'=>true],
					'sibiling'=>['label'=>$sibiling_discount['discount_label'],'amount'=>$sibiling_discount['discount_amount'],'status'=>false]
				];
			break;
			case (empty($isee_discount['discount_amount']) and !empty($sibiling_discount['discount_amount'])):  			
				$return=[
					'isee'=>['label'=>$isee_discount['discount_label'],'amount'=>$isee_discount['discount_amount'],'status'=>false],
					'sibiling'=>['label'=>$sibiling_discount['discount_label'],'amount'=>$sibiling_discount['discount_amount'],'status'=>true]
				];
			break;
			default: 				
				$return=[]; 
		endswitch; 
		return $return;
	 }	 
	/* FINE COMMENTO per aggiunta logica sconto settimane consecutive */
	
	/* INIZO COMMENTO PER INSERIRE NUOVA LOGICA CHE VALUTI IN BASE A TUTTI E TRE GLI SCONTI E NON SOLO TRA ISEE E FRATELLI */
	
	/* Nuova logica per sconto settimane consecutive 
	public function getFeeAppliedArray(){ 
    // 1. Mantieni la logica originale per ISEE e Fratelli
    $isee_discount=$this->validateUserProductEligibility(WC()->cart); 
    $sibiling_discount=$this->checkSD_EligibilityFromCart(WC()->cart); 
    
    // 2. Calcola il nuovo sconto (Sempre)
    $consecutive_discount = $this->checkConsecutiveDiscount(WC()->cart); 

    $return = [];

    // Logica originale per scegliere tra ISEE e Fratelli (il più vantaggioso)
    switch(true):
        case (!empty($isee_discount['discount_amount']) and !empty($sibiling_discount['discount_amount'])):  
            if(($isee_discount['discount_amount']*100) >= ($sibiling_discount['discount_amount']*100)){ 						
                $return['isee'] = ['label'=>$isee_discount['discount_label'],'amount'=>$isee_discount['discount_amount'],'status'=>true];
            } else { 				
                $return['sibiling'] = ['label'=>$sibiling_discount['discount_label'],'amount'=>$sibiling_discount['discount_amount'],'status'=>true];
            }
        break;
        case (!empty($isee_discount['discount_amount'])):  		
            $return['isee'] = ['label'=>$isee_discount['discount_label'],'amount'=>$isee_discount['discount_amount'],'status'=>true];
        break;
        case (!empty($sibiling_discount['discount_amount'])):  			
            $return['sibiling'] = ['label'=>$sibiling_discount['discount_label'],'amount'=>$sibiling_discount['discount_amount'],'status'=>true];
        break;
    endswitch; 

    // 3. AGGIUNTA: Applica SEMPRE lo sconto consecutivo se esiste un importo
    if(!empty($consecutive_discount['discount_amount']) && $consecutive_discount['discount_amount'] > 0){
        $return['consecutive'] = [
            'label'  => $consecutive_discount['discount_label'],
            'amount' => $consecutive_discount['discount_amount'],
            'status' => true
        ];
    }

    return $return;
} 
 /* FINE Nuova logica per sconto settimane consecutive */

 /* FINE COMMENTO PER INSERIRE NUOVA LOGICA CHE VALUTI IN BASE A TUTTI E TRE GLI SCONTI E NON SOLO TRA ISEE E FRATELLI */
	
/* Nuova logica: Sceglie il migliore tra ISEE, Fratelli e Settimane Consecutive */
public function getFeeAppliedArray(){ 
    // 1. Recupera i tre sconti potenziali
    $isee_discount = $this->validateUserProductEligibility(WC()->cart); 
    $sibiling_discount = $this->checkSD_EligibilityFromCart(WC()->cart); 
    $consecutive_discount = $this->checkConsecutiveDiscount(WC()->cart); 

    // Preparo i dati in un formato comparabile
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

    // 2. Se ci sono sconti disponibili, trova il maggiore
    if (!empty($discounts)) {
        $best_key = '';
        $max_amount = -1;

        foreach ($discounts as $key => $data) {
            // Usiamo * 100 per evitare problemi di precisione dei decimali nel confronto
            if (($data['amount'] * 100) > ($max_amount * 100)) {
                $max_amount = $data['amount'];
                $best_key = $key;
            }
        }

        // 3. Popola il return solo con lo sconto migliore
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
/* FINE Nuova logica */



	 /*  validate if user and product is eligible for ISEE discount */
	 public function validateUserEligibility(){
		 $current_user=wp_get_current_user();  
		 if(isset($current_user->roles) && is_array($current_user->roles) && in_array('parent', (array) $current_user->roles)){
			 return get_user_meta($current_user->ID, 'isee_certificate', true);
		 }
		 return false;
	 }
	 public function validateProductEligibilty($product_id=false, $subtotal=false){
		 if(!empty($product_id)): $isee=$this->validateUserEligibility();
			$isee_data=$this->getISEECertificateTeatro($isee); 
			$product=wc_get_product($product_id);
			if(!empty($isee_data['product_types']) && in_array($product->get_type(), $isee_data['product_types'])){
				if($isee_data['expire_date_timestamp'] > time()){					
					return ['discount_amount'=>($subtotal*$isee_data['discount'])/100, 'discount_label'=>$isee_data['discount_label']]; //strtoupper($isee).' Discount'
				}
			} 
		 endif;
	 }
	 public function validateUserProductEligibility($cart=false){
		 if(!empty($cart)): $discount_amount=0; $discount_label='';
			foreach($cart->get_cart() as $cart_item_key => $cart_item){ //$this->printRData($cart_item);
				$subtotal = $this->get_product_subtotal($cart_item['data'], $cart_item['quantity']);				
				$is_eligible = $this->validateProductEligibilty($cart_item['product_id'], $subtotal);
				if(!empty($is_eligible['discount_amount']) and !empty($is_eligible['discount_label'])){
					$discount_amount+=$is_eligible['discount_amount'];
					$discount_label=$is_eligible['discount_label'];
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
				$comparisonResults = array_merge($comparisonResults, $subComparisonResults);
			} else {				
				foreach ($array as $otherKey => $otherValue){				
					if ($key !== $otherKey){	
						$comparisonResults[]=($value === $otherValue)?$otherValue:'';
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
	public function getWeekDetailsFromOrders(){ //product_weeks_selected
		$args=['customer_id'=>get_current_user_id(),'limit'=>-1,'status'=>'completed'];	
		$orders = wc_get_orders($args); $return_weeks=$return_childs=[];
		if(!empty($orders)){ global $WC_custom_teatro_attributes;
			foreach($orders as $order): ///$this->printRData($order->get_items());
				foreach($order->get_items() as $order_items): ///$this->printRData($order_items);
					$s_week=$order_items->get_meta('product_weeks_selected'); //$this->printRData($s_week);
					$s_child=$order_items->get_meta('parent_childs_selected'); ///$this->printRData($s_child);
					$s_child_array=$WC_custom_teatro_attributes->extractMultipleSelections(!empty($s_child)?$s_child:[]);
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
			$discount_amount=$repeated_week_order=$subtotal_courses=0; $discount_label='Sconto fratelli '; 
			$cartData_wb=$cart_weeks=$cart_childs=[]; $repeated_woc=$this->getWeekDetailsFromOrders(); ///$this->printRData($repeated_woc);		
			///$this->printRData($repeated_week_orders_completed);	//die;
			foreach($cart->get_cart() as $cart_item_key => $cart_item){ 			///$this->printRData($cart_item);						
				if(!empty($cart_item['product_weeks_selected'])){ 
					$repeated_week_orders_completed=!empty($repeated_woc['weeks'])?$repeated_woc['weeks']:[];
					$repeated_child_orders_completed=!empty($repeated_woc['schds'])?$repeated_woc['schds']:[]; 
					///$this->printRData($repeated_child_orders_completed);	
					$pcs=$WC_custom_teatro_attributes->extractMultipleSelections($cart_item['parent_childs_selected']); //$this->printRData($pcs);	
					foreach($WC_custom_teatro_attributes->extractMultipleSelections($cart_item['product_weeks_selected']) as $k => $week): 
						///$this->printRData($pcs[$k]); $this->printRData($week);	 
						if(in_array($WC_custom_teatro_attributes->getReadableWeekString($week), $repeated_week_orders_completed) and !in_array($pcs[$k], $repeated_child_orders_completed)):
							if($cart_item['data']->get_type() == 'courses'){
								$subtotal_courses = $subtotal_courses + $cart_item['data']->get_regular_price();
							}
						endif;
					endforeach;				
				}
			}			
			$discount_amount=!empty($subtotal_courses)?$this->getSibilingDiscountAmount($subtotal_courses):0;
			return ['discount_amount'=>$discount_amount, 'discount_label'=>$discount_label];
		 endif;
	}

	/* INIZO COMMENTO PER CAMBIARE LA LOGICA IN BASE ALLE SETTIMANE E ALLO STORICO DEL CLIENTE */
	/* Sconto in base alle settimane consecutive o meno 

	public function checkConsecutiveDiscount($cart) {
    if (empty($cart)) return ['discount_amount' => 0, 'discount_label' => ''];

    global $WC_custom_teatro_attributes;
    $all_weeks_timestamps = [];
    $discount_amount = 0;
    $percentuale = 10; // Definisci qui la percentuale di sconto
    $label = "Sconto settimane consecutive";

    foreach ($cart->get_cart() as $item) {
        if (!empty($item['product_weeks_selected'])) {
            $weeks = $WC_custom_teatro_attributes->extractMultipleSelections($item['product_weeks_selected']);
            foreach ($weeks as $w) {
                // 1. Dividiamo la stringa "gg mm yyyy - gg mm yyyy" prendendo solo la prima parte
                $parts = explode('-', $w);
                $start_date_string = trim($parts[0]); // Otteniamo "21 July 2025"
                
                $ts = strtotime($start_date_string);
                
                if ($ts) {
                    $all_weeks_timestamps[] = [
                        'ts' => $ts, 
                        'price' => $item['data']->get_price()
                    ];
                }
            }
        }
    }

    // 2. Ordina cronologicamente
    usort($all_weeks_timestamps, function($a, $b) {
        return $a['ts'] - $b['ts'];
    });

    // 3. Verifica se le date di inizio distano 7 giorni (con tolleranza per ora legale)
    for ($i = 0; $i < count($all_weeks_timestamps) - 1; $i++) {
        $diff = abs($all_weeks_timestamps[$i+1]['ts'] - $all_weeks_timestamps[$i]['ts']);
        
        // Un intervallo tra 6 e 8 giorni (in secondi) gestisce ogni anomalia di fuso orario
        if ($diff >= 518400 && $diff <= 691200) {
            $discount_amount += ($all_weeks_timestamps[$i+1]['price'] * $percentuale) / 100;
            $i++; // Salta il compagno della coppia
        }
    }

    return [
        'discount_amount' => $discount_amount,
        'discount_label' => $label
    ];
}

/* FINE Sconto in base alle settimane consecutive o meno */
/* FINE COMMENTO PER CAMBIARE LA LOGICA IN BASE ALLE SETTIMANE E ALLO STORICO DEL CLIENTE */

public function checkConsecutiveDiscount($cart) {
    if (empty($cart)) return ['discount_amount' => 0, 'discount_label' => ''];

    global $WC_custom_teatro_attributes;
    
    // 1. Recuperiamo lo storico delle settimane già acquistate (ordini 'completed')
    $history = $this->getWeekDetailsFromOrders();
    $weeks_already_bought = count($history['weeks']); 

    $discount_amount = 0;
    $all_items_in_cart = [];

    // 2. Raccogliamo tutte le singole settimane presenti nel carrello
    foreach ($cart->get_cart() as $item) {
        if (!empty($item['product_weeks_selected'])) {
            $weeks = $WC_custom_teatro_attributes->extractMultipleSelections($item['product_weeks_selected']);
            
            // Usiamo il prezzo originale (regular price) per il calcolo
            $regular_price = (float)$item['data']->get_regular_price();

            foreach ($weeks as $w) {
                $all_items_in_cart[] = [
                    'price' => $regular_price,
                    'label' => $w
                ];
            }
        }
    }

    // Se non ci sono settimane nel carrello, restituiamo zero
    if (empty($all_items_in_cart)) return ['discount_amount' => 0, 'discount_label' => ''];

    // 3. Calcolo progressivo basato sulla posizione (storico + carrello attuale)
    foreach ($all_items_in_cart as $index => $item) {
        // La posizione determina la fascia di sconto
        $current_position = $weeks_already_bought + $index + 1;

       if ($current_position == 1) {
    continue; // Prima settimana: nessuno sconto fedeltà
} elseif ($current_position == 2) {
    $percentage = 15;
} elseif ($current_position == 3) {
    $percentage = 20;
} elseif ($current_position == 4) {
    $percentage = 25;
} else {
    // Dalla quinta settimana in poi
    $percentage = 30;
}

        // Sommiamo lo sconto calcolato sul prezzo originale
        $discount_amount += ($item['price'] * $percentage) / 100;
    }

    return [
        'discount_amount' => $discount_amount,
        'discount_label'  => 'Sconto fedeltà settimane'
    ];
}


	 /* Woocommerce function setup here due to not able to get raw price */
	 public function get_product_subtotal($product, $quantity){
		$price = $product->get_price(); 
		/*if($product->is_taxable()){ 
			if($product->display_prices_including_tax()){
				$row_price  = wc_get_price_including_tax($product, array('qty' => $quantity));
				$product_subtotal = wc_price($row_price);		 
				if(!wc_prices_include_tax() && $product->get_subtotal_tax() > 0){
					$product_subtotal .= ' <small class="tax_label">' . WC()->countries->inc_tax_or_vat() . '</small>';
				}
			} else {
				$row_price = wc_get_price_excluding_tax( $product, array( 'qty' => $quantity ) );
				$product_subtotal = wc_price( $row_price );		 
				if(wc_prices_include_tax() && $product->get_subtotal_tax() > 0 ) {
						$product_subtotal .= ' <small class="tax_label">' . WC()->countries->ex_tax_or_vat() . '</small>';
				}
			}
		} else { 
			$row_price = (int)$price * $quantity; 
			$product_subtotal = $row_price;  //wc_price( $row_price );
		} */
		return  sprintf("%.2f", (int)$price * $quantity);//apply_filters('woocommerce_cart_product_subtotal', $product_subtotal, $product, $quantity, $this);
	}
	
	/* Get ISEE certificate data === start */
	public function getISEECertificateTeatro($id=false){
		$isees=get_field('isee_settings','option');  
		if(!empty($isees)){ $all_isee_array=[];
			foreach($isees as $iseesa){
				if(!empty($id) and strtolower(trim($id)) == strtolower(trim($iseesa['certificate']))):
					$all_isee['certificate']=$iseesa['certificate'];
					$all_isee['discount']=$iseesa['discount'];
					$all_isee['discount_label']=$iseesa['discount_label'];
					$all_isee['product_types']=$this->getISEE_producttypes($iseesa['product_type']);
					$all_isee['expire_date_timestamp']=$this->getExpireDateISEE($iseesa['expire']);
					$all_isee['expire_date']=$this->getExpireDateISEE($iseesa['expire'],1);
					return $all_isee;
				else:
					array_push($all_isee_array, [
						'certificate'=>$iseesa['certificate'],
						'discount'=>$iseesa['discount'],
						'discount_label'=>$iseesa['discount_label'],
						'product_types'=>$this->getISEE_producttypes($iseesa['product_type']),
						'expire_date_timestamp'=>$this->getExpireDateISEE($iseesa['expire']),
						'expire_date'=>$this->getExpireDateISEE($iseesa['expire'],1)
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
			$year=$this->getYearByMonth($month_num);
			$date=$this->getMonthLastDate($month_num, $year);
			$timestamp=mktime(0,0,0, $month_num, $date, $year);
			// Use date_i18n so month names and date format are localized according to WP locale (e.g. Italian)
			return !empty($return)?date_i18n(get_option('date_format'), $timestamp):$timestamp;
		}
	}
	private function getYearByMonth($month=false){
		return (!empty($month) and $month <= date('m'))?date('Y')+1:date('Y');
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
					$cart_subtotal=WC()->cart->subtotal; 
					$amount=($cart_subtotal*$coupon->get_amount())/100;
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
		//return WC()->cart->get_coupons();
		if(count(WC()->cart->get_coupons()) > 0){ $return=[];
			foreach(WC()->cart->get_coupons() as $coupon) {
				$coupon_code[]=$coupon->get_code(); // Coupon code
				$coupon_amount[]=$coupon->get_amount(); // Coupon discount amount
				$return[] = [
					'coupon_amount'=>WC()->cart->get_coupon_discount_amount($coupon->get_code()),
					'coupon_code'=>$coupon->get_code(),
				];
			}	
			return $return;		
			//return ['coupon_amount'=>WC()->cart->get_coupon_discount_amount(end($coupon_code)),'coupon_code'=>end($coupon_code)];
		}	
	}
	
}
global $teatro_discounts;
$teatro_discounts=new Teatro_discounts();
endif;