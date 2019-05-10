<?php
/* vim: set noexpandtab tabstop=2 softtabstop=2 shiftwidth=2: */

/**
 * Msglog plugin.
 * Keeps log of system messages, and displays the messages log.
 * Created by Xymph
 *
 * Dependencies: none
 */

Aseco::addChatCommand('msglog', 'Displays log of recent system messages');

// handles action id "7223" for /msglog button
Aseco::registerEvent('onPlayerManialinkPageAnswer', 'event_msglog');
Aseco::registerEvent('onPlayerConnect', 'msglog_button');

global $msgbuf;  // message history buffer
global $msglen;  // length of message history
global $linlen;  // max length of message line
global $winlen;  // number of message lines

$msgbuf = array();
$msglen = 21;
$linlen = 800;
$winlen = 5;

function send_window_message($aseco, $message, $scoreboard) {
	global $msgbuf, $msglen, $linlen, $winlen;

	// append message line(s) to history
	$message = explode(LF, $message);
	foreach ($message as $item) {
		// break up long (report) lines into chunks
		$multi = explode(LF, wordwrap('$z$s' . $item, $linlen, LF . '$z$s$n'));
		foreach ($multi as $line) {
			// drop oldest message line if buffer full
			if (count($msgbuf) >= $msglen) {
				array_shift($msgbuf);
			}
			$msgbuf[] = $aseco->formatColors($line);
		}
	}

	// check for display at end of track
	if ($scoreboard) {
		$aseco->client->query('GetChatTime');
		$timeout = $aseco->client->getResponse();
		$timeout = $timeout['CurrentValue'] + 5000;  // podium animation
	} else {
		$timeout = $aseco->settings['window_timeout'] * 1000;
	}
	$lines = array_slice($msgbuf, -$winlen);
	display_msgwindow($aseco, $lines, $timeout);
}  // send_window_message

function chat_msglog($aseco, $command) {
	global $msgbuf;

	$player = $command['author'];
	$login = $player->login;

	if ($aseco->server->getGame() == 'TMF') {
		if (!empty($msgbuf)) {
			$header = 'Recent system message history:';
			$msgs = array();
			foreach ($msgbuf as $line)
				$msgs[] = array($line);

			// display ManiaLink message
			display_manialink($login, $header, array('Icons64x64_1', 'NewMessage'), $msgs, array(1.53), 'OK');
		} else {
			$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors('{#server}> {#error}No system message history found!'), $login);
		}
	} else {
		$message = $aseco->getChatMessage('FOREVER_ONLY');
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
	}
}  // chat_msglog


// called @ onPlayerConnect
function msglog_button($aseco, $player) {

	display_msglogbutton($aseco, $player->login);
}  // msglog_button

// called @ onPlayerManialinkPageAnswer // Handles ManiaLink style responses
// [0]=PlayerUid, [1]=Login, [2]=Answer
function event_msglog($aseco, $answer) {

	// leave actions other than 7223 to other handlers
	if ($answer[2] == 7223) {
		// get player
		$player = $aseco->server->players->getPlayer($answer[1]);

		// log clicked command
		$aseco->console('player {1} clicked command "/msglog "', $player->login);

		// /msglog
		$command = array();
		$command['author'] = $player;
		chat_msglog($aseco, $command);
	}
}  // event_msglog
?>
