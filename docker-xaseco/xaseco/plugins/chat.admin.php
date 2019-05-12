<?php
/* vim: set noexpandtab tabstop=2 softtabstop=2 shiftwidth=2: */

/**
 * Chat plugin.
 * Provides regular admin commands.
 * Updated by Xymph
 *
 * Dependencies: requires plugin.rasp_jukebox.php, plugin.rasp_votes.php, plugin.uptodate.php
 *               uses plugin.autotime.php, plugin.donate.php, plugin.panels.php, plugin.rpoints.php
 *               used by plugin.matchsave.php
 */

// these cannot be included in aseco.php because of their events registration
require_once('includes/rasp.funcs.php');  // functions for the RASP plugins
require_once('includes/manialinks.inc.php');  // provides ManiaLinks windows

// handles action id's "2201"-"2400" for /admin warn
// handles action id's "2401"-"2600" for /admin ignore
// handles action id's "2601"-"2800" for /admin unignore
// handles action id's "2801"-"3000" for /admin kick
// handles action id's "3001"-"3200" for /admin ban
// handles action id's "3201"-"3400" for /admin unban
// handles action id's "3401"-"3600" for /admin black
// handles action id's "3601"-"3800" for /admin unblack
// handles action id's "3801"-"4000" for /admin addguest
// handles action id's "4001"-"4200" for /admin removeguest
// handles action id's "4201"-"4400" for /admin forcespec
// handles action id's "4401"-"4600" for /admin unignore in listignores
// handles action id's "4601"-"4800" for /admin unban in listbans
// handles action id's "4801"-"5000" for /admin unblack in listblacks
// handles action id's "5001"-"5200" for /admin removeguest in listguests
// handles action id's "-7901"-"-8100" for /admin unbanip
Aseco::registerEvent('onPlayerManialinkPageAnswer', 'event_admin');

Aseco::addChatCommand('admin', 'Provides admin commands (see: /admin help)');
if (ABBREV_COMMANDS) {
	Aseco::addChatCommand('ad', 'Provides admin commands (see: /ad help)');
	function chat_ad($aseco, $command) { chat_admin($aseco, $command); }
}
Aseco::addChatCommand('help', 'Shows all available /admin commands', true);
Aseco::addChatCommand('helpall', 'Displays help for available /admin commands', true);
Aseco::addChatCommand('setservername', 'Changes the name of the server', true);
Aseco::addChatCommand('setcomment', 'Changes the server comment', true);
Aseco::addChatCommand('setpwd', 'Changes the player password', true);
Aseco::addChatCommand('setspecpwd', 'Changes the spectator password', true);
Aseco::addChatCommand('setrefpwd', 'Changes the referee password', true);
Aseco::addChatCommand('setmaxplayers', 'Sets a new maximum of players', true);
Aseco::addChatCommand('setmaxspecs', 'Sets a new maximum of spectators', true);
Aseco::addChatCommand('setgamemode', 'Sets next mode {ta,rounds,team,laps,stunts,cup}', true);
Aseco::addChatCommand('setrefmode', 'Sets referee mode {0=top3,1=all}', true);
Aseco::addChatCommand('nextmap/next', 'Forces server to load next track', true);
Aseco::addChatCommand('skipmap/skip', 'Forces server to load next track', true);
Aseco::addChatCommand('previous/prev', 'Forces server to load previous track', true);
Aseco::addChatCommand('nextenv', 'Loads next track in same environment', true);
Aseco::addChatCommand('restartmap/res', 'Restarts currently running track', true);
Aseco::addChatCommand('replaymap/replay', 'Replays current track (via jukebox)', true);
Aseco::addChatCommand('dropjukebox/djb', 'Drops a track from the jukebox', true);
Aseco::addChatCommand('clearjukebox/cjb', 'Clears the entire jukebox', true);
Aseco::addChatCommand('clearhist', 'Clears (part of) track history', true);
Aseco::addChatCommand('add', 'Adds tracks directly from TMX (<ID>... {sec})', true);
Aseco::addChatCommand('addthis', 'Adds current /add-ed track permanently', true);
Aseco::addChatCommand('addlocal', 'Adds a local track (<filename>)', true);
Aseco::addChatCommand('warn', 'Sends a kick/ban warning to a player', true);
Aseco::addChatCommand('kick', 'Kicks a player from server', true);
Aseco::addChatCommand('kickghost', 'Kicks a ghost player from server', true);
Aseco::addChatCommand('ban', 'Bans a player from server', true);
Aseco::addChatCommand('unban', 'UnBans a player from server', true);
Aseco::addChatCommand('banip', 'Bans an IP address from server', true);
Aseco::addChatCommand('unbanip', 'UnBans an IP address from server', true);
Aseco::addChatCommand('black', 'Blacklists a player from server', true);
Aseco::addChatCommand('unblack', 'UnBlacklists a player from server', true);
Aseco::addChatCommand('addguest', 'Adds a guest player to server', true);
Aseco::addChatCommand('removeguest', 'Removes a guest player from server', true);
Aseco::addChatCommand('pass', 'Passes a chat-based or TMX /add vote', true);
Aseco::addChatCommand('cancel/can', 'Cancels any running vote', true);
Aseco::addChatCommand('endround/er', 'Forces end of current round', true);
Aseco::addChatCommand('players', 'Displays list of known players {string}', true);
Aseco::addChatCommand('showbanlist/listbans', 'Displays current ban list', true);
Aseco::addChatCommand('showiplist/listips', 'Displays current banned IPs list', true);
Aseco::addChatCommand('showblacklist/listblacks', 'Displays current black list', true);
Aseco::addChatCommand('showguestlist/listguests', 'Displays current guest list', true);
Aseco::addChatCommand('writeiplist', 'Saves current banned IPs list (def: bannedips.xml)', true);
Aseco::addChatCommand('readiplist', 'Loads current banned IPs list (def: bannedips.xml)', true);
Aseco::addChatCommand('writeblacklist', 'Saves current black list (def: blacklist.txt)', true);
Aseco::addChatCommand('readblacklist', 'Loads current black list (def: blacklist.txt)', true);
Aseco::addChatCommand('writeguestlist', 'Saves current guest list (def: guestlist.txt)', true);
Aseco::addChatCommand('readguestlist', 'Loads current guest list (def: guestlist.txt)', true);
Aseco::addChatCommand('cleanbanlist', 'Cleans current ban list', true);
Aseco::addChatCommand('cleaniplist', 'Cleans current banned IPs list', true);
Aseco::addChatCommand('cleanblacklist', 'Cleans current black list', true);
Aseco::addChatCommand('cleanguestlist', 'Cleans current guest list', true);
Aseco::addChatCommand('mergegbl', 'Merges a global black list {URL}', true);
Aseco::addChatCommand('access', 'Handles player access control (see: /admin access help)', true);
Aseco::addChatCommand('writetracklist', 'Saves current track list (def: tracklist.txt)', true);
Aseco::addChatCommand('readtracklist', 'Loads current track list (def: tracklist.txt)', true);
Aseco::addChatCommand('shuffle/shufflemaps', 'Randomizes current track list', true);
Aseco::addChatCommand('listdupes', 'Displays list of duplicate tracks', true);
Aseco::addChatCommand('remove', 'Removes a track from rotation', true);
Aseco::addChatCommand('erase', 'Removes a track from rotation & deletes track file', true);
Aseco::addChatCommand('removethis', 'Removes this track from rotation', true);
Aseco::addChatCommand('erasethis', 'Removes this track from rotation & deletes track file', true);
Aseco::addChatCommand('mute/ignore', 'Adds a player to global mute/ignore list', true);
Aseco::addChatCommand('unmute/unignore', 'Removes a player from global mute/ignore list', true);
Aseco::addChatCommand('mutelist/listmutes', 'Displays global mute/ignore list', true);
Aseco::addChatCommand('ignorelist/listignores', 'Displays global mute/ignore list', true);
Aseco::addChatCommand('cleanmutes/cleanignores', 'Cleans global mute/ignore list', true);
Aseco::addChatCommand('addadmin', 'Adds a new admin', true);
Aseco::addChatCommand('removeadmin', 'Removes an admin', true);
Aseco::addChatCommand('addop', 'Adds a new operator', true);
Aseco::addChatCommand('removeop', 'Removes an operator', true);
Aseco::addChatCommand('listmasters', 'Displays current masteradmin list', true);
Aseco::addChatCommand('listadmins', 'Displays current admin list', true);
Aseco::addChatCommand('listops', 'Displays current operator list', true);
Aseco::addChatCommand('adminability', 'Shows/changes admin ability {ON/OFF}', true);
Aseco::addChatCommand('opability', 'Shows/changes operator ability {ON/OFF}', true);
Aseco::addChatCommand('listabilities', 'Displays current abilities list', true);
Aseco::addChatCommand('writeabilities', 'Saves current abilities list (def: adminops.xml)', true);
Aseco::addChatCommand('readabilities', 'Loads current abilities list (def: adminops.xml)', true);
Aseco::addChatCommand('wall/mta', 'Displays popup message to all players', true);
Aseco::addChatCommand('delrec', 'Deletes specific record on current track', true);
Aseco::addChatCommand('prunerecs', 'Deletes records for specified track', true);
Aseco::addChatCommand('rpoints', 'Sets custom Rounds points (see: /admin rpoints help)', true);
Aseco::addChatCommand('match', '{begin/end} to start/stop match tracking', true);
Aseco::addChatCommand('acdl', 'Sets AllowChallengeDownload {ON/OFF}', true);
Aseco::addChatCommand('autotime', 'Sets Auto TimeLimit {ON/OFF}', true);
Aseco::addChatCommand('disablerespawn', 'Disables respawn at CPs {ON/OFF}', true);
Aseco::addChatCommand('forceshowopp', 'Forces to show opponents {##/ALL/OFF}', true);
Aseco::addChatCommand('scorepanel', 'Shows automatic scorepanel {ON/OFF}', true);
Aseco::addChatCommand('roundsfinish', 'Shows rounds panel upon first finish {ON/OFF}', true);
Aseco::addChatCommand('forceteam', 'Forces player into {Blue} or {Red} team', true);
Aseco::addChatCommand('forcespec', 'Forces player into free spectator', true);
Aseco::addChatCommand('specfree', 'Forces spectator into free mode', true);
Aseco::addChatCommand('panel', 'Selects admin panel (see: /admin panel help)', true);
Aseco::addChatCommand('style', 'Selects default window style', true);
Aseco::addChatCommand('admpanel', 'Selects default admin panel', true);
Aseco::addChatCommand('donpanel', 'Selects default donate panel', true);
Aseco::addChatCommand('recpanel', 'Selects default records panel', true);
Aseco::addChatCommand('votepanel', 'Selects default vote panel', true);
Aseco::addChatCommand('coppers', 'Shows server\'s coppers amount', true);
Aseco::addChatCommand('pay', 'Pays server coppers to login', true);
Aseco::addChatCommand('relays', 'Displays relays list or shows relay master', true);
Aseco::addChatCommand('server', 'Displays server\'s detailed settings', true);
Aseco::addChatCommand('pm', 'Sends private message to all available admins', true);
Aseco::addChatCommand('pmlog', 'Displays log of recent private admin messages', true);
Aseco::addChatCommand('call', 'Executes direct server call (see: /admin call help)', true);
Aseco::addChatCommand('unlock', 'Unlocks admin commands & features', true);
Aseco::addChatCommand('debug', 'Toggles debugging output', true);
Aseco::addChatCommand('shutdown', 'Shuts down XASECO', true);
Aseco::addChatCommand('shutdownall', 'Shuts down Server & XASECO', true);
//Aseco::addChatCommand('uptodate', 'Checks current version of XASECO', true);  // already defined in plugin.uptodate.php

global $pmbuf;  // pm history buffer
global $pmlen;  // length of pm history
global $lnlen;  // max length of pm line

$pmbuf = array();
$pmlen = 30;
$lnlen = 40;

global $method_results, $auto_scorepanel, $rounds_finishpanel;
$auto_scorepanel = true;
$rounds_finishpanel = true;

