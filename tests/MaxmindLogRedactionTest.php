<?php

declare(strict_types=1);

namespace Detain\MyAdminMaxMind\Tests;

use PHPUnit\Framework\TestCase;

/**
 * The MaxMind request is logged on every lookup; the log copy must never
 * carry the license key, a password hash or the session id, and the
 * request itself no longer sends a password hash at all.
 */
class MaxmindLogRedactionTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        require_once dirname(__DIR__) . '/src/maxmind_log.inc.php';
    }

    public function testLoggableRequestDropsSecretsAndKeepsTheRest(): void
    {
        $request = [
            'requested_type' => 'premium',
            'emailMD5' => md5('user@example.com'),
            'usernameMD5' => md5('user@example.com'),
            'passwordMD5' => md5('Secret123'),
            'sessionID' => 'abcdef0123456789',
            'i' => '192.0.2.10',
            'license_key' => 'LICENSEKEY123',
            'city' => 'Secaucus',
            'bin' => '411111',
        ];
        $logged = \maxmind_loggable_request($request);
        $this->assertArrayNotHasKey('license_key', $logged);
        $this->assertArrayNotHasKey('passwordMD5', $logged);
        $this->assertArrayNotHasKey('sessionID', $logged);
        $this->assertSame(
            ['requested_type', 'emailMD5', 'usernameMD5', 'i', 'city', 'bin'],
            array_keys($logged)
        );
        $encoded = json_encode($logged);
        $this->assertStringNotContainsString('LICENSEKEY123', $encoded);
        $this->assertStringNotContainsString(md5('Secret123'), $encoded);
        $this->assertStringNotContainsString('abcdef0123456789', $encoded);
    }

    public function testLoggableRequestLeavesTheSentRequestUntouched(): void
    {
        $request = ['license_key' => 'K', 'i' => '192.0.2.10'];
        \maxmind_loggable_request($request);
        $this->assertSame(['license_key' => 'K', 'i' => '192.0.2.10'], $request);
    }

    public function testUpdateMaxmindNoLongerReadsOrSendsThePasswordHash(): void
    {
        $source = (string) file_get_contents(dirname(__DIR__) . '/src/maxmind.inc.php');
        $this->assertStringNotContainsString('account_passwd', $source);
        $this->assertDoesNotMatchRegularExpression("/'passwordMD5'\\s*=>/", $source);
        $this->assertDoesNotMatchRegularExpression("/\\['passwordMD5'\\]\\s*=/", $source);
        // both request log lines go through the redactor
        $this->assertSame(0, preg_match('/myadmin_log\([^;]*json_encode\(\$request\)/', $source));
        $this->assertSame(2, preg_match_all('/json_encode\(maxmind_loggable_request\(\$request\)\)/', $source));
    }
}
