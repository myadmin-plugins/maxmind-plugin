---
name: plugin-tests
description: Creates or updates PHPUnit 9 tests in `tests/` following existing patterns — `TestCase` subclass, `declare(strict_types=1)`, namespace `Detain\MyAdminMaxMind\Tests`. Bootstrap stubs in `tests/bootstrap.php` and `tests/helpers/`. Use when user says 'add test', 'write tests', 'test coverage', 'create unit test', 'increase coverage'. Do NOT use for production code changes, CI config, or non-test PHP files.
---
# Plugin Tests

## Critical

- Every test file MUST start with `<?php` followed by `declare(strict_types=1);` on the next line
- Every test class MUST use namespace `Detain\MyAdminMaxMind\Tests`
- Every test class MUST extend `PHPUnit\Framework\TestCase`
- Test files MUST be placed in `tests/` and named `*Test.php` (matches `phpunit.xml.dist` testsuite pattern)
- Functions from `src/` that depend on MyAdmin globals (like `myadmin_unstringify`, database functions, etc.) MUST be stubbed in `tests/bootstrap.php` or extracted into `tests/helpers/maxmind_functions.php` — never call production MyAdmin functions directly
- Run `composer exec phpunit` to verify all tests pass before considering work complete

## Instructions

### Step 1: Identify what to test

Determine whether you are testing:
- **A class** (e.g., `Plugin`) — import it with `use Detain\MyAdminMaxMind\ClassName;`
- **A procedural function** (e.g., `maxmind_decode`, `get_maxmind_field_descriptions`) — call with global namespace `\function_name()`. These functions must be defined in `tests/helpers/maxmind_functions.php` or stubbed in `tests/bootstrap.php`
- **A data file** (e.g., `src/female_names.inc.php`) — load via `include dirname(__DIR__) . '/src/filename.inc.php'` in `setUpBeforeClass()`
- **File/structure existence** — use `assertFileExists()` with paths relative to `dirname(__DIR__)`

Verify: Confirm the source file exists in `src/` before writing tests.

### Step 2: Check if stubs are needed

If the function under test calls MyAdmin globals that don't exist in the test environment, add stubs.

**For bootstrap stubs** (`tests/bootstrap.php`), wrap in `if (!function_exists('...'))` guards:
```php
if (!function_exists('myadmin_unstringify')) {
    function myadmin_unstringify(string $data)
    {
        return json_decode($data, true);
    }
}
```

**For extracted pure functions** (`tests/helpers/maxmind_functions.php`), copy the function from `src/` and wrap in `if (!function_exists('...'))` guard. This file is already loaded by bootstrap.

Verify: Run `composer exec phpunit` to confirm stubs resolve all undefined function errors before writing test assertions.

### Step 3: Create the test file

Use this exact template (place in `tests/` directory with a descriptive name ending in `Test.php`):

```php
<?php

declare(strict_types=1);

namespace Detain\MyAdminMaxMind\Tests;

use PHPUnit\Framework\TestCase;

/**
 * Tests for [description of what is being tested].
 */
class DescriptiveNameTest extends TestCase
{
    /**
     * @test
     * Test that [description].
     */
    public function testMethodName(): void
    {
        // assertion here
    }
}
```

Naming conventions:
- File: `tests/DescriptiveNameTest.php` — PascalCase, ends with `Test.php`
- Class: matches filename exactly
- Methods: `testDescriptiveName` with `camelCase`, return type `: void`
- Every test method gets both `@test` annotation AND `test` prefix
- Every test method gets a descriptive docblock: `Test that [what it verifies].`

Verify: The class name matches the filename (without `.php`).

### Step 4: Write assertions following project patterns

**For class testing** (see `tests/PluginTest.php` and `tests/PluginClassStructureTest.php`):
- Use `ReflectionClass` for structural assertions (method visibility, static, parameter types)
- Use `setUp()` to initialize shared state like `$this->reflection = new ReflectionClass(ClassName::class)`
- Test static properties with `$reflection->getStaticProperties()` and direct access `ClassName::$prop`
- Test method signatures: parameter count, parameter names, type hints

```php
public function testMethodIsPublicStatic(): void
{
    $reflection = new ReflectionClass(Plugin::class);
    $method = $reflection->getMethod('methodName');
    $this->assertTrue($method->isPublic());
    $this->assertTrue($method->isStatic());
}
```

**For procedural function testing** (see `tests/MaxmindDecodeTest.php`):
- Call functions with global namespace prefix: `\maxmind_decode($input)`
- Test existence: `$this->assertTrue(function_exists('function_name'))`
- Test with valid input, invalid input, empty input, and edge cases

