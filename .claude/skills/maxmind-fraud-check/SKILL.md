---
name: maxmind-fraud-check
description: Implements or modifies MaxMind minFraud fraud detection logic including score thresholds, risk penalties, country/name adjustments, and CC disabling rules in src/maxmind.inc.php. Use when user says 'add fraud rule', 'change score threshold', 'modify risk penalty', 'add country penalty', or edits update_maxmind()/update_maxmind_noaccount(). Do NOT use for UI changes (view_maxmind.php, maxmind_compare.php), test modifications, Plugin.php hook registration, or maxmind_lookup.php modern SDK work.
---
# MaxMind Fraud Check

## Critical

- **All fraud logic lives in `src/maxmind.inc.php`** in two procedural functions: `update_maxmind()` (account-based) and `update_maxmind_noaccount()` (data-array-based). Both must stay in sync when adding new rules.
- **Thresholds are defined as PHP constants** (e.g., `MAXMIND_SCORE_LOCK`, `MAXMIND_RISKSCORE_DISABLE_CC`). Never hardcode numeric thresholds — always use or define a constant. Constants are registered as admin settings via the `getSettings()` method in `src/Plugin.php`.
- **The response has two score fields**: `score` (legacy, capped at 10) and `riskScore` (probability-based, 0-100). The `score` field may not exist in newer API responses — always guard with `isset($response['score'])` before accessing it.
- **CC disabling sets TWO fields**: `$new_data['disable_cc'] = 1` AND `$new_data['payment_method'] = 'paypal'`. Never set one without the other.
- **Account locking checks for old invoices first** (line 304): if the customer has a paid invoice older than 1 day, the account is NOT disabled even on high scores. Preserve this safeguard.
- **Logging is mandatory** for every fraud action. Use `myadmin_log('maxmind', $level, $message, __LINE__, __FILE__)` with appropriate levels: `'info'` for data, `'notice'` for skips, `'warning'` for CC disables / account locks.

## Instructions

### Step 1: Identify which function(s) need modification

Determine if the change applies to:
- `update_maxmind($custid, $ip, $ccIdx)` — called for existing accounts (line 155 in `src/maxmind.inc.php`)
- `update_maxmind_noaccount($data)` — called for pre-account signups (line 346 in `src/maxmind.inc.php`)
- Both — most penalty/threshold rules must be mirrored in both functions

**Verify:** Read both functions in `src/maxmind.inc.php` to confirm current state before editing.

### Step 2: Define the constant for any new threshold

If adding a new threshold or penalty amount, define a new `MAXMIND_*` constant. Register it in the `getSettings()` method of `src/Plugin.php` following the existing pattern:

```php
// For numeric thresholds (in src/Plugin.php getSettings method):
$settings->add_text_setting(
    _('Security & Fraud'),
    _('MaxMind Fraud Detection'),
    'maxmind_your_new_setting',           // lowercase, underscored
    _('Description for Admin UI'),
    _('Description for Admin UI'),
    (defined('MAXMIND_YOUR_NEW_SETTING') ? MAXMIND_YOUR_NEW_SETTING : '')
);

// For boolean toggles:
$settings->add_radio_setting(
    _('Security & Fraud'),
    _('MaxMind Fraud Detection'),
    'maxmind_your_new_toggle',
    _('Enable Your Feature'),
    _('Enable Your Feature'),
    MAXMIND_YOUR_NEW_TOGGLE,
    [true, false],
    ['Enabled', 'Disabled']
);
```

**Verify:** The setting name (2nd arg to `add_text_setting`/`add_radio_setting`) matches the constant name in lowercase. Check no duplicate setting name exists in `src/Plugin.php`.

### Step 3: Add the fraud rule in `update_maxmind()`

Penalties and rules follow a specific order in `src/maxmind.inc.php`. Place new rules in the correct position:

