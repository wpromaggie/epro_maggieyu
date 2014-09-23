
function swapp_post()
{
	this.$separate_messages = $('input[name="separate_messages"]');
	this.$when = $('input[name="when"]');
	this.is_media = ($('#current_media').length !== 0);

	this.networks = new Networks(this);
	// no networks, nothing to do
	if (!this.networks.ok) {
		return;
	}

	this.$separate_messages.bind('click', this, function(e){ e.data.separate_messages_click(); });
	this.$when.bind('click', this, function(e){ e.data.when_click(); });
	$('#media').bind('change', this, function(e){ return e.data.media_change($(this)); });
	$('#remove_media').bind('click', this, function(e){ e.data.remove_media_click(); return false; });
	$('#delete_button').bind('click', this, function(e){ return e.data.delete_click(); });
	$('#copy_post').bind('click', this, function(e){ e.data.copy_click(); return false; });
	$('#refresh_albums_link').bind('click', this, function(e){ e.data.refresh_albums_click(); return false; });

	// hook for message length so we can add on characters for twitter posts with media
	$('.message').siblings('.chars_left').data('length_hook', function(len, $driver, $elem){ return this.message_length_hook(len, $driver, $elem); }.bind(this));

	this.$separate_messages.filter(':checked')[0].click();
	this.$when.filter(':checked')[0].click();
}

swapp_post.prototype = {
	when_click:function() {
		if (this.$when.filter(':checked').val() == 'Now') {
			$('#when_dt input').attr('disabled', true);
			$('#when_dt').hide();
		}
		else {
			$('#when_dt input').attr('disabled', false);
			$('#when_dt').show();
		}
	},

	message_length_hook:function(len, $driver, $elem) {
		// nothing to do for facebook
		if ($driver.is('#facebook_message')) {
			return len;
		}
		// twitter, check for media and other urls
		else {
			var $note = $('.twitter_msg_len_note'),
				tweet = $driver.val(),
				url_info = twttr.txt.extractUrlsWithIndices(tweet),
				tweet_len = twttr.txt.getTweetLength(tweet, {
					short_url_length:globals.TWITTER_URL_LEN,
					short_url_length_https:Number(globals.TWITTER_URL_LEN) + 1
				})
			;
			if (url_info.length > 0 || this.is_media) {
				$note.show();
				// media
				if (this.is_media) {
					tweet_len += globals.TWITTER_MEDIA_LEN;
					$('.twitter_media_msg').show();
				}
				else {
					$('.twitter_media_msg').hide();
				}
				// other urls
				if (url_info.length > 0) {
					$('.twitter_other_urls_msg').show();
					$('.twitter_other_urls_count').text(url_info.length);
				}
				else {
					$('.twitter_other_urls_msg').hide();
				}
			}
			else {
				tweet_len = len;
				$note.hide();
			}
			$('#twitter_msg_len').val(tweet_len);
			return tweet_len;
		}
	},

	media_change:function() {
		this.is_media = true;
		$('#remove_media').show();
		this.network_toggle_album($('#album_tr'));
		$('.message:visible').each(function(){ $(this).trigger('keyup'); });
	},

	remove_media_click:function($input) {
		this.is_media = false;
		$('#media').val(null);
		$('#remove_media').hide();
		this.network_toggle_album($('#album_tr'));
		$('.message:visible').each(function(){ $(this).trigger('keyup'); });
	},

	separate_messages_click:function() {
		if (this.$separate_messages.filter(':checked').val() == '1') {
			$('#message_tr :input').attr('disabled', true);
			$('#message_tr').hide();

			var self = this;
			$('.network_message').each(function(){
				self.network_toggle_message($(this));
			});
		}
		else {
			$('#message_tr :input').attr('disabled', false);
			$('#message_tr').show();

			// disable/hide network messages
			$('.network_message :input').attr('disabled', true);
			$('.network_message').hide();
		}
		$('.message:visible').each(function(){ $(this).trigger('keyup'); });
	},

	is_network_on:function(network) {
		return ($('div.network_options img.network').filter('[network="'+network+'"]').hasClass('on'));
	},

	network_toggle_album:function($network_elem) {
		if (this.is_media && this.is_network_on($network_elem.attr('network'))) {
			this.network_toggle_default_on($network_elem);
		}
		else {
			this.network_toggle_default_off($network_elem);
		}
	},

	network_toggle_message:function($network_elem) {
		if (this.is_network_on($network_elem.attr('network'))) {
			if (this.$separate_messages.filter(':checked').val() == '1') {
				this.network_toggle_default_on($network_elem);
			}
		}
		else {
			this.network_toggle_default_off($network_elem);
		}
	},

	network_toggle_default:function($network_elem) {
		if (this.is_network_on($network_elem.attr('network'))) {
			this.network_toggle_default_on($network_elem);
		}
		else {
			this.network_toggle_default_off($network_elem);
		}
	},

	network_toggle_default_on:function($network_elem) {
		$network_elem.show();
		$network_elem.find(':input').attr('disabled', false);
	},

	network_toggle_default_off:function($network_elem) {
		$network_elem.hide();
		$network_elem.find(':input').attr('disabled', true);
	},

	delete_click:function(){
		return confirm('Delete this post?');
	},

	copy_click:function(){
		// all we do is get rid of post id from url so when post is submitted server sees it as new
		window.location.replace_path(window.location.pathname+'?aid='+window.location.get['aid']);
		$('#network_post_results').hide();
		$('#post_title').text('NEW POST');
		$('#submit_button').show();

		// hide the link, show message saying post will be treated as new post
		$('#copy_post').hide();
		$('#copy_post').closest('td').append('<div id="post_copy_message"><br /><div class="msg success_msg">Post copied and converted to new post.</div></div>');
		setTimeout(function(){ $('#post_copy_message').fadeOut(1500); }, 1500);
	},

	refresh_albums_click:function(){
		$('#album_select_td img.loading').show();
		this.post('refresh_albums', {});
	},
	
	refresh_albums_callback:function(request, response) {
		$('#album_select_td img.loading').hide();
		if (response.error) {
			Feedback.add_error_msg(response.error);
		}
		else {
			Array.sort2d(response, 1);
			$('#album_id').html(html.options(response));
		}
	}
};

