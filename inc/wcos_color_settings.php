<?php defined( 'ABSPATH' ) or die( __('No script kiddies please!', 'wc-order-split') );
	global $wcos_data, $wcos_pro;

	$successfully_saved = false;
	
	if(isset($_GET['class_id'])){
		$arg = get_option('wcos_colors', array());
		$class_id = sanitize_wcos_data($_GET['class_id']);
		if(array_key_exists($class_id, $arg)){		
			unset($arg[$class_id]);
			//pree($arg);exit;
			update_option('wcos_colors', $arg);
		}
	}
	
	
	
	if(isset($_POST['add_class'])){
	
			if ( ! isset( $_POST['wcos_order_colors_field'] ) 
				|| ! wp_verify_nonce( $_POST['wcos_order_colors_field'], 'wcos_order_colors_action' ) 
			) {
			   print __('Sorry, your nonce did not verify.', 'wc-order-split');
			   exit;
			} else {
				
				$class_name = sanitize_wcos_data($_POST['class_name']);
				$background_color = sanitize_wcos_data($_POST['background_color']);
				$color = sanitize_wcos_data($_POST['color']);
				//pree($background_color);
				//pree($color);
				
				$len = sizeof($class_name);	
				$arg = get_option('wcos_colors', array());
				$arg = (is_array($arg)?$arg:array());
				for($i=0; $i<$len; $i++){
					if(trim($class_name[$i])){
						$class_name[$i] = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '_', $class_name[$i]));
						$css_class = '.'.$class_name[$i].'{background-color:'.$background_color[$i].'; color:'.$color[$i].'}';
						$arg[$class_name[$i]] = array($class_name[$i], $background_color[$i], $color[$i], $css_class);
						update_option('wcos_colors', sanitize_wcos_data($arg));
						$successfully_saved = true;
					}
				}
				
			}
	}
	
	$class_arr = array();
	$arg = get_option('wcos_colors', array());
	$all_classes = (is_array($arg)?$arg:array());
	//pree($all_classes);
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
				<div class="icon32" id="icon-options-general"><br></div><h4><?php echo $wcos_data['Name']; ?> <?php echo '('.$wcos_data['Version'].($wcos_pro?') Pro':')'); ?> - <?php _e("Order","wc-order-split"); ?> <?php _e("Group","wc-order-split"); ?> <?php _e("Management","wc-order-split"); ?> </h4> 
			</div>
	<?php if($successfully_saved): ?>
			<div class="row">
				<div class="alert alert-success">
				<strong> <?php _e('Success', 'wc-order-split'); ?>!</strong> <?php _e('Group label added successfully', 'wc-order-split'); ?>.
				</div>
			</div>
	<?php endif; ?>        
	
			<div class="row">     
			
			<div class="col-md-9 pl-0">
			<div class="row">        
			<div class="col-md-3"><?php _e('Group Label', 'wc-order-split'); ?></div>
			<div class="col-md-3"><?php _e('Background Color', 'wc-order-split'); ?></div>
			<div class="col-md-3"><?php _e('Text Color', 'wc-order-split'); ?></div>
			<div class="col-md-3"><?php _e('Action', 'wc-order-split'); ?></div>
			</div>
			<hr>
			<form method="post" class="mx-auto wcos-colors-form">
			<?php wp_nonce_field( 'wcos_order_colors_action', 'wcos_order_colors_field' ); ?>
			<div class="row">
	
				<div class="form-group col-md-3">				
						<input type="text" class="form-control" name="class_name[]">
				</div> 
	
				<div class="form-group col-md-3">				
					<input type="color" class="form-control" name="background_color[]" id="class_bg_color" />
				</div>
				  
				  <div class="form-group col-md-3">			  
					<input type="color" class="form-control" name="color[]" id="class_txt_color" />
				  </div>
				  
				  <div class="form-group col-md-3">
					<div class="btn btn-success btn-sm add_class">+</div>
					<div class="btn btn-danger btn-sm clear">-&nbsp;</div>
				  </div>
				</div>
				
				<div class="form-group">
					
						<button type="submit" name="add_class" class="btn btn-success form-control btn-sm mt-3 mb-3" style="width:240px">Add Group</button>
					
				</div>
				
				
				
				<div class="alert alert-primary">
				 <strong><?php _e('Important', 'wc-order-split'); ?>!</strong> <?php _e('Maybe you are new to', 'wc-order-split'); ?> <a href="<?php echo plugins_url('img/colorpicker-help.png', dirname(__FILE__)); ?>" target="_blank"><?php _e('HTML color picker', 'wc-order-split'); ?></a>, <?php _e('kindly move the arrow to choose the color', 'wc-order-split'); ?>.
				</div>
				
				<div class="alert alert-info">
				 <strong><?php _e('Info', 'wc-order-split'); ?>!</strong> <?php _e('You can add as many as group labels you want', 'wc-order-split'); ?>. <?php _e('These are just a kind of bookmark helpers in splitting action', 'wc-order-split'); ?>. <?php _e('You can group multiple items within an order and split accordingly', 'wc-order-split'); ?>.
				</div>
				
				<div class="alert alert-warning">
				 <strong><?php _e('Warning', 'wc-order-split'); ?>!</strong> <?php _e('Saved labels cannot be edited, you can delete and create new', 'wc-order-split'); ?>. <?php _e('It will not effect any database value if you delete and create the same label again', 'wc-order-split'); ?>. <?php _e('Special characters and numbers are not allowed', 'wc-order-split'); ?>,<?php _e('those will be replaced with and'); ?> "<?php _e('underscore','wc-order-split'); ?> " <?php _e('automatically', 'wc-order-split'); ?> .
				</div>            
			</form>  
			</div>
			<div class="col-md-2 ml-5 wcos_saved_colors h-100">
			<div class="row w-100 mb-3">
			<button class="btn btn-default w-100"><b class="badge"><?php _e('Saved Labels', 'wc-order-split'); ?></b></button>
			</div>
					<?php foreach ($all_classes as $classes) {
						list($name, $bg, $color, $class) = $classes;                
					?>
					<div class="row w-100 mb-1" title="<?php echo esc_html($name);?>">
						<a href="admin.php?page=wcos_colors&class_id=<?php echo esc_html($name); ?>" class="btn <?php echo $name?esc_html($name):'text-white'; ?> w-100"> <?php echo esc_html($name);?> </a>
					</div>               
					<?php } ?> 
			</div>	
			</div>		
	</div>
	<script type="text/javascript" language="javascript">
	jQuery(document).ready(function($) {
		<?php if(isset($_GET['class_id'])): ?>
			wcos_removeParam('class_id');
		<?php endif; ?>	
	});
	</script>        