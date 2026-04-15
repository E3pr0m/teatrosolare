<?php
add_action('woocommerce_product_meta_start', 'getCBPageHTML_teatro');
function getCBPageHTML_teatro(){
	global $WC_custom_teatro_attributes; global $product; $pid = $product->get_id();		
	if($product->get_type() == 'courses' || $product->get_type() == 'courses-noisee'){
		$html=''; 
		$step=$WC_custom_teatro_attributes->getQueryString('step');
		$token=$WC_custom_teatro_attributes->getQueryString('token');
		switch($step):
			case 2: $html=getCBStep2HTML($pid, $token); break;
			case 3: $html=getCBStep3HTML($pid, $token);  break;
			default: $html=getCBStep1HTML($pid);
		endswitch;
		echo $html;
	}
}
function getCBStep1HTML($pid=false){	
	if(!empty($pid)): global $teatro_subscriptions; global $WC_custom_teatro_attributes; //$WC_custom_teatro_attributes->emptyWCCartTemp();
		$user = get_user_by( 'ID', get_current_user_id()); $username=($user)?$user->display_name:''; $pName=get_the_title($pid); 
		$html = '<div class="productStepTitle text-up">' . esc_html__('Iscrizione a ', 'teatro-courses-buses') . ' <span class="highlightText"> '.$pName.' </span> '. esc_html__(' per ', 'teatro-courses-buses') .' <span class="highlightText">'.$username .'</span></div>';
		if(get_current_user_id()): 			
			if(!empty(getSubAccounts())): $x=0; 
				$html.='<input type="hidden" name="teatro_cb_pid" value="'.$pid.'" />';
				$html.='<input type="hidden" name="teatro_cb_page_url" value="'.get_permalink().'" />';
				$html.='<div class="radioGroupWrap">';
				foreach(getSubAccounts() as $child){ $selected=($x==0)?'checked':''; 
					$child_dob=get_user_meta($child->ID,'child_dob', true); $is_product_req_sub=get_post_meta($pid, 'teatrosubscription_required', true);
					$ss=$teatro_subscriptions->getSubscriptionStatus($child->ID, $pid); ///$WC_custom_teatro_attributes->printRData($ss);
					$ag=$WC_custom_teatro_attributes->validateAgeGroup($child_dob, $pid);
					$sub_link=$teatro_subscriptions->getPrimarySubscriptionParmalink($child->ID);
					$disabled_ag = (!$ag) ? 'disabled' : '';
					$hide_ag = (!$ag) ? ' style="opacity: 0.1"' : '';
					$msg_ag = (!$ag) ? ' ' . esc_html__(' (Age not matched)', 'teatro-courses-buses') : '';

					$disabled=(strtolower($ss) != 'active')?'disabled':''; $hide=(strtolower($ss) != 'active')?' style="opacity: 0.1" ':''; 
					$msg=(strtolower($ss) != 'active')?''.esc_html__(' (Does not have subscription)', 'teatro-courses-buses').'':''; $msg_d=!empty($msg)?$msg:$msg_ag;	
					$sublink=(strtolower($ss) != 'active')?'<a style="color:#000" class="" href=\''.$sub_link.'\'>'.esc_html__('Subscribe Now', 'teatro-courses-buses').'</a>':'';
					if(!empty($is_product_req_sub) and $is_product_req_sub == strtolower(trim('tsreq'))){
						$html.='<div class="radioOuter"><div class="radioWrap"><input type="radio" class="btn-check" name="parent_childs_selected" id="parent_childs_selected" value="'.$child->ID.'" autocomplete="off" '.$disabled.' '.$disabled_ag.'><label class="btn btn-outline-success" for="parent_childs_selected">'.ucwords(get_user_by('id', $child->ID)->display_name).'</label><span class="checkmark" '.$hide.' '.$hide_ag.'></span>'.$msg_d.'</div></div>'.$sublink;
					} else {
						$html.='<div class="radioOuter"><div class="radioWrap"><input type="radio" class="btn-check" name="parent_childs_selected" id="parent_childs_selected" value="'.$child->ID.'" autocomplete="off" '.$disabled_ag.'><label class="btn btn-outline-success" for="parent_childs_selected">'.ucwords(get_user_by('id', $child->ID)->display_name).'</label><span class="checkmark" '.$hide_ag.'></span>'.$msg_ag.'</div></div>';	
					}				
				$x++; }
				$redirect_courselink = add_query_arg('redirect_to', urlencode(get_permalink()), wc_get_account_endpoint_url('add-child'));
				$html.='</div><div class="newProf">'.esc_html__('Do you want to add a new profile?', 'teatro-courses-buses').' <a href="'.$redirect_courselink.'">'.esc_html__('Click here', 'teatro-courses-buses').'</a></div>';
				$html.='<button class="add-to-cart" id="gotostep_two">'.esc_html__('continue', 'teatro-courses-buses').'</button>';
			else:
				$redirect_courselink = add_query_arg('redirect_to', urlencode(get_permalink()), wc_get_account_endpoint_url('add-child'));
				$html.='</div><div class="newProf">'.esc_html__('Do you want to add a new profile?', 'teatro-courses-buses').' <a href="'.$redirect_courselink.'">'.esc_html__('Click here', 'teatro-courses-buses').'</a></div>';				
			endif;				
		else:
			$redirect_productlink=add_query_arg('redirect_to', urlencode(get_permalink($pid)),home_url('mio-account'));
			$html .= esc_html__('Please login as parent to purchase this course', 'teatro-courses-buses') . ', <a class="siteButton courses_cta" href="' . $redirect_productlink . '">' . esc_html__('click here to login', 'teatro-courses-buses') . '</a>';
		endif;
		return $html;
	endif;
}
function getCBStep2HTML($pid=false, $token=false){
	if(!empty($pid) and !empty($token)): global $WC_custom_teatro_attributes;  $html = "";
		$token_data = $WC_custom_teatro_attributes->extractStepToken($token); //$WC_custom_teatro_attributes->printRData($token_data);		
		if(!empty(the_content())){
			$html.='<div class="productSummary">'.the_content().'</div>';
		}
		if(!empty($token_data[0]) and $token_data[0] == $pid){  
			$weeks=get_field('weeks', $pid);	$pName=get_the_title($pid); 	 
			$user = get_user_by( 'ID', $token_data[1]); $username=($user)?$user->display_name:''; 
			$html.='<input type="hidden" name="teatro_cb_pid" value="'.$pid.'" />';
			$html.='<input type="hidden" name="teatro_cb_selchild" value="'.$token_data[1].'" />';
			$html.='<input type="hidden" name="teatro_cb_page_url" value="'.get_permalink().'" />';

			// $html.=' <div class="productStepTitle">STAI ACQUISTANDO IL PRODOTTO <span class="highlightText">CAMPI SOLARI</span> PER: <span class="highlightText">'.ucwords(get_user_by('id', $token_data[1])->display_name).'</span></div>'; //get_the_title()
			
			//$html.= '<div class="productStepTitle text-up">' . esc_html__('Registration for Solar Camps for ', 'teatro-courses-buses') . '<span class="highlightText">'.ucwords(get_user_by('id', $token_data[1])->display_name).'</span></div>';
			
			$html.= '<div class="productStepTitle text-up">'.esc_html__('Iscrizione a ', 'teatro-courses-buses').' <span class="highlightText"> '.$pName.' </span> '.esc_html__(' per ', 'teatro-courses-buses').' <span class="highlightText">'.ucwords($username).'</span></div>';

			$html.='<div class="checkBoxGroupWrap"><div class="radioGroupTitle">'.esc_html__('Choose from the available weeks:', 'teatro-courses-buses').'</div>';
			if(!empty($weeks)){ $x=0; $unavailability = 0; 
				foreach($weeks as $week): $selected=($x==0)?'checked':''; 
					$seats = $WC_custom_teatro_attributes->getAvailableSeatsbyWeek($week, false, $pid); ///$WC_custom_teatro_attributes->printRData($seats);
					if($seats == 0){ $unavailability++; }
					$disabled_seats=(empty($seats) || $seats <= 0)?'disabled':'';
					$hide_seats=(empty($seats) || $seats <= 0)?' style="opacity: 0.1" ':'';
					$week_desc = !empty($week['week_description']) ? ' ('.esc_html($week['week_description']).')' : '';
					$html.='<div class="checkBoxOuter"><div class="checkBoxWrap"><input type="checkbox" class="btn-check" name="product_weeks_selected[]" id="product_weeks_selected_'.$x.'" value="'.$WC_custom_teatro_attributes->getForamttedDate($week['start_date']).' - '.$WC_custom_teatro_attributes->getForamttedDate($week['end_date']).'" autocomplete="off" '.$disabled_seats.' '.$hide_seats.'><label class="btn btn-outline-success" for="product_weeks_selected_'.$x.'">'.$WC_custom_teatro_attributes->getTranslatedDateString($week['start_date']).'-'.$WC_custom_teatro_attributes->getTranslatedDateString($week['end_date']).$week_desc.' ['.$seats.' '.esc_html__('available seats', 'teatro-courses-buses').']</label><span class="checkmark" '.$disabled_seats.' '.$hide_seats.'></span></div></div>';
					$x++;
				endforeach;
			}
			$html.='</div>';
		}

		if($unavailability > 0){
			$html.= '<div class="course_unavailability">' . __('If the week you were looking for is not available, <a href="mailto:info@teatrosolare.it">contact us</a> to be added to the waiting list.', 'teatro-courses-buses') . '</div>';
		}

		$html.='<button class="add-to-cart" id="gotostep_three">'.esc_html__('Continue', 'teatro-courses-buses').'</button>';
		return $html;
	endif;
}
function getCBStep3HTML($pid=false, $token=false){
	if(!empty($pid) and !empty($token)): global $WC_custom_teatro_attributes; $html = "";
		$token_data=$WC_custom_teatro_attributes->extractStepToken($token);  ///$WC_custom_teatro_attributes->printRData($token_data);		
		if(!empty(the_content())){
			$html.='<div class="productSummary">'.the_content().'</div>';
		}
		if(!empty($token_data[0]) and $token_data[0] == $pid){ 
			$pName=get_the_title($pid); 	 $user = get_user_by( 'ID', $token_data[1]); $username=($user)?$user->display_name:''; 
			$html.='<input type="hidden" name="teatro_cb_pid" value="'.$pid.'" />';
			$html.='<input type="hidden" name="teatro_cb_selchild" value="'.$token_data[1].'" />';
			$html.='<input type="hidden" name="teatro_cb_selweek" value="'.$token_data[2].'" />';
			$html.='<input type="hidden" name="teatro_cb_page_url" value="'.get_permalink().'" />';
			$html.='<input type="hidden" name="teatro_timestring_array" value="'.$WC_custom_teatro_attributes->getTimeStringWeeksArray($token_data[2]).'" />';
			$html.='<input type="hidden" name="teatro_bc_array" value="'.$WC_custom_teatro_attributes->getTimeStringWeeksArray($token_data[2],'count').'" />';
			
			//$html.= '<div class="productStepTitle text-up">' . esc_html__('Registration for Solar Camps for ', 'teatro-courses-buses') . '<span class="highlightText">'. get_the_title() .'</span>'. esc_html__(' For ', 'teatro-courses-buses') . '<span class="highlightText">'.ucwords(get_user_by('id', $token_data[1])->display_name).'</span></div>';
			
			//$html.= '<div class="productStepTitle text-up">' . esc_html__('Registration for for ', 'teatro-courses-buses') . '<span class="highlightText">'.ucwords(get_user_by('id', $token_data[1])->display_name).'</span></div>';
			$html.= '<div class="productStepTitle text-up">' . esc_html__('Iscrizione a ', 'teatro-courses-buses') . ' <span class="highlightText"> '.$pName.' </span> '. esc_html__(' per ', 'teatro-courses-buses') .' <span class="highlightText">'.ucwords($username).'</span></div>';
			
			$html.= '<div class="radioTitleBus">'.esc_html__('Buses', 'teatro-courses-buses').'</div>';
			$bus_count = 0;

			foreach($WC_custom_teatro_attributes->extractMultipleSelections($token_data[2]) as $week):

				$html.= '<div class="radioTitleBus">'.$WC_custom_teatro_attributes->getTranslatedDateStringSE($week).'</div>';
				$buses = $WC_custom_teatro_attributes->getBusesDataByWeek($pid, $week); //$WC_custom_teatro_attributes->printRData($buses);
				$week_bus_availability = 0;
				foreach($buses as $bus){
					if(!empty($bus['seats']) && intval($bus['seats']) > 0) {
						$week_bus_availability = 1;
						break;
					}
				}
				$disabled_seats = ($week_bus_availability == 0)?'disabled':'';
				$hide_seats = ($week_bus_availability == 0)?' style="opacity: 0.1" ':'';
				$checked_yes = ($week_bus_availability == 0)?'': 'checked';
				$checked_no = ($week_bus_availability == 0)?'checked': '';

					if(!empty($buses)){
				$html.='<div class="radioGroupWrap">';
				$html.='<div class="radioOuter"><div class="radioWrap"><input type="radio" class="btn-check" name="product_bus_option_'.$bus_count.'" id="product_bus_option_yes_'.$bus_count.'" value="bus" autocomplete="off" data-bus="'.$bus_count.'" '.$disabled_seats.' '.$checked_yes.'><label for="product_bus_option_yes_'.$bus_count.'">'.esc_html__('Con il pulmino (scelta consigliata)','teatro-courses-buses').'</label><span class="checkmark" '.$disabled_seats.$hide_seats.'></span> </div></div>';

				$html.='<div class="radioOuter"><div class="radioWrap"><input type="radio" class="btn-check" name="product_bus_option_'.$bus_count.'" id="product_bus_option_no_'.$bus_count.'" value="no_bus" autocomplete="off" data-bus="'.$bus_count.'" '.$checked_no.'><label for="product_bus_option_no_'.$bus_count.'">'.esc_html__('Autonomo','teatro-courses-buses').'</label><span class="checkmark"></span> </div></div>';
					$html.='</div>';
					$bus_list_hidden = ($week_bus_availability == 0) ? 'no_stop_selected' : '';
					$html.='<div class="radioGroupWrap '.$bus_list_hidden.' bus_list" data-bus-list="'.$bus_count.'">';
					foreach($buses as $bus):
						//$WC_custom_teatro_attributes->printRData($bus);
						// $bus['seats'] è già la disponibilità corretta per questa settimana (da getBusesDataByWeek con $week)
						$disabled_seats = ($bus['seats'] == 0)?'disabled':'';
						$hide_seats = ($bus['seats'] == 0)?' style="opacity: 0.1" ':'';						
						
						if(!empty($bus['seats'])){ 
							$html.='<div class="radioGroupTitle busName1">'.$bus['bus_title'].' ('.$bus['seats'].' '.esc_html__('posti rimanenti', 'teatro-courses-buses').')'.'</div>';		
						}		
						$stops = get_field('stops', $bus['bus_id']); //$WC_custom_teatro_attributes->printRData($bus);
						//$WC_custom_teatro_attributes->printRData($stops);

						if(!empty($stops)){ 
							foreach($stops as $stop):

								//echo $stop['bus_route_start_time']; echo $stop['bus_route_end_time'];		//['.$WC_custom_teatro_attributes->getReadableWeekString($week).']					
								$html.='<div class="radioOuter"><div class="radioWrap"><input type="radio" class="btn-check" name="product_bus_stops_selected['.$WC_custom_teatro_attributes->getReadableWeekString($week).']" id="product_bus_stops_selected" value="'.$bus['bus_id'].'-'.$stop['stop_name'].'-'.$stop['bus_route_start_time'].'-'.$stop['bus_route_end_time'].'" autocomplete="off" '.$disabled_seats.'><label class="btn btn-outline-success" for="product_bus_stops_selected">'.$stop['stop_name'].' [Andata: '.$stop['bus_route_start_time'].' & Ritorno: '.$stop['bus_route_end_time'].' ] </label><span class="checkmark" '.$disabled_seats.$hide_seats.'></span> </div></div>';
							endforeach;
						} 					
						// $html.='</div>';
					endforeach;		
				} else {
					$html.='<div class="radioGroupWrap">';
						$html.='<div class="radioOuter"><div class="radioWrap"><input type="radio" class="btn-check" name="product_bus_option_'.$bus_count.'" id="product_bus_option_no_'.$bus_count.'" value="no_bus" autocomplete="off" data-bus="'.$bus_count.'" checked><label for="product_bus_option_no_'.$bus_count.'">'.esc_html__('Autonomo','teatro-courses-buses').'</label><span class="checkmark"></span> </div></div>';
					//$html .= '<div class="course_unavailability">' . __('Nessun autobus associato a questo corso', 'teatro-courses-buses') . '</div>';
				}
				$html.='</div>';
				$bus_count++;
			endforeach;	
			$html.='</div>';			
			
		}

		if($week_bus_availability == 0 && !empty($buses)){
			$html .= '<div class="course_unavailability">' . __('If the bus is not available, <a href="mailto:info@teatrosolare.it">contact us</a> to be added to the waiting list.', 'teatro-courses-buses') . '</div>';
		}
		$html.='<div class="alerts3_dnone" id="alert_step3_cb"></div>';
		$html.='<button class="add-to-cart" id="gotostep_addtocart">'.esc_html__('Continue', 'teatro-courses-buses').'</button>';
		return $html;
	endif;
}

