<?php
/* vim: set noexpandtab tabstop=2 softtabstop=2 shiftwidth=2: */

/**
 * Chat plugin.
 * Shows (file)names of current track's song & mod.
 * Created by Xymph
 *
 * Dependencies: none
 */

Aseco::addChatCommand('song', 'Shows filename of current track\'s song');
Aseco::addChatCommand('mod', 'Shows (file)name of current track\'s mod');

function chat_song($aseco, $command) {

	$player = $command['author'];

	// check for track's song
	if ($aseco->server->challenge->gbx->songFile) {
		$message = formatText($aseco->getChatMessage('SONG'),
		                      stripColors($aseco->server->challenge->name),
		                      $aseco->server->challenge->gbx->songFile);
		// use only first parameter
		$command['params'] = explode(' ', $command['params'], 2);
		if ((strtolower($command['params'][0]) == 'url' ||
		     strtolower($command['params'][0]) == 'loc') &&
		    $aseco->server->challenge->gbx->songUrl) {
			$message .= LF . '{#highlite}$l[' . $aseco->server->challenge->gbx->songUrl . ']' . $aseco->server->challenge->gbx->songUrl . '$l';
		}
	} else {
		$message = '{#server}> {#error}No track song found!';
		if ($aseco->server->getGame() == 'TMF' && function_exists('chat_music'))
			$message .= '  Try {#highlite}$i /music current {#error}instead.';
	}
	// show chat message
	$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $player->login);
}  // chat_song

function chat_mod($aseco, $command) {

	$player = $command['author'];

	// check for track's mod
	if ($aseco->server->challenge->gbx->modName) {
		$message = formatText($aseco->getChatMessage('MOD'),
		                      stripColors($aseco->server->challenge->name),
		                      $aseco->server->challenge->gbx->modName,
		                      $aseco->server->challenge->gbx->modFile);
		// use only first parameter
		$command['params'] = explode(' ', $command['params'], 2);
		if ((strtolower($command['params'][0]) == 'url' ||
		     strtolower($command['params'][0]) == 'loc') &&
		    $aseco->server->challenge->gbx->modUrl) {
			$message .= LF . '{#highlite}$l[' . $aseco->server->challenge->gbx->modUrl . ']' . $aseco->server->challenge->gbx->modUrl . '$l';
		}
	} else {
		$message = '{#server}> {#error}No track mod found!';
	}
	// show chat message
	$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $player->login);
}  // chat_mod
?>
