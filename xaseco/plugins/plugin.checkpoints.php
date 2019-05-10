<?php
/* vim: set noexpandtab tabstop=2 softtabstop=2 shiftwidth=2: */

/**
 * Checkpoints plugin.
 * Provides checkpoints/finish tracking, and displays checkpoint passages
 * via automatic pop-ups (useful only in Rounds/Team/Cup modes).
 * On TMF, manages the CP panel during playing, retiring & spectating,
 * as well as the Cup mode's warm-up phase.  Disabled in Stunts mode.
 * Created by Xymph
 *
 * Dependencies: used by plugin.dedimania.php & plugin.localdatabase.php
 */

Aseco::registerEvent('onPlayerConnect', 'addplayer_cp');
Aseco::registerEvent('onPlayerDisconnect', 'removeplayer_cp');
Aseco::registerEvent('onNewChallenge', 'reset_checkp');
Aseco::registerEvent('onBeginRound', 'clear_curr_cp');
Aseco::registerEvent('onEndRace', 'disable_checkp');
Aseco::registerEvent('onRestartChallenge', 'restart_checkp');
Aseco::registerEvent('onCheckpoint', 'store_checkp');
Aseco::registerEvent('onPlayerFinish1', 'store_finish');  // use pre event before local/Dedimania record processsing
Aseco::registerEvent('onPlayerInfoChanged', 'spec_togglecp');

Aseco::addChatCommand('cps', 'Sets local record checkpoints tracking');
Aseco::addChatCommand('cpsspec', 'Shows checkpoints of spectated player');
Aseco::addChatCommand('cptms', 'Displays all local records\' checkpoint times');
Aseco::addChatCommand('sectms', 'Displays all local records\' sector times');

global $checkpoints, $checkpoint_tests;
$checkpoints = array();
$checkpoint_tests = false;  // after reload no tests until end race

class Checkpoints {
	var $loclrec;
	var $dedirec;
	var $best_time;
	var $best_fin;
	var $best_cps;
	var $curr_fin;
	var $curr_cps;
	var $speccers;

	// init empty checkpoints
	function Checkpoints() {
		$this->loclrec = -1;  // -1 = off, 0 = own/last rec, 1-max = rec #1-max
		$this->dedirec = -1;  // -1 = off, 0 = own/last rec, 1-30 = rec #1-30
		$this->best_time = 0;
		$this->best_fin = PHP_INT_MAX;
		$this->curr_fin = PHP_INT_MAX;
		$this->best_cps = array();
		$this->curr_cps = array();
		$this->speccers = array();
	}
}  // class Checkpoints

function chat_cps($aseco, $command) {
	global $checkpoints;

	$player = $command['author'];
	$login = $player->login;

	// check for relay server
	if ($aseco->server->isrelay) {
		$message = formatText($aseco->getChatMessage('NOTONRELAY'));
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
		return;
	}

	if ($aseco->settings['display_checkpoints']) {
		// set local checkpoints tracking
		$param = $command['params'];
		if (strtolower($param) == 'off') {
			$checkpoints[$login]->loclrec = -1;
			$checkpoints[$login]->dedirec = -1;
			$message = '{#server}> Local checkpoints tracking: {#highlite}OFF';
		}
		elseif ($param == '') {
			$checkpoints[$login]->loclrec = 0;
			$checkpoints[$login]->dedirec = -1;
			$message = '{#server}> Local checkpoints tracking: {#highlite}ON {#server}(your own or the last record)';
		}
		elseif (is_numeric($param) && $param > 0 && $param <= $aseco->server->records->max) {
			$checkpoints[$login]->loclrec = intval($param);
			$checkpoints[$login]->dedirec = -1;
			$message = '{#server}> Local checkpoints tracking record: {#highlite}' . $checkpoints[$login]->loclrec;
		}
		else {
			$message = '{#server}> {#error}No such Local record {#highlite}$i ' . $param;
		}

		// handle TMF checkpoints panel
		if ($aseco->server->getGame() == 'TMF') {
			if ($checkpoints[$login]->loclrec == -1) {
				// disable CP panel
				if ($aseco->settings['enable_cpsspec'] && !empty($checkpoints[$login]->speccers))
					cpspanel_off($aseco, $login . ',' . implode(',', $checkpoints[$login]->speccers));
				else
					cpspanel_off($aseco, $login);
			} else {
				// enable CP panel unless spectator, Stunts mode, or warm-up
				if (!$player->isspectator && $aseco->server->gameinfo->mode != Gameinfo::STNT && !$aseco->warmup_phase) {
					if ($aseco->settings['enable_cpsspec'] && !empty($checkpoints[$login]->speccers))
						display_cpspanel($aseco, $login . ',' . implode(',', $checkpoints[$login]->speccers), 0, '$00f -.--');
					else
						display_cpspanel($aseco, $login, 0, '$00f -.--');
				}
			}
		}
	} else {
		$message = '{#server}> {#error}Checkpoints tracking permanently disabled by server';
	}
	$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
}  // chat_cps

