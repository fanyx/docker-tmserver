<?php
/* vim: set noexpandtab tabstop=2 softtabstop=2 shiftwidth=2: */

/**
 * Chat plugin.
 * Provides private messages and a wide variety of shout-outs.
 * Updated by Xymph
 *
 * Dependencies: requires chat.admin.php
 */

Aseco::addChatCommand('pm', 'Sends a private message to login or Player_ID');
Aseco::addChatCommand('pma', 'Sends a private message to player & admins');
Aseco::addChatCommand('pmlog', 'Displays log of your recent private messages');
Aseco::addChatCommand('hi', 'Sends a Hi message to everyone');
Aseco::addChatCommand('bye', 'Sends a Bye message to everyone');
Aseco::addChatCommand('thx', 'Sends a Thanks message to everyone');
Aseco::addChatCommand('lol', 'Sends a Lol message to everyone');
Aseco::addChatCommand('lool', 'Sends a Lool message to everyone');
Aseco::addChatCommand('brb', 'Sends a Be Right Back message to everyone');
Aseco::addChatCommand('afk', 'Sends an Away From Keyboard message to everyone');
Aseco::addChatCommand('gg', 'Sends a Good Game message to everyone');
Aseco::addChatCommand('gr', 'Sends a Good Race message to everyone');
Aseco::addChatCommand('n1', 'Sends a Nice One message to everyone');
Aseco::addChatCommand('bgm', 'Sends a Bad Game message to everyone');
Aseco::addChatCommand('official', 'Shows a helpful message ;-)');
Aseco::addChatCommand('bootme', 'Boot yourself from the server');

function chat_pm($aseco, $command) {
	global $muting_available,  // from plugin.muting.php
	       $pmlen;  // from chat.admin.php

	$command['params'] = explode(' ', $command['params'], 2);

	$player = $command['author'];
	$target = $player;

	// get player login or ID
	if (!$target = $aseco->getPlayerParam($player, $command['params'][0]))
		return;

	// check for a message
	if (isset($command['params'][1]) && $command['params'][1] != '') {
		$stamp = date('H:i:s');
		// strip wide fonts from nicks
		$plnick = str_ireplace('$w', '', $player->nickname);
		$tgnick = str_ireplace('$w', '', $target->nickname);

		// drop oldest pm line if sender's buffer full
		if (count($player->pmbuf) >= $pmlen) {
			array_shift($player->pmbuf);
		}
		// append timestamp, sender nickname and pm line to sender's history
		$player->pmbuf[] = array($stamp, $plnick, $command['params'][1]);

		// drop oldest pm line if receiver's buffer full
		if (count($target->pmbuf) >= $pmlen) {
			array_shift($target->pmbuf);
		}
		// append timestamp, sender nickname and pm line to receiver's history
		$target->pmbuf[] = array($stamp, $plnick, $command['params'][1]);

		// show chat message to both players
		$msg = '{#error}-pm-$g[' . $plnick . '$z$s$i->' . $tgnick . '$z$s$i]$i {#interact}' . $command['params'][1];
		$msg = $aseco->formatColors($msg);
		$aseco->client->addCall('ChatSendServerMessageToLogin', array($msg, $target->login));
		$aseco->client->addCall('ChatSendServerMessageToLogin', array($msg, $player->login));
		if (!$aseco->client->multiquery()) {
			trigger_error('[' . $aseco->client->getErrorCode() . '] ChatSend PM (multi) - ' . $aseco->client->getErrorMessage(), E_USER_WARNING);
		}

		// check if player muting is enabled
		if ($muting_available) {
			// append pm line to both players' buffers
			if (count($target->mutebuf) >= 28) {  // chat window length
				array_shift($target->mutebuf);
			}
			$target->mutebuf[] = $msg;
			if (count($player->mutebuf) >= 28) {  // chat window length
				array_shift($player->mutebuf);
			}
			$player->mutebuf[] = $msg;
		}

	} else {
		$msg = '{#server}> {#error}No message!';
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($msg), $player->login);
	}
}  // chat_pm

