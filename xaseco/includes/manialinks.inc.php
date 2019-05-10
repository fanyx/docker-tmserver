<?php
/* vim: set noexpandtab tabstop=2 softtabstop=2 shiftwidth=2: */

/**
 * Provides simple ManiaLink windows as replacement for TMN windows.
 * Also handles special panels and custom UI changes.
 * Created by Xymph
 *
 * Currently reserved ManiaLink id's and action's:
 *        id= "0": dummy for custom_ui block
 *            "1": Main pop-up window
 *            "2": CheckPoints panel
 *            "3": Admin panel
 *            "4": Records panel
 *            "5": Vote panel
 *            "6": Donate panel
 *            "7": Messages window
 *            "8": /msglog button
 *            "9": Scoreboard stats panel
 *    action= "0": Close main pop-up window
 *           "-1": Ignore action (by server)
 *           "-4": First page of main window
 *           "-3": Previous5 page of main window
 *           "-2": Previous page of main window
 *            "1": Refresh current page
 *            "2": Next page of main window
 *            "3": Next5 page of main window
 *            "4": Last page of main window
 *           "-5": /stats field Time Played       call "/active"
 *           "-6": /stats field Server Rank       call "/top100"
 *            "5": /stats field Records           call "/toprecs"
 *            "6": /stats field Races Won         call "/topwins"
 *            "7": Records panel PB field         call "/topsums"
 *            "8": Records panel Local field      call "/recs"
 *            "9": Records panel Dedi field       call "/dedirecs"
 *           "10": Records panel TMX field        call "/tmxrecs"
 *           "11": /list Env field Stadium        call "/list env:Stadium"
 *           "12": /list Env field Alpine         call "/list env:Alpine"
 *           "13": /list Env field Bay            call "/list env:Bay"
 *           "14": /list Env field Coast          call "/list env:Coast"
 *           "15": /list Env field Island         call "/list env:Island"
 *           "16": /list Env field Rally          call "/list env:Rally"
 *           "17": /list Env field Speed          call "/list env:Speed"
 *           "18": Vote panel Yes, F5 key         call "/y"
 *           "19": Vote panel No, F6 key
 *           "20": /jukebox display Clear button  call "/admin clearjukebox"
 *           "21": Admin panel ClipRewind button  call "/admin restartmap"
 *           "22": Admin panel ClipPause button   call "/admin endround"
 *           "23": Admin panel ClipPlay button    call "/admin nextmap"
 *           "24": Admin panel Refresh button     call "/admin replaymap"
 *           "25": Admin panel ArrowGreen button  call "/admin pass"
 *           "26": Admin panel ArrowRed button    call "/admin cancel"
 *           "27": Admin panel Buddies button     call "/admin players"
 *           "28": Server coppers Payment dialog Yes
 *           "29": Server coppers Payment dialog No
 *           "30": Donate panel, button 1         call "/donate 20"
 *           "31": Donate panel, button 2         call "/donate 50"
 *           "32": Donate panel, button 3         call "/donate 100"
 *           "33": Donate panel, button 4         call "/donate 200"
 *           "34": Donate panel, button 5         call "/donate 500"
 *           "35": Donate panel, button 6         call "/donate 1000"
 *           "36": Donate panel, button 7         call "/donate 2000"
 *      "37"-"48": Vote panels, handled in plugin.panels.php
 *     "-7"-"-48": Admin panels, handled in plugin.panels.php
 *     "49"-"100": Window styles, handled in plugin.style.php
 *   "-49"-"-100": Records panels, handled in plugin.panels.php
 *   "101"-"2000": Track numbers for /jukebox, handled in plugin.rasp_jukebox.php
 * "-101"-"-2000": Track authors for /list, handled in plugin.rasp_jukebox.php
 *"-2001"-"-2100": Jukebox drop numbers, handled in plugin.rasp_jukebox.php
 *"-2101"-"-4000": Song numbers, handled in plugin.music_server.php
 *  "2001"-"2200": Player numbers for /stats, handled in chat.players.php
 *  "2201"-"2400": Player numbers for /admin warn, handled in chat.admin.php
 *  "2401"-"2600": Player numbers for /admin ignore, handled in chat.admin.php
 *  "2601"-"2800": Player numbers for /admin unignore, handled in chat.admin.php
 *  "2801"-"3000": Player numbers for /admin kick, handled in chat.admin.php
 *  "3001"-"3200": Player numbers for /admin ban, handled in chat.admin.php
 *  "3201"-"3400": Player numbers for /admin unban, handled in chat.admin.php
 *  "3401"-"3600": Player numbers for /admin black, handled in chat.admin.php
 *  "3601"-"3800": Player numbers for /admin unblack, handled in chat.admin.php
 *  "3801"-"4000": Player numbers for /admin addguest, handled in chat.admin.php
 *  "4001"-"4200": Player numbers for /admin removeguest, handled in chat.admin.php
 *  "4201"-"4400": Player numbers for /admin forcespec, handled in chat.admin.php
 *  "4401"-"4600": Player numbers for /admin listignores, handled in chat.admin.php
 *  "4601"-"4800": Player numbers for /admin listbans, handled in chat.admin.php
 *  "4801"-"5000": Player numbers for /admin listblacks, handled in chat.admin.php
 *  "5001"-"5200": Player numbers for /admin listguests, handled in chat.admin.php
 *  "5201"-"5700": TMX numbers for /tmxinfo, handled in plugin.rasp_jukebox.php
 *  "5701"-"6200": TMX numbers for /add, handled in plugin.rasp_jukebox.php
 *  "6201"-"6700": TMX numbers for /admin add, handled in plugin.rasp_jukebox.php
 *  "6701"-"7200": Authors for /xlist auth:, handled in plugin.rasp_jukebox.php
 *  "7201"-"7222": Donate panels, handled in plugin.panels.php
 *         "7223": /msglog button, handled in plugin.msglog.php
 *"-4001"-"-4200": Player numbers for /jfreu badword, handled in jfreu.chat.php
 *"-4201"-"-4400": Player numbers for /jfreu banfor 1H, handled in jfreu.chat.php
 *"-4401"-"-4600": Player numbers for /jfreu banfor 24H, handled in jfreu.chat.php
 *"-4601"-"-4800": Player numbers for /jfreu unban, handled in jfreu.chat.php
 *"-4801"-"-5000": Player numbers for /jfreu addvip, handled in jfreu.chat.php
 *"-5001"-"-5200": Player numbers for /jfreu removevip, handled in jfreu.chat.php
 *"-5201"-"-5400": Player numbers for /jfreu unspec, handled in jfreu.chat.php
 *"-5401"-"-5600": Player numbers for /jfreu listbans, handled in jfreu.chat.php
 *"-5601"-"-5800": Player numbers for /jfreu listvips, handled in jfreu.chat.php
 *"-5801"-"-6000": Team numbers for /jfreu listvipteams, handled in jfreu.chat.php
 *"-6001"-"-7900": Track numbers for /karma, handled in plugin.rasp_jukebox.php
 *"-7901"-"-8100": Player numbers for /admin unbanip, handled in chat.admin.php
 */

Aseco::registerEvent('onPlayerManialinkPageAnswer', 'event_manialink');
Aseco::registerEvent('onNewChallenge', 'scorepanel_off');
Aseco::registerEvent('onNewChallenge', 'statspanels_off');
Aseco::registerEvent('onEndRace', 'scorepanel_on');
Aseco::registerEvent('onEndRace', 'allwindows_off');
Aseco::registerEvent('onBeginRound', 'roundspanel_off');
Aseco::registerEvent('onPlayerFinish', 'roundspanel_on');

// stores/inits <custom_ui> block fields & records panel
global $ml_custom_ui, $ml_records;
$ml_custom_ui = array('global' => true,
                      'notice' => true,
                      'challenge_info' => true,
                      'net_infos' => true,
                      'chat' => true,
                      'checkpoint_list' => true,
                      'round_scores' => true,
                      'scoretable' => true
                     );
$ml_records = array('local' => '   --.--', 'dedi' => '   --.--', 'tmx' => '   --.--');