function chat_admin($aseco, $command) {
	global $jukebox;  // from plugin.rasp_jukebox.php

	$admin = $command['author'];
	$login = $admin->login;

	// split params into arrays & insure optional parameters exist
	$arglist = explode(' ', $command['params'], 2);
	if (!isset($arglist[1])) $arglist[1] = '';
	$command['params'] = explode(' ', preg_replace('/ +/', ' ', $command['params']));
	if (!isset($command['params'][1])) $command['params'][1] = '';

	// check if chat command was allowed for a masteradmin/admin/operator
	if ($aseco->isMasterAdmin($admin)) {
		$logtitle = 'MasterAdmin';
		$chattitle = $aseco->titles['MASTERADMIN'][0];
	} else {
		if ($aseco->isAdmin($admin) && $aseco->allowAdminAbility($command['params'][0])) {
			$logtitle = 'Admin';
			$chattitle = $aseco->titles['ADMIN'][0];
		} else {
			if ($aseco->isOperator($admin) && $aseco->allowOpAbility($command['params'][0])) {
				$logtitle = 'Operator';
				$chattitle = $aseco->titles['OPERATOR'][0];
			} else {
				// write warning in console
				$aseco->console($login . ' tried to use admin chat command (no permission!): ' . $arglist[0] . ' ' . $arglist[1]);
				// show chat message
				$aseco->client->query('ChatSendToLogin', $aseco->formatColors('{#error}You don\'t have the required admin rights to do that!'), $login);
				return false;
			}
		}
	}

	// check for unlocked password (or unlock command)
	if ($aseco->settings['lock_password'] != '' && !$admin->unlocked &&
	    $command['params'][0] != 'unlock') {
		// write warning in console
		$aseco->console($login . ' tried to use admin chat command (not unlocked!): ' . $arglist[0] . ' ' . $arglist[1]);
		// show chat message
		$aseco->client->query('ChatSendToLogin', $aseco->formatColors('{#error}You don\'t have the required admin rights to do that!'), $login);
		return false;
	}

	/**
	 * Show admin help.
	 */
	if ($command['params'][0] == 'help') {
		// build list of currently active commands
		$active_commands = array();
		foreach ($aseco->chat_commands as $cc) {
			// strip off optional abbreviation
			$name = preg_replace('/\/.*/', '', $cc->name);

			// check if admin command is within this admin's tier
			if ($cc->isadmin && $aseco->allowAbility($admin, $name)) {
				$active_command = new ChatCommand($cc->name, $cc->help, true);
				$active_commands[] = $active_command;
			}
		}

		// show active admin commands on command line
		showHelp($admin, $active_commands, $logtitle, true, false);

	/**
	 * Display admin help.
	 */
	} elseif ($command['params'][0] == 'helpall') {

		// build list of currently active commands
		$active_commands = array();
		foreach ($aseco->chat_commands as $cc) {
			// strip off optional abbreviation
			$name = preg_replace('/\/.*/', '', $cc->name);

			// check if admin command is within this admin's tier
			if ($cc->isadmin && $aseco->allowAbility($admin, $name)) {
				$active_command = new ChatCommand($cc->name, $cc->help, true);
				$active_commands[] = $active_command;
			}
		}

		// display active admin commands in popup with descriptions
		showHelp($admin, $active_commands, $logtitle, true, true, 0.42);

	/**
	 * Sets a new server name (on the fly).
	 */
	} elseif ($command['params'][0] == 'setservername' && $command['params'][1] != '') {

		// set a new servername
		$aseco->client->query('SetServerName', $arglist[1]);

		// log console message
		$aseco->console('{1} [{2}] set new server name [{3}]', $logtitle, $login, $arglist[1]);

		// show chat message
		$message = formatText('{#server}>> {#admin}{1}$z$s {#highlite}{2}$z$s{#admin} sets servername to {#highlite}{3}',
		                      $chattitle, $admin->nickname, $arglist[1]);
		$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($message));

	/**
	 * Sets a new server comment (on the fly).
	 */
	} elseif ($command['params'][0] == 'setcomment' && $command['params'][1] != '') {

		// set a new server comment
		$aseco->client->query('SetServerComment', $arglist[1]);

		// log console message
		$aseco->console('{1} [{2}] set new server comment [{3}]', $logtitle, $login, $arglist[1]);

		// show chat message
		$message = formatText('{#server}> {#admin}{1}$z$s {#highlite}{2}$z$s{#admin} sets server comment to {#highlite}{3}',
		                      $chattitle, $admin->nickname, $arglist[1]);
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);

	/**
	 * Sets a new player password (on the fly).
	 */
	} elseif ($command['params'][0] == 'setpwd') {

		// set a new player password
		$aseco->client->query('SetServerPassword', $arglist[1]);

		if ($arglist[1] != '') {
			// log console message
			$aseco->console('{1} [{2}] set new player password [{3}]', $logtitle, $login, $arglist[1]);

			// show chat message
			$message = formatText('{#server}> {#admin}{1}$z$s {#highlite}{2}$z$s{#admin} sets player password to {#highlite}{3}',
			                      $chattitle, $admin->nickname, $arglist[1]);
			$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
		} else {
			// log console message
			$aseco->console('{1} [{2}] disabled player password', $logtitle, $login);

			// show chat message
			$message = formatText('{#server}> {#admin}{1}$z$s {#highlite}{2}$z$s{#admin} disables player password',
			                      $chattitle, $admin->nickname);
			$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
		}

	/**
	 * Sets a new spectator password (on the fly).
	 */
	} elseif ($command['params'][0] == 'setspecpwd') {

		// set a new spectator password
		$aseco->client->query('SetServerPasswordForSpectator', $arglist[1]);

		if ($arglist[1] != '') {
			// log console message
			$aseco->console('{1} [{2}] set new spectator password [{3}]', $logtitle, $login, $arglist[1]);

			// show chat message
			$message = formatText('{#server}> {#admin}{1}$z$s {#highlite}{2}$z$s{#admin} sets spectator password to {#highlite}{3}',
			                      $chattitle, $admin->nickname, $arglist[1]);
			$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
		} else {
			// log console message
			$aseco->console('{1} [{2}] disabled spectator password', $logtitle, $login);

			// show chat message
			$message = formatText('{#server}> {#admin}{1}$z$s {#highlite}{2}$z$s{#admin} disables spectator password',
			                      $chattitle, $admin->nickname);
			$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
		}

	/**
	 * Sets a new referee password (on the fly).
	 */
	} elseif ($command['params'][0] == 'setrefpwd') {

		if ($aseco->server->getGame() == 'TMF') {
			// set a new referee password
			$aseco->client->query('SetRefereePassword', $arglist[1]);

			if ($arglist[1] != '') {
				// log console message
				$aseco->console('{1} [{2}] set new referee password [{3}]', $logtitle, $login, $arglist[1]);

				// show chat message
				$message = formatText('{#server}> {#admin}{1}$z$s {#highlite}{2}$z$s{#admin} sets referee password to {#highlite}{3}',
				                      $chattitle, $admin->nickname, $arglist[1]);
				$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
			} else {
				// log console message
				$aseco->console('{1} [{2}] disabled referee password', $logtitle, $login);

				// show chat message
				$message = formatText('{#server}> {#admin}{1}$z$s {#highlite}{2}$z$s{#admin} disables referee password',
				                      $chattitle, $admin->nickname);
				$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
			}
		} else {
			$message = $aseco->getChatMessage('FOREVER_ONLY');
			$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
		}

	/**
	 * Sets a new player maximum that is able to connect to the server.
	 */
	} elseif ($command['params'][0] == 'setmaxplayers' && is_numeric($command['params'][1]) && $command['params'][1] > 0) {

		// tell server to set new player max
		$aseco->client->query('SetMaxPlayers', (int) $command['params'][1]);

		// log console message
		$aseco->console('{1} [{2}] set new player maximum [{3}]', $logtitle, $login, $command['params'][1]);

		// show chat message
		$message = formatText('{#server}>> {#admin}{1}$z$s {#highlite}{2}$z$s{#admin} sets new player maximum to {#highlite}{3}{#admin} !',
		                      $chattitle, $admin->nickname, $command['params'][1]);
		$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($message));

	/**
	 * Sets a new spectator maximum that is able to connect to the server.
	 */
	} elseif ($command['params'][0] == 'setmaxspecs' && is_numeric($command['params'][1]) && $command['params'][1] >= 0) {

		// tell server to set new spectator max
		$aseco->client->query('SetMaxSpectators', (int) $command['params'][1]);

		// log console message
		$aseco->console('{1} [{2}] set new spectator maximum [{3}]', $logtitle, $login, $command['params'][1]);

		// show chat message
		$message = formatText('{#server}>> {#admin}{1}$z$s {#highlite}{2}$z$s{#admin} sets new spectator maximum to {#highlite}{3}{#admin} !',
		                      $chattitle, $admin->nickname, $command['params'][1]);
		$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($message));

	/**
	 * Sets new game mode that will be active upon the next track:
	 * ta,rounds,team,laps,stunts
	 */
	} elseif ($command['params'][0] == 'setgamemode' && $command['params'][1] != '') {

		// check mode parameter
		switch (strtolower($command['params'][1])) {
		case 'ta':
			$mode = Gameinfo::TA;
			break;
		case 'round':  // permit shortcut
		case 'rounds':
			$mode = Gameinfo::RNDS;
			break;
		case 'team':
			$mode = Gameinfo::TEAM;
			break;
		case 'laps':
			$mode = Gameinfo::LAPS;
			break;
		case 'stunts':
			$mode = Gameinfo::STNT;
			break;
		case 'cup':
			if ($aseco->server->getGame() == 'TMF')
				$mode = Gameinfo::CUP;
			else
				$mode = -1;
			break;
		default:
			$mode = -1;
		}

		if ($mode >= 0) {
			if ($aseco->changingmode || $mode != $aseco->server->gameinfo->mode) {
				// tell server to set new game mode
				$aseco->client->query('SetGameMode', $mode);
				$aseco->changingmode = true;

				// log console message
				$aseco->console('{1} [{2}] set new game mode [{3}]', $logtitle, $login, strtoupper($command['params'][1]));

				// show chat message
				$message = formatText('{#server}>> {#admin}{1}$z$s {#highlite}{2}$z$s{#admin} sets next game mode to {#highlite}{3}{#admin} !',
				                      $chattitle, $admin->nickname, strtoupper($command['params'][1]));
				$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($message));
			} else {
				$aseco->changingmode = false;
				$message = '{#server}> Same game mode {#highlite}' . strtoupper($command['params'][1]);
				$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
			}
		} else {
			$message = '{#server}> {#error}Invalid game mode {#highlite}$i ' . strtoupper($command['params'][1]) . '$z$s {#error}!';
			$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
		}

	/**
	 * Sets new referee mode (0 = top3, 1 = all).
	 */
	} elseif ($command['params'][0] == 'setrefmode') {

		if ($aseco->server->getGame() == 'TMF') {
			if (($mode = $command['params'][1]) != '') {
				if (is_numeric($mode) && ($mode == 0 || $mode == 1)) {
					// tell server to set new referee mode
					$aseco->client->query('SetRefereeMode', (int) $mode);

					// log console message
					$aseco->console('{1} [{2}] set new referee mode [{3}]', $logtitle, $login, strtoupper($mode));

					// show chat message
					$message = formatText('{#server}>> {#admin}{1}$z$s {#highlite}{2}$z$s{#admin} sets referee mode to {#highlite}{3}{#admin} !',
					                      $chattitle, $admin->nickname, $mode);
					$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($message));
				} else {
					$message = '{#server}> {#error}Invalid referee mode {#highlite}$i ' . strtoupper($mode) . '$z$s {#error}!';
					$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
				}
			} else {
				// tell server to get current referee mode
				$aseco->client->query('GetRefereeMode');
				$mode = $aseco->client->getResponse();

				// show chat message
				$message = formatText('{#server}> {#admin}Referee mode is set to {#highlite}{1}',
				                      ($mode == 1 ? 'All' : 'Top-3'));
				$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($message));
			}
		} else {
			$message = $aseco->getChatMessage('FOREVER_ONLY');
			$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
		}

	/**
	 * Forces the server to load next track.
	 */
	} elseif ($command['params'][0] == 'nextmap' ||
	          $command['params'][0] == 'next' ||
	          $command['params'][0] == 'skipmap' ||
	          $command['params'][0] == 'skip') {

		// load the next map
		// don't clear scores if in TMF Cup mode
		if ($aseco->server->gameinfo->mode == Gameinfo::CUP)
			$aseco->client->query('NextChallenge', true);
		else
			$aseco->client->query('NextChallenge');

		// log console message
		$aseco->console('{1} [{2}] skips challenge!', $logtitle, $login);

		// show chat message
		$message = formatText('{#server}>> {#admin}{1}$z$s {#highlite}{2}$z$s{#admin} skips challenge!',
		                      $chattitle, $admin->nickname);
		$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($message));

	/**
	 * Forces the server to load previous track.
	 */
	} elseif ($command['params'][0] == 'previous' ||
	          $command['params'][0] == 'prev') {

		// get current track
		$aseco->client->query('GetCurrentChallengeIndex');
		$current = $aseco->client->getResponse();

		// check if not the first track
		if ($current > 0) {
			// find previous track
			$aseco->client->query('GetChallengeList', 1, --$current);
			$track = $aseco->client->getResponse();
			$prev = array();
			$prev['name'] = $track[0]['Name'];
			$prev['environment'] = $track[0]['Environnement'];
			$prev['filename'] = $track[0]['FileName'];
			$prev['uid'] = $track[0]['UId'];
		} else {
			// dummy player to easily obtain entire track list
			$list = new Player();
			getAllChallenges($list, '*', '*');
			// find last track
			$prev = end($list->tracklist);
			unset($list);
		}

		// prepend previous challenge to start of jukebox
		$uid = $prev['uid'];
		$jukebox = array_reverse($jukebox, true);
		$jukebox[$uid]['FileName'] = $prev['filename'];
		$jukebox[$uid]['Name'] = $prev['name'];
		$jukebox[$uid]['Env'] = $prev['environment'];
		$jukebox[$uid]['Login'] = $admin->login;
		$jukebox[$uid]['Nick'] = $admin->nickname;
		$jukebox[$uid]['source'] = 'Previous';
		$jukebox[$uid]['tmx'] = false;
		$jukebox[$uid]['uid'] = $uid;
		$jukebox = array_reverse($jukebox, true);

		if ($aseco->debug) {
			$aseco->console_text('/admin prev jukebox:' . CRLF .
			                     print_r($jukebox, true));
		}

		// load the previous track
		// don't clear scores if in TMF Cup mode
		if ($aseco->server->gameinfo->mode == Gameinfo::CUP)
			$aseco->client->query('NextChallenge', true);
		else
			$aseco->client->query('NextChallenge');

		// log console message
		$aseco->console('{1} [{2}] revisits previous challenge!', $logtitle, $login);

		// show chat message
		$message = formatText('{#server}>> {#admin}{1}$z$s {#highlite}{2}$z$s{#admin} revisits previous challenge!',
		                      $chattitle, $admin->nickname);
		$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($message));

		// throw 'jukebox changed' event
		$aseco->releaseEvent('onJukeboxChanged', array('previous', $jukebox[$uid]));

	/**
	 * Loads the next track in the same environment.
	 */
	} elseif ($command['params'][0] == 'nextenv') {

		// check for TMF United
		if ($aseco->server->getGame() == 'TMF' &&
		    $aseco->server->packmask != 'Stadium') {
			// dummy player to easily obtain environment track list
			$list = new Player();
			getAllChallenges($list, '*', $aseco->server->challenge->environment);

			// search for current track
			$next = null;
			$found = false;
			foreach ($list->tracklist as $track) {
				if ($found) {
					$next = $track;
					break;
				}
				if ($track['uid'] == $aseco->server->challenge->uid)
					$found = true;
			}
			// check for last track and loop back to first
			if ($next === null)
				$next = $list->tracklist[0];
			unset($list);

			// prepend next env challenge to start of jukebox
			$uid = $next['uid'];
			$jukebox = array_reverse($jukebox, true);
			$jukebox[$uid]['FileName'] = $next['filename'];
			$jukebox[$uid]['Name'] = $next['name'];
			$jukebox[$uid]['Env'] = $next['environment'];
			$jukebox[$uid]['Login'] = $admin->login;
			$jukebox[$uid]['Nick'] = $admin->nickname;
			$jukebox[$uid]['source'] = 'Previous';
			$jukebox[$uid]['tmx'] = false;
			$jukebox[$uid]['uid'] = $uid;
			$jukebox = array_reverse($jukebox, true);

			if ($aseco->debug) {
				$aseco->console_text('/admin nextenv jukebox:' . CRLF .
				                     print_r($jukebox, true));
			}

			// load the next environment track
			// don't clear scores if in TMF Cup mode
			if ($aseco->server->gameinfo->mode == Gameinfo::CUP)
				$aseco->client->query('NextChallenge', true);
			else
				$aseco->client->query('NextChallenge');

			// log console message
			$aseco->console('{1} [{2}] skips to next {3} challenge!', $logtitle, $login, $aseco->server->challenge->environment);

			// show chat message
			$message = formatText('{#server}>> {#admin}{1}$z$s {#highlite}{2}$z$s{#admin} skips to next {#highlite}{3}{#admin} challenge!',
			                      $chattitle, $admin->nickname, $aseco->server->challenge->environment);
			$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($message));

			// throw 'jukebox changed' event
			$aseco->releaseEvent('onJukeboxChanged', array('nextenv', $jukebox[$uid]));

		} else {  // TMN(F)
			$message = '{#server}> {#error}Command only available on TMU Forever!';
			$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
		}

	/**
	 * Restarts the currently running map.
	 */
	} elseif ($command['params'][0] == 'restartmap' ||
	          $command['params'][0] == 'res') {
		global $atl_restart;  // from plugin.autotime.php

		// restart the track
		if (isset($atl_restart)) $atl_restart = true;
		// don't clear scores if in TMF Cup mode
		if ($aseco->server->gameinfo->mode == Gameinfo::CUP)
			$aseco->client->query('ChallengeRestart', true);
		else
			$aseco->client->query('ChallengeRestart');

		// log console message
		$aseco->console('{1} [{2}] restarts challenge!', $logtitle, $login);

		// show chat message
		$message = formatText('{#server}>> {#admin}{1}$z$s {#highlite}{2}$z$s{#admin} restarts challenge!',
		                      $chattitle, $admin->nickname);

		$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($message));

	/**
	 * Replays the current map (queues it at start of jukebox).
	 */
	} elseif ($command['params'][0] == 'replaymap' ||
	          $command['params'][0] == 'replay') {
		global $chatvote;  // from plugin.rasp_votes.php

		// cancel possibly ongoing replay/restart vote
		$aseco->client->query('CancelVote');
		if (!empty($chatvote) && $chatvote['type'] == 2) {
			$chatvote = array();
			// disable all vote panels
			if ($aseco->server->getGame() == 'TMF')
				allvotepanels_off($aseco);
		}

		// check if track already in jukebox
		if (!empty($jukebox) && array_key_exists($aseco->server->challenge->uid, $jukebox)) {
			$message = '{#server}> {#error}Track is already getting replayed!';
			$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
			return;
		}

		// prepend current challenge to start of jukebox
		$uid = $aseco->server->challenge->uid;
		$jukebox = array_reverse($jukebox, true);
		$jukebox[$uid]['FileName'] = $aseco->server->challenge->filename;
		$jukebox[$uid]['Name'] = $aseco->server->challenge->name;
		$jukebox[$uid]['Env'] = $aseco->server->challenge->environment;
		$jukebox[$uid]['Login'] = $admin->login;
		$jukebox[$uid]['Nick'] = $admin->nickname;
		$jukebox[$uid]['source'] = 'AdminReplay';
		$jukebox[$uid]['tmx'] = false;
		$jukebox[$uid]['uid'] = $uid;
		$jukebox = array_reverse($jukebox, true);

		if ($aseco->debug) {
			$aseco->console_text('/admin replay jukebox:' . CRLF .
			                     print_r($jukebox, true));
		}

		// log console message
		$aseco->console('{1} [{2}] requeues challenge!', $logtitle, $login);

		// show chat message
		$message = formatText('{#server}>> {#admin}{1}$z$s {#highlite}{2}$z$s{#admin} queues challenge for replay!',
		                      $chattitle, $admin->nickname);
		$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($message));

		// throw 'jukebox changed' event
		$aseco->releaseEvent('onJukeboxChanged', array('replay', $jukebox[$uid]));

	/**
	 * Drops a track from the jukebox (for use with rasp jukebox plugin).
	 */
	} elseif ($command['params'][0] == 'dropjukebox' ||
	          $command['params'][0] == 'djb') {

		// verify parameter
		if (is_numeric($command['params'][1]) &&
		    $command['params'][1] >= 1 && $command['params'][1] <= count($jukebox)) {
			$i = 1;
			foreach ($jukebox as $item) {
				if ($i++ == $command['params'][1]) {
					$name = stripColors($item['Name']);
					$uid = $item['uid'];
					break;
				}
			}
			$drop = $jukebox[$uid];
			unset($jukebox[$uid]);

			// log console message
			$aseco->console('{1} [{2}] drops track {3} from jukebox!', $logtitle, $login, stripColors($name, false));

			// show chat message
			$message = formatText('{#server}>> {#admin}{1}$z$s {#highlite}{2}$z$s{#admin} drops track {#highlite}{3}{#admin} from jukebox!',
			                      $chattitle, $admin->nickname, $name);
			$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($message));

			// throw 'jukebox changed' event
			$aseco->releaseEvent('onJukeboxChanged', array('drop', $drop));
		} else {
			$message = '{#server}> {#error}Jukebox entry not found! Type {#highlite}$i /jukebox list{#error} or {#highlite}$i /jukebox display{#error} for its contents.';
			$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
		}

	/**
	 * Clears the jukebox (for use with rasp jukebox plugin).
	 */
	} elseif ($command['params'][0] == 'clearjukebox' ||
	          $command['params'][0] == 'cjb') {

		// clear jukebox
		$jukebox = array();

		// log console message
		$aseco->console('{1} [{2}] clears jukebox!', $logtitle, $login);

		// show chat message
		$message = formatText('{#server}>> {#admin}{1}$z$s {#highlite}{2}$z$s{#admin} clears jukebox!',
		                      $chattitle, $admin->nickname);
		$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($message));

		// throw 'jukebox changed' event
		$aseco->releaseEvent('onJukeboxChanged', array('clear', null));

	/**
	 * Clears (part of) track history.
	 */
	} elseif ($command['params'][0] == 'clearhist') {
		global $buffersize, $jb_buffer;  // from rasp.settings.php

		// check for optional portion (pos = newest, neg = oldest)
		if ($command['params'][1] != '' && is_numeric($command['params'][1]) && $command['params'][1] != 0) {
			$clear = intval($command['params'][1]);

			// log console message
			$aseco->console('{1} [{2}] clears {3} track{4} from history!', $logtitle, $login,
			                ($clear > 0 ? 'newest ' : 'oldest ') . abs($clear),
			                abs($clear) == 1 ? '' : 's');

			// show chat message
			$message = formatText('{#server}>> {#admin}{1}$z$s {#highlite}{2}$z$s{#admin} clears {3}{#admin} track{4} from history!',
			                      $chattitle, $admin->nickname,
			                      ($clear > 0 ? 'newest {#highlite}' : 'oldest {#highlite}') . abs($clear),
			                      abs($clear) == 1 ? '' : 's');
			$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($message));
		} elseif (strtolower($command['params'][1]) == 'all') {  // entire history
			$clear = $buffersize;

			// log console message
			$aseco->console('{1} [{2}] clears entire track history!', $logtitle, $login);

			// show chat message
			$message = formatText('{#server}>> {#admin}{1}$z$s {#highlite}{2}$z$s{#admin} clears entire track history!',
			                      $chattitle, $admin->nickname);
			$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($message));
		} else {
			// show chat message
			$message = formatText('{#server}> {#admin}The track history contains {#highlite}{3}{#admin} track{4}',
			                      $chattitle, $admin->nickname, count($jb_buffer),
			                      (count($jb_buffer) == 1 ? '' : 's'));
			$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
			return;
		}

		// clear track history (portion)
		$i = 0;
		if ($clear > 0) {
			if ($clear > $buffersize) $clear = $buffersize;
			while ($i++ < $clear) array_pop($jb_buffer);
		} else {
			if ($clear < -$buffersize) $clear = -$buffersize;
			while ($i-- > $clear) array_shift($jb_buffer);
		}

	/**
	 * Adds TMX tracks to the track rotation.
	 */
	} elseif ($command['params'][0] == 'add') {
		global $rasp, $tmxdir, $jukebox_adminadd;  // from plugin.rasp.php, rasp.settings.php

		$sections = array('TMO' => 'original',
		                  'TMS' => 'sunrise',
		                  'TMN' => 'nations',
		                  'TMU' => 'united',
		                  'TMNF' => 'tmnforever');

		// check last parameter
		$last = strtoupper(end($command['params']));
		// try to load the track(s) from TMX
		$source = 'TMX';
		$section = $aseco->server->getGame();
		if ($section == 'TMF' && count($command['params']) > 2 &&
		    substr($last, 0, 2) == 'TM') {
			$section = $last;
			array_pop($command['params']);
			if (!array_key_exists($section, $sections)) {
				$message = '{#server}> {#error}No such section on TMX!';
				$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
				return;
			}
		} else {  // TMN/TMS/TMO or no section
			if ($section == 'TMF') {
				if ($aseco->server->packmask == 'Stadium')
					$section = 'TMNF';
				else
					$section = 'TMU';
			}
		}
		$remotelink = 'http://' . $sections[$section] . '.tm-exchange.com/get.aspx?action=trackgbx&id=';

		if (count($command['params']) == 1) {
			$message = '{#server}> {#error}You must include a TMX Track_ID!';
			$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
			return;
		}

		// try all specified tracks
		for ($id = 1; $id < count($command['params']); $id++) {
			// check for valid TMX ID
			if (is_numeric($command['params'][$id]) && $command['params'][$id] >= 0) {
				$trkid = ltrim($command['params'][$id], '0');
				$file = http_get_file($remotelink . $trkid);
				if ($file === false || $file == -1) {
					$message = '{#server}> {#error}Error downloading, or wrong TMX section, or TMX is down!';
					$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
				} else {
					// check for maximum online track size (256 KB)
					if (strlen($file) >= 256 * 1024) {
						$message = formatText($rasp->messages['TRACK_TOO_LARGE'][0],
						                      round(strlen($file) / 1024));
						$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
						continue;
					}
					$sepchar = substr($aseco->server->trackdir, -1, 1);
					$partialdir = $tmxdir . $sepchar . $trkid . '.Challenge.gbx';
					$localfile = $aseco->server->trackdir . $partialdir;
					if ($nocasepath = file_exists_nocase($localfile)) {
						if (!unlink($nocasepath)) {
							$message = '{#server}> {#error}Error erasing old file - unable to erase {#highlite}$i ' . $localfile;
							$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
							continue;
						}
					}
					if (!$lfile = @fopen($localfile, 'wb')) {
						$message = '{#server}> {#error}Error creating file - unable to create {#highlite}$i ' . $localfile;
						$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
						continue;
					}
					if (!fwrite($lfile, $file)) {
						$message = '{#server}> {#error}Error saving file - unable to write data';
						$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
						fclose($lfile);
						continue;
					}
					fclose($lfile);
					$newtrk = getChallengeData($localfile, false);  // 2nd parm is whether or not to get players & votes required
					if ($newtrk['votes'] == 500 && $newtrk['name'] == 'Not a GBX file') {
						$message = '{#server}> {#error}No such track on ' . $source;
						if ($source == 'TMX' && $aseco->server->getGame() == 'TMF')
							$message .= ' section ' . $section;
						$message .= '!';
						$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
						unlink($localfile);
						continue;
					}
					// dummy player to easily obtain entire track list
					$list = new Player();
					getAllChallenges($list, '*', '*');
					// check for track presence on server
					foreach ($list->tracklist as $key) {
						if ($key['uid'] == $newtrk['uid']) {
							$message = $rasp->messages['ADD_PRESENT'][0];
							$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
							unlink($localfile);
							unset($list);
							continue 2;  // outer for loop
						}
					}
					unset($list);
					// rename ID filename to track's name
					$md5new = md5_file($localfile);
					$filename = trim(utf8_decode(stripColors($newtrk['name'])));
					$filename = preg_replace('/[^A-Za-z0-9 \'#=+~_,.-]/', '_', $filename);
					$filename = preg_replace('/ +/', ' ', preg_replace('/_+/', '_', $filename));
					$partialdir = $tmxdir . $sepchar . $filename . '_' . $trkid . '.Challenge.gbx';
					// insure unique filename by incrementing sequence number,
					// if not a duplicate track
					$i = 1;
					$dupl = false;
					while ($nocasepath = file_exists_nocase($aseco->server->trackdir . $partialdir)) {
						$md5old = md5_file($nocasepath);
						if ($md5old == $md5new) {
							$dupl = true;
							$partialdir = str_replace($aseco->server->trackdir, '', $nocasepath);
							break;
						} else {
							$partialdir = $tmxdir . $sepchar . $filename . '_' . $trkid . '-' . $i++ . '.Challenge.gbx';
						}
					}
					if ($dupl) {
						unlink($localfile);
					} else {
						rename($localfile, $aseco->server->trackdir . $partialdir);
					}

					// check track vs. server settings
					if ($aseco->server->getGame() == 'TMF')
						$rtn = $aseco->client->query('CheckChallengeForCurrentServerParams', $partialdir);
					else
						$rtn = true;
					if (!$rtn) {
						trigger_error('[' . $aseco->client->getErrorCode() . '] CheckChallengeForCurrentServerParams - ' . $aseco->client->getErrorMessage(), E_USER_WARNING);
						$message = formatText($rasp->messages['JUKEBOX_IGNORED'][0],
						                      stripColors($newtrk['name']), $aseco->client->getErrorMessage());
						$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
					} else {
						// permanently add the track to the server list
						$rtn = $aseco->client->query('AddChallenge', $partialdir);
						if (!$rtn) {
							trigger_error('[' . $aseco->client->getErrorCode() . '] AddChallenge - ' . $aseco->client->getErrorMessage(), E_USER_WARNING);
						} else {
							$aseco->client->resetError();
							$aseco->client->query('GetChallengeInfo', $partialdir);
							$track = $aseco->client->getResponse();
							if ($aseco->client->isError()) {
								trigger_error('[' . $aseco->client->getErrorCode() . '] GetChallengeInfo - ' . $aseco->client->getErrorMessage(), E_USER_WARNING);
								$message = formatText('{#server}> {#error}Error getting info on track {#highlite}$i {1} {#error}!',
								                      $partialdir);
								$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
							} else {
								$track['Name'] = stripNewlines($track['Name']);
								// check whether to jukebox as well
								// overrules /add-ed but not yet played track
								if ($jukebox_adminadd) {
									$uid = $track['UId'];
									$jukebox[$uid]['FileName'] = $track['FileName'];
									$jukebox[$uid]['Name'] = $track['Name'];
									$jukebox[$uid]['Env'] = $track['Environnement'];
									$jukebox[$uid]['Login'] = $login;
									$jukebox[$uid]['Nick'] = $admin->nickname;
									$jukebox[$uid]['source'] = $source;
									$jukebox[$uid]['tmx'] = false;
									$jukebox[$uid]['uid'] = $uid;
								}

								// log console message
								$aseco->console('{1} [{2}] adds track "{3}" from {4}!', $logtitle, $login, stripColors($track['Name'], false), $source);

								// show chat message
								$message = formatText('{#server}>> {#admin}{1}$z$s {#highlite}{2}$z$s {#admin}adds {3}track: {#highlite}{4} {#admin}from {5}',
								                      $chattitle, $admin->nickname,
								                      ($jukebox_adminadd ? '& jukeboxes ' : ''),
								                      stripColors($track['Name']), $source);
								$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($message));

								// throw 'tracklist changed' event
								$aseco->releaseEvent('onTracklistChanged', array('add', $partialdir));

								// throw 'jukebox changed' event
								if ($jukebox_adminadd)
									$aseco->releaseEvent('onJukeboxChanged', array('add', $jukebox[$uid]));
							}
						}
					}
				}
			} else {
				$message = formatText('{#server}> {#highlite}{1}{#error} is not a valid TMX Track_ID!',
				                      $command['params'][$id]);
				$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
			}
		}

	/**
	 * Adds current /add-ed track permanently to server's track list
	 * by preventing its removal that normally occurs afterwards
	 */
	} elseif ($command['params'][0] == 'addthis') {
		global $tmxplayed, $tmxdir, $tmxtmpdir;  // from plugin.rasp_jukebox.php, rasp.settings.php

		// check for TMX /add-ed track
		if ($tmxplayed) {
			// remove track with old path
			$rtn = $aseco->client->query('RemoveChallenge', $tmxplayed);
			if (!$rtn) {
				trigger_error('[' . $aseco->client->getErrorCode() . '] RemoveChallenge - ' . $aseco->client->getErrorMessage(), E_USER_WARNING);
				return;
			} else {
				// move the track file
				$tmxnew = str_replace($tmxtmpdir, $tmxdir, $tmxplayed);
				if (!rename($aseco->server->trackdir . $tmxplayed,
				            $aseco->server->trackdir . $tmxnew)) {
					trigger_error('Could not rename TMX track ' . $tmxplayed . ' to ' . $tmxnew, E_USER_WARNING);
					return;
				} else {
					// add track with new path
					$rtn = $aseco->client->query('AddChallenge', $tmxnew);
					if (!$rtn) {
						trigger_error('[' . $aseco->client->getErrorCode() . '] AddChallenge - ' . $aseco->client->getErrorMessage(), E_USER_WARNING);
						return;
					} else {  // store new path
						$aseco->server->challenge->filename = $tmxnew;

						// throw 'tracklist changed' event
						$aseco->releaseEvent('onTracklistChanged', array('rename', $tmxnew));
					}
				}
			}

			// disable track removal afterwards
			$tmxplayed = false;

			// show chat message
			$message = formatText('{#server}>> {#admin}{1}$z$s {#highlite}{2}$z$s {#admin}permanently adds current track: {#highlite}{3}',
			                      $chattitle, $admin->nickname,
			                      stripColors($aseco->server->challenge->name));
			$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($message));
		} else {
			$message = formatText('{#server}> {#error}Current track {#highlite}$i {1} {#error}already permanently in track list!',
			                      stripColors($aseco->server->challenge->name));
			$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
		}

	/**
	 * Add a local track to the track rotation.
	 */
	} elseif ($command['params'][0] == 'addlocal') {
		global $rasp, $jukebox_adminadd;  // from plugin.rasp.php, rasp.settings.php

		// check for local track file
		if ($arglist[1] != '') {
			$sepchar = substr($aseco->server->trackdir, -1, 1);
			$partialdir = 'Challenges' . $sepchar . 'Downloaded' . $sepchar . $arglist[1];
			if (!stristr($partialdir, '.Challenge.gbx')) {
				$partialdir .= '.Challenge.gbx';
			}
			$localfile = $aseco->server->trackdir . $partialdir;
			if ($nocasepath = file_exists_nocase($localfile)) {
				// check for maximum online track size (256 KB)
				if (filesize($nocasepath) >= 256 * 1024) {
					$message = formatText($rasp->messages['TRACK_TOO_LARGE'][0],
					                      round(filesize($nocasepath) / 1024));
					$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
					return;
				}
				$partialdir = str_replace($aseco->server->trackdir, '', $nocasepath);

				// check track vs. server settings
				if ($aseco->server->getGame() == 'TMF')
					$rtn = $aseco->client->query('CheckChallengeForCurrentServerParams', $partialdir);
				else
					$rtn = true;
				if (!$rtn) {
					trigger_error('[' . $aseco->client->getErrorCode() . '] CheckChallengeForCurrentServerParams - ' . $aseco->client->getErrorMessage(), E_USER_WARNING);
					$message = formatText($rasp->messages['JUKEBOX_IGNORED'][0],
					                      stripColors(str_replace('Challenges' . $sepchar . 'Downloaded' . $sepchar, '', $partialdir)), $aseco->client->getErrorMessage());
					$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
				} else {
					// permanently add the track to the server list
					$rtn = $aseco->client->query('AddChallenge', $partialdir);
					if (!$rtn) {
						trigger_error('[' . $aseco->client->getErrorCode() . '] AddChallenge - ' . $aseco->client->getErrorMessage(), E_USER_WARNING);
					} else {
						$aseco->client->resetError();
						$aseco->client->query('GetChallengeInfo', $partialdir);
						$track = $aseco->client->getResponse();
						if ($aseco->client->isError()) {
							trigger_error('[' . $aseco->client->getErrorCode() . '] GetChallengeInfo - ' . $aseco->client->getErrorMessage(), E_USER_WARNING);
							$message = formatText('{#server}> {#error}Error getting info on track {#highlite}$i {1} {#error}!',
							                      $partialdir);
							$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
						} else {
							$track['Name'] = stripNewlines($track['Name']);
							// check whether to jukebox as well
							// overrules /add-ed but not yet played track
							if ($jukebox_adminadd) {
								$uid = $track['UId'];
								$jukebox[$uid]['FileName'] = $track['FileName'];
								$jukebox[$uid]['Name'] = $track['Name'];
								$jukebox[$uid]['Env'] = $track['Environnement'];
								$jukebox[$uid]['Login'] = $login;
								$jukebox[$uid]['Nick'] = $admin->nickname;
								$jukebox[$uid]['source'] = 'Local';
								$jukebox[$uid]['tmx'] = false;
								$jukebox[$uid]['uid'] = $uid;
							}
	
							// log console message
							$aseco->console('{1} [{2}] adds local track {3} !', $logtitle, $login, stripColors($track['Name'], false));
	
							// show chat message
							$message = formatText('{#server}>> {#admin}{1}$z$s {#highlite}{2}$z$s {#admin}adds {3}track: {#highlite}{4}',
							                      $chattitle, $admin->nickname,
							                      ($jukebox_adminadd ? '& jukeboxes ' : ''),
							                      stripColors($track['Name']));
							$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($message));
	
							// throw 'tracklist changed' event
							$aseco->releaseEvent('onTracklistChanged', array('add', $partialdir));
	
							// throw 'jukebox changed' event
							if ($jukebox_adminadd)
								$aseco->releaseEvent('onJukeboxChanged', array('add', $jukebox[$uid]));
						}
					}
				}
			} else {
				$message = '{#server}> {#highlite}' . $partialdir . '{#error} not found!';
				$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
			}
		} else {
			$message = '{#server}> {#error}You must include a local track filename!';
			$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
		}

	/**
	 * Warns a player with the specified login/PlayerID.
	 */
	} elseif ($command['params'][0] == 'warn' && $command['params'][1] != '') {

		// get player information
		if ($target = $aseco->getPlayerParam($admin, $command['params'][1])) {
			// display warning message
			$message = $aseco->getChatMessage('WARNING');
			if ($aseco->server->getGame() == 'TMF') {
				$message = preg_split('/{br}/', $aseco->formatColors($message));
				foreach ($message as &$line)
					$line = array($line);

				display_manialink($target->login, $aseco->formatColors('{#welcome}WARNING:'), array('Icons64x64_1', 'TV'),
				                  $message, array(0.8), 'OK');
			} else {  // TMN
				$message = str_replace('{br}', LF, $aseco->formatColors($message));
				$aseco->client->query('SendDisplayServerMessageToLogin', $target->login, $message, 'OK', '', 0);
			}
			// log console message
			$aseco->console('{1} [{2}] warned player {3}!', $logtitle, $login, stripColors($target->nickname, false));

			// show chat message
			$message = formatText('{#server}>> {#admin}{1}$z$s {#highlite}{2}$z$s{#admin} warned {#highlite}{3}$z$s{#admin} !',
			                      $chattitle, $admin->nickname, str_ireplace('$w', '', $target->nickname));
			$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($message));
		}

	/**
	 * Kicks a player with the specified login/PlayerID.
	 */
	} elseif ($command['params'][0] == 'kick' && $command['params'][1] != '') {

		// get player information
		if ($target = $aseco->getPlayerParam($admin, $command['params'][1])) {
			// log console message
			$aseco->console('{1} [{2}] kicked player {3}!', $logtitle, $login, stripColors($target->nickname, false));

			// show chat message
			$message = formatText('{#server}>> {#admin}{1}$z$s {#highlite}{2}$z$s{#admin} kicked {#highlite}{3}$z$s{#admin} !',
			                      $chattitle, $admin->nickname, str_ireplace('$w', '', $target->nickname));
			$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($message));

			// kick the player
			$aseco->client->query('Kick', $target->login);
		}

	/**
	 * Kicks a ghost player with the specified login.
	 * This variant for ghost players that got disconnected doesn't
	 * check the login for validity and doesn't work with Player_IDs.
	 */
	} elseif ($command['params'][0] == 'kickghost' && $command['params'][1] != '') {

		// get player login without validation
		$target = $command['params'][1];

		// log console message
		$aseco->console('{1} [{2}] kicked ghost player {3}!', $logtitle, $login, $target);

		// show chat message
		$message = formatText('{#server}>> {#admin}{1}$z$s {#highlite}{2}$z$s{#admin} kicked ghost {#highlite}{3}$z$s{#admin} !',
		                      $chattitle, $admin->nickname, $target);
		$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($message));

		// kick the ghost player
		$aseco->client->query('Kick', $target);

	/**
	 * Ban a player with the specified login/PlayerID.
	 */
	} elseif ($command['params'][0] == 'ban' && $command['params'][1] != '') {

		// get player information
		if ($target = $aseco->getPlayerParam($admin, $command['params'][1])) {
			// log console message
			$aseco->console('{1} [{2}] bans player {3}!', $logtitle, $login, stripColors($target->nickname, false));

			// show chat message
			$message = formatText('{#server}>> {#admin}{1}$z$s {#highlite}{2}$z$s{#admin} bans {#highlite}{3}$z$s{#admin} !',
			                      $chattitle, $admin->nickname, str_ireplace('$w', '', $target->nickname));
			$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($message));

			// update banned IPs file
			$aseco->bannedips[] = $target->ip;
			$aseco->writeIPs();

			// ban the player and also kick him
			$aseco->client->query('Ban', $target->login);
		}

	/**
	 * Un-bans player with the specified login/PlayerID.
	 */
	} elseif ($command['params'][0] == 'unban' && $command['params'][1] != '') {

		// get player information
		if ($target = $aseco->getPlayerParam($admin, $command['params'][1], true)) {
			$bans = get_banlist($aseco);
			// unban the player
			$rtn = $aseco->client->query('UnBan', $target->login);
			if (!$rtn) {
				$message = formatText('{#server}> {#highlite}{1}{#error} is not a banned player!',
				                      $command['params'][1]);
				$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
			} else {
				if (($i = array_search($bans[$target->login][2], $aseco->bannedips)) !== false) {
					// update banned IPs file
					$aseco->bannedips[$i] = '';
					$aseco->writeIPs();
				}

				// log console message
				$aseco->console('{1} [{2}] unbans player {3}', $logtitle, $login, stripColors($target->nickname, false));

				// show chat message
				$message = formatText('{#server}> {#admin}{1}$z$s {#highlite}{2}$z$s{#admin} un-bans {#highlite}{3}',
				                      $chattitle, $admin->nickname, str_ireplace('$w', '', $target->nickname));
				$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
			}
		}

	/**
	 * Ban a player with the specified IP address.
	 */
	} elseif ($command['params'][0] == 'banip' && $command['params'][1] != '') {

		// check for valid IP not already banned
		$ipaddr = $command['params'][1];
		if (preg_match('/^\d+\.\d+\.\d+\.\d+$/', $ipaddr)) {
			if (empty($aseco->bannedips) || !in_array($ipaddr, $aseco->bannedips)) {
				// log console message
				$aseco->console('{1} [{2}] banned IP {3}!', $logtitle, $login, $ipaddr);

				// show chat message
				$message = formatText('{#server}>> {#admin}{1}$z$s {#highlite}{2}$z$s{#admin} bans IP {#highlite}{3}$z$s{#admin} !',
				                      $chattitle, $admin->nickname, $ipaddr);
				$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($message));

				// update banned IPs file
				$aseco->bannedips[] = $ipaddr;
				$aseco->writeIPs();
			} else {
				$message = formatText('{#server}> {#highlite}{1}{#error} is already banned!',
				                      $ipaddr);
			}
		} else {
			$message = formatText('{#server}> {#highlite}{1}{#error} is not a valid IP address!',
			                      $ipaddr);
			$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
		}

	/**
	 * Un-bans player with the specified IP address.
	 */
	} elseif ($command['params'][0] == 'unbanip' && $command['params'][1] != '') {

		// check for banned IP
		if (($i = array_search($command['params'][1], $aseco->bannedips)) === false) {
			$message = formatText('{#server}> {#highlite}{1}{#error} is not a banned IP address!',
			                      $command['params'][1]);
			$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
		} else {
			// update banned IPs file
			$aseco->bannedips[$i] = '';
			$aseco->writeIPs();

			// log console message
			$aseco->console('{1} [{2}] unbans IP {3}', $logtitle, $login, $command['params'][1]);

			// show chat message
			$message = formatText('{#server}> {#admin}{1}$z$s {#highlite}{2}$z$s{#admin} un-bans IP {#highlite}{3}',
			                      $chattitle, $admin->nickname, $command['params'][1]);
			$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
		}

	/**
	 * Blacklists a player with the specified login/PlayerID.
	 */
	} elseif ($command['params'][0] == 'black' && $command['params'][1] != '') {

		// get player information
		if ($target = $aseco->getPlayerParam($admin, $command['params'][1], true)) {
			// log console message
			$aseco->console('{1} [{2}] blacklists player {3}!', $logtitle, $login, stripColors($target->nickname, false));

			// show chat message
			$message = formatText('{#server}>> {#admin}{1}$z$s {#highlite}{2}$z$s{#admin} blacklists {#highlite}{3}$z$s{#admin} !',
			                      $chattitle, $admin->nickname, str_ireplace('$w', '', $target->nickname));
			$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($message));

			// blacklist the player and then kick him
			$aseco->client->query('BlackList', $target->login);
			$aseco->client->query('Kick', $target->login);

			// update blacklist file
			$filename = $aseco->settings['blacklist_file'];
			$rtn = $aseco->client->query('SaveBlackList', $filename);
			if (!$rtn) {
				trigger_error('[' . $aseco->client->getErrorCode() . '] SaveBlackList - ' . $aseco->client->getErrorMessage(), E_USER_WARNING);
			}
		}

	/**
	 * Un-blacklists player with the specified login/PlayerID.
	 */
	} elseif ($command['params'][0] == 'unblack' && $command['params'][1] != '') {

		$target = false;
		$param = $command['params'][1];

		// get new list of all blacklisted players
		$blacks = get_blacklist($aseco);
		// check as login
		if (array_key_exists($param, $blacks)) {
			$target = new Player();
		// check as player ID
		} elseif (is_numeric($param) && $param > 0) {
			if (empty($admin->playerlist)) {
				$message = '{#server}> {#error}Use {#highlite}$i/players {#error}first (optionally {#highlite}$i/players <string>{#error})';
				$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
				return false;
			}
			$pid = ltrim($param, '0');
			$pid--;
			// find player by given #
			if (array_key_exists($pid, $admin->playerlist)) {
				$param = $admin->playerlist[$pid]['login'];
				$target = new Player();
			} else {
				$message = '{#server}> {#error}Player_ID not found! Type {#highlite}$i/players {#error}to see all players.';
				$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
				return false;
			}
		}

		// check for valid param
		if ($target !== false) {
			$target->login = $param;
			$target->nickname = $aseco->getPlayerNick($param);
			if ($target->nickname == '')
				$target->nickname = $param;

			// unblacklist the player
			$rtn = $aseco->client->query('UnBlackList', $target->login);
			if (!$rtn) {
				$message = formatText('{#server}> {#highlite}{1}{#error} is not a blacklisted player!',
				                      $command['params'][1]);
				$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
			} else {
				// log console message
				$aseco->console('{1} [{2}] unblacklists player {3}', $logtitle, $login, stripColors($target->nickname, false));

				// show chat message
				$message = formatText('{#server}> {#admin}{1}$z$s {#highlite}{2}$z$s{#admin} un-blacklists {#highlite}{3}',
				                      $chattitle, $admin->nickname, str_ireplace('$w', '', $target->nickname));
				$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);

				// update blacklist file
				$filename = $aseco->settings['blacklist_file'];
				$rtn = $aseco->client->query('SaveBlackList', $filename);
				if (!$rtn) {
					trigger_error('[' . $aseco->client->getErrorCode() . '] SaveBlackList - ' . $aseco->client->getErrorMessage(), E_USER_WARNING);
				}
			}
		} else {
			$message = '{#server}> {#highlite}' . $param . ' {#error}is not a valid player! Use {#highlite}$i/players {#error}to find the correct login or Player_ID.';
			$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
		}

	/**
	 * Adds a guest player with the specified login/PlayerID.
	 */
	} elseif ($command['params'][0] == 'addguest' && $command['params'][1] != '') {

		// get player information
		if ($target = $aseco->getPlayerParam($admin, $command['params'][1], true)) {
			// add the guest player
			$aseco->client->query('AddGuest', $target->login);

			// log console message
			$aseco->console('{1} [{2}] adds guest player {3}', $logtitle, $login, stripColors($target->nickname, false));

			// show chat message
			$message = formatText('{#server}> {#admin}{1}$z$s {#highlite}{2}$z$s{#admin} adds guest {#highlite}{3}',
			                      $chattitle, $admin->nickname, str_ireplace('$w', '', $target->nickname));
			$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);

			// update guestlist file
			$filename = $aseco->settings['guestlist_file'];
			$rtn = $aseco->client->query('SaveGuestList', $filename);
			if (!$rtn) {
				trigger_error('[' . $aseco->client->getErrorCode() . '] SaveGuestList - ' . $aseco->client->getErrorMessage(), E_USER_WARNING);
			}
		}

	/**
	 * Removes a guest player with the specified login/PlayerID.
	 */
	} elseif ($command['params'][0] == 'removeguest' && $command['params'][1] != '') {

		// get player information
		if ($target = $aseco->getPlayerParam($admin, $command['params'][1], true)) {
			// remove the guest player
			$rtn = $aseco->client->query('RemoveGuest', $target->login);
			if (!$rtn) {
				$message = formatText('{#server}> {#highlite}{1}{#error} is not a guest player!',
				                      $command['params'][1]);
				$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
			} else {
				// log console message
				$aseco->console('{1} [{2}] removes guest player {3}', $logtitle, $login, stripColors($target->nickname, false));

				// show chat message
				$message = formatText('{#server}> {#admin}{1}$z$s {#highlite}{2}$z$s{#admin} removes guest {#highlite}{3}',
				                      $chattitle, $admin->nickname, str_ireplace('$w', '', $target->nickname));
				$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);

				// update guestlist file
				$filename = $aseco->settings['guestlist_file'];
				$rtn = $aseco->client->query('SaveGuestList', $filename);
				if (!$rtn) {
					trigger_error('[' . $aseco->client->getErrorCode() . '] SaveGuestList - ' . $aseco->client->getErrorMessage(), E_USER_WARNING);
				}
			}
		}

	/**
	 * Passes a chat-based or TMX /add vote.
	 */
	} elseif ($command['params'][0] == 'pass') {
		global $tmxadd, $chatvote, $plrvotes;  // from plugin.rasp_jukebox.php, plugin.rasp_votes.php

		// pass any TMX and chat vote
		if (!empty($tmxadd)) {
			// force required votes down to the last one
			$tmxadd['votes'] = 1;
		}
		elseif (!empty($chatvote)) {
			$chatvote['votes'] = 1;
		}
		else {  // no vote in progress
			$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors('{#server}> {#error}There is no vote right now!'), $login);
			return;
		}

		// log console message
		$aseco->console('{1} [{2}] passes vote!', $logtitle, $login);

		// show chat message
		$message = formatText('{#server}>> {#admin}{1}$z$s {#highlite}{2}$z$s{#admin} passes vote!',
		                      $chattitle, $admin->nickname);
		$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($message));

		// bypass double vote check
		$plrvotes = array();
		// enter the last vote
		chat_y($aseco, $command);

	/**
	 * Cancels any vote.
	 */
	} elseif ($command['params'][0] == 'cancel' ||
	          $command['params'][0] == 'can') {
		global $tmxadd, $chatvote;  // from plugin.rasp_jukebox.php, plugin.rasp_votes.php

		// cancel any CallVote, TMX and chat vote
		$aseco->client->query('CancelVote');
		$tmxadd = array();
		$chatvote = array();
		// disable all vote panels
		if ($aseco->server->getGame() == 'TMF')
			allvotepanels_off($aseco);

		// log console message
		$aseco->console('{1} [{2}] cancels vote!', $logtitle, $login);

		// show chat message
		$message = formatText('{#server}>> {#admin}{1}$z$s {#highlite}{2}$z$s{#admin} cancels vote!',
		                      $chattitle, $admin->nickname);
		$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($message));

	/**
	 * Forces end of current round.
	 */
	} elseif ($command['params'][0] == 'endround' ||
	          $command['params'][0] == 'er') {
		global $chatvote;  // from plugin.rasp_votes.php

		// cancel possibly ongoing endround vote
		if (!empty($chatvote) && $chatvote['type'] == 0) {
			$chatvote = array();
			// disable all vote panels
			if ($aseco->server->getGame() == 'TMF')
				allvotepanels_off($aseco);
		}

		// end this round
		$aseco->client->query('ForceEndRound');

		// log console message
		$aseco->console('{1} [{2}] forces round end!', $logtitle, $login);

		// show chat message
		$message = formatText('{#server}>> {#admin}{1}$z$s {#highlite}{2}$z$s{#admin} forces round end!',
		                      $chattitle, $admin->nickname);
		$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($message));

	/**
	 * Displays the live or known players (on/offline) list.
	 * TMF player management inspired by Mistral.
	 */
	} elseif ($command['params'][0] == 'players') {

		$admin->playerlist = array();
		$admin->msgs = array();

		// remember players parameter for possible refresh
		$admin->panels['plyparam'] = $command['params'][1];
		$onlineonly = (strtolower($command['params'][1]) == 'live');
		// get current ignore/ban/black/guest lists
		if ($aseco->server->getGame() == 'TMF') {
			$ignores = get_ignorelist($aseco);
			$bans = get_banlist($aseco);
			$blacks = get_blacklist($aseco);
			$guests = get_guestlist($aseco);
		}

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
						                                  'nick' => $pl['NickName'],
						                                  'spec' => $pl['SpectatorStatus']);
				} else {
					$onlinelist[$pl['Login']] = array('login' => $pl['Login'],
					                                  'nick' => $pl['NickName'],
					                                  'spec' => $pl['IsSpectator']);
			}
		}

		// use online list?
		if ($onlineonly) {
			$playerlist = $onlinelist;
		} else {
			// search for known players
			$query = 'SELECT login,nickname FROM players
			          WHERE login LIKE ' . quotedString('%' . $arglist[1] . '%') .
			           ' OR nickname LIKE ' . quotedString('%' . $arglist[1] . '%') .
			         ' LIMIT 5000';  // prevent possible memory overrun
			$result = mysql_query($query);

			$playerlist = array();
			if (mysql_num_rows($result) > 0) {
				while ($row = mysql_fetch_row($result)) {
					// skip any LAN logins
					if (!isLANLogin($row[0]))
						$playerlist[$row[0]] = array('login' => $row[0],
						                             'nick' => $row[1],
						                             'spec' => false);
				}
			}
			mysql_free_result($result);
		}

		if (!empty($playerlist)) {
			if ($aseco->server->getGame() == 'TMN') {
				$head = ($onlineonly ? 'Online' : 'Known') . ' Players On This Server:' . LF .
				         'Id     {#nick}Nick $g/{#login} Login' . LF;
				$msg = '';
				$pid = 1;
				$lines = 0;
				$admin->msgs[0] = 1;
				foreach ($playerlist as $pl) {
					$plarr = array();
					$plarr['login'] = $pl['login'];
					$admin->playerlist[] = $plarr;

					$msg .= '$g' . str_pad($pid, 2, '0', STR_PAD_LEFT) . '.   {#black}'
					        . str_ireplace('$w', '', $pl['nick']) . '$z / '
					        . ($aseco->isAnyAdminL($pl['login']) ? '{#logina}' : '{#login}' )
					        . $pl['login'] . LF;
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
				$msg[] = array('Id', '{#nick}Nick $g/{#login} Login', 'Warn', 'Ignore', 'Kick', 'Ban', 'Black', 'Guest', 'Spec');
				$pid = 1;
				$lines = 0;
				$admin->msgs[0] = array(1, $head, array(1.49, 0.15, 0.5, 0.12, 0.12, 0.12, 0.12, 0.12, 0.12, 0.12), array('Icons128x128_1', 'Buddies'));

				foreach ($playerlist as $lg => $pl) {
					$plarr = array();
					$plarr['login'] = $lg;
					$admin->playerlist[] = $plarr;

					// format nickname & login
					$ply = '{#black}' . str_ireplace('$w', '', $pl['nick']) . '$z / '
					       . ($aseco->isAnyAdminL($pl['login']) ? '{#logina}' : '{#login}' )
					       . $pl['login'];
					// define colored column strings
					$wrn = '$ff3Warn';
					$ign = '$f93Ignore';
					$uig = '$d93UnIgn';
					$kck = '$c3fKick';
					$ban = '$f30Ban';
					$ubn = '$c30UnBan';
					$blk = '$f03Black';
					$ubk = '$c03UnBlack';
					$gst = '$3c3Add';
					$ugt = '$393Remove';
					$frc = '$09fForce';
					$off = '$09cOffln';
					$spc = '$09cSpec';

					// always add clickable buttons
					if ($pid <= 200) {
						$ply = array($ply,     $pid+2000);
						if (array_key_exists($lg, $onlinelist)) {
							// determine online operations
							$wrn = array($wrn,   $pid+2200);
							if (array_key_exists($lg, $ignores))
								$ign = array($uig, $pid+2600);
							else
								$ign = array($ign, $pid+2400);
							$kck = array($kck,   $pid+2800);
							if (array_key_exists($lg, $bans))
								$ban = array($ubn, $pid+3200);
							else
								$ban = array($ban, $pid+3000);
							if (array_key_exists($lg, $blacks))
								$blk = array($ubk, $pid+3600);
							else
								$blk = array($blk, $pid+3400);
							if (array_key_exists($lg, $guests))
								$gst = array($ugt, $pid+4000);
							else
								$gst = array($gst, $pid+3800);
							if (!$onlinelist[$lg]['spec'])
								$spc = array($frc, $pid+4200);
						} else {
							// determine offline operations
							if (array_key_exists($lg, $ignores))
								$ign = array($uig, $pid+2600);
							if (array_key_exists($lg, $bans))
								$ban = array($ubn, $pid+3200);
							if (array_key_exists($lg, $blacks))
								$blk = array($ubk, $pid+3600);
							else
								$blk = array($blk, $pid+3400);
							if (array_key_exists($lg, $guests))
								$gst = array($ugt, $pid+4000);
							else
								$gst = array($gst, $pid+3800);
							$spc = $off;
						}
					} else {
						// no more buttons
						if (array_key_exists($lg, $ignores))
							$ign = $uig;
						if (array_key_exists($lg, $bans))
							$ban = $ubn;
						if (array_key_exists($lg, $blacks))
							$blk = $ubk;
						if (array_key_exists($lg, $guests))
							$gst = $ugt;
						if (array_key_exists($lg, $onlinelist)) {
							if (!$onlinelist[$lg]['spec'])
								$spc = $frc;
						} else {
							$spc = $off;
						}
					}

					$msg[] = array(str_pad($pid, 2, '0', STR_PAD_LEFT) . '.', $ply,
					               $wrn, $ign, $kck, $ban, $blk, $gst, $spc);
					$pid++;
					if (++$lines > 14) {
						$admin->msgs[] = $msg;
						$lines = 0;
						$msg = array();
						$msg[] = array('Id', '{#nick}Nick $g/{#login} Login', 'Warn', 'Ignore', 'Kick', 'Ban', 'Black', 'Guest', 'Spec');
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

	/**
	 * Displays the ban list.
	 */
	} elseif ($command['params'][0] == 'showbanlist' ||
	          $command['params'][0] == 'listbans') {

		$admin->playerlist = array();
		$admin->msgs = array();

		// get new list of all banned players
		$newlist = get_banlist($aseco);

		if ($aseco->server->getGame() == 'TMN') {
			$head = 'Currently Banned Players:' . LF . 'Id     {#nick}Nick $g/{#login} Login' . LF;
			$msg = '';
			$pid = 1;
			$lines = 0;
			$admin->msgs[0] = 1;
			foreach ($newlist as $player) {
				$plarr = array();
				$plarr['login'] = $player[0];
				$admin->playerlist[] = $plarr;

				$msg .= '$g' . str_pad($pid, 2, '0', STR_PAD_LEFT) . '.   {#black}'
				        . str_ireplace('$w', '', $player[1]) . '$z / {#login}' . $player[0] . LF;
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
				$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors('{#server}> {#error}No banned player(s) found!'), $login);
			}

		} elseif ($aseco->server->getGame() == 'TMF') {
			$head = 'Currently Banned Players:';
			$msg = array();
			if ($aseco->settings['clickable_lists'])
				$msg[] = array('Id', '{#nick}Nick $g/{#login} Login$g (click to UnBan)');
			else
				$msg[] = array('Id', '{#nick}Nick $g/{#login} Login');
			$pid = 1;
			$lines = 0;
			$admin->msgs[0] = array(1, $head, array(0.9, 0.1, 0.8), array('Icons64x64_1', 'NotBuddy'));
			foreach ($newlist as $player) {
				$plarr = array();
				$plarr['login'] = $player[0];
				$admin->playerlist[] = $plarr;

				// format nickname & login
				$ply = '{#black}' . str_ireplace('$w', '', $player[1])
				       . '$z / {#login}' . $player[0];
				// add clickable button
				if ($aseco->settings['clickable_lists'] && $pid <= 200)
					$ply = array($ply, $pid+4600);  // action id

				$msg[] = array(str_pad($pid, 2, '0', STR_PAD_LEFT) . '.', $ply);
				$pid++;
				if (++$lines > 14) {
					$admin->msgs[] = $msg;
					$lines = 0;
					$msg = array();
					if ($aseco->settings['clickable_lists'])
						$msg[] = array('Id', '{#nick}Nick $g/{#login} Login$g (click to UnBan)');
					else
						$msg[] = array('Id', '{#nick}Nick $g/{#login} Login');
				}
			}
			// add if last batch exists
			if (count($msg) > 1)
				$admin->msgs[] = $msg;

			// display ManiaLink message
			if (count($admin->msgs) > 1) {
				display_manialink_multi($admin);
			} else {  // == 1
				$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors('{#server}> {#error}No banned player(s) found!'), $login);
			}
		}

	/**
	 * Displays the banned IPs list.
	 */
	} elseif ($command['params'][0] == 'showiplist' ||
	          $command['params'][0] == 'listips') {

		$admin->playerlist = array();
		$admin->msgs = array();

		// get new list of all banned IPs
		$newlist = $aseco->bannedips;
		if (empty($newlist)) {
			$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors('{#server}> {#error}No banned IP(s) found!'), $login);
			return;
		}

		if ($aseco->server->getGame() == 'TMN') {
			$head = 'Currently Banned IPs:' . LF . 'Id     {#login}IP' . LF;
			$msg = '';
			$pid = 1;
			$lines = 0;
			$admin->msgs[0] = 1;
			foreach ($newlist as $ip) {
				if ($ip != '') {
					$plarr = array();
					$plarr['ip'] = $ip;
					$admin->playerlist[] = $plarr;

					$msg .= '$g' . str_pad($pid, 2, '0', STR_PAD_LEFT) . '.   {#login}' . $ip . LF;
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
				$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors('{#server}> {#error}No banned IP(s) found!'), $login);
			}

		} elseif ($aseco->server->getGame() == 'TMF') {
			$head = 'Currently Banned IPs:';
			$msg = array();
			if ($aseco->settings['clickable_lists'])
				$msg[] = array('Id', '{#nick}IP$g (click to UnBan)');
			else
				$msg[] = array('Id', '{#nick}IP');
			$pid = 1;
			$lines = 0;
			$admin->msgs[0] = array(1, $head, array(0.6, 0.1, 0.5), array('Icons64x64_1', 'NotBuddy'));
			foreach ($newlist as $ip) {
				if ($ip != '') {
					$plarr = array();
					$plarr['ip'] = $ip;
					$admin->playerlist[] = $plarr;

					// format IP
					$ply = '{#black}' . $ip;
					// add clickable button
					if ($aseco->settings['clickable_lists'] && $pid <= 200)
						$ply = array($ply, -7900-$pid);  // action id

					$msg[] = array(str_pad($pid, 2, '0', STR_PAD_LEFT) . '.', $ply);
					$pid++;
					if (++$lines > 14) {
						$admin->msgs[] = $msg;
						$lines = 0;
						$msg = array();
						if ($aseco->settings['clickable_lists'])
							$msg[] = array('Id', '{#login}IP$g (click to UnBan)');
						else
							$msg[] = array('Id', '{#login}IP');
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
				$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors('{#server}> {#error}No banned IP(s) found!'), $login);
			}
		}

	/**
	 * Displays the black list.
	 */
	} elseif ($command['params'][0] == 'showblacklist' ||
	          $command['params'][0] == 'listblacks') {

		$admin->playerlist = array();
		$admin->msgs = array();

		// get new list of all blacklisted players
		$newlist = get_blacklist($aseco);

		if ($aseco->server->getGame() == 'TMN') {
			$head = 'Currently Blacklisted Players:' . LF . 'Id     {#nick}Nick $g/{#login} Login' . LF;
			$msg = '';
			$pid = 1;
			$lines = 0;
			$admin->msgs[0] = 1;
			foreach ($newlist as $player) {
				$plarr = array();
				$plarr['login'] = $player[0];
				$admin->playerlist[] = $plarr;

				$msg .= '$g' . str_pad($pid, 2, '0', STR_PAD_LEFT) . '.   {#black}'
			        . str_ireplace('$w', '', $player[1]) . '$z / {#login}' . $player[0] . LF;
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
				$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors('{#server}> {#error}No blacklisted player(s) found!'), $login);
			}

		} elseif ($aseco->server->getGame() == 'TMF') {
			$head = 'Currently Blacklisted Players:';
			$msg = array();
			if ($aseco->settings['clickable_lists'])
				$msg[] = array('Id', '{#nick}Nick $g/{#login} Login$g (click to UnBlack)');
			else
				$msg[] = array('Id', '{#nick}Nick $g/{#login} Login');
			$pid = 1;
			$lines = 0;
			$admin->msgs[0] = array(1, $head, array(0.9, 0.1, 0.8), array('Icons64x64_1', 'NotBuddy'));
			foreach ($newlist as $player) {
				$plarr = array();
				$plarr['login'] = $player[0];
				$admin->playerlist[] = $plarr;

				// format nickname & login
				$ply = '{#black}' . str_ireplace('$w', '', $player[1])
				       . '$z / {#login}' . $player[0];
				// add clickable button
				if ($aseco->settings['clickable_lists'] && $pid <= 200)
					$ply = array($ply, $pid+4800);  // action id

				$msg[] = array(str_pad($pid, 2, '0', STR_PAD_LEFT) . '.', $ply);
				$pid++;
				if (++$lines > 14) {
					$admin->msgs[] = $msg;
					$lines = 0;
					$msg = array();
					if ($aseco->settings['clickable_lists'])
						$msg[] = array('Id', '{#nick}Nick $g/{#login} Login$g (click to UnBlack)');
					else
						$msg[] = array('Id', '{#nick}Nick $g/{#login} Login');
				}
			}
			// add if last batch exists
			if (count($msg) > 1)
				$admin->msgs[] = $msg;

			// display ManiaLink message
			if (count($admin->msgs) > 1) {
				display_manialink_multi($admin);
			} else {  // == 1
				$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors('{#server}> {#error}No blacklisted player(s) found!'), $login);
			}
		}

	/**
	 * Displays the guest list.
	 */
	} elseif ($command['params'][0] == 'showguestlist' ||
	          $command['params'][0] == 'listguests') {

		$admin->playerlist = array();
		$admin->msgs = array();

		// get new list of all guest players
		$newlist = get_guestlist($aseco);

		if ($aseco->server->getGame() == 'TMN') {
			$head = 'Current Guest Players:' . LF . 'Id     {#nick}Nick $g/{#login} Login' . LF;
			$msg = '';
			$pid = 1;
			$lines = 0;
			$admin->msgs[0] = 1;
			foreach ($newlist as $player) {
				$plarr = array();
				$plarr['login'] = $player[0];
				$admin->playerlist[] = $plarr;

				$msg .= '$g' . str_pad($pid, 2, '0', STR_PAD_LEFT) . '.   {#black}'
			        . str_ireplace('$w', '', $player[1]) . '$z / {#login}' . $player[0] . LF;
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
				$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors('{#server}> {#error}No guest player(s) found!'), $login);
			}

		} elseif ($aseco->server->getGame() == 'TMF') {
			$head = 'Current Guest Players:';
			$msg = array();
			if ($aseco->settings['clickable_lists'])
				$msg[] = array('Id', '{#nick}Nick $g/{#login} Login$g (click to Remove)');
			else
				$msg[] = array('Id', '{#nick}Nick $g/{#login} Login');
			$pid = 1;
			$lines = 0;
			$admin->msgs[0] = array(1, $head, array(0.9, 0.1, 0.8), array('Icons128x128_1', 'Invite'));
			foreach ($newlist as $player) {
				$plarr = array();
				$plarr['login'] = $player[0];
				$admin->playerlist[] = $plarr;

				// format nickname & login
				$ply = '{#black}' . str_ireplace('$w', '', $player[1])
				       . '$z / {#login}' . $player[0];
				// add clickable button
				if ($aseco->settings['clickable_lists'] && $pid <= 200)
					$ply = array($ply, $pid+5000);  // action id

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
			// add if last batch exists
			if (count($msg) > 1)
				$admin->msgs[] = $msg;

			// display ManiaLink message
			if (count($admin->msgs) > 1) {
				display_manialink_multi($admin);
			} else {  // == 1
				$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors('{#server}> {#error}No guest player(s) found!'), $login);
			}
		}

	/**
	 * Saves the banned IPs list to bannedips.xml (default).
	 */
	} elseif ($command['params'][0] == 'writeiplist') {

		// write banned IPs file
		$filename = $aseco->settings['bannedips_file'];
		if (!$aseco->writeIPs()) {
			$message = '{#server}> {#error}Error writing {#highlite}$i ' . $filename . ' {#error}!';
		} else {
			// log console message
			$aseco->console('{1} [{2}] wrote ' . $filename . '!', $logtitle, $login);

			$message = '{#server}> {#highlite}' . $filename . ' {#admin}written';
		}
		// show chat message
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);

	/**
	 * Loads the banned IPs list from bannedips.xml (default).
	 */
	} elseif ($command['params'][0] == 'readiplist') {

		// read banned IPs file
		$filename = $aseco->settings['bannedips_file'];
		if (!$aseco->readIPs()) {
			$message = '{#server}> {#highlite}' . $filename . ' {#error}not found, or error reading!';
		} else {
			// log console message
			$aseco->console('{1} [{2}] read ' . $filename . '!', $logtitle, $login);

			$message = '{#server}> {#highlite}' . $filename . ' {#admin}read';
		}
		// show chat message
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);

	/**
	 * Saves the black list to blacklist.txt (default).
	 */
	} elseif ($command['params'][0] == 'writeblacklist') {

		$filename = $aseco->settings['blacklist_file'];
		$rtn = $aseco->client->query('SaveBlackList', $filename);
		if (!$rtn) {
			trigger_error('[' . $aseco->client->getErrorCode() . '] SaveBlackList - ' . $aseco->client->getErrorMessage(), E_USER_WARNING);
			$message = '{#server}> {#error}Error writing {#highlite}$i ' . $filename . ' {#error}!';
		} else {
			// log console message
			$aseco->console('{1} [{2}] wrote ' . $filename . '!', $logtitle, $login);

			$message = '{#server}> {#highlite}' . $filename . ' {#admin}written';
		}
		// show chat message
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);

	/**
	 * Loads the black list from blacklist.txt (default).
	 */
	} elseif ($command['params'][0] == 'readblacklist') {

		$filename = $aseco->settings['blacklist_file'];
		$rtn = $aseco->client->query('LoadBlackList', $filename);
		if (!$rtn) {
			trigger_error('[' . $aseco->client->getErrorCode() . '] LoadBlackList - ' . $aseco->client->getErrorMessage(), E_USER_WARNING);
			$message = '{#server}> {#highlite}' . $filename . ' {#error}not found, or error reading!';
		} else {
			// log console message
			$aseco->console('{1} [{2}] read ' . $filename . '!', $logtitle, $login);

			$message = '{#server}> {#highlite}' . $filename . ' {#admin}read';
		}
		// show chat message
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);

	/**
	 * Saves the guest list to guestlist.txt (default).
	 */
	} elseif ($command['params'][0] == 'writeguestlist') {

		$filename = $aseco->settings['guestlist_file'];
		$rtn = $aseco->client->query('SaveGuestList', $filename);
		if (!$rtn) {
			trigger_error('[' . $aseco->client->getErrorCode() . '] SaveGuestList - ' . $aseco->client->getErrorMessage(), E_USER_WARNING);
			$message = '{#server}> {#error}Error writing {#highlite}$i ' . $filename . ' {#error}!';
		} else {
			// log console message
			$aseco->console('{1} [{2}] wrote ' . $filename . '!', $logtitle, $login);

			$message = '{#server}> {#highlite}' . $filename . ' {#admin}written';
		}
		// show chat message
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);

	/**
	 * Loads the guest list from guestlist.txt (default).
	 */
	} elseif ($command['params'][0] == 'readguestlist') {

		$filename = $aseco->settings['guestlist_file'];
		$rtn = $aseco->client->query('LoadGuestList', $filename);
		if (!$rtn) {
			trigger_error('[' . $aseco->client->getErrorCode() . '] LoadGuestList - ' . $aseco->client->getErrorMessage(), E_USER_WARNING);
			$message = '{#server}> {#highlite}' . $filename . ' {#error}not found, or error loading!';
		} else {
			// log console message
			$aseco->console('{1} [{2}] read ' . $filename . '!', $logtitle, $login);

			$message = '{#server}> {#highlite}' . $filename . ' {#admin}read';
		}
		// show chat message
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);

	/**
	 * Cleans the ban list.
	 */
	} elseif ($command['params'][0] == 'cleanbanlist') {

		// clean server ban list
		$aseco->client->query('CleanBanList');

		// log console message
		$aseco->console('{1} [{2}] cleaned ban list!', $logtitle, $login);

		// show chat message
		$message = '{#server}> {#admin}Cleaned ban list!';
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);

	/**
	 * Cleans the banned IPs list.
	 */
	} elseif ($command['params'][0] == 'cleaniplist') {

		// clean banned IPs file
		$aseco->bannedips = array();
		$aseco->writeIPs();

		// log console message
		$aseco->console('{1} [{2}] cleaned banned IPs list!', $logtitle, $login);

		// show chat message
		$message = '{#server}> {#admin}Cleaned banned IPs list!';
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);

	/**
	 * Cleans the black list.
	 */
	} elseif ($command['params'][0] == 'cleanblacklist') {

		// clean server black list
		$aseco->client->query('CleanBlackList');

		// log console message
		$aseco->console('{1} [{2}] cleaned black list!', $logtitle, $login);

		// show chat message
		$message = '{#server}> {#admin}Cleaned black list!';
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);

	/**
	 * Cleans the guest list.
	 */
	} elseif ($command['params'][0] == 'cleanguestlist') {

		// clean server guest list
		$aseco->client->query('CleanGuestList');

		// log console message
		$aseco->console('{1} [{2}] cleaned guest list!', $logtitle, $login);

		// show chat message
		$message = '{#server}> {#admin}Cleaned guest list!';
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);

	/**
	 * Merges a global black list.
	 */
	} elseif ($command['params'][0] == 'mergegbl') {
		global $globalbl_url;  // from rasp.settings.php

		if (function_exists('admin_mergegbl')) {
			if (isset($command['params'][1]) && $command['params'][1] != '') {
				if (preg_match('/^https?:\/\/[-\w:.]+\//i', $command['params'][1])) {
					admin_mergegbl($aseco, $logtitle, $login, true, $command['params'][1]);  // from plugin.uptodate.php
				} else {
					$message = '{#server}> {#highlite}' . $command['params'][1] . ' {#error}is an invalid HTTP URL!';
					$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
				}
			} else {
				admin_mergegbl($aseco, $logtitle, $login, true, $globalbl_url);
			}
		} else {
			// show chat message
			$message = '{#server}> {#admin}Merge Global BL unavailable - include plugins.uptodate.php in plugins.xml';
			$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
		}

	/**
	 * Shows/reloads player access control.
	 */
	} elseif ($command['params'][0] == 'access') {

		if (function_exists('admin_access')) {
			$command['params'] = $command['params'][1];
			admin_access($aseco, $command);  // from plugin.access.php
		} else {
			// show chat message
			$message = '{#server}> {#admin}Access control unavailable - include plugins.access.php in plugins.xml';
			$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
		}

	/**
	 * Saves the track list to tracklist.txt (default).
	 */
	} elseif ($command['params'][0] == 'writetracklist') {

		$filename = $aseco->settings['default_tracklist'];
		// check for optional alternate filename
		if ($command['params'][1] != '') {
			$filename = $command['params'][1];
			if (!stristr($filename, '.txt')) {
				$filename .= '.txt';
			}
		}
		$rtn = $aseco->client->query('SaveMatchSettings', 'MatchSettings/' . $filename);
		if (!$rtn) {
			trigger_error('[' . $aseco->client->getErrorCode() . '] SaveMatchSettings - ' . $aseco->client->getErrorMessage(), E_USER_WARNING);
			$message = '{#server}> {#error}Error writing {#highlite}$i ' . $filename . ' {#error}!';
		} else {
			// should a random filter be added?
			if ($aseco->settings['writetracklist_random']) {
				$tracksfile = $aseco->server->trackdir . 'MatchSettings/' . $filename;
				// read the match settings file
				if (!$list = @file_get_contents($tracksfile)) {
					trigger_error('Could not read match settings file ' . $tracksfile . ' !', E_USER_WARNING);
				} else {
					// insert random filter after <gameinfos> section
					$list = preg_replace('/<\/gameinfos>/', '$0' . CRLF . CRLF .
					                     "\t<filter>" . CRLF .
					                     "\t\t<random_map_order>1</random_map_order>" . CRLF .
					                     "\t</filter>", $list);

					// write out the match settings file
					if (!@file_put_contents($tracksfile, $list)) {
						trigger_error('Could not write match settings file ' . $tracksfile . ' !', E_USER_WARNING);
					}
				}
			}

			// log console message
			$aseco->console('{1} [{2}] wrote track list: {3} !', $logtitle, $login, $filename);

			$message = '{#server}> {#highlite}' . $filename . '{#admin} written';

			// throw 'tracklist changed' event
			$aseco->releaseEvent('onTracklistChanged', array('write', null));
		}
		// show chat message
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);

	/**
	 * Loads the track list from tracklist.txt (default).
	 */
	} elseif ($command['params'][0] == 'readtracklist') {

		$filename = $aseco->settings['default_tracklist'];
		// check for optional alternate filename
		if ($command['params'][1] != '') {
			$filename = $command['params'][1];
			if (!stristr($filename, '.txt')) {
				$filename .= '.txt';
			}
		}
		if (file_exists($aseco->server->trackdir . 'MatchSettings/' . $filename)) {
			$rtn = $aseco->client->query('LoadMatchSettings', 'MatchSettings/' . $filename);
			if (!$rtn) {
				trigger_error('[' . $aseco->client->getErrorCode() . '] LoadMatchSettings - ' . $aseco->client->getErrorMessage(), E_USER_WARNING);
				$message = '{#server}> {#error}Error reading {#highlite}$i ' . $filename . ' {#error}!';
			} else {
				// get track count
				$cnt = $aseco->client->getResponse();

				// log console message
				$aseco->console('{1} [{2}] read track list: {3} ({4} tracks)!', $logtitle, $login, $filename, $cnt);

				$message = '{#server}> {#highlite}' . $filename . '{#admin} read with {#highlite}' . $cnt . '{#admin} track' . ($cnt == 1 ? '' : 's');

				// throw 'tracklist changed' event
				$aseco->releaseEvent('onTracklistChanged', array('read', null));
			}
		} else {
			$message = '{#server}> {#error}Cannot find {#highlite}$i ' . $filename . ' {#error}!';
		}
		// show chat message
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);

	/**
	 * Randomizes current track list.
	 */
	} elseif ($command['params'][0] == 'shuffle' ||
	          $command['params'][0] == 'shufflemaps') {
		global $autosave_matchsettings;  // from rasp.settings.php

		if ($aseco->settings['writetracklist_random']) {
			if ($autosave_matchsettings) {
				if (file_exists($aseco->server->trackdir . 'MatchSettings/' . $autosave_matchsettings)) {
					$rtn = $aseco->client->query('LoadMatchSettings', 'MatchSettings/' . $autosave_matchsettings);
					if (!$rtn) {
						trigger_error('[' . $aseco->client->getErrorCode() . '] LoadMatchSettings - ' . $aseco->client->getErrorMessage(), E_USER_WARNING);
						$message = '{#server}> {#error}Error reading {#highlite}$i ' . $autosave_matchsettings . ' {#error}!';
					} else {
						// get track count
						$cnt = $aseco->client->getResponse();

						// log console message
						$aseco->console('{1} [{2}] shuffled track list: {3} ({4} tracks)!', $logtitle, $login, $autosave_matchsettings, $cnt);

						$message = formatText('{#server}>> {#admin}{1}$z$s {#highlite}{2}$z$s{#admin} shuffled track list with {#highlite}{3}{#admin} track{4}!',
						                      $chattitle, $admin->nickname, $cnt, ($cnt == 1 ? '' : 's'));
						$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($message));
						return;
					}
				} else {
					$message = '{#server}> {#error}Cannot find autosave matchsettings file {#highlite}$i ' . $autosave_matchsettings . ' {#error}!';
				}
			} else {
				$message = '{#server}> {#error}No autosave matchsettings file defined in {#highlite}$i rasp.settings.php {#error}!';
			}
		} else {
			$message = '{#server}> {#error}No tracklist randomization defined in {#highlite}$i config.xml {#error}!';
		}
		// show chat message
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);

	/**
	 * Displays list of duplicate tracks.
	 */
	} elseif ($command['params'][0] == 'listdupes') {

		$admin->tracklist = array();
		$admin->msgs = array();

		// get new list of all tracks
		$aseco->client->resetError();
		$dupelist = array();
		$newlist = array();
		$done = false;
		$size = 300;
		$i = 0;
		while (!$done) {
			$aseco->client->query('GetChallengeList', $size, $i);
			$tracks = $aseco->client->getResponse();
			if (!empty($tracks)) {
				if ($aseco->client->isError()) {
					// warning if no tracks found
					if (empty($newlist))
						trigger_error('[' . $aseco->client->getErrorCode() . '] GetChallengeList - ' . $aseco->client->getErrorMessage() . ' - No tracks found!', E_USER_WARNING);
					$done = true;
					break;
				}
				foreach ($tracks as $trow) {
					$trow['Name'] = stripNewlines($trow['Name']);
					// store duplicate track
					if (isset($newlist[$trow['UId']])) {
						$dupelist[] = $trow;
					} else {
						$newlist[$trow['UId']] = $trow;
					}
				}
				if (count($tracks) < $size) {
					// got less than 300 tracks, might as well leave
					$done = true;
				} else {
					$i += $size;
				}
			} else {
				$done = true;
			}
		}

		// check for duplicate tracks
		if (!empty($dupelist)) {
			if ($aseco->server->getGame() == 'TMN') {
				$head = 'Duplicate Tracks On This Server:' . LF . 'Id        Name' . LF;
				$msg = '';
				$tid = 1;
				$lines = 0;
				$admin->msgs[0] = 1;
				foreach ($dupelist as $row) {
					$trackname = $row['Name'];
					if (!$aseco->settings['lists_colortracks'])
						$trackname = stripColors($trackname);

					// store track in player object for remove/erase
					$trkarr = array();
					$trkarr['name'] = $row['Name'];
					$trkarr['filename'] = $row['FileName'];
					$trkarr['uid'] = $row['UId'];
					$admin->tracklist[] = $trkarr;

					$msg .= '$g' . str_pad($tid, 3, '0', STR_PAD_LEFT) . '.  {#black}' . $trackname . LF;
					$tid++;
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
				} else {  // > 2
					$aseco->client->query('SendDisplayServerMessageToLogin', $login, $admin->msgs[1], 'Close', 'Next', 0);
				}

			} elseif ($aseco->server->getGame() == 'TMF') {
				$head = 'Duplicate Tracks On This Server:';
				$msg = array();
				if ($aseco->server->packmask != 'Stadium')
					$msg[] = array('Id', 'Name', 'Env');
				else
					$msg[] = array('Id', 'Name');
				$tid = 1;
				$lines = 0;
				// reserve extra width for $w tags
				$extra = ($aseco->settings['lists_colortracks'] ? 0.2 : 0);
				if ($aseco->server->packmask != 'Stadium')
					$admin->msgs[0] = array(1, $head, array(0.90+$extra, 0.15, 0.6+$extra, 0.15), array('Icons128x128_1', 'Challenge'));
				else
					$admin->msgs[0] = array(1, $head, array(0.75+$extra, 0.15, 0.6+$extra), array('Icons128x128_1', 'Challenge'));
				foreach ($dupelist as $row) {
					$trackname = stripColors($row['Name']);
					if (!$aseco->settings['lists_colortracks'])
						$trackname = stripColors($trackname);

					// store track in player object for remove/erase
					$trkarr = array();
					$trkarr['name'] = $row['Name'];
					$trkarr['environment'] = $row['Environnement'];
					$trkarr['filename'] = $row['FileName'];
					$trkarr['uid'] = $row['UId'];
					$admin->tracklist[] = $trkarr;

					if ($aseco->server->packmask != 'Stadium')
						$msg[] = array(str_pad($tid, 3, '0', STR_PAD_LEFT) . '.',
						               '{#black}' . $trackname,
						               $trkarr['environment']);
					else
						$msg[] = array(str_pad($tid, 3, '0', STR_PAD_LEFT) . '.',
						               '{#black}' . $trackname);
					$tid++;
					if (++$lines > 14) {
						$admin->msgs[] = $msg;
						$lines = 0;
						$msg = array();
						if ($aseco->server->packmask != 'Stadium')
							$msg[] = array('Id', 'Name', 'Env');
						else
							$msg[] = array('Id', 'Name');
					}
				}
				// add if last batch exists
				if (count($msg) > 1)
					$admin->msgs[] = $msg;

				// display ManiaLink message
				display_manialink_multi($admin);
			}

		} else {
			$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors('{#server}> {#error}No duplicate track(s) found!'), $login);
			return;
		}

	/**
	 * Remove a track from the active rotation, optionally erase track file too.
	 * Doesn't update match settings unfortunately - command 'writetracklist' will though.
	 */
	} elseif (($command['params'][0] == 'remove' && $command['params'][1] != '') ||
	          ($command['params'][0] == 'erase' && $command['params'][1] != '')) {
		global $rasp;  // from plugin.rasp.php

		// verify parameter
		$param = $command['params'][1];
		if (is_numeric($param) && $param >= 0) {
			if (empty($admin->tracklist)) {
				$message = $rasp->messages['LIST_HELP'][0];
				$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
				return;
			}
			// find track by given #
			$tid = ltrim($param, '0');
			$tid--;
			if (array_key_exists($tid, $admin->tracklist)) {
				$name = stripColors($admin->tracklist[$tid]['name']);
				$filename = $aseco->server->trackdir . $admin->tracklist[$tid]['filename'];
				$rtn = $aseco->client->query('RemoveChallenge', $filename);
				if (!$rtn) {
					trigger_error('[' . $aseco->client->getErrorCode() . '] RemoveChallenge - ' . $aseco->client->getErrorMessage(), E_USER_WARNING);
					$message = formatText('{#server}> {#error}Error removing track {#highlite}$i {1} {#error}!',
					                      $filename);
					$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
				} else {
					$message = formatText('{#server}>> {#admin}{1}$z$s {#highlite}{2}$z$s {#admin}removes track: {#highlite}{3}',
					                      $chattitle, $admin->nickname, $name);
					if ($command['params'][0] == 'erase' && is_file($filename)) {
						if (unlink($filename)) {
							$message = formatText('{#server}>> {#admin}{1}$z$s {#highlite}{2}$z$s {#admin}erases track: {#highlite}{3}',
							                      $chattitle, $admin->nickname, $name);
						} else {
							$message = '{#server}> {#error}Delete file {#highlite}$i ' . $filename . '{#error} failed';
							$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
							$message = formatText('{#server}>> {#admin}{1}$z$s {#highlite}{2}$z$s {#admin}erase track failed: {#highlite}{3}',
							                      $chattitle, $admin->nickname, $name);
						}
					}
					// show chat message
					$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($message));
					// log console message
					$aseco->console('{1} [{2}] ' . $command['params'][0] . 'd track {3}', $logtitle, $login, stripColors($name, false));

					// throw 'tracklist changed' event
					$aseco->releaseEvent('onTracklistChanged', array('remove', $filename));
				}
			} else {
				$message = $rasp->messages['JUKEBOX_NOTFOUND'][0];
				$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
			}
		} else {
			$message = $rasp->messages['JUKEBOX_HELP'][0];
			$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
		}

	/**
	 * Remove current track from the active rotation, optionally erase track file too.
	 * Doesn't update match settings unfortunately - command 'writetracklist' will though.
	 */
	} elseif ($command['params'][0] == 'removethis' ||
	          $command['params'][0] == 'erasethis') {

		// get current track info and remove it from rotation
		$name = stripColors($aseco->server->challenge->name);
		$filename = $aseco->server->trackdir . $aseco->server->challenge->filename;
		$rtn = $aseco->client->query('RemoveChallenge', $filename);
		if (!$rtn) {
			trigger_error('[' . $aseco->client->getErrorCode() . '] RemoveChallenge - ' . $aseco->client->getErrorMessage(), E_USER_WARNING);
			$message = formatText('{#server}> {#error}Error removing track {#highlite}$i {1} {#error}!',
			                      $filename);
			$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
		} else {
			$message = formatText('{#server}>> {#admin}{1}$z$s {#highlite}{2}$z$s {#admin}removes current track: {#highlite}{3}',
			                      $chattitle, $admin->nickname, $name);
			if ($command['params'][0] == 'erasethis' && is_file($filename)) {
				if (unlink($filename)) {
					$message = formatText('{#server}>> {#admin}{1}$z$s {#highlite}{2}$z$s {#admin}erases current track: {#highlite}{3}',
					                      $chattitle, $admin->nickname, $name);
				} else {
					$message = '{#server}> {#error}Delete file {#highlite}$i ' . $filename . '{#error} failed';
					$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
					$message = formatText('{#server}>> {#admin}{1}$z$s {#highlite}{2}$z$s {#admin}erase track failed: {#highlite}{3}',
					                      $chattitle, $admin->nickname, $name);
				}
			}
			// show chat message
			$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($message));
			// log console message
			$aseco->console('{1} [{2}] ' . $command['params'][0] . '-ed track {3}', $logtitle, $login, stripColors($name, false));

			// throw 'tracklist changed' event
			$aseco->releaseEvent('onTracklistChanged', array('remove', $filename));
		}

	/**
	 * Adds a player to global mute/ignore list
	 */
	} elseif (($command['params'][0] == 'mute' || $command['params'][0] == 'ignore')
	          && $command['params'][1] != '') {
		global $muting_available;  // from plugin.muting.php

		if ($aseco->server->getGame() != 'TMF') {
			// check for muting plugin
			if ($muting_available) {
				// get player information
				if ($target = $aseco->getPlayerParam($admin, $command['params'][1])) {
					// check if not yet in global mute/ignore list
					if (!in_array($target->login, $aseco->server->mutelist)) {
						// mute this player
						$aseco->server->mutelist[] = $target->login;

						// log console message
						$aseco->console('{1} [{2}] mutes player [{3} : {4}]!', $logtitle, $login, $target->login, stripColors($target->nickname, false));

						// show chat message
						$message = formatText('{#server}>> {#admin}{1}$z$s {#highlite}{2}$z$s{#admin} mutes {#highlite}{3}$z$s{#admin} !',
						                      $chattitle, $admin->nickname, str_ireplace('$w', '', $target->nickname));
						$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($message));
					} else {
						$message = '{#server}> {#error}Player {#highlite}$i ' . stripColors($target->nickname) . '{#error} is already in global mute/ignore list!';
						$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
					}
				}
			} else {
				$message = '{#server}> {#error}Player muting not available!';
				$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
			}
		} else {  // TMF
			// get player information
			if ($target = $aseco->getPlayerParam($admin, $command['params'][1])) {
				// ignore the player
				$aseco->client->query('Ignore', $target->login);

				// check if in global mute/ignore list
				if (!in_array($target->login, $aseco->server->mutelist)) {
					// add player to list
					$aseco->server->mutelist[] = $target->login;
				}

				// log console message
				$aseco->console('{1} [{2}] ignores player {3}!', $logtitle, $login, stripColors($target->nickname, false));

				// show chat message
				$message = formatText('{#server}>> {#admin}{1}$z$s {#highlite}{2}$z$s{#admin} ignores {#highlite}{3}$z$s{#admin} !',
				                      $chattitle, $admin->nickname, str_ireplace('$w', '', $target->nickname));
				$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($message));
			}
		}

	/**
	 * Removes a player from global mute/ignore list
	 */
	} elseif (($command['params'][0] == 'unmute' || $command['params'][0] == 'unignore')
	          && $command['params'][1] != '') {
		global $muting_available;  // from plugin.muting.php

		if ($aseco->server->getGame() != 'TMF') {
			// check for muting plugin
			if ($muting_available) {
				// get player information
				if ($target = $aseco->getPlayerParam($admin, $command['params'][1], true)) {
					// check if already in global mute/ignore list
					if (($i = array_search($target->login, $aseco->server->mutelist)) !== false) {
						// unmute this player
						$aseco->server->mutelist[$i] = '';

						// log console message
						$aseco->console('{1} [{2}] unmutes player [{3} : {4}]!', $logtitle, $login, $target->login, stripColors($target->nickname, false));

						// show chat message
						$message = formatText('{#server}>> {#admin}{1}$z$s {#highlite}{2}$z$s{#admin} unmutes {#highlite}{3}$z$s{#admin} !',
						                      $chattitle, $admin->nickname, str_ireplace('$w', '', $target->nickname));
						$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($message));
					} else {
						$message = '{#server}> {#error}Player {#highlite}$i ' . stripColors($target->nickname) . '{#error} is not in global mute/ignore list!';
						$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
					}
				}
			} else {
				$message = '{#server}> {#error}Player muting not available!';
				$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
			}
		} else {  // TMF
			// get player information
			if ($target = $aseco->getPlayerParam($admin, $command['params'][1], true)) {
				// unignore the player
				$rtn = $aseco->client->query('UnIgnore', $target->login);
				if (!$rtn) {
					$message = formatText('{#server}> {#highlite}{1}{#error} is not an ignored player!',
					                      $command['params'][1]);
					$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
				} else {
					// check if in global mute/ignore list
					if (($i = array_search($target->login, $aseco->server->mutelist)) !== false) {
						// remove player from list
						$aseco->server->mutelist[$i] = '';
					}

					// log console message
					$aseco->console('{1} [{2}] unignores player {3}', $logtitle, $login, stripColors($target->nickname, false));

					// show chat message
					$message = formatText('{#server}> {#admin}{1}$z$s {#highlite}{2}$z$s{#admin} un-ignores {#highlite}{3}',
					                      $chattitle, $admin->nickname, str_ireplace('$w', '', $target->nickname));
					$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
				}
			}
		}

	/**
	 * Displays the global mute/ignore list.
	 */
	} elseif ($command['params'][0] == 'mutelist' ||
	          $command['params'][0] == 'listmutes' ||
	          $command['params'][0] == 'ignorelist' ||
	          $command['params'][0] == 'listignores') {
		global $muting_available;  // from plugin.muting.php

		$admin->playerlist = array();
		$admin->msgs = array();

		if ($aseco->server->getGame() == 'TMN') {
			// check for muting plugin
			if ($muting_available) {
				// check for muted players
				if (empty($aseco->server->mutelist)) {
					$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors('{#server}> {#error}No muted/ignored players found!'), $login);
					return;
				}

				$head = 'Globally Muted/Ignored Players:' . LF . 'Id     {#nick}Nick $g/{#login} Login' . LF;
				$msg = '';
				$pid = 1;
				$lines = 0;
				$admin->msgs[0] = 1;
				foreach ($aseco->server->mutelist as $pl) {
					if ($pl != '') {
						$plarr = array();
						$plarr['login'] = $pl;
						$admin->playerlist[] = $plarr;

						$msg .= '$g' . str_pad($pid, 2, '0', STR_PAD_LEFT) . '.   {#black}'
					        . $aseco->getPlayerNick($pl) . '$z / {#login}' . $pl . LF;
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
					$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors('{#server}> {#error}No muted/ignored players found!'), $login);
				}

			} else {
				$message = '{#server}> {#error}Player muting not available!';
				$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
			}

		} elseif ($aseco->server->getGame() == 'TMF') {
			// get new list of all ignored players
			$newlist = get_ignorelist($aseco);

			$head = 'Globally Muted/Ignored Players:';
			$msg = array();
			if ($aseco->settings['clickable_lists'])
				$msg[] = array('Id', '{#nick}Nick $g/{#login} Login$g (click to UnIgnore)');
			else
				$msg[] = array('Id', '{#nick}Nick $g/{#login} Login');
			$pid = 1;
			$lines = 0;
			$admin->msgs[0] = array(1, $head, array(0.9, 0.1, 0.8), array('Icons128x128_1', 'Padlock', 0.01));
			foreach ($newlist as $player) {
				$plarr = array();
				$plarr['login'] = $player[0];
				$admin->playerlist[] = $plarr;

				// format nickname & login
				$ply = '{#black}' . str_ireplace('$w', '', $player[1])
				       . '$z / {#login}' . $player[0];
				// add clickable button
				if ($aseco->settings['clickable_lists'] && $pid <= 200)
					$ply = array($ply, $pid+4400);  // action id

				$msg[] = array(str_pad($pid, 2, '0', STR_PAD_LEFT) . '.', $ply);
				$pid++;
				if (++$lines > 14) {
					$admin->msgs[] = $msg;
					$lines = 0;
					$msg = array();
					if ($aseco->settings['clickable_lists'])
						$msg[] = array('Id', '{#nick}Nick $g/{#login} Login$g (click to UnIgnore)');
					else
						$msg[] = array('Id', '{#nick}Nick $g/{#login} Login');
				}
			}
			// add if last batch exists
			if (count($msg) > 1)
				$admin->msgs[] = $msg;

			// display ManiaLink message
			if (count($admin->msgs) > 1) {
				display_manialink_multi($admin);
			} else {  // == 1
				$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors('{#server}> {#error}No muted/ignored players found!'), $login);
			}
		}

	/**
	 * Cleans the global mute/ignore list.
	 */
	} elseif ($command['params'][0] == 'cleanmutes' ||
	          $command['params'][0] == 'cleanignores') {

		// clean internal and server list
		$aseco->server->mutelist = array();
		if ($aseco->server->getGame() == 'TMF')
			$aseco->client->query('CleanIgnoreList');

		// log console message
		$aseco->console('{1} [{2}] cleaned global mute/ignore list!', $logtitle, $login);

		// show chat message
		$message = '{#server}> {#admin}Cleaned global mute/ignore list!';
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);

	/**
	 * Adds a new admin.
	 */
	} elseif ($command['params'][0] == 'addadmin' && $command['params'][1] != '') {

		// get player information
		if ($target = $aseco->getPlayerParam($admin, $command['params'][1])) {
			// check if player not already admin
			if (!$aseco->isAdminL($target->login)) {
				// add the new admin
				$aseco->admin_list['TMLOGIN'][] = $target->login;
				$aseco->admin_list['IPADDRESS'][] = ($aseco->settings['auto_admin_addip'] ? $target->ip : '');
				$aseco->writeLists();

				// log console message
				$aseco->console('{1} [{2}] adds admin [{3} : {4}]!', $logtitle, $login, $target->login, stripColors($target->nickname, false));

				// show chat message
				$message = formatText('{#server}>> {#admin}{1}$z$s {#highlite}{2}$z$s{#admin} adds new {3}$z$s{#admin}: {#highlite}{4}$z$s{#admin} !',
				                      $chattitle, $admin->nickname,
				                      $aseco->titles['ADMIN'][0], $target->nickname);
				$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($message));
			} else {
				$message = formatText('{#server}> {#error}Login {#highlite}$i {1}{#error} is already in Admin List!', $target->login);
				$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
			}
		}

	/**
	 * Removes an admin.
	 */
	} elseif ($command['params'][0] == 'removeadmin' && $command['params'][1] != '') {

		// get player information
		if ($target = $aseco->getPlayerParam($admin, $command['params'][1], true)) {
			// check if player is indeed admin
			if ($aseco->isAdminL($target->login)) {
				$i = array_search($target->login, $aseco->admin_list['TMLOGIN']);
				$aseco->admin_list['TMLOGIN'][$i] = '';
				$aseco->admin_list['IPADDRESS'][$i] = '';
				$aseco->writeLists();

				// log console message
				$aseco->console('{1} [{2}] removes admin [{3} : {4}]!', $logtitle, $login, $target->login, stripColors($target->nickname, false));

				// show chat message
				$message = formatText('{#server}>> {#admin}{1}$z$s {#highlite}{2}$z$s{#admin} removes {3}$z$s{#admin}: {#highlite}{4}$z$s{#admin} !',
				                      $chattitle, $admin->nickname,
				                      $aseco->titles['ADMIN'][0], $target->nickname);
				$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($message));
			} else {
				$message = formatText('{#server}> {#error}Login {#highlite}$i {1}{#error} is not in Admin List!', $target->login);
				$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
			}
		}

	/**
	 * Adds a new operator.
	 */
	} elseif ($command['params'][0] == 'addop' && $command['params'][1] != '') {

		// get player information
		if ($target = $aseco->getPlayerParam($admin, $command['params'][1])) {
			// check if player not already operator
			if (!$aseco->isOperatorL($target->login)) {
				// add the new operator
				$aseco->operator_list['TMLOGIN'][] = $target->login;
				$aseco->operator_list['IPADDRESS'][] = ($aseco->settings['auto_admin_addip'] ? $target->ip : '');
				$aseco->writeLists();

				// log console message
				$aseco->console('{1} [{2}] adds operator [{3} : {4}]!', $logtitle, $login, $target->login, stripColors($target->nickname, false));

				// show chat message
				$message = formatText('{#server}>> {#admin}{1}$z$s {#highlite}{2}$z$s{#admin} adds new {3}$z$s{#admin}: {#highlite}{4}$z$s{#admin} !',
				                      $chattitle, $admin->nickname,
				                      $aseco->titles['OPERATOR'][0], $target->nickname);
				$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($message));
			} else {
				$message = formatText('{#server}> {#error}Login {#highlite}$i {1}{#error} is already in Operator List!', $target->login);
				$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
			}
		}

	/**
	 * Removes an operator.
	 */
	} elseif ($command['params'][0] == 'removeop' && $command['params'][1] != '') {

		// get player information
		if ($target = $aseco->getPlayerParam($admin, $command['params'][1], true)) {
			// check if player is indeed operator
			if ($aseco->isOperatorL($target->login)) {
				$i = array_search($target->login, $aseco->operator_list['TMLOGIN']);
				$aseco->operator_list['TMLOGIN'][$i] = '';
				$aseco->operator_list['IPADDRESS'][$i] = '';
				$aseco->writeLists();

				// log console message
				$aseco->console('{1} [{2}] removes operator [{3} : {4}]!', $logtitle, $login, $target->login, stripColors($target->nickname, false));

				// show chat message
				$message = formatText('{#server}>> {#admin}{1}$z$s {#highlite}{2}$z$s{#admin} removes {3}$z$s{#admin}: {#highlite}{4}$z$s{#admin} !',
				                      $chattitle, $admin->nickname,
				                      $aseco->titles['OPERATOR'][0], $target->nickname);
				$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($message));
			} else {
				$message = formatText('{#server}> {#error}Login {#highlite}$i {1}{#error} is not in Operator List!', $target->login);
				$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
			}
		}

	/**
	 * Displays the masteradmins list.
	 */
	} elseif ($command['params'][0] == 'listmasters') {

		$admin->playerlist = array();
		$admin->msgs = array();

		if ($aseco->server->getGame() == 'TMN') {
			$head = 'Current MasterAdmins:' . LF . 'Id     {#nick}Nick $g/{#login} Login' . LF;
			$msg = '';
			$pid = 1;
			$lines = 0;
			$admin->msgs[0] = 1;
			foreach ($aseco->masteradmin_list['TMLOGIN'] as $player) {
				// skip any LAN logins
				if ($player != '' && !isLANLogin($player)) {
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
				$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors('{#server}> {#error}No masteradmin(s) found!'), $login);
			}

		} elseif ($aseco->server->getGame() == 'TMF') {
			$head = 'Current MasterAdmins:';
			$msg = array();
			$msg[] = array('Id', '{#nick}Nick $g/{#login} Login');
			$pid = 1;
			$lines = 0;
			$admin->msgs[0] = array(1, $head, array(0.9, 0.1, 0.8), array('Icons128x128_1', 'Solo'));
			foreach ($aseco->masteradmin_list['TMLOGIN'] as $player) {
				// skip any LAN logins
				if ($player != '' && !isLANLogin($player)) {
					$plarr = array();
					$plarr['login'] = $player;
					$admin->playerlist[] = $plarr;

					$msg[] = array(str_pad($pid, 2, '0', STR_PAD_LEFT) . '.',
					               '{#black}' . $aseco->getPlayerNick($player)
					               . '$z / {#login}' . $player);
					$pid++;
					if (++$lines > 14) {
						$admin->msgs[] = $msg;
						$lines = 0;
						$msg = array();
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
				$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors('{#server}> {#error}No masteradmin(s) found!'), $login);
			}
		}

	/**
	 * Displays the admins list.
	 */
	} elseif ($command['params'][0] == 'listadmins') {

		if (empty($aseco->admin_list['TMLOGIN'])) {
			$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors('{#server}> {#error}No admin(s) found!'), $login);
			return;
		}

		$admin->playerlist = array();
		$admin->msgs = array();

		if ($aseco->server->getGame() == 'TMN') {
			$head = 'Current Admins:' . LF . 'Id     {#nick}Nick $g/{#login} Login' . LF;
			$msg = '';
			$pid = 1;
			$lines = 0;
			$admin->msgs[0] = 1;
			foreach ($aseco->admin_list['TMLOGIN'] as $player) {
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
				$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors('{#server}> {#error}No admin(s) found!'), $login);
			}

		} elseif ($aseco->server->getGame() == 'TMF') {
			$head = 'Current Admins:';
			$msg = array();
			$msg[] = array('Id', '{#nick}Nick $g/{#login} Login');
			$pid = 1;
			$lines = 0;
			$admin->msgs[0] = array(1, $head, array(0.9, 0.1, 0.8), array('Icons128x128_1', 'Solo'));
			foreach ($aseco->admin_list['TMLOGIN'] as $player) {
				if ($player != '') {
					$plarr = array();
					$plarr['login'] = $player;
					$admin->playerlist[] = $plarr;

					$msg[] = array(str_pad($pid, 2, '0', STR_PAD_LEFT) . '.',
					               '{#black}' . $aseco->getPlayerNick($player)
					               . '$z / {#login}' . $player);
					$pid++;
					if (++$lines > 14) {
						$admin->msgs[] = $msg;
						$lines = 0;
						$msg = array();
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
				$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors('{#server}> {#error}No admin(s) found!'), $login);
			}
		}

	/**
	 * Displays the operators list.
	 */
	} elseif ($command['params'][0] == 'listops') {

		if (empty($aseco->operator_list['TMLOGIN'])) {
			$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors('{#server}> {#error}No operator(s) found!'), $login);
			return;
		}

		$admin->playerlist = array();
		$admin->msgs = array();

		if ($aseco->server->getGame() == 'TMN') {
			$head = 'Current Operators:' . LF . 'Id     {#nick}Nick $g/{#login} Login' . LF;
			$msg = '';
			$pid = 1;
			$lines = 0;
			$admin->msgs[0] = 1;
			foreach ($aseco->operator_list['TMLOGIN'] as $player) {
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
				$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors('{#server}> {#error}No operator(s) found!'), $login);
			}

		} elseif ($aseco->server->getGame() == 'TMF') {
			$head = 'Current Operators:';
			$msg = array();
			$msg[] = array('Id', '{#nick}Nick $g/{#login} Login');
			$pid = 1;
			$lines = 0;
			$admin->msgs[0] = array(1, $head, array(0.9, 0.1, 0.8), array('Icons128x128_1', 'Solo'));
			foreach ($aseco->operator_list['TMLOGIN'] as $player) {
				if ($player != '') {
					$plarr = array();
					$plarr['login'] = $player;
					$admin->playerlist[] = $plarr;

					$msg[] = array(str_pad($pid, 2, '0', STR_PAD_LEFT) . '.',
					               '{#black}' . $aseco->getPlayerNick($player)
					               . '$z / {#login}' . $player);
					$pid++;
					if (++$lines > 14) {
						$admin->msgs[] = $msg;
						$lines = 0;
						$msg = array();
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
				$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors('{#server}> {#error}No operator(s) found!'), $login);
			}
		}

	/**
	 * Show/change an admin ability
	 */
	} elseif ($command['params'][0] == 'adminability') {

		// check for ability parameter
		if ($command['params'][1] != '') {
			// map to uppercase before checking list
			$ability = strtoupper($command['params'][1]);

			// check for valid ability
			if (isset($aseco->adm_abilities[$ability])) {
				if (isset($command['params'][2]) && $command['params'][2] != '') {
					// update ability
					if (strtoupper($command['params'][2]) == 'ON') {
						$aseco->adm_abilities[$ability][0] = true;
						$aseco->writeLists();

						// log console message
						$aseco->console('{1} [{2}] set new Admin ability: {3} ON', $logtitle, $login, strtolower($ability));
					}
					elseif (strtoupper($command['params'][2]) == 'OFF') {
						$aseco->adm_abilities[$ability][0] = false;
						$aseco->writeLists();

						// log console message
						$aseco->console('{1} [{2}] set new Admin ability: {3} OFF', $logtitle, $login, strtolower($ability));
					}  // else ignore bogus parameter
				}
				// show current/new ability message
				$message = formatText('{#server}> {#admin}{1}$z$s {#admin}ability {#highlite}{2}{#admin} is: {#highlite}{3}',
				                      $aseco->titles['ADMIN'][0], strtolower($ability),
				                      ($aseco->adm_abilities[$ability][0] ? 'ON' : 'OFF'));
				$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
			} else {
				$message = formatText('{#server}> {#error}No ability {#highlite}$i {1}{#error} known!',
				                      $command['params'][1]);
				$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
			}
		} else {
			$message = '{#server}> {#error}No ability specified - see {#highlite}$i /admin helpall{#error} and {#highlite}$i /admin listabilities{#error}!';
			$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
		}

	/**
	 * Show/change an operator ability
	 */
	} elseif ($command['params'][0] == 'opability') {

		// check for ability parameter
		if ($command['params'][1] != '') {
			// map to uppercase before checking list
			$ability = strtoupper($command['params'][1]);

			// check for valid ability
			if (isset($aseco->op_abilities[$ability])) {
				if (isset($command['params'][2]) && $command['params'][2] != '') {
					// update ability
					if (strtoupper($command['params'][2]) == 'ON') {
						$aseco->op_abilities[$ability][0] = true;
						$aseco->writeLists();

						// log console message
						$aseco->console('{1} [{2}] set new Operator ability: {3} ON', $logtitle, $login, strtolower($ability));
					}
					elseif (strtoupper($command['params'][2]) == 'OFF') {
						$aseco->op_abilities[$ability][0] = false;
						$aseco->writeLists();

						// log console message
						$aseco->console('{1} [{2}] set new Operator ability: {3} OFF', $logtitle, $login, strtolower($ability));
					}  // else ignore bogus parameter
				}
				// show current/new ability message
				$message = formatText('{#server}> {#admin}{1}$z$s {#admin}ability {#highlite}{2}{#admin} is: {#highlite}{3}',
				                      $aseco->titles['OPERATOR'][0], strtolower($ability),
				                      ($aseco->op_abilities[$ability][0] ? 'ON' : 'OFF'));
				$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
			} else {
				$message = formatText('{#server}> {#error}No ability {#highlite}$i {1}{#error} known!',
				                      $command['params'][1]);
				$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
			}
		} else {
			$message = '{#server}> {#error}No ability specified - see {#highlite}$i /admin helpall{#error} and {#highlite}$i /admin listabilities{#error}!';
			$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
		}

	/**
	 * Displays Admin and Operator abilities
	 */
	} elseif ($command['params'][0] == 'listabilities') {

		$master = false;
		if ($aseco->isMasterAdminL($login)) {
			if ($command['params'][1] == '') {
				$master = true;
				$abilities = $aseco->adm_abilities;
				$title = 'MasterAdmin';
			} else {
				if (stripos('admin', $command['params'][1]) === 0) {
					$abilities = $aseco->adm_abilities;
					$title = 'Admin';
				}
				elseif (stripos('operator', $command['params'][1]) === 0) {
					$abilities = $aseco->op_abilities;
					$title = 'Operator';
				}
				// all three above fall through to listing below
				else {
					$message = formatText('{#server}> {#highlite}{1}{#error} is not a valid administrator tier!',
					                      $command['params'][1]);
					$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
					return;
				}
			}
		}
		elseif ($aseco->isAdminL($login)) {
			$abilities = $aseco->adm_abilities;
			$title = 'Admin';
		}
		else {  // isOperator
			$abilities = $aseco->op_abilities;
			$title = 'Operator';
		}

		if ($aseco->server->getGame() == 'TMN') {
			// compile current ability listing
			$help = 'Current ' . $title . ' abilities:' . LF . LF;
			$chat = false;
			foreach ($abilities as $ability => $value) {
				switch (strtolower($ability)) {
				case 'chat_pma':
					if ($value[0] || $master) {
						$help .= 'chat_pma          : {#black}/pma$g sends a PM to player & admins' . LF;
						$chat = true;
					}
					break;
				case 'chat_bestworst':
					if ($value[0] || $master) {
						$help .= 'chat_bestworst : {#black}/best$g & {#black}/worst$g accept login/Player_ID' . LF;
						$chat = true;
					}
					break;
				case 'chat_statsip':
					if ($value[0] || $master) {
						$help .= 'chat_statsip       : {#black}/stats$g includes IP address' . LF;
						$chat = true;
					}
					break;
				case 'chat_summary':
					if ($value[0] || $master) {
						$help .= 'chat_summary  : {#black}/summary$g accepts login/Player_ID' . LF;
						$chat = true;
					}
					break;
				case 'chat_jb_multi':
					if ($value[0] || $master) {
						$help .= 'chat_jb_multi    : {#black}/jukebox$g adds more than one track' . LF;
						$chat = true;
					}
					break;
				case 'chat_jb_recent':
					if ($value[0] || $master) {
						$help .= 'chat_jb_recent : {#black}/jukebox$g adds recently played track' . LF;
						$chat = true;
					}
					break;
				case 'chat_add_tref':
					if ($value[0] || $master) {
						$help .= 'chat_add_tref   : {#black}/add trackref$g writes TMX trackref file' . LF;
						$chat = true;
					}
					break;
				case 'chat_match':
					if ($value[0] || $master) {
						$help .= 'chat_match       : {#black}/match$g allows match control' . LF;
						$chat = true;
					}
					break;
				case 'chat_tc_listen':
					if ($value[0] || $master) {
						$help .= 'chat_tc_listen   : {#black}/tc$g will copy team chat to admins' . LF;
						$chat = true;
					}
					break;
				case 'chat_jfreu':
					if ($value[0] || $master) {
						$help .= 'chat_jfreu          : use all {#black}/jfreu$g commands' . LF;
						$chat = true;
					}
					break;
				case 'noidlekick_play':
					if ($value[0] || $master) {
						$help .= 'noidlekick_play : no idlekick when {#black}player$g' . LF;
						$chat = true;
					}
					break;
				case 'noidlekick_spec':
					if ($value[0] || $master) {
						$help .= 'noidlekick_spec: no idlekick when {#black}spectator$g' . LF;
						$chat = true;
					}
					break;
				}
			}

			if ($chat) $help .= LF;
			$help .= 'See {#black}/admin helpall$g for available /admin commands';

			// display popup message
			$aseco->client->query('SendDisplayServerMessageToLogin', $login, $aseco->formatColors($help), 'OK', '', 0);

		} elseif ($aseco->server->getGame() == 'TMF') {
			// compile current ability listing
			$header = 'Current ' . $title . ' abilities:';
			$help = array();
			$chat = false;
			foreach ($abilities as $ability => $value) {
				switch (strtolower($ability)) {
				case 'chat_pma':
					if ($value[0] || $master) {
						$help[] = array('chat_pma', '{#black}/pma$g sends a PM to player & admins');
						$chat = true;
					}
					break;
				case 'chat_bestworst':
					if ($value[0] || $master) {
						$help[] = array('chat_bestworst', '{#black}/best$g & {#black}/worst$g accept login/Player_ID');
						$chat = true;
					}
					break;
				case 'chat_statsip':
					if ($value[0] || $master) {
						$help[] = array('chat_statsip', '{#black}/stats$g includes IP address');
						$chat = true;
					}
					break;
				case 'chat_summary':
					if ($value[0] || $master) {
						$help[] = array('chat_summary', '{#black}/summary$g accepts login/Player_ID');
						$chat = true;
					}
					break;
				case 'chat_jb_multi':
					if ($value[0] || $master) {
						$help[] = array('chat_jb_multi', '{#black}/jukebox$g adds more than one track');
						$chat = true;
					}
					break;
				case 'chat_jb_recent':
					if ($value[0] || $master) {
						$help[] = array('chat_jb_recent', '{#black}/jukebox$g adds recently played track');
						$chat = true;
					}
					break;
				case 'chat_add_tref':
					if ($value[0] || $master) {
						$help[] = array('chat_add_tref', '{#black}/add trackref$g writes TMX trackref file');
						$chat = true;
					}
					break;
				case 'chat_match':
					if ($value[0] || $master) {
						$help[] = array('chat_match', '{#black}/match$g allows match control');
						$chat = true;
					}
					break;
				case 'chat_tc_listen':
					if ($value[0] || $master) {
						$help[] = array('chat_tc_listen', '{#black}/tc$g will copy team chat to admins');
						$chat = true;
					}
					break;
				case 'chat_jfreu':
					if ($value[0] || $master) {
						$help[] = array('chat_jfreu', 'use all {#black}/jfreu$g commands');
						$chat = true;
					}
					break;
				case 'chat_musicadmin':
					if ($value[0] || $master) {
						$help[] = array('chat_musicadmin', 'use {#black}/music$g admin commands');
						$chat = true;
					}
					break;
				case 'noidlekick_play':
					if ($value[0] || $master) {
						$help[] = array('noidlekick_play', 'no idlekick when {#black}player$g');
						$chat = true;
					}
					break;
				case 'noidlekick_spec':
					if ($value[0] || $master) {
						$help[] = array('noidlekick_spec', 'no idlekick when {#black}spectator$g');
						$chat = true;
					}
					break;
				case 'server_coppers':
					if ($value[0] || $master) {
						$help[] = array('server_coppers', 'view coppers amount in {#black}/server$g');
						$chat = true;
					}
					break;
				}
			}

			if ($chat) $help[] = array();
			$help[] = array('See {#black}/admin helpall$g for available /admin commands');

			// display ManiaLink message
			display_manialink($login, $header, array('Icons128x128_1', 'ProfileAdvanced', 0.02), $help, array(1.0, 0.3, 0.7), 'OK');
		}

	/**
	 * Saves the admins/operators/abilities list to adminops.xml (default).
	 */
	} elseif ($command['params'][0] == 'writeabilities') {

		// write admins/operators file
		$filename = $aseco->settings['adminops_file'];
		if (!$aseco->writeLists()) {
			$message = '{#server}> {#error}Error writing {#highlite}$i ' . $filename . ' {#error}!';
		} else {
			// log console message
			$aseco->console('{1} [{2}] wrote ' . $filename . '!', $logtitle, $login);

			$message = '{#server}> {#highlite}' . $filename . ' {#admin}written';
		}
		// show chat message
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);

	/**
	 * Loads the admins/operators/abilities list from adminops.xml (default).
	 */
	} elseif ($command['params'][0] == 'readabilities') {

		// read admins/operators file
		$filename = $aseco->settings['adminops_file'];
		if (!$aseco->readLists()) {
			$message = '{#server}> {#highlite}' . $filename . ' {#error}not found, or error reading!';
		} else {
			// log console message
			$aseco->console('{1} [{2}] read ' . $filename . '!', $logtitle, $login);

			$message = '{#server}> {#highlite}' . $filename . ' {#admin}read';
		}
		// show chat message
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);

	/**
	 * Display message in pop-up to all players
	 */
	} elseif ($command['params'][0] == 'wall' ||
	          $command['params'][0] == 'mta') {

		// check for non-empty message
		if ($arglist[1] != '') {
			if ($aseco->server->getGame() == 'TMN') {
				$message = $aseco->formatColors('{#black}') . $chattitle . ' ' . $admin->nickname . '$z :' . LF;
				// insure window doesn't become too wide
				$message .= wordwrap($aseco->formatColors('{#welcome}') . $arglist[1], 30, LF);
				// display popup message to all players
				$aseco->client->query('SendDisplayServerMessage', $message, 'OK', '', 0);
			} elseif ($aseco->server->getGame() == 'TMF') {
				$header = '{#black}' . $chattitle . ' ' . $admin->nickname . '$z :';
				// insure window doesn't become too wide
				$message = wordwrap('{#welcome}' . $arglist[1], 40, LF . '{#welcome}');
				$message = explode(LF, $aseco->formatColors($message));
				foreach ($message as &$line)
					$line = array($line);

				// display ManiaLink message to all players
				foreach ($aseco->server->players->player_list as $target)
					display_manialink($target->login, $header, array('Icons64x64_1', 'Inbox'), $message, array(0.8), 'OK');
			}

			// log console message
			$aseco->console('{1} [{2}] sent wall message: {3}', $logtitle, $login, $arglist[1]);
		} else {
			$message = '{#server}> {#error}No message!';
			$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
		}

	/**
	 * Delete records/rs_times database entries for specific record & sync.
	 */
	} elseif ($command['params'][0] == 'delrec' && $command['params'][1] != '') {
		global $rasp;  // from plugin.rasp.php

		// verify parameter
		$param = $command['params'][1];
		if (is_numeric($param) && $param > 0 && $param <= $aseco->server->records->count()) {
			$param = ltrim($param, '0');
			$param--;
			// get record info
			$record = $aseco->server->records->getRecord($param);
			$pid = $aseco->getPlayerId($record->player->login);

			// remove times before record
			if (method_exists($rasp, 'deleteTime'))
				$rasp->deleteTime($aseco->server->challenge->id, $pid);
			// remove record and fill up if necessary
			ldb_removeRecord($aseco, $aseco->server->challenge->id, $pid, $param);
			$param++;

			// log console message
			$aseco->console('{1} [{2}] removed record {3} by {4} !', $logtitle, $login, $param, $record->player->login);

			// show chat message
			$message = formatText('{#server}>> {#admin}{1}$z$s {#highlite}{2}$z$s {#admin}removes record {#highlite}{3}{#admin} by {#highlite}{4}',
			                      $chattitle, $admin->nickname, $param, stripColors($record->player->nickname));
			$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($message));
		} else {
			$message = '{#server}> {#error}No such record {#highlite}$i ' . $param . ' {#error}!';
			$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
		}

	/**
	 * Prune records/rs_times database entries for specific track.
	 */
	} elseif ($command['params'][0] == 'prunerecs' && $command['params'][1] != '') {
		global $rasp;  // from plugin.rasp.php

		// verify parameter
		$param = $command['params'][1];
		if (is_numeric($param) && $param >= 0) {
			if (empty($admin->tracklist)) {
				$message = $rasp->messages['LIST_HELP'][0];
				$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
				return;
			}
			// find track by given #
			$jid = ltrim($param, '0');
			$jid--;
			if (array_key_exists($jid, $admin->tracklist)) {
				$uid = $admin->tracklist[$jid]['uid'];
				$name = stripColors($admin->tracklist[$jid]['name']);
				$track = $aseco->getChallengeId($uid);

				if ($track > 0) {
					// delete the records and rs_times
					$query = 'DELETE FROM records WHERE ChallengeID=' . $track;
					mysql_query($query);
					$query = 'DELETE FROM rs_times WHERE challengeID=' . $track;
					mysql_query($query);

					// log console message
					$aseco->console('{1} [{2}] pruned records/times for track {3} !', $logtitle, $login, stripColors($name, false));

					// show chat message
					$message = '{#server}> {#admin}Deleted all records & times for track: {#highlite}' . $name;
					$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
				} else {
					$message = '{#server}> {#error}Can\'t find ChallengeId for track: {#highlite}$i ' . $name . ' / ' . $uid;
					$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
				}
			} else {
				$message = $rasp->messages['JUKEBOX_NOTFOUND'][0];
				$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
			}
		} else {
			$message = $rasp->messages['JUKEBOX_HELP'][0];
			$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
		}

	/**
	 * Sets custom rounds points.
	 */
	} elseif ($command['params'][0] == 'rpoints' && $command['params'][1] != '') {

		if ($aseco->server->getGame() == 'TMF') {
			if (function_exists('admin_rpoints')) {
				admin_rpoints($aseco, $admin, $logtitle, $chattitle, $arglist[1]);  // from plugin.rpoints.php
			} else {
				// show chat message
				$message = '{#server}> {#admin}Custom Rounds points unavailable - include plugins.rpoints.php in plugins.xml';
				$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
			}
		} else {
			$message = $aseco->getChatMessage('FOREVER_ONLY');
			$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
		}

	/**
	 * Start or stop match tracking.
	 */
	} elseif ($command['params'][0] == 'match') {
		global $MatchSettings;  // from plugin.matchsave.php

		if (function_exists('match_loadsettings')) {
			if ($command['params'][1] == 'begin') {
				match_loadsettings();  // from plugin.matchsave.php
				$MatchSettings['enable'] = true;

				// log console message
				$aseco->console('{1} [{2}] started match!', $logtitle, $login);

				// show chat message
				$message = formatText('{#server}>> {#admin}{1}$z$s {#highlite}{2}$z$s{#admin} has started the match',
				                      $chattitle, $admin->nickname);
				$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($message));
			}
			elseif ($command['params'][1] == 'end') {
				$MatchSettings['enable'] = false;

				// log console message
				$aseco->console('{1} [{2}] ended match!', $logtitle, $login);

				// show chat message
				$message = formatText('{#server}>> {#admin}{1}$z$s {#highlite}{2}$z$s{#admin} has ended the match',
				                      $chattitle, $admin->nickname);
				$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($message));
			}
			else {
				// show chat message
				$message = '{#server}> {#admin}Match is currently ' . ($MatchSettings['enable'] ? 'Running' : 'Stopped');
				$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
			}
		} else {
			// show chat message
			$message = '{#server}> {#admin}Match tracking unavailable - include plugins.matchsave.php in plugins.xml';
			$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
		}

	/**
	 * Shows or sets AllowChallengeDownload status.
	 */
	} elseif ($command['params'][0] == 'acdl') {

		$param = strtolower($command['params'][1]);
		if ($param == 'on' || $param == 'off') {
			$enabled = ($param == 'on');
			$aseco->client->query('AllowChallengeDownload', $enabled);

			// log console message
			$aseco->console('{1} [{2}] set AllowChallengeDownload {3} !', $logtitle, $login, ($enabled ? 'ON' : 'OFF'));

			// show chat message
			$message = '{#server}> {#admin}AllowChallengeDownload set to ' . ($enabled ? 'Enabled' : 'Disabled');
			$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
		} else {
			$aseco->client->query('IsChallengeDownloadAllowed');
			$enabled = $aseco->client->getResponse();

			// show chat message
			$message = '{#server}> {#admin}AllowChallengeDownload is currently ' . ($enabled ? 'Enabled' : 'Disabled');
			$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
		}

	/**
	 * Shows or sets Auto TimeLimit status.
	 */
	} elseif ($command['params'][0] == 'autotime') {
		global $atl_active;  // from plugin.autotime.php

		// check for autotime plugin
		if (isset($atl_active)) {
			$param = strtolower($command['params'][1]);
			if ($param == 'on' || $param == 'off') {
				$atl_active = ($param == 'on');

				// log console message
				$aseco->console('{1} [{2}] set Auto TimeLimit {3} !', $logtitle, $login, ($atl_active ? 'ON' : 'OFF'));

				// show chat message
				$message = '{#server}> {#admin}Auto TimeLimit set to ' . ($atl_active ? 'Enabled' : 'Disabled');
				$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
			} else {
				// show chat message
				$message = '{#server}> {#admin}Auto TimeLimit is currently ' . ($atl_active ? 'Enabled' : 'Disabled');
				$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
			}
		} else {
			// show chat message
			$message = '{#server}> {#admin}Auto TimeLimit unavailable - include plugins.autotime.php in plugins.xml';
			$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
		}

	/**
	 * Shows or sets DisableRespawn status (TMF).
	 */
	} elseif ($command['params'][0] == 'disablerespawn') {

		if ($aseco->server->getGame() == 'TMF') {
			$param = strtolower($command['params'][1]);
			if ($param == 'on' || $param == 'off') {
				$enabled = ($param == 'on');
				$aseco->client->query('SetDisableRespawn', $enabled);

				// log console message
				$aseco->console('{1} [{2}] set DisableRespawn {3} !', $logtitle, $login, ($enabled ? 'ON' : 'OFF'));

				// show chat message
				$message = '{#server}>> {#admin}DisableRespawn set to ' . ($enabled ? 'Enabled' : 'Disabled') . ' on the next track';
				$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($message));
			} else {
				$aseco->client->query('GetDisableRespawn');
				$enabled = $aseco->client->getResponse();

				// show chat message
				$message = '{#server}> {#admin}DisableRespawn is currently ' . ($enabled['CurrentValue'] ? 'Enabled' : 'Disabled');
				$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
			}
		} else {
			$message = $aseco->getChatMessage('FOREVER_ONLY');
			$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
		}

	/**
	 * Shows or sets ForceShowAllOpponents status (TMF).
	 */
	} elseif ($command['params'][0] == 'forceshowopp') {

		if ($aseco->server->getGame() == 'TMF') {
			$param = strtolower($command['params'][1]);
			if ($param == 'all' || $param == 'off') {
				$enabled = ($param == 'all' ? 1 : 0);
				$aseco->client->query('SetForceShowAllOpponents', $enabled);

				// log console message
				$aseco->console('{1} [{2}] set ForceShowAllOpponents {3} !', $logtitle, $login, ($enabled ? 'ALL' : 'OFF'));

				// show chat message
				$message = '{#server}>> {#admin}ForceShowAllOpponents set to {#highlite}' . ($enabled ? 'Enabled' : 'Disabled') . '{#admin} on the next track';
				$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($message));
			} elseif (is_numeric($param) && $param > 1) {
				$enabled = intval($param);
				$aseco->client->query('SetForceShowAllOpponents', $enabled);

				// log console message
				$aseco->console('{1} [{2}] set ForceShowAllOpponents to {3} !', $logtitle, $login, $enabled);

				// show chat message
				$message = '{#server}>> {#admin}ForceShowAllOpponents set to {#highlite}' . $enabled . '{#admin} on the next track';
				$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($message));
			} else {
				$aseco->client->query('GetForceShowAllOpponents');
				$enabled = $aseco->client->getResponse();
				$enabled = $enabled['CurrentValue'];

				// show chat message
				$message = '{#server}> {#admin}ForceShowAllOpponents is set to: {#highlite}' . ($enabled != 0 ? ($enabled > 1 ? $enabled : 'All') : 'Off');
				$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
			}
		} else {
			$message = $aseco->getChatMessage('FOREVER_ONLY');
			$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
		}

	/**
	 * Shows or sets Automatic ScorePanel status (TMF).
	 */
	} elseif ($command['params'][0] == 'scorepanel') {
		global $auto_scorepanel;

		if ($aseco->server->getGame() == 'TMF') {
			$param = strtolower($command['params'][1]);
			if ($param == 'on' || $param == 'off') {
				$auto_scorepanel = ($param == 'on');
				scorepanel_off($aseco, null);

				// log console message
				$aseco->console('{1} [{2}] set Automatic ScorePanel {3} !', $logtitle, $login, ($auto_scorepanel ? 'ON' : 'OFF'));

				// show chat message
				$message = '{#server}>> {#admin}Automatic ScorePanel set to ' . ($auto_scorepanel ? 'Enabled' : 'Disabled');
				$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($message));
			} else {
				// show chat message
				$message = '{#server}> {#admin}Automatic ScorePanel is currently ' . ($auto_scorepanel ? 'Enabled' : 'Disabled');
				$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
			}
		} else {
			$message = $aseco->getChatMessage('FOREVER_ONLY');
			$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
		}

	/**
	 * Shows or sets Rounds Finishpanel status (TMF).
	 */
	} elseif ($command['params'][0] == 'roundsfinish') {
		global $rounds_finishpanel;

		if ($aseco->server->getGame() == 'TMF') {
			$param = strtolower($command['params'][1]);
			if ($param == 'on' || $param == 'off') {
				$rounds_finishpanel = ($param == 'on');

				// log console message
				$aseco->console('{1} [{2}] set Rounds Finishpanel {3} !', $logtitle, $login, ($rounds_finishpanel ? 'ON' : 'OFF'));

				// show chat message
				$message = '{#server}>> {#admin}Rounds Finishpanel set to ' . ($rounds_finishpanel ? 'Enabled' : 'Disabled');
				$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($message));
			} else {
				// show chat message
				$message = '{#server}> {#admin}Rounds Finishpanel is currently ' . ($rounds_finishpanel ? 'Enabled' : 'Disabled');
				$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
			}
		} else {
			$message = $aseco->getChatMessage('FOREVER_ONLY');
			$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
		}

	/**
	 * Forces a player into Blue or Red team (TMF).
	 */
	} elseif ($command['params'][0] == 'forceteam' && $command['params'][1] != '') {

		if ($aseco->server->getGame() == 'TMF') {
			// check for Team mode
			if ($aseco->server->gameinfo->mode == Gameinfo::TEAM) {
				// get player information
				if ($target = $aseco->getPlayerParam($admin, $command['params'][1])) {
					// get player's team
					$aseco->client->query('GetPlayerInfo', $target->login);
					$info = $aseco->client->getResponse();
					// check for new team
					if (isset($command['params'][2]) && $command['params'][2] != '') {
						$team = strtolower($command['params'][2]);

						if (strpos('blue', $team) === 0) {
							if ($info['TeamId'] != 0) {
								// set player to Blue team
								$aseco->client->query('ForcePlayerTeam', $target->login, 0);

								// log console message
								$aseco->console('{1} [{2}] forces {3} into Blue team!', $logtitle, $login, stripColors($target->nickname, false));

								// show chat message
								$message = formatText('{#server}>> {#admin}{1}$z$s {#highlite}{2}$z$s{#admin} forces {#highlite}{3}$z$s{#admin} into $00fBlue{#admin} team!',
								                      $chattitle, $admin->nickname, str_ireplace('$w', '', $target->nickname));
								$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($message));
							} else {
								$message = '{#server}> {#admin}Player {#highlite}' .
								           stripColors($target->nickname) .
								           '{#admin} is already in $00fBlue{#admin} team';
								$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
							}

						} elseif (strpos('red', $team) === 0) {
							if ($info['TeamId'] != 1) {
								// set player to Red team
								$aseco->client->query('ForcePlayerTeam', $target->login, 1);

								// log console message
								$aseco->console('{1} [{2}] forces {3} into Red team!', $logtitle, $login, stripColors($target->nickname, false));

								// show chat message
								$message = formatText('{#server}>> {#admin}{1}$z$s {#highlite}{2}$z$s{#admin} forces {#highlite}{3}$z$s{#admin} into $f00Red{#admin} team!',
								                      $chattitle, $admin->nickname, str_ireplace('$w', '', $target->nickname));
								$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($message));
							} else {
								$message = '{#server}> {#admin}Player {#highlite}' .
								           stripColors($target->nickname) .
								           '{#admin} is already in $f00Red{#admin} team';
								$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
							}

						} else {
							$message = '{#server}> {#highlite}' . $team . '$z$s{#error} is not a valid team!';
							$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
						}
					} else {
						// show current team
						$message = '{#server}> {#admin}Player {#highlite}' .
						           stripColors($target->nickname) . '{#admin} is in ' .
						           ($info['TeamId'] == 0 ? '$00fBlue' : '$f00Red') .
						           '{#admin} team';
						$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
					}
				}
			} else {
				$message = '{#server}> {#error}Command only available in {#highlite}$i Team {#error}mode!';
				$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
			}
		} else {
			$message = $aseco->getChatMessage('FOREVER_ONLY');
			$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
		}

	/**
	 * Forces player into free camera spectator (TMF).
	 */
	} elseif ($command['params'][0] == 'forcespec' && $command['params'][1] != '') {

		if ($aseco->server->getGame() == 'TMF') {
			// get player information
			if ($target = $aseco->getPlayerParam($admin, $command['params'][1])) {
				if (!$aseco->isSpectator($target)) {
					// force player into free spectator
					$rtn = $aseco->client->query('ForceSpectator', $target->login, 1);
					if (!$rtn) {
						trigger_error('[' . $aseco->client->getErrorCode() . '] ForceSpectator - ' . $aseco->client->getErrorMessage(), E_USER_WARNING);
					} else {
						// allow spectator to switch back to player
						$rtn = $aseco->client->query('ForceSpectator', $target->login, 0);
						// force free camera mode on spectator
						$aseco->client->addCall('ForceSpectatorTarget', array($target->login, '', 2));
						// free up player slot
						$aseco->client->addCall('SpectatorReleasePlayerSlot', array($target->login));
						// log console message
						$aseco->console('{1} [{2}] forces player {3} into spectator!', $logtitle, $login, stripColors($target->nickname, false));

						// show chat message
						$message = formatText('{#server}>> {#admin}{1}$z$s {#highlite}{2}$z$s{#admin} forces player {#highlite}{3}$z$s{#admin} into spectator!',
						                      $chattitle, $admin->nickname, str_ireplace('$w', '', $target->nickname));
						$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($message));
					}
				} else {
					$message = formatText('{#server}> {#highlite}{1} {#error}is already a spectator!',
					                      stripColors($target->nickname));
					$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
				}
			}
		} else {
			$message = $aseco->getChatMessage('FOREVER_ONLY');
			$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
		}

	/**
	 * Forces a spectator into free camera mode (TMF).
	 */
	} elseif ($command['params'][0] == 'specfree' && $command['params'][1] != '') {

		if ($aseco->server->getGame() == 'TMF') {
			// get player information
			if ($target = $aseco->getPlayerParam($admin, $command['params'][1])) {
				if ($aseco->isSpectator($target)) {
					// force free camera mode on spectator
					$rtn = $aseco->client->query('ForceSpectatorTarget', $target->login, '', 2);
					if (!$rtn) {
						trigger_error('[' . $aseco->client->getErrorCode() . '] ForceSpectatorTarget - ' . $aseco->client->getErrorMessage(), E_USER_WARNING);
					} else {
						// log console message
						$aseco->console('{1} [{2}] forces spectator free mode on {3}!', $logtitle, $login, stripColors($target->nickname, false));

						// show chat message
						$message = formatText('{#server}>> {#admin}{1}$z$s {#highlite}{2}$z$s{#admin} forces spectator free mode on {#highlite}{3}$z$s{#admin} !',
						                      $chattitle, $admin->nickname, str_ireplace('$w', '', $target->nickname));
						$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($message));
					}
				} else {
					$message = formatText('{#server}> {#highlite}{1} {#error}is not a spectator!',
					                      stripColors($target->nickname));
					$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
				}
			}
		} else {
			$message = $aseco->getChatMessage('FOREVER_ONLY');
			$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
		}

	/**
	 * Selects default window style (TMF).
	 */
	} elseif ($command['params'][0] == 'panel') {

		if ($aseco->server->getGame() == 'TMF') {
			if (function_exists('admin_panel')) {
				$command['params'] = $command['params'][1];
				admin_panel($aseco, $command);  // from plugin.panels.php
			} else {
				// show chat message
				$message = '{#server}> {#admin}Admin panel unavailable - include plugins.panels.php in plugins.xml';
				$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
			}
		} else {
			$message = $aseco->getChatMessage('FOREVER_ONLY');
			$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
		}

	/**
	 * Selects default window style (TMF).
	 */
	} elseif ($command['params'][0] == 'style' && $command['params'][1] != '') {

		if ($aseco->server->getGame() == 'TMF') {
			if (strtolower($command['params'][1]) == 'off') {
				$aseco->style = array();
				$aseco->settings['window_style'] = 'Off';

				// log console message
				$aseco->console('{1} [{2}] reset default window style', $logtitle, $login);

				// show chat message
				$message = formatText('{#server}>> {#admin}{1}$z$s {#highlite}{2}$z$s{#admin} reset default window style',
				                      $chattitle, $admin->nickname);
				$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($message));
			} else {
				$style_file = 'styles/' . $command['params'][1] . '.xml';
				// load default style
				if (($style = $aseco->xml_parser->parseXml($style_file)) && isset($style['STYLES'])) {
					$aseco->style = $style['STYLES'];

					// log console message
					$aseco->console('{1} [{2}] selects default window style [{3}]', $logtitle, $login, $command['params'][1]);

					// show chat message
					$message = formatText('{#server}>> {#admin}{1}$z$s {#highlite}{2}$z$s{#admin} selects default window style {#highlite}{3}',
					                      $chattitle, $admin->nickname, $command['params'][1]);
					$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($message));
				} else {
					// Could not read/parse XML file
					$message = '{#server}> {#error}No valid style file, use {#highlite}$i /style list {#error}!';
					$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
				}
			}
		} else {
			$message = $aseco->getChatMessage('FOREVER_ONLY');
			$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
		}

	/**
	 * Selects default admin panel (TMF).
	 */
	} elseif ($command['params'][0] == 'admpanel' && $command['params'][1] != '') {

		if ($aseco->server->getGame() == 'TMF') {
			if (strtolower($command['params'][1]) == 'off') {
				$aseco->panels['admin'] = '';
				$aseco->settings['admin_panel'] = 'Off';

				// log console message
				$aseco->console('{1} [{2}] reset default admin panel', $logtitle, $login);

				// show chat message
				$message = formatText('{#server}> {#admin}{1}$z$s {#highlite}{2}$z$s{#admin} reset default admin panel',
				                      $chattitle, $admin->nickname);
				$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
			} else {
				// added file prefix
				$panel = $command['params'][1];
				if (strtolower(substr($command['params'][1], 0, 5)) != 'admin')
					$panel = 'Admin' . $panel;
				$panel_file = 'panels/' . $panel . '.xml';
				// load default panel
				if ($panel = @file_get_contents($panel_file)) {
					$aseco->panels['admin'] = $panel;

					// log console message
					$aseco->console('{1} [{2}] selects default admin panel [{3}]', $logtitle, $login, $command['params'][1]);

					// show chat message
					$message = formatText('{#server}> {#admin}{1}$z$s {#highlite}{2}$z$s{#admin} selects default admin panel {#highlite}{3}',
					                      $chattitle, $admin->nickname, $command['params'][1]);
					$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
				} else {
					// Could not read XML file
					$message = '{#server}> {#error}No valid admin panel file, use {#highlite}$i /admin panel list {#error}!';
					$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
				}
			}
		} else {
			$message = $aseco->getChatMessage('FOREVER_ONLY');
			$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
		}

	/**
	 * Selects default donate panel (TMUF).
	 */
	} elseif ($command['params'][0] == 'donpanel' && $command['params'][1] != '') {

		if ($aseco->server->getGame() == 'TMF') {
			// check for TMUF server
			if ($aseco->server->rights) {
				if (strtolower($command['params'][1]) == 'off') {
					$aseco->panels['donate'] = '';
					$aseco->settings['donate_panel'] = 'Off';

					// log console message
					$aseco->console('{1} [{2}] reset default donate panel', $logtitle, $login);

					// show chat message
					$message = formatText('{#server}>> {#admin}{1}$z$s {#highlite}{2}$z$s{#admin} reset default donate panel',
					                      $chattitle, $admin->nickname);
					$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($message));
				} else {
					// added file prefix
					$panel = $command['params'][1];
					if (strtolower(substr($command['params'][1], 0, 6)) != 'donate')
						$panel = 'Donate' . $panel;
					$panel_file = 'panels/' . $panel . '.xml';
					// load default panel
					if ($panel = @file_get_contents($panel_file)) {
						$aseco->panels['donate'] = $panel;

						// log console message
						$aseco->console('{1} [{2}] selects default donate panel [{3}]', $logtitle, $login, $command['params'][1]);

						// show chat message
						$message = formatText('{#server}>> {#admin}{1}$z$s {#highlite}{2}$z$s{#admin} selects default donate panel {#highlite}{3}',
						                      $chattitle, $admin->nickname, $command['params'][1]);
						$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($message));
					} else {
						// Could not read XML file
						$message = '{#server}> {#error}No valid donate panel file, use {#highlite}$i /donpanel list {#error}!';
						$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
					}
				}
			} else {
				$message = formatText($aseco->getChatMessage('UNITED_ONLY'), 'server');
				$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
			}
		} else {
			$message = $aseco->getChatMessage('FOREVER_ONLY');
			$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
		}

	/**
	 * Selects default records panel (TMF).
	 */
	} elseif ($command['params'][0] == 'recpanel' && $command['params'][1] != '') {

		if ($aseco->server->getGame() == 'TMF') {
			if (strtolower($command['params'][1]) == 'off') {
				$aseco->panels['records'] = '';
				$aseco->settings['records_panel'] = 'Off';

				// log console message
				$aseco->console('{1} [{2}] reset default records panel', $logtitle, $login);

				// show chat message
				$message = formatText('{#server}>> {#admin}{1}$z$s {#highlite}{2}$z$s{#admin} reset default records panel',
				                      $chattitle, $admin->nickname);
				$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($message));
			} else {
				// added file prefix
				$panel = $command['params'][1];
				if (strtolower(substr($command['params'][1], 0, 7)) != 'records')
					$panel = 'Records' . $panel;
				$panel_file = 'panels/' . $panel . '.xml';
				// load default panel
				if ($panel = @file_get_contents($panel_file)) {
					$aseco->panels['records'] = $panel;

					// log console message
					$aseco->console('{1} [{2}] selects default records panel [{3}]', $logtitle, $login, $command['params'][1]);

					// show chat message
					$message = formatText('{#server}>> {#admin}{1}$z$s {#highlite}{2}$z$s{#admin} selects default records panel {#highlite}{3}',
					                      $chattitle, $admin->nickname, $command['params'][1]);
					$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($message));
				} else {
					// Could not read XML file
					$message = '{#server}> {#error}No valid records panel file, use {#highlite}$i /recpanel list {#error}!';
					$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
				}
			}
		} else {
			$message = $aseco->getChatMessage('FOREVER_ONLY');
			$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
		}

	/**
	 * Selects default vote panel (TMF).
	 */
	} elseif ($command['params'][0] == 'votepanel' && $command['params'][1] != '') {

		if ($aseco->server->getGame() == 'TMF') {
			if (strtolower($command['params'][1]) == 'off') {
				$aseco->panels['vote'] = '';
				$aseco->settings['vote_panel'] = 'Off';

				// log console message
				$aseco->console('{1} [{2}] reset default vote panel', $logtitle, $login);

				// show chat message
				$message = formatText('{#server}>> {#admin}{1}$z$s {#highlite}{2}$z$s{#admin} reset default vote panel',
				                      $chattitle, $admin->nickname);
				$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($message));
			} else {
				// added file prefix
				$panel = $command['params'][1];
				if (strtolower(substr($command['params'][1], 0, 4)) != 'vote')
					$panel = 'Vote' . $panel;
				$panel_file = 'panels/' . $panel . '.xml';
				// load default panel
				if ($panel = @file_get_contents($panel_file)) {
					$aseco->panels['vote'] = $panel;

					// log console message
					$aseco->console('{1} [{2}] selects default vote panel [{3}]', $logtitle, $login, $command['params'][1]);

					// show chat message
					$message = formatText('{#server}>> {#admin}{1}$z$s {#highlite}{2}$z$s{#admin} selects default vote panel {#highlite}{3}',
					                      $chattitle, $admin->nickname, $command['params'][1]);
					$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($message));
				} else {
					// Could not read XML file
					$message = '{#server}> {#error}No valid vote panel file, use {#highlite}$i /votepanel list {#error}!';
					$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
				}
			}
		} else {
			$message = $aseco->getChatMessage('FOREVER_ONLY');
			$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
		}

	/**
	 * Shows server's coppers amount (TMUF).
	 */
	} elseif ($command['params'][0] == 'coppers') {

		if ($aseco->server->getGame() == 'TMF') {
			// check for TMUF server
			if ($aseco->server->rights) {
				// get server coppers
				$aseco->client->query('GetServerCoppers');
				$coppers = $aseco->client->getResponse();

				// show chat message
				$message = formatText($aseco->getChatMessage('COPPERS'),
				                      $aseco->server->name, $coppers);
				$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
			} else {
				$message = formatText($aseco->getChatMessage('UNITED_ONLY'), 'server');
				$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
			}
		} else {
			$message = $aseco->getChatMessage('FOREVER_ONLY');
			$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
		}

	/**
	 * Pays server coppers to login (TMUF).
	 */
	} elseif ($command['params'][0] == 'pay') {

		if ($aseco->server->getGame() == 'TMF') {
			// check for TMUF server
			if ($aseco->server->rights) {
				if (function_exists('admin_payment')) {
					if (!isset($command['params'][2])) $command['params'][2] = '';
					admin_payment($aseco, $login, $command['params'][1],
					              $command['params'][2]);  // from plugin.donate.php
				} else {
					// show chat message
					$message = '{#server}> {#admin}Server payment unavailable - include plugins.donate.php in plugins.xml';
					$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
				}
			} else {
				$message = formatText($aseco->getChatMessage('UNITED_ONLY'), 'server');
				$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
			}
		} else {
			$message = $aseco->getChatMessage('FOREVER_ONLY');
			$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
		}

	/**
	 * Displays relays list or shows relay master (TMF).
	 */
	} elseif ($command['params'][0] == 'relays') {

		if ($aseco->server->getGame() == 'TMF') {
			if ($aseco->server->isrelay) {
				// show chat message
				$message = formatText($aseco->getChatMessage('RELAYMASTER'),
				                      $aseco->server->relaymaster['Login'], $aseco->server->relaymaster['NickName']);
				$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
			} else {
				if (empty($aseco->server->relayslist)) {
					// show chat message
					$message = formatText($aseco->getChatMessage('NO_RELAYS'));
					$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
				} else {
					$header = 'Relay servers:';
					$relays = array();
					$relays[] = array('{#login}Login', '{#nick}Nick');
					foreach ($aseco->server->relayslist as $relay)
						$relays[] = array($relay['Login'], $relay['NickName']);

					// display ManiaLink message
					display_manialink($login, $header, array('BgRaceScore2', 'Spectator'), $relays, array(1.0, 0.35, 0.65), 'OK');
				}
			}
		} else {
			$message = $aseco->getChatMessage('FOREVER_ONLY');
			$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
		}

	/**
	 * Shows server's detailed settings (TMF).
	 */
	} elseif ($command['params'][0] == 'server') {

		if ($aseco->server->getGame() == 'TMN') {
			$version = $aseco->client->addCall('GetVersion', array());
			$network = $aseco->client->addCall('GetNetworkStats', array());
			$options = $aseco->client->addCall('GetServerOptions', array(1));
			$gameinfo = $aseco->client->addCall('GetCurrentGameInfo', array(1));
			if (!$aseco->client->multiquery()) {
				trigger_error('[' . $aseco->client->getErrorCode() . '] GetServer (multi) - ' . $aseco->client->getErrorMessage(), E_USER_WARNING);
				return;
			} else {
				$response = $aseco->client->getResponse();
				$version = $response[$version][0];
				$network = $response[$network][0];
				$options = $response[$options][0];
				$gameinfo = $response[$gameinfo][0];
			}

			// compile settings overview
			$admin->msgs = array();
			$admin->msgs[0] = 1;
			$head = 'System info for: ' . $options['Name'] . '$z' . LF . LF;

			$stats = $head . '{#black}GetVersion:' . LF;
			foreach ($version as $key => $val) {
				$stats .= '$g' . str_pad($key, 30) . '{#black}' . $val . LF;
			}

			$stats .= '{#black}GetNetworkStats:' . LF;
			foreach ($network as $key => $val) {
				if ($key != 'PlayerNetInfos')
					$stats .= '$g' . str_pad($key, 30) . '{#black}' . $val . LF;
			}

			$admin->msgs[] = $aseco->formatColors($stats);

			$stats = $head . '{#black}GetServerOptions:' . LF;
			foreach ($options as $key => $val) {
				// show only Current values, not Next ones
				if ($key != 'Name' && $key != 'Comment' && substr($key, 0, 4) != 'Next')
					if (is_bool($val))
						$stats .= '$g' . str_pad($key, 30) . '{#black}' . bool2text($val) . LF;
					else
						$stats .= '$g' . str_pad($key, 30) . '{#black}' . $val . LF;
			}

			$admin->msgs[] = $aseco->formatColors($stats);

			$stats = $head . '{#black}GetCurrentGameInfo:' . LF;
			foreach ($gameinfo as $key => $val) {
				if (is_bool($val))
					$stats .= '$g' . str_pad($key, 30) . '{#black}' . bool2text($val) . LF;
				else
					if ($key == 'GameMode')
						$stats .= '$g' . str_pad($key, 30) . '{#black}' . $val . '$g  (' . $aseco->server->gameinfo->getMode() . ')' . LF;
					else
						$stats .= '$g' . str_pad($key, 30) . '{#black}' . $val . LF;
			}

			$admin->msgs[] = $aseco->formatColors($stats);
			$aseco->client->query('SendDisplayServerMessageToLogin', $login, $admin->msgs[1], 'Close', 'Next', 0);

		} elseif ($aseco->server->getGame() == 'TMF') {
			// get all server settings in one go
			$version = $aseco->client->addCall('GetVersion', array());
			$info = $aseco->client->addCall('GetSystemInfo', array());
			$coppers = $aseco->client->addCall('GetServerCoppers', array());
			$ladderlim = $aseco->client->addCall('GetLadderServerLimits', array());
			$options = $aseco->client->addCall('GetServerOptions', array(1));
			$gameinfo = $aseco->client->addCall('GetCurrentGameInfo', array(1));
			$network = $aseco->client->addCall('GetNetworkStats', array());
			$callvotes = $aseco->client->addCall('GetCallVoteRatios', array());
			if (!$aseco->client->multiquery()) {
				trigger_error('[' . $aseco->client->getErrorCode() . '] GetServer (multi) - ' . $aseco->client->getErrorMessage(), E_USER_WARNING);
				return;
			} else {
				$response = $aseco->client->getResponse();
				$version = $response[$version][0];
				$info = $response[$info][0];
				$coppers = $response[$coppers][0];
				$ladderlim = $response[$ladderlim][0];
				$options = $response[$options][0];
				$gameinfo = $response[$gameinfo][0];
				$network = $response[$network][0];
				$callvotes = $response[$callvotes][0];
			}

			// compile settings overview
			$head = 'System info for: ' . $options['Name'];
			$admin->msgs = array();
			$admin->msgs[0] = array(1, $head, array(1.1, 0.6, 0.5), array('Icons64x64_1', 'DisplaySettings', 0.01));
			$stats = array();

			$stats[] = array('{#black}GetVersion:', '');
			foreach ($version as $key => $val) {
				$stats[] = array($key, '{#black}' . $val);
			}

			$stats[] = array();
			$stats[] = array('{#black}GetSystemInfo:', '');
			foreach ($info as $key => $val) {
				$stats[] = array($key, '{#black}' . $val);
			}

			$stats[] = array();
			$stats[] = array('Rights', '{#black}' . ($aseco->server->rights ? 'United   $gCoppers: {#black}' . $coppers : 'Nations'));
			$stats[] = array('Packmask', '{#black}' . $aseco->server->packmask);
			if ($aseco->server->isrelay)
				$stats[] = array('Relays', '{#black}' . $aseco->server->relaymaster['Login']);
			else
				$stats[] = array('Master to', '{#black}' . count($aseco->server->relayslist) .
				                 ' $grelay' . (count($aseco->server->relayslist) == 1 ? '' : 's'));

			$stats[] = array();
			$stats[] = array('{#black}GetLadderServerLimits:', '');
			foreach ($ladderlim as $key => $val) {
				$stats[] = array($key, '{#black}' . $val);
			}

			$admin->msgs[] = $stats;
			$stats = array();

			$stats[] = array('{#black}GetServerOptions:', '');
			foreach ($options as $key => $val) {
				// show only Current values, not Next ones
				if ($key != 'Name' && $key != 'Comment' && substr($key, 0, 4) != 'Next')
					if (is_bool($val))
						$stats[] = array($key, '{#black}' . bool2text($val));
					else
						$stats[] = array($key, '{#black}' . $val);
			}

			$admin->msgs[] = $stats;
			$stats = array();

			$lines = 0;
			$stats[] = array('{#black}GetCurrentGameInfo:', '');
			foreach ($gameinfo as $key => $val) {
				if (is_bool($val))
					$stats[] = array($key, '{#black}' . bool2text($val));
				else
					if ($key == 'GameMode')
						$stats[] = array($key, '{#black}' . $val . '$g  (' . $aseco->server->gameinfo->getMode() . ')');
					else
						$stats[] = array($key, '{#black}' . $val);

				if (++$lines > 18) {
					$admin->msgs[] = $stats;
					$stats = array();
					$stats[] = array('{#black}GetCurrentGameInfo:', '');
					$lines = 0;
				}
			}

			$stats[] = array();
			$stats[] = array('{#black}GetNetworkStats:', '');
			foreach ($network as $key => $val) {
				if ($key != 'PlayerNetInfos')
					$stats[] = array($key, '{#black}' . $val);
			}

			$stats[] = array();
			$stats[] = array('{#black}GetCallVoteRatios:', '');
			$stats[] = array('Command', 'Ratio');
			foreach ($callvotes as $entry) {
				$stats[] = array('{#black}' . $entry['Command'], '{#black}' . round($entry['Ratio'], 2));
			}

			$admin->msgs[] = $stats;
			display_manialink_multi($admin);
		} else {
			$message = $aseco->getChatMessage('FOREVER_ONLY');
			$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
		}

	/**
	 * Send private message to all available admins.
	 */
	} elseif ($command['params'][0] == 'pm') {
		global $pmbuf, $pmlen, $muting_available;  // from plugin.muting.php

		// check for non-empty message
		if ($arglist[1] != '') {
			// drop oldest pm line if buffer full
			if (count($pmbuf) >= $pmlen) {
				array_shift($pmbuf);
			}
			// append timestamp, admin nickname (but strip wide font) and pm line to history
			$nick = str_ireplace('$w', '', $admin->nickname);
			$pmbuf[] = array(date('H:i:s'), $nick, $arglist[1]);

			// find and pm other masteradmins/admins/operators
			$nicks = '';
			$msg = '{#error}-pm-$g[' . $nick . '$z$s$i->{#logina}Admins$g]$i {#interact}' . $arglist[1];
			$msg = $aseco->formatColors($msg);
			foreach ($aseco->server->players->player_list as $pl) {
				// check for admin ability
				if ($pl->login != $login && $aseco->allowAbility($pl, 'pm')) {
					$nicks .= str_ireplace('$w', '', $pl->nickname) . '$z$s$i,';
					$aseco->client->addCall('ChatSendServerMessageToLogin', array($msg, $pl->login));

					// check if player muting is enabled
					if ($muting_available) {
						// drop oldest message if receiver's mute buffer full
						if (count($pl->mutebuf) >= 28) {  // chat window length
							array_shift($pl->mutebuf);
						}
						// append pm line to receiver's mute buffer
						$pl->mutebuf[] = $msg;
					}
				}
			}

			// CC message to self
			if ($nicks) {
				$nicks = substr($nicks, 0, strlen($nicks)-1);  // strip trailing ','
				$msg = '{#error}-pm-$g[' . $nick . '$z$s$i->' . $nicks . ']$i {#interact}' . $arglist[1];
			} else {
				$msg = '{#server}> {#error}No other admins currectly available!';
			}
			$msg = $aseco->formatColors($msg);
			$aseco->client->addCall('ChatSendServerMessageToLogin', array($msg, $login));
			if (!$aseco->client->multiquery()) {
				trigger_error('[' . $aseco->client->getErrorCode() . '] ChatSend PM (multi) - ' . $aseco->client->getErrorMessage(), E_USER_WARNING);
			}

			// check if player muting is enabled
			if ($muting_available) {
				// drop oldest message if sender's mute buffer full
				if (count($admin->mutebuf) >= 28) {  // chat window length
					array_shift($admin->mutebuf);
				}
				// append pm line to sender's mute buffer
				$admin->mutebuf[] = $msg;
			}
		} else {
			$msg = '{#server}> {#error}No message!';
			$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($msg), $login);
		}

	/**
	 * Displays log of recent private admin messages.
	 */
	} elseif ($command['params'][0] == 'pmlog') {
		global $pmbuf, $lnlen;

		if (!empty($pmbuf)) {
			if ($aseco->server->getGame() == 'TMN') {
				$head = 'Recent PM history:' . LF;
				$msg = '';
				$lines = 0;
				$admin->msgs = array();
				$admin->msgs[0] = 1;
				foreach ($pmbuf as $item) {
					// break up long lines into chunks with continuation strings
					$multi = explode(LF, wordwrap(stripColors($item[2]), $lnlen, LF . '...'));
					foreach ($multi as $line) {
						$line = substr($line, 0, $lnlen+3);  // chop off excessively long words
						$msg .= '$z' . ($aseco->settings['chatpmlog_times'] ? '$n<{#server}' . $item[0] . '$z$n>$m ' : '') .
						        '[{#black}' . $item[1] . '$z] ' . $line . LF;
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
				} else {  // > 2
					$aseco->client->query('SendDisplayServerMessageToLogin', $login, $admin->msgs[1], 'Close', 'Next', 0);
				}

			} elseif ($aseco->server->getGame() == 'TMF') {
				$head = 'Recent Admin PM history:';
				$msg = array();
				$lines = 0;
				$admin->msgs = array();
				$admin->msgs[0] = array(1, $head, array(1.2), array('Icons64x64_1', 'Outbox'));
				foreach ($pmbuf as $item) {
					// break up long lines into chunks with continuation strings
					$multi = explode(LF, wordwrap(stripColors($item[2]), $lnlen+30, LF . '...'));
					foreach ($multi as $line) {
						$line = substr($line, 0, $lnlen+33);  // chop off excessively long words
						$msg[] = array('$z' . ($aseco->settings['chatpmlog_times'] ? '<{#server}' . $item[0] . '$z> ' : '') .
						               '[{#black}' . $item[1] . '$z] ' . $line);
						if (++$lines > 14) {
							$admin->msgs[] = $msg;
							$lines = 0;
							$msg = '';
						}
					}
				}
				// add if last batch exists
				if (!empty($msg))
					$admin->msgs[] = $msg;

				// display ManiaLink message
				display_manialink_multi($admin);
			}
		} else {
			$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors('{#server}> {#error}No PM history found!'), $login);
		}

	/**
	 * Executes direct server call
	 */
	} elseif ($command['params'][0] == 'call') {
		global $method_results;

		// extra admin tier check
		if (!$aseco->isMasterAdmin($admin)) {
			$aseco->client->query('ChatSendToLogin', $aseco->formatColors('{#error}You don\'t have the required admin rights to do that!'), $login);
			return;
		}

		// check parameter(s)
		if ($command['params'][1] != '') {
			if ($command['params'][1] == 'help') {
				if (isset($command['params'][2]) && $command['params'][2] != '') {
					// generate help message for method
					$method = $command['params'][2];
					$sign = $aseco->client->addCall('system.methodSignature', array($method));
					$help = $aseco->client->addCall('system.methodHelp', array($method));
					if (!$aseco->client->multiquery()) {
						trigger_error('[' . $aseco->client->getErrorCode() . '] system.method - ' . $aseco->client->getErrorMessage(), E_USER_WARNING);
					} else {
						$response = $aseco->client->getResponse();
						if (isset($response[0]['faultCode'])) {
							$message = '{#server}> {#error}No such method {#highlite}$i ' . $method . ' {#error}!';
							$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
						} else {
							$sign = $response[$sign][0][0];
							$help = $response[$help][0];

							// format signature & help
							$params = '';
							for ($i = 1; $i < count($sign); $i++)
								$params .= $sign[$i] . ', ';
							$params = substr($params, 0, strlen($params)-2);  // strip trailing ", "
							$sign = $sign[0] . ' {#black}' . $method . '$g (' . $params . ')';
							$sign = explode(LF, wordwrap($sign, 58, LF));
							$help = str_replace(array('<i>', '</i>'),
							                    array('$i', '$i'), $help);
							$help = explode(LF, wordwrap($help, 58, LF));

							// compile & display help message
							if ($aseco->server->getGame() == 'TMN') {
								$info = 'Server Method help for:' . LF . LF;
								foreach ($sign as $line)
									$info .= $line . LF;
								$info .= LF;
								foreach ($help as $line)
									$info .= $line . LF;

								// display popup message
								$aseco->client->query('SendDisplayServerMessageToLogin', $login, $aseco->formatColors($info), 'OK', '', 0);
							} elseif ($aseco->server->getGame() == 'TMF') {
								$header = 'Server Method help for:';
								$info = array();
								foreach ($sign as $line)
									$info[] = array($line);
								$info[] = array();
								foreach ($help as $line)
									$info[] = array($line);

								// display ManiaLink message
								display_manialink($login, $header, array('Icons128x128_1', 'Advanced', 0.02), $info, array(1.05), 'OK');
							}
						}
					}

				} else {
					// compile & display help message
					if ($aseco->server->getGame() == 'TMN') {
						$help = '{#black}/admin call$g executes server method:' . LF;
						$help .= '  - {#black}help$g, displays this help information' . LF;
						$help .= '  - {#black}help Method$g, displays help for method' . LF;
						$help .= '  - {#black}list$g, lists all available methods' . LF;
						$help .= '  - {#black}Method {params}$g, executes method & displays result' . LF;

						// display popup message
						$aseco->client->query('SendDisplayServerMessageToLogin', $login, $aseco->formatColors($help), 'OK', '', 0);

					} elseif ($aseco->server->getGame() == 'TMF') {
						$header = '{#black}/admin call$g executes server method:';
						$help = array();
						$help[] = array('...', '{#black}help',
						                'Displays this help information');
						$help[] = array('...', '{#black}help Method',
						                'Displays help for method');
						$help[] = array('...', '{#black}list',
						                'Lists all available methods');
						$help[] = array('...', '{#black}Method {params}',
						                'Executes method & displays result');

						// display ManiaLink message
						display_manialink($login, $header, array('Icons64x64_1', 'TrackInfo', -0.01), $help, array(1.0, 0.05, 0.35, 0.6), 'OK');
					}
				}

			} elseif ($command['params'][1] == 'list') {
				// get list of methods
				$aseco->client->query('system.listMethods');
				$methods = $aseco->client->getResponse();
				$admin->msgs = array();

				if ($aseco->server->getGame() == 'TMN') {
					$head = 'Available Methods on this Server:' . LF . 'Id       Method' . LF;
					$msg = '';
					$mid = 1;
					$lines = 0;
					$admin->msgs[0] = 1;
					foreach ($methods as $method) {
						$msg .= '$g' . str_pad($mid, 3, '0', STR_PAD_LEFT) . '.   {#black}'
						        . $method . LF;
						$mid++;
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
					$aseco->client->query('SendDisplayServerMessageToLogin', $login, $admin->msgs[1], 'Close', 'Next', 0);

				} elseif ($aseco->server->getGame() == 'TMF') {
					$head = 'Available Methods on this Server:';
					$msg = array();
					$msg[] = array('Id', 'Method');
					$mid = 1;
					$lines = 0;
					$admin->msgs[0] = array(1, $head, array(0.9, 0.15, 0.75), array('Icons128x128_1', 'Advanced', 0.02));
					foreach ($methods as $method) {
						$msg[] = array(str_pad($mid, 2, '0', STR_PAD_LEFT) . '.',
						               '{#black}' . $method);
						$mid++;
						if (++$lines > 14) {
							$admin->msgs[] = $msg;
							$lines = 0;
							$msg = array();
							$msg[] = array('Id', 'Method');
						}
					}
					// add if last batch exists
					if (count($msg) > 1)
						$admin->msgs[] = $msg;

					// display ManiaLink message
					display_manialink_multi($admin);
				}

			} else {  // server method
				$method = $command['params'][1];
				// collect parameters with correct types
				$args = array();
				$multistr = '';
				$in_multi = false;
				for ($i = 2; $i < count($command['params']); $i++) {
					if (!$in_multi && strtolower($command['params'][$i]) == 'true')
						$args[] = true;
					elseif (!$in_multi && strtolower($command['params'][$i]) == 'false')
						$args[] = false;
					elseif (!$in_multi && is_numeric($command['params'][$i]))
						$args[] = intval($command['params'][$i]);
					else
						// check for multi-word strings
						if ($in_multi) {
							if (substr($command['params'][$i], -1) == '"') {
								$args[] = $multistr . ' ' . substr($command['params'][$i], 0, -1);
								$multistr = '';
								$in_multi = false;
							} else {
								$multistr .= ' ' . $command['params'][$i];
							}
						} else {
							if (substr($command['params'][$i], 0, 1) == '"') {
								$multistr = substr($command['params'][$i], 1);
								$in_multi = true;
							} else {
								$args[] = $command['params'][$i];
							}
						}
				}

				// execute method
				switch (count($args)) {
				case 0: $res = $aseco->client->query($method);
				        break;
				case 1: $res = $aseco->client->query($method, $args[0]);
				        break;
				case 2: $res = $aseco->client->query($method, $args[0], $args[1]);
				        break;
				case 3: $res = $aseco->client->query($method, $args[0], $args[1], $args[2]);
				        break;
				case 4: $res = $aseco->client->query($method, $args[0], $args[1], $args[2], $args[3]);
				        break;
				case 5: $res = $aseco->client->query($method, $args[0], $args[1], $args[2], $args[3], $args[4]);
				        break;
				}
				// process result
				if ($res) {
					$res = $aseco->client->getResponse();
					$admin->msgs = array();
					$method_results = array();
					collect_results($method, $res, '');

					// compile & display result message
					if ($aseco->server->getGame() == 'TMN') {
						$head = 'Method results for:' . LF . LF;
						$msg = '';
						$mid = 1;
						$lines = 0;
						$admin->msgs[0] = 1;
						foreach ($method_results as $line) {
							$msg .= $line . '$z' . LF;
							$mid++;
							if (++$lines > 14) {
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
						} else {  // > 2
							$aseco->client->query('SendDisplayServerMessageToLogin', $login, $admin->msgs[1], 'Close', 'Next', 0);
						}

					} elseif ($aseco->server->getGame() == 'TMF') {
						$head = 'Method results for:';
						$msg = array();
						$mid = 1;
						$lines = 0;
						$admin->msgs[0] = array(1, $head, array(1.1), array('Icons128x128_1', 'Advanced', 0.02));
						foreach ($method_results as $line) {
							$msg[] = array($line);
							$mid++;
							if (++$lines > 20) {
								$admin->msgs[] = $msg;
								$lines = 0;
								$msg = array();
							}
						}
						// add if last batch exists
						if (!empty($msg))
							$admin->msgs[] = $msg;

						// display ManiaLink message
						display_manialink_multi($admin);
					}
				} else {
					$message = '{#server}> {#error}Method error for {#highlite}$i ' . $method . '{#error}: [' . $aseco->client->getErrorCode() . '] ' . $aseco->client->getErrorMessage();
					$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
				}
			}
		} else {
			$message = '{#server}> {#error}No call specified - see {#highlite}$i /admin call help{#error} and {#highlite}$i /admin call list{#error}!';
			$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
		}

	/**
	 * Unlocks admin commands & features.
	 */
	} elseif ($command['params'][0] == 'unlock' && $command['params'][1] != '') {

		// check unlock password
		if ($aseco->settings['lock_password'] == $command['params'][1]) {
			$admin->unlocked = true;
			$message = '{#server}> {#admin}Password accepted: admin commands unlocked!';
		} else {
			$message = '{#server}> {#error}Invalid password!';
		}
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);

	/**
	 * Toggle debug on/off.
	 */
	} elseif ($command['params'][0] == 'debug') {

		$aseco->debug = !$aseco->debug;
		if ($aseco->debug) {
			$message = '{#server}> Debug is now enabled';
		} else {
			$message = '{#server}> Debug is now disabled';
		}
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);

	/**
	 * Shuts down XASECO.
	 */
	} elseif ($command['params'][0] == 'shutdown') {

		trigger_error('Shutdown XASECO!', E_USER_ERROR);

	/**
	 * Shuts down Server & XASECO.
	 */
	} elseif ($command['params'][0] == 'shutdownall') {

		$message = '{#server}>> {#error}$wShutting down server now!';
		$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($message));

		$rtn = $aseco->client->query('StopServer');
		if (!$rtn) {
			trigger_error('[' . $aseco->client->getErrorCode() . '] StopServer - ' . $aseco->client->getErrorMessage(), E_USER_WARNING);
		} else {
			// test for /noautoquit
			sleep(2);
			$autoquit = new IXR_ClientMulticall_Gbx();
			if ($autoquit->InitWithIp($aseco->server->ip, $aseco->server->port))
				$aseco->client->query('QuitGame');

			trigger_error('Shutdown ' . $aseco->server->getGame() . ' server & XASECO!', E_USER_ERROR);
		}

	/**
	 * Checks current version of XASECO.
	 */
	} elseif ($command['params'][0] == 'uptodate') {

		if (function_exists('admin_uptodate')) {
			admin_uptodate($aseco, $command);  // from plugin.uptodate.php
		} else {
			// show chat message
			$message = '{#server}> {#admin}Version checking unavailable - include plugins.uptodate.php in plugins.xml';
			$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
		}

	} else {
		$message = '{#server}> {#error}Unknown admin command or missing parameter(s): {#highlite}$i ' . $arglist[0] . ' ' . $arglist[1];
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
	}
}  // chat_admin


