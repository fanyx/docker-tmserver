<?php
/* vim: set noexpandtab tabstop=2 softtabstop=2 shiftwidth=2: */

/**
 * Dedimania plugin.
 * Handles interaction with the Dedimania world database.
 * Created by Xymph, based on FAST
 *
 * Dependencies: requires chat.dedimania.php, plugin.checkpoints.php
 *               used by chat.dedimania.php
 *               requires plugin.panels.php on TMF
 */

require_once('includes/GbxRemote.response.php');
require_once('includes/web_access.inc.php');
require_once('includes/xmlrpc_db.inc.php');

define('DEDICONFIG', 'dedimania.xml');

global $dedi_db, $dedi_db_defaults, $dedi_debug, $dedi_lastsent, $dedi_timeout,
       $dedi_refresh, $dedi_minauth, $dedi_mintime, $dedi_webaccess;

// how many seconds before retrying connection
$dedi_timeout = 1800;  // 30 mins
// how many seconds before reannouncing server
$dedi_refresh = 240;   // 4 mins
// minimum author & finish times that are still accepted
$dedi_minauth = 8000;  // 8 secs
$dedi_mintime = 6000;  // 6 secs
$dedi_debug = 0;  /* max debug level = 5:
1 +internal warnings
2 +main data structure, initial connection response, progress messages, dedicated callback data
3 +config defaults, XML config, full record lists, data in XML responses
4 +full XML responses
5 +record checkpoints
*/

// overrule these in dedimania.xml, don't change them here
$dedi_db_defaults = array(
	'Name' => 'Dedimania',
	'LogNews' => false,
	'ShowWelcome' => true,
	'ShowMinRecs' => 8,
	'ShowRecsBefore' => 1,
	'ShowRecsAfter' => 1,
	'ShowRecsRange' => true,
	'DisplayRecs' => true,
	'RecsInWindow' => false,
	'ShowRecLogins' => true,
	'LimitRecs' => 10,
);

Aseco::registerEvent('onSync', 'dedimania_init');
Aseco::registerEvent('onEverySecond', 'dedimania_update');
Aseco::registerEvent('onPlayerConnect', 'dedimania_playerconnect');
Aseco::registerEvent('onPlayerDisconnect', 'dedimania_playerdisconnect');
Aseco::registerEvent('onNewChallenge', 'dedimania_newchallenge');
Aseco::registerEvent('onEndRace', 'dedimania_endrace');
Aseco::registerEvent('onPlayerFinish', 'dedimania_playerfinish');

