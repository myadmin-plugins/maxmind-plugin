---
name: maxmind-admin-page
description: Creates or modifies admin UI pages like `src/view_maxmind.php` or `src/maxmind_compare.php`. Pattern: ACL check with `has_acl()`, `get_module_db()` queries, Smarty template rendering with `TFSmarty`. Use when user says 'add admin page', 'new view', 'admin UI', 'new admin function'. Do NOT use for API-only changes, CLI scripts in `bin/`, or procedural fraud logic in `maxmind.inc.php`.
---
# MaxMind Admin Page

## Critical

- Every admin page function MUST start with an ACL check. Without it, any user can access the page:
  ```php
  function_requirements('has_acl');
  if ($GLOBALS['tf']->ima != 'admin' || !has_acl('view_customer')) {
      dialog('Not admin', 'Not Admin or you lack the permissions to view this page.');
      return false;
  }
  ```
- ACL permission names used in this plugin: `view_customer` (for viewing), `client_billing` (for billing/menu). Pick the one matching your page's purpose.
- Never use PDO. Use `$GLOBALS['tf']->db` or `get_module_db($module)` for all queries.
- Always pass `__LINE__, __FILE__` as the second and third arguments to `$db->query()`.
- Escape all user input with `$db->real_escape()` before interpolating into SQL.

## Instructions

1. **Create the page function file** in `src/` following the naming convention `src/{page_name}.php`. The file contains a single procedural function named after the page. Use this boilerplate:

   ```php
   <?php

       /**
        * Administrative Functionality
        * @author Joe Huss <detain@interserver.net>
        * @copyright 2025
        * @package MyAdmin
        * @category Admin
        */

       function your_page_name()
       {
           function_requirements('has_acl');
           if ($GLOBALS['tf']->ima != 'admin' || !has_acl('view_customer')) {
               dialog('Not admin', 'Not Admin or you lack the permissions to view this page.');
               return false;
           }
           page_title('Your Page Title');

           // ... page logic here ...
       }
   ```

   Verify: The file exists at `src/{page_name}.php` and the function name matches the filename (without `.php`).

2. **Add database queries** if needed. Use `$GLOBALS['tf']->db` for the main accounts DB, or `get_module_db($module)` for module-specific databases:

   ```php
   $db = $GLOBALS['tf']->db;
   $db->query("SELECT account_id, account_lid FROM accounts WHERE account_id = {$id}", __LINE__, __FILE__);
   while ($db->next_record(MYSQL_ASSOC)) {
       $rows[] = $db->Record;
   }
   ```

   For reading request parameters:
   ```php
   $customer = $GLOBALS['tf']->variables->request['customer'];
   $module = get_module_name(($GLOBALS['tf']->variables->request['module'] ?? 'default'));
   ```

   Verify: Every `$db->query()` call includes `__LINE__, __FILE__` arguments.

3. **Add JavaScript dependencies** using `add_js()` before rendering. Common dependencies used in this plugin:

   ```php
   add_js('bootstrap');              // Bootstrap JS
   add_js('isotope');                // Isotope layout (used in view_maxmind)
   add_js('tablesorter_bootstrap');  // Sortable tables with Bootstrap styling
   ```

   For inline JS/CSS, use `$GLOBALS['tf']->add_html_head_js()` with the full `<script>` or `<style>` tag as a string.

4. **Render output with Smarty** using `TFSmarty`. Two patterns exist:

   **Pattern A — Custom template** (like `view_maxmind`):
   ```php
   $smarty = new TFSmarty();
   $smarty->assign('variable_name', $data);
   add_output($smarty->fetch('billing/your_template.tpl'));
   ```

   **Pattern B — Tablesorter template** (like `maxmind_compare`):
   ```php
   $smarty = new TFSmarty();
   $smarty->assign('table_header', $header);  // array of column names
   $smarty->assign('table_rows', $rows);      // array of associative arrays
   $smarty->assign('sortcol', 0);             // default sort column index
   $smarty->assign('sortdir', 1);             // 0=asc, 1=desc
   $smarty->assign('textextraction', "'complex'");
   $tablesorter = str_replace(
       ['mainelement', 'itemtable', 'itempager'],
       ['youruniqueid_mainelement', 'youruniqueid_table', 'youruniqueid_pager'],
       $smarty->fetch('tablesorter/tablesorter_bootstrap.tpl')
   );
   add_output($tablesorter);
   ```

   The `str_replace` on element IDs is required when multiple tablesorter instances may appear on the same page.

   **Pattern C — TFTable** (simple key-value display):
   ```php
   $table = new TFTable();
   $table->set_title('Title');
   // ... add rows ...
   ```

   Verify: Output is added via `add_output()`, not `echo` or `print`.

5. **Register the page in Plugin.php** — uses Step 1 output. Add the page requirement in `getRequirements()` and optionally a menu link in `getMenu()`:

   In `Plugin::getRequirements()`:
   ```php
   $loader->add_page_requirement('your_page_name', '/../vendor/detain/myadmin-maxmind-plugin/src/your_page_name.php');
   ```

   In `Plugin::getMenu()` (if the page needs a sidebar link):
   ```php
   if (has_acl('client_billing')) {
       $menu->add_link('billing', 'choice=none.your_page_name', '/images/myadmin/icon.png', _('Your Page Title'));
   }
   ```

   The `choice=none.your_page_name` route maps to the function name registered via `add_page_requirement`.

   Verify: The function name in `add_page_requirement` exactly matches the function defined in Step 1.