/* change product price based on week selection */
add_action('woocommerce_before_calculate_totals', 'update_price_based_on_week');
function update_price_based_on_week($cart){   global $WC_custom_teatro_attributes;
    foreach($cart->cart_contents as $key => $value){ //$WC_custom_teatro_attributes->printRData($value);
      if(!empty($value['product_weeks_selected'])){
		  $weeks=$WC_custom_teatro_attributes->extractMultipleSelections($value['product_weeks_selected']);
		  $new_price = !empty($weeks)?count($weeks)*$value['data']->get_price():$value['data']->get_price();
		  $value['data']->set_price($new_price);
	  }        
    }
}

/* Add step buttons before the product title */
add_action('woocommerce_single_product_summary', 'teatro_bc_add_steps_counter_before_product_title',5); 
function teatro_bc_add_steps_counter_before_product_title(){  
   global $WC_custom_teatro_attributes; global $product;
   if($product->get_type() == 'courses' || $product->get_type() == 'courses-noisee'){
	   $step1=$WC_custom_teatro_attributes->getQueryString('step');
	   $step=!empty($step1)?$step1:1;
	   $html='<ul class="unstyledList productCheckMark">';
	   for($i=1;$i<=3;$i++){
		   $active=($i == $step)?'active':'';
		   $html.='<li class="'.$active.'">'.$i.'</li>';
	   }   
	   $html.='</ul>'; 
	   echo $html;
   }
}

