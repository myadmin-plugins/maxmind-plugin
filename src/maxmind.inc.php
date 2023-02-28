<?php
/**
 * MaxMind Fraud Stuff
 *
 * API Downloaded @ http://www.maxmind.com/download/ccfd/
 * MaxMind Fields @ http://www.maxmind.com/app/ccv
 * Fields and Descriptions @ http://www.maxmind.com/app/fraud-detection-manual
 * Set your MaxMind Version @ http://www.maxmind.com/app/minfraud_version

 * Score Formula from http://www.maxmind.com/app/ccfd_formula
 * The "score" is calculated as follows:
 * score = 	2.5 * isFreeEmail +
 * 2.5 * countryDoesntMatch +
 * 5 * isAnonymousProxy +
 * 5 * highRiskCountry +
 * 10 * min(distance,5000) / maxEarthArc +
 * 2 * binDoesntMatch +
 * 1 * binNameDoesntMatch +
 * 5 * carderEmail +
 * 5 * highRiskUsername +
 * 5 * highRiskPassword +
 * 5 * shipForward +
 * 2.5 * proxyScore
 * Note this formula is capped at 10. maxEarthArc is defined as 20037 kilometers.
 *
 * @author Joe Huss <detain@interserver.net>
 * @copyright 2019
 * @package MyAdmin
 * @category General
 */

use ForceUTF8\Encoding;

require_once __DIR__.'/../../../workerman/statistics/Applications/Statistics/Clients/StatisticClient.php';

/**
 * decodes stored maxmind data
 *
 * @param string $encoded_maxmind encoded maxmind string
 * @return array|false the decoded maxmind data in an associative array, or false if there was a problem
 */
function maxmind_decode($encoded_maxmind)
{
    return myadmin_unstringify($encoded_maxmind);
}

/**
 * This handles fraud protection
 */
