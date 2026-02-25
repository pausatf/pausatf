--
-- Table structure for table `webcal_asst`
--

DROP TABLE IF EXISTS `webcal_asst`;
CREATE TABLE IF NOT EXISTS `webcal_asst` (
  `cal_boss` varchar(25) NOT NULL default '',
  `cal_assistant` varchar(25) NOT NULL default '',
  PRIMARY KEY  (`cal_boss`,`cal_assistant`)
) TYPE=MyISAM;

--
-- Dumping data for table `webcal_asst`
--


-- --------------------------------------------------------

--
-- Table structure for table `webcal_categories`
--

DROP TABLE IF EXISTS `webcal_categories`;
CREATE TABLE IF NOT EXISTS `webcal_categories` (
  `cat_id` int(11) NOT NULL default '0',
  `cat_owner` varchar(25) default NULL,
  `cat_name` varchar(80) NOT NULL default '',
  PRIMARY KEY  (`cat_id`)
) TYPE=MyISAM;

--
-- Dumping data for table `webcal_categories`
--


-- --------------------------------------------------------

--
-- Table structure for table `webcal_config`
--

DROP TABLE IF EXISTS `webcal_config`;
CREATE TABLE IF NOT EXISTS `webcal_config` (
  `cal_setting` varchar(50) NOT NULL default '',
  `cal_value` varchar(100) default NULL,
  PRIMARY KEY  (`cal_setting`)
) TYPE=MyISAM;

--
-- Dumping data for table `webcal_config`
--

