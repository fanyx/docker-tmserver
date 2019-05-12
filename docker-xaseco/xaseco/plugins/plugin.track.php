<?php
/* vim: set noexpandtab tabstop=2 softtabstop=2 shiftwidth=2: */

/**
 * Track plugin.
 * Times playing time of a track, and provides track & time info.
 * Created by Xymph
 *
 * Dependencies: used by plugin.rasp_jukebox.php, plugin.rasp_votes.php
 */

Aseco::registerEvent('onNewChallenge', 'time_gameinfo');
Aseco::registerEvent('onNewChallenge2', 'time_newtrack');  // use 2nd event to start timer just before racing commences
Aseco::registerEvent('onEndRace', 'time_endrace');
Aseco::registerEvent('onSync', 'time_initreplays');

Aseco::addChatCommand('track', 'Shows info about the current track');
Aseco::addChatCommand('playtime', 'Shows time current track has been playing');
Aseco::addChatCommand('time', 'Shows current server time & date');

function chat_track($aseco, $command) {

	$name = stripColors($aseco->server->challenge->name);
	if ($aseco->server->getGame() == 'TMF' &&
	    isset($aseco->server->challenge->tmx->name) && $aseco->server->challenge->tmx->name != '')
		$name = '$l[http://' . $aseco->server->challenge->tmx->prefix .
		        '.tm-exchange.com/main.aspx?action=trackshow&id=' .
		        $aseco->server->challenge->tmx->id . ']' . $name . '$l';

	// check for Stunts mode
	if ($aseco->server->gameinfo->mode != Gameinfo::STNT) {
		$message = formatText($aseco->getChatMessage('TRACK'),
		                      $name, $aseco->server->challenge->author,
		                      formatTime($aseco->server->challenge->authortime),
		                      formatTime($aseco->server->challenge->goldtime),
		                      formatTime($aseco->server->challenge->silvertime),
		                      formatTime($aseco->server->challenge->bronzetime),
		                      $aseco->server->challenge->copperprice);
	} else {  // Stunts mode
		$message = formatText($aseco->getChatMessage('TRACK'),
		                      $name, $aseco->server->challenge->author,
		                      $aseco->server->challenge->gbx->authorScore,
		                      $aseco->server->challenge->goldtime,
		                      $aseco->server->challenge->silvertime,
		                      $aseco->server->challenge->bronzetime,
		                      $aseco->server->challenge->copperprice);
	}
	// $message .= LF . ' {#server}FileName: {#highlite}' . $aseco->server->challenge->filename;
	// show chat message
	$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $command['author']->login);
}  // chat_track

// called @ onNewChallenge
function time_gameinfo($aseco, $challenge) {

	// check for divider message
	if ($aseco->settings['show_curtrack'] > 0) {
		$name = stripColors($challenge->name);
		if ($aseco->server->getGame() == 'TMF' &&
		    isset($challenge->tmx->name) && $challenge->tmx->name != '')
			$name = '$l[http://' . $challenge->tmx->prefix .
			        '.tm-exchange.com/main.aspx?action=trackshow&id=' .
			        $challenge->tmx->id . ']' . $name . '$l';

		// compile message
		$message = formatText($aseco->getChatMessage('CURRENT_TRACK'),
		                      $name, $challenge->author,
		                      ($aseco->server->gameinfo->mode == Gameinfo::STNT ?
		                       $challenge->gbx->authorScore :
		                       formatTime($challenge->authortime)));

		// show chat message
		if ($aseco->server->getGame() == 'TMF' && $aseco->settings['show_curtrack'] == 2 &&
		    function_exists('send_window_message'))
			send_window_message($aseco, $message, false);
		else
			$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($message));
	}
}  // time_gameinfo

// called @ onSync
function time_initreplays($aseco, $data) {
	global $replays_counter, $replays_total;

	$replays_counter = 0;
	$replays_total = 0;
	$aseco->server->starttime = time();
}  // time_init