1. **Country penalties** (after API response, ~line 237): adjust `score`/`riskScore` based on billing country
2. **Name-based penalties** (after country, ~line 245): check against `$female_names` array from `src/female_names.inc.php`
3. **Distance penalty** (~line 272): adjust `riskScore` based on IP-to-billing distance
4. **Account lock check** (~line 303): `disable_account()` if scores exceed lock thresholds
5. **CC disable check** (~line 313): disable CC if scores exceed CC thresholds
6. **No-response check** (~line 318): disable CC if MaxMind returned blank scores
7. **Fraud email alert** (~line 325): email admin if scores exceed alert thresholds
8. **Low queries alert** (~line 330): email admin if queries running low

Pattern for a new penalty adjustment:
```php
// After the API response is received, before storing results
if ($your_condition) {
    if (isset($response['score']) && $response['score'] < MAXMIND_YOUR_SCORE_LIMIT) {
        $response['score'] += MAXMIND_YOUR_SCORE_PENALTY;
    }
    if (isset($response['riskScore']) && $response['riskScore'] <= MAXMIND_YOUR_RISKSCORE_LIMIT) {
        $response['riskScore'] += MAXMIND_YOUR_RISKSCORE_PENALTY;
    }
}
```

Pattern for a new CC-disabling rule:
```php
if ($response['someField'] >= MAXMIND_YOUR_THRESHOLD) {
    myadmin_log('maxmind', 'warning', "update_maxmind({$custid}, {$ip}) Your reason message", __LINE__, __FILE__);
    $new_data['disable_cc'] = 1;
    $new_data['payment_method'] = 'paypal';
}
```

Pattern for a new account-locking rule (must check old invoices first):
```php
if ($your_high_risk_condition) {
    $db->query("select * from invoices where invoices_type=1 and invoices_paid=1 and invoices_custid={$custid} and invoices_date <= date_sub(now(), INTERVAL 1 DAY) limit 1", __LINE__, __FILE__);
    if ($db->num_rows() == 0) {
        myadmin_log('maxmind', 'warning', "update_maxmind({$custid}, {$ip}) Reason, Disabling Account", __LINE__, __FILE__);
        function_requirements('disable_account');
        disable_account($custid);
    } else {
        myadmin_log('maxmind', 'warning', "update_maxmind({$custid}, {$ip}) Would disable but has old invoices", __LINE__, __FILE__);
    }
}
```

**Verify:** Every branch that modifies `$response['score']` or `$response['riskScore']` guards with `isset()` for `score`. Every CC disable sets both `disable_cc` and `payment_method`. Every action has a `myadmin_log()` call.

### Step 4: Mirror the rule in `update_maxmind_noaccount()`

The no-account variant in `src/maxmind.inc.php` stores results differently:
- Uses `$data` array instead of `$new_data` + `$GLOBALS['tf']->accounts->update()`
- Sets `$data['status'] = 'locked'` instead of calling `disable_account()`
- Uses `myadmin_stringify()` instead of `json_encode()` for storage
- Country/name penalties are inside a nested `if` block checking for `['br', 'tw']` countries (~line 420)
- Distance formula differs: `floor($response['distance'] / 1000)` vs `floor(floatval($response['distance']) / 100)` in the main function

**Verify:** The same threshold constants are used in both functions. The penalty logic produces equivalent results.

### Step 5: Register the setting constant in `src/Plugin.php` (if new constant added)

Add the setting to the `getSettings()` method in `src/Plugin.php`. Group it with related settings (country penalties near other country settings, score thresholds near other thresholds).

**Verify:** Run the test suite to ensure tests still pass. The `tests/PluginTest.php` tests check hook structure but not individual settings, so focus on ensuring no syntax errors:

```bash
composer exec phpunit
```

### Step 6: Test the change

Run the test suite from the project root:

```bash
composer exec phpunit
```