/**
 * Displays a single ManiaLink window to a player
 *
 * $login : player login to send window to
 * $header: string
 * $icon  : array( $style, $substyle {, $sizechg} )
 * $data  : array( $line1=array($col1, $col2, ...), $line2=array(...) )
 * $widths: array( $overal, $col1, $col2, ...)
 * $button: string
 *
 * A $line with one $col will occupy the full window width,
 * otherwise all $line's must have the same number of columns,
 * as should $widths (+1 for $overall).
 * line height=".046" is required minimum to prevent alignment glitches
 * due to large characters in some cells.
 * If $colX is an array, it contains the string and the button's action id.
 */
function display_manialink($login, $header, $icon, $data, $widths, $button) {
	global $aseco;

	$player = $aseco->server->players->getPlayer($login);
	$style = $player->style;

	// check for old TMN-style window
	if (empty($style)) {

		$tsp = 'B';  // 'F' = solid, '0' = invisible
		$txt = '333' . $tsp;  // dark grey
		$bgd = 'FFF' . $tsp;  // white
		$spc = 'DDD' . $tsp;  // light grey

		// build manialink header
		$xml  = '<manialink id="1" posx="' . ($widths[0]/2) . '" posy="0.47">' .
		        '<background bgcolor="' . $bgd . '" bgborderx="0.01" bgbordery="0.01"/>' . LF .
		        '<format textsize="3" textcolor="' . $txt . '"/>' . LF;

		// add header
		$xml .= '<line><cell bgcolor="' . $spc . '" width="' . $widths[0] . '"><text> $o' . htmlspecialchars(validateUTF8String($header)) . '</text></cell></line>' . LF;

		// add spacer
		$xml .= '<format textsize="2" textcolor="' . $txt . '"/>' . LF;
		$xml .= '<line><cell bgcolor="' . $bgd . '" width="' . $widths[0] . '"><text>$</text></cell></line>' . LF;

		// add lines with optional columns
		foreach ($data as $line) {
			$xml .= '<line height=".046">';
			if (!empty($line)) {
				if (count($line) > 1) {
					for ($i = 0; $i < count($widths)-1; $i++) {
						if (is_array($line[$i])) {
							$xml .= '<cell bgcolor="' . $bgd . '" width="' . $widths[$i+1] . '"><text action="' . $line[$i][1] . '">  $o' . htmlspecialchars(validateUTF8String($line[$i][0])) . '</text></cell>';
						} else {
							$xml .= '<cell bgcolor="' . $bgd . '" width="' . $widths[$i+1] . '"><text>  $o' . htmlspecialchars(validateUTF8String($line[$i])) . '</text></cell>';
						}
					}
				} else {
					$xml .= '<cell bgcolor="' . $bgd . '" width="' . $widths[0] . '"><text>  $o' . htmlspecialchars(validateUTF8String($line[0])) . '</text></cell>';
				}
			} else {  // spacer
				$xml .= '<cell bgcolor="' . $bgd . '" width="' . $widths[0] . '"><text>$</text></cell>';
			}
			$xml .= '</line>' . LF;
		}

		// add spacer, button (action "0" = close) & footer
		$xml .= '<line><cell bgcolor="' . $bgd . '" width="' . $widths[0] . '"><text>$</text></cell></line>' . LF;
		$xml .= '<line height=".046"><cell bgcolor="' . $bgd . '" width="' . $widths[0] . '"><text halign="center" action="0">$o' . $button . '</text></cell></line></manialink>';

	} else {  // TMF-style window
		$hsize = $style['HEADER'][0]['TEXTSIZE'][0];
		$bsize = $style['BODY'][0]['TEXTSIZE'][0];
		$lines = count($data);

		// build manialink header & window
		$xml  = '<manialink id="1"><frame pos="' . ($widths[0]/2) . ' 0.47 0">' .
		        '<quad size="' . $widths[0] . ' ' . (0.11+$hsize+$lines*$bsize) .
		        '" style="' . $style['WINDOW'][0]['STYLE'][0] .
		        '" substyle="' . $style['WINDOW'][0]['SUBSTYLE'][0] . '"/>' . LF;

		// add header and optional icon
		$xml .= '<quad pos="-' . ($widths[0]/2) . ' -0.01 -0.1" size="' . ($widths[0]-0.02) . ' ' . $hsize .
		        '" halign="center" style="' . $style['HEADER'][0]['STYLE'][0] .
		        '" substyle="' . $style['HEADER'][0]['SUBSTYLE'][0] . '"/>' . LF;
		if (is_array($icon)) {
			$isize = $hsize;
			if (isset($icon[2]))
				$isize += $icon[2];
			$xml .= '<quad pos="-0.055 -0.045 -0.2" size="' . $isize . ' ' . $isize .
			        '" halign="center" valign="center" style="' . $icon[0] . '" substyle="' . $icon[1] . '"/>' . LF;
			$xml .= '<label pos="-0.10 -0.025 -0.2" size="' . ($widths[0]-0.12) . ' ' . $hsize .
			        '" halign="left" style="' . $style['HEADER'][0]['TEXTSTYLE'][0] .
			        '" text="' . htmlspecialchars(validateUTF8String($header)) . '"/>' . LF;
		} else {
			$xml .= '<label pos="-0.03 -0.025 -0.2" size="' . ($widths[0]-0.05) . ' ' . $hsize .
			        '" halign="left" style="' . $style['HEADER'][0]['TEXTSTYLE'][0] .
			        '" text="' . htmlspecialchars(validateUTF8String($header)) . '"/>' . LF;
		}
		// add body
		$xml .= '<quad pos="-' . ($widths[0]/2) . ' -' . (0.02+$hsize) .
		        ' -0.1" size="' . ($widths[0]-0.02) . ' ' . (0.02+$lines*$bsize) .
		        '" halign="center" style="' . $style['BODY'][0]['STYLE'][0] .
		        '" substyle="' . $style['BODY'][0]['SUBSTYLE'][0] . '"/>' . LF;

		// add lines with optional columns
		$xml .= '<format style="' . $style['BODY'][0]['TEXTSTYLE'][0] . '"/>' . LF;
		$cnt = 0;
		foreach ($data as $line) {
			$cnt++;
			if (!empty($line)) {
				if (count($line) > 1) {
					for ($i = 0; $i < count($widths)-1; $i++) {
						if (is_array($line[$i])) {
							$xml .= '<quad pos="-' . (0.015+array_sum(array_slice($widths,1,$i))) .
							        ' -' . ($hsize-0.013+$cnt*$bsize) .
							        ' -0.15" size="' . ($widths[$i+1]-0.03) . ' ' . ($bsize+0.005) .
							        '" halign="left" style="' . $style['BUTTON'][0]['STYLE'][0] .
							        '" substyle="' . $style['BUTTON'][0]['SUBSTYLE'][0] .
							        '" action="' . $line[$i][1] . '"/>' . LF;
							$xml .= '<label pos="-' . (0.025+array_sum(array_slice($widths,1,$i))) .
							        ' -' . ($hsize-0.008+$cnt*$bsize) .
							        ' -0.2" size="' . ($widths[$i+1]-0.05) . ' ' . (0.02+$bsize) .
							        '" halign="left" style="' . $style['BODY'][0]['TEXTSTYLE'][0] .
							        '" text="' . htmlspecialchars(validateUTF8String($line[$i][0])) . '"/>' . LF;
						} else {
							$xml .= '<label pos="-' . (0.025+array_sum(array_slice($widths,1,$i))) .
							        ' -' . ($hsize-0.008+$cnt*$bsize) .
							        ' -0.2" size="' . ($widths[$i+1]-0.05) . ' ' . (0.02+$bsize) .
							        '" halign="left" style="' . $style['BODY'][0]['TEXTSTYLE'][0] .
							        '" text="' . htmlspecialchars(validateUTF8String($line[$i])) . '"/>' . LF;
						}
					}
				} else {
					$xml .= '<label pos="-0.025 -' . ($hsize-0.008+$cnt*$bsize) .
					        ' -0.2" size="' . ($widths[0]-0.04) . ' ' . (0.02+$bsize) .
					        '" halign="left" style="' . $style['BODY'][0]['TEXTSTYLE'][0] .
					        '" text="' . htmlspecialchars(validateUTF8String($line[0])) . '"/>' . LF;
				}
			}
		}

		// add button (action "0" = close) & footer
		$xml .= '<quad pos="-' . ($widths[0]/2) . ' -' . (0.04+$hsize+$lines*$bsize) .
		        ' -0.2" size="0.06 0.06" halign="center" style="Icons64x64_1" substyle="Close" action="0"/>' . LF;
		$xml .= '</frame></manialink>';
		$xml = str_replace('{#black}', $style['WINDOW'][0]['BLACKCOLOR'][0], $xml);
	}

	//$aseco->console_text($xml);
	$aseco->client->addCall('SendDisplayManialinkPageToLogin', array($login, $aseco->formatColors($xml), 0, true));
}  // display_manialink


