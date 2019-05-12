#!/usr/bin/php -q
<?php
// vim: set noexpandtab tabstop=2 softtabstop=2 shiftwidth=2:

// Repair truncation issues with the Nation field in the XASECO players table
// Created Apr 2008 by Xymph <tm@gamers.org>

	if (!mysql_connect('localhost','YOUR_MYSQL_LOGIN','YOUR_MYSQL_PASSWORD')) {
		echo "could not connect\n";
		exit;
	}
	if (!mysql_select_db('aseco')) {
		echo "could not select\n";
		exit;
	}

	// get all unique Nation strings
	$query = 'SELECT Nation FROM players';
	$resply = mysql_query($query);

	$nations = array();
	if (mysql_num_rows($resply) > 0) {
		while ($rowply = mysql_fetch_object($resply)) {
			$nations[] = $rowply->Nation;
		}
		mysql_free_result($resply);
	} else {
		echo "no players!\n";
	}
	$uniques = array_unique($nations);

	echo 'Unique nations: ' . count($uniques) . "\n\n";

	$count = 0;
	foreach ($uniques as $oldnation) {
		if (strlen($oldnation) == 0)
			$newnation = 'OTH';  // default OTH(ER) for empty nations
		// check for full, capitalized country name
		elseif (strlen($oldnation) > 3)
			$newnation = mapCountry($oldnation);
		// check for trunctated, capitalized country name
		elseif ($oldnation != strtoupper($oldnation))
			$newnation = mapAbbrev($oldnation);
		else  // already all-caps abbreviation
			continue;

		// update Nation with 3-letter abbreviation
		$query = 'UPDATE players
		          SET Nation="' . $newnation . '"
		          WHERE Nation="' . $oldnation . '"';
		$result = mysql_query($query);
		$count++;
	}
	echo 'Updated nations: ' . $count . "\n";

/**
 * Map country names to 3-letter Nation abbreviations
 * Created by Xymph
 * Based on http://en.wikipedia.org/wiki/List_of_IOC_country_codes
 */