function chat_cpsspec($aseco, $command) {
	global $checkpoints;

	$player = $command['author'];
	$login = $player->login;

	// check for relay server
	if ($aseco->server->isrelay) {
		$message = formatText($aseco->getChatMessage('NOTONRELAY'));
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
		return;
	}

	if ($aseco->server->getGame() == 'TMF') {
		if ($aseco->settings['enable_cpsspec']) {
			// toggle cpsspec setting
			if ($player->speclogin != '') {
				// if subscribed, unsubscribe first
				if ($player->speclogin != ',' && isset($checkpoints[$player->speclogin])) {
					if (($i = array_search($login, $checkpoints[$player->speclogin]->speccers)) !== false)
						unset($checkpoints[$player->speclogin]->speccers[$i]);
				}
				$player->speclogin = '';
				cpspanel_off($aseco, $login);
			} else {
				// if spectator, subscribe
				$aseco->client->query('GetPlayerInfo', $login, 1);
				$info = $aseco->client->getResponse();
				$targetid = floor($info['SpectatorStatus'] / 10000);
				// check for player or free camera
				if ($info['SpectatorStatus'] == 0 || $targetid == 255) {
					$player->speclogin = ',';  // no target
				} else {
					// find login for target
					foreach ($aseco->server->players->player_list as $pl) {
						if ($pl->pid == $targetid) {
							$player->speclogin = $pl->login;
							// subscribe to this player
							if (!in_array($login, $checkpoints[$player->speclogin]->speccers))
								$checkpoints[$player->speclogin]->speccers[] = $login;
							break;
						}
					}
				}
			}

			// show chat message
			$message = '{#server}> Spectated player checkpoints tracking ';
			if ($player->speclogin != '')
				$message .= 'enabled';
			else
				$message .= 'disabled';
			$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
		} else {
			$message = $aseco->getChatMessage('NO_CPSSPEC');
			$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
		}
	} else {
		$message = $aseco->getChatMessage('FOREVER_ONLY');
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
	}
}  // chat_cpsspec


function chat_cptms($aseco, $command) {
	chat_sectms($aseco, $command, false);
}  // chat_cptms

