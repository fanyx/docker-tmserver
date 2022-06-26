<?php
/* vim: set noexpandtab tabstop=2 softtabstop=2 shiftwidth=2: */

/**
 * Chat plugin.
 * Shows last online info.
 * Created by Xymph
 *
 * Dependencies: none
 */

Aseco::addChatCommand('laston', 'Shows when a player was last online');

function chat_laston($aseco, $command) {

	$player = $command['author'];
	$target = $player;

	// get player login or ID
	if ($command['params'] != '')
		if (!$target = $aseco->getPlayerParam($player, $command['params'], true))
			return;

	// obtain last online timestamp
	$query = 'SELECT UpdatedAt FROM players
	          WHERE login=' . quotedString($target->login);
	$result = mysql_query($query);
	$laston = mysql_fetch_row($result);
	mysql_free_result($result);

	// show chat message (strip seconds off timestamp)
	$message = '{#server}> Player {#highlite}' . $target->nickname .
	           '$z$s{#server} was last online on: {#highlite}' .
	           preg_replace('/:\d\d$/', '', $laston[0]);
	$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $player->login);
}  // chat_laston
?>
