<?php
/* vim: set noexpandtab tabstop=2 softtabstop=2 shiftwidth=2: */

/**
 * Chat plugin.
 * Displays help for public chat commands.
 * Updated by Xymph
 *
 * Dependencies: none
 */

Aseco::addChatCommand('help', 'Shows all available commands');
Aseco::addChatCommand('helpall', 'Displays help for available commands');

function chat_help($aseco, $command) {

	// show normal chat commands on command line
	showHelp($command['author'], $aseco->chat_commands, 'chat', false, false);

	// add extra explanation?
	if ($aseco->settings['help_explanation']) {
		$message = $aseco->getChatMessage('HELP_EXPLANATION');
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $command['author']->login);
	}
}  // chat_help

function chat_helpall($aseco, $command) {

	// display normal chat commands in popup with descriptions
	showHelp($command['author'], $aseco->chat_commands, 'chat', false, true, 0.3);
}  // chat_helpall
?>