If you added new functions or changed `maxmind_decode`/`get_maxmind_field_descriptions`, check relevant test files in `tests/`:
- `tests/MaxmindDecodeTest.php` — tests for `maxmind_decode()`
- `tests/MaxmindFieldDescriptionsTest.php` — tests for `get_maxmind_field_descriptions()`
- `tests/PluginTest.php` — tests for hook structure
- `tests/PluginClassStructureTest.php` — tests for class structure

## Examples

### User says: "Add a penalty for proxy scores above 3"

**Actions:**
1. Read `src/maxmind.inc.php` to find where proxy-related checks exist
2. Define constants `MAXMIND_PROXY_PENALTY_ENABLE`, `MAXMIND_PROXY_SCORE_PENALTY`, `MAXMIND_PROXY_RISKSCORE_PENALTY`, `MAXMIND_PROXY_THRESHOLD`
3. In `update_maxmind()` in `src/maxmind.inc.php`, after the distance penalty block (~line 275), add:
```php
if (MAXMIND_PROXY_PENALTY_ENABLE == true && isset($response['proxyScore']) && $response['proxyScore'] >= MAXMIND_PROXY_THRESHOLD) {
    if (isset($response['score'])) {
        $response['original_score'] = $response['score'];
        $response['score'] += MAXMIND_PROXY_SCORE_PENALTY;
    }
    $response['original_riskScore'] = $response['riskScore'];
    $response['riskScore'] += MAXMIND_PROXY_RISKSCORE_PENALTY;
    myadmin_log('maxmind', 'info', "update_maxmind({$custid}, {$ip}) Proxy score {$response['proxyScore']} >= " . MAXMIND_PROXY_THRESHOLD . ", adding penalty", __LINE__, __FILE__);
}
```
4. Mirror in `update_maxmind_noaccount()` in `src/maxmind.inc.php`
5. Register all four constants in the `getSettings()` method of `src/Plugin.php`
6. Run `composer exec phpunit`

**Result:** Proxy scores above the admin-configured threshold add a penalty to both `score` and `riskScore`, following the same pattern as the existing female name and country penalties.

### User says: "Change the country list to include India"

**Actions:**
1. Read `src/maxmind.inc.php` line 237 and line 420
2. Change `['br', 'tw']` to `['br', 'tw', 'in']` in both `update_maxmind()` and `update_maxmind_noaccount()`
3. Run `composer exec phpunit`

**Result:** India (`in`) is now in the high-penalty country list alongside Brazil and Taiwan.

## Common Issues

- **"Undefined constant MAXMIND_*"**: The constant hasn't been defined in the system settings. Add it to the `getSettings()` method in `src/Plugin.php` and ensure the admin has saved settings. During development, define a fallback: `(defined('MAXMIND_X') ? MAXMIND_X : default_value)`.

- **Score not changing for new rules**: Check that you're modifying `$response['riskScore']` BEFORE the line that stores it into `$new_data['maxmind_riskscore']` (~line 296 in `src/maxmind.inc.php`). If your penalty is added after the storage block, it won't be persisted.

- **Rule works in `update_maxmind()` but not signups**: The `update_maxmind_noaccount()` function has different structure — country/name penalties are inside a nested conditional block checking country codes. Verify your rule is placed inside the correct scope in `src/maxmind.inc.php`.

- **`isset($response['score'])` always false**: The legacy `score` field is deprecated by MaxMind. Newer API versions may not return it. Always write rules that work with `riskScore` alone and treat `score` as optional.

- **CC not actually disabled**: Both `disable_cc = 1` AND `payment_method = 'paypal'` must be set. Check that both are assigned in your code path. Also verify `$GLOBALS['tf']->accounts->update($custid, $new_data)` is called AFTER your assignments (it's at line 335 in `src/maxmind.inc.php`, end of function).

- **Account not locked despite high score**: The invoice check at line 304 in `src/maxmind.inc.php` prevents locking accounts that have paid invoices older than 1 day. This is intentional — established customers are exempt from auto-locking.
