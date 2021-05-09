<?php

namespace Atomi\WooPix;

use Atomi\WooPix\Pix\WooPixGateway;

defined( 'ABSPATH' ) or exit;

/**
 * Class WooPix
 * @package Atomi\WooPix
 */
class WooPix
{
	/**
	 * Initiates de plugin
	 */
	public static function init_plugin()
	{
		if ( class_exists( 'WC_Integration' ) ) {
			( new Admin() )->init_admin();
			add_filter( 'woocommerce_payment_gateways', array( WooPix::class, 'woopix_add_pix_gateway' ) );
		} else {
			add_action( 'admin_notices', array( WooPix::class, 'woocommerce_missing_notice' ) );
		}
	}

	/**
	 * Adds the WooPix payment gateway to WooCommerce
	 *
	 * @param array $gateways
	 *
	 * @return array
	 */
	public static function woopix_add_pix_gateway( array $gateways ): array
	{
		array_push( $gateways, WooPixGateway::class );

		return $gateways;
	}

	/**
	 * Adds error notice if WooCommerce plugin is not active
	 */
	public static function woocommerce_missing_notice()
	{
		?>
        <div class="error">
            <p>
				<?= WOOPIX_PLUGIN_NAME . ' ' . __( 'precisa do ' ); ?>
                <a href="https://woocommerce.com/" target="_blank">WooCommerce</a>
				<?= ' ' . __( 'instalado e ativo para funcionar!' ); ?>
            </p>
        </div>
		<?php
	}

}
