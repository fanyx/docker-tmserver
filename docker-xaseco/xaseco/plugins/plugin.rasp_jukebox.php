<?php
/* vim: set noexpandtab tabstop=2 softtabstop=2 shiftwidth=2: */

/**
 * Jukebox plugin.
 * Allow players to add tracks to the 'jukebox' so they can play favorites
 * without waiting. Each player can only have one track in jukebox at a time.
 * Also allows to add a track from TMX, and provides related chat commands,
 * including TMX searches.
 * Finally, handles the voting and passing for chat-based votes.
 * Updated by Xymph
 *
 * Dependencies: requires plugin.rasp_votes.php, plugin.track.php, chat.records2.php;
 *               used by plugin.rasp_votes.php
 */

require_once('includes/rasp.funcs.php');  // functions for the RASP plugins
require_once('includes/tmxinfosearcher.inc.php');  // provides TMX searches

// Register events and chat commands with aseco
Aseco::registerEvent('onSync', 'init_jbhistory');
Aseco::registerEvent('onEndRace', 'rasp_endrace');
Aseco::registerEvent('onNewChallenge', 'rasp_newtrack');

// handles action id's "101"-"2000" for jukeboxing max. 1900 tracks
// handles action id's "-101"-"-2000" for listing max. 1900 authors
// handles action id's "-2001"-"-2100" for dropping max. 100 jukeboxed tracks
// handles action id's "-6001"-"-7900" for invoking /karma on max. 1900 tracks
// handles action id's "5201"-"5700" for invoking /tmxinfo on max. 500 tracks
// handles action id's "5701"-"6200" for invoking /add on max. 500 tracks
// handles action id's "6201"-"6700" for invoking /admin add on max. 500 tracks
// handles action id's "6701"-"7200" for invoking /xlist auth: on max. 500 authors
Aseco::registerEvent('onPlayerManialinkPageAnswer', 'event_jukebox');

Aseco::addChatCommand('list', 'Lists tracks currently on the server (see: /list help)');
Aseco::addChatCommand('jukebox', 'Sets track to be played next (see: /jukebox help)');
if (ABBREV_COMMANDS) {
	Aseco::addChatCommand('jb', 'Sets a track to be played next (see: /jb help)');
	function chat_jb($aseco, $command) { chat_jukebox($aseco, $command); }
}
Aseco::addChatCommand('autojuke', 'Jukeboxes track from /list (see: /autojuke help)');
if (ABBREV_COMMANDS) {
	Aseco::addChatCommand('aj', 'Jukeboxes track from /list (see: /aj help)');
	function chat_aj($aseco, $command) { chat_autojuke($aseco, $command); }
}
Aseco::addChatCommand('add', 'Adds a track directly from TMX (<ID> {sec})');
Aseco::addChatCommand('y', 'Votes Yes for a TMX track or chat-based vote');
Aseco::addChatCommand('history', 'Shows the 10 most recently played tracks');
Aseco::addChatCommand('xlist', 'Lists tracks on TMX (see: /xlist help)');

// called @ onEndRace
function rasp_endrace($aseco, $data) {
	global $rasp, $tmxadd, $jukebox, $jukebox_check, $jukebox_skipleft, $jukebox_adminnoskip,
	       $jukebox_in_window, $tmxplaying, $autosave_matchsettings, $replays_counter, $replays_total;

	// check for relay server
	if ($aseco->server->isrelay) return;

	// check for & cancel ongoing TMX vote
	if (!empty($tmxadd)) {
		$aseco->console('Vote by {1} to add {2} reset!',
		                $tmxadd['login'], stripColors($tmxadd['name'], false));
		$message = $rasp->messages['JUKEBOX_CANCEL'][0];
		if ($jukebox_in_window && function_exists('send_window_message'))
			send_window_message($aseco, $message, true);
		else
			$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($message));
		$tmxadd = array();
		// disable all vote panels
		if ($aseco->server->getGame() == 'TMF')
			allvotepanels_off($aseco);
	}

	// reset UID check
	$jukebox_check = '';

	// check for jukeboxed track(s)
	if (!empty($jukebox)) {
		if ($aseco->debug) {
			$aseco->console_text('rasp_endrace step1 - $jukebox:' . CRLF .
			                     print_r($jukebox, true));
		}

		// skip jukeboxed track(s) if their requesters left
		if ($jukebox_skipleft) {
			// go over jukeboxed tracks
			while ($next = array_shift($jukebox)) {
				// check if requester is still online, or was admin
				foreach ($aseco->server->players->player_list as $pl) {
					if ($pl->login == $next['Login'] ||
					    ($jukebox_adminnoskip && $aseco->isAnyAdminL($next['Login']))) {
						// found player, so proceed to play this track
						// put it back for rasp_newtrack to remove
						$uid = $next['uid'];
						$jukebox = array_merge(array($uid => $next), $jukebox);
						break 2;  // exit foreach & while
					}
				}
				// player offline, so report skip
				$message = '{RASP Jukebox} Skipping Next Challenge ' . stripColors($next['Name'], false) . ' because requester ' . stripColors($next['Nick'], false) . ' left';
				$aseco->console_text($message);
				$message = formatText($rasp->messages['JUKEBOX_SKIPLEFT'][0],
				                      stripColors($next['Name']), stripColors($next['Nick']));
				if ($jukebox_in_window && function_exists('send_window_message'))
					send_window_message($aseco, $message, true);
				else
					$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($message));

				// throw 'jukebox changed' event
				$aseco->releaseEvent('onJukeboxChanged', array('skip', $next));
			}
			// if jukebox went empty, bail out
			if (!isset($next)) return;
		} else {
			// just play the next track
			$next = array_shift($jukebox);
			// put it back for rasp_newtrack to remove
			$uid = $next['uid'];
			$jukebox = array_merge(array($uid => $next), $jukebox);
		}

		// remember UID of next track to check whether it really plays
		$jukebox_check = $uid;

		if ($aseco->debug) {
			$aseco->console_text('rasp_endrace step2 - $jukebox_check: ' . $jukebox_check);
			$aseco->console_text('rasp_endrace step2 - $jukebox:' . CRLF .
			                     print_r($jukebox, true));
		}

		// if a TMX track, add it to server
		if ($next['tmx']) {
			if ($aseco->debug) {
				$aseco->console_text('{RASP Jukebox} ' . $next['source'] . ' challenge filename is ' . $next['FileName']);
			}
			$rtn = $aseco->client->query('AddChallenge', $next['FileName']);
			if (!$rtn) {
				trigger_error('[' . $aseco->client->getErrorCode() . '] AddChallenge - ' . $aseco->client->getErrorMessage(), E_USER_WARNING);
				return;
			} else {
				// throw 'tracklist changed' event
				$aseco->releaseEvent('onTracklistChanged', array('juke', $next['FileName']));
			}
		}

		// select jukebox/TMX track as next challenge
		$rtn = $aseco->client->query('ChooseNextChallenge', $next['FileName']);
		if (!$rtn) {
			trigger_error('[' . $aseco->client->getErrorCode() . '] ChooseNextChallenge - ' . $aseco->client->getErrorMessage(), E_USER_WARNING);
		} else {
			// check for TMF United
			if ($aseco->server->getGame() == 'TMF' && $aseco->server->packmask != 'Stadium') {
				// report track change from TMX or jukebox
				if ($next['tmx']) {
					$logmsg = '{RASP Jukebox} Setting Next Challenge to [' . $next['Env'] . '] ' . stripColors($next['Name'], false) . ', file downloaded from ' . $next['source'];
					// remember it for later removal
					$tmxplaying = $next['FileName'];
				} else {
					$logmsg = '{RASP Jukebox} Setting Next Challenge to [' . $next['Env'] . '] ' . stripColors($next['Name'], false) . ', requested by ' . stripColors($next['Nick'], false);
				}
				$message = formatText($rasp->messages['JUKEBOX_NEXTENV'][0],
				                      $next['Env'], stripColors($next['Name']), stripColors($next['Nick']));
			} else {  // TMN(F)
				// report track change from TMX or jukebox
				if ($next['tmx']) {
					$logmsg = '{RASP Jukebox} Setting Next Challenge to ' . stripColors($next['Name'], false) . ', file downloaded from ' . $next['source'];
					// remember it for later removal
					$tmxplaying = $next['FileName'];
				} else {
					$logmsg = '{RASP Jukebox} Setting Next Challenge to ' . stripColors($next['Name'], false) . ', requested by ' . stripColors($next['Nick'], false);
				}
				$message = formatText($rasp->messages['JUKEBOX_NEXT'][0],
				                      stripColors($next['Name']), stripColors($next['Nick']));
			}
			$aseco->console_text($logmsg);
			if ($jukebox_in_window && function_exists('send_window_message'))
				send_window_message($aseco, $message, true);
			else
				$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($message));
		}
	} else {
		// reset just in case current track was replayed
		$replays_counter = 0;
		$replays_total = 0;
	}

	// check for autosaving tracklist
	if ($autosave_matchsettings != '') {
		$rtn = $aseco->client->query('SaveMatchSettings', 'MatchSettings/' . $autosave_matchsettings);
		if (!$rtn) {
			trigger_error('[' . $aseco->client->getErrorCode() . '] SaveMatchSettings - ' . $aseco->client->getErrorMessage(), E_USER_WARNING);
		} else {
			// should a random filter be added?
			if ($aseco->settings['writetracklist_random']) {
				$tracksfile = $aseco->server->trackdir . 'MatchSettings/' . $autosave_matchsettings;
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
		}
	}
}  // rasp_endrace

