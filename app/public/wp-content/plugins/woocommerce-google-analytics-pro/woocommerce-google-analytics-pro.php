<?php
/**
 * Plugin Name: WooCommerce Google Analytics Pro
 * Plugin URI: http://www.woocommerce.com/products/woocommerce-google-analytics-pro/
 * Description: Supercharge your Google Analytics tracking with enhanced eCommerce tracking and custom event tracking
 * Author: SkyVerge
 * Author URI: http://www.skyverge.com
 * Version: 1.4.1
 * Text Domain: woocommerce-google-analytics-pro
 * Domain Path: /i18n/languages/
 *
 * Copyright: (c) 2015-2018, SkyVerge, Inc.
 *
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 * @package   WC-Google-Analytics-Pro
 * @author    SkyVerge
 * @category  Integration
 * @copyright Copyright (c) 2015-2018, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 *
 * Woo: 1312497:d8aed8b7306b509eec1589e59abe319f
 * WC requires at least: 2.6.14
 * WC tested up to: 3.3.0
 */

defined( 'ABSPATH' ) or exit;

// Required functions
if ( ! function_exists( 'woothemes_queue_update' ) ) {
	require_once( plugin_dir_path( __FILE__ ) . 'woo-includes/woo-functions.php' );
}

// Plugin updates
woothemes_queue_update( plugin_basename( __FILE__ ), 'd8aed8b7306b509eec1589e59abe319f', '1312497' );

// WC active check
if ( ! is_woocommerce_active() ) {
	return;
}

// Required library class
if ( ! class_exists( 'SV_WC_Framework_Bootstrap' ) ) {
	require_once( plugin_dir_path( __FILE__ ) . 'lib/skyverge/woocommerce/class-sv-wc-framework-bootstrap.php' );
}

SV_WC_Framework_Bootstrap::instance()->register_plugin( '4.9.0', 'WooCommerce Google Analytics Pro', __FILE__, 'init_woocommerce_google_analytics_pro', array(
	'minimum_wc_version'   => '2.6.14',
	'minimum_wp_version'   => '4.4',
	'backwards_compatible' => '4.4',
) );

