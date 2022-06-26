<?php
/* vim: set noexpandtab tabstop=2 softtabstop=2 shiftwidth=2: */

/**
 * Chat plugin.
 * Shows ranked records and their relations on the current track.
 * Created by Xymph
 *
 * Dependencies: none
 */

if (!INHIBIT_RECCMDS) {
	Aseco::addChatCommand('firstrec', 'Shows first ranked record on current track');
	Aseco::addChatCommand('lastrec', 'Shows last ranked record on current track');
	Aseco::addChatCommand('nextrec', 'Shows next better ranked record to beat');
	Aseco::addChatCommand('diffrec', 'Shows your difference to first ranked record');
	Aseco::addChatCommand('recrange', 'Shows difference first to last ranked record');
}

function chat_firstrec($aseco, $command) {

	$login = $command['author']->login;

	// check for relay server
	if ($aseco->server->isrelay) {
		$message = formatText($aseco->getChatMessage('NOTONRELAY'));
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
		return;
	}

	if ($aseco->server->records->count() > 0) {
		// get the first ranked record
		$record = $aseco->server->records->getRecord(0);

		// show chat message
		$message = formatText($aseco->getChatMessage('FIRST_RECORD'))
		         . formatText($aseco->getChatMessage('RANKING_RECORD_NEW'),
		                                             1,
		                                             stripColors($record->player->nickname),
		                                             ($aseco->server->gameinfo->mode == Gameinfo::STNT ?
		                                              $record->score : formatTime($record->score)));

		$message = substr($message, 0, strlen($message)-2);  // strip trailing ", "
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
	} else {
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors('{#server}> {#error}No records found!'), $login);
	}
}  // chat_firstrec

function chat_lastrec($aseco, $command) {

	$login = $command['author']->login;

	// check for relay server
	if ($aseco->server->isrelay) {
		$message = formatText($aseco->getChatMessage('NOTONRELAY'));
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
		return;
	}

	if ($total = $aseco->server->records->count()) {
		// get the last ranked record
		$record = $aseco->server->records->getRecord($total-1);

		// show chat message
		$message = formatText($aseco->getChatMessage('LAST_RECORD'))
		         . formatText($aseco->getChatMessage('RANKING_RECORD_NEW'),
		                                             $total,
		                                             stripColors($record->player->nickname),
		                                             ($aseco->server->gameinfo->mode == Gameinfo::STNT ?
		                                              $record->score : formatTime($record->score)));

		$message = substr($message, 0, strlen($message)-2);  // strip trailing ", "
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
	} else {
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors('{#server}> {#error}No records found!'), $login);
	}
}  // chat_lastrec

function chat_nextrec($aseco, $command) {

	$login = $command['author']->login;

	// check for relay server
	if ($aseco->server->isrelay) {
		$message = formatText($aseco->getChatMessage('NOTONRELAY'));
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
		return;
	}

	if ($total = $aseco->server->records->count()) {
		$found = false;
		// find ranked record
		for ($i = 0; $i < $total; $i++) {
			$rec = $aseco->server->records->getRecord($i);
			if ($rec->player->login == $login) {
				$rank = $i;
				$found = true;
				break;
			}
		}

		if ($found) {
			// get current and next better ranked records
			$nextrank = ($rank > 0 ? $rank-1 : 0);
			$record = $aseco->server->records->getRecord($rank);
			$next = $aseco->server->records->getRecord($nextrank);

			// compute difference to next record
			if ($aseco->server->gameinfo->mode != Gameinfo::STNT) {
				$diff = $record->score - $next->score;
				$sec = floor($diff/1000);
				$hun = ($diff - ($sec * 1000)) / 10;
			} else {  // Stunts mode
				$diff = $next->score - $record->score;
			}

			// show chat message
			$message1 = formatText($aseco->getChatMessage('RANKING_RECORD_NEW'),
			                                              $rank+1,
			                                              stripColors($record->player->nickname),
			                                              ($aseco->server->gameinfo->mode == Gameinfo::STNT ?
			                                               $record->score : formatTime($record->score)));
			$message1 = substr($message1, 0, strlen($message1)-2);  // strip trailing ", "
			$message2 = formatText($aseco->getChatMessage('RANKING_RECORD_NEW'),
			                                              $nextrank+1,
			                                              stripColors($next->player->nickname),
			                                              ($aseco->server->gameinfo->mode == Gameinfo::STNT ?
			                                               $record->score : formatTime($next->score)));
			$message2 = substr($message2, 0, strlen($message2)-2);  // strip trailing ", "
			$message = formatText($aseco->getChatMessage('DIFF_RECORD'),
			                      $message1, $message2,
			                      ($aseco->server->gameinfo->mode == Gameinfo::STNT ?
			                       $diff : sprintf("%d.%02d", $sec, $hun)));

			$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
		} else {
			// look for unranked time instead
			$order = ($aseco->server->gameinfo->mode == Gameinfo::STNT ? 'DESC' : 'ASC');
			$query = 'SELECT score FROM rs_times
			          WHERE playerID=' . $command['author']->id . ' AND
			                challengeID=' . $aseco->server->challenge->id . '
			          ORDER BY score ' . $order . ' LIMIT 1';
			$result = mysql_query($query);
			if (mysql_num_rows($result) > 0) {
				$unranked = mysql_fetch_object($result);
				$found = true;
			}
			mysql_free_result($result);

			if ($found) {
				// get the last ranked record
				$last = $aseco->server->records->getRecord($total-1);

				// compute difference to next record
				if ($aseco->server->gameinfo->mode != Gameinfo::STNT) {
					$diff = $unranked->score - $last->score;
					$sec = floor($diff/1000);
					$hun = ($diff - ($sec * 1000)) / 10;
				} else {  // Stunts mode
					$diff = $last->score - $unranked->score;
				}

				// show chat message
				$message1 = formatText($aseco->getChatMessage('RANKING_RECORD_NEW'),
				                                              'PB',
				                                              stripColors($command['author']->nickname),
				                                              ($aseco->server->gameinfo->mode == Gameinfo::STNT ?
				                                               $unranked->score : formatTime($unranked->score)));
				$message1 = substr($message1, 0, strlen($message1)-2);  // strip trailing ", "
				$message2 = formatText($aseco->getChatMessage('RANKING_RECORD_NEW'),
				                                              $total,
				                                              stripColors($last->player->nickname),
				                                              ($aseco->server->gameinfo->mode == Gameinfo::STNT ?
				                                               $last->score : formatTime($last->score)));
				$message2 = substr($message2, 0, strlen($message2)-2);  // strip trailing ", "
				$message = formatText($aseco->getChatMessage('DIFF_RECORD'),
				                      $message1, $message2,
				                      ($aseco->server->gameinfo->mode == Gameinfo::STNT ?
				                       $diff : sprintf("%d.%02d", $sec, $hun)));

				$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
			} else {
				$message = '{#server}> {#error}You don\'t have a record on this track yet... use {#highlite}$i/lastrec';
				$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
			}
		}
	} else {
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors('{#server}> {#error}No records found!'), $login);
	}
}  // chat_nextrec