// called @ onNewChallenge
function rasp_newtrack($aseco, $data) {
	global $rasp, $buffersize, $jukebox, $jb_buffer, $tmxplaying, $tmxplayed,
	       $jukebox_check, $jukebox_failed, $jukebox_permadd, $replays_counter, $replays_total;

	// check for relay server
	if ($aseco->server->isrelay) return;

	// don't duplicate replayed track in history
	if (!empty($jb_buffer)) {
		$previous = array_pop($jb_buffer);
		// put back previous track if different
		if ($previous != $data->uid)
			$jb_buffer[] = $previous;
	}
	// remember current track in history
	if (count($jb_buffer) >= $buffersize) {
		// drop oldest track if buffer full
		array_shift($jb_buffer);
	}
	// append current track to history
	$jb_buffer[] = $data->uid;

	// write track history to file in case of XASECO restart
	if ($fp = @fopen($aseco->server->trackdir . $aseco->settings['trackhist_file'], 'wb')) {
		foreach ($jb_buffer as $uid)
			fwrite($fp, $uid . CRLF);
		fclose($fp);
	} else {
		trigger_error('Could not write track history file ' . $aseco->server->trackdir . $aseco->settings['trackhist_file'] . ' !', E_USER_WARNING);
	}

	// process jukebox
	if (!empty($jukebox)) {
		if ($aseco->debug) {
			$aseco->console_text('rasp_newtrack step1 - $data->uid: ' . $data->uid);
			$aseco->console_text('rasp_newtrack step1 - $jukebox_check: ' . $jukebox_check);
			$aseco->console_text('rasp_newtrack step1 - $jukebox:' . CRLF .
			                     print_r($jukebox, true));
		}
		// look for current track in jukebox
		if (array_key_exists($data->uid, $jukebox)) {
			if ($aseco->debug) {
				$message = '{RASP Jukebox} Current Challenge ' .
				           stripColors($jukebox[$data->uid]['Name'], false) . ' loaded - index: ' .
				           array_search($data->uid, array_keys($jukebox));
				$aseco->console_text($message);
			}

			// check for /replay-ed track
			if ($jukebox[$data->uid]['source'] == 'Replay')
				$replays_counter++;
			else
				$replays_counter = 0;
			if (substr($jukebox[$data->uid]['source'], -6) == 'Replay') // AdminReplay
				$replays_total++;
			else
				$replays_total = 0;

			// remove loaded track
			$play = $jukebox[$data->uid];
			unset($jukebox[$data->uid]);

			if ($aseco->debug) {
				$aseco->console_text('rasp_newtrack step2a - $jukebox:' . CRLF .
				                     print_r($jukebox, true));
			}

			// throw 'jukebox changed' event
			$aseco->releaseEvent('onJukeboxChanged', array('play', $play));
		} else {
			// look for intended track in jukebox
			if ($jukebox_check != '') {
				if (array_key_exists($jukebox_check, $jukebox)) {
					if ($aseco->debug) {
						$message = '{RASP Jukebox} Intended Challenge ' .
						           stripColors($jukebox[$jukebox_check]['Name'], false) . ' dropped - index: ' .
						           array_search($jukebox_check, array_keys($jukebox));
						$aseco->console_text($message);
					}

					// drop stuck track
					$stuck = $jukebox[$jukebox_check];
					unset($jukebox[$jukebox_check]);

					if ($aseco->debug) {
						$aseco->console_text('rasp_newtrack step2b - $jukebox:' . CRLF .
						                     print_r($jukebox, true));
					}

					// throw 'jukebox changed' event
					$aseco->releaseEvent('onJukeboxChanged', array('drop', $stuck));
				} else {
					if ($aseco->debug) {
						$message = '{RASP Jukebox} Intended Challenge ' . $jukebox_check . ' not found!';
						$aseco->console_text($message);
					}
				}
			}
		}
	}

	// remove previous TMX track from server
	if ($tmxplayed) {
		// unless it is permanent
		if (!$jukebox_permadd) {
			if ($aseco->debug) {
				$aseco->console_text('rasp_newtrack step3 - remove: ' . $tmxplayed);
			}
			$rtn = $aseco->client->query('RemoveChallenge', $tmxplayed);
			if (!$rtn) {
				trigger_error('[' . $aseco->client->getErrorCode() . '] RemoveChallenge - ' . $aseco->client->getErrorMessage(), E_USER_WARNING);
			} else {
				// throw 'tracklist changed' event
				$aseco->releaseEvent('onTracklistChanged', array('unjuke', $tmxplayed));
			}
		}
		$tmxplayed = false;
	}
	// check whether current track was from TMX
	if ($tmxplaying) {
		// remember it for removal afterwards
		$tmxplayed = $tmxplaying;
		$tmxplaying = false;
	}
}  // rasp_newtrack

// calls function disp_recs() from chat.records2.php
function chat_list($aseco, $command) {
	global $feature_karma;  // from rasp.settings.php

	$player = $command['author'];
	$login = $player->login;

	// check for relay server
	if ($aseco->server->isrelay) {
		$message = formatText($aseco->getChatMessage('NOTONRELAY'));
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
		return;
	}

	// split params into array
	$arglist = preg_replace('/ +/', ' ', $command['params']);
	$command['params'] = explode(' ', $arglist);
	$cmdcount = count($command['params']);

	if ($cmdcount == 1 && $command['params'][0] == 'help') {
		if ($aseco->server->getGame() == 'TMN') {
			$help = '{#black}/list$g will show tracks in rotation on the server:' . LF;
			$help .= '  - {#black}help$g, displays this help information' . LF;
			$help .= '  - {#black}nofinish$g, tracks you haven\'t completed' . LF;
			$help .= '  - {#black}norank$g, tracks you don\'t have a rank on' . LF;
			$help .= '  - {#black}nogold$g, tracks you didn\'t beat gold time on' . LF;
			$help .= '  - {#black}noauthor$g, tracks you didn\'t beat author time on' . LF;
			$help .= '  - {#black}norecent$g, tracks you didn\'t play recently' . LF;
			$help .= '  - {#black}best$g/{#black}worst$g, tracks with your best/worst records' . LF;
			$help .= '  - {#black}longest$g/{#black}shortest$g, the longest/shortest tracks' . LF;
			$help .= '  - {#black}newest$g/{#black}oldest #$g, newest/oldest # tracks (def: 50)' . LF;
			$help .= '  - {#black}xxx$g, where xxx is part of a track or author name' . LF;
		if ($feature_karma) {
			$help .= '  - {#black}novote$g, tracks you didn\'t karma vote for' . LF;
			$help .= '  - {#black}karma +/-#$g, display all tracks with karma >= or <=' . LF
			       . '     given value (example: {#black}/list karma -3$g shows all' . LF
			       . '     tracks with karma equal or worse than -3)' . LF;
		}
			$help .= LF . 'Pick an Id number from the list, and use {#black}/jukebox #';
			// display popup message
			$aseco->client->query('SendDisplayServerMessageToLogin', $login, $aseco->formatColors($help), 'OK', '', 0);
		} elseif ($aseco->server->getGame() == 'TMF') {
			$header = '{#black}/list$g will show tracks in rotation on the server:';
			$help = array();
			$help[] = array('...', '{#black}help',
			                'Displays this help information');
			$help[] = array('...', '{#black}nofinish',
			                'Shows tracks you haven\'t completed');
			$help[] = array('...', '{#black}norank',
			                'Shows tracks you don\'t have a rank on');
			$help[] = array('...', '{#black}nogold',
			                'Shows tracks you didn\'t beat gold ' .
			                 ($aseco->server->gameinfo->mode == Gameinfo::STNT ? 'score on' : 'time on'));
			$help[] = array('...', '{#black}noauthor',
			                'Shows tracks you didn\'t beat author '.
			                 ($aseco->server->gameinfo->mode == Gameinfo::STNT ? 'score on' : 'time on'));
			$help[] = array('...', '{#black}norecent',
			                'Shows tracks you didn\'t play recently');
			$help[] = array('...', '{#black}best$g/{#black}worst',
			                'Shows tracks with your best/worst records');
		if ($aseco->server->gameinfo->mode != Gameinfo::STNT) {
			$help[] = array('...', '{#black}longest$g/{#black}shortest',
			                'Shows the longest/shortest tracks');
		}
			$help[] = array('...', '{#black}newest$g/{#black}oldest #',
			                'Shows newest/oldest # tracks (def: 50)');
			$help[] = array('...', '{#black}xxx',
			                'Where xxx is part of a track or author name');
		if ($aseco->server->packmask != 'Stadium') {
			$help[] = array('...', '{#black}env:zzz',
			                'Where zzz is an environment from: stadium,');
			$help[] = array('', '',
			                'bay,coast,island,alpine/snow,desert/speed,rally');
			$help[] = array('...', '{#black}xxx env:zzz',
			                'Combines the name and environment searches');
		}
		if ($feature_karma) {
			$help[] = array('...', '{#black}novote',
			                'Shows tracks you didn\'t karma vote for');
			$help[] = array('...', '{#black}karma +/-#',
			                'Shows all tracks with karma >= or <=');
			$help[] = array('', '',
			                'given value (example: {#black}/list karma -3$g shows all');
			$help[] = array('', '',
			                'tracks with karma equal or worse than -3)');
		}
			$help[] = array();
			$help[] = array('Pick an Id number from the list, and use {#black}/jukebox #');
			// display ManiaLink message
			display_manialink($login, $header, array('Icons64x64_1', 'TrackInfo', -0.01), $help, array(1.1, 0.05, 0.3, 0.75), 'OK');
		}
		return;
	}
	elseif ($cmdcount == 1 && $command['params'][0] == 'nofinish') {
		getChallengesNoFinish($player);
	}
	elseif ($cmdcount == 1 && $command['params'][0] == 'norank') {
		getChallengesNoRank($player);
	}
	elseif ($cmdcount == 1 && $command['params'][0] == 'nogold') {
		getChallengesNoGold($player);
	}
	elseif ($cmdcount == 1 && $command['params'][0] == 'noauthor') {
		getChallengesNoAuthor($player);
	}
	elseif ($cmdcount == 1 && $command['params'][0] == 'norecent') {
		getChallengesNoRecent($player);
	}
	elseif ($cmdcount == 1 && $command['params'][0] == 'best') {
		// avoid interference from possible parameters
		$command['params'] = '';
		// display player records, best first
		disp_recs($aseco, $command, true);  // from chat.records2.php
		return;
	}
	elseif ($cmdcount == 1 && $command['params'][0] == 'worst') {
		// avoid interference from possible parameters
		$command['params'] = '';
		// display player records, worst first
		disp_recs($aseco, $command, false);  // from chat.records2.php
		return;
	}
	elseif ($cmdcount == 1 && $command['params'][0] == 'longest') {
		getChallengesByLength($player, false);
	}
	elseif ($cmdcount == 1 && $command['params'][0] == 'shortest') {
		getChallengesByLength($player, true);
	}
	elseif ($cmdcount >= 1 && $command['params'][0] == 'newest') {
		$count = 50;  // default
		if ($cmdcount == 2 && is_numeric($command['params'][1]) && $command['params'][1] > 0)
			$count = intval($command['params'][1]);
		getChallengesByAdd($player, true, $count);
	}
	elseif ($cmdcount >= 1 && $command['params'][0] == 'oldest') {
		$count = 50;  // default
		if ($cmdcount == 2 && is_numeric($command['params'][1]) && $command['params'][1] > 0)
			$count = intval($command['params'][1]);
		getChallengesByAdd($player, false, $count);
	}
	elseif ($cmdcount == 1 && $command['params'][0] == 'novote' && $feature_karma) {
		getChallengesNoVote($player);
	}
	elseif ($cmdcount == 2 && $command['params'][0] == 'karma' && $feature_karma) {
		$karmaval = intval($command['params'][1]);
		getChallengesByKarma($player, $karmaval);
	}
	elseif ($cmdcount >= 1 && strlen($command['params'][0]) > 0) {
		// check for TMUF
		if ($aseco->server->getGame() == 'TMF' && $aseco->server->packmask != 'Stadium') {
			$env = '*';  // wildcard
			// find and delete optional env: parameter
			foreach ($command['params'] as &$param) {
				if (strtolower(substr($param, 0, 4)) == 'env:') {
					$env = substr($param, 4);
					// map external to internal envs, allowing abbreviations
					if (stripos('desert', $env) === 0)
						$env = 'speed';
					elseif (stripos('snow', $env) === 0)
						$env = 'alpine';
					$param = '';  // drop env:zzz from arglist
				}
			}
			// rebuild parameter list
			$arglist = trim(implode(' ', $command['params']));
			// set wildcard name if searching for env
			if ($arglist == '') $arglist = '*';
			getAllChallenges($player, $arglist, $env);
		} else { // TMN(F)/TMS/TMO
			getAllChallenges($player, $arglist, '*');  // wildcard
		}
	}
	else {
		getAllChallenges($player, '*', '*');  // wildcards
	}

	if (empty($player->tracklist)) {
		$message = '{#server}> {#error}No tracks found, try again!';
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
		return;
	}
	if ($aseco->server->getGame() == 'TMN') {
		// display popup message
		if (count($player->msgs) == 2) {
			$aseco->client->query('SendDisplayServerMessageToLogin', $login, $player->msgs[1], 'OK', '', 0);
		} else {  // > 2
			$aseco->client->query('SendDisplayServerMessageToLogin', $login, $player->msgs[1], 'Close', 'Next', 0);
		}
	} elseif ($aseco->server->getGame() == 'TMF') {
		// display ManiaLink message
		display_manialink_multi($player);
	}
}  // chat_list

