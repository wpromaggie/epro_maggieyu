/**
 * JavaScript for Campaign Development.
 */

/**
 * Called when the camp_dev module loads.
 * This is basically the
 */
function camp_dev()
{	
	//Variables
	this.generated_keywords = '';
	this.num_generated_keywords = 0;
	this.kw_style_broad = true;
	this.kw_style_exact = true;
	this.kw_style_phrase = true;

	//Event bindings
	$('#the_add_button').bind('click', this, function(e){ e.data.add_list(); });
	$('#the_clearall_button').bind('click', this, function(e){ e.data.clear_all_lists(); });
	$('#the_big_button').bind('click', this, function(e){ e.data.generate_phrases(); });
	$('#clear_generated_btn').bind('click', this, function(e){ e.data.clear_generated(); });
	$('#clear_master_btn').bind('click', this, function(e){ e.data.clear_master(); });
	$('#gen_to_master_btn').bind('click', this, function(e){ e.data.move_gen_to_master(); });
	$('#mod_broad').bind('click', this, function(e){ e.data.mod_broad_click(); });
	$('input[name="mod_broad_all_or_some"]').bind('click', this, function(e){ e.data.mod_broad_option_click($(this)); });

	//List initialization
	this.add_list();
	this.add_list();
	
	$('#list_container_0 .list_textarea').focus();
}

camp_dev.prototype.add_list = function()
{
	//get info for this list
	var $list_container = $('#list_container');
	var list_id = 0;
	$list_container.children().each(function(){
		list_id = Math.max(list_id, Number($(this).attr("list_id"))+1);
	});

	//create the list structure
	$list_container.append('\
		<div id="list_container_'+list_id+'" list_id="'+list_id+'" class="list list_off">\
			<div>\
				<div align=center><b>' + 'List ' + (list_id + 1) + '</b></div>\
				<p><input type=radio name=op_req'+list_id+' value="req" checked /> Required</p>\
				<p><input type=radio name=op_req'+list_id+' value="op" /> Optional</p>\
				<p><input type=checkbox name=pause'+list_id+' value=1 /> Pause</p>\
			</div>\
			<select name="l_actions_'+list_id+'" style="margin-top:5px" >\
				<option value=""> - Actions -</option>\
				<option value="separate_list">Separate By</option>\
				<option value="remove_duplicates">Remove Duplicates</option>\
				<option value="clear_list">Clear</option>\
				<option value="remove_list">Delete</option>\
			</select>\
			<textarea name="l'+list_id+'" wrap="off" class="list_textarea"></textarea>\
			<table width=100%>\
				<tr>\
					<td id=arrow_cell_l width=50% align=right>\
						<input type=button name=arrow_l_'+list_id+' value="<-" >\
					</td>\
					<td id=arrow_cell_r width=50%>\
						<input type=button name=arrow_r_'+list_id+' value="->" >\
					</td>\
				</tr>\
			</table>\
		</div>\
	');

	//add actions to the list
	var $new_list = $('#list_container_'+list_id);

	$new_list.find('[name=l_actions_'+list_id+']').bind('change', this, function(e){
		var $select = $(this);
		var method = $select.val();
		switch (method) {
			case 'separate_list':
				e.data.separate_list(list_id);
				break;
			case 'remove_duplicates':
				e.data.remove_duplicates(list_id);
				break;
			case 'clear_list':
				e.data.clear_list(list_id);
				break;
			case 'remove_list':
				e.data.delete_list(list_id);
				break;
			default:
				break;
		}
		$select.find('option:first-child').attr('selected', 'selected');
	});
	
	$new_list.bind('keydown', this, function(e){ return e.data.list_keydown($(this), e); });
	$new_list.find('[name=arrow_l_'+list_id+']').bind('click', this, function(e) { e.data.move_list_left(list_id); });
	$new_list.find('[name=arrow_r_'+list_id+']').bind('click', this, function(e) { e.data.move_list_right(list_id); });
	
	this.sync_lists();
}