function get_maxmind_field_descriptions()
{
    $fields = [
        'distance' => 'Distance from IP address location to billing location in kilometers (large distance = higher risk).',
        'countryMatch' => 'Whether country of IP address matches billing address country (mismatch = higher risk).',
        'highRiskCountry' => 'Whether IP address or billing address country is in Ghana, Nigeria, or Vietnam.',
        'ip_city' => 'City or town name associated with IP address. ',
        'ip_region' => 'State/Region code associated with IP address. For US/Canada, ISO-3166-2 code for the state/province name, with the addition of AA, AE, and AP for Armed Forces America, Europe and Pacific. Outside of the US and Canada, FIPS 10-4 code. ',
        'ip_regionName' => 'Region name associated with IP address.',
        'countryCode' => 'ISO 3166 Country Code, with the addition of

		A1 for Anonymous Proxies. See proxyScore below for Open Proxy detection.
		A2 for Satellite Providers
		EU for Europe
		AP for Asia/Pacific Region
		In addition, we map overseas military bases to US
		',
        'ip_countryName' => 'Country name associated with IP address.',
        'ip_continentCode' => 'Continent code associated with IP address. ',
        'ip_latitude' => 'Latitude associated with IP address.',
        'ip_longitude' => 'Longitude associated with IP address.',
        'ip_postalCode' => 'Postal Code associated with IP address. For US, Zipcodes, for Canada, postal codes',
        'ip_metroCode' => 'Metro Code associated with IP address. Metropolitan Area (US Only)',
        'ip_areaCode' => 'Area Code associated with IP address. Three digit telephone prefix (US Only).',
        'ip_timeZone' => 'Time Zone associated with IP address.',
        'ip_asnum' => 'Autonomous system number associated with IP address.',
        'ip_userType' => 'User type associated with IP address.

		User type can have the following values:

		business
		cafe
		cellular
		college
		contentDeliveryNetwork
		government
		hosting
		library
		military
		residential
		router
		school
		searchEngineSpider
		traveler ',
        'ip_netSpeedCell' => 'Netspeed associated with IP address.

		Netspeed can have the following values:

		Dialup
		Cable/DSL
		Corporate
		Cellular ',
        'ip_domain' => 'Top/second level domain name associated with IP address.',
        'ip_isp' => 'ISP associated with IP address.',
        'ip_org' => '	Organization associated with IP address.',
        'ip_accuracyRadius' => 'The average distance between the actual location of the end user using the IP address and the location returned, in kilometers.',
        'ip_countryConf' => 'Value from 0-100 representing our confidence that the country location is correct.',
        'ip_regionConf' => 'Value from 0-100 representing our confidence that the region location is correct.',
        'ip_cityConf' => 'Value from 0-100 representing our confidence that the city location is correct.',
        'ip_postalConf' => 'Value from 0-100 representing our confidence that the postal code location is correct. ',
        'anonymousProxy' => 'Whether IP address is an Anonymous Proxy (anonymous proxy = very high risk)',
        'proxyScore' => 'Likelihood of IP Address being an Open Proxy',
        'isTransProxy' => 'Whether IP address is in our database of known transparent proxy servers, returned if forwardedIP is passed as an input.',
        'ip_corporateProxy' => 'Whether the IP is a Corporate Proxy',
        'freeMail' => 'Whether e-mail is from free e-mail provider (free e-mail = higher risk)',
        'carderEmail' => 'Whether e-mail is in database of high risk e-mails',
        'highRiskUsername' => 'Whether usernameMD5 input is in database of high risk usernames. Only returned if usernameMD5 is included in inputs.',
        'highRiskPassword' => 'Whether passwordMD5 input is in database of high risk passwords. Only returned if passwordMD5 is included in inputs.',
        'binMatch' => 'Whether country of issuing bank based on BIN number matches billing address country',
        'binCountry' => 'Country Code of the bank which issued the credit card based on BIN number',
        'binNameMatch' => 'Whether name of issuing bank matches inputted binName. A return value of Yes provides a positive indication that cardholder is in possession of credit card.',
        'binName' => 'Name of the bank which issued the credit card based on BIN number*. Available for approximately 96% of BIN numbers. ',
        'binPhoneMatch' => 'Whether customer service phone number matches inputed binPhone. A return value of Yes provides a positive indication that cardholder is in possession of credit card.',
        'binPhone' => 'Customer service phone number listed on back of credit card*. Available for approximately 75% of BIN numbers. In some cases phone number returned may be outdated.',
        'prepaid' => 'Whether the credit card is a prepaid or gift card.',
        'custPhoneInBillingLoc' => 'Whether the customer phone number is in the billing zip code. A return value of Yes provides a positive indication that the phone number listed belongs to the cardholder. A return value of No indicates that the phone number may be in a different area, or may not be listed in our database. NotFound is returned when the phone number prefix cannot be found in our database at all. Currently we only support US Phone numbers.',
        'shipForward' => 'Whether shipping address is in database of known mail drops',
        'cityPostalMatch' => 'Whether billing city and state match zipcode. Currently available for US addresses only, returns empty string outside the US. ',
        'shipCityPostalMatch' => 'Whether shipping city and state match zipcode. Currently available for US addresses only, returns empty string outside the US. ',
        'score' => 'Overall fraud score based on outputs listed above. This is the original fraud score, and is based on a simple formula. It has been replaced with riskScore (see below), and should no longer be used.',
        'explanation' => 'A brief explanation of the score, detailing what factors contributed to it, according to our formula. Please note this corresponds to the score, not the riskScore. More details This field has been deprecated and should no longer be used.',
        'riskScore' => 'New fraud score representing the estimated probability that the order is fraud. Requires an upgrade for clients who signed up before February 2007.',
        'queriesRemaining' => 'Number of queries remaining in your account, can be used to alert you when you may need to add more queries to your account',
        'maxmindID' => 'Unique identifier, used to reference transactions when reporting fraudulent activity back to MaxMind. This reporting will help MaxMind improve its service to you and will enable a planned feature to customize the fraud scoring formula based on your chargeback history.',
        'minfraud_version' => 'Returns minFraud version (1.0 - 1.3) used ',
        'service_level' => 'Returns service level used, can be:
		standard (standard service)
		premium (premium service)',
        'err' => 'Returns an error string with a warning message or a reason why the request failed.'
    ];
    return $fields;
}

