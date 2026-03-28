<?php
/*
* Plugin Name:  Teatro Courses Multiple 
* Description: Teatro Courses with Multiple selection of weeks & buses with stops
* Text Domain: teatro-courses-buses
* Version: 1.0.0
* Author: Shambix
* Author URI: https://www.shambix.com
* Edit by: E3pr0m
* Author URI: https://www.e3pr0m.com
*/

if(!class_exists('WC_custom_teatro_attributes')):
class WC_custom_teatro_attributes
{
	private $mypath='';
	public function __construct() {
		$this->mypath = plugin_dir_url(__DIR__).'teatro-courses-buses/';
		add_action('plugins_loaded', array($this, 'init'));
		add_action('wp_enqueue_scripts', array($this, 'enqueue_teatro_script'));		
		///ajax calls for buses and routes dynamically
		add_action('wp_ajax_get_buses', array($this, 'get_buses_byweek'));
		add_action('wp_ajax_nopriv_get_buses', array($this, 'get_buses_byweek'));
		add_action('wp_ajax_get_stops', array($this, 'get_stops_bybus'));
		add_action('wp_ajax_nopriv_get_stops', array($this, 'get_stops_bybus'));
		
		/* frontend ajax calls*/
		add_action('wp_ajax_goto_2step', array($this, 'goto_2step'));
		add_action('wp_ajax_nopriv_goto_2step', array($this, 'goto_2step'));
		add_action('wp_ajax_goto_3step', array($this, 'goto_3step'));
		add_action('wp_ajax_nopriv_goto_3step', array($this, 'goto_3step'));
		add_action('wp_ajax_goto_addtocart', array($this, 'goto_addtocart'));
		add_action('wp_ajax_nopriv_goto_addtocart', array($this, 'goto_addtocart'));
	}
	public function init(){		
		if(class_exists('WC_Integration')) {		
			require_once 'teatro-wc-single.php';		 
		}

		// Translation
		load_plugin_textdomain( 'teatro-courses-buses', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' ); 
	}	  
	public function printRData($args=false){
		  echo '<pre>'; print_r($args); echo '</pre>';
	}	
	/* add javascript or jquery support to plugin */
	 public function enqueue_teatro_script(){
		wp_register_script('teatro-ajax-call', $this->mypath.'teatro-wc-script.js', ['jquery']);
		wp_localize_script('teatro-ajax-call', 'teatro_ajax_call', ['ajaxurl'=>admin_url('admin-ajax.php')]);        
		wp_enqueue_script('teatro-ajax-call');		
	 }	 
	 /* get available seats by week */
	 public function getAvailableSeatsbyWeek($week=false,$user=false){
		 $user=!empty($user)?$user:get_current_user_id(); $seats_booked=0;
		 if(!empty($week)): $orders=$this->getAllCompltedOrdersForCorses('weeks'); ///$this->printRData($week);
			$capacity=!empty($week['week_seats'])?$week['week_seats']:0;		//$this->printRData($orders);		
			$week_selected=(!empty($week['start_date']) and !empty($week['end_date']))?strtotime(str_replace('/','-',$week['start_date'])).'@@'.strtotime(str_replace('/','-',$week['end_date'])):'';
			if(!empty($orders)){
				foreach($orders as $order){ 
					foreach($this->extractMultipleSelections($order) as $ordera){
						$order_parts=explode('-', $ordera); //$this->printRData($order_parts);
						$order_week=(!empty($order_parts[0]) and !empty($order_parts[1]))?strtotime($order_parts[0]).'@@'.strtotime($order_parts[1]):'';
						if($order_week === $week_selected){
							$seats_booked+=1;
						}
					}					
				}
			} 	
		 endif; 
		 //return ($capacity-$seats_booked);
		 return ($capacity > $seats_booked)?($capacity-$seats_booked):0;
	 }
	 
	 public function getAllCompltedOrdersForCorses($rtype=false){
		 $order_status_array = wc_get_order_statuses(); unset($order_status_array['wc-checkout-draft']);
		 $args=['limit'=>-1,'status'=>array_keys($order_status_array)];	//$this->printRData($order_status_array);
		 $orders = wc_get_orders($args); $return=$return_weeks=$return_ids=[];
		if(!empty($orders)){ 
			foreach($orders as $order): 	//$this->printRData($order_count);
				foreach($order->get_items() as $order_items):  //$this->printRData($order_items);
					$s_week=$order_items->get_meta('product_weeks_selected'); //$this->printRData($s_week);
					$s_child=$order_items->get_meta('parent_childs_selected');
					if(!empty($s_week) and !empty($s_child)){
						array_push($return_ids, $order->get_id());
						array_push($return_weeks, $s_week);
					}
				endforeach;			
			endforeach;
		} //$this->printRData($return_weeks);
		switch($rtype):
			case 'weeks': $return=($return_weeks); break;
			default: $return=array_unique($return_ids);
		endswitch;
		return $return;
	 }
	 
	 /* Get course orders by customer  */
	 public function getMyCourseOrders($rtype=false){
		$order_status_array = wc_get_order_statuses(); unset($order_status_array['wc-checkout-draft']);
		$args=['customer_id'=>get_current_user_id(),'limit'=>-1,'status'=>array_keys($order_status_array)];			
		$orders = wc_get_orders($args); $return=$return_weeks=$return_ids=[];
		if(!empty($orders)){ 
			foreach($orders as $order): 	//$this->printRData($order_count);
				foreach($order->get_items() as $order_items): 
					$s_week=$order_items->get_meta('product_weeks_selected');
					$s_child=$order_items->get_meta('parent_childs_selected');
					if(!empty($s_week) and !empty($s_child)){
						array_push($return_ids, $order->get_id());
						array_push($return_weeks, $s_week);
					}
				endforeach;			
			endforeach;
		} //$this->printRData($rtype);
		switch($rtype):
			case 'weeks': $return=($return_weeks); break;
			default: $return=array_unique($return_ids);
		endswitch;
		return $return;
	 }
	 
	 /* create token */
	 private function createStepToken($array=false){
		 if(!empty($array)):			
			return base64_encode(implode('@@@', $array));
		 endif;
	 }
	 public function extractStepToken($string=false){
		 if(!empty($string)){
			 return explode('@@@', base64_decode($string));
		 }
	 }
	 /* ajax call for 2nd step */
	 public function goto_2step(){
		 if(!empty($_POST['action']) and $_POST['action'] == 'goto_2step'):
			if(!empty($_POST['selected_child']) and !empty($_POST['product'])):
				$return_url=add_query_arg(['step'=>2,'token'=>$this->createStepToken([$_POST['product'],$_POST['selected_child']])], sanitize_text_field($_POST['page_url']));
				wp_send_json(['error'=>false,'url'=>$return_url]);
			else:
				wp_send_json(['error'=>true,'message'=>'Required parameters missing, please refresh the page and try again']);
			endif;
		 else:
			wp_send_json(['error'=>true,'message'=>'invalid function callled ']);
		 endif;
		 exit;
	 }	 
	 /* ajax call for 3 step */
	 public function goto_3step(){
		if(!empty($_POST['action']) and $_POST['action'] == 'goto_3step'):
			if(!empty($_POST['selected_child']) and !empty($_POST['product']) and !empty($_POST['selected_week'])):
				$return_url=add_query_arg([
					'step'=>3,
					'token'=>$this->createStepToken([$_POST['product'],$_POST['selected_child'],$this->combineMultipleSelections($_POST['selected_week'])])], 
					sanitize_text_field($_POST['page_url'])
				);
				wp_send_json(['error'=>false,'url'=>$return_url]);
			else:
				wp_send_json(['error'=>true,'message'=>'Required parameters missing, please refresh the page and try again']);
			endif;
		else:
			wp_send_json(['error'=>true,'message'=>'invalid function callled ']);
		endif;
		exit;		  
	 }
	 public function getTimeStringWeeksArray($args=false, $wr=false){
		 if(!empty($args)){ $return=$count=[]; $c=1;
			 foreach($this->extractMultipleSelections($args) as $week):
				array_push($return, $this->getReadableWeekString($week));
				array_push($count, $c); $c++;
			 endforeach;
			 if(!empty($wr) and $wr='count'):
				return implode(',', $count);
			 else:
				return implode(',', $return);
			 endif;			
 		 }
	 }
	 public function extractMultipleSelections($args=false){
		 if(!empty($args)){
			 return explode('@@', $args);
		 }
	 }
	 public function combineMultipleSelections($args=false){
		 if(!empty($args) and is_array($args)){
			 return implode('@@', $args);
		 }
	 }
	 public function replaceSignWithPipe($args=false, $sign='@@'){
		 return !empty($args)?str_replace($sign,'|', str_replace('empty','#',$args)):'';
	 }
	 public function replaceSignWithComma($args=false, $sign='@@'){
		 return !empty($args)?str_replace($sign,', ', str_replace('empty','-',$args)):'';
	 }
	 public function getBusNameById($args=false){ 
		 if(!empty($args)){ $return=[];
			 foreach($this->extractMultipleSelections($args) as $bus){ 
				array_push($return, (trim($bus) != 'empty')?get_the_title($bus):'-');
			 }			
			 return implode(', ', $return);
		 }
	 }
	 /* ajax add to cart  */
	 private function getBusStopsArray($args=false, $week=false){ //$this->printRData($args);
		 if(!empty($args) and !empty($week)){ $return=$bus=$stop=$start=$end=[];
			$weeks=$this->extractMultipleSelections($week); 
			foreach($weeks as $val): 		
				if(!empty($val)){  ///$this->printRData($this->getTimeStringWeeksArray($val));
					if(!empty($args[$this->getTimeStringWeeksArray($val)])){
						$bs=explode('-', $args[$this->getTimeStringWeeksArray($val)]); 
						array_push($bus, $bs[0]); array_push($stop, $bs[1]);
						array_push($start, $bs[2]); array_push($end, $bs[3]);
					} else {
						array_push($bus, 'empty'); array_push($stop, 'empty');
						array_push($start, 'empty'); array_push($end, 'empty');
					}					
				}				
			endforeach;
			return [
				'buses'=>$this->combineMultipleSelections($bus),
				'stops'=>$this->combineMultipleSelections($stop),
				'start_time'=>$this->combineMultipleSelections($start),
				'end_time'=>$this->combineMultipleSelections($end)
			];
		 }
	 }
	 public function goto_addtocart(){ 
		 if(!empty($_POST['action']) and $_POST['action'] == 'goto_addtocart'): global $woocommerce;
			if(!empty($_POST['selected_child']) and !empty($_POST['product']) and !empty($_POST['selected_week'])):	
				$post_sel_bus_stop = !empty($_POST['selected_bus_stop']) ? $_POST['selected_bus_stop'] : false;
				$sel_bus_stops=$this->getBusStopsArray($post_sel_bus_stop, $_POST['selected_week']);
				$product_id=sanitize_text_field($_POST['product']); $product_status=get_post_status($product_id); 
				$cart_item_data['product_weeks_selected']=sanitize_text_field($_POST['selected_week']);
				$cart_item_data['parent_childs_selected']=sanitize_text_field($_POST['selected_child']);				
				$cart_item_data['product_buses_selected']=!empty($sel_bus_stops['buses'])?sanitize_text_field($sel_bus_stops['buses']):'';
				$cart_item_data['product_bus_stops_selected']=!empty($sel_bus_stops['stops'])?sanitize_text_field($sel_bus_stops['stops']):'';
				$cart_item_data['product_bus_stop_start_time']=!empty($sel_bus_stops['start_time'])?sanitize_text_field($sel_bus_stops['start_time']):'';
				$cart_item_data['product_bus_stop_end_time']=!empty($sel_bus_stops['end_time'])?sanitize_text_field($sel_bus_stops['end_time']):'';				
				$passed_validation=apply_filters('woocommerce_add_to_cart_validation', true, $product_id, 1, '','', $cart_item_data);	
				$this->validateCourseProductCartQty($product_id, $cart_item_data);
				if($passed_validation && WC()->cart->add_to_cart($product_id,1,'','',$cart_item_data) && 'publish' === $product_status){				
					do_action('woocommerce_ajax_added_to_cart', $product_id); //WC_AJAX::get_refreshed_fragments(); 
					$data=['error'=>false,'url'=>wc_get_cart_url()]; wp_send_json($data);
				} else {
					$data=['error'=>true,'product_url'=>apply_filters('woocommerce_cart_redirect_after_error', get_permalink($product_id), $product_id)];
					wp_send_json($data);
				}	
			else:
				wp_send_json(['error'=>true,'message'=>'Required parameters missing, please refresh the page and try again']);
			endif;
		 else:
			wp_send_json(['error'=>true,'message'=>'invalid function callled ']);
		endif;
		exit;	
	 }

	/* New cart validation for "courses" type of products === Added on 08-04-2025  */
	public function validateCourseProductCartQty($product_id, $cart_item_data){ ///$this->printRData($product_id); $this->printRData($cart_item_data);
		if(!empty($product_id) and !empty($cart_item_data)){			
			if(sizeof(WC()->cart->get_cart()) > 0){ 
				foreach(WC()->cart->get_cart() as $cart_item_key => $cart_item){  ///$this->printRData($cart_item['product_weeks_selected']);
					if($cart_item['product_id'] == $product_id and $cart_item['parent_childs_selected'] == $cart_item_data['parent_childs_selected'] and $cart_item['product_weeks_selected'] == $cart_item_data['product_weeks_selected']){ 
						WC()->cart->remove_cart_item($cart_item_key); 
					} 				
				} 	 	
			} 
		}
	}
	 
	 /* ajax call handler function for buses  */
	 public function get_buses_byweek(){ 
		 if(!empty($_POST['action']) and $_POST['action'] == 'get_buses'):
			if(!empty($_POST['week']) and !empty($_POST['product'])):
				$data = $this->getBuses($_POST['product'], $_POST['week']);		
				wp_send_json(['error'=>0,'data'=>$data]);
			else:
				wp_send_json(['error'=>1,'message'=>'Required parameters missing, please refresh the page and try again']);			
			endif;
		 endif;
		 exit;
	 }
	 /* ajax call handler function for bus stops  */
	 public function get_stops_bybus(){ 
		 if(!empty($_POST['action']) and $_POST['action'] == 'get_stops'):
			if(!empty($_POST['bus']) and !empty($_POST['product'])):
				$data = $this->getBusStops($_POST['product'], $_POST['bus']);						
				echo json_encode(['error'=>0,'data'=>$data]);
			else:
				echo json_encode(['error'=>1,'message'=>'Required parameters missing, please refresh the page and try again']);
			endif;
		 endif;
		 exit;
	 }
	 
	/* get weeks custom data */
	public function getWeeks($id=false){
		$weeks = get_field('weeks', $id); $return=''; 
		if(!empty($weeks)){ $x=0; $return='<p class="product_weeks">'; 
			foreach($weeks as $week): $selected=($x==0)?'checked':'';
				$return.='<input type="radio" class="btn-check" name="product_weeks_selected" id="product_weeks_selected" value="'.$this->getForamttedDate($week['start_date']).' - '.$this->getForamttedDate($week['end_date']).'" autocomplete="off" '.$selected.'><label class="btn btn-outline-success" for="product_weeks_selected">'.$this->getForamttedDate($week['start_date']).'-'.$this->getForamttedDate($week['end_date']).'</label>';
			$x++; endforeach;
			$return.='</p>'; 
		}	
		return $return;
	}
	/* empty cart temp */
	public function emptyWCCartTemp(){
		global $woocommerce; $woocommerce->cart->empty_cart();
		//$woocommerce->cart->add_to_cart($product_id,$qty);
	}
	/* validate child age */
	private function getAgeByDOB($dob=false){
		if(!empty($dob)){
			// old - year and month validation
			/*
			$from=new DateTime($dob);
			$to=new DateTime('today');
			return $from->diff($to)->y;
			*/
			// new - year only validation
			$birth_year = date('Y', strtotime($dob));
			$current_year = date('Y');
			return $current_year - $birth_year;
		}
	}
	public function validateAgeGroup($dob=false, $pid=false){
		if(!empty($dob) and !empty($pid)){ $child_age=$this->getAgeByDOB($dob); ///$this->printRData($child_age);
			$ages=get_field('age_group', $pid); $age_grp=!empty($ages)?explode('-', $ages):''; ///$this->printRData($age_grp);
			if(!empty(trim($age_grp[0])) and !empty(trim($age_grp[1])) and !empty($child_age)){
				if($child_age >=  trim($age_grp[0]) && $child_age <= trim($age_grp[1])){
					return true;
				} 
				return false;
			}
			return false;
		}
		return false;
	}
	/* Get Age custom data */
	public function getAgeList($id=false){
		$ages = get_field('age_group', $id); $return=''; 
		if(!empty($ages)){ $x=0; $return='<p class="product_ages">'; 
			foreach($ages as $age): $selected=($x==0)?'checked':'';
				$return.='<input type="radio" class="btn-check" name="product_ages_selected" id="product_ages_selected" value="'.$age['age'].'" autocomplete="off" '.$selected.'><label class="btn btn-outline-success" for="product_ages_selected">'.$age['age'].'</label>';
			$x++; endforeach;
			$return.='</p>'; 
		}
		return $return;
	}
	/* Get Buses custom data  */
	public function getBusesDataByWeek($product=false, $week=false){
		if(!empty($product)){
			$buses=get_field('buses', $product); $weeks=get_field('weeks', $product); 	
			$week_default=!empty($weeks[0]['start_date'])?$this->getForamttedDate($weeks[0]['start_date']).'-'.$this->getForamttedDate($weeks[0]['end_date']):'';
			$week_selected=!empty($week)?$week:$week_default; $return=[];	
			if(!empty($buses)){
				foreach($buses as $bus): 
					if(!empty($bus->ID)):
						$seats=$this->getBusAvailability($bus->ID, $week_selected);
						//if(!empty($seats)):
							array_push($return, [
								'bus_id'=>$bus->ID,
								'bus_title'=>$bus->post_title,
								'seats'=>$seats
							]);
						//endif;
					endif;
				endforeach;
			}
			return $return;
		}
	}
	
	public function getBuses($id=false, $week=false){
		$buses = get_field('buses', $id); $return=''; $weeks=get_field('weeks', $id); 		
		$week_default=!empty($weeks[0]['start_date'])?$this->getForamttedDate($weeks[0]['start_date']).'-'.$this->getForamttedDate($weeks[0]['end_date']):'';
		$week_selected=!empty($week)?$week:$week_default; 		
		if(!empty($buses)){ $x=0; $return='<p class="product_buses">'; 
			foreach($buses as $bus): 
				if(!empty($bus->ID)):
					$selected=($x==0)?'checked':''; $seats=$this->getBusAvailability($bus->ID, $week_selected);
					$return.='<input type="radio" class="btn-check" name="product_buses_selected" id="product_buses_selected" value="'.$bus->ID.'" autocomplete="off" ><label class="btn btn-outline-success" for="product_buses_selected">'.$bus->post_title.' ('.$seats.')'.'</label>';
					$x++; 
				endif;
			endforeach;
			$return.='</p>'; 
		}
		return $return;
	}
	/* Get Stops based on the bus */
	public function getBusStops($product=false, $bus=false){

		//echo 'xxxxxx';
		$buses=get_field('buses', $product);
		$return='';
		$bus_default=!empty($buses[0]->ID)?$buses[0]->ID:'';
		$selected_bus=!empty($bus)?$bus:$bus_default;

		if(!empty($product) and !empty($selected_bus)):
			$stops=get_field('stops', $selected_bus);

			if(!empty($stops)){
				$x=0;
				$return='<p class="product_bus_stops">'; 

				foreach($stops as $stop): 

					$selected=($x==0)?'checked':''; 
					$return.='<input type="radio" class="btn-check" name="product_bus_stops_selected" id="product_bus_stops_selected" value="'.$stop['stop_name'].'" autocomplete="off" '.$selected.'><label class="btn btn-outline-success" for="product_bus_stops_selected">'.$stop['stop_name'].'</label>';
					$x++;
				endforeach;

				$return.='</p>'; 
			}
		endif;
		return $return;
	}
	/* week readable format */
	public function getReadableWeekString($week=false,$sign='-'){
		if(!empty($week)){
			$week_parts=explode('-', trim($week));			
			return  strtotime($week_parts[0]).$sign.strtotime($week_parts[1]);
		}
	}
	/* get available seats */
	public function getBusAvailability($bus=false, $week=false){
		if(!empty($bus)): 
			$alreadyBooked=$this->alreadyBookedSeats($bus, $week);
			$seats = get_field('seats_capacity', $bus);
			return ($seats-$alreadyBooked);
		endif;
	}
	public function alreadyBookedSeats($bus=false, $week=false){ 	
		if(!empty($bus) and !empty($week)){ $return=0;
			$booked=get_post_meta($bus, 'seats_booked'); 	
			if(!empty($booked[0])): $ws=$this->getReadableWeekString($week);
				foreach(unserialize($booked[0]) as $ab): 
					if($ab['week_id'] == $ws){
						$return++;
					}
				endforeach;
			endif;
			return $return;			
		}
	}
	public function bookBusSeat($args=false){ //$this->printRData($args);
		if(!empty($args['bus']) and !empty($args['book_data'])){
			foreach($this->extractMultipleSelections($args['bus']) as $bus){
				if($bus != 'empty'){
					$abooked=get_post_meta($bus, 'seats_booked'); 
					$abooked_array=!empty($abooked[0])?unserialize($abooked[0]):[]; 
					$validOrder=$this->validateOrderAlreadySaved($args['book_data']['order_id'], $abooked_array);
					if(empty($validOrder)){
						array_push($abooked_array, $args['book_data']); 
						update_post_meta($bus, 'seats_booked', serialize($abooked_array));				
					}
				}
			}		
		}
	}
	private function validateOrderAlreadySaved($order=false, $booked=false){
		if(!empty($order)){ $return=0;
			if(!empty($booked)){
				foreach($booked as $bookeda){
					if($bookeda['order_id'] == $order){
						$return=1;
					}
				}				
			} else { 
				$return=0;
			}
			return $return;
		} 
		return 2;
	}
	/* Get Childs of curent parent user */
	public function getChildSubAccounts($args=false){				
		$ch_data = getSubAccounts();	$x=0; $return = '';
		if(!empty($ch_data)): $return.=''; 
			foreach($ch_data as $child){ $selected=($x==0)?'checked':''; 
				$return.='<div class="radioOuter"><div class="radioWrap"><input type="radio" class="btn-check" name="parent_childs_selected" id="parent_childs_selected" value="'.$child->ID.'" autocomplete="off" '.$selected.'><label class="btn btn-outline-success" for="parent_childs_selected">'.get_user_by('id', $child->ID)->display_name.'</label><span class="checkmark"></span></div></div>';					
			$x++; } $return.=''; 
		endif;		
		return $return;
	}
	
	/* add query args to any url  */
	public function addQueryArgs($args=false, $url=false){
		
	}
	public function getQueryString($key=false){
		if(!empty($key)){
			return filter_input(INPUT_GET, $key);
		} 
		return 0;		
	}
	/*  get transalated date  */
	public function getTranslatedDateStringSE($date=false){
		if(!empty($date)){ $date_parts=explode('-', $date); //print_r($date_parts);
			$start=!empty($date_parts[0])?date_i18n(get_option('date_format'), strtotime(str_replace(' ', '-', trim($date_parts[0])))):'';
			$end=!empty($date_parts[1])?date_i18n(get_option('date_format'), strtotime(str_replace(' ', '-', trim($date_parts[1])))):'';
			return $start.' - '.$end;
		}
	}
	public function getTranslatedDateString($date=false){
		$formattedDate = DateTime::createFromFormat('d/m/Y', $date);
		if ($formattedDate) {
			return date_i18n(get_option('date_format'), $formattedDate->getTimestamp());
		}
	}
	public function getTranslatedDateStringMulti($date=false, $seprator=', '){ //print_r($date);	
		if(!empty($date)){  $return=[];
			foreach(explode('@@', $date) as $datec): //print_r($datec);		
				$date_parts=explode('-', $datec); //print_r($date_parts);			
				$start=!empty($date_parts[0])?date_i18n(get_option('date_format'), strtotime(str_replace(' ', '-', trim($date_parts[0])))):'';
				$end=!empty($date_parts[1])?date_i18n(get_option('date_format'), strtotime(str_replace(' ', '-', trim($date_parts[1])))):'';
				$return[]=$start.' - '.$end;
			endforeach;
			return implode($seprator, $return);
		}
	}
	/* get formatted date */
	public function getForamttedDate($date=false){
		if(!empty($date)){ $format=''; //'F j, Y';
			$wp_date = get_option('date_format'); 			
			return date(!empty($format)?$format:$wp_date, strtotime(str_replace('/','-', $date)));
		}	
	}
	/* get formatted time */
	public function getForamttedTime($time=false){
		if(!empty($time)){ 			
			$wp_time = get_option('time_format'); 
			return date($wp_time, strtotime($time));
		}	
	}
	
	
	
	  
}
global $WC_custom_teatro_attributes;
$WC_custom_teatro_attributes=new WC_custom_teatro_attributes();
endif;

