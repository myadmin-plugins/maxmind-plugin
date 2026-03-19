<?php

declare(strict_types=1);

namespace Detain\MyAdminMaxMind\Tests;

use PHPUnit\Framework\TestCase;

/**
 * Tests for the female_names.inc.php data file.
 */
class FemaleNamesTest extends TestCase
{
    /**
     * @var array<string> The female names array loaded from the include file.
     */
    private static array $femaleNames = [];

    /**
     * @var bool Whether the names have been loaded.
     */
    private static bool $loaded = false;

    public static function setUpBeforeClass(): void
    {
        if (!self::$loaded) {
            include dirname(__DIR__) . '/src/female_names.inc.php';
            /** @var array<string> $female_names */
            self::$femaleNames = $female_names;
            self::$loaded = true;
        }
    }

    /**
     * @test
     * Test that the female names array is not empty.
     */
    public function testFemaleNamesIsNotEmpty(): void
    {
        $this->assertNotEmpty(self::$femaleNames);
    }

    /**
     * @test
     * Test that the female names array is a flat array of strings.
     */
    public function testAllEntriesAreStrings(): void
    {
        foreach (self::$femaleNames as $index => $name) {
            $this->assertIsString($name, "Entry at index {$index} should be a string");
        }
    }

    /**
     * @test
     * Test that all names are lowercase.
     */
    public function testAllNamesAreLowercase(): void
    {
        foreach (self::$femaleNames as $name) {
            $this->assertSame(
                strtolower($name),
                $name,
                "Name '{$name}' should be lowercase"
            );
        }
    }

    /**
     * @test
     * Test that the array contains well-known female names.
     */
    public function testContainsCommonFemaleNames(): void
    {
        $commonNames = ['mary', 'jennifer', 'elizabeth', 'susan', 'jessica', 'sarah', 'emily', 'emma'];
        foreach ($commonNames as $name) {
            $this->assertContains($name, self::$femaleNames, "Common female name '{$name}' should be in the list");
        }
    }

    /**
     * @test
     * Test that names do not contain whitespace.
     */
    public function testNoNamesContainWhitespace(): void
    {
        foreach (self::$femaleNames as $name) {
            $this->assertSame(
                trim($name),
                $name,
                "Name '{$name}' should not contain leading or trailing whitespace"
            );
            $this->assertDoesNotMatchRegularExpression(
                '/\s/',
                $name,
                "Name '{$name}' should not contain whitespace"
            );
        }
    }

    /**
     * @test
     * Test that no entries are empty strings.
     */
    public function testNoEmptyEntries(): void
    {
        foreach (self::$femaleNames as $index => $name) {
            $this->assertNotEmpty($name, "Entry at index {$index} should not be empty");
        }
    }

    /**
     * @test
     * Test that the array has a reasonable number of entries.
     */
    public function testReasonableEntryCount(): void
    {
        $count = count(self::$femaleNames);
        $this->assertGreaterThan(500, $count, "Should contain a substantial list of female names");
    }

    /**
     * @test
     * Test that there are no duplicate names.
     */
    public function testNoDuplicates(): void
    {
        $uniqueNames = array_unique(self::$femaleNames);
        $this->assertCount(
            count($uniqueNames),
            self::$femaleNames,
            'There should be no duplicate names in the list'
        );
    }

    /**
     * @test
     * Test that names contain only alphabetic characters.
     */
    public function testNamesContainOnlyAlphaCharacters(): void
    {
        foreach (self::$femaleNames as $name) {
            $this->assertMatchesRegularExpression(
                '/^[a-z]+$/',
                $name,
                "Name '{$name}' should only contain lowercase alphabetic characters"
            );
        }
    }

    /**
     * @test
     * Test that the array is numerically indexed starting from zero.
     */
    public function testArrayIsNumericallyIndexed(): void
    {
        $keys = array_keys(self::$femaleNames);
        $expected = range(0, count(self::$femaleNames) - 1);
        $this->assertSame($expected, $keys, 'Array should be numerically indexed from 0');
    }
}
