<?php
/* vim: set noexpandtab tabstop=2 softtabstop=2 shiftwidth=2: */

/**
 * Access Control plugin.
 * Controls player access by nation (TMN) or zone (TMF).
 * Inspired by Apache's mod_access:
 * http://httpd.apache.org/docs/2.0/mod/mod_access.html
 * Created by Xymph
 *
 * Dependencies: none
 */

Aseco::registerEvent('onStartup', 'access_initcontrol');
Aseco::registerEvent('onPlayerConnect2', 'access_playerconnect');  // use post event after all join processing

global $access_control;
	// ['order'] - boolean: allow,deny = true; deny,allow = false
	// ['allowall'] - boolean: Allow from all = true; otherwise false
	// ['allow'] - array of nations/zones to allow
	// ['denyall'] - boolean: Deny from all = true; otherwise false
	// ['deny'] - array of nations/zones to deny

// called @ onStartup
function access_initcontrol($aseco, $reload = false) {
	global $access_control;

	// initialize access control
	$access_control = array();
	$access_control['order'] = false;
	$access_control['allowall'] = false;
	$access_control['allow'] = array();
	$access_control['denyall'] = false;
	$access_control['deny'] = array();
	$access_control['messages'] = array();

	$error = '';
	// log console message
	if (!$reload)
		$aseco->console('Load player access control [access.xml]');

	// read & parse config file
	if (!$settings = $aseco->xml_parser->parseXml('access.xml'))
		$error = 'Could not read/parse access control config file access.xml !';

	// check/store Order section
	if (!$error) {
		if (isset($settings['ACCESS']['ORDER'][0])) {
			// strip all spaces
			$order = str_replace(' ', '', strtolower($settings['ACCESS']['ORDER'][0]));
			if ($order == 'allow,deny')
				$access_control['order'] = true;
			elseif ($order == 'deny,allow')
				$access_control['order'] = false;
			else {
				$error = 'Access control config invalid \'order\' section: "' . $settings['ACCESS']['ORDER'][0] . '"';
			}
		} else {
			$error = 'Access control config missing section: order';
		}
	}

	// check/store Allow section
	if (!$error) {
		if (isset($settings['ACCESS']['ALLOW'][0])) {
			// check/store From entry(ies)
			if (isset($settings['ACCESS']['ALLOW'][0]['FROM'])) {
				foreach ($settings['ACCESS']['ALLOW'][0]['FROM'] as $from) {
					if ($from == 'all') {
						if (count($settings['ACCESS']['ALLOW'][0]['FROM']) > 1) {
							$error = 'Access control config \'allow\' section contains more besides "all" value';
							break;
						} else {
							$access_control['allowall'] = true;
						}
					} else {  // != 'all'
						if (in_array($from, $access_control['allow'])) {
							$error = 'Access control config \'allow\' section contains duplicate value: ' . $from;
							break;
						} else {
							if ($from != '')  // ignore empty entries
								$access_control['allow'][] = $from;
						}
					}
				}
			} else {
				$error = 'Access control config \'allow\' section must contain at least one \'from\' entry';
			}
		} else {
			$error = 'Access control config missing section: allow';
		}
	}

	// check/store Deny section
	if (!$error) {
		if (isset($settings['ACCESS']['DENY'][0])) {
			// check/store From entry(ies)
			if (isset($settings['ACCESS']['DENY'][0]['FROM'])) {
				foreach ($settings['ACCESS']['DENY'][0]['FROM'] as $from) {
					if ($from == 'all') {
						if (count($settings['ACCESS']['DENY'][0]['FROM']) > 1) {
							$error = 'Access control config \'deny\' section contains more besides "all" value';
							break;
						} else {
							$access_control['denyall'] = true;
						}
					} else {  // != 'all'
						if (in_array($from, $access_control['deny'])) {
							$error = 'Access control config \'deny\' section contains duplicate value: ' . $from;
							break;
						} else {
							if ($from != '')  // ignore empty entries
								$access_control['deny'][] = $from;
						}
					}
				}
			} else {
				$error = 'Access control config \'deny\' section must contain at least one \'from\' entry';
			}
		} else {
			$error = 'Access control config missing section: deny';
		}
	}

	// final consistency check
	if (!$error && $access_control['allowall'] && $access_control['denyall'])
		$error = 'Access control config \'allow\' & \'deny\' sections cannot both use "all" value';

	// load messages
	if (!$error) {
		if (isset($settings['ACCESS']['MESSAGES'][0])) {
			if (isset($settings['ACCESS']['MESSAGES'][0]['DENIED'][0]))
				$access_control['messages']['denied'] = $settings['ACCESS']['MESSAGES'][0]['DENIED'][0];
			else
				$error = 'Access control config \'messages\' section missing value: denied';
			if (isset($settings['ACCESS']['MESSAGES'][0]['DIALOG'][0]))
				$access_control['messages']['dialog'] = $settings['ACCESS']['MESSAGES'][0]['DIALOG'][0];
			else
				$error = 'Access control config \'messages\' section missing value: dialog';
			if (isset($settings['ACCESS']['MESSAGES'][0]['RELOAD'][0]))
				$access_control['messages']['reload'] = $settings['ACCESS']['MESSAGES'][0]['RELOAD'][0];
			else
				$error = 'Access control config \'messages\' section missing value: reload';
			if (isset($settings['ACCESS']['MESSAGES'][0]['XMLERR'][0]))
				$access_control['messages']['xmlerr'] = $settings['ACCESS']['MESSAGES'][0]['XMLERR'][0];
			else
				$error = 'Access control config \'messages\' section missing value: xmlerr';
			if (isset($settings['ACCESS']['MESSAGES'][0]['MISSING'][0]))
				$access_control['messages']['missing'] = $settings['ACCESS']['MESSAGES'][0]['MISSING'][0];
			else
				$error = 'Access control config \'messages\' section missing value: missing';
		} else {
			$error = 'Access control config missing section: messages';
		}
	}

	if (!$error) {
		// sort access lists
		sort($access_control['allow']);
		sort($access_control['deny']);

		// log console message
		if ($reload)
			$aseco->console('Player access control reloaded from access.xml');

		return true;
	} else {
		// log error message
		trigger_error($error, E_USER_WARNING);
		$access_control = array();
		return false;
	}
}  // access_initcontrol