// called @ onNewChallenge2
function time_newtrack($aseco, $data) {
	global $replays_total;

	// remember time this track starts playing
	$aseco->server->challenge->starttime = time();
	if ($replays_total == 0)
		$aseco->server->starttime = time();
}  // time_newtrack

function time_playing($aseco) {

	// return track playing time
	return (time() - $aseco->server->challenge->starttime);
}  // time_playing

// called @ onEndRace
function time_endrace($aseco, $data) {
	global $replays_total;

	// skip if TimeAttack/Stunts mode (always same playing time),
	// or if disabled
	if ($aseco->settings['show_playtime'] == 0 ||
	    $aseco->server->gameinfo->mode == Gameinfo::TA ||
	    $aseco->server->gameinfo->mode == Gameinfo::STNT)
		return;

	$name = stripColors($aseco->server->challenge->name);
	if ($aseco->server->getGame() == 'TMF' &&
	    isset($aseco->server->challenge->tmx->name) && $aseco->server->challenge->tmx->name != '')
		$name = '$l[http://' . $aseco->server->challenge->tmx->prefix .
		        '.tm-exchange.com/main.aspx?action=trackshow&id=' .
		        $aseco->server->challenge->tmx->id . ']' . $name . '$l';

	// compute track playing time
	$playtime = time() - $aseco->server->challenge->starttime;
	$playtime = formatTimeH($playtime * 1000, false);
	$totaltime = time() - $aseco->server->starttime;
	$totaltime = formatTimeH($totaltime * 1000, false);

	// show chat message
	$message = formatText($aseco->getChatMessage('PLAYTIME_FINISH'),
	                      $name, $playtime);
	if ($replays_total > 0)
		$message .= formatText($aseco->getChatMessage('PLAYTIME_REPLAY'),
		                       $replays_total, ($replays_total == 1 ? '' : 's'), $totaltime);

	if ($aseco->server->getGame() == 'TMF' && $aseco->settings['show_playtime'] == 2 &&
	    function_exists('send_window_message'))
		send_window_message($aseco, $message, false);
	else
		$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($message));

	// log console message
	if ($replays_total == 0)
		$aseco->console('track [{1}] finished after: {2}',
		                stripColors($aseco->server->challenge->name, false), $playtime);
	else
		$aseco->console('track [{1}] finished after: {2} ({3} replay{4}, total: {5})',
		                stripColors($aseco->server->challenge->name, false), $playtime,
		                $replays_total, ($replays_total == 1 ? '' : 's'), $totaltime);
}  // time_endrace

function chat_playtime($aseco, $command) {
	global $replays_total;

	$name = stripColors($aseco->server->challenge->name);
	if ($aseco->server->getGame() == 'TMF' &&
	    isset($aseco->server->challenge->tmx->name) && $aseco->server->challenge->tmx->name != '')
		$name = '$l[http://' . $aseco->server->challenge->tmx->prefix .
		        '.tm-exchange.com/main.aspx?action=trackshow&id=' .
		        $aseco->server->challenge->tmx->id . ']' . $name . '$l';

	// compute track playing time
	$playtime = time() - $aseco->server->challenge->starttime;
	$totaltime = time() - $aseco->server->starttime;

	// show chat message
	$message = formatText($aseco->getChatMessage('PLAYTIME'),
	                      $name, formatTimeH($playtime * 1000, false));
	if ($replays_total > 0)
		$message .= formatText($aseco->getChatMessage('PLAYTIME_REPLAY'),
		                       $replays_total, ($replays_total == 1 ? '' : 's'), formatTimeH($totaltime * 1000, false));

	$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $command['author']->login);
}  // chat_playtime

function chat_time($aseco, $command) {

	// show chat message
	$message = formatText($aseco->getChatMessage('TIME'),
	                      date('H:i:s T'), date('Y/M/d'));
	$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $command['author']->login);
}  // chat_time
?>
