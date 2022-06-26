<?php
/* vim: set noexpandtab tabstop=2 softtabstop=2 shiftwidth=2: */

/**
 * Votes plugin.
 * Provides sophisticated chat-based voting features, similar to
 * (and fully integrated with) TMX /add votes.
 * Created by Xymph
 *
 * Dependencies: requires plugin.rasp_jukebox.php, plugin.track.php, plugin.panels.php;
 *               used by plugin.rasp_jukebox.php
 */

Aseco::registerEvent('onSync', 'init_votes');
Aseco::registerEvent('onSync', 'reset_votes');
Aseco::registerEvent('onEndRace1', 'reset_votes');  // use pre event before all other processing
Aseco::registerEvent('onNewChallenge2', 'enable_votes');
Aseco::registerEvent('onPlayerConnect', 'explain_votes');
Aseco::registerEvent('onPlayerDisconnect', 'cancel_kick');
Aseco::registerEvent('onEndRound', 'r_expire_votes');
Aseco::registerEvent('onCheckpoint', 'ta_expire_votes');

Aseco::addChatCommand('helpvote', 'Displays info about the chat-based votes');
Aseco::addChatCommand('votehelp', 'Displays info about the chat-based votes');
Aseco::addChatCommand('endround', 'Starts a vote to end current round');
Aseco::addChatCommand('ladder', 'Starts a vote to restart track for ladder');
Aseco::addChatCommand('replay', 'Starts a vote to replay this track');
Aseco::addChatCommand('skip', 'Starts a vote to skip this track');
Aseco::addChatCommand('ignore', 'Starts a vote to ignore a player');
Aseco::addChatCommand('kick', 'Starts a vote to kick a player');
Aseco::addChatCommand('cancel', 'Cancels your current vote');

// called @ onSync
function init_votes($aseco, $data) {
	global $feature_votes, $plrvotes, $global_explain, $vote_ratios, $vote_in_window,
	       $allow_spec_startvote, $allow_spec_voting, $disable_upon_admin, $disable_while_sb,
	       $allow_kickvotes, $allow_admin_kick, $allow_ignorevotes, $allow_admin_ignore,
	       $disabled_scoreboard, $ladder_fast_restart, $auto_vote_starter,
	       $r_expire_limit, $r_show_reminder, $r_points_limits,
	       $r_ladder_max, $r_replay_min, $r_skip_max,
	       $ta_expire_limit, $ta_show_reminder, $ta_show_interval, $ta_time_limits,
	       $ta_ladder_max, $ta_replay_min, $ta_skip_max,
	       $max_laddervotes, $max_replayvotes, $max_skipvotes,
	       $replays_limit, $replays_counter;

	// init votes
	$plrvotes = array();
	$replays_counter = 0;

	// load configuration settings
	include('includes/votes.config.php');
}  // init_votes

// called @ onSync, onEndRace1
function reset_votes($aseco, $data) {
	global $rasp, $chatvote, $num_laddervotes, $num_replayvotes, $num_skipvotes,
	       $disable_while_sb, $disabled_scoreboard, $vote_in_window;

	// check for ongoing chat vote
	if (!empty($chatvote)) {
		$aseco->console('Vote by {1} to {2} reset!',
		                $chatvote['login'], $chatvote['desc']);
		$message = $rasp->messages['VOTE_CANCEL'][0];
		if ($vote_in_window && function_exists('send_window_message'))
			send_window_message($aseco, $message, false);
		else
			$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($message));
		$chatvote = array();  // $tmxadd is already reset in rasp_newtrack()
		// disable all vote panels
		if ($aseco->server->getGame() == 'TMF')
			allvotepanels_off($aseco);
	}

	// reset counters
	$num_laddervotes = 0;
	$num_replayvotes = 0;
	$num_skipvotes = 0;

	// disable voting during scoreboard?
	if ($disable_while_sb)
		$disabled_scoreboard = true;
}  // reset_votes

// called @ onNewChallenge2
function enable_votes($aseco, $data) {
	global $disabled_scoreboard;

	// always enable voting after scoreboard
	$disabled_scoreboard = false;
}  // enable_votes

// called @ onPlayerConnect
function explain_votes($aseco, $player) {
	global $rasp, $feature_votes, $global_explain, $vote_in_window;

	// if starting up, bail out immediately
	if ($aseco->startup_phase) return;

	// check for active voting system
	if ($feature_votes) {
		// show info message
		$message = $rasp->messages['VOTE_EXPLAIN'][0];

		// check for global explanation
		if ($global_explain == 2) {
			if ($vote_in_window && function_exists('send_window_message'))
				send_window_message($aseco, $message, false);
			else
				$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($message));
		}
		elseif ($global_explain == 1) {  // just to the new player
			// strip 1 leading '>' to indicate a player message instead of system-wide
			$message = str_replace('{#server}>> ', '{#server}> ', $message);
			$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $player->login);
		}  // == 0, no explanation
	}
}  // explain_votes

// called @ onPlayerDisconnect
function cancel_kick($aseco, $player) {
	global $rasp, $feature_votes, $chatvote, $vote_in_window;

	// check for ongoing vote
	if ($feature_votes && !empty($chatvote)) {
		// check for vote to kick this player
		if ($chatvote['type'] == 4 && $chatvote['target'] == $player->login) {
			$aseco->console('Vote by {1} to {2} reset!',
			                $chatvote['login'], $chatvote['desc']);
			$message = $rasp->messages['VOTE_CANCEL'][0];
			if ($vote_in_window && function_exists('send_window_message'))
				send_window_message($aseco, $message, false);
			else
				$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($message));
			$chatvote = array();
			// disable all vote panels
			if ($aseco->server->getGame() == 'TMF')
				allvotepanels_off($aseco);
		}
	}
}  // cancel_kick

