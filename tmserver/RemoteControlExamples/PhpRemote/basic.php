<?php
   header('Content-Type: text/html; charset=utf-8');

require("GbxRemote.inc.php");

// functions.

// Converts trackmania styles to html
function styledString($str) {
  preg_match_all('/./u', $str, $dummy);
  $_Str = $dummy[0];
  $_Style = NULL;
  $string = NULL;
  $i = 0;
  while (true) {
    $Char = ReadNextChar($_Str,$_Style);
    if ($Char === FALSE) {
      break;
    }
    $string .= "<span style='".$_Style."'>".$Char."</span>";
    $i++;
    if ($i>100) {
      break;
    }
  }
  return $string;
}

// Read a styled string
function ReadNextChar(&$_Str, &$_Style) {
  
  $Char = current($_Str);
  next($_Str);

  if ($Char == '\r') {	// skip \r
    return ReadNextChar($_Str,  $_Style);
  }
  if ($Char != '$')	{	// detect markup start sequence '$'
    return $Char;
  }

  $Char = current($_Str);
	next($_Str);
  if ($Char === FALSE) {
    return 0;
  }
	$MarkupCode = $Char;

  switch ($MarkupCode) {
	// color
	case '0': case '1': case '2': case '3': case '4': case '5':
	case '6': case '7': case '8': case '9': case 'a': case 'b':
	case 'c': case 'd': case 'e': case 'f': case 'A': case 'B':
	case 'C': case 'D': case 'E': case 'F':
		{
      $RGB = NULL;
			for ($Count = 0; $Count < 3; $Count ++) {
				if ($Count > 0) {
          $Char = current($_Str);
          next($_Str);
          if ($Char === FALSE) {
            return 0;
          }
				}
        $RGB .= $Char.$Char;
			}
			$_Style .= "color:#".$RGB.";";
		}
		break;

    // no color
    case 'G': case 'g':
      $_Style .= "color:;";
      break;

    // Shodowed / Embossed
    case 'S': case 's':	
      break;

    // Italic
    case 'I': case 'i':
      $_Style .= "font-style:italic;";
      break;
    
    // Wide
    case 'W': case 'w':
      $_Style .= "letter-spacing:1px;";
      break;

    // Narrow
    case 'N': case 'n':
      $_Style .= "letter-spacing:-1px;";
      break;

    // Medium
    case 'M': case 'm':
      $_Style .= "letter-spacing:0px;";
      break;

    // underlined
    case 'U': case 'u':
      $_Style .= "text-decoration:underline;";
      break;

    // reset all
    case 'Z': case 'z':
      $_Style = "";
      break;

    // escaped char.
    case '$': case '[':
      return $MarkupCode;

    default:
      // eat silently the character...
      break;
  };

  return ReadNextChar($_Str, $_Style);	// tail recursion.
}

function catchError($errno, $errstr, $errfile, $errline){
  echo("<p><font color='red'>$errstr (line:$errline)</font></p>");
}
set_error_handler('catchError');

function MwTimeToString($MwTime) 
{
	if ($MwTime == -1) {
		return "???";
	} else {
		$minutes = floor($MwTime/(1000*60));
		return $minutes.":".floor(($MwTime-$minutes*60*1000)/1000);
	}
}

function ParseArgument(&$ArgumentValue, $ArgumentName, $DefaultValue)
{
	if (array_key_exists($ArgumentName, $_POST)) {
		$ArgumentValue = $_POST[$ArgumentName];
	} else if (array_key_exists($ArgumentName, $_GET)) {
		$ArgumentValue = $_GET[$ArgumentName];
	} else {
		$ArgumentValue = $DefaultValue;
	}
}

// parse the arguments.
ParseArgument( $AuthLogin, 'authLogin', "SuperAdmin" );
ParseArgument( $AuthPassword, 'authPassword', "SuperAdmin" );
ParseArgument( $OldAuthLogin, 'oldAuthLogin', $AuthLogin );
ParseArgument( $OldAuthPassword, 'oldAuthPassword', $AuthPassword );
ParseArgument( $port, 'port', 5000 );
ParseArgument( $MSLogin, 'mslogin', "" );
ParseArgument( $MSPassword, 'mspassword', "" );

if (array_key_exists('action', $_POST)) {
	$Action = $_POST['action'];
/*} else if (array_key_exists('action', $_GET)) 
	$Action = $_GET['action'];*/
} else {
	$Action="";
}


echo "<center><h1> - Trackmania Forever dedicated server - </h1></center>";


// ----------------------------------------------------------------
// connect
// ----------------------------------------------------------------
$client = new IXR_Client_Gbx;
if (!$client->Init($port)) {
   trigger_error("[".$client->getErrorCode()."] ".$client->getErrorMessage());
}

if (!$client->query("Authenticate", $AuthLogin, $AuthPassword)) {
   trigger_error("[".$client->getErrorCode()."] ".$client->getErrorMessage());

	$AuthLogin = $OldAuthLogin;
	$AuthPassword = $OldAuthPassword;
}
else
{
	$OldAuthLogin = $AuthLogin;
	$OldAuthPassword = $AuthPassword;
}