/**
 * Displays custom TMX track ManiaLink window to a player
 *
 * $login : player login to send window to
 * $header: string
 * $icon  : array( $style, $substyle {, $sizechg} )
 * $links : array( $image, $square, $page, $download )
 * $data  : array( $line1=array($col1, $col2, ...), $line2=array(...) )
 * $widths: array( $overal, $col1, $col2, ...)
 * $button: string
 *
 * A $line with one $col will occupy the full window width,
 * otherwise all $line's must have the same number of columns,
 * as should $widths (+1 for $overall).
 * line height=".046" is required minimum to prevent alignment glitches
 * due to large characters in some cells.
 */
function display_manialink_track($login, $header, $icon, $links, $data, $widths, $button) {
	global $aseco;

	$player = $aseco->server->players->getPlayer($login);
	$style = $player->style;
	$square = $links[1];

	// check for old TMN-style window
	if (empty($style)) {

		$tsp = 'B';  // 'F' = solid, '0' = invisible
		$txt = '333' . $tsp;  // dark grey
		$bgd = 'FFF' . $tsp;  // white
		$spc = 'DDD' . $tsp;  // light grey

		// build manialink header
		$xml  = '<manialink id="1" posx="' . ($widths[0]/2) . '" posy="0.47">' .
		        '<background bgcolor="' . $bgd . '" bgborderx="0.01" bgbordery="0.01"/>' . LF .
		        '<format textsize="3" textcolor="' . $txt . '"/>' . LF;

		// add header
		$xml .= '<line><cell bgcolor="' . $spc . '" width="' . $widths[0] . '"><text> $o' . htmlspecialchars(validateUTF8String($header)) . '</text></cell></line>' . LF;

		// add spacers & image
		$xml .= '<format textsize="2" textcolor="' . $txt . '"/>' . LF;
		$xml .= '<line><cell bgcolor="' . $bgd . '" width="' . $widths[0] . '"><text>$</text></cell></line>' . LF;
		$xml .= '<line><cell bgcolor="' . $bgd . '" width="' . $widths[0] . '"><icon width="0.4" height="' . ($square ? '0.4' : '0.3') . '" halign="center">' . htmlspecialchars($links[0]) . '</icon></cell></line>' . LF;
		$xml .= '<line><cell bgcolor="' . $bgd . '" width="' . $widths[0] . '"><text>$</text></cell></line>' . LF;

		// add lines with optional columns
		foreach ($data as $line) {
			$xml .= '<line height=".046">';
			if (!empty($line)) {
				for ($i = 0; $i < count($widths)-1; $i++) {
					$xml .= '<cell bgcolor="' . $bgd . '" width="' . $widths[$i+1] . '"><text>  $o' . htmlspecialchars(validateUTF8String($line[$i])) . '</text></cell>';
				}
			} else {  // spacer
				$xml .= '<cell bgcolor="' . $bgd . '" width="' . $widths[0] . '"><text>$</text></cell>';
			}
			$xml .= '</line>' . LF;
		}

		// add spacer & links
		$xml .= '<line><cell bgcolor="' . $bgd . '" width="' . $widths[0] . '"><text>$</text></cell></line>' . LF;
		$xml .= '<format textsize="3" textcolor="' . $txt . '"/>' . LF;
		$xml .= '<line><cell bgcolor="' . $bgd . '" width="' . ($widths[0]/2) . '"><text halign="center">$o' . htmlspecialchars($links[2]) . '</text></cell>' .
		              '<cell bgcolor="' . $bgd . '" width="' . ($widths[0]/2) . '"><text halign="center">$o' . htmlspecialchars($links[3]) . '</text></cell></line>' . LF;

		// add spacer, button (action "0" = close) & footer
		$xml .= '<format textsize="2" textcolor="' . $txt . '"/>' . LF;
		$xml .= '<line><cell bgcolor="' . $bgd . '" width="' . $widths[0] . '"><text>$</text></cell></line>' . LF;
		$xml .= '<line height=".046"><cell bgcolor="' . $bgd . '" width="' . $widths[0] . '"><text halign="center" action="0">$o' . $button . '</text></cell></line></manialink>';

	} else {  // TMF-style window
		$hsize = $style['HEADER'][0]['TEXTSIZE'][0];
		$bsize = $style['BODY'][0]['TEXTSIZE'][0];
		$lines = count($data);

		// build manialink header & window
		$xml  = '<manialink id="1"><frame pos="' . ($widths[0]/2) . ' 0.47 0">' .
		        '<quad size="' . $widths[0] . ' ' . (0.42+($square?0.1:0)+2*$hsize+$lines*$bsize) .
		        '" style="' . $style['WINDOW'][0]['STYLE'][0] .
		        '" substyle="' . $style['WINDOW'][0]['SUBSTYLE'][0] . '"/>' . LF;

		// add header
		$xml .= '<quad pos="-' . ($widths[0]/2) . ' -0.01 -0.1" size="' . ($widths[0]-0.02) . ' ' . $hsize .
		        '" halign="center" style="' . $style['HEADER'][0]['STYLE'][0] .
		        '" substyle="' . $style['HEADER'][0]['SUBSTYLE'][0] . '"/>' . LF;
		if (is_array($icon)) {
			$isize = $hsize;
			if (isset($icon[2]))
				$isize += $icon[2];
			$xml .= '<quad pos="-0.055 -0.045 -0.2" size="' . $isize . ' ' . $isize .
			        '" halign="center" valign="center" style="' . $icon[0] . '" substyle="' . $icon[1] . '"/>' . LF;
			$xml .= '<label pos="-0.10 -0.025 -0.2" size="' . ($widths[0]-0.12) . ' ' . $hsize .
			        '" halign="left" style="' . $style['HEADER'][0]['TEXTSTYLE'][0] .
			        '" text="' . htmlspecialchars(validateUTF8String($header)) . '"/>' . LF;
		} else {
			$xml .= '<label pos="-0.03 -0.025 -0.2" size="' . ($widths[0]-0.05) . ' ' . $hsize .
			        '" halign="left" style="' . $style['HEADER'][0]['TEXTSTYLE'][0] .
			        '" text="' . htmlspecialchars(validateUTF8String($header)) . '"/>' . LF;
		}

		// add image
		$xml .= '<quad pos="-' . ($widths[0]/2) . ' -' . (0.02+$hsize) .
		        ' -0.2" size="0.4 ' . ($square ? '0.4' : '0.3') . '" halign="center" image="' . htmlspecialchars($links[0]) . '"/>' . LF;
		// add body
		$xml .= '<quad pos="-' . ($widths[0]/2) . ' -' . (0.33+($square?0.1:0)+$hsize) .
		        ' -0.1" size="' . ($widths[0]-0.02) . ' ' . (0.02+$hsize+$lines*$bsize) .
		        '" halign="center" style="' . $style['BODY'][0]['STYLE'][0] .
		        '" substyle="' . $style['BODY'][0]['SUBSTYLE'][0] . '"/>' . LF;

		// add lines with optional columns
		$xml .= '<format style="' . $style['BODY'][0]['TEXTSTYLE'][0] . '"/>' . LF;
		$cnt = 0;
		foreach ($data as $line) {
			$cnt++;
			if (!empty($line)) {
				for ($i = 0; $i < count($widths)-1; $i++) {
					$xml .= '<label pos="-' . (0.025+array_sum(array_slice($widths,1,$i))) .
					        ' -' . (0.305+($square?0.1:0)+$hsize+$cnt*$bsize) .
					        ' -0.2" size="' . $widths[$i+1] . ' ' . (0.02+$bsize) .
					        '" halign="left" style="' . $style['BODY'][0]['TEXTSTYLE'][0] .
					        '" text="' . htmlspecialchars(validateUTF8String($line[$i])) . '"/>' . LF;
				}
			}
		}

		// add links
		$xml .= '<format style="' . $style['HEADER'][0]['TEXTSTYLE'][0] . '"/>' . LF;
		$xml .= '<label pos="-' . ($widths[0]*0.25) . ' -' . (0.36+($square?0.1:0)+$hsize+$lines*$bsize) .
		        ' -0.2" size="' . ($widths[0]/2) . ' ' . $hsize .
		        '" halign="center" style="' . $style['HEADER'][0]['TEXTSTYLE'][0] .
		        '" text="' . htmlspecialchars($links[2]) . '"/>' . LF;
		$xml .= '<label pos="-' . ($widths[0]*0.75) . ' -' . (0.36+($square?0.1:0)+$hsize+$lines*$bsize) .
		        ' -0.2" size="' . ($widths[0]/2) . ' ' . $hsize .
		        '" halign="center" style="' . $style['HEADER'][0]['TEXTSTYLE'][0] .
		        '" text="' . htmlspecialchars($links[3]) . '"/>' . LF;

		// add button (action "0" = close) & footer
		$xml .= '<quad pos="-' . ($widths[0]/2) . ' -' . (0.35+($square?0.1:0)+2*$hsize+$lines*$bsize) .
		        ' -0.2" size="0.06 0.06" halign="center" style="Icons64x64_1" substyle="Close" action="0"/>' . LF;
		$xml .= '</frame></manialink>';
		$xml = str_replace('{#black}', $style['WINDOW'][0]['BLACKCOLOR'][0], $xml);
	}

	//$aseco->console_text($xml);
	$aseco->client->addCall('SendDisplayManialinkPageToLogin', array($login, $aseco->formatColors($xml), 0, true));
}  // display_manialink_track


