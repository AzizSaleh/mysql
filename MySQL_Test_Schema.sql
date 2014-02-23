DROP DATABASE IF EXISTS unit_sql_v_1;
DROP DATABASE IF EXISTS unit_sql_v_2;
CREATE DATABASE unit_sql_v_1;
CREATE TABLE `unit_sql_v_1`.`unit_sql_table_1` (
  `field_id` bigint(20) unsigned zerofill NOT NULL AUTO_INCREMENT,
  `field_name` varchar(200) DEFAULT NULL,
  `field_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`field_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1