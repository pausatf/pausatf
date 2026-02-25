-- MySQL dump 10.11
--
-- Host: localhost    Database: pausat_wcln1
-- ------------------------------------------------------
-- Server version	5.0.51a-community-log

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
-- Table structure for table `webcal_asst`
--

DROP TABLE IF EXISTS `webcal_asst`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `webcal_asst` (
  `cal_boss` varchar(25) NOT NULL default '',
  `cal_assistant` varchar(25) NOT NULL default '',
  PRIMARY KEY  (`cal_boss`,`cal_assistant`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `webcal_asst`
--

LOCK TABLES `webcal_asst` WRITE;
/*!40000 ALTER TABLE `webcal_asst` DISABLE KEYS */;
/*!40000 ALTER TABLE `webcal_asst` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `webcal_categories`
--

DROP TABLE IF EXISTS `webcal_categories`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `webcal_categories` (
  `cat_id` int(11) NOT NULL default '0',
  `cat_owner` varchar(25) default NULL,
  `cat_name` varchar(80) NOT NULL default '',
  PRIMARY KEY  (`cat_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `webcal_categories`
--

LOCK TABLES `webcal_categories` WRITE;
/*!40000 ALTER TABLE `webcal_categories` DISABLE KEYS */;
/*!40000 ALTER TABLE `webcal_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `webcal_config`
--

DROP TABLE IF EXISTS `webcal_config`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `webcal_config` (
  `cal_setting` varchar(50) NOT NULL default '',
  `cal_value` varchar(100) default NULL,
  PRIMARY KEY  (`cal_setting`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `webcal_config`
--

LOCK TABLES `webcal_config` WRITE;
/*!40000 ALTER TABLE `webcal_config` DISABLE KEYS */;
INSERT INTO `webcal_config` VALUES ('application_name','WebCalendar'),('LANGUAGE','English-US'),('demo_mode','N'),('require_approvals','Y'),('groups_enabled','N'),('user_sees_only_his_groups','N'),('categories_enabled','N'),('allow_conflicts','N'),('conflict_repeat_months','6'),('disable_priority_field','N'),('disable_access_field','N'),('disable_participants_field','N'),('disable_repeating_field','N'),('allow_view_other','Y'),('remember_last_login','Y'),('allow_color_customization','Y'),('BGCOLOR','#DDDDDD'),('TEXTCOLOR','#000000'),('H2COLOR','#3300FF'),('CELLBG','#FFFFFF'),('WEEKENDBG','#FFFFFF'),('TABLEBG','#CC0033'),('THBG','#FF0033'),('THFG','#3333FF'),('POPUP_FG','#000000'),('POPUP_BG','#DDDDDD'),('TODAYCELLBG','#FFFF33'),('WEEK_START','0'),('TIME_FORMAT','12'),('DISPLAY_UNAPPROVED','Y'),('DISPLAY_WEEKNUMBER','Y'),('WORK_DAY_START_HOUR','7'),('WORK_DAY_END_HOUR','17'),('send_email','N'),('EMAIL_REMINDER','Y'),('EMAIL_EVENT_ADDED','Y'),('EMAIL_EVENT_UPDATED','Y'),('EMAIL_EVENT_DELETED','Y'),('EMAIL_EVENT_REJECTED','Y'),('auto_refresh','N'),('nonuser_enabled','N'),('allow_html_description','N'),('reports_enabled','N'),('DISPLAY_WEEKENDS','Y'),('DISPLAY_DESC_PRINT_DAY','N'),('DATE_FORMAT','__month__ __dd__, __yyyy__'),('TIME_SLOTS','24'),('TIMED_EVT_LEN','D'),('PUBLISH_ENABLED','N'),('DATE_FORMAT_MY','__month__ __yyyy__'),('DATE_FORMAT_MD','__month__ __dd__'),('CUSTOM_SCRIPT','Y'),('CUSTOM_HEADER','N'),('CUSTOM_TRAILER','N'),('bold_days_in_year','Y'),('site_extras_in_popup','N'),('add_link_in_views','Y'),('allow_conflict_override','Y'),('limit_appts','N'),('limit_appts_number','6'),('public_access','Y'),('public_access_default_visible','Y'),('public_access_default_selected','N'),('public_access_others','N'),('public_access_can_add','N'),('public_access_add_needs_approval','Y'),('public_access_view_part','N'),('nonuser_at_top','Y'),('allow_external_users','N'),('external_notifications','N'),('external_reminders','N'),('enable_gradients','N'),('server_url','pausatf.org/calendar/'),('FONTS','Arial, Helvetica, sans-serif'),('STARTVIEW','month.php'),('auto_refresh_time','0');
/*!40000 ALTER TABLE `webcal_config` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `webcal_entry`
--

DROP TABLE IF EXISTS `webcal_entry`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `webcal_entry` (
  `cal_id` int(11) NOT NULL default '0',
  `cal_group_id` int(11) default NULL,
  `cal_ext_for_id` int(11) default NULL,
  `cal_create_by` varchar(25) NOT NULL default '',
  `cal_date` int(11) NOT NULL default '0',
  `cal_time` int(11) default NULL,
  `cal_mod_date` int(11) default NULL,
  `cal_mod_time` int(11) default NULL,
  `cal_duration` int(11) NOT NULL default '0',
  `cal_priority` int(11) default '2',
  `cal_type` char(1) default 'E',
  `cal_access` char(1) default 'P',
  `cal_name` varchar(80) NOT NULL default '',
  `cal_description` text,
  PRIMARY KEY  (`cal_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `webcal_entry`
--

LOCK TABLES `webcal_entry` WRITE;
/*!40000 ALTER TABLE `webcal_entry` DISABLE KEYS */;
INSERT INTO `webcal_entry` VALUES (1,NULL,NULL,'pausat',20051016,-1,20051018,182135,0,2,'E','P','Humboldt Half Marathon','Location:  Weott, CA\r\nStart Time:  9:00 AM\r\nCourse Description:  Out and back flat, paved, through the redwoods.'),(2,NULL,NULL,'pausat',20051113,-1,20051018,182915,0,2,'E','P','Clarksburg 30K','Location: Clarksburg, CA\r\nStart time:  10:00 AM\r\nCourse Descriptin:  Out and back flat, levee roads.\r\nGP Point Value : 2x'),(3,NULL,NULL,'pausat',20051211,-1,20051018,183212,0,2,'E','P','Christmas Relays','Location:  Lake Merced, SF\r\nStart Time:   9:00 AM\r\nDescription:  4-person relay around 4.47 mile lake course.  '),(4,NULL,NULL,'pausat',20051016,90000,20051019,134239,180,2,'E','P','Humbolt Half Marathon','Location: Weott, CA\r\nStart Time:  9:00 AM\r\nCourse:  Flat paved out and back, under towering redwoods'),(5,NULL,NULL,'pausat',20051016,-1,20051019,134257,180,2,'E','P','Humbolt Half Marathon','Location: Weott, CA\r\nStart Time:  9:00 AM\r\nCourse:  Flat paved out and back, under towering redwoods'),(6,NULL,NULL,'pausat',20051016,-1,20051019,134844,0,2,'E','P','LDR Meeting','Meeting to discuss LDR agenda, 2006 race schedule and more'),(7,NULL,NULL,'pausat',20051019,-1,20051019,140815,0,2,'E','P','Race Walk Event','Totally fictional 50 meter race walk for testing purposes'),(8,NULL,NULL,'pausat',20051019,-1,20051019,140912,0,2,'E','P','Webmaster Meeting','Totally bogus event \r\n\r\nTime:  Never\r\n\r\nDonuts will be served via LAN connection'),(9,NULL,NULL,'pausat',20051016,-1,20051019,140924,0,2,'E','P','Webmaster Meeting','Totally bogus event \r\n\r\nTime:  Never\r\n\r\nDonuts will be served via LAN connection'),(10,NULL,NULL,'pausat',20051016,-1,20051019,141007,0,2,'E','P','Race Walk Event','The famous and fictional 100m hurdles racewalk sprint'),(11,NULL,NULL,'pausat',20051016,-1,20051019,141112,0,2,'E','P','Youth Track Meet','Roseville Express odd distance events, including the 75 meter dash, 1.2 mile run and 27 meter hurdles'),(12,NULL,NULL,'pausat',20051019,-1,20051019,141338,0,2,'E','P','PA Meeting on Beer','I like beer.'),(13,NULL,NULL,'cynci',20051020,-1,20051019,142257,0,2,'E','P','Illegal Event Entry','This is not a legal entry...bad bad..'),(14,NULL,NULL,'joe',20051021,-1,20051019,143738,0,2,'E','P','Silly silly race','bogus...');
/*!40000 ALTER TABLE `webcal_entry` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `webcal_entry_ext_user`
--

DROP TABLE IF EXISTS `webcal_entry_ext_user`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `webcal_entry_ext_user` (
  `cal_id` int(11) NOT NULL default '0',
  `cal_fullname` varchar(50) NOT NULL default '',
  `cal_email` varchar(75) default NULL,
  PRIMARY KEY  (`cal_id`,`cal_fullname`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `webcal_entry_ext_user`
--

LOCK TABLES `webcal_entry_ext_user` WRITE;
/*!40000 ALTER TABLE `webcal_entry_ext_user` DISABLE KEYS */;
/*!40000 ALTER TABLE `webcal_entry_ext_user` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `webcal_entry_log`
--

DROP TABLE IF EXISTS `webcal_entry_log`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `webcal_entry_log` (
  `cal_log_id` int(11) NOT NULL default '0',
  `cal_entry_id` int(11) NOT NULL default '0',
  `cal_login` varchar(25) NOT NULL default '',
  `cal_user_cal` varchar(25) default NULL,
  `cal_type` char(1) NOT NULL default '',
  `cal_date` int(11) NOT NULL default '0',
  `cal_time` int(11) default NULL,
  `cal_text` text,
  PRIMARY KEY  (`cal_log_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `webcal_entry_log`
--

LOCK TABLES `webcal_entry_log` WRITE;
/*!40000 ALTER TABLE `webcal_entry_log` DISABLE KEYS */;
INSERT INTO `webcal_entry_log` VALUES (1,1,'pausat','pausat','C',20051018,182135,NULL),(2,2,'pausat','pausat','C',20051018,182915,NULL),(3,3,'pausat','pausat','C',20051018,183212,NULL),(4,4,'pausat','pausat','C',20051019,134239,NULL),(5,5,'pausat','pausat','C',20051019,134257,NULL),(6,4,'pausat','__public__','D',20051019,134445,NULL),(7,6,'pausat','pausat','C',20051019,134844,NULL),(8,7,'pausat','pausat','C',20051019,140815,NULL),(9,8,'pausat','pausat','C',20051019,140912,NULL),(10,9,'pausat','pausat','C',20051019,140924,NULL),(11,10,'pausat','pausat','C',20051019,141007,NULL),(12,11,'pausat','pausat','C',20051019,141112,NULL),(13,12,'pausat','pausat','C',20051019,141338,NULL),(14,13,'cynci','cynci','C',20051019,142257,NULL),(15,14,'joe','joe','C',20051019,143738,NULL),(16,14,'pausat','__public__','D',20051019,155916,NULL);
/*!40000 ALTER TABLE `webcal_entry_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `webcal_entry_repeats`
--

DROP TABLE IF EXISTS `webcal_entry_repeats`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `webcal_entry_repeats` (
  `cal_id` int(11) NOT NULL default '0',
  `cal_type` varchar(20) default NULL,
  `cal_end` int(11) default NULL,
  `cal_frequency` int(11) default '1',
  `cal_days` varchar(7) default NULL,
  PRIMARY KEY  (`cal_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `webcal_entry_repeats`
--

LOCK TABLES `webcal_entry_repeats` WRITE;
/*!40000 ALTER TABLE `webcal_entry_repeats` DISABLE KEYS */;
/*!40000 ALTER TABLE `webcal_entry_repeats` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `webcal_entry_repeats_not`
--

DROP TABLE IF EXISTS `webcal_entry_repeats_not`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `webcal_entry_repeats_not` (
  `cal_id` int(11) NOT NULL default '0',
  `cal_date` int(11) NOT NULL default '0',
  PRIMARY KEY  (`cal_id`,`cal_date`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `webcal_entry_repeats_not`
--

LOCK TABLES `webcal_entry_repeats_not` WRITE;
/*!40000 ALTER TABLE `webcal_entry_repeats_not` DISABLE KEYS */;
/*!40000 ALTER TABLE `webcal_entry_repeats_not` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `webcal_entry_user`
--

DROP TABLE IF EXISTS `webcal_entry_user`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `webcal_entry_user` (
  `cal_id` int(11) NOT NULL default '0',
  `cal_login` varchar(25) NOT NULL default '',
  `cal_status` char(1) default 'A',
  `cal_category` int(11) default NULL,
  PRIMARY KEY  (`cal_id`,`cal_login`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `webcal_entry_user`
--

LOCK TABLES `webcal_entry_user` WRITE;
/*!40000 ALTER TABLE `webcal_entry_user` DISABLE KEYS */;
INSERT INTO `webcal_entry_user` VALUES (1,'pausat','D',NULL),(2,'pausat','A',NULL),(3,'pausat','A',NULL),(4,'__public__','D',NULL),(5,'__public__','A',NULL),(6,'__public__','A',NULL),(7,'__public__','A',NULL),(8,'__public__','A',NULL),(9,'__public__','A',NULL),(10,'__public__','A',NULL),(11,'__public__','A',NULL),(12,'pausat','D',NULL),(13,'__public__','A',NULL),(14,'__public__','D',NULL);
/*!40000 ALTER TABLE `webcal_entry_user` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `webcal_group`
--

DROP TABLE IF EXISTS `webcal_group`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `webcal_group` (
  `cal_group_id` int(11) NOT NULL default '0',
  `cal_owner` varchar(25) default NULL,
  `cal_name` varchar(50) NOT NULL default '',
  `cal_last_update` int(11) NOT NULL default '0',
  PRIMARY KEY  (`cal_group_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `webcal_group`
--

LOCK TABLES `webcal_group` WRITE;
/*!40000 ALTER TABLE `webcal_group` DISABLE KEYS */;
/*!40000 ALTER TABLE `webcal_group` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `webcal_group_user`
--

DROP TABLE IF EXISTS `webcal_group_user`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `webcal_group_user` (
  `cal_group_id` int(11) NOT NULL default '0',
  `cal_login` varchar(25) NOT NULL default '',
  PRIMARY KEY  (`cal_group_id`,`cal_login`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `webcal_group_user`
--

LOCK TABLES `webcal_group_user` WRITE;
/*!40000 ALTER TABLE `webcal_group_user` DISABLE KEYS */;
/*!40000 ALTER TABLE `webcal_group_user` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `webcal_import`
--

DROP TABLE IF EXISTS `webcal_import`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `webcal_import` (
  `cal_import_id` int(11) NOT NULL default '0',
  `cal_name` varchar(50) default NULL,
  `cal_date` int(11) NOT NULL default '0',
  `cal_type` varchar(10) NOT NULL default '',
  `cal_login` varchar(25) default NULL,
  PRIMARY KEY  (`cal_import_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `webcal_import`
--

LOCK TABLES `webcal_import` WRITE;
/*!40000 ALTER TABLE `webcal_import` DISABLE KEYS */;
/*!40000 ALTER TABLE `webcal_import` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `webcal_import_data`
--

DROP TABLE IF EXISTS `webcal_import_data`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `webcal_import_data` (
  `cal_import_id` int(11) NOT NULL default '0',
  `cal_id` int(11) NOT NULL default '0',
  `cal_login` varchar(25) NOT NULL default '',
  `cal_import_type` varchar(15) NOT NULL default '',
  `cal_external_id` varchar(200) default NULL,
  PRIMARY KEY  (`cal_id`,`cal_login`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `webcal_import_data`
--

LOCK TABLES `webcal_import_data` WRITE;
/*!40000 ALTER TABLE `webcal_import_data` DISABLE KEYS */;
/*!40000 ALTER TABLE `webcal_import_data` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `webcal_nonuser_cals`
--

DROP TABLE IF EXISTS `webcal_nonuser_cals`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `webcal_nonuser_cals` (
  `cal_login` varchar(25) NOT NULL default '',
  `cal_lastname` varchar(25) default NULL,
  `cal_firstname` varchar(25) default NULL,
  `cal_admin` varchar(25) NOT NULL default '',
  PRIMARY KEY  (`cal_login`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `webcal_nonuser_cals`
--

LOCK TABLES `webcal_nonuser_cals` WRITE;
/*!40000 ALTER TABLE `webcal_nonuser_cals` DISABLE KEYS */;
/*!40000 ALTER TABLE `webcal_nonuser_cals` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `webcal_reminder_log`
--

DROP TABLE IF EXISTS `webcal_reminder_log`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `webcal_reminder_log` (
  `cal_id` int(11) NOT NULL default '0',
  `cal_name` varchar(25) NOT NULL default '',
  `cal_event_date` int(11) NOT NULL default '0',
  `cal_last_sent` int(11) NOT NULL default '0',
  PRIMARY KEY  (`cal_id`,`cal_name`,`cal_event_date`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `webcal_reminder_log`
--

LOCK TABLES `webcal_reminder_log` WRITE;
/*!40000 ALTER TABLE `webcal_reminder_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `webcal_reminder_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `webcal_report`
--

DROP TABLE IF EXISTS `webcal_report`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `webcal_report` (
  `cal_login` varchar(25) NOT NULL default '',
  `cal_report_id` int(11) NOT NULL default '0',
  `cal_is_global` char(1) NOT NULL default 'N',
  `cal_report_type` varchar(20) NOT NULL default '',
  `cal_include_header` char(1) NOT NULL default 'Y',
  `cal_report_name` varchar(50) NOT NULL default '',
  `cal_time_range` int(11) NOT NULL default '0',
  `cal_user` varchar(25) default NULL,
  `cal_allow_nav` char(1) default 'Y',
  `cal_cat_id` int(11) default NULL,
  `cal_include_empty` char(1) default 'N',
  `cal_show_in_trailer` char(1) default 'N',
  `cal_update_date` int(11) NOT NULL default '0',
  PRIMARY KEY  (`cal_report_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `webcal_report`
--

LOCK TABLES `webcal_report` WRITE;
/*!40000 ALTER TABLE `webcal_report` DISABLE KEYS */;
/*!40000 ALTER TABLE `webcal_report` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `webcal_report_template`
--

DROP TABLE IF EXISTS `webcal_report_template`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `webcal_report_template` (
  `cal_report_id` int(11) NOT NULL default '0',
  `cal_template_type` char(1) NOT NULL default '',
  `cal_template_text` text,
  PRIMARY KEY  (`cal_report_id`,`cal_template_type`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `webcal_report_template`
--

LOCK TABLES `webcal_report_template` WRITE;
/*!40000 ALTER TABLE `webcal_report_template` DISABLE KEYS */;
INSERT INTO `webcal_report_template` VALUES (0,'T','PAUSATF Web Calendar'),(0,'S','<link href=\"/PAstylesheetpg2.css\" type=\"text/css\" rel=\"stylesheet\">');
/*!40000 ALTER TABLE `webcal_report_template` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `webcal_site_extras`
--

DROP TABLE IF EXISTS `webcal_site_extras`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `webcal_site_extras` (
  `cal_id` int(11) NOT NULL default '0',
  `cal_name` varchar(25) NOT NULL default '',
  `cal_type` int(11) NOT NULL default '0',
  `cal_date` int(11) default '0',
  `cal_remind` int(11) default '0',
  `cal_data` text,
  PRIMARY KEY  (`cal_id`,`cal_name`,`cal_type`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `webcal_site_extras`
--

LOCK TABLES `webcal_site_extras` WRITE;
/*!40000 ALTER TABLE `webcal_site_extras` DISABLE KEYS */;
/*!40000 ALTER TABLE `webcal_site_extras` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `webcal_user`
--

DROP TABLE IF EXISTS `webcal_user`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `webcal_user` (
  `cal_login` varchar(25) NOT NULL default '',
  `cal_passwd` varchar(32) default NULL,
  `cal_lastname` varchar(25) default NULL,
  `cal_firstname` varchar(25) default NULL,
  `cal_is_admin` char(1) default 'N',
  `cal_email` varchar(75) default NULL,
  PRIMARY KEY  (`cal_login`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `webcal_user`
--

LOCK TABLES `webcal_user` WRITE;
/*!40000 ALTER TABLE `webcal_user` DISABLE KEYS */;
INSERT INTO `webcal_user` VALUES ('pausat','8271fe232582c6492a659518c45dc4b0','USATF','Pacific Association','Y',''),('cynci','e6e66b8981c1030d5650da159e79539a','Calvin','Cynci','Y',NULL),('maura','564f10260067a9b0c8d8e206ecdb49c6','Kent','Maura','Y',NULL),('joe','5144abbeb84239f038276f8e36b2251d','Blow','Joe','N',NULL);
/*!40000 ALTER TABLE `webcal_user` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `webcal_user_layers`
--

DROP TABLE IF EXISTS `webcal_user_layers`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `webcal_user_layers` (
  `cal_layerid` int(11) NOT NULL default '0',
  `cal_login` varchar(25) NOT NULL default '',
  `cal_layeruser` varchar(25) NOT NULL default '',
  `cal_color` varchar(25) default NULL,
  `cal_dups` char(1) default 'N',
  PRIMARY KEY  (`cal_login`,`cal_layeruser`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `webcal_user_layers`
--

LOCK TABLES `webcal_user_layers` WRITE;
/*!40000 ALTER TABLE `webcal_user_layers` DISABLE KEYS */;
/*!40000 ALTER TABLE `webcal_user_layers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `webcal_user_pref`
--

DROP TABLE IF EXISTS `webcal_user_pref`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `webcal_user_pref` (
  `cal_login` varchar(25) NOT NULL default '',
  `cal_setting` varchar(25) NOT NULL default '',
  `cal_value` varchar(100) default NULL,
  PRIMARY KEY  (`cal_login`,`cal_setting`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `webcal_user_pref`
--

LOCK TABLES `webcal_user_pref` WRITE;
/*!40000 ALTER TABLE `webcal_user_pref` DISABLE KEYS */;
INSERT INTO `webcal_user_pref` VALUES ('pausat','LANGUAGE','English-US'),('pausat','TZ_OFFSET','0'),('pausat','FONTS','Arial, Helvetica, sans-serif'),('pausat','STARTVIEW','month.php'),('pausat','DISPLAY_WEEKENDS','Y'),('pausat','DISPLAY_DESC_PRINT_DAY','N'),('pausat','DATE_FORMAT','__month__ __dd__, __yyyy__'),('pausat','DATE_FORMAT_MY','__month__ __yyyy__'),('pausat','DATE_FORMAT_MD','__month__ __dd__'),('pausat','TIME_FORMAT','12'),('pausat','TIME_SLOTS','24'),('pausat','auto_refresh','N'),('pausat','auto_refresh_time','0'),('pausat','DISPLAY_UNAPPROVED','Y'),('pausat','DISPLAY_WEEKNUMBER','N'),('pausat','WEEK_START','0'),('pausat','WORK_DAY_START_HOUR','7'),('pausat','WORK_DAY_END_HOUR','17'),('pausat','TIMED_EVT_LEN','D'),('pausat','EMAIL_REMINDER','Y'),('pausat','EMAIL_EVENT_ADDED','Y'),('pausat','EMAIL_EVENT_UPDATED','Y'),('pausat','EMAIL_EVENT_DELETED','Y'),('pausat','EMAIL_EVENT_REJECTED','Y'),('pausat','BGCOLOR','#DDDDDD'),('pausat','H2COLOR','#990033'),('pausat','CELLBG','#FFFFFF'),('pausat','TODAYCELLBG','#FFFF33'),('pausat','WEEKENDBG','#FFFFFF'),('__public__','LANGUAGE','English-US'),('__public__','TZ_OFFSET','0'),('__public__','FONTS','Arial, Helvetica, sans-serif'),('__public__','STARTVIEW','month.php'),('__public__','DISPLAY_WEEKENDS','Y'),('__public__','DISPLAY_DESC_PRINT_DAY','N'),('__public__','DATE_FORMAT','__month__ __dd__, __yyyy__'),('__public__','DATE_FORMAT_MY','__month__ __yyyy__'),('__public__','DATE_FORMAT_MD','__month__ __dd__'),('__public__','TIME_FORMAT','12'),('__public__','TIME_SLOTS','24'),('__public__','auto_refresh','Y'),('__public__','auto_refresh_time','0'),('__public__','DISPLAY_UNAPPROVED','Y'),('__public__','DISPLAY_WEEKNUMBER','N'),('__public__','WEEK_START','0'),('__public__','WORK_DAY_START_HOUR','7'),('__public__','WORK_DAY_END_HOUR','17'),('__public__','TIMED_EVT_LEN','D'),('__public__','BGCOLOR','#FFFFFF'),('__public__','H2COLOR','#3300FF'),('__public__','CELLBG','#DDDDDD'),('__public__','TODAYCELLBG','#FFFF66'),('__public__','WEEKENDBG','#DDDDDD'),('paust','LANGUAGE','English-US'),('cynci','LANGUAGE','English-US'),('joe','LANGUAGE','English-US');
/*!40000 ALTER TABLE `webcal_user_pref` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `webcal_view`
--

DROP TABLE IF EXISTS `webcal_view`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `webcal_view` (
  `cal_view_id` int(11) NOT NULL default '0',
  `cal_owner` varchar(25) NOT NULL default '',
  `cal_name` varchar(50) NOT NULL default '',
  `cal_view_type` char(1) default NULL,
  `cal_is_global` char(1) NOT NULL default 'N',
  PRIMARY KEY  (`cal_view_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `webcal_view`
--

LOCK TABLES `webcal_view` WRITE;
/*!40000 ALTER TABLE `webcal_view` DISABLE KEYS */;
/*!40000 ALTER TABLE `webcal_view` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `webcal_view_user`
--

DROP TABLE IF EXISTS `webcal_view_user`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `webcal_view_user` (
  `cal_view_id` int(11) NOT NULL default '0',
  `cal_login` varchar(25) NOT NULL default '',
  PRIMARY KEY  (`cal_view_id`,`cal_login`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `webcal_view_user`
--

LOCK TABLES `webcal_view_user` WRITE;
/*!40000 ALTER TABLE `webcal_view_user` DISABLE KEYS */;
/*!40000 ALTER TABLE `webcal_view_user` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2008-08-02  5:22:55
