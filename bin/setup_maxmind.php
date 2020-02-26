#!/usr/bin/env php
<?php
		$data_parse = [
			'Risk Score' => [
				'riskscore' => ['title' => 'Risk Score', 'message' => 'New fraud score representing the estimated probability that the order is fraud, based off of analysis of past transactions.'],
				'score' => ['title' => 'Fraud Score', 'message' => 'Overall fraud score based on outputs listed above. This is the original fraud score, and is based on a simple formula. It has been replaced with riskScore (see above), but is kept for backwards compatibility.'],
				'explanation' => ['title' => 'Explanation', 'message' => 'A brief explanation of the score, detailing what factors contributed to it, according to our formula.']
			],
			'Geographical IP Address Location Checking ' => [
				'countrymatch' => ['title' => 'Country Match', 'message' => 'Whether country of IP address matches billing address country (mismatch = higher risk).'],
				'countrycode' => ['title' => 'IP Country Code', 'message' => 'Country Code of the IP address.'],
				'highriskcountry' => ['title' => 'High Risk Country', 'message' => 'Whether IP address or billing address country is in Egypt, Ghana, Indonesia, Lebanon, Macedonia, Morocco, Nigeria, Pakistan, Romania, Serbia and Montenegro, Ukraine, or Vietnam.'],
				'distance' => ['title' => 'IP to Billing Distance', 'message' => 'Distance from IP address to Billing Location in kilometers (large distance = higher risk).'],
				'ip_region' => ['title' => 'IP State or Province', 'message' => 'Estimated State/Region of the IP address, ISO-3166-2/FIPS 10-4 code.'],
				'ip_city' => ['title' => 'IP City', 'message' => 'Estimated City of the IP address.'],
				'ip_latitude'			=> ['title' => 'IP Latitude', 'message' => 'Estimated Latitude of the IP address.'],
				'ip_longitude'			=> ['title' => 'IP Longitude', 'message' => 'Estimated Longitude of the IP address.'],
				'ip_isp'				=> ['title' => 'IP ISP', 'message' => 'ISP of the IP address.'],
				'ip_org'				=> ['title' => 'IP Orgainization', 'message' => 'Organization of the IP address.']
			],
			'Proxy Detection ' => [
				'anonymousproxy'		=> ['title' => 'Anonymous Proxy?', 'message' => 'Whether IP address is Anonymous Proxy (anonymous proxy = very high risk).'],
				'proxyscore'			=> ['title' => 'Proxy Score', 'message' => 'Likelihood of IP Address being an Open Proxy.'],
				'istransproxy'			=> ['title' => 'Transaction Proxy', 'message' => 'Whether IP address is in our database of known transparent proxy servers, returned if forwardedIP is passed as an input.']
			],
			'E-mail and Login Checks' => [
				'freemail'				=> ['title' => 'Free E-mail Provider?', 'message' => 'Whether e-mail is from free e-mail provider (free e-mail = higher risk).'],
				'carderemail'			=> ['title' => 'Carder Email', 'message' => 'Whether e-mail is in database of high risk e-mails.'],
				'highriskusername'		=> ['title' => 'High Risk Username', 'message' => 'Whether usernameMD5 input is in database of high risk usernames. Only returned if usernameMD5 is included in inputs.'],
				'highriskpassword'		=> ['title' => 'High Risk Password', 'message' => 'Whether passwordMD5 input is in database of high risk passwords. Only returned if passwordMD5 is included in inputs.']
			],
			'Issuing Bank BIN Number Checks' => [
				'binmatch'				=> ['title' => 'BIN Match', 'message' => 'Whether country of issuing bank based on BIN number matches billing address country.*'],
				'bincountry'			=> ['title' => 'BIN Country Code', 'message' => 'Country Code of the bank which issued the credit card based on BIN number.*'],
				'binnamematch'			=> ['title' => 'BIN Name Match', 'message' => 'Whether name of issuing bank matches inputted binName. A return value of Yes provides a positive indication that cardholder is in possession of credit card.*'],
				'binname'				=> ['title' => 'BIN Name', 'message' => 'Name of the bank which issued the credit card based on BIN number*. Available for approximately 96% of BIN numbers.'],
				'binphonematch'			=> ['title' => 'BIN Phone Match', 'message' => 'Whether customer service phone number matches inputed binPhone. A return value of Yes provides a positive indication that cardholder is in possession of credit card.*'],
				'binphone'				=> ['title' => 'BIN Phone', 'message' => 'Customer service phone number listed on back of credit card*. Available for approximately 75% of BIN numbers. In some cases phone number returned may be outdated.']
			],
			'Address and Phone Number Checks' => [
				'custphoneinbillingloc'	=> ['title' => 'Phone In Billing Location?', 'message' => 'Whether the customer phone number is in the billing zip code. A return value of Yes provides a positive indication that the phone number listed belongs to the cardholder. A return value of No indicates that the phone number may be in a different area, or may not be listed in our database. NotFound is returned when the phone number prefix cannot be found in our database at all. Currently we only support US Phone numbers.'],
				'shipforward'			=> ['title' => 'Shipping Forward', 'message' => 'Whether shipping address is in database of known mail drops.'],
				'citypostalmatch'		=> ['title' => 'City Postal Code Match', 'message' => 'Whether billing city and state match zipcode. Currently available for US addresses only, returns empty string outside the US.'],
				'shipcitypostalmatch'	=> ['title' => 'Shipping City Postal Match', 'message' => 'Whether shipping city and state match zipcode. Currently available for US addresses only, returns empty string outside the US.']
			],
			'Misc Data' => [
				'errors'				=> ['title' => 'Errors', 'message' => 'Errors reported during lookup.'],
				'maxmindid'				=> ['title' => 'FraudScore ID', 'message' => 'Unique id assigned to your FraudScore lookup.'],
				'remaining'				=> ['title' => 'Remaining Credits', 'message' => 'Credits remaining on your FraudScore account.']
			]
		];
	$json = json_decode(file_get_contents('../../include/config/maxmind_output.json'));
	$rows = [];
	$sql = "create table maxmind_output (
    `account_id` int(11) NOT NULL DEFAULT 0 COMMENT 'Account ID this lookup was for.',