// Initialize Dedimania subsystem
// called @ onSync
function dedimania_init($aseco) {
	global $dedi_db, $dedi_db_defaults, $dedi_debug, $dedi_webaccess, $dedi_lastsent,
	       $checkpoints;  // from plugin.checkpoints.php

	// check for checkpoints plugin
	if (!isset($checkpoints) || !is_array($checkpoints))
		trigger_error('Dedimania system cannot find $checkpoints - include plugin.checkpoints.php in plugins.xml!', E_USER_ERROR);

	// create web access
	$dedi_webaccess = new Webaccess();

	if ($dedi_debug > 2)
		print_r($dedi_db_defaults);

	// read & parse config file
	$dedi_db = array();
	if ($config = $aseco->xml_parser->parseXml(DEDICONFIG)) {
		if ($dedi_debug > 2)
			print_r($config);

		// read the XML structure into array
		if (isset($config['DEDIMANIA']['DATABASE']) && is_array($config['DEDIMANIA']['DATABASE']) &&
		    isset($config['DEDIMANIA']['MASTERSERVER_ACCOUNT']) && is_array($config['DEDIMANIA']['MASTERSERVER_ACCOUNT'])) {
			$dbdata = &$config['DEDIMANIA']['DATABASE'][0];

			if ($dedi_debug > 2)
				print_r($dbdata);

			if (isset($dbdata['URL'][0])) {
				if (!is_array($dbdata['URL'][0]))
					$dedi_db['Url'] = $dbdata['URL'][0];
				else
					trigger_error('Multiple URLs specified in your Dedimania config file!', E_USER_ERROR);

				if (isset($dbdata['WELCOME'][0]))
					$dedi_db['Welcome'] = $dbdata['WELCOME'][0];
				else
					$dedi_db['Welcome'] = '';

				if (isset($dbdata['TIMEOUT'][0]))
					$dedi_db['Timeout'] = $dbdata['TIMEOUT'][0];
				else
					$dedi_db['Timeout'] = '';

				if (isset($dbdata['NAME'][0]))
					$dedi_db['Name'] = $dbdata['NAME'][0];
				else
					$dedi_db['Name'] = $dedi_db_defaults['Name'];

				if (isset($dbdata['LOG_NEWS'][0]))
					$dedi_db['LogNews'] = (strtolower($dbdata['LOG_NEWS'][0]) == 'true');
				else
					$dedi_db['LogNews'] = $dedi_db_defaults['LogNews'];

				if (isset($dbdata['SHOW_WELCOME'][0]))
					$dedi_db['ShowWelcome'] = (strtolower($dbdata['SHOW_WELCOME'][0]) == 'true');
				else
					$dedi_db['ShowWelcome'] = $dedi_db_defaults['ShowWelcome'];

				if (isset($dbdata['SHOW_MIN_RECS'][0]))
					$dedi_db['ShowMinRecs'] = intval($dbdata['SHOW_MIN_RECS'][0]);
				else
					$dedi_db['ShowMinRecs'] = $dedi_db_defaults['ShowMinRecs'];

				if (isset($dbdata['SHOW_RECS_BEFORE'][0]))
					$dedi_db['ShowRecsBefore'] = intval($dbdata['SHOW_RECS_BEFORE'][0]);
				else
					$dedi_db['ShowRecsBefore'] = $dedi_db_defaults['ShowRecsBefore'];

				if (isset($dbdata['SHOW_RECS_AFTER'][0]))
					$dedi_db['ShowRecsAfter'] = intval($dbdata['SHOW_RECS_AFTER'][0]);
				else
					$dedi_db['ShowRecsAfter'] = $dedi_db_defaults['ShowRecsAfter'];

				if (isset($dbdata['SHOW_RECS_RANGE'][0]))
					$dedi_db['ShowRecsRange'] = (strtolower($dbdata['SHOW_RECS_RANGE'][0]) == 'true');
				else
					$dedi_db['ShowRecsRange'] = $dedi_db_defaults['ShowRecsRange'];

				if (isset($dbdata['DISPLAY_RECS'][0]))
					$dedi_db['DisplayRecs'] = (strtolower($dbdata['DISPLAY_RECS'][0]) == 'true');
				else
					$dedi_db['DisplayRecs'] = $dedi_db_defaults['DisplayRecs'];

				if (isset($dbdata['RECS_IN_WINDOW'][0]))
					$dedi_db['RecsInWindow'] = (strtolower($dbdata['RECS_IN_WINDOW'][0]) == 'true');
				else
					$dedi_db['RecsInWindow'] = $dedi_db_defaults['RecsInWindow'];

				if (isset($dbdata['SHOW_REC_LOGINS'][0]))
					$dedi_db['ShowRecLogins'] = (strtolower($dbdata['SHOW_REC_LOGINS'][0]) == 'true');
				else
					$dedi_db['ShowRecLogins'] = $dedi_db_defaults['ShowRecLogins'];

				if (isset($dbdata['LIMIT_RECS'][0]))
					$dedi_db['LimitRecs'] = intval($dbdata['LIMIT_RECS'][0]);
				else
					$dedi_db['LimitRecs'] = $dedi_db_defaults['LimitRecs'];

				// set default MaxRank
				$dedi_db['MaxRank'] = 30;

				// check/initialise server configuration
				$dbdata = &$config['DEDIMANIA']['MASTERSERVER_ACCOUNT'][0];
				$dedi_db['Login'] = $dbdata['LOGIN'][0];
				$dedi_db['Password'] = $dbdata['PASSWORD'][0];
				$dedi_db['Nation'] = $dbdata['NATION'][0];
				if ($dedi_db['Login'] == '' || $dedi_db['Login'] == 'YOUR_SERVER_LOGIN' ||
				    $dedi_db['Password'] == '' || $dedi_db['Password'] == 'YOUR_SERVER_PASSWORD' ||
				    $dedi_db['Nation'] == '' || $dedi_db['Nation'] == 'YOUR_SERVER_NATION')
					trigger_error('Dedimania not configured! <masterserver_account> contains default or empty value(s)', E_USER_ERROR);

				if ($aseco->server->getGame() == 'TMF' && strtolower($dedi_db['Login']) != $aseco->server->serverlogin)
					trigger_error('Dedimania misconfigured! <masterserver_account><login> (' . $dedi_db['Login'] . ') is not the actual server login (' . $aseco->server->serverlogin . ')', E_USER_ERROR);

				$dedi_db['Messages'] = &$config['DEDIMANIA']['MESSAGES'][0];
				$dedi_db['RecsValid'] = false;
				$dedi_db['BannedLogins'] = array();

				$dedi_db['ModeList'] = array();
				$dedi_db['ModeList'][Gameinfo::RNDS] = 'Rounds';
				$dedi_db['ModeList'][Gameinfo::TA]   = 'TA';
				$dedi_db['ModeList'][Gameinfo::TEAM] = 'Rounds';
				$dedi_db['ModeList'][Gameinfo::LAPS] = 'TA';
				$dedi_db['ModeList'][Gameinfo::STNT] = '';
				$dedi_db['ModeList'][Gameinfo::CUP]  = 'Rounds';
			} else {
				trigger_error('No URL specified in your Dedimania config file!', E_USER_ERROR);
			}
		} else {
			trigger_error('Structure error in your Dedimania config file!', E_USER_ERROR);
		}
	} else {
		trigger_error('Could not read/parse Dedimania config file ' . DEDICONFIG . ' !', E_USER_ERROR);
	}

	if ($dedi_debug > 1)
		print_r($dedi_db);

	// connect to Dedimania server
	$aseco->console('************* (Dedimania) *************');
	dedimania_connect($aseco);
	$aseco->console('------------- (Dedimania) -------------');

	$dedi_lastsent = time();
}  // dedimania_init

function dedimania_connect($aseco) {
	global $dedi_db, $dedi_debug, $dedi_timeout, $dedi_webaccess;

	$time = time();

	// check for no or timed-out connection
	if (!isset($dedi_db['XmlrpcDB']) &&
	    (!isset($dedi_db['XmlrpcDBbadTime']) || ($time - $dedi_db['XmlrpcDBbadTime']) > $dedi_timeout)) {

		$aseco->console('* Dataserver connection on ' . $dedi_db['Name'] . ' ...');
		$aseco->console('* Try connection on ' . $dedi_db['Url'] . ' ...');

		// establish Dedimania connection and login
		$xmlrpcdb = new XmlrpcDB($dedi_webaccess, $dedi_db['Url'],
		                         $aseco->server->getGame(),
		                         $dedi_db['Login'],
		                         $dedi_db['Password'],
		                         'XASECO', XASECO_VERSION,
		                         $dedi_db['Nation'],
		                         $aseco->server->packmask);
		$response = $xmlrpcdb->RequestWait('dedimania.ValidateAccount');
		if ($dedi_debug > 3)
			$aseco->console_text('dedimania_connect - response' . CRLF . print_r($response, true));
		elseif ($dedi_debug > 2)
			$aseco->console_text('dedimania_connect - response[Data]' . CRLF . print_r($response['Data'], true));

		// Reply a struct {'Status': boolean,
		//                 'Messages': array of struct {'Date': string, 'Text': string} }

		// check response
		if ($response === false) {
			$aseco->console_text('  !!!' . CRLF . '  !!! Error bad database response !' . CRLF . '  !!!');
		}
		elseif (isset($response['Data']['params']['Status']) && $response['Data']['params']['Status']) {
			// establish Dedimania connection and login
			$xmlrpcdb = new XmlrpcDB($dedi_webaccess, $dedi_db['Url'],
			                         $aseco->server->getGame(),
			                         $dedi_db['Login'],
			                         $dedi_db['Password'],
			                         'XASECO', XASECO_VERSION,
			                         $dedi_db['Nation'],
			                         $aseco->server->packmask);
			$dedi_db['XmlrpcDB'] = $xmlrpcdb;
			$dedi_db['News'] = $response['Data']['params']['Messages'];
			$aseco->console('* Connection and status ok! (' . $response['Headers']['server'][0] . ')');
			if (($errors = dedi_iserror($response)) !== false)
				$aseco->console_text('  !!!' . CRLF . '  !!! ...with authentication warning(s): ' . $errors);
		}
		elseif (($errors = dedi_iserror($response)) !== false) {
			$aseco->console_text('  !!!' . CRLF . '  !!! Connection Error !!! (' . $response['Headers']['server'][0] . ')' . CRLF . $errors . CRLF . '  !!!');
		}
		elseif (!isset($response['Code'])) {
			$aseco->console_text('  !!!' . CRLF . '  !!! Error no database response (' . $dedi_db['Url'] . ')' . CRLF . '  !!!');
		}
		else {
			$aseco->console_text('  !!!' . CRLF . '  !!! Error bad database response or contents (' . $response['Headers']['server'][0] . ') ['
			                     . $response['Code'] . ', ' . $response['Reason'] . ']' . CRLF . '  !!!');
			if ($dedi_debug > 1) {
				if ($response['Code'] == 200)
					$aseco->console_text('dedimania_connect - response[Message]' . CRLF . $response['Message']);
				elseif ($response['Code'] != 404)
					$aseco->console_text('dedimania_connect - response' . CRLF . print_r($response, true));
			}
		}

		// check for valid connection
		if (isset($dedi_db['XmlrpcDB'])) {
			// log Dedimania news
			if ($dedi_db['LogNews'])
				foreach ($dedi_db['News'] as $news)
					$aseco->console('* NEWS (' . $dedi_db['Name'] . ', ' . $news['Date'] . '): ' . $news['Text']);
			return;
		}

		// prepare for next connection attempt
		$dedi_db['XmlrpcDBbadTime'] = $time;
	}
}  // dedimania_connect


