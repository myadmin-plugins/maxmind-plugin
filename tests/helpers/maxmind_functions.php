<?php

/**
 * Extracted pure functions from maxmind.inc.php for testing purposes.
 * These functions have no external dependencies and can be tested in isolation.
 */

if (!function_exists('get_maxmind_field_descriptions')) {
    /**
     * Returns an associative array of MaxMind field names and their descriptions.
     *
     * @return array<string, string>
     */
    function get_maxmind_field_descriptions(): array
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
}

if (!function_exists('maxmind_decode')) {
    /**
     * Decodes stored maxmind data.
     *
     * @param string $encoded_maxmind encoded maxmind string
     * @return array|false the decoded maxmind data in an associative array, or false if there was a problem
     */
    function maxmind_decode($encoded_maxmind)
    {
        return myadmin_unstringify($encoded_maxmind);
    }
}
