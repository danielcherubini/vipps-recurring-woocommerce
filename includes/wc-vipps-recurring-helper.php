<?php

defined( 'ABSPATH' ) || exit;

/**
 * Provides static methods as helpers.
 *
 * @since 4.0.0
 */
class WC_Vipps_Recurring_Helper {
	/**
	 * Vipps charges
	 */
	public const META_CHARGE_FAILED = '_vipps_recurring_failed_charge';
	public const META_CHARGE_FAILED_REASON = '_vipps_recurring_failed_charge_reason';
	public const META_CHARGE_FAILED_DESCRIPTION = '_vipps_recurring_failed_charge_description';
	public const META_CHARGE_CAPTURED = '_vipps_recurring_captured';
	public const META_CHARGE_PENDING = '_vipps_recurring_pending_charge';
	public const META_CHARGE_ID = '_charge_id';
	public const META_CHARGE_LATEST_STATUS = '_vipps_recurring_latest_api_status';

	/**
	 * Vipps agreements
	 */
	public const META_AGREEMENT_ID = '_agreement_id';
	public const META_AGREEMENT_CONFIRMATION_URL = '_agreement_confirmation_url';

	/**
	 * Product
	 */
	public const META_PRODUCT_DIRECT_CAPTURE = '_vipps_recurring_direct_capture';

	/**
	 * Orders
	 */
	public const META_ORDER_STOCK_REDUCED = '_order_stock_reduced';
	public const META_ORDER_TRANSACTION_ID = '_transaction_id';
	public const META_ORDER_INITIAL = '_vipps_recurring_initial';
	public const META_ORDER_ZERO_AMOUNT = '_vipps_recurring_zero_amount';

	/**
	 * Subscription
	 */
	public const META_SUBSCRIPTION_WAITING_FOR_GATEWAY_CHANGE = '_vipps_recurring_waiting_for_gateway_change';
	public const META_SUBSCRIPTION_UPDATE_IN_APP = '_vipps_recurring_update_in_app';

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
		return self::get_meta( $order, self::META_AGREEMENT_ID );
	}

	/**
	 * @param $order
	 *
	 * @return mixed
	 */
	public static function is_charge_captured_for_order( $order ) {
		return self::get_meta( $order, self::META_CHARGE_CAPTURED );
	}

	/**
	 * @param $order
	 *
	 * @return mixed
	 */
	public static function get_charge_id_from_order( $order ) {
		return self::get_meta( $order, self::META_CHARGE_ID );
	}

	/**
	 * @param $order
	 *
	 * @return mixed
	 */
	public static function get_latest_api_status_from_order( $order ) {
		return self::get_meta( $order, self::META_CHARGE_LATEST_STATUS );
	}

	/**
	 * @param $order
	 * @param $status
	 */
	public static function set_latest_api_status_for_order( $order, $status ) {
		self::update_meta_data( $order, self::META_CHARGE_LATEST_STATUS, $status );
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
			? update_post_meta( $order->id, self::META_ORDER_TRANSACTION_ID, $transaction_id )
			: $order->set_transaction_id( $transaction_id );
	}

	/**
	 * @param $order
	 *
	 * @return mixed
	 */
	public static function get_transaction_id_for_order( $order ) {
		return self::is_wc_lt( '3.0' )
			? get_post_meta( self::get_id( $order ), self::META_ORDER_TRANSACTION_ID )
			: $order->get_transaction_id();
	}

	/**
	 * @param $order
	 * @param $charge_id
	 */
	public static function set_order_as_pending( $order, $charge_id ) {
		self::update_meta_data( $order, self::META_CHARGE_PENDING, true );
		self::update_meta_data( $order, self::META_CHARGE_CAPTURED, true );
		self::update_meta_data( $order, self::META_CHARGE_ID, $charge_id );
	}

	/**
	 * @param $order
	 */
	public static function set_order_as_not_pending( $order ) {
		self::update_meta_data( $order, self::META_CHARGE_PENDING, false );
		self::update_meta_data( $order, self::META_CHARGE_CAPTURED, false );
	}

	/**
	 * @param $order
	 * @param $charge
	 */
	public static function set_order_charge_failed( $order, $charge ) {
		self::set_order_as_not_pending( $order );
		self::update_meta_data( $order, self::META_CHARGE_FAILED, true );

		if ( isset( $charge['failureReason'] ) ) {
			self::update_meta_data( $order, self::META_CHARGE_FAILED_REASON, $charge['failureReason'] );
		}

		if ( isset( $charge['failureDescription'] ) ) {
			self::update_meta_data( $order, self::META_CHARGE_FAILED_DESCRIPTION, $charge['failureDescription'] );
		}
	}

	/**
	 * @param $order
	 * @param $charge_id
	 */
	public static function set_order_charge_not_failed( $order, $charge_id ) {
		self::update_meta_data( $order, self::META_CHARGE_FAILED, false );
		self::set_order_as_pending( $order, $charge_id );
	}

	/**
	 * @param $order
	 *
	 * @return mixed
	 */
	public static function is_charge_failed_for_order( $order ): bool {
		return (bool) self::get_meta( $order, self::META_CHARGE_FAILED );
	}

	/**
	 * @param $order
	 *
	 * @return mixed
	 */
	public static function get_failure_reason_for_order( $order ) {
		return self::get_meta( $order, self::META_CHARGE_FAILED_REASON );
	}

	/**
	 * @param $order
	 *
	 * @return mixed
	 */
	public static function get_failure_description_for_order( $order ) {
		return self::get_meta( $order, self::META_CHARGE_FAILED_DESCRIPTION );
	}

	/**
	 * @param $order
	 *
	 * @return mixed
	 */
	public static function is_stock_reduced_for_order( $order ) {
		return self::get_meta( $order, self::META_ORDER_STOCK_REDUCED );
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