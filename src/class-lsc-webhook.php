<?php

namespace lsc;

/**
 * Class to handle Webhook.
 */
class Webhook {
	/**
	 * Contains instance or null
	 *
	 * @var object|null
	 */
	private static $instance = null;

	/**
	 * Returns instance of Webhook.
	 *
	 * @return object
	 */
	public static function get_instance() {

		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor for Webhook.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'register_customer' ) );
	}

	/**
	 * Handles the HTTP Request sent from order_created event in Lemon Squeezy.
	 */
	public function register_customer() {
		$bearer_token = ''; // The API key for Lemon Squeezy.

		// Validate it's coming from Lemon Squeezy.
		$secret    = 'ls_order_changed'; // add webhook secret.
		$payload   = file_get_contents( 'php://input' );
		$hash      = hash_hmac( 'sha256', $payload, $secret );
		$signature = $_SERVER['HTTP_X_SIGNATURE'] ?? null;

		if ( $hash !== $signature ) {
			return;
		}

		$data = json_decode( $payload );

		// Get the id.
		$order_id = $data->data->id;

		// Get customer data.
		$customer = array(
			'event'      => $data->meta->event_name,
			'full_name'  => $data->data->attributes->user_name,
			'user_email' => $data->data->attributes->user_email,
			'created_at' => $data->data->attributes->created_at,
		);

		// Get product data.
		$order_item = $this->get_product_from_order_id( $order_id, $bearer_token );

		// Set product name.
		if ( is_array( $order_item ) ) {
			$product_name = $order_item['product_name'] . ' (' . $order_item['variant_name'] . ')';
		} else {
			$product_name = '';
		}

		// Maybe create user.
		$users_exists = email_exists( $customer['user_email'] );

		if ( $users_exists ) {
			$user_id = get_user_by( 'email', $user->ID );

			// Only update metadata.
			update_user_meta( $user_id, 'purchase-date', sanitize_text_field( $customer['created_at'] ) );
			update_user_meta( $user_id, 'purchase-product', sanitize_text_field( $product_name ) );
		} else {
			// Check that a new order was created.
			if ( 'order_created' === $customer['event'] ) {
				$password = wp_generate_password( 12, true );
				$user_id  = wp_create_user( $customer['full_name'], $password, $customer['user_email'] );

				// Update meta data.
				update_user_meta( $user_id, 'purchase-date', sanitize_text_field( $customer['created_at'] ) );
				update_user_meta( $user_id, 'purchase-product', sanitize_text_field( $product_name ) );

				// Send e-mail to the user.
				wp_new_user_notification( $user_id, null, 'both' );

				// Send post request with data to URL (optional).
				$args = array(
					'e-mail'    => $customer['user_email'],
					'full_name' => $customer['full_name'],
				);

				//wp_remote_post( 'https://sample-url.com' ), $args );
			}
		}
	}

	/**
	 * Get product from given LS order id.
	 *
	 * @param  int    $order_id given order id.
	 * @param  string $bearer_token API token.
	 * @return array
	 */
	public function get_product_from_order_id( $order_id, $bearer_token ) {

		$response = wp_remote_get(
			'https://api.lemonsqueezy.com/v1/orders/' . $order_id . '/order-items',
			array(
				'headers' => array(
					'Accept'        => 'application/vnd.api+json',
					'Content-Type'  => 'application/vnd.api+json',
					'Cache-Control' => 'no-cache',
					'Authorization' => 'Bearer ' . $bearer_token,
				),
			)
		);

		if ( ! is_wp_error( $response ) ) {
			if ( 200 == wp_remote_retrieve_response_code( $response ) ) {
				$body  = wp_remote_retrieve_body( $response );
				$data  = json_decode( $body );
				$items = $data->data;

				$order_item = array(
					'order_id'     => $items[0]->attributes->order_id,
					'product_id'   => $items[0]->attributes->product_id,
					'variant_id'   => $items[0]->attributes->variant_id,
					'product_name' => $items[0]->attributes->product_name,
					'variant_name' => $items[0]->attributes->variant_name,
					'price'        => $items[0]->attributes->price,
				);

				return $order_item;

			} else {
				$error_message = wp_remote_retrieve_response_message( $response );
			}
		} else {
			$error_message = $response->get_error_message();
		}
	}
}