/**
 * update_maxmind()
 * updates the MaxMind data for a given user.
 *
 * @param integer     $custid customer id
 * @param bool|string $ip       ip address to register with the query, or false to have it use session ip
 * @param bool|int $ccIdx       false for global account or idx of cc your updating info on
 * @return bool pretty much always returns true
 * @throws \Exception
 */
function update_maxmind($custid, $ip = false, $ccIdx = false)
{
    $custid = (int)$custid;
    $good = true;
    $new_data = [];
    $requiredFields = ['city', 'state', 'zip'];
    $fields = [
        'city' => 'city',
        'state' => 'region',
        'zip' => 'postal',
        'country' => 'country',
        'phone' => 'custPhone,'
    ];
    require_once __DIR__.'/../../../minfraud/http/src/CreditCardFraudDetection.php';
    $module = get_module_name('default');
    $db = get_module_db($module);
    $data = $GLOBALS['tf']->accounts->read($custid);
    function_requirements('parse_ccs');
    $ccs = parse_ccs($data);
    $ccData = $ccIdx !== false ? $ccs[$ccIdx] : $data;
    if ($ccIdx === false) {
        if (isset($data['cc_whitelist']) && $data['cc_whitelist'] == 1) { // skip maxmind if whitelisted
            return true;
        }
        $db->query("select * from access_log where access_owner={$custid} and access_login <= date_sub(now(), INTERVAL 1 YEAR) limit 1", __LINE__, __FILE__);
        if ($db->num_rows() > 0) { // skip maxmind if account is older than a year
            myadmin_log('maxmind', 'notice', "update_maxmind({$custid}, {$ip}) customer is older than a year old, Skipping Updating Maxmind", __LINE__, __FILE__);
            return true;
        }
    }
    $db->query("select account_passwd from accounts where account_id={$custid}", __LINE__, __FILE__);
    $db->next_record(MYSQL_ASSOC);
    $md5_passwd = $db->Record['account_passwd'];
    $request = [
        'requested_type' => 'premium',                                              // Which level (free, city, premium) of CCFD to use
        'emailMD5' => md5($data['account_lid']),                                    // CreditCardFraudDetection.php will take
        'usernameMD5' => md5($data['account_lid']),                                 // MD5 hash of e-mail address passed to emailMD5 if it detects '@' in the string
        'passwordMD5' => $md5_passwd,
        'sessionID' => $GLOBALS['tf']->session->sessionid,                          // Session ID
        'i' => $ip === false ? \MyAdmin\Session::get_client_ip() : $ip,
        'forwardedIP' => $ip === false ? \MyAdmin\Session::get_client_ip() : $ip,   // X-Forwarded-For or Client-IP HTTP Header
        'domain' => mb_substr($data['account_lid'], mb_strpos($data['account_lid'], '@') + 1),
        'license_key' => MAXMIND_LICENSE_KEY,
        'country' => 'US',
    ];
    foreach ($fields as $myField => $reqField)
        if (isset($ccData[$myField]) && trim($ccData[$myField]) != '')
            $request[$reqField] = $ccData[$myField];
        elseif (in_array($myField, $requiredFields))
            $good = false;
    if ($good === false) {
        myadmin_log('maxmind', 'notice', "update_maxmind({$custid}, {$ip}) Blank Required Fields - Disabling CC", __LINE__, __FILE__);
        $new_data['disable_cc'] = 1;
        $new_data['payment_method'] = 'paypal';
        $GLOBALS['tf']->accounts->update($custid, $new_data);
        return false;
    }
    if (isset($ccData['cc']) && $GLOBALS['tf']->decrypt($ccData['cc']) != '') {
        $request['bin'] = mb_substr($GLOBALS['tf']->decrypt($ccData['cc']), 0, 6); // bank identification number
    }
    myadmin_log('maxmind', 'info', "update_maxmind({$custid}, {$ip}) Called with ".json_encode($request), __LINE__, __FILE__);
    \StatisticClient::tick('MaxMind', 'MinFraudLookup');
    $ccfs = new CreditCardFraudDetection();
    $ccfs->isSecure = 1;
    $ccfs->timeout = 20; //set the time out to be five seconds
    // $ccfs->debug = 1; //uncomment to turn on debugging
    $ccfs->input($request); //next we pass the input hash to the server
    $ccfs->query();
    $response = $ccfs->output();
    if (is_array($response) & isset($response['riskScore'])) {
        \StatisticClient::report('MaxMind', 'MinFraudLookup', true, 0, '', STATISTICS_SERVER);
    } else {
        \StatisticClient::report('MaxMind', 'MinFraudLookup', false, 100, 'Invalid Response', STATISTICS_SERVER);
    }
    if (isset($ccData['country']) && in_array(strtolower($ccData['country']), ['br', 'tw'])) {
        if (isset($response['score']) && $response['score'] < MAXMIND_COUNTRY_SCORE_LIMIT)
            $response['score'] += MAXMIND_COUNTRY_SCORE_PENALTY;
        if (isset($response['riskScore']) && $response['riskScore'] <= MAXMIND_COUNTRY_RISKSCORE_LIMIT)
            $response['riskScore'] += MAXMIND_COUNTRY_SCORE_PENALTY;
    }
    if (isset($ccData['name'])) {
        $nparts = explode(' ', $ccData['name']);
        $first_name = strtolower($nparts[0]);
        include_once __DIR__.'/../../../../include/config/female_names.inc.php';
        if (in_array($first_name, $female_names)) {
            $response['female_name'] = 'yes';
            if (isset($response['score']))
                $response['score'] = trim($response['score']);
            if (MAXMIND_FEMALE_PENALTY_ENABLE == true && ((isset($response['score']) && $response['score'] < MAXMIND_FEMALE_SCORE_LIMIT) || $response['riskScore'] < MAXMIND_FEMALE_RISKSCORE_LIMIT)) {
                if (isset($response['score'])) {
                    $response['original_score'] = $response['score'];
                    $response['score'] += MAXMIND_FEMALE_SCORE_PENALTY;
                }
                $response['original_riskScore'] = $response['riskScore'];
                $response['riskScore'] += MAXMIND_FEMALE_RISKSCORE_PENALTY;
                if (isset($response['explanation']))
                    $response['explanation'] = trim($response['explanation']).' The user has a female first name, as per request, that means + '.MAXMIND_FEMALE_SCORE_PENALTY.' to fraud score';
            }
        } else
            $response['female_name'] = 'no';
    } else
        $response['female_name'] = 'no';
    $distance_penalty = floor(floatval($response['distance']) / 100);
    $distance_penalty = $distance_penalty > 50 ? 50 : $distance_penalty;
    myadmin_log('maxmind', 'info', 'Distance is '.$response['distance'].' or +'.$distance_penalty.' to riskScore (previously '.$response['riskScore'].')', __LINE__, __FILE__);
    $response['riskScore'] += $distance_penalty;
    $json = @json_encode($response);
    if (json_last_error() === JSON_ERROR_UTF8) { // Detect UTF8 encoding errors and attempt to automatically recover the data
        foreach ($response as $key => $value)
            $response[$key] = \ForceUTF8\Encoding::fixUTF8($value);
        $json = @json_encode($response);
    }
    $smarty = new TFSmarty();
    $smarty->assign('account_id', $custid);
    $smarty->assign('account_lid', $GLOBALS['tf']->accounts->cross_reference($custid));
    $smarty->assign('fraudArray', $response);
    $email = $smarty->fetch('email/admin/fraud.tpl');
    if ($ccIdx !== false) {
        $ccs[$ccIdx]['maxmind_riskscore'] = trim($response['riskScore']);
        if (isset($response['score']))
            $ccs[$ccIdx]['maxmind_score'] = trim($response['score']);
        $ccs[$ccIdx]['maxmind'] = $response;
        $new_data['ccs'] = json_encode($ccs);
    } else {
        $new_data['maxmind_riskscore'] = trim($response['riskScore']);
        if (isset($response['score']))
            $new_data['maxmind_score'] = trim($response['score']);
        $new_data['maxmind'] = $json;
    }
    myadmin_log('maxmind', 'notice', 'Maxmind '.(isset($response['score']) ? 'Score: '.$response['score'] : '').' riskScore: '.$response['riskScore'].' '.$json, __LINE__, __FILE__);
    if ((MAXMIND_CARDER_LOCK == true && isset($response['carderEmail']) && $response['carderEmail'] == 'Yes') || (isset($response['score']) && $response['score'] >= MAXMIND_SCORE_LOCK) || $response['riskScore'] >= MAXMIND_RISKSCORE_LOCK) {
        $db->query("select * from invoices where invoices_type=1 and invoices_paid=1 and invoices_custid={$custid} and invoices_date <= date_sub(now(), INTERVAL 1 DAY) limit 1", __LINE__, __FILE__);
        if ($db->num_rows() == 0) {
            myadmin_log('maxmind', 'warning', "update_maxmind({$custid}, {$ip}) Carder Email Or High Score From Customer {$custid} (".(isset($response['score']) ? 'Score: '.$response['score'] : '')." RiskScore {$response['riskScore']}), Disabling Account", __LINE__, __FILE__);
            function_requirements('disable_account');
            disable_account($custid);
        } else {
            myadmin_log('maxmind', 'warning', "update_maxmind({$custid}, {$ip}) Carder Email Or High Score From Customer {$custid} (".(isset($response['score']) ? 'Score: '.$response['score'] : '')." RiskScore {$response['riskScore']}), I would disable the account but they have old invoices", __LINE__, __FILE__);
        }
    }
    if ((isset($response['score']) && $response['score'] >= MAXMIND_SCORE_DISABLE_CC) || $response['riskScore'] >= MAXMIND_RISKSCORE_DISABLE_CC || $response['proxyScore'] >= MAXMIND_PROXYSCORE_DISABLE_CC) {
        myadmin_log('maxmind', 'warning', "update_maxmind({$custid}, {$ip}) ".(isset($response['score']) ? 'Score: '.$response['score']. ' >' . MAXMIND_SCORE_DISABLE_CC : ''). "   {$response['riskScore']} >" . MAXMIND_POSSIBLE_FRAUD_RISKSCORE.' Fraud Score, Disabling CC and Setting Payment Method To PayPal', __LINE__, __FILE__);
        $new_data['disable_cc'] = 1;
        $new_data['payment_method'] = 'paypal';
    }
    if (MAXMIND_NORESPONSE_DISABLE_CC == true && (!isset($response['score']) || trim($response['score']) == '') && trim($response['riskScore']) == '') {
        myadmin_log('maxmind', 'warning', "update_maxmind({$custid}, {$ip}) BLANK Maxmind Score and Risk % Score, Disabling CC and Setting Payment Method To PayPal", __LINE__, __FILE__);
        $new_data['disable_cc'] = 1;
        $new_data['payment_method'] = 'paypal';
        $subject = TITLE.' MISSING MaxMind Data - Possible Fraud';
        (new \MyAdmin\Mail())->adminMail($subject, $email, false, 'admin/fraud.tpl');
    }
    if ((isset($response['score']) && $response['score'] > MAXMIND_POSSIBLE_FRAUD_SCORE) || $response['riskScore'] >= MAXMIND_POSSIBLE_FRAUD_SCORE) {
        $subject = TITLE.' MaxMind Possible Fraud';
        (new \MyAdmin\Mail())->adminMail($subject, $email, false, 'admin/fraud.tpl');
        myadmin_log('maxmind', 'warning', "update_maxmind({$custid}, {$ip}) ".(isset($response['score']) ? $response['score']. ' >' . MAXMIND_POSSIBLE_FRAUD_SCORE : ''). " or  {$response['riskScore']} >" . MAXMIND_POSSIBLE_FRAUD_RISKSCORE.',  Emailing Possible Fraud', __LINE__, __FILE__);
    }
    if ($response['queriesRemaining'] <= MAXMIND_QUERIES_REMAINING) {
        $subject = 'MaxMind Down To '.$response['queriesRemaining'].' Queries Remaining';
        myadmin_log('maxmind', 'warning', $subject, __LINE__, __FILE__);
        (new \MyAdmin\Mail())->adminMail($subject, $subject, false, 'admin/maxmind_queries.tpl');
    }
    $GLOBALS['tf']->accounts->update($custid, $new_data);
    return true;
}