// ----------------------------------------------------------------
// do the job.
// ----------------------------------------------------------------
$SimpleActions = array('RestartChallenge', 'NextChallenge', 'StopServer', 'QuitGame', 'CleanBanList');
if (in_array($Action, $SimpleActions)) {
	if (!$client->query($Action)) {
	   trigger_error("[".$client->getErrorCode()."] ".$client->getErrorMessage());
	}

} else if ($Action == 'SetServerOptions') {
	$IsP2PUpload = array_key_exists('IsP2PUpload', $_POST);
	$IsP2PDownload = array_key_exists('IsP2PDownload', $_POST);
	$AutoSaveReplays = array_key_exists('AutoSaveReplays', $_POST);
	$AutoSaveValidationReplays = array_key_exists('AutoSaveValidationReplays', $_POST);
	$AllowChallengeDownload = array_key_exists('AllowChallengeDownload', $_POST);
	$struct = array(	'Name' => $_POST['ServerName'],
						'Comment' => $_POST['ServerComment'],
						'Password' => $_POST['ServerPassword'],
						'PasswordForSpectator' => $_POST['SpectatorPassword'],
						'NextMaxPlayers' => $_POST['NextMaxPlayers']+0,
						'NextMaxSpectators' => $_POST['NextMaxSpectators']+0,
						'IsP2PUpload' => $IsP2PUpload,
						'IsP2PDownload' => $IsP2PDownload,
						'NextLadderMode' => $_POST['NextLadderMode']+0,
						'NextVehicleNetQuality' => $_POST['NextVehicleNetQuality']+0,
						'NextCallVoteTimeOut' => $_POST['NextCallVoteTimeOut']+0,
						'CallVoteRatio' => $_POST['CallVoteRatio']+0,
						'RefereePassword' => $_POST['RefereePassword'],
						'RefereeMode' => $_POST['RefereeMode']+0,
						'AllowChallengeDownload' => $AllowChallengeDownload,
						'AutoSaveValidationReplays' => $AutoSaveValidationReplays,
						'AutoSaveReplays' => $AutoSaveReplays);
	if (!$client->query('SetServerOptions', $struct)) {
	   trigger_error("[".$client->getErrorCode()."] ".$client->getErrorMessage());
	}

} else if ($Action == 'StartServerInternet' || $Action == 'StartServerLan') {
	$struct = array(	'Login' => $_POST['Login'],
						'Password' => $_POST['Password']);
	if (!$client->query($Action, $struct)) {
	   trigger_error("[".$client->getErrorCode()."] ".$client->getErrorMessage());
	}

} else if ($Action == 'RemoveChallenge' || $Action == 'AddChallenge' || $Action == 'ChooseNextChallenge') {
	if (!$client->query($Action, urldecode($_POST['ChallengeFileName']))) {
	   trigger_error("[".$client->getErrorCode()."] ".$client->getErrorMessage());
	}

} else if ($Action == 'Kick' || $Action == 'Ban' || $Action == 'UnBan' || $Action == 'AddGuest' || $Action == 'RemoveGuest' || $Action == 'BlackList' || $Action == 'UnBlackList') {
	if (!$client->query($Action, urldecode($_POST['PlayerLogin']))) {
	   trigger_error("[".$client->getErrorCode()."] ".$client->getErrorMessage());
	}

} else if ($Action == 'SaveMatchSettings' || $Action == 'LoadMatchSettings' || $Action == 'AppendPlaylistFromMatchSettings') {
	if (!$client->query($Action, urldecode($_POST['MatchSettingsFileName']))) {
	   trigger_error("[".$client->getErrorCode()."] ".$client->getErrorMessage());
	}
} else if ($Action == 'LoadGuestList' || $Action == 'SaveGuestList' ) {
	if (!$client->query($Action, urldecode($_POST['GuestListFileName']))) {
	   trigger_error("[".$client->getErrorCode()."] ".$client->getErrorMessage());
	}
} else if ($Action == 'LoadBlackList' || $Action == 'SaveBlackList' ) {
	if (!$client->query($Action, urldecode($_POST['BlackListFileName']))) {
	   trigger_error("[".$client->getErrorCode()."] ".$client->getErrorMessage());
	}
} else if ($Action == 'SetGameInfos') {
	$NextRoundsUseNewRules = array_key_exists('NextRoundsUseNewRules', $_POST);
	$NextTeamUseNewRules = array_key_exists('NextTeamUseNewRules', $_POST);
	$NextDisableRespawn = array_key_exists('NextDisableRespawn', $_POST);
	$struct = array(	'GameMode' => $_POST['NextGameMode']+0,
						'ChatTime' => $_POST['NextChatTime']+0,
						'FinishTimeout' => $_POST['NextFinishTimeout']+0,
						'RoundsPointsLimit' => $_POST['NextRoundsPointsLimit']+0,
						'RoundsForcedLaps' => $_POST['NextRoundsForcedLaps']+0,
						'RoundsUseNewRules' => $NextRoundsUseNewRules,
						'RoundsPointsLimitNewRules' => $_POST['NextRoundsPointsLimitNewRules']+0,
						'TimeAttackLimit' => $_POST['NextTimeAttackLimit']+0,
						'TimeAttackSynchStartPeriod' => $_POST['NextTimeAttackSynchStartPeriod']+0,
						'TeamPointsLimit' => $_POST['NextTeamPointsLimit']+0,
						'TeamMaxPoints' => $_POST['NextTeamMaxPoints']+0,
						'TeamUseNewRules' => $NextTeamUseNewRules,
						'TeamPointsLimitNewRules' => $_POST['NextTeamPointsLimitNewRules']+0,
						'CupPointsLimit' => $_POST['NextCupPointsLimit']+0,
						'CupRoundsPerChallenge' => $_POST['NextCupRoundsPerChallenge']+0,
						'CupNbWinners' => $_POST['NextCupNbWinners']+0,
						'CupWarmUpDuration' => $_POST['NextCupWarmUpDuration']+0,
						'DisableRespawn' => False,
						'ForceShowAllOpponents' => False,
						'LapsNbLaps' => $_POST['NextLapsNbLaps']+0,
						'LapsTimeLimit' => $_POST['NextLapsTimeLimit']+0,
						'AllWarmUpDuration' => 0);
	if (!$client->query('SetGameInfos', $struct)) {
	   trigger_error("[".$client->getErrorCode()."] ".$client->getErrorMessage());
	}
} else if ($Action == 'ChangeAuthPassword') {
	if (!$client->query('ChangeAuthPassword', $_POST['newLogin'], $_POST['newPassword'])) {
	   trigger_error("[".$client->getErrorCode()."] ".$client->getErrorMessage());
	}
	else if( $AuthLogin==$_POST['newLogin'] )
	{
		$AuthLogin = $_POST['newLogin'];
		$AuthPassword = $_POST['newLogin'];
		$OldAuthLogin = $AuthLogin;
		$OldAuthPassword = $AuthPassword;
	}
} else if( ($Action == 'ChatSend') || ($Action == 'ChatSendServerMessage') ){
	if (!$client->query($Action, urldecode($_POST['ChatText']))) {
	   trigger_error("[".$client->getErrorCode()."] ".$client->getErrorMessage());
	}
} else if($Action == 'SendDisplayManialinkPage'){
	$AutoHide = array_key_exists('AutoHide', $_POST);
	if (!$client->query($Action, $_POST['Maniacode'], $_POST['TimeOut']+0, $AutoHide)) {
	   trigger_error("[".$client->getErrorCode()."] ".$client->getErrorMessage());
	}
} else if($Action == 'SendDisplayServerMessageToLogin'){
	$AutoHide = array_key_exists('AutoHide', $_POST);
	if (!$client->query($Action, $_POST['Login'], $_POST['Maniacode'],$_POST['TimeOut']+0, $AutoHide)) {
	   trigger_error("[".$client->getErrorCode()."] ".$client->getErrorMessage());
	}
} else if($Action == 'SendHideManialinkPage'){
	if (!$client->query($Action)) {
	   trigger_error("[".$client->getErrorCode()."] ".$client->getErrorMessage());
	}
} else if($Action == 'SendHideManialinkPageToLogin'){
	if (!$client->query($Action, $_POST['Login'])) {
	   trigger_error("[".$client->getErrorCode()."] ".$client->getErrorMessage());
	}
}

