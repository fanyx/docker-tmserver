<?php
/* vim: set noexpandtab tabstop=2 softtabstop=2 shiftwidth=2: */

/**
 * Chat plugin.
 * Displays server/XAseco info & plugins/nations lists.
 * Created by Xymph
 *
 * Dependencies: none
 */

Aseco::addChatCommand('server', 'Displays info about this server');
Aseco::addChatCommand('xaseco', 'Displays info about this XASECO');
Aseco::addChatCommand('plugins', 'Displays list of active plugins');
Aseco::addChatCommand('nations', 'Displays top 10 most visiting nations');

function chat_server($aseco, $command) {
	global $maxrecs, $admin_contact, $feature_votes;  // from rasp.settings.php

	$player = $command['author'];
	$login = $player->login;

	// collect players/nations stats
	$query = 'SELECT COUNT(Id), COUNT(DISTINCT Nation), SUM(TimePlayed) FROM players';
	$res = mysql_query($query);
	if (mysql_num_rows($res) > 0) {
		$row = mysql_fetch_row($res);
		$players = $row[0];
		$nations = $row[1];
		$totaltime = $row[2];
		mysql_free_result($res);
		$playdays = floor($totaltime / (24 * 3600));
		$playtime = $totaltime - ($playdays * 24 * 3600);
	} else {
		mysql_free_result($res);
		trigger_error('No players/nations stats found!', E_USER_ERROR);
	}

	// get server uptime
	$aseco->client->query('GetNetworkStats');
	$network = $aseco->client->getResponse();
	$aseco->server->uptime = $network['Uptime'];
	$updays = floor($aseco->server->uptime / (24 * 3600));
	$uptime = $aseco->server->uptime - ($updays * 24 * 3600);

	// showing info for TMN
	if ($aseco->server->getGame() == 'TMN') {

		$stats = 'Welcome to: ' . $aseco->server->name . '$z' . LF . LF;
		$stats .= '$gServer Date    : {#black}' . date('M d, Y') . LF;
		$stats .= '$gServer Time    : {#black}' . date('H:i:s T') . LF;
		$stats .= '$gUptime            : {#black}' . $updays . ' day' . ($updays == 1 ? ' ' : 's ') . formatTimeH($uptime * 1000, false) . LF;
		$stats .= '$gTrack Count     : {#black}' . $aseco->server->gameinfo->numchall . LF;
		$stats .= '$gGame Mode    : {#black}' . $aseco->server->gameinfo->getMode() . LF;
	switch ($aseco->server->gameinfo->mode) {
	case 0:
		$stats .= '$gPoints Limit     : {#black}' . $aseco->server->gameinfo->rndslimit . LF;
		break;
	case 1:
		$stats .= '$gTime Limit       : {#black}' . formatTime($aseco->server->gameinfo->timelimit) . LF;
		break;
	case 2:
		$stats .= '$gPoints Limit     : {#black}' . $aseco->server->gameinfo->teamlimit . LF;
		break;
	case 3:
		$stats .= '$gTime Limit       : {#black}' . formatTime($aseco->server->gameinfo->lapslimit) . LF;
		break;
	}
		$stats .= '$gMax Players    : {#black}' . $aseco->server->maxplay . LF;
		$stats .= '$gMax Specs      : {#black}' . $aseco->server->maxspec . LF;
		$stats .= '$gRecs/Track     : {#black}' . $maxrecs . LF;
	if ($feature_votes) {
		$stats .= '$gVoting info      : {#black}/helpvote' . LF;
	} else {
		$stats .= '$gVote Timeout  : {#black}' . formatTime($aseco->server->votetime) . LF;
		$stats .= '$gVote Ratio       : {#black}' . round($aseco->server->voterate, 2) . LF;
	}
	if ($admin_contact) {
		$stats .= '$gAdmin Contact: {#black}' . $admin_contact . LF;
	}
		$stats .= LF . '$gVisited by $f80' . $players . ' $gPlayers from $f40' . $nations . ' $gNations' . LF;
		$stats .= 'who together played: {#black}' . $playdays . ' day' . ($playdays == 1 ? ' ' : 's ') . formatTimeH($playtime * 1000, false) . ' $g!';

		// display popup message
		$aseco->client->query('SendDisplayServerMessageToLogin', $login, $aseco->formatColors($stats), 'OK', '', 0);

	// showing info for TMF
	} elseif ($aseco->server->getGame() == 'TMF') {

		// get more server settings in one go
		$comment = $aseco->client->addCall('GetServerComment', array());
		$coppers = $aseco->client->addCall('GetServerCoppers', array());
		$cuprpc = $aseco->client->addCall('GetCupRoundsPerChallenge', array());
		if (!$aseco->client->multiquery()) {
			trigger_error('[' . $aseco->client->getErrorCode() . '] GetServer (multi) - ' . $aseco->client->getErrorMessage(), E_USER_WARNING);
		} else {
			$response = $aseco->client->getResponse();
			$comment = $response[$comment][0];
			$coppers = $response[$coppers][0];
			$cuprpc = $response[$cuprpc][0]['CurrentValue'];
		}

		$header = 'Welcome to: ' . $aseco->server->name;
		$stats = array();
		$stats[] = array('Server Date', '{#black}' . date('M d, Y'));
		$stats[] = array('Server Time', '{#black}' . date('H:i:s T'));
		$stats[] = array('Zone', '{#black}' . $aseco->server->zone);
		$field = 'Comment';

		// break up long line into chunks with continuation strings
		$multicmt = explode(LF, wordwrap($comment, 35, LF . '...'));
		foreach ($multicmt as $line) {
			$stats[] = array($field, '{#black}' . $line);
			$field = '';
		}

		$stats[] = array('Uptime', '{#black}' . $updays . ' day' . ($updays == 1 ? ' ' : 's ') . formatTimeH($uptime * 1000, false));
		if ($aseco->server->isrelay)
			$stats[] = array('Relays', '{#black}' . $aseco->server->relaymaster['Login'] .
			                 ' / ' . $aseco->server->relaymaster['NickName']);
		else
			$stats[] = array('Track Count', '{#black}' . $aseco->server->gameinfo->numchall);
		$stats[] = array('Game Mode', '{#black}' . $aseco->server->gameinfo->getMode());
	switch ($aseco->server->gameinfo->mode) {
	case 0:
		$stats[] = array('Points Limit', '{#black}' . $aseco->server->gameinfo->rndslimit);
		break;
	case 1:
		$stats[] = array('Time Limit', '{#black}' . formatTime($aseco->server->gameinfo->timelimit));
		break;
	case 2:
		$stats[] = array('Points Limit', '{#black}' . $aseco->server->gameinfo->teamlimit);
		break;
	case 3:
		$stats[] = array('Time Limit', '{#black}' . formatTime($aseco->server->gameinfo->lapslimit));
		break;
	case 4:
		$stats[] = array('Time Limit', '{#black}' . formatTime(5 * 60 * 1000));  // always 5 minutes?
		break;
	case 5:
		$stats[] = array('Points Limit', '{#black}' . $aseco->server->gameinfo->cuplimit . '$g   R/C: {#black}' . $cuprpc);
		break;
	}
		$stats[] = array('Max Players', '{#black}' . $aseco->server->maxplay);
		$stats[] = array('Max Specs', '{#black}' . $aseco->server->maxspec);
		$stats[] = array('Recs/Track', '{#black}' . $maxrecs);
	if ($feature_votes) {
		$stats[] = array('Voting info', '{#black}/helpvote');
	} else {
		$stats[] = array('Vote Timeout', '{#black}' . formatTime($aseco->server->votetime));
		$stats[] = array('Vote Ratio', '{#black}' . round($aseco->server->voterate, 2));
	}
	// check for TMUF server
	if ($aseco->server->rights) {
		$stats[] = array('Rights', '{#black}United' .
		                 ($aseco->allowAbility($player, 'server_coppers') ?
		                  '   $gCoppers: {#black}' . $coppers : ''));
	} else {  // TMNF
		$stats[] = array('Rights', '{#black}Nations');
	}
		$stats[] = array('Ladder Limits', '{#black}' . $aseco->server->laddermin .
		                  '$g - {#black}' . $aseco->server->laddermax);
	if ($admin_contact) {
		$stats[] = array('Admin Contact', '{#black}' . $admin_contact);
	}
		$stats[] = array();
		$stats[] = array('Visited by $f80' . $players . ' $gPlayers from $f40' . $nations . ' $gNations');
		$stats[] = array('who together played: {#black}' . $playdays . ' day' . ($playdays == 1 ? ' ' : 's ') . formatTimeH($playtime * 1000, false) . ' $g!');

		// display ManiaLink message
		display_manialink($login, $header, array('Icons64x64_1', 'DisplaySettings', 0.01), $stats, array(1.0, 0.3, 0.7), 'OK');

	} else {  // TMS/TMO
		$stats = '{#server}>> Server Stats' . LF;
		$stats .= 'Showing info of ' . $aseco->server->name . LF;
		// no info actually shown

		// show chat message
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($stats), $login);
	}
}  // chat_server

