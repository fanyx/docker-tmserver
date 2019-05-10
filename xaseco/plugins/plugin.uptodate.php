<?php
/* vim: set noexpandtab tabstop=2 softtabstop=2 shiftwidth=2: */

/**
 * Uptodate plugin.
 * Checks XASECO version at start-up & MasterAdmin connect, and provides
 * /admin uptodate command.
 * Also merges global blacklist at MasterAdmin connect, and provides
 * /admin mergegbl command.
 * Created by Xymph
 *
 * Dependencies: used by chat.admin.php
 */

Aseco::registerEvent('onSync', 'start_uptodate');
Aseco::registerEvent('onPlayerConnect', 'connect_uptodate');
Aseco::addChatCommand('uptodate', 'Checks current version of XASECO', true);

function up_to_date($aseco) {

	$version_url = XASECO_TMN . 'version.txt';  // URL to current version file

	// grab version file
	$current = trim(http_get_file($version_url));
	if ($current && $current != -1) {
		// compare versions
		if ($current != XASECO_VERSION) {
			$message = formatText($aseco->getChatMessage('UPTODATE_NEW'), $current,
			                      // hyperlink release page on TMF
			                      ($aseco->server->getGame() == 'TMF' ?
			                       '$l[' . XASECO_TMN . ']' . XASECO_TMN . '$l' :
			                       XASECO_TMN));
		} else {
			$message = formatText($aseco->getChatMessage('UPTODATE_OK'), $current);
		}
	} else {
		$message = false;
	}
	return $message;
}  // up_to_date

// called @ onSync
function start_uptodate($aseco, $command) {
	global $uptodate_check;

	// check version but ignore error
	if ($uptodate_check && $message = up_to_date($aseco)) {
		// show chat message
		$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($message));
	}
}  // start_uptodate

// called @ onPlayerConnect
function connect_uptodate($aseco, $player) {
	global $uptodate_check, $globalbl_merge, $globalbl_url;

	// check for a master admin
	if ($aseco->isMasterAdmin($player)) {
		// check version but ignore error
		if ($uptodate_check && $message = up_to_date($aseco)) {
			// check whether out of date
			if (!preg_match('/' . formatText($aseco->getChatMessage('UPTODATE_OK'), '.*') . '/', $message)) {
				// strip 1 leading '>' to indicate a player message instead of system-wide
				$message = str_replace('{#server}>> ', '{#server}> ', $message);
				// show chat message
				$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $player->login);
			}
		}

		// check whether to merge global black list
		if ($globalbl_merge && $globalbl_url != '') {
			admin_mergegbl($aseco, 'MasterAdmin', $player->login, false, $globalbl_url);
		}
	}
}  // connect_uptodate

function admin_uptodate($aseco, $command) {

	$login = $command['author']->login;

	// check version or report error
	if ($message = up_to_date($aseco)) {
		// strip 1 leading '>' to indicate a player message instead of system-wide
		$message = str_replace('{#server}>> ', '{#server}> ', $message);
		// show chat message
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
	} else {
		$message = '{#server}> {#error}Error: can\'t access the last version!';
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
	}
}  // admin_uptodate

function admin_mergegbl($aseco, $logtitle, $login, $manual, $url) {
	global $globalbl_united;

	// download & parse global black list
	$blacklist = http_get_file($url);
	if ($blacklist && $blacklist != -1) {
		if ($globals = $aseco->xml_parser->parseXml($blacklist, false)) {
			// get current black list
			$blacks = get_blacklist($aseco);  // from chat.admin.php

			// merge new global entries
			$new = 0;
			foreach ($globals['BLACKLIST']['PLAYER'] as $black) {
				if (!array_key_exists($black['LOGIN'][0], $blacks)) {
					// check for United account if needed
					if (!isset($black['ACCOUNT'][0]) || !$globalbl_united ||
					    ($globalbl_united && $black['ACCOUNT'][0] == 'United')) {
						$aseco->client->addCall('BlackList', array($black['LOGIN'][0]));
						$new++;
					}
				}
			}
			// send all entries and ignore results
			if (!$aseco->client->multiquery(true)) {
				trigger_error('[' . $this->client->getErrorCode() . '] BlackList (merge) - ' . $this->client->getErrorMessage(), E_USER_ERROR);
			}

			// update black list file if necessary
			if ($new > 0) {
				$filename = $aseco->settings['blacklist_file'];
				$aseco->client->addCall('SaveBlackList', array($filename));
			}

			// check whether to report new mergers
			if ($new > 0 || $manual) {
				// log console message
				$aseco->console('{1} [{2}] merged global blacklist [{3}] new: {4}', $logtitle, $login, $url, $new);

				// show chat message
				$message = formatText('{#server}> {#highlite}{1} {#server}new login{2} merged into blacklist',
				                      $new, ($new == 1 ? '' : 's'));
				$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
			}
		} else {
			$message = formatText('{#server}> {#error}Error: can\'t parse {#highlite}$i{1}{#error}!',
			                      $url);
			$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
		}
	} else {
		$message = formatText('{#server}> {#error}Error: can\'t access {#highlite}$i{1}{#error}!',
		                      $url);
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $login);
	}
}  // admin_mergegbl
?>
