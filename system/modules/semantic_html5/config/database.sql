-- ********************************************************
-- *                                                      *
-- * IMPORTANT NOTE                                       *
-- *                                                      *
-- * Do not import this file manually but use the Contao  *
-- * install tool to create and maintain database tables! *
-- *                                                      *
-- ********************************************************

-- 
-- Table `tl_content`
-- 

CREATE TABLE `tl_content` (
  `sh5_type` varchar(64) NOT NULL default '',
  `sh5_tag` varchar(32) NOT NULL default 'start'
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