// called @ onEndRound
function r_expire_votes($aseco) {
	global $rasp, $tmxadd, $chatvote, $jukebox_in_window, $vote_in_window,
	       $r_expire_limit, $r_expire_num, $r_show_reminder;

	// in TimeAttack/Laps/Stunts modes, bail out immediately
	// (ignoring the 1 EndRound event that happens at the end of the track)
	if ($aseco->server->gameinfo->mode == Gameinfo::TA ||
	    $aseco->server->gameinfo->mode == Gameinfo::LAPS ||
	    $aseco->server->gameinfo->mode == Gameinfo::STNT) return;

	// expire an /endround vote immediately
	if (!empty($chatvote) && $chatvote['type'] == 0) {
		$message = formatText($rasp->messages['VOTE_END'][0],
		                      $chatvote['desc'],
		                      'expired', 'Server');
		if ($vote_in_window && function_exists('send_window_message'))
			send_window_message($aseco, $message, false);
		else
			$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($message));
		$chatvote = array();
		// disable all vote panels
		if ($aseco->server->getGame() == 'TMF')
			allvotepanels_off($aseco);
		return;
	}

	// check for ongoing chat or TMX vote
	if (!empty($chatvote) || !empty($tmxadd)) {
		// check for expiration limit
		$expire_limit = !empty($tmxadd) ? $r_expire_limit[5]  // /add
		                                : $r_expire_limit[$chatvote['type']];
		if (++$r_expire_num >= $expire_limit) {
			// check for type of vote
			if (!empty($chatvote)) {
				$aseco->console('Vote by {1} to {2} expired!',
				                $chatvote['login'], $chatvote['desc']);
				$message = formatText($rasp->messages['VOTE_END'][0],
				                      $chatvote['desc'],
				                      'expired', 'Server');
				if ($vote_in_window && function_exists('send_window_message'))
					send_window_message($aseco, $message, false);
				else
					$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($message));
				$chatvote = array();
				// disable all vote panels
				if ($aseco->server->getGame() == 'TMF')
					allvotepanels_off($aseco);
			} else {  // !empty($tmxadd)
				$aseco->console('Vote by {1} to add {2} expired!',
				                $tmxadd['login'], stripColors($tmxadd['name'], false));
				$message = formatText($rasp->messages['JUKEBOX_END'][0],
				                      stripColors($tmxadd['name']),
				                      'expired', 'Server');
				if ($jukebox_in_window && function_exists('send_window_message'))
					send_window_message($aseco, $message, false);
				else
					$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($message));
				$tmxadd = array();
				// disable all vote panels
				if ($aseco->server->getGame() == 'TMF')
					allvotepanels_off($aseco);
			}
		} else {
			// optionally remind players to vote
			if ($r_show_reminder) {
				// check for type of vote
				if (!empty($chatvote)) {
					$message = formatText($rasp->messages['VOTE_Y'][0],
					                      $chatvote['votes'],
					                      ($chatvote['votes'] == 1 ? '' : 's'),
					                      $chatvote['desc']);
					if ($vote_in_window && function_exists('send_window_message'))
						send_window_message($aseco, $message, false);
					else
						$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($message));
				} else {  // !empty($tmxadd)
					$message = formatText($rasp->messages['JUKEBOX_Y'][0],
					                      $tmxadd['votes'],
					                      ($tmxadd['votes'] == 1 ? '' : 's'),
					                      stripColors($tmxadd['name']));
					if ($jukebox_in_window && function_exists('send_window_message'))
						send_window_message($aseco, $message, false);
					else
						$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($message));
				}
			}
		}
	}
}  // r_expire_votes

