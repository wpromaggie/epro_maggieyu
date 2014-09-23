/**
 * This function is called when the SAP edit default module page loads.
 * It's kind of like a constructor, but not exactly.
 */
function sap_edit_default()
{
	//Editing elements
	this.$edit_buffer = $("#edit_buffer");
	this.$edit_display = $("#edit_display");

	//Add node package controls to page
	$("#sap_edit_controls .leaf_edit textarea.edit_text").after("<button type='button' class='edit_pkg_btn'>Package</button>");
	$("#sap_edit_controls #branch_edit button.delete_branch_btn").after("<button type='button' class='edit_pkg_btn'>Package</button>");

	$("#proposal .branch").live("click", this, function(e){
		var $target = $(e.target);
		if ($target.hasClass("edit_pkg_btn")) {
			e.data.show_package_box(e, $target);
		}
		e.stopPropagation();
	});
}

sap_edit_default.prototype.show_package_box = function(e, $target) {
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
			if (packages != null) {
				e.data.edit_node_package($node, packages);
				$.box_close_click($(this));
			}
		});
		$box.find("button.cancel_pkg_btn").click(function(){
			$.box_close_click($(this));
		});
	}

	//Set the current packages
	//$wah.wah(wah)
}

sap_edit_default.prototype.edit_node_package = function($node, packages) {

	//Get values
	var node_id = $node.attr("id");
	var op_num = window.modules.sap_edit.num_ops;

	//Change packages
	$node.attr("node_package", packages);
	$node.children("div.package").text(packages.join(","));

	//Put in edit buffer
	this.$edit_display.append("<p>" + ["package",node_id,packages].join(", ") + "</p>");
	this.$edit_buffer.append('\
		<input type="hidden" name="ops_array['+op_num+'][op_type]" value="package" />\
		<input type="hidden" name="ops_array['+op_num+'][node_id]" value="'+node_id+'" />\
		<input type="hidden" name="ops_array['+op_num+'][packages]" value="'+packages+'" />\
	');

	//Increment number of ops
	window.modules.sap_edit.num_ops++;
}