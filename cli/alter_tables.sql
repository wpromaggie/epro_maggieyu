alter table eppctwo.users change type primary_guild varchar(32) not null default '';

CREATE TABLE eppctwo.user_guilds (
user_id int unsigned not null default 0,
guild_id varchar(32) not null default '',
role varchar(64) not null default '',
primary key (user_id, guild_id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE eppctwo.guild_roles (
guild_id varchar(32) not null default '',
role varchar(64) not null default '',
primary key (guild_id, role)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

update eppctwo.users set primary_guild = 'administrate' where primary_guild = 'Superadmin';
update eppctwo.users set primary_guild = 'ppc' where primary_guild = 'Admin';
update eppctwo.users set primary_guild = 'performance' where primary_guild = 'Affiliate Manager';
update eppctwo.users set primary_guild = 'ppc' where primary_guild = 'Client Manager';
update eppctwo.users set primary_guild = 'sales' where primary_guild = 'Sales';
update eppctwo.users set primary_guild = 'sb' where primary_guild = 'SocialBoost';
update eppctwo.users set primary_guild = 'ql' where primary_guild = 'QuickList';
update eppctwo.users set primary_guild = 'wd' where primary_guild = 'Web Development';

drop table eppctwo.user_type_defs;

CREATE TABLE  `eppctwo`.`sbs_interest_page` (
`id` INT( 10 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`user_id` INT( 10 ) NOT NULL ,
`ql_package` VARCHAR( 16 ) NOT NULL ,
`ql_setup_fee` DOUBLE NOT NULL ,
`sb_package` VARCHAR( 16 ) NOT NULL ,
`sb_setup_fee` DOUBLE NOT NULL ,
`sb_fanpage` TINYINT( 1 ) NOT NULL ,
`gs_package` VARCHAR( 16 ) NOT NULL ,
`gs_setup_fee` DOUBLE NOT NULL
) ENGINE = MYISAM ;

ALTER TABLE  `sbs_contacts` ADD  `interest_page_id` INT( 10 ) NOT NULL AFTER  `id`;

CREATE DATABASE `account_tasks`;

CREATE TABLE IF NOT EXISTS `account_tasks`.`client_nodes` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `layout_id` int(10) NOT NULL,
  `parent_id` int(10) NOT NULL,
  `child_order` int(5) NOT NULL,
  `struct` char(8) NOT NULL,
  `type` varchar(16) NOT NULL,
  `ac_id` int(10) NOT NULL,
  `text` text NOT NULL,
  `note` text,
  `status` varchar(16) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `account_tasks`.`default_nodes` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `layout_id` int(10) NOT NULL,
  `parent_id` int(10) NOT NULL,
  `child_order` int(5) NOT NULL,
  `type` varchar(16) NOT NULL,
  `struct` char(8) NOT NULL,
  `text` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `account_tasks`.`layouts` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `ac_type` varchar(16) NOT NULL,
  `user_id` int(10) NOT NULL,
  `title` varchar(128) NOT NULL,
  `dt` datetime NOT NULL,
  `status` varchar(16) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;
