var io_types = {};
jQuery(document).ready(function($) {
    var class_name;
    $(".liSelection").on('click',function(){
        
        $('.liSelection').removeClass('selected');
        $(this).addClass('selected');
        
    })

    $(".wcos_order_items .li-Item").on('click',function(){
        $(this).toggleClass('default');
		var selected = $('.wcos_order_groups li.liSelection.selected');
		if(selected.length==0){
		   selected = $('.wcos_order_groups li.liSelection').eq(0);
		   selected.addClass('selected');		   
		}
		
		class_name = ($(this).hasClass('default')?'default':selected.data('class'));
		//var id = $(this).data('id'); 27-12-2019
		var id = $(this).data('key');
		
		
		if(!$('input[name="wcos_predict_backorder"]').is(':checked')){
			$(this).find('input[type="hidden"]').attr('name','grouping['+class_name+']['+id+']');
			if($(this).hasClass('default')){
				$(this).attr('class', 'list-group-item li-Item default');			
			}else{
				
					$(this).attr('class', 'list-group-item li-Item flagged '+class_name);
	
			}
			
		}
		
    }).children().click(function(e) {
		return false;
	});

	$('body').on('click', '.wcos_saved_colors a', function(event){
		event.preventDefault();
		var txt;
		var r = confirm("Do you want to delete this label?");
		if (r == true) {
			document.location.href = $(this).attr('href');
		} else {
			return false;
		} 
	});

    

    $("body").on("click",".add_class", function () {
        
        var row_add = '<div class="row">';

        row_add +='<div class="form-group col-md-3"><input type="text" class="form-control" name="class_name[]"></div>';
        row_add +='<div class="form-group col-md-3"><input type="color" class="form-control" name="background_color[]"></div>'; 
        row_add +='<div class="form-group col-md-3"><input type="color" class="form-control" name="color[]" id="txt_color"></div>';         
        row_add += '<div class="form-group col-md-2"><div class="btn btn-success btn-sm add_class">+</div> <div class="btn btn-danger btn-sm del_class">-&nbsp;</div></div>';
        row_add += '</div>'
        //newRow.append(cols);
        //$(":submit").before(row_add);
		$(this).parent().parent().after(row_add);
        counter++;
    });

    $("body").on("click",".del_class" , function () {
        
        $(this).parent().parent().remove();
        
    });
       
	$('input[name="wcos_predict_backorder"]').on('click', function(){
		
		var chk_obj = $(this);
		
		if(chk_obj.is(':checked')){
			$('.qty_slice_group').show();
			$('input[name="wcos_remove_selected"]').prop({'checked':false, 'disabled':true});
			$(".wcos_order_items .li-Item").removeClass('flagged').removeClass('default');
			$.each($(".wcos_order_items .li-Item"), function(){
				$(this).find('input[type="hidden"]').attr('name','grouping[default]['+$(this).data('key')+']');
			});
			$.each($('.wcos_order_groups li'), function(){
				var defined_class = $(this).data('class');
				$(".wcos_order_items .li-Item").removeClass(defined_class);				
			});
		}else{
			$('.qty_slice_group').hide();
			$('input[name="wcos_remove_selected"]').prop({'checked':false, 'disabled':false});
			$(".wcos_order_items .li-Item").addClass('default');
		}
		
		$.each($('.wcos_order_items li'), function(){
			var row_id = $(this).attr('id')+'_'+$(this).data('id');
			row_id = row_id.replace('row-', '');
			var left_obj = $(this).find('input[name^="qty_slice_left["]');
			var right_obj = $(this).find('input[name^="qty_slice_right["]')
			
			
			if(chk_obj.is(':checked')){
				var predicted_left = (typeof io_types["backorder"][row_id]!='undefined'?io_types["backorder"][row_id]:left_obj.data('value'));
				predicted_left = (predicted_left?predicted_left:left_obj.data('value'));
				
				// var predicted_right = (typeof io_types["in_stock"][row_id]!='undefined'?io_types["in_stock"][row_id]:right_obj.data('value'));
				// predicted_right = (predicted_right?predicted_right:right_obj.data('value'));
				var predicted_right = right_obj.data('value');
				left_obj.prop({'readonly':false, 'max':predicted_right, 'min':0}).val(predicted_left);
				//right_obj.prop('readonly', false).val(predicted_right);
			}else{
				left_obj.prop('readonly', true).val(left_obj.data('value'));
				right_obj.prop('readonly', true).val(right_obj.data('value'));
			}
			
		});
	
	});

	function summ_all_rows(){
		var sum = 0;
		$('input[name^="qty_slice_left"]').each(function(){
			sum += parseInt($(this).val());
		});
		return sum;
	}
	$('body').on('keyup, change', 'input[name^="qty_slice_left"]', function(){
		
		if($(this).val() == 0){
			$(this).parents().eq(1).addClass('wcos_line_through');
		}else{
			$(this).parents().eq(1).removeClass('wcos_line_through');
		}
		var sum = summ_all_rows();
		if(sum<=0){
			$('.proceed-btn').prop('disabled', true);
		}else{
			$('.proceed-btn').prop('disabled', false);
		}
	});
	
	$('form.main-split-form').on('submit', function(){
		if($('input[name="wcos_predict_backorder"]').is(':checked')){
			if(summ_all_rows()<=0){
				
				return false;
			}
		}
	});
	

	
	
	
	
  
})

function wcos_removeParam(key) {
    var url = document.location.href;
    var params = url.split('?');
    if (params.length == 1) return;

    url = params[0] + '?';
    params = params[1];
    params = params.split('&');

    $.each(params, function (index, value) {
        var v = value.split('=');
        if (v[0] != key) url += value + '&';
    });

    url = url.replace(/&$/, '');
    url = url.replace(/\?$/, '');

    document.location.href = url;
}