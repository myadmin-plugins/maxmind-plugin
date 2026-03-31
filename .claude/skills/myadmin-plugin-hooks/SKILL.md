---
name: myadmin-plugin-hooks
description: Registers new event hooks or modifies existing ones in `src/Plugin.php` using Symfony EventDispatcher GenericEvent. Handles getHooks(), getSettings(), getRequirements(), getMenu(). Use when user says 'add hook', 'register event', 'add setting', 'add menu item', 'add requirement', 'add page requirement'. Do NOT use for non-plugin PHP files, procedural code in public_html/, or API classes in include/Api/.
---
# MyAdmin Plugin Hooks

## Critical

- **All hook handler methods MUST be `public static` and accept exactly one parameter: `\Symfony\Component\EventDispatcher\GenericEvent $event`.**
- **Every event name registered in `getHooks()` MUST have a corresponding static method on the same class.** The mapping uses `[__CLASS__, 'methodName']` — never use closures or instance methods.
- **Never modify `getHooks()` to return anything other than an associative array** of event-name-string => `[__CLASS__, 'methodName']` pairs.
- **All user-facing strings MUST be wrapped in `_()` for gettext i18n.** Example: `_('Enable MaxMind')`, not `'Enable MaxMind'`.
- **Settings constants** (e.g., `MAXMIND_ENABLE`) must use `defined()` guard for text/password settings: `(defined('CONST_NAME') ? CONST_NAME : '')`.
- **File paths in `add_requirement()` / `add_page_requirement()`** are relative to the MyAdmin include directory, prefixed with `/../vendor/detain/{package-name}/src/`.

## Instructions

### Step 1: Identify the hook type needed

MyAdmin plugins support these standard event hooks (as seen in `src/Plugin.php`):

| Event Name | Handler Method | Subject Object | Purpose |
|---|---|---|---|
| `system.settings` | `getSettings` | `\MyAdmin\Settings` | Register admin settings (radio, text, password) |
| `function.requirements` | `getRequirements` | `\MyAdmin\Plugins\Loader` | Register function/page requirements for lazy loading |
| Menu event | `getMenu` | Menu object | Add links to admin sidebar/menus |

Verify: Confirm which event type matches your task before proceeding.

### Step 2: Register the hook in `getHooks()`

Open `src/Plugin.php`. Add a new entry to the array returned by `getHooks()`:

```php
public static function getHooks()
{
    return [
        'system.settings' => [__CLASS__, 'getSettings'],
        'function.requirements' => [__CLASS__, 'getRequirements'],
        // existing menu hook and any new hooks:
        'event.name' => [__CLASS__, 'handlerMethodName'],
    ];
}
```

Verify: The event name string matches an event dispatched by the MyAdmin core (check `include/config/hooks.json` or `run_event()` calls in the main codebase).

### Step 3: Create the handler method

Add the corresponding `public static` method to the Plugin class in `src/Plugin.php`. It MUST:
- Accept `GenericEvent $event` as its only parameter
- Use `$event->getSubject()` to get the context object
- Have the `use Symfony\Component\EventDispatcher\GenericEvent;` import at the top of the file

Verify: The method name exactly matches what you registered in `getHooks()`.

### Step 4a: Adding settings (system.settings hook)

The `$settings` object from `$event->getSubject()` supports these methods (see `src/Plugin.php` for existing examples):