function chat_jukebox($aseco, $command) {
	global $rasp, $feature_jukebox, $jukebox_in_window, $jukebox, $jb_buffer;

	$player = $command['author'];
	$login = $player->login;

	// check for relay server
	if ($aseco->server->isrelay) {
		$message = formatText($aseco->getChatMessage('NOTONRELAY'));
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
		return;
	}

	if ($feature_jukebox || $aseco->allowAbility($player, 'chat_jukebox')) {
		// check parameter
		$param = $command['params'];
		if (is_numeric($param) && $param >= 0) {
			if (empty($player->tracklist)) {
				$message = $rasp->messages['LIST_HELP'][0];
				$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
				return;
			}

			// check for track by this player in jukebox
			if (!$aseco->allowAbility($player, 'chat_jb_multi')) {
				foreach ($jukebox as $key) {
					if ($login == $key['Login']) {
						$message = $rasp->messages['JUKEBOX_ALREADY'][0];
						$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
						return;
					}
				}
			}

			// find track by given #
			$jid = ltrim($param, '0');
			$jid--;
			if (array_key_exists($jid, $player->tracklist)) {
				$uid = $player->tracklist[$jid]['uid'];
				// check if track is already queued in jukebox
				if (array_key_exists($uid, $jukebox)) {  // find by uid in jukebox
					$message = $rasp->messages['JUKEBOX_DUPL'][0];
					$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
					return;
				}
				// check if track was recently played
				elseif (in_array($uid, $jb_buffer)) {
					$message = $rasp->messages['JUKEBOX_REPEAT'][0];
					$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
					// if not an admin with this ability, bail out
					if (!$aseco->allowAbility($player, 'chat_jb_recent'))
						return;
				}

				// check track vs. server settings
				if ($aseco->server->getGame() == 'TMF')
					$rtn = $aseco->client->query('CheckChallengeForCurrentServerParams', $player->tracklist[$jid]['filename']);
				else
					$rtn = true;
				if (!$rtn) {
					trigger_error('[' . $aseco->client->getErrorCode() . '] CheckChallengeForCurrentServerParams - ' . $aseco->client->getErrorMessage(), E_USER_WARNING);
					$message = formatText($rasp->messages['JUKEBOX_IGNORED'][0],
					                      stripColors($player->tracklist[$jid]['name']), $aseco->client->getErrorMessage());
					$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
				} else {
					// add track to jukebox
					$jukebox[$uid]['FileName'] = $player->tracklist[$jid]['filename'];
					$jukebox[$uid]['Name'] = $player->tracklist[$jid]['name'];
					$jukebox[$uid]['Env'] = $player->tracklist[$jid]['environment'];
					$jukebox[$uid]['Login'] = $player->login;
					$jukebox[$uid]['Nick'] = $player->nickname;
					$jukebox[$uid]['source'] = 'Jukebox';
					$jukebox[$uid]['tmx'] = false;
					$jukebox[$uid]['uid'] = $uid;
					$message = formatText($rasp->messages['JUKEBOX'][0],
					                      stripColors($player->tracklist[$jid]['name']),
					                      stripColors($player->nickname));
					if ($jukebox_in_window && function_exists('send_window_message'))
						send_window_message($aseco, $message, false);
					else
						$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($message));
	
					// throw 'jukebox changed' event
					$aseco->releaseEvent('onJukeboxChanged', array('add', $jukebox[$uid]));
				}
			} else {
				$message = $rasp->messages['JUKEBOX_NOTFOUND'][0];
				$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
			}
		}
		elseif ($param == 'list') {
			if (!empty($jukebox)) {
				$message = $rasp->messages['JUKEBOX_LIST'][0];
				$i = 1;
				foreach ($jukebox as $item) {
					$message .= '{#highlite}' . $i . '{#emotic}.[{#highlite}' . stripColors($item['Name']) . '{#emotic}], ';
					$i++;
				}
				$message = substr($message, 0, strlen($message)-2);  // strip trailing ", "
				$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
			} else {
				$message = $rasp->messages['JUKEBOX_EMPTY'][0];
				$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
			}
		}
		elseif ($param == 'display') {
			if (!empty($jukebox)) {
				if ($aseco->server->getGame() == 'TMN') {
					$head = 'Upcoming tracks in the jukebox:' . LF . 'Id     Name / Requester' . LF;
					$msg = '';
					$tid = 1;
					$lines = 0;
					$player->msgs = array();
					$player->msgs[0] = 1;
					foreach ($jukebox as $item) {
						$trackname = $item['Name'];
						if (!$aseco->settings['lists_colortracks'])
							$trackname = stripColors($trackname);
						$msg .= '$g' . str_pad($tid, 2, '0', STR_PAD_LEFT) . '.   {#black}'
						        . $trackname . '$z / {#black}' . stripColors($item['Nick']) . LF;
						$tid++;
						if (++$lines > 9) {
							$player->msgs[] = $aseco->formatColors($head . $msg);
							$lines = 0;
							$msg = '';
						}
					}
					// add if last batch exists
					if ($msg != '')
						$player->msgs[] = $aseco->formatColors($head . $msg);

					// display popup message
					if (count($player->msgs) == 2) {
						$aseco->client->query('SendDisplayServerMessageToLogin', $login, $player->msgs[1], 'OK', '', 0);
					} else {  // > 2
						$aseco->client->query('SendDisplayServerMessageToLogin', $login, $player->msgs[1], 'Close', 'Next', 0);
					}

				} elseif ($aseco->server->getGame() == 'TMF') {
					// determine admin ability to drop all jukeboxed tracks
					$dropall = $aseco->allowAbility($player, 'dropjukebox');
					$head = 'Upcoming tracks in the jukebox:';
					$page = array();
					if ($aseco->server->packmask != 'Stadium')
						if ($aseco->settings['clickable_lists'])
							$page[] = array('Id', 'Name (click to drop)', 'Env', 'Requester');
						else
							$page[] = array('Id', 'Name', 'Env', 'Requester');
					else
						if ($aseco->settings['clickable_lists'])
							$page[] = array('Id', 'Name (click to drop)', 'Requester');
						else
							$page[] = array('Id', 'Name', 'Requester');

					$tid = 1;
					$lines = 0;
					$player->msgs = array();
					// reserve extra width for $w tags
					$extra = ($aseco->settings['lists_colortracks'] ? 0.2 : 0);
					if ($aseco->server->packmask != 'Stadium')
						$player->msgs[0] = array(1, $head, array(1.25+$extra, 0.1, 0.6+$extra, 0.15, 0.4), array('Icons128x128_1', 'LoadTrack', 0.02));
					else
						$player->msgs[0] = array(1, $head, array(1.10+$extra, 0.1, 0.6+$extra, 0.4), array('Icons128x128_1', 'LoadTrack', 0.02));
					foreach ($jukebox as $item) {
						$trackname = $item['Name'];
						if (!$aseco->settings['lists_colortracks'])
							$trackname = stripColors($trackname);
						// add clickable button if admin with 'dropjukebox' ability or track by this player
						if ($aseco->settings['clickable_lists'] && $tid <= 100 &&
						    ($dropall || $item['Login'] == $login))
							$trackname = array('{#black}' . $trackname, -2000-$tid);  // action id
						else
							$trackname = '{#black}' . $trackname;
						if ($aseco->server->packmask != 'Stadium')
							$page[] = array(str_pad($tid, 2, '0', STR_PAD_LEFT) . '.',
							                $trackname, $item['Env'],
							                '{#black}' . stripColors($item['Nick']));
						else
							$page[] = array(str_pad($tid, 2, '0', STR_PAD_LEFT) . '.',
							                $trackname,
							                '{#black}' . stripColors($item['Nick']));
						$tid++;
						if (++$lines > 14) {
							if ($aseco->allowAbility($player, 'clearjukebox')) {
								$page[] = array();
								if ($aseco->server->packmask != 'Stadium')
									$page[] = array('', array('{#emotic}                  Clear Entire Jukebox', 20), '', '');  // action id
								else
									$page[] = array('', array('{#emotic}                  Clear Entire Jukebox', 20), '');  // action id
							}
							$player->msgs[] = $page;
							$lines = 0;
							$page = array();
							if ($aseco->server->packmask != 'Stadium')
								if ($aseco->settings['clickable_lists'])
									$page[] = array('Id', 'Name (click to drop)', 'Env', 'Requester');
								else
									$page[] = array('Id', 'Name', 'Env', 'Requester');
							else
								if ($aseco->settings['clickable_lists'])
									$page[] = array('Id', 'Name (click to drop)', 'Requester');
								else
									$page[] = array('Id', 'Name', 'Requester');
						}
					}
					// add if last batch exists
					if (count($page) > 1) {
						if ($aseco->allowAbility($player, 'clearjukebox')) {
							$page[] = array();
							if ($aseco->server->packmask != 'Stadium')
								$page[] = array('', array('{#emotic}                  Clear Entire Jukebox', 20), '', '');  // action id
							else
								$page[] = array('', array('{#emotic}                  Clear Entire Jukebox', 20), '');  // action id
						}
						$player->msgs[] = $page;
					}
					// display ManiaLink message
					display_manialink_multi($player);
				}
			} else {
				$message = $rasp->messages['JUKEBOX_EMPTY'][0];
				$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
			}
		}
		elseif ($param == 'drop') {
			// find track by current player
			$uid = '';
			foreach ($jukebox as $item) {
				if ($item['Login'] == $login) {
					$name = $item['Name'];
					$uid = $item['uid'];
					break;
				}
			}
			if ($uid) {
				// drop it from the jukebox
				$drop = $jukebox[$uid];
				unset($jukebox[$uid]);

				$message = formatText($rasp->messages['JUKEBOX_DROP'][0],
				                      stripColors($player->nickname), stripColors($name));
				if ($jukebox_in_window && function_exists('send_window_message'))
					send_window_message($aseco, $message, false);
				else
					$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($message));

				// throw 'jukebox changed' event
				$aseco->releaseEvent('onJukeboxChanged', array('drop', $drop));
			} else {
				$message = $rasp->messages['JUKEBOX_NODROP'][0];
				$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
			}
		}
		elseif ($param == 'help') {
			if ($aseco->server->getGame() == 'TMN') {
				$help = '{#black}/jukebox$g will add a track to the jukebox' . LF;
				$help .= '  - {#black}help$g, displays this help information' . LF;
				$help .= '  - {#black}list$g, shows upcoming tracks' . LF;
				$help .= '  - {#black}display$g, displays upcoming tracks' . LF . '     and requesters' . LF;
				$help .= '  - {#black}drop$g, drops your currently added track' . LF . '     so you can jukebox another' . LF;
				$help .= '  - {#black}##$g, adds a track where ## is the track Id' . LF . '     from your most recent {#black}/list$g command';
				// display popup message
				$aseco->client->query('SendDisplayServerMessageToLogin', $login, $aseco->formatColors($help), 'OK', '', 0);

			} elseif ($aseco->server->getGame() == 'TMF') {
				$header = '{#black}/jukebox$g will add a track to the jukebox:';
				$help = array();
				$help[] = array('...', '{#black}help',
				                'Displays this help information');
				$help[] = array('...', '{#black}list',
				                'Shows upcoming tracks');
				$help[] = array('...', '{#black}display',
				                'Displays upcoming tracks and requesters');
				$help[] = array('...', '{#black}drop',
				                'Drops your currently added track');
				$help[] = array('...', '{#black}##',
				                'Adds a track where ## is the track Id');
				$help[] = array('', '',
				                'from your most recent {#black}/list$g command');
				// display ManiaLink message
				display_manialink($login, $header, array('Icons64x64_1', 'TrackInfo', -0.01), $help, array(0.9, 0.05, 0.15, 0.7), 'OK');
			}
		} else {
			$message = $rasp->messages['JUKEBOX_HELP'][0];
			$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
		}
	} else {
		$message = $rasp->messages['NO_JUKEBOX'][0];
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
	}
}  // chat_jukebox

