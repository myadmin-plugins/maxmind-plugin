<?php
/**
 * Log-safe copies of MaxMind request payloads.
 *
 * Kept free of any MyAdmin dependency so it can be loaded and tested on
 * its own.
 *
 * @package MyAdmin
 * @category General
 */

if (!function_exists('maxmind_loggable_request')) {
    /**
     * Returns a copy of a MaxMind request with the fields that must never
     * reach a log line removed: the account license key, any password hash,
     * and the customer's session id.
     *
     * @param array $request the request exactly as it is sent to MaxMind
     * @return array the same request minus the secret fields
     */
    function maxmind_loggable_request(array $request)
    {
        return array_diff_key($request, array_flip(['license_key', 'passwordMD5', 'sessionID']));
    }
}
