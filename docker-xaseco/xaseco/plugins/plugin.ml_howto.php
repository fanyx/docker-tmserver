<?php
/* vim: set noexpandtab tabstop=2 softtabstop=2 shiftwidth=2: */

/**
 * ManiaLink example plugin (TMF).
 * Demonstrates how to create ManiaLink windows.
 * Created by Xymph
 */

Aseco::addChatCommand('howtofixed', 'Demonstrates a fixed content window');
Aseco::addChatCommand('howtosingle', 'Demonstrates a single-page window');
Aseco::addChatCommand('howtomulti', 'Demonstrates a multi-page window');

/*
The ManiaLink windows are provided by includes/manialinks.inc.php.
It operates via a data-driven interface that allows you to easily
define lines and columns in the windows without worrying about the
actual XML to format everything correctly.

A single ManiaLink window is produced by display_manialink and requires
the following input variables:

	$login : player login to send window to
	         an obvious field
	$header: string
	         a string that's put in the window header
	$icon  : array( $style, $substyle {, $sizechg} )
	$data  : array( $line1=array($col1, $col2, ...), $line2=array(...) )
	         the window contents in lines and (optional) columns
	$widths: array( $overal, $col1, $col2, ...)
	         the overal window width and the (optional) column widths
	$button: string
	         a string used for the close button, typically 'OK'

Each line in the $data array is represent by an array containing one
or more strings.  If a single string, that text will be able to use
the full width of the window.  If multiple strings, then each string
is put in its own column.  The number of columns in each multi-column
line must match the number of columns specified in the $widths array,
otherwise there will be layout glitches.  An empty line array will
produce an empty line in the window.

A multi-page ManiaLink window is produced by display_manialink_multi,
and is an extension of the above principles, with all data stored in
the $player object instead of separate input variables, so that it is
preserved throughout the player's interaction with the window:

	$player: player object to send windows to
	 ->msgs: array( array( $ptr, $header, $widths, $icon ),
	  page1:        array( $line1=array($col1, $col2, ...), $line2=array(...) ),
	      2:        array( $line1=array($col1, $col2, ...), $line2=array(...) ),
	                ... )
	$header: string
	$icon  : array( $style, $substyle {, $sizechg} )
	$widths: array( $overal, $col1, $col2, ...)

The first array in $player->msgs (at index [0]) contains the control
variables: $ptr is the pointer to the current page, typically 1 to
start, and $header and $widths are before.  All subsequent arrays
(at index[1+]) contain one page, each structured the same way as the
aforementioned single page.  The button texts are handled automatically,
including the Next5/Prev5 ones if there are more than 5 pages in
the window.

The manialinks.inc.php module will do a $aseco->formatColors call over
the entire XML output, so you can use XAseco color tags without having
to convert them.

There currently is no general framework for handling widgets, the CPS
panel is handled by dedicated functions.
*/

function chat_howtofixed($aseco, $command) {

	if ($aseco->server->getGame() == 'TMF') {
		$header = '{#welcome}ManiaLink Demonstration:';
		$data = array();
		$data[] = array('{#message}This window$g illustrates a simple ManiaLink pop-up');
		$data[] = array('with fixed text and some $f60color$g tags and links.');
		$data[] = array();
		$data[] = array('Check the $l[' . XASECO_ORG . ']XASECO homepage$l :)');

		// display ManiaLink message
		display_manialink($command['author']->login, $header, array('Icons64x64_1', 'TrackInfo', -0.01), $data, array(0.9), 'OK');
	}
}  // chat_howtofixed

function chat_howtosingle($aseco, $command) {

	if ($aseco->server->getGame() == 'TMF') {
		$header = '{#welcome}ManiaLink Demonstration:';
		$data = array();
		$data[] = array('{#message}This window$g illustrates a single-page ManiaLink');
		$data[] = array('pop-up with dynamic text and two columns in the rest');
		$data[] = array('of the window.');
		$data[] = array();
		// put in some filler data
		for ($i = 1; $i <= 5; $i++)
			$data[] = array('Line ' . $i, md5($i));

		// display ManiaLink message
		display_manialink($command['author']->login, $header, array('Icons64x64_1', 'TrackInfo', -0.01), $data, array(0.9, 0.2, 0.7), 'OK');
	}
}  // chat_howtosingle

function chat_howtomulti($aseco, $command) {

	$player = $command['author'];

	if ($aseco->server->getGame() == 'TMF') {
		$header = '{#welcome}ManiaLink Demonstration:';
		$player->msgs = array();
		$player->msgs[0] = array(1, $header, array(0.9, 0.2, 0.7), array('Icons64x64_1', 'GenericButton'));

		$data = array();
		$data[] = array('{#message}This window$g illustrates a multi-page ManiaLink');
		$data[] = array('pop-up with dynamic text and two columns in the rest');
		$data[] = array('of the window.');
		$data[] = array();

		// put in some filler data
		$ctr = 1;
		for ($i = 1; $i <= 15; $i++) {
			$data[] = array('Line ' . $i, md5($i));
			if ($i % 5 == 0) {
				$player->msgs[] = $data;
				$data = array();
				$data[] = array('Intro text on $wPage ' . (++$ctr));
				$data[] = array();
			}
		}

		// display ManiaLink message
		display_manialink_multi($player);
	}
}  // chat_howtomulti
?>