function chat_pma($aseco, $command) {
	global $muting_available,  // from plugin.muting.php
	       $pmlen;  // from chat.admin.php

	$command['params'] = explode(' ', $command['params'], 2);

	$player = $command['author'];
	$target = $player;

	// check for admin ability
	if ($aseco->allowAbility($player, 'chat_pma')) {
		// get player login or ID
		if (!$target = $aseco->getPlayerParam($player, $command['params'][0]))
			return;

		// check for a message
		if ($command['params'][1] != '') {
			$stamp = date('H:i:s');
			// strip wide fonts from nicks
			$plnick = str_ireplace('$w', '', $player->nickname);
			$tgnick = str_ireplace('$w', '', $target->nickname);

			// drop oldest pm line if receiver's history full
			if (count($target->pmbuf) >= $pmlen) {
				array_shift($target->pmbuf);
			}
			// append timestamp, sender nickname and pm line to receiver's history
			$target->pmbuf[] = array($stamp, $plnick, $command['params'][1]);

			// show chat message to receiver
			$msg = '{#error}-pm-$g[' . $plnick . '$z$s$i->' . $tgnick . '$z$s$i]$i {#interact}' . $command['params'][1];
			$msg = $aseco->formatColors($msg);
			$aseco->client->addCall('ChatSendServerMessageToLogin', array($msg, $target->login));

			// check if player muting is enabled
			if ($muting_available) {
				// drop oldest message if receiver's mute buffer full
				if (count($target->mutebuf) >= 28) {  // chat window length
					array_shift($target->mutebuf);
				}
				// append pm line to receiver's mute buffer
				$target->mutebuf[] = $msg;
			}

			// show chat message to all admins
			foreach ($aseco->server->players->player_list as $admin) {
				// check for admin ability
				if ($aseco->allowAbility($admin, 'chat_pma')) {
					// drop oldest pm line if admin's buffer full
					if (count($admin->pmbuf) >= $pmlen) {
						array_shift($admin->pmbuf);
					}
					// append timestamp, sender nickname and pm line to admin's history
					$admin->pmbuf[] = array($stamp, $plnick, $command['params'][1]);

					// CC the message
					$aseco->client->addCall('ChatSendServerMessageToLogin', array($msg, $admin->login));

					// check if player muting is enabled
					if ($muting_available) {
						// append pm line to admin's mute buffer
						if (count($admin->mutebuf) >= 28) {  // chat window length
							array_shift($admin->mutebuf);
						}
						$admin->mutebuf[] = $msg;
					}
				}
			}
			if (!$aseco->client->multiquery()) {
				trigger_error('[' . $aseco->client->getErrorCode() . '] ChatSend PMA (multi) - ' . $aseco->client->getErrorMessage(), E_USER_WARNING);
			}

		} else {
			$msg = '{#server}> {#error}No message!';
			$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($msg), $player->login);
		}
	} else {
		$msg = $aseco->getChatMessage('NO_ADMIN');
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($msg), $player->login);
	}
}  // chat_pma