add_filter('woocommerce_post_class', 'filter_woocommerce_post_class', 10, 2);
function filter_woocommerce_post_class($classes, $product){
	if(!is_product()) return $classes;
	$classes[] = 'single-product';
	return $classes;
}

add_filter('woocommerce_is_purchasable', 'hideordeactive_addtocart');
function hideordeactive_addtocart($is_purchasable){
	global $WC_custom_teatro_attributes; $childs=$WC_custom_teatro_attributes->getChildSubAccounts('childs');
	if(!empty($childs)){
		return true;
	}
	return true;
	//return false;
}
/* Get Cart item data */
add_filter('woocommerce_get_item_data', 'display_cart_item_custom_meta_data', 10, 2);
function display_cart_item_custom_meta_data($item_data, $cart_item) { 	
	global $WC_custom_teatro_attributes; $sweeks=!empty($cart_item['product_weeks_selected'])?$WC_custom_teatro_attributes->getTranslatedDateStringMulti($cart_item['product_weeks_selected'],'|'):[]; $stops=!empty($cart_item['product_bus_stops_selected'])?$WC_custom_teatro_attributes->replaceSignWithPipe($cart_item['product_bus_stops_selected']):[]; $bs_startime=!empty($cart_item['product_bus_stop_start_time'])?$WC_custom_teatro_attributes->replaceSignWithPipe($cart_item['product_bus_stop_start_time']):[]; $bs_endtime=!empty($cart_item['product_bus_stop_end_time'])?$WC_custom_teatro_attributes->replaceSignWithPipe($cart_item['product_bus_stop_end_time']):[];
	if(isset($cart_item['parent_childs_selected'])) {
        $item_data[] = array(
            'key'       => 'Child ',
            'value'     => get_user_by('id', $cart_item['parent_childs_selected'])->display_name,
        );
    }	
	if(!empty($sweeks)){ $stops_array=!empty($stops)?explode('|', $stops):'';
		$bs_startime_array=!empty($bs_startime)?explode('|', $bs_startime):'';
		$bs_endtime_array=!empty($bs_endtime)?explode('|', $bs_endtime):'';
		foreach(explode('|', $sweeks) as $key => $sweek){ //$WC_custom_teatro_attributes->printRData($sweek);
			$item_data[] = array('key'=>'Week ','value'=> $sweek); 
			$item_data[] = array('key'=>'Bus Stop ','value'=> (!empty($stops_array[$key]) and $stops_array[$key] != '#')?$stops_array[$key]:'');
			$item_bs_start=(!empty($bs_startime_array[$key]) and $bs_startime_array[$key] != '#')?$bs_startime_array[$key]:'';
			$item_bs_end=(!empty($bs_endtime_array[$key]) and $bs_endtime_array[$key] != '#')?$bs_endtime_array[$key]:'';
			$item_data[] = array('key'=>'Bus Stop Start - End Time ','value'=> (!empty($item_bs_start) and $item_bs_start != '#')?$item_bs_start.' - '.$item_bs_end:''); 
		}		
	}
	
   /* if(isset($cart_item['product_weeks_selected'])) {
        $item_data[] = array(
            'key'       => 'Week ',
            'value'     => $WC_custom_teatro_attributes->getTranslatedDateStringMulti($cart_item['product_weeks_selected']),
        );
    }
	if(isset($cart_item['product_ages_selected'])) {
        $item_data[] = array(
            'key'       => 'Age ',
            'value'     => $cart_item['product_ages_selected'],
        );
    }
	if(isset($cart_item['product_buses_selected'])) {
        $item_data[] = array(
            'key'       => 'Bus ',
            'value'     => !empty($cart_item['product_buses_selected'])?get_the_title($cart_item['product_buses_selected']):'',
        );
    }
	if(isset($cart_item['product_bus_stops_selected'])) {
        $item_data[] = array(
            'key'       => 'Bus Stop ',
            'value'     => $WC_custom_teatro_attributes->replaceSignWithComma($cart_item['product_bus_stops_selected']),
        );
    }
	if(isset($cart_item['product_bus_stop_start_time'])) {
        $item_data[] = array(
            'key'       => 'Bus Stop Start Time',
            'value'     => $WC_custom_teatro_attributes->replaceSignWithComma($cart_item['product_bus_stop_start_time']),
        );
    }
	if(isset($cart_item['product_bus_stop_end_time'])) {
        $item_data[] = array(
            'key'       => 'Bus Stop End Time',
            'value'     => $WC_custom_teatro_attributes->replaceSignWithComma($cart_item['product_bus_stop_end_time']),
        );
    }
	*/
    return $item_data;
}
/* Add custom data to order line items */
add_action('woocommerce_checkout_create_order_line_item', 'save_cart_item_custom_meta_as_order_item_meta', 10, 4);
function save_cart_item_custom_meta_as_order_item_meta($item, $cart_item_key, $values, $order) {    
    if(isset($values['product_weeks_selected'])){
        $item->update_meta_data('product_weeks_selected', $values['product_weeks_selected']);
    }
	if(isset($values['product_ages_selected'])){
        $item->update_meta_data('product_ages_selected', $values['product_ages_selected']);
    }
	if(isset($values['product_buses_selected'])){
        $item->update_meta_data('product_buses_selected', $values['product_buses_selected']);
    }
	if(isset($values['product_bus_stop_start_time'])){
        $item->update_meta_data('product_bus_stop_start_time', $values['product_bus_stop_start_time']);
    }
	if(isset($values['product_bus_stop_end_time'])){
        $item->update_meta_data('product_bus_stop_end_time', $values['product_bus_stop_end_time']);
    }
	if(isset($values['product_bus_stops_selected'])){
        $item->update_meta_data('product_bus_stops_selected', $values['product_bus_stops_selected']);
    }
	if(isset($values['parent_childs_selected'])){
        $item->update_meta_data('parent_childs_selected', $values['parent_childs_selected']);
    }
}
/* Add custom cart item data */
add_filter('woocommerce_add_cart_item_data', 'wk_add_cart_item_data', 10, 3);
function wk_add_cart_item_data($cart_item_data, $product_id, $variation_id) {
    if(isset($_POST['product_weeks_selected'])) {
        $cart_item_data['product_weeks_selected']=sanitize_text_field($_POST['product_weeks_selected']);
    }
	if(isset($_POST['product_ages_selected'])) {
        $cart_item_data['product_ages_selected']=sanitize_text_field($_POST['product_ages_selected']);
    }
	if(isset($_POST['product_buses_selected'])) {
        $cart_item_data['product_buses_selected']=sanitize_text_field($_POST['product_buses_selected']);
    }
	if(isset($_POST['product_bus_stops_selected'])) {
        $cart_item_data['product_bus_stops_selected']=sanitize_text_field($_POST['product_bus_stops_selected']);
    }
	if(isset($_POST['product_bus_stop_start_time'])) {
        $cart_item_data['product_bus_stop_start_time']=sanitize_text_field($_POST['product_bus_stop_start_time']);
    }
	if(isset($_POST['product_bus_stop_end_time'])) {
        $cart_item_data['product_bus_stop_end_time']=sanitize_text_field($_POST['product_bus_stop_end_time']);
    }
	if(isset($_POST['parent_childs_selected'])) {
        $cart_item_data['parent_childs_selected']=sanitize_text_field($_POST['parent_childs_selected']);
    }
    return $cart_item_data;
}