camp_dev.prototype.list_keydown = function(list_input, e)
{
	switch (e.which)
	{
		// tab
		case (9):
			var w_lists = $('#list_container'),
				w_list = list_input.closest('.list'),
				list_index = list_input.prevAll().length,
				lists = w_lists.find('.list'),
				num_lists = lists.length,
				new_index = list_index + ((e.shiftKey) ? -1 : 1);
			
			if (new_index > -1 && new_index < num_lists)
			{
				$(lists[new_index]).find('.list_textarea').focus();
			}
			return false;
	}
	return true;
};

camp_dev.prototype.delete_list = function(list_id)
{
	$('#list_container_'+list_id).remove();
	this.sync_lists();
};

camp_dev.prototype.clear_list = function(list_id)
{
	$('#list_container_'+list_id+' textarea').val('');
};

camp_dev.prototype.clear_all_lists = function()
{
	$('#list_container textarea.list_textarea').val('');
};

camp_dev.prototype.separate_list = function(list_id)
{
	alert('separate_list');
};

camp_dev.prototype.remove_duplicates = function(list_id)
{
	alert('remove_duplicates');
};

camp_dev.prototype.move_list_right = function(list_id)
{
	var $move_list = $('#list_container_'+list_id)
	var $next_list = $move_list.next();
	if ($next_list.length != 0) {
		$next_list.after($move_list.detach());
	} else {
		alert('too far!');
	}
};

camp_dev.prototype.move_list_left = function(list_id)
{
	var $move_list = $('#list_container_'+list_id)
	var $prev_list = $move_list.prev();
	if ($prev_list.length != 0) {
		$prev_list.before($move_list.detach());
	} else {
		alert('too far!');
	}
};

camp_dev.prototype.mod_broad_click = function()
{
	var mbo = $('#mod_broad_options');
	mbo.toggle();
};

camp_dev.prototype.mod_broad_option_click = function(input)
{
	this.toggle_mod_broad_list_cboxes_enabled();
};

camp_dev.prototype.toggle_mod_broad_list_cboxes_enabled = function()
{
	var inputs = $('#mod_broad_lists input'),
		labels = $('#mod_broad_lists label');
	
	if (this.is_mod_broad_all())
	{
		inputs.attr('disabled', true);
		labels.addClass('disabled');
	}
	else
	{
		inputs.attr('disabled', false);
		labels.removeClass('disabled');
	}
};

camp_dev.prototype.set_mod_broad_list_cboxes = function()
{
	var i, key, ml,
		num_lists = $('#list_container .list').length;
	
	ml = '';
	for (i = 0; i < num_lists; ++i)
	{
		key = 'mod_broad_list_'+i;
		ml += '\
			<p>\
				<input type="checkbox" name="'+key+'" id="'+key+'" value="1" />\
				<label for="'+key+'">List '+(i + 1)+'</label>\
			</p>\
		';
	}
	$('#mod_broad_lists').html(ml);
	this.toggle_mod_broad_list_cboxes_enabled();
};

camp_dev.prototype.set_mod_broad_some_re = function()
{
	var i, list_kws, all_words,
		lists = $('#list_container .list .list_textarea');
	
	all_words = [];
	for (i = 0; i < lists.length; ++i)
	{
		if ($('#mod_broad_list_'+i).is(':checked'))
		{
			list_kws = $(lists[i]).val().replace(/\s+/g, ' ').trim().split(' ');
			all_words = all_words.concat(all_words, list_kws);
		}
	}
	this.mod_broad_re = new RegExp('(^|\\s)('+all_words.join('|')+')', 'g');
};

camp_dev.prototype.is_mod_broad_all = function()
{
	return ($('input[name="mod_broad_all_or_some"]:checked').val() == 'all');
};


/**
 * The big phrase generator function
 */
