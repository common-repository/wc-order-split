<?php defined( 'ABSPATH' ) or die( __('No script kiddies please!', 'wc-order-split') );

	global $wcos_data, $wcos_pro, $wcos_premium_link;
	
	$order_id = 0;
	$successfully_saved = false;
	$submitted_action = (isset($_POST['wcos_remove_selected']) && $_POST['wcos_remove_selected']==1);


	$colors = admin_url().'admin.php?page=wcos_colors';		
			
	if(isset($_POST['submit_form'])){
		
		if ( ! isset( $_POST['wcos_order_split_field'] ) 
			|| ! wp_verify_nonce( $_POST['wcos_order_split_field'], 'wcos_order_split_action' ) 
		) {
		   print __('Sorry, your nonce did not verify.', 'wc-order-split');
		   exit;
		} else {
		   // process form data
		   //pree($_POST);exit;
		   if(isset($_POST['grouping']) || isset($_POST['wcos_predict_backorder'])){
			   $order_id = ((isset($_POST['order_id']) && $_POST['order_id']>0)?sanitize_wcos_data($_POST['order_id']):$order_id);
			   $order_data = wc_get_order( $order_id );
			   $user_id = $order_data->get_user_id();
			   $new_order_ids = array();
			   //pree($user_id);exit;
			   
			   foreach($_POST['grouping'] as $group=>$items){
				   if(in_array($group, array('default')) && !isset($_POST['wcos_predict_backorder'])){ continue; }
				//    wcos_pree($group);
				//    wcos_pree($items);
				   $order_data_arr =  array(
						'post_type'     => 'shop_order',
						'post_status'   => 'publish',
						'ping_status'   => 'closed',
						'post_author'   => $user_id,
						'post_password' => uniqid( 'order_' ),
					);
				
					$split_order_id = wp_insert_post( apply_filters( 'woocommerce_new_order_data', $order_data_arr), true );
					
					//wc_os_pree($order_data);exit;
					
					//wc_os_pree('order_id:'.$order_id);
			
					if ( is_wp_error( $split_order_id ) ) {	
						//add_action( 'admin_notices', array($this, 'clone__error'));
					} else {
						update_post_meta($split_order_id, 'splitted_from', $order_id);
						update_post_meta($split_order_id, 'split_status', true);
						update_post_meta($split_order_id, 'split_group', $group);
						//update_post_meta($split_order_id, 'cloned_from', $order_id);
						$new_order_ids[] = $split_order_id;
						$split_items = array_keys($items);
						wcos_split_order($split_order_id, $order_id, $split_items);
						
					}	
			   }
			   $successfully_saved = true;
		   }
		   //exit;
		   //pree($_POST);exit;
		}		
		
	}

	if(isset($_GET['order_id'])){
		$order_id = sanitize_wcos_data($_GET['order_id']);
		$order_data = wc_get_order( $order_id );
		$order_data->calculate_totals();			
	}else{
		wp_redirect(admin_url().'edit.php?post_type=shop_order');
	}	
	

	$arg = get_option('wcos_colors', array());
	$all_classes = (is_array($arg)?$arg:array());
	if(!empty($all_classes)){
		foreach ($all_classes as $classes) {
			list($name, $bg, $color, $class) = $classes;
			$class_arr[] = esc_html($class);
		}
	}
	if(!empty($class_arr)){
		echo '<style type="text/css">'.implode(' ', $class_arr).'</style>';
	}
?>
<div class="container-fluid wcos_wrapper_div pt-4">
		<div class="row mb-4">
        	<div class="icon32" id="icon-options-general"><br></div><h4><?php echo $wcos_data['Name']; ?> <?php echo '('.$wcos_data['Version'].($wcos_pro?') Pro':')'); ?> - <?php _e("Order","wc-order-split"); ?> <?php _e("Management","wc-order-split"); ?> </h4> 
            <?php if(!$wcos_pro): ?>
            <a href="<?php echo esc_url($wcos_premium_link); ?>" target="_blank" class="btn btn-info btn-sm" style="position:absolute; right:15px"><?php _e('Go Premium', 'wc-order-split'); ?></a>
            <?php endif; ?>
        </div>
<?php if($successfully_saved):  ?>
        <div class="row">
            <div class="alert alert-success">
            <strong><?php _e('Success', 'wc-order-split'); ?>!</strong> <?php _e('Order splitted successfully', 'wc-order-split'); ?>.
            </div>
        </div>
