
function account_tasks()
{
	//nodes
	this.$task_list = $(".task-list");
	this.$branches = $(".branch");
	this.$items = $(".item");
	this.$titles = this.$branches.find('.title');
	
	//branch actions
	this.$expands =  this.$branches.find('.icons .expanded');
	this.$collapses =  this.$branches.find('.icons .collapsed');
	this.$delete_sections = this.$branches.find('.delete-section');
	
	//action btns
	this.$checkboxes = this.$items.find('.checkbox');
	this.$edits = this.$items.find('.actions .edit');
	this.$deletes = this.$items.find('.actions .delete');
	this.$saves = this.$items.find('.save_task');
	this.$adds = this.$branches.find('.add_task');
	
	//order vars
	this.start_move_order = 0;
	this.start_move_parent_id = null;
	
	//Init
	this.$collapses.hide();
	this.update_count(this.$branches);
	
	/*
	 * Layout Actions
	 */
	$('#collapse-all').bind("click", this, function(e){
		e.data.$expands.click();
		return false;
	});
	$('#edit-title').bind("click", this, function(e){
		$('#layout-title').hide();
		$('#edit-layout-title-wrapper').show().find('textarea').focus();
		return false;
	});
	$('#edit-layout-title-wrapper textarea').bind("blur", this, function(e){
		e.data.save_layout_title($(this));
	});
	
	
	this.$task_list.sortable({
		items: "> .default_root > .branch",
		containment: "parent",
		axis: "y",
		tolerance: "pointer",
		handel: '> .title'
	});
	
	this.$branches.sortable({
		items: "> .item-list > .item",
		containment: ".default_root",
		axis: "y",
		tolerance: "pointer",
		connectWith: '.branch'
	});
	
	$(document).on('sortstart', '.branch', this, function(e, ui){
		e.data.start_move_order = ui.item.prevAll("div.node").length;
		e.data.start_move_parent_id = ui.item.parents('.branch').attr('id');
		e.stopPropagation();
	});
	$(document).on('sortstop', '.branch', this, function(e, ui){
		
		var $end_move_parent = ui.item.parents('.branch');
		var end_move_order = ui.item.prevAll("div.node").length;
		
		if($end_move_parent.attr('id') != e.data.start_move_parent_id){
			
			e.data.move_node(ui.item, $end_move_parent, e.data.start_move_order, end_move_order, e.data.start_move_parent_id);
			
		} else {
			
			if (e.data.start_move_order != end_move_order) {
				e.data.move_node(ui.item, $end_move_parent, e.data.start_move_order, end_move_order, 0);
			}
			
		}
		
		e.stopPropagation();
	});
	

	$(document).on('mouseover', '.item', function(){
		if(!$(this).is('.editing')){
			$(this).find('.actions').show();
		}
	});
	
	$(document).on('mouseout', '.item', function(){
		$(this).find('.actions').hide();
	});
	
	$(document).on('dblclick', '.item', function(){
		$(this).find('.edit').trigger('click');
	});
	
	this.$branches.on('click', '.delete-section', this, function(e){
		if(confirm("Are you sure you want to delete this section?")){
			e.data.delete_node($(this).closest('.branch'));
		}
		return false;
	});
	
	/*
	 *  Item Node Actions
	 */
	this.$items.on('click', '.checkbox', this, function(e){
		if($(this).is(':checked')){
			e.data.set_status($(this).closest('.item'), 'complete');
		} else {
			e.data.set_status($(this).closest('.item'), 'incomplete');
		}
	});
	
	this.$items.on('click', '.actions .edit', function()
	{
		//close the item that is currently being edited
		var $last = $(this).closest('.branch').find('.editing');
		$last.removeClass('editing');
		$last.find('.display-text, .checkbox').show();
		$last.find('.edit_task_wrapper, .save_task').hide();
		
		var $item = $(this).closest('.item');
		$item.addClass('editing');
		$item.find('.display-text, .actions, .checkbox').hide();
		$item.find('.edit_task_wrapper, .save_task').show();
		$item.find('textarea.edit_input').focus();
		return false;
	});

	this.$items.on('click', '.actions .delete', this, function(e)
	{
		var $item = $(this).closest('.item');
		e.data.delete_node($item);
		return false;
	});
	
	this.$items.on('click', '.save_task', this, function(e)
	{
		var $item = $(this).closest('.item');
		e.data.save_task($item);
		return false;
	});
	
	this.$items.on('click', '.add_task', this, function(e){
		var $branch = $(this).closest('.branch');
		e.data.add_task($(this).parent().find('textarea').val(), $branch);
		return false;
	});
	
	/*
	 *  Section Title Actions
	 */
	this.$branches.on('dblclick', '.title', this, function(e){
		$(this).find('.text,.count').hide();
		$(this).find('.edit_title_wrapper').show().find('textarea').focus();
	});
	
	this.$titles.on('blur', '.edit_title_wrapper textarea', this, function(e){
		e.data.save_title($(this).closest('.branch'));
	});
	
	
	/*
	 *  Section Node Actions
	 */
	this.$branches.on('click', '.icons .expanded', this, function(e){
		e.data.collapse_item_list($(this).closest('.branch'));
	});
	this.$branches.on('click', '.icons .collapsed', this, function(e){
		e.data.expand_item_list($(this).closest('.branch'));
	});
	
	/*
	 *  Enter in textarea action
	 */
	$('#account_tasks').on('keydown', 'textarea', this, function(e){
		if(e.keyCode == 13){
			if($(this).is('.edit_input')){
				var $item = $(this).closest('.item');
				e.data.save_task($item);
			} else if($(this).is('.add_input')){
				var $branch = $(this).closest('.branch');
				e.data.add_task($(this).val(), $branch);
			} else if($(this).is('.title_input')){
				e.data.save_title($(this).closest('.branch'));
			} else if($(this).is('.layout_title_input')){
				e.data.save_layout_title($(this));
			} else if($(this).is('.edit_input_note')){
				return true;
			}
			return false;
		}
		return true;
	});
}