";
	foreach ($json as $idx => $data) {
		if ($data[1] == '' && $data[2] == '' && $data[3] == '') {
			if (isset($cat_rows)) {
				$rows[$category] = $cat_rows;
			}
			$lines = explode("\n", $data[0]);
			$category = $lines[0];
			$cat_rows = [];
			if (count($lines) > 1) {
				$cat_rows['_desc'] = str_replace($category.PHP_EOL, '', $data[0]);
			}
			continue;
		}
		if (isset($cat_rows)) {
			$cat_rows[$data[0]] = [
				'title' => trim(ucwords(str_replace(['_', 'maxmind', 'queries', 'bin'], [' ', 'maxmind ', 'queries ', 'BIN '], $data[0]))),
				'desc' => $data[2],
				'type' => $data[1],
				'api_ver' => $data[3]
			];
			foreach ($data_parse as $cat => $catdata) {
				foreach ($catdata as $field => $values) {
					if ($data[0] == $field) {
						$cat_rows[$data[0]]['title'] = $values['title'];
					}
				}
			}
			$sql .= '    `' .strtolower($data[0])."` varchar(255) DEFAULT NULL COMMENT '".@mysql_escape_string(str_replace("\n", ' ', $cat_rows[$data[0]]['desc']))."',\n";
		}
	}
	$sql .= "    UNIQUE KEY `maxmindid` (`maxmindid`),
    KEY `account_id` (`account_id`)
) ENGINE=InnoDB;\n";
	if (count($cat_rows) > 0) {
		$rows[$category] = $cat_rows;
	}
	file_put_contents('../../include/config/maxmind_output_fields.json', json_encode($rows, JSON_PRETTY_PRINT));
