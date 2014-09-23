/**
 * This function is called when the SAP edit module page loads.
 * It's kind of like a constructor, but not exactly.
 */
function sap_edit()
{
	//Store the number of new nodes created and number of operations
	this.num_new_nodes = 0;
	this.num_ops = 0;

	//Store the order of a node when it starts moving
	this.start_move_order = 0;

	//Store editing stuff for later access
	this.$branch_edit = $("#branch_edit");
	this.$branch_edit_type = $("#branch_edit .node_type");
	this.$leaf_edit = $("#sap_edit_controls .leaf_edit");
	this.$edit_buffer = $("#edit_buffer");
	this.$edit_display = $("#edit_display");

	//Store sortable options so we don't have to keep creating
	this.sortable_options = {
		items: "> div.node",
		containment: "parent",
		axis: "y",
		tolerance: "pointer"
	};

	//Bind event handlers for Click and Sortable events
	var $sap_branches = $("#proposal .branch");
	$sap_branches.live("click", this, function(e){

		//Handle all click events on branch nodes
		var $parent_branch = $(this);
		if ($parent_branch.hasClass("editing")) {
			//Check and delegate for various node editing buttons
			var $target = $(e.target);
			if ($target.hasClass("add_btn")) {
				e.data.add_node($parent_branch);
			}
			else if ($target.hasClass("delete_branch_btn")) {
				e.data.delete_node($parent_branch);
			}
			else if ($target.hasClass("delete_btn")) {
				e.data.delete_node($target.closest(".leaf"));
			}
			else if($target.hasClass("edit_btn")) {
				e.data.start_leaf_edit($target.closest(".leaf"));
			}
			else if($target.hasClass("save_btn")) {
				e.data.stop_leaf_edit($target.closest(".leaf"), true);
			}
			else if($target.hasClass("cancel_btn")) {
				e.data.stop_leaf_edit($target.closest(".leaf"), false);
			}
		} else {
			e.data.set_branch_edit($parent_branch);
		}

		//Stop event bubbling
		e.stopPropagation();
	});
	$sap_branches.live("sortstart", this, function(e, ui){

		//Store initial order of moving node
		e.data.start_move_order = ui.item.prevAll("div.node").length;
		//e.data.$edit_display.append("<p>startmove: "+e.data.start_move_order+"</p>")

		//stop event bubbling
		e.stopPropagation();
	});
	$sap_branches.live("sortstop", this, function(e, ui){

		//Handle node movement events
		var end_move_order = ui.item.prevAll("div.node").length;
		//e.data.$edit_display.append("<p>endmove: "+end_move_order+"</p>");

		if (e.data.start_move_order != end_move_order) {
			e.data.move_node(ui.item, $(this), e.data.start_move_order, end_move_order);
		}

		//Stop event bubbling
		e.stopPropagation();
	});

	//Bind events for double-click editing
	$("#proposal").bind("dblclick", this, function(e){
		var $leaf_node = $(e.target).closest(".leaf");
		if ($leaf_node.length > 0 && !$leaf_node.hasClass("editing")) {
			e.data.start_leaf_edit($leaf_node);
		}
	});
}

/**
 * Sets the given branch node as the editable node in the SAP.
 */
sap_edit.prototype.set_branch_edit = function($branch_node) {

	var $edit_parent = this.$branch_edit.closest(".branch");

	//Don't do anything if already editing
	if ($branch_node.is($edit_parent)) {
		return;
	}

	//Deal with previous editing node
	this.$branch_edit.hide();
	$edit_parent.removeClass("editing");
	$edit_parent.sortable("destroy");
	var self = this;
	$edit_parent.children(".leaf.editing").each(function(){
		self.stop_leaf_edit($(this), false);
	});
	$edit_parent.children(".leaf").children(".leaf_edit").remove();
	this.$branch_edit.detach();

	//Deal with new editing node
	$branch_node.append(this.$branch_edit);
	$branch_node.children(".leaf").append($(this.$leaf_edit).clone());
	$branch_node.sortable(this.sortable_options);
	$branch_node.addClass("editing");
	this.$branch_edit.show();
}

sap_edit.prototype.start_leaf_edit = function($leaf_node) {

	$leaf_node.addClass("editing")
	var $text_div = $leaf_node.find("div.text");
	var $text_edit = $leaf_node.find("textarea.edit_text");

	//Move text to edit area
	$text_edit.val($text_div.text());

	//Show & hide UI elements
	$leaf_node.find("button.edit_btn, button.delete_btn").add($text_div).hide();
	$leaf_node.find("button.save_btn, button.cancel_btn").add($text_edit).show();

	$text_edit.focus();
	$text_edit.select();
}

