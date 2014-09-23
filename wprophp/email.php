<?php
/* ----
 * Description:
 *	wpro email object
 * Programmers:
 *  v1 Vyrus
 *	mc MerlinCorey
 *	cp CP
 * History:
 *	0.0.1 2008February04 First recorded version; converted to house-style
 * ---- */
if (class_exists('email')) {
	trigger_error('Error: "email" already exists!', E_USER_WARNING);
} else {
	class email {
				
		public function send($to, $message, $other = array(
			'from' => 'contact@wpromote.com',
			'followup' => null,
			'reply' => null,
			'subject' => 'Message from wpromote',
			'cc' => null,
			'bcc' => null
		), $header_endl = "\r\n") {
			if (is_array($other)) {
			
			if (!array_key_exists('from', $other)) {
				$other['from'] = 'contact@wpromote.com';
			} // missing from key
	
				if ($other['reply'] == null) {
					$other['reply'] = $other['from'];
				} // missing reply key
				
				// todo => rest of the email rfc
				$s_headers = '';
				$a_header_map = array(
					'from' => 'From',
					'followup' => 'Mail-Followup-To',
					'reply' => 'Mail-Reply-To',
					'cc' => 'Cc',
					'bcc' => 'Bcc'
				);
				
				foreach ($a_header_map as $k => $v) {
					$s_header_value = $other[$k];	
					if (!empty($s_header_value)) {
						if (!empty($s_headers)) $s_headers .= $header_endl;
						$s_headers .= $v.': '.$s_header_value;
					} // not empty
				} // each header
				
				if ($header_endl == "\n") $message = str_replace("\r\n", "\n", $message);
				$return = mail(
					$to,
					$other['subject'],
					$message,
					$s_headers
				);
			} // other is array
			return $return;
		} // send
	}; // email
} // class exists ?>