INSERT INTO `webcal_config` (`cal_setting`, `cal_value`) VALUES ('application_name', 'WebCalendar');
INSERT INTO `webcal_config` (`cal_setting`, `cal_value`) VALUES ('LANGUAGE', '<LANGUAGE>');
INSERT INTO `webcal_config` (`cal_setting`, `cal_value`) VALUES ('demo_mode', 'N');
INSERT INTO `webcal_config` (`cal_setting`, `cal_value`) VALUES ('require_approvals', 'Y');
INSERT INTO `webcal_config` (`cal_setting`, `cal_value`) VALUES ('groups_enabled', 'N');
INSERT INTO `webcal_config` (`cal_setting`, `cal_value`) VALUES ('user_sees_only_his_groups', 'N');
INSERT INTO `webcal_config` (`cal_setting`, `cal_value`) VALUES ('categories_enabled', 'N');
INSERT INTO `webcal_config` (`cal_setting`, `cal_value`) VALUES ('allow_conflicts', 'N');
INSERT INTO `webcal_config` (`cal_setting`, `cal_value`) VALUES ('conflict_repeat_months', '6');
INSERT INTO `webcal_config` (`cal_setting`, `cal_value`) VALUES ('disable_priority_field', 'N');
INSERT INTO `webcal_config` (`cal_setting`, `cal_value`) VALUES ('disable_access_field', 'N');
INSERT INTO `webcal_config` (`cal_setting`, `cal_value`) VALUES ('disable_participants_field', 'N');
INSERT INTO `webcal_config` (`cal_setting`, `cal_value`) VALUES ('disable_repeating_field', 'N');
INSERT INTO `webcal_config` (`cal_setting`, `cal_value`) VALUES ('allow_view_other', 'Y');
INSERT INTO `webcal_config` (`cal_setting`, `cal_value`) VALUES ('email_fallback_from', '<ADMINEMAIL>');
INSERT INTO `webcal_config` (`cal_setting`, `cal_value`) VALUES ('remember_last_login', 'Y');
INSERT INTO `webcal_config` (`cal_setting`, `cal_value`) VALUES ('allow_color_customization', 'Y');
INSERT INTO `webcal_config` (`cal_setting`, `cal_value`) VALUES ('BGCOLOR', '#FFFFFF');
INSERT INTO `webcal_config` (`cal_setting`, `cal_value`) VALUES ('TEXTCOLOR', '#000000');
INSERT INTO `webcal_config` (`cal_setting`, `cal_value`) VALUES ('H2COLOR', '#000000');
INSERT INTO `webcal_config` (`cal_setting`, `cal_value`) VALUES ('CELLBG', '#C0C0C0');
INSERT INTO `webcal_config` (`cal_setting`, `cal_value`) VALUES ('WEEKENDBG', '#D0D0D0');
INSERT INTO `webcal_config` (`cal_setting`, `cal_value`) VALUES ('TABLEBG', '#000000');
INSERT INTO `webcal_config` (`cal_setting`, `cal_value`) VALUES ('THBG', '#FFFFFF');
INSERT INTO `webcal_config` (`cal_setting`, `cal_value`) VALUES ('THFG', '#000000');
INSERT INTO `webcal_config` (`cal_setting`, `cal_value`) VALUES ('POPUP_FG', '#000000');
INSERT INTO `webcal_config` (`cal_setting`, `cal_value`) VALUES ('POPUP_BG', '#FFFFFF');
INSERT INTO `webcal_config` (`cal_setting`, `cal_value`) VALUES ('TODAYCELLBG', '#FFFF33');
INSERT INTO `webcal_config` (`cal_setting`, `cal_value`) VALUES ('STARTVIEW', 'week.php');
INSERT INTO `webcal_config` (`cal_setting`, `cal_value`) VALUES ('WEEK_START', '0');
INSERT INTO `webcal_config` (`cal_setting`, `cal_value`) VALUES ('TIME_FORMAT', '12');
INSERT INTO `webcal_config` (`cal_setting`, `cal_value`) VALUES ('DISPLAY_UNAPPROVED', 'Y');
INSERT INTO `webcal_config` (`cal_setting`, `cal_value`) VALUES ('DISPLAY_WEEKNUMBER', 'Y');
INSERT INTO `webcal_config` (`cal_setting`, `cal_value`) VALUES ('WORK_DAY_START_HOUR', '8');
INSERT INTO `webcal_config` (`cal_setting`, `cal_value`) VALUES ('WORK_DAY_END_HOUR', '17');
INSERT INTO `webcal_config` (`cal_setting`, `cal_value`) VALUES ('send_email', 'N');
INSERT INTO `webcal_config` (`cal_setting`, `cal_value`) VALUES ('EMAIL_REMINDER', 'Y');
INSERT INTO `webcal_config` (`cal_setting`, `cal_value`) VALUES ('EMAIL_EVENT_ADDED', 'Y');
INSERT INTO `webcal_config` (`cal_setting`, `cal_value`) VALUES ('EMAIL_EVENT_UPDATED', 'Y');
INSERT INTO `webcal_config` (`cal_setting`, `cal_value`) VALUES ('EMAIL_EVENT_DELETED', 'Y');
INSERT INTO `webcal_config` (`cal_setting`, `cal_value`) VALUES ('EMAIL_EVENT_REJECTED', 'Y');
INSERT INTO `webcal_config` (`cal_setting`, `cal_value`) VALUES ('auto_refresh', 'N');
INSERT INTO `webcal_config` (`cal_setting`, `cal_value`) VALUES ('nonuser_enabled', 'N');
INSERT INTO `webcal_config` (`cal_setting`, `cal_value`) VALUES ('allow_html_description', 'N');
INSERT INTO `webcal_config` (`cal_setting`, `cal_value`) VALUES ('reports_enabled', 'N');
INSERT INTO `webcal_config` (`cal_setting`, `cal_value`) VALUES ('DISPLAY_WEEKENDS', 'Y');
INSERT INTO `webcal_config` (`cal_setting`, `cal_value`) VALUES ('DISPLAY_DESC_PRINT_DAY', 'N');
INSERT INTO `webcal_config` (`cal_setting`, `cal_value`) VALUES ('DATE_FORMAT', '__month__ __dd__, __yyyy__');
INSERT INTO `webcal_config` (`cal_setting`, `cal_value`) VALUES ('TIME_SLOTS', '12');
INSERT INTO `webcal_config` (`cal_setting`, `cal_value`) VALUES ('TIMED_EVT_LEN', 'D');
INSERT INTO `webcal_config` (`cal_setting`, `cal_value`) VALUES ('PUBLISH_ENABLED', 'N');
INSERT INTO `webcal_config` (`cal_setting`, `cal_value`) VALUES ('DATE_FORMAT_MY', '__month__ __yyyy__');
INSERT INTO `webcal_config` (`cal_setting`, `cal_value`) VALUES ('DATE_FORMAT_MD', '__month__ __dd__');
INSERT INTO `webcal_config` (`cal_setting`, `cal_value`) VALUES ('CUSTOM_SCRIPT', 'N');
INSERT INTO `webcal_config` (`cal_setting`, `cal_value`) VALUES ('CUSTOM_HEADER', 'N');
INSERT INTO `webcal_config` (`cal_setting`, `cal_value`) VALUES ('CUSTOM_TRAILER', 'N');
INSERT INTO `webcal_config` (`cal_setting`, `cal_value`) VALUES ('bold_days_in_year', 'Y');
INSERT INTO `webcal_config` (`cal_setting`, `cal_value`) VALUES ('site_extras_in_popup', 'N');
INSERT INTO `webcal_config` (`cal_setting`, `cal_value`) VALUES ('add_link_in_views', 'Y');
INSERT INTO `webcal_config` (`cal_setting`, `cal_value`) VALUES ('allow_conflict_override', 'Y');
INSERT INTO `webcal_config` (`cal_setting`, `cal_value`) VALUES ('limit_appts', 'N');
INSERT INTO `webcal_config` (`cal_setting`, `cal_value`) VALUES ('limit_appts_number', '6');
INSERT INTO `webcal_config` (`cal_setting`, `cal_value`) VALUES ('public_access', 'N');
INSERT INTO `webcal_config` (`cal_setting`, `cal_value`) VALUES ('public_access_default_visible', 'N');
INSERT INTO `webcal_config` (`cal_setting`, `cal_value`) VALUES ('public_access_default_selected', 'N');
INSERT INTO `webcal_config` (`cal_setting`, `cal_value`) VALUES ('public_access_others', 'N');
INSERT INTO `webcal_config` (`cal_setting`, `cal_value`) VALUES ('public_access_can_add', 'N');
INSERT INTO `webcal_config` (`cal_setting`, `cal_value`) VALUES ('public_access_add_needs_approval', 'Y');
INSERT INTO `webcal_config` (`cal_setting`, `cal_value`) VALUES ('public_access_view_part', 'N');
INSERT INTO `webcal_config` (`cal_setting`, `cal_value`) VALUES ('nonuser_at_top', 'Y');
INSERT INTO `webcal_config` (`cal_setting`, `cal_value`) VALUES ('allow_external_users', 'N');
INSERT INTO `webcal_config` (`cal_setting`, `cal_value`) VALUES ('external_notifications', 'N');
INSERT INTO `webcal_config` (`cal_setting`, `cal_value`) VALUES ('external_reminders', 'N');
INSERT INTO `webcal_config` (`cal_setting`, `cal_value`) VALUES ('enable_gradients', 'N');