<?php if($submitted_action):  ?>        
<script type="text/javascript" language="javascript">
jQuery(document).ready(function($) {
	setTimeout(function(){
		document.location.reload();
	}, 2000);
});
</script>        
<?php endif; ?>
<?php endif; ?>

		<div class="row">
			<div class="col-md-9 pl-0">
			<form method="post" class="main-split-form">
			<?php wp_nonce_field( 'wcos_order_split_action', 'wcos_order_split_field' ); ?>
            <input type="hidden" name="order_id" value="<?php echo $order_id; ?>" />
        
		<ul class="list-group wcos_order_items">
       
			<?php 
			if(!empty($order_data->get_items())){
				
						$wc_order_items = array();
						$wc_order_items_qty = array();
						
						foreach( $order_data->get_items() as $item_key => $item_values ){
							
							if($item_values->get_variation_id()){
								$product_item_id = $item_values->get_variation_id();
							}else{
								$product_item_id = $item_values->get_product_id();
							}
							$wc_order_items[] = array('item_key'=>$item_key, 'product_item_id'=>$product_item_id);
							$wc_order_items_qty[$item_key] = $item_values->get_quantity();


							$item = $item_values->get_data();

							
							$product_id = ($item['variation_id'] == 0)?$item['product_id']:$item['variation_id'];

							$product = wc_get_product($product_id);
							
							$variation_attributes = array();

							if(method_exists($product, 'get_variation_attributes')){

								$variation_attributes = $product->get_variation_attributes();
							}
							$attribs = array();
							
							if(!empty($variation_attributes)){
								foreach ($item_values->get_meta_data() as $metaData) {
									$attribute = $metaData->get_data();

									$attribute_key = 'attribute_'.$attribute['key'];
									//pree($attribute_key);
									//pree($variation_attributes);
									
									if(array_key_exists($attribute_key, $variation_attributes)){
										$attribs[] = ucwords($attribute['key']).': '.$attribute['value'];
									}
								}
								
							}


							
							//pree($variation_attributes);
							// wcos_pree($item);exit;

				?>
			<li id="row-<?php echo $item_key; ?>" class="list-group-item li-Item default" data-id="<?php echo $product_id;?>" data-key="<?php echo $item_key;?>">
			<?php echo $item['name'].'<small>'.(!empty($attribs)?'&nbsp;-&nbsp;('.implode(', ', $attribs).')&nbsp;':'').'</small>'.'_________&times;'.$item['quantity'];?>
            <div class="qty_slice_group">
            
            <small><?php _e('Split', 'wc-order-split'); ?>:</small>
            
            <input readonly="readonly" type="number" name="qty_slice_left[<?php echo $item_key;?>][<?php echo $product_id; ?>]" data-value="<?php echo $item['quantity']; ?>" value="<?php echo $item['quantity']; ?>" /> 
			
			<small><?php _e('out of', 'wc-order-split'); ?> </small>
			
			<input readonly="readonly" type="number" name="qty_slice_right[<?php echo $item_key;?>][<?php echo $product_id; ?>]" data-value="<?php echo $item['quantity']; ?>" value="<?php echo $item['quantity']; ?>" />
            
            </div>
            <input type="hidden" name="grouping[default][<?php echo $item_key;?>]">
            </li>			

			<?php } 
			
			} ?>
			</ul>
<script type="text/javascript" language="javascript">
	jQuery(document).ready(function($){
<?php
				$items_io = array();
				if(function_exists('wcos_separate_io_items')){
?>
<?php					
					$items_io = wcos_separate_io_items($wc_order_items, $wc_order_items_qty);
					
					//pree($items_io);
					if(!empty($items_io)){
?>
									
<?php						
						
						foreach($items_io as $io_type => $io_arr){
							
							switch($io_type){
								case 'backorder':
								case 'in_stock':
							
?>
									io_types["<?php echo $io_type; ?>"] = {};
						
<?php							
									if(isset($io_arr['items']) && !empty($io_arr['items'])){
										foreach($io_arr['items'] as $io_item_key=>$io_item){
?>
										io_types["<?php echo $io_type; ?>"]["<?php echo $io_item_key.'_'.$io_item; ?>"] = "<?php echo array_key_exists($io_item_key, $io_arr['quantity'])?$io_arr['quantity'][$io_item_key]:''; ?>";
<?php									
										}
									}
									
							}
						}
						
					}
?>
<?php					
				}


?>            
	});
</script>
            
            <div class="form-group mt-4 wcos_extra_features float-left w-100">                
                <div class="checkbox <?php echo $wcos_pro?'':'disabled'; ?>">
                  <label><input type="checkbox" value="1" name="wcos_remove_selected" <?php echo $wcos_pro?'':'disabled'; ?> /><?php _e('Remove selected items from this order / after split', 'wc-order-split'); ?></label>
                </div>
                <div class="checkbox <?php echo ($wcos_pro && !empty($items_io))?'':'disabled'; ?>">
                  <label><input type="checkbox" value="1" name="wcos_predict_backorder" <?php echo ($wcos_pro && !empty($items_io))?'':'disabled'; ?> /><?php _e('Predict Backorder Split', 'wc-order-split'); ?></label>
                </div>
                <div class="checkbox <?php echo ($wcos_pro && !empty($order_tax))?'':'disabled'; ?>">
                  <label><input type="checkbox" value="1" name="wcos_order_tax" <?php echo ($wcos_pro)?'':'disabled'; ?> /><?php _e('Order Tax - Clone from Parent Order', 'wc-order-split'); ?></label>
                </div>

                <div class="float-left">
                	<a href="https://www.youtube.com/embed/emfL5yV8Ypk" target="_blank" class="btn btn-warning btn-sm mt-3"><?php _e('Watch Premium Version Tutorial', 'wc-order-split'); ?></a>
                </div>
            </div>
 <?php if(count($order_data->get_items())<=1 && !$wcos_pro):  ?>
        <div class="form-group float-left mt-3">
            <div class="alert alert-warning">
            <strong><?php _e('Warning', 'wc-order-split'); ?>!</strong> <?php _e('This order has not enough items to split further', 'wc-order-split'); ?>.
            </div>
        </div>
