<?php

namespace Detain\MyAdminMaxMind;

use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * Class Plugin
 *
 * @package Detain\MyAdminMaxMind
 */
class Plugin {

	public static $name = 'MaxMind Plugin';
	public static $description = 'Allows handling of MaxMind based Fraud Lookups and Fraud Reporting';
	public static $help = '';
	public static $type = 'plugin';

	/**
	 * Plugin constructor.
	 */
	public function __construct() {
	}

	/**
	 * @return array
	 */
	public static function getHooks() {
		return [
			'system.settings' => [__CLASS__, 'getSettings'],
			'function.requirements' => [__CLASS__, 'getRequirements'],
			//'ui.menu' => [__CLASS__, 'getMenu'],
		];
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getMenu(GenericEvent $event) {
		$menu = $event->getSubject();
		if ($GLOBALS['tf']->ima == 'admin') {
			function_requirements('has_acl');
					if (has_acl('client_billing'))
							$menu->add_link('admin', 'choice=none.abuse_admin', '/bower_components/webhostinghub-glyphs-icons/icons/development-16/Black/icon-spam.png', 'MaxMind');
		}
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getRequirements(GenericEvent $event) {
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
	public static function getSettings(GenericEvent $event) {
		$settings = $event->getSubject();
		$settings->add_radio_setting('Security & Fraud', 'MaxMind Fraud Detection', 'maxmind_enable', 'Enable MaxMind', 'Enable MaxMind', MAXMIND_ENABLE, [TRUE, FALSE], ['Enabled', 'Disabled']);
		$settings->add_text_setting('Security & Fraud', 'MaxMind Fraud Detection', 'maxmind_user_id', 'User ID', 'User ID', (defined('MAXMIND_USER_ID') ? MAXMIND_USER_ID : ''));
		$settings->add_text_setting('Security & Fraud', 'MaxMind Fraud Detection', 'maxmind_license_key', 'License Key', 'License Key', (defined('MAXMIND_LICENSE_KEY') ? MAXMIND_LICENSE_KEY : ''));
		$settings->add_radio_setting('Security & Fraud', 'MaxMind Fraud Detection', 'maxmind_carder_lock', 'Lock if Carder Email', 'Lock if Carder Email', MAXMIND_CARDER_LOCK, [TRUE, FALSE], ['Enabled', 'Disabled']);
		$settings->add_text_setting('Security & Fraud', 'MaxMind Fraud Detection', 'maxmind_score_lock', 'Lock if Score >= #', 'Lock if Score >= #', (defined('MAXMIND_SCORE_LOCK') ? MAXMIND_SCORE_LOCK : ''));
		$settings->add_text_setting('Security & Fraud', 'MaxMind Fraud Detection', 'maxmind_score_disable_cc', 'Disable CC if Score >= #', 'Disable CC if Score >= #', (defined('MAXMIND_SCORE_DISABLE_CC') ? MAXMIND_SCORE_DISABLE_CC : ''));
		$settings->add_text_setting('Security & Fraud', 'MaxMind Fraud Detection', 'maxmind_riskscore_lock', 'Lock if Risk % Score >= #', 'Lock if Risk % Score >= #', (defined('MAXMIND_RISKSCORE_LOCK') ? MAXMIND_RISKSCORE_LOCK : ''));
		$settings->add_text_setting('Security & Fraud', 'MaxMind Fraud Detection', 'maxmind_riskscore_disable_cc', 'Disable CC if Risk % Score >= #', 'Disable CC if Risk % Score >= #', (defined('MAXMIND_RISKSCORE_DISABLE_CC') ? MAXMIND_RISKSCORE_DISABLE_CC : ''));
		$settings->add_text_setting('Security & Fraud', 'MaxMind Fraud Detection', 'maxmind_proxyscore_disable_cc', 'Disable CC if Proxy Score >= #', 'Disable CC if Proxy Score >= #', (defined('MAXMIND_PROXYSCORE_DISABLE_CC') ? MAXMIND_PROXYSCORE_DISABLE_CC : ''));
		$settings->add_text_setting('Security & Fraud', 'MaxMind Fraud Detection', 'maxmind_possible_fraud_score', 'Email Possible Fraud Score >= #', 'Email Possible Fraud Score >= #', (defined('MAXMIND_POSSIBLE_FRAUD_SCORE') ? MAXMIND_POSSIBLE_FRAUD_SCORE : ''));
		$settings->add_text_setting('Security & Fraud', 'MaxMind Fraud Detection', 'maxmind_possible_fraud_riskscore', 'Email Possible Fraud Risk % Score >= #', 'Email Possible Fraud Risk % Score >= #', (defined('MAXMIND_POSSIBLE_FRAUD_RISKSCORE') ? MAXMIND_POSSIBLE_FRAUD_RISKSCORE : ''));
		$settings->add_text_setting('Security & Fraud', 'MaxMind Fraud Detection', 'maxmind_queries_remaining', 'Email when down to # Queries Remaining', 'Email when down to # Queries Remaining', (defined('MAXMIND_QUERIES_REMAINING') ? MAXMIND_QUERIES_REMAINING : ''));
		$settings->add_radio_setting('Security & Fraud', 'MaxMind Fraud Detection', 'maxmind_noresponse_disable_cc', 'Disable CC if No Response', 'Disable CC if No Response', MAXMIND_NORESPONSE_DISABLE_CC, [TRUE, FALSE], ['Enabled', 'Disabled']);
		$settings->add_radio_setting('Security & Fraud', 'MaxMind Fraud Detection', 'maxmind_reporting', 'Enable MaxMind Reporting', 'Enable MaxMind Reporting', MAXMIND_REPORTING, [TRUE, FALSE], ['Enabled', 'Disabled']);
		$settings->add_radio_setting('Security & Fraud', 'MaxMind Fraud Detection', 'maxmind_female_penalty_enable', 'Female Names Penalty ', 'Female Names Penalty ', MAXMIND_FEMALE_PENALTY_ENABLE, [TRUE, FALSE], ['Enabled', 'Disabled']);
		$settings->add_text_setting('Security & Fraud', 'MaxMind Fraud Detection', 'maxmind_female_score_penalty', 'Female Name Score Penalty Amount', 'Female Name Score Penalty Amount', (defined('MAXMIND_FEMALE_SCORE_PENALTY') ? MAXMIND_FEMALE_SCORE_PENALTY : ''));
		$settings->add_text_setting('Security & Fraud', 'MaxMind Fraud Detection', 'maxmind_female_score_limit', 'Female Name Score Penalty Cutoff Limit', 'Female Name Score Penalty Cutoff Limit', (defined('MAXMIND_FEMALE_SCORE_LIMIT') ? MAXMIND_FEMALE_SCORE_LIMIT : ''));
		$settings->add_text_setting('Security & Fraud', 'MaxMind Fraud Detection', 'maxmind_female_riskscore_penalty', 'Female Name Risk % Score Penalty Amount', 'Female Name Risk % Score Penalty Amount', (defined('MAXMIND_FEMALE_RISKSCORE_PENALTY') ? MAXMIND_FEMALE_RISKSCORE_PENALTY : ''));
		$settings->add_text_setting('Security & Fraud', 'MaxMind Fraud Detection', 'maxmind_female_riskscore_limit', 'Female Name Risk % Score Penalty Cutoff Limit', 'Female Name Risk % Score Penalty Cutoff Limit', (defined('MAXMIND_FEMALE_RISKSCORE_LIMIT') ? MAXMIND_FEMALE_RISKSCORE_LIMIT : ''));
		$settings->add_radio_setting('Security & Fraud', 'MaxMind Fraud Detection', 'maxmind_country_penalty_enable', 'Country Penalty ', 'Country Penalty ', MAXMIND_COUNTRY_PENALTY_ENABLE, [TRUE, FALSE], ['Enabled', 'Disabled']);
		$settings->add_text_setting('Security & Fraud', 'MaxMind Fraud Detection', 'maxmind_country_score_penalty', 'Country Score Penalty Amount', 'Country Score Penalty Amount', (defined('MAXMIND_COUNTRY_SCORE_PENALTY') ? MAXMIND_COUNTRY_SCORE_PENALTY : ''));
		$settings->add_text_setting('Security & Fraud', 'MaxMind Fraud Detection', 'maxmind_country_score_limit', 'Country Score Penalty Cutoff Limit', 'Country Score Penalty Cutoff Limit', (defined('MAXMIND_COUNTRY_SCORE_LIMIT') ? MAXMIND_COUNTRY_SCORE_LIMIT : ''));
		$settings->add_text_setting('Security & Fraud', 'MaxMind Fraud Detection', 'maxmind_country_riskscore_penalty', 'Country Risk % Score Penalty Amount', 'Country Risk % Score Penalty Amount', (defined('MAXMIND_COUNTRY_RISKSCORE_PENALTY') ? MAXMIND_COUNTRY_RISKSCORE_PENALTY : ''));
		$settings->add_text_setting('Security & Fraud', 'MaxMind Fraud Detection', 'maxmind_country_riskscore_limit', 'Country Risk % Score Penalty Cutoff Limit', 'Country Risk % Score Penalty Cutoff Limit', (defined('MAXMIND_COUNTRY_RISKSCORE_LIMIT') ? MAXMIND_COUNTRY_RISKSCORE_LIMIT : ''));
	}

}