function chat_sectms($aseco, $command, $diff = true) {

	$player = $command['author'];
	$login = $player->login;

	// check for relay server
	if ($aseco->server->isrelay) {
		$message = formatText($aseco->getChatMessage('NOTONRELAY'));
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
		return;
	}

	if (!$total = $aseco->server->records->count()) {
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors('{#server}> {#error}No records found!'), $login);
		return;
	}

	// find sector count from first record with CP times
	$cpscnt = '?';
	for ($i = 0; $i < $total; $i++) {
		$cur_record = $aseco->server->records->getRecord($i);
		if (!empty($cur_record->checks)) {
			$cpscnt = count($cur_record->checks);
			break;
		}
	}

	// display popup window for TMN
	if ($aseco->server->getGame() == 'TMN') {
		$head = 'Current TOP ' . $aseco->server->records->max . ' Local ' . ($diff ? 'Sector' : 'CP') . ' Times (' . $cpscnt . '):' . LF;
		$cpsmax = 9;
		$msg = '';
		$lines = 0;
		$player->msgs = array();
		$player->msgs[0] = 1;

		// create list of records
		for ($i = 0; $i < $total; $i++) {
			$cur_record = $aseco->server->records->getRecord($i);
			$msg .= str_pad($i+1, 2, '0', STR_PAD_LEFT) . '.  '
			        . ($cur_record->new ? '{#black}' : '')
			        . formatTime($cur_record->score);
			// append up to $cpsmax sector/CP times
			if (!empty($cur_record->checks)) {
				$j = 1;
				$pr = 0;
				$msg .= '$n';
				foreach ($cur_record->checks as $cp) {
					$msg .= ' ' . formatTime($cp - $pr);
					if ($diff) $pr = $cp;
					if (++$j > $cpsmax) {
						if ($cpscnt > $cpsmax) $msg .= ' +';
						break;
					}
				}
				$msg .= '$m';
			}
			$msg .= LF;
			if (++$lines > 9) {
				$player->msgs[] = $aseco->formatColors($head . $msg);
				$lines = 0;
				$msg = '';
			}
		}
		// add if last batch exists
		if ($msg != '')
			$player->msgs[] = $aseco->formatColors($head . $msg);

		// display popup message
		if (count($player->msgs) == 2) {
			$aseco->client->query('SendDisplayServerMessageToLogin', $login, $player->msgs[1], 'OK', '', 0);
		} else {  // > 2
			$aseco->client->query('SendDisplayServerMessageToLogin', $login, $player->msgs[1], 'Close', 'Next', 0);
		}

	// display ManiaLink window for TMF
	} elseif ($aseco->server->getGame() == 'TMF') {
		$head = 'Current TOP ' . $aseco->server->records->max . ' Local ' . ($diff ? 'Sector' : 'CP') . ' Times (' . $cpscnt . '):';
		$cpsmax = 12;
		// compute widths
		$width = 0.1 + 0.18 + min($cpscnt, $cpsmax) * 0.1 + ($cpscnt > $cpsmax ? 0.06 : 0.0);
		if ($width < 1.0) $width = 1.0;
		$widths = array($width, 0.1, 0.18);
		for ($i = 0; $i < min($cpscnt, $cpsmax); $i++)
			$widths[] = 0.1; // cp
		if ($cpscnt > $cpsmax)
			$widths[] = 0.06;

		$msg = array();
		$lines = 0;
		$player->msgs = array();
		$player->msgs[0] = array(1, $head, $widths, array('BgRaceScore2', 'Podium'));

		// create list of records
		for ($i = 0; $i < $total; $i++) {
			$cur_record = $aseco->server->records->getRecord($i);
			$line = array();
			$line[] = str_pad($i+1, 2, '0', STR_PAD_LEFT) . '.';
			$line[] = ($cur_record->new ? '{#black}' : '') .
			          ($aseco->server->gameinfo->mode == Gameinfo::STNT ?
			           $cur_record->score : formatTime($cur_record->score));
			// append up to $cpsmax sector/CP times
			if (!empty($cur_record->checks)) {
				$j = 1;
				$pr = 0;
				foreach ($cur_record->checks as $cp) {
					$line[] = '$n' . formatTime($cp - $pr);
					if ($diff) $pr = $cp;
					if (++$j > $cpsmax) {
						if ($cpscnt > $cpsmax) $line[] = '+';
						break;
					}
				}
			}
			$msg[] = $line;
			if (++$lines > 14) {
				$player->msgs[] = $msg;
				$lines = 0;
				$msg = array();
			}
		}
		// add if last batch exists
		if (!empty($msg))
			$player->msgs[] = $msg;

		// display ManiaLink message
		display_manialink_multi($player);

	// show chat message for TMO & TMS
	} else {
		$msg = $aseco->formatColors('{#server}> {#error}No sector times available');
		$aseco->client->query('ChatSendServerMessageToLogin', $msg, $login);
	}
}  // chat_sectms


