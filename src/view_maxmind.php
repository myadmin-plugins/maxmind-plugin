<?php

    /**
     * Administrative Functionality
     * @author Joe Huss <detain@interserver.net>
     * @copyright 2019
     * @package MyAdmin
     * @category Admin
     */
/**
 * view_maxmind()
 *
 * @return bool|void
 * @throws \Exception
 * @throws \SmartyException
 */
    function view_maxmind()
    {
        require_once __DIR__.'/maxmind.inc.php'; // This handles fraud protection
        page_title('MaxMind Credit Fraud Output');
        function_requirements('has_acl');
        if ($GLOBALS['tf']->ima != 'admin' || !has_acl('view_customer')) {
            dialog('Not admin', 'Not Admin or you lack the permissions to view this page.');
            return false;
        }
        add_js('bootstrap');
        add_js('isotope');
        $customer = $GLOBALS['tf']->variables->request['customer'];
        $module = get_module_name((isset($GLOBALS['tf']->variables->request['module']) ? $GLOBALS['tf']->variables->request['module'] : 'default'));
        $data = $GLOBALS['tf']->accounts->read($customer);
        function_requirements('get_maxmind_field_descriptions');
        $fields = get_maxmind_field_descriptions();
        if (!isset($data['maxmind'])) {
            $lid = $data['account_lid'];
            $module = get_module_name('default');
            $customer = $GLOBALS['tf']->accounts->cross_reference($lid);
            $data = $GLOBALS['tf']->accounts->read($customer);
        }
        $table = new TFTable();
        $table->set_title('Maxmind Output');
        $maxmind = myadmin_unstringify($data['maxmind']);
        if (!is_array($maxmind)) {
            $maxmind = myadmin_unstringify(stripslashes($data['maxmind']));
        }
        $GLOBALS['tf']->add_html_head_js('
<style type="text/css">
.isotope h4 {
	margin-top: 0px;
	margin-bottom: 0px;
}
.isotope td {
	font-size: 10pt;
}
div.tooltip-inner {
	max-width: 350px;
}
</style>
<script type="text/javascript">
jQuery(document).ready(function () {
	jQuery(".isotope").isotope({
		layoutMode: "masonry",
	});
	jQuery("tr[title]").tooltip();
});
</script>');
        $smarty = new TFSmarty();
        $smarty->assign('maxmind', $maxmind);
        $smarty->assign('maxmind_fields', obj2array(json_decode(file_get_contents(INCLUDE_ROOT.'/config/maxmind_output_fields.json'))));
        add_output($smarty->fetch('billing/view_maxmind.tpl'));
    }