function chat_xaseco($aseco, $command) {
	global $admin_contact;  // from rasp.settings.php

	$player = $command['author'];
	$login = $player->login;

	$uptime = time() - $aseco->uptime;
	$updays = floor($uptime / (24 * 3600));
	$uptime = $uptime - ($updays * 24 * 3600);

	// showing info for TMN
	if ($aseco->server->getGame() == 'TMN') {

		$info = 'XASECO info: ' . $aseco->server->name . '$z' . LF . LF;
		$info .= '$gVersion           : {#black}' . XASECO_VERSION . LF;
		$info .= '$gUptime            : {#black}' . $updays . ' day' . ($updays == 1 ? ' ' : 's ') . formatTimeH($uptime * 1000, false) . LF;
		$info .= '$gWebsites        : {#black}' . XASECO_ORG . LF;
		$info .= '$g                         {#black}' . XASECO_TMN . LF;
		$info .= '$g                         {#black}' . XASECO_TMF . LF;
		$info .= '$g                         {#black}' . XASECO_TM2 . LF;
		$info .= '$gCredits            : {#black}Main author: Xymph (since v0.8)' . LF;
		$info .= '$g                         {#black}Original authors: $nFlo, Assembler Maniac, Jfreu & others$m' . LF;
		if (isset($aseco->masteradmin_list['TMLOGIN'])) {
			// count non-LAN logins
			$count = 0;
			foreach ($aseco->masteradmin_list['TMLOGIN'] as $lgn) {
				if ($lgn != '' && !isLANLogin($lgn)) {
					$count++;
				}
			}
			if ($count > 0) {
				$field = 'Masteradmin';
				if ($count > 1)
					$field .= 's';
				else
					$field .= '  ';
				$field .= ' : ';
				foreach ($aseco->masteradmin_list['TMLOGIN'] as $lgn) {
					// skip any LAN logins
					if ($lgn != '' && !isLANLogin($lgn)) {
						$info .= '$g' . $field . '{#black}' . $aseco->getPlayerNick($lgn) . '$z' . LF;
						$field = '                          ';
					}
				}
			}
		}
		if ($admin_contact) {
			$info .= '$gAdmin Contact: {#black}' . $admin_contact . LF;
		}

		// display popup message
		$aseco->client->query('SendDisplayServerMessageToLogin', $login, $aseco->formatColors($info), 'OK', '', 0);

	// showing info for TMF
	} elseif ($aseco->server->getGame() == 'TMF') {

		// prepare Welcome message
		$welcome = formatText($aseco->getChatMessage('WELCOME'),
		                      stripColors($player->nickname),
		                      $aseco->server->name, XASECO_VERSION);

		$header = 'XASECO info: ' . $aseco->server->name;
		$info = array();
		$info[] = array('Version', '{#black}' . XASECO_VERSION);
		$field = 'Welcome';
		$welcome = preg_split('/{br}/', $aseco->formatColors($welcome));
		foreach ($welcome as $line) {
			$info[] = array($field, '{#black}' . $line);
			$field = '';
		}

		$info[] = array('Uptime', '{#black}' . $updays . ' day' . ($updays == 1 ? ' ' : 's ') . formatTimeH($uptime * 1000, false));
		$info[] = array('Websites', '{#black}$l[' . XASECO_ORG . ']' . XASECO_ORG . '$l');
		$info[] = array('', '{#black}$l[' . XASECO_TMN . ']' . XASECO_TMN . '$l');
		$info[] = array('', '{#black}$l[' . XASECO_TMF . ']' . XASECO_TMF . '$l');
		$info[] = array('', '{#black}$l[' . XASECO_TM2 . ']' . XASECO_TM2 . '$l');
		$info[] = array('Credits', '{#black}Main author: Xymph (since v0.8)');
		$info[] = array('', '{#black}Original authors: Flo, Assembler Maniac, Jfreu & others');
		if (isset($aseco->masteradmin_list['TMLOGIN'])) {
			// count non-LAN logins
			$count = 0;
			foreach ($aseco->masteradmin_list['TMLOGIN'] as $lgn) {
				if ($lgn != '' && !isLANLogin($lgn)) {
					$count++;
				}
			}
			if ($count > 0) {
				$field = 'Masteradmin';
				if ($count > 1)
					$field .= 's';
				foreach ($aseco->masteradmin_list['TMLOGIN'] as $lgn) {
					// skip any LAN logins
					if ($lgn != '' && !isLANLogin($lgn)) {
						$info[] = array($field, '{#black}' . $aseco->getPlayerNick($lgn) . '$z');
						$field = '';
					}
				}
			}
		}
		if ($admin_contact) {
			$info[] = array('Admin Contact', '{#black}' . $admin_contact);
		}

		// display ManiaLink message
		display_manialink($login, $header, array('BgRaceScore2', 'Warmup'), $info, array(1.0, 0.3, 0.7), 'OK');

	} else {  // TMS/TMO
		$info = '{#server}>> XASECO Info' . LF;
		$info .= 'Showing info of ' . $aseco->server->name . LF;
		// no info actually shown

		// show chat message
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($info), $login);
	}
}  // chat_xaseco