function get_ignorelist($aseco) {

	$aseco->client->resetError();
	$newlist = array();
	$done = false;
	$size = 300;
	$i = 0;
	while (!$done) {
		$aseco->client->query('GetIgnoreList', $size, $i);
		$players = $aseco->client->getResponse();
		if (!empty($players)) {
			if ($aseco->client->isError()) {
				trigger_error('[' . $aseco->client->getErrorCode() . '] GetIgnoreList - ' . $aseco->client->getErrorMessage(), E_USER_WARNING);
				$done = true;
				break;
			}
			foreach ($players as $prow) {
				// fetch nickname for this login
				$lgn = $prow['Login'];
				$nick = $aseco->getPlayerNick($lgn);
				$newlist[$lgn] = array($lgn, $nick);
			}
			if (count($players) < $size) {
				// got less than 300 players, might as well leave
				$done = true;
			} else {
				$i += $size;
			}
		} else {
			$done = true;
		}
	}
	return $newlist;
}  // get_ignorelist

function get_banlist($aseco) {

	$aseco->client->resetError();
	$newlist = array();
	$done = false;
	$size = 300;
	$i = 0;
	while (!$done) {
		$aseco->client->query('GetBanList', $size, $i);
		$players = $aseco->client->getResponse();
		if (!empty($players)) {
			if ($aseco->client->isError()) {
				trigger_error('[' . $aseco->client->getErrorCode() . '] GetBanList - ' . $aseco->client->getErrorMessage(), E_USER_WARNING);
				$done = true;
				break;
			}
			foreach ($players as $prow) {
				// fetch nickname for this login
				$lgn = $prow['Login'];
				$nick = $aseco->getPlayerNick($lgn);
				$newlist[$lgn] = array($lgn, $nick,
				                 preg_replace('/:\d+/', '', $prow['IPAddress']));  // strip port
			}
			if (count($players) < $size) {
				// got less than 300 players, might as well leave
				$done = true;
			} else {
				$i += $size;
			}
		} else {
			$done = true;
		}
	}
	return $newlist;
}  // get_banlist