function chat_autojuke($aseco, $command) {
	global $feature_karma, $buffersize, $jukebox, $jb_buffer;  // from rasp.settings.php

	$player = $command['author'];
	$login = $player->login;

	// check for relay server
	if ($aseco->server->isrelay) {
		$message = formatText($aseco->getChatMessage('NOTONRELAY'));
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
		return;
	}

	// split params into array
	$command['params'] = explode(' ', preg_replace('/ +/', ' ', $command['params']));
	$cmdcount = count($command['params']);

	if ($cmdcount == 1 && $command['params'][0] == 'help') {
		if ($aseco->server->getGame() == 'TMN') {
			$help = '{#black}/autojuke$g will jukebox a track from /list selection:' . LF;
			$help .= '  - {#black}help$g, displays this help information' . LF;
			$help .= '  - {#black}nofinish$g, tracks you haven\'t completed' . LF;
			$help .= '  - {#black}norank$g, tracks you don\'t have a rank on' . LF;
			$help .= '  - {#black}nogold$g, tracks you didn\'t beat gold time on' . LF;
			$help .= '  - {#black}noauthor$g, tracks you didn\'t beat author time on' . LF;
			$help .= '  - {#black}norecent$g, tracks you didn\'t play recently' . LF;
			$help .= '  - {#black}longest$g/{#black}shortest$g, the longest/shortest tracks' . LF;
			$help .= '  - {#black}newest$g/{#black}oldest$g, the newest/oldest tracks' . LF;
		if ($feature_karma) {
			$help .= '  - {#black}novote$g, tracks you didn\'t karma vote for' . LF;
		}
			$help .= LF . 'The jukeboxed track is the first one from the' . LF
			            . 'chosen selection that is not in the track history.';
			// display popup message
			$aseco->client->query('SendDisplayServerMessageToLogin', $login, $aseco->formatColors($help), 'OK', '', 0);
		} elseif ($aseco->server->getGame() == 'TMF') {
			$header = '{#black}/autojuke$g will jukebox a track from /list selection:';
			$help = array();
			$help[] = array('...', '{#black}help',
			                'Displays this help information');
			$help[] = array('...', '{#black}nofinish',
			                'Selects tracks you haven\'t completed');
			$help[] = array('...', '{#black}norank',
			                'Selects tracks you don\'t have a rank on');
			$help[] = array('...', '{#black}nogold',
			                'Selects tracks you didn\'t beat gold ' .
			                 ($aseco->server->gameinfo->mode == Gameinfo::STNT ? 'score on' : 'time on'));
			$help[] = array('...', '{#black}noauthor',
			                'Selects tracks you didn\'t beat author '.
			                 ($aseco->server->gameinfo->mode == Gameinfo::STNT ? 'score on' : 'time on'));
			$help[] = array('...', '{#black}norecent',
			                'Selects tracks you didn\'t play recently');
		if ($aseco->server->gameinfo->mode != Gameinfo::STNT) {
			$help[] = array('...', '{#black}longest$g/{#black}shortest',
			                'Selects the longest/shortest tracks');
		}
			$help[] = array('...', '{#black}newest$g/{#black}oldest',
			                'Selects the newest/oldest tracks');
		if ($feature_karma) {
			$help[] = array('...', '{#black}novote',
			                'Selects tracks you didn\'t karma vote for');
		}
			$help[] = array();
			$help[] = array('The jukeboxed track is the first one from the chosen selection');
			$help[] = array('that is not in the track history.');
			// display ManiaLink message
			display_manialink($login, $header, array('Icons64x64_1', 'TrackInfo', -0.01), $help, array(1.1, 0.05, 0.3, 0.75), 'OK');
		}
		return;
	}
	elseif ($cmdcount == 1 && $command['params'][0] == 'nofinish') {
		getChallengesNoFinish($player);
	}
	elseif ($cmdcount == 1 && $command['params'][0] == 'norank') {
		getChallengesNoRank($player);
	}
	elseif ($cmdcount == 1 && $command['params'][0] == 'nogold') {
		getChallengesNoGold($player);
	}
	elseif ($cmdcount == 1 && $command['params'][0] == 'noauthor') {
		getChallengesNoAuthor($player);
	}
	elseif ($cmdcount == 1 && $command['params'][0] == 'norecent') {
		getChallengesNoRecent($player);
	}
	elseif ($cmdcount == 1 && $command['params'][0] == 'longest') {
		getChallengesByLength($player, false);
	}
	elseif ($cmdcount == 1 && $command['params'][0] == 'shortest') {
		getChallengesByLength($player, true);
	}
	elseif ($cmdcount == 1 && $command['params'][0] == 'newest') {
		getChallengesByAdd($player, true, $buffersize+1);
	}
	elseif ($cmdcount == 1 && $command['params'][0] == 'oldest') {
		getChallengesByAdd($player, false, $buffersize+1);
	}
	elseif ($cmdcount == 1 && $command['params'][0] == 'novote' && $feature_karma) {
		getChallengesNoVote($player);
	}
	else {
		$message = '{#server}> {#error}Invalid selection, try again!';
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
		return;
	}

	if (empty($player->tracklist)) {
		$message = '{#server}> {#error}No tracks found, try again!';
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
		return;
	}
	// find first available track
	$ctr = 1;
	$found = false;
	foreach ($player->tracklist as $key) {
		if (!array_key_exists($key['uid'], $jukebox) && !in_array($key['uid'], $jb_buffer)) {
			$found = true;
			break;
		}
		$ctr++;
	}
	if ($found) {
		// jukebox it
		$command['params'] = $ctr;
		chat_jukebox($aseco, $command);
	} else {
		$message = '{#server}> {#highlite}' . $command['params'][0] . '{#error} tracks currently unavailable, try again later!';
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
	}
}  // chat_autojuke