```php
public static function getSettings(GenericEvent $event)
{
    /** @var \MyAdmin\Settings $settings */
    $settings = $event->getSubject();

    // Radio (boolean toggle):
    $settings->add_radio_setting(
        _('Category'),           // settings group
        _('Subcategory'),        // settings subgroup
        'setting_key',           // unique setting key (snake_case)
        _('Label'),              // display label
        _('Description'),        // tooltip/description
        CONSTANT_DEFAULT,        // default value (reference a defined() constant)
        [true, false],           // possible values
        ['Enabled', 'Disabled']  // display labels for values
    );

    // Text input:
    $settings->add_text_setting(
        _('Category'),
        _('Subcategory'),
        'setting_key',
        _('Label'),
        _('Description'),
        (defined('CONSTANT_NAME') ? CONSTANT_NAME : '')
    );

    // Password input:
    $settings->add_password_setting(
        _('Category'),
        _('Subcategory'),
        'setting_key',
        _('Label'),
        _('Description'),
        (defined('CONSTANT_NAME') ? CONSTANT_NAME : '')
    );
}
```

Verify: Each setting key is unique across all plugins. Use a plugin-specific prefix (e.g., `maxmind_`, `paypal_`).

### Step 4b: Adding function requirements (function.requirements hook)

The `$loader` object supports two registration methods (see `src/Plugin.php` `getRequirements()` for live examples):

```php
public static function getRequirements(GenericEvent $event)
{
    /** @var \MyAdmin\Plugins\Loader $loader */
    $loader = $event->getSubject();

    // Page requirement (renders a page — maps to choice=none.function_name URL):
    $loader->add_page_requirement(
        'function_name',
        '/../vendor/detain/myadmin-{plugin-name}/src/file.php'
    );

    // Function requirement (utility function loaded on demand):
    $loader->add_requirement(
        'function_name',
        '/../vendor/detain/myadmin-{plugin-name}/src/file.php'
    );
}
```

Verify:
- The function name matches the actual function defined in the referenced PHP file.
- The file path starts with `/../vendor/detain/` and points to an existing file in `src/`.
- Page requirements correspond to functions that render output; regular requirements are for utility functions.

### Step 4c: Adding menu items (menu event hook)

```php
public static function getMenu(GenericEvent $event)
{
    $menu = $event->getSubject();
    if ($GLOBALS['tf']->ima == 'admin') {
        function_requirements('has_acl');
        if (has_acl('client_billing')) {
            $menu->add_link(
                'billing',                                    // menu section
                'choice=none.page_function_name',             // URL choice parameter
                '/images/myadmin/icon.png',                   // icon path
                _('Menu Item Label')                          // display text
            );
        }
    }
}
```

Verify:
- The `choice=none.page_function_name` matches a page requirement registered in `getRequirements()`.
- ACL checks are in place — always guard admin menu items with `$GLOBALS['tf']->ima == 'admin'` and `has_acl()`.
- The ACL permission string (e.g., `client_billing`, `view_customer`) exists in the system.

### Step 5: Update tests

If you added a new hook, update `tests/PluginTest.php`:

1. Update `testGetHooksContainsExpectedKeys` to assert the new key exists
2. Update `testGetHooksReturnsThreeHooks` count (change `assertCount(3, ...)` to the new count)
3. Add a test that the new hook points to the correct method:
   ```php
   public function testNewEventHookPointsToHandler(): void
   {
       $hooks = Plugin::getHooks();
       $this->assertSame([Plugin::class, 'handlerMethodName'], $hooks['event.name']);
   }
   ```
4. Add a signature test for the new handler method:
   ```php
   public function testHandlerMethodSignature(): void
   {
       $reflection = new ReflectionClass(Plugin::class);
       $method = $reflection->getMethod('handlerMethodName');
       $params = $method->getParameters();
       $this->assertCount(1, $params);
       $this->assertSame('event', $params[0]->getName());
       $type = $params[0]->getType();
       $this->assertNotNull($type);
       $this->assertSame(GenericEvent::class, $type->getName());
   }
   ```

Also update `tests/PluginClassStructureTest.php`:
- Update `testPublicMethodCount` to include the new method in `$expectedMethods`

Verify: Run `composer exec phpunit` and confirm all tests pass.

### Step 6: Validate the complete Plugin class

