/**
 * Package editing
 */
function contracts_package()
{
	$('#layout_select').change(function(){
		$(this).closest('form').submit();
	});
	
	$('#service_select').bind('change', this, function(e){
		e.data.remove_package_select();
		if($(this).val()!=""){
			e.data.show_package_select($(this).val());
		} else {
			e.data.hide_package_select();
		}
	});
	
	$('#package_select').bind('change', this, function(e){
		e.data.remove_package_select();
		if($(this).val()!=""){
			e.data.show_package_edit($(this).val());
		}
	});

	$(document).on('click', '.save_package_var', this, function(e){
		e.data.save_package_var($(this).closest('.package_var'));
		return false;
	});
	
	$(document).on('click', '.delete_package_var', this, function(e){
		e.data.delete_package_var($(this).closest('.package_var'));
		return false;
	});
	
	$(document).on('click', '.add_var_btn', this, function(e){
		e.data.add_package_var($(this).closest('.package_var_add'));
		return false;
	});
	
	$(document).on('click', '.delete_package_btn', this, function(){
		var msg = "Are you sure you want to delete this package?\nAll related contract text will be removed as well.";
		if(!confirm(msg)) return false;
		return true;
	});
	
	$(document).on('click', '.edit_package_name', this, function(e){
		e.data.toggle_package_name($(this));
		return false;
	});
	
	$(document).on('click', '.save_package_name', this, function(e){
		var name;
		if(name = e.data.save_package_name($(this).siblings('input'))){
			$(".title_display .package_name").text(name);
			e.data.toggle_package_name($(this));
		}
		return false;
	});
	
	$(document).on('click', '.cancel_package_name', this, function(e){
		e.data.toggle_package_name($(this));
		return false;
	});
	
}

/*
 * EDIT PACKAGE
 */
contracts_package.prototype.save_package_name = function($input)
{
	var name = $input.val();
	if(name==""){
		alert("Please enter a name.");
		return false;
	}
	
	var request = {
		package_id: $('.package_id').val(),
		name: name
	}
	
	this.post('save_package', request);
	return name;
}
contracts_package.prototype.save_package_callback = function(request, response){
	//alert(response);
}


contracts_package.prototype.toggle_package_name = function($button)
{
	$button.parent().hide().siblings().show();
}
/*
 * ADD PACKAGE VAR
 */
contracts_package.prototype.add_package_var = function($package_var){
	var request = {
		package_id: $package_var.siblings('input.package_id').val(),
		name: $package_var.find('.add_name').val(),
		description: $package_var.find('.add_description').val(),
		type: $package_var.find('.add_charge_method').val(),
		payment_part_type: $package_var.find('.add_payment_type').val(),
		value: $package_var.find('.add_value').val(),
		required: $package_var.find('.add_required').is(':checked')?1:0,
		charge: $package_var.find('.add_charge').is(':checked')?1:0,
		order_table: $package_var.find('.add_order_table').is(':checked')?1:0
	};
	//validate name
	if(request.name==""){
		alert('Variable name is required.');
		return;
	}
	//dbg(request);
	this.post('add_package_var', request);
}
contracts_package.prototype.add_package_var_callback = function(request, response){
	if(response){
		$('.package_id[value='+request.package_id+']').siblings('.package_vars').append(response);
		
		//clear add var fields
		$('.package_var_add input[type!="submit"]').val('');
		$('.package_var_add textarea').val('');
		$('.package_var_add input[type="checkbox"]').attr('checked', 'checked');
	} else {
		alert('Failed.');
	}
}

/*
 * DELETE PACKAGE VAR
 */
contracts_package.prototype.delete_package_var = function($package_var){
	var request = {
		package_id: $('#package_edit').find('input.package_id').val(),
		name: $package_var.find('.var_name').val()
	};
	this.post('delete_package_var', request);
}
contracts_package.prototype.delete_package_var_callback = function(request, response){
	$('input[value='+request.name+']').parent().fadeOut();
}

/*
 * SAVE PACKAGE VAR
 */
contracts_package.prototype.save_package_var = function($package_var){
	var request = {
		package_id: $('input.package_id').val(),
		name: $package_var.find('.var_name').val(),
		description: $package_var.find('.edit_desc').val(),
		type: $package_var.find('.edit_charge_method').val(),
		payment_part_type: $package_var.find('.edit_payment_type').val(),
		value: $package_var.find('.edit_val').val(),
		required: $package_var.find('.edit_req').is(':checked')?1:0,
		charge: $package_var.find('.edit_chrg').is(':checked')?1:0,
		order_table: $package_var.find('.edit_ord_tbl').is(':checked')?1:0
	};
	this.post('save_package_var', request);
}
contracts_package.prototype.save_package_var_callback = function(request, response){
	if (response != 'TRUE') {
		alert(String(response));
	} else {
		alert('Save Success!');
	}
}

/*
 * SHOW PACKAGE EDIT
 */
contracts_package.prototype.show_package_edit = function(package_id){
	var request = {
		package_id: package_id
	};
	this.post('show_package_edit', request);
}
contracts_package.prototype.show_package_edit_callback = function(request, response){
	$("#package_edit").append(response);
	$("#package_actions").show();
}

/*
 * REMOVE PACKAGE EDIT
 */
contracts_package.prototype.remove_package_select = function() {
	$("#package_edit").empty();
	$("#package_actions").hide();
}

/*
 * SHOW PACKAGE SELECT
 */
contracts_package.prototype.show_package_select = function(service) {
	var request = {
		service: service
	};
	this.post('show_package_select', request);
}
contracts_package.prototype.show_package_select_callback = function(request, response) {
	$("#package_select").html(response).show();
}

/*
 * HIDE PACKAGE SELECT
 */
contracts_package.prototype.hide_package_select = function() {
	$("#package_select").empty().hide();
	$("#package_actions").hide();
}