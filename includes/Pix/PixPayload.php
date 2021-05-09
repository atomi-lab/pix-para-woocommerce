<?php

namespace Atomi\WooPix\Pix;

defined( 'ABSPATH' ) or exit;

/**
 * Class PixPayload
 * @package Atomi\WooPix\Pix
 */
class PixPayload
{
	/**
	 * Using https://github.com/william-costa/wdev-qrcode-pix-estatico-php repository as code base
	 */

	/** @var string */
	const DEFAULT_BANK_DOMAIN = 'br.gov.bcb.pix';

	/**
	 * Pix Payload IDs
	 * @var string
	 */
	const ID_PAYLOAD_FORMAT_INDICATOR = '00';

	/** @var string */
	const ID_MERCHANT_ACCOUNT_INFORMATION = '26';

	/** @var string */
	const ID_MERCHANT_ACCOUNT_INFORMATION_GUI = '00';

	/** @var string */
	const ID_MERCHANT_ACCOUNT_INFORMATION_KEY = '01';

	/** @var string */
	const ID_MERCHANT_ACCOUNT_INFORMATION_DESCRIPTION = '02';

	/** @var string */
	const ID_MERCHANT_CATEGORY_CODE = '52';

	/** @var string */
	const ID_TRANSACTION_CURRENCY = '53';

	/** @var string */
	const ID_TRANSACTION_AMOUNT = '54';

	/** @var string */
	const ID_COUNTRY_CODE = '58';

	/** @var string */
	const COUNTRY_CODE = 'BR';

	/** @var string */
	const ID_MERCHANT_NAME = '59';

	/** @var string */
	const ID_MERCHANT_CITY = '60';

	/** @var string */
	const ID_ADDITIONAL_DATA_FIELD_TEMPLATE = '62';

	/** @var string */
	const ID_ADDITIONAL_DATA_FIELD_TEMPLATE_TXID = '05';

	/** @var string */
	const ID_CRC16 = '63';

	/**
	 * Pix key (email, phone, CPF, random key...)
	 * @var string
	 */
	private $pix_key;

	/**
	 * Pix payment description
	 * @var string
	 */
	private $payment_description;

	/**
	 * Account Merchant Name
	 * @var string
	 */
	private $merchant_name;

	/**
	 * Account Merchant City
	 * @var string
	 */
	private $merchant_city;

	/**
	 * Pix transaction ID
	 * @var string
	 */
	private $txid;

	/**
	 * Pix transaction amount
	 * @var float
	 */
	private $amount;

	/**
	 * PixPayload constructor.
	 *
	 * @param string $pix_key
	 * @param string $payment_description
	 * @param string $merchant_name
	 * @param string $merchant_city
	 * @param string $txid
	 * @param float $amount
	 */
	public function __construct( $pix_key, $payment_description, $merchant_name, $merchant_city, $txid, $amount )
	{
		$this->pix_key             = $this->set_pix_key( $pix_key );
		$this->payment_description = $this->set_payment_description( $payment_description );
		$this->merchant_name       = $this->set_merchant_name( $merchant_name );
		$this->merchant_city       = $this->set_merchant_city( $merchant_city );
		$this->txid                = $this->set_txid( $txid );
		$this->amount              = $this->set_amount( $amount );

		return $this;
	}

	/**
	 * @param string $pix_key
	 *
	 * @return string
	 */
	public function set_pix_key( $pix_key )
	{
		return $this->pix_key = $pix_key;
	}

	/**
	 * @param string $payment_description
	 *
	 * @return string
	 */
	public function set_payment_description( $payment_description )
	{
		return $this->payment_description = $payment_description;
	}

	/**
	 * @param string $merchant_name
	 *
	 * @return string
	 */
	public function set_merchant_name( $merchant_name )
	{
		return $this->merchant_name = $merchant_name;
	}

