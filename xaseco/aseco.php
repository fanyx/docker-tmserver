<?php
/* vim: set noexpandtab tabstop=2 softtabstop=2 shiftwidth=2: */

/**
 * Projectname: XASECO (formerly ASECO/RASP)
 *
 * Requires: PHP version 5, MySQL version 4/5
 *
 * LICENSE: This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as published
 * by the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA
 *
 * @license             http://www.gnu.org/copyleft/gpl.html GNU GPL
 *
 * Authored & copyright 2006 by Florian Schnell <floschnell@gmail.com>
 *
 * Re-authored & copyright May 2007 - Jul 2013 by Xymph <tm@gamers.org>
 *
 * Visit the official site at http://www.xaseco.org/
 */

/**
 * Include required classes
 */
require_once('includes/types.inc.php');  // contains classes to store information
require_once('includes/basic.inc.php');  // contains standard functions
require_once('includes/GbxRemote.inc.php');  // needed for dedicated server connections
require_once('includes/xmlparser.inc.php');  // provides an XML parser
require_once('includes/gbxdatafetcher.inc.php');  // provides access to GBX data
require_once('includes/tmndatafetcher.inc.php');  // provides access to TMN world stats
require_once('includes/rasp.settings.php');  // specific to the RASP plugins

/**
 * Runtime configuration definitions
 */

// add abbreviations for some chat commands?
// /admin -> /ad, /jukebox -> /jb, /autojuke -> /aj
define('ABBREV_COMMANDS', false);
// disable local & Dedi record relations commands from help lists?
define('INHIBIT_RECCMDS', false);
// separate logs by month in logs/ dir?
define('MONTHLY_LOGSDIR', false);
// keep UTF-8 encoding in config.xml?
define('CONFIG_UTF8ENCODE', false);

/**
 * System definitions - no changes below this point
 */

// current project version
define('XASECO_VERSION', '1.16');
define('XASECO_TMN', 'http://www.gamers.org/tmn/');
define('XASECO_TMF', 'http://www.gamers.org/tmf/');
define('XASECO_TM2', 'http://www.gamers.org/tm2/');
define('XASECO_ORG', 'http://www.xaseco.org/');

// required official dedicated server builds
define('TMN_BUILD', '2006-05-30');
define('TMF_BUILD', '2011-02-21');

// check current operating system
if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
	// on Win32/NT use:
	define('CRLF', "\r\n");
} else {
	// on Unix use:
	define('CRLF', "\n");
}
if (!defined('LF')) {
	define('LF', "\n");
}

/**
 * Error function
 * Report errors in a regular way.
 */
set_error_handler('displayError');
function displayError($errno, $errstr, $errfile, $errline) {
	global $aseco;

	// check for error suppression
	if (error_reporting() == 0) return;

	switch ($errno) {
	case E_USER_ERROR:
		$message = "[XASECO Fatal Error] $errstr on line $errline in file $errfile" . CRLF;
		echo $message;
		doLog($message);

		// throw 'shutting down' event
		$aseco->releaseEvent('onShutdown', null);
		// clear all ManiaLinks
		$aseco->client->query('SendHideManialinkPage');

		if (function_exists('xdebug_get_function_stack'))
			doLog(print_r(xdebug_get_function_stack()), true);
		die();
		break;
	case E_USER_WARNING:
		$message = "[XASECO Warning] $errstr" . CRLF;
		echo $message;
		doLog($message);
		break;
	case E_ERROR:
		$message = "[PHP Error] $errstr on line $errline in file $errfile" . CRLF;
		echo $message;
		doLog($message);
		break;
	case E_WARNING:
		$message = "[PHP Warning] $errstr on line $errline in file $errfile" . CRLF;
		echo $message;
		doLog($message);
		break;
	default:
		if (strpos($errstr, 'Function call_user_method') !== false) break;
		//$message = "[PHP $errno] $errstr on line $errline in file $errfile" . CRLF;
		//echo $message;
		//doLog($message);
		// do nothing, only treat known errors
	}
}  // displayError

/**
 * Here XASECO actually starts.
 */
class Aseco {

	/**
	 * Public fields
	 */
	var $client;
	var $xml_parser;
	var $script_timeout;
	var $debug;
	var $server;
	var $command;
	var $events;
	var $rpc_calls;
	var $rpc_responses;
	var $chat_commands;
	var $chat_colors;
	var $chat_messages;
	var $plugins;
	var $settings;
	var $style;
	var $panels;
	var $statspanel;
	var $titles;
	var $masteradmin_list;
	var $admin_list;
	var $adm_abilities;
	var $operator_list;
	var $op_abilities;
	var $bannedips;
	var $startup_phase;  // XAseco start-up phase
	var $warmup_phase;  // warm-up phase
	var $restarting;  // restarting challenge (0 = not, 1 = instant, 2 = chattime)
	var $changingmode;  // changing game mode
	var $currstatus;  // server status changes
	var $prevstatus;
	var $currsecond;  // server time changes
	var $prevsecond;
	var $uptime;  // XAseco start-up time


	/**
	 * Initializes the server.
	 */
	function Aseco($debug) {
		global $maxrecs;  // from rasp.settings.php

		echo '# initialize XASECO ###########################################################' . CRLF;

		// log php & mysql version info
		$this->console_text('[XAseco] PHP Version is ' . phpversion() . ' on ' . PHP_OS);

		// initialize
		$this->uptime = time();
		$this->chat_commands = array();
		$this->debug = $debug;
		$this->client = new IXR_ClientMulticall_Gbx();
		$this->xml_parser = new Examsly();
		$this->server = new Server('127.0.0.1', 5000, 'SuperAdmin', 'SuperAdmin');
		$this->server->challenge = new Challenge();
		$this->server->players = new PlayerList();
		$this->server->records = new RecordList($maxrecs);
		$this->server->mutelist = array();
		$this->plugins = array();
		$this->titles = array();
		$this->masteradmin_list = array();
		$this->admin_list = array();
		$this->adm_abilities = array();
		$this->operator_list = array();
		$this->op_abilities = array();
		$this->bannedips = array();
		$this->startup_phase = true;
		$this->warmup_phase = false;
		$this->restarting = 0;
		$this->changingmode = false;
		$this->currstatus = 0;
	}  // Aseco


