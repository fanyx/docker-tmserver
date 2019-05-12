<?php
/* vim: set noexpandtab tabstop=2 softtabstop=2 shiftwidth=2: */

/**
 * Chat plugin.
 * Re-displays last closed multi-page window.
 * Created by Xymph
 *
 * Dependencies: none
 */

Aseco::addChatCommand('lastwin', 'Re-opens the last closed multi-page window');

function chat_lastwin($aseco, $command) {

	$player = $command['author'];
	$login = $player->login;

	if (!isset($player->msgs) || empty($player->msgs)) {
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors('{#server}> {#error}No multi-page window available!'), $login);
		return;
	}

	// redisplay popup window for TMN
	if ($aseco->server->getGame() == 'TMN') {
		$player->msgs[0] = 1;  // reset page #
		// display popup message
		if (count($player->msgs) == 2) {
			$aseco->client->query('SendDisplayServerMessageToLogin', $login, $player->msgs[1], 'OK', '', 0);
		} else {  // > 2
			$aseco->client->query('SendDisplayServerMessageToLogin', $login, $player->msgs[1], 'Close', 'Next', 0);
		}

	// redisplay ManiaLink window for TMF
	} elseif ($aseco->server->getGame() == 'TMF') {
		// display ManiaLink message
		display_manialink_multi($player);
	}
}  // chat_lastwin
?>