/**
 * Displays a multipage ManiaLink window to a player
 *
 * $player: player object to send windows to
 *  ->msgs: array( array( $ptr, $header, $widths, $icon ),
 *   page1:        array( $line1=array($col1, $col2, ...), $line2=array(...) ),
 *       2:        array( $line1=array($col1, $col2, ...), $line2=array(...) ),
 *                 ... )
 * $header: string
 * $widths: array( $overal, $col1, $col2, ...)
 * $icon  : array( $style, $substyle {, $sizechg} )
 *
 * A $line with one $col will occupy the full window width,
 * otherwise all $line's must have the same number of columns,
 * as should $widths (+1 for $overall).
 * line height=".046" is required minimum to prevent alignment glitches
 * due to large characters in some cells.
 * If $colX is an array, it contains the string and the button's action id.
 */
function display_manialink_multi($player) {
	global $aseco;

	// fake current page event
	event_manialink($aseco, array(0, $player->login, 1));
}  // display_manialink_multi

// called @ onPlayerManialinkPageAnswer
// Handles all ManiaLink main system responses,
// as well as multi-page ManiaLink windows
// [0]=PlayerUid, [1]=Login, [2]=Answer
function event_manialink($aseco, $answer) {
	global $donation_values;

	// leave actions outside -6 - 36 to other handlers
	if ($answer[2] < -6 || $answer[2] > 36)
		return;

	// get player
	$login = $answer[1];
	$player = $aseco->server->players->getPlayer($login);

	// check player answer
	switch ($answer[2]) {
	case  0:
		// close main pop-up window
		mainwindow_off($aseco, $login);
		return;

	// /stats fields
	case -5:
		// log clicked command
		$aseco->console('player {1} clicked command "/active "', $player->login);
		// /stats field Time Played
		$command = array();
		$command['author'] = $player;
		chat_active($aseco, $command);
		return;
	case -6:
		// log clicked command
		$aseco->console('player {1} clicked command "/top100 "', $player->login);
		// /stats field Server Rank
		$command = array();
		$command['author'] = $player;
		chat_top100($aseco, $command);
		return;
	case  5:
		// log clicked command
		$aseco->console('player {1} clicked command "/toprecs "', $player->login);
		// /stats field Records
		$command = array();
		$command['author'] = $player;
		chat_toprecs($aseco, $command);
		return;
	case  6:
		// log clicked command
		$aseco->console('player {1} clicked command "/topwins "', $player->login);
		// /stats field Races Won
		$command = array();
		$command['author'] = $player;
		chat_topwins($aseco, $command);
		return;

	// Records panel fields
	case  7:
		// log clicked command
		$aseco->console('player {1} clicked command "/topsums "', $player->login);
		// records panel PB field
		$command = array();
		$command['author'] = $player;
		chat_topsums($aseco, $command);
		return;
	case  8:
		// log clicked command
		$aseco->console('player {1} clicked command "/recs "', $player->login);
		// records panel Local field
		$command = array();
		$command['author'] = $player;
		$command['params'] = '';
		chat_recs($aseco, $command);
		return;
	case  9:
		// log clicked command
		$aseco->console('player {1} clicked command "/dedirecs "', $player->login);
		// records panel Dedi field
		$command = array();
		$command['author'] = $player;
		$command['params'] = '';
		if (function_exists('chat_dedirecs')) chat_dedirecs($aseco, $command);
		return;
	case 10:
		// log clicked command
		$aseco->console('player {1} clicked command "/tmxrecs "', $player->login);
		// records panel TMX field
		$command = array();
		$command['author'] = $player;
		$command['params'] = '';
		if (function_exists('chat_tmxrecs')) chat_tmxrecs($aseco, $command);
		return;

	// /list Env fields
	case 11:
		// close main window because /list can take a while
		mainwindow_off($aseco, $login);
		// log clicked command
		$aseco->console('player {1} clicked command "/list env:Stadium"', $player->login);
		// /list Env field Stadium
		$command = array();
		$command['author'] = $player;
		$command['params'] = 'env:Stadium';
		chat_list($aseco, $command);
		return;
	case 12:
		// close main window because /list can take a while
		mainwindow_off($aseco, $login);
		// log clicked command
		$aseco->console('player {1} clicked command "/list env:Alpine"', $player->login);
		// /list Env field Alpine
		$command = array();
		$command['author'] = $player;
		$command['params'] = 'env:Alpine';
		chat_list($aseco, $command);
		return;
	case 13:
		// close main window because /list can take a while
		mainwindow_off($aseco, $login);
		// log clicked command
		$aseco->console('player {1} clicked command "/list env:Bay"', $player->login);
		// /list Env field Bay
		$command = array();
		$command['author'] = $player;
		$command['params'] = 'env:Bay';
		chat_list($aseco, $command);
		return;
	case 14:
		// close main window because /list can take a while
		mainwindow_off($aseco, $login);
		// log clicked command
		$aseco->console('player {1} clicked command "/list env:Coast"', $player->login);
		// /list Env field Coast
		$command = array();
		$command['author'] = $player;
		$command['params'] = 'env:Coast';
		chat_list($aseco, $command);
		return;
	case 15:
		// close main window because /list can take a while
		mainwindow_off($aseco, $login);
		// log clicked command
		$aseco->console('player {1} clicked command "/list env:Island"', $player->login);
		// /list Env field Island
		$command = array();
		$command['author'] = $player;
		$command['params'] = 'env:Island';
		chat_list($aseco, $command);
		return;
	case 16:
		// close main window because /list can take a while
		mainwindow_off($aseco, $login);
		// log clicked command
		$aseco->console('player {1} clicked command "/list env:Rally"', $player->login);
		// /list Env field Rally
		$command = array();
		$command['author'] = $player;
		$command['params'] = 'env:Rally';
		chat_list($aseco, $command);
		return;
	case 17:
		// close main window because /list can take a while
		mainwindow_off($aseco, $login);
		// log clicked command
		$aseco->console('player {1} clicked command "/list env:Speed"', $player->login);
		// /list Env field Speed
		$command = array();
		$command['author'] = $player;
		$command['params'] = 'env:Speed';
		chat_list($aseco, $command);
		return;

	// Vote panel buttons/keys
	case 18:
		// log clicked command
		$aseco->console('player {1} clicked command "/y "', $player->login);
		// /y on chat-based vote
		$command = array();
		$command['author'] = $player;
		chat_y($aseco, $command);
		return;
	case 19:
		// log clicked command
		$aseco->console('player {1} clicked command "/n " (ignored)', $player->login);
		// /n on chat-based vote (ignored)
		return;

	case 20:
		// log clicked command
		$aseco->console('player {1} clicked command "/admin clearjukebox"', $player->login);
		// close main window
		mainwindow_off($aseco, $login);
		// /jukebox display Clear Jukebox button
		$command = array();
		$command['author'] = $player;
		$command['params'] = 'clearjukebox';
		chat_admin($aseco, $command);
		return;

	// Admin panel buttons
	case 21:
		// log clicked command
		$aseco->console('player {1} clicked command "/admin restartmap"', $player->login);
		// admin panel ClipRewind button
		$command = array();
		$command['author'] = $player;
		$command['params'] = 'restartmap';
		chat_admin($aseco, $command);
		return;
	case 22:
		// log clicked command
		$aseco->console('player {1} clicked command "/admin endround"', $player->login);
		// admin panel ClipPause button
		$command = array();
		$command['author'] = $player;
		$command['params'] = 'endround';
		chat_admin($aseco, $command);
		return;
	case 23:
		// log clicked command
		$aseco->console('player {1} clicked command "/admin nextmap"', $player->login);
		// admin panel ClipPlay button
		$command = array();
		$command['author'] = $player;
		$command['params'] = 'nextmap';
		chat_admin($aseco, $command);
		return;
	case 24:
		// log clicked command
		$aseco->console('player {1} clicked command "/admin replaymap"', $player->login);
		// admin panel Refresh button
		$command = array();
		$command['author'] = $player;
		$command['params'] = 'replaymap';
		chat_admin($aseco, $command);
		return;
	case 25:
		// log clicked command
		$aseco->console('player {1} clicked command "/admin pass"', $player->login);
		// admin panel ArrowGreen button
		$command = array();
		$command['author'] = $player;
		$command['params'] = 'pass';
		chat_admin($aseco, $command);
		return;
	case 26:
		// log clicked command
		$aseco->console('player {1} clicked command "/admin cancel"', $player->login);
		// admin panel ArrowRed button
		$command = array();
		$command['author'] = $player;
		$command['params'] = 'cancel';
		chat_admin($aseco, $command);
		return;
	case 27:
		// log clicked command
		$aseco->console('player {1} clicked command "/admin players live"', $player->login);
		// admin panel Buddies button
		$command = array();
		$command['author'] = $player;
		$command['params'] = 'players live';
		chat_admin($aseco, $command);
		return;

	// Payment dialog buttons
	case 28:
		// log clicked command
		$aseco->console('player {1} confirmed command "/admin pay"', $player->login);
		admin_pay($aseco, $player->login, true);
		return;
	case 29:
		// log clicked command
		$aseco->console('player {1} cancelled command "/admin pay"', $player->login);
		admin_pay($aseco, $player->login, false);
		return;

	// Donate panel buttons
	case 30:
		// log clicked command
		$aseco->console('player {1} clicked command "/donate ' . $donation_values[0] . '"', $player->login);
		// donate panel field 1
		$command = array();
		$command['author'] = $player;
		$command['params'] = $donation_values[0];
		chat_donate($aseco, $command);
		return;
	case 31:
		// log clicked command
		$aseco->console('player {1} clicked command "/donate ' . $donation_values[1] . '"', $player->login);
		// donate panel field 2
		$command = array();
		$command['author'] = $player;
		$command['params'] = $donation_values[1];
		chat_donate($aseco, $command);
		return;
	case 32:
		// log clicked command
		$aseco->console('player {1} clicked command "/donate ' . $donation_values[2] . '"', $player->login);
		// donate panel field 3
		$command = array();
		$command['author'] = $player;
		$command['params'] = $donation_values[2];
		chat_donate($aseco, $command);
		return;
	case 33:
		// log clicked command
		$aseco->console('player {1} clicked command "/donate ' . $donation_values[3] . '"', $player->login);
		// donate panel field 4
		$command = array();
		$command['author'] = $player;
		$command['params'] = $donation_values[3];
		chat_donate($aseco, $command);
		return;
	case 34:
		// log clicked command
		$aseco->console('player {1} clicked command "/donate ' . $donation_values[4] . '"', $player->login);
		// donate panel field 5
		$command = array();
		$command['author'] = $player;
		$command['params'] = $donation_values[4];
		chat_donate($aseco, $command);
		return;
	case 35:
		// log clicked command
		$aseco->console('player {1} clicked command "/donate ' . $donation_values[5] . '"', $player->login);
		// donate panel field 6
		$command = array();
		$command['author'] = $player;
		$command['params'] = $donation_values[5];
		chat_donate($aseco, $command);
		return;
	case 36:
		// log clicked command
		$aseco->console('player {1} clicked command "/donate ' . $donation_values[6] . '"', $player->login);
		// donate panel field 7
		$command = array();
		$command['author'] = $player;
		$command['params'] = $donation_values[6];
		chat_donate($aseco, $command);
		return;
	}

	// Handle multi-page ManiaLink windows in all styles
	// update page pointer
	$tot = count($player->msgs) - 1;
	switch ($answer[2]) {
	case -4:  $player->msgs[0][0] = 1; break;
	case -3:  $player->msgs[0][0] -= 5; break;
	case -2:  $player->msgs[0][0] -= 1; break;
	case  1:  break;  // stay on current page
	case  2:  $player->msgs[0][0] += 1; break;
	case  3:  $player->msgs[0][0] += 5; break;
	case  4:  $player->msgs[0][0] = $tot; break;
	}

	// stay within boundaries
	if ($player->msgs[0][0] < 1)
		$player->msgs[0][0] = 1;
	elseif ($player->msgs[0][0] > $tot)
		$player->msgs[0][0] = $tot;

	// get control variables
	$ptr = $player->msgs[0][0];
	$header = $player->msgs[0][1];
	$widths = $player->msgs[0][2];
	$icon = $player->msgs[0][3];
	$style = $player->style;

	// check for old TMN-style window
	if (empty($style)) {

		$tsp = 'B';  // 'F' = solid, '0' = invisible
		$txt = '333' . $tsp;  // dark grey
		$bgd = 'FFF' . $tsp;  // white
		$spc = 'DDD' . $tsp;  // light grey

		// build manialink header
		$xml  = '<manialink id="1" posx="' . ($widths[0]/2) . '" posy="0.47">' .
		        '<background bgcolor="' . $bgd . '" bgborderx="0.01" bgbordery="0.01"/>' . LF .
		        '<format textsize="3" textcolor="' . $txt . '"/>' . LF;

		// add header
		$xml .= '<line><cell bgcolor="' . $spc . '" width="' . ($widths[0]-0.12) . '"><text> $o' . htmlspecialchars(validateUTF8String($header)) . '</text></cell>' .
		        '<cell bgcolor="' . $spc . '" width="0.12"><text halign="right">$n(' . $ptr . '/' . $tot . ')</text></cell></line>' . LF;

		// add spacer
		$xml .= '<format textsize="2" textcolor="' . $txt . '"/>' . LF;
		$xml .= '<line><cell bgcolor="' . $bgd . '" width="' . $widths[0] . '"><text>$</text></cell></line>' . LF;

		// add lines with optional columns
		foreach ($player->msgs[$ptr] as $line) {
			$xml .= '<line height=".046">';
			if (!empty($line)) {
				if (count($line) > 1) {
					for ($i = 0; $i < count($widths)-1; $i++) {
						if (is_array($line[$i])) {
							$xml .= '<cell bgcolor="' . $bgd . '" width="' . $widths[$i+1] . '"><text action="' . $line[$i][1] . '">  $o' . htmlspecialchars(validateUTF8String($line[$i][0])) . '</text></cell>';
						} else {
							$xml .= '<cell bgcolor="' . $bgd . '" width="' . $widths[$i+1] . '"><text>  $o' . htmlspecialchars(validateUTF8String($line[$i])) . '</text></cell>';
						}
					}
				} else {
					$xml .= '<cell bgcolor="' . $bgd . '" width="' . $widths[0] . '"><text>  $o' . htmlspecialchars(validateUTF8String($line[0])) . '</text></cell>';
				}
			} else {  // spacer
				$xml .= '<cell bgcolor="' . $bgd . '" width="' . $widths[0] . '"><text>$</text></cell>';
			}
			$xml .= '</line>' . LF;
		}

		// add spacer
		$xml .= '<line><cell bgcolor="' . $bgd . '" width="' . $widths[0] . '"><text>$</text></cell></line>' . LF;

		// add button(s)
		$add5 = ($tot > 5);
		$butw = ($widths[0] - ($add5 ? 0.22 : 0)) / 3;
		$xml .= '<line height=".046">';
		// check for preceding page(s), then Prev(5) button(s)
		if ($ptr > 1) {
			if ($add5)
				$xml .= '<cell bgcolor="' . $bgd . '" width="0.11"><text halign="center" action="-3">$oPrev5</text></cell>';
			$xml .= '<cell bgcolor="' . $bgd . '" width="' . $butw . '"><text halign="center" action="-2">$oPrev</text></cell>';
		} else {
			if ($add5)
				$xml .= '<cell bgcolor="' . $bgd . '" width="0.11"><text>$</text></cell>';
			$xml .= '<cell bgcolor="' . $bgd . '" width="' . $butw . '"><text>$</text></cell>';
		}
		// always a Close button
			$xml .= '<cell bgcolor="' . $bgd . '" width="' . $butw . '"><text halign="center" action="0">$oClose</text></cell>';
		// check for succeeding page(s), then Next(5) button(s)
		if ($ptr < $tot) {
			$xml .= '<cell bgcolor="' . $bgd . '" width="' . $butw . '"><text halign="center" action="2">$oNext</text></cell>';
			if ($add5)
				$xml .= '<cell bgcolor="' . $bgd . '" width="0.11"><text halign="center" action="3">$oNext5</text></cell>';
		} else {
			$xml .= '<cell bgcolor="' . $bgd . '" width="' . $butw . '"><text>$</text></cell>';
			if ($add5)
				$xml .= '<cell bgcolor="' . $bgd . '" width="0.11"><text>$</text></cell>';
		}
		$xml .= '</line></manialink>';

	} else {  // TMF-style window
		$hsize = $style['HEADER'][0]['TEXTSIZE'][0];
		$bsize = $style['BODY'][0]['TEXTSIZE'][0];
		$lines = count($player->msgs[$ptr]);
		// fill up multipage windows
		if ($tot > 1)
			$lines = max($lines, count($player->msgs[1]));

		// build manialink header & window
		$xml  = '<manialink id="1"><frame pos="' . ($widths[0]/2) . ' 0.47 0">' .
		        '<quad size="' . $widths[0] . ' ' . (0.11+$hsize+$lines*$bsize) .
		        '" style="' . $style['WINDOW'][0]['STYLE'][0] .
		        '" substyle="' . $style['WINDOW'][0]['SUBSTYLE'][0] . '"/>' . LF;

		// add header
		$xml .= '<quad pos="-' . ($widths[0]/2) . ' -0.01 -0.1" size="' . ($widths[0]-0.02) . ' ' . $hsize .
		        '" halign="center" style="' . $style['HEADER'][0]['STYLE'][0] .
		        '" substyle="' . $style['HEADER'][0]['SUBSTYLE'][0] . '"/>' . LF;
		if (is_array($icon)) {
			$isize = $hsize;
			if (isset($icon[2]))
				$isize += $icon[2];
			$xml .= '<quad pos="-0.055 -0.045 -0.2" size="' . $isize . ' ' . $isize .
			        '" halign="center" valign="center" style="' . $icon[0] . '" substyle="' . $icon[1] . '"/>' . LF;
			$xml .= '<label pos="-0.10 -0.025 -0.2" size="' . ($widths[0]-0.25) . ' ' . $hsize .
			        '" halign="left" style="' . $style['HEADER'][0]['TEXTSTYLE'][0] .
			        '" text="' . htmlspecialchars(validateUTF8String($header)) . '"/>' . LF;
		} else {
			$xml .= '<label pos="-0.03 -0.025 -0.2" size="' . ($widths[0]-0.18) . ' ' . $hsize .
			        '" halign="left" style="' . $style['HEADER'][0]['TEXTSTYLE'][0] .
			        '" text="' . htmlspecialchars(validateUTF8String($header)) . '"/>' . LF;
		}
		$xml .= '<label pos="-' . ($widths[0]-0.02) . ' -0.025 -0.2" size="0.12 ' . $hsize .
		        '" halign="right" style="' . $style['HEADER'][0]['TEXTSTYLE'][0] .
		        '" text="$n(' . $ptr . '/' . $tot . ')"/>' . LF;
		// add body
		$xml .= '<quad pos="-' . ($widths[0]/2) . ' -' . (0.02+$hsize) .
		        ' -0.1" size="' . ($widths[0]-0.02) . ' ' . (0.02+$lines*$bsize) .
		        '" halign="center" style="' . $style['BODY'][0]['STYLE'][0] .
		        '" substyle="' . $style['BODY'][0]['SUBSTYLE'][0] . '"/>' . LF;

		// add lines with optional columns
		$xml .= '<format style="' . $style['BODY'][0]['TEXTSTYLE'][0] . '"/>' . LF;
		$cnt = 0;
		foreach ($player->msgs[$ptr] as $line) {
			$cnt++;
			if (!empty($line)) {
				if (count($line) > 1) {
					for ($i = 0; $i < count($widths)-1; $i++) {
						if (isset($line[$i])) {
							// check for action button
							if (is_array($line[$i])) {
								$xml .= '<quad pos="-' . (0.015+array_sum(array_slice($widths,1,$i))) .
								        ' -' . ($hsize-0.013+$cnt*$bsize) .
								        ' -0.15" size="' . ($widths[$i+1]-0.03) . ' ' . ($bsize+0.005) .
								        '" halign="left" style="' . $style['BUTTON'][0]['STYLE'][0] .
								        '" substyle="' . $style['BUTTON'][0]['SUBSTYLE'][0] .
								        '" action="' . $line[$i][1] . '"/>' . LF;
								$xml .= '<label pos="-' . (0.025+array_sum(array_slice($widths,1,$i))) .
								        ' -' . ($hsize-0.008+$cnt*$bsize) .
								        ' -0.2" size="' . ($widths[$i+1]-0.05) . ' ' . (0.02+$bsize) .
								        '" halign="left" style="' . $style['BODY'][0]['TEXTSTYLE'][0] .
								        '" text="' . htmlspecialchars(validateUTF8String($line[$i][0])) . '"/>' . LF;
							} else {
								$xml .= '<label pos="-' . (0.025+array_sum(array_slice($widths,1,$i))) .
								        ' -' . ($hsize-0.008+$cnt*$bsize) .
								        ' -0.2" size="' . ($widths[$i+1]-0.05) . ' ' . (0.02+$bsize) .
								        '" halign="left" style="' . $style['BODY'][0]['TEXTSTYLE'][0] .
								        '" text="' . htmlspecialchars(validateUTF8String($line[$i])) . '"/>' . LF;
							}
						}
					}
				} else {
					$xml .= '<label pos="-0.025 -' . ($hsize-0.008+$cnt*$bsize) .
					        ' -0.2" size="' . ($widths[0]-0.04) . ' ' . (0.02+$bsize) .
					        '" halign="left" style="' . $style['BODY'][0]['TEXTSTYLE'][0] .
					        '" text="' . htmlspecialchars(validateUTF8String($line[0])) . '"/>' . LF;
				}
			}
		}

		// add button(s) & footer
		$add5 = ($tot > 5);
		// check for preceding page(s), then First & Prev(5) button(s)
		if ($ptr > 1) {
			$first = '"ArrowFirst" action="-4"';
			$prev5 = '"ArrowFastPrev" action="-3"';
			$prev1 = '"ArrowPrev" action="-2"';
		} else {  // first page so dummy buttons
			$first = '"StarGold"';
			$prev5 = '"StarGold"';
			$prev1 = '"StarGold"';
		}
		$xml .= '<quad pos="-0.04 -' . (0.045+$hsize+$lines*$bsize) .
		        ' -0.2" size="0.055 0.055" halign="center" style="Icons64x64_1" substyle=' . $first . '/>' . LF;
		if ($add5) {
			$xml .= '<quad pos="-0.095 -' . (0.045+$hsize+$lines*$bsize) .
			        ' -0.2" size="0.055 0.055" halign="center" style="Icons64x64_1" substyle=' . $prev5 . '/>' . LF;
		}
		$xml .= '<quad pos="-' . ($widths[0]*0.25) . ' -' . (0.045+$hsize+$lines*$bsize) .
		        ' -0.2" size="0.055 0.055" halign="center" style="Icons64x64_1" substyle=' . $prev1 . '/>' . LF;
		// always a Close button
		$xml .= '<quad pos="-' . ($widths[0]/2) . ' -' . (0.04+$hsize+$lines*$bsize) .
		        ' -0.2" size="0.06 0.06" halign="center" style="Icons64x64_1" substyle="Close" action="0"/>' . LF;
		// check for succeeding page(s), then Next(5) & Last button(s)
		if ($ptr < $tot) {
			$next1 = '"ArrowNext" action="2"';
			$next5 = '"ArrowFastNext" action="3"';
			$last  = '"ArrowLast" action="4"';
		} else {  // last page so dummy buttons
			$next1 = '"StarGold"';
			$next5 = '"StarGold"';
			$last  = '"StarGold"';
		}
		$xml .= '<quad pos="-' . ($widths[0]*0.75) . ' -' . (0.045+$hsize+$lines*$bsize) .
		        ' -0.2" size="0.055 0.055" halign="center" style="Icons64x64_1" substyle=' . $next1 . '/>' . LF;
		if ($add5) {
			$xml .= '<quad pos="-' . ($widths[0]-0.095) . ' -' . (0.045+$hsize+$lines*$bsize) .
			        ' -0.2" size="0.055 0.055" halign="center" style="Icons64x64_1" substyle=' . $next5 . '/>' . LF;
		}
		$xml .= '<quad pos="-' . ($widths[0]-0.04) . ' -' . (0.045+$hsize+$lines*$bsize) .
		        ' -0.2" size="0.055 0.055" halign="center" style="Icons64x64_1" substyle=' . $last . '/>' . LF;

		$xml .= '</frame></manialink>';
		$xml = str_replace('{#black}', $style['WINDOW'][0]['BLACKCOLOR'][0], $xml);
	}

	//$aseco->console_text($xml);
	$aseco->client->addCall('SendDisplayManialinkPageToLogin', array($player->login, $aseco->formatColors($xml), 0, false));
}  // event_manialink