	/**
	 * Load settings and apply them on the current instance.
	 */
	function loadSettings($config_file) {

		if ($settings = $this->xml_parser->parseXml($config_file, true, CONFIG_UTF8ENCODE)) {
			// read the XML structure into an array
			$aseco = $settings['SETTINGS']['ASECO'][0];

			// read settings and apply them
			$this->chat_colors = $aseco['COLORS'][0];
			$this->chat_messages = $aseco['MESSAGES'][0];
			$this->masteradmin_list = $aseco['MASTERADMINS'][0];
			if (!isset($this->masteradmin_list) || !is_array($this->masteradmin_list))
				trigger_error('No MasterAdmin(s) configured in config.xml!', E_USER_ERROR);

			// check masteradmin list consistency
			if (empty($this->masteradmin_list['IPADDRESS'])) {
				// fill <ipaddress> list to same length as <tmlogin> list
				if (($cnt = count($this->masteradmin_list['TMLOGIN'])) > 0)
					$this->masteradmin_list['IPADDRESS'] = array_fill(0, $cnt, '');
			} else {
				if (count($this->masteradmin_list['TMLOGIN']) != count($this->masteradmin_list['IPADDRESS']))
					trigger_error("MasterAdmin mismatch between <tmlogin>'s and <ipaddress>'s!", E_USER_WARNING);
			}

			// set admin lock password
			$this->settings['lock_password'] = $aseco['LOCK_PASSWORD'][0];
			// set cheater action
			$this->settings['cheater_action'] = $aseco['CHEATER_ACTION'][0];
			// set script timeout
			$this->settings['script_timeout'] = $aseco['SCRIPT_TIMEOUT'][0];
			// set minimum number of records to be displayed
			$this->settings['show_min_recs'] = $aseco['SHOW_MIN_RECS'][0];
			// show records before start of track?
			$this->settings['show_recs_before'] = $aseco['SHOW_RECS_BEFORE'][0];
			// show records after end of track?
			$this->settings['show_recs_after'] = $aseco['SHOW_RECS_AFTER'][0];
			// show TMX world record?
			$this->settings['show_tmxrec'] = $aseco['SHOW_TMXREC'][0];
			// show played time at end of track?
			$this->settings['show_playtime'] = $aseco['SHOW_PLAYTIME'][0];
			// show current track at start of track?
			$this->settings['show_curtrack'] = $aseco['SHOW_CURTRACK'][0];
			// set default filename for readtracklist/writetracklist
			$this->settings['default_tracklist'] = $aseco['DEFAULT_TRACKLIST'][0];
			// set minimum number of ranked players in a clan to be included in /topclans
			$this->settings['topclans_minplayers'] = $aseco['TOPCLANS_MINPLAYERS'][0];
			// set multiple of win count to show global congrats message
			$this->settings['global_win_multiple'] = ($aseco['GLOBAL_WIN_MULTIPLE'][0] > 0 ? $aseco['GLOBAL_WIN_MULTIPLE'][0] : 1);
			// timeout of the TMF message window in seconds
			$this->settings['window_timeout'] = $aseco['WINDOW_TIMEOUT'][0];
			// set filename of admin/operator/ability lists file
			$this->settings['adminops_file'] = $aseco['ADMINOPS_FILE'][0];
			// set filename of banned IPs list file
			$this->settings['bannedips_file'] = $aseco['BANNEDIPS_FILE'][0];
			// set filename of blacklist file
			$this->settings['blacklist_file'] = $aseco['BLACKLIST_FILE'][0];
			// set filename of guestlist file
			$this->settings['guestlist_file'] = $aseco['GUESTLIST_FILE'][0];
			// set filename of track history file
			$this->settings['trackhist_file'] = $aseco['TRACKHIST_FILE'][0];
			// set minimum admin client version
			$this->settings['admin_client'] = $aseco['ADMIN_CLIENT_VERSION'][0];
			// set minimum player client version
			$this->settings['player_client'] = $aseco['PLAYER_CLIENT_VERSION'][0];
			// set default rounds points system
			$this->settings['default_rpoints'] = $aseco['DEFAULT_RPOINTS'][0];
			// set windows style (none = old TMN style)
			$this->settings['window_style'] = $aseco['WINDOW_STYLE'][0];
			// set admin panel (none = no panel)
			$this->settings['admin_panel'] = $aseco['ADMIN_PANEL'][0];
			// set donate panel (none = no panel)
			$this->settings['donate_panel'] = $aseco['DONATE_PANEL'][0];
			// set records panel (none = no panel)
			$this->settings['records_panel'] = $aseco['RECORDS_PANEL'][0];
			// set vote panel (none = no panel)
			$this->settings['vote_panel'] = $aseco['VOTE_PANEL'][0];

			// display welcome message as window ?
			if (strtoupper($aseco['WELCOME_MSG_WINDOW'][0]) == 'TRUE') {
				$this->settings['welcome_msg_window'] = true;
			} else {
				$this->settings['welcome_msg_window'] = false;
			}

			// log all chat, not just chat commands ?
			if (strtoupper($aseco['LOG_ALL_CHAT'][0]) == 'TRUE') {
				$this->settings['log_all_chat'] = true;
			} else {
				$this->settings['log_all_chat'] = false;
			}

			// show timestamps in /chatlog, /pmlog & /admin pmlog ?
			if (strtoupper($aseco['CHATPMLOG_TIMES'][0]) == 'TRUE') {
				$this->settings['chatpmlog_times'] = true;
			} else {
				$this->settings['chatpmlog_times'] = false;
			}

			// show records range?
			if (strtoupper($aseco['SHOW_RECS_RANGE'][0]) == 'TRUE') {
				$this->settings['show_recs_range'] = true;
			} else {
				$this->settings['show_recs_range'] = false;
			}

			// show records in message window?
			if (strtoupper($aseco['RECS_IN_WINDOW'][0]) == 'TRUE') {
				$this->settings['recs_in_window'] = true;
			} else {
				$this->settings['recs_in_window'] = false;
			}

			// show round reports in message window?
			if (strtoupper($aseco['ROUNDS_IN_WINDOW'][0]) == 'TRUE') {
				$this->settings['rounds_in_window'] = true;
			} else {
				$this->settings['rounds_in_window'] = false;
			}

			// add random filter to /admin writetracklist output
			if (strtoupper($aseco['WRITETRACKLIST_RANDOM'][0]) == 'TRUE') {
				$this->settings['writetracklist_random'] = true;
			} else {
				$this->settings['writetracklist_random'] = false;
			}

			// add explanation to /help output
			if (strtoupper($aseco['HELP_EXPLANATION'][0]) == 'TRUE') {
				$this->settings['help_explanation'] = true;
			} else {
				$this->settings['help_explanation'] = false;
			}

			// color nicknames in the various /top... etc lists?
			if (strtoupper($aseco['LISTS_COLORNICKS'][0]) == 'TRUE') {
				$this->settings['lists_colornicks'] = true;
			} else {
				$this->settings['lists_colornicks'] = false;
			}

			// color tracknames in the various /lists... lists?
			if (strtoupper($aseco['LISTS_COLORTRACKS'][0]) == 'TRUE') {
				$this->settings['lists_colortracks'] = true;
			} else {
				$this->settings['lists_colortracks'] = false;
			}

			// display checkpoints panel (TMF) or pop-up (TMN)?
			if (strtoupper($aseco['DISPLAY_CHECKPOINTS'][0]) == 'TRUE') {
				$this->settings['display_checkpoints'] = true;
			} else {
				$this->settings['display_checkpoints'] = false;
			}

			// enable /cpsspec command (TMF-only)?
			if (strtoupper($aseco['ENABLE_CPSSPEC'][0]) == 'TRUE') {
				$this->settings['enable_cpsspec'] = true;
			} else {
				$this->settings['enable_cpsspec'] = false;
			}

			// automatically enable /cps for new players?
			if (strtoupper($aseco['AUTO_ENABLE_CPS'][0]) == 'TRUE') {
				$this->settings['auto_enable_cps'] = true;
			} else {
				$this->settings['auto_enable_cps'] = false;
			}

			// automatically enable /dedicps for new players?
			if (strtoupper($aseco['AUTO_ENABLE_DEDICPS'][0]) == 'TRUE') {
				$this->settings['auto_enable_dedicps'] = true;
			} else {
				$this->settings['auto_enable_dedicps'] = false;
			}

			// automatically add IP for new admins/operators?
			if (strtoupper($aseco['AUTO_ADMIN_ADDIP'][0]) == 'TRUE') {
				$this->settings['auto_admin_addip'] = true;
			} else {
				$this->settings['auto_admin_addip'] = false;
			}

			// automatically force spectator on player using /afk ?
			if (strtoupper($aseco['AFK_FORCE_SPEC'][0]) == 'TRUE') {
				$this->settings['afk_force_spec'] = true;
			} else {
				$this->settings['afk_force_spec'] = false;
			}

			// provide clickable buttons in TMF lists?
			if (strtoupper($aseco['CLICKABLE_LISTS'][0]) == 'TRUE') {
				$this->settings['clickable_lists'] = true;
			} else {
				$this->settings['clickable_lists'] = false;
			}

			// show logins in /recs on TMF?
			if (strtoupper($aseco['SHOW_REC_LOGINS'][0]) == 'TRUE') {
				$this->settings['show_rec_logins'] = true;
			} else {
				$this->settings['show_rec_logins'] = false;
			}

			// display individual stats panels at TMF scoreboard?
			if (strtoupper($aseco['SB_STATS_PANELS'][0]) == 'TRUE') {
				$this->settings['sb_stats_panels'] = true;
			} else {
				$this->settings['sb_stats_panels'] = false;
			}

			// read the XML structure into an array
			$tmserver = $settings['SETTINGS']['TMSERVER'][0];

			// read settings and apply them
			$this->server->login = $tmserver['LOGIN'][0];
			$this->server->pass = $tmserver['PASSWORD'][0];
			$this->server->port = $tmserver['PORT'][0];
			$this->server->ip = $tmserver['IP'][0];
			if (isset($tmserver['TIMEOUT'][0])) {
				$this->server->timeout = (int)$tmserver['TIMEOUT'][0];
			} else {
				$this->server->timeout = null;
				trigger_error('Server init timeout not specified in config.xml !', E_USER_WARNING);
			}

			$this->style = array();
			$this->panels = array();
			$this->panels['admin'] = '';
			$this->panels['donate'] = '';
			$this->panels['records'] = '';
			$this->panels['vote'] = '';

			if ($this->settings['admin_client'] != '' &&
			    preg_match('/^2\.11\.[12][0-9]$/', $this->settings['admin_client']) != 1 ||
			    $this->settings['admin_client'] == '2.11.10')
				trigger_error('Invalid admin client version : ' . $this->settings['admin_client'] . ' !', E_USER_ERROR);
			if ($this->settings['player_client'] != '' &&
			    preg_match('/^2\.11\.[12][0-9]$/', $this->settings['player_client']) != 1 ||
			    $this->settings['player_client'] == '2.11.10')
				trigger_error('Invalid player client version: ' . $this->settings['player_client'] . ' !', E_USER_ERROR);
		} else {
			// could not parse XML file
			trigger_error('Could not read/parse config file ' . $config_file . ' !', E_USER_ERROR);
		}
	}  // loadSettings


	/**
	 * Read Admin/Operator/Ability lists and apply them on the current instance.
	 */
	function readLists() {

		// get lists file name
		$adminops_file = $this->settings['adminops_file'];

		if ($lists = $this->xml_parser->parseXml($adminops_file, true, true)) {
			// read the XML structure into arrays
			$this->titles = $lists['LISTS']['TITLES'][0];

			if (is_array($lists['LISTS']['ADMINS'][0])) {
				$this->admin_list = $lists['LISTS']['ADMINS'][0];
				// check admin list consistency
				if (empty($this->admin_list['IPADDRESS'])) {
					// fill <ipaddress> list to same length as <tmlogin> list
					if (($cnt = count($this->admin_list['TMLOGIN'])) > 0)
						$this->admin_list['IPADDRESS'] = array_fill(0, $cnt, '');
				} else {
					if (count($this->admin_list['TMLOGIN']) != count($this->admin_list['IPADDRESS']))
						trigger_error("Admin mismatch between <tmlogin>'s and <ipaddress>'s!", E_USER_WARNING);
				}
			}

			if (is_array($lists['LISTS']['OPERATORS'][0])) {
				$this->operator_list = $lists['LISTS']['OPERATORS'][0];
				// check operator list consistency
				if (empty($this->operator_list['IPADDRESS'])) {
					// fill <ipaddress> list to same length as <tmlogin> list
					if (($cnt = count($this->operator_list['TMLOGIN'])) > 0)
						$this->operator_list['IPADDRESS'] = array_fill(0, $cnt, '');
				} else {
					if (count($this->operator_list['TMLOGIN']) != count($this->operator_list['IPADDRESS']))
						trigger_error("Operators mismatch between <tmlogin>'s and <ipaddress>'s!", E_USER_WARNING);
				}
			}

			$this->adm_abilities = $lists['LISTS']['ADMIN_ABILITIES'][0];
			$this->op_abilities = $lists['LISTS']['OPERATOR_ABILITIES'][0];

			// convert strings to booleans
			foreach ($this->adm_abilities as $ability => $value) {
				if (strtoupper($value[0]) == 'TRUE') {
					$this->adm_abilities[$ability][0] = true;
				} else {
					$this->adm_abilities[$ability][0] = false;
				}
			}
			foreach ($this->op_abilities as $ability => $value) {
				if (strtoupper($value[0]) == 'TRUE') {
					$this->op_abilities[$ability][0] = true;
				} else {
					$this->op_abilities[$ability][0] = false;
				}
			}
			return true;
		} else {
			// could not parse XML file
			trigger_error('Could not read/parse adminops file ' . $adminops_file . ' !', E_USER_WARNING);
			return false;
		}
	}  // readLists

	/**
	 * Write Admin/Operator/Ability lists to save them for future runs.
	 */
	function writeLists() {

		// get lists file name
		$adminops_file = $this->settings['adminops_file'];

		// compile lists file contents
		$lists = "<?xml version=\"1.0\" encoding=\"utf-8\" ?>" . CRLF
		       . "<lists>" . CRLF
		       . "\t<titles>" . CRLF;
		foreach ($this->titles as $title => $value) {
			$lists .= "\t\t<" . strtolower($title) . ">" .
			          $value[0]
			           . "</" . strtolower($title) . ">" . CRLF;
		}
		$lists .= "\t</titles>" . CRLF
		        . CRLF
		        . "\t<admins>" . CRLF;
		$empty = true;
		if (isset($this->admin_list['TMLOGIN'])) {
			for ($i = 0; $i < count($this->admin_list['TMLOGIN']); $i++) {
				if ($this->admin_list['TMLOGIN'][$i] != '') {
					$lists .= "\t\t<tmlogin>" . $this->admin_list['TMLOGIN'][$i] . "</tmlogin>"
					         . " <ipaddress>" . $this->admin_list['IPADDRESS'][$i] . "</ipaddress>" . CRLF;
					$empty = false;
				}
			}
		}
		if ($empty) {
			$lists .= "<!-- format:" . CRLF
			        . "\t\t<tmlogin>YOUR_ADMIN_LOGIN</tmlogin> <ipaddress></ipaddress>" . CRLF
			        . "-->" . CRLF;
		}
		$lists .= "\t</admins>" . CRLF
		        . CRLF
		        . "\t<operators>" . CRLF;
		$empty = true;
		if (isset($this->operator_list['TMLOGIN'])) {
			for ($i = 0; $i < count($this->operator_list['TMLOGIN']); $i++) {
				if ($this->operator_list['TMLOGIN'][$i] != '') {
					$lists .= "\t\t<tmlogin>" . $this->operator_list['TMLOGIN'][$i] . "</tmlogin>"
					         . " <ipaddress>" . $this->operator_list['IPADDRESS'][$i] . "</ipaddress>" . CRLF;
					$empty = false;
				}
			}
		}
		if ($empty) {
			$lists .= "<!-- format:" . CRLF
			        . "\t\t<tmlogin>YOUR_OPERATOR_LOGIN</tmlogin> <ipaddress></ipaddress>" . CRLF
			        . "-->" . CRLF;
		}
		$lists .= "\t</operators>" . CRLF
		        . CRLF
		        . "\t<admin_abilities>" . CRLF;
		foreach ($this->adm_abilities as $ability => $value) {
			$lists .= "\t\t<" . strtolower($ability) . ">" .
			          ($value[0] ? "true" : "false")
			           . "</" . strtolower($ability) . ">" . CRLF;
		}
		$lists .= "\t</admin_abilities>" . CRLF
		        . CRLF
		        . "\t<operator_abilities>" . CRLF;
		foreach ($this->op_abilities as $ability => $value) {
			$lists .= "\t\t<" . strtolower($ability) . ">" .
			          ($value[0] ? "true" : "false")
			           . "</" . strtolower($ability) . ">" . CRLF;
		}
		$lists .= "\t</operator_abilities>" . CRLF
		        . "</lists>" . CRLF;

		// write out the lists file
		if (!@file_put_contents($adminops_file, $lists)) {
			trigger_error('Could not write adminops file ' . $adminops_file . ' !', E_USER_WARNING);
			return false;
		} else {
			return true;
		}
	}  // writeLists


