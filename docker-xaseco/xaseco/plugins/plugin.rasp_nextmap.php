<?php
/* vim: set noexpandtab tabstop=2 softtabstop=2 shiftwidth=2: */

/**
 * Nextmap plugin.
 * Shows the name of the next challenge.
 * Updated by Xymph & AssemblerManiac
 *
 * Dependencies: none
 */

Aseco::addChatCommand('nextmap', 'Shows name of the next challenge');

function chat_nextmap($aseco, $command) {
	global $rasp, $jukebox;

	$login = $command['author']->login;

	// check for relay server
	if ($aseco->server->isrelay) {
		$message = formatText($aseco->getChatMessage('NOTONRELAY'));
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
		return;
	}

	// check jukebox first
	if (!empty($jukebox)) {
		$jbtemp = $jukebox;
		$track = array_shift($jbtemp);
		$next = $track['Name'];
		// get environment if TMF/TMS/TMO
		if ($aseco->server->getGame() != 'TMN') {
			$aseco->client->query('GetChallengeInfo', $track['FileName']);
			$track = $aseco->client->getResponse();
			$env = $track['Environnement'];
		}
	} else {
		if ($aseco->server->getGame() != 'TMF') {
			// rewrite by AssemblerManiac with the current index
			$aseco->client->query('GetCurrentChallengeIndex');
			$current = $aseco->client->getResponse();
			// do GetChallengeList with the incremented index, this way we avoid looping through data to find the right track
			$aseco->client->resetError();
			$rtn = $aseco->client->query('GetChallengeList', 1, ++$current);
			$track = $aseco->client->getResponse();
			// if we try to get one more than really exists, we get a trappable error, so then get the first track
			if ($aseco->client->isError()) {
				$rtn = $aseco->client->query('GetChallengeList', 1, 0);
				$track = $aseco->client->getResponse();
			}
		} else {  // TMF
			$aseco->client->query('GetNextChallengeIndex');
			$next = $aseco->client->getResponse();
			$rtn = $aseco->client->query('GetChallengeList', 1, $next);
			$track = $aseco->client->getResponse();
		}
		$next = stripNewlines($track[0]['Name']);
		$env = $track[0]['Environnement'];
	}

	// show chat message
	if ($aseco->server->getGame() == 'TMF') {
		if ($aseco->server->packmask == 'Stadium')
			$message = formatText($rasp->messages['NEXTMAP'][0],
			                      stripColors($next));
		else
			$message = formatText($rasp->messages['NEXTENVMAP'][0],
			                      $env, stripColors($next));
	} elseif ($aseco->server->getGame() == 'TMN') {
		$message = formatText($rasp->messages['NEXTMAP'][0],
		                      stripColors($next));
	} else {  // TMS/TMO
		$message = formatText($rasp->messages['NEXTENVMAP'][0],
		                      $env, stripColors($next));
	}
	$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
}  // chat_nextmap
?>