// called @ onCheckpoint
function ta_expire_votes($aseco, $data) {
	global $rasp, $tmxadd, $chatvote, $jukebox_in_window, $vote_in_window,
	       $ta_expire_limit, $ta_expire_start,
	       $ta_show_reminder, $ta_show_interval, $ta_show_num;

	// in Rounds/Team/Cup modes, bail out immediately
	if ($aseco->server->gameinfo->mode == Gameinfo::RNDS ||
	    $aseco->server->gameinfo->mode == Gameinfo::TEAM ||
	    $aseco->server->gameinfo->mode == Gameinfo::CUP) return;

	// check for ongoing chat or TMX vote
	if (!empty($chatvote) || !empty($tmxadd)) {
		// check for expiration limit
		$expire_limit = !empty($tmxadd) ? $ta_expire_limit[5]  // add
		                                : $ta_expire_limit[$chatvote['type']];
		$played = time_playing($aseco);  // from plugin.track.php
		if (($played - $ta_expire_start) >= $expire_limit) {
			// check for type of vote
			if (!empty($chatvote)) {
				$aseco->console('Vote by {1} to {2} expired!',
				                $chatvote['login'], $chatvote['desc']);
				$message = formatText($rasp->messages['VOTE_END'][0],
				                      $chatvote['desc'],
				                      'expired', 'Server');
				if ($vote_in_window && function_exists('send_window_message'))
					send_window_message($aseco, $message, false);
				else
					$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($message));
				$chatvote = array();
				// disable all vote panels
				if ($aseco->server->getGame() == 'TMF')
					allvotepanels_off($aseco);
			} else {  // !empty($tmxadd)
				$aseco->console('Vote by {1} to add {2} expired!',
				                $tmxadd['login'], stripColors($tmxadd['name'], false));
				$message = formatText($rasp->messages['JUKEBOX_END'][0],
				                      stripColors($tmxadd['name']),
				                      'expired', 'Server');
				if ($jukebox_in_window && function_exists('send_window_message'))
					send_window_message($aseco, $message, false);
				else
					$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($message));
				$tmxadd = array();
				// disable all vote panels
				if ($aseco->server->getGame() == 'TMF')
					allvotepanels_off($aseco);
			}
		} else {
			// optionally remind players to vote
			if ($ta_show_reminder) {
				// compute how many $ta_show_interval's have passed
				$intervals = floor(($played - $ta_expire_start) / $ta_show_interval);
				// check whether this is more than the previous interval count
				if ($intervals > $ta_show_num) {
					// remember new interval count
					$ta_show_num = $intervals;
					// check for type of vote
					if (!empty($chatvote)) {
						$message = formatText($rasp->messages['VOTE_Y'][0],
						                      $chatvote['votes'],
						                      ($chatvote['votes'] == 1 ? '' : 's'),
						                      $chatvote['desc']);
						if ($vote_in_window && function_exists('send_window_message'))
							send_window_message($aseco, $message, false);
						else
							$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($message));
					} else {  // !empty($tmxadd)
						$message = formatText($rasp->messages['JUKEBOX_Y'][0],
						                      $tmxadd['votes'],
						                      ($tmxadd['votes'] == 1 ? '' : 's'),
						                      stripColors($tmxadd['name']));
						if ($jukebox_in_window && function_exists('send_window_message'))
							send_window_message($aseco, $message, false);
						else
							$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($message));
					}
				}
			}
		}
	}
}  // ta_expire_votes


function chat_votehelp($aseco, $command) { chat_helpvote($aseco, $command); }
function chat_helpvote($aseco, $command) {
	global $rasp, $feature_votes, $vote_ratios, $allow_kickvotes, $allow_ignorevotes;

	$login = $command['author']->login;

	// check for active voting system
	if (!$feature_votes) {
		$message = $rasp->messages['NO_VOTE'][0];
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
		return;
	}

	if ($aseco->server->getGame() == 'TMN') {
		// compile & display help message
		$help = '{#vote}Chat-based votes$g are available for these actions:' . LF;
		$help .= '$nRatio $m {#black}Command$g' . LF;
		$help .= '$n' . $vote_ratios[0] * 100;
		$help .= '%$m  {#black}/endround$g Starts a vote to end current round' . LF;
		$help .= '$n' . $vote_ratios[1] * 100;
		$help .= '%$m  {#black}/ladder$g Starts a vote to restart track for ladder' . LF;
		$help .= '$n' . $vote_ratios[2] * 100;
		$help .= '%$m  {#black}/replay$g Starts a vote to play this track again' . LF;
		$help .= '$n' . $vote_ratios[3] * 100;
		$help .= '%$m  {#black}/skip$g Starts a vote to skip this track' . LF;
		if ($allow_ignorevotes) {
		$help .= '$n' . $vote_ratios[6] * 100;
		$help .= '%$m  {#black}/ignore$g Starts a vote to ignore a player' . LF;
		}
		if ($allow_kickvotes) {
		$help .= '$n' . $vote_ratios[4] * 100;
		$help .= '%$m  {#black}/kick$g Starts a vote to kick a player' . LF;
		}
		$help .= '       {#black}/cancel$g Cancels your current vote' . LF . LF;
		$help .= 'Players can vote with {#black}/y$g until the required number' . LF . 'of votes is reached, or the vote expires.';

		// display popup message
		$aseco->client->query('SendDisplayServerMessageToLogin', $login, $aseco->formatColors($help), 'OK', '', 0);

	} elseif ($aseco->server->getGame() == 'TMF') {
		$header = '{#vote}Chat-based votes$g are available for these actions:';
		$data = array();
		$data[] = array('Ratio', '{#black}Command', '');
		$data[] = array($vote_ratios[0] * 100 . '%', '{#black}/endround',
		                'Starts a vote to end current round');
		$data[] = array($vote_ratios[1] * 100 . '%', '{#black}/ladder',
		                'Starts a vote to restart track for ladder');
		$data[] = array($vote_ratios[2] * 100 . '%', '{#black}/replay',
		                'Starts a vote to play this track again');
		$data[] = array($vote_ratios[3] * 100 . '%', '{#black}/skip',
		                'Starts a vote to skip this track');
		if ($allow_ignorevotes) {
		$data[] = array($vote_ratios[6] * 100 . '%', '{#black}/ignore',
		                'Starts a vote to ignore a player');
		}
		if ($allow_kickvotes) {
		$data[] = array($vote_ratios[4] * 100 . '%', '{#black}/kick',
		                'Starts a vote to kick a player');
		}
		$data[] = array('', '{#black}/cancel',
		                'Cancels your current vote');
		$data[] = array();
		$data[] = array('Players can vote with {#black}/y$g until the required number of votes');
		$data[] = array('is reached, or the vote expires.');

		// display ManiaLink message
		display_manialink($login, $header, array('Icons64x64_1', 'TrackInfo', -0.01), $data, array(1.0, 0.1, 0.2, 0.7), 'OK');
	}
}  // chat_helpvote

