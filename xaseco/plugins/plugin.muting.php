<?php
/* vim: set noexpandtab tabstop=2 softtabstop=2 shiftwidth=2: */

/**
 * Muting plugin.
 * Handles individual and global player muting, and provides
 * /mute, /unmute, /mutelist & /refresh commands.
 * Created by Xymph
 *
 * Dependencies: none
 */

Aseco::registerEvent('onStartup', 'init_globalpat');
Aseco::registerEvent('onChat', 'handle_muting');
Aseco::addChatCommand('mute', 'Mute another player\'s chat messages');
Aseco::addChatCommand('unmute', 'UnMute another player\'s chat messages');
Aseco::addChatCommand('mutelist', 'Display list of muted players');
Aseco::addChatCommand('refresh', 'Refresh chat window');

global $globalpat;  // pre-defined pattern for global messages
global $muting_available;  // signal to chat.admin.php & plugin.rasp_chat.php

// called @ onStartup
function init_globalpat() {
	global $aseco, $globalpat, $muting_available;

	// define pattern for known global messages to reduce overhead
	$globalpat = '/' . $aseco->formatColors(formatText($aseco->getChatMessage('ROUND'), '\d+'))
	             . '|' . $aseco->formatColors('$z$s{#server}>> ')
	             . '|' . $aseco->formatColors('{#server}>> ') . '/A';  // anchor at start
	$globalpat = str_replace('$', '\$', $globalpat);  // escape dollars

	$muting_available = true;
}  // init_globalpat

// called @ onChat
function handle_muting($aseco, $chat) {
	global $globalpat;

	// check for player chat line
	if ($chat[0] != $aseco->server->id) {
		// check if not a registered (== hidden) chat command
		if (!$chat[3] && ($chatter = $aseco->server->players->getPlayer($chat[1]))) {

			// check each player's mute list and global mute list
			foreach ($aseco->server->players->player_list as $player) {
				if (in_array($chat[1], $player->mutelist) ||
				    in_array($chat[1], $aseco->server->mutelist)) {
					// spew buffer back to player and thus mute the chatter
					if (!empty($player->mutebuf)) {
						$buf = '';
						foreach ($player->mutebuf as $line) {
							// double '$z' to avoid match with $globalpat that would cause
							// spewed buffer to be buffered again
							$buf .= LF . '$z$z$s' . $line;
						}
						$aseco->client->query('ChatSendServerMessageToLogin', $buf, $player->login);
					}
				} else {
					// append chatter line to buffer
					if (count($player->mutebuf) >= 28) {  // chat window length
						array_shift($player->mutebuf);
					}
					$player->mutebuf[] = '$z$s[' . $chatter->nickname . '$z$s] ' . $chat[2];
				}
			}
		}
	} else {  // any server chat
		// check for global server message
		if (preg_match($globalpat, $chat[2])) {
			// append global server message to all players' buffers
			foreach ($aseco->server->players->player_list as $player) {
				if (count($player->mutebuf) >= 28) {  // chat window length
					array_shift($player->mutebuf);
				}
				$player->mutebuf[] = $chat[2];
			}
		}
	}
}  // handle_muting

function chat_mute($aseco, $command) {

	$player = $command['author'];
	$target = $player;

	// get player login or ID
	if (!$target = $aseco->getPlayerParam($player, $command['params']))
		return;

	// check for any admin tier
	if ($aseco->isAnyAdmin($target)) {
		// obtain correct title
		$title = $aseco->isMasterAdmin($target) ? $aseco->titles['MASTERADMIN'][0] :
		         ($aseco->isAdmin($target) ? $aseco->titles['ADMIN'][0] :
		          ($aseco->isOperator($target) ? $aseco->titles['OPERATOR'][0] :
		           'Player'));
		$message = formatText('{#server}> {#error}Cannot mute {#logina}$i {1} {#highlite}{2}$z$s{#error} !',
		                      $title, stripColors($target->nickname));
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $player->login);
		return;
	}

	// check if not yet in mute list
	if (!in_array($target->login, $player->mutelist)) {
		// mute this player
		$player->mutelist[] = $target->login;

		$message = formatText($aseco->getChatMessage('MUTE'),
		                      $target->nickname);
	} else {
		$message = '{#server}> {#error}Player {#highlite}$i ' . stripColors($target->nickname) . '$z$s{#error} is already in your mute list!';
	}
	// show chat message
	$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $player->login);
}  // chat_mute

