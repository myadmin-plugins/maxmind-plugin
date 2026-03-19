<?php

declare(strict_types=1);

namespace Detain\MyAdminMaxMind\Tests;

use PHPUnit\Framework\TestCase;

/**
 * Tests for the get_maxmind_field_descriptions() function.
 */
class MaxmindFieldDescriptionsTest extends TestCase
{
    /**
     * @test
     * Test that get_maxmind_field_descriptions returns an array.
     */
    public function testReturnsArray(): void
    {
        $fields = \get_maxmind_field_descriptions();
        $this->assertIsArray($fields);
    }

    /**
     * @test
     * Test that the returned array is not empty.
     */
    public function testReturnsNonEmptyArray(): void
    {
        $fields = \get_maxmind_field_descriptions();
        $this->assertNotEmpty($fields);
    }

    /**
     * @test
     * Test that all keys are strings.
     */
    public function testAllKeysAreStrings(): void
    {
        $fields = \get_maxmind_field_descriptions();
        foreach ($fields as $key => $value) {
            $this->assertIsString($key, "All field keys should be strings");
        }
    }

    /**
     * @test
     * Test that all values are strings.
     */
    public function testAllValuesAreStrings(): void
    {
        $fields = \get_maxmind_field_descriptions();
        foreach ($fields as $key => $value) {
            $this->assertIsString($value, "Value for field '{$key}' should be a string");
        }
    }

    /**
     * @test
     * Test that the 'distance' field key exists.
     */
    public function testDistanceFieldExists(): void
    {
        $fields = \get_maxmind_field_descriptions();
        $this->assertArrayHasKey('distance', $fields);
    }

    /**
     * @test
     * Test that the 'score' field key exists.
     */
    public function testScoreFieldExists(): void
    {
        $fields = \get_maxmind_field_descriptions();
        $this->assertArrayHasKey('score', $fields);
    }

    /**
     * @test
     * Test that the 'riskScore' field key exists.
     */
    public function testRiskScoreFieldExists(): void
    {
        $fields = \get_maxmind_field_descriptions();
        $this->assertArrayHasKey('riskScore', $fields);
    }

    /**
     * @test
     * Test that the 'countryMatch' field key exists.
     */
    public function testCountryMatchFieldExists(): void
    {
        $fields = \get_maxmind_field_descriptions();
        $this->assertArrayHasKey('countryMatch', $fields);
    }

    /**
     * @test
     * Test that the 'highRiskCountry' field key exists.
     */
    public function testHighRiskCountryFieldExists(): void
    {
        $fields = \get_maxmind_field_descriptions();
        $this->assertArrayHasKey('highRiskCountry', $fields);
    }

    /**
     * @test
     * Test that the 'anonymousProxy' field key exists.
     */
    public function testAnonymousProxyFieldExists(): void
    {
        $fields = \get_maxmind_field_descriptions();
        $this->assertArrayHasKey('anonymousProxy', $fields);
    }

    /**
     * @test
     * Test that the 'proxyScore' field key exists.
     */
    public function testProxyScoreFieldExists(): void
    {
        $fields = \get_maxmind_field_descriptions();
        $this->assertArrayHasKey('proxyScore', $fields);
    }

    /**
     * @test
     * Test that the 'freeMail' field key exists.
     */
    public function testFreeMailFieldExists(): void
    {
        $fields = \get_maxmind_field_descriptions();
        $this->assertArrayHasKey('freeMail', $fields);
    }

    /**
     * @test
     * Test that the 'carderEmail' field key exists.
     */
    public function testCarderEmailFieldExists(): void
    {
        $fields = \get_maxmind_field_descriptions();
        $this->assertArrayHasKey('carderEmail', $fields);
    }

    /**
     * @test
     * Test that the 'err' field key exists.
     */
    public function testErrFieldExists(): void
    {
        $fields = \get_maxmind_field_descriptions();
        $this->assertArrayHasKey('err', $fields);
    }

    /**
     * @test
     * Test that the 'queriesRemaining' field key exists.
     */
    public function testQueriesRemainingFieldExists(): void
    {
        $fields = \get_maxmind_field_descriptions();
        $this->assertArrayHasKey('queriesRemaining', $fields);
    }

    /**
     * @test
     * Test that the 'maxmindID' field key exists.
     */
    public function testMaxmindIdFieldExists(): void
    {
        $fields = \get_maxmind_field_descriptions();
        $this->assertArrayHasKey('maxmindID', $fields);
    }

    /**
     * @test
     * Test that IP-related fields exist.
     */
    public function testIpRelatedFieldsExist(): void
    {
        $fields = \get_maxmind_field_descriptions();
        $ipFields = [
            'ip_city', 'ip_region', 'ip_regionName', 'countryCode',
            'ip_countryName', 'ip_continentCode', 'ip_latitude', 'ip_longitude',
            'ip_postalCode', 'ip_metroCode', 'ip_areaCode', 'ip_timeZone',
            'ip_asnum', 'ip_userType', 'ip_netSpeedCell', 'ip_domain',
            'ip_isp', 'ip_org', 'ip_accuracyRadius', 'ip_countryConf',
            'ip_regionConf', 'ip_cityConf', 'ip_postalConf', 'ip_corporateProxy',
        ];
        foreach ($ipFields as $field) {
            $this->assertArrayHasKey($field, $fields, "IP field '{$field}' should exist");
        }
    }

    /**
     * @test
     * Test that BIN-related fields exist.
     */
    public function testBinRelatedFieldsExist(): void
    {
        $fields = \get_maxmind_field_descriptions();
        $binFields = ['binMatch', 'binCountry', 'binNameMatch', 'binName', 'binPhoneMatch', 'binPhone'];
        foreach ($binFields as $field) {
            $this->assertArrayHasKey($field, $fields, "BIN field '{$field}' should exist");
        }
    }

    /**
     * @test
     * Test that no field description is empty.
     */
    public function testNoEmptyDescriptions(): void
    {
        $fields = \get_maxmind_field_descriptions();
        foreach ($fields as $key => $value) {
            $this->assertNotEmpty(trim($value), "Description for field '{$key}' should not be empty");
        }
    }

    /**
     * @test
     * Test that the function returns consistent results on multiple calls.
     */
    public function testConsistentResults(): void
    {
        $first = \get_maxmind_field_descriptions();
        $second = \get_maxmind_field_descriptions();
        $this->assertSame($first, $second);
    }

    /**
     * @test
     * Test that the expected number of fields is returned (sanity check).
     */
    public function testExpectedFieldCount(): void
    {
        $fields = \get_maxmind_field_descriptions();
        $this->assertGreaterThan(30, count($fields));
    }
}