function chat_endround($aseco, $command) {
	global $rasp, $feature_votes, $tmxadd, $chatvote, $vote_in_window, $plrvotes,
	       $vote_ratios, $auto_vote_starter, $allow_spec_startvote,
	       $disable_upon_admin, $disabled_scoreboard;

	$player = $command['author'];
	$login = $player->login;

	// check for active voting system
	if (!$feature_votes) {
		$message = $rasp->messages['NO_VOTE'][0];
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
		return;
	}

	if ($disabled_scoreboard) {
		$message = $rasp->messages['NO_SB_VOTE'][0];
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
		return;
	}

	// check whether this player is spectator
	if (!$allow_spec_startvote && $aseco->isSpectator($player)) {
		$message = $rasp->messages['NO_SPECTATORS'][0];
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
		return;
	}

	// check whether available admin should be asked for endround
	if ($disable_upon_admin && admin_online()) {
		$message = $rasp->messages['ASK_ADMIN'][0] . ' {#highlite}End this Round';
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
		return;
	}

	// check for ongoing chat or TMX vote
	if (!empty($chatvote) || !empty($tmxadd)) {
		$message = $rasp->messages['VOTE_ALREADY'][0];
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
		return;
	}

	// check for TimeAttack/Laps/Stunts modes
	if ($aseco->server->gameinfo->mode == Gameinfo::TA ||
	    $aseco->server->gameinfo->mode == Gameinfo::LAPS ||
	    $aseco->server->gameinfo->mode == Gameinfo::STNT) {
		$message = '{#server}> {#error}Running {#highlite}$i ' .
		           $aseco->server->gameinfo->getMode() .
		           '{#error} mode - end round disabled!';
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
		return;
	}

	// start endround vote
	$chatvote['login'] = $login;
	$chatvote['nick'] = $player->nickname;
	$chatvote['votes'] = required_votes($vote_ratios[0]);
	$chatvote['type'] = 0;
	$chatvote['desc'] = 'End this Round';
	// reset votes
	$plrvotes = array();
	// no need to reset $r_expire_num etc as vote expires automatically

	// compile & show chat message
	$message = formatText($rasp->messages['VOTE_START'][0],
	                      stripColors($chatvote['nick']),
	                      $chatvote['desc'],
	                      $chatvote['votes']);
	$message = str_replace('{br}', LF, $message);  // split long message
	if ($vote_in_window && function_exists('send_window_message'))
		send_window_message($aseco, $message, false);
	else
		$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($message));

	// enable all vote panels
	if ($aseco->server->getGame() == 'TMF' && function_exists('allvotepanels_on'))
		allvotepanels_on($aseco, $login, $aseco->formatColors('{#vote}'));
	// vote automatically for vote starter?
	if ($auto_vote_starter) chat_y($aseco, $command);
}  // chat_endround

function chat_ladder($aseco, $command) {
	global $rasp, $feature_votes, $tmxadd, $chatvote, $vote_in_window, $plrvotes,
	       $vote_ratios, $num_laddervotes, $max_laddervotes, $auto_vote_starter,
	       $allow_spec_startvote, $disable_upon_admin, $disabled_scoreboard,
	       $r_expire_num, $ta_expire_start, $ta_show_num,
	       $r_points_limits, $ta_time_limits, $r_ladder_max, $ta_ladder_max;

	$player = $command['author'];
	$login = $player->login;

	// check for active voting system
	if (!$feature_votes) {
		$message = $rasp->messages['NO_VOTE'][0];
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
		return;
	}

	if ($disabled_scoreboard) {
		$message = $rasp->messages['NO_SB_VOTE'][0];
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
		return;
	}

	// check whether this player is spectator
	if (!$allow_spec_startvote && $aseco->isSpectator($player)) {
		$message = $rasp->messages['NO_SPECTATORS'][0];
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
		return;
	}

	// check whether available admin should be asked for ladder restart
	if ($disable_upon_admin && admin_online()) {
		$message = $rasp->messages['ASK_ADMIN'][0] . ' {#highlite}Restart Track for Ladder';
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
		return;
	}

	// check for ongoing chat or TMX vote
	if (!empty($chatvote) || !empty($tmxadd)) {
		$message = $rasp->messages['VOTE_ALREADY'][0];
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
		return;
	}

	// check whether ladder votes are allowed
	if ($max_laddervotes == 0) {
		$message = '{#server}> {#error}Ladder restart votes not allowed!';
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
		return;
	}

	// check for max ladder vote limit
	if ($num_laddervotes >= $max_laddervotes) {
		$message = formatText($rasp->messages['VOTE_LIMIT'][0],
		                      $max_laddervotes,
		                      '/ladder',
		                      ($max_laddervotes == 1 ? '' : 's'));
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
		return;
	}

	// check for mode-specific restrictions
	if ($aseco->server->gameinfo->mode == Gameinfo::RNDS && $r_points_limits) {
		// in Rounds mode, get points of first player & points limit
		$aseco->client->query('GetCurrentRanking', 1, 0);
		$info = $aseco->client->getResponse();
		$points = $info[0]['Score'];
		$aseco->client->query('GetRoundPointsLimit');
		$info = $aseco->client->getResponse();
		$limit = $info['CurrentValue'];

		// check whether to disable /ladder
		if ($points > ($limit * $r_ladder_max)) {
			$message = '{#server}> {#error}First player already has {#highlite}$i ' .
			           $points .
			           '{#error} points - too late for ladder restart!';
			$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
			return;
		}
	}
	elseif ($aseco->server->gameinfo->mode == Gameinfo::TA && $ta_time_limits) {
		// in TimeAttack mode, get track playing time & time limit
		$played = time_playing($aseco);  // from plugin.track.php
		$aseco->client->query('GetTimeAttackLimit');
		$info = $aseco->client->getResponse();
		$limit = $info['CurrentValue'] / 1000;  // convert to seconds

		// check whether to disable /ladder
		if ($played > ($limit * $ta_ladder_max)) {
			$message = '{#server}> {#error}Track is already playing for {#highlite}$i ' .
			           preg_replace('/^00:/', '', formatTimeH($played * 1000, false)) .
			           '{#error} minutes - too late for ladder restart!';
			$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
			return;
		}
	}  // no restrictions in other modes

	// start ladder vote
	$num_laddervotes++;
	$chatvote['login'] = $login;
	$chatvote['nick'] = $player->nickname;
	$chatvote['votes'] = required_votes($vote_ratios[1]);
	$chatvote['type'] = 1;
	$chatvote['desc'] = 'Restart Track for Ladder';
	// reset votes, rounds counter, TA interval counter & start time
	$plrvotes = array();
	$r_expire_num = 0;
	$ta_show_num = 0;
	$ta_expire_start = time_playing($aseco);  // from plugin.track.php

	// compile & show chat message
	$message = formatText($rasp->messages['VOTE_START'][0],
	                      stripColors($chatvote['nick']),
	                      $chatvote['desc'],
	                      $chatvote['votes']);
	$message = str_replace('{br}', LF, $message);  // split long message
	if ($vote_in_window && function_exists('send_window_message'))
		send_window_message($aseco, $message, false);
	else
		$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($message));

	// enable all vote panels
	if ($aseco->server->getGame() == 'TMF' && function_exists('allvotepanels_on'))
		allvotepanels_on($aseco, $login, $aseco->formatColors('{#vote}'));
	// vote automatically for vote starter?
	if ($auto_vote_starter) chat_y($aseco, $command);
}  // chat_ladder