function chat_add($aseco, $command) {
	global $rasp, $feature_jukebox, $feature_tmxadd, $jukebox_in_window, $jukebox,
	       $tmxadd, $tmxtmpdir, $chatvote, $plrvotes, $allow_spec_startvote,
	       $r_expire_num, $ta_show_num, $ta_expire_start, $auto_vote_starter;

	$player = $command['author'];
	$login = $player->login;

	// check for relay server
	if ($aseco->server->isrelay) {
		$message = formatText($aseco->getChatMessage('NOTONRELAY'));
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $player->login);
		return;
	}

	$sections = array('TMO' => 'original',
	                  'TMS' => 'sunrise',
	                  'TMN' => 'nations',
	                  'TMU' => 'united',
	                  'TMNF' => 'tmnforever');

	// check whether jukebox & /add are enabled
	if ($feature_jukebox && $feature_tmxadd) {
		// check whether this player is spectator
		if (!$allow_spec_startvote && $aseco->isSpectator($player)) {
			$message = $rasp->messages['NO_SPECTATORS'][0];
			$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
			return;
		}

		// check for ongoing TMX or chat vote
		if (!empty($tmxadd) || !empty($chatvote)) {
			$message = $rasp->messages['VOTE_ALREADY'][0];
			$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
			return;
		}
		// check for special 'trackref' parameter & write file
		if ($command['params'] == 'trackref' && $aseco->allowAbility($player, 'chat_add_tref')) {
			build_tmx_trackref($aseco);
			$message = '{#server}> {#emotic}Wrote trackref.txt files';
			$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
			return;
		}

		// split params into array
		$command['params'] = explode(' ', preg_replace('/ +/', ' ', $command['params']));
		// check for valid TMX ID
		if (is_numeric($command['params'][0]) && $command['params'][0] >= 0) {
			$trkid = ltrim($command['params'][0], '0');
			$source = 'TMX';
			$section = $aseco->server->getGame();
			if ($section == 'TMF' && isset($command['params'][1])) {
				$section = strtoupper($command['params'][1]);
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
			// try to load the track from TMX
			$remotefile = 'http://' . $sections[$section] . '.tm-exchange.com/get.aspx?action=trackgbx&id=' . $trkid;

			$file = http_get_file($remotefile);
			if ($file === false || $file == -1) {
				$message = '{#server}> {#error}Error downloading, or wrong TMX section, or TMX is down!';
				$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
			} else {
				// check for maximum online track size (256 KB)
				if (strlen($file) >= 256 * 1024) {
					$message = formatText($rasp->messages['TRACK_TOO_LARGE'][0],
					                      round(strlen($file) / 1024));
					$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
					return;
				}
				$sepchar = substr($aseco->server->trackdir, -1, 1);
				$partialdir = $tmxtmpdir . $sepchar . $trkid . '.Challenge.gbx';
				$localfile = $aseco->server->trackdir . $partialdir;
				if ($aseco->debug) {
					$aseco->console_text('/add - tmxtmpdir=' . $tmxtmpdir);
					$aseco->console_text('/add - path + filename=' . $partialdir);
					$aseco->console_text('/add - aseco->server->trackdir = ' . $aseco->server->trackdir);
				}
				if ($nocasepath = file_exists_nocase($localfile)) {
					if (!unlink($nocasepath)) {
						$message = '{#server}> {#error}Error erasing old file. Please contact admin.';
						$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
						return;
					}
				}
				if (!$lfile = @fopen($localfile, 'wb')) {
					$message = '{#server}> {#error}Error creating file. Please contact admin.';
					$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
					return;
				}
				if (!fwrite($lfile, $file)) {
					$message = '{#server}> {#error}Error saving file - unable to write data. Please contact admin.';
					$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
					fclose($lfile);
					return;
				}
				fclose($lfile);
				$newtrk = getChallengeData($localfile, true);  // 2nd parm is whether or not to get players & votes required
				if ($newtrk['votes'] == 500 && $newtrk['name'] == 'Not a GBX file') {
					$message = '{#server}> {#error}No such track on ' . $source;
					if ($source == 'TMX' && $aseco->server->getGame() == 'TMF')
						$message .= ' section ' . $section;
					$message .= '!';
					$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
					unlink($localfile);
					return;
				}
				// dummy player to easily obtain entire track list
				$list = new Player();
				getAllChallenges($list, '*', '*');
				// check for track presence on server
				$ctr = 1;
				foreach ($list->tracklist as $key) {
					if ($key['uid'] == $newtrk['uid']) {
						$message = $rasp->messages['ADD_PRESENTJB'][0];
						$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
						unlink($localfile);
						// jukebox already available track
						$player->tracklist = $list->tracklist;
						$command['params'] = $ctr;
						chat_jukebox($aseco, $command);
						unset($list);
						return;
					}
					$ctr++;
				}
				unset($list);
				// check for track presence in jukebox via previous /add
				if (isset($jukebox[$newtrk['uid']])) {
					$message = $rasp->messages['ADD_DUPL'][0];
					$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
					unlink($localfile);
					return;
				}
				// rename ID filename to track's name
				$md5new = md5_file($localfile);
				$filename = trim(utf8_decode(stripColors($newtrk['name'])));
				$filename = preg_replace('/[^A-Za-z0-9 \'#=+~_,.-]/', '_', $filename);
				$filename = preg_replace('/ +/', ' ', preg_replace('/_+/', '_', $filename));
				$partialdir = $tmxtmpdir . $sepchar . $filename . '_' . $trkid . '.Challenge.gbx';
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
						$partialdir = $tmxtmpdir . $sepchar . $filename . '_' . $trkid . '-' . $i++ . '.Challenge.gbx';
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
					// start /add vote
					$tmxadd['filename'] = $partialdir;
					$tmxadd['votes'] = $newtrk['votes'];
					$tmxadd['name'] = $newtrk['name'];
					$tmxadd['environment'] = $newtrk['environment'];
					$tmxadd['login'] = $player->login;
					$tmxadd['nick'] = $player->nickname;
					$tmxadd['source'] = $source;
					$tmxadd['uid'] = $newtrk['uid'];

					// reset votes, rounds counter, TA interval counter & start time
					$plrvotes = array();
					$r_expire_num = 0;
					$ta_show_num = 0;
					$ta_expire_start = time_playing($aseco);  // from plugin.track.php
					// compile & show chat message
					$message = formatText($rasp->messages['JUKEBOX_ADD'][0],
					                      stripColors($tmxadd['nick']),
					                      stripColors($tmxadd['name']),
					                      $tmxadd['source'], $tmxadd['votes']);
					$message = str_replace('{br}', LF, $message);  // split long message
					if ($jukebox_in_window && function_exists('send_window_message'))
						send_window_message($aseco, $message, true);
						else
						$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($message));

					// enable all vote panels
					if ($aseco->server->getGame() == 'TMF' && function_exists('allvotepanels_on'))
						allvotepanels_on($aseco, $login, $aseco->formatColors('{#emotic}'));
					// vote automatically by vote starter?
					if ($auto_vote_starter) chat_y($aseco, $command);
				}
			}
		} else {
			$message = '{#server}> {#error}You must include a TMX Track_ID!';
			$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
		}
	} else {
		$message = $rasp->messages['NO_ADD'][0];
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
	}
}  // chat_add