function init_woocommerce_google_analytics_pro() {

/**
 * # WooCommerce Google Analytics Pro Main Plugin Class
 *
 * ## Plugin Overview
 *
 * This plugin adds Google Analytics tracking to many different WooCommerce events, like adding a product to the cart or completing
 * a purchase. Admins can control the name of the events and properties sent to Google Analytics in the integration settings section.
 *
 * ## Features
 *
 * + Provides basic Google Analytics tracking
 * + Provides basic eCommerce tracking
 * + Provides enhanced eCommerce tracking
 * + Provides event tracking using both analytics.js and the Measurement Protocol
 *
 * ## Admin Considerations
 *
 * + The plugin is added as an integration, so all settings exist inside the integrations section (WooCommerce > Settings > Integrations)
 *
 * ## Frontend Considerations
 *
 * + The Google Analytics tracking javascript is added to the <head> of every page load
 *
 * ## Database
 *
 * ### Global Settings
 *
 * + `woocommerce_google_analytics_pro_settings` - a serialized array of Google Analytics Pro integration settings, include API credentials and event/property names
 *
 * ### Options table
 *
 * + `wc_google_analytics_pro_version` - the current plugin version, set on install/upgrade
 *
 * @since 1.0.0
 */
class WC_Google_Analytics_Pro extends SV_WC_Plugin {


	/** plugin version number */
	const VERSION = '1.4.1';

	/** @var \WC_Google_Analytics_Pro the singleton instance of the plugin */
	protected static $instance;

	/** the plugin ID */
	const PLUGIN_ID = 'google_analytics_pro';

	/** @var \WC_Google_Analytics_Pro_AJAX the AJAX class instance */
	protected $ajax;

	/** @var \WC_Google_Analytics_Pro_Integration the integration class instance */
	private $integration = null;

	/** @var bool whether we have run analytics profile checks */
	private $has_run_analytics_profile_checks = false;


	/**
	 * Constructs the class and initializes the plugin.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		parent::__construct(
			self::PLUGIN_ID,
			self::VERSION,
			array(
				'text_domain'        => 'woocommerce-google-analytics-pro',
				'display_php_notice' => true,
			)
		);

		// load integration
		add_action( 'sv_wc_framework_plugins_loaded', array( $this, 'includes' ) );
	}


	/**
	 * Includes the required files.
	 *
	 * @since 1.0.0
	 */
	public function includes() {

		// load the Google client API if it doesn't exist
		if ( ! ( function_exists( 'google_api_php_client_autoload' ) || class_exists( 'Google_Client' ) ) ) {
			require_once( $this->get_plugin_path() . '/lib/google/google-api-php-client/src/Google/autoload.php' );
		}

		// base integration
		require_once( $this->get_plugin_path() . '/includes/class-sv-wc-tracking-integration.php' );
		require_once( $this->get_plugin_path() . '/includes/class-wc-google-analytics-pro-integration.php' );

		add_filter( 'woocommerce_integrations', array( $this, 'load_integration' ) );

		// AJAX includes
		if ( is_ajax() ) {
			$this->ajax_includes();
		}

		if ( is_admin() ) {

			// Check if free WooCommerce Google Analytics integration is activated and deactivate it
			if ( $this->is_plugin_active( 'woocommerce-google-analytics-integration.php' ) ) {
				$this->deactivate_free_integration();
			}
		}
	}


	/**
	 * Includes the required AJAX files.
	 *
	 * @since 1.0.0
	 */
	private function ajax_includes() {

		$this->ajax = $this->load_class( '/includes/class-wc-google-analytics-pro-ajax.php', 'WC_Google_Analytics_Pro_AJAX' );
	}


	/**
	 * Adds GA Pro as a WooCommerce integration.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 * @param array $integrations the existing integrations
	 * @return array
	 */
	public function load_integration( $integrations ) {

		$integrations[] = 'WC_Google_Analytics_Pro_Integration';

		return $integrations;
	}


	/**
	 * Returns the AJAX class instance.
	 *
	 * @since 1.1.0
	 * @return \WC_Google_Analytics_Pro_AJAX the AJAX class instance
	 */
	public function get_ajax_instance() {

		return $this->ajax;
	}


	/** Helper methods ********************************************************/


	/**
	 * Returns the plugin singleton instance.
	 *
	 * @see wc_google_analytics_pro()
	 *
	 * @since 1.0.0
	 * @return \WC_Google_Analytics_Pro the plugin singleton instance
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}


	/**
	 * Returns the AJAX class instance.
	 *
	 * @deprecated since 1.1.0
	 *
	 * @since 1.0.0
	 * @return \WC_Google_Analytics_Pro_AJAX the AJAX class instance
	 */
	public function ajax() {

		_deprecated_function( 'wc_google_analytics_pro()->ajax()', '1.1.0', 'wc_google_analytics_pro()->get_ajax_instance()' );

		return $this->get_ajax_instance();
	}


	/**
	 * Returns deprecated/removed hooks.
	 *
	 * @since 1.3.0
	 * @see SV_WC_Plugin::get_deprecated_hooks()
	 * @return array
	 */
	protected function get_deprecated_hooks() {

		$deprecated_hooks = array(
			'wc_google_analytics_pro_product_funnel_steps' => array(
				'version' => '1.3.0',
				'removed' => true,
			),
		);

		return $deprecated_hooks;
	}


	/**
	 * Returns the plugin name, localized.
	 *
	 * @see \SV_WC_Plugin::get_plugin_name()
	 *
	 * @since 1.0.0
	 * @return string the plugin name
	 */
	public function get_plugin_name() {

		return __( 'WooCommerce Google Analytics Pro', 'woocommerce-google-analytics-pro' );
	}


	/**
	 * Returns the full path and filename of the plugin file.
	 *
	 * @see \SV_WC_Plugin::get_file()
	 *
	 * @since 1.0.0
	 * @return string the full path and filename of the plugin file
	 */
	protected function get_file() {

		return __FILE__;
	}

	/**
	 * Returns the plugin documentation URL.
	 *
	 * @see \SV_WC_Plugin::get_documentation_url()
	 *
	 * @since 1.0.0
	 * @return string the plugin documentation URL
	 */
	public function get_documentation_url() {

		return 'http://docs.woocommerce.com/document/woocommerce-google-analytics-pro/';
	}


	/**
	 * Returns the plugin support URL.
	 *
	 * @see \SV_WC_Plugin::get_support_url()
	 *
	 * @since 1.0.0
	 * @return string the plugin support URL
	 */
	public function get_support_url() {

		return 'https://woocommerce.com/my-account/marketplace-ticket-form/';
	}

	/**
	 * Returns the settings page URL.
	 *
	 * @see \SV_WC_Plugin::is_plugin_settings()
	 *
	 * @since 1.0.0
	 * @param string $_ unused
	 * @return string the settings page URL
	 */
	public function get_settings_url( $_ = '' ) {

		return admin_url( 'admin.php?page=wc-settings&tab=integration&section=google_analytics_pro' );
	}


	/**
	 * Determines if viewing the plugin settings page.
	 *
	 * @see \SV_WC_Plugin::is_plugin_settings()
	 *
	 * @since 1.0.0
	 * @return bool whether viewing the plugin settings page
	 */
	public function is_plugin_settings() {

		return isset( $_GET['page'] ) && 'wc-settings' === $_GET['page'] && isset( $_GET['tab'] ) && 'integration' === $_GET['tab'] && ( ! isset( $_GET['section'] ) || $this->get_id() === $_GET['section'] );
	}


	/**
	 * Logs API requests & responses.
	 *
	 * Overridden to check if debug mode is enabled in the integration settings.
	 *
	 * @see \SV_WC_Plugin::add_api_request_logging()
	 *
	 * @since 1.0.0
	 */
	public function add_api_request_logging() {

		$settings = get_option( 'woocommerce_google_analytics_pro_settings', array() );

		if ( ! isset( $settings['debug_mode'] ) || 'off' === $settings['debug_mode'] ) {
			return;
		}

		parent::add_api_request_logging();
	}


	/**
	 * Handles deactivating the free integration if needed.
	 *
	 * In 1.3.0 renamed from maybe_deactivate_free_integration to deactivate_free_integration,
	 * changed from public to private
	 *
	 * @since 1.0.0
	 */
	private function deactivate_free_integration() {

		// simply deactivate the free plugin
		require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		deactivate_plugins( 'woocommerce-google-analytics-integration/woocommerce-google-analytics-integration.php' );

		$notice = '<strong>' . $this->get_plugin_name() . ':</strong> ' .
			__( 'The free WooCommerce Google Analytics integration has been deactivated and is not needed when the Pro version is active.', 'woocommerce-google-analytics-pro' );

		// hide the free integration's connection notice, if it hasn't already been dismissed
		ob_start();

		?>$( 'a[href$="page=wc-settings&tab=integration&section=google_analytics"]' ).closest( 'div.updated' ).hide();<?php

		wc_enqueue_js( ob_get_clean() );

		$this->get_admin_notice_handler()->add_admin_notice( $notice, 'free-integration', array( 'dismissible' => true, 'notice_class' => 'updated' ) );
	}


	/**
	 * Adds various admin notices to assist with proper setup and configuration.
	 *
	 * @since 1.0.0
	 */
	public function add_admin_notices() {

		// show any dependency notices
		parent::add_admin_notices();

		// onboarding notice
		if ( ! $this->get_integration()->get_access_token() && 'yes' !== $this->get_integration()->get_option( 'use_manual_tracking_id' ) ) {

			if ( $this->is_plugin_settings() ) {

				// just show "read the docs" notice when on settings
				$notice = sprintf(
					/* translators: Placeholders: %1$s - <strong> tag, %2$s - </strong> tag, %3$s - <a> tag, %4$s - </a> tag */
					__( '%1$sNeed help setting up WooCommerce Google Analytics Pro?%2$s Please %3$sread the documentation%4$s.', 'woocommerce-google-analytics-pro' ),
					'<strong>',
					'</strong>',
					'<a target="_blank" href="' . esc_url( $this->get_documentation_url() ) . '">',
					'</a>'
				);

			} else {

				// show "Connect to GA" notice everywhere else
				$notice = sprintf(
					/* translators: Placeholders: %1$s - <strong> tag, %2$s - </strong> tag, %3$s - <a> tag, %4$s - </a> tag */
					__( '%1$sWooCommerce Google Analytics Pro is almost ready!%2$s To get started, please ​%3$sconnect to Google Analytics%4$s.', 'woocommerce-google-analytics-pro' ),
					'<strong>',
					'</strong>',
					'<a href="' . esc_url( $this->get_settings_url() ) . '">',
					'</a>'
				);
			}

			$this->get_admin_notice_handler()->add_admin_notice( $notice, 'onboarding', array(
				'dismissible'             => true,
				'notice_class'            => 'updated',
				'always_show_on_settings' => false,
			) );
		}

		// show MonsterInsights-related notices
		if ( $this->is_monsterinsights_active() && $this->is_plugin_settings() ) {

			// Ga by Yoast was renamed to MonsterInsights in 5.4.9
			$plugin_name = $this->is_monsterinsights_lt_5_4_9() ? 'Google Analytics by Yoast' : 'Google Analytics by MonsterInsights';

			// warn about MonsterInsights's settings taking over ours
			$this->get_admin_notice_handler()->add_admin_notice(
				'<strong>' . $this->get_plugin_name() . ':</strong> ' .
				/* translators: placeholders: %s - plugin name */
				sprintf( __( '%s is active. Its settings will take precedence over the values set in the "Tracking Settings" section.', 'woocommerce-google-analytics-pro' ), $plugin_name ),
				'yoast-active',
				array(
					'dismissible'             => true,
					'always_show_on_settings' => false,
				)
			);

			// warn about MonsterInsights in debug mode
			if ( $this->get_monsterinsights_option( 'debug_mode' ) ) {

				$this->get_admin_notice_handler()->add_admin_notice(
					'<strong>' . $this->get_plugin_name() . ':</strong> ' .
					/* translators: placeholders: %s - plugin name */
					sprintf( __( '%s is set to Debug Mode. Please disable debug mode so WooCommerce Google Analytics Pro can function properly.', 'woocommerce-google-analytics-pro' ), $plugin_name ),
					'yoast-in-debug'
				);

			}

			// warn about MonsterInsights not having universal tracking enabled
			if ( $this->is_monsterinsights_lt_6() && ! $this->get_monsterinsights_option( 'enable_universal' ) ) {

				$this->get_admin_notice_handler()->add_admin_notice(
					'<strong>' . $this->get_plugin_name() . ':</strong> ' .
					/* translators: placeholders: %s - plugin name */
					sprintf( __( 'WooCommerce Google Analytics Pro requires Universal Analytics. Please enable Universal Analytics in %s.', 'woocommerce-google-analytics-pro' ), $plugin_name ),
					'yoast-in-non-universal'
				);
			}
		}
	}


	/**
	 * Adds delayed admin notices on the Integration page if Analytics profile settings are not correct.
	 *
	 * @since 1.0.0
	 */
	public function add_delayed_admin_notices() {

		$this->check_analytics_profile_settings();

		// Warn about deprecated javascript function name
		if ( get_option( 'woocommerce_google_analytics_upgraded_from_gatracker' ) && '__gaTracker' === $this->get_integration()->get_option( 'function_name' ) ) {

			$this->get_admin_notice_handler()->add_admin_notice(
				/* translators: %1$s - function name, %2$s, %4$s - opening <a> tag, %3$s, %5$s - closing </a> tag */
				sprintf( esc_html__( 'Please update any custom tracking code & switch the Google Analytics javascript tracker function name to %1$s in the %2$sGoogle Analytics settings%3$s. You can %4$slearn more from the plugin documentation%5$s.', 'woocommerce-google-analytics-pro' ), '<code>ga</code>', '<a href="' . $this->get_settings_url() . '#woocommerce_google_analytics_pro_additional_settings_section">', '</a>', '<a href="https://docs.woocommerce.com/document/woocommerce-google-analytics-pro/#upgrading">', '</a>' ),
				'update_function_name',
				array( 'dismissible' => true, 'notice_class' => 'error', 'always_show_on_settings' => true )
			);
		}
	}


	/**
	 * Checks the Google Analytics profiles for correct settings.
	 *
	 * @since 1.0.0
	 */
	private function check_analytics_profile_settings() {

		if ( $this->has_run_analytics_profile_checks ) {
			return;
		}

		if ( ! $this->is_plugin_settings() ) {
			return;
		}

		$integration = $this->get_integration();

		if ( 'yes' === $integration->get_option( 'use_manual_tracking_id' ) ) {
			return;
		}

		if ( ! $integration->get_access_token() ) {
			return;
		}

		$analytics   = $integration->get_analytics();
		$account_id  = $integration->get_ga_account_id();
		$property_id = $integration->get_ga_property_id();

		if ( $account_id && $property_id ) {

			try {

				$profiles          = $analytics->management_profiles->listManagementProfiles( $account_id, $property_id );
				$ec_disabled       = array();
				$currency_mismatch = array();

				foreach ( $profiles as $profile ) {

					$profile_id           = $profile->getId();
					$property_internal_id = $profile->getInternalWebPropertyId();

					if ( ! $profile->getEnhancedECommerceTracking() ) {

						$url  = "https://www.google.com/analytics/web/?hl=en#management/Settings/a{$account_id}w{$property_internal_id}p{$profile_id}/%3Fm.page%3DEcommerceSettings/";
						$link = '<a href="' . $url . '" target="_blank">' . $profile->getName() . '</a>';

						$ec_disabled[] = array(
							'url'  => $url,
							'link' => $link,
						);
					}

					if ( $profile->getCurrency() !== get_woocommerce_currency() ) {

						$url  = "https://www.google.com/analytics/web/?hl=en#management/Settings/a{$account_id}w{$property_internal_id}p{$profile_id}/%3Fm.page%3DProfileSettings/";
						$link = '<a href="' . $url . '" target="_blank">' . $profile->getName() . '</a>';

						$currency_mismatch[] = array(
							'url'      => $url,
							'link'     => $link,
							'currency' => $profile->getCurrency(),
						);
					}
				}

				if ( ! empty( $ec_disabled ) ) {

					if ( 1 === count( $ec_disabled ) ) {
						/* translators: Placeholders: %1$s - <a> tag, %2$s - </a> tag */
						$message = sprintf( __( 'WooCommerce Google Analytics Pro requires Enhanced Ecommerce to be enabled. Please enable Enhanced Ecommerce on your %1$sGoogle Analytics View%2$s.', 'woocommerce-google-analytics-pro' ), '<a href="' . $ec_disabled[0]['url'] . '" target="_blank">', '</a>' );

					} else {

						$views   = '<ul><li>' . implode( '</li><li>', wp_list_pluck( $ec_disabled, 'link' ) ) . '</li></ul>';
						/* translators: Placeholders: %s - a list of links */
						$message = sprintf( __( 'WooCommerce Google Analytics Pro requires Enhanced Ecommerce to be enabled. Please enable Enhanced Ecommerce on the following Google Analytics Views: %s', 'woocommerce-google-analytics-pro' ), $views );
					}

					$this->get_admin_notice_handler()->add_admin_notice(
						'<strong>' . $this->get_plugin_name() . ':</strong> ' .
						$message,
						'enhanced-ecommerce-not-enabled'
					);
				}

				if ( ! empty( $currency_mismatch ) ) {

					if ( 1 === count( $currency_mismatch ) ) {
						/* translators: Placeholders: %1$s and %2$s - currency code, e.g. USD, %3$s - <a> tag, %4$s - </a> tag */
						$message = sprintf( __( 'Your Google Analytics View currency (%1$s) does not match WooCommerce currency (%2$s). You can change it %3$son your Google Analytics View%4$s.', 'woocommerce-google-analytics-pro' ), $profile->getCurrency(), get_woocommerce_currency(), '<a href="' . $url . '" target="_blank">', '</a>' );
					} else {

						$views   = '<ul><li>' . implode( '</li><li>', wp_list_pluck( $currency_mismatch, 'link' ) ) . '</li></ul>';
						/* translators: Placeholders: %1$s - currency code, %2$s - a list of links */
						$message = sprintf( __( 'Your Google Analytics Views currencies does not match WooCommerce currency (%1$s). You can change it on the following Google Analytics Views: %2$s', 'woocommerce-google-analytics-pro' ), get_woocommerce_currency(), $views );
					}

					$this->get_admin_notice_handler()->add_admin_notice(
						'<strong>' . $this->get_plugin_name() . ':</strong> ' .
						$message,
						'analytics-currency-mismatch',
						array( 'dismissible' => true, 'always_show_on_settings' => false )
					);
				}

				$this->has_run_analytics_profile_checks = true;

			} catch ( Exception $e ) {

				$this->log( $e->getMessage() );
			}
		}
	}


	/**
	 * Determines if "Google Analytics by Yoast" is active.
	 *
	 * @deprecated since 1.3.0
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	public function is_yoast_ga_active() {

		_deprecated_function( 'wc_google_analytics_pro()->is_yoast_ga_active()', '1.3.0', 'wc_google_analytics_pro()->is_monsterinsights_active()' );

		return $this->is_monsterinsights_active();
	}


	/**
	 * Determines if "Google Analytics by MonsterInsights" is active.
	 *
	 * @internal
	 *
	 * @since 1.3.0
	 *
	 * @return bool
	 */
	public function is_monsterinsights_active() {

		return    defined( 'GAWP_VERSION' )              // Yoast 4.1 - 5.x
		       || class_exists( 'MonsterInsights_Lite' ) // MonsterInsights 6.x Free
		       || class_exists( 'MonsterInsights' );     // MonsterInsights 6.x Pro
	}


	/**
	 * Returns a "Google Analytics by Yoast" option.
	 *
	 * @deprecated since 1.3.0
	 *
	 * @since 1.0.0
	 * @param string $option_name the option name
	 * @return mixed|null
	 */
	public function get_yoast_ga_option( $option_name ) {

		_deprecated_function( 'wc_google_analytics_pro()->get_yoast_ga_option()', '1.3.0', 'wc_google_analytics_pro()->get_monsterinsights_option()' );

		return $this->get_monsterinsights_option( $option_name );
	}


	/**
	 * Returns a "Google Analytics by MonsterInsights" option.
	 *
	 * This also includes MonsterInsights / Yoast pre v6.
	 *
	 * @internal
	 *
	 * @since 1.3.0
	 * @param string $option_name the option name
	 * @return mixed|null
	 */
	public function get_monsterinsights_option( $option_name ) {

		$options = array();

		if ( function_exists( 'monsterinsights_get_options' ) ) {
			$options = monsterinsights_get_options();
		} elseif ( class_exists( 'Yoast_GA_Options' ) ) {
			$options =  (array) Yoast_GA_Options::instance()->options;
		}

		return isset( $options[ $option_name ] ) ? $options[ $option_name ] : null;
	}


	/**
	 * Returns the "Google Analytics by MonsterInsights" version.
	 *
	 * This also includes MonsterInsights / Yoast pre v6.
	 *
	 * @internal
	 *
	 * @since 1.3.0
	 * @return string|null
	 */
	public function get_monsterinsights_version() {
		return defined( 'MONSTERINSIGHTS_VERSION' ) ? MONSTERINSIGHTS_VERSION : ( defined( 'GAWP_VERSION' ) ? GAWP_VERSION : null );
	}


	/**
	 * Checks whether the currently installed version of MonsterInsights is less than 6.0.0
	 *
	 * @internal
	 *
	 * @since 1.3.0
	 * @return bool
	 */
	public function is_monsterinsights_lt_6() {
		return version_compare( $this->get_monsterinsights_version(), '6.0.0', '<' );
	}


	/**
	 * Checks whether the currently installed version of MonsterInsights is at least 6.0.0
	 *
	 * @internal
	 *
	 * @since 1.3.0
	 * @return bool
	 */
	public function is_monsterinsights_gte_6() {
		return version_compare( $this->get_monsterinsights_version(), '6.0.0', '>' );
	}


	/**
	 * Checks whether the currently installed version of MonsterInsights is less than 5.4.9
	 *
	 * Note: 5.4.9 was significant as the plugin was renamed then.
	 *
	 * @internal
	 *
	 * @since 1.3.0
	 * @return bool
	 */
	public function is_monsterinsights_lt_5_4_9() {
		return version_compare( $this->get_monsterinsights_version(), '5.4.9', '<' );
	}


	/**
	 * Returns the identifier for a given product.
	 *
	 * @deprecated since 1.3.0
	 *
	 * @since 1.0.3
	 * @param \WC_Product|int $product the product object or ID
	 * @return string the product identifier, either its SKU or `#<id>`
	 */
	public function get_product_identifier( $product ) {

		_deprecated_function( 'wc_google_analytics_pro()->get_product_identifier()', '1.3.0', 'wc_google_analytics_pro()->get_integration()->get_product_identifier()' );

		return wc_google_analytics_pro()->get_integration()->get_product_identifier( $product );
	}


	/**
	 * Returns the integration class instance.
	 *
	 * @since 1.0.0
	 * @return \WC_Google_Analytics_Pro_Integration the integration class instance
	 */
	public function get_integration() {

		if ( is_null( $this->integration ) ) {

			$integrations = WC()->integrations->get_integrations();

			$this->integration = $integrations['google_analytics_pro'];
		}

		return $this->integration;
	}


	/** Lifecycle methods ******************************************************/


	/**
	 * Performs any version-related changes.
	 *
	 * @since 1.3.0
	 * @see SV_WC_Plugin::upgrade()
	 * @param int $installed_version the currently installed version of the plugin
	 */
	protected function upgrade( $installed_version ) {

		// upgrade to 1.3.0
		if ( version_compare( $installed_version, '1.3.0', '<' ) ) {

			$settings = get_option( 'woocommerce_google_analytics_pro_settings' );

			// pre 1.3.0 `__gaTracker` was the default function name - store an option & setting for it, so we don't break compatibility
			add_option( 'woocommerce_google_analytics_upgraded_from_gatracker', true );

			$settings['function_name'] = '__gaTracker';

			// convert profile > property
			if ( ! empty( $settings['profile'] ) ) {

				$parts = explode( '|', $settings['profile'] );

				$settings['property'] = $parts[0] . '|' . $parts[1];

				unset( $settings['profile'] );
			}

			// install default event names for new events
			$new_events = array(
				'provided_billing_email',
				'selected_payment_method',
				'placed_order',
			);

			$form_fields = $this->get_integration()->get_form_fields();

			foreach ( $new_events as $event ) {
				$settings[ "{$event}_event_name" ] = $form_fields[ "{$event}_event_name" ]['default'];
			}

			update_option( 'woocommerce_google_analytics_pro_settings', $settings );

			delete_transient( 'wc_google_analytics_pro_profiles' );

			// ensure that events and properties are reloaded after the upgrade
			$this->get_integration()->load_events_and_properties();
		}
	}


} // \WC_Google_Analytics_Pro


/**
 * Gets the one true instance of Google Analytics Pro.
 *
 * @since 1.0.0
 * @return \WC_Google_Analytics_Pro
 */
function wc_google_analytics_pro() {

	return WC_Google_Analytics_Pro::instance();
}

// fire it up!
wc_google_analytics_pro();

} // init_woocommerce_google_analytics_pro()