function chat_replay($aseco, $command) {
	global $rasp, $feature_votes, $tmxadd, $chatvote, $vote_in_window, $plrvotes,
	       $vote_ratios, $num_replayvotes, $max_replayvotes, $auto_vote_starter,
	       $allow_spec_startvote, $disable_upon_admin, $disabled_scoreboard,
	       $jukebox, $r_expire_num, $ta_expire_start, $ta_show_num,
	       $r_points_limits, $ta_time_limits, $r_replay_min, $ta_replay_min,
	       $replays_limit, $replays_counter;

	$player = $command['author'];
	$login = $player->login;

	// check for active voting system
	if (!$feature_votes) {
		$message = $rasp->messages['NO_VOTE'][0];
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
		return;
	}

	if ($disabled_scoreboard) {
		$message = $rasp->messages['NO_SB_VOTE'][0];
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
		return;
	}

	// check whether this player is spectator
	if (!$allow_spec_startvote && $aseco->isSpectator($player)) {
		$message = $rasp->messages['NO_SPECTATORS'][0];
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
		return;
	}

	// check whether available admin should be asked for replay
	if ($disable_upon_admin && admin_online()) {
		$message = $rasp->messages['ASK_ADMIN'][0] . ' {#highlite}Replay Track after Finish';
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
		return;
	}

	// check for ongoing chat or TMX vote
	if (!empty($chatvote) || !empty($tmxadd)) {
		$message = $rasp->messages['VOTE_ALREADY'][0];
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
		return;
	}

	// check whether replay votes are allowed
	if ($max_replayvotes == 0) {
		$message = '{#server}> {#error}Replay votes not allowed!';
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
		return;
	}

	// check for max replay vote limit
	if ($num_replayvotes >= $max_replayvotes) {
		$message = formatText($rasp->messages['VOTE_LIMIT'][0],
		                      $max_replayvotes,
		                      '/replay',
		                      ($max_replayvotes == 1 ? '' : 's'));
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
		return;
	}

	// check for replay count limit
	if ($replays_limit > 0 && $replays_counter >= $replays_limit) {
		$message = formatText($rasp->messages['NO_MORE_REPLAY'][0],
		                      $replays_limit, ($replays_limit == 1 ? '' : 's'));
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
		return;
	}

	// check if track already in jukebox
	if (!empty($jukebox) && array_key_exists($aseco->server->challenge->uid, $jukebox)) {
		$message = '{#server}> {#error}Track is already getting replayed!';
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
		return;
	}

	// check for mode-specific restrictions
	if ($aseco->server->gameinfo->mode == Gameinfo::RNDS && $r_points_limits) {
		// in Rounds mode, get points of first player & points limit
		$aseco->client->query('GetCurrentRanking', 1, 0);
		$info = $aseco->client->getResponse();
		$points = $info[0]['Score'];
		$aseco->client->query('GetRoundPointsLimit');
		$info = $aseco->client->getResponse();
		$limit = $info['CurrentValue'];

		// check whether to disable /replay
		if ($points < ($limit * $r_replay_min)) {
			$message = '{#server}> {#error}First player has only {#highlite}$i ' .
			           $points .
			           '{#error} points - too early for replay!';
			$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
			return;
		}
	}
	elseif ($aseco->server->gameinfo->mode == Gameinfo::TA && $ta_time_limits) {
		// in TimeAttack mode, get track playing time & time limit
		$played = time_playing($aseco);  // from plugin.track.php
		$aseco->client->query('GetTimeAttackLimit');
		$info = $aseco->client->getResponse();
		$limit = $info['CurrentValue'] / 1000;  // convert to seconds

		// check whether to disable /replay
		if ($played < ($limit * $ta_replay_min)) {
			$message = '{#server}> {#error}Track is only playing for {#highlite}$i ' .
			           preg_replace('/^00:/', '', formatTimeH($played * 1000, false)) .
			           '{#error} minutes - too early for replay!';
			$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
			return;
		}
	}  // no restrictions in other modes

	// start replay vote
	$num_replayvotes++;
	$chatvote['login'] = $login;
	$chatvote['nick'] = $player->nickname;
	$chatvote['votes'] = required_votes($vote_ratios[2]);
	$chatvote['type'] = 2;
	$chatvote['desc'] = 'Replay Track after Finish';
	// reset votes, rounds counter, TA interval counter & start time
	$plrvotes = array();
	$r_expire_num = 0;
	$ta_show_num = 0;
	$ta_expire_start = time_playing($aseco);  // from plugin.track.php

	// compile & show chat message
	$message = formatText($rasp->messages['VOTE_START'][0],
	                      stripColors($chatvote['nick']),
	                      $chatvote['desc'],
	                      $chatvote['votes']);
	$message = str_replace('{br}', LF, $message);  // split long message
	if ($vote_in_window && function_exists('send_window_message'))
		send_window_message($aseco, $message, false);
	else
		$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($message));

	// enable all vote panels
	if ($aseco->server->getGame() == 'TMF' && function_exists('allvotepanels_on'))
		allvotepanels_on($aseco, $login, $aseco->formatColors('{#vote}'));
	// vote automatically for vote starter?
	if ($auto_vote_starter) chat_y($aseco, $command);
}  // chat_replay

