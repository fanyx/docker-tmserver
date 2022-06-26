<?php
/* vim: set noexpandtab tabstop=2 softtabstop=2 shiftwidth=2: */

/**
 * Chat plugin.
 * Provides Jfreu admin commands.
 * This file is included by jfreu.plugin.php, so don't list it in plugins.xml!
 * Updated by Xymph
 *
 * Dependencies: used by jfreu.plugin.php
 */

// handles action id's "-4001"-"-4200" for /jfreu badword
// handles action id's "-4201"-"-4400" for /jfreu banfor 1H
// handles action id's "-4401"-"-4600" for /jfreu banfor 24H
// handles action id's "-4601"-"-4800" for /jfreu unban
// handles action id's "-4801"-"-5000" for /jfreu addvip
// handles action id's "-5001"-"-5200" for /jfreu removevip
// handles action id's "-5201"-"-5400" for /jfreu unspec
// handles action id's "-5401"-"-5600" for /jfreu unban in listbans
// handles action id's "-5601"-"-5800" for /jfreu removevip in listvips
// handles action id's "-5801"-"-6000" for /jfreu removevipteam in listvipteams
Aseco::registerEvent('onPlayerManialinkPageAnswer', 'event_jfreu');

function init_jfreu_admin_commands($aseco)
{
	addJfreuAdminChatCommand('help', 'Shows Jfreu commands');
	addJfreuAdminChatCommand('helpall', 'Displays help for Jfreu commands');
	addJfreuAdminChatCommand('autochangename', 'Auto change servername {ON/OFF}');
	addJfreuAdminChatCommand('setrank', 'Sets ranklimiting {ON/OFF}');
	addJfreuAdminChatCommand('setlimit', 'Sets ranklimit value');
	addJfreuAdminChatCommand('autorank', 'Sets autoranking {ON/OFF}');
	addJfreuAdminChatCommand('offset', 'Sets autorank offset (-999 - 999)');
	addJfreuAdminChatCommand('hardlimit', 'Sets hardlimit value');
	addJfreuAdminChatCommand('autorankminplayers', 'Sets min players for autorank {0-50}');
	addJfreuAdminChatCommand('autorankvip', 'Include VIPs in autorank {ON/OFF}');
	addJfreuAdminChatCommand('maxplayers', 'Sets maxplayers for Kick HiRank');
	addJfreuAdminChatCommand('kickhirank', 'Kick HiRank when server full {ON/OFF}');
	addJfreuAdminChatCommand('listlimits', 'Displays rank limit settings');
	addJfreuAdminChatCommand('kickworst', 'Kicks worst players {count}');
	addJfreuAdminChatCommand('players', 'Displays list of known players {string}');
	addJfreuAdminChatCommand('unspec', 'UnSpecs player {login/ID} (clear SpecOnly)');
	addJfreuAdminChatCommand('addvip', 'Adds a VIP {login/ID}');
	addJfreuAdminChatCommand('removevip', 'Removes a VIP {login/ID}');
	addJfreuAdminChatCommand('addvipteam', 'Adds a VIP_Team {team}');
	addJfreuAdminChatCommand('removevipteam', 'Removes VIP_Team {team}');
	addJfreuAdminChatCommand('listvips', 'Displays VIPs list');
	addJfreuAdminChatCommand('listvipteams', 'Displays VIP_Teams list');
	addJfreuAdminChatCommand('writelists', 'Saves VIP/VIP_Team lists (def: jfreu.vips.xml)');
	addJfreuAdminChatCommand('readlists', 'Loads VIP/VIP_Team lists (def: jfreu.vips.xml)');
	addJfreuAdminChatCommand('badwords', 'Sets badwords bot {ON/OFF}');
	addJfreuAdminChatCommand('badwordsban', 'Sets badwords ban {ON/OFF}');
	addJfreuAdminChatCommand('badwordsnum', 'Sets badwords limit {count}');
	addJfreuAdminChatCommand('badwordstime', 'Sets banning period {mins}');
	addJfreuAdminChatCommand('badword', 'Gives extra badword warning {login/ID}');
	addJfreuAdminChatCommand('banfor', 'Bans player {mins/hoursH} {login/ID}');
	addJfreuAdminChatCommand('unban', 'UnBans temporarily banned player');
	addJfreuAdminChatCommand('listbans', 'Displays temporarily banned players');
	addJfreuAdminChatCommand('message', 'Fakes message from server {msg}');
	addJfreuAdminChatCommand('player', 'Fakes message from player {login/ID} {msg}');
	addJfreuAdminChatCommand('nopfkick', 'Sets NoPfKick {map/time/OFF}');
	addJfreuAdminChatCommand('cancel', 'Cancels current vote (kick/ban/nextmap/restart)');
	addJfreuAdminChatCommand('novote', 'Auto-cancel CallVotes {ON/OFF}');
	addJfreuAdminChatCommand('unspecvote', 'Allow /unspec votes {ON/OFF}');
	addJfreuAdminChatCommand('infomessages', 'Sets info messages {2/1/0}');
	addJfreuAdminChatCommand('writeconfig', 'Saves Jfreu config (def: jfreu.config.xml)');
	addJfreuAdminChatCommand('readconfig', 'Loads Jfreu config (def: jfreu.config.xml)');
}  // init_jfreu_admin_commands