function chat_plugins($aseco, $command) {

	$player = $command['author'];

	// display plugins list for TMN
	if ($aseco->server->getGame() == 'TMN') {
		$head = $aseco->formatColors('Currently active plugins:{#black}') . LF;
		$list = '';
		$lines = 0;
		$player->msgs = array();
		$player->msgs[0] = 1;
		// create list of plugins
		foreach ($aseco->plugins as $plugin) {
			$list .= $plugin . LF;
			if (++$lines > 14) {
				$player->msgs[] = $head . $list;
				$lines = 0;
				$list = '';
			}
		}
		// add if last batch exists
		if ($list != '')
			$player->msgs[] = $head . $list;

		// display popup message
		if (count($player->msgs) == 2) {
			$aseco->client->query('SendDisplayServerMessageToLogin', $player->login, $player->msgs[1], 'OK', '', 0);
		} else {  // > 2
			$aseco->client->query('SendDisplayServerMessageToLogin', $player->login, $player->msgs[1], 'Close', 'Next', 0);
		}

	// display plugins list for TMF
	} elseif ($aseco->server->getGame() == 'TMF') {
		$head = 'Currently active plugins:';
		$list = array();
		$lines = 0;
		$player->msgs = array();
		$player->msgs[0] = array(1, $head, array(0.7), array('Icons128x128_1', 'Browse', 0.02));
		// create list of plugins
		foreach ($aseco->plugins as $plugin) {
			$list[] = array('{#black}' . $plugin);
			if (++$lines > 14) {
				$player->msgs[] = $list;
				$lines = 0;
				$list = array();
			}
		}
		// add if last batch exists
		if (!empty($list))
			$player->msgs[] = $list;

		// display ManiaLink message
		display_manialink_multi($player);
	}  // TMO/TMS
}  // chat_plugins