camp_dev.prototype.generate_phrases = function()
{
	//Set things up
	this.generated_keywords = '';
	this.num_generated_keywords = 0;
	if (this.is_mod_broad_all())
	{
		this.mod_broad_re = /(^|\s)(\w)/g;
	}
	else
	{
		this.set_mod_broad_some_re();
	}
	this.kw_style_broad = $('#broad').is(":checked");
	this.kw_style_exact = $('#exact').is(":checked");
	this.kw_style_phrase = $('#phrase').is(":checked");
	this.kw_style_mod_broad = $('#mod_broad').is(":checked");

	if (!(this.kw_style_broad || this.kw_style_exact || this.kw_style_phrase || this.kw_style_mod_broad)) {
		alert('Please select a match type');
		return;
	}

	//Convert keyword lists into arrays
	var working_lists = [];
	$('#list_container').find('.list').each(function(){
		var $source_list = $(this);
		if (!$source_list.find('[name^="pause"]').is(':checked')) {
			var new_list = $source_list.find('textarea.list_textarea').val().split('\n');
			if ($source_list.find('[value="op"]').is(':checked')) {
				new_list.unshift('');
			}
			working_lists.push(new_list);
		}
	});

	//Hacky Quacky baloney for single lists
	if (working_lists.length == 1) {
		working_lists.push(['']);
	}

	//Do some recursive magic
	var first_list = working_lists.shift();
	for (var i=0; i<first_list.length; i++) {
		this.help_generate_phrases(first_list[i], working_lists);
	}
	
	//Put generated results in the Generated List
	$('#gen_list').val(this.generated_keywords);
	$('#generated_count_cell').html(this.num_generated_keywords);

};

camp_dev.prototype.help_generate_phrases = function(phrase, word_lists)
{
	//Get the current list
	var current_list = word_lists.shift();

	//Handle your shizz
	if (word_lists.length == 0) {
		for (var i=0; i<current_list.length; i++) {
			this.help_add_keyword(phrase + ' ' + current_list[i]);
		}
	} else {
		for (var i=0; i<current_list.length; i++) {
			this.help_generate_phrases(phrase + ' ' + current_list[i], word_lists);
		}
	}

	//PUT BACK THE CURRENT LIST!!
	word_lists.unshift(current_list);
};

camp_dev.prototype.help_add_keyword = function(keyword)
{
	keyword = $.trim(keyword);

	if (empty(keyword)) return;

	if (this.kw_style_broad)
	{
		this.generated_keywords += keyword + '\tbroad\n';
		this.num_generated_keywords++;
	}
	if (this.kw_style_exact)
	{
		this.generated_keywords += keyword + '\texact\n';
		this.num_generated_keywords++;
	}
	if (this.kw_style_phrase)
	{
		this.generated_keywords += keyword + '\tphrase\n';
		this.num_generated_keywords++;
	}
	if (this.kw_style_mod_broad)
	{
		this.generated_keywords += keyword.replace(this.mod_broad_re, '$1+$2') + '\tbroad\n';
		this.num_generated_keywords++;
	}
};

camp_dev.prototype.clear_generated = function()
{
	$('#gen_list').val('');
	$('#generated_count_cell').html('0');
};

camp_dev.prototype.clear_master = function()
{
	$('#master_list').val('');
	$('#master_count_cell').html('0');
};

camp_dev.prototype.move_gen_to_master = function()
{
	var $master_list = $('#master_list');
	var $gen_list = $('#gen_list');
	
	$master_list.val($master_list.val() + $gen_list.val());
	$gen_list.val('');

	var $master_count = $('#master_count_cell');
	var $gen_count = $('#generated_count_cell');

	$master_count.html(Number($master_count.html()) + Number($gen_count.html()));
	$gen_count.html('0');
};

camp_dev.prototype.sync_lists = function()
{
	this.set_mod_broad_list_cboxes();
};