```php
public function testFunctionExists(): void
{
    $this->assertTrue(function_exists('function_name'));
}

public function testWithValidInput(): void
{
    $result = \function_name($validInput);
    $this->assertIsArray($result);
}

public function testWithInvalidInput(): void
{
    $result = \function_name('invalid');
    $this->assertNull($result);
}
```

**For data file testing** (see `tests/FemaleNamesTest.php`):
- Load data once in `setUpBeforeClass()` into a `private static` property
- Use a `private static bool $loaded` flag to prevent double-loading
- Test: not empty, correct types, no duplicates, expected values present, consistent indexing

```php
private static array $data = [];
private static bool $loaded = false;

public static function setUpBeforeClass(): void
{
    if (!self::$loaded) {
        include dirname(__DIR__) . '/src/female_names.inc.php';
        self::$data = $variable_from_include;
        self::$loaded = true;
    }
}
```

**For file existence testing** (see `tests/FileExistenceTest.php`):
- Set base path in `setUp()`: `$this->srcDir = dirname(__DIR__) . '/src'`
- Use `assertFileExists()`, `assertStringContainsString()` on file contents

Verify: Run the specific test file to confirm the new test passes:

```bash
composer exec phpunit -- tests/SpecificTest.php
```

### Step 5: Run full test suite

Run the full suite from the project root to confirm no regressions:

```bash
composer exec phpunit
```

Verify: All tests pass with zero errors and zero failures.

## Examples

### Example 1: Adding tests for a new helper function

**User says:** "Add tests for the maxmind_decode function"

**Actions taken:**
1. Read `src/maxmind.inc.php` to find the `maxmind_decode()` function
2. Confirm `maxmind_decode` is already extracted in `tests/helpers/maxmind_functions.php` (if not, extract it)
3. Confirm `myadmin_unstringify` stub exists in `tests/bootstrap.php`
4. Create `tests/MaxmindDecodeTest.php`:

```php
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
}
```

5. Run `composer exec phpunit -- tests/MaxmindDecodeTest.php` — all pass
6. Run `composer exec phpunit` — full suite passes

### Example 2: Adding a stub for a new MyAdmin function

**User says:** "Write tests for a function that calls `myadmin_log`"

**Actions taken:**
1. Add stub to `tests/bootstrap.php`:
```php
if (!function_exists('myadmin_log')) {
    function myadmin_log(string $module, string $level, string $message, int $line, string $file): void
    {
        // No-op stub for testing
    }
}
```
2. Extract the testable function into `tests/helpers/maxmind_functions.php` with `if (!function_exists(...))` guard
3. Write the test file following Step 3-4 patterns
4. Run full suite with `composer exec phpunit`

## Common Issues

**Error: `Call to undefined function myadmin_unstringify()`**
1. Verify `tests/bootstrap.php` contains the `myadmin_unstringify` stub
2. Verify `phpunit.xml.dist` has `bootstrap="tests/bootstrap.php"`
3. Run with explicit bootstrap: `composer exec phpunit -- --bootstrap tests/bootstrap.php`

**Error: `Call to undefined function get_maxmind_field_descriptions()`**
1. Check that `tests/helpers/maxmind_functions.php` defines the function
2. Check that `tests/bootstrap.php` has `require_once __DIR__ . '/helpers/maxmind_functions.php';`

**Error: `Class "Detain\MyAdminMaxMind\Plugin" not found`**
1. Run `composer install` to generate autoloader
2. Verify `composer.json` has PSR-4 autoload mapping: `"Detain\\MyAdminMaxMind\\" : "src/"`
3. Run `composer dump-autoload`

**Error: `PHPUnit\Framework\TestCase not found`**
1. Run `composer install` — `phpunit/phpunit` is a dev dependency
2. Verify `composer.json` has `"phpunit/phpunit": "^9.6"` in `require-dev`

**Test file not discovered by PHPUnit:**
1. File must be in `tests/` directory (not a subdirectory unless configured)
2. File must end with `Test.php` (case-sensitive)
3. Class name must match filename
4. Class must extend `TestCase`

**Cannot test function that uses database/globals:**
1. Do NOT try to test functions with heavy MyAdmin dependencies directly
2. Extract the pure logic into a standalone function in `tests/helpers/maxmind_functions.php`
3. Stub any remaining global function calls in `tests/bootstrap.php` with `if (!function_exists(...))` guards
4. Test the extracted function instead