/**
 * Displays a payment dialog
 *
 * $login : player login to send dialog to
 * $server: server name for payment
 * $label : payment label string
 */
function display_payment($aseco, $login, $server, $label) {

	// build manialink
	$xml = '<manialink id="1"><frame pos="0.5 0.15 0">' .
	       '<quad size="1.0 0.3" style="Bgs1" substyle="BgWindow3"/>' .
	       '<label pos="-0.04 -0.04 -0.2" textsize="2" text="$fffInitiating payment from server ' . $server . '$z $fff:"/>' .
	       '<label pos="-0.04 -0.08 -0.2" textsize="2" text="$fffLabel: ' . $label . '"/>' .
	       '<label pos="-0.04 -0.12 -0.2" textsize="2" text="$fffWould you like to pay?"/>' .
	       '<label pos="-0.27 -0.19 -0.2" halign="center" style="CardButtonMedium" text="Yes" action="28"/>' .
	       '<label pos="-0.73 -0.19 -0.2" halign="center" style="CardButtonMedium" text="No" action="29"/>' .
	       '</frame></manialink>';

	//$aseco->console_text($xml);
	// disable dialog once clicked
	$aseco->client->addCall('SendDisplayManialinkPageToLogin', array($login, $xml, 0, true));
}  // display_payment