function dedimania_announce() {
	global $aseco, $dedi_db, $dedi_debug, $dedi_lastsent;

	// check for valid track
	if (isset($aseco->server->challenge->uid)) {
		// check for valid connection
		if (isset($dedi_db['XmlrpcDB']) && !$dedi_db['XmlrpcDB']->isBad()) {
			if ($dedi_debug > 1)
				$aseco->console('** Update server Dedimania info...');

			// collect server & players info
			$serverinfo = dedimania_serverinfo($aseco);
			$players = dedimania_players($aseco);

			$dedi_lastsent = time();
			$callback = array('dedimania_announce_cb');
			$dedi_db['XmlrpcDB']->addRequest($callback,
			                                 'dedimania.UpdateServerPlayers',
			                                 $aseco->server->getGame(),
			                                 $aseco->server->gameinfo->mode,
			                                 $serverinfo,
			                                 $players);
			// UpdateServerPlayers(Game, Mode, SrvInfo, Players)
		}
	}
}  // dedimania_announce

function dedimania_announce_cb($response) {
	global $aseco, $dedi_debug;

	// Reply true

	if (($errors = dedi_iserror($response)) !== false) {
		if ($dedi_debug > 3)
			$aseco->console_text('dedimania_announce_cb - response' . CRLF . print_r($response, true));
		elseif ($dedi_debug > 2)
			$aseco->console_text('dedimania_announce_cb - response[Data]' . CRLF . print_r($response['Data'], true));
		else
			$aseco->console_text('dedimania_announce_cb - error(s): ' . $errors);
	}
}  // dedimania_announce_cb

// called @ onEverySecond
function dedimania_update($aseco) {
	global $dedi_db, $dedi_lastsent, $dedi_timeout, $dedi_refresh, $dedi_webaccess;

	// check for valid connection
	if (isset($dedi_db['XmlrpcDB'])) {
		// refresh DB every 4 mins after last DB update
		if ($dedi_lastsent + $dedi_refresh < time())
			dedimania_announce();

		if ($dedi_db['XmlrpcDB']->isBad()) {
			// retry after 30 mins of bad state
			if ($dedi_db['XmlrpcDB']->badTime() > $dedi_timeout) {
				$aseco->console('Dedimania retry to send after ' . round($dedi_timeout/60) . ' minutes...');
				$dedi_db['XmlrpcDB']->retry();
			}
		} else {
			$response = $dedi_db['XmlrpcDB']->sendRequests();
			if (!$response) {
				$message = '{#server}>> ' . formatText($dedi_db['Timeout'], round($dedi_timeout/60));
				$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($message));
				trigger_error('Dedimania has consecutive connection errors!', E_USER_WARNING);
			}
		}
	} else {
		// reconnect to Dedimania server
		dedimania_connect($aseco);
	}

	// trigger pending callbacks
	$read = array();
	$write = null;
	$except = null;
	$dedi_webaccess->select($read, $write, $except, 0);
}  // dedimania_update


// called @ onPlayerConnect
function dedimania_playerconnect($aseco, $player) {
	global $dedi_db, $dedi_debug;

	if ($dedi_debug > 1)
		$aseco->console_text('dedimania_playerconnect - ' . $player->login . ' : ' . stripColors($player->nickname, false));

	// get player info & check for non-LAN login
	if ($pinfo = dedimania_playerinfo($aseco, $player)) {
		if ($dedi_debug > 1)
			$aseco->console_text('dedimania_playerconnect - pinfo' . CRLF . print_r($pinfo, true));

		// check for valid connection
		if (isset($dedi_db['XmlrpcDB']) && !$dedi_db['XmlrpcDB']->isBad()) {
			$callback = array('dedimania_playerconnect_cb', $player->login);
			$dedi_db['XmlrpcDB']->addRequest($callback,
			                                 'dedimania.PlayerArrive',
			                                 $aseco->server->getGame(),
			                                 $player->login,
			                                 $player->nickname,
			                                 $pinfo['Nation'],
			                                 $pinfo['TeamName'],
			                                 $pinfo['Ranking'],
			                                 $pinfo['IsSpec'],
			                                 $pinfo['IsOff']);
			// PlayerArrive(Game, Login, Nickname, Nation, TeamName, LadderRanking, IsSpectator, IsOfficial)
		}
	}
}  // dedimania_playerconnect

