
function root_show_cur_type()
{
	var user;
	
	user = $("#user_select").val();
	if (empty(user)) return;
	$("#cur_type").html(g_user_types[user]);
}

function root_impersonate_stop()
{
	html.form_set("cl_type", "");
	html.hidden("go", "impersonate_stop");
}

function administrate_user_guilds()
{
	var self = this,
		cboxes = $('.guild_box');
	
	$('#user_select').change(f_submit);
	
	cboxes.bind('click', this, function(e){ e.data.guild_box_click($(this)); });
	cboxes.filter(':checked').map(function(){
		self.guild_box_on($(this));
	});
	$('#primary_dept').val($('#cur_primary_dept').val());
}

administrate_user_guilds.prototype.guild_box_click = function(elem)
{
	var self = this;
	this['guild_box_'+(elem.is(':checked') ? 'on' : 'off')](elem);
	
	// call on children
	elem.closest('li').find('ul input[type="checkbox"]').map(function(){
		$(this).attr('checked', elem.is(':checked'));
		self['guild_box_'+(elem.is(':checked') ? 'on' : 'off')]($(this));
	});
};

administrate_user_guilds.prototype.guild_box_on = function(elem)
{
	var guild = elem.attr('name');
	
	$('select[name='+guild+'_role]').show();
	$('#primary_dept').append('<option value="'+guild+'">'+guild+'</option>');
};

administrate_user_guilds.prototype.guild_box_off = function(elem)
{
	var guild = elem.attr('name');
	
	$('select[name='+guild+'_role]').hide();
	$('#primary_dept option[value="'+guild+'"]').remove();
};

function administrate_delly()
{
	this.init_data();
	this.cols = [
		{key:'type'},
		{key:'account_id',type:'alpha'},
		{key:'client'},
		{key:'user_id',totals_val:'-'},
		{key:'who'},
		{key:'status'},
		{key:'hostname'},
		{key:'process_id',totals_val:'-'},
		{key:'scheduled'},
		{key:'created'},
		{key:'started'},
		{key:'finished'}
	];
	$('#w_cur_jobs').table({
		'id_key':'id',
		'data':this.data,
		'cols':this.cols,
		'sort_init':'created',
		'sort_dir_init':'desc'
	});
	$('#w_cur_jobs').find('select').bind('change', this, function(e){ e.data.status_change($(this)); });
}

administrate_delly.prototype = {

	init_data:function() {
		var i, d;

		this.data = [];
		for (i = 0; i < globals.jobs.length; ++i) {
			d = globals.jobs[i];
			d._display_type = '<a target="_blank" href="'+e2.url('administrate/job_details?jid='+d.id)+'">'+d.type+'</a>';
			d._display_status = html.select('status_'+d.id, globals.status_options, d.status);
			this.data.push(d);
		}
	},

	status_change:function($select) {
		var jid = $select.closest('tr').attr('i');
		if (!this.status_changes) {
			this.status_changes = {};
		}
		this.status_changes[jid] = $select.val();
		$('#status_updates').val(JSON.stringify(this.status_changes));
	}
};

function administrate_job_details()
{
	this.children = window.globals.children;
	this.done_stati = window.globals.done_stati;

	this.init_details();

	this.$w_children = $('#w_children');
	if (this.children.length == 0) {
		this.$w_children.html('<p>No Children</p>');
	}
	else {
		this.init_children();
	}
}