	/**
	 * Read Banned IPs list and apply it on the current instance.
	 */
	function readIPs() {

		// get banned IPs file name
		$bannedips_file = $this->settings['bannedips_file'];

		if ($list = $this->xml_parser->parseXml($bannedips_file)) {
			// read the XML structure into variable
			if (isset($list['BAN_LIST']['IPADDRESS']))
				$this->bannedips = $list['BAN_LIST']['IPADDRESS'];
			else
				$this->bannedips = array();
			return true;
		} else {
			// could not parse XML file
			trigger_error('Could not read/parse banned IPs file ' . $bannedips_file . ' !', E_USER_WARNING);
			return false;
		}
	}  // readIPs

	/**
	 * Write Banned IPs list to save it for future runs.
	 */
	function writeIPs() {

		// get banned IPs file name
		$bannedips_file = $this->settings['bannedips_file'];
		$empty = true;

		// compile banned IPs file contents
		$list = "<?xml version=\"1.0\" encoding=\"utf-8\" ?>" . CRLF
		      . "<ban_list>" . CRLF;
		for ($i = 0; $i < count($this->bannedips); $i++) {
			if ($this->bannedips[$i] != '') {
				$list .= "\t\t<ipaddress>" . $this->bannedips[$i] . "</ipaddress>" . CRLF;
				$empty = false;
			}
		}
		if ($empty) {
			$list .= "<!-- format:" . CRLF
			       . "\t\t<ipaddress>xx.xx.xx.xx</ipaddress>" . CRLF
			       . "-->" . CRLF;
		}
		$list .= "</ban_list>" . CRLF;

		// write out the list file
		if (!@file_put_contents($bannedips_file, $list)) {
			trigger_error('Could not write banned IPs file ' . $bannedips_file . ' !', E_USER_WARNING);
			return false;
		} else {
			return true;
		}
	}  // writeIPs


	/**
	 * Loads files in the plugins directory.
	 */
	function loadPlugins() {

		// load and parse the plugins file
		if ($plugins = $this->xml_parser->parseXml('plugins.xml')) {
			if (!empty($plugins['ASECO_PLUGINS']['PLUGIN'])) {
				// take each plugin tag
				foreach ($plugins['ASECO_PLUGINS']['PLUGIN'] as $plugin) {
					// log plugin message
					$this->console_text('[XAseco] Load plugin [' . $plugin . ']');
					// include the plugin
					require_once('plugins/' . $plugin);
					$this->plugins[] = $plugin;
				}
			}
		} else {
			trigger_error('Could not read/parse plugins list plugins.xml !', E_USER_ERROR);
		}
	}  // loadPlugins


	/**
	 * Runs the server.
	 */
	function run($config_file) {

		// load new settings, if available
		$this->console_text('[XAseco] Load settings [{1}]', $config_file);
		$this->loadSettings($config_file);

		// load admin/operator/ability lists, if available
		$this->console_text('[XAseco] Load admin/ops lists [{1}]', $this->settings['adminops_file']);
		$this->readLists();

		// load banned IPs list, if available
		$this->console_text('[XAseco] Load banned IPs list [{1}]', $this->settings['bannedips_file']);
		$this->readIPs();

		// load plugins and register chat commands
		$this->console_text('[XAseco] Load plugins list [plugins.xml]');
		$this->loadPlugins();

		// connect to Trackmania Dedicated Server
		if (!$this->connect()) {
			// kill program with an error
			trigger_error('Connection could not be established !', E_USER_ERROR);
		}

		// log status message
		$this->console('Connection established successfully !');
		// log admin lock message
		if ($this->settings['lock_password'] != '')
			$this->console_text("[XAseco] Locked admin commands & features with password '{1}'", $this->settings['lock_password']);

		// get basic server info
		$this->client->query('GetVersion');
		$response['version'] = $this->client->getResponse();
		$this->server->game = $response['version']['Name'];
		$this->server->version = $response['version']['Version'];
		$this->server->build = $response['version']['Build'];

		// throw 'starting up' event
		$this->releaseEvent('onStartup', null);

		// synchronize information with server
		$this->serverSync();

		// register all chat commands
		if ($this->server->getGame() != 'TMF') {
			$this->registerChatCommands();
			// set spectator not available outside TMF
			if ($this->settings['cheater_action'] == 1)
				$this->settings['cheater_action'] = 0;
		}

		// make a visual header
		$this->sendHeader();

		// get current game infos if server loaded a track yet
		if ($this->currstatus == 100) {
			$this->console_text('[XAseco] Waiting for the server to start a challenge');
		} else {
			$this->beginRace(false);
		}

		// main loop
		$this->startup_phase = false;
		while (true) {
			$starttime = microtime(true);
			// get callbacks from the server
			$this->executeCallbacks();

			// sends calls to the server
			$this->executeCalls();

			// throw timing events
			$this->releaseEvent('onMainLoop', null);

			$this->currsecond = time();
			if ($this->prevsecond != $this->currsecond) {
				$this->prevsecond = $this->currsecond;
				$this->releaseEvent('onEverySecond', null);
			}

			// reduce CPU usage if main loop has time left
			$endtime = microtime(true);
			$delay = 200000 - ($endtime - $starttime) * 1000000;
			if ($delay > 0)
				usleep($delay);
			// make sure the script does not timeout
			@set_time_limit($this->settings['script_timeout']);
		}

		// close the client connection
		$this->client->Terminate();
	}  // run


	/**
	 * Authenticates XASECO at the server.
	 */
	function connect() {

		// only if logins are set
		if ($this->server->ip && $this->server->port && $this->server->login && $this->server->pass) {
			// log console message
			$this->console('Try to connect to TM dedicated server on {1}:{2} timeout {3}s',
			               $this->server->ip, $this->server->port,
			               ($this->server->timeout !== null ? $this->server->timeout : 0));

			// connect to the server
			if (!$this->client->InitWithIp($this->server->ip, $this->server->port, $this->server->timeout)) {
				trigger_error('[' . $this->client->getErrorCode() . '] InitWithIp - ' . $this->client->getErrorMessage(), E_USER_WARNING);
				return false;
			}

			// log console message
			$this->console("Try to authenticate with login '{1}' and password '{2}'",
			               $this->server->login, $this->server->pass);

			// check login
			if ($this->server->login != 'SuperAdmin') {
				trigger_error("Invalid login '" . $this->server->login . "' - must be 'SuperAdmin' in config.xml !", E_USER_WARNING);
				return false;
			}
			// check password
			if ($this->server->pass == 'SuperAdmin') {
				trigger_error("Insecure password '" . $this->server->pass . "' - should be changed in dedicated config and config.xml !", E_USER_WARNING);
			}

			// log into the server
			if (!$this->client->query('Authenticate', $this->server->login, $this->server->pass)) {
				trigger_error('[' . $this->client->getErrorCode() . '] Authenticate - ' . $this->client->getErrorMessage(), E_USER_WARNING);
				return false;
			}

			// enable callback system
			$this->client->query('EnableCallbacks', true);

			// wait for server to be ready
			$this->waitServerReady();

			// connection established
			return true;
		} else {
			// connection failed
			return false;
		}
	}  // connect


	/**
	 * Waits for the server to be ready (status 4, 'Running - Play')
	 */
	function waitServerReady() {

		$this->client->query('GetStatus');
		$status = $this->client->getResponse();
		if ($status['Code'] != 4) {
			$this->console("Waiting for dedicated server to reach status 'Running - Play'...");
			$this->console('Status: ' . $status['Name']);
			$timeout = 0;
			$laststatus = $status['Name'];
			while ($status['Code'] != 4) {
				sleep(1);
				$this->client->query('GetStatus');
				$status = $this->client->getResponse();
				if ($laststatus != $status['Name']) {
					$this->console('Status: ' . $status['Name']);
					$laststatus = $status['Name'];
				}
				if (isset($this->server->timeout) && $timeout++ > $this->server->timeout)
					trigger_error('Timed out while waiting for dedicated server!', E_USER_ERROR);
			}
		}
	}  // waitServerReady

	/**
	 * Initializes the server and the player list.
	 * Reads a list of the players who are on the server already,
	 * and loads all server variables.
	 */
	function serverSync() {

		// check server build
		if (strlen($this->server->build) == 0 ||
		    ($this->server->getGame() != 'TMF' && strcmp($this->server->build, TMN_BUILD) < 0) ||
		    ($this->server->getGame() == 'TMF' && strcmp($this->server->build, TMF_BUILD) < 0)) {
			trigger_error("Obsolete server build '" . $this->server->build . "' - must be " .
			              ($this->server->getGame() == 'TMF' ? "at least '" . TMF_BUILD . "' !" : "'" . TMN_BUILD . "' !"), E_USER_ERROR);
		}

		// get server id, login, nickname, zone & packmask
		$this->server->id = 0;  // on TMN/TMO/TMS
		$this->server->rights = false;
		$this->server->isrelay = false;
		$this->server->relaymaster = null;
		$this->server->relayslist = array();
		$this->server->gamestate = Server::RACE;
		$this->server->packmask = '';
		if ($this->server->getGame() == 'TMF') {
			$this->client->query('GetSystemInfo');
			$response['system'] = $this->client->getResponse();
			$this->server->serverlogin = $response['system']['ServerLogin'];

			$this->client->query('GetDetailedPlayerInfo', $this->server->serverlogin);
			$response['info'] = $this->client->getResponse();
			$this->server->id = $response['info']['PlayerId'];
			$this->server->nickname = $response['info']['NickName'];
			$this->server->zone = substr($response['info']['Path'], 6);  // strip 'World|'
			$this->server->rights = ($response['info']['OnlineRights'] == 3);  // United = true

			$this->client->query('GetLadderServerLimits');
			$response['ladder'] = $this->client->getResponse();
			$this->server->laddermin = $response['ladder']['LadderServerLimitMin'];
			$this->server->laddermax = $response['ladder']['LadderServerLimitMax'];

			$this->client->query('IsRelayServer');
			$this->server->isrelay = ($this->client->getResponse() > 0);
			if ($this->server->isrelay) {
				$this->client->query('GetMainServerPlayerInfo', 1);
				$this->server->relaymaster = $this->client->getResponse();
			}

			// TMNF packmask = 'Stadium' for 'nations' or 'stadium'
			$this->client->query('GetServerPackMask');
			$this->server->packmask = $this->client->getResponse();

			// clear possible leftover ManiaLinks
			$this->client->query('SendHideManialinkPage');
		}

		// get mode & limits
		$this->client->query('GetCurrentGameInfo', ($this->server->getGame() == 'TMF' ? 1 : 0));
		$response['gameinfo'] = $this->client->getResponse();
		$this->server->gameinfo = new Gameinfo($response['gameinfo']);

		// get status
		$this->client->query('GetStatus');
		$response['status'] = $this->client->getResponse();
		$this->currstatus = $response['status']['Code'];

		// get game & trackdir
		$this->client->query('GameDataDirectory');
		$this->server->gamedir = $this->client->getResponse();
		$this->client->query('GetTracksDirectory');
		$this->server->trackdir = $this->client->getResponse();

		// get server name & options
		$this->getServerOptions();

		// throw 'synchronisation' event
		$this->releaseEvent('onSync', null);

		// get current players/servers on the server (hardlimited to 300)
		if ($this->server->getGame() == 'TMF')
			$this->client->query('GetPlayerList', 300, 0, 2);
		else
			$this->client->query('GetPlayerList', 300, 0);
		$response['playerlist'] = $this->client->getResponse();

		// update players/relays lists
		if (!empty($response['playerlist'])) {
			foreach ($response['playerlist'] as $player) {
				// fake it into thinking it's a connecting player:
				// it gets team & ladder info this way & will also throw an
				// onPlayerConnect event for players (not relays) to all plugins
				$this->playerConnect(array($player['Login'], ''));
			}
		}
	}  // serverSync