// called @ onPlayerConnect
function addplayer_cp($aseco, $player) {
	global $checkpoints;

	$login = $player->login;

	$checkpoints[$login] = new Checkpoints();
	// set first lap reference in Laps mode
	if ($aseco->server->gameinfo->mode == Gameinfo::LAPS)
		$checkpoints[$login]->curr_fin = 0;
	if ($aseco->settings['display_checkpoints']) {
		// set personal or default CPs
		if ($cps = ldb_getCPs($aseco, $login)) {
			$checkpoints[$login]->loclrec = $cps['cps'];
			$checkpoints[$login]->dedirec = $cps['dedicps'];
		} else {
			if ($aseco->settings['auto_enable_cps'])
				$checkpoints[$login]->loclrec = 0;
			if ($aseco->settings['auto_enable_dedicps'])
				$checkpoints[$login]->dedirec = 0;
		}
	}
}  // addplayer_cp

// called @ onPlayerDisconnect
function removeplayer_cp($aseco, $player) {
	global $checkpoints;

	$login = $player->login;

	ldb_setCPs($aseco, $login,
	           $checkpoints[$login]->loclrec, $checkpoints[$login]->dedirec);

	// free up memory
	unset($checkpoints[$login]);
}  // removeplayer_cp

// called @ onNewChallenge
function reset_checkp($aseco, $challenge) {
	global $checkpoints, $laps_cpcount;

	// clear all checkpoints
	foreach ($checkpoints as $login => $cp) {
		$checkpoints[$login]->best_cps = array();
		$checkpoints[$login]->curr_cps = array();
		$checkpoints[$login]->best_fin = PHP_INT_MAX;
		if ($aseco->server->gameinfo->mode == Gameinfo::LAPS)
			$checkpoints[$login]->curr_fin = 0;
		else
			$checkpoints[$login]->curr_fin = PHP_INT_MAX;
	}

	// set local checkpoint references
	if ($aseco->settings['display_checkpoints']) {
		foreach ($checkpoints as $login => $cp) {
			$lrec = $checkpoints[$login]->loclrec - 1;

			// check for specific record
			if ($lrec+1 > 0) {
				// if specific record unavailable, use last one
				if ($lrec > $aseco->server->records->count() - 1)
					$lrec = $aseco->server->records->count() - 1;
				$curr = $aseco->server->records->getRecord($lrec);
				// check for valid checkpoints
				if (!empty($curr->checks) && $curr->score == end($curr->checks)) {
					$checkpoints[$login]->best_fin = $curr->score;
					$checkpoints[$login]->best_cps = $curr->checks;
				}
			}
			elseif ($lrec+1 == 0) {
				// search for own/last record
				$lrec = 0;
				while ($lrec < $aseco->server->records->count()) {
					$curr = $aseco->server->records->getRecord($lrec++);
					if ($curr->player->login == $login)
						break;
				}
				// check for valid checkpoints
				if (!empty($curr->checks) && $curr->score == end($curr->checks)) {
					$checkpoints[$login]->best_fin = $curr->score;
					$checkpoints[$login]->best_cps = $curr->checks;
				}
			}  // else -1
		}
	}

	// CP count only for Laps mode
	if ($aseco->server->getGame() == 'TMF')
		$laps_cpcount = $challenge->nbchecks;
	else
		$laps_cpcount = 0;
}  // reset_checkp

// called @ onBeginRound
function clear_curr_cp($aseco) {
	global $checkpoints;

	// if Stunts mode or warm-up, bail out immediately
	if ($aseco->server->gameinfo->mode == Gameinfo::STNT || $aseco->warmup_phase) return;

	// clear current checkpoints
	foreach ($checkpoints as $login => $cp) {
		$checkpoints[$login]->curr_cps = array();
		// set first lap reference in Laps mode, otherwise max time
		if ($aseco->server->gameinfo->mode == Gameinfo::LAPS)
			$checkpoints[$login]->curr_fin = 0;
		else
			$checkpoints[$login]->curr_fin = PHP_INT_MAX;

		// reset CP panel unless spectator
		if ($aseco->server->getGame() == 'TMF' && $checkpoints[$login]->loclrec != -1) {
			$player = $aseco->server->players->getPlayer($login);
			if (!$player->isspectator) {
				if ($aseco->settings['enable_cpsspec'] && !empty($checkpoints[$login]->speccers))
					display_cpspanel($aseco, $login . ',' . implode(',', $checkpoints[$login]->speccers), 0, '$00f -.--');
				else
					display_cpspanel($aseco, $login, 0, '$00f -.--');
			}
		}
	}
}  // clear_curr_cp

