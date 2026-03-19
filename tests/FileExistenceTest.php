<?php

declare(strict_types=1);

namespace Detain\MyAdminMaxMind\Tests;

use PHPUnit\Framework\TestCase;

/**
 * Tests for verifying all expected source files exist in the package.
 */
class FileExistenceTest extends TestCase
{
    /**
     * @var string Base path to the package source directory.
     */
    private string $srcDir;

    protected function setUp(): void
    {
        $this->srcDir = dirname(__DIR__) . '/src';
    }

    /**
     * @test
     * Test that Plugin.php exists in the src directory.
     */
    public function testPluginPhpExists(): void
    {
        $this->assertFileExists($this->srcDir . '/Plugin.php');
    }

    /**
     * @test
     * Test that maxmind.inc.php exists in the src directory.
     */
    public function testMaxmindIncPhpExists(): void
    {
        $this->assertFileExists($this->srcDir . '/maxmind.inc.php');
    }

    /**
     * @test
     * Test that female_names.inc.php exists in the src directory.
     */
    public function testFemaleNamesIncPhpExists(): void
    {
        $this->assertFileExists($this->srcDir . '/female_names.inc.php');
    }

    /**
     * @test
     * Test that maxmind_compare.php exists in the src directory.
     */
    public function testMaxmindComparePhpExists(): void
    {
        $this->assertFileExists($this->srcDir . '/maxmind_compare.php');
    }

    /**
     * @test
     * Test that maxmind_lookup.php exists in the src directory.
     */
    public function testMaxmindLookupPhpExists(): void
    {
        $this->assertFileExists($this->srcDir . '/maxmind_lookup.php');
    }

    /**
     * @test
     * Test that view_maxmind.php exists in the src directory.
     */
    public function testViewMaxmindPhpExists(): void
    {
        $this->assertFileExists($this->srcDir . '/view_maxmind.php');
    }

    /**
     * @test
     * Test that composer.json exists in the package root.
     */
    public function testComposerJsonExists(): void
    {
        $this->assertFileExists(dirname(__DIR__) . '/composer.json');
    }

    /**
     * @test
     * Test that Plugin.php contains the expected namespace declaration.
     */
    public function testPluginPhpHasCorrectNamespace(): void
    {
        $content = file_get_contents($this->srcDir . '/Plugin.php');
        $this->assertStringContainsString(
            'namespace Detain\MyAdminMaxMind;',
            $content
        );
    }

    /**
     * @test
     * Test that Plugin.php imports GenericEvent.
     */
    public function testPluginPhpImportsGenericEvent(): void
    {
        $content = file_get_contents($this->srcDir . '/Plugin.php');
        $this->assertStringContainsString(
            'use Symfony\Component\EventDispatcher\GenericEvent;',
            $content
        );
    }
}
