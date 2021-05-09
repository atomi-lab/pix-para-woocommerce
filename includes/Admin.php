<?php

namespace Atomi\WooPix;

defined( 'ABSPATH' ) or exit;

/**
 * Class Admin
 * @package Atomi\WooPix
 */
class Admin
{
	/**
	 * Initiates all admin parts of the plugin
	 */
	public function init_admin()
	{
		add_filter( 'plugin_action_links_' . WOOPIX_PLUGIN_BASE_NAME, array(
			$this,
			'woopix_admin_action_links'
		), 10, 5 );
	}

	/**
	 * Adds action links to plugin in the plugins page
	 *
	 * @param $actions
	 * @param $plugin_file
	 *
	 * @return array|mixed|string[]
	 */
	public function woopix_admin_action_links( $actions, $plugin_file )
	{
		static $plugin;

		if ( ! isset( $plugin ) ) {
			$plugin = WOOPIX_PLUGIN_BASE_NAME;
		}
		if ( $plugin == $plugin_file ) {

			$settings  = array(
				'settings' => '<a href="' . esc_url( admin_url() ) . 'admin.php?page=wc-settings&tab=checkout&section=woopix-pix">' . __( 'Settings' ) . '</a>'
			);
			$site_link = array( 'support' => '<a href="https://www.atomi.com.br/woopix-pix-para-woocommerce" target="_blank">' . __( 'Support' ) . '</a>' );

			$actions = array_merge( $site_link, $actions );
			$actions = array_merge( $settings, $actions );
		}

		return $actions;
	}
}