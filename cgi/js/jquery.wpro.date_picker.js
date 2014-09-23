
(function($){
	
	var PICKER_LEFT_PADDING = 50;

	var id_counter = 0;

	function Date_Picker(settings, _src)
	{
		this.id = 'dp'+(id_counter++);
		this.has_been_focused = false;
		this.src = _src;

		$.extend(this, settings);

		this.src.after('\
			<div class="date_picker" id="'+this.id+'">\
				<table>\
					<tbody>\
						<tr>\
							<td class="w_ranges">'+this.ml_date_picker_select()+'</td>\
							<td>\
								<table class="date_table">\
									<thead>\
										<tr class="month">\
											<th class="month_change" delta="-1">&laquo;</th>\
											<th class="month_year" colspan=5></th>\
											<th class="month_change" delta="1">&raquo;</th>\
										</tr>\
										<tr class="days_of_week"><th>S</th><th>M</th><th>T</th><th>W</th><th>T</th><th>F</th><th>S</th></tr>\
									</thead>\
									<tbody class="days">\
									</tbody>\
								</table>\
							</td>\
						</tr>\
					</tbody>\
				</table>\
			</div>\
		');

		this.$picker = $('#'+this.id);

		// if we have a date range, picker is wider, center it a little
		// we make sure we haven't moved too far left once picker is actually displayed
		if (this.date_range) {
			this.$picker.css('left', '-'+Math.round(this.$picker.width() / 4)+'px');
		}
		
		this.$picker.bind('click', this, function(e){ e.data.picker_click($(e.target)); e.stopPropagation(); return false; });
		this.$picker.bind('mouseenter', this, function(e){ e.data.is_mouse_over = true; });
		this.$picker.bind('mouseleave', this, function(e){ e.data.is_mouse_over = false; });
		this.$picker.find('#'+this.id+'_select').bind('change', this, function(e){ e.data.select_change($(this)); });
		
		this.src.bind('click', this, function(e){ e.stopPropagation(); });
		this.src.bind('focus', this, function(e){ e.data.input_focus(); });
		this.src.bind('blur', this, function(e){ e.data.input_blur(); });
	}
	
	Date_Picker.prototype.ml_date_picker_select = function()
	{
		if (this.date_range) {
			var i, j, option_group, ml_group, option,
				options = this.date_range.get_options(),
				ml = ''
			;
			for (i = 0; i < options.length; ++i) {
				option_group = options[i];
				ml_group = '';
				for (j = 0; j < option_group.length; ++j) {
					option = option_group[j];
					ml_group += '<p><a class="range_link'+((option == 'aoifejo') ? ' selected' : '')+'" href="">'+option+'</a></p>';
				}
				ml += '<div class="group">'+ml_group+'</div>';
			}
			return ml;
		}
		else {
			return '';
		}
	};
	
	Date_Picker.prototype.find_range_input = function(type)
	{
		var input, ancestor;
		
		for (i = 0, ancestor = this.src.parent(); ancestor.length > 0; ++i, ancestor = ancestor.parent())
		{
			input = ancestor.find('input.range_'+type);
			if (input.length > 0)
			{
				return input;
			}
		}
		return false;
	};
	
	/*
	 * show month from src input
	 * optionally pass in month we want to display
	 */
	Date_Picker.prototype.display_month = function()
	{
		var date;
		
		if (arguments.length > 0)
		{
			date = arguments[0];
		}
		else
		{
			date = Date.str_to_js(this.src.val());
			if (!date)
			{
				date = new Date();
			}
		}
		this.$picker.find('.month_year').text(String(Date.month_by_index(date.getMonth())+' '+date.getFullYear()));
		this.$picker.find('.days').html(this.get_month_ml(date));
	};
	
	/*
	 * the actual date doesn't matter, we just want a Date object of the month we need to show
	 */
	Date_Picker.prototype.get_month_ml = function(date)
	{
		var ml, ml_week, classes, i, j, month, weeks, week, day, day_of_month, day_of_week, week_of_month, today, today_date, selected_date, selected_day;
		
		month = date.getMonth();
		
		// for highlighting today
		today_date = new Date();
		today = today_date.getDate();
		
		// for highlighting selected
		selected_date = Date.str_to_js(this.src.val());
		if (selected_date)
		{
			selected_day = (selected_date) ? selected_date.getDate() : false;
		}
		
		weeks = [];
		for (i = new Date(date.getFullYear(), month, 1, 12, 0, 0); i.getMonth() == month; i = new Date(i.getTime() + 86400000))
		{
			day_of_month = i.getDate();
			day_of_week = i.getDay();
			week_of_month = Math.ceil(day_of_month / 7) + ((((day_of_month - 1) % 7) > day_of_week) ? 1 : 0) - 1;
			
			classes = ['day'];
			if (day_of_month == today && month == today_date.getMonth() && date.getFullYear() == today_date.getFullYear()) classes.push('today');
			if (selected_date && day_of_month == selected_day && month == selected_date.getMonth() && date.getFullYear() == selected_date.getFullYear()) classes.push('on');
			
			if (!weeks[week_of_month])
			{
				weeks[week_of_month] = [];
			}
			weeks[week_of_month][day_of_week] = '<td t="'+i.getTime()+'" class="'+classes.join(' ')+'">'+i.getDate()+'</td>';
		}
		
		ml = '';
		for (i = 0; i < weeks.length; ++i)
		{
			week = weeks[i];
			ml_week = '';
			for (j = 0; j < week.length; ++j)
			{
				day = week[j];
				ml_week += (day) ? day : '<td></td>';
			}
			ml += '<tr>'+ml_week+'</tr>';
		}
		
		return ml;
	};
	
	Date_Picker.prototype.picker_click = function($clicked)
	{
		if ($clicked.is('.day')) {
			var before = this.src.val();
			this.set_date($clicked);
			if (this.date_range) {
				this.date_range.picker_day_click($clicked, before, this.src.val());
			}
		}
		else if ($clicked.is('.month_change')) {
			this.change_month($clicked);
		}
		else if ($clicked.is('.range_link')) {
			this.date_range.set_range($clicked.text());
			this.$picker.hide();
		}
	};
	
	Date_Picker.prototype.set_date = function(day_td)
	{
		var date;
		
		date = new Date(Number(day_td.attr('t')));
		
		// toggle previously clicked and currently clicked
		this.$picker.find('.day.on').removeClass('on');
		day_td.addClass('on');
		
		// set input and hide cal
		this.src.val(Date.js_to_str(date));
		this.$picker.hide();
	};
	
	Date_Picker.prototype.change_month = function(month_change_th)
	{
		var sample_day, new_month;
		
		sample_day = this.$picker.find('td.day:contains("15")');
		new_month = Date.add_month(sample_day.attr('t'), Number(month_change_th.attr('delta')));
		this.display_month(new_month);
	};
	
	Date_Picker.prototype.input_focus = function()
	{
		if (!this.has_been_focused) {
			this.has_been_focused = true;
			this.display_month();
		}
		this.$picker.show();
		// make sure picker isn't too far left
		var offset = this.$picker.offset();
		if (offset.left < PICKER_LEFT_PADDING) {
			this.$picker.css('left', Math.round(parseInt(this.$picker.css('left')) + (PICKER_LEFT_PADDING - offset.left))+'px');
		}
		// highlight defined range if one is selected
		var defined = (this.date_range && this.date_range.$defined_input) ? this.date_range.$defined_input.val() : false;
		this.$picker.find('a.range_link.selected').removeClass('selected');
		if (!empty(defined)) {
			this.$picker.find('a.range_link').filter(function(){ return $(this).text() == defined}).addClass('selected');
		}
	};
	
	Date_Picker.prototype.clear_defined = function()
	{
		if (this.date_range) {
			this.date_range.clear_defined();
		}
	};

	Date_Picker.prototype.input_blur = function()
	{
		if (!this.is_mouse_over) {
			this.$picker.hide();
		}
	};

	$.fn.date_picker = function(settings)
	{
		this.each(function(){
			var
				$elem = $(this),
				dp = new Date_Picker(settings, $(this))
			;
			$elem.data('Date_Picker', dp);
		});
		return this;
	};

})(jQuery);
