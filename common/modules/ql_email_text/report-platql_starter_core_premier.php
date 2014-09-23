<?php

function get_report_platql_starter_core_premier_email_text()
{
	return <<<EMAIL_TEXT
Hello again! This is your QuickList monthly email which gives you a quick reminder in regards to what url and Ad we are currently running for your campaign. We have also attached a list of your current keywords. Review the information below at your leisure and have a great day!

=====================
Campaign Information
=====================

Web Page URL: {url}

Ad title: {title}
Ad description: {description}

Keywords:
{keywords}
{report_data}
QuickList gives you the ability to edit your ads or keywords at any point. To manage your account, simply login to your online client area at:

{login_link}

* Please Note: If you have not previously logged into this account, you will need to create your password at the above link before logging in.

Having trouble logging in? Have questions about your account? We're here to help make your online marketing a success. Email us at productsupport@wpromote.com, or give us a call at 888.400.9680.

Best,

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