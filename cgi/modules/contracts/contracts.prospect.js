/**
 * Javascript (mostly jQuery hopefully) for contracts.prospect
 */
function contracts_prospect()
{
	//
	//New Prospect
	//
	if($("#new_prospect").length){
		var dirty_key = 0;
		$("#new_prospect input[name='prospects_url_key']").keyup(function(){
			if($(this).val()==""){
				dirty_key = 0;
			} else {
				dirty_key = 1;
			}
		})
		$("#new_prospect input[name='prospects_company']").keyup(function(){
			if(!dirty_key){
				$("#new_prospect input[name='prospects_url_key']").val($(this).val().toLowerCase().replace(/[^A-Za-z0-9\s]/g, '').replace(/\s/g, '-'));
			}
		})
	}
	
	$('#parent_prospect_id').change(function(){
		$(this).closest('form').submit();
	});
	
	//
	//Agency Services Order Details
	//
	if($("#order_details.agency_services").length){
		
		$("#order_details .table_row .display_mode").hover(function(){
			$(this).find('button.hidden').show();
		}, function(){
			$(this).find('button.hidden').hide();
		});
		
		$("#order_details #table_header .td").hover(function(){
			$(this).find('button.hidden').show();
		}, function(){
			$(this).find('button.hidden').hide();
		});
		
		$("#order_details #total_header .toggle_show_btn").bind("click", this, function(e){
			var hide_total = 0;
			if($(this).text()=='Hide') hide_total = 1;
			e.data.set_total_display(hide_total);
			return false;
		});
		
		$("#order_vars .table_row .edit_desc_btn").bind("click", this, function(e){
			e.data.var_edit_mode($(this).closest('.table_row'));
			return false;
		});
		
		$("#order_vars .table_row .toggle_show_btn").bind("click", this, function(e){
			var display = 0;
			if($(this).text()=='Show') display = 1;
			e.data.set_var_display($(this).closest('.table_row'), display);
			return false;
		});
		
		$("#order_vars .table_row .save_desc_btn").bind("click", this, function(e){
			e.data.save_var_desc($(this).closest('.table_row'));
			return false;
		});
		
		$("#order_vars .table_row .cancel_desc_btn").bind("click", this, function(e){
			e.data.var_display_mode($(this).closest('.table_row'));
			return false;
		});
		
		$("#order_vars").sortable({
			items: "> div.table_row",
			containment: "parent",
			axis: "y",
			tolerance: "pointer"
		});
		
		$("#order_vars").bind("sortstop", this, function(e, ui){
			e.data.update_row_order();
		});
	}
	
	
	//Bind handlers for selecting packages
	$("#package_chooser .pkg-select").bind("change", this, function(e){
		e.data.hide_package_edit($(this).attr('service'));
		if($(this).val()!=""){
			e.data.show_package_edit($(this).val(), $(this).attr('service'));
		}
	});
	
	//
	//Service Option Handlers
	//
	$('#service_options .change_package').bind("click", this, function(e){
		$(this).parent().hide().siblings('.edit_package_select').show().siblings('.edit_package_vars, .package_title').hide();
		return false;
	});
	
	$('#service_options .delete_package').bind("click", this, function(e){
		if(confirm("Are you sure you want to remove this package?")){
			e.data.delete_package($(this).closest('.edit_service'));
		}
		return false;
	});
	
	$('#service_options .save_change_package').bind("click", this, function(e){
		e.data.change_package($(this).closest('.edit_service'));
		return false;
	});
	
	$('#service_options .cancel_change_package').bind("click", this, function(e){
		$(this).parent().hide().siblings('.edit_actions, .edit_package_vars, .package_title').show();
		return false;
	});
	
	$('#service_options .add_package').bind("click", this, function(e){
		$(this).hide().siblings('.add_package_select').show();
		return false;
	});
	
	$('#service_options .cancel_add_package').bind("click", this, function(e){
		$(this).parent().hide().siblings('.add_package').show();
		return false;
	});
	
	$('#service_options .save_add_package').bind("click", this, function(e){
		var $edit_service = $(this).closest('.edit_service');
		if($edit_service.find('.add_package_id').val()){
			e.data.save_add_package($edit_service);
		} else {
			alert('Please select a package.');
		}
		return false;
	});
	
	$('#service_options .save_package_vars').bind("click", this, function(e){
		e.data.save_package_vars($(this).closest('.edit_service'));
		return false;
	});
	
	$('#service_options .add_package_var').bind("click", this, function(e){
		e.data.add_package_var(e, $(this).closest('.edit_service'));
		return false;
	});
	
	$('#service_options .delete_var').bind("click", this, function(e){
		e.data.delete_package_var($(this).closest('.package_var'));
		return false;
	});

	var $client_type_radio = $('.client_type_radio');
	if ($client_type_radio.length) {
		$client_type_radio.on('click', this, function(e){ e.data.client_type_radio_click(); });
		// click the button that is selected
		$client_type_radio.filter(':checked')[0].click();
		$('#client_option_submit').on('click', this, function(e){ return e.data.client_option_submit_click(); });
	}
}

