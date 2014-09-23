function surveys_edit()
{	
	//Store the number of new questions created and number of operations
	this.num_new_qs = 0;
	this.num_new_cats = 0;
	this.num_ops = 0;
	
	this.start_move_order = 0;
	
	this.$edit_survey = $("#edit_survey");
	this.$edit_buffer = $("#edit_buffer");
	this.$edit_display = $("#edit_display");
	
	this.$cat_edit = $('#edit_survey_controls .cat_edit');
	this.$q_edit = $('#edit_survey_controls .q_edit');
	this.$cat_name_edit = $('#edit_survey_controls .cat_name_edit_btns');
	this.$cat_move = $('#edit_survey_controls .cat_move_btns');
	
	this.$current_cat = null;
	
	//Store sortable options so we don't have to keep creating
	this.sortable_options = {
		items: ".questions .q",
		containment: "parent",
		axis: "y",
		tolerance: "pointer"
	};
	
	$(document).on("click", "#edit_survey .cat", this, function(e){
		var $parent_cat = $(this);
		if(!$parent_cat.is('.editing')){
			//A new category target was selected
			e.data.set_cat_edit($parent_cat);
		} else {
			//Check is a question action was selected
			var $target = $(e.target);
			if($target.is(".edit_btn")) {
				e.data.q_edit_click($target.closest(".q"));
			} else if($target.is(".delete_btn")){
				e.data.q_delete_click($target.closest(".q"));
			} else if($target.is(".save_btn")){
				e.data.q_save_click($target.closest(".q"));
			} else if($target.is(".cancel_btn")){
				e.data.q_cancel_click($target.closest(".q"));
			} else if($target.is(".add_q_btn")){
				var $new_q = e.data.q_add_click($target.closest(".cat"));
				e.data.q_edit_click($new_q);
			} else if($target.is(".delete_cat_btn")){
				e.data.cat_delete_click($target.closest(".cat"));
			} else if($target.is(".edit_cat_name_btn")){
				e.data.edit_cat_name_click($target.closest(".cat"));
			} else if($target.is(".cancel_cat_name_btn")){
				e.data.cancel_cat_name_click($target.closest(".cat"));
			} else if($target.is(".save_cat_name_btn")){
				e.data.save_cat_name_click($target.closest(".cat"));
			} else if($target.is(".cat_up_btn")){
				e.data.cat_move_click($target.closest(".cat"), "up");
			} else if($target.is(".cat_down_btn")){
				e.data.cat_move_click($target.closest(".cat"), "down");
			}
		}
		
		//Stop event bubbling
		e.stopPropagation();
	});
	
	$(document).on("sortstart", "#edit_survey .cat", this, function(e, ui){
		//Store initial order of moving node
		e.data.start_move_order = ui.item.prevAll(".q").length;
		e.data.$edit_display.append("<p>startmove: "+e.data.start_move_order+"</p>");
		//stop event bubbling
		e.stopPropagation();
	});
	$(document).on("sortstop", "#edit_survey .cat", this, function(e, ui){
		//Handle node movement events
		var end_move_order = ui.item.prevAll(".q").length;
		e.data.$edit_display.append("<p>endmove: "+end_move_order+"</p>");
		if (e.data.start_move_order != end_move_order) {
			e.data.move_q(ui.item, ui.item.closest('.cat'), e.data.start_move_order, end_move_order);
		}
		//Stop event bubbling
		e.stopPropagation();
	});
	
	$(document).on("click", "#edit_survey #rename_layout_btn", function(){
		$('#survey_title_display').hide();
		$('#survey_title_edit').show();
	});
	
	$(document).on("click", "#edit_survey #save_rename_layout_btn", this, function(e){
		var title_text = $('#edit_survey_title').val();
		e.data.save_layout_name(title_text);
		$('#survey_title_text').text(title_text);
		$('#survey_title_display').show();
		$('#survey_title_edit').hide();
	});
	
	$(document).on("click", "#edit_survey #cancel_rename_layout_btn", function(){
		$('#survey_title_display').show();
		$('#survey_title_edit').hide();
		$('#edit_survey_title').val($('#survey_title_text').text());
	});
	
	$(document).on("click", "#edit_survey #add_cat", this, function(e){
		var $new_cat = e.data.cat_add_click();
		var $new_q = e.data.q_add_click($new_cat);
		e.data.q_edit_click($new_q);
		return false;
	});
	
	//Bind events for double-click editing
	$("#edit_survey .cat").bind("dblclick", this, function(e){
		var $q = $(e.target).closest(".q");
		if ($q.length > 0 && !$q.hasClass("editing")) {
			e.data.q_edit_click($q);
		}
	});
}