function dedimania_playerconnect_cb($response, $login) {
	global $aseco, $dedi_db, $dedi_debug;

	// Reply a struct {'Login': string, 'TeamName': string, 'Nation': string,
	//                 'Options': array of struct {'Option': string, 'Value': string, 'Tool': string},
	//                 'Aliases': array of struct {'Alias': string, 'Text': string, 'Tool': string} }

	if ($dedi_debug > 3)
		$aseco->console_text('dedimania_playerconnect_cb - response' . CRLF . print_r($response, true));
	elseif ($dedi_debug > 2)
		$aseco->console_text('dedimania_playerconnect_cb - response[Data]' . CRLF . print_r($response['Data'], true));
	elseif (($errors = dedi_iserror($response)) !== false)
		$aseco->console_text('dedimania_playerconnect_cb - error(s): ' . $errors);

	// check response
	if (!$player = $aseco->server->players->getPlayer($login)) {
		if ($dedi_debug > 0)
			$aseco->console('dedimania_playerconnect_cb - ' . $login . ' does not exist!');
	}
	elseif (isset($response['Data']['params'])) {
		// update nickname in record
		if ($dedi_db['RecsValid'] && !empty($dedi_db['Challenge']['Records']) && isset($player->nickname)) {
			foreach ($dedi_db['Challenge']['Records'] as &$rec) {
				if ($rec['Login'] == $login && $rec['Game'] ==
				    ($aseco->server->getGame() == 'TMF' ? 'TMU' : $aseco->server->getGame())) {
					$rec['NickName'] = $player->nickname;
					break;
				}
			}
		}

		// show welcome message
		if ($dedi_db['ShowWelcome']) {
			$message = '{#server}> ' . $dedi_db['Welcome'];
			$message = str_replace('{br}', LF, $message);  // split long message
			// hyperlink Dedimania site on TMF
			if ($aseco->server->getGame() == 'TMF')
				$message = str_replace('www.dedimania.com', '$l[http://www.dedimania.com/]www.dedimania.com$l', $message);
			$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
		}

		// get player rank
		$player->dedirank = $dedi_db['MaxRank'];
		if (isset($response['Data']['params']['MaxRank']))
			$player->dedirank = $response['Data']['params']['MaxRank']+0;

		// check for banned player
		if (!isset($response['Data']['params']['Status']))
			trigger_error('Incomplete XASECO update - includes/xmlrpc_db.inc.php is out of date!', E_USER_ERROR);
		if ($response['Data']['params']['Status'] % 2 == 1) {
			// remember banned login
			$dedi_db['BannedLogins'][] = $login;
			// show chat message to all
			$message = formatText($dedi_db['Messages']['BANNED_LOGIN'][0],
			                      stripColors($player->nickname), $login);
			$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($message));
			// log banned player
			$aseco->console('[Dedimania] player {1} is banned - finishes ignored!', $login);
		}
	}
	else {
		if ($dedi_debug > 2)
			$aseco->console('dedimania_playerconnect_cb - bad response!');
	}
}  // dedimania_playerconnect_cb


// called @ onPlayerDisconnect
function dedimania_playerdisconnect($aseco, $player) {
	global $dedi_db, $dedi_debug;

	if ($dedi_debug > 1)
		$aseco->console_text('dedimania_playerdisconnect - ' . $player->login . ' : ' . stripColors($player->nickname, false));

	// check for non-LAN login
	if (!isLANLogin($player->login)) {
		// check for valid connection
		if (isset($dedi_db['XmlrpcDB']) && !$dedi_db['XmlrpcDB']->isBad()) {
			$dedi_db['XmlrpcDB']->addRequest(null,
			                                 'dedimania.PlayerLeave',
			                                 $aseco->server->getGame(),
			                                 $player->login);
			// PlayerLeave(Game, Login)
			// ignore: Reply a struct {'Login': string}
		}
	}

	// clear possible banned login
	if (($i = array_search($player->login, $dedi_db['BannedLogins'])) !== false)
		unset($dedi_db['BannedLogins'][$i]);
}  // dedimania_playerdisconnect


// called @ onNewChallenge
function dedimania_newchallenge($aseco, $challenge) {
	global $dedi_db, $dedi_debug, $dedi_minauth;

	if ($dedi_debug > 1)
		$aseco->console_text('dedimania_newchallenge - challenge' . CRLF . print_r($challenge, true));

	// check for valid connection
	$dedi_db['Challenge'] = array();
	if (isset($dedi_db['XmlrpcDB']) && !$dedi_db['XmlrpcDB']->isBad()) {
		// collect server & players info
		$serverinfo = dedimania_serverinfo($aseco);
		$players = dedimania_players($aseco);

		$callback = array('dedimania_newchallenge_cb', $challenge);
		$dedi_db['XmlrpcDB']->addRequest($callback,
		                                 'dedimania.CurrentChallenge',
		                                 $challenge->uid,
		                                 $challenge->name,
		                                 $challenge->environment,
		                                 $challenge->author,
		                                 $aseco->server->getGame(),
		                                 $aseco->server->gameinfo->mode,
		                                 $serverinfo,
		                                 $dedi_db['MaxRank'],
		                                 $players);
		// CurrentChallenge(Uid, Name, Environment, Author, Game, Mode, SrvInfos, MaxGetTimes, Players)
	}

	$dedi_db['RecsValid'] = false;
	$dedi_db['TrackValid'] = false;
	$dedi_db['ServerMaxRank'] = $dedi_db['MaxRank'];
	// check for Stunts mode
	if ($aseco->server->gameinfo->mode == Gameinfo::STNT)
		$aseco->console('[Dedimania] Stunts mode unsupported: records ignored');
	// check for multilap track in TMF Rounds/Team/Cup modes
	elseif ($aseco->server->getGame() == 'TMF' &&
	        $challenge->laprace && $challenge->forcedlaps != 0 &&
	        ($aseco->server->gameinfo->mode == Gameinfo::RNDS ||
	         $aseco->server->gameinfo->mode == Gameinfo::TEAM ||
	         $aseco->server->gameinfo->mode == Gameinfo::CUP))
		$aseco->console('[Dedimania] RoundForcedLaps != 0: records ignored');
	// check for minimum author time
	elseif ($challenge->authortime < $dedi_minauth)
		$aseco->console('[Dedimania] Map\'s Author time < ' . ($dedi_minauth / 1000) . 's: records ignored');
	else
		$dedi_db['TrackValid'] = true;
}  // dedimania_newchallenge