function in_zones($access, $zones) {

	// check all zones for matching (leading part of) player's zone
	foreach ($zones as $zone)
		if (strpos($access, $zone) === 0)
			return true;

	return false;
}  // in_zones

// called @ onPlayerConnect2
function access_playerconnect($aseco, $player) {
	global $access_control;

	// if no access control, bail out immediately
	if (!$access_control) return;

	// get nation/zone to check for access
	if ($aseco->server->getGame() == 'TMF') {
		$access = $player->zone;
	} elseif ($aseco->server->getGame() == 'TMN') {
		$access = $player->nation;
	} else { // TMS/TMO
		return;  // no access control
	}

	// check for empty nation/zone
	if ($access == '') {
		if ($access_control['order']) {  // Allow,Deny
			;  // default denied
		} else {  // Deny,Allow
			return;  // default allowed
		}
	} else {
		if ($aseco->server->getGame() == 'TMF') {
			if ($access_control['order']) {  // Allow,Deny
				// first check Allow list
				if ($access_control['allowall'] || in_zones($access, $access_control['allow'])) {
					// then check Deny list
					if ($access_control['denyall'] || in_zones($access, $access_control['deny']))
						;  // deny this nation
					else
						return;  // allow this nation
				}
				else
					;  // deny this nation
			} else {  // Deny,Allow
				// first check Deny list
				if ($access_control['denyall'] || in_zones($access, $access_control['deny'])) {
					// then check Allow list
					if ($access_control['allowall'] || in_zones($access, $access_control['allow']))
						return;  // allow this nation
					else
						;  // deny this nation
				}
				else
					return;  // allow this nation
			}

		} else {  // TMN
			if ($access_control['order']) {  // Allow,Deny
				// first check Allow list
				if ($access_control['allowall'] || in_array($access, $access_control['allow'])) {
					// then check Deny list
					if ($access_control['denyall'] || in_array($access, $access_control['deny']))
						;  // deny this nation
					else
						return;  // allow this nation
				}
				else
					;  // deny this nation
			} else {  // Deny,Allow
				// first check Deny list
				if ($access_control['denyall'] || in_array($access, $access_control['deny'])) {
					// then check Allow list
					if ($access_control['allowall'] || in_array($access, $access_control['allow']))
						return;  // allow this nation
					else
						;  // deny this nation
				}
				else
					return;  // allow this nation
			}
		}
	}

	// log & kick player
	$aseco->console('Player \'{1}\' denied access from "{2}" - kicking...', $player->login, $access);

	$message = formatText($access_control['messages']['denied'],
	                      stripColors($player->nickname),
	                      ($aseco->server->getGame() == 'TMF' ? 'zone' : 'nation'),
	                      $access);
	$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($message));
	if ($aseco->server->getGame() == 'TMF') {
		$message = formatText($access_control['messages']['dialog'],
		                      $access);
		$aseco->client->addCall('Kick', array($player->login, $aseco->formatColors($message)));
	} else {
		$aseco->client->addCall('Kick', array($player->login));
	}
}  // access_playerconnect