surveys_edit.prototype.set_cat_edit = function($parent_cat)
{
	this.$current_cat = $parent_cat;
	var $last_edit = $(".cat.editing");
	
	//Deal with previous editing node
	$last_edit.removeClass("editing");
	var self = this;
	self.cancel_cat_name_click($last_edit);
	$last_edit.find(".q.editing").each(function(){
		self.q_cancel_click($(this));
	});
	$last_edit.find(".q_edit").remove();
	$last_edit.find(".cat_edit").remove();
	$last_edit.find(".cat_move_btns").remove();
	$last_edit.find(".cat_name_edit_btns").remove();
	$last_edit.sortable("destroy");
	
	$parent_cat.addClass('editing');
	$parent_cat.append($(this.$cat_edit).clone());
	$parent_cat.find('.questions').children(".q").append($(this.$q_edit).clone());
	
	$parent_cat.find('.questions').before($(this.$cat_name_edit).clone());
	$parent_cat.find('.questions').before($(this.$cat_move).clone());
	
	this.init_cat_move_btns($parent_cat);
	
	$parent_cat.sortable(this.sortable_options);
}

surveys_edit.prototype.init_cat_move_btns = function($cat)
{
	var cat_index = $('#edit_survey .cat').index($cat);
	$cat.find('.cat_move_btns button').removeAttr('disabled');
	if(cat_index==0){
		$cat.find('.cat_up_btn').attr('disabled', 'disabled');
	} else if(cat_index==($('#edit_survey .cat').length-1)) {
		$cat.find('.cat_down_btn').attr('disabled', 'disabled');
	}
}

surveys_edit.prototype.edit_cat_name_click = function($cat)
{
	var $text_div = $cat.find('.cat_name');
	var $text_edit = $cat.find('.edit_cat_name');
	
	$text_edit.val($text_div.text());
	
	//Show & hide UI elements
	$cat.find("button.edit_cat_name_btn").add($text_div).hide();
	$cat.find("button.cancel_cat_name_btn, button.save_cat_name_btn").add($text_edit).show();
	
	$text_edit.focus();
}

surveys_edit.prototype.cancel_cat_name_click = function($cat)
{
	var $text_div = $cat.find('.cat_name');
	var $text_edit = $cat.find('.edit_cat_name');
	
	//Show & hide UI elements
	$cat.find("button.cancel_cat_name_btn, button.save_cat_name_btn").add($text_edit).hide();
	$cat.find("button.edit_cat_name_btn").add($text_div).show();
}

surveys_edit.prototype.save_cat_name_click = function($cat)
{
	var $text_div = $cat.find('.cat_name');
	var $text_edit = $cat.find('.edit_cat_name');
	
	this.save_cat($cat);
	
	//Move edits to text area
	$text_div.text($text_edit.val());
	
	//Show & hide UI elements
	$cat.find("button.cancel_cat_name_btn, button.save_cat_name_btn").add($text_edit).hide();
	$cat.find("button.edit_cat_name_btn").add($text_div).show();
	
	this.alert_edit();
}

surveys_edit.prototype.cat_move_click = function($cat, direction)
{
	//Store initial order of moving node
	var start_move_order = $cat.prevAll(".cat").length;
	var end_move_order = 0;
	
	if(direction=="up"){
		end_move_order = start_move_order-1;
		$cat.prev().before($cat);
	} else if(direction=="down"){
		end_move_order = start_move_order+1;
		$cat.next().after($cat);
	} else {
		return;
	}
	this.init_cat_move_btns($cat);
	this.move_cat($cat, start_move_order, end_move_order);
}

surveys_edit.prototype.q_edit_click = function($q)
{
	$q.addClass("editing");
	var $text_div = $q.find("div.text");
	var $text_edit = $q.find("textarea.edit_text");

	//Move text to edit area
	$text_edit.val($text_div.html());

	//Show & hide UI elements
	$q.find(".q_edit_type").show();
	$q.find("button.edit_btn, button.delete_btn").add($text_div).hide();
	$q.find("button.save_btn, button.cancel_btn").add($text_edit).show();

	$text_edit.focus();
	$text_edit.select();
}

