<?php
/* vim: set noexpandtab tabstop=2 softtabstop=2 shiftwidth=2: */

/**
 * Rounds plugin.
 * Reports finishes in each individual round.
 * Created by Xymph
 * Sorting improved by .anDy
 *
 * Dependencies: none
 */

Aseco::registerEvent('onSync', 'reset_rounds');
Aseco::registerEvent('onNewChallenge', 'reset_rounds');
Aseco::registerEvent('onRestartChallenge', 'reset_rounds');
Aseco::registerEvent('onEndRound', 'report_round');
Aseco::registerEvent('onPlayerFinish', 'store_time');

global $rounds_count, $round_times, $round_pbs;

// called @ onSync, onNewChallenge, onRestartChallenge
function reset_rounds($aseco, $data) {
	global $rounds_count, $round_times, $round_pbs;

	// reset counter, times & PBs
	$rounds_count = 0;
	$round_times = array();
	$round_pbs = array();
}  // reset_rounds

// called @ onEndRound
function report_round($aseco) {
	global $rounds_count, $round_times, $round_pbs;

	// if someone finished (in Rounds/Team/Cup mode), then report this round
	if (!empty($round_times)) {
		$rounds_count++;
		// sort by times, PBs & PIDs
		$round_scores = array();

		ksort($round_times);
		foreach ($round_times as &$item){
			// sort only times which were driven more than once
			if (count($item) > 1) {
				$scores = array();
				$pbs = array();
				$pids = array();
				foreach ($item as $key => &$row) {
					$scores[$key] = $row['score'];
					$pbs[$key] = $round_pbs[$row['login']];
					$pids[$key] = $row['playerid'];
				}
				// sort order: SCORE, PB and PID, like the game does
				array_multisort($scores, SORT_NUMERIC, $pbs, SORT_NUMERIC, $pids, SORT_NUMERIC, $item);
			}
			// merge all score arrays
			$round_scores = array_merge($round_scores, $item);
		}

		$pos = 1;
		$message = formatText($aseco->getChatMessage('ROUND'), $rounds_count);

		// report all new records, first 'show_min_recs' w/ time, rest w/o
		foreach ($round_scores as $tm) {
			// check if player still online
			if ($player = $aseco->server->players->getPlayer($tm['login']))
				$nick = stripColors($player->nickname);
			else  // fall back on login
				$nick = $tm['login'];
			$new = false;

			// go through each record
			for ($i = 0; $i < $aseco->server->records->count(); $i++) {
				$cur_record = $aseco->server->records->getRecord($i);

				// if the record is new on this track then check if it's in this round
				if ($cur_record->new && $cur_record->player->login == $tm['login'] && $cur_record->score == $tm['score']) {
					$new = true;
					break;
				}
			}

			if ($new) {
				$message .= formatText($aseco->getChatMessage('RANKING_RECORD_NEW'),
				                       $pos, $nick, formatTime($tm['score']));
			} elseif ($pos <= $aseco->settings['show_min_recs']) {
				$message .= formatText($aseco->getChatMessage('RANKING_RECORD'),
				                       $pos, $nick, formatTime($tm['score']));
			} else {
				$message .= formatText($aseco->getChatMessage('RANKING_RECORD2'),
				                       $pos, $nick);
			}
			$pos++;
		}

		// show chat message
		$message = substr($message, 0, strlen($message)-2);  // strip trailing ", "
		$message = $aseco->formatColors($message);
		$aseco->console_text(stripColors($message, false));
		if ($aseco->server->getGame() == 'TMF' && $aseco->settings['rounds_in_window'] &&
		    function_exists('send_window_message'))
			send_window_message($aseco, $message, false);
		else
			$aseco->client->query('ChatSendServerMessage', $message);

		// reset times
		$round_times = array();
	}
}  // report_round

// called @ onPlayerFinish
function store_time($aseco, $finish_item) {
	global $round_times, $round_pbs;

	// if Rounds/Team/Cup mode & actual finish, then store time & PB
	if (($aseco->server->gameinfo->mode == Gameinfo::RNDS ||
	     $aseco->server->gameinfo->mode == Gameinfo::TEAM ||
	     $aseco->server->gameinfo->mode == Gameinfo::CUP) && $finish_item->score > 0) {
		$round_times[$finish_item->score][] = array(
			'playerid' => $finish_item->player->pid,
			'login' => $finish_item->player->login,
			'score' => $finish_item->score,
		);
		if (isset($round_pbs[$finish_item->player->login])) {
			if ($round_pbs[$finish_item->player->login] > $finish_item->score) {
				$round_pbs[$finish_item->player->login] = $finish_item->score;
			}
		} else {
			$round_pbs[$finish_item->player->login] = $finish_item->score;
		}
	}
}  // store_time
?>
