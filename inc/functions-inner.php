<?php
	if(!function_exists('wcos_pre')){
	function wcos_pre($data){
			if(isset($_GET['debug'])){
				wcos_pree($data);
			}
		}	 
	} 	
	if(!function_exists('wcos_pree')){
	function wcos_pree($data){
				echo '<pre>';
				print_r($data);
				echo '</pre>';	
		
		}	 
	} 