// ----------------------------------------------------------------
// connection info
// ----------------------------------------------------------------
echo "\n<h3>Connection Status:</h3>\n";

if (!$client->query('GetVersion')) {
   trigger_error("[".$client->getErrorCode()."] ".$client->getErrorMessage());
}
else
{
	$AuthSuperAdmin = $AuthLogin=="SuperAdmin" ? "selected" : "";
	$AuthAdmin = $AuthLogin=="Admin" ? "selected" : "";
	$AuthUser = $AuthLogin=="User" ? "selected" : "";

	echo <<<END
	<form name="input" action="basic.php" method="post">
	<table>
	<tr><td>Permission Level:		</td><td>
	<SELECT name="authLogin" >
		<OPTION value="SuperAdmin" $AuthSuperAdmin> SuperAdmin </OPTION>
		<OPTION value="Admin" $AuthAdmin> Admin </OPTION>
		<OPTION value="User" $AuthUser> User </OPTION>
	</SELECT>
	</td></tr>
	<tr><td>Password:		</td><td><input type="password" name="authPassword" size=30 value="$AuthPassword"/><td>
	</table>
	<input type="submit" value="Authenticate">
	<input type="hidden" name="oldAuthLogin" value="$OldAuthLogin">
	<input type="hidden" name="oldAuthPassword" value="$OldAuthPassword">
	<input type="hidden" name="port" value="$port">
	</form>
END;

	if( $AuthLogin=="SuperAdmin" )
	{
		echo <<<END
		<form name="input" action="basic.php" method="post">
		<table><tr>
		<td>Set new Password:		</td><td><input type="password" name="newPassword" size=30 value=""/><td>
		<td> for </td><td>
		<SELECT name="newLogin" >
			<OPTION value="SuperAdmin"> SuperAdmin </OPTION>
			<OPTION value="Admin"> Admin </OPTION>
			<OPTION value="User"> User </OPTION>
		</SELECT>
		</td>
		</table>
		<input type="submit" name="action" value="ChangeAuthPassword">
		<input type="hidden" name="authLogin" value="$AuthLogin">
		<input type="hidden" name="authPassword" value="$AuthPassword">
		<input type="hidden" name="port" value="$port">
		</form>
END;
	}

	$Version = $client->getResponse();
	echo "Connected to " . $Version['Name']. " - " . $Version['Version'] . "<br>";
}


// ----------------------------------------------------------------
// status info
// ----------------------------------------------------------------
echo "\n<h3>Server Status:</h3>\n";

