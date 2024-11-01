<?php if ( ! defined( 'ABSPATH' ) ) exit; 

	include_once('functions-inner.php');
	

	function sanitize_wcos_data( $input ) {
		if(is_array($input)){		
			$new_input = array();	
			foreach ( $input as $key => $val ) {
				$new_input[ $key ] = (is_array($val)?sanitize_wcos_data($val):sanitize_text_field( $val ));
			}			
		}else{
			$new_input = sanitize_text_field($input);			
			if(stripos($new_input, '@') && is_email($new_input)){
				$new_input = sanitize_email($new_input);
			}
			if(stripos($new_input, 'http') || wp_http_validate_url($new_input)){
				$new_input = esc_url($new_input);
			}			
		}	
		return $new_input;
	}	
	
	function wco_sp_admin_menu()
	{
		global $wcos_data;		
		$title = str_replace('WooCommerce', 'WC', $wcos_data['Name']);
		add_submenu_page(null, $title, $title, 'manage_woocommerce', 'wcos_', 'wcos_' );
		add_submenu_page('woocommerce', $title, $title, 'manage_woocommerce', 'wcos_colors', 'wcos_colors' );
		

	}
	function wcos_(){ 		
		global $wpdb; 
		include('wcos_settings.php');	
	}
	function wcos_colors(){ 		
		global $wpdb; 
		include('wcos_color_settings.php');	
	}
	add_filter( 'woocommerce_admin_order_actions', 'wcos_order_status_actions_button', 100, 2 );
	function wcos_order_status_actions_button( $actions, $order ) {
		
		
		$actions['order_details'] = array(
			'url'       => get_admin_url().'admin.php?page=wcos_&order_id='.$order->get_id(),
			'name'      => (count($order->get_items())>1)?__( 'Select Order to Split', 'wc-order-split'):__( 'Select Order to View', 'wc-order-split'),
			'action'    => (count($order->get_items())>1)?'wcos_btn':'wcos_btn_done', 
		);
	
		
		
		return $actions;

	}
	add_action( 'wp_enqueue_scripts', 'wcos_enqueue_scripts' );
	add_action( 'admin_enqueue_scripts', 'wcos_enqueue_scripts' );
	
	function wcos_enqueue_scripts() 
	{
	
		
		if(is_admin()){
			if(isset($_GET['page']) && in_array($_GET['page'], array('wcos_', 'wcos_colors'))){
				wp_enqueue_script('wcos-boostrap-script', plugins_url('js/bootstrap.min.js', dirname(__FILE__)), array( 'jquery' ), '1.0', true );
				wp_enqueue_style('wcos-boostrap-style', plugins_url('css/bootstrap.min.css', dirname(__FILE__)));		
										
				wp_enqueue_script('wcos-fontawesome', plugins_url('js/fontawesome.min.js', dirname(__FILE__)), array( 'jquery' ), '1.0', true );
				wp_enqueue_style('wcos-fontawesome', plugins_url('css/fontawesome.min.css', dirname(__FILE__)));								
				
				wp_enqueue_script('wcos-scripts', plugins_url('js/admin-scripts.js?t='.time(), dirname(__FILE__)), array( 'jquery' ), '1.0', true );
			}
			if(
					((isset($_GET['page']) && in_array($_GET['page'], array('wcos_', 'wcos_colors'))) 
				|| 
				(isset($_GET['post_type']) && in_array($_GET['post_type'], array('shop_order'))))
			){
				wp_enqueue_style('wcos-style', plugins_url('css/admin-style.css?t='.time(), dirname(__FILE__)));
			}
		}
				
	}
	function wcos_split_order($order_id, $original_order_id, $split_items){
		global $yith_pre_order;
		$original_order = new WC_Order($original_order_id);
		$order = new WC_Order($order_id);
		
		$order_status = $original_order->get_status();		
		
		if ( class_exists( 'WC_Seq_Order_Number_Pro' ) ) {
			// Set sequential order number 
			$setnumber = new WC_Seq_Order_Number_Pro;
			$setnumber->set_sequential_order_number($order_id);
			
		}		

		wcos_clone_order_header($order_id, $original_order_id);
		wcos_clone_order_billing($order_id, $original_order_id);
		wcos_clone_order_shipping($order_id, $original_order_id);		
		wcos_clone_order_shipping_items($order_id, $original_order);
		wcos_clone_order_fees($order, $original_order);		
		wcos_clone_order_coupons($order, $original_order);
		wcos_add_order_items($order, $original_order, $split_items);
		
		update_post_meta( $order_id, '_payment_method', get_post_meta($original_order_id, '_payment_method', true) );
		update_post_meta( $order_id, '_payment_method_title', get_post_meta($original_order_id, '_payment_method_title', true) );
		
		$order->update_status($order_status); //('on-hold');
		$order->calculate_totals();
		
		wcos_meta_keys_clone_from_to($order_id, $original_order_id);//exit;
		
		$order->add_order_note(__('Parent Order', 'wc-order-split').' #'.$original_order_id.'');
		
	}
	
	function wcos_clone_order_header($order_id, $original_order_id, $restrict_options=array(), $_order_total=false){
			
		if($_order_total){
			
		}else{
			
			$_order_total = get_post_meta($original_order_id, '_order_total', true);
			
		}
		
		$all_options = array(
			'_order_shipping',
			'_order_discount',
			'_cart_discount',
			'_order_tax',
			'_order_shipping_tax',			
			'_customer_user',
			'_order_currency',
			'_prices_include_tax',
			'_customer_ip_address',
			'_customer_user_agent',
			'_tribe_tickets_meta',
		);
		
		if(!empty($all_options)){
			foreach($all_options as $option){
				if(!empty($restrict_options) && !in_array($option, $restrict_options)){ continue; }
				$val = get_post_meta($original_order_id, $option, true);
				update_post_meta( $order_id, $option, $val );
			}
			if(empty($restrict_options)){
				update_post_meta( $order_id, '_order_key', 'wc_' . apply_filters('woocommerce_generate_order_key', uniqid('order_') ) );
			}
		}
		
		if($_order_total){
			update_post_meta( $order_id, '_order_total',  sanitize_wcos_data($_order_total));
		}

		
		
		//exit;
		
	}	
	function wcos_clone_order_billing($order_id, $original_order_id){
	
		update_post_meta( $order_id, '_billing_city', get_post_meta($original_order_id, '_billing_city', true));
		update_post_meta( $order_id, '_billing_state', get_post_meta($original_order_id, '_billing_state', true));
		update_post_meta( $order_id, '_billing_postcode', get_post_meta($original_order_id, '_billing_postcode', true));
		update_post_meta( $order_id, '_billing_email', get_post_meta($original_order_id, '_billing_email', true));
		update_post_meta( $order_id, '_billing_phone', get_post_meta($original_order_id, '_billing_phone', true));
		update_post_meta( $order_id, '_billing_address_1', get_post_meta($original_order_id, '_billing_address_1', true));
		update_post_meta( $order_id, '_billing_address_2', get_post_meta($original_order_id, '_billing_address_2', true));
		update_post_meta( $order_id, '_billing_country', get_post_meta($original_order_id, '_billing_country', true));
		update_post_meta( $order_id, '_billing_first_name', get_post_meta($original_order_id, '_billing_first_name', true));
		update_post_meta( $order_id, '_billing_last_name', get_post_meta($original_order_id, '_billing_last_name', true));
		update_post_meta( $order_id, '_billing_company', get_post_meta($original_order_id, '_billing_company', true));
		
		do_action('clone_extra_billing_fields_hook', $order_id, $original_order_id);
		
	}	
	function wcos_clone_order_shipping($order_id, $original_order_id){
	
		update_post_meta( $order_id, '_shipping_country', get_post_meta($original_order_id, '_shipping_country', true));
		update_post_meta( $order_id, '_shipping_first_name', get_post_meta($original_order_id, '_shipping_first_name', true));
		update_post_meta( $order_id, '_shipping_last_name', get_post_meta($original_order_id, '_shipping_last_name', true));
		update_post_meta( $order_id, '_shipping_company', get_post_meta($original_order_id, '_shipping_company', true));
		update_post_meta( $order_id, '_shipping_address_1', get_post_meta($original_order_id, '_shipping_address_1', true));
		update_post_meta( $order_id, '_shipping_address_2', get_post_meta($original_order_id, '_shipping_address_2', true));
		update_post_meta( $order_id, '_shipping_city', get_post_meta($original_order_id, '_shipping_city', true));
		update_post_meta( $order_id, '_shipping_state', get_post_meta($original_order_id, '_shipping_state', true));
		update_post_meta( $order_id, '_shipping_postcode', get_post_meta($original_order_id, '_shipping_postcode', true));
		
		do_action('clone_extra_shipping_fields_hook', $order_id, $original_order_id);
	
	}
	function wcos_clone_order_shipping_items($order_id, $original_order, $qty=false){
		
		$original_order_shipping_items = $original_order->get_items('shipping');
	
		foreach ( $original_order_shipping_items as $original_order_shipping_item ) {
			
			//wc_os_pree($original_order_shipping_item);
			//wc_os_pree(wc_format_decimal($original_order_shipping_item['cost']));exit;
			$cost = wc_format_decimal( $original_order_shipping_item['cost'] );
			//wc_os_pree($cost);
			if($cost && $qty){ //22/05/2019
				$qty_total = wcos_order_total_qty($original_order);
				//wc_os_pree($qty_total);
				$per_item_cost = ($cost/$qty_total);
				//wc_os_pree($per_item_cost);
				$order = new WC_Order($order_id);
				//$qty_total_new = wcos_order_total_qty($order);
				//wc_os_pree($qty_total_new);
				
				//$cost = ($qty_total_new*$per_item_cost);
				//wc_os_pree($cost);
				$cost = ($qty*$per_item_cost);
				//wc_os_pree($cost);
			}
			//exit;
			$item_id = wc_add_order_item( $order_id, array(
				'order_item_name'       => $original_order_shipping_item['name'],
				'order_item_type'       => 'shipping'
			) );
	
			if ( $item_id ) {
				wc_add_order_item_meta( $item_id, 'method_id', $original_order_shipping_item['method_id'] );
				wc_add_order_item_meta( $item_id, 'cost',  $cost );
			}
	
		}
	}	
	function wcos_order_total_qty($order){
		$qty = 0;		
		foreach($order->get_items() as $item_id=>$item_data){
		
			$qty += $item_data->get_quantity();
			
		}
		
		return $qty;
	}	
	function wcos_clone_order_fees($order, $original_order){
	
		$fee_items = $original_order->get_fees();
 
		if (empty($fee_items)) {
			
		} else {
			
			foreach($fee_items as $fee_key => $fee_value){
				
				$fee_item  = new WC_Order_Item_Fee();

				$fee_item->set_props( array(
					'name'        => $fee_item->get_name(),
					'tax_class'   => $fee_value['tax_class'],
					'tax_status'  => $fee_value['tax_status'],
					'total'       => $fee_value['total'],
					'total_tax'   => $fee_value['total_tax'],
					'taxes'       => $fee_value['taxes'],
				) );
				//pree('clone_order_fees');exit;
				$order->add_item( $fee_item );	 
				
			}
			
		}
   
	}
	function wcos_clone_order_coupons($order, $original_order){

		$coupon_items = (method_exists($original_order, 'get_coupon_codes')?$original_order->get_coupon_codes():$original_order->get_used_coupons());

		if (empty($coupon_items)) {
			
		} else {
			
			foreach($original_order->get_items( 'coupon' ) as $coupon_key => $coupon_values){
				
				$coupon_item  = new WC_Order_Item_Coupon();

				$coupon_item->set_props( array(
					'name'  	   => $coupon_values['name'],
					'code'  	   => $coupon_values['code'],
					'discount'     => $coupon_values['discount'],
					'discount_tax' => $coupon_values['discount_tax'],
				) );

				$order->add_item( $coupon_item );	 
				
			}
			
		}
   
	}
	
	function wcos_add_order_items($new_order, $original_order, $split_items){
		
		global $wcos_;
		
		$items_added = array();
		
		//pree($_POST);
		//exit;
		//pree($new_order->get_ID());
		//pree($original_order->get_ID());
		
		foreach($split_items as $product_items){
			
			
	
			foreach($original_order->get_items() as $order_key => $values){
				
				//wc_os_pree($values);
				$product_id = ($values['variation_id'] == 0)?$values['product_id']:$values['variation_id'];
				
				
				/*if(in_array($product_id, $items_added) || !in_array($product_id, $split_items)){
					continue;
				}*/ //28-12-2019
				if(in_array($order_key, $items_added) || !in_array($order_key, $split_items)){
					continue;
				}
				
				$items_added[] = $order_key;//$product_id;//$values['product_id'];
				
				
				if ($values['variation_id'] != 0) {
					$product = new WC_Product_Variation($values['variation_id']);
				
				} else {
					$product = new WC_Product($values['product_id']);	
				}
			
				$item                       = new WC_Order_Item_Product();
				$item->legacy_values        = $values;
				$item->legacy_cart_item_key = $order_key;
				
				$filtered_qty = apply_filters('wcos_filter_item_quantity', $values['quantity'] , $original_order, $order_key);
				
				
				$unit_price = $product->get_price();
				$line_price = ($values['quantity']>=1?($values['line_total']/$values['quantity']):$values['line_total']);
							
				$pros = array(
					'quantity'     => $filtered_qty,
					'variation'    => $values['variation'],
					//'subtotal'     => $values->get_subtotal(),
					//'total'        => $values->get_total(),
					'subtotal_tax' => $values['line_subtotal_tax'],
					'total_tax'    => $values['line_tax'],
					'taxes'        => $values['line_tax_data'],
				);
				
				if($line_price!=$unit_price){
					$total = $line_price*$pros['quantity'];
				}else{
					$total = false;
				}
				$pros['subtotal'] = ($total?$total:$unit_price*$pros['quantity']);
				$pros['total'] = ($total?$total:$unit_price*$pros['quantity']);
								
				//pree($pros);
				$item->set_props( $pros );
				
				if ( $product ) {
					$item->set_props( array(
						'name'         => $product->get_name(),
						'tax_class'    => $product->get_tax_class(),
						'product_id'   => $product->is_type( 'variation' ) ? $product->get_parent_id() : $product->get_id(),
						'variation_id' => (method_exists($product, 'get_type') && $product->get_type()=='variation') ? $product->get_id() : 0,
					) );
				}
				
				if($filtered_qty != 0){
					$new_order->add_item( $item );
				}
					 
				
			}
			//pree($_POST);
			//pree(function_exists('wcos_delete_order_item'));
			$proceed_elimination = (isset($_POST['wcos_remove_selected']) && $_POST['wcos_remove_selected']==1 && function_exists('wcos_delete_order_item'));
			//pree($proceed_elimination);exit;
			//pree($original_order->get_id());pree($split_items);exit;
			if($proceed_elimination){
				//wcos_delete_order_item($original_order->get_id(), $split_items);								
				update_post_meta($original_order->get_id(), '_wcos_delete_order_item', $split_items);
			}
			$wcos_ensure_meta_data = (isset($_POST['wcos_order_tax']) && $_POST['wcos_order_tax']==1 && function_exists('wcos_ensure_meta_data'));
			if($wcos_ensure_meta_data){
				update_post_meta($original_order->get_id(), '_wcos_ensure_meta_data', true);				
			}
			
		}
		//exit;
		
	}
	function wcos_meta_keys_clone_from_to($order_id_to=0, $order_id_from=0){
		if($order_id_from && $order_id_to){
			$order_id_to_meta = get_post_meta($order_id_to);
			$order_id_to_keys = array_keys($order_id_to_meta);
			
			$order_id_from_meta = get_post_meta($order_id_from);
			//$order_id_from_meta['wpml_language'] = array('de');
			$order_id_from_keys = array_keys($order_id_from_meta);
			
			
			
			//wc_os_pree($order_id_to_keys);
			//wc_os_pree($order_id_from_keys);
			
			$arr_diff = array_diff($order_id_from_keys, $order_id_to_keys);
			//wc_os_pree($arr_diff);
			
			if(!empty($arr_diff)){
				foreach($arr_diff as $diff_key){
					//wc_os_pree($order_id_from_meta[$diff_key]);
					if(array_key_exists($diff_key, $order_id_from_meta)){
						$diff_value = current($order_id_from_meta[$diff_key]);						
						$diff_value = maybe_unserialize($diff_value);
						
						if(!in_array($diff_key, array('wc_os_order_splitter_cron', 'wos_update_status', '_wcos_delete_order_item')))
						update_post_meta($order_id_to, $diff_key, $diff_value);
					}
				}
			}
			
			$original_order = wc_get_order($order_id_from);
			$new_order = wc_get_order($order_id_to);
			
			$old_order_items = array();
			foreach($original_order->get_items() as $item_id=>$item_data){
				$item_meta = wc_get_order_item_meta($item_id, '');
				//pree($item_meta);
				$pid = $item_data->get_product_id();
				$vid = $item_data->get_variation_id();	
				
				$old_order_items[$pid][$vid] = $item_meta;
									
				
			}

			
			$new_order_items = array();
			foreach($new_order->get_items() as $item_id=>$item_data){
				$pid = $item_data->get_product_id();
				$vid = $item_data->get_variation_id();					
				wc_update_order_item_meta($item_id, __('Original Order', 'wc-order-split'), $order_id_from);
				
				if(array_key_exists($pid, $old_order_items)){
					if(array_key_exists($vid, $old_order_items[$pid])){
						$item_meta = $old_order_items[$pid][$vid];
						foreach($item_meta as $key=>$value){			
							$value = (current($value));			
							$existing_value = wc_get_order_item_meta($item_id, $key, true);		
							if($existing_value==''){
								wc_update_order_item_meta($item_id, $key, $value);
							}
						}
					}
				}
			}			
			
			//exit;
			
		}			
	}	
	
	function wcsp_plugin_links($links) { 

		global $wcos_premium_link, $wcos_pro;


		$settings_link = '<a href="admin.php?page=wcos_colors">'.__('Settings', 'wc-order-split').'</a>';

		
		if($wcos_pro){
			array_unshift($links, $settings_link); 
		}else{
			 
			$wcos_premium_link = '<a href="'.esc_url($wcos_premium_link).'" title="'.__('Go Premium', 'wc-order-split').'" target="_blank">'.__('Go Premium', 'wc-order-split').'</a>'; 
			array_unshift($links, $settings_link, $wcos_premium_link); 
		
		}
				
		
		return $links; 
	}	
	
	add_filter('acf/settings/remove_wp_meta_box', '__return_false');
		
	if(!function_exists('wcos_admin_init')){
		function wcos_admin_init($data){
			//http://demo.gpthemes.com/wp-admin/post.php?post=320372&action=edit&get_keys&debug
	
			
			if(isset($_GET['post']) && is_numeric($_GET['post']) && $_GET['post']>0 && isset($_GET['debug'])){
				
				$order = get_post(sanitize_wcos_data($_GET['post']));
				if(is_object($order) && $order->post_type=='shop_order'){
					
					if(isset($_GET['get_keys'])){
						pree(get_post_meta($order->ID));
					}
					if(isset($_GET['get_items'])){
						$order_obj = wc_get_order($order->ID);
						foreach($order_obj->get_items() as $item_key=>$item_data){
							pree($item_key);
							pree($item_data);
						}
					}
					
					if(isset($_GET['get_items_meta'])){
						$order_obj = wc_get_order($order->ID);
						foreach($order_obj->get_items() as $item_key=>$item_data){
							pree($item_key);
							pree(wc_get_order_item_meta($item_key, ''));
						}
						
					}
					exit;
					
				}
			}else{
				wcos_ensure_meta_data();
			}
			
		}
	}	