	/**
	 * Sends program header to console and ingame chat.
	 */
	function sendHeader() {

		$this->console_text('###############################################################################');
		$this->console_text('  XASECO v' . XASECO_VERSION . ' running on {1}:{2}', $this->server->ip, $this->server->port);
		if ($this->server->getGame() == 'TMF') {
			$this->console_text('  Name   : {1} - {2}', stripColors($this->server->name, false), $this->server->serverlogin);
			if ($this->server->isrelay)
				$this->console_text('  Relays : {1} - {2}', stripColors($this->server->relaymaster['NickName'], false), $this->server->relaymaster['Login']);
			$this->console_text('  Game   : {1} {2} - {3} - {4}', $this->server->game,
			                    ($this->server->rights ? 'United' : 'Nations'),
			                    $this->server->packmask, $this->server->gameinfo->getMode());
		} else {
			$this->console_text('  Name   : {1}', stripColors($this->server->name, false));
			$this->console_text('  Game   : {1} - {2}', $this->server->game, $this->server->gameinfo->getMode());
		}
		$this->console_text('  Version: {1} / {2}', $this->server->version, $this->server->build);
		$this->console_text('  Authors: Florian Schnell & Assembler Maniac');
		$this->console_text('  Re-Authored: Xymph');
		$this->console_text('###############################################################################');

		// format the text of the message
		$startup_msg = formatText($this->getChatMessage('STARTUP'),
		                          XASECO_VERSION,
		                          $this->server->ip, $this->server->port);
		// show startup message
		$this->client->query('ChatSendServerMessage', $this->formatColors($startup_msg));
	}  // sendHeader


	/**
	 * Gets callbacks from the TM Dedicated Server and reacts on them.
	 */
	function executeCallbacks() {

		// receive callbacks with a timeout (default: 2 ms)
		$this->client->resetError();
		$this->client->readCB();

		// now get the responses out of the 'buffer'
		$calls = $this->client->getCBResponses();
		if ($this->client->isError()) {
			trigger_error('ExecCallbacks XMLRPC Error [' . $this->client->getErrorCode() . '] - ' . $this->client->getErrorMessage(), E_USER_ERROR);
		}

		if (!empty($calls)) {
			while ($call = array_shift($calls)) {
				switch ($call[0]) {
					case 'TrackMania.PlayerConnect':  // [0]=Login, [1]=IsSpectator
						$this->playerConnect($call[1]);
						break;

					case 'TrackMania.PlayerDisconnect':  // [0]=Login
						$this->playerDisconnect($call[1]);
						break;

					case 'TrackMania.PlayerChat':  // [0]=PlayerUid, [1]=Login, [2]=Text, [3]=IsRegistredCmd
						$this->playerChat($call[1]);
						$this->releaseEvent('onChat', $call[1]);
						break;

					case 'TrackMania.PlayerServerMessageAnswer':  // [0]=PlayerUid, [1]=Login, [2]=Answer
						$this->playerServerMessageAnswer($call[1]);
						break;

					case 'TrackMania.PlayerCheckpoint':  // TMN: [0]=PlayerUid, [1]=Login, [2]=Time, [3]=Score, [4]=CheckpointIndex; TMF: [0]=PlayerUid, [1]=Login, [2]=TimeOrScore, [3]=CurLap, [4]=CheckpointIndex
						if (!$this->server->isrelay)
							$this->releaseEvent('onCheckpoint', $call[1]);
						break;

					case 'TrackMania.PlayerFinish':  // [0]=PlayerUid, [1]=Login, [2]=TimeOrScore
						$this->playerFinish($call[1]);
						break;

					case 'TrackMania.BeginRace':  // [0]=Challenge
						if ($this->server->getGame() != 'TMF')
							$this->beginRace($call[1]);
						break;

					case 'TrackMania.EndRace':  // [0]=Rankings[], [1]=Challenge
						if ($this->server->getGame() != 'TMF')
							$this->endRace($call[1]);
						break;

					case 'TrackMania.BeginRound':  // none
						$this->beginRound();
						break;

					case 'TrackMania.StatusChanged':  // [0]=StatusCode, [1]=StatusName
						// update status changes
						$this->prevstatus = $this->currstatus;
						$this->currstatus = $call[1][0];
						// if TMF mode Sync, check WarmUp state
						if ($this->server->getGame() == 'TMF') {
							if ($this->currstatus == 3 || $this->currstatus == 5) {
								$this->client->query('GetWarmUp');
								$this->warmup_phase = $this->client->getResponse();
							}
						} else {
							$this->warmup_phase = false;
						}
						// on TMF, use real EndRound callback
						if ($this->server->getGame() != 'TMF') {
							// if change from Play (4) to Sync (3) or Finish (5),
							// it's the end of a round
							if ($this->prevstatus == 4 && ($this->currstatus == 3 || $this->currstatus == 5))
								$this->endRound();
						}
						if ($this->currstatus == 4) {  // Running - Play
							$this->runningPlay();
						}
						$this->releaseEvent('onStatusChangeTo' . $this->currstatus, $call[1]);
						break;

					// new TMF callbacks:

					case 'TrackMania.EndRound':  // none
						$this->endRound();
						break;

					case 'TrackMania.BeginChallenge':  // [0]=Challenge, [1]=WarmUp, [2]=MatchContinuation
						$this->beginRace($call[1]);
						break;

					case 'TrackMania.EndChallenge':  // [0]=Rankings[], [1]=Challenge, [2]=WasWarmUp, [3]=MatchContinuesOnNextChallenge, [4]=RestartChallenge
						$this->endRace($call[1]);
						break;

					case 'TrackMania.PlayerManialinkPageAnswer':  // [0]=PlayerUid, [1]=Login, [2]=Answer
						$this->releaseEvent('onPlayerManialinkPageAnswer', $call[1]);
						break;

					case 'TrackMania.BillUpdated':  // [0]=BillId, [1]=State, [2]=StateName, [3]=TransactionId
						$this->releaseEvent('onBillUpdated', $call[1]);
						break;

					case 'TrackMania.ChallengeListModified':  // [0]=CurChallengeIndex, [1]=NextChallengeIndex, [2]=IsListModified
						$this->releaseEvent('onChallengeListModified', $call[1]);
						break;

					case 'TrackMania.PlayerInfoChanged':  // [0]=PlayerInfo
						$this->playerInfoChanged($call[1][0]);
						break;

					case 'TrackMania.PlayerIncoherence':  // [0]=PlayerUid, [1]=Login
						$this->releaseEvent('onPlayerIncoherence', $call[1]);
						break;

					case 'TrackMania.TunnelDataReceived':  // [0]=PlayerUid, [1]=Login, [2]=Data
						$this->releaseEvent('onTunnelDataReceived', $call[1]);
						break;

					case 'TrackMania.Echo':  // [0]=Internal, [1]=Public
						$this->releaseEvent('onEcho', $call[1]);
						break;

					case 'TrackMania.ManualFlowControlTransition':  // [0]=Transition
						$this->releaseEvent('onManualFlowControlTransition', $call[1]);
						break;

					case 'TrackMania.VoteUpdated':  // [0]=StateName, [1]=Login, [2]=CmdName, [3]=CmdParam
						$this->releaseEvent('onVoteUpdated', $call[1]);
						break;

					default:
						// do nothing
				}
			}
			return $calls;
		} else {
			return false;
		}
	}  // executeCallbacks


	/**
	 * Adds calls to a multiquery.
	 * It's possible to set a callback function which
	 * will be executed on incoming response.
	 * You can also set an ID to read response later on.
	 */
	function addCall($call, $params = array(), $id = 0, $callback_func = false) {

		// adds call and registers a callback if needed
		$index = $this->client->addCall($call, $params);
		$rpc_call = new RPCCall($id, $index, $callback_func, array($call, $params));
		$this->rpc_calls[] = $rpc_call;
	}  // addCall


	/**
	 * Executes a multicall and gets responses.
	 * Saves responses in array with IDs as keys.
	 */
	function executeCalls() {

		// clear responses
		$this->rpc_responses = array();

		// stop if there are no rpc calls in query
		if (empty($this->client->calls)) {
			return true;
		}

		$this->client->resetError();
		$tmpcalls = $this->client->calls;  // debugging code to find UTF-8 errors
		// sends multiquery to the server and gets the response
		if ($this->client->multiquery()) {
			if ($this->client->isError()) {
				$this->console_text(print_r($tmpcalls, true));
				trigger_error('ExecCalls XMLRPC Error [' . $this->client->getErrorCode() . '] - ' . $this->client->getErrorMessage(), E_USER_ERROR);
			}

			// get new response from server
			$responses = $this->client->getResponse();

			// handle server responses
			foreach ($this->rpc_calls as $call) {
				// display error message if needed
				$err = false;
				if (isset($responses[$call->index]['faultString'])) {
					$this->rpcErrorResponse($responses[$call->index]);
					print_r($call->call);
					$err = true;
				}

				// if an id was set, then save the response under the specified id
				if ($call->id) {
					$this->rpc_responses[$call->id] = $responses[$call->index][0];
				}

				// if a callback function has been set, then execute it
				if ($call->callback && !$err) {
					if (function_exists($call->callback)) {
						// callback the function with the response as parameter
						call_user_func($call->callback, $responses[$call->index][0]);
					}

					// if a function with the name of the callback wasn't found, then
					// try to execute a method with its name
					elseif (method_exists($this, $call->callback)) {
						// callback the method with the response as parameter
						call_user_func(array($this, $call->callback), $responses[$call->index][0]);
					}
				}
			}
		}

		// clear calls
		$this->rpc_calls = array();
	}  // executeCalls