if (!$client->query('GetStatus')) {
   trigger_error("[".$client->getErrorCode()."] ".$client->getErrorMessage());
}
else
{
	$Status = $client->getResponse();

	echo "Status: ".$Status['Name'];
	echo <<<END
	<form name="input" action="basic.php" method="post">
	<input type="submit" value="Refresh">
	<input type="hidden" name="authLogin" value="$AuthLogin">
	<input type="hidden" name="authPassword" value="$AuthPassword">
	<input type="hidden" name="port" value="$port">
	</form>
END;

	if ($Status['Code'] == 1) {
		// ----------------------------------------------------------------
		// start server
		// ----------------------------------------------------------------
		echo <<<END
		<form name="input" action="basic.php" method="post">
		<table>
		<tr><td>Master Server Login: </td><td><input type="text" name="Login" size=30 value="$MSLogin"/> </td></tr>
		<tr><td>Master Server Password: </td><td><input type="password" name="Password" size=30 value="$MSPassword"/> </td></tr>
		</table>
		<input type="submit" name="action" value="StartServerInternet">
		<input type="submit" name="action" value="StartServerLan">
		<input type="hidden" name="authLogin" value="$AuthLogin">
		<input type="hidden" name="authPassword" value="$AuthPassword">
		<input type="hidden" name="port" value="$port">
		</form>
END;

		echo <<<END
		<form name="input" action="basic.php" method="post">
		<input type="submit" name="action" value="QuitGame">
		<input type="hidden" name="authLogin" value="$AuthLogin">
		<input type="hidden" name="authPassword" value="$AuthPassword">
		<input type="hidden" name="port" value="$port">
		</form>
END;

	} else if ( ($Status['Code'] == 3) || ($Status['Code'] == 4) || ($Status['Code'] == 5) ) {
		if (!$client->query('GetCurrentChallengeInfo')) {
		   trigger_error("[".$client->getErrorCode()."] ".$client->getErrorMessage());
		}
		else
		{
			$CurrentChallengeInfo = $client->getResponse();
			echo "Current challenge : " . $CurrentChallengeInfo['UId'] . " - " . styledString($CurrentChallengeInfo['Name']) . " - " . $CurrentChallengeInfo['Author'] . "<BR>";
		}

		// ----------------------------------------------------------------
		// in game actions
		// ----------------------------------------------------------------
		if( $Status['Code'] == 4 )
		{
			echo <<<END
			<form name="input" action="basic.php" method="post">
			<input type="submit" name="action" value="RestartChallenge"/>
			<input type="submit" name="action" value="NextChallenge"/>
			<input type="submit" name="action" value="StopServer"/>
			<input type="submit" name="action" value="QuitGame"/>
            <input type="submit" Name="action" Value="CleanBanList"/>
			<input type="hidden" name="authLogin" value="$AuthLogin">
			<input type="hidden" name="authPassword" value="$AuthPassword">
			<input type="hidden" name="port" value="$port">
			</form>
END;
		}
		else
		{
			echo "<BR>";
		}

		echo "Players:<BR>";

		if (!$client->query('GetPlayerList', 50, 0)) {
		   trigger_error("[".$client->getErrorCode()."] ".$client->getErrorMessage());
		}
		else
		{
			$PlayerList = $client->getResponse();
			echo '<TABLE cellspacing=5>';
			foreach ($PlayerList as $player) {
				$PlayerLogin = $player['Login'];
				$PlayerName = styledString($player['NickName']);
				$PlayerRanking = $player['LadderRanking'];

				$PlayerIsSpectator = ($player['IsSpectator']!=0) ? "Spectator" : "Player";
				$PlayerIsInOfficialMode = ($player['IsInOfficialMode']!=0) ? "Official" : "Not Official";

				$PlayerTeamId = $player['TeamId'];
				if( $PlayerTeamId == -1)
					$PlayerTeam = "No Team";
				else if( $PlayerTeamId == 0)
					$PlayerTeam = "Blue Team";
				else
					$PlayerTeam = "Red Team";

				echo <<<END
				<TR><TD>$PlayerLogin</TD><TD>$PlayerName</TD><TD>$PlayerTeam</TD><TD>$PlayerIsSpectator</TD><TD>$PlayerIsInOfficialMode</TD><TD>$PlayerRanking</TD>
				<TD><form action="basic.php" method="post"><input type="hidden" name="PlayerLogin" value="$PlayerLogin"><input type="submit" name="action" value="Kick"><input type="hidden" name="authLogin" value="$AuthLogin"><input type="hidden" name="authPassword" value="$AuthPassword"><input type="hidden" name="port" value="$port"></form></TD>
				<TD><form action="basic.php" method="post"><input type="hidden" name="PlayerLogin" value="$PlayerLogin"><input type="submit" name="action" value="Ban"><input type="hidden" name="authLogin" value="$AuthLogin"><input type="hidden" name="authPassword" value="$AuthPassword"><input type="hidden" name="port" value="$port"></form></TD></TR>
END;
			}
			echo "</TABLE><BR>";
		}

		echo <<<END
		<form name="input" action="basic.php" method="post">
		<input type="text" name="ChatText" size=70/>
		<input type="submit" name="action" value="ChatSend">
		<input type="submit" name="action" value="ChatSendServerMessage">
		<input type="hidden" name="authLogin" value="$AuthLogin">
		<input type="hidden" name="authPassword" value="$AuthPassword">
		<input type="hidden" name="port" value="$port">
		</form>
END;
		$AutoHide = False;
		echo <<<END
		<form name="input" action="basic.php" method="post">
		<table>
		<tr><td>Maniacode:	</td><td><textarea cols="50" rows="4" name="Maniacode"></textarea> </td></tr>
		<tr><td>TimeOut:	</td><td><input type="text" name="TimeOut" size=70/> </td></tr>
		<tr><td>Login:	</td><td><input type="text" name="Login" size=70/> </td></tr>
		<tr><td>AutoHide:	</td><td><input type="checkbox" name="AutoHide" $AutoHide/> </td></tr>
		
		</table>
		<input type="submit" name="action" value="SendDisplayManialinkPage">
		<input type="submit" name="action" value="SendDisplayManialinkPageToLogin">
		<input type="submit" name="action" value="SendHideManialinkPage">
		<input type="submit" name="action" value="SendHideManialinkPageToLogin">
		<input type="hidden" name="authLogin" value="$AuthLogin">
		<input type="hidden" name="authPassword" value="$AuthPassword">
		<input type="hidden" name="port" value="$port">
		</form>
END;

		echo "Ranking:<BR>";

		if (!$client->query('GetCurrentRanking', 50, 0)) {
		   trigger_error("[".$client->getErrorCode()."] ".$client->getErrorMessage());
		}
		else
		{
			$CurrentRanking = $client->getResponse();
			echo '<TABLE cellspacing=5>';
			foreach ($CurrentRanking as $Ranking) {
				$PlayerLogin = $Ranking['Login'];
				$PlayerName = styledString($Ranking['NickName']);
				$PlayerRank = $Ranking['Rank'];
				$PlayerBestTime = $Ranking['BestTime'];
				$PlayerScore = $Ranking['Score'];
				$PlayerNbrLaps = $Ranking['NbrLapsFinished'];
				$PlayerLadderScore = $Ranking['LadderScore'];
				echo <<<END
				<TR><TD>$PlayerLogin</TD><TD>$PlayerName</TD><TD>$PlayerRank</TD><TD>$PlayerBestTime</TD><TD>$PlayerScore</TD><TD>$PlayerNbrLaps</TD><TD>$PlayerLadderScore</TD></TR>
END;
			}
			echo "</TABLE><BR>";
		}

	} else if ($Status['Code'] == 2) {
		echo "server busy..<BR><BR>";
	}

	echo "GuestList:<BR>";

	if (!$client->query('GetGuestList', 50, 0)) {
	   trigger_error("[".$client->getErrorCode()."] ".$client->getErrorMessage());
	}
	else
	{
		$GuestList = $client->getResponse();
		echo '<TABLE cellspacing=5>';
		foreach ($GuestList as $player) {
			$PlayerLogin=$player['Login'];
			echo <<<END
			<TR><TD>$PlayerLogin</TD>
			<TD><form action="basic.php" method="post">
				<input type="hidden" name="PlayerLogin" value="$PlayerLogin">
				<input type="submit" name="action" value="RemoveGuest">
				<input type="hidden" name="authLogin" value="$AuthLogin">
				<input type="hidden" name="authPassword" value="$AuthPassword">
				<input type="hidden" name="port" value="$port">
			</form></TD></TR>
END;
		}
		echo "</TABLE>";

		echo <<<END
		<form name="input" action="basic.php" method="post">
		<input type="text" name="PlayerLogin" size=70/>
		<input type="submit" name="action" value="AddGuest">
		<input type="hidden" name="authLogin" value="$AuthLogin">
		<input type="hidden" name="authPassword" value="$AuthPassword">
		<input type="hidden" name="port" value="$port">
		</form>
END;

		echo <<<END
		<form name="input" action="basic.php" method="post">
		<input type="text" name="GuestListFileName" size=70/><BR>
		<input type="submit" name="action" value="LoadGuestList">
		<input type="submit" name="action" value="SaveGuestList">
		<input type="hidden" name="authLogin" value="$AuthLogin">
		<input type="hidden" name="authPassword" value="$AuthPassword">
		<input type="hidden" name="port" value="$port">
		</form>
END;

	}

	echo "BlackList:<BR>";

	if (!$client->query('GetBlackList', 50, 0)) {
	   trigger_error("[".$client->getErrorCode()."] ".$client->getErrorMessage());
	}
	else
	{
		$BlackList = $client->getResponse();
		echo '<TABLE cellspacing=5>';
		foreach ($BlackList as $player) {
			$PlayerLogin=$player['Login'];
			echo <<<END
			<TR><TD>$PlayerLogin</TD>
			<TD><form action="basic.php" method="post">
				<input type="hidden" name="PlayerLogin" value="$PlayerLogin">
				<input type="submit" name="action" value="UnBlackList">
				<input type="hidden" name="authLogin" value="$AuthLogin">
				<input type="hidden" name="authPassword" value="$AuthPassword">
				<input type="hidden" name="port" value="$port">
			</form></TD>
END;
		}
		echo "</TABLE>";

		echo <<<END
		<TR><form name="input" action="basic.php" method="post">
		<TD colspan=4><input type="text" name="PlayerLogin" size=70/></TD>
		<TD><input type="submit" name="action" value="BlackList"></TD>
		<input type="hidden" name="authLogin" value="$AuthLogin">
		<input type="hidden" name="authPassword" value="$AuthPassword">
		<input type="hidden" name="port" value="$port">
		</form></TD>
END;

		echo <<<END
		<form name="input" action="basic.php" method="post">
		<input type="text" name="BlackListFileName" size=70/><BR>
		<input type="submit" name="action" value="LoadBlackList">
		<input type="submit" name="action" value="SaveBlackList">
		<input type="hidden" name="authLogin" value="$AuthLogin">
		<input type="hidden" name="authPassword" value="$AuthPassword">
		<input type="hidden" name="port" value="$port">
		</form>
END;

	}
               echo "BanList:<BR>";

	if (!$client->query('GetBanList', 50, 0)) {
	   trigger_error("[".$client->getErrorCode()."] ".$client->getErrorMessage());
	}
	else
	{
		$BanList = $client->getResponse();
		echo '<TABLE cellspacing=5>';
		foreach ($BanList as $player) {
			$PlayerLogin=$player['Login'];
			echo <<<END
			<TR><TD>$PlayerLogin</TD>
			<TD><form action="basic.php" method="post">
				<input type="hidden" name="PlayerLogin" value="$PlayerLogin">
				<input type="submit" name="action" value="UnBan">
				<input type="hidden" name="authLogin" value="$AuthLogin">
				<input type="hidden" name="authPassword" value="$AuthPassword">
				<input type="hidden" name="port" value="$port">
			</form></TD></TR>
END;
		}
		echo "</TABLE>";

		echo <<<END
		<form name="input" action="basic.php" method="post">
		<input type="text" name="PlayerLogin" size=70/>
		<input type="submit" name="action" value="Ban">
		<input type="hidden" name="authLogin" value="$AuthLogin">
		<input type="hidden" name="authPassword" value="$AuthPassword">
		<input type="hidden" name="port" value="$port">
		</form>
END;
                }
}


