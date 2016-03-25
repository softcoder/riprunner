ALTER TABLE callouts_response ADD COLUMN eta int(11) AFTER longitude;

CREATE TABLE IF NOT EXISTS `config` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `firehall_id` varchar(80) COLLATE utf8_unicode_ci NOT NULL,
  `keyname` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `keyindex` int(11) NOT NULL DEFAULT 0,
  `keyvalue` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `updatetime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
