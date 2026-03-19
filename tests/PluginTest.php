<?php

declare(strict_types=1);

namespace Detain\MyAdminMaxMind\Tests;

use Detain\MyAdminMaxMind\Plugin;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * Tests for the Plugin class.
 */
class PluginTest extends TestCase
{
    /**
     * @test
     * Test that the Plugin class exists and can be instantiated.
     */
    public function testPluginClassExists(): void
    {
        $this->assertTrue(class_exists(Plugin::class));
    }

    /**
     * @test
     * Test that the Plugin class can be instantiated without arguments.
     */
    public function testPluginCanBeInstantiated(): void
    {
        $plugin = new Plugin();
        $this->assertInstanceOf(Plugin::class, $plugin);
    }

    /**
     * @test
     * Test that the $name static property is set correctly.
     */
    public function testNameStaticProperty(): void
    {
        $this->assertSame('MaxMind Plugin', Plugin::$name);
    }

    /**
     * @test
     * Test that the $description static property is set correctly.
     */
    public function testDescriptionStaticProperty(): void
    {
        $this->assertSame(
            'Allows handling of MaxMind based Fraud Lookups and Fraud Reporting',
            Plugin::$description
        );
    }

    /**
     * @test
     * Test that the $help static property is an empty string.
     */
    public function testHelpStaticProperty(): void
    {
        $this->assertSame('', Plugin::$help);
    }

    /**
     * @test
     * Test that the $type static property is set to 'plugin'.
     */
    public function testTypeStaticProperty(): void
    {
        $this->assertSame('plugin', Plugin::$type);
    }

    /**
     * @test
     * Test that getHooks returns an array.
     */
    public function testGetHooksReturnsArray(): void
    {
        $hooks = Plugin::getHooks();
        $this->assertIsArray($hooks);
    }

    /**
     * @test
     * Test that getHooks contains the expected event keys.
     */
    public function testGetHooksContainsExpectedKeys(): void
    {
        $hooks = Plugin::getHooks();
        $this->assertArrayHasKey('system.settings', $hooks);
        $this->assertArrayHasKey('function.requirements', $hooks);
        $this->assertArrayHasKey('ui.menu', $hooks);
    }

    /**
     * @test
     * Test that getHooks returns exactly three hooks.
     */
    public function testGetHooksReturnsThreeHooks(): void
    {
        $hooks = Plugin::getHooks();
        $this->assertCount(3, $hooks);
    }

    /**
     * @test
     * Test that each hook value is a callable array with the class name and method.
     */
    public function testGetHooksValuesAreCallableArrays(): void
    {
        $hooks = Plugin::getHooks();
        foreach ($hooks as $eventName => $callback) {
            $this->assertIsArray($callback, "Hook for '{$eventName}' should be an array");
            $this->assertCount(2, $callback, "Hook for '{$eventName}' should have two elements");
            $this->assertSame(Plugin::class, $callback[0], "First element of hook for '{$eventName}' should be the Plugin class");
            $this->assertIsString($callback[1], "Second element of hook for '{$eventName}' should be a string method name");
        }
    }

    /**
     * @test
     * Test that the system.settings hook points to getSettings method.
     */
    public function testSystemSettingsHookPointsToGetSettings(): void
    {
        $hooks = Plugin::getHooks();
        $this->assertSame([Plugin::class, 'getSettings'], $hooks['system.settings']);
    }

    /**
     * @test
     * Test that the function.requirements hook points to getRequirements method.
     */
    public function testFunctionRequirementsHookPointsToGetRequirements(): void
    {
        $hooks = Plugin::getHooks();
        $this->assertSame([Plugin::class, 'getRequirements'], $hooks['function.requirements']);
    }

    /**
     * @test
     * Test that the ui.menu hook points to getMenu method.
     */
    public function testUiMenuHookPointsToGetMenu(): void
    {
        $hooks = Plugin::getHooks();
        $this->assertSame([Plugin::class, 'getMenu'], $hooks['ui.menu']);
    }

    /**
     * @test
     * Test that all hook methods exist on the Plugin class.
     */
    public function testAllHookMethodsExist(): void
    {
        $hooks = Plugin::getHooks();
        $reflection = new ReflectionClass(Plugin::class);
        foreach ($hooks as $eventName => $callback) {
            $methodName = $callback[1];
            $this->assertTrue(
                $reflection->hasMethod($methodName),
                "Method '{$methodName}' referenced by hook '{$eventName}' should exist on Plugin class"
            );
        }
    }

