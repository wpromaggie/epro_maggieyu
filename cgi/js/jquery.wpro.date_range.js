
(function($){

	$.fn.date_range = function(){
		var opts = (arguments.length > 0) ? arguments[0] : {};

		this.each(function(){
			new DateRange(opts, $(this))
		});
		return this;
	};

	function DateRange(opts, $src)
	{
		// if we have js object, us that, otherwise use opts
		var $table, ml,
			settings = window.globals['date_range_'+$src.attr('drid')] || opts,
			self = this,
			defaults = {
				layout:'table',
				orientation:'vertical'
			}
		;

		$.extend(this, defaults, settings);

		if (this.default_defined == 'undefined') {
			this.default_defined = '';
		}

		// if date range is created is js rather than php, user may want to set keys
		if (this.do_set_keys) {
			this.init_keys();
		}

		$src.after(this.get_ml());
		$table = $src.closest('table');

		this.$start_input = $table.find('#'+this.start_key);
		this.$start_input.date_picker({date_range:self});

		this.$end_input = $table.find('#'+this.end_key);
		this.$end_input.date_picker({date_range:self});

		this.$defined_input = $table.find('#'+this.defined_key);
		this.$defined_display = $table.find('#'+this.defined_key+'_display');

		$src.remove();

		if (this.default_start || this.default_end) {
			if (this.default_start) {
				this.$start_input.val(this.default_start);
			}
			if (this.default_end) {
				this.$end_input.val(this.default_end);
			}
			this.range_type = 'custom';
		}
		else if (this.default_defined) {
			// default dates will usually be set by php, only set if we need to
			if (empty(this.start)) {
				this.set_range(this.default_defined);
			}
		}
		// update form t
		if (this.range_type == 'defined') {
			this.form_update_range((this[this.defined_key]) ? this[this.defined_key] : this.default_defined);
		}
	}

	DateRange.prototype.init_keys = function()
	{
		var i, key_base, key_default, key;
		for (i = 0; i < this.base_keys.length; ++i) {
			key_base = this.base_keys[i];
			key_default = key_base+'_date';
			key = key_base+'_key';
			if (empty(this[key])) {
				this[key] = key_default;
			}
			if (this.key_prefix) {
				this[key] = this.key_prefix+'_'+this.key;
			}
			if (this.key_suffix) {
				this[key] += '_'+this.key_suffix;
			}
		}
	};

	DateRange.prototype.get_ml = function()
	{
		var
			ml_start = '<input type="text" class="date_input date_range range_start" name="'+this.start_key+'" id="'+this.start_key+'" value="'+this.start+'" />',
			ml_end = '<input type="text" class="date_input date_range range_end" name="'+this.end_key+'" id="'+this.end_key+'" value="'+this.end+'" />',
			ml_defined = '\
				<span class="defined_display" id="'+this.defined_key+'_display"></span>\
				<input type="hidden" name="'+this.defined_key+'" id="'+this.defined_key+'" value="'+this.defined+'" />\
			'
		;
		if (this.layout == 'table' && this.orientation == 'vertical') {
			return '\
				<tr>\
					<td>Start Date</td>\
					<td>\
						'+ml_start+'\
						'+ml_defined+'\
					</td>\
				</tr>\
				<tr>\
					<td>End Date</td>\
					<td>'+ml_end+'</td>\
				</tr>\
			';
		}
		else if (this.layout == 'table' && this.orientation == 'horizontal') {
			return '\
				<tr>\
					<td>Start Date</td>\
					<td>'+ml_start+'</td>\
					<td>End Date</td>\
					<td>'+ml_end+'</td>\
					<td>'+ml_defined+'</td>\
				</tr>\
			';
		}
	};

	DateRange.prototype.get_options = function()
	{
		var options = [];

		if (this.rollover_date) {
			options.push([
				'This Client Month',
				'Previous Client Month'
			]);
		}
		options.push([
			'This Month',
			'Previous Month'
		]);
		if (this.show_quarterly) {
			options.push([
				'This Quarter',
				'Previous Quarter'
			]);
		}
		options.push([
			'Last 7',
			'Last 14',
			'Last 30',
			'Last 90'
		]);
		return options;
	};
	

	DateRange.prototype.picker_day_click = function($clicked, before_val, after_val)
	{
		// a new date was clicked, clear any defined range info we have
		if (before_val != after_val) {
			this.clear_defined();
		}
	};

	DateRange.prototype.clear_defined = function(range)
	{
		this.$defined_input.val('');
		this.$defined_display.text('');
	};

	DateRange.prototype.set_range = function(range)
	{
		this.set_inputs_from_defined(range);
		this.sync_date_picker('start');
		this.sync_date_picker('end');
	}
	
	DateRange.prototype.sync_date_picker = function(key)
	{
		var
			$input = this['$'+key+'_input'],
			picker = $input.data('Date_Picker'),
			picker_date = new Date(Number(picker.$picker.find('.day.on').attr('t')))
		;
		if (Date.js_to_str(picker_date) != $input.val()) {
			picker.display_month();
		}
	}

	DateRange.prototype.form_update_range = function(range)
	{
		this.$defined_input.val(range);
		this.$defined_display.text(range);
	};

	DateRange.prototype.get_start_of_quarter = function(reference_date)
	{
		var tmp = reference_date.getMonth() + 1;
		
		tmp = Math.ceil(tmp / 3);
		tmp = ((tmp - 1) * 3) + 1;
		tmp = String(tmp).pad(2, '0', 'LEFT');
		return reference_date.getFullYear()+'-'+tmp+'-01';
	};

	DateRange.prototype.set_inputs_from_defined = function(range)
	{
		var now, today, stmp, etmp, valid_range;

		today = new Date();
		now = today.getTime();
		valid_range = true;
		switch (range) {
			case ('Last 7'):
				this.$end_input.val(Date.js_to_str(new Date(now - 86400000)));
				this.$start_input.val(Date.js_to_str(new Date(now - 604800000)));
				break;
				
			case ('Last 14'):
				this.$end_input.val(Date.js_to_str(new Date(now - 86400000)));
				this.$start_input.val(Date.js_to_str(new Date(now - 1209600000)));
				break;
				
			case ('Last 30'):
				this.$end_input.val(Date.js_to_str(new Date(now - 86400000)));
				this.$start_input.val(Date.js_to_str(new Date(now - 2592000000)));
				break;
				
			case ('Last 90'):
				this.$end_input.val(Date.js_to_str(new Date(now - 86400000)));
				this.$start_input.val(Date.js_to_str(new Date(now - 7776000000)));
				break;
				
			case ('This Quarter'):
				this.$end_input.val(Date.js_to_str(new Date(now - 86400000)));
				this.$start_input.val(this.get_start_of_quarter(today));
				break;
				
			case ('Previous Quarter'):
				etmp = this.get_start_of_quarter(today);
				etmp = Date.js_to_str(new Date(Date.str_to_js(etmp).getTime() - 86400000));
				stmp = this.get_start_of_quarter(Date.str_to_js(etmp));
				this.$end_input.val(etmp);
				this.$start_input.val(stmp);
				break;
				
			case ('This Month'):
				this.$end_input.val(Date.js_to_str(new Date(now - 86400000)));
				stmp = Date.js_to_str(today);
				stmp = stmp.substr(0, 7)+'-01';
				this.$start_input.val(stmp);
				break;
				
			case ('Previous Month'):
				etmp = Date.js_to_str(today);
				etmp = etmp.substr(0, 7)+'-01';
				etmp = Date.js_to_str(new Date(Date.str_to_js(etmp).getTime() - 86400000));
				stmp = etmp.substr(0, 7)+'-01';
				this.$end_input.val(etmp);
				this.$start_input.val(stmp);
				break;
				
			case ('This Client Month'):
				etmp = Date.js_to_str(new Date(Date.str_to_js(this.rollover_date).getTime() - 86400000));
				stmp = Date.delta_month(this.rollover_date, -1);
					
				this.$end_input.val(etmp);
				this.$start_input.val(stmp);
				break;
				
			case ('Previous Client Month'):
				var prev_start = Date.delta_month(this.rollover_date, -1);
				etmp = Date.js_to_str(new Date(Date.str_to_js(prev_start).getTime() - 86400000));
				stmp = Date.delta_month(prev_start, -1);

				this.$end_input.val(etmp);
				this.$start_input.val(stmp);
				break;

			default:
				valid_range = false;
		}
		if (valid_range) {
			this.form_update_range(range);
		}
	};

}(jQuery));