/* Change meta values in admin and  order details page === added on 09-04-2025 */
//add_filter('woocommerce_order_item_display_meta_key', 'hide_all_item_meta_keys', 10, 3);
function hide_all_item_meta_keys($display_key, $meta, $item) {  return '';  }
//add_filter('woocommerce_order_item_display_meta_value', 'hide_all_item_meta_values', 10, 3);
function hide_all_item_meta_values($display_key, $meta, $item) { return ''; }

//add_filter('woocommerce_order_items_meta_display', 'hide_all_item_meta_values_admin', 10, 2);
function hide_all_item_meta_values_admin($meta, $item){ return ''; }

//add_filter('woocommerce_order_item_get_formatted_meta_data', 'conditionally_hide_woo_item_meta', 100, 2);
function conditionally_hide_woo_item_meta($formatted_meta, $item) {
	if (is_admin()) {
		return []; // prevent WooCommerce from rendering the default meta table in admin
	}
	return $formatted_meta; // let frontend and email still get meta if needed
}


//add_action( 'woocommerce_after_order_itemmeta', 'custom_combined_order_item_meta_admin', 10, 3 );
function custom_combined_order_item_meta_admin( $item_id, $item, $order ) {
	$weeks = $stops = $bs_start = $bs_end = [];
	$child = $child1 = '';
	$meta_data = $item->get_meta_data();

	foreach ( $meta_data as $meta ) {
		switch ( $meta->key ) {
			case 'product_weeks_selected':
				$weeks = ! empty( $meta->value ) ? explode( '@@', $meta->value ) : [];
				break;
			case 'product_bus_stops_selected':
				$stops = ! empty( $meta->value ) ? explode( '@@', $meta->value ) : [];
				break;
			case 'product_bus_stop_start_time':
				$bs_start = ! empty( $meta->value ) ? explode( '@@', $meta->value ) : [];
				break;
			case 'product_bus_stop_end_time':
				$bs_end = ! empty( $meta->value ) ? explode( '@@', $meta->value ) : [];
				break;
			case 'parent_childs_selected':
				$child = ! empty( $meta->value ) ? get_user_by( 'id', $meta->value )->display_name : '';
				break;
			case 'parent_children_selected':
				$child1 = ! empty( $meta->value ) ? get_user_by( 'id', $meta->value )->display_name : '';
				break;
		}
	}

	$child_display = $child ?: $child1;
	$table_rows = '';

	if ( $child_display ) {
		$table_rows .= '<tr><th>Selected Child Name:</th><td>' . esc_html( $child_display ) . '</td></tr>';
	}

	foreach ( $weeks as $i => $week ) {
		$table_rows .= '<tr><th>Selected Week:</th><td>' . esc_html( $week ) . '</td></tr>';
		if ( ! empty( $stops[ $i ] ) && $stops[ $i ] !== 'empty' ) {
			$table_rows .= '<tr><th>Selected Stop:</th><td>' . esc_html( $stops[ $i ] ) . '</td></tr>';
		}
		if ( ! empty( $bs_start[ $i ] ) && $bs_start[ $i ] !== 'empty' ) {
			$table_rows .= '<tr><th>Bus Start Time:</th><td>' . esc_html( $bs_start[ $i ] ) . '</td></tr>';
		}
		if ( ! empty( $bs_end[ $i ] ) && $bs_end[ $i ] !== 'empty' ) {
			$table_rows .= '<tr><th>Bus End Time:</th><td>' . esc_html( $bs_end[ $i ] ) . '</td></tr>';
		}
	}

	if ( $table_rows ) {
		echo '<table class="display_meta" cellspacing="0"><tbody>' . $table_rows . '</tbody></table>';
	}
}


