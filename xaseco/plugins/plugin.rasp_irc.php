<?php
/* vim: set noexpandtab tabstop=2 softtabstop=2 shiftwidth=2: */

/**
 * IRC plugin.
 * Provides IRC bot to link the server to a channel on an IRC server.
 *
 * Dependencies: none
 */

Aseco::registerEvent('onSync', 'irc_onsync');
Aseco::registerEvent('onMainLoop', 'irc_onloop');

// called @ onSync
function irc_onsync($aseco, $null) {
	global $con, $CONFIG, $show_connect, $ircmsgs, $aseco;

	set_time_limit(0);

	/* We need this to see if we need to JOIN (the channel) during
	the first iteration of the main loop */
	$firstTime = true;
	$aseco->console_text('****** Connecting to IRC *********');
	/* Connect to the irc server */
	$con['socket'] = fsockopen($CONFIG['server'], $CONFIG['port']);

	/* Check that we have connected */
	if (!$con['socket']) {
		print ('Could not connect to: ' . $CONFIG['server'] . ' on port ' . $CONFIG['port']);
	} else {
		/* Send the username and nick */
		irc_send('USER ' . $CONFIG['nick'] . ' codedemons.net codedemons.net :' . $CONFIG['name']);
		irc_send('NICK ' . $CONFIG['nick'] . ' codedemons.net');

		/* Here is the loop. Read the incoming data (from the socket connection) */
		while (!feof($con['socket'])) {
			/* Think of $con['buffer']['all'] as a line of chat messages.
			   We are getting a 'line' and getting rid of whitespace around it. */
			$con['buffer']['all'] = trim(fgets($con['socket'], 4096));

			if ($show_connect) {
				$aseco->console_text($con['buffer']['all']);
			}
			/* If the server is PINGing, then PONG. This is to tell the server that
			   we are still here, and have not lost the connection */
			if (substr($con['buffer']['all'], 0, 6) == 'PING :') {
				/* PONG : is followed by the line that the server
				   sent us when PINGing */
				irc_send('PONG :'.substr($con['buffer']['all'], 6));
				/* If this is the first time we have reached this point,
				   then JOIN the channel */
				if ($firstTime) {
					irc_send('JOIN '. $CONFIG['channel']);
					/* The next time we get here, it will NOT be the firstTime */
					$firstTime = false;
				}

				/* Make sure that we have a NEW line of chats to analyse. If we don't,
				   there is no need to parse the data again */
			} elseif ($old_buffer != $con['buffer']['all']) {
				if (strpos($con['buffer']['all'], ':End of /NAMES list.') != false) {
					$aseco->console_text('****** Connected to IRC! *********');
					break;
				}
			}

			$old_buffer = $con['buffer']['all'];
		}
	}
}  // irc_onsync

function irc_getMessages() {
	global $aseco, $linesbuffer, $ircmsgs, $outbuffer;

	$rtn = $aseco->client->query('GetChatLines', 50, 0);
	$lines = $aseco->client->getResponse();
	if (!empty($lines)) {
		if ($aseco->client->isError()) {
			trigger_error('[' . $aseco->client->getErrorCode() . '] GetChatLines - ' . $aseco->client->getErrorMessage(), E_USER_WARNING);
		} else {
			foreach ($lines as $msg) {
				if (!in_array($msg, $linesbuffer)) {
					if (!strstr($msg, '-IRC-')) {
						$ircmsgs[] = $msg;
					}
					if (count($linesbuffer) >= 100) {
						$drop = array_shift($linesbuffer);
						$linesbuffer[] = $msg;
					} else {
						$linesbuffer[] = $msg;
					}
				}
			}
		}
	}
}  // irc_getMessages

// called @ onMainLoop
function irc_onloop($aseco, $null) {
	global $con, $aseco, $CONFIG, $ircmsgs, $outbuffer;

	//executeCallbacks();
	$srch = '[';
	if (!$con['socket']) {
		sleep(2);
		$null = '';
		irc_onsync($aseco, $null);
		return;
	}

	if (!feof($con['socket'])) {
		irc_getMessages();
		if (!empty($ircmsgs)) {
			foreach ($ircmsgs as $msg) {
				if (strstr($msg, $srch)) {
					irc_send(irc_prep('', stripColors($msg)));
				}
			}
			$ircmsgs = array();
		}
		irc_send(irc_prep2('', '-'));
		if (!$buffer = fgets($con['socket'], 4096)) {
		} else {
			$buffer = trim($buffer);
			$name_buffer = explode(' ', $buffer, 4);
			$msg_buffer = explode(' ', str_replace('\'', '', $buffer), 4);
			$player = substr($name_buffer[0], 1, strpos($name_buffer['0'], '!')-1);
			$text = substr($msg_buffer[3], 1);
			if ($player != $CONFIG['nick'] && strlen($player) > 0) {
				$player = '$f00'.$player;
				$msg = '$0f0-IRC-$fff['.$player.'$fff] '.$text;
				$aseco->client->query('ChatSendServerMessage', $msg);
			}
		}
	} else {
		sleep(2);
		$null = '';
		irc_onsync($aseco, $null);
		return;
	}
}  // irc_onloop

function irc_send($command) {
	global $con, $aseco;

	if (!$con['socket']) {
		sleep(2);
		$null = '';
		irc_onsync($aseco, $null);
		return;
	}
	fwrite($con['socket'], $command . CRLF);
}  // irc_send

function irc_prep($type, $message) {
	global $CONFIG;

	return ('PRIVMSG '. $CONFIG['channel'] .' :'.$type.''.$message);
}  // irc_prep

function irc_prep2($type, $message) {
	global $CONFIG;

	return ('PRIVMSG '. $CONFIG['nick'] .', '. $CONFIG['channel'] .' :'.$type.''.$message);
}  // irc_prep2
?>