-- --------------------------------------------------------

--
-- Table structure for table `webcal_entry`
--

DROP TABLE IF EXISTS `webcal_entry`;
CREATE TABLE IF NOT EXISTS `webcal_entry` (
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
) TYPE=MyISAM;

--
-- Dumping data for table `webcal_entry`
--


-- --------------------------------------------------------

--
-- Table structure for table `webcal_entry_ext_user`
--

DROP TABLE IF EXISTS `webcal_entry_ext_user`;
CREATE TABLE IF NOT EXISTS `webcal_entry_ext_user` (
  `cal_id` int(11) NOT NULL default '0',
  `cal_fullname` varchar(50) NOT NULL default '',
  `cal_email` varchar(75) default NULL,
  PRIMARY KEY  (`cal_id`,`cal_fullname`)
) TYPE=MyISAM;

--
-- Dumping data for table `webcal_entry_ext_user`
--


-- --------------------------------------------------------

--
-- Table structure for table `webcal_entry_log`
--

DROP TABLE IF EXISTS `webcal_entry_log`;
CREATE TABLE IF NOT EXISTS `webcal_entry_log` (
  `cal_log_id` int(11) NOT NULL default '0',
  `cal_entry_id` int(11) NOT NULL default '0',
  `cal_login` varchar(25) NOT NULL default '',
  `cal_user_cal` varchar(25) default NULL,
  `cal_type` char(1) NOT NULL default '',
  `cal_date` int(11) NOT NULL default '0',
  `cal_time` int(11) default NULL,
  `cal_text` text,
  PRIMARY KEY  (`cal_log_id`)
) TYPE=MyISAM;