function chat_skip($aseco, $command) {
	global $rasp, $feature_votes, $tmxadd, $chatvote, $vote_in_window, $plrvotes,
	       $vote_ratios, $num_skipvotes, $max_skipvotes, $auto_vote_starter,
	       $allow_spec_startvote, $disable_upon_admin, $disabled_scoreboard,
	       $r_expire_num, $ta_expire_start, $ta_show_num,
	       $r_points_limits, $ta_time_limits, $r_skip_max, $ta_skip_max;

	$player = $command['author'];
	$login = $player->login;

	// check for active voting system
	if (!$feature_votes) {
		$message = $rasp->messages['NO_VOTE'][0];
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
		return;
	}

	if ($disabled_scoreboard) {
		$message = $rasp->messages['NO_SB_VOTE'][0];
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
		return;
	}

	// check whether this player is spectator
	if (!$allow_spec_startvote && $aseco->isSpectator($player)) {
		$message = $rasp->messages['NO_SPECTATORS'][0];
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
		return;
	}

	// check whether available admin should be asked for skip
	if ($disable_upon_admin && admin_online()) {
		$message = $rasp->messages['ASK_ADMIN'][0] . ' {#highlite}Skip this Track';
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
		return;
	}

	// check for ongoing chat or TMX vote
	if (!empty($chatvote) || !empty($tmxadd)) {
		$message = $rasp->messages['VOTE_ALREADY'][0];
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
		return;
	}

	// check whether skip votes are allowed
	if ($max_skipvotes == 0) {
		$message = '{#server}> {#error}Skip votes not allowed!';
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
		return;
	}

	// check for max skip vote limit
	if ($num_skipvotes >= $max_skipvotes) {
		$message = formatText($rasp->messages['VOTE_LIMIT'][0],
		                      $max_skipvotes,
		                      '/skip',
		                      ($max_skipvotes == 1 ? '' : 's'));
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
		return;
	}

	// check for mode-specific restrictions
	if ($aseco->server->gameinfo->mode == Gameinfo::RNDS && $r_points_limits) {
		// in Rounds mode, get points of first player & points limit
		$aseco->client->query('GetCurrentRanking', 1, 0);
		$info = $aseco->client->getResponse();
		$points = $info[0]['Score'];
		$aseco->client->query('GetRoundPointsLimit');
		$info = $aseco->client->getResponse();
		$limit = $info['CurrentValue'];

		// check whether to disable /skip
		if ($points > ($limit * $r_skip_max)) {
			$message = '{#server}> {#error}First player already has {#highlite}$i ' .
			           $points .
			           '{#error} points - too late for skip!';
			$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
			return;
		}
	}
	elseif ($aseco->server->gameinfo->mode == Gameinfo::TA && $ta_time_limits) {
		// in TimeAttack mode, get track playing time & time limit
		$played = time_playing($aseco);  // from plugin.track.php
		$aseco->client->query('GetTimeAttackLimit');
		$info = $aseco->client->getResponse();
		$limit = $info['CurrentValue'] / 1000;  // convert to seconds

		// check whether to disable /skip
		if ($played > ($limit * $ta_skip_max)) {
			$message = '{#server}> {#error}Track is already playing for {#highlite}$i ' .
			           preg_replace('/^00:/', '', formatTimeH($played * 1000, false)) .
			           '{#error} minutes - too late for skip!';
			$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
			return;
		}
	}  // no restrictions in other modes

	// start skip vote
	$num_skipvotes++;
	$chatvote['login'] = $login;
	$chatvote['nick'] = $player->nickname;
	$chatvote['votes'] = required_votes($vote_ratios[3]);
	$chatvote['type'] = 3;
	$chatvote['desc'] = 'Skip this Track';
	// reset votes, rounds counter, TA interval counter & start time
	$plrvotes = array();
	$r_expire_num = 0;
	$ta_show_num = 0;
	$ta_expire_start = time_playing($aseco);  // from plugin.track.php

	// compile & show chat message
	$message = formatText($rasp->messages['VOTE_START'][0],
	                      stripColors($chatvote['nick']),
	                      $chatvote['desc'],
	                      $chatvote['votes']);
	$message = str_replace('{br}', LF, $message);  // split long message
	if ($vote_in_window && function_exists('send_window_message'))
		send_window_message($aseco, $message, false);
	else
		$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($message));

	// enable all vote panels
	if ($aseco->server->getGame() == 'TMF' && function_exists('allvotepanels_on'))
		allvotepanels_on($aseco, $login, $aseco->formatColors('{#vote}'));
	// vote automatically for vote starter?
	if ($auto_vote_starter) chat_y($aseco, $command);
}  // chat_skip

