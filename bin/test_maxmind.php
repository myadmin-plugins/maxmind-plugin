#!/usr/bin/env php
<?php
//require_once __DIR__.'/../../vendor/autoload.php';
require_once __DIR__.'/../../vendor/minfraud/http/src/CreditCardFraudDetection.php';

$ccfs = new CreditCardFraudDetection();

$h['license_key'] = 'xxxxxxxxxxxx';

// Required fields
$h['i'] = '24.152.199.105';
$h['city'] = 'Ephrata';
$h['region'] = 'PA';
$h['postal'] = '17522';
$h['country'] = 'US';

// Recommended fields
//$h["domain"] = "yahoo.com";		// Email domain
$h['custPhone'] = '2012308833';

// Optional fields
$h['requested_type'] = 'premium';	// Which level (free, city, premium) of CCFD to use
$h['emailMD5'] = md5('detain@interserver.net');
$h['usernameMD5'] = $h['emailMD5'];
// MD5 hash of e-mail address passed to emailMD5 if it detects '@' in the string
$h['shipAddr'] = '221 Duke St.';
//$h["txnID"] = "1234";			// Transaction ID
//$h["sessionID"] = "abcd9876";		// Session ID

$ccfs->isSecure = 1;

//set the time out to be five seconds
$ccfs->timeout = 5;

//uncomment to turn on debugging
//$ccfs->debug = 1;

//next we pass the input hash to the server
$ccfs->input($h);

//then we query the server
$ccfs->query();

//then we get the result from the server
$h = $ccfs->output();
print_r($h);
