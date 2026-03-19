<?php

declare(strict_types=1);

namespace Detain\MyAdminMaxMind\Tests;

use PHPUnit\Framework\TestCase;

/**
 * Tests for the maxmind_decode() function.
 */
class MaxmindDecodeTest extends TestCase
{
    /**
     * @test
     * Test that maxmind_decode function exists after loading.
     */
    public function testMaxmindDecodeFunctionExists(): void
    {
        $this->assertTrue(
            function_exists('maxmind_decode'),
            'maxmind_decode function should exist after class loading'
        );
    }

    /**
     * @test
     * Test that maxmind_decode delegates to myadmin_unstringify.
     */
    public function testMaxmindDecodeCallsMyadminUnstringify(): void
    {
        $data = ['score' => '5.0', 'riskScore' => '25.0'];
        $encoded = json_encode($data);
        $result = \maxmind_decode($encoded);
        $this->assertIsArray($result);
        $this->assertSame('5.0', $result['score']);
        $this->assertSame('25.0', $result['riskScore']);
    }

    /**
     * @test
     * Test that maxmind_decode returns null for invalid data.
     */
    public function testMaxmindDecodeWithInvalidData(): void
    {
        $result = \maxmind_decode('not-valid-json');
        $this->assertNull($result);
    }

    /**
     * @test
     * Test that maxmind_decode handles an empty JSON object.
     */
    public function testMaxmindDecodeWithEmptyObject(): void
    {
        $result = \maxmind_decode('{}');
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * @test
     * Test that maxmind_decode handles empty string.
     */
    public function testMaxmindDecodeWithEmptyString(): void
    {
        $result = \maxmind_decode('');
        $this->assertNull($result);
    }
}