//add_action('woocommerce_order_item_meta_start', 'custom_combined_order_item_meta', 10, 3);
function custom_combined_order_item_meta($item_id, $item, $order){	   
	global $WC_custom_teatro_attributes; $output=$weeks=$stops=$bs_start=$bs_end=$child=$child1=[]; 
    $meta_data = $item->get_formatted_meta_data('');       
    foreach($meta_data as $meta){ 
		switch($meta->key): 
			case 'product_weeks_selected': $weeks=!empty($meta->value)?explode('@@', $meta->value):[]; break;
			case 'product_bus_stops_selected': $stops=!empty($meta->value)?explode('@@', $meta->value):[]; break;
			case 'product_bus_stop_start_time': $bs_start=!empty($meta->value)?explode('@@', $meta->value):[]; break;
			case 'product_bus_stop_end_time': $bs_end=!empty($meta->value)?explode('@@', $meta->value):[]; break;
			case 'parent_childs_selected': $child=!empty($meta->value)?get_user_by('id', $meta->value)->display_name:''; break;
			case 'parent_children_selected': $child1=!empty($meta->value)?get_user_by('id', $meta->value)->display_name:'';   break;
			default: 
		endswitch;        
    } 
	//$schild=!empty($child)?$child:$child1;
	//$output[]='<strong>Selected Child Name:</strong> '.$schild;
	$output[]=!empty($child)?'<strong>Selected Child Name:</strong> '.$child:'';
	foreach($weeks as $ws_key=>$ws_value): $line=''; 
		$line.='<strong>Selected Week:</strong> '.$ws_value; 
		$line.=(!empty($stops[$ws_key]) and $stops[$ws_key] != 'empty')?'<br/><strong>Selected Stop:</strong> '.$stops[$ws_key]:'';
		$start=(!empty($bs_start[$ws_key]) and $bs_start[$ws_key] != 'empty')?$bs_start[$ws_key]:'';
		$end=(!empty($bs_end[$ws_key]) and $bs_end[$ws_key] != 'empty')?$bs_end[$ws_key]:'';
		$line.=!empty($start)?'<br/><strong>Bus Start Time :</strong>'.$start.' <strong>Bus End Time :</strong>'.$end:''; $output[]=$line; 
	endforeach;	
    if (!empty($output)) {
        echo '<div class="custom-item-meta">' . implode('<br/><br/>', $output) . '</div>';
    }
}
/* add values to admin order section  */
//add_action('woocommerce_after_order_itemmeta', 'custom_combined_order_item_meta_in_admin', 10, 3);
function custom_combined_order_item_meta_in_admin($product, $item, $item_id){
	global $WC_custom_teatro_attributes; $output=$weeks=$stops=$bs_start=$bs_end=$child=$child1=[]; 
    $meta_data = $item->get_formatted_meta_data('');       
    foreach($meta_data as $meta){ 
		switch($meta->key): 
			case 'product_weeks_selected': $weeks=!empty($meta->value)?explode('@@', $meta->value):[]; break;
			case 'product_bus_stops_selected': $stops=!empty($meta->value)?explode('@@', $meta->value):[]; break;
			case 'product_bus_stop_start_time': $bs_start=!empty($meta->value)?explode('@@', $meta->value):[]; break;
			case 'product_bus_stop_end_time': $bs_end=!empty($meta->value)?explode('@@', $meta->value):[]; break;
			case 'parent_childs_selected': $child=!empty($meta->value)?get_user_by('id', $meta->value)->display_name:''; break;
			case 'parent_children_selected': $child1=!empty($meta->value)?get_user_by('id', $meta->value)->display_name:'';   break;
			default: 
		endswitch;        
    } 
	//$schild=!empty($child)?$child:$child1;
	$output[]=!empty($child)?'<strong>Selected Child Name:</strong> '.$child:'';
	foreach($weeks as $ws_key=>$ws_value): $line=''; 
		$line.='<strong>Selected Week:</strong> '.$ws_value; 
		$line.=(!empty($stops[$ws_key]) and $stops[$ws_key] != 'empty')?'<br/><strong>Selected Stop:</strong> '.$stops[$ws_key]:'';
		$start=(!empty($bs_start[$ws_key]) and $bs_start[$ws_key] != 'empty')?$bs_start[$ws_key]:'';
		$end=(!empty($bs_end[$ws_key]) and $bs_end[$ws_key] != 'empty')?$bs_end[$ws_key]:'';
		$line.=!empty($start)?'<br/><strong>Bus Start Time :</strong>'.$start.' <strong>Bus End Time :</strong>'.$end:''; $output[]=$line; 
	endforeach;	
    if (!empty($output)) {
        echo  '<div class="custom-item-meta">' . implode('<br/><br/>', $output) . '</div>';
    }
}