function dedimania_newchallenge_cb($response, $challenge) {
	global $aseco, $dedi_db, $dedi_debug,
	       $checkpoints;  // from plugin.checkpoints.php

	// Reply a struct {'Uid': string, 'TotalRaces': int, 'TotalPlayers': int,
	//                 'TimeAttackRaces': int, 'TimeAttackPlayers': int,
	//                 'NumberOfChecks': int, 'ServerMaxRecords': int,
	//                 'Records': array of struct {'Login': string, 'NickName': string,
	//                                             'Best': int, 'Rank': int,
	//                                             'Checks': array of int, 'Vote': int} }

	// if Stunts mode, bail out
	if ($aseco->server->gameinfo->mode == Gameinfo::STNT) return;

	if ($dedi_debug > 3)
		$aseco->console_text('dedimania_newchallenge_cb - response' . CRLF . print_r($response, true));
	elseif ($dedi_debug > 2)
		$aseco->console_text('dedimania_newchallenge_cb - response[Data]' . CRLF . print_r($response['Data'], true));
	elseif (($errors = dedi_iserror($response)) !== false)
		$aseco->console_text('dedimania_newchallenge_cb - error(s): ' . $errors);

	// check response
	if (isset($response['Data']['params']) && $dedi_db['TrackValid']) {
		$dedi_db['Challenge'] = $response['Data']['params'];
		$dedi_db['RecsValid'] = true;
		if (isset($response['Data']['params']['ServerMaxRecords']))
			$dedi_db['ServerMaxRank'] = $response['Data']['params']['ServerMaxRecords']+0;

		if ($dedi_debug > 1)
			$aseco->console_text('dedimania_newchallenge_cb - records' . CRLF . print_r($dedi_db['Challenge']['Records'], true));

		// check for records
		if (!empty($dedi_db['Challenge']['Records'])) {
			// strip line breaks in nicknames
			foreach ($dedi_db['Challenge']['Records'] as &$rec) {
				$rec['NickName'] = str_replace("\n", '', $rec['NickName']);
			}

			// set Dedimania record/checkpoints references
			if ($aseco->settings['display_checkpoints']) {
				foreach ($checkpoints as $login => $cp) {
					$drec = $checkpoints[$login]->dedirec - 1;

					// check for specific record
					if ($drec+1 > 0) {
						// if specific record unavailable, use last one
						if ($drec > count($dedi_db['Challenge']['Records']) - 1)
							$drec = count($dedi_db['Challenge']['Records']) - 1;
						// store record/checkpoints reference
						$checkpoints[$login]->best_fin = $dedi_db['Challenge']['Records'][$drec]['Best'];
						$checkpoints[$login]->best_cps = $dedi_db['Challenge']['Records'][$drec]['Checks'];
					}
					elseif ($drec+1 == 0) {
						// search for own/last record
						$drec = 0;
						while ($drec < count($dedi_db['Challenge']['Records'])) {
							if ($dedi_db['Challenge']['Records'][$drec++]['Login'] == $login)
								break;
						}
						$drec--;
						// store record/checkpoints reference
						$checkpoints[$login]->best_fin = $dedi_db['Challenge']['Records'][$drec]['Best'];
						$checkpoints[$login]->best_cps = $dedi_db['Challenge']['Records'][$drec]['Checks'];
					}  // else -1
				}
			}
			if ($dedi_debug > 4)
				$aseco->console_text('dedimania_newchallenge_cb - checkpoints' . CRLF . print_r($checkpoints, true));

			// notify records panel & update all panels
			if ($aseco->server->getGame() == 'TMF') {
				setRecordsPanel('dedi', ($aseco->server->gameinfo->mode == Gameinfo::STNT ?
				                         str_pad($dedi_db['Challenge']['Records'][0]['Best'], 5, ' ', STR_PAD_LEFT) :
				                         formatTime($dedi_db['Challenge']['Records'][0]['Best'])));
				if (function_exists('update_allrecpanels'))
					update_allrecpanels($aseco, null);  // from plugin.panels.php
			}
		}

		if ($dedi_db['ShowRecsBefore'] > 0)
			show_dedirecs($aseco, $challenge->name, $challenge->uid,
			              $dedi_db['Challenge']['Records'], false, 1,
			              $dedi_db['ShowRecsBefore']);  // from chat.dedimania.php
	} else {
		if ($dedi_debug > 2)
			$aseco->console('dedimania_newchallenge_cb - bad response or track invalid!');
	}

	// throw 'Dedimania records loaded' event
	$aseco->releaseEvent('onDediRecsLoaded', $dedi_db['RecsValid']);
}  // dedimania_newchallenge_cb


// called @ onEndRace
function dedimania_endrace($aseco, $data) {
	global $dedi_db, $dedi_debug, $dedi_lastsent, $dedi_mintime;

	// notify records panel
	if ($aseco->server->getGame() == 'TMF') {
		setRecordsPanel('dedi', ($aseco->server->gameinfo->mode == Gameinfo::STNT ?
		                         '  ---' : '   --.--'));
	}

	// if Stunts mode, bail out
	if ($aseco->server->gameinfo->mode == Gameinfo::STNT) return;

	if ($dedi_debug > 1)
		$aseco->console_text('dedimania_endrace - data' . CRLF . print_r($data, true));

	// check for valid track
	if (isset($data[1]['UId']) && isset($dedi_db['TrackValid']) && $dedi_db['TrackValid']) {
		// check for valid connection
		if (isset($dedi_db['XmlrpcDB']) && !$dedi_db['XmlrpcDB']->isBad()) {
			// collect/sort new finish times & checkpoints
			if ($dedi_db['RecsValid'] && !empty($dedi_db['Challenge']['Records'])) {
				$times = array();
				foreach ($dedi_db['Challenge']['Records'] as $rec) {
					// check for valid, minimum finish time
					if (isset($rec['NewBest']) && $rec['NewBest'] &&
					    $rec['Best'] >= $dedi_mintime)
						$times[] = array('Login' => $rec['Login'], 'Best' => $rec['Best'],
						                 'Checks' => implode(',', $rec['Checks']));
				}
				if (!empty($times))
					usort($times, 'dedi_timecompare');

				// compute number of checkpoints from best time
				$numchecks = 0;
				if (isset($times[0]['Checks']))
					$numchecks = count(explode(',', $times[0]['Checks']));

				if ($dedi_debug > 1) {
					$aseco->console_text('dedimania_endrace - numchecks: ' . $numchecks);
					$aseco->console_text('dedimania_endrace - times' . CRLF . print_r($times, true));
				}

				$dedi_lastsent = time();
				$callback = array('dedimania_endrace_cb', $data[1]);
				$dedi_db['XmlrpcDB']->addRequest($callback,
				                                 'dedimania.ChallengeRaceTimes',
				                                 $data[1]['UId'],
				                                 $data[1]['Name'],
				                                 $data[1]['Environnement'],
				                                 $data[1]['Author'],
				                                 $aseco->server->getGame(),
				                                 $aseco->server->gameinfo->mode,
				                                 $numchecks,
				                                 $dedi_db['MaxRank'],
				                                 $times);
				// ChallengeRaceTimes(Uid, Name, Environment, Author, Game, Mode, MaxGetTimes, Times)
				// Times is an array of struct {'Login': string, 'Best': int, 'Checks': array of int or comma-separated string of int}
			}
		}
	}
}  // dedimania_endrace

