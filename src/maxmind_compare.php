<?php
	/**
	 * Administrative Functionality
	 * @author Joe Huss <detain@interserver.net>
	 * @copyright 2018
	 * @package MyAdmin
	 * @category Admin
	 */

	function maxmind_compare() {
		function_requirements('has_acl');
		if ($GLOBALS['tf']->ima != 'admin' || !has_acl('view_customer')) {
			dialog('Not admin', 'Not Admin or you lack the permissions to view this page.');
			return false;
		}
		$limit = 500;
		$title = 'Compare MaxMind score vs riskScore in last ' . $limit . ' Accounts';
		page_title($title);
		add_output('<h3>'.$title.'</h3>');
		$db = $GLOBALS['tf']->db;
		$db->query("select account_id, account_lid, account_status, account_value from accounts left join accounts_ext using (account_id) where account_key='maxmind' order by account_id desc limit {$limit}", __LINE__, __FILE__);
		while ($db->next_record(MYSQL_ASSOC)) {
			$maxmind = @myadmin_unstringify($db->Record['account_value']);
			if ($maxmind === false)
				$maxmind = myadmin_unstringify($db->Record['account_value']);
			$db->Record['score'] = $maxmind['score'];
			$db->Record['riskScore'] = $maxmind['riskScore'];
			unset($db->Record['account_value']);
			if (!isset($header)) {
				$header = [];
				foreach (array_keys($db->Record) as $key)
					$header[] = ucwords(str_replace(['_', ' ip', 'vps'], [' ', ' IP', 'VPS'], $key));
			}
			$rows[] = $db->Record;
		}
		$bootstrap = true;
		if ($bootstrap == true) {
			add_js('bootstrap');
			add_js('tablesorter_bootstrap');
		} else {
			add_js('tablesorter');
		}
		$smarty = new TFSmarty;
		$smarty->debugging = true;
		$smarty->assign('table_header', $header);
		$smarty->assign('sortcol', 4);
		$smarty->assign('sortdir', 1);
		$smarty->assign('textextraction', "'complex'");
		$smarty->assign('table_rows', $rows);
		$tablesorter = str_replace(
			[
			'mainelement',
			'itemtable',
			'itempager'
			], [
			'histmainelement',
			'histtable',
			'histpager'
		                           ], ($bootstrap == true ? $smarty->fetch('tablesorter/tablesorter_bootstrap.tpl') : $smarty->fetch('tablesorter/tablesorter.tpl')));
		add_output($tablesorter);
	}
