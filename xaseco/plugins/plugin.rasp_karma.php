<?php
/* vim: set noexpandtab tabstop=2 softtabstop=2 shiftwidth=2: */

/**
 * Karma plugin.
 * Votes for a track and displays current score of it.
 * Updated by Xymph
 *
 * Dependencies: used by plugin.rasp.php
 */

Aseco::registerEvent('onChat', 'check4Karma');
Aseco::registerEvent('onPlayerFinish', 'remind_onfinish');
Aseco::registerEvent('onEndRace', 'remind_onendrace');

Aseco::addChatCommand('karma', 'Shows karma for the current track {Track_ID}');
Aseco::addChatCommand('++', 'Increases karma for the current track');
Aseco::addChatCommand('--', 'Decreases karma for the current track');

// called @ onChat
function check4Karma($aseco, $chat) {
	global $rasp, $allow_public_karma;

	// if server message, bail out immediately
	if ($chat[0] == $aseco->server->id) return;

	// check for possible public karma vote
	if ($chat[2] == '++') {
		if ($allow_public_karma) {
			if ($command['author'] = $aseco->server->players->getPlayer($chat[1]))
				KarmaVote($aseco, $command, 1);
		} else {
			$message = $rasp->messages['KARMA_NOPUBLIC'][0];
			$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($message));
		}
	}
	elseif ($chat[2] == '--') {
		if ($allow_public_karma) {
			if ($command['author'] = $aseco->server->players->getPlayer($chat[1]))
				KarmaVote($aseco, $command, -1);
		} else {
			$message = $rasp->messages['KARMA_NOPUBLIC'][0];
			$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($message));
		}
	}
}  // check4Karma

function chat_plusplus($aseco, $command) {
	KarmaVote($aseco, $command, 1);
}  // chat_plusplus

function chat_dashdash($aseco, $command) {
	KarmaVote($aseco, $command, -1);
}  // chat_dashdash

function KarmaVote($aseco, $command, $vote) {
	global $rasp, $feature_karma, $karma_require_finish;

	// if karma system disabled, bail out immediately
	if (!$feature_karma) return;

	$login = $command['author']->login;
	$pid = $command['author']->id;

	// check for relay server
	if ($aseco->server->isrelay) {
		$message = formatText($aseco->getChatMessage('NOTONRELAY'));
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
		return;
	}

	// check if finishes are required
	if ($karma_require_finish > 0) {
		$query = 'SELECT id FROM rs_times
		          WHERE playerID=' . $pid . ' AND challengeID=' . $aseco->server->challenge->id;
		$res = mysql_query($query);
		// check whether player finished required number of times
		if (mysql_num_rows($res) < $karma_require_finish) {
			// show chat message
			$message = formatText($rasp->messages['KARMA_REQUIRE'][0],
			                      $karma_require_finish,
			                      ($karma_require_finish == 1 ? '' : 's'));
			$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
			mysql_free_result($res);
			return;
		} else {
			mysql_free_result($res);
		}
	}

	$command['params'] = '';  // clear sneaky params before chat_karma
	$query = 'SELECT Id, Score FROM rs_karma
	          WHERE PlayerId=' . $pid . ' AND ChallengeId=' . $aseco->server->challenge->id;
	$res = mysql_query($query);
	if (mysql_num_rows($res) > 0) {
		$row = mysql_fetch_object($res);
		if ($row->Score == $vote) {
			$message = $rasp->messages['KARMA_VOTED'][0];
			$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
		} else {
			$query2 = 'UPDATE rs_karma SET Score=' . $vote . ' WHERE Id=' . $row->Id;
			mysql_query($query2);
			if (mysql_affected_rows() < 1) {
				$message = $rasp->messages['KARMA_FAIL'][0];
				$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
			} else {
				$message = $rasp->messages['KARMA_CHANGE'][0];
				$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
				chat_karma($aseco, $command);
				$aseco->releaseEvent('onKarmaChange', getKarmaValues($aseco->server->challenge->id));
			}
		}
	} else {
		$query2 = 'INSERT INTO rs_karma (Score, PlayerId, ChallengeId)
		           VALUES (' . $vote . ', ' . $pid . ', ' . $aseco->server->challenge->id . ')';
		mysql_query($query2);
		if (mysql_affected_rows() < 1) {
			$message = $rasp->messages['KARMA_FAIL'][0];
			$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
		} else {
			$message = $rasp->messages['KARMA_DONE'][0];
			$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
			chat_karma($aseco, $command);
			$aseco->releaseEvent('onKarmaChange', getKarmaValues($aseco->server->challenge->id));
		}
	}
	mysql_free_result($res);
}  // KarmaVote