function chat_jfreu($aseco, $command)
{
	$red = $aseco->server->jfreu->red;
	$yel = $aseco->server->jfreu->yellow;
	$whi = $aseco->server->jfreu->white;
	$blu = $aseco->server->jfreu->blue;
	$gre = $aseco->server->jfreu->green;

	$admin = $command['author'];
	$login = $admin->login;

	// check if chat command was allowed for a masteradmin/admin/operator
	if ($aseco->isMasterAdmin($admin))
	{
		$logtitle = 'MasterAdmin';
		$chattitle = $aseco->titles['MASTERADMIN'][0];
	}
	else
	{
		if ($aseco->isAdmin($admin) && $aseco->allowAdminAbility('chat_jfreu')) {
			$logtitle = 'Admin';
			$chattitle = $aseco->titles['ADMIN'][0];
		}
		else
		{
			if ($aseco->isOperator($admin) && $aseco->allowOpAbility('chat_jfreu')) {
				$logtitle = 'Operator';
				$chattitle = $aseco->titles['OPERATOR'][0];
			}
			else
			{
				// write warning in console
				$aseco->console($login . ' tried to use Jfreu chat command (no permission!): ' . $command['params']);
				// sends chat message
				$aseco->client->query('ChatSendToLogin', $red.'You don\'t have the required admin rights to do that!', $login);
				return false;
			}
		}
	}

	// check for unlocked password
	if ($aseco->settings['lock_password'] != '' && !$admin->unlocked) {
		// write warning in console
		$aseco->console($login . ' tried to use Jfreu chat command (not unlocked!): ' . $command['params']);
		// sends chat message
		$aseco->client->query('ChatSendToLogin', $red.'You don\'t have the required admin rights to do that!', $login);
		return false;
	}

	// split params into arrays & insure optional parameters exist
	$arglist = explode(' ', $command['params'], 2);
	if (!isset($arglist[1])) $arglist[1] = '';
	$command['params'] = explode(' ', preg_replace('/ +/', ' ', $command['params']));
	if (!isset($command['params'][1])) $command['params'][1] = '';

	// Show jfreuAdmin help.
	if ($command['params'][0] == 'help')
	{
		// show Jfreu commands on command line
		showHelp($admin, $aseco->server->jfreu->admin_commands, 'Jfreu admin', true, false);
	}

	// Display jfreuAdmin help.
	elseif ($command['params'][0] == 'helpall')
	{
		// display Jfreu commands in popup with descriptions
		showHelp($admin, $aseco->server->jfreu->admin_commands, 'Jfreu admin', true, true, 0.45);
	}

	// Set autochangename ON/OFF.
	elseif ($command['params'][0] == 'autochangename')
	{
		if ($command['params'][1] != '')
		{
			if (strtoupper($command['params'][1]) == 'ON')
			{
				$aseco->server->jfreu->autochangename = true;
				// log console message
				$aseco->console('{1} [{2}] set autochangename: ON', $logtitle, $login);
				// show chat message
				$message = formatText('{#server}>> {#message}{1}$z$s {#highlite}{2}$z$s{#message} set AutoChangeName: {#highlite}{3}{#message}.',
				                      $chattitle, $admin->nickname, 'ON');
				$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($message));
				write_config_xml($aseco);
			}
			elseif (strtoupper($command['params'][1]) == 'OFF')
			{
				$aseco->server->jfreu->autochangename = false;
				// log console message
				$aseco->console('{1} [{2}] set autochangename: OFF', $logtitle, $login);
				// show chat message
				$message = formatText('{#server}>> {#message}{1}$z$s {#highlite}{2}$z$s{#message} set AutoChangeName: {#highlite}{3}{#message}.',
				                      $chattitle, $admin->nickname, 'OFF');
				$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($message));
				write_config_xml($aseco);
			}
		}
		else
		{
			$message = $yel.'> '.$blu.'AutoChangeName is: '.$whi.
			           ($aseco->server->jfreu->autochangename ? 'ON' : 'OFF').$blu.'.';
			$aseco->client->query('ChatSendServerMessageToLogin', $message, $login);
		}
	}

	// Set ranklimit ON/OFF.
	elseif ($command['params'][0] == 'setrank')
	{
		if ($command['params'][1] != '')
		{
			if (strtoupper($command['params'][1]) == 'ON')
			{
				$aseco->server->jfreu->ranklimit = true;
				// log console message
				$aseco->console('{1} [{2}] set rank limiting: ON', $logtitle, $login);
				// show chat message
				$message = formatText('{#server}>> {#message}{1}$z$s {#highlite}{2}$z$s{#message} set RankLimit: {#highlite}{3}{#message}.',
				                      $chattitle, $admin->nickname, 'ON');
				$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($message));
				write_config_xml($aseco);
				if ($aseco->server->jfreu->autochangename)
				{
					$servername = $aseco->server->jfreu->servername . $aseco->server->jfreu->top . $aseco->server->jfreu->limit;
					$aseco->client->query('SetServerName', $servername);
				}
			}
			elseif (strtoupper($command['params'][1]) == 'OFF')
			{
				$aseco->server->jfreu->ranklimit = false;
				// log console message
				$aseco->console('{1} [{2}] set rank limiting: OFF', $logtitle, $login);
				// show chat message
				$message = formatText('{#server}>> {#message}{1}$z$s {#highlite}{2}$z$s{#message} set RankLimit: {#highlite}{3}{#message}.',
				                      $chattitle, $admin->nickname, 'OFF');
				$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($message));
				write_config_xml($aseco);
				if ($aseco->server->jfreu->autochangename)
				{
					$servername = $aseco->server->jfreu->servername . ' NoLimit';
					$aseco->client->query('SetServerName', $servername);
				}
			}
		}
		else
		{
			$message = $yel.'> '.$blu.'RankLimit is: '.$whi.
			           ($aseco->server->jfreu->ranklimit ? 'ON' : 'OFF').$blu.'.';
			$aseco->client->query('ChatSendServerMessageToLogin', $message, $login);
		}
	}

	// Set ranklimit value.
	elseif ($command['params'][0] == 'setlimit')
	{
		if ($command['params'][1] != '')
		{
			if (is_numeric($command['params'][1]) &&
			    $command['params'][1] > 0 && $command['params'][1] < 2000000)
			{
				if ($aseco->server->jfreu->autorank)
				{
					set_ranklimit($aseco, $command['params'][1], 2);
				}
				else
				{
					set_ranklimit($aseco, $command['params'][1], 0);
				}
				write_config_xml($aseco);
				// log console message
				$aseco->console('{1} [{2}] set (auto)ranklimit: {3}', $logtitle, $login, $command['params'][1]);
			}
			else
			{
				$message = '{#server}> {#highlite}' . $command['params'][1] . '{#error} is not a valid ranklimit value!';
				$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
			}
		}
		else
		{
			$message = $yel.'> '.$blu.'RankLimit value is: '.$whi.$aseco->server->jfreu->limit.$blu.'.';
			$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
		}
	}

	// Set autorank ON/OFF.
	elseif ($command['params'][0] == 'autorank')
	{
		if ($command['params'][1] != '')
		{
			if (strtoupper($command['params'][1]) == 'ON')
			{
				$aseco->server->jfreu->autorank = true;
				// log console message
				$aseco->console('{1} [{2}] set autoranking: ON', $logtitle, $login);
				// show chat message
				$message = formatText('{#server}>> {#message}{1}$z$s {#highlite}{2}$z$s{#message} set AutoRank: {#highlite}{3}{#message}.',
				                      $chattitle, $admin->nickname, 'ON');
				$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($message));
				autorank($aseco, $command);
				write_config_xml($aseco);
			}
			elseif (strtoupper($command['params'][1]) == 'OFF')
			{
				$aseco->server->jfreu->autorank = false;
				// log console message
				$aseco->console('{1} [{2}] set autoranking: OFF', $logtitle, $login);
				// show chat message
				$message = formatText('{#server}>> {#message}{1}$z$s {#highlite}{2}$z$s{#message} set AutoRank: {#highlite}{3}{#message}.',
				                      $chattitle, $admin->nickname, 'OFF');
				$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($message));
				set_ranklimit($aseco, $aseco->server->jfreu->limit, 0);
				write_config_xml($aseco);
			}
		}
		else
		{
			$message = $yel.'> '.$blu.'AutoRank is: '.$whi.
			           ($aseco->server->jfreu->autorank ? 'ON' : 'OFF').$blu.'.';
			$aseco->client->query('ChatSendServerMessageToLogin', $message, $login);
		}
	}

	// Set offset value.
	elseif ($command['params'][0] == 'offset')
	{
		if ($command['params'][1] != '')
		{
			if (is_numeric($command['params'][1]) &&
			    $command['params'][1] > -1000 && $command['params'][1] < 1000)
			{
				$aseco->server->jfreu->offset = $command['params'][1];
				// log console message
				$aseco->console('{1} [{2}] set autorank offset: {3}', $logtitle, $login, $aseco->server->jfreu->offset);
				// show chat message
				$message = formatText('{#server}>> {#message}{1}$z$s {#highlite}{2}$z$s{#message} set AutoRank Offset to: {#highlite}{3}{#message}.',
				                      $chattitle, $admin->nickname, $aseco->server->jfreu->offset);
				$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($message));
				autorank($aseco, $command);
				write_config_xml($aseco);
			}
			else
			{
				$message = '{#server}> {#highlite}' . $command['params'][1] . '{#error} is not a valid offset value!';
				$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
			}
		}
		else
		{
			$message = $yel.'> '.$blu.'AutoRank Offset value is: '.$whi.$aseco->server->jfreu->offset.$blu.'.';
			$aseco->client->query('ChatSendServerMessageToLogin', $message, $login);
		}
	}

	// Set hardlimit value.
	elseif ($command['params'][0] == 'hardlimit')
	{
		if ($command['params'][1] != '')
		{
			if (is_numeric($command['params'][1]) &&
			    $command['params'][1] >= 0 && $command['params'][1] < 2000000)
			{
				$aseco->server->jfreu->hardlimit = $command['params'][1];
				// log console message
				$aseco->console('{1} [{2}] set hardlimit: {3}', $logtitle, $login, $aseco->server->jfreu->hardlimit);
				// show chat message
				$message = formatText('{#server}>> {#message}{1}$z$s {#highlite}{2}$z$s{#message} set HardLimit to: {#highlite}{3}{#message}.',
				                      $chattitle, $admin->nickname, $aseco->server->jfreu->hardlimit);
				$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($message));
				write_config_xml($aseco);
			}
			else
			{
				$message = '{#server}> {#highlite}' . $command['params'][1] . '{#error} is not a valid hardlimit value!';
				$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
			}
		}
		elseif ($aseco->server->jfreu->hardlimit > 0)
		{
			$message = $yel.'> '.$blu.'HardLimit value is: '.$whi.$aseco->server->jfreu->hardlimit.$blu.'.';
			$aseco->client->query('ChatSendServerMessageToLogin', $message, $login);
		}
		else
		{
			$message = $yel.'> '.$blu.'HardLimit is: '.$whi.'OFF'.$blu.'.';
			$aseco->client->query('ChatSendServerMessageToLogin', $message, $login);
		}
	}

	// Set autorankminplayers value.
	elseif ($command['params'][0] == 'autorankminplayers')
	{
		if ($command['params'][1] != '')
		{
			if (is_numeric($command['params'][1]) &&
			    $command['params'][1] >= 0 && $command['params'][1] < 50)
			{
				$aseco->server->jfreu->autorankminplayers = $command['params'][1];
				// log console message
				$aseco->console('{1} [{2}] set autorankminplayers: {3}', $logtitle, $login, $aseco->server->jfreu->autorankminplayers);
				// show chat message
				$message = formatText('{#server}>> {#message}{1}$z$s {#highlite}{2}$z$s{#message} set AutoRankMinPlayer to: {#highlite}{3}{#message}.',
				                      $chattitle, $admin->nickname, $aseco->server->jfreu->autorankminplayers);
				$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($message));
				autorank($aseco, $command);
				write_config_xml($aseco);
			}
			else
			{
				$message = '{#server}> {#highlite}' . $command['params'][1] . '{#error} is not a valid minplayers value!';
				$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
			}
		}
		else
		{
			$message = $yel.'> '.$blu.'AutoRankMinPlayer value is: '.$whi.$aseco->server->jfreu->autorankminplayers.$blu.'.';
			$aseco->client->query('ChatSendServerMessageToLogin', $message, $login);
		}
	}

	// Set autorankvip ON/OFF.
	elseif ($command['params'][0] == 'autorankvip')
	{
		if ($command['params'][1] != '')
		{
			if (strtoupper($command['params'][1]) == 'ON')
			{
				$aseco->server->jfreu->autorankvip = true;
				// log console message
				$aseco->console('{1} [{2}] set autorankvip: ON', $logtitle, $login);
				// show chat message
				$message = formatText('{#server}>> {#message}{1}$z$s {#highlite}{2}$z$s{#message} set AutoRankVIP: {#highlite}{3}{#message}.',
				                      $chattitle, $admin->nickname, 'ON');
				$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($message));
				write_config_xml($aseco);
			}
			elseif (strtoupper($command['params'][1]) == 'OFF')
			{
				$aseco->server->jfreu->autorankvip = false;
				// log console message
				$aseco->console('{1} [{2}] set autorankvip: OFF', $logtitle, $login);
				// show chat message
				$message = formatText('{#server}>> {#message}{1}$z$s {#highlite}{2}$z$s{#message} set AutoRankVIP: {#highlite}{3}{#message}.',
				                      $chattitle, $admin->nickname, 'OFF');
				$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($message));
				write_config_xml($aseco);
			}
		}
		else
		{
			$message = $yel.'> '.$blu.'AutoRankVIP is: '.$whi.
			           ($aseco->server->jfreu->autorankvip ? 'ON' : 'OFF').$blu.'.';
			$aseco->client->query('ChatSendServerMessageToLogin', $message, $login);
		}
	}

	// Set maxplayers value.
	elseif ($command['params'][0] == 'maxplayers')
	{
		if ($command['params'][1] != '')
		{
			if ($command['params'][1] >= 0 && $command['params'][1] < 150)
			{
				$aseco->server->jfreu->maxplayers = $command['params'][1];
				// log console message
				$aseco->console('{1} [{2}] set maxplayers: {3}', $logtitle, $login, $aseco->server->jfreu->maxplayers);
				// show chat message
				$message = formatText('{#server}>> {#message}{1}$z$s {#highlite}{2}$z$s{#message} set MaxPlayers to: {#highlite}{3}{#message}.',
				                      $chattitle, $admin->nickname, $aseco->server->jfreu->maxplayers);
				$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($message));
				write_config_xml($aseco);
			}
			else
			{
				$message = '{#server}> {#highlite}' . $command['params'][1] . '{#error} is not a valid maxplayers value!';
				$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
			}

		}
		else
		{
			$message = $yel.'> '.$blu.'MaxPlayers value is: '.$whi.$aseco->server->jfreu->maxplayers.$blu.'.';
			$aseco->client->query('ChatSendServerMessageToLogin', $message, $login);
		}
	}

	// Set kickhirank ON/OFF.
	elseif ($command['params'][0] == 'kickhirank')
	{
		if ($command['params'][1] != '')
		{
			if (strtoupper($command['params'][1]) == 'ON')
			{
				$aseco->server->jfreu->kickhirank = true;
				// log console message
				$aseco->console('{1} [{2}] set kickhirank: ON', $logtitle, $login);
				// show chat message
				$message = formatText('{#server}>> {#message}{1}$z$s {#highlite}{2}$z$s{#message} set KickHiRank: {#highlite}{3}{#message}.',
				                      $chattitle, $admin->nickname, 'ON');
				$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($message));
				write_config_xml($aseco);
			}
			elseif (strtoupper($command['params'][1]) == 'OFF')
			{
				$aseco->server->jfreu->kickhirank = false;
				// log console message
				$aseco->console('{1} [{2}] set kickhirank: OFF', $logtitle, $login);
				// show chat message
				$message = formatText('{#server}>> {#message}{1}$z$s {#highlite}{2}$z$s{#message} set KickHiRank: {#highlite}{3}{#message}.',
				                      $chattitle, $admin->nickname, 'OFF');
				$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($message));
				write_config_xml($aseco);
			}
		}
		else
		{
			$message = $yel.'> '.$blu.'KickHiRank is: '.$whi.
			           ($aseco->server->jfreu->kickhirank ? 'ON' : 'OFF').$blu.'.';
			$aseco->client->query('ChatSendServerMessageToLogin', $message, $login);
		}
	}

	// Displays rank limit settings
	elseif ($command['params'][0] == 'listlimits')
	{
		if ($aseco->server->getGame() == 'TMN') {
			$limits = 'Current rank limit settings:' . LF;
			$limits .= '  $gRank limiting            : {#black}' . ($aseco->server->jfreu->ranklimit ? 'ON' : 'OFF') . LF;
			$limits .= '  $gRank limit                 : {#black}' . $aseco->server->jfreu->limit . LF;
			$limits .= '  $gHard limit                  : {#black}' . $aseco->server->jfreu->hardlimit . LF;
			$limits .= '  $gAuto ranking            : {#black}' . ($aseco->server->jfreu->autorank ? 'ON' : 'OFF') . LF;
			$limits .= '  $gAutorank offset       : {#black}' . $aseco->server->jfreu->offset . LF;
			$limits .= '  $gAutorank limit           : {#black}' . $aseco->server->jfreu->autolimit . LF;
			$limits .= '  $gAutorank minplayers: {#black}' . $aseco->server->jfreu->autorankminplayers . LF;
			$limits .= '  $gAutorank VIP            : {#black}' . ($aseco->server->jfreu->autorankvip ? 'ON' : 'OFF') . LF;
			$limits .= '  $gMaxplayers HiRank  : {#black}' . $aseco->server->jfreu->maxplayers . LF;
			$limits .= '  $gKickHiRank               : {#black}' . ($aseco->server->jfreu->kickhirank ? 'ON' : 'OFF');
			// display popup message
			$aseco->client->query('SendDisplayServerMessageToLogin', $login, $aseco->formatColors($limits), 'OK', '', 0);

		} elseif ($aseco->server->getGame() == 'TMF') {
			$header = 'Current rank limit settings:';
			$limits = array();
			$limits[] = array('Rank limiting', '{#black}' . ($aseco->server->jfreu->ranklimit ? 'ON' : 'OFF'));
			$limits[] = array('Rank limit', '{#black}' . $aseco->server->jfreu->limit);
			$limits[] = array('Hard limit', '{#black}' . $aseco->server->jfreu->hardlimit);
			$limits[] = array('Auto ranking', '{#black}' . ($aseco->server->jfreu->autorank ? 'ON' : 'OFF'));
			$limits[] = array('Autorank offset', '{#black}' . $aseco->server->jfreu->offset);
			$limits[] = array('Autorank limit', '{#black}' . $aseco->server->jfreu->autolimit);
			$limits[] = array('Autorank minplayers', '{#black}' . $aseco->server->jfreu->autorankminplayers);
			$limits[] = array('Autorank VIP', '{#black}' . ($aseco->server->jfreu->autorankvip ? 'ON' : 'OFF'));
			$limits[] = array('Maxplayers HiRank', '{#black}' . $aseco->server->jfreu->maxplayers);
			$limits[] = array('KickHiRank', '{#black}' . ($aseco->server->jfreu->kickhirank ? 'ON' : 'OFF'));
			// display ManiaLink message
			display_manialink($login, $header, array('Icons128x128_1', 'ProfileAdvanced', 0.02), $limits, array(0.8, 0.4, 0.4), 'OK');
		}
	}

	// KickWorst (kick X worst players)
	elseif ($command['params'][0] == 'kickworst' && $command['params'][1] != '')
	{
		if (is_numeric($command['params'][1]) &&
		    $command['params'][1] > 0 && $command['params'][1] < 50)
		{
			$message = formatText('{#server}>> {#message}{1}$z$s {#highlite}{2}$z$s{#message} kicks the {#highlite}{3}{#message} worst ranked player{4}.',
			                      $chattitle, $admin->nickname, $command['params'][1],
			                      ($command['params'][1] == 1 ? '' : 's'));
			$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($message));
			kick_worst($aseco, $command['params'][1]);
		}
		else
		{
			$message = '{#server}> {#highlite}' . $command['params'][1] . '{#error} is not a valid kickworst value!';
			$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
		}
	}

	// Players (display players list)
	elseif ($command['params'][0] == 'players')
	{
		$admin->playerlist = array();
		$admin->msgs = array();

		// remember players parameter for possible refresh
		$admin->panels['plyparam'] = $command['params'][1];
		$onlineonly = (strtolower($command['params'][1]) == 'live');

		// create new list of online players
		$aseco->client->resetError();
		$onlinelist = array();
		// get current players on the server (hardlimited to 300)
		if ($aseco->server->getGame() == 'TMF')
			$aseco->client->query('GetPlayerList', 300, 0, 1);
		else
			$aseco->client->query('GetPlayerList', 300, 0);
		$players = $aseco->client->getResponse();
		if ($aseco->client->isError()) {
			trigger_error('[' . $aseco->client->getErrorCode() . '] GetPlayerList - ' . $aseco->client->getErrorMessage(), E_USER_WARNING);
		} else {
			foreach ($players as $pl)
				if ($aseco->server->getGame() == 'TMF') {
					// on relay, check for player from master server
					if (!$aseco->server->isrelay || floor($pl['Flags'] / 10000) % 10 == 0)
						$onlinelist[$pl['Login']] = array('login' => $pl['Login'],
						                                  'spec' => $pl['SpectatorStatus']);
				} else {
					$onlinelist[$pl['Login']] = array('login' => $pl['Login'],
					                                  'spec' => $pl['IsSpectator']);
				}
		}

		$playerlist = array();
		// use online list?
		if ($onlineonly) {
			foreach ($aseco->server->jfreu->playerlist as $pl => $entry) {
				if (array_key_exists($pl, $onlinelist))
					$playerlist[$pl] = $entry;
			}
		} else {
			// search for known logins
			foreach ($aseco->server->jfreu->playerlist as $pl => $entry) {
				if ($command['params'][1] == '' || stripos($pl, $command['params'][1]) !== false)
					$playerlist[$pl] = $entry;
			}
			// append vip list
			foreach ($aseco->server->jfreu->vip_list as $pl) {
				// check if vip is not yet in the new list and matches search
				if ($pl != '' && !array_key_exists($pl, $playerlist) &&
				    ($command['params'][1] == '' || stripos($pl, $command['params'][1]) !== false)) {
					ajouter_joueur_liste($aseco, $pl, true, false);
					$playerlist[$pl] = $aseco->server->jfreu->playerlist[$pl];
				}
			}
		}

		if (!empty($playerlist)) {
			if ($aseco->server->getGame() == 'TMN') {
				$head = ($onlineonly ? 'Online' : 'Known') . ' Players On This Server:' . LF .
				         'Id     ' . ($aseco->server->jfreu->badwords ? 'Bw  ' : '') .
				         '{#nick}Nick $g/{#login} Login' . LF;
				$msg = '';
				$pid = 1;
				$lines = 0;
				$admin->msgs[0] = 1;
				foreach ($playerlist as $pl => $entry) {
					$plarr = array();
					$plarr['login'] = $pl;
					$admin->playerlist[] = $plarr;

					// check for badwords filtering
					$msg .= '$g' . str_pad($pid, 2, '0', STR_PAD_LEFT) . '.   '
					        . ($aseco->server->jfreu->badwords ? $entry->badwords . '    ' : '')
					        . '{#black}' . str_ireplace('$w', '', $aseco->getPlayerNick($pl))
					        . '$z / ' . ($aseco->isAnyAdminL($pl) ? '{#logina}' : '{#login}') . $pl . LF;
					$pid++;
					if (++$lines > 9) {
						$admin->msgs[] = $aseco->formatColors($head . $msg);
						$lines = 0;
						$msg = '';
					}
				}
				// add if last batch exists
				if ($msg != '')
					$admin->msgs[] = $aseco->formatColors($head . $msg);

				// display popup message
				if (count($admin->msgs) == 2) {
					$aseco->client->query('SendDisplayServerMessageToLogin', $login, $admin->msgs[1], 'OK', '', 0);
				} elseif (count($admin->msgs) > 2) {
					$aseco->client->query('SendDisplayServerMessageToLogin', $login, $admin->msgs[1], 'Close', 'Next', 0);
				} else {  // == 1
					$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors('{#server}> {#error}No player(s) found!'), $login);
				}

			} elseif ($aseco->server->getGame() == 'TMF') {
				$head = ($onlineonly ? 'Online' : 'Known') . ' Players On This Server:';
				$msg = array();
				// check for badwords filtering
				if ($aseco->server->jfreu->badwords) {
					$msg[] = array('Id', '{#nick}Nick $g/{#login} Login', '$nCount', 'BadW', 'Ban', 'Ban', 'Left', 'UnBan', 'VIP', 'Spec');
					$admin->msgs[0] = array(1, $head, array(1.591, 0.15, 0.5, 0.10, 0.12, 0.12, 0.12, 0.12, 0.121, 0.12, 0.12), array('Icons128x128_1', 'Buddies'));
				} else {
					$msg[] = array('Id', '{#nick}Nick $g/{#login} Login', 'Ban', 'Ban', 'Left', 'UnBan', 'VIP', 'Spec');
					$admin->msgs[0] = array(1, $head, array(1.371, 0.15, 0.5, 0.12, 0.12, 0.12, 0.121, 0.12, 0.12), array('Icons128x128_1', 'Buddies'));
				}
				$pid = 1;
				$lines = 0;
				$time = time();
				foreach ($playerlist as $pl => $entry) {
					$plarr = array();
					$plarr['login'] = $pl;
					$admin->playerlist[] = $plarr;

					// format nickname & login
					$ply = '{#black}' . str_ireplace('$w', '', $aseco->getPlayerNick($pl))
					       . '$z / ' . ($aseco->isAnyAdminL($pl) ? '{#logina}' : '{#login}') . $pl;
					// define colored column strings
					$bdw = '$ff3+1';
					$bn1 = '$f301Hour';
					$bn2 = '$f0324H';
					$ubn = '$c3fUnBan';
					$gst = '$3c3Add';
					$ugt = '$393Remove';
					$usp = '$09fUnSpec';
					$off = '$09cOffln';
					$plr = '$09cPlayer';
					$spc = '$09cSpec';

					// check whether temporarily banned
					if ($entry->banned > $time) {
						$remain = round(($entry->banned - $time) / 60);  // compute mins
						if ($remain > 60) {  // check for >1 hour
							$remain = sprintf('%dh%02d', $remain / 60, $remain % 60);
						}
					} else {
						$remain = false;
					}

					// always add clickable buttons
					if ($pid <= 200) {
						$ply = array($ply, $pid+2000);
						if (array_key_exists($pl, $onlinelist)) {
							$bdw = array($bdw,   -4000-$pid);
							$bn1 = array($bn1,   -4200-$pid);
							$bn2 = array($bn2,   -4400-$pid);
							if ($remain !== false)
								$ubn = array($ubn, -4600-$pid);
							if (in_array($pl, $aseco->server->jfreu->vip_list))
								$gst = array($ugt, -5000-$pid);
							else
								$gst = array($gst, -4800-$pid);
							if ($entry->speconly)
								$spc = array($usp, -5200-$pid);
							elseif (!$onlinelist[$pl]['spec'])
								$spc = $plr;
						} else {
							// determine offline operations
							$bn1 = array($bn1,   -4200-$pid);
							$bn2 = array($bn2,   -4400-$pid);
							if ($remain !== false)
								$ubn = array($ubn, -4600-$pid);
							if (in_array($pl, $aseco->server->jfreu->vip_list))
								$gst = array($ugt, -5000-$pid);
							else
								$gst = array($gst, -4800-$pid);
							$spc = $off;
						}
					} else {
						// no more buttons
						if (in_array($pl, $aseco->server->jfreu->vip_list))
							$gst = $ugt;
						if (array_key_exists($pl, $onlinelist)) {
							if ($entry->speconly)
								$spc = $usp;
							elseif (!$onlinelist[$pl]['spec'])
								$spc = $plr;
						} else {
							$spc = $off;
						}
					}

					// check for badwords filtering
					if ($aseco->server->jfreu->badwords)
						$msg[] = array(str_pad($pid, 2, '0', STR_PAD_LEFT) . '.', $ply,
						               $entry->badwords, $bdw, $bn1, $bn2,
						               ($remain !== false ? $remain : 'none'),
						               $ubn, $gst, $spc);
					else
						$msg[] = array(str_pad($pid, 2, '0', STR_PAD_LEFT) . '.', $ply,
						               $bn1, $bn2,
						               ($remain !== false ? $remain : 'none'),
						               $ubn, $gst, $spc);
					$pid++;
					if (++$lines > 14) {
						$admin->msgs[] = $msg;
						$lines = 0;
						$msg = array();
						if ($aseco->server->jfreu->badwords)
							$msg[] = array('Id', '{#nick}Nick $g/{#login} Login', '$nCount', 'BadW', 'Ban', 'Ban', 'Left', 'UnBan', 'VIP', 'Spec');
						else
							$msg[] = array('Id', '{#nick}Nick $g/{#login} Login', 'Ban', 'Ban', 'Left', 'UnBan', 'VIP', 'Spec');
					}
				}
				// add if last batch exists
				if (count($msg) > 1)
					$admin->msgs[] = $msg;

				// display ManiaLink message
				if (count($admin->msgs) > 1) {
					display_manialink_multi($admin);
				} else {  // == 1
					$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors('{#server}> {#error}No player(s) found!'), $login);
				}
			}
		} else {
			$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors('{#server}> {#error}No player(s) found!'), $login);
		}
	}

	// Unspec (unspec a player)
	elseif ($command['params'][0] == 'unspec' && $command['params'][1] != '')
	{
		if (!$target = $aseco->getPlayerParam($admin, $command['params'][1]))
			return;

		if (isset($aseco->server->jfreu->playerlist[$target->login]) &&
		    $aseco->server->jfreu->playerlist[$target->login]->speconly)
		{
			$aseco->server->jfreu->playerlist[$target->login]->speconly = false;
			$aseco->server->jfreu->playerlist[$target->login]->isvip = true;
			// log console message
			$aseco->console('{1} [{2}] unSpec-ed [{3}]', $logtitle, $login, $target->login);
			// show chat message
			$message = formatText('{#server}>> {#message}{1}$z$s {#highlite}{2}$z$s{#message} unSpecs {#highlite}{3}{#message}.',
			                      $chattitle, $admin->nickname, clean_nick($target->nickname));
			$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($message));
			if ($aseco->server->jfreu->autorank)
			{
				autorank($aseco, $command);
			}
		}
		else
		{
			$message = $yel.'> '.$red.'Login:  '.$whi.$target->login.$red.' is not SpecOnly!';
			$aseco->client->query('ChatSendServerMessageToLogin', $message, $login);
		}
	}

	// Add VIP (permanent !!)
	elseif ($command['params'][0] == 'addvip' && $command['params'][1] != '')
	{
		if (!$target = $aseco->getPlayerParam($admin, $command['params'][1]))
			return;

		if (in_array($target->login, $aseco->server->jfreu->vip_list))
		{
			$message = $yel.'> '.$blu.'Login: '.$whi.$target->login.$blu.' is already in VIP list.';
			$aseco->client->query('ChatSendServerMessageToLogin', $message, $login);
			return;
		}
		$aseco->server->jfreu->vip_list[] = $target->login;
		write_lists_xml($aseco);
		// log console message
		$aseco->console('{1} [{2}] adds VIP [{3}]', $logtitle, $login, $target->login);
		// show chat message
		$message = $yel.'>> '.$blu.'New VIP: '.$whi.$target->login.$blu.' / '.$whi.clean_nick($target->nickname).'.';
		$aseco->client->query('ChatSendServerMessage', $message);
		// mark player as VIP
		$aseco->server->jfreu->playerlist[$target->login]->isvip = true;
	}

	// Remove VIP (permanent !!)
	elseif ($command['params'][0] == 'removevip' && $command['params'][1] != '')
	{
		if (!$target = $aseco->getPlayerParam($admin, $command['params'][1], true))
			return;

		if (($i = array_search($target->login, $aseco->server->jfreu->vip_list)) === false)
		{
			$message = $yel.'> '.$blu.'Login: '.$whi.$target->login.$blu.' is not in VIP list.';
			$aseco->client->query('ChatSendServerMessageToLogin', $message, $login);
			return;
		}
		$aseco->server->jfreu->vip_list[$i] = '';
		write_lists_xml($aseco);
		// log console message
		$aseco->console('{1} [{2}] removes VIP [{3}]', $logtitle, $login, $target->login);
		// show chat message
		$message = $yel.'>> '.$blu.'Login: '.$whi.$target->login.$blu.' removed from VIP list.';
		$aseco->client->query('ChatSendServerMessage', $message);
		// mark player as non-VIP
		if (isset($aseco->server->jfreu->playerlist[$target->login]))
		{
			$aseco->server->jfreu->playerlist[$target->login]->isvip = false;
		}
	}

	// Add VIP_Team (permanent !!)
	elseif ($command['params'][0] == 'addvipteam' && $command['params'][1] != '')
	{
		$team = $command['params'][1];
		if (in_array($team, $aseco->server->jfreu->vip_team_list))
		{
			$message = $yel.'> '.$blu.'Team: '.$whi.$team.$blu.' is already in VIP_Team list.';
			$aseco->client->query('ChatSendServerMessageToLogin', $message, $login);
			return;
		}
		$aseco->server->jfreu->vip_team_list[] = $team;
		write_lists_xml($aseco);
		// log console message
		$aseco->console('{1} [{2}] adds VIP_Team [{3}]', $logtitle, $login, $team);
		// show chat message
		$message = $yel.'>> '.$blu.'New VIP_Team: '.$whi.$team.$blu.'.';
		$aseco->client->query('ChatSendServerMessage', $message);
	}

	// Remove VIP_Team (permanent !!)
	elseif ($command['params'][0] == 'removevipteam' && $command['params'][1] != '')
	{
		$team = $command['params'][1];
		if (($i = array_search($team, $aseco->server->jfreu->vip_team_list)) === false)
		{
			$message = $yel.'> '.$blu.'Team: '.$whi.$team.$blu.' is not in VIP_Team list.';
			$aseco->client->query('ChatSendServerMessageToLogin', $message, $login);
			return;
		}
		$aseco->server->jfreu->vip_team_list[$i] = '';
		write_lists_xml($aseco);
		// log console message
		$aseco->console('{1} [{2}] removes VIP_Team [{3}]', $logtitle, $login, $team);
		// show chat message
		$message = $yel.'>> '.$blu.'Team: '.$whi.$team.$blu.' removed from VIP_Team list.';
		$aseco->client->query('ChatSendServerMessage', $message);
	}

	// Display VIPs list
	elseif ($command['params'][0] == 'listvips')
	{
		$admin->playerlist = array();
		$admin->msgs = array();

		if ($aseco->server->getGame() == 'TMN') {
			$head = 'Current VIPs:' . LF . 'Id     {#nick}Nick $g/{#login} Login' . LF;
			$msg = '';
			$pid = 1;
			$lines = 0;
			$admin->msgs[0] = 1;
			foreach ($aseco->server->jfreu->vip_list as $player) {
				if ($player != '') {
					$plarr = array();
					$plarr['login'] = $player;
					$admin->playerlist[] = $plarr;

					$msg .= '$g' . str_pad($pid, 2, '0', STR_PAD_LEFT) . '.   {#black}'
					        . $aseco->getPlayerNick($player) . '$z / {#login}' . $player . LF;
					$pid++;
					if (++$lines > 9) {
						$admin->msgs[] = $aseco->formatColors($head . $msg);
						$lines = 0;
						$msg = '';
					}
				}
			}
			// add if last batch exists
			if ($msg != '')
				$admin->msgs[] = $aseco->formatColors($head . $msg);

			// display popup message
			if (count($admin->msgs) == 2) {
				$aseco->client->query('SendDisplayServerMessageToLogin', $login, $admin->msgs[1], 'OK', '', 0);
			} elseif (count($admin->msgs) > 2) {
				$aseco->client->query('SendDisplayServerMessageToLogin', $login, $admin->msgs[1], 'Close', 'Next', 0);
			} else {  // == 1
				$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors('{#server}> {#error}No VIP(s) found!'), $login);
			}

		} elseif ($aseco->server->getGame() == 'TMF') {
			$head = 'Current VIPs:';
			$msg = array();
			if ($aseco->settings['clickable_lists'])
				$msg[] = array('Id', '{#nick}Nick $g/{#login} Login$g (click to Remove)');
			else
				$msg[] = array('Id', '{#nick}Nick $g/{#login} Login');
			$pid = 1;
			$lines = 0;
			$admin->msgs[0] = array(1, $head, array(0.9, 0.1, 0.8), array('Icons128x128_1', 'Invite'));
			foreach ($aseco->server->jfreu->vip_list as $player) {
				if ($player != '') {
					$plarr = array();
					$plarr['login'] = $player;
					$admin->playerlist[] = $plarr;

					// format nickname & login
					$ply = '{#black}' . str_ireplace('$w', '', $aseco->getPlayerNick($player))
					       . '$z / {#login}' . $player;
					if ($aseco->settings['clickable_lists'])
						$ply = array($ply, -5600-$pid);  // action id  // action id
					$msg[] = array(str_pad($pid, 2, '0', STR_PAD_LEFT) . '.', $ply);
					$pid++;
					if (++$lines > 14) {
						$admin->msgs[] = $msg;
						$lines = 0;
						$msg = array();
						if ($aseco->settings['clickable_lists'])
							$msg[] = array('Id', '{#nick}Nick $g/{#login} Login$g (click to Remove)');
						else
							$msg[] = array('Id', '{#nick}Nick $g/{#login} Login');
					}
				}
			}
			// add if last batch exists
			if (count($msg) > 1)
				$admin->msgs[] = $msg;

			// display ManiaLink message
			if (count($admin->msgs) > 1) {
				display_manialink_multi($admin);
			} else {  // == 1
				$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors('{#server}> {#error}No VIP(s) found!'), $login);
			}
		}
	}

	// Display VIP_Teams list
	elseif ($command['params'][0] == 'listvipteams')
	{
		$admin->playerlist = array();
		$admin->msgs = array();

		if ($aseco->server->getGame() == 'TMN') {
			$head = 'Current VIP_Teams:' . LF . 'Id     {#nick}Team' . LF;
			$msg = '';
			$pid = 1;
			$lines = 0;
			$admin->msgs[0] = 1;
			foreach ($aseco->server->jfreu->vip_team_list as $team) {
				if ($team != '') {
					$plarr = array();
					$plarr['login'] = $team;
					$admin->playerlist[] = $plarr;

					$msg .= '$z' . str_pad($pid, 2, '0', STR_PAD_LEFT) . '.   {#black}'
					        . $team . LF;
					$pid++;
					if (++$lines > 9) {
						$admin->msgs[] = $aseco->formatColors($head . $msg);
						$lines = 0;
						$msg = '';
					}
				}
			}
			// add if last batch exists
			if ($msg != '')
				$admin->msgs[] = $aseco->formatColors($head . $msg);

			// display popup message
			if (count($admin->msgs) == 2) {
				$aseco->client->query('SendDisplayServerMessageToLogin', $login, $admin->msgs[1], 'OK', '', 0);
			} elseif (count($admin->msgs) > 2) {
				$aseco->client->query('SendDisplayServerMessageToLogin', $login, $admin->msgs[1], 'Close', 'Next', 0);
			} else {  // == 1
				$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors('{#server}> {#error}No VIP_Team(s) found!'), $login);
			}

		} elseif ($aseco->server->getGame() == 'TMF') {
			$head = 'Current VIP_Teams:';
			$msg = array();
			if ($aseco->settings['clickable_lists'])
				$msg[] = array('Id', '{#nick}Team$g (click to Remove)');
			else
				$msg[] = array('Id', '{#nick}Team');
			$pid = 1;
			$lines = 0;
			$admin->msgs[0] = array(1, $head, array(0.8, 0.1, 0.7), array('Icons128x128_1', 'Invite'));
			foreach ($aseco->server->jfreu->vip_team_list as $team) {
				if ($team != '') {
					$plarr = array();
					$plarr['login'] = $team;
					$admin->playerlist[] = $plarr;

					$msg[] = array(str_pad($pid, 2, '0', STR_PAD_LEFT) . '.',
					               ($aseco->settings['clickable_lists'] ?
					                array('{#black}' . $team, -5800-$pid) :  // action id
					                '{#black}' . $team));
					$pid++;
					if (++$lines > 14) {
						$admin->msgs[] = $msg;
						$lines = 0;
						$msg = array();
						if ($aseco->settings['clickable_lists'])
							$msg[] = array('Id', '{#nick}Team$g (click to Remove)');
						else
							$msg[] = array('Id', '{#nick}Team');
					}
				}
			}
			// add if last batch exists
			if (count($msg) > 1)
				$admin->msgs[] = $msg;

			// display ManiaLink message
			if (count($admin->msgs) > 1) {
				display_manialink_multi($admin);
			} else {  // == 1
				$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors('{#server}> {#error}No VIP_Team(s) found!'), $login);
			}
		}
	}

	// Saves vip/team lists
	elseif ($command['params'][0] == 'writelists')
	{
		write_lists_xml($aseco);
		$message = $yel.'> '.$whi.'Jfreu lists'.$yel.' written.';
		$aseco->client->query('ChatSendServerMessageToLogin', $message, $login);
	}

	// Loads vip/team lists
	elseif ($command['params'][0] == 'readlists')
	{
		$aseco->server->jfreu->vip_list = array();
		$aseco->server->jfreu->vip_team_list = array();
		read_lists_xml($aseco);
		read_guest_list($aseco);

		$message = $yel.'> '.$whi.'Jfreu lists'.$yel.' read.';
		$aseco->client->query('ChatSendServerMessageToLogin', $message, $login);
	}

	// Set badwords ON/OFF.
	elseif ($command['params'][0] == 'badwords')
	{
		if ($command['params'][1] != '')
		{
			if (strtoupper($command['params'][1]) == 'ON')
			{
				$aseco->server->jfreu->badwords = true;
				// log console message
				$aseco->console('{1} [{2}] set badwords bot: ON', $logtitle, $login);
				// show chat message
				$message = formatText('{#server}>> {#message}{1}$z$s {#highlite}{2}$z$s{#message} set BadWords bot: {#highlite}{3}{#message}.',
				                      $chattitle, $admin->nickname, 'ON');
				$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($message));
				write_config_xml($aseco);
			}
			elseif (strtoupper($command['params'][1]) == 'OFF')
			{
				$aseco->server->jfreu->badwords = false;
				// log console message
				$aseco->console('{1} [{2}] set badwords bot: OFF', $logtitle, $login);
				// show chat message
				$message = formatText('{#server}>> {#message}{1}$z$s {#highlite}{2}$z$s{#message} set BadWords bot: {#highlite}{3}{#message}.',
				                      $chattitle, $admin->nickname, 'OFF');
				$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($message));
				write_config_xml($aseco);
			}
		}
		else
		{
			$message = $yel.'> '.$blu.'BadWords is: '.$whi.
			           ($aseco->server->jfreu->badwords ? 'ON' : 'OFF').$blu.'.';
			$aseco->client->query('ChatSendServerMessageToLogin', $message, $login);
		}
	}

	// Set badwordsban ON/OFF.
	elseif ($command['params'][0] == 'badwordsban')
	{
		if ($command['params'][1] != '')
		{
			if (strtoupper($command['params'][1]) == 'ON')
			{
				$aseco->server->jfreu->badwordsban = true;
				// log console message
				$aseco->console('{1} [{2}] set badwords ban: ON', $logtitle, $login);
				// show chat message

				$message = formatText('{#server}>> {#message}{1}$z$s {#highlite}{2}$z$s{#message} set BadWordsBan: {#highlite}{3}{#message}.',
				                      $chattitle, $admin->nickname, 'ON');
				$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($message));
				write_config_xml($aseco);
			}
			elseif (strtoupper($command['params'][1]) == 'OFF')
			{
				$aseco->server->jfreu->badwordsban = false;
				// log console message
				$aseco->console('{1} [{2}] set badwords ban: ON', $logtitle, $login);
				// show chat message
				$message = formatText('{#server}>> {#message}{1}$z$s {#highlite}{2}$z$s{#message} set BadWordsBan: {#highlite}{3}{#message}.',
				                      $chattitle, $admin->nickname, 'OFF');
				$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($message));
				write_config_xml($aseco);
			}
		}
		else
		{
			$message = $yel.'> '.$blu.'BadWordsBan is: '.$whi.
			           ($aseco->server->jfreu->badwordsban ? 'ON' : 'OFF').$blu.'.';
			$aseco->client->query('ChatSendServerMessageToLogin', $message, $login);
		}
	}

	// Set badwordsnum value.
	elseif ($command['params'][0] == 'badwordsnum')
	{
		if ($command['params'][1] != '' && is_numeric($command['params'][1]))
		{
			if ($command['params'][1] > 0 && $command['params'][1] < 10)
			{
				$aseco->server->jfreu->badwordsnum = $command['params'][1];
				// log console message
				$aseco->console('{1} [{2}] set badwordsnum: {3}', $logtitle, $login, $aseco->server->jfreu->badwordsnum);
				// show chat message
				$message = formatText('{#server}>> {#message}{1}$z$s {#highlite}{2}$z$s{#message} set BadWordsNum to: {#highlite}{3}{#message}.',
				                      $chattitle, $admin->nickname, $aseco->server->jfreu->badwordsnum);
				$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($message));
				write_config_xml($aseco);
			}
		}
		else
		{
			$message = $yel.'> '.$blu.'BadWordsNum value is: '.$whi.$aseco->server->jfreu->badwordsnum.$blu.'.';
			$aseco->client->query('ChatSendServerMessageToLogin', $message, $login);
		}
	}

	// Set badwordstime value.
	elseif ($command['params'][0] == 'badwordstime')
	{
		if ($command['params'][1] != '' && is_numeric($command['params'][1]))
		{
			if ($command['params'][1] > 0 && $command['params'][1] <= 24 * 60)  // 1 day
			{
				$aseco->server->jfreu->badwordstime = $command['params'][1];
				// log console message
				$aseco->console('{1} [{2}] set badwordstime: {3}', $logtitle, $login, $aseco->server->jfreu->badwordstime);
				// show chat message
				$message = formatText('{#server}>> {#message}{1}$z$s {#highlite}{2}$z$s{#message} set BadWordsTime to: {#highlite}{3}{#message} mins.',
				                      $chattitle, $admin->nickname, $aseco->server->jfreu->badwordstime);
				$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($message));
				write_config_xml($aseco);
			}
		}
		else
		{
			$message = $yel.'> '.$blu.'BadWordsTime value is: '.$whi.$aseco->server->jfreu->badwordstime.$blu.' mins.';
			$aseco->client->query('ChatSendServerMessageToLogin', $message, $login);
		}
	}

	// Give extra badword warning {login/ID}
	elseif ($command['params'][0] == 'badword' && $command['params'][1] != '')
	{
		if (!$target = $aseco->getPlayerParam($admin, $command['params'][1]))
			return;

		badword_found($target->login, $target->nickname, '');
	}

	// Ban player temporarily (/jfreu banfor <mins>/<hours>H <login>)
	elseif ($command['params'][0] == 'banfor' && $command['params'][1] != '' && isset($command['params'][2]))
	{
		if (!$target = $aseco->getPlayerParam($admin, $command['params'][2], true))
			return;

		// check time parameter
		$time = $command['params'][1];
		if (strtolower(substr($time, -1)) == 'h') {
			$time = substr($time, 0, -1);
			if (is_numeric($time)) {
				$time *= 60;  // convert to hours
			} else {
				$message = $yel.'> '.$whi.$command['params'][1].$red.' is not a valid time!';
				$aseco->client->query('ChatSendServerMessageToLogin', $message, $login);
				return;
			}
		}
		elseif (!is_numeric($time) || $time <= 0) {
			$message = $yel.'> '.$whi.$command['params'][1].$red.' is not a valid time!';
			$aseco->client->query('ChatSendServerMessageToLogin', $message, $login);
			return;
		}

		// format time value
		if ($time > 60) {  // check for >1 hour
			$ban = sprintf('%d {#message}hour%s {#highlite}%02d {#message}min%s',
			               $time / 60, (floor($time / 60) == 1 ? '' : 's'),
			               $time % 60, (($time % 60) == 1 ? '' : 's'));
		} else {
			$ban = sprintf('%d {#message}min%s', $time, ($time == 1 ? '' : 's'));
		}

		// show chat message
		$message = formatText('{#server}>> {#message}{1}$z$s {#highlite}{2}$z$s{#message} bans {#highlite}{3}{#message} for {#highlite}{4}.',
		                      $chattitle, $admin->nickname,
		                      clean_nick($target->nickname), $ban);
		$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($message));
		banfor($aseco, $target->login, $time);
	}

	// UnBans temporarily banned player
	elseif ($command['params'][0] == 'unban')
	{
		if (!$target = $aseco->getPlayerParam($admin, $command['params'][1], true))
			return;

		if (isset($aseco->server->jfreu->playerlist[$target->login])) {
			if ($aseco->server->jfreu->playerlist[$target->login]->banned > 0) {
				$aseco->server->jfreu->playerlist[$target->login]->banned = 0;
				$message = formatText('{#server}>> {#message}{1}$z$s {#highlite}{2}$z$s{#message} unbans {#highlite}{3}{#message}.',
				                      $chattitle, $admin->nickname,
				                      clean_nick($target->nickname));
				$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($message));
				write_bans_xml($aseco);
				return;
			}
		}
		$message = $yel.'> '.$whi.$target->login.$red.' is not a banned player!';
		$aseco->client->query('ChatSendServerMessageToLogin', $message, $login);
	}

	// Displays temporarily banned players
	elseif ($command['params'][0] == 'listbans')
	{
		$admin->playerlist = array();
		$admin->msgs = array();

		if ($aseco->server->getGame() == 'TMN') {
			$head = 'Temporarily Banned Players:' . LF . 'Id     {#nick}Nick $g/{#login} Login $g/{#black} Time' . LF;
			$msg = '';
			$pid = 1;
			$lines = 0;
			$admin->msgs[0] = 1;
			$time = time();
			foreach ($aseco->server->jfreu->playerlist as $player => $entry) {
				// check for banned players
				if ($entry->banned > $time) {
					$plarr = array();
					$plarr['login'] = $player;
					$admin->playerlist[] = $plarr;

					$remain = round(($entry->banned - $time) / 60);  // compute mins
					if ($remain > 60) {  // check for >1 hour
						$remain = sprintf('%dh%02d', $remain / 60, $remain % 60);
					}
					$msg .= '$g' . str_pad($pid, 2, '0', STR_PAD_LEFT) . '.   {#black}'
					        . str_ireplace('$w', '', $aseco->getPlayerNick($player))
					        . '$z / {#login}' . $player . '$g / {#black}' . $remain . LF;
					$pid++;
					if (++$lines > 9) {
						$admin->msgs[] = $aseco->formatColors($head . $msg);
						$lines = 0;
						$msg = '';
					}
				}
			}
			// add if last batch exists
			if ($msg != '')
				$admin->msgs[] = $aseco->formatColors($head . $msg);

			// display popup message
			if (count($admin->msgs) == 2) {
				$aseco->client->query('SendDisplayServerMessageToLogin', $login, $admin->msgs[1], 'OK', '', 0);
			} elseif (count($admin->msgs) > 2) {
				$aseco->client->query('SendDisplayServerMessageToLogin', $login, $admin->msgs[1], 'Close', 'Next', 0);
			} else {  // == 1
				$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors('{#server}> {#error}No player(s) found!'), $login);
			}

		} elseif ($aseco->server->getGame() == 'TMF') {
			$head = 'Temporarily Banned Players:';
			$msg = array();
			if ($aseco->settings['clickable_lists'])
				$msg[] = array('Id', '{#nick}Nick $g/{#login} Login$g (click to UnBan)', '{#black}Time');
			else
				$msg[] = array('Id', '{#nick}Nick $g/{#login} Login', '{#black}Time');
			$pid = 1;
			$lines = 0;
			$admin->msgs[0] = array(1, $head, array(1.1, 0.1, 0.8, 0.2), array('Icons64x64_1', 'NotBuddy'));
			$time = time();
			foreach ($aseco->server->jfreu->playerlist as $player => $entry) {
				// check for banned players
				if ($entry->banned > $time) {
					$plarr = array();
					$plarr['login'] = $player;
					$admin->playerlist[] = $plarr;

					$remain = round(($entry->banned - $time) / 60);  // compute mins
					if ($remain > 60) {  // check for >1 hour
						$remain = sprintf('%dh%02d', $remain / 60, $remain % 60);
					}
					// format nickname & login
					$ply = '{#black}' . str_ireplace('$w', '', $aseco->getPlayerNick($player))
					       . '$z / {#login}' . $player;
					if ($aseco->settings['clickable_lists'])
						$ply = array($ply, -5400-$pid);  // action id
					$msg[] = array(str_pad($pid, 2, '0', STR_PAD_LEFT) . '.', $ply,
					               '{#black}' . $remain);
					$pid++;
					if (++$lines > 14) {
						$admin->msgs[] = $msg;
						$lines = 0;
						$msg = array();
						if ($aseco->settings['clickable_lists'])
							$msg[] = array('Id', '{#nick}Nick $g/{#login} Login$g (click to UnBan)', '{#black}Time');
						else
							$msg[] = array('Id', '{#nick}Nick $g/{#login} Login', '{#black}Time');
					}
				}
			}
			// add if last batch exists
			if (count($msg) > 1)
				$admin->msgs[] = $msg;

			// display ManiaLink message
			if (count($admin->msgs) > 1) {
				display_manialink_multi($admin);
			} else {  // == 1
				$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors('{#server}> {#error}No player(s) found!'), $login);
			}
		}
	}

	// Message from Server (/jfreu message <message>)
	elseif ($command['params'][0] == 'message' && $command['params'][1] != '')
	{
		$message = $whi.'['.$aseco->server->name.$whi.'] $z$s' . $arglist[1];
		$aseco->client->query('ChatSendServerMessage', $message);
	}

	// Message from Player (/jfreu player <login> <message>)
	elseif ($command['params'][0] == 'player' && $command['params'][1] != '')
	{
		if (!$target = $aseco->getPlayerParam($admin, $command['params'][1]))
			return;

		$text = explode(' ', $arglist[1], 2);
		// don't use $s as a subtle hint this isn't the actual player saying it
		$message = '$z['.$target->nickname.'$z] '.$text[1];
		$aseco->client->query('ChatSendServerMessage', $message);
	}

	// Set PF (BAD BAD BAD) {map} or {time}/OFF.
	elseif ($command['params'][0] == 'nopfkick')
	{
		if ($command['params'][1] != '')
		{
			if (strtoupper($command['params'][1]) == 'OFF' ||
			    $command['params'][1] == '0')
			{
				$aseco->server->jfreu->pf = 0;
				$message = formatText('{#server}>> {#message}{1}$z$s {#highlite}{2}$z$s{#message} set NoPfKick: {#highlite}{3}{#message}.',
				                      $chattitle, $admin->nickname, 'OFF');
				$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($message));
				write_config_xml($aseco);
			}
			elseif (isset($aseco->server->jfreu->pf_list[$command['params'][1]]))
			{
				$aseco->server->jfreu->pf = $aseco->server->jfreu->pf_list[$command['params'][1]];
				$message = formatText('{#server}>> {#message}{1}$z$s {#highlite}{2}$z$s{#message} set NoPfKick on {#highlite}{3}{#message} map.',
				                      $chattitle, $admin->nickname, $command['params'][1]);
				$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($message));
				write_config_xml($aseco);
			}
			elseif (is_numeric($command['params'][1]) &&
			        $command['params'][1] > 0 && $command['params'][1] < 600000)
			{
				$aseco->server->jfreu->pf = $command['params'][1];
				$message = formatText('{#server}>> {#message}{1}$z$s {#highlite}{2}$z$s{#message} set NoPfKick time: {#highlite}{3}{#message}.',
				                      $chattitle, $admin->nickname, $command['params'][1]);
				$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($message));
				write_config_xml($aseco);
			}
			else
			{
				$message = $yel.'> '.$blu.'Map '.$whi.$command['params'][1].$blu.' is not in PF list.';
				$aseco->client->query('ChatSendServerMessageToLogin', $message, $login);
			}
			// log console message
			$aseco->console('{1} [{2}] set NoPfKick: {3}', $logtitle, $login, $aseco->server->jfreu->pf);
		}
		else
		{
			$message = $yel.'> '.$blu.'NoPfKick is: '.$whi.
			           ($aseco->server->jfreu->pf == 0 ? 'OFF' : 'ON').$blu.'.';
			$aseco->client->query('ChatSendServerMessageToLogin', $message, $login);
		}
	}

	// Cancel vote - redundant with /admin cancel
	elseif ($command['params'][0] == 'cancel')
	{
		global $feature_votes;  // from rasp.settings.php

		// disabled if chat-based votes are enabled
		if (!$feature_votes)
		{
			$aseco->client->query('CancelVote');
			$message = $yel.'>> '.$blu.'Vote canceled.';
			$aseco->client->query('ChatSendServerMessage', $message);
		}
	}

	// Set novote ON/OFF.
	elseif ($command['params'][0] == 'novote')
	{
		if ($command['params'][1] != '')
		{
			if (strtoupper($command['params'][1]) == 'ON')
			{
				$aseco->server->jfreu->novote = true;
				// log console message
				$aseco->console('{1} [{2}] set novote: ON', $logtitle, $login);
				// show chat message
				$message = formatText('{#server}>> {#message}{1}$z$s {#highlite}{2}$z$s{#message} set NoVote: {#highlite}{3}{#message}.',
				                      $chattitle, $admin->nickname, 'ON');
				$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($message));
				write_config_xml($aseco);
			}
			elseif (strtoupper($command['params'][1]) == 'OFF')
			{
				$aseco->server->jfreu->novote = false;
				// log console message
				$aseco->console('{1} [{2}] set novote: OFF', $logtitle, $login);
				// show chat message
				$message = formatText('{#server}>> {#message}{1}$z$s {#highlite}{2}$z$s{#message} set NoVote: {#highlite}{3}{#message}.',
				                      $chattitle, $admin->nickname, 'OFF');
				$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($message));
				write_config_xml($aseco);
			}
		}
		else
		{
			$message = $yel.'> '.$blu.'NoVote is: '.$whi.
			           ($aseco->server->jfreu->novote ? 'ON' : 'OFF').$blu.'.';
			$aseco->client->query('ChatSendServerMessageToLogin', $message, $login);
		}
	}

	// Set unspecvote ON/OFF.
	elseif ($command['params'][0] == 'unspecvote')
	{
		if ($command['params'][1] != '')
		{
			if (strtoupper($command['params'][1]) == 'ON')
			{
				$aseco->server->jfreu->unspecvote = true;
				// log console message
				$aseco->console('{1} [{2}] set /unspec vote: ON', $logtitle, $login);
				// show chat message
				$message = formatText('{#server}>> {#message}{1}$z$s {#highlite}{2}$z$s{#message} set UnSpecVote: {#highlite}{3}{#message}.',
				                      $chattitle, $admin->nickname, 'ON');
				$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($message));
				write_config_xml($aseco);
			}
			elseif (strtoupper($command['params'][1]) == 'OFF')
			{
				$aseco->server->jfreu->unspecvote = false;
				// log console message
				$aseco->console('{1} [{2}] set /unspec vote: OFF', $logtitle, $login);
				// show chat message
				$message = formatText('{#server}>> {#message}{1}$z$s {#highlite}{2}$z$s{#message} set UnSpecVote: {#highlite}{3}{#message}.',
				                      $chattitle, $admin->nickname, 'OFF');
				$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($message));
				write_config_xml($aseco);
			}
		}
		else
		{
			$message = $yel.'> '.$blu.'UnSpecVote is: '.$whi.
			           ($aseco->server->jfreu->unspecvote ? 'ON' : 'OFF').$blu.'.';
			$aseco->client->query('ChatSendServerMessageToLogin', $message, $login);
		}
	}

	// Set infomessages ON/OFF.
	elseif ($command['params'][0] == 'infomessages')
	{
		if ($command['params'][1] != '')
		{
			if ($command['params'][1] >= 0 && $command['params'][1] <= 2)
			{
				$aseco->server->jfreu->infomessages = $command['params'][1];
				// log console message
				$aseco->console('{1} [{2}] set info messages: {3}', $logtitle, $login, $command['params'][1]);
				// show chat message
				$message = formatText('{#server}>> {#message}{1}$z$s {#highlite}{2}$z$s{#message} set InfoMessages: {#highlite}{3}{#message}.',
				                      $chattitle, $admin->nickname,
				                      $command['params'][1]);
				$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($message));
				write_config_xml($aseco);
			}
			else
			{
				$message = '{#server}> {#highlite}' . $command['params'][1] . '{#error} is not a valid infomessages value!';
				$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
			}
		}
		else
		{
			$message = $yel.'> '.$blu.'InfoMessages is: '.$whi.
			           $aseco->server->jfreu->infomessages .$blu.'.';
			$aseco->client->query('ChatSendServerMessageToLogin', $message, $login);
		}
	}

	// Saves Jfreu config
	elseif ($command['params'][0] == 'writeconfig')
	{
		write_config_xml($aseco);
		$message = $yel.'> '.$whi.'Jfreu config'.$yel.' written.';
		$aseco->client->query('ChatSendServerMessageToLogin', $message, $login);
	}

	// Loads Jfreu config
	elseif ($command['params'][0] == 'readconfig')
	{
		read_config_xml($aseco);
		$message = $yel.'> '.$whi.'Jfreu config'.$yel.' read.  Servername: '.$whi . $aseco->getServerName();
		$aseco->client->query('ChatSendServerMessageToLogin', $message, $login);
	}

	else
	{
		$message = '{#server}> {#error}Unknown Jfreu command or missing parameter(s): {#highlite}$i ' . $arglist[0] . ' ' . $arglist[1];
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
	}
}  // chat_jfreu