function dedimania_endrace_cb($response, $challenge) {
	global $aseco, $dedi_db, $dedi_debug;

	//Reply a struct {'Uid': string, 'TotalRaces': int, 'TotalPlayers': int,
	//                'TimeAttackRaces': int, 'TimeAttackPlayers': int,
	//                'NumberOfChecks': int, 'ServerMaxRecords': int,
	//                'Records': array of struct {'Login': string, 'NickName': string,
	//                                            'Best': int, 'Rank': int,
	//                                            'Checks': array of int, 'NewBest': boolean} }

	if ($dedi_debug > 3)
		$aseco->console_text('dedimania_endrace_cb - response' . CRLF . print_r($response, true));
	elseif ($dedi_debug > 2)
		$aseco->console_text('dedimania_endrace_cb - response[Data]' . CRLF . print_r($response['Data'], true));
	elseif (($errors = dedi_iserror($response)) !== false)
		$aseco->console_text('dedimania_endrace_cb - error(s): ' . $errors);

	// check response
	if (isset($response['Data']['params'])) {
		$dedi_db['Results'] = $response['Data']['params'];

		// check for records
		if (!empty($dedi_db['Results']['Records'])) {
			// strip line breaks in nicknames
			foreach ($dedi_db['Results']['Records'] as &$rec) {
				$rec['NickName'] = str_replace("\n", '', $rec['NickName']);
			}
			if ($dedi_debug > 1)
				$aseco->console_text('dedimania_endrace_cb - results' . CRLF . print_r($dedi_db['Results'], true));

			if ($dedi_db['ShowRecsAfter'] > 0)
				show_dedirecs($aseco, $challenge['Name'], $challenge['UId'],
				              $dedi_db['Results']['Records'], false, 3,
				              $dedi_db['ShowRecsAfter']);  // from chat.dedimania.php
		}

		// check for banned players
		if (isset($response['Data']['errors']) &&
		    preg_match('/Warning.+Player TM.+is banned on Dedimania/', $response['Data']['errors'])) {
			// log banned players
			$errors = explode("\n", $response['Data']['errors']);
			foreach ($errors as $error) {
				if (preg_match('/Warning.+Player TM[A-Z]+:(.+) is banned on Dedimania/', $error, $login))
					$aseco->console('[Dedimania] player {1} is banned - record ignored!', $login[1]);
			}
		}
	} else {
		if ($dedi_debug > 2)
			$aseco->console('dedimania_endrace_cb - bad response!');
	}
}  // dedimania_endrace_cb


