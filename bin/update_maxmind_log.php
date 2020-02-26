#!/usr/bin/env php
<?php
/**
* Wipes vps
*
* Wipes a vps from the system entirely, repeat invoices, invoices, everything.
* @author Joe Huss <detain@interserver.net>
* @package MyAdmin
* @category wipe_vps
* @copyright 2020
*/


	require_once __DIR__.'/../../include/functions.inc.php';
	$webpage = false;
	define('VERBOSE_MODE', false);
	global $console;
	function_requirements('maxmind_decode');

	$db = clone $GLOBALS['tf']->db;
	$db2 = clone $GLOBALS['tf']->db;
	$continue = true;
	$offset = 0;
	$limit = 10000;
	$db->query('truncate maxmind_output');
	$ids = [];
	echo '0.';
	$db->query('describe maxmind_output');
	$field = [];
	while ($db->next_record(MYSQL_ASSOC)) {
		$fields[] = $db->Record['Field'];
	}
	$queries = '';
	while ($continue == true) {
		$db->query("select * from accounts_ext where account_key='maxmind' limit {$offset}, {$limit}", __LINE__, __FILE__);
		if ($db->num_rows() < $limit) {
			$continue = false;
		}
		while ($db->next_record(MYSQL_ASSOC)) {
			if (trim($db->Record['account_value']) == '') {
				continue;
			}
			$maxmind = maxmind_decode($db->Record['account_value']);
			if (!is_array($maxmind) || count($maxmind) == 0 || !isset($maxmind['maxmindID']) || trim($maxmind['maxmindID']) == '') {
				continue;
			}
			if (in_array($maxmind['maxmindID'], $ids)) {
				continue;
			}
			$ids[] = $maxmind['maxmindID'];
			$omaxmind = $maxmind;
			$maxmind = ['account_id' => $db->Record['account_id'], 'maxmindid' => ''];
			if (isset($omaxmind['original_score'])) {
				$omaxmind['score'] = $omaxmind['original_score'];
				unset($omaxmind['original_score']);
			}
			foreach ($omaxmind as $key => $value) {
				if (trim($key) != '' && !in_array(strtolower($key), ['female_name', 'spamscore'])) {
					if (in_array(strtolower($key), $fields)) {
						$maxmind[strtolower($key)] = $value;
					} else {
						echo "skipping field {$key}";
					}
				}
			}
			$query = make_insert_query('maxmind_output', $maxmind);
			$queries .= "{$query};\n";
			//$db2->query($query, __LINE__, __FILE__);
		}
		$offset += $limit;
		echo $offset. '.';
	}
	file_put_contents('maxmind_queries.sql', $queries);
	echo "done\n";
