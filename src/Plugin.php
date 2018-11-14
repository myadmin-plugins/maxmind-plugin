<?php

namespace Detain\MyAdminMaxMind;

use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * Class Plugin
 *
 * @package Detain\MyAdminMaxMind
 */
class Plugin
{
	public static $name = 'MaxMind Plugin';
	public static $description = 'Allows handling of MaxMind based Fraud Lookups and Fraud Reporting';
	public static $help = '';
	public static $type = 'plugin';

	/**
	 * Plugin constructor.
	 */
	public function __construct()
	{
	}

	/**
	 * @return array
	 */
	public static function getHooks()
	{
		return [
			'system.settings' => [__CLASS__, 'getSettings'],
			'function.requirements' => [__CLASS__, 'getRequirements'],
			'ui.menu' => [__CLASS__, 'getMenu'],
		];
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getMenu(GenericEvent $event)
	{
		$menu = $event->getSubject();
		if ($GLOBALS['tf']->ima == 'admin') {
			function_requirements('has_acl');
			if (has_acl('client_billing')) {
				$menu->add_link('billing', 'choice=none.maxmind_compare', '/images/myadmin/exchange.png', __('MaxMind Compare score/riskScore'));
			}
		}
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getRequirements(GenericEvent $event)
	{
        /**
         * @var \MyAdmin\Plugins\Loader $this->loader
         */
        $loader = $event->getSubject();
		$loader->add_page_requirement('view_maxmind', '/../vendor/detain/myadmin-maxmind-plugin/src/view_maxmind.php');
		$loader->add_page_requirement('maxmind_compare', '/../vendor/detain/myadmin-maxmind-plugin/src/maxmind_compare.php');
		$loader->add_requirement('get_maxmind_field_descriptions', '/../vendor/detain/myadmin-maxmind-plugin/src/maxmind.inc.php');
		$loader->add_requirement('maxmind_decode', '/../vendor/detain/myadmin-maxmind-plugin/src/maxmind.inc.php');
		$loader->add_requirement('update_maxmind', '/../vendor/detain/myadmin-maxmind-plugin/src/maxmind.inc.php');
		$loader->add_page_requirement('maxmind_lookup', '/../vendor/detain/myadmin-maxmind-plugin/src/maxmind_lookup.php');
		$loader->add_requirement('update_maxmind_noaccount', '/../vendor/detain/myadmin-maxmind-plugin/src/maxmind.inc.php');
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
    public static function getSettings(GenericEvent $event)
    {
        /**
         * @var \MyAdmin\Settings $settings
         **/
        $settings = $event->getSubject();
		$settings->add_radio_setting(__('Security & Fraud'), __('MaxMind Fraud Detection'), 'maxmind_enable', __('Enable MaxMind'), __('Enable MaxMind'), MAXMIND_ENABLE, [true, false], ['Enabled', 'Disabled']);
		$settings->add_text_setting(__('Security & Fraud'), __('MaxMind Fraud Detection'), 'maxmind_user_id', __('User ID'), __('User ID'), (defined('MAXMIND_USER_ID') ? MAXMIND_USER_ID : ''));
		$settings->add_text_setting(__('Security & Fraud'), __('MaxMind Fraud Detection'), 'maxmind_license_key', __('License Key'), __('License Key'), (defined('MAXMIND_LICENSE_KEY') ? MAXMIND_LICENSE_KEY : ''));
		$settings->add_radio_setting(__('Security & Fraud'), __('MaxMind Fraud Detection'), 'maxmind_carder_lock', __('Lock if Carder Email'), __('Lock if Carder Email'), MAXMIND_CARDER_LOCK, [true, false], ['Enabled', 'Disabled']);
		$settings->add_text_setting(__('Security & Fraud'), __('MaxMind Fraud Detection'), 'maxmind_score_lock', __('Lock if Score >= #'), __('Lock if Score >= #'), (defined('MAXMIND_SCORE_LOCK') ? MAXMIND_SCORE_LOCK : ''));
		$settings->add_text_setting(__('Security & Fraud'), __('MaxMind Fraud Detection'), 'maxmind_score_disable_cc', __('Disable CC if Score >= #'), __('Disable CC if Score >= #'), (defined('MAXMIND_SCORE_DISABLE_CC') ? MAXMIND_SCORE_DISABLE_CC : ''));
		$settings->add_text_setting(__('Security & Fraud'), __('MaxMind Fraud Detection'), 'maxmind_riskscore_lock', __('Lock if Risk % Score >= #'), __('Lock if Risk % Score >= #'), (defined('MAXMIND_RISKSCORE_LOCK') ? MAXMIND_RISKSCORE_LOCK : ''));
		$settings->add_text_setting(__('Security & Fraud'), __('MaxMind Fraud Detection'), 'maxmind_riskscore_disable_cc', __('Disable CC if Risk % Score >= #'), __('Disable CC if Risk % Score >= #'), (defined('MAXMIND_RISKSCORE_DISABLE_CC') ? MAXMIND_RISKSCORE_DISABLE_CC : ''));
		$settings->add_text_setting(__('Security & Fraud'), __('MaxMind Fraud Detection'), 'maxmind_proxyscore_disable_cc', __('Disable CC if Proxy Score >= #'), __('Disable CC if Proxy Score >= #'), (defined('MAXMIND_PROXYSCORE_DISABLE_CC') ? MAXMIND_PROXYSCORE_DISABLE_CC : ''));
		$settings->add_text_setting(__('Security & Fraud'), __('MaxMind Fraud Detection'), 'maxmind_possible_fraud_score', __('Email Possible Fraud Score >= #'), __('Email Possible Fraud Score >= #'), (defined('MAXMIND_POSSIBLE_FRAUD_SCORE') ? MAXMIND_POSSIBLE_FRAUD_SCORE : ''));
		$settings->add_text_setting(__('Security & Fraud'), __('MaxMind Fraud Detection'), 'maxmind_possible_fraud_riskscore', __('Email Possible Fraud Risk % Score >= #'), __('Email Possible Fraud Risk % Score >= #'), (defined('MAXMIND_POSSIBLE_FRAUD_RISKSCORE') ? MAXMIND_POSSIBLE_FRAUD_RISKSCORE : ''));
		$settings->add_text_setting(__('Security & Fraud'), __('MaxMind Fraud Detection'), 'maxmind_queries_remaining', __('Email when down to # Queries Remaining'), __('Email when down to # Queries Remaining'), (defined('MAXMIND_QUERIES_REMAINING') ? MAXMIND_QUERIES_REMAINING : ''));
		$settings->add_radio_setting(__('Security & Fraud'), __('MaxMind Fraud Detection'), 'maxmind_noresponse_disable_cc', __('Disable CC if No Response'), __('Disable CC if No Response'), MAXMIND_NORESPONSE_DISABLE_CC, [true, false], ['Enabled', 'Disabled']);
		$settings->add_radio_setting(__('Security & Fraud'), __('MaxMind Fraud Detection'), 'maxmind_reporting', __('Enable MaxMind Reporting'), __('Enable MaxMind Reporting'), MAXMIND_REPORTING, [true, false], ['Enabled', 'Disabled']);
		$settings->add_radio_setting(__('Security & Fraud'), __('MaxMind Fraud Detection'), 'maxmind_female_penalty_enable', __('Female Names Penalty '), __('Female Names Penalty '), MAXMIND_FEMALE_PENALTY_ENABLE, [true, false], ['Enabled', 'Disabled']);
		$settings->add_text_setting(__('Security & Fraud'), __('MaxMind Fraud Detection'), 'maxmind_female_score_penalty', __('Female Name Score Penalty Amount'), __('Female Name Score Penalty Amount'), (defined('MAXMIND_FEMALE_SCORE_PENALTY') ? MAXMIND_FEMALE_SCORE_PENALTY : ''));
		$settings->add_text_setting(__('Security & Fraud'), __('MaxMind Fraud Detection'), 'maxmind_female_score_limit', __('Female Name Score Penalty Cutoff Limit'), __('Female Name Score Penalty Cutoff Limit'), (defined('MAXMIND_FEMALE_SCORE_LIMIT') ? MAXMIND_FEMALE_SCORE_LIMIT : ''));
		$settings->add_text_setting(__('Security & Fraud'), __('MaxMind Fraud Detection'), 'maxmind_female_riskscore_penalty', __('Female Name Risk % Score Penalty Amount'), __('Female Name Risk % Score Penalty Amount'), (defined('MAXMIND_FEMALE_RISKSCORE_PENALTY') ? MAXMIND_FEMALE_RISKSCORE_PENALTY : ''));
		$settings->add_text_setting(__('Security & Fraud'), __('MaxMind Fraud Detection'), 'maxmind_female_riskscore_limit', __('Female Name Risk % Score Penalty Cutoff Limit'), __('Female Name Risk % Score Penalty Cutoff Limit'), (defined('MAXMIND_FEMALE_RISKSCORE_LIMIT') ? MAXMIND_FEMALE_RISKSCORE_LIMIT : ''));
		$settings->add_radio_setting(__('Security & Fraud'), __('MaxMind Fraud Detection'), 'maxmind_country_penalty_enable', __('Country Penalty '), __('Country Penalty '), MAXMIND_COUNTRY_PENALTY_ENABLE, [true, false], ['Enabled', 'Disabled']);
		$settings->add_text_setting(__('Security & Fraud'), __('MaxMind Fraud Detection'), 'maxmind_country_score_penalty', __('Country Score Penalty Amount'), __('Country Score Penalty Amount'), (defined('MAXMIND_COUNTRY_SCORE_PENALTY') ? MAXMIND_COUNTRY_SCORE_PENALTY : ''));
		$settings->add_text_setting(__('Security & Fraud'), __('MaxMind Fraud Detection'), 'maxmind_country_score_limit', __('Country Score Penalty Cutoff Limit'), __('Country Score Penalty Cutoff Limit'), (defined('MAXMIND_COUNTRY_SCORE_LIMIT') ? MAXMIND_COUNTRY_SCORE_LIMIT : ''));
		$settings->add_text_setting(__('Security & Fraud'), __('MaxMind Fraud Detection'), 'maxmind_country_riskscore_penalty', __('Country Risk % Score Penalty Amount'), __('Country Risk % Score Penalty Amount'), (defined('MAXMIND_COUNTRY_RISKSCORE_PENALTY') ? MAXMIND_COUNTRY_RISKSCORE_PENALTY : ''));
		$settings->add_text_setting(__('Security & Fraud'), __('MaxMind Fraud Detection'), 'maxmind_country_riskscore_limit', __('Country Risk % Score Penalty Cutoff Limit'), __('Country Risk % Score Penalty Cutoff Limit'), (defined('MAXMIND_COUNTRY_RISKSCORE_LIMIT') ? MAXMIND_COUNTRY_RISKSCORE_LIMIT : ''));
	}
}
