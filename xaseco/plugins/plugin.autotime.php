<?php
/* vim: set noexpandtab tabstop=2 softtabstop=2 shiftwidth=2: */

/**
 * Auto TimeLimit plugin.
 * Changes Timelimit for TimeAttack dynamically depending on the next
 * track's author time.
 *
 * Original by ck|cyrus
 * Rewrite by Xymph
 *
 * Dependencies: none (but must be after plugin.rasp_jukebox.php in plugins.xml)
 */

Aseco::registerEvent('onSync', 'load_atlconfig');
Aseco::registerEvent('onEndRace', 'autotimelimit');

global $atl_config, $atl_active, $atl_restart;

// load ATL configuration
function load_atlconfig($aseco) {
	global $atl_config, $atl_active, $atl_restart;

	// initialize flags
	$atl_active = false;
	$atl_restart = false;

	// load config file
	$config_file = 'autotime.xml';
	if (file_exists($config_file)) {
		$aseco->console('Load auto timelimit config [' . $config_file . ']');
		if ($xml = $aseco->xml_parser->parseXml($config_file)) {
			$atl_config = $xml['AUTOTIME'];
			$atl_active = true;
		} else {
			trigger_error('[ATL] Could not read/parse config file ' . $config_file . ' !', E_USER_WARNING);
		}
	} else {
		trigger_error('[ATL] Could not find config file ' . $config_file . ' !', E_USER_WARNING);
	}
}  // load_atlconfig

// called @ onEndRace
function autotimelimit($aseco, $data) {
	global $atl_config, $atl_active, $atl_restart;

	// if not active, bail out immediately
	if (!$atl_active) return;
	// if restarting, bail out immediately
	if ($atl_restart) {
		$atl_restart = false;
		return;
	}

	// get next game settings
	$aseco->client->query('GetNextGameInfo');
	$nextgame = $aseco->client->getResponse();

	// check for TimeAttack on next track
	if ($nextgame['GameMode'] == Gameinfo::TA) {
		// check if auto timelimit enabled
		if ($atl_config['MULTIPLICATOR'][0] > 0) {
			// check if at least one active player on the server
			if (active_player($aseco)) {
				// get next track details
				$challenge = get_trackinfo($aseco, 1);
				$newtime = intval($challenge->authortime);
			} else {
				// server already switched so get current track name
				$challenge = get_trackinfo($aseco, 0);
				$newtime = 0;  // force default
				$newtime = intval($challenge->authortime);
			}

			// compute new timelimit
			if ($newtime <= 0) {
				$newtime = $atl_config['DEFAULTTIME'][0] * 60 * 1000;
				$tag = 'default';
			} else {
				$newtime *= $atl_config['MULTIPLICATOR'][0];
				$newtime -= ($newtime % 1000);  // round down to seconds
				$tag = 'new';
			}
			// check for min/max times
			if ($newtime < $atl_config['MINTIME'][0] * 60 * 1000) {
				$newtime = $atl_config['MINTIME'][0] * 60 * 1000;
				$tag = 'min';
			} elseif ($newtime > $atl_config['MAXTIME'][0] * 60 * 1000) {
				$newtime = $atl_config['MAXTIME'][0] * 60 * 1000;
				$tag = 'max';
			}

			// set and log timelimit (strip .00 sec)
			$aseco->client->addcall('SetTimeAttackLimit', array($newtime));
			$aseco->console('set {1} timelimit for [{2}]: {3} (Author time: {4})',
			                $tag, stripColors($challenge->name, false),
			                substr(formatTime($newtime), 0, -3),
			                formatTime($challenge->authortime));

			// display timelimit (strip .00 sec)
			$message = formatText($atl_config['MESSAGE'][0], $tag,
			                      stripColors($challenge->name),
			                      substr(formatTime($newtime), 0, -3),
			                      formatTime($challenge->authortime));
			if ($atl_config['DISPLAY'][0] == 2 && function_exists('send_window_message'))
				send_window_message($aseco, $message, true);
			elseif ($atl_config['DISPLAY'][0] > 0)
				$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($message));
		}
	}
}  // autotimelimit

// get info on current/next track
function get_trackinfo($aseco, $offset) {

	// get current/next track using /nextmap algorithm
	if ($aseco->server->getGame() != 'TMF') {
		$aseco->client->query('GetCurrentChallengeIndex');
		$trkid = $aseco->client->getResponse();
		$trkid += $offset;
		$aseco->client->resetError();
		$rtn = $aseco->client->query('GetChallengeList', 1, $trkid);
		$track = $aseco->client->getResponse();
		if ($aseco->client->isError()) {
			// get first track
			$rtn = $aseco->client->query('GetChallengeList', 1, 0);
			$track = $aseco->client->getResponse();
		}
	} else {  // TMF
		if ($offset == 1)
			$aseco->client->query('GetNextChallengeIndex');
		else
			$aseco->client->query('GetCurrentChallengeIndex');
		$trkid = $aseco->client->getResponse();
		$rtn = $aseco->client->query('GetChallengeList', 1, $trkid);
		$track = $aseco->client->getResponse();
	}

	// get track info
	$rtn = $aseco->client->query('GetChallengeInfo', $track[0]['FileName']);
	$trackinfo = $aseco->client->getResponse();
	return new Challenge($trackinfo);
}  // get_trackinfo

// check for at least one active player
function active_player($aseco) {

	$total = 0;
	// check all connected players
	foreach ($aseco->server->players->player_list as $player) {
		// get current player status
		if (!$aseco->isSpectator($player))
			return true;
	}
	return false;
}  // active_player
?>