account_tasks.prototype.save_layout_title = function($title)
{
	var new_text = $title.val();
	var old_text = $title.attr('data-default');
	if(new_text=="" || new_text==old_text){
		$title.val(old_text).parent().hide();
		$('#layout-title').show();
		return;
	}
	this.post('save_layout_title', {
		id: $title.attr('id'),
		text: new_text
	});
}
account_tasks.prototype.save_layout_title_callback = function(request, response)
{
	$('#'+request.id).attr('data-default', request.text).parent().hide();
	$('#layout-title').text(request.text).show();
}

account_tasks.prototype.expand_item_list = function($branch)
{
	$branch.find('.collapsed').hide().siblings('.expanded').show();
	$branch.find('.item-list').slideDown();
}

account_tasks.prototype.collapse_item_list = function($branch)
{
	$branch.find('.expanded').hide().siblings('.collapsed').show();
	$branch.find('.item-list').slideUp();
}

account_tasks.prototype.update_count = function($branches)
{
	$branches.each(function(){
		var count = $(this).find('.item-list .item').length;
		$(this).find('.title .count').text("("+count+")");
	});
}

account_tasks.prototype.save_title = function($branch)
{
	var new_text = $branch.find('.title textarea').val();
	var old_text = $branch.find('.title .text').text();
	if(new_text=="" || new_text==old_text){
		//set to old text and do nothing
		$branch.find('.title textarea').val(old_text);
		$branch.find('.edit_title_wrapper').hide();
		$branch.find('.title .text, .title .count').show();
		return;
	}
	
	this.post('save_node', {
		id: $branch.attr('id'),
		type: 'section',
		text: new_text
	});
	
}

account_tasks.prototype.save_task = function($item)
{
	$item.removeClass('editing');
	$item.find('.display-text, .checkbox').show();
	$item.find('.edit_task_wrapper, .save_task').hide();
	
	var text = $item.find('textarea.edit_input').val();
	var note = $item.find('textarea.edit_input_note').val();
	
	if(text==""){
		alert("Blank tasks are just confusing. Try again.");
		return;
	}
	
	this.post('save_node', {
		id: $item.attr('id'),
		type: 'item',
		text: text,
		note: note
	});
}

account_tasks.prototype.save_node_callback = function(request, response)
{
	//if task
	if(request.type=="item"){
		$('#'+request.id).find('.text', 'textarea.edit_input').text(request.text);
		if(request.note){
			$('#'+request.id).find('.note', 'textarea.edit_input_note').html(request.note.replace(/\n/g,"<br>"));
		}
	} else {
		//if title
		$('#'+request.id).find('.edit_title_wrapper').hide();
		$('#'+request.id).find('.title .text').html(request.text).show().siblings('.count').show();
	}
}

account_tasks.prototype.add_task = function(text, $branch)
{
	if(text==""){
		alert("Blank tasks are just confusing. Try again.");
		return;
	}
	this.post('add_task', {
		id: $branch.attr('id'),
		text: text,
		child_order: $branch.find('.node').length
	});
}

account_tasks.prototype.add_task_callback = function(request, response)
{
	if(response){
		$('#'+request.id+' .item-list').append(response);
		this.expand_item_list($('#'+request.id));
		
		//update task count
		this.update_count($('#'+request.id));
	}
	//clear textarea
	$('#'+request.id).find('.add textarea').val('').focus();
}

account_tasks.prototype.delete_node = function($node)
{
	this.post('delete_node', {
		id: $node.attr('id')
	});
}

account_tasks.prototype.delete_node_callback = function(request, response)
{
	var $node = $('#'+request.id);
	if($node.is('.item')){
		var $branch = $node.closest('.branch');

		//update task count
		this.update_count($branch);
	}
	$node.remove();
}

account_tasks.prototype.move_node = function($item, $branch, start_order, stop_order, start_parent_id)
{	
	this.post('move_node', {
		id: $item.attr('id'),
		parent_id: $branch.attr('id'),
		start_order: start_order,
		stop_order: stop_order,
		start_parent_id: start_parent_id
	});
	
	//pause sorting while the db updates
	$branch.sortable("disable");
}

account_tasks.prototype.move_node_callback = function(request, response)
{
	//enable sorting
	$('#'+request.parent_id).sortable("enable");
}

account_tasks.prototype.set_status = function($item, status)
{
	this.post('set_status', {
		id: $item.attr('id'),
		status: status
	});
}

account_tasks.prototype.set_status_callback = function(request, response)
{
	if(request.status=='complete'){
		$('#'+request.id).addClass('complete');
	} else {
		$('#'+request.id).removeClass('complete');
	}
}