--
-- Dumping data for table `webcal_entry_log`
--


-- --------------------------------------------------------

--
-- Table structure for table `webcal_entry_repeats`
--

DROP TABLE IF EXISTS `webcal_entry_repeats`;
CREATE TABLE IF NOT EXISTS `webcal_entry_repeats` (
  `cal_id` int(11) NOT NULL default '0',
  `cal_type` varchar(20) default NULL,
  `cal_end` int(11) default NULL,
  `cal_frequency` int(11) default '1',
  `cal_days` varchar(7) default NULL,
  PRIMARY KEY  (`cal_id`)
) TYPE=MyISAM;

--
-- Dumping data for table `webcal_entry_repeats`
--


-- --------------------------------------------------------

--
-- Table structure for table `webcal_entry_repeats_not`
--

DROP TABLE IF EXISTS `webcal_entry_repeats_not`;
CREATE TABLE IF NOT EXISTS `webcal_entry_repeats_not` (
  `cal_id` int(11) NOT NULL default '0',
  `cal_date` int(11) NOT NULL default '0',
  PRIMARY KEY  (`cal_id`,`cal_date`)
) TYPE=MyISAM;

--
-- Dumping data for table `webcal_entry_repeats_not`
--


-- --------------------------------------------------------

--
-- Table structure for table `webcal_entry_user`
--

DROP TABLE IF EXISTS `webcal_entry_user`;
CREATE TABLE IF NOT EXISTS `webcal_entry_user` (
  `cal_id` int(11) NOT NULL default '0',
  `cal_login` varchar(25) NOT NULL default '',
  `cal_status` char(1) default 'A',
  `cal_category` int(11) default NULL,
  PRIMARY KEY  (`cal_id`,`cal_login`)
) TYPE=MyISAM;

--
-- Dumping data for table `webcal_entry_user`
--


-- --------------------------------------------------------

--
-- Table structure for table `webcal_group`
--

DROP TABLE IF EXISTS `webcal_group`;
CREATE TABLE IF NOT EXISTS `webcal_group` (
  `cal_group_id` int(11) NOT NULL default '0',
  `cal_owner` varchar(25) default NULL,
  `cal_name` varchar(50) NOT NULL default '',
  `cal_last_update` int(11) NOT NULL default '0',
  PRIMARY KEY  (`cal_group_id`)
) TYPE=MyISAM;

--
-- Dumping data for table `webcal_group`
--


-- --------------------------------------------------------

--
-- Table structure for table `webcal_group_user`
--

DROP TABLE IF EXISTS `webcal_group_user`;
CREATE TABLE IF NOT EXISTS `webcal_group_user` (
  `cal_group_id` int(11) NOT NULL default '0',
  `cal_login` varchar(25) NOT NULL default '',
  PRIMARY KEY  (`cal_group_id`,`cal_login`)
) TYPE=MyISAM;

