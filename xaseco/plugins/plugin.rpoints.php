<?php
/* vim: set noexpandtab tabstop=2 softtabstop=2 shiftwidth=2: */

/**
 * Rounds Points plugin.
 * Allows setting common and custom Rounds points systems.
 * Created by Xymph
 *
 * Dependencies: used by chat.admin.php
 */

Aseco::registerEvent('onSync', 'init_rpoints');

Aseco::addChatCommand('rpoints', 'Shows current Rounds points system');

global $rounds_points;
$rounds_points = array();

// define common points systems:

// http://www.formula1.com/inside_f1/rules_and_regulations/sporting_regulations/6833/
$rounds_points['f1old']      = array('Formula 1 GP Old',
                                     array(10,8,6,5,4,3,2,1));
// http://www.formula1.com/inside_f1/rules_and_regulations/sporting_regulations/8681/
$rounds_points['f1new']      = array('Formula 1 GP New',
                                     array(25,18,15,12,10,8,6,4,2,1));
// http://www.motogp.com/en/about+MotoGP/key+rules
$rounds_points['motogp']     = array('MotoGP',
                                     array(25,20,16,13,11,10,9,8,7,6,5,4,3,2,1));
// MotoGP + 5 points
$rounds_points['motogp5']    = array('MotoGP + 5',
                                     array(30,25,21,18,16,15,14,13,12,11,10,9,8,7,6,5,4,3,2,1));
// http://www.et-leagues.com/fet1/rules.php
$rounds_points['fet1']       = array('Formula ET Season 1',
                                     array(12,10,9,8,7,6,5,4,4,3,3,3,2,2,2,1));
// http://www.et-leagues.com/fet2/rules.php (fixed: #17-19 = 2, not #17-21)
$rounds_points['fet2']       = array('Formula ET Season 2',
                                     array(15,12,11,10,9,8,7,6,6,5,5,4,4,3,3,3,2,2,2,1));
// http://www.et-leagues.com/fet3/rules.php
$rounds_points['fet3']       = array('Formula ET Season 3',
                                     array(15,12,11,10,9,8,7,6,6,5,5,4,4,3,3,3,2,2,2,2,1));
// http://www.champcarworldseries.com/News/Article.asp?ID=7499
$rounds_points['champcar']   = array('Champ Car World Series',
                                     array(31,27,25,23,21,19,17,15,13,11,10,9,8,7,6,5,4,3,2,1));
// http://www.eurosuperstars.com/eng/regolamenti.asp
$rounds_points['superstars'] = array('Superstars',
                                     array(20,15,12,10,8,6,4,3,2,1));
$rounds_points['simple5']    = array('Simple 5',
                                     array(5,4,3,2,1));
$rounds_points['simple10']   = array('Simple 10',
                                     array(10,9,8,7,6,5,4,3,2,1));

// any players finishing beyond the last points entry get the same number of points (typically 1) as that last entry


function init_rpoints($aseco) {
	global $rounds_points;

	// set default rounds points system
	$system = $aseco->settings['default_rpoints'];
	if (preg_match('/^\d+,[\d,]*\d+$/', $system)) {
		// set new custom points as array of ints
		$points = array_map('intval', explode(',', $system));
		$rtn = $aseco->client->query('SetRoundCustomPoints', $points, false);
		// log console message
		if (!$rtn) {
			$aseco->console('Invalid rounds points: {1}  Error: {2}', $system, $aseco->client->getErrorMessage());
		} else {
			$aseco->console('Initialize default rounds points: {1}', $system);
		}

	} elseif (array_key_exists($system, $rounds_points)) {
		// set new custom points
		$rtn = $aseco->client->query('SetRoundCustomPoints', $rounds_points[$system][1], false);
		// log console message
		if (!$rtn) {
			$aseco->console('Invalid rounds points: {1}  Error: {2}', $system, $aseco->client->getErrorMessage());
		} else {
			$aseco->console('Initialize default rounds points: {1} - {2}',
			                $rounds_points[$system][0],
			                implode(',', $rounds_points[$system][1]));
		}

	} elseif ($system == '') {
		// disable custom points
		$rtn = $aseco->client->query('SetRoundCustomPoints', array(), false);

	} else {
		$aseco->console('Unknown rounds points: {1}', $system);
	}
}  // init_rpoints

