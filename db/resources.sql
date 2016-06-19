SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

CREATE TABLE IF NOT EXISTS `resources` (
  `userid` TINYINT UNSIGNED NOT NULL,
  `projectid` TINYINT UNSIGNED DEFAULT NULL,
  `roleid` TINYINT UNSIGNED DEFAULT 1,
  `name` VARCHAR(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `workhours` TINYINT UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `roles` (
  `id` TINYINT UNSIGNED NOT NULL,
  `name` VARCHAR(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `description` VARCHAR(100) COLLATE utf8_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

ALTER TABLE `resources`
 ADD PRIMARY KEY (`userid`,`projectid`);

INSERT INTO `roles` (`id`, `name`, `description`) VALUES
(1, 'developper', 'tasks user'),
(2, 'master', 'projects user'),
(3, 'owner', 'project owner'),
(4, 'leader', 'team leader');

ALTER TABLE `roles`
 ADD PRIMARY KEY (`id`);

ALTER TABLE `roles`
 MODIFY `id` TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=5;

ALTER TABLE `resources`
 ADD CONSTRAINT FK_resources_projectid FOREIGN KEY (`roleid`) REFERENCES `roles` (`id`);