function chat_pmlog($aseco, $command) {
	global $lnlen;  // from chat.admin.php

	$player = $command['author'];
	$login = $player->login;

	if (!empty($player->pmbuf)) {
		if ($aseco->server->getGame() == 'TMN') {
			$head = 'Your recent PM history:' . LF;
			$msg = '';
			$lines = 0;
			$player->msgs = array();
			$player->msgs[0] = 1;
			foreach ($player->pmbuf as $item) {
				// break up long lines into chunks with continuation strings
				$multi = explode(LF, wordwrap(stripColors($item[2]), $lnlen, LF . '...'));
				foreach ($multi as $line) {
					$line = substr($line, 0, $lnlen+3);  // chop off excessively long words
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
			$head = 'Your recent PM history:';
			$msg = array();
			$lines = 0;
			$player->msgs = array();
			$player->msgs[0] = array(1, $head, array(1.2), array('Icons64x64_1', 'Outbox'));
			foreach ($player->pmbuf as $item) {
				// break up long lines into chunks with continuation strings
				$multi = explode(LF, wordwrap(stripColors($item[2]), $lnlen+30, LF . '...'));
				foreach ($multi as $line) {
					$line = substr($line, 0, $lnlen+33);  // chop off excessively long words
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
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors('{#server}> {#error}No PM history found!'), $login);
	}
}  // chat_pmlog

function chat_hi($aseco, $command) {

	$player = $command['author'];

	// check if on global mute list
	if (in_array($player->login, $aseco->server->mutelist)) {
		$message = formatText($aseco->getChatMessage('MUTED'), '/hi');
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $player->login);
		return;
	}

	if ($command['params'] != '') {
		$msg = '$g[' . $player->nickname . '$z$s] {#interact}Hello ' . $command['params'] . ' !';
	} else {
		$msg = '$g[' . $player->nickname . '$z$s] {#interact}Hello All !';
	}
	$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($msg));
}  // chat_hi

function chat_bye($aseco, $command) {

	$player = $command['author'];

	// check if on global mute list
	if (in_array($player->login, $aseco->server->mutelist)) {
		$message = formatText($aseco->getChatMessage('MUTED'), '/bye');
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $player->login);
		return;
	}

	if ($command['params'] != '') {
		$msg = '$g[' . $player->nickname . '$z$s] {#interact}Bye ' . $command['params'] . ' !';
	} else {
		$msg = '$g[' . $player->nickname . '$z$s] {#interact}I have to go... Bye All !';
	}
	$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($msg));
}  // chat_bye

function chat_thx($aseco, $command) {

	$player = $command['author'];

	// check if on global mute list
	if (in_array($player->login, $aseco->server->mutelist)) {
		$message = formatText($aseco->getChatMessage('MUTED'), '/thx');
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $player->login);
		return;
	}

	if ($command['params'] != '') {
		$msg = '$g[' . $player->nickname . '$z$s] {#interact}Thanks ' . $command['params'] . ' !';
	} else {
		$msg = '$g[' . $player->nickname . '$z$s] {#interact}Thanks All !';
	}
	$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($msg));
}  // chat_thx

function chat_lol($aseco, $command) {

	$player = $command['author'];

	// check if on global mute list
	if (in_array($player->login, $aseco->server->mutelist)) {
		$message = formatText($aseco->getChatMessage('MUTED'), '/lol');
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $player->login);
		return;
	}

	$msg = '$g[' . $player->nickname . '$z$s] {#interact}LoL !';
	$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($msg));
}  // chat_lol

function chat_lool($aseco, $command) {

	$player = $command['author'];

	// check if on global mute list
	if (in_array($player->login, $aseco->server->mutelist)) {
		$message = formatText($aseco->getChatMessage('MUTED'), '/lool');
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $player->login);
		return;
	}

	$msg = '$g[' . $player->nickname . '$z$s] {#interact}LooOOooL !';
	$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($msg));
}  // chat_lool

function chat_brb($aseco, $command) {

	$player = $command['author'];

	// check if on global mute list
	if (in_array($player->login, $aseco->server->mutelist)) {
		$message = formatText($aseco->getChatMessage('MUTED'), '/brb');
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $player->login);
		return;
	}

	$msg = '$g[' . $player->nickname . '$z$s] {#interact}Be Right Back !';
	$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($msg));
}  // chat_brb

