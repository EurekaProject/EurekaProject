SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

CREATE TABLE IF NOT EXISTS `settings` (
  `key` VARCHAR(20) NOT NULL,
  `value` VARCHAR(75) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

ALTER TABLE `settings`
 ADD PRIMARY KEY (`key`);

INSERT INTO `settings` (`key`,`value`) VALUE 
  ('MaxDaysPerWeek','5'),
  ('MaxHoursPerDay','7'),
  ('HourResolution','4');