// EDIT ORDER TABLE FUNCTIONS //
contracts_prospect.prototype.set_total_display = function(hide_total){
	this.post('edit_total_display', {
		hide_total: hide_total
	});
}
contracts_prospect.prototype.edit_total_display_callback = function(request, response){
	if (response == 'TRUE') {
		window.location.reload(true);
	}
}

contracts_prospect.prototype.set_var_display = function($row, display){
	this.post('edit_var_display', {
		package_id: $row.attr('package_id'),
		name: $row.attr('var_name'),
		order_table: display
	});
}
contracts_prospect.prototype.edit_var_display_callback = function(request, response){
	if (response == 'TRUE') {
		window.location.reload(true);
	}
}

contracts_prospect.prototype.update_row_order = function(){
	$("#order_vars").sortable("disable");
	this.post('edit_var_order', {
		data: $("#order_vars").sortable("toArray")
	});
}
contracts_prospect.prototype.edit_var_order_callback = function(request, response){
	//dbg(request);
	//dbg(response);
	$("#order_vars").sortable('enable');
}

contracts_prospect.prototype.var_edit_mode = function($row){
	$row.find('.display_mode').hide().siblings('.edit_mode').show();
}

contracts_prospect.prototype.var_display_mode = function($row){
	$row.find('.edit_mode').hide().siblings('.display_mode').show();
}

contracts_prospect.prototype.save_var_desc = function($row){
	//alert($row.find('.edit_desc').val());
	var request = {
		row_id: $row.attr('id'),
		package_id: $row.attr('package_id'),
		name: $row.attr('var_name'),
		description: $row.find('.edit_desc').val(),
		note: $row.find('.edit_note').val()
	}
	this.post('edit_package_var', request);
}
contracts_prospect.prototype.edit_package_var_callback = function(request, response){
	var $row = $('#'+request.row_id);
	if (response != 'TRUE') {
		alert('Invalid description.')
	} else {
		$row.find('.description').text(request.description);
		$row.find('.note').text(request.note);
	}
	this.var_display_mode($row);
}

// EDIT PROSPECT FUNCTIONS //
contracts_prospect.prototype.save_add_package = function($edit_service){
	var request = {
		package_id: $edit_service.find('.add_package_id').val()
	}
	this.show_package_loading($edit_service);
	this.post('add_package', request);
}

contracts_prospect.prototype.show_package_loading = function($edit_service){
	$edit_service.find('.update_package_buttons').html('');
	$edit_service.find('.edit_package_vars').html('<div>Package being created, please wait for page to reload.. '+e2.loading()+'</div>');
}

contracts_prospect.prototype.add_package_callback = function(request, response){
	if (response != 'TRUE'){
		Feedback.add_error_msg(String(response));
	} else {
		window.location.reload(true);
	}
}

contracts_prospect.prototype.delete_package = function($edit_service){
	var request = {
		package_id: $edit_service.find('.package_id').val()
	}
	this.post('delete_package', request);
}
contracts_prospect.prototype.delete_package_callback = function(request, response){
	window.location.reload(true);
}

contracts_prospect.prototype.change_package = function($edit_service){
	var request = {
		package_id: $edit_service.find('.package_id').val(),
		new_package_id: $edit_service.find('.edit_package_id').val()
	}
	this.show_package_loading($edit_service);
	this.post('change_package', request);
}
contracts_prospect.prototype.change_package_callback = function(request, response){
	window.location.reload(true);
}

