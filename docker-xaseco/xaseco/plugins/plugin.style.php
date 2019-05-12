<?php
/* vim: set noexpandtab tabstop=2 softtabstop=2 shiftwidth=2: */

/**
 * Style plugin (TMF).
 * Selects ManiaLink window style templates.
 * Created by Xymph
 *
 * Dependencies: none
 */

Aseco::registerEvent('onStartup', 'style_default');
Aseco::registerEvent('onPlayerConnect', 'init_playerstyle');

// handles action id's "49"-"100" for selecting from max. 50 style templates
Aseco::registerEvent('onPlayerManialinkPageAnswer', 'event_style');

Aseco::addChatCommand('style', 'Selects window style (see: /style help)');

// called @ onStartup
function style_default($aseco) {

	// check for non-TMN style
	if ($aseco->server->getGame() == 'TMF' && $aseco->settings['window_style'] != '') {
		$style_file = 'styles/' . $aseco->settings['window_style'] . '.xml';
		$aseco->console('Load default style [{1}]', $style_file);
		// load default style
		if (($aseco->style = $aseco->xml_parser->parseXml($style_file)) && isset($aseco->style['STYLES'])) {
			$aseco->style = $aseco->style['STYLES'];
		} else {
			// Could not parse XML file
			trigger_error('Could not read/parse style file ' . $style_file . ' !', E_USER_ERROR);
		}
	}
}  // style_default

// called @ onPlayerConnect
function init_playerstyle($aseco, $player) {

	if ($style = ldb_getStyle($aseco, $player->login)) {
		// load player's personal style
		$style_file = 'styles/' . $style . '.xml';
		if (($player->style = $aseco->xml_parser->parseXml($style_file)) && isset($player->style['STYLES'])) {
			$player->style = $player->style['STYLES'];
		} else {
			// Could not parse XML file
			trigger_error('Could not read/parse style file ' . $style_file . ' !', E_USER_WARNING);
		}
	}
}  // init_playerstyle

function chat_style($aseco, $command) {

	$player = $command['author'];
	$login = $player->login;

	if ($aseco->server->getGame() != 'TMF') {
		$message = $aseco->getChatMessage('FOREVER_ONLY');
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
		return;
	}

	if ($command['params'] == 'help') {
		$header = '{#black}/style$g will change the window style:';
		$help = array();
		$help[] = array('...', '{#black}help',
		                'Displays this help information');
		$help[] = array('...', '{#black}list',
		                'Displays available styles');
		$help[] = array('...', '{#black}default',
		                'Resets style to server default');
		$help[] = array('...', '{#black}off',
		                'Disables TMF window style');
		$help[] = array('...', '{#black}xxx',
		                'Selects window style xxx');
		// display ManiaLink message
		display_manialink($login, $header, array('Icons64x64_1', 'TrackInfo', -0.01), $help, array(0.8, 0.05, 0.15, 0.6), 'OK');
	}

	elseif ($command['params'] == 'list') {
		$player->tracklist = array();

		// read list of style files
		$styledir = 'styles/';
		$dir = opendir($styledir);
		$files = array();
		while (($file = readdir($dir)) !== false) {
			if (strtolower(substr($file, -4)) == '.xml')
				$files[] = substr($file, 0, strlen($file)-4);
		}
		closedir($dir);
		sort($files, SORT_STRING);
		if (count($files) > 50) {
			$files = array_slice($files, 0, 50);  // maximum 50 templates
			trigger_error('Too many style templates - maximum 50!', E_USER_WARNING);
		}
		// sneak in standard entries
		$files[] = 'default';
		$files[] = 'off';

		$head = 'Currently available window styles:';
		$list = array();
		$sid = 1;
		$lines = 0;
		$player->msgs = array();
		$player->msgs[0] = array(1, $head, array(0.8, 0.1, 0.7), array('Icons64x64_1', 'Windowed'));
		foreach ($files as $file) {
			// store style in player object for jukeboxing
			$trkarr = array();
			$trkarr['style'] = $file;
			$player->tracklist[] = $trkarr;

			$list[] = array(str_pad($sid, 2, '0', STR_PAD_LEFT) . '.',
			                array('{#black}' . $file, $sid+48));  // action id
			$sid++;
			if (++$lines > 14) {
				$player->msgs[] = $list;
				$lines = 0;
				$list = array();
			}
		}
		// add if last batch exists
		if (!empty($list))
			$player->msgs[] = $list;

		// display ManiaLink message
		display_manialink_multi($player);
	}

	elseif ($command['params'] != '') {
		$style = $command['params'];
		if (is_numeric($style) && $style > 0) {
			$sid = ltrim($style, '0');
			$sid--;
			if (array_key_exists($sid, $player->tracklist) &&
			    isset($player->tracklist[$sid]['style'])) {
				$style = $player->tracklist[$sid]['style'];
			}
		}
		if ($style == 'off') {
			$player->style = array();
			$message = '{#server}> TMF window style disabled!';
			ldb_setStyle($aseco, $login, '');
		}
		elseif ($style == 'default') {
			$player->style = $aseco->style;
			$message = '{#server}> Style reset to server default {#highlite}' . $aseco->settings['window_style'] . '{#server} !';
			ldb_setStyle($aseco, $login, $aseco->settings['window_style']);
		}
		else {
			$style_file = 'styles/' . $style . '.xml';
			// load new style
			if (($styledata = $aseco->xml_parser->parseXml($style_file)) && isset($styledata['STYLES'])) {
				$player->style = $styledata['STYLES'];
				$message = '{#server}> Style {#highlite}' . $command['params'] . '{#server} selected!';
				ldb_setStyle($aseco, $login, $style);
			} else {
				// Could not parse XML file
				trigger_error('Could not read/parse style file ' . $style_file . ' !', E_USER_WARNING);
				$message = '{#server}> {#error}No valid style file, use {#highlite}$i /style list {#error}!';
			}
		}
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
	}

	else {
		$message = '{#server}> {#error}No style specified, use {#highlite}$i /style help {#error}!';
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
	}
}  // chat_style


// called @ onPlayerManialinkPageAnswer
// Handles ManiaLink style responses
// [0]=PlayerUid, [1]=Login, [2]=Answer
function event_style($aseco, $answer) {

	// leave actions outside 49 - 100 to other handlers
	if ($answer[2] >= 49 && $answer[2] <= 100) {
		// get player & style
		$player = $aseco->server->players->getPlayer($answer[1]);
		$style = $player->tracklist[$answer[2]-49]['style'];

		// log clicked command
		$aseco->console('player {1} clicked command "/style {2}"',
		                $player->login, $style);

		// select new style & refresh list
		$command = array();
		$command['author'] = $player;
		$command['params'] = $style;
		chat_style($aseco, $command);

		// log clicked command
		$aseco->console('player {1} clicked command "/style list"', $player->login);

		// display restyled list
		$command['params'] = 'list';
		chat_style($aseco, $command);
	}
}  // event_style
?>