--
-- Dumping data for table `webcal_group_user`
--


-- --------------------------------------------------------

--
-- Table structure for table `webcal_import`
--

DROP TABLE IF EXISTS `webcal_import`;
CREATE TABLE IF NOT EXISTS `webcal_import` (
  `cal_import_id` int(11) NOT NULL default '0',
  `cal_name` varchar(50) default NULL,
  `cal_date` int(11) NOT NULL default '0',
  `cal_type` varchar(10) NOT NULL default '',
  `cal_login` varchar(25) default NULL,
  PRIMARY KEY  (`cal_import_id`)
) TYPE=MyISAM;

--
-- Dumping data for table `webcal_import`
--


-- --------------------------------------------------------

--
-- Table structure for table `webcal_import_data`
--

DROP TABLE IF EXISTS `webcal_import_data`;
CREATE TABLE IF NOT EXISTS `webcal_import_data` (
  `cal_import_id` int(11) NOT NULL default '0',
  `cal_id` int(11) NOT NULL default '0',
  `cal_login` varchar(25) NOT NULL default '',
  `cal_import_type` varchar(15) NOT NULL default '',
  `cal_external_id` varchar(200) default NULL,
  PRIMARY KEY  (`cal_id`,`cal_login`)
) TYPE=MyISAM;

--
-- Dumping data for table `webcal_import_data`
--


-- --------------------------------------------------------

--
-- Table structure for table `webcal_nonuser_cals`
--

DROP TABLE IF EXISTS `webcal_nonuser_cals`;
CREATE TABLE IF NOT EXISTS `webcal_nonuser_cals` (
  `cal_login` varchar(25) NOT NULL default '',
  `cal_lastname` varchar(25) default NULL,
  `cal_firstname` varchar(25) default NULL,
  `cal_admin` varchar(25) NOT NULL default '',
  PRIMARY KEY  (`cal_login`)
) TYPE=MyISAM;

--
-- Dumping data for table `webcal_nonuser_cals`
--


-- --------------------------------------------------------

--
-- Table structure for table `webcal_reminder_log`
--

DROP TABLE IF EXISTS `webcal_reminder_log`;
CREATE TABLE IF NOT EXISTS `webcal_reminder_log` (
  `cal_id` int(11) NOT NULL default '0',
  `cal_name` varchar(25) NOT NULL default '',
  `cal_event_date` int(11) NOT NULL default '0',
  `cal_last_sent` int(11) NOT NULL default '0',
  PRIMARY KEY  (`cal_id`,`cal_name`,`cal_event_date`)
) TYPE=MyISAM;

--
-- Dumping data for table `webcal_reminder_log`
--


-- --------------------------------------------------------

--
-- Table structure for table `webcal_report`
--

DROP TABLE IF EXISTS `webcal_report`;
CREATE TABLE IF NOT EXISTS `webcal_report` (
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
) TYPE=MyISAM;

--
-- Dumping data for table `webcal_report`
--


-- --------------------------------------------------------

--
-- Table structure for table `webcal_report_template`
--

DROP TABLE IF EXISTS `webcal_report_template`;
CREATE TABLE IF NOT EXISTS `webcal_report_template` (
  `cal_report_id` int(11) NOT NULL default '0',
  `cal_template_type` char(1) NOT NULL default '',
  `cal_template_text` text,
  PRIMARY KEY  (`cal_report_id`,`cal_template_type`)
) TYPE=MyISAM;

--
-- Dumping data for table `webcal_report_template`
--


-- --------------------------------------------------------

--
-- Table structure for table `webcal_site_extras`
--

DROP TABLE IF EXISTS `webcal_site_extras`;
CREATE TABLE IF NOT EXISTS `webcal_site_extras` (
  `cal_id` int(11) NOT NULL default '0',
  `cal_name` varchar(25) NOT NULL default '',
  `cal_type` int(11) NOT NULL default '0',
  `cal_date` int(11) default '0',
  `cal_remind` int(11) default '0',
  `cal_data` text,
  PRIMARY KEY  (`cal_id`,`cal_name`,`cal_type`)
) TYPE=MyISAM;

