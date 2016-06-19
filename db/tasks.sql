SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

CREATE TABLE IF NOT EXISTS `tasks` (
  `id` INT UNSIGNED NOT NULL,
  `name` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `description` BLOB DEFAULT NULL,
  `parentid` TINYINT UNSIGNED DEFAULT NULL,
  `userid` TINYINT UNSIGNED DEFAULT NULL,
  `projectid` TINYINT UNSIGNED DEFAULT NULL,
  `estimate` INT UNSIGNED DEFAULT NULL,
  `conditionid` TINYINT UNSIGNED DEFAULT NULL,
  `start` DATE DEFAULT NULL,
  `end` DATE DEFAULT NULL,
  `state` ENUM('open', 'close', 'milestone', 'flowing','vacation') DEFAULT 'open'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `tasks` (`id`, `name`, `description`, `parentid` ) VALUES
(0, 'root', 'main task at office', NULL);

ALTER TABLE `tasks`
 ADD PRIMARY KEY (`id`),
 ADD KEY `parentid` (`id`),
 ADD CONSTRAINT FK_tasks_projectid FOREIGN KEY (`projectid`) REFERENCES `projects` (`id`);

ALTER TABLE `tasks`
MODIFY `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=1;