<?php elseif(!empty($all_classes)): ?>           
            
            <div class="form-group mt-3">
				<button type="submit" class="btn btn-primary form-control btn-sm proceed-btn mb-3" name="submit_form" style="width:200px;"><?php _e('Proceed to Split', 'wc-order-split'); ?></button>
            </div>
            

<?php endif; ?>            
		</form>
        
<?php if(count($order_data->get_items())>1):  ?>    
<?php if(!empty($all_classes)): ?>  
            <div class="col-md-9 pl-0">
            
			<div class="alert alert-info">
           	<strong><?php _e('Info', 'wc-order-split'); ?>!</strong> <?php _e('You can choose any group label and then select items to split in a new order', 'wc-order-split'); ?>.
            </div>            
            <div class="alert alert-warning">
			<strong><?php _e('Warning', 'wc-order-split'); ?>!</strong> <?php _e("In case you don't select any group label", 'wc-order-split'); ?>, <?php _e('still it will automatically pick the first label', 'wc-order-split'); ?>.
            </div> 
            </div>    
<?php else: ?>
			<div class="col-md-12 pl-0 mt-3">			           
            <div class="alert alert-danger clear">
			<strong><?php _e('Required', 'wc-order-split'); ?>!</strong> <?php _e('Please define at least one group label', 'wc-order-split'); ?>. <a href="<?php echo $colors; ?>" target="_blank"><?php _e('Click here', 'wc-order-split'); ?></a> <?php _e('to define a group label', 'wc-order-split'); ?>.
            </div> 
            </div>
<?php endif; ?>               
<?php endif; ?>         
		</div>

		<div class="col-md-3">
		
		<ul class="list-group wcos_order_groups w-100">
        <li class="list-group-item text-center"><a class="btn btn-warning btn-sm" href="<?php echo $colors; ?>" target="_blank"><?php _e('Define Group Labels', 'wc-order-split'); ?></a></li>
	<?php 
		if(!empty($all_classes)){
			foreach ($all_classes as $classes) {
				list($name, $bg, $color, $class) = $classes; 
					
	?>
				<li data-class="<?php echo esc_html($name);?>" class="list-group-item <?php echo esc_html($name);?> liSelection w-100"><?php echo esc_html($name);?></li>
		
	<?php } } ?>
			
		</ul>
        <li class="list-group-item text-center"><a href="https://www.youtube.com/embed/gocz4OdSOkw" target="_blank" class="btn btn-warning btn-sm"><?php _e('How it works', 'wc-order-split'); ?>?</a></li>
            
			

		</div>

		</div>
        
        <div class="row">
<?php 
		$arr = array(
			'numberposts' => -1,
			'post_type' => 'shop_order',
			'post_status' => 'any',
			'meta_key' => 'splitted_from',//'cloned_from',
			'meta_value' => $order_id
		);
		//pree($arr);
		$all_orders = get_posts($arr);
		//pree($all_orders);
		if(!empty($all_orders)){
?>        
        <div class="col-md-12 pl-0">
        <h5><?php _e('Splitted Orders', 'wc-order-split'); ?></h5>
        </div>
        <div class="col-md-12 pl-0">

        <ul class="list-group wcos_orders">
<?php 
		foreach($all_orders as $orders){
			//pree($orders);
?>
		<li class="list-group-item" data-id="<?php echo $orders->ID;?>">
        <a href="<?php echo admin_url(); ?>post.php?post=<?php echo $orders->ID;?>&action=edit" target="_blank">#<?php echo $orders->ID;?></a>
        <span class="<?php echo esc_html(get_post_meta($orders->ID, 'split_group', true)); ?>"></span>
        <small class="ml-4"><?php echo date('d M, Y h:i:s A', strtotime($orders->post_date));?></small>
        </li>
<?php			
		}
?>		
        </ul>
        </div>
<?php 
		}
?>		
</div>

		
</div>
<script type="text/javascript" language="javascript">
jQuery(document).ready(function($) {
	<?php if($wcos_pro && !empty($items_io)): ?>
	if($('input[name="wcos_predict_backorder"]').length>0){
		setTimeout(function(){
			$('input[name="wcos_predict_backorder"]').click();
		}, 100);
	}
	<?php endif; ?>
});
</script>