surveys_edit.prototype.q_delete_click = function($q)
{
	if(this.delete_q($q)){
		$q.remove();
	}
}

surveys_edit.prototype.q_save_click = function($q)
{
	$q.removeClass("editing");
	var $text_div = $q.find("div.text");
	var $text_edit = $q.find("textarea.edit_text");
	
	if(this.save_q($q)){
		
		//Move edits to text area
		$text_div.html($text_edit.val());

		//Show & hide UI elements
		$q.find(".q_edit_type").hide();
		$q.find("button.save_btn, button.cancel_btn").add($text_edit).hide();
		$q.find("button.edit_btn, button.delete_btn").add($text_div).show();
		
	}
}

surveys_edit.prototype.q_cancel_click = function($q)
{
	$q.removeClass("editing");
	var $text_div = $q.find("div.text");
	var $text_edit = $q.find("textarea.edit_text");
	
	//Show & hide UI elements
	$q.find(".q_edit_type").hide();
	$q.find("button.save_btn, button.cancel_btn").add($text_edit).hide();
	$q.find("button.edit_btn, button.delete_btn").add($text_div).show();
}

surveys_edit.prototype.save_cat = function($cat) {
	//Get info
	var cat_id = $cat.attr("id");
	var text = $cat.find(".edit_cat_name").val();
	
	//Put in edit buffer
	this.$edit_display.append("<p>" + ["edit",cat_id].join(", ") + "</p>");
	this.$edit_buffer.append('\
		<input type="hidden" name="ops_array['+this.num_ops+'][op_type]" value="edit" />\
		<input type="hidden" name="ops_array['+this.num_ops+'][node_id]" value="'+cat_id+'" />\
		<textarea class="hidden" name="ops_array['+this.num_ops+'][text]" >'+text+'</textarea>\
	');
	
	//Increment number of ops
	this.num_ops++;
	
	return true;
}

surveys_edit.prototype.save_q = function($q) {

	//Get info
	var q_id = $q.attr("id");
	var q_type = $q.find(".q_type").val();
	var text = $q.find("textarea.edit_text").val();

	//Put in edit buffer
	this.$edit_display.append("<p>" + ["edit",q_id].join(", ") + "</p>");
	this.$edit_buffer.append('\
		<input type="hidden" name="ops_array['+this.num_ops+'][op_type]" value="edit" />\
		<input type="hidden" name="ops_array['+this.num_ops+'][node_id]" value="'+q_id+'" />\\n\
		<input type="hidden" name="ops_array['+this.num_ops+'][node_type]" value="'+q_type+'" />\
		<textarea class="hidden" name="ops_array['+this.num_ops+'][text]" >'+text+'</textarea>\
	');
	
	this.alert_edit();

	//Increment number of ops
	this.num_ops++;
	
	return true;
}

surveys_edit.prototype.delete_q = function($q) {

	//Get info
	var q_id = $q.attr("id");

	//Put in edit buffer
	this.$edit_display.append("<p>" + ["delete",q_id].join(", ") + "</p>");
	this.$edit_buffer.append('\
		<input type="hidden" name="ops_array['+this.num_ops+'][op_type]" value="delete" />\
		<input type="hidden" name="ops_array['+this.num_ops+'][node_id]" value="'+q_id+'" />\
	');
	
	this.alert_edit();

	//Increment number of ops
	this.num_ops++;
	
	return true;
}

surveys_edit.prototype.q_add_click = function($cat)
{
	//Get info for new node
	var new_node_id = "new_q_" + this.num_new_qs;
	var cat_id = $cat.attr("id");
	var node_order = this.$current_cat.find('.questions .q').length;

	//Add to edit buffer
	this.$edit_display.append("<p>" + ["add_q",new_node_id,cat_id,node_order].join(", ") + "</p>");
	this.$edit_buffer.append('\
		<input type="hidden" name="ops_array['+this.num_ops+'][op_type]" value="add_q" />\
		<input type="hidden" name="ops_array['+this.num_ops+'][cat_id]" value="'+cat_id+'" />\
		<input type="hidden" name="ops_array['+this.num_ops+'][node_id]" value="'+new_node_id+'" />\
		<input type="hidden" name="ops_array['+this.num_ops+'][node_order]" value="'+node_order+'" />\
	');
	this.alert_edit();
	
	//Add to display
	var $new_q = $("#q_structure .q").clone();
	$new_q.attr({
		id: new_node_id
	});
	$new_q.append($(this.$q_edit).clone());
	
	this.$current_cat.find('.questions').append($new_q);

	//Increment number of new nodes and ops
	this.num_new_qs++;
	this.num_ops++;
	
	return $new_q;
}