// ----------------------------------------------------------------
// Server options
// ----------------------------------------------------------------
echo "\n<h3>Server options:</h3>\n";

if (!$client->query('GetServerOptions', 1)) {
   trigger_error("[".$client->getErrorCode()."] ".$client->getErrorMessage());
}
else
{
	$ServerOptions = $client->getResponse();
	$repGetServerName = $ServerOptions['Name'];
	$repServerComment = $ServerOptions['Comment'];
	$repServerPassword = $ServerOptions['Password'];
	$repPasswordForSpectator = $ServerOptions['PasswordForSpectator'];
	$repRefereePassword = $ServerOptions['RefereePassword'];
	$repRefereeMode = $ServerOptions['RefereeMode'];
	$repCurrentMaxPlayer = $ServerOptions['CurrentMaxPlayers'];
	$repNextMaxPlayer = $ServerOptions['NextMaxPlayers'];
	$repCurrentMaxSpectator = $ServerOptions['CurrentMaxSpectators'];
	$repNextMaxSpectator = $ServerOptions['NextMaxSpectators'];
	$repP2PUpload = ($ServerOptions['IsP2PUpload']!=0) ? "checked" : " ";
	$repP2PDownload = ($ServerOptions['IsP2PDownload']!=0) ? "checked" : " ";
	$repCurrentLadderMode = $ServerOptions['CurrentLadderMode'];
	$repNextLadderMode = $ServerOptions['NextLadderMode'];
	$repCurrentVehicleNetQuality = $ServerOptions['CurrentVehicleNetQuality'];
	$repNextVehicleNetQuality = $ServerOptions['NextVehicleNetQuality'];
	$repCurrentCallVoteTimeOut = $ServerOptions['CurrentCallVoteTimeOut'];
	$repNextCallVoteTimeOut = $ServerOptions['NextCallVoteTimeOut'];
	$repCallVoteRatio = $ServerOptions['CallVoteRatio'];
	$repAllowChallengeDownload = ($ServerOptions['AllowChallengeDownload']!=0) ? "checked" : " ";
	$repAutoSaveReplays = ($ServerOptions['AutoSaveReplays']!=0) ? "checked" : " ";
	$repAutoSaveValidationReplays = ($ServerOptions['AutoSaveValidationReplays']!=0) ? "checked" : " ";

	if( $repCurrentLadderMode==0 )
		$CurrentLadderMode = "Inactive";
	else if( $repCurrentLadderMode==1 )
		$CurrentLadderMode = "Forced";
	else 
		$CurrentLadderMode = "Undefined";

	if( $repNextLadderMode==0 )
	{
		$NextLadderModeInactive = "selected";
		$NextLadderModeForced = "";
	}
	{
		$NextLadderModeInactive = "";
		$NextLadderModeForced = "selected";
	}

	if( $repCurrentVehicleNetQuality==0 )
		$CurrentVehicleNetQuality = "Fast";
	else if( $repCurrentVehicleNetQuality==1 )
		$CurrentVehicleNetQuality = "High";
	else 
		$CurrentLadderMode = "Undefined";

	if( $repNextVehicleNetQuality==1 )
	{
		$NextVehicleNetQualityFast = "";
		$NextVehicleNetQualityHigh = "selected";
	}
	else
	{
		$NextVehicleNetQualityFast = "selected";
		$NextVehicleNetQualityHigh = "";
	}

	echo <<<END
	<form name="input" action="basic.php" method="post">
	<table>
	<tr><td>Name:		</td><td><input type="text" name="ServerName" size=30 value="$repGetServerName"/> </td></tr>
	<tr><td>Comment:	</td><td><textarea name="ServerComment" cols=40 rows=3> $repServerComment </textarea> </td></tr>
	<tr><td>Password:	</td><td><input type="text" name="ServerPassword" size=30 value="$repServerPassword"/> </td></tr>
	<tr><td>PasswordForSpectator:	</td><td><input type="text" name="SpectatorPassword" size=30 value="$repPasswordForSpectator"/> </td></tr>
	<tr><td>RefereePassword:	</td><td><input type="text" name="RefereePassword" size=30 value="$repRefereePassword"/> </td></tr>
	<tr><td>MaxPlayer:	</td><td><table><td><input type="text" name="CurrentMaxPlayers" size=10 readonly value="$repCurrentMaxPlayer"/> </td><td> Next Value:	</td><td><input type="text" name="NextMaxPlayers" size=10 value="$repNextMaxPlayer"/> </td></table></td></tr>
	<tr><td>MaxSpectator:	</td><td><table><td><input type="text" name="CurrentMaxSpectators" size=10 readonly value="$repCurrentMaxSpectator"/> </td><td> Next Value:	</td><td><input type="text" name="NextMaxSpectators" size=10 value="$repNextMaxSpectator"/> </td></table></td></tr>
	<tr><td>P2PUpload:	</td><td><input type="checkbox" name="IsP2PUpload" $repP2PUpload/> </td></tr>
	<tr><td>P2PDownload:	</td><td><input type="checkbox" name="IsP2PDownload" $repP2PDownload/> </td></tr>
	<tr><td>LadderMode:	</td><td><table><td><input type="text" name="CurrentLadderMode" size=10 readonly value="$CurrentLadderMode"/> </td><td> Next Value:	</td><td>
	<SELECT name="NextLadderMode" >
		<OPTION value="0" $NextLadderModeInactive> Inactive </OPTION>
		<OPTION value="1" $NextLadderModeForced> Forced </OPTION>
	</SELECT>
	</td></table></td></tr>
	<tr><td>VehicleNetQuality:	</td><td><table><td><input type="text" name="CurrentVehicleNetQuality" size=10 readonly value="$CurrentVehicleNetQuality"/> </td><td> Next Value:	</td><td>
	<SELECT name="NextVehicleNetQuality" >
		<OPTION value="0" $NextVehicleNetQualityFast> Fast </OPTION>
		<OPTION value="1" $NextVehicleNetQualityHigh> High </OPTION>
	</SELECT>
	</td></table></td></tr>
	<tr><td>CallVoteTimeOut:	</td><td><table><td><input type="text" name="CurrentCallVoteTimeOut" size=10 readonly value="$repCurrentCallVoteTimeOut"/> </td><td> Next Value:	</td><td><input type="text" name="NextCallVoteTimeOut" size=10 value="$repNextCallVoteTimeOut"/> </td></table></td></tr>
	<tr><td>CallVoteRatio:	</td><td><input type="text" name="CallVoteRatio" size=10 value="$repCallVoteRatio"/> </td></tr>
	<tr><td>AllowChallengeDownload:	</td><td><input type="checkbox" name="AllowChallengeDownload" $repAllowChallengeDownload/> </td></tr>
	<tr><td>AutoSaveReplays:  </td><td><input type="checkbox" name="AutoSaveReplays" $repAutoSaveReplays/> </td></tr>
	<tr><td>AutoSaveValidationReplays:  </td><td><input type="checkbox" name="AutoSaveValidationReplays" $repAutoSaveValidationReplays/> </td></tr>
	</table>
	<input type="submit" name="action" value="SetServerOptions">
	<input type="hidden" name="authLogin" value="$AuthLogin">
	<input type="hidden" name="authPassword" value="$AuthPassword">
	<input type="hidden" name="port" value="$port">
	<input type="hidden" name="RefereeMode" value="$repRefereeMode">
	</form>
END;
}


