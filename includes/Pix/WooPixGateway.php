<?php

namespace Atomi\WooPix\Pix;

defined( 'ABSPATH' ) or exit;

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use WC_Payment_Gateway;

/**
 * Class WooPixGateway
 * @package Atomi\WooPix\Pix
 */
class WooPixGateway extends WC_Payment_Gateway
{
	/**
	 * WooPixGateway constructor.
	 */
	public function __construct()
	{
		$this->id                 = WOOPIX_PAYMENT_ID;
		$this->icon               = apply_filters( 'woocommerce_gateway_icon', WOOPIX_PLUGIN_URL . '/assets/public/img/logo-pix-icone-60.png' );
		$this->has_fields         = false;
		$this->method_title       = __( 'WooPix - Pix para WooCommerce' );
		$this->method_description = __( 'O WooPix habilita pagamentos via Pix. Ao final do pedido o cliente terá opção de escanear um Qr Code ou copiar o código para realizar o pagamento Pix e enviar o comprovante' );

		$this->supports = array( 'products' );

		$this->init_form_fields();

		$this->init_settings();

		$this->enabled              = $this->get_option( 'enabled' );
		$this->title                = $this->get_option( 'title' );
		$this->description          = $this->get_option( 'description' );
		$this->pix_description      = $this->get_option( 'pix_description' );
		$this->woopix_pix_key       = $this->get_option( 'woopix_pix_key' );
		$this->woopix_merchant_name = $this->get_option( 'woopix_merchant_name' );
		$this->woopix_merchant_city = $this->get_option( 'woopix_merchant_city' );
		$this->woopix_whatsapp      = $this->get_option( 'woopix_whatsapp' );
		$this->woopix_email         = $this->get_option( 'woopix_email' );

		// This action hook saves the settings
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array(
			$this,
			'process_admin_options'
		) );