surveys_edit.prototype.cat_add_click = function()
{
	//Get info for new node
	var new_node_id = "new_cat_" + this.num_new_cats;
	var node_order = this.$edit_survey.find('.cat').length;

	//Add to edit buffer
	this.$edit_display.append("<p>" + ["add_cat",new_node_id,node_order].join(", ") + "</p>");
	this.$edit_buffer.append('\
		<input type="hidden" name="ops_array['+this.num_ops+'][op_type]" value="add_cat" />\
		<input type="hidden" name="ops_array['+this.num_ops+'][node_id]" value="'+new_node_id+'" />\
		<input type="hidden" name="ops_array['+this.num_ops+'][node_order]" value="'+node_order+'" />\
	');
	this.alert_edit();

	//Add to display
	var $new_cat = $("#cat_structure .cat").clone();
	$new_cat.attr({
		id: new_node_id
	});

	$("#add_cat").before($new_cat);
	
	this.set_cat_edit($new_cat);

	//Increment number of new nodes and ops
	this.num_new_cats++;
	this.num_ops++;
	
	return $new_cat;
}

surveys_edit.prototype.cat_delete_click = function($cat)
{
	var cat_id = $cat.attr('id');
	
	//Put in edit buffer
	this.$edit_display.append("<p>" + ["delete",cat_id].join(", ") + "</p>");
	this.$edit_buffer.append('\
		<input type="hidden" name="ops_array['+this.num_ops+'][op_type]" value="delete" />\
		<input type="hidden" name="ops_array['+this.num_ops+'][node_id]" value="'+cat_id+'" />\
	');
	
	$cat.remove();
	
	this.num_ops++;
	return true;
}

surveys_edit.prototype.move_q = function($move_node, $parent_branch, start_order, stop_order)
{
	//Get info
	var move_id = $move_node.attr("id");
	var parent_id = $parent_branch.attr("id");

	//put in edit buffer
	this.$edit_display.append("<p>" + ["move",move_id,parent_id,start_order,stop_order].join(", ") + "</p>");
	this.$edit_buffer.append('\
		<input type="hidden" name="ops_array['+this.num_ops+'][op_type]" value="move" />\
		<input type="hidden" name="ops_array['+this.num_ops+'][node_id]" value="'+move_id+'" />\
		<input type="hidden" name="ops_array['+this.num_ops+'][parent_id]" value="'+parent_id+'" />\
		<input type="hidden" name="ops_array['+this.num_ops+'][start_order]" value="'+start_order+'" />\
		<input type="hidden" name="ops_array['+this.num_ops+'][stop_order]" value="'+stop_order+'" />\
	');
	this.alert_edit();

	//Increment number of ops
	this.num_ops++;
}

surveys_edit.prototype.move_cat = function($move_node, start_order, stop_order)
{
	//Get info
	var move_id = $move_node.attr("id");

	//put in edit buffer
	this.$edit_display.append("<p>" + ["move",move_id,start_order,stop_order].join(", ") + "</p>");
	this.$edit_buffer.append('\
		<input type="hidden" name="ops_array['+this.num_ops+'][op_type]" value="move" />\
		<input type="hidden" name="ops_array['+this.num_ops+'][node_id]" value="'+move_id+'" />\
		<input type="hidden" name="ops_array['+this.num_ops+'][start_order]" value="'+start_order+'" />\
		<input type="hidden" name="ops_array['+this.num_ops+'][stop_order]" value="'+stop_order+'" />\
	');
	this.alert_edit();

	//Increment number of ops
	this.num_ops++;
}

surveys_edit.prototype.save_layout_name = function(text)
{
	//put in edit buffer
	this.$edit_display.append("<p>" + ["rename",text].join(", ") + "</p>");
	this.$edit_buffer.append('\
		<input type="hidden" name="ops_array['+this.num_ops+'][op_type]" value="rename" />\
		<input type="hidden" name="ops_array['+this.num_ops+'][text]" value="'+text+'" />\
	');
	this.alert_edit();

	//Increment number of ops
	this.num_ops++;
}

surveys_edit.prototype.alert_edit = function()
{
	$('#save_cancel').addClass('alert');
}