// ----------------------------------------------------------------
// Game infos
// ----------------------------------------------------------------
echo "\n<h3>Game infos:</h3>\n";

if (!$client->query('GetGameInfos', 1)) {
   trigger_error("[".$client->getErrorCode()."] ".$client->getErrorMessage());
}
else
{
	$GameInfos = $client->getResponse();

	$CurrentGameInfo = $GameInfos['CurrentGameInfos'];
	$NextGameInfo = $GameInfos['NextGameInfos'];

	$ChatTime = $CurrentGameInfo['ChatTime'];
	$NbChallenge = $CurrentGameInfo['NbChallenge'];
	if( $CurrentGameInfo['GameMode']==0 )
		$GameMode = "Rounds";
	else if( $CurrentGameInfo['GameMode']==1 )
		$GameMode = "TimeAttack";
	else if( $CurrentGameInfo['GameMode']==2 )
		$GameMode = "Team";
	else if( $CurrentGameInfo['GameMode']==3 )
		$GameMode = "Laps";
	else if( $CurrentGameInfo['GameMode']==4 )
		$GameMode = "Stunts";
	else if( $CurrentGameInfo['GameMode']==5 )
		$GameMode = "Cup";
	else 
		$GameMode = "Undefined";
	$RoundsPointsLimit = $CurrentGameInfo['RoundsPointsLimit'];
	$RoundsPointsLimitNewRules = $CurrentGameInfo['RoundsPointsLimitNewRules'];
	$RoundsUseNewRules = ($CurrentGameInfo['RoundsUseNewRules']!=0) ? "True" : "False";
	$RoundsForcedLaps = $CurrentGameInfo['RoundsForcedLaps'];
	$FinishTimeout = $CurrentGameInfo['FinishTimeout'];
	$TimeAttackLimit = $CurrentGameInfo['TimeAttackLimit'];
	$TimeAttackSynchStartPeriod = $CurrentGameInfo['TimeAttackSynchStartPeriod'];
	$TeamPointsLimit = $CurrentGameInfo['TeamPointsLimit'];
	$TeamPointsLimitNewRules = $CurrentGameInfo['TeamPointsLimitNewRules'];
	$TeamMaxPoints = $CurrentGameInfo['TeamMaxPoints'];
	$TeamUseNewRules = ($CurrentGameInfo['TeamUseNewRules']!=0) ? "True" : "False";
	$CupPointsLimit = $CurrentGameInfo['CupPointsLimit'];
	$CupRoundsPerChallenge = $CurrentGameInfo['CupRoundsPerChallenge'];
	$CupNbWinners = $CurrentGameInfo['CupNbWinners'];
	$CupWarmUpDuration = $CurrentGameInfo['CupWarmUpDuration'];
	$LapsNbLaps = $CurrentGameInfo['LapsNbLaps'];
	$LapsTimeLimit = $CurrentGameInfo['LapsTimeLimit'];
	$NextChatTime = $NextGameInfo['ChatTime'];
	$NextGameMode0 = $NextGameInfo['GameMode']==0 ? "selected" : "";
	$NextGameMode1 = $NextGameInfo['GameMode']==1 ? "selected" : "";
	$NextGameMode2 = $NextGameInfo['GameMode']==2 ? "selected" : "";
	$NextGameMode3 = $NextGameInfo['GameMode']==3 ? "selected" : "";
	$NextGameMode4 = $NextGameInfo['GameMode']==4 ? "selected" : "";
	$NextGameMode5 = $NextGameInfo['GameMode']==5 ? "selected" : "";
	$NextRoundsPointsLimit = $NextGameInfo['RoundsPointsLimit'];
	$NextRoundsPointsLimitNewRules = $NextGameInfo['RoundsPointsLimitNewRules'];
	$NextRoundsUseNewRules = ($NextGameInfo['RoundsUseNewRules']!=0) ? "checked" : " ";
	$NextRoundsForcedLaps = $NextGameInfo['RoundsForcedLaps'];
	$NextFinishTimeout = $NextGameInfo['FinishTimeout'];
	$NextTimeAttackLimit = $NextGameInfo['TimeAttackLimit'];
	$NextTimeAttackSynchStartPeriod = $NextGameInfo['TimeAttackSynchStartPeriod'];
	$NextTeamPointsLimit = $NextGameInfo['TeamPointsLimit'];
	$NextTeamPointsLimitNewRules = $NextGameInfo['TeamPointsLimitNewRules'];
	$NextTeamMaxPoints = $NextGameInfo['TeamMaxPoints'];
	$NextTeamUseNewRules = ($NextGameInfo['TeamUseNewRules']!=0) ? "checked" : " ";
	$NextCupPointsLimit = $NextGameInfo['CupPointsLimit'];
	$NextCupRoundsPerChallenge = $NextGameInfo['CupRoundsPerChallenge'];
	$NextCupNbWinners = $NextGameInfo['CupNbWinners'];
	$NextCupWarmUpDuration = $NextGameInfo['CupWarmUpDuration'];
	$NextLapsNbLaps = $NextGameInfo['LapsNbLaps'];
	$NextLapsTimeLimit = $NextGameInfo['LapsTimeLimit'];

	echo <<<END
	<form name="input" action="basic.php" method="post">
	<table>
	<tr><td></td><td>Current</td><td>Next</td></tr>
	<tr><td>Mode:		</td><td><input type="text" name="GameMode" readonly value="$GameMode"/> </td><td>
	<SELECT name="NextGameMode" >
		<OPTION value="0" $NextGameMode0> Rounds </OPTION>
		<OPTION value="1" $NextGameMode1> TimeAttack </OPTION>
		<OPTION value="2" $NextGameMode2> Team </OPTION>
		<OPTION value="3" $NextGameMode3> Laps </OPTION>
		<OPTION value="4" $NextGameMode4> Stunts </OPTION>
		<OPTION value="5" $NextGameMode5> Cup </OPTION>
	</SELECT>
	</td></tr>
	<tr><td>ChatTime:	</td><td><input type="text" name="ChatTime" readonly value="$ChatTime"/> </td><td><input type="text" name="NextChatTime" value="$NextChatTime"/> </td></tr>
	<tr><td>NbChallenge:	</td><td><input type="text" name="NbChallenge" readonly value="$NbChallenge"/> </td></tr>
	<tr><td>RoundsPointsLimit:	</td><td><input type="text" name="RoundsPointsLimit" readonly value="$RoundsPointsLimit"/> </td><td><input type="text" name="NextRoundsPointsLimit" value="$NextRoundsPointsLimit"/> </td></tr>
	<tr><td>RoundsPointsLimitNewRules:	</td><td><input type="text" name="RoundsPointsLimitNewRules" readonly value="$RoundsPointsLimitNewRules"/> </td><td><input type="text" name="NextRoundsPointsLimitNewRules" value="$NextRoundsPointsLimitNewRules"/> </td></tr>
	<tr><td>RoundsUseNewRules:	</td><td><input type="text" name="RoundsUseNewRules" readonly value="$RoundsUseNewRules"/> </td><td><input type="checkbox" name="NextRoundsUseNewRules" $NextRoundsUseNewRules/> </td></tr>
	<tr><td>RoundsForcedLaps:	</td><td><input type="text" name="RoundsForcedLaps" readonly value="$RoundsForcedLaps"/> </td><td><input type="text" name="NextRoundsForcedLaps" value="$NextRoundsForcedLaps"/> </td></tr>
	<tr><td>FinishTimeout:	</td><td><input type="text" name="FinishTimeout" readonly value="$FinishTimeout"/> </td><td><input type="text" name="NextFinishTimeout" value="$NextFinishTimeout"/> </td></tr>
	<tr><td>TimeAttackLimit:	</td><td><input type="text" name="TimeAttackLimit" readonly value="$TimeAttackLimit"/> </td><td><input type="text" name="NextTimeAttackLimit" value="$NextTimeAttackLimit"/> </td></tr>
	<tr><td>TimeAttackSynchStartPeriod:	</td><td><input type="text" name="TimeAttackSynchStartPeriod" readonly value="$TimeAttackSynchStartPeriod"/> </td><td><input type="text" name="NextTimeAttackSynchStartPeriod" value="$NextTimeAttackSynchStartPeriod"/> </td></tr>
	<tr><td>TeamPointsLimit:	</td><td><input type="text" name="TeamPointsLimit" readonly value="$TeamPointsLimit"/> </td><td><input type="text" name="NextTeamPointsLimit" value="$NextTeamPointsLimit"/> </td></tr>
	<tr><td>TeamPointsLimitNewRules:	</td><td><input type="text" name="TeamPointsLimitNewRules" readonly value="$TeamPointsLimitNewRules"/> </td><td><input type="text" name="NextTeamPointsLimitNewRules" value="$NextTeamPointsLimitNewRules"/> </td></tr>
	<tr><td>TeamMaxPoints:	</td><td><input type="text" name="TeamMaxPoints" readonly value="$TeamMaxPoints"/> </td><td><input type="text" name="NextTeamMaxPoints" value="$NextTeamMaxPoints"/> </td></tr>
	<tr><td>TeamUseNewRules:	</td><td><input type="text" name="TeamUseNewRules" readonly value="$TeamUseNewRules"/> </td><td><input type="checkbox" name="NextTeamUseNewRules" $NextTeamUseNewRules/> </td></tr>
	<tr><td>CupPointsLimit:	</td><td><input type="text" name="CupPointsLimit" readonly value="$CupPointsLimit"/> </td><td><input type="text" name="NextCupPointsLimit" value="$NextCupPointsLimit"/> </td></tr>
	<tr><td>CupRoundsPerChallenge:	</td><td><input type="text" name="CupRoundsPerChallenge" readonly value="$CupRoundsPerChallenge"/> </td><td><input type="text" name="NextCupRoundsPerChallenge" value="$NextCupRoundsPerChallenge"/> </td></tr>
	<tr><td>CupNbWinners:	</td><td><input type="text" name="CupNbWinners" readonly value="$CupNbWinners"/> </td><td><input type="text" name="NextCupNbWinners" value="$NextCupNbWinners"/> </td></tr>
	<tr><td>CupWarmUpDuration:	</td><td><input type="text" name="CupWarmUpDuration" readonly value="$CupWarmUpDuration"/> </td><td><input type="text" name="NextCupWarmUpDuration" value="$NextCupWarmUpDuration"/> </td></tr>
	<tr><td>LapsNbLaps:	</td><td><input type="text" name="LapsNbLaps" readonly value="$LapsNbLaps"/> </td><td><input type="text" name="NextLapsNbLaps" value="$NextLapsNbLaps"/> </td></tr>
	<tr><td>LapsTimeLimit:	</td><td><input type="text" name="LapsTimeLimit" readonly value="$LapsTimeLimit"/> </td><td><input type="text" name="NextLapsTimeLimit" value="$NextLapsTimeLimit"/> </td></tr>
	</table>
	<input type="submit" name="action" value="SetGameInfos">
	<input type="hidden" name="authLogin" value="$AuthLogin">
	<input type="hidden" name="authPassword" value="$AuthPassword">
	<input type="hidden" name="port" value="$port">
	</form>
END;
}