$sql .= "
ALTER TABLE maxmind_output
  DROP INDEX account_id,
  CHANGE COLUMN maxmindid maxmindid CHAR(8) DEFAULT NULL COMMENT 'This is a unique eight character string identifying this minFraud request. Please use this ID in bug reports or support requests to MaxMind so that we can easily identify a particular request.' AFTER account_id,
  CHANGE COLUMN riskscore riskscore FLOAT DEFAULT NULL COMMENT 'This field contains the risk score, from 0.01 to 100. A higher score indicates a higher risk of fraud. For example, a score of 20 indicates a 20% chance that a transaction is fraudulent. We never return a risk score of 0, since all transactions have the possibility of being fraudulent.' AFTER maxmindid,
  CHANGE COLUMN score score FLOAT DEFAULT NULL COMMENT 'This field has been deprecated, is not supported, and is no longer present in API version 1.3.' AFTER riskscore,
  CHANGE COLUMN explanation explanation TEXT DEFAULT NULL COMMENT 'This field has been deprecated, is not supported, and is no longer present in API version 1.3. This is a brief explanation of the score (not the riskScore).' AFTER score,
  CHANGE COLUMN countrymatch countrymatch ENUM('','No','Yes') NOT NULL COMMENT 'This field can be either Yes or No. It indicates whether the country of the IP address matched the billing address country. A mismatch indicates a higher risk of fraud. If no country input was provided, this field will be left blank.' AFTER explanation,
  CHANGE COLUMN highriskcountry highriskcountry ENUM('','No','Yes') NOT NULL COMMENT 'This field can be either Yes or No. The field will be set to ''Yes'' if either the billing country or the IP country are associated with a high risk of fraud; otherwise, it will be set to ''No''.' AFTER countrymatch,
  CHANGE COLUMN distance distance INT(7) DEFAULT NULL COMMENT 'The distance from the IP address location to the billing location, in kilometers. A higher distance indicates a higher risk of fraud.' AFTER highriskcountry,
  CHANGE COLUMN ip_accuracyradius ip_accuracyradius INT(6) DEFAULT NULL COMMENT 'The radius in kilometers around the specified location where the IP address is likely to be.' AFTER distance,
  CHANGE COLUMN ip_city ip_city VARCHAR(60) DEFAULT NULL COMMENT 'The city or town name associated with the IP address. See our list of cities to see all the possible return values. This list is updated on a regular basis.' AFTER ip_accuracyradius,
  CHANGE COLUMN ip_region ip_region CHAR(2) DEFAULT NULL COMMENT 'A two character ISO-3166-2 or FIPS 10-4 code for the state/region associated with the IP address. For the US and Canada, we return an ISO-3166-2 code. In addition to the standard ISO codes, we may also return one of the following:  	AA - Armed Forces America 	AE - Armed Forces Europe 	AP - Armed Forces Pacific  We return a FIPS code for all other countries. We provide a CSV file which maps our region codes to region names. The columns are ISO country code, region code (FIPS or ISO), and the region name.' AFTER ip_city,
  CHANGE COLUMN ip_regionname ip_regionname VARCHAR(100) DEFAULT NULL COMMENT 'The region name associated with the IP address.' AFTER ip_region,
  CHANGE COLUMN ip_postalcode ip_postalcode VARCHAR(30) DEFAULT NULL COMMENT 'The postal code associated with the IP address. These are available for some IP addresses in Australia, Canada, France, Germany, Italy, Spain, Switzerland, United Kingdom, and the US. We return the first 3 characters for Canadian postal codes. We return the the first 2-4 characters (outward code) for postal codes in the United Kingdom.' AFTER ip_regionname,
  CHANGE COLUMN ip_metrocode ip_metrocode VARCHAR(30) DEFAULT NULL COMMENT 'The metro code associated with the IP address. These are only available for IP addresses in the US. MaxMind returns the same metro codes as the Google AdWords API.' AFTER ip_postalcode,
  CHANGE COLUMN ip_areacode ip_areacode VARCHAR(30) DEFAULT NULL COMMENT 'The telephone area code associated with the IP address. These are only available for IP addresses in the US. This output is deprecated, and may not reflect newer area codes.' AFTER ip_metrocode,
  CHANGE COLUMN countrycode countrycode CHAR(2) DEFAULT NULL COMMENT 'A two-character ISO 3166-1 country code for the country associated with the IP address. In addition to the standard codes, we may also return one of the following:  	A1 - an anonymous proxy. 	A2 - a satellite provider. 	EU - an IP in a block used by multiple European countries. 	AP - an IP in a block used by multiple Asia/Pacific region countries.  The US country code is returned for IP addresses associated with overseas US military bases.' AFTER ip_areacode,
  CHANGE COLUMN ip_countryname ip_countryname VARCHAR(100) DEFAULT NULL COMMENT 'The country name associated with the IP address.' AFTER countrycode,
  CHANGE COLUMN ip_continentcode ip_continentcode CHAR(2) DEFAULT NULL COMMENT 'A two-character code for the continent associated with the IP address. The possible codes are:  	AF - Africa         AN - Antarctica 	AS - Asia 	EU - Europe 	NA - North America 	OC - Oceania 	SA - South America' AFTER ip_countryname,
  CHANGE COLUMN ip_latitude ip_latitude VARCHAR(20) DEFAULT NULL COMMENT 'The latitude associated with the IP address. The latitude and longitude are near the center of the most granular location value returned: postal code, city, region, or country.' AFTER ip_continentcode,
  CHANGE COLUMN ip_longitude ip_longitude VARCHAR(20) DEFAULT NULL COMMENT 'The longitude associated with the IP address.' AFTER ip_latitude,
  CHANGE COLUMN ip_timezone ip_timezone VARCHAR(50) DEFAULT NULL COMMENT 'The time zone associated with the IP address. Time zone names are taken from the IANA time zone database. See the list of possible values.' AFTER ip_longitude,
  CHANGE COLUMN ip_usertype ip_usertype VARCHAR(50) DEFAULT NULL COMMENT 'The user type associated with the IP address. This will be one of the following values.  	business 	cafe 	cellular 	college 	contentDeliveryNetwork 	government 	hosting 	library 	military 	residential 	router 	school 	searchEngineSpider 	traveler' AFTER ip_asnum,
  CHANGE COLUMN ip_netspeedcell ip_netspeedcell VARCHAR(20) DEFAULT NULL COMMENT 'The connection type associated with the IP address. This can be one of the following values:  	Dialup 	Cable/DSL 	Corporate 	Cellular' AFTER ip_usertype,
  CHANGE COLUMN ip_isp ip_isp VARCHAR(100) DEFAULT NULL COMMENT 'The name of the ISP associated with the IP address.' AFTER ip_domain,
  CHANGE COLUMN ip_cityconf ip_cityconf INT(11) DEFAULT NULL COMMENT 'A value from 0-100 representing our confidence that the city is correct.' AFTER ip_org,
  CHANGE COLUMN ip_regionconf ip_regionconf INT(11) DEFAULT NULL COMMENT 'A value from 0-100 representing our confidence that the region is correct.' AFTER ip_cityconf,
  CHANGE COLUMN ip_postalconf ip_postalconf INT(11) DEFAULT NULL COMMENT 'A value from 0-100 representing our confidence that the postal code is correct.' AFTER ip_regionconf,
  CHANGE COLUMN ip_countryconf ip_countryconf INT(11) DEFAULT NULL COMMENT 'A value from 0-100 representing our confidence that the country is correct.' AFTER ip_postalconf,
  CHANGE COLUMN anonymousproxy anonymousproxy ENUM('','No','Yes') NOT NULL COMMENT 'This field can be either Yes or No. It indicates whether the user''s IP address is an anonymous proxy. An anonymous proxy indicates a high risk of fraud.' AFTER ip_countryconf,
  CHANGE COLUMN ip_corporateproxy ip_corporateproxy ENUM('','No','Yes') DEFAULT NULL COMMENT 'This field can be either Yes or No. It indicates whether the user''s IP address is a known corporate proxy.' AFTER proxyscore,
  CHANGE COLUMN freemail freemail ENUM('','No','Yes') NOT NULL COMMENT 'This field can be either Yes or No. It indicates whether the user''s email address is from a free email provider. Note that this will be set to ''No'' if no domain is passed in the input.' AFTER ip_corporateproxy,
  CHANGE COLUMN carderemail carderemail ENUM('No','Yes') DEFAULT NULL COMMENT 'This field can be either Yes or No. It indicates whether the user''s email address is in a database of known high risk emails.' AFTER freemail,
  CHANGE COLUMN highriskusername highriskusername ENUM('','No','Yes') DEFAULT NULL COMMENT 'This field has been deprecated. It will be removed in a future release.' AFTER carderemail,
  CHANGE COLUMN highriskpassword highriskpassword ENUM('','No','Yes') DEFAULT NULL COMMENT 'This field has been deprecated. It will be removed in a future release.' AFTER highriskusername,
  CHANGE COLUMN binmatch binmatch ENUM('','NA','No','NotFound','Yes') DEFAULT NULL COMMENT 'This field can be either Yes, No, NotFound, or NA.  It indicates whether the country of the billing address matches the country of the majority of customers using that BIN. In cases where the location of customers is highly mixed, the match is to the country of the bank issuing the card. The NotFound response means that we could not find a match for the provided bin input field. The NA response means that you did not provide a bin in the input.' AFTER highriskpassword,
  CHANGE COLUMN binnamematch binnamematch VARCHAR(10) DEFAULT NULL COMMENT 'This field can be either Yes, No, NotFound, or NA It indicates whether the credit card''s bank name matches the binName input field. The NotFound response means that we could not find a match for the provided bin input field. The NA response means that you did not provide a binName in the input.' AFTER bincountry,
  CHANGE COLUMN binphonematch binphonematch VARCHAR(20) DEFAULT NULL COMMENT 'This field can be either Yes, No, NotFound, or NA It indicates whether the credit card''s bank name matches the binPhone input field. The NotFound response means that we could not find a match for the provided bin input field. The NA response means that you did not provide a binPhone in the input.' AFTER binname,
  CHANGE COLUMN binphone binphone VARCHAR(100) DEFAULT NULL COMMENT 'The phone number of the bank which issued the credit card. This is available for approximately 75% of all BIN numbers. In some cases the phone number we return may be out of date. This field is only returned for premium service level queries.' AFTER binphonematch,
  CHANGE COLUMN prepaid prepaid VARCHAR(10) DEFAULT NULL COMMENT 'This field can be either Yes or No. This indicates whether the card is a prepaid or gift card. If no bin input was provided, this field will be left blank.' AFTER binphone,
  CHANGE COLUMN custphoneinbillingloc custphoneinbillingloc ENUM('','No','NotFound','Yes') DEFAULT NULL COMMENT 'This field can be either Yes, No, or NotFound. This indicates whether the customer''s phone number is in the billing address''s postal code. The No response means that phone number may be in a different area, or it is not listed in our database. The NotFound response indicates that the phone number prefix is not in our database. Currently we only return information about US phone numbers. For all other countries, this field will be left blank.' AFTER prepaid,
  CHANGE COLUMN shipforward shipforward ENUM('NA','No','Yes') DEFAULT NULL COMMENT 'This field can be either Yes, No, or NA. This indicates whether the customer''s shipping address is in a database of known high risk shipping addresses. The NA response indicates that we could not parse the shipping address.' AFTER custphoneinbillingloc,
  CHANGE COLUMN citypostalmatch citypostalmatch ENUM('','No','Yes') NOT NULL COMMENT 'This field can be either Yes or No. This indicates whether the customer''s billing city and state match their postal code. This is currently only available for US addresses. For addresses outside the US, this field is empty.' AFTER shipforward,
  CHANGE COLUMN shipcitypostalmatch shipcitypostalmatch ENUM('','No','Yes') NOT NULL COMMENT 'This field can be either Yes or No. This indicates whether the customer''s shipping city and state match their postal code. This is currently only available for US addresses. For addresses outside the US, this field is empty.' AFTER citypostalmatch,
  CHANGE COLUMN queriesremaining queriesremaining INT(5) DEFAULT NULL COMMENT 'This is the number of minFraud queries remaining in your account.' AFTER shipcitypostalmatch,
  CHANGE COLUMN minfraud_version minfraud_version VARCHAR(10) DEFAULT NULL COMMENT 'This returns the API version that was used for this request.',
  CHANGE COLUMN service_level service_level VARCHAR(20) DEFAULT NULL COMMENT 'This returns the service level that was used for this request. This can be either standard or premium.',
  CHANGE COLUMN err err VARCHAR(50) DEFAULT NULL COMMENT 'If there was an error or warning with this request, this field contains an error code string. The possible error codes are:  	INVALID_LICENSE_KEY 	IP_REQUIRED 	IP_NOT_FOUND - this error will be returned if the IP address is not valid, if it is not public, or if it is not in our GeoIP database. 	MAX_REQUESTS_REACHED - this is returned when your account is out of queries.     LICENSE_REQUIRED - this is returned if you do not provide a license key at all.  The possible warning codes are:  	COUNTRY_NOT_FOUND 	CITY_NOT_FOUND 	CITY_REQUIRED         INVALID_EMAIL_MD5 	POSTAL_CODE_REQUIRED 	POSTAL_CODE_NOT_FOUND';

ALTER TABLE maxmind_output
  ADD CONSTRAINT FK_maxmind_output_accounts_account_id FOREIGN KEY (account_id)
    REFERENCES accounts(account_id) ON DELETE CASCADE ON UPDATE CASCADE;
";
	file_put_contents('maxmind_table.sql', $sql);
//	print_r($rows);
	exit;

		$desc_array = ['riskscore' => '(Decimal from 0 to 100)', 'score' => '(Decimal from 0 to 10)', 'proxyscore' => '(Decimal from 0 to 10)'];

		$count = 1000;
		foreach ($data_parse as $key => $val) {
			$output_results .= "<strong>{$key}:</strong><br /><table>";
			foreach ($val as $k => $v) {
				if ($results[$k] == '') {
					continue;
				}
				$output_results .= '<tr><td style="text-align: right; vertical-align: top; width: 150px;">' .$this->help($v[title], $v[message], $count++)."</td><td>{$results[$k]} ".(isset($desc_array[$k]) ?" {$desc_array[$k]}": ''). '</td></tr>';
			}
			$output_results .= '</table><br />';
		}
