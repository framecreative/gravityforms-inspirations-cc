<?php

GFForms::include_addon_framework();

class GFInspirationsCc extends GFAddOn {

	protected $_version = GF_INSPIRATIONS_CC_VERSION;
	protected $_min_gravityforms_version = '2.0';
	protected $_slug = 'inspirations-cc';
	protected $_path = 'gravityforms-inspirations-cc/inspirations-cc.php';
	protected $_full_path = __FILE__;
	protected $_title = 'Gravity Forms Inspirations CC Processing';
	protected $_short_title = 'Inspirations CC';

	protected $_inMemoryCreditCard = null;
	protected $_inMemoryCcv = null;

	protected $_dummyCcv = 'XXX';
	protected $_dummyCreditCardNumber = "XXXX-XXXX-XXXX-";
	protected $_notificationToTarget = '';

	private static $_instance = null;

	/**
	 * Get an instance of this class.
	 *
	 * @return GFInspirationsCc
	 */
	public static function get_instance() {
		if ( self::$_instance == null ) {
			self::$_instance = new GFInspirationsCc();
		}

		return self::$_instance;
	}

	/**
	 * Handles hooks and loading of language files.
	 */
	public function init() {
		parent::init();
		add_action( 'gform_pre_submission', array( $this, 'pre_submission' ), 5 );
		add_filter( 'gform_enable_credit_card_field', '__return_true', 11 );
	}



	// # ADMIN FUNCTIONS -----------------------------------------------------------------------------------------------


	/**
	 * Configures the settings which should be rendered on the Form Settings > Simple Add-On tab.
	 *
	 * @return array
	 */
	public function form_settings_fields( $form ) {
		return array(
			array(
				'title'  => esc_html__( 'CC Card submission info', 'simpleaddon' ),
				'fields' => array(
					array(
						'label'   => esc_html__( 'Enable CC Add-on for this form', 'simpleaddon' ),
						'type'    => 'checkbox',
						'name'    => 'enabled',
						'choices' => array(
							array(
								'label' => esc_html__( 'Enabled', 'simpleaddon' ),
								'name'  => 'enabled',
							),
						),
					),
					array(
						'label'   => esc_html__( 'Notification to attach information to', 'simpleaddon' ),
						'type'    => 'select',
						'name'    => 'notificationToAttach',
						'tooltip' => esc_html__( 'This (and only this) notification will have the credit card data appended to it when a form is submitted', 'simpleaddon' ),
						'choices' => $this->return_notifications_as_select_array( $form )
					),
					array(
						'name'      => 'mappedFields',
						'label'     => esc_html__( 'Form Fields', 'simplefeedaddon' ),
						'type'      => 'field_map',
						'field_map' => array(
							array(
								'name'       => 'submittedCc',
								'label'      => esc_html__( 'Actual Credit Card Field', 'simplefeedaddon' ),
								'required'   => 0,
								'field_type' => array( 'creditcard' ),
							),
							array(
								'name'       => 'hiddenCcNumber',
								'label'      => esc_html__( 'Hidden Partial CC Number Field', 'simplefeedaddon' ),
								'required'   => 0,
								'field_type' => array( 'hidden' ),
							),
							array(
								'name'       => 'hiddenCcName',
								'label'      => esc_html__( 'Hidden CC Name Field', 'simplefeedaddon' ),
								'required'   => 0,
								'field_type' => array( 'hidden' ),
							),
							array(
								'name'       => 'hiddenCcExpiry',
								'label'      => esc_html__( 'Hidden CC Expiry Field', 'simplefeedaddon' ),
								'required'   => 0,
								'field_type' => array( 'hidden' ),
							),
						),
					),
				),

			),
		);
	}

	public function return_notifications_as_select_array( Array $form ){
		$notifications = [];

		foreach ( $form['notifications'] as $notification_id => $notification_settings ) {
			$notifications[] = [
				'label' => esc_html( $notification_settings['name'] ),
				'value' => $notification_id
			];
		}

		return $notifications;

	}


	/**
	 * Performing a custom action at the end of the form submission process.
	 *
	 * @param array $form The form currently being processed.
	 */
	public function pre_submission( $form ) {

		if ( ! array_key_exists( 'inspirations-cc', $form) || $form['inspirations-cc']['enabled'] === "0" ) {
			return $form;
		}

		$settings = $form['inspirations-cc'];

		$hidden_credit_card_field_name = 'input_' . $settings[ 'mappedFields_hiddenCcNumber' ];

		$hidden_customer_field_name = 'input_' . $settings[ 'mappedFields_hiddenCcName' ];

		$hidden_expiry_field_name = 'input_' . $settings[ 'mappedFields_hiddenCcExpiry' ];

		$credit_card_field_id = $this->get_parent_field_id_from_child( $settings[ 'mappedFields_submittedCc' ] );

		// kinda 'magic number' but the sub fields ID's are set by Gravity Forms, and always follow the same format
		$credit_card_field_name = 'input_' . $credit_card_field_id . '_1';

		$expiry_date_field_name = 'input_' . $credit_card_field_id . '_2';

		$ccv_field_name = 'input_' . $credit_card_field_id . '_3';

		$credit_card_customer_name = 'input_' . $credit_card_field_id . '_5';

		// We save them to this object so they are available for the rest of this request

        $this->_inMemoryCreditCard = rgpost( $credit_card_field_name );

		$this->_inMemoryCcv = rgpost( $ccv_field_name );

		// Fill out the hidden fields
        $_POST[ $hidden_customer_field_name ] = rgpost( $credit_card_customer_name );
        $_POST[ $hidden_expiry_field_name ] = implode( '/', rgpost( $expiry_date_field_name ) );

		// The Notification ID is needed later by the notification filter
		$this->_notificationToTarget = $settings['notificationToAttach'];

		// Hook in the notification filter
		add_filter( 'gform_notification', array( $this, 'append_details_to_email'), 10, 3 );

		return $form; // return the updated form

	}

	public function append_details_to_email( $notification, $form, $entry ){

		if ( $notification['id'] !== $this->_notificationToTarget ) {
			return $notification;
		}

		$notification['message'] .= PHP_EOL . 'Order ID: '. $entry['id'] . ' | Card Number: ' . $this->_inMemoryCreditCard .  ' | CCV: ' . $this->_inMemoryCcv;

		return $notification;

	}


	// # HELPERS -------------------------------------------------------------------------------------------------------

	/**
	 * The feedback callback for the 'mytextbox' setting on the plugin settings page and the 'mytext' setting on the form settings page.
	 *
	 * @param string $value The setting value.
	 *
	 * @return bool
	 */
	public function is_valid_setting( $value ) {
		return strlen( $value ) < 10;
	}

	public function get_parent_field_id_from_child( $childId = '' ) {
		return explode('.', $childId )[0];
	}

}
