<?php

defined( 'ABSPATH' ) || exit;

/**
 * Provides static methods as helpers.
 *
 * @since 4.0.0
 */
class WC_Vipps_Recurring_Helper {
	/**
	 * Get Vipps amount to pay
	 *
	 * @param float $total Amount due.
	 *
	 * @return int
	 */
	public static function get_vipps_amount( $total ): int {
		return absint( wc_format_decimal( ( (float) $total * 100 ), wc_get_price_decimals() ) ); // In cents.
	}

	/**
	 * Vipps uses smallest denomination in currencies such as cents/øre.
	 * We need to format the returned currency from Vipps into human readable form.
	 * The amount is not used in any calculations so returning string is sufficient.
	 *
	 * @param object $balance_transaction
	 *
	 * @return string
	 */
	public static function format_balance_fee( $balance_transaction ): string {
		if ( ! is_object( $balance_transaction ) ) {
			return false;
		}

		return number_format( $balance_transaction->net / 100, 2, '.', '' );
	}

	/**
	 * @param null $setting
	 *
	 * @return mixed|string|void
	 */
	public static function get_settings( $setting = null ) {
		$all_settings = get_option( 'woocommerce_vipps_recurring_settings', [] );

		if ( null === $setting ) {
			return $all_settings;
		}

		return $all_settings[ $setting ] ?? '';
	}

	/**
	 * Checks if WC version is less than passed in version.
	 *
	 * @param string $version Version to check against.
	 *
	 * @return bool
	 * @since 1.0.0
	 */
	public static function is_wc_lt( $version ): bool {
		if ( defined( 'WC_VERSION' ) ) {
			return version_compare( WC_VERSION, $version, '<' );
		}

		return true;
	}

	/**
	 * Checks if WP version is less than passed in version.
	 *
	 * @param string $version Version to check against.
	 *
	 * @return bool
	 * @since 1.1.1
	 */
	public static function is_wp_lt( $version ): bool {
		global $wp_version;

		return version_compare( $wp_version, $version, '<' );
	}

	/**
	 * Checks if a phone number is valid according to Vipps standards
	 *
	 * @param $phone_number
	 *
	 * @return bool
	 */
	public static function is_valid_phone_number( $phone_number ): bool {
		return strlen( $phone_number ) >= 8 && strlen( $phone_number ) <= 16;
	}

	/**
	 * @param DateTime $date
	 *
	 * @return string
	 */
	public static function get_rfc_3999_date( DateTime $date ): string {
		return $date->format( 'Y-m-d\TH:i:s\Z' );
	}

	/**
	 * @param string $date
	 *
	 * @return string
	 */
	public static function rfc_3999_date_to_unix( string $date ): string {
		return strtotime( $date );
	}

	/**
	 * Gets the id of a resource
	 *
	 * @param $order
	 *
	 * @return mixed
	 */
	public static function get_id( $resource ) {
		return method_exists( $resource, 'get_id' )
			? $resource->get_id()
			: $resource->id;
	}

	/**
	 * Gets meta data from a resource
	 *
	 * @param $resource
	 * @param $meta_key
	 *
	 * @return mixed
	 */
	public static function get_meta( $resource, $meta_key ) {
		return self::is_wc_lt( '3.0' )
			? get_post_meta( self::get_id( $resource ), $meta_key, true )
			: $resource->get_meta( $meta_key );
	}

	/**
	 * Updates meta data on a resource
	 *
	 * @param $resource
	 * @param $meta_key
	 * @param $meta_value
	 */
	public static function update_meta_data( $resource, $meta_key, $meta_value ) {
		self::is_wc_lt( '3.0' )
			? update_post_meta( self::get_id( $resource ), $meta_key, $meta_value )
			: $resource->update_meta_data( $meta_key, $meta_value );
	}

	/**
	 * @param $order
	 *
	 * @return mixed
	 */
	public static function get_agreement_id_from_order( $order ) {
		return self::get_meta( $order, '_agreement_id' );
	}

	/**
	 * @param $order
	 *
	 * @return mixed
	 */
	public static function is_charge_captured_for_order( $order ) {
		return self::get_meta( $order, '_vipps_recurring_captured' );
	}

	/**
	 * @param $order
	 *
	 * @return mixed
	 */
	public static function get_charge_id_from_order( $order ) {
		return self::get_meta( $order, '_charge_id' );
	}

	/**
	 * @param $order
	 *
	 * @return mixed
	 */
	public static function get_payment_method( $order ) {
		return self::is_wc_lt( '3.0' )
			? $order->payment_method
			: $order->get_payment_method();
	}

	/**
	 * @param $order
	 * @param $transaction_id
	 */
	public static function set_transaction_id_for_order( $order, $transaction_id ) {
		self::is_wc_lt( '3.0' )
			? update_post_meta( $order->id, '_transaction_id', $transaction_id )
			: $order->set_transaction_id( $transaction_id );
	}

	/**
	 * @param $order
	 *
	 * @return mixed
	 */
	public static function get_transaction_id_for_order( $order ) {
		return self::is_wc_lt( '3.0' )
			? get_post_meta( self::get_id( $order ), '_transaction_id' )
			: $order->get_transaction_id();
	}

	/**
	 * @param $order
	 * @param $charge_id
	 */
	public static function set_order_as_pending( $order, $charge_id ) {
		self::update_meta_data( $order, '_vipps_recurring_pending_charge', true );
		self::update_meta_data( $order, '_vipps_recurring_captured', true );
		self::update_meta_data( $order, '_charge_id', $charge_id );
	}

	/**
	 * @param $order
	 */
	public static function set_order_as_not_pending( $order ) {
		self::update_meta_data( $order, '_vipps_recurring_pending_charge', false );
		self::update_meta_data( $order, '_vipps_recurring_captured', false );
	}

	/**
	 * @param $order
	 *
	 * @return mixed
	 */
	public static function is_stock_reduced_for_order( $order ) {
		return self::get_meta( $order, '_order_stock_reduced' );
	}

	/**
	 * @param $order
	 */
	public static function reduce_stock_for_order( $order ) {
		self::is_wc_lt( '3.0' )
			? $order->reduce_order_stock()
			: wc_reduce_stock_levels( self::get_id( $order ) );
	}
}