function get_blacklist($aseco) {

	$aseco->client->resetError();
	$newlist = array();
	$done = false;
	$size = 300;
	$i = 0;
	while (!$done) {
		$aseco->client->query('GetBlackList', $size, $i);
		$players = $aseco->client->getResponse();
		if (!empty($players)) {
			if ($aseco->client->isError()) {
				trigger_error('[' . $aseco->client->getErrorCode() . '] GetBlackList - ' . $aseco->client->getErrorMessage(), E_USER_WARNING);
				$done = true;
				break;
			}
			foreach ($players as $prow) {
				// fetch nickname for this login
				$lgn = $prow['Login'];
				$nick = $aseco->getPlayerNick($lgn);
				$newlist[$lgn] = array($lgn, $nick);
			}
			if (count($players) < $size) {
				// got less than 300 players, might as well leave
				$done = true;
			} else {
				$i += $size;
			}
		} else {
			$done = true;
		}
	}
	return $newlist;
}  // get_blacklist

function get_guestlist($aseco) {

	$aseco->client->resetError();
	$newlist = array();
	$done = false;
	$size = 300;
	$i = 0;
	while (!$done) {
		$aseco->client->query('GetGuestList', $size, $i);
		$players = $aseco->client->getResponse();
		if (!empty($players)) {
			if ($aseco->client->isError()) {
				trigger_error('[' . $aseco->client->getErrorCode() . '] GetGuestList - ' . $aseco->client->getErrorMessage(), E_USER_WARNING);
				$done = true;
				break;
			}
			foreach ($players as $prow) {
				// fetch nickname for this login
				$lgn = $prow['Login'];
				$nick = $aseco->getPlayerNick($lgn);
				$newlist[$lgn] = array($lgn, $nick);
			}
			if (count($players) < $size) {
				// got less than 300 players, might as well leave
				$done = true;
			} else {
				$i += $size;
			}
		} else {
			$done = true;
		}
	}
	return $newlist;
}  // get_guestlist

