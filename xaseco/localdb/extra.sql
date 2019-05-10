-- Database: `aseco`
--
-- --------------------------------------------------------

--
-- Tablestructure for Table `players_extra`
--

CREATE TABLE IF NOT EXISTS `players_extra` (
  `playerID` mediumint(9) NOT NULL default 0,
  `cps` smallint(3) NOT NULL default -1,
  `dedicps` smallint(3) NOT NULL default -1,
  `donations` mediumint(9) NOT NULL default 0,
  `style` varchar(20) NOT NULL default '',
  `panels` varchar(255) NOT NULL default '',
  PRIMARY KEY (`playerID`),
  KEY `donations` (`donations`)
) ENGINE=MyISAM;