// called @ onPlayerFinish
function dedimania_playerfinish($aseco, $finish_item) {
	global $dedi_db, $dedi_debug,
	       $checkpoints;  // from plugin.checkpoints.php

	// if no Dedimania records, bail out - Stunts mode temporarily too
	if (!$dedi_db['RecsValid'] || $aseco->server->gameinfo->mode == Gameinfo::STNT) return;

	// if no actual finish, bail out immediately
	if ($finish_item->score == 0) return;

	// in Laps mode on real PlayerFinish event, bail out too
	if ($aseco->server->gameinfo->mode == Gameinfo::LAPS && !$finish_item->new) return;

	$login = $finish_item->player->login;
	$nickname = stripColors($finish_item->player->nickname);

	// if LAN login, bail out immediately
	if (isLANLogin($login)) return;

	// if banned login, notify player and bail out
	if (in_array($login, $dedi_db['BannedLogins'])) {
		$message = formatText($dedi_db['Messages']['BANNED_FINISH'][0]);
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
		return;
	}

	if ($dedi_debug > 4)
		$aseco->console_text('dedimania_playerfinish - checkpoints ' . $login . CRLF . print_r($checkpoints[$login], true));

	// check finish/checkpoints consistency, unless Stunts mode
	if ($aseco->server->gameinfo->mode != Gameinfo::STNT) {
		if (($aseco->server->gameinfo->mode != Gameinfo::LAPS && $finish_item->score != $checkpoints[$login]->curr_fin) ||
		    $finish_item->score != end($checkpoints[$login]->curr_cps)) {
			$aseco->console('[Dedimania] player ' . $login . ' inconsistent finish/checks, ignored: ' . $finish_item->score . CRLF . print_r($checkpoints[$login], true));
			return;
		}
	}

	// point to master records list
	$dedi_recs = &$dedi_db['Challenge']['Records'];
	$maxrank = max($dedi_db['ServerMaxRank'], $finish_item->player->dedirank);

	// go through all records
	for ($i = 0; $i < $maxrank; $i++) {
		// check if no record, or player's time/score is better
		if (!isset($dedi_recs[$i]) || ($aseco->server->gameinfo->mode == Gameinfo::STNT ?
		                               $finish_item->score > $dedi_recs[$i]['Best'] :
		                               $finish_item->score < $dedi_recs[$i]['Best'])) {
			// does player have a record already?
			$cur_rank = -1;
			$cur_score = 0;
			for ($rank = 0; $rank < count($dedi_recs); $rank++) {
				$rec = $dedi_recs[$rank];

				if ($login == $rec['Login'] && $rec['Game'] ==
				    ($aseco->server->getGame() == 'TMF' ? 'TMU' : $aseco->server->getGame())) {
					// new record worse than old one
					if ($aseco->server->gameinfo->mode == Gameinfo::STNT ?
					    $finish_item->score < $rec['Best'] :
					    $finish_item->score > $rec['Best']) {
						return;

					// new record is better than or equal to old one
					} else {
						$cur_rank = $rank;
						$cur_score = $rec['Best'];
						break;
					}
				}
			}

			$finish_time = $finish_item->score;
			if ($aseco->server->gameinfo->mode != Gameinfo::STNT)
				$finish_time = formatTime($finish_time);

			if ($cur_rank != -1) {  // player has a record in topXX already

				// compute difference to old record
				if ($aseco->server->gameinfo->mode != Gameinfo::STNT) {
					$diff = $cur_score - $finish_item->score;
					$sec = floor($diff/1000);
					$hun = ($diff - ($sec * 1000)) / 10;
				} else {  // Stunts
					$diff = $finish_item->score - $cur_score;
				}

				// update the record if improved
				if ($diff > 0) {
					// ignore 'Rank' field - not used in /dedi* commands
					$dedi_recs[$cur_rank]['Best'] = $finish_item->score;
					$dedi_recs[$cur_rank]['Checks'] = $checkpoints[$login]->curr_cps;
					$dedi_recs[$cur_rank]['NewBest'] = true;
				}

				// player moved up in Dedimania list
				if ($cur_rank > $i) {

					// move record to the new position
					moveArrayElement($dedi_recs, $cur_rank, $i);

					// do a player improved his/her Dedimania rank message
					$message = formatText($dedi_db['Messages']['RECORD_NEW_RANK'][0],
					                      $nickname,
					                      $i+1,
					                      ($aseco->server->gameinfo->mode == Gameinfo::STNT ? 'Score' : 'Time'),
					                      $finish_time,
					                      $cur_rank+1,
					                      ($aseco->server->gameinfo->mode == Gameinfo::STNT ?
					                       '+' . $diff : sprintf('-%d.%02d', $sec, $hun)));

					// show chat message to all or player
					if ($dedi_db['DisplayRecs']) {
						if ($i < $dedi_db['LimitRecs']) {
							if ($dedi_db['RecsInWindow'] && function_exists('send_window_message'))
								send_window_message($aseco, $message, false);
							else
								$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($message));
						} else {
							$message = str_replace('{#server}>> ', '{#server}> ', $message);
							$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
						}
					}

				} else {

					if ($diff == 0) {
						// do a player equaled his/her record message
						$message = formatText($dedi_db['Messages']['RECORD_EQUAL'][0],
						                      $nickname,
						                      $cur_rank+1,
						                      ($aseco->server->gameinfo->mode == Gameinfo::STNT ? 'Score' : 'Time'),
						                      $finish_time);
					} else {
						// do a player secured his/her record message
						$message = formatText($dedi_db['Messages']['RECORD_NEW'][0],
						                      $nickname,
						                      $i+1,
						                      ($aseco->server->gameinfo->mode == Gameinfo::STNT ? 'Score' : 'Time'),
						                      $finish_time,
						                      $cur_rank+1,
						                      ($aseco->server->gameinfo->mode == Gameinfo::STNT ?
						                       '+' . $diff : sprintf('-%d.%02d', $sec, $hun)));
					}

					// show chat message to all or player
					if ($dedi_db['DisplayRecs']) {
						if ($i < $dedi_db['LimitRecs']) {
							if ($dedi_db['RecsInWindow'] && function_exists('send_window_message'))
								send_window_message($aseco, $message, false);
							else
								$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($message));
						} else {
							$message = str_replace('{#server}>> ', '{#server}> ', $message);
							$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
						}
					}
				}

			} else {  // player hasn't got a record yet

				// if previously tracking own/last Dedi record, now track new one
				if ($checkpoints[$login]->dedirec == 0) {
					$checkpoints[$login]->best_fin = $checkpoints[$login]->curr_fin;
					$checkpoints[$login]->best_cps = $checkpoints[$login]->curr_cps;
					// store timestamp for sorting in case of equal bests
					$checkpoints[$login]->best_time = microtime(true);
				}

				// insert new record at the specified position
				// ignore 'Rank' field - not used in /dedi* commands
				$record = array('Game' => ($aseco->server->getGame() == 'TMF' ? 'TMU' : $aseco->server->getGame()),
				                'Login' => $login,
				                'NickName' => $finish_item->player->nickname,
				                'Best' => $finish_item->score,
				                'Checks' => $checkpoints[$login]->curr_cps,
				                'NewBest' => true);
				insertArrayElement($dedi_recs, $record, $i);

				// do a player drove first record message
				$message = formatText($dedi_db['Messages']['RECORD_FIRST'][0],
				                      $nickname,
				                      $i+1,
				                      ($aseco->server->gameinfo->mode == Gameinfo::STNT ? 'Score' : 'Time'),
				                      $finish_time);

				// show chat message to all or player
				if ($dedi_db['DisplayRecs']) {
					if ($i < $dedi_db['LimitRecs']) {
						if ($dedi_db['RecsInWindow'] && function_exists('send_window_message'))
							send_window_message($aseco, $message, false);
						else
							$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($message));
					} else {
						$message = str_replace('{#server}>> ', '{#server}> ', $message);
						$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
					}
				}
			}

			// log a new Dedimania record (not an equalled one)
			if (isset($dedi_recs[$i]['NewBest']) && $dedi_recs[$i]['NewBest']) {
				// update all panels if new #1 record
				if ($aseco->server->getGame() == 'TMF' && $i == 0) {
					setRecordsPanel('dedi', ($aseco->server->gameinfo->mode == Gameinfo::STNT ?
					                         str_pad($finish_item->score, 5, ' ', STR_PAD_LEFT) :
					                         formatTime($finish_item->score)));
					if (function_exists('update_allrecpanels'))
						update_allrecpanels($aseco, null);  // from plugin.panels.php
				}

				// log record message in console
				$aseco->console('[Dedimania] player {1} finished with {2} and took the {3}. WR place!',
				                $login, $finish_item->score, $i+1);

				// throw 'Dedimania record' event
				$dedi_recs[$i]['Pos'] = $i+1;
				$aseco->releaseEvent('onDedimaniaRecord', $dedi_recs[$i]);
			}
			if ($dedi_debug > 1)
				$aseco->console_text('dedimania_playerfinish - dedi_recs' . CRLF . print_r($dedi_recs, true));

			// got the record, now stop!
			return;
		}
	}
}  // dedimania_playerfinish