function Networks(parent)
{
	this.parent = parent;
	this.$input = $('#networks');
	if (this.$input.length) {
		this.ok = true;
		this.$wrapper = $('div.network_options');
		this.$imgs = this.$wrapper.find('img.network');

		this.$imgs.bind('click', this, function(e){ e.data.click($(this)); });

		// "click" each network to init ui
		this.$imgs.each(function(){ this.click(); });
	}
	else {
		this.ok = false;
	}
}

Networks.prototype = {
	click:function($elem) {
		var self = this;

		// elem was on, turn it off
		if ($elem.hasClass('on')) {
			$elem.removeClass('on');
			$elem.addClass('off');
		}
		// elem was off, turn it on
		else {
			$elem.removeClass('off');
			$elem.addClass('on');
		}
		this.set_networks();

		$('.'+$elem.attr('network')).each(function(){
			var $network_elem = $(this),
				callback = $network_elem.attr('network_toggle_callback');

			// elem has a callback
			if (callback) {
				self.parent['network_toggle_'+callback]($network_elem);
			}
			// no callback, default action
			else {
				self.parent.network_toggle_default($network_elem);
			}
		});
	},

	set_networks:function() {
		this.$input.val(this.$imgs.filter('.on').map(function(){ return $(this).attr('network'); }).get().join("\t"));
	}
};

function swapp_facebook()
{
	$('#f_set_page').bind('click', this, function(e){ e.data.f_set_page_click(); return false; });
}

swapp_facebook.prototype = {
	f_set_page_click:function() {
		$('#w_f_set_page_loading').show();
		this.post('facebook_get_pages', {});
	},

	facebook_get_pages_callback:function(request, response) {
		Array.sort2d(response, 1);
		$('#f_page').html(html.options(response));
		$('#w_f_set_page_loading').hide();
		$('#w_f_set_page').show();
	}
};
