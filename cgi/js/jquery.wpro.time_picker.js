
(function($){
	
	var counter = 0;
	
	$.fn.time_picker = function(settings)
	{
		this.init = function(settings)
		{
			var defaults = {
				h_per_row:4,
				m_per_row:2,
				m_step:15
			};
			
			$.extend(this, defaults, settings);
			
			// jquery constructor
			var self = this;
			this.each(function()
			{
				var src, picker, id;
				
				id = 'tp'+(counter++);
				
				src = $(this);
				src.after('\
					<div class="time_picker" id="'+id+'">\
						<table>\
							<thead>\
								<tr>\
									<th>Hour</th>\
									<th>Minute</th>\
								</tr>\
							</thead>\
							<tbody>\
								<tr>\
									<td class="time_table hour">'+self.init_hour_ml()+'</td>\
									<td class="time_table minute">'+self.init_minute_ml()+'</td>\
								</tr>\
							</tbody>\
						</table>\
					</div>\
				');
				picker = $('#'+id);
				picker.bind('click', self, function(e){ e.data.picker_click($(e.target), src); e.stopPropagation(); return false; });
				picker.bind('mouseenter', self, function(e){ picker.is_mouse_over = true; });
				picker.bind('mouseleave', self, function(e){ picker.is_mouse_over = false; });
				
				src.bind('click', self, function(e){ e.stopPropagation(); });
				src.bind('focus', self, function(e){ e.data.input_focus(src, picker); });
				src.bind('blur', self, function(e){ e.data.input_blur(picker); });
			});
			
			return this;
		};
		
		this.init_get_time_rows = function(times, num_per_row)
		{
			var time, rows, row, i;
			
			rows = [];
			row = [];
			for (i = 0; i < times.length; ++i)
			{
				row.push(times[i]);
				if (row.length == num_per_row)
				{
					rows.push(row);
					row = [];
				}
			}
			return rows;
		};
		
		this.init_get_time_rows_ml = function(rows)
		{
			var rows, row, i, j, ml, ml_row;
			
			ml = '';
			for (i = 0; i < rows.length; ++i)
			{
				row = rows[i];
				ml_row = '';
				for (j = 0; j < row.length; ++j)
				{
					ml_row += '<td class="time_td hour" val="'+String(row[j][0]).pad(2, '0', 'LEFT')+'">'+row[j][1]+'</td>';
				}
				ml += '<tr>'+ml_row+'</tr>';
			}
			return ml;
		};
		
		this.init_hour_ml = function()
		{
			var i, meridian, hours, rows, ml;
			
			ml = '';
			for (meridian in {AM:1,PM:1})
			{
				hours = [];
				for (i = 0; i < 12; ++i)
				{
					hours.push([i + ((meridian == 'PM') ? 12 : 0), ((i == 0) ? 12 : i)]);
				}
				rows = this.init_get_time_rows(hours, this.h_per_row);
				ml += '\
					<table class="meridian_table">\
						<tbody>\
							<tr>\
								<td class="meridian" rowspan="'+(rows.length + 1)+'">'+meridian+'</td>\
							</tr>\
							'+this.init_get_time_rows_ml(rows)+'\
						</tbody>\
					</table>\
				';
			}
			return ml;
		};
		
		this.init_minute_ml = function()
		{
			var rows, minutes, minute;
			
			minutes = [];
			for (minute = 0; minute < 60; minute += Number(this.m_step)) {
				minutes.push([minute, (minute) ? minute : '00']);
			}
			rows = this.init_get_time_rows(minutes, this.m_per_row);
			return '\
				<table>\
					<tbody>\
						'+this.init_get_time_rows_ml(rows)+'\
					</tbody>\
				</table>\
			';
		};
		
		this.picker_set_selected = function(input, picker)
		{
			var matches, hour, minute;
			
			matches = input.val().match(/^(\d\d):(\d\d)/);
			if (matches)
			{
				this.picker_set_hour(picker, matches[1]);
				this.picker_set_minute(picker, matches[2]);
			}
		};
		
		this.picker_set_hour = function(picker, hour)
		{
			var am_pm;
			
			am_pm = (Number(hour) < 12) ? 'AM' : 'PM';
			picker.find('.time_table.hour .meridian:contains("'+am_pm+'")').closest('.time_table').find('td[val="'+hour+'"]').addClass('on');
		};
		
		this.picker_set_minute = function(picker, minute)
		{
			picker.find('.time_table.minute td:contains("'+minute+'")').addClass('on');
		};
		
		this.picker_click = function(elem, input)
		{
			var time_picker, table, minute, hour, cur_set;
			
			if (elem.is('.time_td')) {
				table = elem.closest('.time_table');
				cur_set = table.find('td.on');
				cur_set.removeClass('on');
				
				elem.addClass('on');
				if (table.is('.minute')) {
					this.check_other_and_set(table, input, elem, 'hour');
				}
				// hour, only set input if minute is set and hour was not
				else if (cur_set.length == 0) {
					this.check_other_and_set(table, input, elem, 'minute');
				}
			}
		};
		
		this.check_other_and_set = function(table, input, elem, other_name)
		{
			var hour, minute,
				time_picker = table.closest('.time_picker'),
				other = time_picker.find('.time_table.'+other_name+' td.on');

			if (other.length != 0) {
				if (other_name == 'hour') {
					hour = other;
					minute = elem;
				}
				else {
					hour = elem;
					minute = other;
				}
				input.val(hour.attr('val')+':'+minute.attr('val'));
				time_picker.hide();
			}
		};
		
		this.input_focus = function(input, picker)
		{
			this.picker_set_selected(input, picker);
			picker.show();
		};
		
		this.input_blur = function(picker)
		{
			if (!picker.is_mouse_over)
			{
				picker.hide();
			}
		};
		
		// call constructor!?!?
		return this.init((settings) ? settings : {});
	};

})(jQuery);
