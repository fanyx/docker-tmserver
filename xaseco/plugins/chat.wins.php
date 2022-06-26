<?php
/* vim: set noexpandtab tabstop=2 softtabstop=2 shiftwidth=2: */

/**
 * Chat plugin.
 * Shows player wins.
 * Created by Xymph
 *
 * Dependencies: none
 */

Aseco::addChatCommand('wins', 'Shows wins for current player');

function chat_wins($aseco, $command) {

	$wins = $command['author']->getWins();
	// use plural unless 1, and add ! for 2 or more
	$message = formatText($aseco->getChatMessage('WINS'), $wins,
	                      ($wins == 1 ? '.' : ($wins > 1 ? 's!' : 's.')));
	// show chat message
	$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $command['author']->login);
}  // chat_wins
?>