/**
 * Closes main window
 *
 * $login: player login to close window for
 */
function mainwindow_off($aseco, $login) {

	// close main window
	$xml = '<manialink id="1"></manialink>';
	$aseco->client->addCall('SendDisplayManialinkPageToLogin', array($login, $xml, 0, false));
}  // mainwindow_off

// called @ onEndRace
function allwindows_off($aseco, $data) {

	if ($aseco->server->getGame() == 'TMF') {
		// disable all pop-up windows and records & donate panels
		$xml = '<manialinks><manialink id="1"></manialink><manialink id="4"></manialink><manialink id="6"></manialink></manialinks>';
		$aseco->client->query('SendDisplayManialinkPage', $xml, 0, false);
	}
}  // allwindows_off


/**
 * Displays a CheckPoints panel
 *
 * $login: player login(s) to send panel to
 * $cp   : CP number
 * $diff : color+sign+diff
 */
function display_cpspanel($aseco, $login, $cp, $diff) {

	// build manialink
	$xml = '<manialink id="2"><frame posn="-7.9 -38.1 0">' .
	       '<quad sizen="16 4" style="Bgs1InRace" substyle="NavButton"/>' .
	       '<label posn="6.4 -0.7 1" sizen="6.0 4" textsize="3" textcolor="000" halign="right" text="CP' . $cp . ':"/>' .
	       '<label posn="6.8 -0.7 1" sizen="8.8 4" textsize="3" halign="left" text="$o' . $diff . '"/>' .
	       '</frame></manialink>';

	//$aseco->console_text($xml);
	$aseco->client->addCall('SendDisplayManialinkPageToLogin', array($login, $xml, 0, false));
}  // display_cpspanel