/* Change meta labels in admin and order details page  */
add_filter('woocommerce_order_item_display_meta_key', 'filter_wc_order_item_display_meta_key', 20, 3);
function filter_wc_order_item_display_meta_key($display_key, $meta, $item){
	if($item->get_type() === 'line_item' && $meta->key === 'parent_childs_selected' ) {
        $display_key = __("Bambino", "woocommerce" ); //Utente
    }
    if($item->get_type() === 'line_item' && $meta->key === 'product_weeks_selected' ) {
        $display_key = __("Turni", "woocommerce" ); //Turni
    }
	if($item->get_type() === 'line_item' && $meta->key === 'product_bus_stops_selected' ) {
        $display_key = __("Fermata del pulmino", "woocommerce" ); //Fermata del pulmino
    }
	if($item->get_type() === 'line_item' && $meta->key === 'product_bus_stop_start_time' ) {
        $display_key = __("Ora di inizio della fermata dell'autobus", "woocommerce" ); //Orario di inizio e fine della fermata dell'autobus
    }
	if($item->get_type() === 'line_item' && $meta->key === 'product_bus_stop_end_time' ) {
        $display_key = __("Ora di fine fermata dell'autobus", "woocommerce" ); //Bus Stop End Time
    }
	if($item->get_type() === 'line_item' && $meta->key === 'product_ages_selected' ) {
        $display_key = __("Fascia d'età", "woocommerce" ); //Age Group
    }
	if($item->get_type() === 'line_item' && $meta->key === 'product_buses_selected' ) {
        $display_key = __("Autobus selezionato", "woocommerce" ); //Buses
    }
    return $display_key;
}
/* Change meta values in admin and  order details page */
add_filter('woocommerce_order_item_display_meta_value', 'change_order_item_meta_value', 20, 3);
function change_order_item_meta_value($value, $meta, $item){  
	global $WC_custom_teatro_attributes; $value=''; 
	if($item->get_type() === 'line_item' && $meta->key === 'parent_childs_selected'){
		$child_user = get_user_by('id', $meta->value);
		$child_cf = get_user_meta($meta->value, 'codice_fiscale', true);
		$value = $child_user->display_name;
		if(!empty($child_cf)){
			$value .= '<br><strong>CF: ' . esc_html($child_cf) . '</strong>';
		}
    }	
    if($item->get_type() === 'line_item' && $meta->key === 'product_buses_selected'){        
		$value = $WC_custom_teatro_attributes->getBusNameById($meta->value); 
    }	
	if($item->get_type() === 'line_item' && $meta->key === 'product_weeks_selected'){        
		$value = $WC_custom_teatro_attributes->getTranslatedDateStringMulti($meta->value);
    }
	if($item->get_type() === 'line_item' && $meta->key === 'product_bus_stops_selected'){        
		$value = $WC_custom_teatro_attributes->replaceSignWithComma($meta->value);
    }
	if($item->get_type() === 'line_item' && $meta->key === 'product_bus_stop_start_time'){        
		$value = $WC_custom_teatro_attributes->replaceSignWithComma($meta->value);
    }
	if($item->get_type() === 'line_item' && $meta->key === 'product_bus_stop_end_time'){        
		$value = $WC_custom_teatro_attributes->replaceSignWithComma($meta->value);
    }
   return ($value); //wp_kses_post
} 