/*
 * Support functions
 */
function dedimania_players($aseco) {
	global $dedi_debug;

	// collect all players
	$players = array();
	foreach ($aseco->server->players->player_list as $pl) {
		$pinfo = dedimania_playerinfo($aseco, $pl);
		if ($pinfo !== false)
			$players[] = $pinfo;
	}
	if ($dedi_debug > 2 || ($dedi_debug > 1 && count($players) > 0))
		$aseco->console_text('dedimania_players - players' . CRLF . print_r($players, true));
	return $players;
}  // dedimania_players

function dedimania_playerinfo($aseco, $player) {

	// check for non-LAN login
	if (!isLANLogin($player->login)) {
		$aseco->client->resetError();
		// get current player info
		if ($aseco->server->getGame() == 'TMF') {
			$aseco->client->query('GetDetailedPlayerInfo', $player->login);
			$info = $aseco->client->getResponse();

			if ($aseco->client->isError()) {
				return false;
			} else {
				$nation = explode('|', $info['Path']);
				if (isset($nation[1]))
					$nation = mapCountry($nation[1]);
				else
					$nation = mapCountry('');

				return array('Login' => $info['Login'],
				             'Nation' => $nation,
				             'TeamName' => $info['LadderStats']['TeamName'],
				             'TeamId' => -1,
				             'IsSpec' => $info['IsSpectator'],
				             'Ranking' => $info['LadderStats']['PlayerRankings'][0]['Ranking'],
				             'IsOff' => $info['IsInOfficialMode']
				            );
			}
		} else {  // TMN/TMS/TMO
			$aseco->client->query('GetPlayerInfo', $player->login);
			$info = $aseco->client->getResponse();

			if ($aseco->client->isError()) {
				return false;
			} else {
				return array('Login' => $info['Login'],
				             'Nation' => $info['Nation'],
				             'TeamName' => $info['LadderStats']['TeamName'],
				             'TeamId' => -1,
				             'IsSpec' => $info['IsSpectator'],
				             'Ranking' => $info['LadderStats']['Ranking'],
				             'IsOff' => $info['IsInOfficialMode']
				            );
			}
		}
	}
	return false;
}  // dedimania_playerinfo

function dedimania_serverinfo($aseco) {
	global $dedi_debug;

	// compute number of players and spectators
	$numplayers = 0;
	$numspecs = 0;
	foreach ($aseco->server->players->player_list as $pl) {
		if ($aseco->isSpectator($pl))
			$numspecs++;
		else
			$numplayers++;
	}

	// get current server options
	$aseco->client->query('GetServerOptions');
	$options = $aseco->client->getResponse();

	$serverinfo = array('SrvName' => $options['Name'],
	                    'Comment' => $options['Comment'],
	                    'Private' => ($options['Password'] != ''),
	                    'SrvIP' => '',
	                    'SrvPort' => 0,
	                    'XmlrpcPort' => 0,
	                    'NumPlayers' => $numplayers,
	                    'MaxPlayers' => $options['CurrentMaxPlayers'],
	                    'NumSpecs' => $numspecs,
	                    'MaxSpecs' => $options['CurrentMaxSpectators'],
	                    'LadderMode' => $options['CurrentLadderMode'],
	                    'NextFiveUID' => dedi_getnextuid($aseco)
	                   );
	if ($dedi_debug > 1)
		$aseco->console_text('dedimania_serverinfo - serverinfo' . CRLF . print_r($serverinfo, true));
	return $serverinfo;
}  // dedimania_serverinfo

function dedi_getnextuid($aseco) {
	global $jukebox;  // from plugin.rasp_jukebox.php

	// check for jukeboxed track
	if (isset($jukebox) && !empty($jukebox)) {
		$jbtemp = $jukebox;
		$track = array_shift($jbtemp);
		$next = $track['uid'];
	} else {
		// check server for next track
		if ($aseco->server->getGame() != 'TMF') {
			$aseco->client->query('GetCurrentChallengeIndex');
			$current = $aseco->client->getResponse();
			$aseco->client->query('GetChallengeList', 1, ++$current);
			$track = $aseco->client->getResponse();
			if ($aseco->client->isError()) {
				$aseco->client->query('GetChallengeList', 1, 0);
				$track = $aseco->client->getResponse();
			}
		} else {  // TMF
			$aseco->client->query('GetNextChallengeIndex');
			$next = $aseco->client->getResponse();
			$aseco->client->query('GetChallengeList', 1, $next);
			$track = $aseco->client->getResponse();
		}
		$next = $track[0]['UId'];
	}
	return $next;
}  // dedi_getnextuid

function dedi_iserror(&$response) {

	if (!isset($response))
		return 'No response!';
	if (isset($response['Error'])) {
		if (is_string($response['Error']) && strlen($response['Error']) > 0)
			return $response['Error'];
	}
	if (isset($response['Data']['errors'])) {
		if (is_string($response['Data']['errors']) && strlen($response['Data']['errors']) > 0)
			return $response['Data']['errors'];
		if (is_array($response['Data']['errors']) && count($response['Data']['errors']) > 0)
			return print_r($response['Data']['errors'], true);
	}
	return false;
}  // dedi_iserror

// usort comparison function: return -1 if $a should be before $b, 1 if vice-versa
function dedi_timecompare($a, $b) {
	global $checkpoints;  // from plugin.checkpoints.php

	// best a better than best b
	if ($a['Best'] < $b['Best'])
		return -1;
	// best b better than best a
	elseif ($a['Best'] > $b['Best'])
		return 1;
	// same best, use timestamp
	else
		return ($checkpoints[$a['Login']]->best_time < $checkpoints[$b['Login']]->best_time) ? -1 : 1;
}  // dedi_timecompare
?>
