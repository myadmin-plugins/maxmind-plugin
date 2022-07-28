<?php
/**
 * MaxMind Fraud Stuff
 *
 * @link https://dev.maxmind.com/minfraud/ minFraud Score, minFraud Insights, and minFraud Factors API
 * @link https://dev.maxmind.com/minfraud/chargeback/ minFraud Chargeback Web Service API
 * @link https://github.com/maxmind/minfraud-api-php GitHub Repo
 *
 * Notification URL Sample:
 * @link https://my.interserver.net/payments/maxmind_alert.php?i=24.24.24.24&maxmindID=1234ABCD&domain=sample.com&city=Anytown&region=CA&country=US&date=January+1,+1970&txnID=foo123&reason=IP+address+has+been+marked+as+a+high-risk+IP&reason_code=HIGH_RISK_IP&minfraud_id=2afb0d26-e3b4-4624-8e66-fd10e64b95df
 */

use MaxMind\MinFraud;

/**
 * updates the MaxMind data for a given user.
 *
 * @param integer $customer customer id
 * @param bool|string $ip ip address to register with the query, or false to have it use session ip
 * @return bool pretty much always returns true
 */
function maxmind_lookup($customer, $ip = false)
{
    $mf = new MinFraud(MAXMIND_USER_ID, MAXMIND_LICENSE_KEY);
    $data = $GLOBALS['tf']->accounts->read($customer);
    $request = $mf->withDevice([
        'ip_address'      => $ip == false ? \MyAdmin\Session::get_client_ip() : $ip,	// string The IP address associated with the device used by the customer in the transaction. The IP address must be in IPv4 or IPv6 presentation format, i.e., dotted-quad notation or the IPv6 hexadecimal-colon notation. (Required)
        'session_age'     => time() - $GLOBALS['tf']->session->loggedInAt,					// string (255) The number of seconds between the creation of the user’s session and the time of the transaction. Note that session_age is not the duration of the current visit, but the time since the start of the first visit.
        'session_id'      => $GLOBALS['tf']->session->sessionid,							// string (255) An ID that uniquely identifies a visitor’s session on the site.
        'user_agent'      => $_SERVER['HTTP_USER_AGENT'],									// decimal	The HTTP “User-Agent” header of the browser used in the transaction.
        'accept_language' => $_SERVER['HTTP_ACCEPT_LANGUAGE']								// string (255) The HTTP “Accept-Language” header of the device used in the transaction.
    ])->withAccount([
        'user_id'      => $data['account_id'],				// string (255)	A unique user ID associated with the end-user in your system. If your system allows the login name for the account to be changed, this should not be the login name for the account, but rather should be an internal ID that does not change. This is not your MaxMind user ID.
        'username_md5' => md5($data['account_lid'])			// string (32)	An MD5 hash as a hexadecimal string of the username or login name associated with the account.
    ])->withEmail([
        'address' => $data['account_lid'],													// string (255)	This field must be either be a valid email address or an MD5 of the email used in the transaction.
        'domain'  => substr($data['account_lid'], strpos($data['account_lid'], '@')+1)		// string (255)	The domain of the email address used in the transaction.
    ])->withBilling([
        'first_name'         => get_first_name($data['name']),									// string (255)	The first name of the end user as provided in their billing information.
        'last_name'          => get_last_name($data['name']),									// string (255)	The last name of the end user as provided in their billing information.
        'company'            => $data['company'],												// string (255)	The company of the end user as provided in their billing information.
        'address'            => $data['address'],												// string (255)	The first line of the user’s billing address.
        'address_2'          => $data['address2'],												// string (255)	The second line of the user’s billing address.
        'city'               => $data['city'],													// string (255)	The city of the user’s shipping address.
        'region'             => $data['state'],													// string (4) The ISO 3166-2 subdivision code for the user’s billing address.
        'country'            => $data['country'],												// string (2)	The two character ISO 3166-1 alpha-2 country code of the user’s billing address.
        'postal'             => $data['zip'],													// string (255)	The postal code of the user’s billing address.
        'phone_number'       => get_phone_no_country_code($data['phone'], $data['country']),	// string (255)	The phone number without the country code for the user’s billing address.
        'phone_country_code' => get_phone_country_code($data['phone'], $data['country'])		// string (4)	The country code for phone number associated with the user’s billing address.
    ])->withEvent([
        'transaction_id' => 'txn3134133',					// string (255)	Your internal ID for the transaction. We can use this to locate a specific transaction in our logs, and it will also show up in email alerts and notifications from us to you.
        //'shop_id'        => 's2123',						// string (255)	Your internal ID for the shop, affiliate, or merchant this order is coming from. Required for minFraud users who are resellers, payment providers, gateways and affiliate networks.
        'time'           => date(DATE_RFC3339),				// string	The date and time the event occurred. The string must be in the RFC 3339 date-time format, e.g., “2012-04-12T23:20:50.52Z”. If this field is not in the request, the current time will be used.
        'type'           => 'purchase'						// enum  The type of event being scored. The valid types are: account_creation account_login email_change password_reset purchase recurring_purchase referral survey
    ])->withPayment([
        'processor'             => 'paypal',				// enum The payment processor used for the transaction. The valid values are:adyen altapay amazon_payments american_express_payment_gateway authorizenet balanced beanstream bluepay bluesnap braintree ccnow chase_paymentech cielo collector compropago commdoo concept_payments conekta cuentadigital curopayments dalpay dibs digital_river ebs ecomm365 elavon epay eprocessing_network eway exact first_data global_payments hipay ingenico internetsecure intuit_quickbooks_payments iugu lemon_pay mastercard_payment_gateway mercadopago merchant_esolutions mirjeh mollie moneris_solutions nmi oceanpayment openpaymx optimal_payments orangepay other pacnet_services payfast paygate paymentwall payone paypal payplus paystation paytrace paytrail payture payu payulatam payza pinpayments princeton_payment_solutions psigate qiwi quickpay raberil rede redpagos rewardspay sagepay securetrading simplify_commerce skrill smartcoin solidtrust_pay sps_decidir stripe telerecargas towah usa_epay vantiv verepay vericheck vindicia virtual_card_services vme vpos worldpay
        'was_authorized'        => false,					// boolean	The authorization outcome from the payment processor. If the transaction has not yet been approved or denied, do not include this field.
        'decline_code'          => 'invalid number'			// string (255)	The decline code as provided by your payment processor. If the transaction was not declined, do not include this field.
    ])->withCreditCard([
        'issuer_id_number'        => get_cc_bank_number($data['cc']),	// string (6)	The issuer ID number for the credit card. This is the first 6 digits of the credit card number. It identifies the issuing bank.
        'last_4_digits'           => get_cc_last_four($data['cc']),	// string (4)	The last four digits of the credit card number.
        //'bank_name'               => 'Bank of No Hope',				// string (255)	The name of the issuing bank as provided by the end user.
        //'bank_phone_country_code' => '1',								// string (4)	The phone country code for the issuing bank as provided by the end user. If you provide this information then you must provide at least one digit.
        //'bank_phone_number'       => '800-342-1232',					// string (255) The phone number, without the country code, for the issuing bank as provided by the end user. Punctuation characters will be stripped. After stripping punctuation characters, the number must contain only digits.
        'avs_result'              => 'Y',								// character (1)	The address verification system (AVS) check result, as returned to you by the credit card processor. The minFraud service supports the standard AVS codes.
        'cvv_result'              => 'N'								// character (1)	The card verification value (CVV) code as provided by the payment processor.
    ])->withOrder([
        'amount'           => 323.21,						// decimal      The total order amount for the transaction before taxes and discounts.
        'currency'         => 'USD',						// string (3)   The ISO 4217 currency code for the currency used in the transaction.
        'discount_code'    => 'FIRST',						// string (255) The discount code applied to the transaction. If multiple discount codes were used, please separate them with a comma.
        'is_gift'          => false,						// boolean      Whether order was marked as a gift by the purchaser.
        'has_gift_message' => false,						// boolean      Whether the purchaser included a gift message.
        'affiliate_id'     => 'af12',						// string (255) The ID of the affiliate where the order is coming from.
        //'subaffiliate_id'  => 'saf42',						// string (255) The ID of the sub-affiliate where the order is coming from.
        'referrer_uri'     => 'http://www.amazon.com/'		// string (1024)        The URI of the referring site for this order. Needs to be absolute and have a URI scheme such as https://
    ])->withShoppingCartItem([
        'category' => 'pets',			// string (255) The category of the item.
        'item_id'  => 'leash-0231',		// string (255) Your internal ID for the item.
        'quantity' => 2,				// integer      The quantity of the item in the shopping cart.
        'price'    => 20.43				// decimal      The per-unit price of this item in the shopping cart. This should use the same currency as the order currency.
    ])->withShoppingCartItem([
        'category' => 'beauty',			// string (255) The category of the item.
        'item_id'  => 'msc-1232',		// string (255) Your internal ID for the item.
        'quantity' => 1,				// integer      The quantity of the item in the shopping cart.
        'price'    => 100.00			// decimal      The per-unit price of this item in the shopping cart. This should use the same currency as the order currency.
    ])->withCustomInputs([
        'section'                      => 'news',
        'number_of_previous_purchases' => 19,
        'discount'                     => 3.2,
        'previous_user'                => true
    ]);

    try {
        $factorsResponse = $request->factors();
        $insightsResponse = $request->insights();
        $scoreResponse = $request->score();
    } catch (\MaxMind\Exception\InvalidInputException $r) {
        $msg = 'Invalid input data or when ->score() or ->insights() is called on a request where the required ip_address field in the device array is missing.';
    } catch (\MaxMind\Exception\AuthenticationException $r) {
        $msg = 'the server is unable to authenticate the request, e.g., if the license key or user ID is invalid.';
    } catch (\MaxMind\Exception\InsufficientFundsException $r) {
        $msg = 'your account is out of funds.';
    } catch (\MaxMind\Exception\InvalidRequestException $r) {
        $msg = 'server rejects the request for another reason such as invalid JSON in the POST.';
    } catch (\MaxMind\Exception\HttpException $r) {
        $msg = 'unexpected HTTP error occurs such as a firewall interfering with the request to the server.';
    } catch (\MaxMind\Exception\WebServiceException $r) {
        $msg = 'An Undetermined Error Occurred';
    }

    print($insightsResponse->subscores->email . "\n");

    print($insightsResponse->riskScore . "\n");
    print($insightsResponse->creditCard->issuer->name . "\n");
    foreach ($insightsResponse->warnings as $warning) {
        print($warning->warning . "\n");
    }

    print($scoreResponse->riskScore . "\n");
    foreach ($scoreResponse->warnings as $warning) {
        print($warning->warning . "\n");
    }
}