/**
 * Disables a CheckPoints panel
 *
 * $login: player login(s) to disable panel for
 */
function cpspanel_off($aseco, $login) {

	$xml = '<manialink id="2"></manialink>';
	$aseco->client->addCall('SendDisplayManialinkPageToLogin', array($login, $xml, 0, false));
}  // cpspanel_off

/**
 * Disables all CheckPoints panels
 */
function allcpspanels_off($aseco) {

	$xml = '<manialink id="2"></manialink>';
	$aseco->client->query('SendDisplayManialinkPage', $xml, 0, false);
}  // allcpspanels_off


/**
 * Displays an Admin panel
 *
 * $player: player to send panel to
 */
function display_admpanel($aseco, $player) {

	// build manialink
	$xml = $player->panels['admin'];

	//$aseco->console_text($xml);
	$aseco->client->addCall('SendDisplayManialinkPageToLogin', array($player->login, $xml, 0, false));
}  // display_admpanel

/**
 * Disables an Admin panel
 *
 * $login: player login to disable panel for
 */
function admpanel_off($aseco, $login) {

	$xml = '<manialink id="3"></manialink>';
	$aseco->client->addCall('SendDisplayManialinkPageToLogin', array($login, $xml, 0, false));
}  // admpanel_off


/**
 * Displays a Donate panel
 *
 * $player : player to send panel to
 * $coppers: donation values
 */
function display_donpanel($aseco, $player, $coppers) {

	// build manialink
	$xml = $player->panels['donate'];
	for ($i = 1; $i <= 7; $i++)
		$xml = str_replace('%COP' . $i . '%', $coppers[$i-1], $xml);

	//$aseco->console_text($xml);
	$aseco->client->addCall('SendDisplayManialinkPageToLogin', array($player->login, $xml, 0, false));
}  // display_donpanel

/**
 * Disables a Donate panel
 *
 * $login: player login to disable panel for
 */
function donpanel_off($aseco, $login) {

	$xml = '<manialink id="6"></manialink>';
	$aseco->client->addCall('SendDisplayManialinkPageToLogin', array($login, $xml, 0, false));
}  // donpanel_off


/**
 * Displays a Records panel
 *
 * $player: player to send panel to
 * $pb    : personal best
 */
function display_recpanel($aseco, $player, $pb) {
	global $ml_records;

	// build manialink
	$xml = str_replace(array('%PB%', '%TMX%', '%LCL%', '%DED%'),
	                   array($pb, $ml_records['tmx'], $ml_records['local'], $ml_records['dedi']),
	                   $player->panels['records']);

	//$aseco->console_text($xml);
	$aseco->client->addCall('SendDisplayManialinkPageToLogin', array($player->login, $xml, 0, false));
}  // display_recpanel

/**
 * Disables a Records panel
 *
 * $login: player login to disable panel for
 */
function recpanel_off($aseco, $login) {

	$xml = '<manialink id="4"></manialink>';
	$aseco->client->addCall('SendDisplayManialinkPageToLogin', array($login, $xml, 0, false));
}  // recpanel_off

