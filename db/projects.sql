SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

CREATE TABLE IF NOT EXISTS `projects` (
  `id` TINYINT UNSIGNED NOT NULL,
  `name` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `description` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

/*INSERT INTO `projects` (`id`,`name`,`description`) VALUES (0,"company","general management of the company");*/

ALTER TABLE `projects`
 ADD PRIMARY KEY (`id`);

ALTER TABLE `projects`
MODIFY `id` TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=1;
