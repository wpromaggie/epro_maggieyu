/**
 * Javascript (mostly jQuery hopefully) for sap.prospect
 */
function sap_prospect()
{
	//Deal with selected packages
	//var self = this;
	//$("#package_chooser input.pkg_chk:checked").each(function(){
	//	self.show_package_edit($(this).val());
	//});

	//Bind handlers for selecting packages
	$("#package_chooser input.pkg_chk").bind("click", this, function(e){
		if (this.checked) {
			e.data.show_package_edit($(this).val());
		} else {
			e.data.hide_package_edit($(this).val());
		}
	});
}

sap_prospect.prototype.show_package_edit = function(package_name) {
	var request = {
		pkg_name: package_name
	};
	this.post('show_package_edit', request);
}
sap_prospect.prototype.show_package_edit_callback = function(request, response) {
	$("#pkg_" + request.pkg_name).append(response);
}

sap_prospect.prototype.hide_package_edit = function(package_name) {
	$("#pkg_" + package_name).empty();
}