	/**
	 * Documents RPC Errors.
	 */
	function rpcErrorResponse($response) {

		$this->console_text('[RPC Error ' . $response['faultCode'] . '] ' . $response['faultString']);
	}  // rpcErrorResponse


	/**
	 * Registers functions which are called on specific events.
	 */
	function registerEvent($event_type, $callback_func) {

		// registers a new event
		$this->events[$event_type][] = $callback_func;
	}  // registerEvent

	/**
	 * Executes the functions which were registered for specified events.
	 */
	function releaseEvent($event_type, $func_param) {

		// executes registered event functions
		// if there are any events for that type
		if (!empty($this->events[$event_type])) {
			// for each registered function of this type
			foreach ($this->events[$event_type] as $func_name) {
				// if function for the specified player connect event can be found
				if (is_callable($func_name)) {
					// ... execute it!
					call_user_func($func_name, $this, $func_param);
				}
			}
		}
	}  // releaseEvent


	/**
	 * Stores a new user command.
	 */
	function addChatCommand($command_name, $command_help, $command_is_admin = false) {

		$chat_command = new ChatCommand($command_name, $command_help, $command_is_admin);
		$this->chat_commands[] = $chat_command;
	}  // addChatCommand

	/**
	 * Registers all chat commands with the server.
	 */
	function registerChatCommands() {

		// clear the current list of chat commands
		$this->client->query('CleanChatCommand');

		if (isset($this->chat_commands)) {
			foreach ($this->chat_commands as $command) {
				// only if it's no admin command
				if (!$command->isadmin) {
					// log message if debug mode is set to true
					if ($this->debug) {
						$this->console_text('register chat command: ' . $command->name);
					}

					// register chat command at server
					$this->client->query('AddChatCommand', $command->name);
				}
			}
		}
	}  // registerChatCommands


	/**
	 * When a round is started, signal the event.
	 */
	function beginRound() {

		$this->console_text('Begin Round');
		$this->releaseEvent('onBeginRound', null);
	}  // beginRound

	/**
	 * When a round is ended, signal the event.
	 */
	function endRound() {

		$this->console_text('End Round');
		$this->releaseEvent('onEndRound', null);
	}  // endRound


	/**
	 * When a TMF player's info changed, signal the event.  Fields:
	 * Login, NickName, PlayerId, TeamId, SpectatorStatus, LadderRanking, Flags
	 */
	function playerInfoChanged($playerinfo) {

		// on relay, check for player from master server
		if ($this->server->isrelay && floor($playerinfo['Flags'] / 10000) % 10 != 0)
			return;

		// check for valid player
		if (!$player = $this->server->players->getPlayer($playerinfo['Login']))
			return;

		// check ladder ranking
		if ($playerinfo['LadderRanking'] > 0) {
			$player->ladderrank = $playerinfo['LadderRanking'];
			$player->isofficial = true;
		} else {
			$player->isofficial = false;
		}

		// check spectator status (ignoring temporary changes)
		$player->prevstatus = $player->isspectator;
		if (($playerinfo['SpectatorStatus'] % 10) != 0)
			$player->isspectator = true;
		else
			$player->isspectator = false;

		$this->releaseEvent('onPlayerInfoChanged', $playerinfo);
	}  // playerInfoChanged


	/**
	 * When a new track is started we have to get information
	 * about the new track and so on.
	 */
	function runningPlay() {
		// request information about the new challenge
		// ... and callback to function newChallenge()
	}  // runningPlay


	/**
	 * When a new race is started we have to get information
	 * about the new track and so on.
	 */
	function beginRace($race) {
		// request information about the new challenge
		// ... and callback to function newChallenge()

		// if TMF new challenge, check WarmUp state
		if ($this->server->getGame() == 'TMF' && $race)
			$this->warmup_phase = $race[1];

		if (!$race) {
			$this->addCall('GetCurrentChallengeInfo', array(), '', 'newChallenge');
		} else {
			$this->newChallenge($race[0]);
		}
	}  // beginRace


	/**
	 * Reacts on new challenges.
	 * Gets record to current challenge etc.
	 */
	function newChallenge($challenge) {

		// log if not a restart
		$this->server->gamestate = Server::RACE;
		if ($this->restarting == 0)
			$this->console_text('Begin Challenge');

		// refresh game info
		$this->client->query('GetCurrentGameInfo', ($this->server->getGame() == 'TMF' ? 1 : 0));
		$gameinfo = $this->client->getResponse();
		$this->server->gameinfo = new Gameinfo($gameinfo);

		// check for TMF restarting challenge
		$this->changingmode = false;
		if ($this->server->getGame() == 'TMF' && $this->restarting > 0) {
			// check type of restart and signal an instant one
			if ($this->restarting == 2) {
				$this->restarting = 0;
			} else {  // == 1
				$this->restarting = 0;
				// throw postfix 'restart challenge' event
				$this->releaseEvent('onRestartChallenge2', $challenge);
				return;
			}
		}
		// refresh server name & options
		$this->getServerOptions();

		// reset record list
		$this->server->records->clear();
		// reset player votings
		//$this->server->players->resetVotings();

		// create new challenge object
		$challenge_item = new Challenge($challenge);

		// in TMF Rounds/Team/Cup mode if multilap track, get forced laps
		if ($this->server->getGame() == 'TMF' && $challenge_item->laprace &&
		    ($this->server->gameinfo->mode == Gameinfo::RNDS ||
		     $this->server->gameinfo->mode == Gameinfo::TEAM ||
		     $this->server->gameinfo->mode == Gameinfo::CUP)) {
			$challenge_item->forcedlaps = $this->server->gameinfo->forcedlaps;
		}

		// obtain challenge's GBX data, TMX info & records
		$challenge_item->gbx = new GBXChallMapFetcher(true);
		try
		{
			$challenge_item->gbx->processFile($this->server->trackdir . $challenge_item->filename);
		}
		catch (Exception $e)
		{
			trigger_error($e->getMessage(), E_USER_WARNING);
		}
		$challenge_item->tmx = findTMXdata($challenge_item->uid, $challenge_item->environment, $challenge_item->gbx->exeVer, true);

		// throw main 'begin challenge' event
		$this->releaseEvent('onNewChallenge', $challenge_item);

		// log console message
		$this->console('track changed [{1}] >> [{2}]',
		               stripColors($this->server->challenge->name, false),
		               stripColors($challenge_item->name, false));

/* disabled in favor of RASP's karma system - Xymph
		// log track's score
		if ($challenge_item->score && $challenge_item->votes) {
			// calculate avarage score and display
			$this->console('average score of this track is {1}',
			               $challenge_item->score/$challenge_item->votes);
		} else {
			// no votings
			$this->console('no votings available for this track');
		}
disabled */

		// check for relay server
		if (!$this->server->isrelay) {
			// check if record exists on new track
			$cur_record = $this->server->records->getRecord(0);
			if ($cur_record !== false && $cur_record->score > 0) {
				$score = ($this->server->gameinfo->mode == Gameinfo::STNT ?
				          str_pad($cur_record->score, 5, ' ', STR_PAD_LEFT) :
				          formatTime($cur_record->score));

				// log console message of current record
				$this->console('current record on {1} is {2} and held by {3}',
				               stripColors($challenge_item->name, false),
				               trim($score),
				               stripColors($cur_record->player->nickname, false));

				// replace parameters
				$message = formatText($this->getChatMessage('RECORD_CURRENT'),
				                      stripColors($challenge_item->name),
				                      trim($score),
				                      stripColors($cur_record->player->nickname));
			} else {
				if ($this->server->gameinfo->mode == Gameinfo::STNT)
					$score = '  ---';
				else
					$score = '   --.--';

				// log console message of no record
				$this->console('currently no record on {1}',
				               stripColors($challenge_item->name, false));

				// replace parameters
				$message = formatText($this->getChatMessage('RECORD_NONE'),
				                      stripColors($challenge_item->name));
			}
			if (function_exists('setRecordsPanel'))
				setRecordsPanel('local', $score);

			// if no trackrecs, show the original record message to all players
			if (($this->settings['show_recs_before'] & 1) == 1) {
				if (($this->settings['show_recs_before'] & 4) == 4 && function_exists('send_window_message'))
					send_window_message($this, $message, false);
				else
					$this->client->query('ChatSendServerMessage', $this->formatColors($message));
			}
		}

		// update the field which contains current challenge
		$this->server->challenge = $challenge_item;

		// throw postfix 'begin challenge' event (various)
		$this->releaseEvent('onNewChallenge2', $challenge_item);

		// show top-8 & records of all online players before track
		if (($this->settings['show_recs_before'] & 2) == 2 && function_exists('show_trackrecs')) {
			show_trackrecs($this, false, 1, $this->settings['show_recs_before']);  // from chat.records2.php
		}
	}  // newChallenge


	/**
	 * End of current race.
	 * Write records to database and/or display final statistics.
	 */
	function endRace($race) {

		// check for TMF RestartChallenge flag
		if ($this->server->getGame() == 'TMF' && $race[4]) {
			$this->restarting = 1;
			// check whether changing game mode or any player has a time/score,
			// then there will be ChatTime, otherwise it's an instant restart
			if ($this->changingmode)
				$this->restarting = 2;
			else
				foreach ($race[0] as $pl) {
					if ($pl['BestTime'] > 0 || $pl['Score'] > 0) {
						$this->restarting = 2;
						break;
					}
				}
			// log type of restart and signal an instant one
			if ($this->restarting == 2) {
				$this->console_text('Restart Challenge (with ChatTime)');
			} else {  // == 1
				$this->console_text('Restart Challenge (instant)');
				// throw main 'restart challenge' event
				$this->releaseEvent('onRestartChallenge', $race);
				return;
			}
		}
		// log if not a restart
		$this->server->gamestate = Server::SCORE;
		if ($this->restarting == 0)
			$this->console_text('End Challenge');

		// show top-8 & all new records after track
		if (($this->settings['show_recs_after'] & 2) == 2 && function_exists('show_trackrecs')) {
			show_trackrecs($this, false, 3, $this->settings['show_recs_after']);  // from chat.records2.php
		} elseif (($this->settings['show_recs_after'] & 1) == 1) {
			// fall back on old top-5
			$records = '';

			if ($this->server->records->count() == 0) {
				// display a no-new-record message
				$message = formatText($this->getChatMessage('RANKING_NONE'),
				                      stripColors($this->server->challenge->name),
				                      'after');
			} else {
				// display new records set up this round
				$message = formatText($this->getChatMessage('RANKING'),
				                      stripColors($this->server->challenge->name),
				                      'after');

				// go through each record
				for ($i = 0; $i < 5; $i++) {
					$cur_record = $this->server->records->getRecord($i);

					// if the record is set then display it
					if ($cur_record !== false && $cur_record->score > 0) {
						// replace parameters
						$record_msg = formatText($this->getChatMessage('RANKING_RECORD_NEW'),
						                         $i+1,
						                         stripColors($cur_record->player->nickname),
						                         ($this->server->gameinfo->mode == Gameinfo::STNT ?
						                          $cur_record->score : formatTime($cur_record->score)));
						$records .= $record_msg;
					}
				}
			}

			// append the records if any
			if ($records != '') {
				$records = substr($records, 0, strlen($records)-2);  // strip trailing ", "
				$message .= LF . $records;
			}

			// show ranking message to all players
			if (($this->settings['show_recs_after'] & 4) == 4 && function_exists('send_window_message'))
				send_window_message($this, $message, true);
			else
				$this->client->query('ChatSendServerMessage', $this->formatColors($message));
		}

		// get rankings and call endRaceRanking as soon as we have them
		// $this->addCall('GetCurrentRanking', array(2, 0), false, 'endRaceRanking');
		if (!$this->server->isrelay)
			$this->endRaceRanking($race[0]);

		// throw prefix 'end challenge' event (chat-based votes)
		$this->releaseEvent('onEndRace1', $race);
		// throw main 'end challenge' event
		$this->releaseEvent('onEndRace', $race);
	}  // endRace