function chat_ignore($aseco, $command) {
	global $rasp, $feature_votes, $allow_ignorevotes, $allow_admin_ignore, $tmxadd,
	       $chatvote, $vote_in_window, $plrvotes, $vote_ratios, $allow_spec_startvote,
	       $disable_upon_admin, $disabled_scoreboard, $auto_vote_starter,
	       $r_expire_num, $ta_expire_start, $ta_show_num;

	$player = $command['author'];
	$login = $player->login;

	// check for active voting system
	if (!$feature_votes) {
		$message = $rasp->messages['NO_VOTE'][0];
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
		return;
	}

	if ($disabled_scoreboard) {
		$message = $rasp->messages['NO_SB_VOTE'][0];
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
		return;
	}

	// check whether this player is spectator
	if (!$allow_spec_startvote && $aseco->isSpectator($player)) {
		$message = $rasp->messages['NO_SPECTATORS'][0];
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
		return;
	}

	// check whether available admin should be asked for ignore
	if ($disable_upon_admin && admin_online()) {
		$message = $rasp->messages['ASK_ADMIN'][0] . ' {#highlite}Ignore a Player';
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
		return;
	}

	// check for ongoing chat or TMX vote
	if (!empty($chatvote) || !empty($tmxadd)) {
		$message = $rasp->messages['VOTE_ALREADY'][0];
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
		return;
	}

	// check for permission to ignore
	if (!$allow_ignorevotes) {
		$message = '{#server}> {#error}Ignore votes not allowed!';
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
		return;
	}

	// get player information
	if ($target = $aseco->getPlayerParam($player, $command['params'])) {
		// check for admin ignore
		if ($allow_admin_ignore || !$aseco->isAnyAdmin($target)) {
			// start ignore vote
			$chatvote['login'] = $login;
			$chatvote['nick'] = $player->nickname;
			$chatvote['votes'] = required_votes($vote_ratios[6]);
			$chatvote['type'] = 6;
			$chatvote['desc'] = 'Ignore ' . stripColors($target->nickname);
			$chatvote['target'] = $target->login;
			// reset votes, rounds counter, TA interval counter & start time
			$plrvotes = array();
			$r_expire_num = 0;
			$ta_show_num = 0;
			$ta_expire_start = time_playing($aseco);  // from plugin.track.php

			// compile & show chat message
			$message = formatText($rasp->messages['VOTE_START'][0],
			                      stripColors($chatvote['nick']),
			                      $chatvote['desc'],
			                      $chatvote['votes']);
			$message = str_replace('{br}', LF, $message);  // split long message
			if ($vote_in_window && function_exists('send_window_message'))
				send_window_message($aseco, $message, false);
			else
				$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($message));

			// enable all vote panels
			if ($aseco->server->getGame() == 'TMF' && function_exists('allvotepanels_on'))
				allvotepanels_on($aseco, $login, $aseco->formatColors('{#vote}'));
			// vote automatically for vote starter?
			if ($auto_vote_starter) chat_y($aseco, $command);
		} else {
			// expose naughty player ;)
			$message = formatText($rasp->messages['NO_ADMIN_IGNORE'][0],
			                      stripColors($player->nickname),
			                      stripColors($target->nickname));
			$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($message));
		}
	}
}  // chat_ignore

