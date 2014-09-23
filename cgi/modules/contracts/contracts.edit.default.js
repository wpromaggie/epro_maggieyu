/**
 * This function is called when the SAP edit default module page loads.
 * It's kind of like a constructor, but not exactly.
 */
function contracts_edit_default()
{
	//Changing up the layout
	$('#layout_select').change(function(){
		$(this).closest('form').submit();
		return false;
	});

	
	//Editing elements
	this.$edit_buffer = $("#edit_buffer");
	this.$edit_display = $("#edit_display");

	//Add node package controls to page
	$("#sap_edit_controls .leaf_edit textarea.edit_text").after("<button type='button' class='edit_pkg_btn'>Package</button>");
	$("#sap_edit_controls #branch_edit button.delete_branch_btn").after("<button type='button' class='edit_pkg_btn'>Package</button>");

	$(document).on('click', '#proposal .branch', this, function(e){
		var $target = $(e.target);
		if ($target.hasClass("edit_pkg_btn")) {
			e.data.show_package_box(e, $target);
		}
		e.stopPropagation();
	});
}

contracts_edit_default.prototype.show_package_box = function(e, $target) {
	//Create a box
	var $node = $target.closest(".node");
	var box_id = "edit_pkg_box_"+$node.attr("id");

	$.box({
		title:"Select Packages",
		id:box_id,
		event:e,
		close:true,
		content:''
	});

	//Put controls in the box
	var $box = $("#"+box_id);
	if ($box.find(".edit_package").length == 0) {
		$box.find(".content").append($("#default_edit_controls .edit_package").clone());
		$box.find("button.save_pkg_btn").click(this, function(e){
			var packages = $box.find("select.pkg_select").val();
			e.data.edit_node_package($node, packages);
			$.box_close_click($(this));
		});
		$box.find("button.cancel_pkg_btn").click(function(){
			$.box_close_click($(this));
		});
	}
	
}

contracts_edit_default.prototype.edit_node_package = function($node, packages) {

	//Get values
	var node_id = $node.attr("id");
	var op_num = window.modules.contracts_edit.num_ops;
	
	var package_vals = new Array();
	var package_names = new Array();
	
	if(packages){
		for(var i=0;i<packages.length;i++){
			var package_parts = packages[i].split(':');
			package_vals.push(package_parts[0]);
			package_names.push(package_parts[1]);
		}
	}

	//Change packages
	$node.attr("node_package", package_vals);
	$node.children("div.package").text(package_names.join(","));

	//Put in edit buffer
	this.$edit_display.append("<p>" + ["package",node_id,package_vals].join(", ") + "</p>");
	this.$edit_buffer.append('\
		<input type="hidden" name="ops_array['+op_num+'][op_type]" value="package" />\
		<input type="hidden" name="ops_array['+op_num+'][node_id]" value="'+node_id+'" />\
		<input type="hidden" name="ops_array['+op_num+'][packages]" value="'+package_vals+'" />\
	');
	window.modules.contracts_edit.alert_edit();

	//Increment number of ops
	window.modules.contracts_edit.num_ops++;
}