function setRecordsPanel($field, $value) {
	global $ml_records;

	$ml_records[$field] = $value;
}  // setRecordsPanel


/**
 * Displays a Vote panel
 *
 * $player : player to send panel to
 * $yesstr : string for the Yes button
 * $nostr  : string for the No button
 * $timeout: timeout for temporary panel (used only by /votepanel list)
 */
function display_votepanel($aseco, $player, $yesstr, $nostr, $timeout) {

	// build manialink
	$xml = str_replace(array('%YES%', '%NO%'),
	                   array($yesstr, $nostr), $player->panels['vote']);

	//$aseco->console_text($xml);
	// disable panel once clicked
	$aseco->client->addCall('SendDisplayManialinkPageToLogin', array($player->login, $xml, $timeout, true));
}  // display_votepanel

/**
 * Disables a Vote panel
 *
 * $login: player login to disable panel for
 */
function votepanel_off($aseco, $login) {

	$xml = '<manialink id="5"></manialink>';
	$aseco->client->addCall('SendDisplayManialinkPageToLogin', array($login, $xml, 0, false));
}  // votepanel_off

/**
 * Disables all Vote panels
 */
function allvotepanels_off($aseco) {

	$xml = '<manialink id="5"></manialink>';
	$aseco->client->addCall('SendDisplayManialinkPage', array($xml, 0, false));
}  // allvotepanels_off


/**
 * Displays the Message window
 *
 * $msgs   : lines to be displayed
 * $timeout: timeout for window in msec
 */
function display_msgwindow($aseco, $msgs, $timeout) {

	$cnt = count($msgs);
	$xml = '<manialink id="7"><frame posn="-49 43.5 0">' . LF .
	       '<quad sizen="93 ' . (1.5 + $cnt*2.5) . '" style="Bgs1" substyle="NavButton"/>' . LF;
	$pos = -1;
	foreach ($msgs as $msg) {
		$xml .= '<label posn="1 ' . $pos . ' 1" sizen="91 1" style="TextRaceChat" text="' . htmlspecialchars(validateUTF8String($msg)) . '"/>' . LF .
		$pos -= 2.5;
	}
	$xml .= '</frame></manialink>';

	//$aseco->console_text($xml);
	$aseco->client->addCall('SendDisplayManialinkPage', array($xml, $timeout, false));
}  // display_msgwindow

/**
 * Displays the /msglog button
 *
 * $login: player login to display button for
 */
function display_msglogbutton($aseco, $login) {

	$xml = '<manialink id="8"><frame posn="-63.9 -33.5 0">' . LF .
	       '<quad sizen="1.65 1.65" style="Icons64x64_1" substyle="ArrowUp" action="7223"/>' . LF .
	       '</frame></manialink>';

	//$aseco->console_text($xml);
	$aseco->client->addCall('SendDisplayManialinkPageToLogin', array($login, $xml, 0, false));
}  // display_msglogbutton


/**
 * Displays a Scoreboard Stats panel
 *
 * $player : player to send panel to
 * $rank   : server rank
 * $avg    : record average
 * $recs   : records total
 * $wins   : wins total
 * $play   : session play time
 * $dons   : donations total
 */
function display_statspanel($aseco, $player, $rank, $avg, $recs, $wins, $play, $dons) {

	// build manialink
	$xml = str_replace(array('%RANK%', '%AVG%', '%RECS%', '%WINS%', '%PLAY%', '%DONS%'),
	                   array($rank, $avg, $recs, $wins, $play, $dons), $aseco->statspanel);

	//$aseco->console_text($xml);
	$aseco->client->addCall('SendDisplayManialinkPageToLogin', array($player->login, $xml, 0, false));
}  // display_votepanel

/**
 * Disables all Scoreboard Stats panels
 *
 * called @ onNewChallenge
 */
function statspanels_off($aseco, $data) {

	$xml = '<manialink id="9"></manialink>';
	$aseco->client->addCall('SendDisplayManialinkPage', array($xml, 0, false));
}  // statspanels_off


// called @ onNewChallenge
// Disables Automatic Scorepanel at start of track if $auto_scorepanel = off
function scorepanel_off($aseco, $data) {
	global $auto_scorepanel;

	if ($aseco->server->getGame() == 'TMF' && !$auto_scorepanel) {
		setCustomUIField('scoretable', false);
		// dummy ManiaLink to preserve custom_ui
		$xml = '<manialinks><manialink id="0"><line></line></manialink>' .
		       getCustomUIBlock() . '</manialinks>';
		$aseco->client->addCall('SendDisplayManialinkPage', array($xml, 0, false));
	}
}  // scorepanel_off

// called @ onEndRace
// Enables Automatic Scorepanel at end of track
function scorepanel_on($aseco, $data) {

	if ($aseco->server->getGame() == 'TMF') {
		setCustomUIField('scoretable', true);
		// dummy ManiaLink to preserve custom_ui
		$xml = '<manialinks><manialink id="0"><line></line></manialink>' .
		       getCustomUIBlock() . '</manialinks>';
		$aseco->client->addCall('SendDisplayManialinkPage', array($xml, 0, false));
	}
}  // scorepanel_on

// called @ onBeginRound
// Disables Rounds Finishpanel at start of round if $rounds_finishpanel = off
function roundspanel_off($aseco) {
	global $auto_scorepanel, $rounds_finishpanel;

	// check for Rounds/Team/Cup modes
	if ($aseco->server->gameinfo->mode == Gameinfo::RNDS ||
	    $aseco->server->gameinfo->mode == Gameinfo::TEAM ||
	    $aseco->server->gameinfo->mode == Gameinfo::CUP) {
		// check whether to disable panel
		if ($aseco->server->getGame() == 'TMF' && !$rounds_finishpanel) {
			setCustomUIField('round_scores', false);
			// dummy ManiaLink to preserve custom_ui
			$xml = '<manialinks><manialink id="0"><line></line></manialink>' .
			       getCustomUIBlock() . '</manialinks>';
			$aseco->client->addCall('SendDisplayManialinkPage', array($xml, 0, false));
		}
	}
}  // roundspanel_off

// called @ onPlayerFinish
// Enables Rounds Finishpanel at player finish
function roundspanel_on($aseco, $finish_item) {
	global $auto_scorepanel, $rounds_finishpanel;

	// check for Rounds/Team/Cup modes
	if ($aseco->server->gameinfo->mode == Gameinfo::RNDS ||
	    $aseco->server->gameinfo->mode == Gameinfo::TEAM ||
	    $aseco->server->gameinfo->mode == Gameinfo::CUP) {
		// check whether panel was disabled
		if ($aseco->server->getGame() == 'TMF' && !$rounds_finishpanel) {
			setCustomUIField('round_scores', true);
			// dummy ManiaLink to preserve custom_ui
			$xml = '<manialinks><manialink id="0"><line></line></manialink>' .
			       getCustomUIBlock() . '</manialinks>';
			$aseco->client->addCall('SendDisplayManialinkPageToLogin', array($finish_item->player->login, $xml, 0, false));
		}
	}
}  // roundspanel_on

function setCustomUIField($field, $value) {
	global $ml_custom_ui;

	$ml_custom_ui[$field] = $value;
}  // setCustomUIField

function getCustomUIBlock() {
	global $ml_custom_ui;

	return '<custom_ui>' .
	       '<notice visible="' . bool2text($ml_custom_ui['notice']) . '"/>' .
	       '<challenge_info visible="' . bool2text($ml_custom_ui['challenge_info']) . '"/>' .
	       '<net_infos visible="' . bool2text($ml_custom_ui['net_infos']) . '"/>' .
	       '<chat visible="' . bool2text($ml_custom_ui['chat']) . '"/>' .
	       '<checkpoint_list visible="' . bool2text($ml_custom_ui['checkpoint_list']) . '"/>' .
	       '<round_scores visible="' . bool2text($ml_custom_ui['round_scores']) . '"/>' .
	       '<scoretable visible="' . bool2text($ml_custom_ui['scoretable']) . '"/>' .
	       '<global visible="' . bool2text($ml_custom_ui['global']) . '"/>' .
	       '</custom_ui>';
}  // getCustomUIBlock
?>