function mapCountry($country) {

	$nations = array(
		'Afghanistan' => 'AFG',
		'Albania' => 'ALB',
		'Algeria' => 'ALG',
		'Andorra' => 'AND',
		'Angola' => 'ANG',
		'Argentina' => 'ARG',
		'Armenia' => 'ARM',
		'Aruba' => 'ARU',
		'Australia' => 'AUS',
		'Austria' => 'AUT',
		'Azerbaijan' => 'AZE',
		'Bahamas' => 'BAH',
		'Bahrain' => 'BRN',
		'Bangladesh' => 'BAN',
		'Barbados' => 'BAR',
		'Belarus' => 'BLR',
		'Belgium' => 'BEL',
		'Belize' => 'BIZ',
		'Benin' => 'BEN',
		'Bermuda' => 'BER',
		'Bhutan' => 'BHU',
		'Bolivia' => 'BOL',
		'Bosnia&Herzegovina' => 'BIH',
		'Botswana' => 'BOT',
		'Brazil' => 'BRA',
		'Brunei' => 'BRU',
		'Bulgaria' => 'BUL',
		'Burkina Faso' => 'BUR',
		'Burundi' => 'BDI',
		'Cambodia' => 'CAM',
		'Cameroon' => 'CAR',  // actually CMR
		'Canada' => 'CAN',
		'Cape Verde' => 'CPV',
		'Central African Republic' => 'CAF',
		'Chad' => 'CHA',
		'Chile' => 'CHI',
		'China' => 'CHN',
		'Chinese Taipei' => 'TPE',
		'Colombia' => 'COL',
		'Congo' => 'CGO',
		'Costa Rica' => 'CRC',
		'Croatia' => 'CRO',
		'Cuba' => 'CUB',
		'Cyprus' => 'CYP',
		'Czech Republic' => 'CZE',
		'Czech republic' => 'CZE',
		'DR Congo' => 'COD',
		'Denmark' => 'DEN',
		'Djibouti' => 'DJI',
		'Dominica' => 'DMA',
		'Dominican Republic' => 'DOM',
		'Ecuador' => 'ECU',
		'Egypt' => 'EGY',
		'El Salvador' => 'ESA',
		'Eritrea' => 'ERI',
		'Estonia' => 'EST',
		'Ethiopia' => 'ETH',
		'Fiji' => 'FIJ',
		'Finland' => 'FIN',
		'France' => 'FRA',
		'Gabon' => 'GAB',
		'Gambia' => 'GAM',
		'Georgia' => 'GEO',
		'Germany' => 'GER',
		'Ghana' => 'GHA',
		'Greece' => 'GRE',
		'Grenada' => 'GRN',
		'Guam' => 'GUM',
		'Guatemala' => 'GUA',
		'Guinea' => 'GUI',
		'Guinea-Bissau' => 'GBS',
		'Guyana' => 'GUY',
		'Haiti' => 'HAI',
		'Honduras' => 'HON',
		'Hong Kong' => 'HKG',
		'Hungary' => 'HUN',
		'Iceland' => 'ISL',
		'India' => 'IND',
		'Indonesia' => 'INA',
		'Iran' => 'IRI',
		'Iraq' => 'IRQ',
		'Ireland' => 'IRL',
		'Israel' => 'ISR',
		'Italy' => 'ITA',
		'Ivory Coast' => 'CIV',
		'Jamaica' => 'JAM',
		'Japan' => 'JPN',
		'Jordan' => 'JOR',
		'Kazakhstan' => 'KAZ',
		'Kenya' => 'KEN',
		'Kiribati' => 'KIR',
		'Korea' => 'KOR',
		'Kuwait' => 'KUW',
		'Kyrgyzstan' => 'KGZ',
		'Laos' => 'LAO',
		'Latvia' => 'LAT',
		'Lebanon' => 'LIB',
		'Lesotho' => 'LES',
		'Liberia' => 'LBR',
		'Libya' => 'LBA',
		'Liechtenstein' => 'LIE',
		'Lithuania' => 'LTU',
		'Luxembourg' => 'LUX',
		'Macedonia' => 'MKD',
		'Malawi' => 'MAW',
		'Malaysia' => 'MAS',
		'Mali' => 'MLI',
		'Malta' => 'MLT',
		'Mauritania' => 'MTN',
		'Mauritius' => 'MRI',
		'Mexico' => 'MEX',
		'Moldova' => 'MDA',
		'Monaco' => 'MON',
		'Mongolia' => 'MGL',
		'Montenegro' => 'MNE',
		'Morocco' => 'MAR',
		'Mozambique' => 'MOZ',
		'Myanmar' => 'MYA',
		'Namibia' => 'NAM',
		'Nauru' => 'NRU',
		'Nepal' => 'NEP',
		'Netherlands' => 'NED',
		'New Zealand' => 'NZL',
		'Nicaragua' => 'NCA',
		'Niger' => 'NIG',
		'Nigeria' => 'NGR',
		'Norway' => 'NOR',
		'Oman' => 'OMA',
		'Other Countries' => 'OTH',
		'Pakistan' => 'PAK',
		'Palau' => 'PLW',
		'Palestine' => 'PLE',
		'Panama' => 'PAN',
		'Paraguay' => 'PAR',
		'Peru' => 'PER',
		'Philippines' => 'PHI',
		'Poland' => 'POL',
		'Portugal' => 'POR',
		'Puerto Rico' => 'PUR',
		'Qatar' => 'QAT',
		'Romania' => 'ROM',  // actually ROU
		'Russia' => 'RUS',
		'Rwanda' => 'RWA',
		'Samoa' => 'SAM',
		'San Marino' => 'SMR',
		'Saudi Arabia' => 'KSA',
		'Senegal' => 'SEN',
		'Serbia' => 'SCG',  // actually SRB
		'Sierra Leone' => 'SLE',
		'Singapore' => 'SIN',
		'Slovakia' => 'SVK',
		'Slovenia' => 'SLO',
		'Somalia' => 'SOM',
		'South Africa' => 'RSA',
		'Spain' => 'ESP',
		'Sri Lanka' => 'SRI',
		'Sudan' => 'SUD',
		'Suriname' => 'SUR',
		'Swaziland' => 'SWZ',
		'Sweden' => 'SWE',
		'Switzerland' => 'SUI',
		'Syria' => 'SYR',
		'Taiwan' => 'TWN',
		'Tajikistan' => 'TJK',
		'Tanzania' => 'TAN',
		'Thailand' => 'THA',
		'Togo' => 'TOG',
		'Tonga' => 'TGA',
		'Trinidad and Tobago' => 'TRI',
		'Tunisia' => 'TUN',
		'Turkey' => 'TUR',
		'Turkmenistan' => 'TKM',
		'Tuvalu' => 'TUV',
		'Uganda' => 'UGA',
		'Ukraine' => 'UKR',
		'United Arab Emirates' => 'UAE',
		'United Kingdom' => 'GBR',
		'United States of America' => 'USA',
		'Uruguay' => 'URU',
		'Uzbekistan' => 'UZB',
		'Vanuatu' => 'VAN',
		'Venezuela' => 'VEN',
		'Vietnam' => 'VIE',
		'Yemen' => 'YEM',
		'Zambia' => 'ZAM',
		'Zimbabwe' => 'ZIM',
	);

	if (array_key_exists($country, $nations)) {
		$nation = $nations[$country];
	} else {
		$nation = "OTH";
	}
	return $nation;
}  // end mapCountry

/*
 * Map truncated country names to 3-letter Nation abbreviations
 */
