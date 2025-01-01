#!/usr/bin/env php
<?php
/**
* Wipes vps
*
* Wipes a vps from the system entirely, repeat invoices, invoices, everything.
* @author Joe Huss <detain@interserver.net>
* @package MyAdmin
* @category wipe_vps
* @copyright 2025
*/


require_once __DIR__.'/../../include/functions.inc.php';
$webpage = false;
define('VERBOSE_MODE', false);
global $console;

$found = 0;
foreach ($GLOBALS['modules'] as $module => $settings) {
    $db = get_module_db($module);
    $db2 = get_module_db($module);
    $db->query("select * from history_log where history_type='changemaxmind' and (history_new_value like '%<td%td%' or history_old_value like '%<td%td%')", __LINE__, __FILE__);
    while ($db->next_record(MYSQL_ASSOC)) {
        preg_match_all("/<tr[^>]*>[^<]*<td[^>]*>(?P<key>.*)<\/td>[^<]*<td[^>]*>(?P<data>.*)<\/td>[^<]*<\/tr>/Ux", $db->Record['history_new_value'], $matches);
        preg_match_all("/<tr[^>]*>[^<]*<td[^>]*>(?P<key>.*)<\/td>[^<]*<td[^>]*>(?P<data>.*)<\/td>[^<]*<\/tr>/Ux", $db->Record['history_old_value'], $matches2);
        if ($matches || $matches2) {
            ++$found;
            $updated = false;
            $maxmind = [];
            foreach ($matches['key'] as $idx => $key) {
                $value = trim(strip_tags($matches['data'][$idx]));
                $key = trim(strip_tags($key));
                if (!in_array($key, ['', 'Account ID', 'Account Login'])) {
                    $updated = true;
                    $maxmind[$key] = $value;
                }
            }
            $maxmind2 = [];
            foreach ($matches2['key'] as $idx => $key) {
                $value = trim(strip_tags($matches2['data'][$idx]));
                $key = trim(strip_tags($key));
                if (!in_array($key, ['', 'Account ID', 'Account Login'])) {
                    $updated = true;
                    $maxmind2[$key] = $value;
                }
            }
            if ($updated) {
                //					print_r($maxmind);
                if (count($maxmind) > 0) {
                    $maxout = json_encode($maxmind);
                } else {
                    $maxout = '';
                }
                if (count($maxmind2) > 0) {
                    $maxout2 = json_encode($maxmind2);
                } else {
                    $maxout2 = '';
                }
                $query = "update history_log set history_new_value='" . $db->real_escape($maxout) . "', history_old_value='" . $db->real_escape($maxout2) . "' where history_id='{$db->Record['history_id']}'";
                $db2->query($query, __LINE__, __FILE__);
                echo "Update $module History Log {$db->Record['history_id']} ($found)\n";
                //					echo "$query\n";
//					if ($found >= 100)
//						exit;
            }
            //				print_r($matches);
        }
    }
    $db->query("select * from accounts_ext where account_key='maxmind' and account_value like '%<td%td%'", __LINE__, __FILE__);
    while ($db->next_record(MYSQL_ASSOC)) {
        if (preg_match_all("/<tr[^>]*>[^<]*<td[^>]*>(?P<key>.*)<\/td>[^<]*<td[^>]*>(?P<data>.*)<\/td>[^<]*<\/tr>/Ux", $db->Record['account_value'], $matches)) {
            ++$found;
            $updated = false;
            $maxmind = [];
            foreach ($matches['key'] as $idx => $key) {
                $value = trim(strip_tags($matches['data'][$idx]));
                $key = trim(strip_tags($key));
                if (!in_array($key, ['', 'Account ID', 'Account Login'])) {
                    $updated = true;
                    $maxmind[$key] = $value;
                }
            }
            if ($updated) {
                //					print_r($maxmind);
                $query = "update accounts_ext set account_value='" . $db->real_escape(json_encode($maxmind)) . "' where account_key='maxmind' and account_id='{$db->Record['account_id']}'";
                $db2->query($query, __LINE__, __FILE__);
                echo "Update $module {$db->Record['account_id']} ($found)\n";
                //					echo "$query\n";
            }
            //				print_r($matches);
        }
    }
}