// called @ onEndRace
function disable_checkp($aseco, $data) {
	global $checkpoint_tests;

	// disable CP panels at end of track
	if ($aseco->server->getGame() == 'TMF') {
		allcpspanels_off($aseco);
	}

	$checkpoint_tests = true;  // now commence cheat tests
}  // disable_checkp

// called @ onRestartChallenge
function restart_checkp($aseco, $data) {
	global $checkpoints, $checkpoint_tests;

	// clear current checkpoints
	foreach ($checkpoints as $login => $cp) {
		$checkpoints[$login]->curr_cps = array();
		// set first lap reference in Laps mode, otherwise max time
		if ($aseco->server->gameinfo->mode == Gameinfo::LAPS)
			$checkpoints[$login]->curr_fin = 0;
		else
			$checkpoints[$login]->curr_fin = PHP_INT_MAX;
	}

	$checkpoint_tests = true;  // now commence cheat tests
}  // restart_checkp

// called @ onCheckpoint
// TMN: [0]=PlayerUid, [1]=Login, [2]=Time, [3]=Score, [4]=CheckpointIndex
// TMF: [0]=PlayerUid, [1]=Login, [2]=TimeOrScore, [3]=CurLap, [4]=CheckpointIndex
function store_checkp($aseco, $checkpt) {
	global $rasp, $checkpoints, $laps_cpcount, $checkpoint_tests,
	       $feature_stats;  // from rasp.settings.php

	// if Stunts mode, bail out immediately
	// no checkpoints during warm-up, so no need to check that
	if ($aseco->server->gameinfo->mode == Gameinfo::STNT) return;

	// if undefined login, bail out too
	$login = $checkpt[1];
	if (!isset($checkpoints[$login])) return;

	// check for Laps mode
	if ($aseco->server->gameinfo->mode != Gameinfo::LAPS) {

		// reset for next run in TimeAttack mode
		if ($aseco->server->gameinfo->mode == Gameinfo::TA && $checkpt[4] == 0)
			$checkpoints[$login]->curr_cps = array();

		// check for cheated checkpoints:
		// non-positive time, wrong index, or time less than preceding one
		if ($checkpt[2] <= 0 || $checkpt[4] != count($checkpoints[$login]->curr_cps) ||
		    ($checkpt[4] > 0 && $checkpt[2] < end($checkpoints[$login]->curr_cps))) {
			if ($checkpoint_tests) {
				$aseco->processCheater($login, $checkpoints[$login]->curr_cps, $checkpt, -1);
				return;
			}
		}

		// store current checkpoint
		$checkpoints[$login]->curr_cps[$checkpt[4]] = $checkpt[2];

		// check if displaying for this player, and for best checkpoints
		if ($checkpoints[$login]->loclrec != -1 &&
		    isset($checkpoints[$login]->best_cps[$checkpt[4]])) {

			// check whether not last one (Finish) on TMN
			$check = $checkpt[4] + 1;
			if ($aseco->server->getGame() == 'TMF' ||
			    $check < count($checkpoints[$login]->best_cps)) {

				$diff = $checkpoints[$login]->curr_cps[$checkpt[4]] -
				        $checkpoints[$login]->best_cps[$checkpt[4]];
				// check for improvement
				if ($diff < 0) {
					$diff = abs($diff);
					$sign = '$00f-';  // blue
				} elseif ($diff == 0) {
					$sign = '$00f';  // blue
				} else {  // $diff > 0
					$sign = '$f00+';  // red
				}
				$sec = floor($diff/1000);
				$hun = ($diff - ($sec * 1000)) / 10;

				if ($aseco->server->getGame() == 'TMN') {
					$cpmsg = '$nCP' . $check . ': $m$000' . formatTime($checkpt[2])
					         . ' $w' . $sign . sprintf('%d.%02d', $sec, $hun);
					// display temporary popup message
					$aseco->client->query('SendDisplayServerMessageToLogin', $login,
					                      $cpmsg, '', '', 2000);  // timeout 2 secs
				} else {  // TMF
					// check for Finish checkpoint
					if ($check == count($checkpoints[$login]->best_cps))
						$check = 'F';
					// update CP panel
					if ($aseco->settings['enable_cpsspec'] && !empty($checkpoints[$login]->speccers))
						display_cpspanel($aseco, $login . ',' . implode(',', $checkpoints[$login]->speccers), $check,
						                 $sign . sprintf('%d.%02d', $sec, $hun));
					else
						display_cpspanel($aseco, $login, $check,
						                 $sign . sprintf('%d.%02d', $sec, $hun));
				}
			}
		}

	} else {  // Laps

		// no support on TMN because PlayerCheckpoint event doesn't supply CurLap
		if ($aseco->server->getGame() != 'TMF') return;

		// check for cheated checkpoints:
		// non-positive time, negative index
		if ($checkpt[2] <= 0 || $checkpt[4] < 0) {
			if ($checkpoint_tests) {
				$aseco->processCheater($login, $checkpoints[$login]->curr_cps, $checkpt, -1);
				return;
			}
		}

		// in TMN get checkpoints count/lap from first player to complete first lap
		if ($laps_cpcount == 0 && $checkpt[3] == 1)
			$laps_cpcount = $checkpt[4] + 1;

		// get relative CP in this lap
		if ($laps_cpcount > 0)
			$relcheck = $checkpt[4] % $laps_cpcount;
		else  // first lap
			$relcheck = $checkpt[4];

		// check for cheated checkpoints:
		// wrong index, time not more than reference, relative time less than preceding one
		if ($relcheck != count($checkpoints[$login]->curr_cps) ||
		    $checkpt[2] < $checkpoints[$login]->curr_fin ||
		    ($relcheck > 0 && $checkpt[2] - $checkpoints[$login]->curr_fin < end($checkpoints[$login]->curr_cps))) {
			if ($checkpoint_tests) {
				$aseco->processCheater($login, $checkpoints[$login]->curr_cps, $checkpt, -1);
				return;
			}
		}

		// store current checkpoint for current lap, relative to reference
		$checkpoints[$login]->curr_cps[$relcheck] = $checkpt[2] - $checkpoints[$login]->curr_fin;

		// check for a completed lap
		if ($checkpt[3] * $laps_cpcount != $checkpt[4] + 1) {

			// check if displaying for this player, and for best checkpoints
			if ($checkpoints[$login]->loclrec != -1 &&
			    isset($checkpoints[$login]->best_cps[$relcheck])) {

				// check for improvement
				$diff = $checkpoints[$login]->curr_cps[$relcheck] - $checkpoints[$login]->best_cps[$relcheck];
				if ($diff < 0) {
					$diff = abs($diff);
					$sign = '$00f-';  // blue
				} elseif ($diff == 0) {
					$sign = '$00f';  // blue
				} else {  // $diff > 0
					$sign = '$f00+';  // red
				}
				$sec = floor($diff/1000);
				$hun = ($diff - ($sec * 1000)) / 10;

				// update CP panel
				if ($aseco->settings['enable_cpsspec'] && !empty($checkpoints[$login]->speccers))
					display_cpspanel($aseco, $login . ',' . implode(',', $checkpoints[$login]->speccers), $relcheck + 1,
					                 $sign . sprintf('%d.%02d', $sec, $hun));
				else
					display_cpspanel($aseco, $login, $relcheck + 1,
					                 $sign . sprintf('%d.%02d', $sec, $hun));
			}

		} else {  // completed lap

			// store current lap finish as reference for next lap
			$checkpoints[$login]->curr_fin = $checkpt[2];

			// build a record object with the current lap information
			$finish_item = new Record();
			$finish_item->player = $aseco->server->players->getPlayer($login);
			$finish_item->score = $checkpoints[$login]->curr_cps[$relcheck];
			$finish_item->date = strftime('%Y-%m-%d %H:%M:%S');
			$finish_item->challenge = clone $aseco->server->challenge;
			unset($finish_item->challenge->gbx);  // reduce memory usage
			unset($finish_item->challenge->tmx);

			// store current lap
			if ($feature_stats) {
				$rasp->insertTime($finish_item, implode(',', $checkpoints[$login]->curr_cps));
			}

			// process for local and Dedimania records
			$finish_item->new = true;  // set lap 'Finish' flag
			ldb_playerFinish($aseco, $finish_item);
			$finish_item->new = true;  // ditto
			if (function_exists('dedimania_playerfinish'))
				dedimania_playerfinish($aseco, $finish_item);

			// check for new best lap
			$diff = $checkpoints[$login]->curr_cps[$relcheck] - $checkpoints[$login]->best_fin;
			if ($diff < 0) {
				// store new best lap
				$checkpoints[$login]->best_fin = $checkpoints[$login]->curr_cps[$relcheck];
				$checkpoints[$login]->best_cps = $checkpoints[$login]->curr_cps;
				// store timestamp for sorting in case of equal bests
				$checkpoints[$login]->best_time = microtime(true);
			}

			// check if displaying for this player, and not first lap
			if ($checkpoints[$login]->loclrec != -1 && $checkpt[4] + 1 >= $laps_cpcount) {
				// check for improvement
				if ($diff < 0) {
					$diff = abs($diff);
					$sign = '$00f-';  // blue
				} elseif ($diff == 0) {
					$sign = '$00f';  // blue
				} else {  // $diff > 0
					$sign = '$f00+';  // red
				}
				$sec = floor($diff/1000);
				$hun = ($diff - ($sec * 1000)) / 10;

				// indicate Lap Finish checkpoint
				$relcheck = 'L';
				// update CP panel
				if ($aseco->settings['enable_cpsspec'] && !empty($checkpoints[$login]->speccers))
					display_cpspanel($aseco, $login . ',' . implode(',', $checkpoints[$login]->speccers), $relcheck,
					                 $sign . sprintf('%d.%02d', $sec, $hun));
				else
					display_cpspanel($aseco, $login, $relcheck,
					                 $sign . sprintf('%d.%02d', $sec, $hun));
			}

			// reset for next lap
			$checkpoints[$login]->curr_cps = array();
		}
	}
}  // store_checkp