administrate_job_details.prototype = {

	init_details:function() {
		this.schedule_details_refresh();
	},

	init_children:function() {
		this.init_children_meta();
		this.init_children_table();
	},

	schedule_details_refresh:function() {
		window.setTimeout(this.update_details.bind(this), 1234);
	},

	update_details:function() {
		$('#w_job_')
		this.post('get_job_details', {});
	},

	get_job_details_callback:function(request, response) {
		var i, jd, ml_details = '',
			ml_status = ''
		;

		for (i = 0; i < response.job_detail.length; ++i) {
			jd = response.job_detail[i];
			ml_details += '<div style="padding-left:'+(2 + (jd.level * 8))+'px;">'+jd.ts+', '+jd.message+'</div>';
		}
		$('#w_details_list').html(ml_details);

		ml_status = response.status;
		if (!Array.in_array(this.done_stati, response.status)) {
			ml_status += ' '+e2.loading();
			this.schedule_details_refresh();
		}
		$('#current_status').html(ml_status);
	},

	init_children_meta:function() {
		$('#w_children_meta').show();
	},

	init_children_table:function() {
		var i, cid, ml = '';

		for (i = 0; i < this.children.length; ++i) {
			cid = this.children[i];
			ml += '\
				<tr id="'+cid+'">\
					<td><a target="_blank" href="'+e2.url('administrate/job_details?jid='+cid)+'">Child '+(i + 1)+'</a></td>\
					<td class="visual"></td>\
					<td class="status"></td>\
					<td class="hostname"></td>\
					<td class="started"></td>\
					<td class="finished"></td>\
				</tr>\
			';
		}
		this.$w_children.html('\
			<table>\
				<tbody>\
					'+ml+'\
				</tbody>\
			</table>\
		');
		this.schedule_children_status_refresh();
	},

	schedule_children_status_refresh:function() {
		window.setTimeout(this.update_children_stati.bind(this), 1234);
	},

	update_children_stati:function() {
		this.post('get_children_stati', {ids:JSON.stringify(this.children)});
	},

	get_children_stati_callback:function(request, response) {
		var i, j, key, child, $row,
			are_all_complete = true,
			simple_keys = ['status', 'hostname', 'started', 'finished']
		;

		for (i = 0; i < response.length; ++i) {
			child = response[i];
			$row = this.$w_children.find('#'+child.id);
			for (j = 0; j < simple_keys.length; ++j) {
				key = simple_keys[j];
				$row.find('.'+key).html(child[key]);
			}

			if (child.status == 'Processing') {
				$row.find('.visual').html(e2.loading());
			}
			
			// check if done
			if (Array.in_array(this.done_stati, child.status)) {
				$row.find('.visual').html('');
			}
			else {
				are_all_complete = false;
			}
		}

		if (!are_all_complete) {
			this.schedule_children_status_refresh();
		}
	}

};

function ss_init()
{
	// ss table
	if (window.location.get.key_type)
	{
	}
	else if (window.location.get.t)
	{
		$('#ss_table').table({
			'data':window.g_data,
			'show_totals':false,
			'show_nav':true,
			'click_tr':ss_table_click_tr
		});
	}
	// ss dashboard
	else
	{
		$('#ss_tables').table({
			'data':window.g_data,
			'show_totals':false,
			'col_meta':{
				'table_name':{'format':ss_format_table_name,'click':'ss_data_table_name_click'}
			}
		});
	}
}

function ss_table_click_tr(e, tr, data)
{
	var key_type, key_data, i;
	
	if (g_table_def.primary_key)
	{
		key_type = 'primary_key';
		key_data = {};
		for (i = 0; i < g_table_def.primary_key.length; ++i)
		{
			key_data[g_table_def.primary_key[i]] = data[g_table_def.primary_key[i]];
		}
	}
	window.location.qsa('key_type='+key_type+'&key_value='+escape(JSON.stringify(key_data)));
}

function ss_format_table_name(val, other_data)
{
	return ('<a href="" onclick="return false;">'+val+'</a>');
}

function ss_data_table_name_click(e, td, data)
{
	window.location.search = 'd='+data.table_schema+'&t='+data.table_name;
}

function administrate()
{
	var elem;
	
	$('#impersonate_select').bind('change', this, function(e){ e.data.impersonate_set($(this)); });
	$('div#guild_roles #guild').change(f_submit);
}

administrate.prototype.impersonate_set = function(select)
{
	if (empty(select.val())) return;
	e2.action_submit('action_impersonate_submit');
};

function mark_edit(elem){
	
	var name = elem.name;
	
	if(name.search(/sap_edit-/)==-1){
		elem.name = 'sap_edit-'+name;
		//alert(elem.name);
	}
}

// rn: add fields dynamically to default sap text

