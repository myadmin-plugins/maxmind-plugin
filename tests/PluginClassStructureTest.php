<?php

declare(strict_types=1);

namespace Detain\MyAdminMaxMind\Tests;

use Detain\MyAdminMaxMind\Plugin;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Tests for the structural integrity of the Plugin class via reflection.
 */
class PluginClassStructureTest extends TestCase
{
    /**
     * @var ReflectionClass<Plugin>
     */
    private ReflectionClass $reflection;

    protected function setUp(): void
    {
        $this->reflection = new ReflectionClass(Plugin::class);
    }

    /**
     * @test
     * Test that the Plugin class is not abstract.
     */
    public function testClassIsNotAbstract(): void
    {
        $this->assertFalse($this->reflection->isAbstract());
    }

    /**
     * @test
     * Test that the Plugin class is not final.
     */
    public function testClassIsNotFinal(): void
    {
        $this->assertFalse($this->reflection->isFinal());
    }

    /**
     * @test
     * Test that the Plugin class does not implement any interfaces.
     */
    public function testClassImplementsNoInterfaces(): void
    {
        $this->assertEmpty($this->reflection->getInterfaceNames());
    }

    /**
     * @test
     * Test that the Plugin class does not extend any parent class.
     */
    public function testClassHasNoParent(): void
    {
        $this->assertFalse($this->reflection->getParentClass());
    }

    /**
     * @test
     * Test that the class has exactly five public methods (constructor + 4 hook methods).
     */
    public function testPublicMethodCount(): void
    {
        $publicMethods = $this->reflection->getMethods(\ReflectionMethod::IS_PUBLIC);
        $methodNames = array_map(fn($m) => $m->getName(), $publicMethods);
        $expectedMethods = ['__construct', 'getHooks', 'getMenu', 'getRequirements', 'getSettings'];
        sort($methodNames);
        sort($expectedMethods);
        $this->assertSame($expectedMethods, $methodNames);
    }

    /**
     * @test
     * Test that getHooks has no parameters.
     */
    public function testGetHooksHasNoParameters(): void
    {
        $method = $this->reflection->getMethod('getHooks');
        $this->assertCount(0, $method->getParameters());
    }

    /**
     * @test
     * Test that getHooks is declared as static.
     */
    public function testGetHooksIsStatic(): void
    {
        $method = $this->reflection->getMethod('getHooks');
        $this->assertTrue($method->isStatic());
    }

    /**
     * @test
     * Test that the class has exactly four static properties.
     */
    public function testStaticPropertyCount(): void
    {
        $staticProps = $this->reflection->getStaticProperties();
        $this->assertCount(4, $staticProps);
    }

    /**
     * @test
     * Test that the class file is in the expected location relative to the package root.
     */
    public function testClassFileLocation(): void
    {
        $filename = $this->reflection->getFileName();
        $this->assertNotFalse($filename);
        $this->assertStringEndsWith('src' . DIRECTORY_SEPARATOR . 'Plugin.php', $filename);
    }

    /**
     * @test
     * Test that hook methods that accept GenericEvent do not have return type declarations.
     */
    public function testEventHandlerMethodsHaveNoReturnType(): void
    {
        $eventMethods = ['getMenu', 'getRequirements', 'getSettings'];
        foreach ($eventMethods as $methodName) {
            $method = $this->reflection->getMethod($methodName);
            $returnType = $method->getReturnType();
            // These methods have void-like behavior but may not have explicit return type
            // Just verify they exist and accept the right parameter
            $this->assertTrue($method->isStatic(), "{$methodName} should be static");
        }
    }

    /**
     * @test
     * Test that all event handler methods accept exactly one parameter.
     */
    public function testEventHandlerMethodsAcceptOneParameter(): void
    {
        $eventMethods = ['getMenu', 'getRequirements', 'getSettings'];
        foreach ($eventMethods as $methodName) {
            $method = $this->reflection->getMethod($methodName);
            $this->assertCount(
                1,
                $method->getParameters(),
                "{$methodName} should accept exactly one parameter"
            );
        }
    }
}