// called @ onPlayerFinish1
function store_finish($aseco, $finish_item) {
	global $checkpoints, $checkpoint_tests;

	// if Laps or Stunts mode, bail out immediately
	// no finishes during warm-up, so no need to check that
	if ($aseco->server->gameinfo->mode == Gameinfo::LAPS ||
	    $aseco->server->gameinfo->mode == Gameinfo::STNT) return;

	$login = $finish_item->player->login;
	// in case of CP order problem
	sort($checkpoints[$login]->curr_cps);

	// check for actual finish
	if ($finish_item->score > 0) {
		// compute number of checkpoints (incl. multilaps except in TA mode)
		$reqchecks = $finish_item->challenge->nbchecks;
		if ($aseco->server->getGame() == 'TMF' && $aseco->server->gameinfo->mode != Gameinfo::TA &&
		    $finish_item->challenge->laprace) {
			if ($finish_item->challenge->forcedlaps != 0)
				$reqchecks *= $finish_item->challenge->forcedlaps;
			else
				$reqchecks *= $finish_item->challenge->nblaps;
		}

		// check for required number of checkpoints on TMF
		if ($aseco->server->getGame() == 'TMF' && $reqchecks != count($checkpoints[$login]->curr_cps)) {
			if ($checkpoint_tests) {
				trigger_error('CPs for ' . $login . ' required: ' . $reqchecks . '  present: ' . count($checkpoints[$login]->curr_cps) .
				              ' - ' . implode(',', $checkpoints[$login]->curr_cps), E_USER_WARNING);
			}
			// reset to prevent local/Dedimania records
			$finish_item->score = 0;
		// check for finish equal last checkpoint
		} elseif ($finish_item->score == end($checkpoints[$login]->curr_cps)) {
			$checkpoints[$login]->curr_fin = $finish_item->score;

		// check for improvement
			if ($checkpoints[$login]->curr_fin < $checkpoints[$login]->best_fin) {
				$checkpoints[$login]->best_fin = $checkpoints[$login]->curr_fin;
				$checkpoints[$login]->best_cps = $checkpoints[$login]->curr_cps;
				// store timestamp for sorting in case of equal bests
				$checkpoints[$login]->best_time = microtime(true);
			}
		} else {
			if ($checkpoint_tests) {
				$aseco->processCheater($login, $checkpoints[$login]->curr_cps, false, $finish_item->score);
				// reset to prevent local/Dedimania records
				$finish_item->score = 0;
			}
		}
	}
	// check for player retire in TimeAttack mode
	elseif ($aseco->server->getGame() == 'TMF' && $aseco->server->gameinfo->mode == Gameinfo::TA &&
	        $finish_item->score == 0 && $checkpoints[$login]->loclrec != -1) {
		// reset CP panel
		if ($aseco->settings['enable_cpsspec'] && !empty($checkpoints[$login]->speccers))
			display_cpspanel($aseco, $login . ',' . implode(',', $checkpoints[$login]->speccers), 0, '$00f -.--');
		else
			display_cpspanel($aseco, $login, 0, '$00f -.--');
	}
}  // store_finish