// ----------------------------------------------------------------
// challenges
// ----------------------------------------------------------------
// debug
echo "\n<h3>Challenges:</h3>\n";

if (!$client->query('GetChallengeList', 50, 0)) {
   trigger_error("[".$client->getErrorCode()."] ".$client->getErrorMessage());
}
else
{
	$ChallengeList = $client->getResponse();
	echo '<TABLE cellspacing=5>';
	foreach ($ChallengeList as $challenge) {
		$ChallengeUId = $challenge['UId'];
		$ChallengeAuthor = $challenge['Author'];
		$ChallengeName = styledString($challenge['Name']);
		$FileName = $challenge['FileName'];
		$FileNameUrl = urlencode($FileName);
		$Environnement = $challenge['Environnement'];
		$GoldTime = MwTimeToString($challenge['GoldTime']);
		$CopperPrice = $challenge['CopperPrice'];
		echo <<<END
		<TR><TD>$ChallengeName<br>$ChallengeUId</TD><TD>$FileName</TD><TD>$Environnement<br>$ChallengeAuthor</TD><TD>$GoldTime</TD><TD>$CopperPrice</TD>
		<TD><form action="basic.php" method="post"><input type="hidden" name="ChallengeFileName" value="$FileName"><input type="submit" name="action" value="RemoveChallenge"><input type="hidden" name="authLogin" value="$AuthLogin"><input type="hidden" name="authPassword" value="$AuthPassword"><input type="hidden" name="port" value="$port"></form></TD>
		<TD><form action="basic.php" method="post"><input type="hidden" name="ChallengeFileName" value="$FileName"><input type="submit" name="action" value="ChooseNextChallenge"><input type="hidden" name="authLogin" value="$AuthLogin"><input type="hidden" name="authPassword" value="$AuthPassword"><input type="hidden" name="port" value="$port"></form></TD>
		</TR>
END;
	}
	echo "</TABLE>";
}