/**
 * update_maxmind_noaccount()
 * does a maxmind update on an array of data without actually checking or modifying an actual account.
 *
 * @param array $data the array of user data to get maxmind info for.
 * @return array the input $data but with the maxmind fields set
 */
function update_maxmind_noaccount($data)
{
    require_once __DIR__.'/../../../minfraud/http/src/CreditCardFraudDetection.php';
    //require_once ('include/accounts/maxmind/CreditCardFraudDetection.php');
    //myadmin_log('maxmind', 'debug', "update_maxmind_noaccount Called", __LINE__, __FILE__);
    $good = true;
    $fields = ['city', 'state', 'zip'];
    foreach ($fields as $field) {
        if (!isset($data[$field]) || trim($data[$field]) == '') {
            $good = false;
        }
    }
    if (!isset($data['country']) || trim($data['country']) == '') {
        $data['country'] = 'US';
    }
    if ($good === false) {
        myadmin_log('maxmind', 'notice', "update_maxmind({$custid}, {$ip}) Blank Required Fields - Disabling CC", __LINE__, __FILE__);
        $data['disable_cc'] = 1;
        $data['payment_method'] = 'paypal';
    } else {
        $ccfs = new CreditCardFraudDetection();
        $request['license_key'] = MAXMIND_LICENSE_KEY;
        $request['i'] = \MyAdmin\Session::get_client_ip();
        if (isset($data['city']) && trim($data['city']) != '') {
            $request['city'] = $data['city'];
        } // set the billing city
        if (isset($data['state']) && trim($data['state']) != '') {
            $request['region'] = $data['state'];
        } // set the billing state
        if (isset($data['zip']) && trim($data['zip']) != '') {
            $request['postal'] = $data['zip'];
        } // set the billing zip code
        if (isset($data['country']) && trim($data['country']) != '') {
            $request['country'] = $data['country'];
        } // set the billing country
        // Recommended fields
        $request['domain'] = mb_substr($data['lid'], mb_strpos($data['lid'], '@') + 1);
        if (isset($data['cc']) && $GLOBALS['tf']->decrypt($data['cc']) != '') {
            $request['bin'] = mb_substr($GLOBALS['tf']->decrypt($data['cc']), 0, 6);
        } // bank identification number
        if ($ip !== false) {
            $request['forwardedIP'] = $ip;
        } // X-Forwarded-For or Client-IP HTTP Header
        $request['custPhone'] = $data['phone']; // Area-code and local prefix of customer phone number
        // Optional fields
        $request['requested_type'] = 'premium'; // Which level (free, city, premium) of CCFD to use
        $request['emailMD5'] = md5($data['lid']); // CreditCardFraudDetection.php will take
        // MD5 hash of e-mail address passed to emailMD5 if it detects '@' in the string
        $request['usernameMD5'] = md5($data['lid']);
        //$request['shipAddr'] = $data['address']; // Shipping Address
        //$request['txnID'] = "1234";         // Transaction ID
        //$request['sessionID'] = "abcd9876";     // Session ID
        // $ccfs->isSecure = 0;
        //set the time out to be five seconds
        $ccfs->timeout = 10;
        //uncomment to turn on debugging
        // $ccfs->debug = 1;
        //next we pass the input hash to the server
        myadmin_log('maxmind', 'debug', "update_maxmind({$custid}, {$ip}) Calling With Arguments: " . json_encode($request), __LINE__, __FILE__);
        \StatisticClient::tick('MaxMind', 'MinFraudLookup');
        $ccfs->input($request);
        $ccfs->query();
        $response = $ccfs->output();
        if (is_array($response) & isset($response['riskScore'])) {
            \StatisticClient::report('MaxMind', 'MinFraudLookup', true, 0, '', STATISTICS_SERVER);
        } else {
            \StatisticClient::report('MaxMind', 'MinFraudLookup', false, 100, 'Invalid Response', STATISTICS_SERVER);
        }
        if (isset($data['country']) && in_array(strtolower($data['country']), ['br', 'tw'])) {
            if (isset($response['score']) && $response['score'] < MAXMIND_COUNTRY_SCORE_LIMIT) {
                $response['score'] += MAXMIND_COUNTRY_SCORE_PENALTY;
            }
            if (isset($response['riskScore']) && $response['riskScore'] <= MAXMIND_COUNTRY_RISKSCORE_LIMIT) {
                $response['riskScore'] += MAXMIND_COUNTRY_SCORE_PENALTY;
            }
            if (isset($data['name'])) {
                $nparts = explode(' ', $data['name']);
                $first_name = $nparts[0];
                include_once __DIR__.'/../../../../include/config/female_names.inc.php';
                if (in_array($first_name, $female_names)) {
                    $response['female_name'] = 'yes';
                    if (isset($response['score'])) {
                        $response['score'] = trim($response['score']);
                    }
                    if (MAXMIND_FEMALE_PENALTY_ENABLE == true && ((isset($response['score']) && $response['score'] < MAXMIND_FEMALE_SCORE_LIMIT) || $response['riskScore'] < MAXMIND_FEMALE_RISKSCORE_LIMIT)) {
                        if (isset($response['score'])) {
                            $response['original_score'] = $response['score'];
                            $response['score'] += MAXMIND_FEMALE_SCORE_PENALTY;
                        }
                        $response['original_riskScore'] = $response['riskScore'];
                        $response['riskScore'] += MAXMIND_FEMALE_RISKSCORE_PENALTY;
                        if (isset($response['explanation'])) {
                            $response['explanation'] = trim($response['explanation']).' The user has a female first name, as per request, that means + '.MAXMIND_FEMALE_SCORE_PENALTY.' to fraud score';
                        }
                    }
                } else {
                    $response['female_name'] = 'no';
                }
            } else {
                $response['female_name'] = 'no';
            }

            $distance_penalty = floor($response['distance'] / 1000);
            myadmin_log('maxmind', 'info', 'Distance is '.$response['distance'].' or +'.$distance_penalty.' to Score', __LINE__, __FILE__);
            $response['riskScore'] += $distance_penalty;

            $data['maxmind_riskscore'] = trim($response['riskScore']);
            if (isset($response['score'])) {
                $data['maxmind_score'] = trim($response['score']);
            }
            $data['maxmind'] = myadmin_stringify($response);
            myadmin_log('maxmind', 'notice', 'Maxmind '.(isset($response['score']) ? 'Score: '.$response['score'] : '').' riskScore: '.$response['riskScore'], __LINE__, __FILE__);
            myadmin_log('maxmind', 'debug', $new_data['maxmind'], __LINE__, __FILE__);
            if ((MAXMIND_CARDER_LOCK == true && $response['carderEmail'] == 'Yes') || (isset($response['score']) && $response['score'] >= MAXMIND_SCORE_LOCK) || $response['riskScore'] >= MAXMIND_RISKSCORE_LOCK) {
                $data['status'] = 'locked';
            }
            if ((isset($response['score']) && $response['score'] >= MAXMIND_SCORE_DISABLE_CC) || $response['riskScore'] >= MAXMIND_RISKSCORE_DISABLE_CC || $response['proxyScore'] >= MAXMIND_PROXYSCORE_DISABLE_CC) {
                myadmin_log('maxmind', 'warning', "update_maxmind({$custid}, {$ip}) ".(isset($response['score']) ? "{$response['score']} >= " . MAXMIND_SCORE_DISABLE_CC : ''). " or  {$response['riskScore']} >= " . MAXMIND_RISKSCORE_DISABLE_CC.' Fraud Score, Disabling CC and Setting Payment Method To PayPal', __LINE__, __FILE__);
                $data['disable_cc'] = 1;
                $data['payment_method'] = 'paypal';
            }
            if (MAXMIND_NORESPONSE_DISABLE_CC == true && (!isset($response['score']) && trim($response['score']) == '') && trim($response['riskScore']) == '') {
                myadmin_log('maxmind', 'warning', "update_maxmind({$custid}, {$ip}) BLANK Maxmind Score and BLANK MaxMind Risk % Score, Disabling CC and Setting Payment Method To PayPal", __LINE__, __FILE__);
                $data['disable_cc'] = 1;
                $data['payment_method'] = 'paypal';
            }
        }
    }
    return $data;
}
