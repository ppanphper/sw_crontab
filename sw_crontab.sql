# ************************************************************
# Sequel Pro SQL dump
# Version 4541
#
# http://www.sequelpro.com/
# https://github.com/sequelpro/sequelpro
#
# Host: 121.199.43.216 (MySQL 5.6.34)
# Database: sw_crontab
# Generation Time: 2018-06-27 05:34:05 +0000
# ************************************************************


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


# Dump of table agents
# ------------------------------------------------------------

DROP TABLE IF EXISTS `agents`;

CREATE TABLE `agents` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(30) NOT NULL DEFAULT '' COMMENT '名称',
  `ip` varchar(50) NOT NULL DEFAULT '',
  `port` smallint(5) unsigned NOT NULL,
  `status` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '状态 0=停用 1=启用',
  PRIMARY KEY (`id`),
  UNIQUE KEY `IDX_UNIQ_IP_PORT` (`ip`,`port`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table agents_category
# ------------------------------------------------------------

DROP TABLE IF EXISTS `agents_category`;

CREATE TABLE `agents_category` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `cid` int(11) unsigned NOT NULL,
  `aid` int(11) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_CID` (`cid`),
  KEY `IDX_AID` (`aid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table alarm_user_group
# ------------------------------------------------------------

DROP TABLE IF EXISTS `alarm_user_group`;

CREATE TABLE `alarm_user_group` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL COMMENT '用户组名称',
  `timeline` int(11) unsigned NOT NULL COMMENT '最后更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `Unique` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table alarm_user_group_account
# ------------------------------------------------------------

DROP TABLE IF EXISTS `alarm_user_group_account`;

CREATE TABLE `alarm_user_group_account` (
  `group_id` int(11) unsigned NOT NULL,
  `account` varchar(50) NOT NULL,
  KEY `Index` (`account`),
  KEY `group_id` (`group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table auth_assignment
# ------------------------------------------------------------

DROP TABLE IF EXISTS `auth_assignment`;

CREATE TABLE `auth_assignment` (
  `item_name` varchar(64) NOT NULL,
  `user_id` varchar(64) NOT NULL,
  `created_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`item_name`,`user_id`),
  KEY `auth_assignment_user_id_idx` (`user_id`),
  CONSTRAINT `auth_assignment_ibfk_1` FOREIGN KEY (`item_name`) REFERENCES `auth_item` (`name`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='存放授权条目对用户的指派情况';

LOCK TABLES `auth_assignment` WRITE;
/*!40000 ALTER TABLE `auth_assignment` DISABLE KEYS */;

INSERT INTO `auth_assignment` (`item_name`, `user_id`, `created_at`)
VALUES
	('管理员','1',1516099151);

/*!40000 ALTER TABLE `auth_assignment` ENABLE KEYS */;
UNLOCK TABLES;


# Dump of table auth_item
# ------------------------------------------------------------

DROP TABLE IF EXISTS `auth_item`;

CREATE TABLE `auth_item` (
  `name` varchar(64) NOT NULL,
  `type` smallint(6) NOT NULL,
  `description` text,
  `rule_name` varchar(64) DEFAULT NULL,
  `data` blob,
  `created_at` int(11) DEFAULT NULL,
  `updated_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`name`),
  KEY `rule_name` (`rule_name`),
  KEY `type` (`type`),
  CONSTRAINT `auth_item_ibfk_1` FOREIGN KEY (`rule_name`) REFERENCES `auth_rule` (`name`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='存放授权条目';

LOCK TABLES `auth_item` WRITE;
/*!40000 ALTER TABLE `auth_item` DISABLE KEYS */;

INSERT INTO `auth_item` (`name`, `type`, `description`, `rule_name`, `data`, `created_at`, `updated_at`)
VALUES
	('/*',2,NULL,NULL,NULL,1510026910,1510026910),
	('/admin/*',2,NULL,NULL,NULL,1510026910,1510026910),
	('/admin/assignment/*',2,NULL,NULL,NULL,1510026909,1510026909),
	('/admin/assignment/assign',2,NULL,NULL,NULL,1510026909,1510026909),
	('/admin/assignment/index',2,NULL,NULL,NULL,1510026909,1510026909),
	('/admin/assignment/revoke',2,NULL,NULL,NULL,1510026909,1510026909),
	('/admin/assignment/view',2,NULL,NULL,NULL,1510026909,1510026909),
	('/admin/default/*',2,NULL,NULL,NULL,1510026909,1510026909),
	('/admin/default/index',2,NULL,NULL,NULL,1510026909,1510026909),
	('/admin/menu/*',2,NULL,NULL,NULL,1510026909,1510026909),
	('/admin/menu/create',2,NULL,NULL,NULL,1510026909,1510026909),
	('/admin/menu/delete',2,NULL,NULL,NULL,1510026909,1510026909),
	('/admin/menu/index',2,NULL,NULL,NULL,1510026909,1510026909),
	('/admin/menu/update',2,NULL,NULL,NULL,1510026909,1510026909),
	('/admin/menu/view',2,NULL,NULL,NULL,1510026909,1510026909),
	('/admin/permission/*',2,NULL,NULL,NULL,1510026909,1510026909),
	('/admin/permission/assign',2,NULL,NULL,NULL,1510026909,1510026909),
	('/admin/permission/create',2,NULL,NULL,NULL,1510026909,1510026909),
	('/admin/permission/delete',2,NULL,NULL,NULL,1510026909,1510026909),
	('/admin/permission/index',2,NULL,NULL,NULL,1510026909,1510026909),
	('/admin/permission/remove',2,NULL,NULL,NULL,1510026909,1510026909),
	('/admin/permission/update',2,NULL,NULL,NULL,1510026909,1510026909),
	('/admin/permission/view',2,NULL,NULL,NULL,1510026909,1510026909),
	('/admin/role/*',2,NULL,NULL,NULL,1510026909,1510026909),
	('/admin/role/assign',2,NULL,NULL,NULL,1510026909,1510026909),
	('/admin/role/create',2,NULL,NULL,NULL,1510026909,1510026909),
	('/admin/role/delete',2,NULL,NULL,NULL,1510026909,1510026909),
	('/admin/role/index',2,NULL,NULL,NULL,1510026909,1510026909),
	('/admin/role/remove',2,NULL,NULL,NULL,1510026909,1510026909),
	('/admin/role/update',2,NULL,NULL,NULL,1510026909,1510026909),
	('/admin/role/view',2,NULL,NULL,NULL,1510026909,1510026909),
	('/admin/route/*',2,NULL,NULL,NULL,1510026909,1510026909),
	('/admin/route/assign',2,NULL,NULL,NULL,1510026909,1510026909),
	('/admin/route/create',2,NULL,NULL,NULL,1510026909,1510026909),
	('/admin/route/index',2,NULL,NULL,NULL,1510026909,1510026909),
	('/admin/route/refresh',2,NULL,NULL,NULL,1510026909,1510026909),
	('/admin/route/remove',2,NULL,NULL,NULL,1510026909,1510026909),
	('/admin/rule/*',2,NULL,NULL,NULL,1510026910,1510026910),
	('/admin/rule/create',2,NULL,NULL,NULL,1510026909,1510026909),
	('/admin/rule/delete',2,NULL,NULL,NULL,1510026909,1510026909),
	('/admin/rule/index',2,NULL,NULL,NULL,1510026909,1510026909),
	('/admin/rule/update',2,NULL,NULL,NULL,1510026909,1510026909),
	('/admin/rule/view',2,NULL,NULL,NULL,1510026909,1510026909),
	('/admin/user/*',2,NULL,NULL,NULL,1510026910,1510026910),
	('/admin/user/activate',2,NULL,NULL,NULL,1510026910,1510026910),
	('/admin/user/change-password',2,NULL,NULL,NULL,1510026910,1510026910),
	('/admin/user/delete',2,NULL,NULL,NULL,1510026910,1510026910),
	('/admin/user/index',2,NULL,NULL,NULL,1510026910,1510026910),
	('/admin/user/login',2,NULL,NULL,NULL,1510026910,1510026910),
	('/admin/user/logout',2,NULL,NULL,NULL,1510026910,1510026910),
	('/admin/user/request-password-reset',2,NULL,NULL,NULL,1510026910,1510026910),
	('/admin/user/reset-password',2,NULL,NULL,NULL,1510026910,1510026910),
	('/admin/user/signup',2,NULL,NULL,NULL,1510026910,1510026910),
	('/admin/user/view',2,NULL,NULL,NULL,1510026910,1510026910),
	('/agent/*',2,NULL,NULL,NULL,1510108317,1510108317),
	('/agent/create',2,NULL,NULL,NULL,1515405143,1515405143),
	('/agent/delete',2,NULL,NULL,NULL,1515405143,1515405143),
	('/agent/index',2,NULL,NULL,NULL,1510026009,1510026009),
	('/agent/update',2,NULL,NULL,NULL,1515405143,1515405143),
	('/agent/view',2,NULL,NULL,NULL,1515405143,1515405143),
	('/category/*',2,NULL,NULL,NULL,1515405139,1515405139),
	('/category/create',2,NULL,NULL,NULL,1515405139,1515405139),
	('/category/delete',2,NULL,NULL,NULL,1515405139,1515405139),
	('/category/index',2,NULL,NULL,NULL,1515405139,1515405139),
	('/category/update',2,NULL,NULL,NULL,1515405139,1515405139),
	('/category/view',2,NULL,NULL,NULL,1515405139,1515405139),
	('/crontab/*',2,NULL,NULL,NULL,1510024852,1510024852),
	('/crontab/change-status',2,NULL,NULL,NULL,1526283645,1526283645),
	('/crontab/create',2,NULL,NULL,NULL,1510024811,1510024811),
	('/crontab/delete',2,NULL,NULL,NULL,1510024828,1510024828),
	('/crontab/index',2,NULL,NULL,NULL,1510024850,1510024850),
	('/crontab/update',2,NULL,NULL,NULL,1510024822,1510024822),
	('/crontab/view',2,NULL,NULL,NULL,1510024840,1510024840),
	('/debug/*',2,NULL,NULL,NULL,1510026910,1510026910),
	('/debug/default/*',2,NULL,NULL,NULL,1510026910,1510026910),
	('/debug/default/db-explain',2,NULL,NULL,NULL,1510026910,1510026910),
	('/debug/default/download-mail',2,NULL,NULL,NULL,1510026910,1510026910),
	('/debug/default/index',2,NULL,NULL,NULL,1510026910,1510026910),
	('/debug/default/toolbar',2,NULL,NULL,NULL,1510026910,1510026910),
	('/debug/default/view',2,NULL,NULL,NULL,1510026910,1510026910),
	('/debug/user/*',2,NULL,NULL,NULL,1510026910,1510026910),
	('/debug/user/reset-identity',2,NULL,NULL,NULL,1510026910,1510026910),
	('/debug/user/set-identity',2,NULL,NULL,NULL,1510026910,1510026910),
	('/gii/*',2,NULL,NULL,NULL,1510026910,1510026910),
	('/gii/default/*',2,NULL,NULL,NULL,1510026910,1510026910),
	('/gii/default/action',2,NULL,NULL,NULL,1510026910,1510026910),
	('/gii/default/diff',2,NULL,NULL,NULL,1510026910,1510026910),
	('/gii/default/index',2,NULL,NULL,NULL,1510026910,1510026910),
	('/gii/default/preview',2,NULL,NULL,NULL,1510026910,1510026910),
	('/gii/default/view',2,NULL,NULL,NULL,1510026910,1510026910),
	('/index/*',2,NULL,NULL,NULL,1510026910,1510026910),
	('/index/index',2,NULL,NULL,NULL,1510026910,1510026910),
	('/login/*',2,NULL,NULL,NULL,1510026910,1510026910),
	('/login/login',2,NULL,NULL,NULL,1510026910,1510026910),
	('/login/logout',2,NULL,NULL,NULL,1510026910,1510026910),
	('/logs/*',2,NULL,NULL,NULL,1525334212,1525334212),
	('/logs/create',2,NULL,NULL,NULL,1525334212,1525334212),
	('/logs/delete',2,NULL,NULL,NULL,1525334212,1525334212),
	('/logs/index',2,NULL,NULL,NULL,1525334212,1525334212),
	('/logs/update',2,NULL,NULL,NULL,1525334212,1525334212),
	('/logs/view',2,NULL,NULL,NULL,1525334212,1525334212),
	('/site/*',2,NULL,NULL,NULL,1510026910,1510026910),
	('/site/about',2,NULL,NULL,NULL,1510026910,1510026910),
	('/site/captcha',2,NULL,NULL,NULL,1510026910,1510026910),
	('/site/contact',2,NULL,NULL,NULL,1510026910,1510026910),
	('/site/error',2,NULL,NULL,NULL,1510026910,1510026910),
	('/site/index',2,NULL,NULL,NULL,1510026910,1510026910),
	('/site/login',2,NULL,NULL,NULL,1510026910,1510026910),
	('/site/logout',2,NULL,NULL,NULL,1510026910,1510026910),
	('/site/password',2,NULL,NULL,NULL,1526462192,1526462192),
	('/user/*',2,NULL,NULL,NULL,1516094579,1516094579),
	('/user/create',2,NULL,NULL,NULL,1516094607,1516094607),
	('/user/delete',2,NULL,NULL,NULL,1516099242,1516099242),
	('/user/index',2,NULL,NULL,NULL,1516094587,1516094587),
	('/user/password',2,NULL,NULL,NULL,1526458527,1526458527),
	('/user/update',2,NULL,NULL,NULL,1516094601,1516094601),
	('/user/view',2,NULL,NULL,NULL,1516094593,1516094593),
	('Agent管理',2,'Agent管理',NULL,NULL,1510110372,1516180753),
	('Crontab管理',2,'Crontab管理',NULL,NULL,1510110330,1526284325),
	('分类管理',2,'分类管理',NULL,NULL,1515405242,1516180770),
	('定时任务管理',1,'定时任务管理',NULL,NULL,1526462223,1526462223),
	('用户管理',2,'用户管理',NULL,NULL,1516094005,1516180778),
	('管理员',1,'管理员',NULL,NULL,1510027163,1526283738),
	('系统配置',2,'系统配置',NULL,NULL,1510110820,1516180786);

/*!40000 ALTER TABLE `auth_item` ENABLE KEYS */;
UNLOCK TABLES;


# Dump of table auth_item_child
# ------------------------------------------------------------

DROP TABLE IF EXISTS `auth_item_child`;

CREATE TABLE `auth_item_child` (
  `parent` varchar(64) NOT NULL,
  `child` varchar(64) NOT NULL,
  PRIMARY KEY (`parent`,`child`),
  KEY `child` (`child`),
  CONSTRAINT `auth_item_child_ibfk_1` FOREIGN KEY (`parent`) REFERENCES `auth_item` (`name`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `auth_item_child_ibfk_2` FOREIGN KEY (`child`) REFERENCES `auth_item` (`name`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='存放授权条目的层次关系';

LOCK TABLES `auth_item_child` WRITE;
/*!40000 ALTER TABLE `auth_item_child` DISABLE KEYS */;

INSERT INTO `auth_item_child` (`parent`, `child`)
VALUES
	('管理员','/*'),
	('系统配置','/admin/*'),
	('Agent管理','/agent/*'),
	('分类管理','/category/*'),
	('分类管理','/category/create'),
	('分类管理','/category/delete'),
	('分类管理','/category/index'),
	('分类管理','/category/update'),
	('分类管理','/category/view'),
	('Crontab管理','/crontab/*'),
	('用户管理','/user/*'),
	('用户管理','/user/create'),
	('用户管理','/user/delete'),
	('用户管理','/user/index'),
	('用户管理','/user/update'),
	('用户管理','/user/view'),
	('定时任务管理','Crontab管理');

/*!40000 ALTER TABLE `auth_item_child` ENABLE KEYS */;
UNLOCK TABLES;


# Dump of table auth_rule
# ------------------------------------------------------------

DROP TABLE IF EXISTS `auth_rule`;

CREATE TABLE `auth_rule` (
  `name` varchar(64) NOT NULL,
  `data` blob,
  `created_at` int(11) DEFAULT NULL,
  `updated_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='存放规则';

LOCK TABLES `auth_rule` WRITE;
/*!40000 ALTER TABLE `auth_rule` DISABLE KEYS */;

INSERT INTO `auth_rule` (`name`, `data`, `created_at`, `updated_at`)
VALUES
	('访问',X'4F3A32353A226170705C636F6D706F6E656E74735C41636365737352756C65223A333A7B733A343A226E616D65223B733A363A22E8AEBFE997AE223B733A393A22637265617465644174223B693A313531363137353537323B733A393A22757064617465644174223B693A313531363137353537323B7D',1516175572,1516175572);

/*!40000 ALTER TABLE `auth_rule` ENABLE KEYS */;
UNLOCK TABLES;


# Dump of table category
# ------------------------------------------------------------

DROP TABLE IF EXISTS `category`;

CREATE TABLE `category` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(30) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table crontab
# ------------------------------------------------------------

DROP TABLE IF EXISTS `crontab`;

CREATE TABLE `crontab` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `cid` int(10) unsigned NOT NULL,
  `name` varchar(64) NOT NULL,
  `rule` varchar(600) NOT NULL DEFAULT '* * * * * *' COMMENT '规则 可以是crontab规则也可以是启动的间隔时间',
  `concurrency` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '并发任务数 0不限制  其他表示限制的数量',
  `command` varchar(512) NOT NULL COMMENT '命令',
  `max_process_time` int(11) unsigned NOT NULL DEFAULT '600' COMMENT '最大执行时间',
  `status` tinyint(2) NOT NULL DEFAULT '0' COMMENT '-1删除 0停止 1启用',
  `run_user` varchar(255) NOT NULL DEFAULT '' COMMENT '进程运行时用户',
  `owner` varchar(255) DEFAULT NULL COMMENT '负责人',
  `agents` varchar(255) DEFAULT NULL,
  `notice_way` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '通知方式 1邮件 2短信 3邮件+短信 4微信 5邮件+微信 6短信+微信 7所有方式',
  `create_time` int(11) unsigned NOT NULL,
  `update_time` int(11) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_NAME` (`name`),
  KEY `IDX_CID` (`cid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table logs
# ------------------------------------------------------------

DROP TABLE IF EXISTS `logs`;

CREATE TABLE `logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `task_id` int(11) unsigned NOT NULL,
  `run_id` bigint(20) unsigned NOT NULL,
  `code` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT '执行的状态码 0=成功 99=解析命令失败 101=变更用户失败',
  `title` varchar(64) NOT NULL DEFAULT '',
  `msg` longtext,
  `consume_time` decimal(16,6) unsigned NOT NULL DEFAULT '0.000000' COMMENT '耗时',
  `created` int(11) unsigned NOT NULL,
  `status` tinyint(1) unsigned NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `IDX_TASK_RUN_ID` (`task_id`,`run_id`),
  KEY `IDX_CREATED` (`created`),
  KEY `IDX_STATUS` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='任务执行日志表';



# Dump of table menu
# ------------------------------------------------------------

DROP TABLE IF EXISTS `menu`;

CREATE TABLE `menu` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(128) NOT NULL,
  `parent` int(11) DEFAULT NULL,
  `route` varchar(256) DEFAULT NULL,
  `order` int(11) unsigned DEFAULT '0',
  `data` text,
  PRIMARY KEY (`id`),
  KEY `parent` (`parent`),
  CONSTRAINT `menu_ibfk_1` FOREIGN KEY (`parent`) REFERENCES `menu` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

LOCK TABLES `menu` WRITE;
/*!40000 ALTER TABLE `menu` DISABLE KEYS */;

INSERT INTO `menu` (`id`, `name`, `parent`, `route`, `order`, `data`)
VALUES
	(1,'定时任务',NULL,'/crontab/index',0,'{\"icon\": \"tasks\"}'),
	(2,'代理节点',NULL,'/agent/index',0,'{\"icon\":\"server\"}'),
	(4,'路由',8,'/admin/route/index',0,NULL),
	(5,'权限',8,'/admin/permission/index',1,NULL),
	(6,'角色',8,'/admin/role/index',2,NULL),
	(7,'分配',8,'/admin/assignment/index',3,NULL),
	(8,'系统配置',NULL,NULL,1,'{\"icon\": \"cog\", \"visible\": true}'),
	(9,'菜单',8,'/admin/menu/index',4,NULL),
	(10,'分类管理',NULL,'/category/index',1,'{\"icon\":\"th-list\"}'),
	(11,'用户',8,'/user/index',5,'{\"icon\":\"users\"}'),
	(12,'规则',8,'/admin/rule/index',0,NULL),
	(13,'任务日志',NULL,'/logs/index',1,'{\"icon\":\"file-o\"}');

/*!40000 ALTER TABLE `menu` ENABLE KEYS */;
UNLOCK TABLES;


# Dump of table user
# ------------------------------------------------------------

DROP TABLE IF EXISTS `user`;

CREATE TABLE `user` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增ID',
  `username` varchar(50) NOT NULL COMMENT '用户名',
  `nickname` varchar(50) NOT NULL DEFAULT '' COMMENT '昵称',
  `password` varchar(100) NOT NULL DEFAULT '' COMMENT '加密密码',
  `auth_key` varchar(32) NOT NULL COMMENT '自动登录key',
  `accessToken` varchar(100) DEFAULT '' COMMENT '访问令牌',
  `mobile` varchar(20) DEFAULT NULL COMMENT '手机号',
  `email` varchar(255) NOT NULL COMMENT '邮箱',
  `status` tinyint(2) unsigned NOT NULL DEFAULT '1' COMMENT '状态 0=禁用 1=启用',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `IDX_UNIQUE` (`username`),
  UNIQUE KEY `IDX_NICKNAME` (`nickname`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='用户表';

LOCK TABLES `user` WRITE;
/*!40000 ALTER TABLE `user` DISABLE KEYS */;

INSERT INTO `user` (`id`, `username`, `nickname`, `password`, `auth_key`, `accessToken`, `mobile`, `email`, `status`, `create_time`, `update_time`)
VALUES
	(1,'admin','超级管理员','$2y$13$oGWYQA0/kHPEkx/vfsmNJOPmWi3ZnhrnNaqKfaV1rd/XLqnfwygoW','','','','',1,0,1526885003);

/*!40000 ALTER TABLE `user` ENABLE KEYS */;
UNLOCK TABLES;



/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