function build_tmx_trackref($aseco) {
	global $tmxdir, $tmxtmpdir;

	$td = $aseco->server->trackdir . $tmxdir;
	if (is_dir($td)) {
		$dir = opendir($td);
		$fp = fopen($td . '/trackref.txt', 'w');
		while (($file = readdir($dir)) !== false) {
			if (strtolower(substr($file, -4)) == '.gbx') {
				$ci = getChallengeData($td . '/' . $file, false);
				$file = str_ireplace('.challenge.gbx', '', $file);
				fwrite($fp, $file . "\t" . $ci['environment'] . "\t" . $ci['author'] . "\t" . stripColors($ci['name']) . "\t" . $ci['coppers'] . CRLF);
			}
		}
		fclose($fp);
		closedir($dir);
	}

	$td = $aseco->server->trackdir . $tmxtmpdir;
	if (is_dir($td)) {
		$dir = opendir($td);
		$fp = fopen($td . '/trackref.txt', 'w');
		while (($file = readdir($dir)) !== false) {
			if (strtolower(substr($file, -4)) == '.gbx') {
				$ci = getChallengeData($td . '/' . $file, false);
				$file = str_ireplace('.challenge.gbx', '', $file);
				fwrite($fp, $file . "\t" . $ci['environment'] . "\t" . $ci['author'] . "\t" . stripColors($ci['name']) . "\t" . $ci['coppers'] . CRLF);
			}
		}
		fclose($fp);
		closedir($dir);
	}

}  // build_tmx_trackref

function chat_y($aseco, $command) {
	global $rasp, $tmxadd, $plrvotes, $chatvote, $jukebox, $allow_spec_voting,
	       $jukebox_in_window, $vote_in_window, $feature_tmxadd, $feature_votes,
	       $ladder_fast_restart;

	$player = $command['author'];
	$login = $player->login;

	// check for relay server
	if ($aseco->server->isrelay) {
		$message = formatText($aseco->getChatMessage('NOTONRELAY'));
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $player->login);
		return;
	}

	// check whether this player is spectator but not any admin
	if (!$allow_spec_voting && $aseco->isSpectator($player) && !$aseco->isAnyAdmin($player)) {
		$message = $rasp->messages['NO_SPECTATORS'][0];
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
		return;
	}

	// check whether this player already voted
	if (in_array($login, $plrvotes)) {
		$message = '{#server}> {#error}You have already voted!';
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
		return;
	}

	// check for ongoing TMX vote
	if (!empty($tmxadd) && $tmxadd['votes'] >= 0) {
		$votereq = $tmxadd['votes'];
		$votereq--;
		// check for sufficient votes
		if ($votereq > 0) {
			// remind all players to vote
			$tmxadd['votes'] = $votereq;
			$message = formatText($rasp->messages['JUKEBOX_Y'][0],
			                      $votereq, ($votereq == 1 ? '' : 's'),
			                      stripColors($tmxadd['name']));
			if ($jukebox_in_window && function_exists('send_window_message'))
				send_window_message($aseco, $message, false);
			else
				$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($message));
			// register this player's vote
			$plrvotes[] = $login;
			// disable panel in case /y was used to vote
			if ($aseco->server->getGame() == 'TMF')
				votepanel_off($aseco, $login);
		} else {
			// pass, so add it to jukebox
			$uid = $tmxadd['uid'];
			$jukebox[$uid]['FileName'] = $tmxadd['filename'];
			$jukebox[$uid]['Name'] = $tmxadd['name'];
			$jukebox[$uid]['Env'] = $tmxadd['environment'];
			$jukebox[$uid]['Login'] = $tmxadd['login'];
			$jukebox[$uid]['Nick'] = $tmxadd['nick'];
			$jukebox[$uid]['source'] = $tmxadd['source'];
			$jukebox[$uid]['tmx'] = true;
			$jukebox[$uid]['uid'] = $uid;

			// show chat message
			$message = formatText($rasp->messages['JUKEBOX_PASS'][0],
			                      stripColors($tmxadd['name']));
			if ($jukebox_in_window && function_exists('send_window_message'))
				send_window_message($aseco, $message, false);
			else
				$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($message));
			// clear for next vote
			$tmxadd = array();
			// disable all vote panels
			if ($aseco->server->getGame() == 'TMF')
				allvotepanels_off($aseco);

			// throw 'jukebox changed' event
			$aseco->releaseEvent('onJukeboxChanged', array('add', $jukebox[$uid]));
		}
	}
	// check for ongoing chat vote
	elseif (!empty($chatvote) && $chatvote['votes'] >= 0) {
		$votereq = $chatvote['votes'];
		$votereq--;
		// check for sufficient votes
		if ($votereq > 0) {
			// remind players to vote
			$chatvote['votes'] = $votereq;
			$message = formatText($rasp->messages['VOTE_Y'][0],
			                      $votereq, ($votereq == 1 ? '' : 's'),
			                      $chatvote['desc']);
			if ($vote_in_window && function_exists('send_window_message'))
				send_window_message($aseco, $message, false);
			else
				$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($message));
			// register this player's vote
			$plrvotes[] = $login;
			// disable panel in case /y was used to vote
			if ($aseco->server->getGame() == 'TMF')
				votepanel_off($aseco, $login);
		} else {
			// show chat message
			$message = formatText($rasp->messages['VOTE_PASS'][0],
			                      $chatvote['desc']);
			if ($vote_in_window && function_exists('send_window_message'))
				send_window_message($aseco, $message, false);
			else
				$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($message));

			// pass, so perform action
			switch ($chatvote['type']) {
			case 0:  // endround
				$aseco->client->query('ForceEndRound');
				$aseco->console('Vote by {1} forced round end!',
				                $chatvote['login']);
				break;
			case 1:  // ladder
				if ($ladder_fast_restart) {
					global $atl_restart;  // from plugin.autotime.php

					// perform quick restart
					if (isset($atl_restart)) $atl_restart = true;
					if ($aseco->server->gameinfo->mode == Gameinfo::CUP)
						// don't clear scores if in TMF Cup mode
						$aseco->client->query('ChallengeRestart', true);
					else
						$aseco->client->query('ChallengeRestart');
				} else {
					// prepend current track to start of jukebox
					$uid = $aseco->server->challenge->uid;
					$jukebox = array_reverse($jukebox, true);
					$jukebox[$uid]['FileName'] = $aseco->server->challenge->filename;
					$jukebox[$uid]['Name'] = $aseco->server->challenge->name;
					$jukebox[$uid]['Env'] = $aseco->server->challenge->environment;
					$jukebox[$uid]['Login'] = $chatvote['login'];
					$jukebox[$uid]['Nick'] = $chatvote['nick'];
					$jukebox[$uid]['source'] = 'Ladder';
					$jukebox[$uid]['tmx'] = false;
					$jukebox[$uid]['uid'] = $uid;
					$jukebox = array_reverse($jukebox, true);

					if ($aseco->debug) {
						$aseco->console_text('/ladder pass - $jukebox:' . CRLF .
						                     print_r($jukebox, true));
					}

					// throw 'jukebox changed' event
					$aseco->releaseEvent('onJukeboxChanged', array('restart', $jukebox[$uid]));

					// ...and skip to it
					if ($aseco->server->gameinfo->mode == Gameinfo::CUP)
						// don't clear scores if in TMF Cup mode
						$aseco->client->query('NextChallenge', true);
					else
						$aseco->client->query('NextChallenge');
				}
				$aseco->console('Vote by {1} restarted track for ladder!',
				                $chatvote['login']);
				break;
			case 2:  // replay
				// prepend current track to start of jukebox
				$uid = $aseco->server->challenge->uid;
				$jukebox = array_reverse($jukebox, true);
				$jukebox[$uid]['FileName'] = $aseco->server->challenge->filename;
				$jukebox[$uid]['Name'] = $aseco->server->challenge->name;
				$jukebox[$uid]['Env'] = $aseco->server->challenge->environment;
				$jukebox[$uid]['Login'] = $chatvote['login'];
				$jukebox[$uid]['Nick'] = $chatvote['nick'];
				$jukebox[$uid]['source'] = 'Replay';
				$jukebox[$uid]['tmx'] = false;
				$jukebox[$uid]['uid'] = $uid;
				$jukebox = array_reverse($jukebox, true);

				if ($aseco->debug) {
					$aseco->console_text('/replay pass - $jukebox:' . CRLF .
					                     print_r($jukebox, true));
				}

				$aseco->console('Vote by {1} replays track after finish!',
				                $chatvote['login']);

				// throw 'jukebox changed' event
				$aseco->releaseEvent('onJukeboxChanged', array('replay', $jukebox[$uid]));
				break;
			case 3:  // skip
				// skip immediately to next track
				if ($aseco->server->gameinfo->mode == Gameinfo::CUP)
					// don't clear scores if in TMF Cup mode
					$aseco->client->query('NextChallenge', true);
				else
					$aseco->client->query('NextChallenge');
				$aseco->console('Vote by {1} skips this track!',
				                $chatvote['login']);
				break;
			case 4:  // kick
				$rtn = $aseco->client->query('Kick', $chatvote['target']);
				if (!$rtn) {
					trigger_error('[' . $aseco->client->getErrorCode() . '] Kick - ' . $aseco->client->getErrorMessage(), E_USER_WARNING);
				} else {
					$aseco->console('Vote by {1} kicked player {2}!',
					                $chatvote['login'], $chatvote['target']);
				}
				break;
			case 6:  // ignore
				$rtn = $aseco->client->query('Ignore', $chatvote['target']);
				if (!$rtn) {
					trigger_error('[' . $aseco->client->getErrorCode() . '] Ignore - ' . $aseco->client->getErrorMessage(), E_USER_WARNING);
				} else {
					// check if in global mute/ignore list
					if (!in_array($chatvote['target'], $aseco->server->mutelist)) {
						// add player to list
						$aseco->server->mutelist[] = $chatvote['target'];
					}
					$aseco->console('Vote by {1} ignored player {2}!',
					                $chatvote['login'], $chatvote['target']);
				}
				break;
			case 5:  // add - can't occur here
				break;
			}

			// clear for next vote
			$chatvote = array();
			// disable all vote panels
			if ($aseco->server->getGame() == 'TMF')
				allvotepanels_off($aseco);
		}
	// all quiet on the voting front :)
	} else {
		$message = '{#server}> {#error}There is no vote right now!';
		if ($feature_tmxadd) {
			if ($feature_votes) {
				$message .= ' Use {#highlite}$i/add <ID>{#error} or see {#highlite}$i/helpvote{#error} to start one.';
			} else {
				$message .= ' Use {#highlite}$i/add <ID>{#error} to start one.';
			}
		} else {
			if ($feature_votes) {
				$message .= ' See {#highlite}$i/helpvote{#error} to start one.';
			} else {
				$message .= '';
			}
		}
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
	}
}  // chat_y

