<?php
/* vim: set noexpandtab tabstop=2 softtabstop=2 shiftwidth=2: */

/**
 * Chatlog plugin.
 * Keeps log of player chat, and displays the chat log.
 * Created by Xymph
 *
 * Dependencies: none
 */

Aseco::registerEvent('onChat', 'log_chat');
Aseco::addChatCommand('chatlog', 'Displays log of recent chat messages');

global $chatbuf;  // chat history buffer
global $chatlen;  // length of chat history
global $linelen;  // max length of chat line

$chatbuf = array();
$chatlen = 30;
$linelen = 40;

// called @ onChat
function log_chat($aseco, $chat) {
	global $chatbuf, $chatlen;

	// check for non-empty player chat line, not a chat command
	if ($chat[0] != $aseco->server->id && $chat[2] != '' && $chat[2]{0} != '/') {
		// drop oldest chat line if buffer full
		if (count($chatbuf) >= $chatlen) {
			array_shift($chatbuf);
		}
		// append timestamp, player nickname (but strip wide font) & chat line to history
		if ($player = $aseco->server->players->getPlayer($chat[1]))
			$chatbuf[] = array(date('H:i:s'), str_ireplace('$w', '', $player->nickname), $chat[2]);
	}
}  // log_chat

function chat_chatlog($aseco, $command) {
	global $chatbuf, $linelen;

	$player = $command['author'];
	$login = $player->login;

	if (!empty($chatbuf)) {
		if ($aseco->server->getGame() == 'TMN') {
			$head = 'Recent chat history:' . LF;
			$msg = '';
			$lines = 0;
			$player->msgs = array();
			$player->msgs[0] = 1;
			foreach ($chatbuf as $item) {
				// break up long lines into chunks with continuation strings
				$multi = explode(LF, wordwrap(stripColors($item[2]), $linelen, LF . '...'));
				foreach ($multi as $line) {
					$line = substr($line, 0, $linelen+3);  // chop off excessively long words
					$msg .= '$z' . ($aseco->settings['chatpmlog_times'] ? '$n<{#server}' . $item[0] . '$z$n>$m ' : '') .
					        '[{#black}' . $item[1] . '$z] ' . $line . LF;
					if (++$lines > 9) {
						$player->msgs[] = $aseco->formatColors($head . $msg);
						$lines = 0;
						$msg = '';
					}
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
			$head = 'Recent chat history:';
			$msg = array();
			$lines = 0;
			$player->msgs = array();
			$player->msgs[0] = array(1, $head, array(1.2), array('Icons64x64_1', 'Outbox'));
			foreach ($chatbuf as $item) {
				// break up long lines into chunks with continuation strings
				$multi = explode(LF, wordwrap(stripColors($item[2]), $linelen+30, LF . '...'));
				foreach ($multi as $line) {
					$line = substr($line, 0, $linelen+33);  // chop off excessively long words
					$msg[] = array('$z' . ($aseco->settings['chatpmlog_times'] ? '<{#server}' . $item[0] . '$z> ' : '') .
					               '[{#black}' . $item[1] . '$z] ' . $line);
					if (++$lines > 14) {
						$player->msgs[] = $msg;
						$lines = 0;
						$msg = array();
					}
				}
			}
			// add if last batch exists
			if (!empty($msg))
				$player->msgs[] = $msg;

			// display ManiaLink message
			display_manialink_multi($player);
		}
	} else {
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors('{#server}> {#error}No chat history found!'), $login);
	}
}  // chat_chatlog
?>
