-- Database: `aseco`
--
-- --------------------------------------------------------

--
-- Tablestructure for Table `rs_karma`
--

CREATE TABLE IF NOT EXISTS `rs_karma` (
  `Id` int(11) NOT NULL auto_increment,
  `ChallengeId` mediumint(9) NOT NULL default 0,
  `PlayerId` mediumint(9) NOT NULL default 0,
  `Score` tinyint(4) NOT NULL default 0,
  PRIMARY KEY (`Id`),
  UNIQUE KEY `PlayerId` (`PlayerId`,`ChallengeId`),
  KEY `ChallengeId` (`ChallengeId`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Tablestructure for Table `rs_rank`
--

CREATE TABLE IF NOT EXISTS `rs_rank` (
  `playerID` mediumint(9) NOT NULL default 0,
  `avg` float NOT NULL default 0,
  KEY `playerID` (`playerID`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Tablestructure for Table `rs_times`
--

CREATE TABLE IF NOT EXISTS `rs_times` (
  `ID` int(11) NOT NULL auto_increment,
  `challengeID` mediumint(9) NOT NULL default 0,
  `playerID` mediumint(9) NOT NULL default 0,
  `score` int(11) NOT NULL default 0,
  `date` int(10) unsigned NOT NULL default 0,
  `checkpoints` text NOT NULL,
  PRIMARY KEY (`ID`),
  KEY `playerID` (`playerID`,`challengeID`),
  KEY `challengeID` (`challengeID`)
) ENGINE=MyISAM;
