<?php

namespace lsc;

/**
 * Class to handle user meta.
 */
class User_Meta {
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
		add_action( 'show_user_profile', array( $this, 'register_meta_fields' ) );
		add_action( 'edit_user_profile', array( $this, 'register_meta_fields' ) );
		add_action( 'personal_options_update', array( $this, 'save_meta' ) );
		add_action( 'edit_user_profile_update', array( $this, 'save_meta' ) );
	}


	/**
	 * Output settings form.
	 *
	 * @param object $user current user object.
	 * @return void
	 */
	public function register_meta_fields( $user ) {

		$purchase_date    = get_user_meta( $user->ID, 'purchase-date', true );
		$purchase_product = get_user_meta( $user->ID, 'purchase-product', true );

		?>
		<h3><?php esc_html_e( 'Lemon Squeezy Data', 'ls-connector' ); ?></h3>
		<p>
		<label for="purchase-date"><?php esc_html_e( 'Purchase date', 'ls-connector' ); ?></label>
		<input type="date" name="purchase-date" id="purchase-date" value="<?php echo esc_html( $purchase_date ); ?>" />
		</p>

		<p>
		<label for="purchase-product"><?php esc_html_e( 'Product', 'ls-connector' ); ?></label>
		<input type="text" name="purchase-product" id="purchase-product" value="<?php echo esc_html( $purchase_product ); ?>" />
		</p>
		<?php
	}


	/**
	 * Save user meta.
	 *
	 * @param  int $user_id given user id.
	 * @return void
	 */
	public function save_meta( $user_id ) {
		if ( ! empty( $_POST['purchase-date'] ) ) {
			update_user_meta( $user_id, 'purchase-date', sanitize_text_field( $_POST['purchase-date'] ) );
		}

		if ( ! empty( $_POST['purchase-product'] ) ) {
			update_user_meta( $user_id, 'purchase-product', sanitize_text_field( $_POST['purchase-product'] ) );
		}
	}
}