function addField(src_elem){

        field_elem = $(src_elem).parent();

        field_id = field_elem.attr("id");

        all_inputs = field_elem.children("ul").children("li");

 	last_item = all_inputs.length;

        count = Number(last_item+1);

        //count is now the number needed for the new field

        if(document.createElement) { //W3C Dom method.
            var li = document.createElement("li");
            var textarea = document.createElement("textarea");

            textarea.id = field_id+"-"+count;
            textarea.name = field_id+"-"+count;
            textarea.onchange=function(){mark_edit(this)};

            li.appendChild(textarea);

            field_elem.children("ul").append(li);

        }



}

function administrate_user_roles()
{
	$('#new_user_role_a').bind('click', this, function(e){ e.data.new_user_role_a_click($(this)); return false; });
	$('#new_user_role_cancel').bind('click', this, function(e){ e.data.new_user_role_cancel(); return false; });
	
	this.init_cur_roles();
}

administrate_user_roles.prototype.init_cur_roles = function()
{
	var data, i, role, d;
	
	data = [];
	for (i = 0; i < globals.roles.length; ++i)
	{
		d = globals.roles[i];
		d._display_name = '<a class="delete_ur_a" href="?delete&urid='+d._id+'">'+d.name+'</a>';
		data.push(d);
	}
	$('#cur_roles_wrapper').table({
		data:data
	});
	$('#cur_roles_wrapper .delete_ur_a').bind('click', this, function(e){ e.data.delete_ur_click($(this)); return false; });
};

administrate_user_roles.prototype.delete_ur_click = function(a)
{
	if (confirm('Delete '+a.closest('tr').find('td:gt(0)').map(function(){ return $(this).text(); }).get().join(', ')+'?'))
	{
		var q = String.parse_query(a.attr('href'));
		$('#delete_ur_id').val(q.urid);
		e2.action_submit('action_delete_user_role');
	}
};

administrate_user_roles.prototype.new_user_role_a_click = function(a)
{
	a.hide();
	$('#new_user_role_wrapper').show();
};

administrate_user_roles.prototype.new_user_role_cancel = function()
{
	$('#new_user_role_a').show();
	$('#new_user_role_wrapper').hide();
};

function administrate_cron()
{
	// new
	this.$new_form = $('#w_new_cron_job');
	$('#new_link').bind('click', this, function(e){ e.data.new_link_click($(this)); return false; });

	// error submitting new form, show it by triggering click
	if (globals.error_field) {
		$('#new_link').trigger('click');
		$('input[name="cron_job_'+globals.error_field+'"]').focus();
	}

	// current
	$('#w_cron_jobs').table({
		data:globals.cron_jobs,
		show_totals:false,
		id_link:'id',
		id_link_href:e2.url('administrate/cron_edit'),
		sort_init:'worker'
	});
}

administrate_cron.prototype = {
	new_link_click:function($a) {
		if (this.$new_form.is(':visible')) {
			$a.text('New Cron Job');
			this.$new_form.hide();
		}
		else {
			$a.text('Nevermind I do not want a new cron job');
			this.$new_form.show();
			$('input[name="cron_job_minute"]').focus();
		}
	}
};

function administrate_cron_edit()
{
	$('#copy_link'    ).bind('click', this, function(e){ e.data.copy_click($(this)); return false; });
	$('#delete_button').bind('click', this, function(e){ return e.data.delete_click(); });

	// created a copy, update with new id
	if (window.globals.copy_id) {
		window.location.replace_path(window.location.pathname+'?id='+window.globals.copy_id);
	}
}

administrate_cron_edit.prototype = {
	copy_click:function($a) {
		var $msg = $('#copy_msg'),
			$button = $('#edit_button')
		;

		// copy had been clicked, revert
		if ($msg.is(':visible')) {
			$a.text('Make a Copy');
			$msg.hide();
			$button.attr('a0', 'action_cron_edit_submit');
		}
		else {
			$a.text('Nevermind');
			$msg.show();
			$button.attr('a0', 'action_cron_copy_submit');
		}
	}

	,delete_click:function() {
		return confirm('Delete this cron job??');
	}
};

function administrate_payment_smallb_to_agency()
{
	$('#smallb_id_submit').bind('click', this, function(e){ e.data.smallb_id_submit_click(); });
}

administrate_payment_smallb_to_agency.prototype = {
	smallb_id_submit_click:function() {
		$('#f').attr('method', 'get');
	}
};
