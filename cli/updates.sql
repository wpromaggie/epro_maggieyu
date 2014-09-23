#2014.07.03
CREATE TABLE IF NOT EXISTS `delly`.`offline_conversion_schedule` (
  `aid` CHAR(16) NOT NULL DEFAULT '',
  `next_runtime_upload` DATETIME NULL DEFAULT NULL,
  `frequency_upload` INT(10) NULL DEFAULT NULL,
  `market` CHAR(5) NULL DEFAULT NULL,
  `next_runtime_download` DATETIME NULL DEFAULT NULL,
  `frequency_download` INT(11) NULL DEFAULT NULL,
  PRIMARY KEY (`aid`))
ENGINE = InnoDB
DEFAULT CHARACTER SET = latin1

2014.02.03
ALTER TABLE eppctwo.m_api_accounts ADD `client_id` text AFTER `access_key`,
	ADD `client_secret` text AFTER `client_id`,
	ADD `redirect_uri` varchar(255) AFTER `client_secret`;

2014.02.03
ALTER TABLE eppctwo.m_accounts ADD `j_auth` text AFTER `pass`,
	ADD `last_updated` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' AFTER `j_auth`;


2014.02.03
CREATE TABLE `delta_kv` (
  `id` char(36) NOT NULL,
  `meta_id` char(36) NOT NULL,
  `field` varchar(64) NOT NULL,
  `value` varchar(255) DEFAULT NULL,
  `delete` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

2014.02.03
CREATE TABLE `delta_meta` (
  `id` char(36) NOT NULL,
  `user` char(36) NOT NULL,
  `time` datetime NOT NULL,
  `database` varchar(64) NOT NULL DEFAULT '',
  `table` varchar(64) NOT NULL,
  `pk_name` varchar(64) DEFAULT NULL,
  `pk_value` varchar(64) DEFAULT NULL,
  `operation` char(20) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

28.01.2014
alter table eppctwo.client_payment_part add `rep_comm` double not null default -1 after `rep_pay_num`;

22.01.2014
alter table eppctwo.clients_partner add `partner_ppc_budget` double not null default 0 after `partner_ppc_fee`;

15.01.2014
alter table delly.job add `scheduled` datetime null default '0000-00-00 00:00:00' after `status`;

04.12.2013
alter table eppctwo.ppc_data_source_refresh add `do_force` bool not null default 0 after `refresh_type`;

27.11.2013
CREATE TABLE `wpro_event` (
`id` char(8) NOT NULL DEFAULT '',
`date` date NOT NULL DEFAULT '0000-00-00',
`all_day` tinyint(1) NOT NULL DEFAULT '1',
`start_time` time NOT NULL DEFAULT '00:00:00',
`end_time` time NOT NULL DEFAULT '00:00:00',
`type` char(32) NOT NULL DEFAULT '',
`name` char(120) NOT NULL DEFAULT '',
PRIMARY KEY (`id`),
KEY `date` (`date`)
);

10.11.2013
alter table social.post_media add `path` char(255) null default '' after `name`;

04.11.2013
CREATE TABLE `request` (
 `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
 `interface` char(8) NOT NULL DEFAULT '',
 `user` char(32) NOT NULL DEFAULT '',
 `context` char(16) NOT NULL DEFAULT '',
 `start_time` double NOT NULL DEFAULT '0',
 `end_time` double NOT NULL DEFAULT '0',
 `elapsed` double NOT NULL DEFAULT '0',
 `hostname` char(32) NOT NULL DEFAULT '',
 `max_memory` bigint(20) NOT NULL DEFAULT '0',
 `is_error` tinyint(4) NOT NULL DEFAULT '0',
 `url` varchar(1024) NOT NULL DEFAULT '',
 `ip` char(16) NOT NULL DEFAULT '',
 `path` char(200) NOT NULL DEFAULT '',
 `args` char(200) NOT NULL DEFAULT '',
 PRIMARY KEY (`id`)
);

31.10.2013
alter table eppctwo.users add `offsite_clockin_ok` bool not null default 0 after `exempt`;

24.10.2013
ALTER TABLE `eppctwo`.`reports` DROP INDEX `user` ,
ADD INDEX `user` ( `user` , `client` );

06.10.2013
CREATE TABLE `ppc_report_sheet` (
`report_id` bigint(20) unsigned NOT NULL,
`id` char(6) NOT NULL DEFAULT '',
`name` char(100) DEFAULT '',
`position` int(10) unsigned DEFAULT '0',
PRIMARY KEY (`id`),
KEY `report_id` (`report_id`)
);

CREATE TABLE `ppc_report_table` (
`report_id` bigint(20) unsigned NOT NULL,
`sheet_id` char(6) DEFAULT '',
`id` char(8) NOT NULL DEFAULT '',
`position` int(10) unsigned DEFAULT '0',
`definition` text,
PRIMARY KEY (`id`),
KEY `report_id` (`report_id`)
);

27.09.2013
alter table eppctwo.clients_ppc add `ncid` char(16) null default '' after `client`;
alter table eppctwo.clients_ppc add `naid` char(16) null default '' after `ncid`;
alter table eppctwo.ppc_data_source_refresh change `client_id` `client_id` char(32) not null default '';

drop table eppctwo.ql_spend;
CREATE TABLE eppctwo.ql_spend (
`account_id` char(16) NOT NULL default '',
`days_to_date` tinyint(3) unsigned NOT NULL,
`days_remaining` tinyint(3) unsigned NOT NULL,
`days_in_month` tinyint(3) unsigned NOT NULL,
`imps_to_date` int(10) unsigned NOT NULL default '0',
`spend_to_date` double NOT NULL,
`spend_remaining` double NOT NULL,
`spend_prev_month` double NOT NULL default '0',
`daily_to_date` double NOT NULL,
`daily_remaining` double NOT NULL,

`imps` int(10) unsigned NOT NULL DEFAULT '0',
`clicks` int(10) unsigned NOT NULL DEFAULT '0',
`cost` double unsigned NOT NULL DEFAULT '0',

`g_imps` int(10) unsigned NOT NULL DEFAULT '0',
`g_clicks` int(10) unsigned NOT NULL DEFAULT '0',
`g_cost` double unsigned NOT NULL DEFAULT '0',

`m_imps` int(10) unsigned NOT NULL DEFAULT '0',
`m_clicks` int(10) unsigned NOT NULL DEFAULT '0',
`m_cost` double unsigned NOT NULL DEFAULT '0',
PRIMARY KEY  (`account_id`)
);

09.03.2013
alter table eppctwo.secondary_manager add `dept` char(16) not null default '' after `client_id`;
alter table eppctwo.secondary_manager drop primary key;
alter table eppctwo.secondary_manager add primary key (client_id, dept, user_id);
update eppctwo.secondary_manager set dept = 'ppc' where dept = '';

08.07.2013
create table eppctwo.clients_email ( `client` char(32) not null default '', `manager` char(64) not null default '', `status` char(16) not null default 'On', `billing_contact_id` bigint(20) unsigned not null, `bill_day` tinyint unsigned not null default 0, `prev_bill_date` date not null default '0000-00-00', `next_bill_date` date not null default '0000-00-00', `url` char(128) not null default '', primary key (`client`) ) engine=MyISAM;

28.05.2013
# enet changes
create database g_objects;
create database g_data_tmp;
create database m_objects;
create database m_data_tmp;
create database delly;
ALTER TABLE eppctwo.conv_type_count CHANGE `account` `account_id` CHAR( 16 ) NOT NULL DEFAULT '';
ALTER TABLE eppctwo.conv_type_count CHANGE `campaign` `campaign_id` CHAR( 16 ) NOT NULL DEFAULT '';
ALTER TABLE eppctwo.conv_type_count CHANGE `ad_group` `ad_group_id` CHAR( 32 ) NOT NULL DEFAULT '';
ALTER TABLE eppctwo.conv_type_count CHANGE `ad` `ad_id` CHAR( 32 ) NOT NULL DEFAULT '';
ALTER TABLE eppctwo.conv_type_count CHANGE `keyword` `keyword_id` CHAR( 32 ) NOT NULL DEFAULT '';
alter table eppctwo.conv_type_count add `device` char(32) not null default '' after `keyword_id`;
alter table standardized_reports.report add `market` char(4) not null default '' after `account_id`;
CREATE TABLE delly.`job` (
`id` char(24) NOT NULL DEFAULT '',
`parent_id` char(24) DEFAULT '',
`fid` char(32) DEFAULT '0',
`type` char(64) DEFAULT '',
`user_id` char(32) DEFAULT '0',
`account_id` char(16) DEFAULT '',
`hostname` char(32) DEFAULT '',
`process_id` int(11) DEFAULT '0',
`status` char(16) DEFAULT '',
`created` datetime DEFAULT '0000-00-00 00:00:00',
`started` datetime DEFAULT '0000-00-00 00:00:00',
`finished` datetime DEFAULT '0000-00-00 00:00:00',
PRIMARY KEY (`id`),
KEY `status` (`status`)
);
CREATE TABLE `cron_job` (
`id` char(8) NOT NULL DEFAULT '',
`minute` char(128) DEFAULT '*',
`hour` char(128) DEFAULT '*',
`day_of_month` char(128) DEFAULT '*',
`month` char(128) DEFAULT '*',
`day_of_week` char(128) DEFAULT '*',
`status` char(16) DEFAULT '',
`worker` char(64) DEFAULT '',
`args` char(200) DEFAULT '',
`comments` char(250) DEFAULT '',
PRIMARY KEY (`id`)
);
create table delly.job_detail ( `id` int unsigned null auto_increment, `job_id` char(24) null default '', `ts` datetime null default '0000-00-00 00:00:00', `level` tinyint(16) unsigned null default 0, `message` char(250) null default '', primary key (`id`), index (`job_id`) );
create table delly.cron_runner ( `dt` datetime null default '0000-00-00 00:00:00', `hostname` char(32) null default '', primary key (`dt`) );

20.05.2013
alter table eac.product add `do_send_receipt` bool not null default 0 after `do_report`;

09.05.2013
alter table eac.ap_sb add `has_ads` bool not null default 0 after `id`;
alter table eac.ap_sb add `has_soci` bool not null default 0 after `has_ads`;

22.04.2013
create table eppctwo.client_media ( `id` char(8) null default '', `client_id` char(16) null default '', `user_id` int unsigned null default 0, `ts` datetime null default '0000-00-00 00:00:00', `type` char(16) null default '', `name` char(96) null default '', `data` mediumblob null, `w` int unsigned null default 0, `h` int unsigned null default 0, primary key (`id`), index (`client_id`) ) engine=MyISAM;
create table eppctwo.client_media_use ( `id` char(10) null default '', `client_media_id` char(8) null default '', `use` char(64) null default '', primary key (`id`), index (`client_media_id`) ) engine=MyISAM;

11.04.2013
create table social.network_post_error ( `id` int unsigned not null auto_increment, `network_post_id` char(16) null default '', `posted_at` datetime null default '0000-00-00 00:00:00', `error` char(200) null default '', primary key (`id`), index (`network_post_id`) ) engine=MyISAM;
	
01.04.2013
create table eppctwo.conv_type_count ( `id` char(16) null default '', `client` char(16) not null default '', `d` date not null default '0000-00-00', `market` char(2) not null default '', `account` char(16) not null default '', `campaign` char(16) not null default '', `ad_group` char(32) not null default '', `ad` char(32) not null default '', `keyword` char(32) not null default '', `purpose` char(64) not null default '', `name` char(64) not null default '', `amount` smallint not null default 0, primary key (`id`), index (`client`,`d`) ) engine=MyISAM;
create table eppctwo.conv_type ( `id` char(8) null default '', `client` char(16) not null default '', `canonical` char(64) not null default '', primary key (`id`), index (`client`) ) engine=MyISAM;
create table eppctwo.conv_type_market ( `conv_type_id` char(8) null default '', `market` char(2) not null default '', `market_name` char(64) not null default '', primary key (`conv_type_id`,`market`) ) engine=MyISAM;
alter table eppctwo.clients_ppc add `conversion_types` bool not null default 0 after `google_mpc_tracking`;

18.01.2013
alter table eac.product add `is_trial` bool null default 0 after `oid`;

16.01.2013
ALTER TABLE `prospects` CHANGE `client_id` `client_id` CHAR( 16 ) NOT NULL DEFAULT '0';

29.11.2012
alter table eppctwo.clients_seo add `billing_reminder` bool not null default 1 after `link_builder_manager`;
alter table eppctwo.clients_seo add `bill_day` tinyint unsigned not null default 0 after `billing_reminder`;
alter table eppctwo.clients_seo add `next_bill_date` date not null default '0000-00-00' after `bill_day`;
alter table eppctwo.clients_seo add `prev_bill_date` date not null default '0000-00-00' after `next_bill_date`;

04.10.2012
alter table time_temp change `type` `type` char(16) not null default '';

19.09.2012
alter table eppctwo.sb_fb_extras add `conversion_specs` char(64) not null default '' after `max_bid_social`;

19.09.2012
alter table eppctwo.sb_fb_extras add `custom_audiences` varchar(128) not null default '' after `broad_category_clusters`;
alter table eppctwo.sb_fb_extras add `targeted_entities` varchar(128) not null default '' after `custom_audiences`;

28.08.2012
create table eppctwo.google_app_token (
`id` int unsigned not null auto_increment,
`scopes` char(128) not null default '',
`iss` char(128) not null default '',
`prn` char(128) not null default '',
`created` int unsigned not null default 0,
`expires_in` int unsigned not null default 0,
`expires_at` int unsigned not null default 0,
`access_token` char(200) not null default '',
primary key (`id`),
unique (`scopes`,`iss`,`prn`)
) engine=MyISAM;

27.08.2012
alter table eppctwo.reports change `sheets` `sheets` text NOT NULL;

07.08.2012
create table log.payment_error (
`id` bigint unsigned not null auto_increment,
`d` date not null default '0000-00-00',
`t` time not null default '00:00:00',
`user` int unsigned not null default 0,
`dept` char(16) not null default '',
`client_id` bigint unsigned not null default 0,
`account_id` bigint unsigned not null default 0,
`msg` varchar(512) not null default '',
primary key (`id`)
) engine=MyISAM;

01.08.2012
ALTER TABLE  surveys.client_surveys ADD `user_id` INT( 10 ) NOT NULL;

31.07.2012
alter table eppctwo.sb_fb_extras add `campaign_type` char(32) not null default '' after `campaign_lifetime_budget`;

25.07.2012
alter table eppctwo.gs_urls add `boo_id` int unsigned not null after `cc_id`;
alter table eppctwo.gs_urls add `boo_note` varchar(512) not null default '' after `boo_id`;

ALTER TABLE  eppctwo.ql_url ADD  `subid` VARCHAR( 32 ) NOT NULL AFTER  `source`;
ALTER TABLE  eppctwo.sb_groups ADD  `subid` VARCHAR( 32 ) NOT NULL AFTER  `source`;
ALTER TABLE  eppctwo.gs_urls ADD  `subid` VARCHAR( 32 ) NOT NULL AFTER  `source`;

22.06.2012
create table eppctwo.ql_ad (
`id` int unsigned not null auto_increment,
`market` char(2) not null default '',
`ad_group_id` char(32) not null default '',
`ad_id` char(32) not null default '',
`account_id` bigint unsigned not null default 0,
`is_su` tinyint unsigned not null default 0,
primary key (`id`),
unique (`market`,`ad_group_id`,`ad_id`),
index (`account_id`)
) engine=MyISAM;

26.06.2012
alter table eppctwo.sb_fb_extras change `likes_and_interests` `likes_and_interests` varchar(1024) not null default '';

25.06.2012
alter table eppctwo.sb_fb_extras add `max_bid_social` char(8) not null default '' after `demo_link`;
alter table eppctwo.sb_fb_extras add `app_platform_type` char(16) not null default '' after `creative_type`;

14.06.2012
create table eppctwo.sales_hierarch (
`id` bigint unsigned not null auto_increment,
`pid` int unsigned not null default 0,
`cid` int unsigned not null default 0,
primary key (`id`)
) engine=MyISAM;

08.06.2012
alter table eppctwo.sbs_contacts add `lphid` int not null default 0;

06.06.2012
create table eppctwo.ad_refresh_log (
`id` int unsigned not null auto_increment,
`client_id` bigint not null default 0,
`user_id` int unsigned not null default 0,
`market` char(8) not null default '',
`process_dt` datetime not null default '0000-00-00 00:00:00',
`start_date` date not null default '0000-00-00',
`end_date` date not null default '0000-00-00',
primary key (`id`)
) engine=MyISAM;

31.05.2012
alter table eppctwo.sbs_contacts add `ip` varchar(40) NOT NULL default '';
alter table eppctwo.sbs_contacts add `browser` varchar(200) NOT NULL default '';

22.05.2012
create table eppctwo.secondary_manager (
`client_id` bigint not null default 0,
`user_id` int unsigned not null default 0,
primary key (`client_id`,`user_id`),
index (`user_id`)
) engine=MyISAM;

15.05.2012
alter table sales_leads.url_lead change `lead_upload_id` `url_lead_upload_id` int unsigned not null;
alter table sales_leads.url_lead_url change `lead_upload_id` `url_lead_upload_id` int unsigned not null;

09.05.2012
alter table eppctwo.sb_fb_extras add `excluded_user_adclusters` varchar(1024) not null default '' after `likes_and_interests`;

27.04.2012
create table sales_leads.contact_lead (
`id` int unsigned not null auto_increment,
`upload_id` int unsigned not null,
`email` char(64) not null default '',
primary key (`id`),
unique (`email`)
) engine=MyISAM;

create table sales_leads.disqualified_lead (
`id` int unsigned not null auto_increment,
`upload_id` int unsigned not null,
`email` char(64) not null default '',
primary key (`id`),
unique (`email`)
) engine=MyISAM;

create table sales_leads.lead (
`id` int unsigned not null auto_increment,
`upload_id` int unsigned not null,
`is_dup` bool not null default 0,
`dup_type` char(16) not null default '',
`dup_upload_id` int unsigned not null default 0,
`company` char(64) not null default '',
`prefix` char(8) not null default '',
`first` char(32) not null default '',
`last` char(32) not null default '',
`phone` char(64) not null default '',
`email` char(64) not null default '',
`title` char(64) not null default '',
`address` text not null default '',
`url` char(128) not null default '',
`biz_desc` text not null default '',
primary key (`id`),
unique (`email`),
index i0 (`upload_id`)
) engine=MyISAM;

create table sales_leads.upload_contact_file (
`id` int unsigned not null auto_increment,
`created` datetime not null default '0000-00-00 00:00:00',
`name` varchar(128) not null default '',
primary key (`id`)
) engine=MyISAM;

create table sales_leads.upload_disqualified_file (
`id` int unsigned not null auto_increment,
`created` datetime not null default '0000-00-00 00:00:00',
`name` char(128) not null default '',
primary key (`id`)
) engine=MyISAM;

create table sales_leads.upload_lead_file (
`id` int unsigned not null auto_increment,
`created` datetime not null default '0000-00-00 00:00:00',
`name` char(128) not null default '',
primary key (`id`)
) engine=MyISAM;

24.04.2012
create table eppctwo.user_role (
`id` int(11) unsigned not null auto_increment,
`user` int(11) unsigned not null,
`guild` char(32) not null default '',
`role` char(32) not null default '',
primary key (`id`)
) engine=MyISAM;

10.04.2012
update eppctwo.client_payment_part set type = 'Partner PPC' where type = 'Televox QuickList';
update eppctwo.client_payment_part set type = 'Partner SMO' where type = 'Televox SocialBoost';
update eppctwo.client_payment_part set type = 'Partner SEO' where type = 'Televox GoSEO';

alter table eppctwo.clients_tv rename to clients_partner;
alter table eppctwo.tv_contacts rename to partner_contacts;

ALTER TABLE eppctwo.partner_contacts CHANGE  `tv_rep`  `rep` VARCHAR( 32 ) NOT NULL;
alter table eppctwo.clients_partner CHANGE  `televox_quicklist_fee`  `partner_ppc_fee` DOUBLE NOT NULL DEFAULT  '0';
alter table eppctwo.clients_partner CHANGE  `televox_socialboost_fee`  `partner_smo_fee` DOUBLE NOT NULL DEFAULT  '0';
alter table eppctwo.clients_partner CHANGE  `televox_goseo_fee`  `partner_seo_fee` DOUBLE NOT NULL DEFAULT  '0';

update eppctwo.users set primary_guild = 'partner' where primary_guild = 'tv';
update eppctwo.user_guilds set guild_id = 'partner' where guild_id = 'tv';

03.04.2012
alter table f_data.clients_ql rename to f_data.clients_sb;
alter table f_data.campaigns_ql rename to f_data.campaigns_sb;
alter table f_data.ad_groups_ql rename to f_data.ad_groups_sb;
alter table f_data.ads_ql rename to f_data.ads_sb;
alter table f_data.keywords_ql rename to f_data.keywords_sb;

update sb_ads set fb_id = substring(fb_id, 2) where fb_id like 'a%';


30.03.2012
create table eppctwo.sb_fb_extras (
`ad_id` bigint unsigned not null default 0,
`campaign_id` char(32) not null default '',
`campaign_run_status` char(16) not null default '',
`campaign_name` char(128) not null default '',
`campaign_time_start` char(64) not null default '',
`campaign_time_stop` char(64) not null default '',
`campaign_daily_budget` char(8) not null default '',
`campaign_lifetime_budget` char(16) not null default '',
`ad_status` char(16) not null default '',
`demo_link` varchar(512) not null default '',
`related_page` char(64) not null default '',
`image_hash` char(64) not null default '',
`creative_type` char(16) not null default '',
`link_object_id` char(64) not null default '',
`story_id` char(64) not null default '',
`auto_update` char(8) not null default '',
`url_tags` char(64) not null default '',
`view_tags` char(64) not null default '',
`connections` char(64) not null default '',
`excluded_connections` char(64) not null default '',
`friends_of_connections` char(64) not null default '',
`locales` char(64) not null default '',
`likes_and_interests` varchar(512) not null default '',
`broad_category_clusters` char(64) not null default '',
`broad_age` char(8) not null default '',
`actions` char(64) not null default '',
`image` char(64) not null default '',
primary key (`ad_id`)
) engine=MyISAM;

29.03.2012
alter table eppctwo.clients add `partner` varchar(32) not null default '' after `name`;

28.03.2012
/* e2 */
update eppctwo.sb_ads set sex = 'All' where sex = '';
update eppctwo.sb_ads set sex = 'Men' where sex = 'male';
update eppctwo.sb_ads set sex = 'Women' where sex = 'female';

update eppctwo.sb_ads set interested_in = 'All' where interested_in = '';
update eppctwo.sb_ads set interested_in = 'Men' where interested_in = 'men';
update eppctwo.sb_ads set interested_in = 'Women' where interested_in = 'women';

update eppctwo.sb_ads set education_status = 'All' where education_status = '';
update eppctwo.sb_ads set education_status = 'College Grad' where education_status = 'alumni';
update eppctwo.sb_ads set education_status = 'College' where education_status = 'college';
update eppctwo.sb_ads set education_status = 'High School' where education_status = 'high school';

/* w3 */
ALTER TABLE w3.socialboost_ads CHANGE  `status` `status` enum('','active','paused') NOT NULL default '';
ALTER TABLE w3.socialboost_ads CHANGE  `name` `name` varchar(35) NOT NULL default '';
ALTER TABLE w3.socialboost_ads CHANGE  `title` `title` varchar(25) NOT NULL default '';
ALTER TABLE w3.socialboost_ads CHANGE  `image` `image` tinytext NOT NULL default '';
ALTER TABLE w3.socialboost_ads CHANGE  `body_text` `body_text` varchar(135) NOT NULL default '';
ALTER TABLE w3.socialboost_ads CHANGE  `link` `link` tinytext NOT NULL default '';
ALTER TABLE w3.socialboost_ads CHANGE  `min_age` `min_age` int(2) NOT NULL default 0;
ALTER TABLE w3.socialboost_ads CHANGE  `max_age` `max_age` int(2) NOT NULL default 0;
ALTER TABLE w3.socialboost_ads CHANGE  `sex`  `sex` CHAR( 8 ) NOT NULL default '';
ALTER TABLE w3.socialboost_ads CHANGE  `keywords` `keywords` varchar(512) NOT NULL default '';

update w3.socialboost_ads set sex = 'All' where sex = '';
update w3.socialboost_ads set sex = 'Men' where sex = 'male';
update w3.socialboost_ads set sex = 'Women' where sex = 'female';


27.03.2012
alter table eppctwo.sb_ad_location add `zip` varchar(500) not null default '' after `state`;

22.03.2012
create table eppctwo.ww_account (
`id` bigint unsigned not null auto_increment,
`client_id` varchar(32) not null default '',
`url` varchar(256) not null default '',
`oid` varchar(32) not null default '',
`status` enum('','New','Incomplete','Active','Cancelled','Declined','OnHold','NonRenewing','BillingFailure') not null default '',
`plan` char(16) not null default '',
`pay_option` enum('','standard','3_0','6_1','12_3') not null default '',
`sales_rep` int unsigned not null,
`account_rep` int unsigned not null default 0,
`partner` varchar(32) not null default '',
`source` varchar(64) not null default '',
`trial_length` tinyint unsigned not null default 0,
`trial_amount` double unsigned not null default 0,
`trial_auth_amount` double unsigned not null default 0,
`trial_auth_id` varchar(64) not null default '',
`signup_date` date not null default '0000-00-00',
`cancel_date` date not null default '0000-00-00',
`de_activation_date` date not null default '0000-00-00',
`created` datetime not null default '0000-00-00 00:00:00',
`bill_day` tinyint unsigned not null default 0,
`first_bill_date` date not null default '0000-00-00',
`next_bill_date` date not null default '0000-00-00',
`last_bill_date` date not null default '0000-00-00',
`cc_id` bigint unsigned not null default 0,
`is_billing_failure` bool not null default 0,
`alt_recur_amount` double unsigned not null default 0,
`landing_page` char(16) not null default '0',
`landing_page_date` date not null default '0000-00-00',
`contract_date` date not null default '0000-00-00',
`extra_pages` smallint unsigned not null default 0,
`contract_length` int(2) unsigned not null default 0,
primary key (`id`)
) engine=MyISAM;

create table eppctwo.ww_new_order (
`account_id` int unsigned not null,
`dt` datetime not null default '0000-00-00 00:00:00',
`ip` varchar(40) not null default '',
`browser` varchar(200) not null default '',
`referer` varchar(200) not null default '',
`plan` varchar(32) not null default '',
`name` varchar(128) not null default '',
`email` varchar(128) not null default '',
`phone` varchar(32) not null default '',
`url` varchar(128) not null default '',
`comments` varchar(256) not null default '',
`pay_option` enum('standard','3_0','6_1','12_3') null,
`partner` varchar(32) not null default '',
`cc_type` varchar(16) not null default '',
`cc_name` varchar(16) not null default '',
`cc_first_four` char(4) not null default '',
`cc_last_four` char(4) not null default '',
`cc_exp_month` varchar(16) not null default '',
`cc_exp_year` varchar(16) not null default '',
`cc_country` varchar(16) not null default '',
`cc_zip` varchar(16) not null default '',
`setup_fee` int not null default 0,
`coupon_code` varchar(16) not null default '',
`landing_page` char(16) not null default '0',
`landing_page_date` date not null default '0000-00-00',
`contract_date` date not null default '0000-00-00',
`extra_pages` smallint unsigned not null default 0,
`contract_length` int(2) unsigned not null default 0,
primary key (`account_id`)
) engine=MyISAM;

alter table eppctwo.sbs_billing_failure change `department` `department` enum('ql','sb','gs','ww') null;
alter table eppctwo.sbs_client_update change `department` `department` enum('ql','sb','gs','ww') null;
alter table eppctwo.sbs_coupons change `department` `department` enum('ql','sb','gs','ww') null;
alter table eppctwo.sbs_payment change `department` `department` enum('ql','sb','gs','ww') null;

23.03.2012
ALTER TABLE  `sb_ads` CHANGE  `bid_type`  `bid_type` CHAR( 8 ) not null default '';
ALTER TABLE  `sb_ads` CHANGE  `location_type`  `location_type` CHAR( 8 ) not null default '';;
ALTER TABLE  `sb_ads` CHANGE  `radius`  `radius` SMALLINT UNSIGNED NOT NULL DEFAULT  '0';
ALTER TABLE  `sb_ads` CHANGE  `sex`  `sex` CHAR( 8 ) NOT NULL default '';
ALTER TABLE  `sb_ads` CHANGE  `education_status`  `education_status` CHAR( 16 ) NOT NULL default '';
ALTER TABLE  `sb_ads` CHANGE  `interested_in`  `interested_in` CHAR( 8 ) NOT NULL default '';
ALTER TABLE  `sb_ads` CHANGE  `birthday`  `birthday` CHAR( 8 ) NOT NULL default '';
ALTER TABLE  `sb_ads` CHANGE  `status`  `status` CHAR( 16 ) NOT NULL default '';

17.02.2012
alter table eppctwo.email_template change `body` `plain` text not null default '';
alter table eppctwo.email_template add `html` text not null default '' after `plain`;
alter table eppctwo.sbs_payment change `type` `type` enum('Order','Recurring','Upgrade','Buyout','Optimization','Reseller','Refund New','Refund Old','Other') null;

11.02.2012
CREATE TABLE eppctwo.g_company_report_log (
`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
`d` date DEFAULT NULL,
`account_id` char(16) NOT NULL,
`details` text NOT NULL,
PRIMARY KEY (`id`)
) ENGINE=MyISAM ;

03.02.2012
ALTER TABLE `g_accounts` ADD  `parent_id` CHAR( 16 ) NOT NULL DEFAULT  '' AFTER  `id`;

create database standardized_reports;

create table standardized_reports.report (
`id` int unsigned not null auto_increment,
`account_id` char(16) not null default '',
`created` datetime not null default '0000-00-00 00:00:00',
primary key (`id`)
) engine=MyISAM;

27.01.2012
create table eppctwo.f_accounts (
`id` bigint(20) unsigned not null,
`text` varchar(64) not null default '',
`status` enum('On','Off') not null default 'On',
`ca_info_mod_time` datetime not null default '0000-00-00 00:00:00',
`currency` varchar(4) not null default 'USD',
primary key (`id`)
) engine=MyISAM;

alter table eppctwo.clients_ppc add `facebook` bool not null default 0 after `revenue_tracking`;

20.01.2012
create table eppctwo.sbs_account_rep (
`users_id` int(11) unsigned not null,
`name` char(64) not null default '',
`email` char(64) not null default '',
`phone` char(64) not null default '',
primary key (`users_id`)
) engine=myisam;

alter table eppctwo.gs_urls add `account_rep` int unsigned not null default 0 after `sales_rep`;
alter table eppctwo.ql_url add `account_rep` int unsigned not null default 0 after `sales_rep`;
alter table eppctwo.sb_groups add `account_rep` int unsigned not null default 0 after `sales_rep`;

03.01.2012
create table `email_template` (
`tkey` char(32) not null default '',
`from` varchar(256) not null default '',
`to` varchar(256) not null default '',
`cc` varchar(256) not null default '',
`bcc` varchar(256) not null default '',
`subject` varchar(256) not null default '',
`body` text not null,
primary key (`tkey`)
) engine=myisam;

create table `email_template_mapping` (
`id` bigint(20) unsigned not null auto_increment,
`tkey` char(32) not null default '',
`department` enum('ql','sb','gs') default null,
`action` char(16) not null default '',
`plan` char(16) not null default '',
primary key (`id`),
unique key `department` (`department`,`action`,`plan`),
key `tkey` (`tkey`)
) engine=myisam;

22.12.2011
-- e2
ALTER TABLE  `coupons` ADD  `contract_length` INT( 2 ) NOT NULL DEFAULT '0' AFTER  `value_type`;
ALTER TABLE  `ql_new_order` ADD  `contract_length` INT( 2 ) NOT NULL DEFAULT  '0';
ALTER TABLE  `ql_url` ADD  `contract_length` INT( 2 ) NOT NULL DEFAULT  '0';
ALTER TABLE  `sb_new_order` ADD  `contract_length` INT( 2 ) NOT NULL DEFAULT  '0';
ALTER TABLE  `sb_groups` ADD  `contract_length` INT( 2 ) NOT NULL DEFAULT  '0';
ALTER TABLE  `gs_new_order` ADD  `contract_length` INT( 2 ) NOT NULL DEFAULT  '0';
ALTER TABLE  `gs_urls` ADD  `contract_length` INT( 2 ) NOT NULL DEFAULT  '0';

-- wpro
ALTER TABLE  `coupons` ADD  `contract_length` INT( 2 ) NOT NULL DEFAULT  '0' AFTER  `value_type`;
ALTER TABLE  `goseo_urls` ADD  `contract_length` INT( 2 ) NOT NULL DEFAULT  '0';
ALTER TABLE  `goseo_orders` ADD  `contract_length` INT( 2 ) NOT NULL DEFAULT  '0';
ALTER TABLE  `quicklist_urls` ADD  `contract_length` INT( 2 ) NOT NULL DEFAULT  '0';
ALTER TABLE  `quicklist_orders` ADD  `contract_length` INT( 2 ) NOT NULL DEFAULT  '0';
ALTER TABLE  `socialboost_groups` ADD  `contract_length` INT( 2 ) NOT NULL DEFAULT  '0';
ALTER TABLE  `socialboost_orders` ADD  `contract_length` INT( 2 ) NOT NULL DEFAULT  '0';

21.12.2011
alter table eppctwo.ql_new_order change `comments` `comments` varchar(256) not null default '';
alter table eppctwo.sb_new_order change `comments` `comments` varchar(256) not null default '';
alter table eppctwo.gs_new_order change `comments` `comments` varchar(256) not null default '';

20.12.2011
create table eppctwo.avv_lead (
`customerlastname` varchar(128) not null default '0',
`customerfirstname` varchar(128) not null default '0',
`address` varchar(128) not null default '0',
`city` varchar(128) not null default '0',
`customerfullname` varchar(256) not null default '0',
`customerid` bigint unsigned not null,
`customermiddlename` varchar(128) not null default '0',
`dayphone` varchar(64) not null default '0',
`dealerid` bigint unsigned not null,
`emailaddress` varchar(128) not null default '0',
`eveningphone` varchar(128) not null default '0',
`groupdir` varchar(32) not null default '0',
`lastaction` varchar(32) not null default '0',
`make` varchar(64) not null default '0',
`model` varchar(64) not null default '0',
`postalcode` varchar(16) not null default '0',
`preferredcontact` varchar(64) not null default '0',
`sales_cycle` varchar(64) not null default '0',
`salesid` bigint unsigned not null,
`contactfirstname` varchar(64) not null default '0',
`contactlastname` varchar(64) not null default '0',
`soldidentifier` varchar(64) not null default '0',
`soldtype` varchar(64) not null default '0',
`soldtimestamp` datetime not null default '0000-00-00 00:00:00',
`sourcedescription` varchar(128) not null default '0',
`stateorprovince` varchar(64) not null default '0',
`stockno` varchar(256) not null default '0',
`tstamp` datetime not null default '0000-00-00 00:00:00',
`vehicleother` varchar(64) not null default '0',
`year` smallint unsigned not null,
`optoutdate` date not null default '0000-00-00',
primary key (`customerid`)
) engine=MyISAM;
			
20.12.2011
create table eppctwo.email_log_entry (
`id` bigint unsigned not null auto_increment,
`department` enum('ql','sb','gs') null,
`account_id` bigint unsigned not null default 0,
`created` datetime not null default '0000-00-00 00:00:00',
`sent_success` bool not null default 0,
`sent_details` varchar(128) not null default '0',
`type` varchar(32) not null default '',
`from` varchar(128) not null default '',
`to` varchar(128) not null default '',
`subject` varchar(128) not null default '',
`headers` varchar(512) not null default '',
`body` text not null default '',
primary key (`id`),
index (`department`,`account_id`),
index (`created`)
) engine=MyISAM;

15.12.2011
alter table eppctwo.sb_groups add `is_7_day_done` bool not null default 0 after trial_auth_id;

alter table eppctwo.gs_new_order add `browser` varchar(200) not null default '' after ip;
alter table eppctwo.gs_new_order add `referer` varchar(200) not null default '' after browser;

create table eppctwo.sb_new_order (
`account_id` int unsigned not null,
`dt` datetime not null default '0000-00-00 00:00:00',
`ip` varchar(40) not null default '',
`browser` varchar(200) not null default '',
`referer` varchar(200) not null default '',
`discount` varchar(32) not null default '',
`plan` varchar(32) not null default '',
`trial_length` tinyint unsigned not null default 0,
`trial_amount` double unsigned not null default 0,
`name` varchar(128) not null default '',
`email` varchar(128) not null default '',
`phone` varchar(32) not null default '',
`url` varchar(128) not null default '',
`comments` varchar(128) not null default '',
`is_likepage` bool not null default 0,
`pay_option` enum('standard','3_0','6_1','12_3') null,
`partner` varchar(32) not null default '',
`cc_type` varchar(16) not null default '',
`cc_name` varchar(16) not null default '',
`cc_first_four` char(4) not null default '',
`cc_last_four` char(4) not null default '',
`cc_exp_month` varchar(16) not null default '',
`cc_exp_year` varchar(16) not null default '',
`cc_country` varchar(16) not null default '',
`cc_zip` varchar(16) not null default '',
`setup_fee` int not null default 0,
`coupon_code` varchar(16) not null default '',
primary key (`account_id`)
) engine=MyISAM;

13.12.2011
alter table eppctwo.gs_urls add `de_activation_date` date not null default '0000-00-00' after cancel_date;
alter table eppctwo.sb_groups add `de_activation_date` date not null default '0000-00-00' after cancel_date;

05.12.2011
alter table eppctwo.ql_url change `clients_id` `client_id` varchar(32) not null default '';

alter table eppctwo.sb_groups change `plan` `plan` enum('','Starter','Core','Premier','silver','gold','platinum') not null default '';
alter table eppctwo.sb_groups change `pay_option` `pay_option` enum('','standard','3_0','6_1','12_3') not null default '';
alter table eppctwo.sb_groups add `is_billing_failure` bool not null default 0 after coupon_code;

08.11.2011
alter table eppctwo.clients_tv change `quicklist_premier_fee` `televox_quicklist_fee` double not null default 0;
alter table eppctwo.clients_tv change `socialboost_premier_fee` `televox_socialboost_fee` double not null default 0;
alter table eppctwo.clients_tv change `goseo_premier_fee` `televox_goseo_fee` double not null default 0;

07.11.2011
/*
 * sbs partner/sales rep/source stuff
 */
-- e2
alter table eppctwo.ql_url add `sales_rep` int(11) unsigned not null after pay_option;
alter table eppctwo.ql_url add `source` varchar(64) not null default '' after partner;

alter table eppctwo.sb_groups add `sales_rep` int(11) unsigned not null after rdt;
alter table eppctwo.sb_groups add `source` varchar(64) not null default '' after partner;

alter table eppctwo.gs_urls add `sales_rep` int(11) unsigned not null after pay_option;
alter table eppctwo.gs_urls add `source` varchar(64) not null default '' after partner;

-- w3
alter table quicklist_orders add `sales_rep` int(11) unsigned not null after pay_option;
alter table quicklist_orders add `source` varchar(64) not null default '' after partner;

alter table socialboost_orders add `sales_rep` int(11) unsigned not null after pay_option;
alter table socialboost_orders add `partner` varchar(64) not null default '' after sales_rep;
alter table socialboost_orders add `source` varchar(64) not null default '' after partner;

alter table goseo_orders add `sales_rep` int(11) unsigned not null after cc_zip;
alter table goseo_orders add `source` varchar(64) not null default '' after partner;


-- e2
create table eppctwo.sbr_partner (
`id` varchar(64) not null default '',
`status` enum('On','Off') not null default 'On',
primary key (`id`)
) engine=MyISAM;

create table eppctwo.sbr_source (
`sbr_partner_id` varchar(64) not null default '',
`id` varchar(64) not null default '',
`status` enum('On','Off') not null default 'On',
primary key (`sbr_partner_id`, `id`)
) engine=MyISAM;

create table eppctwo.sbr_active_rep (
`users_id` int(11) unsigned not null,
primary key (`users_id`)
) engine=MyISAM;

-- w3
create table sbr_partners (
`id` varchar(64) not null default '',
`status` enum('On','Off') not null default 'On',
primary key (`id`)
) engine=MyISAM;

create table sbr_sources (
`sbr_partner_id` varchar(64) not null default '',
`id` varchar(64) not null default '',
`status` enum('On','Off') not null default 'On',
primary key (`id`)
) engine=MyISAM;

create table sbr_active_reps (
`users_id` int(11) unsigned not null,
primary key (`users_id`)
) engine=MyISAM;

01.10.2011 error
create table eppctwo.f_ad (
`id` int unsigned not null auto_increment,
`ad_group_id` int unsigned not null,
`title` varchar(128) not null default '',
`desc_1` varchar(255) not null default '',
`dest_url` varchar(512) not null default '',
primary key (`id`,`ad_group_id`),
unique (`ad_group_id`,`title`,`desc_1`,`dest_url`)
) engine=MyISAM;


create table eppctwo.f_ad_group (
`id` int unsigned not null auto_increment,
`campaign_id` int unsigned not null,
`text` varchar(128) not null default '',
primary key (`id`),
unique (`campaign_id`,`text`)
) engine=MyISAM;


create table eppctwo.f_campaign (
`id` int unsigned not null auto_increment,
`account_id` varchar(32) not null default '',
`text` varchar(128) not null default '',
primary key (`id`),
unique (`account_id`,`text`)
) engine=MyISAM;

