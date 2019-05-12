-- Database: `aseco`
--
-- --------------------------------------------------------

--
-- Tablestructure for Table `challenges`
--

CREATE TABLE IF NOT EXISTS `challenges` (
  `Id` mediumint(9) NOT NULL auto_increment,
  `Uid` varchar(27) NOT NULL default '',
  `Date` datetime NOT NULL default '1970-01-01 00:00:00',
  `Name` varchar(100) NOT NULL default '',
  `Author` varchar(30) NOT NULL default '',
  `Environment` varchar(10) NOT NULL default '',
  PRIMARY KEY (`Id`),
  UNIQUE KEY `Uid` (`Uid`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Tablestructure for Table `players`
--

CREATE TABLE IF NOT EXISTS `players` (
  `Id` mediumint(9) NOT NULL auto_increment,
  `Login` varchar(50) NOT NULL default '',
  `Game` varchar(3) NOT NULL default '',
  `NickName` varchar(100) NOT NULL default '',
  `Nation` varchar(3) NOT NULL default '',
  `UpdatedAt` datetime NOT NULL default '1970-01-01 00:00:00',
  `Wins` mediumint(9) NOT NULL default 0,
  `TimePlayed` int(10) unsigned NOT NULL default 0,
  `TeamName` char(60) NOT NULL default '',
  PRIMARY KEY (`Id`),
  UNIQUE KEY `Login` (`Login`),
  KEY `Game` (`Game`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Tablestructure for Table `records`
--

CREATE TABLE IF NOT EXISTS `records` (
  `Id` int(11) NOT NULL auto_increment,
  `ChallengeId` mediumint(9) NOT NULL default 0,
  `PlayerId` mediumint(9) NOT NULL default 0,
  `Score` int(11) NOT NULL default 0,
  `Date` datetime NOT NULL default '1970-01-01 00:00:00',
  `Checkpoints` text NOT NULL,
  PRIMARY KEY (`Id`),
  UNIQUE KEY `PlayerId` (`PlayerId`,`ChallengeId`),
  KEY `ChallengeId` (`ChallengeId`)
) ENGINE=MyISAM;