// called @ onSync
function init_jbhistory($aseco, $data) {
	global $buffersize, $jb_buffer;

	// read track history from file in case of XASECO restart
	$jb_buffer = array();
	if ($fp = @fopen($aseco->server->trackdir . $aseco->settings['trackhist_file'], 'rb')) {
		while (!feof($fp)) {
			$uid = rtrim(fgets($fp));
			if ($uid != '') $jb_buffer[] = $uid;
		}
		fclose($fp);
		// keep only most recent $buffersize entries
		$jb_buffer = array_slice($jb_buffer, -$buffersize);
		// drop current (last) track as rasp_newtrack() will add it back
		array_pop($jb_buffer);
	}
}  // init_jbhistory

function chat_history($aseco, $command) {
	global $rasp, $jb_buffer;

	$player = $command['author'];

	// check for relay server
	if ($aseco->server->isrelay) {
		$message = formatText($aseco->getChatMessage('NOTONRELAY'));
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $player->login);
		return;
	}

	if (!empty($jb_buffer)) {
		$message = $rasp->messages['HISTORY'][0];
		// loop over last 10 (max) entries in buffer
		for ($i = 1, $j = count($jb_buffer)-1; $i <= 10 && $j >= 0; $i++, $j--) {
			// get track name from UID
			$query = 'SELECT Name FROM challenges
			          WHERE Uid=' . quotedString($jb_buffer[$j]);
			$res = mysql_query($query);
			$row = mysql_fetch_object($res);
			mysql_free_result($res);

			$message .= '{#highlite}' . $i . '{#emotic}.[{#highlite}' . stripColors($row->Name) . '{#emotic}], ';
		}

		$message = substr($message, 0, strlen($message)-2);  // strip trailing ", "
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $player->login);
		return;
	} else {
		$message = '{#server}> {#error}No track history available!';
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $player->login);
		return;
	}
}  // chat_history

function chat_xlist($aseco, $command) {

	$player = $command['author'];
	$login = $player->login;

	// split params into array
	$command['params'] = explode(' ', preg_replace('/ +/', ' ', $command['params']));
	$cmdcount = count($command['params']);

	$section = $aseco->server->getGame();
	if ($section == 'TMF') {
		if ($aseco->server->packmask == 'Stadium')
			$section = 'TMNF';
		else
			$section = 'TMU';
	}

	if ($cmdcount == 1 && $command['params'][0] == 'help') {
		if ($aseco->server->getGame() == 'TMN') {
			$help = '{#black}/xlist$g will show tracks on TMX:' . LF;
			$help .= '  - {#black}help$g, displays this help information' . LF;
			$help .= '  - {#black}recent$g, the 10 most recent tracks' . LF;
			$help .= '  - {#black}xxx$g, tracks matching (partial) name' . LF;
			$help .= '  - {#black}auth:yyy$g, tracks matching (partial) author' . LF;
			$help .= '  - {#black}env:zzz$g, where zzz is an environment from:' . LF;
			$help .= '     stadium,bay,coast,island,snow,desert,rally' . LF;
			$help .= '  - {#black}xxx auth:yyy env:zzz$g, combines any searches' . LF;
			$help .= '  - {#black}tmx$g, selects a TMX section from:' . LF;
			$help .= '     TMO,TMS,TMN,TMNF$n $m,TMU' . LF;
			$help .= LF . 'Pick a TMX Id number from the list, and use {#black}/add #$g';
			// display popup message
			$aseco->client->query('SendDisplayServerMessageToLogin', $login, $aseco->formatColors($help), 'OK', '', 0);
		} elseif ($aseco->server->getGame() == 'TMF') {
			$header = '{#black}/xlist$g will show tracks on TMX:';
			$help = array();
			$help[] = array('...', '{#black}help',
			                'Displays this help information');
			$help[] = array('...', '{#black}recent',
			                'Lists the 10 most recent tracks');
			$help[] = array('...', '{#black}xxx',
			                'Lists tracks matching (partial) name');
			$help[] = array('...', '{#black}auth:yyy',
			                'Lists tracks matching (partial) author');
			$help[] = array('...', '{#black}env:zzz',
			                'Where zzz is an environment from: stadium,');
			$help[] = array('', '',
			                'bay,coast,island,snow/alpine,desert/speed,rally');
			$help[] = array('...', '{#black}xxx auth:yyy env:zzz',
			                'Combines the name, author and/or env searches');
			$help[] = array('...', '{#black}tmx',
			                'Selects a TMX section from:');
			$help[] = array('', '',
			                'TMO,TMS,TMN,TMNF,TMU');
			$help[] = array();
			$help[] = array('Pick a TMX Id number from the list, and use {#black}/add # {sec}');
			// display ManiaLink message
			display_manialink($login, $header, array('Icons64x64_1', 'TrackInfo', -0.01), $help, array(1.2, 0.05, 0.35, 0.8), 'OK');
		}
		return;
	}
	elseif ($command['params'][0] == 'recent') {
		// check for optional <tmx> parameter
		if (isset($command['params'][1]) &&
		    strtolower(substr($command['params'][1], 0, 2)) == 'tm')
			$section = strtoupper($command['params'][1]);

		// get 10 most recent tracks
		$tracks = new TMXInfoSearcher($section, '', '', '', true);
	} else {
		$name = '';
		$auth = '';
		$env = '';
		// collect search parameters
		foreach ($command['params'] as $param) {
			if (strtolower(substr($param, 0, 5)) == 'auth:') {
				$auth = substr($param, 5);
			} elseif (strtolower(substr($param, 0, 4)) == 'env:') {
				$env = substr($param, 4);
				// map internal to external envs
				if (strtolower($env) == 'speed')
					$env = 'desert';
				elseif (strtolower($env) == 'alpine')
					$env = 'snow';
			} elseif (strtolower(substr($param, 0, 2)) == 'tm' &&
			          // only if last parameter
			          $param == end($command['params'])) {
				$section = strtoupper($param);
			} else {
				if ($name == '')
					$name = $param;
				else  // concatenate words in name
					$name .= '%20' . $param;
			}
		}

		// search for matching tracks
		$tracks = new TMXInfoSearcher($section, $name, $auth, $env, false);
	}

	// check for any results
	if (!$tracks->valid()) {
		$message = '{#server}> {#error}No tracks found, or TMX is down!';
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
		return;
	}
	$player->tracklist = array();

	if ($aseco->server->getGame() == 'TMN') {
		$head = 'Tracks On TMX Section {#black}' . $section . '$g:' . LF . 'Id        TMX          Name                  Author' . LF;
		$msg = '';
		$tid = 1;
		$lines = 0;
		$player->msgs = array();
		$player->msgs[0] = 1;

		// list all found tracks
		foreach ($tracks as $row) {
			$msg .= '$z' . str_pad($tid, 3, '0', STR_PAD_LEFT) . '.   {#black}' .
			        str_pad($row->id, 7) . '  ' . str_pad($row->name, 20) . '$z  ' .
			        $row->author . LF;
			$tid++;
			if (++$lines > 9) {
				$player->msgs[] = $aseco->formatColors($head . $msg);
				$lines = 0;
				$msg = '';
			}
		}
		// add if last batch exists
		if ($msg != '')
			$player->msgs[] = $aseco->formatColors($head . $msg);

		// display popup message
		if (count($player->msgs) == 2) {
			$aseco->client->query('SendDisplayServerMessageToLogin', $login, $player->msgs[1], 'OK', '', 0);
		} else {  // > 2
			$aseco->client->query('SendDisplayServerMessageToLogin', $login, $player->msgs[1], 'Close', 'Next', 0);
		}

	} elseif ($aseco->server->getGame() == 'TMF') {
		$adminadd = $aseco->allowAbility($player, 'add');
		$head = 'Tracks On TMX Section {#black}' . $section . '$g:';
		$msg = array();
		if ($aseco->settings['clickable_lists'])
			if ($adminadd)
				$msg[] = array('Id', 'TMX', 'Name (click to /add)', '$nAdmin', 'Author', 'Env');
			else
				$msg[] = array('Id', 'TMX', 'Name (click to /add)', 'Author', 'Env');
		else
			$msg[] = array('Id', 'TMX', 'Name', 'Author', 'Env');

		$tid = 1;
		$lines = 0;
		$player->msgs = array();
		if ($adminadd && $aseco->settings['clickable_lists'])
			$player->msgs[0] = array(1, $head, array(1.55, 0.12, 0.16, 0.6, 0.1, 0.4, 0.17), array('Icons128x128_1', 'LoadTrack', 0.02));
		else
			$player->msgs[0] = array(1, $head, array(1.45, 0.12, 0.16, 0.6, 0.4, 0.17), array('Icons128x128_1', 'LoadTrack', 0.02));

		// list all found tracks
		foreach ($tracks as $row) {
			$tmxid = '{#black}' . $row->id;
			$name = '{#black}' . $row->name;
			$author = $row->author;
			// add clickable buttons
			if ($aseco->settings['clickable_lists'] && $tid <= 500) {
				$tmxid = array($tmxid, $tid+5200);  // action ids
				$name = array($name, $tid+5700);
				$author = array($author, $tid+6700);

				// store track in player object for action buttons
				$trkarr = array();
				$trkarr['id'] = $row->id;
				$trkarr['section'] = $row->section;
				$trkarr['author'] = $row->author;
				$player->tracklist[] = $trkarr;
			}

			if ($adminadd)
				if ($aseco->settings['clickable_lists'] && $tid <= 500)
					$msg[] = array(str_pad($tid, 3, '0', STR_PAD_LEFT) . '.',
					               $tmxid, $name, array('Add', $tid+6200), $author, $row->envir);
				else
					$msg[] = array(str_pad($tid, 3, '0', STR_PAD_LEFT) . '.',
					               $tmxid, $name, 'Add', $author, $row->envir);
			else
				$msg[] = array(str_pad($tid, 3, '0', STR_PAD_LEFT) . '.',
				               $tmxid, $name, $author, $row->envir);
			$tid++;
			if (++$lines > 14) {
				$player->msgs[] = $msg;
				$lines = 0;
				$msg = array();
				if ($aseco->settings['clickable_lists'])
					if ($adminadd)
						$msg[] = array('Id', 'TMX', 'Name (click to /add)', '$nAdmin', 'Author', 'Env');
					else
						$msg[] = array('Id', 'TMX', 'Name (click to /add)', 'Author', 'Env');
				else
					$msg[] = array('Id', 'TMX', 'Name', 'Author', 'Env');
			}
		}
		// add if last batch exists
		if (count($msg) > 1)
			$player->msgs[] = $msg;

		// display ManiaLink message
		display_manialink_multi($player);
	}
}  // chat_xlist


