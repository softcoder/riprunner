--
-- -- Table structures for rip runner for the MySQL engine
--
CREATE TABLE IF NOT EXISTS `config` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `firehall_id` varchar(80) COLLATE utf8_unicode_ci NOT NULL,
  `keyname` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `keyindex` int(11) NOT NULL DEFAULT 0,
  `keyvalue` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `updatetime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `callouts` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `calltime` datetime NOT NULL,
  `calltype` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `address` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `latitude` DECIMAL(10,6) NOT NULL,  
  `longitude` DECIMAL(10,6) NOT NULL,  
  `units` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `status` int(11) NOT NULL DEFAULT 0,
  `updatetime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `call_key` varchar(64) COLLATE utf8_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `callouts_response` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `calloutid` int(11) NOT NULL,
  `useracctid` int(11) NOT NULL,
  `responsetime` datetime NOT NULL,
  `latitude` DECIMAL(10,6) NOT NULL,  
  `longitude` DECIMAL(10,6) NOT NULL,
  `eta` int(11),
  `status` int(11) NOT NULL DEFAULT 0,
  `updatetime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `callouts_geo_tracking` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `calloutid` int(11) NOT NULL,
  `useracctid` int(11) NOT NULL,
  `trackingtime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `latitude` DECIMAL(10,6) NOT NULL,  
  `longitude` DECIMAL(10,6) NOT NULL,  
  `trackingstatus` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE  IF NOT EXISTS `user_accounts` (
`id` INT( 11 ) NOT NULL AUTO_INCREMENT PRIMARY KEY,
`firehall_id` varchar(80) COLLATE utf8_unicode_ci NOT NULL,
`user_id` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
`user_pwd` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
`mobile_phone` varchar(25) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
`access` INT( 11 ) NOT NULL DEFAULT 0,
`active` BOOLEAN NOT NULL DEFAULT 1,
`email`  varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
`updatetime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE = INNODB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `login_attempts` (
    `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `useracctid` INT(11) NOT NULL,
    `time` VARCHAR(30) NOT NULL
) ENGINE = INNODB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `devicereg` (
`id` INT( 11 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`registration_id` TEXT NOT NULL,
`firehall_id` varchar(80) COLLATE utf8_unicode_ci NOT NULL,
`user_id` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
`updatetime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE = INNODB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `trigger_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `triggertime` datetime NOT NULL,
  `type` int(11) NOT NULL DEFAULT 0,
  `firehall_id` varchar(80) COLLATE utf8_unicode_ci NOT NULL,
  `hash_data` TEXT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `callout_status` (
  `id` int(11) NOT NULL PRIMARY KEY,
  `name` varchar(80) COLLATE utf8_unicode_ci NOT NULL,
  `display_name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `status_flags` int(11) NOT NULL DEFAULT 0,
  `behaviour_flags` int(11) NOT NULL DEFAULT 0,
  `access_flags` int(11) NOT NULL DEFAULT 0,
  `access_flags_inclusive` BOOLEAN NOT NULL DEFAULT 0,
  `user_types_allowed` int(11) NOT NULL DEFAULT 0,
  `updatetime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `callout_type` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `code` varchar(12) COLLATE utf8_unicode_ci NOT NULL,
  `name` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8_unicode_ci,
  `custom_tag` varchar(255) COLLATE utf8_unicode_ci,
  `effective_date` datetime NULL,
  `expiration_date` datetime NULL,
  `updatetime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


CREATE TABLE IF NOT EXISTS `callouts_info` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `address` varchar(255) COLLATE utf8_unicode_ci NULL,
  `latitude` DECIMAL(10,6) NULL,  
  `longitude` DECIMAL(10,6) NULL,
  `comments` varchar(255) COLLATE utf8_unicode_ci NULL,
  `effective_date` datetime DEFAULT NULL,
  `expiration_date` datetime DEFAULT NULL,
  `updatetime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `login_audit` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `useracctid` INT(11) NULL,
  `username` varchar(255) COLLATE utf8_unicode_ci NULL,
  `status` INT(11) NOT NULL,
  `login_agent` varchar(255) COLLATE utf8_unicode_ci NULL,
  `login_ip` varchar(100) COLLATE utf8_unicode_ci NULL,
  `updatetime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE INDEX config_fhid_keyname_keyindex ON config (firehall_id, keyname, keyindex);

CREATE INDEX user_accounts_fhid_uid ON user_accounts (firehall_id,user_id);

CREATE INDEX callouts_id_callkey ON callouts (id,call_key);
CREATE INDEX callouts_id_status ON callouts (id,status);

CREATE INDEX callouts_response_useracctid ON callouts_response (useracctid);
CREATE INDEX callouts_response_calloutid ON callouts_response (calloutid);
CREATE INDEX callouts_response_status ON callouts_response (status);
