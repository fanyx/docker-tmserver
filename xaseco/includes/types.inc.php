<?php
/* vim: set noexpandtab tabstop=2 softtabstop=2 shiftwidth=2: */

// Updated by Xymph

/**
 * Structure of a Record.
 */
class Record {
	var $player;
	var $challenge;
	var $score;
	var $date;
	var $checks;
	var $new;
	var $pos;
}  // class Record

/**
 * Manages a list of records.
 * Add records to the list and remove them.
 */
class RecordList {
	var $record_list;
	var $max;

	// instantiates a record list with max $limit records
	function RecordList($limit) {
		$this->record_list = array();
		$this->max = $limit;
	}

	function setLimit($limit) {
		$this->max = $limit;
	}

	function getRecord($rank) {
		if (isset($this->record_list[$rank]))
			return $this->record_list[$rank];
		else
			return false;
	}

	function setRecord($rank, $record) {
		if (isset($this->record_list[$rank])) {
			return $this->record_list[$rank] = $record;
		} else {
			return false;
		}
	}

	function moveRecord($from, $to) {
		moveArrayElement($this->record_list, $from, $to);
	}

	function addRecord($record, $rank = -1) {

		// if no rank was set for this record, then put it to the end of the list
		if ($rank == -1) {
			$rank = count($this->record_list);
		}

		// do not insert a record behind the border of the list
		if ($rank >= $this->max) return;

		// do not insert a record with no score
		if ($record->score <= 0) return;

		// if the given object is a record
		if (get_class($record) == 'Record') {

			// if records are getting too much, drop the last from the list
			if (count($this->record_list) >= $this->max) {
				array_pop($this->record_list);
			}

			// insert the record at the specified position
			return insertArrayElement($this->record_list, $record, $rank);
		}
	}

	function delRecord($rank = -1) {

		// do not remove a record outside the current list
		if ($rank < 0 || $rank >= count($this->record_list)) return;

		// remove the record from the specified position
		return removeArrayElement($this->record_list, $rank);
	}

	function count() {
		return count($this->record_list);
	}

	function clear() {
		$this->record_list = array();
	}
}  // class RecordList


/**
 * Structure of a Player.
 * Can be instantiated with an RPC 'GetPlayerInfo' or
 * 'GetDetailedPlayerInfo' response.
 */
class Player {
	var $id;
	var $pid;
	var $login;
	var $nickname;
	var $teamname;
	var $ip;
	var $client;
	var $ipport;
	var $zone;
	var $nation;
	var $prevstatus;
	var $isspectator;
	var $isofficial;
	var $rights;
	var $language;
	var $avatar;
	var $teamid;
	var $unlocked;
	var $ladderrank;
	var $ladderscore;
	var $created;
	var $wins;
	var $newwins;
	var $timeplayed;
	var $tracklist;
	var $playerlist;
	var $msgs;
	var $pmbuf;
	var $mutelist;
	var $mutebuf;
	var $style;
	var $panels;
	var $speclogin;
	var $dedirank;

	function getWins() {
		return $this->wins + $this->newwins;
	}

	function getTimePlayed() {
		return $this->timeplayed + $this->getTimeOnline();
	}

	function getTimeOnline() {
		return $this->created > 0 ? time() - $this->created : 0;
	}