function chat_nations($aseco, $command) {

	if ($aseco->server->getGame() == 'TMN')
		$top = 10;
	elseif ($aseco->server->getGame() == 'TMF')
		$top = 10;
	else  // TMS/TMO
		$top = 4;

	$query = 'SELECT Nation, COUNT(Nation) AS count FROM players GROUP BY Nation ORDER BY count DESC LIMIT ' . $top;
	$res = mysql_query($query);

	// collect and sort nations
	if (mysql_num_rows($res) > 0) {
		$nations = array();
		while ($row = mysql_fetch_row($res)) {
			$nations[$row[0]] = $row[1];
		}
		mysql_free_result($res);
	} else {
		trigger_error('No players/nations found!', E_USER_WARNING);
		mysql_free_result($res);
		return;
	}
	arsort($nations);

	if ($aseco->server->getGame() == 'TMN') {
		$nats = 'TOP 10 Most Visiting Nations:';
		$bgn = '{#black}';  // nation begin
		$end = '$g';  // ... & end colors

		// compile sorted nations
		$i = 1;
		foreach ($nations as $nat => $tot) {
			$nats .= LF . $i++ . '.  ' . $bgn . $nat . $end . ' - ' . $tot;
		}

		// display popup message
		$aseco->client->query('SendDisplayServerMessageToLogin', $command['author']->login, $aseco->formatColors($nats), 'OK', '', 0);

	} elseif ($aseco->server->getGame() == 'TMF') {
		$header = 'TOP 10 Most Visiting Nations:';
		$nats = array();
		$bgn = '{#black}';  // nation begin

		// compile sorted nations
		$i = 1;
		foreach ($nations as $nat => $tot) {
			$nats[] = array($i++ . '.', $bgn . $nat, $tot);
		}

		// display ManiaLink message
		display_manialink($command['author']->login, $header, array('Icons128x128_1', 'Credits'), $nats, array(0.8, 0.1, 0.4, 0.3), 'OK');

	} else {  // TMS/TMO
		$nats = '{#server}> TOP 4 Most Visiting Nations:{#highlite}';
		$bgn = '{#highlite}';
		$end = '{#highlite}';

		// compile sorted nations
		$i = 1;
		foreach ($nations as $nat => $tot) {
			$nats .= LF . $i++ . '.  ' . $bgn . $nat . $end . ' - ' . $tot;
		}

		// show chat message
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($nats), $command['author']->login);
	}
}  // chat_nations
?>