echo <<<END
<TR><form name="input" action="basic.php" method="post">
<TD colspan=4><input type="text" name="ChallengeFileName" size=70/></TD>
<TD><input type="submit" name="action" value="AddChallenge"></TD>
<input type="hidden" name="authLogin" value="$AuthLogin">
<input type="hidden" name="authPassword" value="$AuthPassword">
<input type="hidden" name="port" value="$port">
</form></TD>
END;

echo <<<END
<form name="input" action="basic.php" method="post">
	<input type="text" name="MatchSettingsFileName" size=70/><BR>
	<input type="submit" name="action" value="LoadMatchSettings"/>
	<input type="submit" name="action" value="SaveMatchSettings"/>
	<input type="submit" name="action" value="AppendPlaylistFromMatchSettings"/>
	<input type="hidden" name="authLogin" value="$AuthLogin">
	<input type="hidden" name="authPassword" value="$AuthPassword">
	<input type="hidden" name="port" value="$port">
</form></TD>
END;

/*
//      uncomment to test the callbacks..

echo "<h2>callbacks:</h2><br/>";
if (!$client->query('EnableCallbacks', true)) {
   trigger_error("[".$client->getErrorCode()."] ".$client->getErrorMessage());
}
flush();
while (true) {
	$client->readCB(5);

	$calls = $client->getCBResponses();
	if (!empty($calls)) {
		foreach ($calls as $call) {
			echo "call: ".$call[0]."<br/>";
		}		
	} else {
		echo "no calls...<br/>";
	}
	flush();
}
*/

$client->Terminate();

?>
