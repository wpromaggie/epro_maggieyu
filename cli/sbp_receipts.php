<?php
require_once('cli.php');
util::load_lib('sbs', 'pdf', 'billing');

cli::run();

class sbp_receipts
{
	// nubmer of days we wait before sending the receipt
	const DAYS_TO_WAIT = 3;
	
	const MAX_URL_LEN = 30;

	const EMAIL_FROM = 'productsupport@wpromote.com';
	const SIMULATION_EMAIL_TO = 'dom@wpromote.com, chimdi@wpromote.com';

	public function send_receipts()
	{
		$simulate = (array_key_exists('s', cli::$args));
		$dbg = (array_key_exists('g', cli::$args));

		// write pdf instead of sending email
		$do_write_file = (array_key_exists('w', cli::$args));

		if (array_key_exists('d', cli::$args)) {
			$date = cli::$args['d'];
		}
		else {
			$date = date(util::DATE, time() - (self::DAYS_TO_WAIT * 86400));
		}

		if ($dbg) {
			db::dbg();
		}
		$payments = payment::get_all(array(
			'select' => array(
				"payment" => array("id as pid", "client_id", "pay_method", "pay_id", "date_received", "date_attributed", "event", "notes"),
				"payment_part" => array("account_id", "dept", "amount as part_amount"),
				"account" => array("id as aid", "url", "plan"),
				"ccs" => array("name", "zip", "country", "cc_number"),
				"contacts" => array("email")
			),
			'join' => array(
				"ccs" => "ccs.id = payment.pay_id",
				"contacts" => "contacts.client_id = payment.client_id"
			),
			'join_many' => array(
				"payment_part" => "payment.id = payment_part.payment_id",
				"account" => "account.id = payment_part.account_id",
				"product" => "account.id = product.id && product.do_send_receipt"
			),
			'where' => "payment.date_attributed = '$date'",
			'flatten' => array("account" => "payment_part")
		));

		foreach ($payments as $payment) {
			// get obscured cc number
			$cc_num = billing::cc_obscure_number(billing::decrypt_val($payment->ccs->cc_number));
			$cc_num = preg_replace("/\*+/", '...', $cc_num);

			// group by account
			$receipt_num = false;
			$account_payments = array();
			foreach ($payment->payment_part as $pp) {
				if (!$receipt_num) {
					$receipt_num = substr($pp->aid, 1).'-'.str_replace('-', '', $date);
				}
				if (empty($account_payments[$pp->aid])) {
					$url = $pp->url;
					$url_info = parse_url($url);
					$url = $url_info['host'].(empty($url_info['path']) ? '' : $url_info['path']);
					if (strlen($url) > self::MAX_URL_LEN) {
						$url = $url_info['host'];
					}
					if (strlen($url) > self::MAX_URL_LEN) {
						$url = substr($url, 0, self::MAX_URL_LEN - 10).'...'.substr($url, strlen($url) - 10);
					}
					$account_payments[$pp->aid] = array(
						'description' => date(util::US_DATE, strtotime($payment->date_attributed)).', '.sbs_lib::get_full_department($pp->dept).' '.$pp->plan.', '.$url,
						'amount' => $pp->part_amount
					);
				}
				else {
					$account_payments[$pp->aid]['amount'] += $pp->part_amount;
				}
			}
			$pdf_name = 'WpromoteReceipt'.$receipt_num.'.pdf';
			$pdf = new SBP_ReceiptPDF();

			$pdf->date = date(util::US_DATE);
			$pdf->invoice_num = $receipt_num;

			$pdf->address_1 = $payment->ccs->name;
			$pdf->address_2 = $payment->ccs->zip.', '.$payment->ccs->country;
			$pdf->address_3 = $cc_num;

			$pdf->wpro_phone = 'Toll-Free: 866.WPROMOTE - Tel: 310.421.4844 - Fax: 310.356.3228';

			$pdf->charges = array();
			foreach ($account_payments as $ac_payment_info) {
				$pdf->charges[$ac_payment_info['description']] = $ac_payment_info['amount'];
			}
			$pdf->MakeThingsHappen();
			// write to file
			if ($do_write_file) {
				$pdf->Output(sys_get_temp_dir().'/'.$pdf_name, 'F');
				$pdf->Close();
				continue;
			}
			else {
				$pdf_data = $pdf->Output('', 'S');
				$pdf->Close();
			}

			if ($simulate) {
				$to = self::SIMULATION_EMAIL_TO;
			}
			else {
				$to = $payment->contacts->email;
			}
			$subject = 'Wpromote Receipt - '.$pdf->date;
			$body  = "Hello,

Your most recent Wpromote receipt has been attached for your convenience and records.

Best,
The Wpromote Small Business Team

============================ 
QuickList by Wpromote 
www.wpromote.com/small-business-products
Email: productsupport@wpromote.com 
Phone: 888.400.9680 
============================
";
			util::mail(self::EMAIL_FROM, $to, $subject, $body, array(
				'Bcc' => self::EMAIL_FROM,
				'attachments' => array(
					array('data' => $pdf_data, 'name' => $pdf_name)
				)
			));
		}
	}
}

?>