// called @ onPlayerInfoChanged
function spec_togglecp($aseco, $playerinfo) {
	global $checkpoints;

	// if Stunts mode or warm-up, bail out immediately
	if ($aseco->server->gameinfo->mode == Gameinfo::STNT || $aseco->warmup_phase) return;

	$login = $playerinfo['Login'];
	$player = $aseco->server->players->getPlayer($login);
	// if no real spectator status change, bail out immediately
	if ($player->prevstatus == $player->isspectator) return;

	// check if CPS active
	if (isset($checkpoints[$login]) && $checkpoints[$login]->loclrec != -1) {
		// check spectator status
		if ($playerinfo['SpectatorStatus'] != 0) {
			// disable CP panel
			if ($aseco->settings['enable_cpsspec'] && !empty($checkpoints[$login]->speccers))
				cpspanel_off($aseco, $login . ',' . implode(',', $checkpoints[$login]->speccers));
			else
				cpspanel_off($aseco, $login);
		} else {
			// enable CP panel
			if ($aseco->settings['enable_cpsspec'] && !empty($checkpoints[$login]->speccers))
				display_cpspanel($aseco, $login . ',' . implode(',', $checkpoints[$login]->speccers), 0, '$00f -.--');
			else
				display_cpspanel($aseco, $login, 0, '$00f -.--');
		}
	}

	// check for spectated player update
	if ($aseco->settings['enable_cpsspec']) {
		// check if /cpsspec enabled
		if ($player->speclogin != '') {
			$targetid = floor($playerinfo['SpectatorStatus'] / 10000);

			// check for player status or free camera
			if ($playerinfo['SpectatorStatus'] == 0 || $targetid == 255) {
				// if subscribed, unsubscribe first
				if ($player->speclogin != ',' && isset($checkpoints[$player->speclogin])) {
					if (($i = array_search($login, $checkpoints[$player->speclogin]->speccers)) !== false)
						unset($checkpoints[$player->speclogin]->speccers[$i]);
				}
				$player->speclogin = ',';  // no target
			} else {
				// ignore stray self-spectating PlayerInfoChanged events
				if ($player->pid != $targetid) {
					// find login for target
					foreach ($aseco->server->players->player_list as $pl) {
						if ($pl->pid == $targetid) {
							$player->speclogin = $pl->login;
							// subscribe to this player
							if (!in_array($login, $checkpoints[$player->speclogin]->speccers))
								$checkpoints[$player->speclogin]->speccers[] = $login;
							break;
						}
					}
				}
			}
		}
	}
}  // spec_togglecp
?>
