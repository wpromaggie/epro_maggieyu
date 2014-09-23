<?php

function get_multi_month_reminder_all_email_text()
{
	return <<<EMAIL_TEXT
{name},

At this point, you've been with our QuickList service for a little over {months_minus_one} months. During this time, we have been directing traffic from Google, Yahoo and Bing to {url} through the use of sponsored listings that we've been maintaining on your behalf.

Your account is currently subscribed to our {months} month {plan} package, which means your next billing is scheduled to take place on {next_bill_date}, in the amount of {charge_amount} (if you have previously paid via check, please be sure that your next check reaches us before your next billing date). By continuing with the {months} month package, you'll receive {savings_text} of service, for another {savings_amount} in savings.

Please feel free to contact us if you have any questions concerning your account, or if you would like to change your service plan or monthly package option before your next regularly scheduled billing takes place on {next_bill_date}. Our Account Managers can be reached at 888.400.9680, M-F 8:30am - 5:30pm PST, or by email at productsupport@wpromote.com.

Thank you for your continued use of our QuickList search engine visibility service. We look forward to exceeding your expectations!


The QuickList Team

============================
QuickList by Wpromote
www.wpromote.com/ql
Email: productsupport@wpromote.com
Phone: 888.400.9680
============================
EMAIL_TEXT;
}

?>