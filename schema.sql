-- Create syntax for TABLE 'log'
CREATE TABLE `log` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(15) NOT NULL DEFAULT '',
  `flexi_units` int(11) DEFAULT NULL,
  `three_to_three_calls` int(11) DEFAULT NULL,
  `evening_weekend_minutes` int(11) DEFAULT NULL,
  `days_remaining` int(11) DEFAULT NULL,
  `current_spend` float DEFAULT NULL,
  `price_plan_flexi_units` int(11) DEFAULT NULL,
  `date_start` datetime DEFAULT NULL,
  `date_end` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `log_username` (`username`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- Create syntax for TABLE 'user_token'
CREATE TABLE `user_token` (
  `token` varchar(40) NOT NULL DEFAULT '' COMMENT 'Pseudorandom token',
  `username` varchar(100) NOT NULL DEFAULT '' COMMENT 'My3 username',
  `password` text NOT NULL COMMENT 'My3 password',
  `created` datetime NOT NULL,
  PRIMARY KEY (`token`),
  KEY `user_token_username` (`username`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