After all changes, verify:
1. `getHooks()` returns a valid array with no duplicate event names
2. Every value in the array is `[__CLASS__, 'existingMethodName']`
3. Every handler method is `public static` and accepts `GenericEvent $event`
4. The `use Symfony\Component\EventDispatcher\GenericEvent;` import is present
5. Run the tests:

```bash
composer exec phpunit
```

## Examples

### Example 1: Add a new admin page with menu item and settings

**User says:** "Add a maxmind_lookup page to the plugin with a menu link and an enable/disable setting."

**Actions taken:**

1. Create `src/maxmind_lookup.php` with a `maxmind_lookup()` function
2. In `src/Plugin.php`, add to `getRequirements()`:
   ```php
   $loader->add_page_requirement('maxmind_lookup', '/../vendor/detain/myadmin-maxmind-plugin/src/maxmind_lookup.php');
   ```
3. In `getMenu()`, add inside the ACL check:
   ```php
   $menu->add_link('billing', 'choice=none.maxmind_lookup', '/images/myadmin/search.png', _('MaxMind Lookup'));
   ```
4. In `getSettings()`, add:
   ```php
   $settings->add_radio_setting(_('Security & Fraud'), _('MaxMind Fraud Detection'), 'maxmind_lookup_enable', _('Enable MaxMind Lookup'), _('Enable MaxMind Lookup'), MAXMIND_LOOKUP_ENABLE, [true, false], ['Enabled', 'Disabled']);
   ```
5. Run `composer exec phpunit` to confirm tests pass.

**Result:** The new page is lazy-loaded when accessed via `choice=none.maxmind_lookup`, appears in the admin billing menu, and has an admin toggle setting.

### Example 2: Register a new event hook

**User says:** "Add a hook for the `billing.chargeback` event."

**Actions taken:**

1. Add to `getHooks()` in `src/Plugin.php`:
   ```php
   'billing.chargeback' => [__CLASS__, 'handleChargeback'],
   ```
2. Add the handler method:
   ```php
   public static function handleChargeback(GenericEvent $event)
   {
       $data = $event->getSubject();
       // process chargeback data
   }
   ```
3. Update test counts and add test for new hook in `tests/PluginTest.php`
4. Update `$expectedMethods` in `tests/PluginClassStructureTest.php`
5. Run `composer exec phpunit`

**Result:** Plugin now listens for `billing.chargeback` events dispatched via `run_event('billing.chargeback', $data)`.

## Common Issues

### "Call to undefined method" when hook fires
The method name in `getHooks()` doesn't match the actual method. Check for typos — the array value must be `[__CLASS__, 'exactMethodName']` where `exactMethodName` exists as a `public static` method on the class.

### Settings not appearing in admin panel
1. Verify the `system.settings` hook is registered in `getHooks()`
2. Check that the category/subcategory strings match existing groups (e.g., `_('Security & Fraud')`) or create new ones intentionally
3. Verify the constant referenced as the default value is defined somewhere, or use the `defined()` guard pattern: `(defined('CONST') ? CONST : '')`

### Menu item not showing up
1. Verify you're logged in as admin (`$GLOBALS['tf']->ima == 'admin'`)
2. Check that the ACL permission exists and is assigned: `has_acl('permission_name')`
3. Verify the `choice=none.function_name` matches a registered page requirement

### Page requirement returns blank page
The function name passed to `add_page_requirement()` must match the actual function name defined in the PHP file. The file is included and the function is called — if the name doesn't match, nothing renders. Check the function definition in the target source file:
```bash
grep -n 'function maxmind_compare' src/maxmind_compare.php
```

### Test failure: "Failed asserting that 3 is identical to 4"
You added a hook but didn't update `testGetHooksReturnsThreeHooks` in `tests/PluginTest.php`. Change the `assertCount()` value to match the new hook count.

### Test failure: "expectedMethods does not match"
You added a new public method but didn't update `testPublicMethodCount` in `tests/PluginClassStructureTest.php`. Add the new method name to the `$expectedMethods` array.