--
-- Dumping data for table `webcal_site_extras`
--


-- --------------------------------------------------------

--
-- Table structure for table `webcal_user`
--

DROP TABLE IF EXISTS `webcal_user`;
CREATE TABLE IF NOT EXISTS `webcal_user` (
  `cal_login` varchar(25) NOT NULL default '',
  `cal_passwd` varchar(32) default NULL,
  `cal_lastname` varchar(25) default NULL,
  `cal_firstname` varchar(25) default NULL,
  `cal_is_admin` char(1) default 'N',
  `cal_email` varchar(75) default NULL,
  PRIMARY KEY  (`cal_login`)
) TYPE=MyISAM;

--
-- Dumping data for table `webcal_user`
--

INSERT INTO `webcal_user` (`cal_login`, `cal_passwd`, `cal_lastname`, `cal_firstname`, `cal_is_admin`, `cal_email`) VALUES ('<ADMIN>', '<ADMINPASS>', '<ADMINSURNAME>', '<ADMINNAME>', 'Y', '<ADMINEMAIL>');

-- --------------------------------------------------------

--
-- Table structure for table `webcal_user_layers`
--

DROP TABLE IF EXISTS `webcal_user_layers`;
CREATE TABLE IF NOT EXISTS `webcal_user_layers` (
  `cal_layerid` int(11) NOT NULL default '0',
  `cal_login` varchar(25) NOT NULL default '',
  `cal_layeruser` varchar(25) NOT NULL default '',
  `cal_color` varchar(25) default NULL,
  `cal_dups` char(1) default 'N',
  PRIMARY KEY  (`cal_login`,`cal_layeruser`)
) TYPE=MyISAM;

--
-- Dumping data for table `webcal_user_layers`
--


-- --------------------------------------------------------

--
-- Table structure for table `webcal_user_pref`
--

DROP TABLE IF EXISTS `webcal_user_pref`;
CREATE TABLE IF NOT EXISTS `webcal_user_pref` (
  `cal_login` varchar(25) NOT NULL default '',
  `cal_setting` varchar(25) NOT NULL default '',
  `cal_value` varchar(100) default NULL,
  PRIMARY KEY  (`cal_login`,`cal_setting`)
) TYPE=MyISAM;

--
-- Dumping data for table `webcal_user_pref`
--

INSERT INTO `webcal_user_pref` (`cal_login`, `cal_setting`, `cal_value`) VALUES ('<ADMIN>', 'LANGUAGE', '<LANGUAGE>');

-- --------------------------------------------------------

--
-- Table structure for table `webcal_view`
--

DROP TABLE IF EXISTS `webcal_view`;
CREATE TABLE IF NOT EXISTS `webcal_view` (
  `cal_view_id` int(11) NOT NULL default '0',
  `cal_owner` varchar(25) NOT NULL default '',
  `cal_name` varchar(50) NOT NULL default '',
  `cal_view_type` char(1) default NULL,
  `cal_is_global` char(1) NOT NULL default 'N',
  PRIMARY KEY  (`cal_view_id`)
) TYPE=MyISAM;

--
-- Dumping data for table `webcal_view`
--


-- --------------------------------------------------------

--
-- Table structure for table `webcal_view_user`
--

DROP TABLE IF EXISTS `webcal_view_user`;
CREATE TABLE IF NOT EXISTS `webcal_view_user` (
  `cal_view_id` int(11) NOT NULL default '0',
  `cal_login` varchar(25) NOT NULL default '',
  PRIMARY KEY  (`cal_view_id`,`cal_login`)
) TYPE=MyISAM;

--
-- Dumping data for table `webcal_view_user`
--