	/**
	 * Check out who won the current track and increment his/her wins by one.
	 */
	function endRaceRanking($ranking) {

		// check for online login
		if (isset($ranking[0]['Login']) &&
		    ($player = $this->server->players->getPlayer($ranking[0]['Login'])) !== false) {
			// check for winner if there's more than one player
			if ($ranking[0]['Rank'] == 1 && count($ranking) > 1 &&
			    ($this->server->gameinfo->mode == Gameinfo::STNT ?
			     ($ranking[0]['Score'] > 0) : ($ranking[0]['BestTime'] > 0))) {
				// increase the player's wins
				$player->newwins++;

				// log console message
				$this->console('{1} won for the {2}. time!',
				               $player->login, $player->getWins());

				if ($player->getWins() % $this->settings['global_win_multiple'] == 0) {
					// replace parameters
					$message = formatText($this->getChatMessage('WIN_MULTI'),
					                      stripColors($player->nickname), $player->getWins());

					// show chat message
					$this->client->query('ChatSendServerMessage', $this->formatColors($message));
				} else {
					// replace parameters
					$message = formatText($this->getChatMessage('WIN_NEW'),
					                      $player->getWins());

					// show chat message
					$this->client->query('ChatSendServerMessageToLogin', $this->formatColors($message), $player->login);
				}

				// throw 'player wins' event
				$this->releaseEvent('onPlayerWins', $player);
			}
		}
	}  // endRaceRanking


	/**
	 * Handles connections of new players.
	 */
	function playerConnect($player) {

		// request information about the new player
		// (removed callback mechanism here, as GetPlayerInfo occasionally
		//  returns no data and then the connecting login would be lost)
		$login = $player[0];
		if ($this->server->getGame() == 'TMF') {
			$this->client->query('GetDetailedPlayerInfo', $login);
			$playerd = $this->client->getResponse();
			$this->client->query('GetPlayerInfo', $login, 1);
		} else {  // TMN/TMS/TMO
			$this->client->query('GetPlayerInfo', $login);
		}
		$player = $this->client->getResponse();

		// check for server
		if (isset($player['Flags']) && floor($player['Flags'] / 100000) % 10 != 0) {
			// register relay server
			if (!$this->server->isrelay && $player['Login'] != $this->server->serverlogin) {
				$this->server->relayslist[$player['Login']] = $player;

				// log console message
				$this->console('<<< relay server {1} ({2}) connected', $player['Login'],
				               stripColors($player['NickName'], false));
			}

		// on relay, check for player from master server
		} elseif ($this->server->isrelay && floor($player['Flags'] / 10000) % 10 != 0) {
			; // ignore
		} else {
			$ipaddr = isset($playerd['IPAddress']) ? preg_replace('/:\d+/', '', $playerd['IPAddress']) : '';  // strip port

			// if no data fetched, notify & kick the player
			if (!isset($player['Login']) || $player['Login'] == '') {
				$message = str_replace('{br}', LF, $this->getChatMessage('CONNECT_ERROR'));
				$message = $this->formatColors($message);
				$this->client->query('ChatSendServerMessageToLogin', str_replace(LF.LF, LF, $message), $login);
				if ($this->server->getGame() == 'TMN')
					$this->client->query('SendDisplayServerMessageToLogin', $login, $message, 'OK', '', 0);
				sleep(5);  // allow time to connect and see the notice
				if ($this->server->getGame() == 'TMF')
					$this->client->addCall('Kick', array($login, $this->formatColors($this->getChatMessage('CONNECT_DIALOG'))));
				else
					$this->client->addCall('Kick', array($login));
				// log console message
				$this->console('GetPlayerInfo failed for ' . $login . ' -- notified & kicked');
				return;

			// if player IP in ban list, notify & kick the player
			} elseif (!empty($this->bannedips) && in_array($ipaddr, $this->bannedips)) {
				$message = str_replace('{br}', LF, $this->getChatMessage('BANIP_ERROR'));
				$message = $this->formatColors($message);
				$this->client->query('ChatSendServerMessageToLogin', str_replace(LF.LF, LF, $message), $login);
				if ($this->server->getGame() == 'TMN')
					$this->client->query('SendDisplayServerMessageToLogin', $login, $message, 'OK', '', 0);
				sleep(5);  // allow time to connect and see the notice
				if ($this->server->getGame() == 'TMF')
					$this->client->addCall('Ban', array($login, $this->formatColors($this->getChatMessage('BANIP_DIALOG'))));
				else
					$this->client->addCall('Ban', array($login));
				// log console message
				$this->console('Player ' . $login . ' banned from ' . $ipaddr . ' -- notified & kicked');
				return;

			// client version checking on TMF
			} elseif ($this->server->getGame() == 'TMF') {
				// extract version number
				$version = str_replace(')', '', preg_replace('/.*\(/', '', $playerd['ClientVersion']));
				if ($version == '') $version = '2.11.11';
				$message = str_replace('{br}', LF, $this->getChatMessage('CLIENT_ERROR'));

				// if invalid version, notify & kick the player
				if ($this->settings['player_client'] != '' &&
				    strcmp($version, $this->settings['player_client']) < 0) {
					$this->client->query('ChatSendServerMessageToLogin', $this->formatColors($message), $login);
					sleep(5);  // allow time to connect and see the notice
					$this->client->addCall('Kick', array($login, $this->formatColors($this->getChatMessage('CLIENT_DIALOG'))));
					// log console message
					$this->console('Obsolete player client version ' . $version . ' for ' . $login . ' -- notified & kicked');
					return;
				}

				// if invalid version, notify & kick the admin
				if ($this->settings['admin_client'] != '' && $this->isAnyAdminL($player['Login']) &&
				    strcmp($version, $this->settings['admin_client']) < 0) {
					$this->client->query('ChatSendServerMessageToLogin', $this->formatColors($message), $login);
					sleep(5);  // allow time to connect and see the notice
					$this->client->addCall('Kick', array($login, $this->formatColors($this->getChatMessage('CLIENT_DIALOG'))));
					// log console message
					$this->console('Obsolete admin client version ' . $version . ' for ' . $login . ' -- notified & kicked');
					return;
				}
			}

			// if no TMN team, try again via world stats
			if ($this->server->getGame() == 'TMN' && !isLANLogin($login) &&
			    $player['LadderStats']['TeamName'] == '') {
				$data = new TMNDataFetcher($login, false);
				if ($data->teamname != '') {
					$player['LadderStats']['TeamName'] = $data->teamname;
				}
			}

			// create player object
			$player_item = new Player($this->server->getGame() == 'TMF' ? $playerd : $player);
			// set default window style & panels
			$player_item->style = $this->style;
			$player_item->panels['admin'] = $this->panels['admin'];
			$player_item->panels['donate'] = $this->panels['donate'];
			$player_item->panels['records'] = $this->panels['records'];
			$player_item->panels['vote'] = $this->panels['vote'];

			// adds a new player to the internal player list
			$this->server->players->addPlayer($player_item);

			// log console message
			$this->console('<< player {1} joined the game [{2} : {3} : {4} : {5} : {6}]',
			               $player_item->pid,
			               $player_item->login,
			               $player_item->nickname,
			               $player_item->nation,
			               $player_item->ladderrank,
			               $player_item->ip);

			// replace parameters
			$message = formatText($this->getChatMessage('WELCOME'),
			                      stripColors($player_item->nickname),
			                      $this->server->name, XASECO_VERSION);
			// hyperlink package name & version number on TMF
			if ($this->server->getGame() == 'TMF')
				$message = preg_replace('/XASECO.+' . XASECO_VERSION . '/', '$l[' . XASECO_TMN . ']$0$l', $message);

			// send welcome popup or chat message
			if ($this->settings['welcome_msg_window']) {
				if ($this->server->getGame() == 'TMF') {
					$message = str_replace('{#highlite}', '{#message}', $message);
					$message = preg_split('/{br}/', $this->formatColors($message));
					// repack all lines
					foreach ($message as &$line)
						$line = array($line);
					display_manialink($player_item->login, '',
					                  array('Icons64x64_1', 'Inbox'), $message,
					                  array(1.2), 'OK');
				} else {  // TMN
					$message = str_replace('{br}', LF, $this->formatColors($message));
					$this->client->query('SendDisplayServerMessageToLogin', $player_item->login, $message, 'OK', '', 0);
				}
			} else {
				$message = str_replace('{br}', LF, $this->formatColors($message));
				$this->client->query('ChatSendServerMessageToLogin', str_replace(LF.LF, LF, $message), $player_item->login);
			}

			// if there's a record on current track
			$cur_record = $this->server->records->getRecord(0);
			if ($cur_record !== false && $cur_record->score > 0) {
				// set message to the current record
				$message = formatText($this->getChatMessage('RECORD_CURRENT'),
				                      stripColors($this->server->challenge->name),
				                      ($this->server->gameinfo->mode == Gameinfo::STNT ?
				                       $cur_record->score : formatTime($cur_record->score)),
				                      stripColors($cur_record->player->nickname));
			} else {  // if there should be no record to display
				// display a no-record message
				$message = formatText($this->getChatMessage('RECORD_NONE'),
				                      stripColors($this->server->challenge->name));
			}

			// show top-8 & records of all online players before track
			if (($this->settings['show_recs_before'] & 2) == 2 && function_exists('show_trackrecs')) {
				show_trackrecs($this, $player_item->login, 1, 0);  // from chat.records2.php
			} elseif (($this->settings['show_recs_before'] & 1) == 1) {
				// or show original record message
				$this->client->query('ChatSendServerMessageToLogin', $this->formatColors($message), $player_item->login);
			}

			// throw main 'player connects' event
			$this->releaseEvent('onPlayerConnect', $player_item);
			// throw postfix 'player connects' event (access control)
			$this->releaseEvent('onPlayerConnect2', $player_item);
		}
	}  // playerConnect

	/**
	 * Handles disconnections of players.
	 */
	function playerDisconnect($player) {

		// check for relay server
		if (!$this->server->isrelay && array_key_exists($player[0], $this->server->relayslist)) {
			// log console message
			$this->console('>>> relay server {1} ({2}) disconnected', $player[0],
			               stripColors($this->server->relayslist[$player[0]]['NickName'], false));

			unset($this->server->relayslist[$player[0]]);
			return;
		}

		// delete player and put him into the player item
		// ignore event if disconnect fluke after player already left,
		// or on relay if player from master server (which wasn't added)
		if (!$player_item = $this->server->players->removePlayer($player[0]))
			return;

		// log console message
		$this->console('>> player {1} left the game [{2} : {3} : {4}]',
		               $player_item->pid,
		               $player_item->login,
		               $player_item->nickname,
		               formatTimeH($player_item->getTimeOnline() * 1000, false));

		// throw 'player disconnects' event
		$this->releaseEvent('onPlayerDisconnect', $player_item);
	}  // playerDisconnect


