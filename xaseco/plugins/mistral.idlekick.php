<?php
/* vim: set noexpandtab tabstop=2 softtabstop=2 shiftwidth=2: */

/****************************************************************************
 *
 * (X)ASECO plugin to kick idle players
 *
 * (C) 2007 by Mistral
 * Updated by Xymph
 *
 * Dependencies: none
 *
 ****************************************************************************/

Aseco::registerEvent('onNewChallenge', 'kickIdleNewChallenge');
Aseco::registerEvent('onPlayerConnect', 'kickIdleInit');
Aseco::registerEvent('onChat', 'kickIdleChat');
Aseco::registerEvent('onCheckpoint', 'kickIdleCheckpoint');
Aseco::registerEvent('onPlayerFinish', 'kickIdleFinish');
Aseco::registerEvent('onEndRace', 'kickIdlePlayers');

global $kickPlayAfter, $kickSpecAfter, $kickSpecToo, $specPlayFirst, $resetOnChat,
       $resetOnCheckpoint, $resetOnFinish, $idlekickStart, $idlekick_log, $idlekick_debug;

$kickPlayAfter = 2;         // Player idle this number of challenges and get kicked or specced
$kickSpecAfter = 4;         // Spectator idle this number of challenges and get kicked
$kickSpecToo = true;        // Kick spectators too
$specPlayFirst = false;     // Set idle player to spectator first instead of kick (TMF only)
$resetOnChat = true;        // Reset idle counter on chat use
$resetOnCheckpoint = true;  // Reset idle counter when passing a checkpoint
$resetOnFinish = false;     // Reset idle counter when reaching the finish
// don't use OnFinish in rounds or team mode, because every player will "finish"

// don't touch:
$idlekickStart = true;
$idlekick_log = false;
$idlekick_debug = false;

// called @ onChat
function kickIdleChat($aseco, $chat) {
	global $resetOnChat, $idlekick_debug;

	// if server message, bail out immediately
	if ($chat[0] == $aseco->server->id) return;

	// if no check on chat use, bail out too
	if (!$resetOnChat) return;

	$player = $aseco->server->players->getPlayer($chat[1]);
	$player->mistral['idleCount'] = 0;
	if ($idlekick_debug)
		$aseco->console('Idlekick: {1} reset on chat', $player->login);
}  // kickIdleChat

// called @ onCheckpoint
function kickIdleCheckpoint($aseco, $checkpt) {
	global $resetOnCheckpoint, $idlekick_debug;

	// if no check on checkpoints, bail out
	if (!$resetOnCheckpoint) return;

	$player = $aseco->server->players->getPlayer($checkpt[1]);
	$player->mistral['idleCount'] = 0;
	if ($idlekick_debug)
		$aseco->console('Idlekick: {1} reset on checkpoint', $player->login);
}  // kickIdleCheckpoint

// called @ onPlayerFinish
function kickIdleFinish($aseco, $finish_item) {
	global $resetOnFinish, $idlekick_debug;

	// if no check on finishes, bail out
	if (!$resetOnFinish) return;

	$player = $finish_item->player;
	$player->mistral['idleCount'] = 0;
	if ($idlekick_debug)
		$aseco->console('Idlekick: {1} reset on finish', $player->login);
}  // kickIdleFinish

// called @ onNewChallenge
function kickIdleNewChallenge($aseco, $challenge) {
	global $kickSpecToo, $idlekickStart, $idlekick_debug, $idlekick_log;

	if ($idlekickStart) {
		$idlekickStart = false;
		if ($idlekick_debug)
			$aseco->console('Idlekick: idlekickStart set to false');
		foreach ($aseco->server->players->player_list as $player)
			kickIdleInit($aseco, $player);
		return;
	}

	foreach ($aseco->server->players->player_list as $player) {
		// get player status
		$spec = $aseco->isSpectator($player);

		// check for admin immunity
		if ($spec ? $aseco->allowAbility($player, 'noidlekick_spec')
		          : $aseco->allowAbility($player, 'noidlekick_play'))
			continue;  // go check next player

		// check for spectator kicking
		if ($kickSpecToo || !$spec)
			$player->mistral['idleCount']++;
		if ($idlekick_log)
			$aseco->console('Idlekick: {1} set to {2}', $player->login, $player->mistral['idleCount']);
	}
}  // kickIdleNewChallenge

// called @ onPlayerConnect
function kickIdleInit($aseco, $player) {
	global $idlekick_debug;

	$player->mistral['idleCount'] = 0;
	if ($idlekick_debug)
		$aseco->console('Idlekick: {1} initialised with 0', $player->login);
}  // kickIdleInit

// called @ onEndRace
function kickIdlePlayers($aseco, $data) {
	global $kickPlayAfter, $kickSpecAfter, $specPlayFirst, $idlekick_debug;

	foreach ($aseco->server->players->player_list as $player) {
		$spec = $aseco->isSpectator($player);
		// check for spectator or player challenge counts
		if ($player->mistral['idleCount'] == ($spec ? $kickSpecAfter
		                                            : $kickPlayAfter)) {
			$dokick = false;
			if ($spec) {
				$dokick = true;
				// log console message
				$aseco->console('IdleKick spectator: {1} after {2} challenge(s) without action', $player->login, $kickSpecAfter);
				$message = formatText($aseco->getChatMessage('IDLEKICK_SPEC'),
				                      $player->nickname,
				                      $kickSpecAfter, ($kickSpecAfter == 1 ? '' : 's'));
			} else {
				if ($aseco->server->getGame() == 'TMF' && $specPlayFirst) {
					// log console message
					$aseco->console('IdleSpec player: {1} after {2} challenge(s) without action', $player->login, $kickPlayAfter);
					$message = formatText($aseco->getChatMessage('IDLESPEC_PLAY'),
					                      $player->nickname,
					                      $kickPlayAfter, ($kickPlayAfter == 1 ? '' : 's'));

					// force player into spectator
					$rtn = $aseco->client->query('ForceSpectator', $player->login, 1);
					if (!$rtn) {
						trigger_error('[' . $aseco->client->getErrorCode() . '] ForceSpectator - ' . $aseco->client->getErrorMessage(), E_USER_WARNING);
					} else {
						// allow spectator to switch back to player
						$rtn = $aseco->client->query('ForceSpectator', $player->login, 0);
					}

					// force free camera mode on spectator
					$aseco->client->addCall('ForceSpectatorTarget', array($player->login, '', 2));
				} else {
					$dokick = true;
					// log console message
					$aseco->console('IdleKick player: {1} after {2} challenge(s) without action', $player->login, $kickPlayAfter);
					$message = formatText($aseco->getChatMessage('IDLEKICK_PLAY'),
					                      $player->nickname,
					                      $kickPlayAfter, ($kickPlayAfter == 1 ? '' : 's'));
				}
			}
			// show chat message
			$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($message));
			// kick idle player
			if ($dokick)
				$aseco->client->query('Kick', $player->login);
		}
		elseif ($idlekick_debug)
			$aseco->console('Idlekick: {1} current value is {2}', $player->login, $player->mistral['idleCount']);
	}
}  // kickIdlePlayers
?>
