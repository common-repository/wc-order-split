<?php if ( ! defined( 'ABSPATH' ) ) exit; 
/*
	Plugin Name: WC Order Split
	Plugin URI: https://profiles.wordpress.org/fahadmahmood/wc-order-split
	Description: Create custom group labels and split WooCommerce orders.
	Version: 1.7.8
	Author: Fahad Mahmood
	Author URI: http://androidbubble.com/blog/
	Text Domain: wc-order-split
	Domain Path: /languages/
	License: GPL2
	
	This WordPress plugin is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 2 of the License, or any later version. This WordPress plugin is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the	GNU General Public License for more details. You should have received a copy of the GNU General Public License along with this WordPress plugin. If not, see http://www.gnu.org/licenses/gpl-2.0.html.
*/


	if ( ! defined( 'ABSPATH' ) ) {
		exit; // Exit if accessed directly
	}else{
		 clearstatcache();
	}
	
	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	
	$wcos_all_plugins = get_plugins();
	$wcos_active_plugins = apply_filters( 'active_plugins', get_option( 'active_plugins' ) );
	
	if ( array_key_exists('woocommerce/woocommerce.php', $wcos_all_plugins) && in_array('woocommerce/woocommerce.php', $wcos_active_plugins) ) {
		
		
		
		
		global $wcos_data, $wcos_pro, $wcos_activated, $yith_pre_order, $wcos_premium_link;
		
		$wcos_premium_link = 'https://shop.androidbubbles.com/product/wc-order-split';//https://shop.androidbubble.com/products/wordpress-plugin?variant=36439508222107';//
		
		$yith_pre_order = (in_array( 'yith-pre-order-for-woocommerce/init.php',  $wcos_active_plugins) || in_array( 'yith-woocommerce-pre-order.premium/init.php',  $wcos_active_plugins));
		
		$wcos_activated = true;
		

		$wcos_data = get_plugin_data(__FILE__);
		
		
		define( 'WCOSP_PLUGIN_DIR', dirname( __FILE__ ) );
		
		$wcos_pro_file = WCOSP_PLUGIN_DIR . '/pro/wcos-pro.php';
		$wcos_pro =  file_exists($wcos_pro_file);
		require_once WCOSP_PLUGIN_DIR . '/inc/functions.php';
		
		if($wcos_pro)
		include_once($wcos_pro_file);		
		
		if(is_admin()){
			add_action( 'admin_menu', 'wco_sp_admin_menu' );
		}else{
			
		}
		
		if(function_exists('wcsp_plugin_links')){
			$plugin = plugin_basename(__FILE__); 
			add_filter("plugin_action_links_$plugin", 'wcsp_plugin_links' );	
		}	
		
		if(function_exists('wcos_detect_to_delete')){
			add_action('admin_init', 'wcos_detect_to_delete');
			add_action('admin_init', 'wcos_admin_init');			
		}

	}