		add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'woopix_thankyou_page' ) );

		// Customer Emails.
		add_action( 'woocommerce_email_before_order_table', array( $this, 'email_instructions' ), 10, 3 );

		if ( is_account_page() ) {
			add_action( 'woocommerce_order_details_before_order_table', array( $this, 'order_details_instructions' ) );
		}

		add_action( 'woocommerce_admin_order_data_after_order_details', array(
			$this,
			'admin_order_details_pix_used'
		) );

	}

	/**
	 * Plugin options
	 */
	public function init_form_fields()
	{
		$this->form_fields = array(
			'woopix_config_title'          => array(
				'title'       => __( 'Settings' ),
				'type'        => 'title',
				'description' => 'Configurações gerais do método de pagamento Pix'
			),
			'enabled'                      => array(
				'title'       => __( 'Habilitar' ),
				'label'       => __( 'Habilitar Pagamentos via Pix' ),
				'type'        => 'checkbox',
				'description' => '',
				'default'     => 'yes'
			),
			'title'                        => array(
				'title'       => __( 'Título' ),
				'type'        => 'text',
				'description' => __( 'Título que o cliente vê na página de checkout' ),
				'default'     => 'Faça um Pix',
				'required'    => true
			),
			'description'                  => array(
				'title'       => __( 'Descrição' ),
				'type'        => 'textarea',
				'description' => __( 'Descrição que o cliente vê na página de checkout' ),
				'default'     => __( 'Ao finalizar a compra, iremos te redirecionar para a próxima tela, onde será disponibilizado o código Pix e o número de WhatsApp para o envio do comprovante de pagamento.' ),
				'required'    => true
			),
			'pix_description'              => array(
				'title'       => __( 'Descrição Pix' ),
				'type'        => 'textarea',
				'description' => __( 'Descrição que o cliente vê no momento do pagamento do Pix. (O texto: {{pedido}} será substituído pelo código do pedido)' ),
				'default'     => get_bloginfo( 'name' ) . ' - ' . __( "Pedido # {{pedido}}" ),
				'required'    => true
			),
			'woopix_integration_title'     => array(
				'title'       => __( 'Integração' ),
				'type'        => 'title',
				'description' => 'Configurações de integração com o Pix'
			),
			'woopix_pix_key'               => array(
				'title'       => __( 'Chave Pix' ),
				'type'        => 'text',
				'description' => __( 'Sua chave Pix, recomendamos utilizar chave aleatória' ),
				'required'    => true
			),
			'woopix_merchant_name'         => array(
				'title'       => __( 'Nome completo do Titular da conta' ),
				'type'        => 'text',
				'description' => __( 'Exemplo: Fulano de Tal' ),
				'desc_tip'    => true,
				'required'    => true
			),
			'woopix_merchant_city'         => array(
				'title'       => __( 'Cidade do Titular da conta' ),
				'type'        => 'text',
				'description' => __( 'Exemplo: São Paulo' ),
				'desc_tip'    => true,
				'required'    => true
			),
			'woopix_payment_voucher_title' => array(
				'title'       => __( 'Comprovante' ),
				'type'        => 'title',
				'description' => 'Configurações de comprovante: é obrigatório ter pelo menos um método de envio de comprovante'
			),
			'woopix_whatsapp'              => array(
				'title'             => __( 'WhatsApp para envio de comprovante' ),
				'type'              => 'text',
				'description'       => __( 'Este número será disponibilizado ao cliente após a compra para enviar o comprovante de pagamento. Siga o modelo: 5511999999999' ),
				'default'           => '',
				'required'          => true,
				'custom_attributes' => [
					'format' => 'phone'
				]
			),
			'woopix_email'                 => array(
				'title'       => __( 'Email para envio de comprovante' ),
				'type'        => 'email',
				'description' => __( 'Este email será disponibilizado ao cliente após a compra para enviar o comprovante de pagamento.' ),
				'default'     => '',
				'required'    => true
			),
		);
	}

	/**
	 * Shows the plugin fields in checkout page, in this plugin only the description
	 */
	public function payment_fields()
	{
		$description = $this->get_description();
		if ( ! empty( $description ) ) {
			echo wpautop( wptexturize( esc_html__( $description ) ) );
		}
	}

	/**
	 * @return bool
	 */
	public function needs_setup()
	{
		return true;
	}

	/**
	 * Check if the gateway is available for use.
	 *
	 * @return bool
	 */
	public function is_available()
	{
		$available = ( 'yes' === $this->enabled && $this->woopix_pix_key !== '' && $this->woopix_merchant_city !== '' && $this->woopix_merchant_name !== '' && $this->woopix_whatsapp !== '' );

		return $available;
	}

	/**
	 * Processes the payment, updates the order status to 'on-hold', empties the cart and redirects to the thank you page
	 *
	 * @param int $order_id
	 *
	 * @return array
	 */
	public function process_payment( $order_id )
	{
		$order = wc_get_order( $order_id );

		$order->update_status( 'on-hold', __( 'Awaiting Pix payment', WOOPIX_PAYMENT_ID ) );

		WC()->cart->empty_cart();

		return array(
			'result'   => 'success',
			'redirect' => $this->get_return_url( $order ),
		);

	}

	/**
	 * Adds to the thank you page the Pix QR Code and link, so the customer can make the payment
	 *
	 * @param int $order_id
	 *
	 * @throws \Exception
	 */
	public function woopix_thankyou_page( $order_id )
	{
		$order = wc_get_order( $order_id );
		if ( $order->get_payment_method() != WOOPIX_PAYMENT_ID ) {
			return;
		}

		$pix_data = $this->generate_pix_payload( $order->get_id() );

		if ( ! empty( $pix_data['qr_code'] ) && ! empty( $pix_data['link'] ) ) {
			?>

            <div style="width: 100%;">
                <div style="margin: 0 auto; text-align: center;">
                    <h3 style="margin: 10px;"><span style="font-weight: bold;">1</span> - Escaneie o código abaixo:</h3>
                    <img style="cursor: pointer; margin: 0 auto;" src="<?= $pix_data['qr_code']; ?>"
                         onclick="copyCode();"
                         alt="<?= __( 'Escaneie o QR Code ou copie o código para efetuar o pagamento' ); ?>">
                    <h3 style="margin: 10px;"><span style="font-weight: bold;">2</span> - Nos envie o comprovante por:
                    </h3>
                    <div style="">
						<?php if ( ! empty( $this->woopix_whatsapp ) && ! empty( $this->woopix_email ) && is_email( $this->woopix_email ) ): ?>
                            <a href="https://wa.me/<?= $this->woopix_whatsapp; ?>?text=<?= urlencode( "Olá, estou enviando o comprovante do meu pedido #{$order->get_order_number()}" ); ?>"
                               style="padding: 10px; background: #25D366; border-radius: 5px; color: white; text-decoration: underline;"
                               target="_blank"
                               title="Envie o comprovante por WhatsApp">WhatsApp</a>
                            <a href="mailto:<?= $this->woopix_email ?>" target="_blank"
                               style="padding: 10px; background: #0274be; border-radius: 5px; color: white; text-decoration: underline;"
                               title="Envie o comprovante por Email">Email</a>
						<?php elseif ( ! empty( $this->woopix_whatsapp ) ): ?>
                            <a href="https://wa.me/<?= $this->woopix_whatsapp; ?>?text=<?= urlencode( "Olá, estou enviando o comprovante do meu pedido #{$order->get_order_number()}" ); ?>"
                               style="padding: 10px; background: #25D366; border-radius: 5px; color: white; text-decoration: underline;"
                               target="_blank"
                               title="Envie o comprovante por WhatsApp">WhatsApp</a>
						<?php elseif ( ! empty( $this->woopix_email ) && is_email( $this->woopix_email ) ): ?>
                            <a href="mailto:<?= $this->woopix_email ?>" target="_blank"
                               style="padding: 10px; background: #0274be; border-radius: 5px; color: white; text-decoration: underline;"
                               title="Envie o comprovante por Email">Email</a>
						<?php endif; ?>
                    </div>
                    <h3 style="margin: 10px;">Ou copie o código Pix abaixo e cole em seu aplicativo favorito:</h3>
                    <div style="display: flex; justify-content: center; flex-wrap: wrap; margin: 0 0 10px 0;">
                        <label for="pix_code" style="display: none;">Código do Pix:</label>
                        <input style="width: 175px; height: 50px; margin: 0;" id="pix_code" type="text" readonly
                               value="<?= $pix_data['link'] ?>">
                        <button onclick="copyCode();" style="width: 175px; height: 50px; margin: 0;">
                            Copiar Código
                        </button>
                    </div>
                    <div id="copied-div" style="margin: 0 0 10px 0; display: none;">
                        <h3 style="" class="green">Código copiado para a área de transferência</h3>
                    </div>
                </div>
            </div>

            <script>
                function copyCode() {
                    let codeInput = document.getElementById('pix_code');
                    let copiedDiv = document.getElementById('copied-div');

                    codeInput.select();
                    codeInput.setSelectionRange(0, 99999);
                    document.execCommand("copy");

                    if (copiedDiv.style.display === 'none') {
                        copiedDiv.style.display = 'block';
                    }
                }
            </script>

			<?php
		}
	}

	/**
	 * Adds to the customer order email Pix payment method information: QR Code and Pix link
	 * @throws \Exception
	 */
	public function email_instructions( $order_id, $email )
	{
		$order = wc_get_order( $order_id );

		if ( $order->get_payment_method() != WOOPIX_PAYMENT_ID && get_class( $email ) !== 'WC_Email_Customer_On_Hold_Order' ) {
			return;
		}

		$pix_data = $this->generate_pix_payload( $order->get_id() );

		?>

        <div style="margin: 36px auto;">
            <h3 style="font-size: 18px;">Pague com o QR Code abaixo</h3>
            <img style="display: table; background-color: #FFF" src="<?= $pix_data['qr_code']; ?>"
                 alt="Pix QR Code">
        </div>
        <div style="margin: 36px auto;">
            <h3 style="font-size: 18px;">Pague copiando o código Pix abaixo e cole em seu aplicativo favorito:</h3>
            <p style="font-size: 14px; margin-bottom:0"><?= $pix_data['link']; ?></p>
        </div>

		<?php
	}

	/**
	 * @param $order_id
	 *
	 * @throws \Exception
	 */
	public function order_details_instructions( $order_id )
	{
		$order = wc_get_order( $order_id );

		if ( $order->get_payment_method() != WOOPIX_PAYMENT_ID ) {
			return;
		}

		$pix_data = $this->generate_pix_payload( $order->get_id() );

		if ( $order->get_status() === 'on-hold' ) {
			?>

            <div style="width: 100%;">
                <div style="margin: 0 auto; text-align: center;">
                    <h2>Caso ainda não tenha efetuado o pagamento do Pix:</h2>
                    <h3 style="margin: 10px;"><span style="font-weight: bold;">1</span> - Escaneie o código abaixo:</h3>
                    <img style="cursor: pointer; margin: 0 auto;" src="<?= $pix_data['qr_code']; ?>"
                         onclick="copyCode();"
                         alt="<?= __( 'Escaneie o QR Code ou copie o código para efetuar o pagamento' ); ?>">
                    <h3 style="margin: 10px;"><span style="font-weight: bold;">2</span> - Nos envie o comprovante por:
                    </h3>
                    <div style="">
						<?php if ( ! empty( $this->woopix_whatsapp ) && ! empty( $this->woopix_email ) && is_email( $this->woopix_email ) ): ?>
                            <a href="https://wa.me/<?= $this->woopix_whatsapp; ?>?text=<?= urlencode( "Olá, estou enviando o comprovante do meu pedido #{$order->get_order_number()}" ); ?>"
                               style="padding: 10px; background: #25D366; border-radius: 5px; color: white; text-decoration: underline;"
                               target="_blank"
                               title="Envie o comprovante por WhatsApp">WhatsApp</a>
                            <a href="mailto:<?= $this->woopix_email ?>" target="_blank"
                               style="padding: 10px; background: #0274be; border-radius: 5px; color: white; text-decoration: underline;"
                               title="Envie o comprovante por Email">Email</a>
						<?php elseif ( ! empty( $this->woopix_whatsapp ) ): ?>
                            <a href="https://wa.me/<?= $this->woopix_whatsapp; ?>?text=<?= urlencode( "Olá, estou enviando o comprovante do meu pedido #{$order->get_order_number()}" ); ?>"
                               style="padding: 10px; background: #25D366; border-radius: 5px; color: white; text-decoration: underline;"
                               target="_blank"
                               title="Envie o comprovante por WhatsApp">WhatsApp</a>
						<?php elseif ( ! empty( $this->woopix_email ) && is_email( $this->woopix_email ) ): ?>
                            <a href="mailto:<?= $this->woopix_email ?>" target="_blank"
                               style="padding: 10px; background: #0274be; border-radius: 5px; color: white; text-decoration: underline;"
                               title="Envie o comprovante por Email">Email</a>
						<?php endif; ?>
                    </div>
                    <h3 style="margin: 10px;">Ou copie o código Pix abaixo e cole em seu aplicativo favorito:</h3>
                    <div style="display: flex; justify-content: center; flex-wrap: wrap; margin: 0 0 10px 0;">
                        <label for="pix_code" style="display: none;">Código do Pix:</label>
                        <input style="width: 175px; height: 50px; margin: 0;" id="pix_code" type="text" readonly
                               value="<?= $pix_data['link'] ?>">
                        <button onclick="copyCode();" style="width: 175px; height: 50px; margin: 0;">
                            Copiar Código
                        </button>
                    </div>
                    <div id="copied-div" style="margin: 0 0 10px 0; display: none;">
                        <h3 style="" class="green">Código copiado para a área de transferência</h3>
                    </div>
                </div>
            </div>

            <script>
                function copyCode() {
                    let codeInput = document.getElementById('pix_code');
                    let copiedDiv = document.getElementById('copied-div');

                    codeInput.select();
                    codeInput.setSelectionRange(0, 99999);
                    document.execCommand("copy");

                    if (copiedDiv.style.display === 'none') {
                        copiedDiv.style.display = 'block';
                    }
                }
            </script>

			<?php
		}
	}

	public function admin_order_details_pix_used( $order_id )
	{
		$order = wc_get_order( $order_id );

		if ( $order->get_payment_method() != WOOPIX_PAYMENT_ID ) {
			return;
		}

		$pix_data = $this->generate_pix_payload( $order->get_id() );

		?>

        <p class="form-field form-field-wide wc-customer-user" style="margin: 10px 0 0 0;">
            <span style="font-weight: bold; margin: 0 0 5px 0;">Código Pix disponibilizado para o cliente:</span>
            <label for="pix_code" style="display: none;">Código do Pix:</label>
            <input style="/*width: 175px; height: 50px; margin: 0;*/" id="pix_code" type="text" readonly
                   value="<?= $pix_data['link'] ?>">
            <button id="copyCodeBtn" style="width: 100%;/* height: 50px; margin: 0;*/">
                Copiar Código
            </button>
        </p>

        <script>
            let copyCodeBtn = document.getElementById('copyCodeBtn');

            copyCodeBtn.addEventListener('click', function copyCode(e) {
                e.preventDefault();
                let codeInput = document.getElementById('pix_code');
                let copiedDiv = document.getElementById('copied-div');

                codeInput.select();
                codeInput.setSelectionRange(0, 99999);
                document.execCommand("copy");
            });


        </script>

		<?php
	}

	/**
	 * Generates the Pix payload: creates the QR Code, uploads it to the
	 * wp-content/uploads/atomi-woopix-for-woocommerce folder and generates
	 * the Pix link
	 *
	 * @param int $order_id
	 *
	 * @return array|void
	 * @throws \Exception
	 */
	public function generate_pix_payload( $order_id )
	{
		$order = wc_get_order( $order_id );

		if ( $order->get_payment_method() != WOOPIX_PAYMENT_ID ) {
			return;
		}

		$pix_description = str_replace( '{{pedido}}', $order->get_order_number(), $this->pix_description );

		$payload = new \Atomi\WooPix\Pix\PixPayload(
			$this->woopix_pix_key,
			$pix_description,
			$this->woopix_merchant_name,
			$this->woopix_merchant_city,
			$order->get_id(),
			$order->get_total()
		);

		/**
		 * Code
		 */
		$payload_qr_code_string = $payload->get_payload();

		/**
		 * Image
		 */
		$upload     = wp_upload_dir();
		$uploadPath = $upload['basedir'] . '/' . WOOPIX_PLUGIN_URI . '/';
		$uploadUrl  = $upload['baseurl'] . '/' . WOOPIX_PLUGIN_URI . '/';
		$qr_image   = false;

		if ( ! file_exists( $uploadPath ) ) {
			wp_mkdir_p( $uploadPath );
		}

		$writer          = new PngWriter();
		$payload_qr_code = QrCode::create( $payload_qr_code_string )
		                         ->setSize( 250 )
		                         ->setMargin( 5 );

		$qr_code       = $writer->write( $payload_qr_code );
		$qr_code_image = $qr_code->getDataUri();

		// upload
		if ( isset( $qr_code_image ) && ! empty( $qr_code_image ) ) {
			$imageName = urlencode( base64_encode( 'pix-order-' . $order->get_order_number() ) ) . '.png';
			$file      = $uploadPath . $imageName;
			if ( ! file_exists( $uploadPath . $imageName ) ) {
				$qr_image = $qr_code->saveToFile( $file );
			}

			return $pix_data = array(
				'qr_code' => $uploadUrl . $imageName,
				'link'    => $payload_qr_code_string
			);
		} else {
			return $pix_data = array(
				'qr_code' => $qr_code_image,
				'link'    => $payload_qr_code_string
			);
		}
	}
}