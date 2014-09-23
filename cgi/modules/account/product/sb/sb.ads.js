
function sb_account_dashboard()
{
	$('#trial_a').bind('click', this, function(e){ e.data.trial_a_click(); return false; });
}

sb_account_dashboard.prototype.trial_a_click = function()
{
	$('#trial_tbody').toggle();
};

function sb_account_ads()
{
	var ad_id = window.location.get['ad_id'];
	
	// ad_id not on page, deleted?
	if (ad_id && $('div.creative_sample_container[ad_id="'+ad_id+'"]').length == 0)
	{
		window.location.replace_path('?aid='+window.location.get['aid']);
	}
}

/*
 * should ejo be a row?
 */
function upload_table()
{
	this.init_selects();
	this.init_edit_type();
}

upload_table.prototype.get_fb_id = function(elem)
{
	return elem.closest('tr').attr('fb_id');
};

upload_table.prototype.init_selects = function()
{
	var i, select, fb_id,
		ad_selects = this.find('.ad_select');
	
	for (i = 0; i < ad_selects.length; ++i)
	{
		select = $(ad_selects[i]);
		fb_id = this.get_fb_id(select);
		select.find('.creative_sample_container').bind('click', this, function(e){ e.data.ad_select_click($(this)); });
	}
};

upload_table.prototype.init_edit_type = function()
{
	var i, row, fb_id, radio,
		rows = this.find('tr[fb_id]');
	
	for (i = 0; i < rows.length; ++i)
	{
		row = $(rows[i]);
		fb_id = row.attr('fb_id');
		radio = row.find('input[name="edit_type_'+fb_id+'"]');
		radio.bind('click', this, function(e){ e.data.edit_type_click($(this)); });
		
		this.edit_type_click(radio.filter(':checked'));
	}
};

upload_table.prototype.edit_type_click = function(radio)
{
	var row = radio.closest('tr'),
		ad_select = row.find('.ad_select');
	
	if (radio.is(':checked') && radio.val() == 'update')
	{
		ad_select.show();
		row.find('.ignore_interests').show();
		
		// if none are selected, select one
		if (ad_select.find('.creative_sample_container.selected').length == 0)
		{
			ad_select.find('.creative_sample_container:nth(0)').click();
		}
	}
	else
	{
		ad_select.hide();
		row.find('.ignore_interests').hide();
	}
};

upload_table.prototype.ad_select_click = function(ad)
{
	ad.closest('.ad_select').find('.creative_sample_container').removeClass('selected');
	ad.addClass('selected');
	$('#update_id_'+this.get_fb_id(ad)).val(ad.attr('ad_id'));
};