function mapAbbrev($abbrev) {

	$nations = array(
		'Afg' => 'AFG',
		'Alb' => 'ALB',
		'Alg' => 'ALG',
		'And' => 'AND',
		'Ang' => 'ANG',
		'Arg' => 'ARG',
		'Arm' => 'ARM',
		'Aru' => 'ARU',
		'Aus' => 'AUT',
		'Aze' => 'AZE',
		'Bah' => 'BAH',
		'Ban' => 'BAN',
		'Bar' => 'BAR',
		'Bel' => 'BEL',
		'Ben' => 'BEN',
		'Ber' => 'BER',
		'Bhu' => 'BHU',
		'Bol' => 'BOL',
		'Bos' => 'BIH',
		'Bot' => 'BOT',
		'Bra' => 'BRA',
		'Bru' => 'BRU',
		'Bul' => 'BUL',
		'Bur' => 'BDI',
		'Cam' => 'CAM',
		'Can' => 'CAN',
		'Cap' => 'CPV',
		'Cen' => 'CAF',
		'Cha' => 'CHA',
		'Chi' => 'CHN',
		'Col' => 'COL',
		'Con' => 'CGO',
		'Cos' => 'CRC',
		'Cro' => 'CRO',
		'Cub' => 'CUB',
		'Cyp' => 'CYP',
		'Cze' => 'CZE',
		'DR ' => 'COD',
		'Den' => 'DEN',
		'Dji' => 'DJI',
		'Dom' => 'DOM',
		'Ecu' => 'ECU',
		'Egy' => 'EGY',
		'El ' => 'ESA',
		'Eri' => 'ERI',
		'Est' => 'EST',
		'Eth' => 'ETH',
		'Fij' => 'FIJ',
		'Fin' => 'FIN',
		'Fra' => 'FRA',
		'Gab' => 'GAB',
		'Gam' => 'GAM',
		'Geo' => 'GEO',
		'Ger' => 'GER',
		'Gha' => 'GHA',
		'Gre' => 'GRE',
		'Gua' => 'GUA',
		'Gui' => 'GUI',
		'Guy' => 'GUY',
		'Hai' => 'HAI',
		'Hon' => 'HKG',
		'Hun' => 'HUN',
		'Ice' => 'ISL',
		'Ind' => 'IND',
		'Ira' => 'IRI',
		'Ire' => 'IRL',
		'Isr' => 'ISR',
		'Ita' => 'ITA',
		'Ivo' => 'CIV',
		'Jam' => 'JAM',
		'Jap' => 'JPN',
		'Jor' => 'JOR',
		'Kaz' => 'KAZ',
		'Ken' => 'KEN',
		'Kir' => 'KIR',
		'Kor' => 'KOR',
		'Kuw' => 'KUW',
		'Kyr' => 'KGZ',
		'Lao' => 'LAO',
		'Lat' => 'LAT',
		'Leb' => 'LIB',
		'Les' => 'LES',
		'Lib' => 'LBA',
		'Lie' => 'LIE',
		'Lit' => 'LTU',
		'Lux' => 'LUX',
		'Mac' => 'MKD',
		'Mal' => 'MAS',
		'Mau' => 'MTN',
		'Mex' => 'MEX',
		'Mol' => 'MDA',
		'Mon' => 'MNE',
		'Mor' => 'MAR',
		'Moz' => 'MOZ',
		'Mya' => 'MYA',
		'Nam' => 'NAM',
		'Nau' => 'NRU',
		'Nep' => 'NEP',
		'Net' => 'NED',
		'New' => 'NZL',
		'Nic' => 'NCA',
		'Nig' => 'NGR',
		'Nor' => 'NOR',
		'Oma' => 'OMA',
		'Oth' => 'OTH',
		'Pak' => 'PAK',
		'Pal' => 'PLE',
		'Pan' => 'PAN',
		'Par' => 'PAR',
		'Per' => 'PER',
		'Phi' => 'PHI',
		'Pol' => 'POL',
		'Por' => 'POR',
		'Pue' => 'PUR',
		'Qat' => 'QAT',
		'Rom' => 'ROM',
		'Rus' => 'RUS',
		'Rwa' => 'RWA',
		'Sam' => 'SAM',
		'San' => 'SMR',
		'Sau' => 'KSA',
		'Sen' => 'SEN',
		'Ser' => 'SCG',
		'Sie' => 'SLE',
		'Sin' => 'SIN',
		'Slo' => 'SVK',
		'Som' => 'SOM',
		'Sou' => 'RSA',
		'Spa' => 'ESP',
		'Sri' => 'SRI',
		'Sud' => 'SUD',
		'Sur' => 'SUR',
		'Swa' => 'SWZ',
		'Swe' => 'SWE',
		'Swi' => 'SUI',
		'Syr' => 'SYR',
		'Tai' => 'TWN',
		'Taj' => 'TJK',
		'Tan' => 'TAN',
		'Tha' => 'THA',
		'Tog' => 'TOG',
		'Ton' => 'TGA',
		'Tri' => 'TRI',
		'Tun' => 'TUN',
		'Tur' => 'TUR',
		'Tuv' => 'TUV',
		'Uga' => 'UGA',
		'Ukr' => 'UKR',
		'Uni' => 'USA',
		'Uru' => 'URU',
		'Uzb' => 'UZB',
		'Van' => 'VAN',
		'Ven' => 'VEN',
		'Vie' => 'VIE',
		'Yem' => 'YEM',
		'Zam' => 'ZAM',
		'Zim' => 'ZIM',
	);

	if (array_key_exists($abbrev, $nations)) {
		$nation = $nations[$abbrev];
	} else {
		$nation = "OTH";
	}
	return $nation;
}  // end mapAbbrev
?>
