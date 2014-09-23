/**
 * Package editing for SAP
 */
function sap_package()
{
	//Add the button actions
	$("#package_edit button.add_var_btn").bind("click", this, function(e){
		e.data.add_var($(this).closest("div.package_var_add"));
		return false;
	});
	$("#package_edit button.edit_var_btn").bind("click", this, function(e){
		e.data.edit_var($(this).closest("div.package_var"));
		return false;
	});
	$("#package_edit button.delete_var_btn").bind("click", this, function(e){
		e.data.delete_var($(this).closest("div.package_var"));
		return false;
	});
}

/**
 * Add a new package variable.
 */
sap_package.prototype.add_var = function($package_var_add) {
	var request = {
		pkg: $package_var_add.siblings("input.pkg_name").val(),
		name: $package_var_add.find("input.add_name").val(),
		value: $package_var_add.find("input.add_value").val(),
		required: $package_var_add.find("input.add_required").prop("checked"),
		charge: $package_var_add.find("input.add_charge").prop("checked")
	};
	this.post('add_var', request);
}
sap_package.prototype.add_var_callback = function(request, response) {
	if (response != 'TRUE') {
		Feedback.add_error_msg(String(response));
	} else {
		window.location.reload(true);
	}
}

/**
 * Edit existing package var
 */
sap_package.prototype.edit_var = function($package_var) {
	var request = {
		pkg: $package_var.siblings("input.pkg_name").val(),
		name: $package_var.find("input.edit_name").val(),
		value: $package_var.find("input.edit_value").val(),
		required: $package_var.find("input.edit_req").prop("checked"),
		charge: $package_var.find("input.edit_chrg").prop("checked")
	};
	this.post('edit_var', request);
}
sap_package.prototype.edit_var_callback = function(request, response) {
	if (response != 'TRUE') {
		Feedback.add_error_msg(String(response));
	} else {
		window.location.reload(true);
	}
}

/**
 * Delete exisiting package var
 */
sap_package.prototype.delete_var = function($package_var) {
	var request = {
		pkg: $package_var.siblings("input.pkg_name").val(),
		name: $package_var.find("input.edit_name").val()
	};
	this.post('delete_var', request);
}
sap_package.prototype.delete_var_callback = function(request, response) {
	if (response != 'TRUE') {
		Feedback.add_error_msg(String(response));
	} else {
		window.location.reload(true);
	}
}
