<?php

/**
 * WooPix - Pix Para WooCommerce
 *
 * @package Atomi\Woopix
 * @license GPL-3.0-or-later
 * @author Agência Atomi <contato@atomi.com.br>
 * @copyright 2021 Agência Atomi
 *
 * Plugin Name: WooPix - Pix Para WooCommerce
 * Plugin URI: https://github.com/atomi-lab/atomi-pix-para-woocommerce
 * Description: A maneira mais fácil de aceitar pagamentos Pix em sua loja WooCommerce.
 * Author: Agência Atomi
 * Author URI: https://www.atomi.com.br
 * Version: 1.0.0
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: atomi-woopix-for-woocommerce
 * Domain Path: /languages
 * Requires PHP: 7.0
 * Tested up to: 5.7.1
 * WC requires at least: 2.2
 * WC tested up to: 5.2.2
 *
 * WooPix - Pix Para WooCommerce is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * any later version.
 *
 * WooPix - Pix Para WooCommerce is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with WooPix - Pix Para WooCommerce. If not, see http://www.gnu.org/licenses/gpl-3.0.html.
 */

use Atomi\WooPix\WooPix;

defined( 'ABSPATH' ) or exit;

define( 'WOOPIX_VERSION', '1.0.0' );
define( 'WOOPIX_PAYMENT_ID', 'woopix-pix' );
define( 'WOOPIX_PLUGIN_NAME', 'WooPix - Pix para WooCommerce' );
define( 'WOOPIX_PLUGIN_URI', 'atomi-woopix-for-woocommerce' );
define( 'WOOPIX_PLUGIN_SNAKE_CASE', 'atomi_woopix_for_woocommerce' );

define( 'WOOPIX_PLUGIN_FILE', __FILE__ );
define( 'WOOPIX_PLUGIN_BASE_NAME', plugin_basename( __FILE__ ) );
define( 'WOOPIX_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'WOOPIX_PLUGIN_URL', plugin_dir_url( __FILE__ ) );


if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
	require __DIR__ . '/vendor/autoload.php';
	// initiate the plugin
	add_action( 'plugins_loaded', array( WooPix::class, 'init_plugin' ) );
}
