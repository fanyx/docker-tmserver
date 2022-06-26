<?php
/* vim: set noexpandtab tabstop=2 softtabstop=2 shiftwidth=2: */

/**
 * Chat plugin.
 * Builds a chat message starting with the player's nickname.
 * Updated by Xymph
 *
 * Dependencies: none
 */

Aseco::addChatCommand('me', 'Can be used to express emotions');

function chat_me($aseco, $command) {

	$player = $command['author'];

	// check if on global mute list
	if (in_array($player->login, $aseco->server->mutelist)) {
		$message = formatText($aseco->getChatMessage('MUTED'), '/me');
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $player->login);
		return;
	}

	// replace parameters
	$message = formatText('$i{1}$z$s$i {#emotic}{2}',
	                      $player->nickname, $command['params']);
	// show chat message
	$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($message));
}  // chat_me
?>