	// instantiates the player with an RPC response
	function Player($rpc_infos = null) {
		$this->id = 0;
		if ($rpc_infos) {
			$this->pid = $rpc_infos['PlayerId'];
			$this->login = $rpc_infos['Login'];
			$this->nickname = $rpc_infos['NickName'];
			$this->ipport = $rpc_infos['IPAddress'];
			$this->ip = preg_replace('/:\d+/', '', $rpc_infos['IPAddress']);  // strip port
			$this->prevstatus = false;
			$this->isspectator = $rpc_infos['IsSpectator'];
			$this->isofficial = $rpc_infos['IsInOfficialMode'];
			$this->teamname = $rpc_infos['LadderStats']['TeamName'];
			if (isset($rpc_infos['Nation'])) {  // TMN (TMS/TMO?)
				$this->zone = $rpc_infos['Nation'];
				$this->nation = $rpc_infos['Nation'];
				$this->ladderrank = $rpc_infos['LadderStats']['Ranking'];
				$this->ladderscore = $rpc_infos['LadderStats']['Score'];
				$this->client = '';
				$this->rights = false;
				$this->language = '';
				$this->avatar = '';
				$this->teamid = 0;
			} else {  // TMF
				$this->zone = substr($rpc_infos['Path'], 6);  // strip 'World|'
				$this->nation = explode('|', $rpc_infos['Path']);
				if (isset($this->nation[1]))
					$this->nation = $this->nation[1];
				else
					$this->nation = '';
				$this->ladderrank = $rpc_infos['LadderStats']['PlayerRankings'][0]['Ranking'];
				$this->ladderscore = round($rpc_infos['LadderStats']['PlayerRankings'][0]['Score'], 2);
				$this->client = $rpc_infos['ClientVersion'];
				$this->rights = ($rpc_infos['OnlineRights'] == 3);  // United = true
				$this->language = $rpc_infos['Language'];
				$this->avatar = $rpc_infos['Avatar']['FileName'];
				$this->teamid = $rpc_infos['TeamId'];
			}
			$this->created = time();
		} else {
			// set defaults
			$this->pid = 0;
			$this->login = '';
			$this->nickname = '';
			$this->ipport = '';
			$this->ip = '';
			$this->prevstatus = false;
			$this->isspectator = false;
			$this->isofficial = false;
			$this->teamname = '';
			$this->zone = '';
			$this->nation = '';
			$this->ladderrank = 0;
			$this->ladderscore = 0;
			$this->rights = false;
			$this->created = 0;
		}
		$this->wins = 0;
		$this->newwins = 0;
		$this->timeplayed = 0;
		$this->unlocked = false;
		$this->pmbuf = array();
		$this->mutelist = array();
		$this->mutebuf = array();
		$this->style = array();
		$this->panels = array();
		$this->speclogin = '';
		$this->dedirank = 0;
	}
}  // class Player

/**
 * Manages players on the server.
 * Add player and remove them.
 */
class PlayerList {
	var $player_list;

	// instantiates the empty player list
	function PlayerList() {
		$this->player_list = array();
	}

	function nextPlayer() {
		if (is_array($this->player_list)) {
			$player_item = current($this->player_list);
			next($this->player_list);
			return $player_item;
		} else {
			$this->resetPlayers();
			return false;
		}
	}

	function resetPlayers() {
		if (is_array($this->player_list)) {
			reset($this->player_list);
		}
	}

	function addPlayer($player) {
		if (get_class($player) == 'Player' && $player->login != '') {
			$this->player_list[$player->login] = $player;
			return true;
		} else {
			return false;
		}
	}

	function removePlayer($login) {
		if (isset($this->player_list[$login])) {
			$player = $this->player_list[$login];
			unset($this->player_list[$login]);
		} else {
			$player = false;
		}
		return $player;
	}

	function getPlayer($login) {
		if (isset($this->player_list[$login]))
			return $this->player_list[$login];
		else
			return false;
	}
}  // class PlayerList


/**
 * Can store challenge information.
 * You can instantiate with an RPC 'GetChallengeInfo' response.
 */
class Challenge {
	var $id;
	var $name;
	var $uid;
	var $filename;
	var $author;
	var $environment;
	var $mood;
	var $bronzetime;
	var $silvertime;
	var $goldtime;
	var $authortime;
	var $copperprice;
	var $laprace;
	var $forcedlaps;
	var $nblaps;
	var $nbchecks;
	var $score;
	var $starttime;
	var $gbx;
	var $tmx;

	// instantiates the challenge with an RPC response
	function Challenge($rpc_infos = null) {
		$this->id = 0;
		if ($rpc_infos) {
			$this->name = stripNewlines($rpc_infos['Name']);
			$this->uid = $rpc_infos['UId'];
			$this->filename = $rpc_infos['FileName'];
			$this->author = $rpc_infos['Author'];
			$this->environment = $rpc_infos['Environnement'];
			$this->mood = $rpc_infos['Mood'];
			$this->bronzetime = $rpc_infos['BronzeTime'];
			$this->silvertime = $rpc_infos['SilverTime'];
			$this->goldtime = $rpc_infos['GoldTime'];
			$this->authortime = $rpc_infos['AuthorTime'];
			$this->copperprice = $rpc_infos['CopperPrice'];
			$this->laprace = $rpc_infos['LapRace'];
			$this->forcedlaps = 0;
			if (isset($rpc_infos['NbLaps']))
				$this->nblaps = $rpc_infos['NbLaps'];
			else
				$this->nblaps = 0;
			if (isset($rpc_infos['NbCheckpoints']))
				$this->nbchecks = $rpc_infos['NbCheckpoints'];
			else
				$this->nbchecks = 0;
		} else {
			// set defaults
			$this->name = 'undefined';
		}
	}
}  // class Challenge