	/**
	 * @param string $merchant_city
	 *
	 * @return string
	 */
	public function set_merchant_city( $merchant_city )
	{
		return $this->merchant_city = $merchant_city;
	}

	/**
	 * @param string $txid
	 *
	 * @return string
	 */
	public function set_txid( $txid )
	{
		return $this->txid = $txid;
	}

	/**
	 * @param float $amount
	 *
	 * @return string
	 */
	public function set_amount( $amount )
	{
		return $this->amount = number_format( $amount, '2', '.', '' );
	}

	/**
	 * @param string $id
	 * @param string $value
	 *
	 * @return string
	 */
	private function get_value( $id, $value )
	{
		$size = str_pad( strlen( $value ), 2, '0', STR_PAD_LEFT );

		return $id . $size . $value;
	}

	/**
	 * @return string
	 */
	private function get_merchant_account_information()
	{
		// bank
		$gui = $this->get_value( self::ID_MERCHANT_ACCOUNT_INFORMATION_GUI, self::DEFAULT_BANK_DOMAIN );

		// pix key
		$key = $this->get_value( self::ID_MERCHANT_ACCOUNT_INFORMATION_KEY, $this->pix_key );

		//payment description
		$description = $this->get_value( self::ID_MERCHANT_ACCOUNT_INFORMATION_DESCRIPTION, $this->payment_description );

		return $this->get_value( self::ID_MERCHANT_ACCOUNT_INFORMATION, $gui . $key . $description );
	}

	/**
	 * TX ID
	 * @return string
	 */
	private function get_additional_data_field_template()
	{
		$txid = $this->get_value( self::ID_ADDITIONAL_DATA_FIELD_TEMPLATE_TXID, $this->txid );

		return $this->get_value( self::ID_ADDITIONAL_DATA_FIELD_TEMPLATE, $txid );
	}

	/**
	 * Método responsável por calcular o valor da hash de validação do código pix
	 *
	 * @param string $payload
	 *
	 * @return string
	 */
	private function get_CRC16( $payload )
	{
		//ADICIONA DADOS GERAIS NO PAYLOAD
		$payload .= self::ID_CRC16 . '04';

		//DADOS DEFINIDOS PELO BACEN
		$polinomio = 0x1021;
		$resultado = 0xFFFF;

		//CHECKSUM
		if ( ( $length = strlen( $payload ) ) > 0 ) {
			for ( $offset = 0; $offset < $length; $offset ++ ) {
				$resultado ^= ( ord( $payload[ $offset ] ) << 8 );
				for ( $bitwise = 0; $bitwise < 8; $bitwise ++ ) {
					if ( ( $resultado <<= 1 ) & 0x10000 ) {
						$resultado ^= $polinomio;
					}
					$resultado &= 0xFFFF;
				}
			}
		}

		//RETORNA CÓDIGO CRC16 DE 4 CARACTERES
		return self::ID_CRC16 . '04' . strtoupper( dechex( $resultado ) );
	}

	/**
	 * Returns the full Pix Payload string
	 * @return string
	 */
	public function get_payload()
	{
		$payload = $this->get_value( self::ID_PAYLOAD_FORMAT_INDICATOR, '01' ) .
		           $this->get_merchant_account_information() .
		           $this->get_value( self::ID_MERCHANT_CATEGORY_CODE, '0000' ) .
		           $this->get_value( self::ID_TRANSACTION_CURRENCY, '986' ) .
		           $this->get_value( self::ID_TRANSACTION_AMOUNT, $this->amount ) .
		           $this->get_value( self::ID_COUNTRY_CODE, self::COUNTRY_CODE ) .
		           $this->get_value( self::ID_MERCHANT_NAME, $this->merchant_name ) .
		           $this->get_value( self::ID_MERCHANT_CITY, $this->merchant_city ) .
		           $this->get_additional_data_field_template();

		return $payload . $this->get_CRC16( $payload );
	}

}