contracts_prospect.prototype.save_package_vars = function($edit_service){
	//make sure all required vars have been filled out
	var valid = true;
	var package_vars = new Array;
	
	$edit_service.find(".package_var").each(function(){
		
		$packge_val = $(this).find("input[type='text'].edit_val");
		$packge_disc = $(this).find("input[type='text'].edit_disc");
		$packge_charge = $(this).find(".edit_chrg");
		
		if($packge_val.is(".required") && $packge_val.val()==""){
			alert('Please make sure to complete all required fields.')
			valid = false;
			//Break the each loop by returning false
			return false;
		}
		
		var charge = 0;
		if($packge_charge.length){
			charge = $packge_charge.is(":checked")?1:0;
		}
		
		var discount = 0;
		if($packge_disc.length){
			discount = $packge_disc.val();
		}
		
		package_vars.push($packge_val.attr('var_name')+":"+$packge_val.val()+":"+charge+":"+discount);
	});
	
	if(valid){
		var request = {
			package_id: $edit_service.find('.package_id').val(),
			'package_vars[]': package_vars
		}
		this.post('save_package_vars', request);
	}
}
contracts_prospect.prototype.save_package_vars_callback = function(request, response) {
	//dbg(response);
	window.location.reload(true);
}

contracts_prospect.prototype.add_package_var = function(e, $edit_service)
{
	var self = this;
	var $pkg_id = $edit_service.find('.package_id').val();
	var box_id = "add_var_box_"+$pkg_id;
	
	$.box({
		title:"Add Var",
		id:box_id,
		event:e,
		close:true,
		content:''
	});
	
	//Put controls in the box
	var $box = $("#"+box_id);
	if ($box.find(".add_package_var").length == 0) {
		
		$box.find('.content').append($("#add_package_var_container .add_package_var").clone());
		
		$box.find(".save_new_package_var").click(this, function(e)
		{
			var request = {
				package_id: $pkg_id,
				name: $box.find('.add_name').val(),
				description: $box.find('.add_description').val(),
				type: $box.find('.add_charge_method').val(),
				payment_part_type: $box.find('.add_payment_type').val(),
				value: $box.find('.add_value').val(),
				charge: $box.find('.add_charge').is(':checked')?1:0,
				required: 0,
				order_table: 1
			};
			
			//validate name
			if(request.name==""){
				alert('Variable name is required.');
				return false;
			}
			
			self.post('add_package_var', request);
			return false;
		});
		
		$box.find(".cancel_new_package_var").click(function()
		{
			$.box_close_click($(this));
			return false;
		});
	}
}
contracts_prospect.prototype.add_package_var_callback = function(request, response) {
	window.location.reload(true);
}

contracts_prospect.prototype.delete_package_var = function($package_var)
{
	var request = {
		package_id: $package_var.closest('.edit_service').find('.package_id').val(),
		name: $package_var.find('.edit_val').attr('var_name')
	};
	this.post('delete_package_var', request);
}
contracts_prospect.prototype.delete_package_var_callback = function(request, response)
{
	window.location.reload(true);
}


contracts_prospect.prototype.client_type_radio_click = function(){
	var checked = $('.client_type_radio:checked').val();
	if (checked == 'create_new') {
		$('#w_client_existing_input').hide();
	}
	else {
		$('#w_client_existing_input').show();
	}
};

contracts_prospect.prototype.client_option_submit_click = function(){
	// check user has selected tie to existing but no client was selected
	var
		checked = $('.client_type_radio:checked').val(),
		client_val = $('#tie_to_client').val()
	;
	if (checked == 'tie_to_existing' && !client_val) {
		alert('Please select the client you would like to tie this prospect to.');
		$('#client_select_tie_to_client').focus();
		return false;
	}
	else {
		return true;
	}
};

// NEW PROSPECT FUNCTIONS //
contracts_prospect.prototype.show_package_edit = function(package_id, service) {
	var request = {
		pkg_id: package_id,
		service: service
	};
	this.post('show_package_edit', request);
}
contracts_prospect.prototype.show_package_edit_callback = function(request, response) {
	//alert(response);
	$("#serv-" + request.service).append(response);
}

contracts_prospect.prototype.hide_package_edit = function(service) {
	$("#serv-" + service).empty();
}