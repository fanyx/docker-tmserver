<?php
// vim: set noexpandtab tabstop=2 softtabstop=2 shiftwidth=2:
require('GbxRemote.inc.php');

/**
 * GBXChallInfo - Return GetCurrentChallengeInfo data for TrackMania tracks
 * Created by Xymph <tm@gamers.org>
 *
 * v1.2: Allow getting info from tracks already on the server w/o removing them
 * v1.1: Fix PHP notices about redefinition of constants
 * v1.0: Initial release
 */

class GBXChallengeInfo {

	public $name, $uid, $filename, $author, $envir, $mood,
	       $bronzetm, $silvertm, $goldtm, $authortm,
	       $coppers, $laprace, $nblaps, $nbcps, $nbrcps, $error;

	/**
	 * Fetches current ChallengeInfo for a GBX challenge
	 * Loads track into server, selects it, gets info, and removes it from server
	 *
	 * @param String $filename
	 *        The challenge filename (must be a path below .../GameData/Tracks/)
	 * @return GBXChallengeInfo
	 *        If $uid is empty, GBX data couldn't be extracted and $error contains
	 *        an error message
	 */
	public function GBXChallengeInfo($filename) {

		$ip   = 'localhost';
		$port = 5000;
		$user = 'SuperAdmin';
		$pass = 'YOUR_SUPERADMIN_PASSWORD';

		$this->uid = '';
		$this->error = '';
		$client = new IXR_Client_Gbx;

		// connect to the server
		if (!$client->InitWithIp($ip, $port)) {
			$this->error = 'Connection failed - Error ' . $client->getErrorCode() . ': ' . $client->getErrorMessage();
			return false;
		}

		// log into the server
		if (!$client->query('Authenticate', $user, $pass)) {
			$this->error = 'Login failed - Error ' . $client->getErrorCode() . ': ' . $client->getErrorMessage();

		} else {
			// add the challenge
			$ret = $client->query('AddChallenge', $filename);
			$already = ($client->getErrorMessage() == 'Challenge already added.');
			if (!$ret && !$already) {
				$this->error = 'AddChallenge failed - Error ' . $client->getErrorCode() . ': ' . $client->getErrorMessage();
			} else {

				// select the challenge
				if (!$client->query('ChooseNextChallenge', $filename)) {
					$this->error = 'ChooseNextChallenge failed - Error ' . $client->getErrorCode() . ': ' . $client->getErrorMessage();

				// switch to our challenge
				} elseif (!$client->query('NextChallenge')) {
					$this->error = 'NextChallenge 1 failed - Error ' . $client->getErrorCode() . ': ' . $client->getErrorMessage();
				} else {

					// allow for challenge switch but time out after 5 seconds
					$retry = 5;
					while (true) {
						sleep(1);

						// obtain challenge details
						if ($client->query('GetCurrentChallengeInfo')) {
							$info = $client->getResponse();
							// check for our challenge
							if ($info['FileName'] == $filename) {
								break;
							}
						} else {
							$this->error = 'GetCurrentChallengeInfo failed - Error ' . $client->getErrorCode() . ': ' . $client->getErrorMessage();
							break;
						}
						if ($retry-- == 0) {
							$this->error = 'GetCurrentChallengeInfo timed out after 5 seconds';
							break;
						}
					}

					// extract our challenge details
					if ($this->error == '') {
						$this->name     = $info['Name'];
						$this->uid      = $info['UId'];
						$this->filename = $info['FileName'];
						$this->author   = $info['Author'];
						$this->envir    = $info['Environnement'];
						$this->mood     = $info['Mood'];
						$this->bronzetm = $info['BronzeTime'];
						$this->silvertm = $info['SilverTime'];
						$this->goldtm   = $info['GoldTime'];
						$this->authortm = $info['AuthorTime'];
						$this->coppers  = $info['CopperPrice'];
						$this->laprace  = $info['LapRace'];
						$this->nblaps   = $info['NbLaps'];
						$this->nbcps    = $info['NbCheckpoints'];
						$this->nbrcps   = $info['NbCheckpoints'];

						if ($this->laprace && $this->nblaps > 1)
							$this->nbrcps *= $this->nblaps;
					}
				}
			}

			// check if challenge wasn't already there
			if (!$already) {
				// remove the challenge
				if (!$client->query('RemoveChallenge', $filename)) {
					$this->error = 'RemoveChallenge failed - Error ' . $client->getErrorCode() . ': ' . $client->getErrorMessage();
				}
				// switch away from our challenge
				sleep(5);
				if (!$client->query('NextChallenge')) {
					$this->error = 'NextChallenge 2 failed - Error ' . $client->getErrorCode() . ': ' . $client->getErrorMessage();
				}
			}
		}

		$client->Terminate();
	}
}
?>