	/**
	 * Handles clicks on server messageboxes.
	 */
	function playerServerMessageAnswer($answer) {

		if ($answer[2]) {
			// throw TMN 'click' event
			$this->releaseEvent('onPlayerServerMessageAnswer', $answer);
		}
	}  // playerServerMessageAnswer


	/**
	 * Player reaches finish.
	 */
	function playerFinish($finish) {

		// if no track info, or if server 'finish', bail out immediately
		if ($this->server->challenge->name == '' || $finish[0] == 0)
			return;

		// if relay server or not in Play status, bail out immediately
		if ($this->server->isrelay || $this->currstatus != 4)
			return;

		// check for valid player
		if ((!$player = $this->server->players->getPlayer($finish[1])) ||
		    $player->login == '')
			return;

		// build a record object with the current finish information
		$finish_item = new Record();
		$finish_item->player = $player;
		$finish_item->score = $finish[2];
		$finish_item->date = strftime('%Y-%m-%d %H:%M:%S');
		$finish_item->new = false;
		$finish_item->challenge = clone $this->server->challenge;
		unset($finish_item->challenge->gbx);  // reduce memory usage
		unset($finish_item->challenge->tmx);

		// throw prefix 'player finishes' event (checkpoints)
		$this->releaseEvent('onPlayerFinish1', $finish_item);
		// throw main 'player finishes' event
		$this->releaseEvent('onPlayerFinish', $finish_item);
	}  // playerFinish


	/**
	 * Receives chat messages and reacts on them.
	 * Reactions are done by the chat plugins.
	 */
	function playerChat($chat) {

		// verify login
		if ($chat[1] == '' || $chat[1] == '???') {
			trigger_error('playerUid ' . $chat[0] . 'has login [' . $chat[1] . ']!', E_USER_WARNING);
			$this->console('playerUid {1} attempted to use chat command "{2}"',
			               $chat[0], $chat[2]);
			return;
		}

		// ignore master server messages on relay
		if ($this->server->isrelay && $chat[1] == $this->server->relaymaster['Login'])
			return;

		// check for chat command '/' prefix
		$command = $chat[2];
		if ($command != '' && $command[0] == '/') {
			// remove '/' prefix
			$command = substr($command, 1);

			// split strings at spaces and add them into an array
			$params = explode(' ', $command, 2);
			$translated_name = str_replace('+', 'plus', $params[0]);
			$translated_name = str_replace('-', 'dash', $translated_name);

			// check if the function and the command exist
			if (function_exists('chat_' . $translated_name)) {
				// insure parameter exists & is trimmed
				if (isset($params[1]))
					$params[1] = trim($params[1]);
				else
					$params[1] = '';

				// get & verify player object
				if (($author = $this->server->players->getPlayer($chat[1])) &&
				    $author->login != '') {
					// log console message
					$this->console('player {1} used chat command "/{2} {3}"',
					               $chat[1], $params[0], $params[1]);

					// save circumstances in array
					$chat_command = array();
					$chat_command['author'] = $author;
					$chat_command['params'] = $params[1];

					// call the function which belongs to the command
					call_user_func('chat_' . $translated_name, $this, $chat_command);
				} else {
					trigger_error('Player object for \'' . $chat[1] . '\' not found!', E_USER_WARNING);
					$this->console('player {1} attempted to use chat command "/{2} {3}"',
					               $chat[1], $params[0], $params[1]);
				}
			} elseif ($params[0] == 'version' ||
			          ($params[0] == 'serverlogin' && $this->server->getGame() == 'TMF')) {
				// log built-in commands
				$this->console('player {1} used built-in command "/{2}"',
				               $chat[1], $command);
			} else {
				// optionally log bogus chat commands too
				if ($this->settings['log_all_chat']) {
					if ($chat[0] != $this->server->id) {
						$this->console('({1}) {2}', $chat[1], stripColors($chat[2], false));
					}
				}
			}
		} else {
			// optionally log all normal chat too
			if ($this->settings['log_all_chat']) {
				if ($chat[0] != $this->server->id && $chat[2] != '') {
					$this->console('({1}) {2}', $chat[1], stripColors($chat[2], false));
				}
			}
		}
	}  // playerChat


	/**
	 * Gets the specified chat message out of the settings file.
	 */
	function getChatMessage($name) {

		return htmlspecialchars_decode($this->chat_messages[$name][0]);
	}  // getChatMessage


	/**
	 * Checks if an admin is allowed to perform this ability
	 */
	function allowAdminAbility($ability) {

		// map to uppercase before checking list
		$ability = strtoupper($ability);
		if (isset($this->adm_abilities[$ability])) {
			return $this->adm_abilities[$ability][0];
		} else {
			return false;
		}
	}  // allowAdminAbility

	/**
	 * Checks if an operator is allowed to perform this ability
	 */
	function allowOpAbility($ability) {

		// map to uppercase before checking list
		$ability = strtoupper($ability);
		if (isset($this->op_abilities[$ability])) {
			return $this->op_abilities[$ability][0];
		} else {
			return false;
		}
	}  // allowOpAbility

	/**
	 * Checks if the given player is allowed to perform this ability
	 */
	function allowAbility($player, $ability) {

		// check for unlocked password
		if ($this->settings['lock_password'] != '' && !$player->unlocked)
			return false;

		// MasterAdmins can always do everything
		if ($this->isMasterAdmin($player))
			return true;

		// check Admins & their abilities
		if ($this->isAdmin($player))
			return $this->allowAdminAbility($ability);

		// check Operators & their abilities
		if ($this->isOperator($player))
			return $this->allowOpAbility($ability);

		return false;
	}  // allowAbility


	/**
	 * Checks if the given player IP matches the corresponding list IP,
	 * allowing for class C and B wildcards, and multiple comma-separated
	 * IPs / wildcards.
	 */
	function ip_match($playerip, $listip) {

		// check for offline player (removeadmin / removeop)
		if ($playerip == '')
			return true;

		$match = false;
		// check all comma-separated IPs/wildcards
		foreach (explode(',', $listip) as $ip) {
			// check for complete list IP
			if (preg_match('/^\d+\.\d+\.\d+\.\d+$/', $ip))
				$match = ($playerip == $ip);
			// check class B wildcard
			elseif (substr($ip, -4) == '.*.*')
				$match = (preg_replace('/\.\d+\.\d+$/', '', $playerip) == substr($ip, 0, -4));
			// check class C wildcard
			elseif (substr($ip, -2) == '.*')
				$match = (preg_replace('/\.\d+$/', '', $playerip) == substr($ip, 0, -2));

			if ($match) return true;
		}
		return false;
	}

	/**
	 * Checks if the given player is in masteradmin list with, optionally,
	 * an authorized IP.
	 */
	function isMasterAdmin($player) {

		// check for masteradmin list entry
		if (isset($player->login) && $player->login != '' && isset($this->masteradmin_list['TMLOGIN']))
			if (($i = array_search($player->login, $this->masteradmin_list['TMLOGIN'])) !== false)
				// check for matching IP if set
				if ($this->masteradmin_list['IPADDRESS'][$i] != '')
					if (!$this->ip_match($player->ip, $this->masteradmin_list['IPADDRESS'][$i])) {
						trigger_error("Attempt to use MasterAdmin login '" . $player->login . "' from IP " . $player->ip . " !", E_USER_WARNING);
						return false;
					} else
						return true;
				else
					return true;
			else
				return false;
		else
			return false;
	}  // isMasterAdmin

	/**
	 * Checks if the given player is in admin list with, optionally,
	 * an authorized IP.
	 */
	function isAdmin($player) {

		// check for admin list entry
		if (isset($player->login) && $player->login != '' && isset($this->admin_list['TMLOGIN']))
			if (($i = array_search($player->login, $this->admin_list['TMLOGIN'])) !== false)
				// check for matching IP if set
				if ($this->admin_list['IPADDRESS'][$i] != '')
					if (!$this->ip_match($player->ip, $this->admin_list['IPADDRESS'][$i])) {
						trigger_error("Attempt to use Admin login '" . $player->login . "' from IP " . $player->ip . " !", E_USER_WARNING);
						return false;
					} else
						return true;
				else
					return true;
			else
				return false;
		else
			return false;
	}  // isAdmin

	/**
	 * Checks if the given player is in operator list with, optionally,
	 * an authorized IP.
	 */
	function isOperator($player) {

		// check for operator list entry
		if (isset($player->login) && $player->login != '' && isset($this->operator_list['TMLOGIN']))
			if (($i = array_search($player->login, $this->operator_list['TMLOGIN'])) !== false)
				// check for matching IP if set
				if ($this->operator_list['IPADDRESS'][$i] != '')
					if (!$this->ip_match($player->ip, $this->operator_list['IPADDRESS'][$i])) {
						trigger_error("Attempt to use Operator login '" . $player->login . "' from IP " . $player->ip . " !", E_USER_WARNING);
						return false;
					} else
						return true;
				else
					return true;
			else
				return false;
		else
			return false;
	}  // isOperator

	/**
	 * Checks if the given player is in any admin tier with, optionally,
	 * an authorized IP.
	 */
	function isAnyAdmin($player) {

		return ($this->isMasterAdmin($player) || $this->isAdmin($player) || $this->isOperator($player));
	}  // isAnyAdmin


	/**
	 * Checks if the given player login is in masteradmin list.
	 */
	function isMasterAdminL($login) {

		if ($login != '' && isset($this->masteradmin_list['TMLOGIN'])) {
			return in_array($login, $this->masteradmin_list['TMLOGIN']);
		} else {
			return false;
		}
	}  // isMasterAdminL

	/**
	 * Checks if the given player login is in admin list.
	 */
	function isAdminL($login) {

		if ($login != '' && isset($this->admin_list['TMLOGIN'])) {
			return in_array($login, $this->admin_list['TMLOGIN']);
		} else {
			return false;
		}
	}  // isAdminL

	/**
	 * Checks if the given player login is in operator list.
	 */
	function isOperatorL($login) {

		// check for operator list entry
		if ($login != '' && isset($this->operator_list['TMLOGIN']))
			return in_array($login, $this->operator_list['TMLOGIN']);
		else
			return false;
	}  // isOperatorL

	/**
	 * Checks if the given player login is in any admin tier.
	 */
	function isAnyAdminL($login) {

		return ($this->isMasterAdminL($login) || $this->isAdminL($login) || $this->isOperatorL($login));
	}  // isAnyAdminL


	/**
	 * Checks if the given player is a spectator.
	 */
	function isSpectator($player) {

		// get current player status
		if ($this->server->getGame() != 'TMF') {
			$this->client->query('GetPlayerInfo', $player->login);
			$info = $this->client->getResponse();
			if (isset($info['IsSpectator']))
				$player->isspectator = $info['IsSpectator'];
			else
				$player->isspectator = false;
		}
		return $player->isspectator;
	}  // isSpectator