function chat_afk($aseco, $command) {

	$player = $command['author'];

	// check if on global mute list
	if (in_array($player->login, $aseco->server->mutelist)) {
		$message = formatText($aseco->getChatMessage('MUTED'), '/afk');
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $player->login);
		return;
	}

	$msg = '$g[' . $player->nickname . '$z$s] {#interact}Away From Keyboard !';
	$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($msg));

	// check for TMF & auto force spectator
	if ($aseco->server->getGame() == 'TMF' && $aseco->settings['afk_force_spec']) {
		if (!$aseco->isSpectator($player)) {
			// force player into spectator
			$rtn = $aseco->client->query('ForceSpectator', $player->login, 1);
			if (!$rtn) {
				trigger_error('[' . $aseco->client->getErrorCode() . '] ForceSpectator - ' . $aseco->client->getErrorMessage(), E_USER_WARNING);
			} else {
				// allow spectator to switch back to player
				$rtn = $aseco->client->query('ForceSpectator', $player->login, 0);
			}
		}

		// force free camera mode on spectator
		$aseco->client->addCall('ForceSpectatorTarget', array($player->login, '', 2));
		// free up player slot
		$aseco->client->addCall('SpectatorReleasePlayerSlot', array($player->login));
	}
}  // chat_afk

function chat_gg($aseco, $command) {

	$player = $command['author'];

	// check if on global mute list
	if (in_array($player->login, $aseco->server->mutelist)) {
		$message = formatText($aseco->getChatMessage('MUTED'), '/gg');
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $player->login);
		return;
	}

	if ($command['params'] != '') {
		$msg = '$g[' . $player->nickname . '$z$s] {#interact}Good Game ' . $command['params'] . ' !';
	} else {
		$msg = '$g[' . $player->nickname . '$z$s] {#interact}Good Game All !';
	}
	$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($msg));
}  // chat_gg

function chat_gr($aseco, $command) {

	$player = $command['author'];

	// check if on global mute list
	if (in_array($player->login, $aseco->server->mutelist)) {
		$message = formatText($aseco->getChatMessage('MUTED'), '/gr');
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $player->login);
		return;
	}

	if ($command['params'] != '') {
		$msg = '$g[' . $player->nickname . '$z$s] {#interact}Good Race ' . $command['params'] . ' !';
	} else {
		$msg = '$g[' . $player->nickname . '$z$s] {#interact}Good Race !';
	}
	$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($msg));
}  // chat_gr

function chat_n1($aseco, $command) {

	$player = $command['author'];

	// check if on global mute list
	if (in_array($player->login, $aseco->server->mutelist)) {
		$message = formatText($aseco->getChatMessage('MUTED'), '/n1');
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $player->login);
		return;
	}

	if ($command['params'] != '') {
		$msg = '$g[' . $player->nickname . '$z$s] {#interact}Nice One ' . $command['params'] . ' !';
	} else {
		$msg = '$g[' . $player->nickname . '$z$s] {#interact}Nice One !';
	}
	$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($msg));
}  // chat_n1

function chat_bgm($aseco, $command) {

	$player = $command['author'];

	// check if on global mute list
	if (in_array($player->login, $aseco->server->mutelist)) {
		$message = formatText($aseco->getChatMessage('MUTED'), '/bgm');
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $player->login);
		return;
	}

	$msg = '$g[' . $player->nickname . '$z$s] {#interact}Bad Game for Me :(';
	$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($msg));
}  // chat_bgm

function chat_official($aseco, $command) {
	global $rasp;

	$msg = $rasp->messages['OFFICIAL'][0];
	$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($msg), $command['author']->login);
}  // chat_official

function chat_bootme($aseco, $command) {
	global $rasp;

	// show departure message and kick player
	$msg = formatText($rasp->messages['BOOTME'][0],
	                  $command['author']->nickname);
	$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($msg));
	if ($aseco->server->getGame() == 'TMF' &&
	    isset($rasp->messages['BOOTME_DIALOG'][0]) && $rasp->messages['BOOTME_DIALOG'][0] != '')
		$aseco->client->addCall('Kick', array($command['author']->login,
		                        $aseco->formatColors($rasp->messages['BOOTME_DIALOG'][0] . '$z')));
	else
		$aseco->client->addCall('Kick', array($command['author']->login));
}  // chat_bootme
?>