function collect_results($key, $val, $indent) {
	global $method_results;

	if (is_array($val)) {
		// recursively compile array results
		$method_results[] = $indent . '*' . $key . ' :';
		foreach ($val as $key2 => $val2) {
			collect_results($key2, $val2, '   ' . $indent);
		}
	} else {
		if (!is_string($val))
			$val = strval($val);
		// format result key/value pair
		$val = explode(LF, wordwrap($val, 32, LF . $indent . '      ', true));
		$firstline = true;
		foreach ($val as $line) {
			if ($firstline)
				$method_results[] = $indent . $key . ' = ' . $line;
			else
				$method_results[] = $line;
			$firstline = false;
		}
	}
}  // collect_results


// called @ onPlayerManialinkPageAnswer
// Handles ManiaLink admin responses
// [0]=PlayerUid, [1]=Login, [2]=Answer
function event_admin($aseco, $answer) {

	// leave actions outside 2201 - 5200 to other handlers
	if ($answer[2] < 2201 && $answer[2] > 5200 &&
	    $answer[2] < -8100 && $answer[2] > -7901)
		return;

	// get player & possible parameter
	$player = $aseco->server->players->getPlayer($answer[1]);
	if (isset($player->panels['plyparam']))
		$param = $player->panels['plyparam'];

	// check for /admin warn command
	if ($answer[2] >= 2201 && $answer[2] <= 2400) {
		$target = $player->playerlist[$answer[2]-2201]['login'];

		// log clicked command
		$aseco->console('player {1} clicked command "/admin warn {2}"',
		                $player->login, $target);

		// warn selected player
		$command = array();
		$command['author'] = $player;
		$command['params'] = 'warn ' . $target;
		chat_admin($aseco, $command);
	}

	// check for /admin ignore command
	elseif ($answer[2] >= 2401 && $answer[2] <= 2600) {
		$target = $player->playerlist[$answer[2]-2401]['login'];

		// log clicked command
		$aseco->console('player {1} clicked command "/admin ignore {2}"',
		                $player->login, $target);

		// ignore selected player
		$command = array();
		$command['author'] = $player;
		$command['params'] = 'ignore ' . $target;
		chat_admin($aseco, $command);

		// log clicked command
		$aseco->console('player {1} clicked command "/admin players {2}"',
		                $player->login, $param);

		// refresh players window
		$command['params'] = 'players ' . $param;
		chat_admin($aseco, $command);
	}

	// check for /admin unignore command
	elseif ($answer[2] >= 2601 && $answer[2] <= 2800) {
		$target = $player->playerlist[$answer[2]-2601]['login'];

		// log clicked command
		$aseco->console('player {1} clicked command "/admin unignore {2}"',
		                $player->login, $target);

		// unignore selected player
		$command = array();
		$command['author'] = $player;
		$command['params'] = 'unignore ' . $target;
		chat_admin($aseco, $command);

		// log clicked command
		$aseco->console('player {1} clicked command "/admin players {2}"',
		                $player->login, $param);

		// refresh players window
		$command['params'] = 'players ' . $param;
		chat_admin($aseco, $command);
	}

	// check for /admin kick command
	elseif ($answer[2] >= 2801 && $answer[2] <= 3000) {
		$target = $player->playerlist[$answer[2]-2801]['login'];

		// log clicked command
		$aseco->console('player {1} clicked command "/admin kick {2}"',
		                $player->login, $target);

		// kick selected player
		$command = array();
		$command['author'] = $player;
		$command['params'] = 'kick ' . $target;
		chat_admin($aseco, $command);

		// log clicked command
		$aseco->console('player {1} clicked command "/admin players {2}"',
		                $player->login, $param);

		// refresh players window
		$command['params'] = 'players ' . $param;
		chat_admin($aseco, $command);
	}

	// check for /admin ban command
	elseif ($answer[2] >= 3001 && $answer[2] <= 3200) {
		$target = $player->playerlist[$answer[2]-3001]['login'];

		// log clicked command
		$aseco->console('player {1} clicked command "/admin ban {2}"',
		                $player->login, $target);

		// ban selected player
		$command = array();
		$command['author'] = $player;
		$command['params'] = 'ban ' . $target;
		chat_admin($aseco, $command);

		// log clicked command
		$aseco->console('player {1} clicked command "/admin players {2}"',
		                $player->login, $param);

		// refresh players window
		$command['params'] = 'players ' . $param;
		chat_admin($aseco, $command);
	}

	// check for /admin unban command
	elseif ($answer[2] >= 3201 && $answer[2] <= 3400) {
		$target = $player->playerlist[$answer[2]-3201]['login'];

		// log clicked command
		$aseco->console('player {1} clicked command "/admin unban {2}"',
		                $player->login, $target);

		// unban selected player
		$command = array();
		$command['author'] = $player;
		$command['params'] = 'unban ' . $target;
		chat_admin($aseco, $command);

		// log clicked command
		$aseco->console('player {1} clicked command "/admin players {2}"',
		                $player->login, $param);

		// refresh players window
		$command['params'] = 'players ' . $param;
		chat_admin($aseco, $command);
	}

	// check for /admin black command
	elseif ($answer[2] >= 3401 && $answer[2] <= 3600) {
		$target = $player->playerlist[$answer[2]-3401]['login'];

		// log clicked command
		$aseco->console('player {1} clicked command "/admin black {2}"',
		                $player->login, $target);

		// black selected player
		$command = array();
		$command['author'] = $player;
		$command['params'] = 'black ' . $target;
		chat_admin($aseco, $command);

		// log clicked command
		$aseco->console('player {1} clicked command "/admin players {2}"',
		                $player->login, $param);

		// refresh players window
		$command['params'] = 'players ' . $param;
		chat_admin($aseco, $command);
	}

	// check for /admin unblack command
	elseif ($answer[2] >= 3601 && $answer[2] <= 3800) {
		$target = $player->playerlist[$answer[2]-3601]['login'];

		// log clicked command
		$aseco->console('player {1} clicked command "/admin unblack {2}"',
		                $player->login, $target);

		// unblack selected player
		$command = array();
		$command['author'] = $player;
		$command['params'] = 'unblack ' . $target;
		chat_admin($aseco, $command);

		// log clicked command
		$aseco->console('player {1} clicked command "/admin players {2}"',
		                $player->login, $param);

		// refresh players window
		$command['params'] = 'players ' . $param;
		chat_admin($aseco, $command);
	}

	// check for /admin addguest command
	elseif ($answer[2] >= 3801 && $answer[2] <= 4000) {
		$target = $player->playerlist[$answer[2]-3801]['login'];

		// log clicked command
		$aseco->console('player {1} clicked command "/admin addguest {2}"',
		                $player->login, $target);

		// addguest selected player
		$command = array();
		$command['author'] = $player;
		$command['params'] = 'addguest ' . $target;
		chat_admin($aseco, $command);

		// log clicked command
		$aseco->console('player {1} clicked command "/admin players {2}"',
		                $player->login, $param);

		// refresh players window
		$command['params'] = 'players ' . $param;
		chat_admin($aseco, $command);
	}

	// check for /admin removeguest command
	elseif ($answer[2] >= 4001 && $answer[2] <= 4200) {
		$target = $player->playerlist[$answer[2]-4001]['login'];

		// log clicked command
		$aseco->console('player {1} clicked command "/admin removeguest {2}"',
		                $player->login, $target);

		// removeguest selected player
		$command = array();
		$command['author'] = $player;
		$command['params'] = 'removeguest ' . $target;
		chat_admin($aseco, $command);

		// log clicked command
		$aseco->console('player {1} clicked command "/admin players {2}"',
		                $player->login, $param);

		// refresh players window
		$command['params'] = 'players ' . $param;
		chat_admin($aseco, $command);
	}

	// check for /admin forcespec command
	elseif ($answer[2] >= 4201 && $answer[2] <= 4400) {
		$target = $player->playerlist[$answer[2]-4201]['login'];

		// log clicked command
		$aseco->console('player {1} clicked command "/admin forcespec {2}"',
		                $player->login, $target);

		// forcespec selected player
		$command = array();
		$command['author'] = $player;
		$command['params'] = 'forcespec ' . $target;
		chat_admin($aseco, $command);

		// log clicked command
		$aseco->console('player {1} clicked command "/admin players {2}"',
		                $player->login, $param);

		// refresh players window
		$command['params'] = 'players ' . $param;
		chat_admin($aseco, $command);
	}

	// check for /admin unignore command in listignores
	elseif ($answer[2] >= 4401 && $answer[2] <= 4600) {
		$target = $player->playerlist[$answer[2]-4401]['login'];

		// log clicked command
		$aseco->console('player {1} clicked command "/admin unignore {2}"',
		                $player->login, $target);

		// unignore selected player
		$command = array();
		$command['author'] = $player;
		$command['params'] = 'unignore ' . $target;
		chat_admin($aseco, $command);

		// check whether last player was unignored
		$ignores = get_ignorelist($aseco);
		if (empty($ignores)) {
			// close main window
			mainwindow_off($aseco, $player->login);
		} else {
			// log clicked command
			$aseco->console('player {1} clicked command "/admin listignores"',
			                $player->login);

			// refresh listignores window
			$command['params'] = 'listignores';
			chat_admin($aseco, $command);
		}
	}

	// check for /admin unban command in listbans
	elseif ($answer[2] >= 4601 && $answer[2] <= 4800) {
		$target = $player->playerlist[$answer[2]-4601]['login'];

		// log clicked command
		$aseco->console('player {1} clicked command "/admin unban {2}"',
		                $player->login, $target);

		// unban selected player
		$command = array();
		$command['author'] = $player;
		$command['params'] = 'unban ' . $target;
		chat_admin($aseco, $command);

		// check whether last player was unbanned
		$bans = get_banlist($aseco);
		if (empty($bans)) {
			// close main window
			mainwindow_off($aseco, $player->login);
		} else {
			// log clicked command
			$aseco->console('player {1} clicked command "/admin listbans"',
			                $player->login);

			// refresh listbans window
			$command['params'] = 'listbans';
			chat_admin($aseco, $command);
		}
	}

	// check for /admin unblack command in listblacks
	elseif ($answer[2] >= 4801 && $answer[2] <= 5000) {
		$target = $player->playerlist[$answer[2]-4801]['login'];

		// log clicked command
		$aseco->console('player {1} clicked command "/admin unblack {2}"',
		                $player->login, $target);

		// unblack selected player
		$command = array();
		$command['author'] = $player;
		$command['params'] = 'unblack ' . $target;
		chat_admin($aseco, $command);

		// check whether last player was unblacked
		$blacks = get_blacklist($aseco);
		if (empty($blacks)) {
			// close main window
			mainwindow_off($aseco, $player->login);
		} else {
			// log clicked command
			$aseco->console('player {1} clicked command "/admin listblacks"',
			                $player->login);

			// refresh listblacks window
			$command['params'] = 'listblacks';
			chat_admin($aseco, $command);
		}
	}

	// check for /admin removeguest command in listguests
	elseif ($answer[2] >= 5001 && $answer[2] <= 5200) {
		$target = $player->playerlist[$answer[2]-5001]['login'];

		// log clicked command
		$aseco->console('player {1} clicked command "/admin removeguest {2}"',
		                $player->login, $target);

		// removeguest selected player
		$command = array();
		$command['author'] = $player;
		$command['params'] = 'removeguest ' . $target;
		chat_admin($aseco, $command);

		// check whether last guest was removed
		$guests = get_guestlist($aseco);
		if (empty($guests)) {
			// close main window
			mainwindow_off($aseco, $player->login);
		} else {
			// log clicked command
			$aseco->console('player {1} clicked command "/admin listguests"',
			                $player->login);

			// refresh listguests window
			$command['params'] = 'listguests';
			chat_admin($aseco, $command);
		}
	}

	// check for /admin unbanip command
	elseif ($answer[2] >= -8100 && $answer[2] <= -7901) {
		$target = $player->playerlist[abs($answer[2])-7901]['ip'];

		// log clicked command
		$aseco->console('player {1} clicked command "/admin unbanip {2}"',
		                $player->login, $target);

		// unbanip selected IP
		$command = array();
		$command['author'] = $player;
		$command['params'] = 'unbanip ' . $target;
		chat_admin($aseco, $command);

		// check whether last IP was unbanned
		if (!$empty = empty($aseco->bannedips)) {
			$empty = true;
			for ($i = 0; $i < count($aseco->bannedips); $i++)
				if ($aseco->bannedips[$i] != '') {
					$empty = false;
					break;
				}
		}
		if ($empty) {
			// close main window
			mainwindow_off($aseco, $player->login);
		} else {
			// log clicked command
			$aseco->console('player {1} clicked command "/admin listips"',
			                $player->login);

			// refresh listips window
			$command['params'] = 'listips';
			chat_admin($aseco, $command);
		}
	}
}  // event_admin
?>