    /**
     * @test
     * Test that all hook methods are public and static.
     */
    public function testAllHookMethodsArePublicStatic(): void
    {
        $hooks = Plugin::getHooks();
        $reflection = new ReflectionClass(Plugin::class);
        foreach ($hooks as $eventName => $callback) {
            $method = $reflection->getMethod($callback[1]);
            $this->assertTrue(
                $method->isPublic(),
                "Method '{$callback[1]}' should be public"
            );
            $this->assertTrue(
                $method->isStatic(),
                "Method '{$callback[1]}' should be static"
            );
        }
    }

    /**
     * @test
     * Test that getMenu accepts a GenericEvent parameter.
     */
    public function testGetMenuSignature(): void
    {
        $reflection = new ReflectionClass(Plugin::class);
        $method = $reflection->getMethod('getMenu');
        $params = $method->getParameters();
        $this->assertCount(1, $params);
        $this->assertSame('event', $params[0]->getName());
        $type = $params[0]->getType();
        $this->assertNotNull($type);
        $this->assertSame(GenericEvent::class, $type->getName());
    }

    /**
     * @test
     * Test that getRequirements accepts a GenericEvent parameter.
     */
    public function testGetRequirementsSignature(): void
    {
        $reflection = new ReflectionClass(Plugin::class);
        $method = $reflection->getMethod('getRequirements');
        $params = $method->getParameters();
        $this->assertCount(1, $params);
        $this->assertSame('event', $params[0]->getName());
        $type = $params[0]->getType();
        $this->assertNotNull($type);
        $this->assertSame(GenericEvent::class, $type->getName());
    }

    /**
     * @test
     * Test that getSettings accepts a GenericEvent parameter.
     */
    public function testGetSettingsSignature(): void
    {
        $reflection = new ReflectionClass(Plugin::class);
        $method = $reflection->getMethod('getSettings');
        $params = $method->getParameters();
        $this->assertCount(1, $params);
        $this->assertSame('event', $params[0]->getName());
        $type = $params[0]->getType();
        $this->assertNotNull($type);
        $this->assertSame(GenericEvent::class, $type->getName());
    }

    /**
     * @test
     * Test that the Plugin class is in the correct namespace.
     */
    public function testPluginNamespace(): void
    {
        $reflection = new ReflectionClass(Plugin::class);
        $this->assertSame('Detain\MyAdminMaxMind', $reflection->getNamespaceName());
    }

    /**
     * @test
     * Test that the constructor has no required parameters.
     */
    public function testConstructorHasNoRequiredParameters(): void
    {
        $reflection = new ReflectionClass(Plugin::class);
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor);
        $this->assertCount(0, $constructor->getParameters());
    }

    /**
     * @test
     * Test that all four static properties are declared on the class.
     */
    public function testStaticPropertiesExist(): void
    {
        $reflection = new ReflectionClass(Plugin::class);
        $properties = $reflection->getStaticProperties();
        $this->assertArrayHasKey('name', $properties);
        $this->assertArrayHasKey('description', $properties);
        $this->assertArrayHasKey('help', $properties);
        $this->assertArrayHasKey('type', $properties);
    }

    /**
     * @test
     * Test that all static properties are public.
     */
    public function testStaticPropertiesArePublic(): void
    {
        $reflection = new ReflectionClass(Plugin::class);
        $expectedProperties = ['name', 'description', 'help', 'type'];
        foreach ($expectedProperties as $propName) {
            $prop = $reflection->getProperty($propName);
            $this->assertTrue($prop->isPublic(), "Property '{$propName}' should be public");
            $this->assertTrue($prop->isStatic(), "Property '{$propName}' should be static");
        }
    }

    /**
     * @test
     * Test that all static properties are strings.
     */
    public function testStaticPropertiesAreStrings(): void
    {
        $this->assertIsString(Plugin::$name);
        $this->assertIsString(Plugin::$description);
        $this->assertIsString(Plugin::$help);
        $this->assertIsString(Plugin::$type);
    }
}
