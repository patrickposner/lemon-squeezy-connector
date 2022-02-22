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
		// Validate it's coming from Lemon Squeezy.
		$secret    = 'ls_order_changed'; // add webhook secret.
		$payload   = file_get_contents( 'php://input' );
		$hash      = hash_hmac( 'sha256', $payload, $secret );
		$signature = $_SERVER['HTTP_X_SIGNATURE'] ?? null;

		if ( $hash !== $signature ) {
			return;
		}

		$data = json_decode( $payload );

		$customer = array(
			'event'      => $data->meta->event_name,
			'full_name'  => $data->data->attributes->user_name,
			'user_email' => $data->data->attributes->user_email,
			'plan'       => $data->data->attributes->product_name,
			'variant'    => $data->data->attributes->variant_name,
			'status'     => $data->data->attributes->status,
			'created_at' => $data->data->attributes->created_at,
			'ends_at'    => $data->data->attributes->ends_at,
		);

		$users_exists = email_exists( $customer['user_email'] );

		if ( $users_exists ) {
			// Only update metadata.
			update_user_meta( $user_id, 'purchase-date', sanitize_text_field( $customer['created_at'] ) );
			update_user_meta( $user_id, 'purchase-product', sanitize_text_field( $customer['variant'] ) );
		} else {
			// Check that a new order was created.
			if ( 'order_created' === $customer['event'] ) {
				$password = wp_generate_password( 12, true );
				$user_id  = wp_create_user( $customer['full_name'], $password, $customer['user_email'] );

				// Update meta data.
				update_user_meta( $user_id, 'purchase-date', sanitize_text_field( $customer['created_at'] ) );
				update_user_meta( $user_id, 'purchase-product', sanitize_text_field( $customer['variant'] ) );

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
}