6. **Add `require_once` for dependencies** if your page uses functions from other files in this plugin:
   ```php
   require_once __DIR__.'/maxmind.inc.php';
   ```
   Or use lazy loading:
   ```php
   function_requirements('get_maxmind_field_descriptions');
   ```

   Verify: Run `vendor/bin/phpunit` to ensure no regressions.

## Examples

### Example: Add a page that lists MaxMind scores for locked accounts

**User says:** "Add an admin page that shows MaxMind scores for all locked accounts"

**Actions taken:**

1. Create `src/maxmind_locked_accounts.php`:

```php
<?php

    /**
     * Administrative Functionality
     * @author Joe Huss <detain@interserver.net>
     * @copyright 2025
     * @package MyAdmin
     * @category Admin
     */

    function maxmind_locked_accounts()
    {
        function_requirements('has_acl');
        if ($GLOBALS['tf']->ima != 'admin' || !has_acl('view_customer')) {
            dialog('Not admin', 'Not Admin or you lack the permissions to view this page.');
            return false;
        }
        $limit = 200;
        $title = 'MaxMind Scores for Locked Accounts (last ' . $limit . ')';
        page_title($title);
        add_output('<h3>'.$title.'</h3>');
        $db = $GLOBALS['tf']->db;
        $db->query("SELECT account_id, account_lid, account_status, account_value FROM accounts LEFT JOIN accounts_ext USING (account_id) WHERE account_key='maxmind' AND account_status='locked' ORDER BY account_id DESC LIMIT {$limit}", __LINE__, __FILE__);
        $rows = [];
        while ($db->next_record(MYSQL_ASSOC)) {
            $maxmind = @myadmin_unstringify($db->Record['account_value']);
            $db->Record['score'] = $maxmind['score'] ?? 'N/A';
            $db->Record['riskScore'] = $maxmind['riskScore'] ?? 'N/A';
            unset($db->Record['account_value']);
            if (!isset($header)) {
                $header = [];
                foreach (array_keys($db->Record) as $key) {
                    $header[] = ucwords(str_replace(['_', ' ip'], [' ', ' IP'], $key));
                }
            }
            $rows[] = $db->Record;
        }
        add_js('bootstrap');
        add_js('tablesorter_bootstrap');
        $smarty = new TFSmarty();
        $smarty->assign('table_header', $header);
        $smarty->assign('table_rows', $rows);
        $smarty->assign('sortcol', 4);
        $smarty->assign('sortdir', 1);
        $smarty->assign('textextraction', "'complex'");
        $tablesorter = str_replace(
            ['mainelement', 'itemtable', 'itempager'],
            ['lockedmainelement', 'lockedtable', 'lockedpager'],
            $smarty->fetch('tablesorter/tablesorter_bootstrap.tpl')
        );
        add_output($tablesorter);
    }
```

2. Register in `src/Plugin.php` `getRequirements()`:
```php
$loader->add_page_requirement('maxmind_locked_accounts', '/../vendor/detain/myadmin-maxmind-plugin/src/maxmind_locked_accounts.php');
```

3. Add menu link in `getMenu()`:
```php
if (has_acl('client_billing')) {
    $menu->add_link('billing', 'choice=none.maxmind_locked_accounts', '/images/myadmin/exchange.png', _('MaxMind Locked Accounts'));
}
```

**Result:** New admin page accessible at `?choice=none.maxmind_locked_accounts` showing a sortable table of locked accounts with their MaxMind scores.

## Common Issues

- **Page shows "Not admin" dialog for valid admins:** The ACL name passed to `has_acl()` doesn't exist or the admin user lacks that permission. Check the ACL name matches one defined in the system (`view_customer`, `client_billing`). Verify with: check the admin user's ACL assignments in the database.

- **"Call to undefined function function_requirements":** The page file is being loaded directly instead of through the MyAdmin router. Admin pages must be accessed via `?choice=none.function_name` routing, not by direct PHP file inclusion.

- **Tablesorter not rendering / JS errors:** Missing `add_js('bootstrap')` and `add_js('tablesorter_bootstrap')` calls before the Smarty fetch. These must be called before `add_output()`. Also verify the `str_replace` on element IDs uses unique prefixes — duplicate IDs cause JS conflicts.

- **Empty `$db->Record` / no query results:** Check that `$db->query()` includes `__LINE__, __FILE__` as arguments. Without them, query errors are silently swallowed. Also verify the SQL is valid by testing it directly in MySQL.

- **Page not accessible / 404:** The function name in `add_page_requirement()` in `Plugin.php` must exactly match the function name defined in the PHP file AND the `choice=none.{name}` URL parameter. Triple-check all three match.

- **"Class 'TFSmarty' not found":** This class is provided by the MyAdmin core framework. If running outside the full MyAdmin environment (e.g., in tests), you need to mock or skip Smarty rendering. In tests, use `$this->markTestSkipped('Requires MyAdmin framework')`.

- **Menu link not appearing:** The `getMenu()` method wraps menu additions in an ACL check (`has_acl('client_billing')`). Make sure the logged-in admin has the correct ACL. Also verify `$GLOBALS['tf']->ima == 'admin'` is true for your session.