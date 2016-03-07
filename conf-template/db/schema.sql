/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `access_log`
--

DROP TABLE IF EXISTS `access_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `access_log` (
  `access_log_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `uri` varchar(500) NOT NULL,
  `post` varchar(3000) DEFAULT NULL,
  `controller` varchar(100) NOT NULL,
  `action` varchar(100) NOT NULL,
  `create_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `l_ip_address` bigint(20) DEFAULT NULL,
  `token` varchar(80) DEFAULT NULL,
  `header` varchar(3000) DEFAULT NULL,
  `detail` varchar(500) DEFAULT NULL,
  `old` varchar(3000) DEFAULT NULL,
  `new` varchar(3000) DEFAULT NULL,
  PRIMARY KEY (`access_log_id`)
) ENGINE=InnoDB AUTO_INCREMENT=11345 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `access_log_description`
--

DROP TABLE IF EXISTS `access_log_description`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `access_log_description` (
  `controller` varchar(100) NOT NULL,
  `action` varchar(100) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`controller`,`action`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `access_profile`
--

DROP TABLE IF EXISTS `access_profile`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `access_profile` (
  `access_profile_id` int(11) NOT NULL DEFAULT '0',
  `name` varchar(255) DEFAULT NULL,
  `create_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_update` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`access_profile_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `auth_mode`
--

