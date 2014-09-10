<?php

class YCommentsCommand extends CConsoleCommand
{
	
	public static $sqlCreate = <<<SQL
	
DROP TABLE IF EXISTS `{comment}`;
CREATE TABLE `{comment}` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `message` varchar(2048) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
  `user_id` int(11) unsigned DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `votes_up` int(11) DEFAULT '0',
  `votes_dn` int(11) DEFAULT '0',
  `rating` int(11) DEFAULT NULL,
  `parent_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `{comment}_user_like`;
CREATE TABLE `{comment}_user_like` (
  `comment_id` int(10) unsigned NOT NULL,
  `user_id` int(10) NOT NULL,
  PRIMARY KEY (`comment_id`,`user_id`),
  KEY `fk_comment_user_like_comment_idx` (`comment_id`),
  KEY `fk_comment_user_like_user_idx` (`user_id`),
  CONSTRAINT `fk_comment_user_like_comment` FOREIGN KEY (`comment_id`) REFERENCES `{comment}` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_comment_user_like_user` FOREIGN KEY (`user_id`) REFERENCES `{user}` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `{entity}_{comment}`;
CREATE TABLE `{entity}_{comment}` (
  `{entity}_id` int(11) NOT NULL,
  `{comment}_id` int(11) unsigned NOT NULL,
  PRIMARY KEY (`{entity}_id`,`{comment}_id`),
  KEY `fk_{entity}_{comment}_{entity}_idx` (`{entity}_id`),
  KEY `fk_{entity}_{comment}_{comment}_idx` (`{comment}_id`),
  CONSTRAINT `fk_{entity}_{comment}_news` FOREIGN KEY (`{entity}_id`) REFERENCES `{entity}` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_{entity}_{comment}_{comment}` FOREIGN KEY (`{comment}_id`) REFERENCES `{comment}` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `notify_global`;
CREATE TABLE `notify_global` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `commentable_type` varchar(128) NOT NULL,
  `last_check_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`,`commentable_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `notify_user`;
CREATE TABLE `notify_user` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `commentable_type` varchar(128) NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `notify_all` tinyint(1) NOT NULL DEFAULT '0',
  `notify_reply` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`,`commentable_type`,`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `notify_subscription`;
CREATE TABLE `notify_subscription` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `commentable_type` varchar(128) NOT NULL,
  `item_id` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

SQL;
	
	/**
	 * @param string $entityTable  
	 * @param string $commentsTable
	 */
	public function actionSql($entityTable, $commentTable='comment', $userTable='users') {

		$params = array('{entity}' => $entityTable, '{comment}' => $commentTable, '{user}' => $userTable);
		echo strtr(self::$sqlCreate, $params).PHP_EOL;
		
	}
	
}