function admin_access($aseco, $command) {
	global $access_control;

	$player = $command['author'];
	$login = $player->login;

	if ($command['params'] == 'help') {
		if ($aseco->server->getGame() == 'TMF') {
			$header = '{#black}/admin access$g handles player access control:';
			$help = array();
			$help[] = array('...', '{#black}help',
			                'Displays this help information');
			$help[] = array('...', '{#black}list',
			                'Displays current access control settings');
			$help[] = array('...', '{#black}reload',
			                'Reloads updated access control settings');

			// display ManiaLink message
			display_manialink($login, $header, array('Icons64x64_1', 'TrackInfo', -0.01), $help, array(0.8, 0.05, 0.15, 0.6), 'OK');
		} elseif ($aseco->server->getGame() == 'TMN') {
			$help = '{#black}/admin access$g handles player access control:' . LF;
			$help .= '  - {#black}help$g, displays this help information' . LF;
			$help .= '  - {#black}list$g, lists current settings' . LF;
			$help .= '  - {#black}reload$g, reloads updated settings' . LF;

			// display popup message
			$aseco->client->query('SendDisplayServerMessageToLogin', $login, $aseco->formatColors($help), 'OK', '', 0);
		}
	}

	elseif ($command['params'] == 'list') {
		$player->msgs = array();

		if ($aseco->server->getGame() == 'TMF') {
			$head = 'Current player access control settings:';
			$info = array();
			// initialize with Order entry
			$info[] = array('Order:', '{#black}' . ($access_control['order'] ? 'Allow,Deny' : 'Deny,Allow'));
			$info[] = array();

			$lines = 2;
			$player->msgs[0] = array(1, $head, array(1.0, 0.2, 0.8), array('Icons128x128_1', 'ManiaZones'));

			// collect Allow entries
			$info[] = array('Allow:', '');
			$lines++;
			if ($access_control['allowall']) {
				$info[] = array('', '{#black}all');
				$lines++;
			} else {
				foreach ($access_control['allow'] as $from) {
					$info[] = array('', '{#black}' . $from);
					if (++$lines > 14) {
						$player->msgs[] = $info;
						$lines = 0;
						$info = array();
					}
				}
			}

			// insert spacer
			$info[] = array();
			if (++$lines > 14) {
				$player->msgs[] = $info;
				$lines = 0;
				$info = array();
			}

			// collect Deny entries
			$info[] = array('Deny:', '');
			$lines++;
			if ($access_control['denyall']) {
				$info[] = array('', '{#black}all');
				$lines++;
			} else {
				foreach ($access_control['deny'] as $from) {
					$info[] = array('', '{#black}' . $from);
					if (++$lines > 14) {
						$player->msgs[] = $info;
						$lines = 0;
						$info = array();
					}
				}
			}

			// add if last batch exists
			if (count($info) > 1)
				$player->msgs[] = $info;

			// display ManiaLink message
			display_manialink_multi($player);

		} elseif ($aseco->server->getGame() == 'TMN') {
			$head = 'Current player access control settings:' . LF;
			// initialize with Order entry
			$info = 'Order: {#black}' . ($access_control['order'] ? 'Allow,Deny' : 'Deny,Allow') . LF . LF;
			$lines = 2;
			$player->msgs[0] = 1;

			// collect Allow entries
			$info .= '$gAllow:' . LF;
			$lines++;
			if ($access_control['allowall']) {
				$info .= '           {#black}all' . LF;
				$lines++;
			} else {
				foreach ($access_control['allow'] as $from) {
					$info .= '           {#black}' . $from . LF;
					if (++$lines > 9) {
						$player->msgs[] = $aseco->formatColors($head . $info);
						$lines = 0;
						$info = '';
					}
				}
			}

			// insert spacer
			$info .= LF;
			if (++$lines > 9) {
				$player->msgs[] = $aseco->formatColors($head . $info);
				$lines = 0;
				$info = '';
			}

			// collect Deny entries
			$info .= '$gDeny:' . LF;
			$lines++;
			if ($access_control['denyall']) {
				$info .= '           {#black}all' . LF;
				$lines++;
			} else {
				foreach ($access_control['deny'] as $from) {
					$info .= '           {#black}' . $from . LF;
					if (++$lines > 9) {
						$player->msgs[] = $aseco->formatColors($head . $info);
						$lines = 0;
						$info = '';
					}
				}
			}

			// add if last batch exists
			if ($info != '')
				$player->msgs[] = $aseco->formatColors($head . $info);

			// display popup message
			if (count($player->msgs) == 2) {
				$aseco->client->query('SendDisplayServerMessageToLogin', $login, $player->msgs[1], 'OK', '', 0);
			} elseif (count($player->msgs) > 2) {
				$aseco->client->query('SendDisplayServerMessageToLogin', $login, $player->msgs[1], 'Close', 'Next', 0);
			}
		}
	}

	elseif ($command['params'] == 'reload') {
		// reload/check access control
		if (access_initcontrol($aseco, true)) {
			$message = $access_control['messages']['reload'];
		} else {
			$access_control = array();
			$message = $access_control['messages']['xmlerr'];
		}
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
	}
	else {
		$message = $access_control['messages']['missing'];
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
	}
}  // admin_access
?>
