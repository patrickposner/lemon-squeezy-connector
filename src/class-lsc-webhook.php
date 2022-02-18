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

		// Check if credentials are set.
		if ( ! defined( 'LS_SECRET' ) && false === LS_SECRET ) {
			return;
		}

		// Validate it's coming from Lemon Squeezy.
		$secret    = LS_SECRET;
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
			if ( 'subscription_cancelled' === $customer['event'] ) {
				error_log( 'Subscription for user with E-Mail ' . $customer['user_email'] . ' was cancelled' );
			} elseif ( 'subscription_created' === $customer['event'] ) {
				error_log( 'Subscription for user with E-Mail ' . $customer['user_email'] . ' was created' );
			} elseif ( 'subscription_updated' === $customer['event'] ) {
				error_log( 'Subscription for user with E-Mail ' . $customer['user_email'] . ' was updated' );
			}

			// Update and add meta field with number of websites (and currently used ones).
		} else {
			$password = wp_generate_password( 12, true );
			$user_id  = wp_create_user( $customer['full_name'], $password, $customer['user_email'] );

			// Set customer role.
			$user = new WP_User( $user_id );
			$user->set_role( 'customer' );

			// Update and add meta field with number of websites (and currently used ones).

			// Send e-mail to the user.
			wp_new_user_notification( $user_id, null, 'both' );
		}
	}
}