DROP TABLE IF EXISTS `auth_mode`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `auth_mode` (
  `auth_mode_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(45) DEFAULT NULL,
  `system_key` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`auth_mode_id`),
  UNIQUE KEY `system_key_UNIQUE` (`system_key`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `group`
--

DROP TABLE IF EXISTS `group`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `group` (
  `group_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `auth_mode_id` int(11) NOT NULL,
  `access_profile_id` int(11) NOT NULL default '5',
  `create_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_update` timestamp NULL DEFAULT NULL,
  `visible` tinyint(1) NOT NULL DEFAULT '0',
  `observations` text,
  PRIMARY KEY (`group_id`),
  UNIQUE KEY `uk_group_auth_mode` (`group_id`,`auth_mode_id`),
  KEY `fk_group_auth_mode1_idx` (`auth_mode_id`),
  CONSTRAINT `fk_group_auth_mode1` FOREIGN KEY (`auth_mode_id`) REFERENCES `auth_mode` (`auth_mode_id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=8667 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `group_group`
--

--
-- Table structure for table `ic_profile`
--

DROP TABLE IF EXISTS `ic_profile`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ic_profile` (
  `ic_profile_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) DEFAULT NULL,
  `network` varchar(45) DEFAULT NULL,
  `mask` varchar(45) DEFAULT NULL,
  `max_upload` decimal(10,0) DEFAULT NULL,
  `max_download` decimal(10,0) DEFAULT NULL,
  `create_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_update` timestamp NULL DEFAULT NULL,
  `observations` text,
  PRIMARY KEY (`ic_profile_id`)
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `invite_template`
--

DROP TABLE IF EXISTS `invite_template`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `invite_template` (
  `invite_template_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `body` text,
  `user_id` int(11) DEFAULT NULL,
  `create_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_update` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`invite_template_id`),
  KEY `invite_template_user_FK` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `maintenance`
--

DROP TABLE IF EXISTS `maintenance`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `maintenance` (
  `maintenance_id` int(11) NOT NULL AUTO_INCREMENT,
  `description` varchar(700) DEFAULT NULL,
  `active` tinyint(1) DEFAULT '1',
  `create_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_update` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`maintenance_id`),
  KEY `active_idx` (`active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `meeting_room`
--

DROP TABLE IF EXISTS `meeting_room`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `meeting_room` (
  `meeting_room_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) DEFAULT NULL,
  `date_start` timestamp NULL DEFAULT NULL,
  `date_end` timestamp NULL DEFAULT NULL,
  `timezone` int(11) DEFAULT NULL,
  `encrypted` tinyint(1) DEFAULT NULL,
  `privacy_policy` tinyint(1) DEFAULT NULL,
  `url` varchar(255) DEFAULT NULL,
  `participants_limit` int(11) DEFAULT NULL,
  `record` tinyint(1) DEFAULT NULL,
  `last_invite_subject` varchar(255) DEFAULT NULL,
  `last_invite_body` text,
  `user_id` int(11) DEFAULT NULL,
  `create_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_update` timestamp NULL DEFAULT NULL,
  `meeting_mute_on_start` tinyint(1) NOT NULL,
  `meeting_lock_on_start` tinyint(1) NOT NULL,
  `lock_disable_mic_for_locked_users` tinyint(1) NOT NULL,
  `lock_disable_cam_for_locked_users` tinyint(1) NOT NULL,
  `lock_disable_public_chat_for_locked_users` tinyint(1) NOT NULL,
  `lock_disable_private_chat_for_locked_users` tinyint(1) NOT NULL,
  `lock_layout_for_locked_users` tinyint(1) NOT NULL DEFAULT 0,
  `observations` text,
  `meeting_room_category_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`meeting_room_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2677 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `meeting_room_action`
--

DROP TABLE IF EXISTS `meeting_room_action`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `meeting_room_action` (
  `meeting_room_action_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`meeting_room_action_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `meeting_room_category`
--

DROP TABLE IF EXISTS `meeting_room_category`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `meeting_room_category` (
  `meeting_room_category_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) DEFAULT NULL,
  `create_date` timestamp NULL DEFAULT NULL,
  `last_update` timestamp NULL DEFAULT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `hierarchy` varchar(150) DEFAULT NULL,
  PRIMARY KEY (`meeting_room_category_id`),
  KEY `fk_meetroomcat_parentid` (`parent_id`)
) ENGINE=InnoDB AUTO_INCREMENT=80 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `meeting_room_group`
--

DROP TABLE IF EXISTS `meeting_room_group`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `meeting_room_group` (
  `meeting_room_id` int(11) NOT NULL,
  `group_id` int(11) NOT NULL,
  `auth_mode_id` varchar(45) NOT NULL,
  `meeting_room_profile_id` int(11) NOT NULL,
  `create_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`meeting_room_id`,`group_id`,`auth_mode_id`,`meeting_room_profile_id`),
  KEY `fk_meeting_room_group_group1_idx` (`group_id`,`auth_mode_id`),
  KEY `fk_meeting_room_group_meeting_room1_idx` (`meeting_room_id`),
  KEY `fk_meeting_room_group_meeting_room_profile1_idx` (`meeting_room_profile_id`),
  CONSTRAINT `fk_meeting_room_group_group1` FOREIGN KEY (`group_id`) REFERENCES `group` (`group_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_meeting_room_group_meeting_room1` FOREIGN KEY (`meeting_room_id`) REFERENCES `meeting_room` (`meeting_room_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_meeting_room_group_meeting_room_profile1` FOREIGN KEY (`meeting_room_profile_id`) REFERENCES `meeting_room_profile` (`meeting_room_profile_id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `meeting_room_log`
--

DROP TABLE IF EXISTS `meeting_room_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `meeting_room_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `meeting_room_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `meeting_room_action_id` int(11) NOT NULL,
  `ip_address` varchar(50) DEFAULT NULL,
  `create_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_meeting_room_log_meeting_room1_idx` (`meeting_room_id`),
  KEY `fk_meeting_room_log_user1_idx` (`user_id`),
  KEY `fk_meeting_room_log_meeting_room_action1_idx` (`meeting_room_action_id`),
  CONSTRAINT `fk_meeting_room_log_meeting_room1` FOREIGN KEY (`meeting_room_id`) REFERENCES `meeting_room` (`meeting_room_id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `fk_meeting_room_log_meeting_room_action1` FOREIGN KEY (`meeting_room_action_id`) REFERENCES `meeting_room_action` (`meeting_room_action_id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `fk_meeting_room_log_user1` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=3622 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `meeting_room_profile`
--

DROP TABLE IF EXISTS `meeting_room_profile`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `meeting_room_profile` (
  `meeting_room_profile_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) DEFAULT NULL,
  `create_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_update` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`meeting_room_profile_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `meeting_room_user`
--

DROP TABLE IF EXISTS `meeting_room_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `meeting_room_user` (
  `meeting_room_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `meeting_room_profile_id` int(11) NOT NULL,
  `create_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `auth_mode_id` int(11) NOT NULL,
  PRIMARY KEY (`meeting_room_id`,`user_id`,`meeting_room_profile_id`),
  KEY `fk_meeting_room_user_user1_idx` (`user_id`),
  KEY `fk_meeting_room_user_meeting_room1_idx` (`meeting_room_id`),
  KEY `fk_meeting_room_user_meeting_room_profile1_idx` (`meeting_room_profile_id`),
  CONSTRAINT `fk_meeting_room_user_meeting_room1` FOREIGN KEY (`meeting_room_id`) REFERENCES `meeting_room` (`meeting_room_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_meeting_room_user_meeting_room_profile1` FOREIGN KEY (`meeting_room_profile_id`) REFERENCES `meeting_room_profile` (`meeting_room_profile_id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `fk_meeting_room_user_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `record`
--

DROP TABLE IF EXISTS `record`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `record` (
  `record_id` int(11) NOT NULL AUTO_INCREMENT,
  `meeting_room_id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `date_start` timestamp NULL DEFAULT NULL,
  `date_end` timestamp NULL DEFAULT NULL,
  `public` tinyint(1) DEFAULT NULL,
  `create_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_update` timestamp NULL DEFAULT NULL,
  `bbb_id` varchar(80) DEFAULT NULL,
  `playback_url` varchar(500) DEFAULT NULL,
  `sync_done` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`record_id`),
  KEY `fk_record_meeting_room1_idx` (`meeting_room_id`),
  CONSTRAINT `fk_record_meeting_room1` FOREIGN KEY (`meeting_room_id`) REFERENCES `meeting_room` (`meeting_room_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `record_group`
--

DROP TABLE IF EXISTS `record_group`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `record_group` (
  `record_id` int(11) NOT NULL,
  `group_id` int(11) NOT NULL,
  `auth_mode_id` varchar(45) NOT NULL,
  `create_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`record_id`,`group_id`,`auth_mode_id`),
  KEY `fk_record_group_group1_idx` (`group_id`,`auth_mode_id`),
  KEY `fk_record_group_record1_idx` (`record_id`),
  CONSTRAINT `fk_record_group_record1` FOREIGN KEY (`record_id`) REFERENCES `record` (`record_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_record_group_group1` FOREIGN KEY (`group_id`) REFERENCES `group` (`group_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `record_record_tag`
--

DROP TABLE IF EXISTS `record_record_tag`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `record_record_tag` (
  `record_tag_id` int(11) NOT NULL,
  `record_id` int(11) NOT NULL,
  `create_date` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`record_tag_id`,`record_id`),
  KEY ` record_record_tag_record_FK` (`record_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `record_tag`
--

DROP TABLE IF EXISTS `record_tag`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `record_tag` (
  `record_tag_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(150) DEFAULT NULL,
  `create_date` timestamp NULL DEFAULT NULL,
  `last_update` timestamp NULL DEFAULT NULL,
  `record_id` int(11) DEFAULT NULL,
  `description` text,
  `start_time` int(11) DEFAULT NULL,
  PRIMARY KEY (`record_tag_id`)
) ENGINE=InnoDB AUTO_INCREMENT=37 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `record_user`
--

DROP TABLE IF EXISTS `record_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `record_user` (
  `record_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `create_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`record_id`,`user_id`),
  KEY `fk_record_user_user1_idx` (`user_id`),
  KEY `fk_record_user_record1_idx` (`record_id`),
  CONSTRAINT `fk_record_user_record` FOREIGN KEY (`record_id`) REFERENCES `record` (`record_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_record_user_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `user`
--

DROP TABLE IF EXISTS `user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `login` varchar(50) DEFAULT NULL,
  `password` varchar(40) DEFAULT NULL,
  `auth_mode_id` int(11) NOT NULL,
  `access_profile_id` int(11) NOT NULL,
  `create_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_update` timestamp NULL DEFAULT NULL,
  `ldap_cn` varchar(255) DEFAULT NULL,
  `valid_from` date DEFAULT NULL,
  `valid_to` date DEFAULT NULL,
  `observations` text,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `idx_uk_login` (`login`),
  UNIQUE KEY `idx_user_email_uk` (`email`),
  KEY `fk_user_auth_mode1_idx` (`auth_mode_id`),
  KEY `fk_user_access_profile1` (`access_profile_id`),
  CONSTRAINT `fk_user_access_profile1` FOREIGN KEY (`access_profile_id`) REFERENCES `access_profile` (`access_profile_id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `fk_user_auth_mode1` FOREIGN KEY (`auth_mode_id`) REFERENCES `auth_mode` (`auth_mode_id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=8630 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `user_group`
--

DROP TABLE IF EXISTS `user_group`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_group` (
  `user_id` int(11) NOT NULL,
  `group_id` int(11) NOT NULL,
  `create_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`,`group_id`),
  KEY `fk_user_has_group_group1_idx` (`group_id`),
  KEY `fk_user_has_group_user_idx` (`user_id`),
  CONSTRAINT `fk_user_has_group_group1` FOREIGN KEY (`group_id`) REFERENCES `group` (`group_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_user_has_group_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

/*Version 2*/
create table proc_user_groups (
    user_id integer not null ,
    group_id integer not null ,
    primary key proc_user_groups (user_id, group_id),
    constraint user_fk foreign key (user_id) references `user` (user_id) on delete cascade on update cascade,
    constraint group_fk foreign key (group_id) references `group` (group_id) on delete cascade on update cascade
);

DROP TABLE IF EXISTS `group_group`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `group_group` (
  `group_id` int(11) NOT NULL,
  `parent_group_id` int(11) NOT NULL,
  `create_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`group_id`,`parent_group_id`),
  KEY `fk_group_group_group2_idx` (`parent_group_id`),
  KEY `fk_group_group_group1_idx` (`group_id`),
  CONSTRAINT `fl_parent_group` FOREIGN KEY (`parent_group_id`) REFERENCES `group` (`group_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_group_group_group1` FOREIGN KEY (`group_id`) REFERENCES `group` (`group_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

alter table `group` add column internal_name varchar(500);
alter table `user` modify access_profile_id int null;
alter table `group` add constraint group_fk_access_profile foreign key (access_profile_id) references access_profile(access_profile_id);
alter table `group` change access_profile_id access_profile_id  integer not null;
alter table `group` alter column access_profile_id set default 5 ;


create table proc_group_recursive
(
    group_id integer not null,
    ancestry_group_id integer not null,
    primary key (group_id, ancestry_group_id)
);

DELIMITER $$
CREATE PROCEDURE `update_security`()
    READS SQL DATA
BEGIN
    DECLARE rowCount INT;
  
    delete from proc_group_recursive;
    insert into proc_group_recursive (group_id, ancestry_group_id) select group_id, parent_group_id from group_group gg ;

    set rowCount = 1;

    while rowCount > 0 DO
        insert into proc_group_recursive (group_id, ancestry_group_id)
        select pgr.group_id, gg.parent_group_id from proc_group_recursive pgr
            inner join group_group gg  on gg.group_id = pgr.ancestry_group_id
        where not exists (
            select 1 from proc_group_recursive pgr2 where pgr2.group_id=pgr.group_id and pgr2.ancestry_group_id = gg.parent_group_id
        ) and pgr.group_id <> gg.parent_group_id;
        
        set rowCount =  ROW_COUNT();
    end while;

    delete from proc_user_groups;
    insert into proc_user_groups
    select 
    distinct user_id, group_id from user_group
    union 
    select distinct  user_id, ancestry_group_id from user_group ug inner join proc_group_recursive pgr on pgr.group_id = ug.group_id;
    
    update `user` dest 
    join (
            select u.user_id, case when min(g.access_profile_id)  = 5 then null else min(g.access_profile_id) end access_profile_id from 
            user u left join proc_user_groups pug on pug.user_id = u.user_id
            left join `group` g on pug.group_id = g.group_id
            group by u.user_id
    ) uap on uap.user_id = dest.user_id
    set dest.access_profile_id = uap.access_profile_id
    where 
    (
        (dest.access_profile_id <> uap.access_profile_id)
        or (dest.access_profile_id is null and uap.access_profile_id is not null)
        or (dest.access_profile_id is not null and uap.access_profile_id is null)
    ) and auth_mode_id <> 3;

    update `user` set access_profile_id = 4 where auth_mode_id = 3;

    update `group` set visible = true where name in ('WEBCONF_USER', 'WEBCONF_ADM' ) ;

END$$
DELIMITER ;

select 'Finished';