// Show to a player if $login defined, otherwise show to all players.
function rasp_karma($cid, $login) {
	global $aseco, $rasp;

	$karma = getKarma($cid, $login);
	$message = formatText($rasp->messages['KARMA'][0], $karma);

	// show chat message
	if ($login) {
		// strip 1 leading '>' to indicate a player message instead of system-wide
		$message = str_replace('{#server}>> ', '{#server}> ', $message);
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
	} else {
		$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($message));
	}
}  // rasp_karma

function chat_karma($aseco, $command) {
	global $rasp, $feature_karma;

	// if karma system disabled, bail out immediately
	if (!$feature_karma) return;

	$player = $command['author'];
	$login = $player->login;

	// check optional parameter
	$param = $command['params'];
	if (is_numeric($param) && $param >= 0) {
		if (empty($player->tracklist)) {
			$message = $rasp->messages['LIST_HELP'][0];
			$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
			return;
		}
		$jid = ltrim($param, '0');
		$jid--;
		// find track by given #
		if (array_key_exists($jid, $player->tracklist)) {
			$uid = $player->tracklist[$jid]['uid'];

			// get track ID and name
			$query = 'SELECT Id,Name FROM challenges
			          WHERE Uid=' . quotedString($uid);
			$res = mysql_query($query);
			$row = mysql_fetch_object($res);
			mysql_free_result($res);

			$karma = getKarma($row->Id, $login);
			$message = formatText($rasp->messages['KARMA_TRACK'][0],
			                      stripColors($row->Name), $karma);
		} else {
			$message = $rasp->messages['JUKEBOX_NOTFOUND'][0];
			$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
			return;
		}
	} else {
		// get karma info
		$karma = getKarma($aseco->server->challenge->id, $login);
		$message = formatText($rasp->messages['KARMA'][0], $karma);
	}

	// strip 1 leading '>' to indicate a player message instead of system-wide
	$message = str_replace('{#server}>> ', '{#server}> ', $message);
	// show chat message
	$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
}  // chat_karma

function getKarmaValues($cid) {

	// get vote sum and count
	$query = 'SELECT SUM(score) AS karma, COUNT(score) AS total FROM rs_karma
	          WHERE ChallengeId=' . $cid;
	$res = mysql_query($query);
	if (mysql_num_rows($res) == 1) {
		$row = mysql_fetch_object($res);
		$karma = $row->karma;
		$total = $row->total;
		mysql_free_result($res);

		// get vote counts & percentages
		if ($total > 0) {
			$query2 = 'SELECT
			            (SELECT COUNT(*) FROM rs_karma WHERE score > 0
			             AND challengeid = karma.challengeid) AS plus,
			            (SELECT COUNT(*) FROM rs_karma WHERE score < 0
			             AND challengeid = karma.challengeid) AS minus
			           FROM rs_karma karma
			           WHERE challengeid=' . $cid . '
			           GROUP BY challengeid';
			$res2 = mysql_query($query2);
			if (mysql_num_rows($res2) == 1) {
				$row2 = mysql_fetch_object($res2);
				$plus = $row2->plus;
				$minus = $row2->minus;
			} else {
				$plus = 0;
				$minus = 0;
			}
			mysql_free_result($res2);
			return array('Karma' => $karma, 'Total' => $total,
			             'Good' => $plus, 'Bad' => $minus,
			             'GoodPct' => $plus / $total * 100,
			             'BadPct' => $minus / $total * 100);
		}
	}
	return array('Karma' => 0, 'Total' => 0,
	             'Good' => 0, 'Bad' => 0,
	             'GoodPct' => 0.0, 'BadPct' => 0.0);
}  // getKarmaValues