// called @ onPlayerManialinkPageAnswer
// Handles ManiaLink jukebox responses
// [0]=PlayerUid, [1]=Login, [2]=Answer
function event_jukebox($aseco, $answer) {
	global $jukebox;

	// leave actions outside 101 - 2000, -2000 - -101, -2100 - -2001,
	// -6001 - -7900 & 5201 - 7200 to other handlers
	if ($answer[2] >= 101 && $answer[2] <= 2000) {
		// get player
		$player = $aseco->server->players->getPlayer($answer[1]);

		// log clicked command
		$aseco->console('player {1} clicked command "/jukebox {2}"',
		                $player->login, $answer[2]-100);

		// jukebox selected track
		$command = array();
		$command['author'] = $player;
		$command['params'] = $answer[2]-100;
		chat_jukebox($aseco, $command);
	}
	elseif ($answer[2] >= -7900 && $answer[2] <= -6001) {
		// get player
		$player = $aseco->server->players->getPlayer($answer[1]);

		// log clicked command
		$aseco->console('player {1} clicked command "/karma {2}"',
		                $player->login, abs($answer[2])-6000);

		// karma selected track
		$command = array();
		$command['author'] = $player;
		$command['params'] = abs($answer[2])-6000;
		chat_karma($aseco, $command);
	}
	elseif ($answer[2] >= -2000 && $answer[2] <= -101) {
		// get player
		$player = $aseco->server->players->getPlayer($answer[1]);
		$author = $player->tracklist[abs($answer[2])-101]['author'];

		// close main window because /list can take a while
		mainwindow_off($aseco, $player->login);
		// log clicked command
		$aseco->console('player {1} clicked command "/list {2}"',
		                $player->login, $author);

		// search for tracks by author
		$command = array();
		$command['author'] = $player;
		$command['params'] = $author;
		chat_list($aseco, $command);
	}
	elseif ($answer[2] >= -2100 && $answer[2] <= -2001) {
		// get player
		$player = $aseco->server->players->getPlayer($answer[1]);
		$login = $player->login;

		// determine admin ability to drop all jukeboxed tracks
		if ($aseco->allowAbility($player, 'dropjukebox')) {
			// log clicked command
			$aseco->console('player {1} clicked command "/admin dropjukebox {2}"',
			                $login, abs($answer[2])-2000);

			// drop any jukeboxed track by admin
			$command = array();
			$command['author'] = $player;
			$command['params'] = 'dropjukebox ' . (abs($answer[2])-2000);
			chat_admin($aseco, $command);

			// check whether last track was dropped
			if (empty($jukebox)) {
				// close main window
				mainwindow_off($aseco, $login);
			} else {
				// log clicked command
				$aseco->console('player {1} clicked command "/jukebox display"', $login);
				// display updated list
				$command['params'] = 'display';
				chat_jukebox($aseco, $command);
			}
		} else {
			// log clicked command
			$aseco->console('player {1} clicked command "/jukebox drop"', $login);

			// drop user's jukeboxed track
			$command = array();
			$command['author'] = $player;
			$command['params'] = 'drop';
			chat_jukebox($aseco, $command);

			// check whether last track was dropped
			if (empty($jukebox)) {
				// close main window
				mainwindow_off($aseco, $login);
			} else {
				// log clicked command
				$aseco->console('player {1} clicked command "/jukebox display"', $login);
				// display updated list
				$command['params'] = 'display';
				chat_jukebox($aseco, $command);
			}
		}
	}
	elseif ($answer[2] >= 5201 && $answer[2] <= 5700) {
		// get player & track ID
		$player = $aseco->server->players->getPlayer($answer[1]);
		$tmxid = $player->tracklist[$answer[2]-5201]['id'];
		$section = $player->tracklist[$answer[2]-5201]['section'];

		// log clicked command
		$aseco->console('player {1} clicked command "/tmxinfo {2} {3}"',
		                $player->login, $tmxid, $section);

		// /tmxinfo selected track
		$command = array();
		$command['author'] = $player;
		$command['params'] = $tmxid . ' ' . $section;
		chat_tmxinfo($aseco, $command);
	}
	elseif ($answer[2] >= 5701 && $answer[2] <= 6200) {
		// get player & track ID
		$player = $aseco->server->players->getPlayer($answer[1]);
		$tmxid = $player->tracklist[$answer[2]-5701]['id'];
		$section = $player->tracklist[$answer[2]-5701]['section'];

		// log clicked command
		$aseco->console('player {1} clicked command "/add {2} {3}"',
		                $player->login, $tmxid, $section);

		// /add selected track
		$command = array();
		$command['author'] = $player;
		$command['params'] = $tmxid . ' ' . $section;
		chat_add($aseco, $command);
	}
	elseif ($answer[2] >= 6201 && $answer[2] <= 6700) {
		// get player & track ID
		$player = $aseco->server->players->getPlayer($answer[1]);
		$tmxid = $player->tracklist[$answer[2]-6201]['id'];
		$section = $player->tracklist[$answer[2]-6201]['section'];

		// log clicked command
		$aseco->console('player {1} clicked command "/admin add {2} {3}"',
		                $player->login, $tmxid, $section);

		// /admin add selected track
		$command = array();
		$command['author'] = $player;
		$command['params'] = 'add ' . $tmxid . ' ' . $section;
		chat_admin($aseco, $command);
	}
	elseif ($answer[2] >= 6701 && $answer[2] <= 7200) {
		// get player & track author
		$player = $aseco->server->players->getPlayer($answer[1]);
		$author = $player->tracklist[$answer[2]-6701]['author'];
		$section = $player->tracklist[$answer[2]-6701]['section'];
		// insure multi-word author is single parameter
		$author = str_replace(' ', '%20', $author);

		// log clicked command
		$aseco->console('player {1} clicked command "/xlist auth:{2} {3}"',
		                $player->login, $author, $section);

		// /xlist auth: selected author
		$command = array();
		$command['author'] = $player;
		$command['params'] = 'auth:' . $author . ' ' . $section;
		chat_xlist($aseco, $command);
	}
}  // event_jukebox
?>