/* update bus seating capacity when order done successfully! */
// Registra i posti immediatamente alla creazione dell'ordine (es. bonifico, PayPal in attesa)
add_action('woocommerce_new_order', 'update_busseats', 10, 1);
// Fallback: registra i posti anche alla visualizzazione della thank-you page (già protetto da validateOrderAlreadySaved contro duplicati)
add_action('woocommerce_thankyou', 'update_busseats', 10, 1);
function update_busseats($order_id){	
    $order = new WC_Order($order_id); global $WC_custom_teatro_attributes;
    if(!empty($order)):
		foreach($order->get_items() as $item_id => $item):
			$product_id = $item->get_product_id(); $parent = wp_get_current_user();
			$child_id   = $item->get_meta('parent_childs_selected');
			$week_meta  = $item->get_meta('product_weeks_selected');
			$bus_meta   = $item->get_meta('product_buses_selected');

			// Crea una prenotazione separata per ogni settimana:
			// ogni settimana può avere un bus diverso (indice parallelo).
			$weeks = $WC_custom_teatro_attributes->extractMultipleSelections($week_meta);
			$buses = $WC_custom_teatro_attributes->extractMultipleSelections($bus_meta);

			if (!empty($weeks)) {
				foreach ($weeks as $i => $week) {
					$single_bus = isset($buses[$i]) ? trim($buses[$i]) : 'empty';
					if ($single_bus === 'empty') continue; // nessun pulmino per questa settimana
					$booking = [
						'order_id'   => $order_id,
						'product_id' => $product_id,
						'parent_id'  => $parent->ID,
						'child_id'   => $child_id,
						'week_id'    => $WC_custom_teatro_attributes->getReadableWeekString($week),
						'booked_at'  => time(),
					];
					$WC_custom_teatro_attributes->bookBusSeat(['bus' => $single_bus, 'book_data' => $booking]);
				}
			}
		endforeach;
		?>
		<a href="<?php echo get_permalink( wc_get_page_id( 'myaccount' ) ); ?>" class="woocommerce-button button view order-actions-button ">Torna al mio account</a>
		<?php
	endif;	
}
/* Create new product type for Courses */
add_action('init', 'create_courses_product_type');
function create_courses_product_type(){
	class WC_Product_Courses extends WC_Product {
		public function get_type() {
			return 'courses';
		}
    }
}
/* Add product type to dropdown */
add_filter('product_type_selector', 'add_courses_product_type');
function add_courses_product_type($types){
	$types['courses'] = __('Courses Product', 'teatro-courses-buses');
    return $types;
}
/* Load Course product class */
add_filter('woocommerce_product_class', 'course_woocommerce_product_class', 10, 2);
function course_woocommerce_product_class($classname, $product_type){
	if($product_type == 'courses') {
        $classname = 'WC_Product_Courses';
    }
    return $classname;
}
/* Add Tabs to course product type */
add_filter('woocommerce_product_data_tabs', 'courses_product_tabs');
function courses_product_tabs($tabs){
	$tabs['general']['class'][] = 'show_if_courses';
	$tabs['attribute']['class'][] = 'hide_if_courses';
	$tabs['shipping']['class'][] = 'hide_if_courses';
	$tabs['inventory']['class'][] = 'show_if_courses'; 	
	return $tabs;
}
/* Show general tab for prices */
add_action('admin_footer', 'show_price_tab_for_courses');
function show_price_tab_for_courses(){	
	if('product' != get_post_type()):
		return;
	endif; 
	?>
	<script type='text/javascript'>
		  jQuery( document ).ready( function() {
			// Aggiungiamo 'show_if_courses-noisee' separata da uno spazio
              jQuery( '.options_group.pricing' ).addClass( 'show_if_courses show_if_courses-noisee' ).show();
			 // jQuery( '.options_group.pricing' ).addClass( 'show_if_courses' ).show();
			  jQuery( '.general_options' ).show();
		 });
	</script>
	<?php	
}