function getKarma($cid, $login) {
	global $aseco, $rasp, $karma_show_details, $karma_show_votes;

	$karmavalues = getKarmaValues($cid);
	$karma = $karmavalues['Karma'];
	$total = $karmavalues['Total'];
	$plus = $karmavalues['Good'];
	$minus = $karmavalues['Bad'];
	$pluspct = $karmavalues['GoodPct'];
	$minuspct = $karmavalues['BadPct'];

	// optionally add vote counts & percentages
	if ($karma_show_details) {
		if ($total > 0) {
			$karma = formatText($rasp->messages['KARMA_DETAILS'][0],
			                    $karma,
			                    $plus, round($pluspct),
			                    $minus, round($minuspct));
		} else {  // no votes yet
			$karma = formatText($rasp->messages['KARMA_DETAILS'][0],
			                    $karma, 0, 0, 0, 0);
		}
	}

	// optionally add player's actual vote
	if ($karma_show_votes) {
		$pid = $aseco->getPlayerId($login);
		if ($pid != 0) {
			$query3 = 'SELECT Score FROM rs_karma
			           WHERE PlayerId=' . $pid . ' AND ChallengeId=' . $cid;
			$res3 = mysql_query($query3);
			if (mysql_num_rows($res3) > 0) {
				$row3 = mysql_fetch_object($res3);
				if ($row3->Score == 1) {
					$vote = '++';
				} else {  // -1
					$vote = '--';
				}
			} else {
				$vote = 'none';
			}
			$karma .= formatText($rasp->messages['KARMA_VOTE'][0], $vote);
			mysql_free_result($res3);
		}
	}
	return $karma;
}  // getKarma

// called @ onPlayerFinish
function remind_onfinish($aseco, $finish_item) {
	global $rasp, $feature_karma, $remind_karma;

	// if no finish reminders, bail out immediately
	if (!$feature_karma || $remind_karma != 2) return;

	// if no actual finish, bail out too
	if ($finish_item->score == 0) return;

	// check whether player already voted
	$query = 'SELECT Id, Score FROM rs_karma
	          WHERE PlayerId=' . $finish_item->player->id . ' AND ChallengeId=' . $aseco->server->challenge->id;
	$res = mysql_query($query);
	if (mysql_num_rows($res) == 0) {
		// show reminder message
		$message = $rasp->messages['KARMA_REMIND'][0];
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $finish_item->player->login);
	}
	mysql_free_result($res);
}  // remind_onfinish

// called @ onEndRace
function remind_onendrace($aseco, $data) {
	global $rasp, $feature_karma, $remind_karma;

	// if no end race reminders, bail out immediately
	if (!$feature_karma || $remind_karma != 1) return;

	// check all connected players
	foreach ($aseco->server->players->player_list as $player) {
		// get current player status
		if (!$aseco->isSpectator($player)) {
			// check whether player already voted
			$query = 'SELECT Id, Score FROM rs_karma
			          WHERE PlayerId=' . $player->id . ' AND ChallengeId=' . $aseco->server->challenge->id;
			$res = mysql_query($query);
			if (mysql_num_rows($res) == 0) {
				// show reminder message
				$message = $rasp->messages['KARMA_REMIND'][0];
				$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $player->login);
			}
			mysql_free_result($res);
		}
	}
}  // remind_onendrace
?>