sap_edit.prototype.stop_leaf_edit = function($leaf_node, is_save) {

	$leaf_node.removeClass("editing")
	var $text_div = $leaf_node.find("div.text");
	var $text_edit = $leaf_node.find("textarea.edit_text");

	//If saving, then do stuff
	if (is_save) {
		$text_div.text($text_edit.val());
		this.edit_node($leaf_node);
	}

	//Show & hide UI elements
	$leaf_node.find("button.save_btn, button.cancel_btn").add($text_edit).hide();
	$leaf_node.find("button.edit_btn, button.delete_btn").add($text_div).show();

}

sap_edit.prototype.add_node = function($parent_branch) {

	//Check that values are set
	if (this.$branch_edit_type.val() == '') {
		alert("Please set node type");
		return;
	}

	//Get info for new node
	var new_node_id = "new_" + this.num_new_nodes;
	var parent_id = $parent_branch.attr("id");
	var node_type = this.$branch_edit_type.children("option:selected").text();
	var node_order = this.$branch_edit.prevAll("div.node").length;
	var node_structure = this.$branch_edit_type.val();

	//Add to edit buffer
	this.$edit_display.append("<p>" + ["add",new_node_id,parent_id,node_type,node_order].join(", ") + "</p>");
	this.$edit_buffer.append('\
		<input type="hidden" name="ops_array['+this.num_ops+'][op_type]" value="add" />\
		<input type="hidden" name="ops_array['+this.num_ops+'][parent_id]" value="'+parent_id+'" />\
		<input type="hidden" name="ops_array['+this.num_ops+'][node_id]" value="'+new_node_id+'" />\
		<input type="hidden" name="ops_array['+this.num_ops+'][node_type]" value="'+node_type+'" />\
		<input type="hidden" name="ops_array['+this.num_ops+'][node_order]" value="'+node_order+'" />\
	');

	//Add to SAP on page
	var $new_node = $("#"+node_structure+"_").clone();
	$new_node.attr({
		id: new_node_id,
		node_type: node_type
	});
	$new_node.addClass(node_type);
	this.$branch_edit.before($new_node);
	this.$branch_edit.prev(".leaf").append($(this.$leaf_edit).clone());

	//Increment number of new nodes and ops
	this.num_new_nodes++;
	this.num_ops++;
}

sap_edit.prototype.delete_node = function($delete_node) {

	//Get info
	var node_id = $delete_node.attr("id");

	//If deleting a branch, do some extra branchy stuff
	if ($delete_node.hasClass("branch")) {
		if ($delete_node.children(".node").length > 0) {
			alert("Can only delete empty "+$delete_node.attr("node_type")+"s");
			return;
		} else {
			var $parent_branch = $delete_node.parent().closest(".branch");
			this.set_branch_edit($parent_branch);
		}
	}

	//Put in edit buffer
	this.$edit_display.append("<p>" + ["delete",node_id].join(", ") + "</p>");
	this.$edit_buffer.append('\
		<input type="hidden" name="ops_array['+this.num_ops+'][op_type]" value="delete" />\
		<input type="hidden" name="ops_array['+this.num_ops+'][node_id]" value="'+node_id+'" />\
	');

	//Remove node from page
	$delete_node.remove();

	//Increment number of ops
	this.num_ops++;
}

sap_edit.prototype.edit_node = function($edit_leaf) {

	//Get info
	var edit_id = $edit_leaf.attr("id");
	var text = $edit_leaf.find("div.text").text();

	//Put in edit buffer
	this.$edit_display.append("<p>" + ["edit",edit_id].join(", ") + "</p>");
	this.$edit_buffer.append('\
		<input type="hidden" name="ops_array['+this.num_ops+'][op_type]" value="edit" />\
		<input type="hidden" name="ops_array['+this.num_ops+'][node_id]" value="'+edit_id+'" />\
		<textarea class="hidden" name="ops_array['+this.num_ops+'][text]" >'+text+'</textarea>\
	');

	//Increment number of ops
	this.num_ops++;
}

sap_edit.prototype.move_node = function($move_node, $parent_branch, start_order, stop_order) {

	//Get info
	var move_id = $move_node.attr("id");
	var parent_id = $parent_branch.attr("id");

	//put in edit buffer
	this.$edit_display.append("<p>" + ["move",move_id,parent_id,start_order,stop_order].join(", ") + "</p>");
	this.$edit_buffer.append('\
		<input type="hidden" name="ops_array['+this.num_ops+'][op_type]" value="move" />\
		<input type="hidden" name="ops_array['+this.num_ops+'][parent_id]" value="'+parent_id+'" />\
		<input type="hidden" name="ops_array['+this.num_ops+'][node_id]" value="'+move_id+'" />\
		<input type="hidden" name="ops_array['+this.num_ops+'][start_order]" value="'+start_order+'" />\
		<input type="hidden" name="ops_array['+this.num_ops+'][stop_order]" value="'+stop_order+'" />\
	');

	//Increment number of ops
	this.num_ops++;
}