// called @ onPlayerManialinkPageAnswer
// Handles ManiaLink jfreu responses
// [0]=PlayerUid, [1]=Login, [2]=Answer
function event_jfreu($aseco, $answer) {

	// leave actions outside -5600 - -4001 to other handlers
	if ($answer[2] < -5600 && $answer[2] > -4001)
		return;

	// get player & possible parameter
	$player = $aseco->server->players->getPlayer($answer[1]);
	if (isset($player->panels['plyparam']))
		$param = $player->panels['plyparam'];

	// check for /jfreu badword command
	if ($answer[2] >= -4200 && $answer[2] <= -4001) {
		$target = $player->playerlist[abs($answer[2])-4001]['login'];

		// log clicked command
		$aseco->console('player {1} clicked command "/jfreu badword {2}"',
		                $player->login, $target);

		// badword selected player
		$command = array();
		$command['author'] = $player;
		$command['params'] = 'badword ' . $target;
		chat_jfreu($aseco, $command);

		// log clicked command
		$aseco->console('player {1} clicked command "/jfreu players {2}"',
		                $player->login, $param);

		// refresh players window
		$command['params'] = 'players ' . $param;
		chat_jfreu($aseco, $command);
	}

	// check for /jfreu banfor 1H command
	elseif ($answer[2] >= -4400 && $answer[2] <= -4201) {
		$target = $player->playerlist[abs($answer[2])-4201]['login'];

		// log clicked command
		$aseco->console('player {1} clicked command "/jfreu banfor 1H {2}"',
		                $player->login, $target);

		// banfor 1H selected player
		$command = array();
		$command['author'] = $player;
		$command['params'] = 'banfor 1H ' . $target;
		chat_jfreu($aseco, $command);

		// log clicked command
		$aseco->console('player {1} clicked command "/jfreu players {2}"',
		                $player->login, $param);

		// refresh players window
		$command['params'] = 'players ' . $param;
		chat_jfreu($aseco, $command);
	}

	// check for /jfreu banfor 24H command
	elseif ($answer[2] >= -4600 && $answer[2] <= -4401) {
		$target = $player->playerlist[abs($answer[2])-4401]['login'];

		// log clicked command
		$aseco->console('player {1} clicked command "/jfreu banfor 24H {2}"',
		                $player->login, $target);

		// banfor 24H selected player
		$command = array();
		$command['author'] = $player;
		$command['params'] = 'banfor 24H ' . $target;
		chat_jfreu($aseco, $command);

		// log clicked command
		$aseco->console('player {1} clicked command "/jfreu players {2}"',
		                $player->login, $param);

		// refresh players window
		$command['params'] = 'players ' . $param;
		chat_jfreu($aseco, $command);
	}

	// check for /jfreu unban command
	elseif ($answer[2] >= -4800 && $answer[2] <= -4601) {
		$target = $player->playerlist[abs($answer[2])-4601]['login'];

		// log clicked command
		$aseco->console('player {1} clicked command "/jfreu unban {2}"',
		                $player->login, $target);

		// unban selected player
		$command = array();
		$command['author'] = $player;
		$command['params'] = 'unban ' . $target;
		chat_jfreu($aseco, $command);

		// log clicked command
		$aseco->console('player {1} clicked command "/jfreu players {2}"',
		                $player->login, $param);

		// refresh players window
		$command['params'] = 'players ' . $param;
		chat_jfreu($aseco, $command);
	}

	// check for /jfreu addvip command
	elseif ($answer[2] >= -5000 && $answer[2] <= -4801) {
		$target = $player->playerlist[abs($answer[2])-4801]['login'];

		// log clicked command
		$aseco->console('player {1} clicked command "/jfreu addvip {2}"',
		                $player->login, $target);

		// addvip selected player
		$command = array();
		$command['author'] = $player;
		$command['params'] = 'addvip ' . $target;
		chat_jfreu($aseco, $command);

		// log clicked command
		$aseco->console('player {1} clicked command "/jfreu players {2}"',
		                $player->login, $param);

		// refresh players window
		$command['params'] = 'players ' . $param;
		chat_jfreu($aseco, $command);
	}

	// check for /jfreu removevip command
	elseif ($answer[2] >= -5200 && $answer[2] <= -5001) {
		$target = $player->playerlist[abs($answer[2])-5001]['login'];

		// log clicked command
		$aseco->console('player {1} clicked command "/jfreu removevip {2}"',
		                $player->login, $target);

		// removevip selected player
		$command = array();
		$command['author'] = $player;
		$command['params'] = 'removevip ' . $target;
		chat_jfreu($aseco, $command);

		// log clicked command
		$aseco->console('player {1} clicked command "/jfreu players {2}"',
		                $player->login, $param);

		// refresh players window
		$command['params'] = 'players ' . $param;
		chat_jfreu($aseco, $command);
	}

	// check for /jfreu unspec command
	elseif ($answer[2] >= -5400 && $answer[2] <= -5201) {
		$target = $player->playerlist[abs($answer[2])-5201]['login'];

		// log clicked command
		$aseco->console('player {1} clicked command "/jfreu unspec {2}"',
		                $player->login, $target);

		// unspec selected player
		$command = array();
		$command['author'] = $player;
		$command['params'] = 'unspec ' . $target;
		chat_jfreu($aseco, $command);

		// log clicked command
		$aseco->console('player {1} clicked command "/jfreu players {2}"',
		                $player->login, $param);

		// refresh players window
		$command['params'] = 'players ' . $param;
		chat_jfreu($aseco, $command);
	}

	// check for /jfreu unban command in listbans
	elseif ($answer[2] >= -5600 && $answer[2] <= -5401) {
		$target = $player->playerlist[abs($answer[2])-5401]['login'];

		// log clicked command
		$aseco->console('player {1} clicked command "/jfreu unban {2}"',
		                $player->login, $target);

		// unban selected player
		$command = array();
		$command['author'] = $player;
		$command['params'] = 'unban ' . $target;
		chat_jfreu($aseco, $command);

		// check whether last player was unbanned
		$bansleft = false;
		$time = time();
		foreach ($aseco->server->jfreu->playerlist as $entry) {
			if ($entry->banned > $time) {
				$bansleft = true;
				break;
			}
		}

		if (!$bansleft) {
			// close main window
			mainwindow_off($aseco, $player->login);
		} else {
			// log clicked command
			$aseco->console('player {1} clicked command "/jfreu listbans"',
			                $player->login);

			// refresh listbans window
			$command['params'] = 'listbans';
			chat_jfreu($aseco, $command);
		}
	}

	// check for /jfreu removevip command in listvips
	elseif ($answer[2] >= -5800 && $answer[2] <= -5601) {
		$target = $player->playerlist[abs($answer[2])-5601]['login'];

		// log clicked command
		$aseco->console('player {1} clicked command "/jfreu removevip {2}"',
		                $player->login, $target);

		// removevip selected player
		$command = array();
		$command['author'] = $player;
		$command['params'] = 'removevip ' . $target;
		chat_jfreu($aseco, $command);

		// check whether last vip was removed
		$vipsleft = false;
		foreach ($aseco->server->jfreu->vip_list as $lg) {
			if ($lg != '') {
				$vipsleft = true;
				break;
			}
		}

		if (!$vipsleft) {
			// close main window
			mainwindow_off($aseco, $player->login);
		} else {
			// log clicked command
			$aseco->console('player {1} clicked command "/jfreu listvips"',
			                $player->login);

			// refresh listvips window
			$command['params'] = 'listvips';
			chat_jfreu($aseco, $command);
		}
	}

	// check for /jfreu removevipteam command in listvipteams
	elseif ($answer[2] >= -6000 && $answer[2] <= -5801) {
		$target = $player->playerlist[abs($answer[2])-5801]['login'];

		// log clicked command
		$aseco->console('player {1} clicked command "/jfreu removevipteam {2}"',
		                $player->login, $target);

		// removevip selected player
		$command = array();
		$command['author'] = $player;
		$command['params'] = 'removevipteam ' . $target;
		chat_jfreu($aseco, $command);

		// check whether last vipteam was removed
		$teamsleft = false;
		foreach ($aseco->server->jfreu->vip_team_list as $tm) {
			if ($tm != '') {
				$teamsleft = true;
				break;
			}
		}

		if (!$teamsleft) {
			// close main window
			mainwindow_off($aseco, $player->login);
		} else {
			// log clicked command
			$aseco->console('player {1} clicked command "/jfreu listvipteams"',
			                $player->login);

			// refresh listvips window
			$command['params'] = 'listvipteams';
			chat_jfreu($aseco, $command);
		}
	}
}  // event_jfreu
?>