	/**
	 * Handles cheating player.
	 */
	function processCheater($login, $checkpoints, $chkpt, $finish) {

		// collect checkpoints
		$cps = '';
		foreach ($checkpoints as $cp)
			$cps .= formatTime($cp) . '/';
		$cps = substr($cps, 0, strlen($cps)-1);  // strip trailing '/'

		// report cheat
		if ($finish == -1)
			trigger_error('Cheat by \'' . $login . '\' detected! CPs: ' . $cps . ' Last: ' . formatTime($chkpt[2]) . ' index: ' . $chkpt[4], E_USER_WARNING);
		else
			trigger_error('Cheat by \'' . $login . '\' detected! CPs: ' . $cps . ' Finish: ' . formatTime($finish), E_USER_WARNING);

		// check for valid player
		if (!$player = $this->server->players->getPlayer($login)) {
			trigger_error('Player object for \'' . $login . '\' not found!', E_USER_WARNING);
			return;
		}

		switch ($this->settings['cheater_action']) {

		case 1:  // set to spec (TMF only)
			$rtn = $this->client->query('ForceSpectator', $login, 1);
			if (!$rtn) {
				trigger_error('[' . $this->client->getErrorCode() . '] ForceSpectator - ' . $this->client->getErrorMessage(), E_USER_WARNING);
			} else {
				// allow spectator to switch back to player
				$rtn = $this->client->query('ForceSpectator', $login, 0);
			}
			// force free camera mode on spectator
			$this->client->addCall('ForceSpectatorTarget', array($login, '', 2));
			// free up player slot
			$this->client->addCall('SpectatorReleasePlayerSlot', array($login));

			// log console message
			$this->console('Cheater [{1} : {2}] forced into free spectator!', $login, stripColors($player->nickname, false));

			// show chat message
			$message = formatText('{#server}>> {#admin}Cheater {#highlite}{1}$z$s{#admin} forced into spectator!',
			                      str_ireplace('$w', '', $player->nickname));
			$this->client->query('ChatSendServerMessage', $this->formatColors($message));
			break;

		case 2:  // kick
			// log console message
			$this->console('Cheater [{1} : {2}] kicked!', $login, stripColors($player->nickname, false));

			// show chat message
			$message = formatText('{#server}>> {#admin}Cheater {#highlite}{1}$z$s{#admin} kicked!',
			                      str_ireplace('$w', '', $player->nickname));
			$this->client->query('ChatSendServerMessage', $this->formatColors($message));

			// kick the cheater
			$this->client->query('Kick', $login);
			break;

		case 3:  // ban (& kick)
			// log console message
			$this->console('Cheater [{1} : {2}] banned!', $login, stripColors($player->nickname, false));

			// show chat message
			$message = formatText('{#server}>> {#admin}Cheater {#highlite}{1}$z$s{#admin} banned!',
			                      str_ireplace('$w', '', $player->nickname));
			$this->client->query('ChatSendServerMessage', $this->formatColors($message));

			// update banned IPs file
			$this->bannedips[] = $player->ip;
			$this->writeIPs();

			// ban the cheater and also kick him
			$this->client->query('Ban', $player->login);
			break;

		case 4:  // blacklist & kick
			// log console message
			$this->console('Cheater [{1} : {2}] blacklisted!', $login, stripColors($player->nickname, false));

			// show chat message
			$message = formatText('{#server}>> {#admin}Cheater {#highlite}{1}$z$s{#admin} blacklisted!',
			                      str_ireplace('$w', '', $player->nickname));
			$this->client->query('ChatSendServerMessage', $this->formatColors($message));

			// blacklist the cheater and then kick him
			$this->client->query('BlackList', $player->login);
			$this->client->query('Kick', $player->login);

			// update blacklist file
			$filename = $this->settings['blacklist_file'];
			$rtn = $this->client->query('SaveBlackList', $filename);
			if (!$rtn) {
				trigger_error('[' . $this->client->getErrorCode() . '] SaveBlackList (kick) - ' . $this->client->getErrorMessage(), E_USER_WARNING);
			}
			break;

		case 5:  // blacklist & ban
			// log console message
			$this->console('Cheater [{1} : {2}] blacklisted & banned!', $login, stripColors($player->nickname, false));

			// show chat message
			$message = formatText('{#server}>> {#admin}Cheater {#highlite}{1}$z$s{#admin} blacklisted & banned!',
			                      str_ireplace('$w', '', $player->nickname));
			$this->client->query('ChatSendServerMessage', $this->formatColors($message));

			// update banned IPs file
			$this->bannedips[] = $player->ip;
			$this->writeIPs();

			// blacklist & ban the cheater
			$this->client->query('BlackList', $player->login);
			$this->client->query('Ban', $player->login);

			// update blacklist file
			$filename = $this->settings['blacklist_file'];
			$rtn = $this->client->query('SaveBlackList', $filename);
			if (!$rtn) {
				trigger_error('[' . $this->client->getErrorCode() . '] SaveBlackList (ban) - ' . $this->client->getErrorMessage(), E_USER_WARNING);
			}
			break;

		default: // ignore
		}
	}  // processCheater


	/**
	 * Finds a player ID from its login.
	 */
	function getPlayerId($login, $forcequery = false) {

		if (isset($this->server->players->player_list[$login]) &&
		    $this->server->players->player_list[$login]->id > 0 && !$forcequery) {
			$rtn = $this->server->players->player_list[$login]->id;
		} else {
			$query = 'SELECT id FROM players
			          WHERE login=' . quotedString($login);
			$result = mysql_query($query);
			if (mysql_num_rows($result) > 0) {
				$row = mysql_fetch_row($result);
				$rtn = $row[0];
			} else {
				$rtn = 0;
			}
			mysql_free_result($result);
		}
		return $rtn;
	}  // getPlayerId

	/**
	 * Finds a player Nickname from its login.
	 */
	function getPlayerNick($login, $forcequery = false) {

		if (isset($this->server->players->player_list[$login]) &&
		    $this->server->players->player_list[$login]->nickname != '' && !$forcequery) {
			$rtn = $this->server->players->player_list[$login]->nickname;
		} else {
			$query = 'SELECT nickname FROM players
			          WHERE login=' . quotedString($login);
			$result = mysql_query($query);
			if (mysql_num_rows($result) > 0) {
				$row = mysql_fetch_row($result);
				$rtn = $row[0];
			} else {
				$rtn = '';
			}
			mysql_free_result($result);
		}
		return $rtn;
	}  // getPlayerNick


	/**
	 * Finds an online player object from its login or Player_ID
	 * If $offline = true, search player database instead
	 * Returns false if not found
	 */
	function getPlayerParam($player, $param, $offline = false) {

		// if numeric param, find Player_ID from /players list (hardlimited to 300)
		if (is_numeric($param) && $param >= 0 && $param < 300) {
			if (empty($player->playerlist)) {
				$message = '{#server}> {#error}Use {#highlite}$i/players {#error}first (optionally {#highlite}$i/players <string>{#error})';
				$this->client->query('ChatSendServerMessageToLogin', $this->formatColors($message), $player->login);
				return false;
			}
			$pid = ltrim($param, '0');
			$pid--;
			// find player by given #
			if (array_key_exists($pid, $player->playerlist)) {
				$param = $player->playerlist[$pid]['login'];
				// check online players list
				$target = $this->server->players->getPlayer($param);
			} else {
				// try param as login string as yet
				$target = $this->server->players->getPlayer($param);
				if (!$target) {
					$message = '{#server}> {#error}Player_ID not found! Type {#highlite}$i/players {#error}to see all players.';
					$this->client->query('ChatSendServerMessageToLogin', $this->formatColors($message), $player->login);
					return false;
				}
			}
		} else {  // otherwise login string
			// check online players list
			$target = $this->server->players->getPlayer($param);
		}

		// not found and offline allowed?
		if (!$target && $offline) {
			// check offline players database
			$query = 'SELECT * FROM players
			          WHERE login=' . quotedString($param);
			$result = mysql_query($query);
			if (mysql_num_rows($result) > 0) {
				$row = mysql_fetch_object($result);
				// create dummy player object
				$target = new Player();
				$target->id = $row->Id;
				$target->login = $row->Login;
				$target->nickname = $row->NickName;
				$target->nation = $row->Nation;
				$target->teamname = $row->TeamName;
				$target->wins = $row->Wins;
				$target->timeplayed = $row->TimePlayed;
			}
			mysql_free_result($result);
		}

		// found anyone anywhere?
		if (!$target) {
			$message = '{#server}> {#highlite}' . $param . ' {#error}is not a valid player! Use {#highlite}$i/players {#error}to find the correct login or Player_ID.';
			$this->client->query('ChatSendServerMessageToLogin', $this->formatColors($message), $player->login);
		}
		return $target;
	}  // getPlayerParam


	/**
	 * Finds a challenge ID from its UID.
	 */
	function getChallengeId($uid) {

		$query = 'SELECT Id FROM challenges
		          WHERE Uid=' . quotedString($uid);
		$res = mysql_query($query);
		if (mysql_num_rows($res) > 0) {
			$row = mysql_fetch_row($res);
			$rtn = $row[0];
		} else {
			$rtn = 0;
		}
		mysql_free_result($res);
		return $rtn;
	}  // getChallengeId

	/**
	 * Gets current servername
	 */
	function getServerName() {

		$this->client->query('GetServerName');
		$this->server->name = $this->client->getResponse();
		return $this->server->name;
	}

	/**
	 * Gets current server name & options
	 */
	function getServerOptions() {

		$this->client->query('GetServerOptions');
		$options = $this->client->getResponse();
		$this->server->name = $options['Name'];
		$this->server->maxplay = $options['CurrentMaxPlayers'];
		$this->server->maxspec = $options['CurrentMaxSpectators'];
		$this->server->votetime = $options['CurrentCallVoteTimeOut'];
		$this->server->voterate = $options['CallVoteRatio'];
	}


	/**
	 * Formats aseco color codes in a string,
	 * for example '{#server} hello' will end up as '$ff0 hello'.
	 * It depends on what you've set in the config file.
	 */
	function formatColors($text) {

		// replace all chat colors
		foreach ($this->chat_colors as $key => $value) {
			$text = str_replace('{#'.strtolower($key).'}', $value[0], $text);
		}
		return $text;
	}  // formatColors


	/**
	 * Outputs a formatted string without datetime.
	 */
	function console_text() {

		$args = func_get_args();
		$message = call_user_func_array('formatText', $args) . CRLF;
		echo $message;
		doLog($message);
		flush();
	}  // console_text

	/**
	 * Outputs a string to console with datetime prefix.
	 */
	function console() {

		$args = func_get_args();
		$message = '[' . date('m/d,H:i:s') . '] ' . call_user_func_array('formatText', $args) . CRLF;
		echo $message;
		doLog($message);
		flush();
	}  // console

}  // class Aseco

// define process settings
if (function_exists('date_default_timezone_get') && function_exists('date_default_timezone_set'))
	date_default_timezone_set(@date_default_timezone_get());
$limit = ini_get('memory_limit');
if (shorthand2bytes($limit) < 128 * 1048576)
	ini_set('memory_limit', '128M');
setlocale(LC_NUMERIC, 'C');

// create an instance of XASECO and run it
$aseco = new Aseco(false);
$aseco->run('config.xml');
?>