/* Add Courses tab to my account */
function add_teatrocourses_endpoint() {
    add_rewrite_endpoint('teatrocourses', EP_ROOT | EP_PAGES);
}  
add_action('init', 'add_teatrocourses_endpoint');
/* insert the name to menu */
function add_teatrocourses_to_my_account($items) {
	$current_user = wp_get_current_user();
	if(isset($current_user->roles) && is_array($current_user->roles) && in_array( 'parent', (array) $current_user->roles)) {
	    $new_items = array(
	        'teatrocourses' => __('My Courses', 'teatro-courses-buses'),
	    );
		$items = $new_items + $items;
	    // $logout_key = array_search( 'customer-logout', array_keys($items));
	    // $items = array_slice($items, 0, $logout_key, true) +
		// 	 $new_items +
		// 	 array_slice($items, $logout_key, NULL, true);	    
	}   
    return $items;
}
add_filter('woocommerce_account_menu_items', 'add_teatrocourses_to_my_account');
/* Add Content to new tab */
function teatrocourses_content(){
	global $WC_custom_teatro_attributes; $mycourses=$WC_custom_teatro_attributes->getMyCourseOrders();
	echo '<div class="myAccContent--title">'.esc_html__('PURCHASE COURSES RESERVED FOR YOUR CHILD DESIGNED SPECIFICALLY FOR HIS/HER AGE GROUP.', 'teatro-courses-buses').'</div>';
	//$WC_custom_teatro_attributes->printRData($mycourses);
	if(!empty($mycourses)):
		echo '<div class="MyAccount-orders__main">';
			foreach($mycourses as $customer_order){						
				$order = wc_get_order($customer_order);
				if($order){
					$items = $order->get_items();
					foreach ( $items as $item_id => $item ) {
					$product_id = $item->get_product_id();
					$product = wc_get_product( $product_id );
					$child = get_user_by( 'id', $item->get_meta('parent_childs_selected') );
					if($product && ($product->get_type() == 'courses' || $product->get_type() == 'courses-noisee')) {
						$product_image = $product->get_image();
							echo '<div class="MyAccount-orders__wrapper row">
									<div class="order_thumbnail">'.$product_image.'</div>
									<div class="content"><ul class="unstyledList"><li>'.$item->get_name().'</li><li>'.$WC_custom_teatro_attributes->getTranslatedDateStringMulti($item->get_meta('product_weeks_selected')).'</li><li>'.$child->display_name.'</li></ul></div>
									<a href="'.esc_url( $order->get_view_order_url() ).'" class="siteButton buttonSmall">'.esc_html__('view', 'teatro-courses-buses').'</a>						
							</div>';
						}
					}					
				}			
			}	
		echo '</div>';
	?>
	<p><a class="siteButtonDark buttonSmall" href="<?php echo site_url();?>/negozio/"><?php esc_html_e( 'Aggiungi un\'altra attività', 'teatro-subscriptions'); ?></a></p>

	<?php
	if ( is_user_logged_in() ) {
			$user_id = get_current_user_id();

			// Recupero ordini completati dell'utente
			$customer_orders = wc_get_orders( array(
					'customer_id' => $user_id,
					'status'      => array( 'wc-completed' ), // solo ordini completati
					'limit'       => 1, // basta 1 ordine
			) );

			if ( ! empty( $customer_orders ) ) {
					?>
					<div class="area-riservata-messaggio">
							<p>Per richiedere un rimborso contattaci via e-mail a <a href="mailto:info@teatrosolare.it">info@teatrosolare.it</a></p>
					</div>
					<?php
			}
	}
	?>


	<!-- Table was here (Use backup file if needed) -->

	<?php do_action( 'woocommerce_before_account_orders_pagination' ); ?>
	<!-- <@?php if ( 1 < $customer_orders->max_num_pages ) : ?>
		<div class="woocommerce-pagination woocommerce-pagination--without-numbers woocommerce-Pagination">
			<@?php if ( 1 !== $current_page ) : ?>
				<a class="woocommerce-button woocommerce-button--previous woocommerce-Button woocommerce-Button--previous button" href="<@?php echo esc_url( wc_get_endpoint_url( 'orders', $current_page - 1 ) ); ?>"><@?php esc_html_e( 'Previous', 'woocommerce' ); ?></a>
			<@?php endif; ?>

			@php if ( intval( $customer_orders->max_num_pages ) !== $current_page ) : ?>
				<a class="woocommerce-button woocommerce-button--next woocommerce-Button woocommerce-Button--next button" href="<@?php echo esc_url( wc_get_endpoint_url( 'orders', $current_page + 1 ) ); ?>"><@?php esc_html_e( 'Next', 'woocommerce' ); ?></a>
			<@?php endif; ?>
		</div>
	<@?php endif; ?> -->
	<?php else:	
		$shop_permalink = wc_get_page_permalink('shop');
		if (!$shop_permalink) {
			$shop_permalink = home_url();
		}			
		$shop_permalink = esc_url(apply_filters('woocommerce_return_to_shop_redirect', $shop_permalink));		
		$notice_message = sprintf(
			'<span class="noticeContent">%s</span> <a class="siteButtonDark buttonSmall" href="%s">%s</a>',
			esc_html__('No order has been made yet.', 'woocommerce'),
			$shop_permalink,
			esc_html__('Guarda le attività', 'teatro-subscriptions')
		);
		wc_print_notice($notice_message, 'notice');
	endif;	
	
}  
add_action('woocommerce_account_teatrocourses_endpoint', 'teatrocourses_content' );
/* Loader HTML at footer  === loaderEnabled */
add_action('wp_footer', 'loader_html_cs');
function loader_html_cs(){
	?><div class="site--loader-sm"><div class="loader-inner"></div></div><?php
}

/* 1. Crea la classe per il nuovo tipo prodotto Noisee */
add_action('init', 'create_courses_noisee_product_type');
function create_courses_noisee_product_type(){
    class WC_Product_Courses_Noisee extends WC_Product {
        public function get_type() {
            return 'courses-noisee';
        }
    }
}

/* 2. Aggiungi il nuovo tipo al menu a tendina in bacheca */
add_filter('product_type_selector', 'add_courses_noisee_product_type');
function add_courses_noisee_product_type($types){
    $types['courses-noisee'] = __('Courses Noisee Product', 'teatro-courses-buses');
    return $types;
}

/* 3. Carica la classe corretta quando il tipo è courses-noisee */
add_filter('woocommerce_product_class', 'course_noisee_woocommerce_product_class', 10, 2);
function course_noisee_woocommerce_product_class($classname, $product_type){
    if($product_type == 'courses-noisee') {
        $classname = 'WC_Product_Courses_Noisee';
    }
    return $classname;
}

/* 4. Mostra i tab necessari nel backend */
add_filter('woocommerce_product_data_tabs', 'courses_noisee_product_tabs');
function courses_noisee_product_tabs($tabs){
    $tabs['general']['class'][] = 'show_if_courses_noisee';
    $tabs['inventory']['class'][] = 'show_if_courses_noisee';
    // Nascondi spedizione e attributi se non servono
    $tabs['attribute']['class'][] = 'hide_if_courses_noisee';
    $tabs['shipping']['class'][] = 'hide_if_courses_noisee';
    return $tabs;
}

add_action('admin_footer', 'show_price_tab_for_courses_noisee');
function show_price_tab_for_courses_noisee(){  
    if('product' != get_post_type()) return; 
    ?>
    <script type='text/javascript'>
        jQuery(document).ready(function() {
            jQuery('.options_group.pricing').addClass('show_if_courses_noisee');
            // Se cambi tipo prodotto, forza la visualizzazione del prezzo
            jQuery('body').on('woocommerce-product-type-change', function() {
                if(jQuery('select#product-type').val() == 'courses-noisee') {
                    jQuery('.options_group.pricing').show();
                    jQuery('.general_options').show();
                }
            });
        });
    </script>
    <?php   
}