function chat_unmute($aseco, $command) {

	$player = $command['author'];
	$target = $player;

	// get player login or ID
	if (!$target = $aseco->getPlayerParam($player, $command['params'], true))
		return;

	// check if indeed in mute list
	if (($i = array_search($target->login, $player->mutelist)) !== false) {
		// unmute this player
		$player->mutelist[$i] = '';

		$message = formatText($aseco->getChatMessage('UNMUTE'),
		                      $target->nickname);
	} else {
		$message = '{#server}> {#error}Player {#highlite}$i ' . stripColors($target->nickname) . '$z$s{#error} is not in your mute list!';
	}
	// show chat message
	$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $player->login);
}  // chat_unmute

function chat_mutelist($aseco, $command) {

	$player = $command['author'];
	$login = $player->login;

	// check for muted players
	if (empty($player->mutelist)) {
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors('{#server}> {#error}No muted players found!'), $login);
		return;
	}

	if ($aseco->server->getGame() == 'TMN') {
		$player->playerlist = array();
		$player->msgs = array();
		$player->msgs[0] = 1;

		$head = 'Currently Muted Players:' . LF . 'Id     {#nick}Nick $g/{#login} Login' . LF;
		$msg = '';
		$pid = 1;
		$lines = 0;
		foreach ($player->mutelist as $pl) {
			if ($pl != '') {
				$plarr = array();
				$plarr['login'] = $pl;
				$player->playerlist[] = $plarr;

				$msg .= '$g' . str_pad($pid, 2, '0', STR_PAD_LEFT) . '.   {#black}'
				        . str_ireplace('$w', '', $aseco->getPlayerNick($pl))
				        . '$z / {#login}' . $pl . LF;
				$pid++;
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
		} elseif (count($player->msgs) > 2) {
			$aseco->client->query('SendDisplayServerMessageToLogin', $login, $player->msgs[1], 'Close', 'Next', 0);
		} else {  // == 1
			$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors('{#server}> {#error}No muted players found!'), $login);
		}

	} elseif ($aseco->server->getGame() == 'TMF') {
		$player->playerlist = array();

		$head = 'Currently Muted Players:';
		$msg = array();
		$msg[] = array('Id', '{#nick}Nick $g/{#login} Login');
		$pid = 1;
		$lines = 0;
		$player->msgs = array();
		$player->msgs[0] = array(1, $head, array(0.9, 0.1, 0.8), array('Icons128x128_1', 'Padlock', 0.01));
		foreach ($player->mutelist as $pl) {
			if ($pl != '') {
				$plarr = array();
				$plarr['login'] = $pl;
				$player->playerlist[] = $plarr;

				$msg[] = array(str_pad($pid, 2, '0', STR_PAD_LEFT) . '.',
				               '{#black}' . str_ireplace('$w', '', $aseco->getPlayerNick($pl))
				               . '$z / {#login}' . $pl);
				$pid++;
				if (++$lines > 14) {
					$player->msgs[] = $msg;
					$lines = 0;
					$msg = array();
					$msg[] = array('Id', '{#nick}Nick $g/{#login} Login');
				}
			}
		}
		// add if last batch exists
		if (count($msg) > 1)
			$player->msgs[] = $msg;

		// display ManiaLink message
		if (count($player->msgs) > 1) {
			display_manialink_multi($player);
		} else {  // == 1
			$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors('{#server}> {#error}No muted players found!'), $login);
		}
	}
}  // chat_mutelist

function chat_refresh($aseco, $command) {

	$player = $command['author'];

	// spew buffer back to player
	if (!empty($player->mutebuf)) {
		$buf = '';
		foreach ($player->mutebuf as $line) {
			// double '$z' to avoid match with $globalpat that would cause
			// spewed buffer to be buffered again
			$buf .= LF . '$z$z$s' . $line;
		}
		$aseco->client->query('ChatSendServerMessageToLogin', $buf, $player->login);
	}
}  // chat_refresh
?>