function chat_kick($aseco, $command) {
	global $rasp, $feature_votes, $allow_kickvotes, $allow_admin_kick, $tmxadd,
	       $chatvote, $vote_in_window, $plrvotes, $vote_ratios, $allow_spec_startvote,
	       $disable_upon_admin, $disabled_scoreboard, $auto_vote_starter,
	       $r_expire_num, $ta_expire_start, $ta_show_num;

	$player = $command['author'];
	$login = $player->login;

	// check for active voting system
	if (!$feature_votes) {
		$message = $rasp->messages['NO_VOTE'][0];
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
		return;
	}

	if ($disabled_scoreboard) {
		$message = $rasp->messages['NO_SB_VOTE'][0];
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
		return;
	}

	// check whether this player is spectator
	if (!$allow_spec_startvote && $aseco->isSpectator($player)) {
		$message = $rasp->messages['NO_SPECTATORS'][0];
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
		return;
	}

	// check whether available admin should be asked for kick
	if ($disable_upon_admin && admin_online()) {
		$message = $rasp->messages['ASK_ADMIN'][0] . ' {#highlite}Kick a Player';
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
		return;
	}

	// check for ongoing chat or TMX vote
	if (!empty($chatvote) || !empty($tmxadd)) {
		$message = $rasp->messages['VOTE_ALREADY'][0];
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
		return;
	}

	// check for permission to kick
	if (!$allow_kickvotes) {
		$message = '{#server}> {#error}Kick votes not allowed!';
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
		return;
	}

	// get player information
	if ($target = $aseco->getPlayerParam($player, $command['params'])) {
		// check for admin kick
		if ($allow_admin_kick || !$aseco->isAnyAdmin($target)) {
			// start kick vote
			$chatvote['login'] = $login;
			$chatvote['nick'] = $player->nickname;
			$chatvote['votes'] = required_votes($vote_ratios[4]);
			$chatvote['type'] = 4;
			$chatvote['desc'] = 'Kick ' . stripColors($target->nickname);
			$chatvote['target'] = $target->login;
			// reset votes, rounds counter, TA interval counter & start time
			$plrvotes = array();
			$r_expire_num = 0;
			$ta_show_num = 0;
			$ta_expire_start = time_playing($aseco);  // from plugin.track.php

			// compile & show chat message
			$message = formatText($rasp->messages['VOTE_START'][0],
			                      stripColors($chatvote['nick']),
			                      $chatvote['desc'],
			                      $chatvote['votes']);
			$message = str_replace('{br}', LF, $message);  // split long message
			if ($vote_in_window && function_exists('send_window_message'))
				send_window_message($aseco, $message, false);
			else
				$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($message));

			// enable all vote panels
			if ($aseco->server->getGame() == 'TMF' && function_exists('allvotepanels_on'))
				allvotepanels_on($aseco, $login, $aseco->formatColors('{#vote}'));
			// vote automatically for vote starter?
			if ($auto_vote_starter) chat_y($aseco, $command);
		} else {
			// expose naughty player ;)
			$message = formatText($rasp->messages['NO_ADMIN_KICK'][0],
			                      stripColors($player->nickname),
			                      stripColors($target->nickname));
			$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($message));
		}
	}
}  // chat_kick

function chat_cancel($aseco, $command) {
	global $rasp, $feature_votes, $tmxadd, $chatvote, $jukebox_in_window, $vote_in_window;

	$player = $command['author'];
	$login = $player->login;

	// check for ongoing chat or TMX vote
	if (!empty($chatvote)) {
		// check for vote ownership or admin
		if ($login == $chatvote['login'] || $aseco->allowAbility($player, 'cancel')) {
			$aseco->console('Vote to {1} cancelled by {2}!',
			                $chatvote['desc'], $login);
			$message = formatText($rasp->messages['VOTE_END'][0],
			                      $chatvote['desc'],
			                      'cancelled',
			                      stripColors($player->nickname));
			if ($vote_in_window && function_exists('send_window_message'))
				send_window_message($aseco, $message, false);
			else
				$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($message));
			$chatvote = array();
			// disable all vote panels
			if ($aseco->server->getGame() == 'TMF')
				allvotepanels_off($aseco);
		} else {
			$message = '{#server}> {#error}You didn\'t start the current vote!';
			$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
		}
	}
	elseif (!empty($tmxadd)) {
		// check for vote ownership or admin
		if ($login == $tmxadd['login'] || $aseco->allowAbility($player, 'cancel')) {
			$aseco->console('Vote to add {1} cancelled by {2}!',
			                stripColors($tmxadd['name'], false), $login);
			$message = formatText($rasp->messages['JUKEBOX_END'][0],
			                      stripColors($tmxadd['name']),
			                      'cancelled',
			                      stripColors($player->nickname));
			if ($jukebox_in_window && function_exists('send_window_message'))
				send_window_message($aseco, $message, false);
			else
				$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($message));
			$tmxadd = array();
			// disable all vote panels
			if ($aseco->server->getGame() == 'TMF')
				allvotepanels_off($aseco);
		} else {
			$message = '{#server}> {#error}You didn\'t start the current vote!';
			$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
		}
	} else {
		$message = '{#server}> {#error}There is no vote in progress!';
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
	}
}  // chat_cancel

// determine required number of votes
function required_votes($ratio) {

	$numplrs = active_players();

	// compute normal vote count
	if ($numplrs <= 7) {
		$votes = round($numplrs * $ratio);
	} else {
		$votes = floor($numplrs * $ratio);
	}
	// exceptions for low player count
	if ($votes == 0) {
		$votes = 1;  // needed for /y
	}
	elseif ($numplrs >= 2 && $numplrs <= 3 && $votes == 1) {
		$votes = 2;  // minimum
	}

	return $votes;
}  // required_votes

// count players but not spectators
function active_players() {
	global $aseco, $allow_spec_voting;

	$total = 0;
	// check all connected players
	foreach ($aseco->server->players->player_list as $player) {
		// get current player status
		if ($allow_spec_voting || !$aseco->isSpectator($player))
			$total++;
	}
	return $total;
}  // active_players

// check whether there's an admin (any tier) online
function admin_online() {
	global $aseco;

	// check all connected players
	foreach ($aseco->server->players->player_list as $player) {
		// get current player status
		if ($aseco->isAnyAdmin($player))
			return true;
	}
	return false;
}  // admin_online
?>
