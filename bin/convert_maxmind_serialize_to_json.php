#!/usr/bin/env php
<?php
/**
* Converts the serialized MaxMind data in the accounts_ext table to be stored in a JSON format
*
* @author Joe Huss <detain@interserver.net>
* @package MyAdmin
* @category Scripts
* @copyright 2020
*/

require_once __DIR__.'/../../include/functions.inc.php';
$webpage = false;
define('VERBOSE_MODE', false);
global $console;

$db = get_module_db('default');
$db2 = get_module_db('default');
$start = time();

ini_set('zend.multibyte', 'On');
ini_set('zend.script_encoding', 'UTF-8');
ini_set('default_charset', 'UTF-8');
ini_set('mbstring.internal_encoding', 'UTF-8');
ini_set('mbstring.http_input', 'UTF-8');
ini_set('mbstring.http_output', 'UTF-8');
ini_set('iconv.input_encoding', 'UTF-8');
ini_set('iconv.internal_encoding', 'UTF-8');
ini_set('iconv.output_encoding', 'UTF-8');

$db->query('SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci, COLLATION_CONNECTION = utf8mb4_unicode_ci, COLLATION_DATABASE = utf8mb4_unicode_ci, COLLATION_SERVER = utf8mb4_unicode_ci;');
$db2->query('SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci, COLLATION_CONNECTION = utf8mb4_unicode_ci, COLLATION_DATABASE = utf8mb4_unicode_ci, COLLATION_SERVER = utf8mb4_unicode_ci;');
$db->query("select * from accounts_ext where account_key='maxmind'", __LINE__, __FILE__);
$bad = 0;
$good = 0;
$skipped = 0;
while ($db->next_record(MYSQL_ASSOC)) {
    if (mb_substr($db->Record['account_value'], 0, 1) == '{') {
        $skipped++;
        continue;
    }
    $maxmind = @myadmin_unstringify($db->Record['account_value']);
    if ($maxmind === false) {
        if (preg_match_all('/s:[0-9]*:\\\*"(?P<key>.*)\\\*";s:[0-9]*:\\\*"(?P<value>.*)\\\*";/mUu', $db->Record['account_value'], $matches)) {
            $maxmind = [];
            foreach ($matches['key'] as $idx => $key) {
                $value = $matches['value'][$idx];
                $maxmind[$key] = $value;
            }
        } else {
            //echo "Didnt match: {$db->Record['account_value']}\n";
        }
    }
    if ($maxmind !== false) {
        $good++;
        $new_maxmind = json_encode($maxmind);
        $db2->query("update accounts_ext set account_value='".$db2->real_escape($new_maxmind)."' where account_id={$db->Record['account_id']} and account_key='{$db->Record['account_key']}'");
    //echo "OLD:".mb_strlen($db->Record['account_value'])."\n{$db->Record['account_value']}\n";
        //echo "NEW:".mb_strlen($new_maxmind)."\n{$new_maxmind}\n";
    } else {
        echo 'Problem unserializing: ' . var_export($db->Record, true).PHP_EOL;
        //$db2->query("delete from accounts_ext where account_id={$db->Record['account_id']} and account_key='{$db->Record['account_key']}'");
        $bad++;
    }
}
$end = time();
echo "Finished, {$skipped} Skipped, {$good} Good, {$bad} Bad, ".($end - $start)." seconds\n";