/**
 * Contains information about an RPC call.
 */
class RPCCall {
	var $index;
	var $id;
	var $callback;
	var $call;

	// instantiates the RPC call with the parameters
	function RPCCall($id, $index, $callback, $call) {
		$this->id = $id;
		$this->index = $index;
		$this->callback = $callback;
		$this->call = $call;
	}
}  // class RPCCall


/**
 * Contains information about a chat command.
 */
class ChatCommand {
	var $name;
	var $help;
	var $isadmin;

	// instantiates the chat command with the parameters
	function ChatCommand($name, $help, $isadmin) {
		$this->name = $name;
		$this->help = $help;
		$this->isadmin = $isadmin;
	}
}  // class ChatCommand


/**
 * Stores basic information of the server XASECO is running on.
 */
class Server {
	var $id;
	var $name;
	var $game;
	var $serverlogin;
	var $nickname;
	var $zone;
	var $rights;
	var $ip;
	var $port;
	var $timeout;
	var $version;
	var $build;
	var $packmask;
	var $laddermin;
	var $laddermax;
	var $login;
	var $pass;
	var $maxplay;
	var $maxspec;
	var $challenge;
	var $records;
	var $players;
	var $mutelist;
	var $gamestate;
	var $gameinfo;
	var $gamedir;
	var $trackdir;
	var $votetime;
	var $voterate;
	var $uptime;
	var $starttime;
	var $isrelay;
	var $relaymaster;
	var $relayslist;

	// game states
	const RACE  = 'race';
	const SCORE = 'score';

	function getGame() {
		switch ($this->game) {
			case 'TmForever':
				return 'TMF';
			case 'TmNationsESWC':
				return 'TMN';
			case 'TmSunrise':
				return 'TMS';
			case 'TmOriginal':
				return 'TMO';
			default:  // TMU was never supported
				return 'Unknown';
		}
	}

	// instantiates the server with default parameters
	function Server($ip, $port, $login, $pass) {
		$this->ip = $ip;
		$this->port = $port;
		$this->login = $login;
		$this->pass = $pass;
		$this->starttime = time();
	}
}  // class Server

/**
 * Contains information to the current game which is played.
 */
class Gameinfo {
	var $mode;
	var $numchall;
	var $rndslimit;
	var $timelimit;
	var $teamlimit;
	var $lapslimit;
	var $cuplimit;
	var $forcedlaps;

	const RNDS = 0;
	const TA   = 1;
	const TEAM = 2;
	const LAPS = 3;
	const STNT = 4;
	const CUP  = 5;

	// returns current game mode as string
	function getMode() {
		switch ($this->mode) {
			case self::RNDS:
				return 'Rounds';
			case self::TA:
				return 'TimeAttack';
			case self::TEAM:
				return 'Team';
			case self::LAPS:
				return 'Laps';
			case self::STNT:
				return 'Stunts';
			case self::CUP:
				return 'Cup';
			default:
				return 'Undefined';
		}
	}

	// instantiates the game info with an RPC response
	function Gameinfo($rpc_infos = null) {
		if ($rpc_infos) {
			$this->mode = $rpc_infos['GameMode'];
			$this->numchall = $rpc_infos['NbChallenge'];
			if (isset($rpc_infos['RoundsUseNewRules']) && $rpc_infos['RoundsUseNewRules'])
				$this->rndslimit = $rpc_infos['RoundsPointsLimitNewRules'];
			else
				$this->rndslimit = $rpc_infos['RoundsPointsLimit'];
			$this->timelimit = $rpc_infos['TimeAttackLimit'];
			if (isset($rpc_infos['TeamUseNewRules']) && $rpc_infos['TeamUseNewRules'])
				$this->teamlimit = $rpc_infos['TeamPointsLimitNewRules'];
			else
				$this->teamlimit = $rpc_infos['TeamPointsLimit'];
			$this->lapslimit = $rpc_infos['LapsTimeLimit'];
			if (isset($rpc_infos['CupPointsLimit']))
				$this->cuplimit = $rpc_infos['CupPointsLimit'];
			if (isset($rpc_infos['RoundsForcedLaps']))
				$this->forcedlaps = $rpc_infos['RoundsForcedLaps'];
			else
				$this->forcedlaps = 0;
		} else {
			$this->mode = -1;
		}
	}
}  // class Gameinfo
?>