function admin_rpoints($aseco, $admin, $logtitle, $chattitle, $command) {
	global $rounds_points;

	$login = $admin->login;
	$command = explode(' ', preg_replace('/ +/', ' ', $command));
	$system = strtolower($command[0]);

	if ($command[0] == 'help') {
		$header = '{#black}/admin rpoints$g sets custom Rounds points:';
		$help = array();
		$help[] = array('...', '{#black}help',
		                'Displays this help information');
		$help[] = array('...', '{#black}list',
		                'Displays available points systems');
		$help[] = array('...', '{#black}show',
		                'Shows current points system');
		$help[] = array('...', '{#black}xxx',
		                'Sets custom points system labelled xxx');
		$help[] = array('...', '{#black}X,Y,...,Z',
		                'Sets custom points system with specified values;');
		$help[] = array('', '',
		                'X,Y,...,Z must be decreasing integers and there');
		$help[] = array('', '',
		                'must be at least two values with no spaces');
		$help[] = array('...', '{#black}off',
		                'Disables custom points system');

		// display ManiaLink message
		display_manialink($login, $header, array('Icons64x64_1', 'TrackInfo', -0.01), $help, array(1.05, 0.05, 0.2, 0.8), 'OK');

	} elseif ($command[0] == 'list') {
		$head = 'Currently available Rounds points systems:';
		$list = array();
		$list[] = array('Label', '{#black}System', '{#black}Distribution');
		$lines = 0;
		$admin->msgs = array();
		$admin->msgs[0] = array(1, $head, array(1.3, 0.2, 0.4, 0.7), array('Icons128x32_1', 'RT_Rounds'));
		foreach ($rounds_points as $tag => $points) {
			$list[] = array('{#black}' . $tag, $points[0],
			                implode(',', $points[1]) . ',...');
			if (++$lines > 14) {
				$admin->msgs[] = $list;
				$lines = 0;
				$list = array();
				$list[] = array('Label', '{#black}System', '{#black}Distribution');
			}
		}
		if (!empty($list)) {
			$admin->msgs[] = $list;
		}
		// display ManiaLink message
		display_manialink_multi($admin);

	} elseif ($command[0] == 'show') {
		// get custom points
		$aseco->client->query('GetRoundCustomPoints');
		$points = $aseco->client->getResponse();

		// search for known points system
		$system = false;
		foreach ($rounds_points as $rpoints) {
			if ($points == $rpoints[1]) {
				$system = $rpoints[0];
				break;
			}
		}

		// check for results
		if (empty($points)) {
			$message = formatText($aseco->getChatMessage('NO_RPOINTS'), '{#admin}');
		} else {
			if ($system)
				$message = formatText($aseco->getChatMessage('RPOINTS_NAMED'),
				                      '{#admin}', $system, '{#admin}', implode(',', $points));
			else
				$message = formatText($aseco->getChatMessage('RPOINTS_NAMELESS'),
				                      '{#admin}', implode(',', $points));
		}
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);

	} elseif ($command[0] == 'off') {
		// disable custom points
		$rtn = $aseco->client->query('SetRoundCustomPoints', array(), false);

		// log console message
		$aseco->console('{1} [{2}] disabled custom points', $logtitle, $login);

		// show chat message
		$message = formatText('{#server}>> {#admin}{1}$z$s {#highlite}{2}$z$s{#admin} disables custom rounds points',
		                      $chattitle, $admin->nickname);
		$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($message));

	} elseif (preg_match('/^\d+,[\d,]*\d+$/', $command[0])) {
		// set new custom points as array of ints
		$points = array_map('intval', explode(',', $command[0]));
		$rtn = $aseco->client->query('SetRoundCustomPoints', $points, false);
		if (!$rtn) {
			$message = '{#server}> {#error}Invalid point distribution!  Error: {#highlite}$i ' . $aseco->client->getErrorMessage();
			$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
		} else {
			// log console message
			$aseco->console('{1} [{2}] set new custom points: {3}', $logtitle, $login, $command[0]);

			// show chat message
			$message = formatText('{#server}>> {#admin}{1}$z$s {#highlite}{2}$z$s{#admin} sets custom rounds points: {#highlite}{3},...',
			                      $chattitle, $admin->nickname,
			                      $command[0]);
			$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($message));
		}

	} elseif (array_key_exists($system, $rounds_points)) {
		// set new custom points
		$rtn = $aseco->client->query('SetRoundCustomPoints', $rounds_points[$system][1], false);
		if (!$rtn) {
			trigger_error('[' . $aseco->client->getErrorCode() . '] SetRoundCustomPoints - ' . $aseco->client->getErrorMessage(), E_USER_WARNING);
		} else {
			// log console message
			$aseco->console('{1} [{2}] set new custom points [{3}]', $logtitle, $login, strtoupper($command[0]));

			// show chat message
			$message = formatText('{#server}>> {#admin}{1}$z$s {#highlite}{2}$z$s{#admin} sets rounds points to {#highlite}{3}{#admin}: {#highlite}{4},...',
			                      $chattitle, $admin->nickname,
			                      $rounds_points[$system][0],
			                      implode(',', $rounds_points[$system][1]));
			$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($message));
		}

	} else {
		$message = '{#server}> {#error}Unknown points system {#highlite}$i ' . strtoupper($command[0]) . '$z$s {#error}!';
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
	}
}  // admin_rpoints

function chat_rpoints($aseco, $command) {
	global $rounds_points;

	$login = $command['author']->login;

	if ($aseco->server->getGame() == 'TMF') {
		// get custom points
		$aseco->client->query('GetRoundCustomPoints');
		$points = $aseco->client->getResponse();

		// search for known points system
		$system = false;
		foreach ($rounds_points as $rpoints) {
			if ($points == $rpoints[1]) {
				$system = $rpoints[0];
				break;
			}
		}

		// check for results
		if (empty($points)) {
			$message = formatText($aseco->getChatMessage('NO_RPOINTS'), '');
		} else {
			if ($system)
				$message = formatText($aseco->getChatMessage('RPOINTS_NAMED'),
				                      '', $system, '', implode(',', $points));
			else
				$message = formatText($aseco->getChatMessage('RPOINTS_NAMELESS'),
				                      '', implode(',', $points));
		}
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
	} else {
		$message = $aseco->getChatMessage('FOREVER_ONLY');
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
	}
}  // chat_rpoints
?>