function chat_diffrec($aseco, $command) {

	$login = $command['author']->login;

	// check for relay server
	if ($aseco->server->isrelay) {
		$message = formatText($aseco->getChatMessage('NOTONRELAY'));
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
		return;
	}

	if ($total = $aseco->server->records->count()) {
		$found = false;
		// find ranked record
		for ($i = 0; $i < $total; $i++) {
			$rec = $aseco->server->records->getRecord($i);
			if ($rec->player->login == $login) {
				$rank = $i;
				$found = true;
				break;
			}
		}

		if ($found) {
			// get current and first ranked records
			$record = $aseco->server->records->getRecord($rank);
			$first = $aseco->server->records->getRecord(0);

			// compute difference to first record
			if ($aseco->server->gameinfo->mode != Gameinfo::STNT) {
				$diff = $record->score - $first->score;
				$sec = floor($diff/1000);
				$hun = ($diff - ($sec * 1000)) / 10;
			} else {  // Stunts mode
				$diff = $first->score - $record->score;
			}

			// show chat message
			$message1 = formatText($aseco->getChatMessage('RANKING_RECORD_NEW'),
			                                              $rank+1,
			                                              stripColors($record->player->nickname),
			                                              ($aseco->server->gameinfo->mode == Gameinfo::STNT ?
			                                               $record->score : formatTime($record->score)));
			$message1 = substr($message1, 0, strlen($message1)-2);  // strip trailing ", "
			$message2 = formatText($aseco->getChatMessage('RANKING_RECORD_NEW'),
			                                              1,
			                                              stripColors($first->player->nickname),
			                                              ($aseco->server->gameinfo->mode == Gameinfo::STNT ?
			                                               $first->score : formatTime($first->score)));
			$message2 = substr($message2, 0, strlen($message2)-2);  // strip trailing ", "
			$message = formatText($aseco->getChatMessage('DIFF_RECORD'),
			                      $message1, $message2,
			                      ($aseco->server->gameinfo->mode == Gameinfo::STNT ?
			                       $diff : sprintf("%d.%02d", $sec, $hun)));

			$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
		} else {
			$message = '{#server}> {#error}You don\'t have a record on this track yet... use {#highlite}$i/lastrec';
			$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
		}
	} else {
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors('{#server}> {#error}No records found!'), $login);
	}
}  // chat_diffrec

function chat_recrange($aseco, $command) {

	$login = $command['author']->login;

	// check for relay server
	if ($aseco->server->isrelay) {
		$message = formatText($aseco->getChatMessage('NOTONRELAY'));
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
		return;
	}

	if ($total = $aseco->server->records->count()) {
		// get the first & last ranked records
		$first = $aseco->server->records->getRecord(0);
		$last = $aseco->server->records->getRecord($total-1);

		// compute difference between records
		if ($aseco->server->gameinfo->mode != Gameinfo::STNT) {
			$diff = $last->score - $first->score;
			$sec = floor($diff/1000);
			$hun = ($diff - ($sec * 1000)) / 10;
		} else {  // Stunts mode
			$diff = $first->score - $last->score;
		}

		// show chat message
		$message1 = formatText($aseco->getChatMessage('RANKING_RECORD_NEW'),
		                                              1,
		                                              stripColors($first->player->nickname),
		                                              ($aseco->server->gameinfo->mode == Gameinfo::STNT ?
		                                               $first->score : formatTime($first->score)));
		$message1 = substr($message1, 0, strlen($message1)-2);  // strip trailing ", "
		$message2 = formatText($aseco->getChatMessage('RANKING_RECORD_NEW'),
		                                              $total,
		                                              stripColors($last->player->nickname),
		                                              ($aseco->server->gameinfo->mode == Gameinfo::STNT ?
		                                               $last->score : formatTime($last->score)));
		$message2 = substr($message2, 0, strlen($message2)-2);  // strip trailing ", "
		$message = formatText($aseco->getChatMessage('DIFF_RECORD'),
		                      $message1, $message2,
		                      ($aseco->server->gameinfo->mode == Gameinfo::STNT ?
		                       $diff : sprintf("%d.%02d", $sec, $hun)));

		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
	} else {
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors('{#server}> {#error}No records found!'), $login);
	}
}  // chat_recrange
?>
