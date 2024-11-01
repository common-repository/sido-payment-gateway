<?php
/*
  Plugin Name: SIDO Payment Gateway
  Plugin URI: http://sido.net/
  Description: SIDO payment gateway plugin for WooCommerce
  Author: BNC Holdings
  Author URI:  http://bncholdings.net/
  Version: 1.30.03
 * @class      WC_Gateway_SIDO
 * @extends        WC_Payment_Gateway
 * @version        1.30.03
 * @package        WooCommerce/Classes/Payment
 * @author         BNC KK
 *
 * SIDO payment gateway
 * Power your WooCommerce site with the SIDO payment gateway
 * Copyright (C) 2013 BNC Holdings
 *
 * This program is free software: you can redistribute it and/or modify it under
 * the terms of the GNU General Public License as published by the Free Software
 * Foundation, either version 2 of the License,
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of  MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with
 * this program.  If not, see <http://www.gnu.org/licenses/>.
 */

if ( !defined( 'ABSPATH' ) )
	exit; // Exit if accessed directly



/* * ****************************************
 *        Initialisation section          *
 * *************************************** */
add_action( 'plugins_loaded', 'sido_load_gateway', 0 );

function sido_load_gateway() {


	if ( !class_exists( 'WC_Payment_Gateway' ) ) {
		return;
	}


	add_filter( 'woocommerce_payment_gateways', 'sido_add_gateway' );

	function sido_add_gateway( $methods ) {
		$methods[] = 'WC_Gateway_SIDO';
		return $methods;
	}

	class WC_Gateway_SIDO extends WC_Payment_Gateway {

		/**
		 * Gateway constructor
		 */
		public function __construct() {

			$this->id = 'SIDO';
			$this->icon = plugins_url( 'images/sido.png', __FILE__ );
			$this->has_fields = false;
			$this->has_settings = true;
			$this->method_title = "SIDO";
			$this->method_description = "SIDO works by sending the user to the SIDO checkout page to enter their payment information.";

			// Load the form fields.
			$this->init_form_fields();

			// Load the settings.
			$this->init_settings();

			// Define user setting variables.
			$this->title = $this->settings['title'];
			$this->description = $this->settings['description'];
			$this->enabled = $this->settings['enabled'];
			$this->enabled_subscription = $this->settings['enabled_subscription'];

			// Subscriptions support
			if ( $this->enabled_subscription == 'yes' ) {
				$this->supports = array( 'subscriptions', 'products', 'gateway_scheduled_payments' );
			} else {
				$this->supports = array( 'products' );
			}


			// Version 2.0 Hook
			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		}

		/**
		 * Administration
		 */
		/**
		 * Admin Panel Options
		 */

		/**
		 * Initialise Gateway Settings Form Fields
		 *
		 * @access public
		 * @return void
		 */
		public function init_form_fields() {
			$this->form_fields = array(
				'enabled' => array(
					'title' => __( 'Enable/Disable SIDO', 'sido' ),
					'type' => 'checkbox',
					'label' => __( 'Enabled', 'sido' ),
					'default' => 'yes'
				),
				'default_gateway' => array(
					'title' => __( 'Make SIDO the default gateway', 'sido' ),
					'type' => 'checkbox',
					'label' => __( 'Default', 'sido' ),
					'default' => 'yes'
				),
				'enabled_subscription' => array(
					'title' => __( 'Enable SIDO for subscriptions', 'sido' ),
					'type' => 'checkbox',
					'label' => __( 'Enabled', 'sido' ),
					'default' => 'yes'
				),
				'title' => array(
					'title' => __( 'Title', 'sido' ),
					'type' => 'text',
					'description' => __( 'This controls the title which the user sees during checkout.', 'sido' ),
					'default' => __( 'SIDO', 'sido' ),
					'desc_tip' => true,
				),
				'description' => array(
					'title' => __( 'Description', 'sido' ),
					'type' => 'textarea',
					'default' => __( 'Pay securely with SIDO.', 'sido' )
				),
				'sido_order_url' => array(
					'title' => __( 'SIDO Order URL', 'sido' ),
					'type' => 'text',
					'description' => __( 'Please enter the SIDO Order checkout url.', 'sido' ),
					'default' => __( 'https://gw.sido.net/sido-mg/api/order', 'sido' )
				),
				'sido_subscription_url' => array(
					'title' => __( 'SIDO Subscription URL', 'sido' ),
					'type' => 'text',
					'description' => __( 'Please enter the SIDO Subscription checkout url.', 'sido' ),
					'default' => __( 'https://gw.sido.net/sido-mg/api/order/subscription', 'sido' )
				),
				'default_language' => array(
					'title' => __( 'Default Language', 'sido' ),
					'type' => 'text',
					'description' => __( 'Enter the default checkout locale if WPML is not used, ja_JP and en_US are supported.', 'sido' ),
					'default' => __( 'en_US', 'sido' )
				),
				'merchant_email' => array(
					'title' => __( 'Merchant Email', 'sido' ),
					'type' => 'email',
					'description' => __( 'Please enter your merchant\'s email registered to SIDO.', 'sido' ),
					'placeholder' => 'merchant@sido.net'
				),
				'api_key' => array(
					'title' => __( 'Secret API Key', 'sido' ),
					'type' => 'text',
					'description' => __( 'Please enter your Secret API Key, from the SIDO Portal under your Profile', 'sido' ),
					'placeholder' => ''
				),
				'required_email' => array(
					'title' => __( 'Require Email', 'sido' ),
					'type' => 'checkbox',
					'label' => __( 'Required', 'sido' ),
					'default' => 'yes',
					'description' => __( 'Requires the user to enter his email address.', 'sido' )
				),
				'required_phone_number' => array(
					'title' => __( 'Require Phone Number', 'sido' ),
					'type' => 'checkbox',
					'label' => __( 'Required', 'sido' ),
					'default' => 'no',
					'description' => __( 'Requires the user to enter his phone number.', 'sido' )
				),
				'required_date_of_birth' => array(
					'title' => __( 'Require Date of Birth', 'sido' ),
					'type' => 'checkbox',
					'label' => __( 'Required', 'sido' ),
					'default' => 'no',
					'description' => __( 'Requires the user to enter his date of birth.', 'sido' ),
				),
				'required_legal_age' => array(
					'title' => __( 'Require Legal Age', 'sido' ),
					'type' => 'checkbox',
					'label' => __( 'Required', 'sido' ),
					'default' => 'no',
					'description' => __( 'Requires the user to to be of legal age.', 'sido' ),
				)
			);
		}

		/**
		 * Process payment
		 */
		function sido_get_args( $order ) {
			global $woocommerce;

			$order_id = $order->get_order_number();

			// Set language appropriately
			$locale = "ja_JP";
			$locale = ( isset( $this->settings['default_language'] ) ? $this->settings['default_language'] : $locale );
			$locale = ( defined( 'ICL_LANGUAGE_CODE' ) && ICL_LANGUAGE_CODE == "en" ? "en_US" : $locale );
			$locale = ( defined( 'ICL_LANGUAGE_CODE' ) && ICL_LANGUAGE_CODE == "ja" ? "ja_JP" : $locale );

			// SIDO Args
			$sido_args = array(
				'currencyCode' => get_woocommerce_currency(),
				'returnUrl' => $this->get_return_url( $order ),
				'editUrl' => $woocommerce->cart->get_cart_url(), // $order->get_checkout_payment_url(),
				'merchantEmail' => $this->settings['merchant_email'],
				'apiKey' => $this->settings['api_key'],
				'locale' => $locale,
				'requiredEmail' => ( $this->settings['required_email'] == "yes" ? true : false ),
				'requiredShippingAddress' => order_needs_shipping( $order ),
				'requiredDateOfBirth' => ( $this->settings['required_date_of_birth'] == "yes" ? true : false ),
				'requiredPaymentMethod' => true,
				'requiredPhoneNumber' => ( $this->settings['required_phone_number'] == "yes" ? true : false ),
				'requiredLegalAge' => ( $this->settings['required_legal_age'] == "yes" ? true : false ),
				'orderItems' => array(),
			);

			$item_key = "orderItems";
			// Subscriptions Support section
			$registrationFee = 0;
			if ( class_exists( 'WC_Subscriptions_Order' ) ) {
				// If it contains a subscription, then add the subscription fields
				if ( WC_Subscriptions_Order::order_contains_subscription( $order->id ) ) {
					$item_key = "subscriptionItems";
					switch ( WC_Subscriptions_Order::get_subscription_period( $order ) ) {
						case 'minute':
							$sido_args['periodType'] = 'QA_SCHEDULE_2';
							break;
						case 'day':
							$sido_args['periodType'] = 'DAILY';
							break;
						case 'week':
							$sido_args['periodType'] = 'WEEKLY';
							break;
						case 'year':
							$sido_args['periodType'] = 'YEARLY';
							break;
						case 'month':
						default:
							$sido_args['periodType'] = 'MONTHLY';
							break;
					}

					$sido_args['registrationFee'] = WC_Subscriptions_Order::get_sign_up_fee( $order );
					$registrationFee = $sido_args['registrationFee'];
					$sido_args['interval'] = WC_Subscriptions_Order::get_subscription_interval( $order );
					if ( 1 <= WC_Subscriptions_Order::get_subscription_length( $order ) && 1 <= WC_Subscriptions_Order::get_subscription_interval( $order ) ) {
						sido_debug( "sub length=" . WC_Subscriptions_Order::get_subscription_length( $order ) );
						sido_debug( "sub int=" . WC_Subscriptions_Order::get_subscription_interval( $order ) );
						$sido_args['totalIssues'] = WC_Subscriptions_Order::get_subscription_length( $order ) /
								WC_Subscriptions_Order::get_subscription_interval( $order );
					}
				}
               //Check if order has free trial period

                $trialPeriod = WC_Subscriptions_Order::get_subscription_trial_length( $order );

                if($trialPeriod > 0){
                    $sido_args['freeFirstIssue'] = true;

                }

			}

			// Cart Contents
			$item_loop = 0;

			if ( sizeof( $order->get_items() ) > 0 ) {
				foreach ( $order->get_items() as $index => $item ) {

					if($item_key=="subscriptionItems"){
						$wcProduct = new WC_Product( $item['product_id']);
						$amount = $wcProduct->get_price();
					} else {
						$amount = $order->get_item_total( $item ) - $registrationFee;
					}

					if ( $item['qty'] ) {
						$sido_args[$item_key][] = array(
							'itemCode' => $index,
							'itemName' => str_replace( '&#8211;', '-', $item['name'] ),
							'quantity' => $item['qty'],
							'amount' => $amount,
						);
					}
				}
			}

			return $sido_args;
		}

		/**
		 * Process the payment and return the result
		 *
		 * @param int $order_id
		 * @return array
		 */
		public function process_payment( $order_id ) {
			global $woocommerce;

			$order = new WC_Order( $order_id );

			$sido_args = $this->sido_get_args( $order );

			$order_json = json_encode( $sido_args );
			sido_debug( "Order: $order_json" );

			$url = $this->settings['sido_order_url'];
			if ( class_exists( 'WC_Subscriptions_Order' ) && ( WC_Subscriptions_Order::order_contains_subscription( $order->id ) ) ) {
				$url = $this->settings['sido_subscription_url'];
			}
			sido_debug( "SIDO Gateway: Order API URL set to " . $url );

			// Using wp_remote_post
			$response = wp_remote_post(
					$url, array(
				'method' => 'POST',
				'timeout' => 45,
				'redirection' => 1,
				'httpversion' => '1.0',
				'blocking' => true,
				'headers' => array( 'Content-Type' => 'application/json' ),
				'body' => $order_json,
				'cookies' => array(),
				'sslverify' => false, // Enable for testing only
					)
			);
			if ( is_wp_error( $response ) ) {
				$woocommerce->add_error( __( 'Payment error:', 'woothemes' ) . "Unable to process order" );
				return;
			} else {
				$body = wp_remote_retrieve_body( $response );
				$response_data = json_decode( $body, true );
				$redirect_url = $response_data['redirectUrl'];

				// Handle an invalid or empty redirect


				$ref = $response_data['referenceId'];

				// Subscriptions Support section
				if ( class_exists( 'WC_Subscriptions_Order' ) && ( WC_Subscriptions_Order::order_contains_subscription( $order->id ) ) ) {
					set_subscription_reference( $order, $ref );
				} else {
					set_tx_reference( $order, $ref );
				}
				sido_debug( "SIDO Gateway: Order API redirected to: " . $redirect_url );
				return array(
					'result' => 'success',
					'redirect' => $redirect_url
				);
			}
		}

	}

}

function set_tx_reference( $order, $ref ) {
	$result = update_post_meta( $order->id, '_sido_tx_reference', $ref );
	sido_debug( "SIDO GATEWAY: set_tx_reference: set ref for order " . $order->id . ", should be $ref, result was $result, is " . get_post_meta( $order->id, '_sido_tx_reference', true ) );
}

// This sets the Subscription Reference meta on the first Order that contains the Subscription items
function set_subscription_reference( $order, $ref ) {
	$result = update_post_meta( $order->id, '_sido_subscription_reference', $ref );
	sido_debug(
			"SIDO GATEWAY: set_subscription_reference: set ref for sub " . $order->id . " with result $result " .
			", should be $ref, is " .
			get_post_meta( $order->id, '_sido_subscription_reference', true )
	);
}

// Returns whether SIDO has been selected as the payment method or not
function is_selected() {
	global $woocommerce;
	return "SIDO" == $woocommerce->session->chosen_payment_method ? true : false;
}

// Override the NeedsShipping function to be false if SIDO is selected as Payment Method
//add_filter( 'woocommerce_cart_needs_shipping', 'sido_needs_shipping', 10, 1 );
//add_filter( 'woocommerce_cart_ready_to_calc_shipping', 'sido_needs_shipping', 10, 1 );

function sido_needs_shipping( $needs_shipping ) {
	if ( is_selected() ) {
		return false;
	} else {
		return $needs_shipping;
	}
}

// Check if the Order will require a shipping address
function order_needs_shipping( $order ) {
	foreach ( $order->get_items() as $item ) {
		$_product = $order->get_product_from_item( $item );
		if ( $_product->needs_shipping() ) {
			return true;
		}
	}
	return false;
}

add_filter( 'wp_footer', 'sido_update_checkout', 8, 1 );

/**
 * Hook a jQuery action to show/hide fields if SIDO payment method is selected
 */
function sido_update_checkout() {
	?>
	<script>
		jQuery(window).load(function($) {
			//woocommerce_enable_guest_checkout
			//Handle woocommerce_enable_signup_and_login_from_checkout setting
			//woocommerce_registration_email_for_username
			//woocommerce_registration_generate_password
			//


			//Jquery check if payment method = SIDO
			<?php
				echo "var woocommerce_enable_guest_checkout = '". get_option('woocommerce_enable_guest_checkout') . "';";
				echo "\nvar woocommerce_enable_signup_and_login_from_checkout = '". get_option('woocommerce_enable_signup_and_login_from_checkout') . "';";
				echo "\nvar woocommerce_registration_generate_username = '" . get_option("woocommerce_registration_generate_username") . "';";
				echo "\nvar woocommerce_registration_generate_password = '" . get_option("woocommerce_registration_generate_password") . "';";
			?>

			/**
			 * Javascript function for hiding uneeded elements
			 * @return {[type]} [description]
			 */
			function showHide(){
				if (jQuery("input[name='payment_method']:checked").attr('id') === "payment_method_SIDO") {
					jQuery(".col-2").hide();
					//PHP check for the variables

					//IF guest checkout is enabled
					if(woocommerce_enable_guest_checkout=='yes'){
						//IF register from checkout page is enabled
						if(woocommerce_enable_signup_and_login_from_checkout=='yes'){
							//Show register options hide other details`
							jQuery(".woocommerce-billing-fields").children().not(".create-account").attr("style","display: none;");;

							if(woocommerce_registration_generate_username=='yes'){

								if(woocommerce_registration_generate_password=='yes'){
									//Only show email

								var html = jQuery("<div class='create-account' style='display: none;'><p class='form-row woocommerce-validated' id='billing_email_field2'><label for='billing_email' class=''>Account email <abbr class='required' title='required'>*</abbr></label><input type='text' class='input-text' name='billing_email' id='billing_email' placeholder='Email' value=''></p></div>");


								jQuery('p.create-account').after().append(html);



								} else {
									//woocommerce_registration_generate_password == no
									//email + password

									var html = jQuery("<p class='form-row woocommerce-validated' id='billing_email_field2'><label for='billing_email' class=''>Account email <abbr class='required' title='required'>*</abbr></label><input type='text' class='input-text' name='billing_email' id='billing_email' placeholder='Email' value=''></p>");

									jQuery('div.create-account').append(html);


								}
							} else {
								//If woocommerce_registration_generate_username == no
								//Account username + email
									if(get_option('woocommerce_registration_generate_password')=='yes'){



								var html = jQuery("<p class='form-row woocommerce-validated' id='billing_email_field2'><label for='billing_email' class=''>Account email <abbr class='required' title='required'>*</abbr></label><input type='text' class='input-text' name='billing_email' id='billing_email' placeholder='Email' value=''></p>");

									jQuery('div.create-account').append(html);


								}
							}


						} else {
							//Hide register options
							jQuery("#customer_details").hide();
							return;
						}

					} else {

					}

				} else {
					jQuery(".woocommerce-billing-fields").children().not(".create-account").attr("style","display: block;");
					jQuery(".col-2").show();
					jQuery("#billing_email_field2").remove();
				}
			}

			showHide();

		jQuery('.payment_methods input.input-radio').live('change', function() {

			showHide();

			jQuery("body").trigger("update_checkout");
			jQuery(".woocommerce-error").hide();

			});

		});



	</script>
<?php

}

// Mark the billing Fields as not required if SIDO is selected as the payment method

add_filter( 'woocommerce_billing_fields', 'sido_override_billing_fields', 9, 1 );

function sido_override_billing_fields( $fields ) {
	global $woocommerce;



	if ( is_selected() ) {

		$fields['billing_country']['required'] = false;
		$fields['billing_first_name']['required'] = false;
		$fields['billing_last_name']['required'] = false;
		$fields['billing_company']['required'] = false;
		$fields['billing_address_1']['required'] = false;
		$fields['billing_address_2']['required'] = false;
		$fields['billing_city']['required'] = false;
		$fields['billing_state']['required'] = false;
		$fields['billing_postcode']['required'] = false;
		$fields['billing_phone']['required'] = false;

		if ( get_option( 'woocommerce_registration_email_for_username' ) == 'no' || get_option( 'woocommerce_enable_guest_checkout' ) == 'yes' ) {
			$fields['billing_email']['required'] = false;
		} else {
			$fields['billing_email']['required'] = true;
		}
	}


	return $fields;
}

function register_session() {
	if ( !session_id() )
		session_start();
}

add_action( 'init', 'register_session' );



// Mark the Shipping Fields as not required if SIDO is selected as the payment method
add_filter( 'woocommerce_shipping_fields', 'sido_override_shipping_fields', 9, 1 );

function sido_override_shipping_fields( $fields ) {
	if ( is_selected() ) {

		$fields['shipping_country']['required'] = false;
		$fields['shipping_first_name']['required'] = false;
		$fields['shipping_last_name']['required'] = false;
		$fields['shipping_company']['required'] = false;
		$fields['shipping_address_1']['required'] = false;
		$fields['shipping_address_2']['required'] = false;
		$fields['shipping_city']['required'] = false;
		$fields['shipping_state']['required'] = false;
		$fields['shipping_postcode']['required'] = false;
	}
	return $fields;
}

// Output only if Debug is enabled
function sido_debug( $str ) {
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG == true )
		error_log( $str );
}

/**
 * Checks if the cart has an item that has subscription to set if the field to be updated on accounts would be required
 * @return {bool}
 */
function sido_checkRequired() {
	if ( is_user_logged_in() ) {
		return false;
	}
	if ( class_exists( 'WC_Subscriptions_Cart' ) ) {
		if ( WC_Subscriptions_Cart::cart_contains_subscription() >= 1 ) {
			return true;
		} else {
			//This function might be useful
			//if(get_option('woocommerce_enable_signup_and_login_from_checkout')=='yes'){
			return false;
		}
	} else {
		return false;
	}
}

// @TODO: Override account field
//add_filter( 'woocommerce_checkout_fields', 'sido_overide_account_field' );

/**
 * Over ride account fields if SIDO payment gateway was selected,this overrides the AJAX response
 * @param array $fields Fields that are to be overridden
 * @return string HTML String
 */
function sido_overide_account_field( $fields ) {
	if ( is_selected() ) {
		$required = sido_checkRequired();

		//Cannot delete billing_country,some JS events are hooked there

		$fields['account']['account_password']['required'] = $required;
//		$fields['account']['account_password-2']['required'] = $required;
	}

	return $fields;
}

// If the http_response_code function is not provided, use this implementation
if ( !function_exists( 'http_response_code' ) ) {

	function http_response_code( $code = NULL ) {

		if ( $code !== NULL ) {

			switch ( $code ) {
				case 100: $text = 'Continue';
					break;
				case 101: $text = 'Switching Protocols';
					break;
				case 200: $text = 'OK';
					break;
				case 201: $text = 'Created';
					break;
				case 202: $text = 'Accepted';
					break;
				case 203: $text = 'Non-Authoritative Information';
					break;
				case 204: $text = 'No Content';
					break;
				case 205: $text = 'Reset Content';
					break;
				case 206: $text = 'Partial Content';
					break;
				case 300: $text = 'Multiple Choices';
					break;
				case 301: $text = 'Moved Permanently';
					break;
				case 302: $text = 'Moved Temporarily';
					break;
				case 303: $text = 'See Other';
					break;
				case 304: $text = 'Not Modified';
					break;
				case 305: $text = 'Use Proxy';
					break;
				case 400: $text = 'Bad Request';
					break;
				case 401: $text = 'Unauthorized';
					break;
				case 402: $text = 'Payment Required';
					break;
				case 403: $text = 'Forbidden';
					break;
				case 404: $text = 'Not Found';
					break;
				case 405: $text = 'Method Not Allowed';
					break;
				case 406: $text = 'Not Acceptable';
					break;
				case 407: $text = 'Proxy Authentication Required';
					break;
				case 408: $text = 'Request Time-out';
					break;
				case 409: $text = 'Conflict';
					break;
				case 410: $text = 'Gone';
					break;
				case 411: $text = 'Length Required';
					break;
				case 412: $text = 'Precondition Failed';
					break;
				case 413: $text = 'Request Entity Too Large';
					break;
				case 414: $text = 'Request-URI Too Large';
					break;
				case 415: $text = 'Unsupported Media Type';
					break;
				case 500: $text = 'Internal Server Error';
					break;
				case 501: $text = 'Not Implemented';
					break;
				case 502: $text = 'Bad Gateway';
					break;
				case 503: $text = 'Service Unavailable';
					break;
				case 504: $text = 'Gateway Time-out';
					break;
				case 505: $text = 'HTTP Version not supported';
					break;
				default:
					exit( 'Unknown http status code "' . html_entity_decode( $code ) . '"' );
					break;
			}
			//if protocol is set use it else use http/1
			$protocol = (isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0');

			header( $protocol . ' ' . $code . ' ' . $text );

			$GLOBALS['http_response_code'] = $code;
		} else {
			//if http_response_code is set use it, else use 200
			$code = (isset( $GLOBALS['http_response_code'] ) ? $GLOBALS['http_response_code'] : 200);
		}

		return $code;
	}

}

// Logs and ignores all reported errors so they don't interfere with JSON response
// only to be used in the sido_api function
function sido_error_handler( $errno, $errstr, $errfile, $errline ) {
	// Don't log if the error is excluded from the current error_reporting level
	if ( !( $errno & error_reporting() ) )
		return true;

	// Log the error and continue
	sido_debug( "Error [$errno] $errstr on line $errline in file $errfile" );
	return true;
}

//Create database upon plugin activation\
register_activation_hook( __FILE__, 'sido_init_db' );


global $wpdb;
$table_name = $wpdb->prefix . 'sido_orders';

/**
 * Function that is called upon the plugin activation
 * @return void creates a table if the table doesn't exist
 */
function sido_init_db() {
	global $wpdb;
	global $table_name;

	if ( false == sido_table_check( $table_name ) ) {
		sido_create_db( $table_name );
	}
}

/**
 * Function that creates the table
 * @param  str $table_name string of the table name to be created
 * @return void
 */
function sido_create_db( $table_name ) {
	global $wpdb;

	$sql = "CREATE TABLE " . $table_name . " (
        `id` int NOT NULL AUTO_INCREMENT,
        `reference` varchar(45) NOT NULL,
        `datetimeID` timestamp NOT NULL,
        UNIQUE KEY id (id)
        );";

	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	dbDelta( $sql );
}

/**
 * checks if the table exists
 * @param  str $table_name table name
 * @return bool             returns true if the table exist else false
 */
function sido_table_check( $table_name ) {
	global $wpdb;
	if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) != $table_name ) {
		return false;
	} else {
		return true;
	}
}

/**
 * Insert reference
 * @param  str $table_name name of the table
 * @param  str $reference  reference ID returned by the server
 * @return bool             true if successful false if not
 */
function sido_insert_reference( $reference ) {
	global $wpdb;
	global $table_name;

	// $time = current_time('mysql');
	date_default_timezone_set( 'Asia/Manila' );
	$time = date( 'Y-m-d H:i:s' );
	if ( $wpdb->insert(
					$table_name, array(
				"reference" => "$reference",
				"datetimeID" => "$time"
					)
			)
	) {
		return true;
	} else {
		return false;
	}
}

/**
 * get row that has reference = X
 * @param  str $reference reference
 * @return object            results object
 */
function sido_get_reference( $reference ) {
	global $wpdb;
	global $table_name;

	$results = $wpdb->get_results( "SELECT * FROM $table_name where reference = '$reference'" );

	return $results;
}

// @TODO: Calculate the time difference between request to differentiate update calls from multiple instantaneous calls

/**
 * Acquires the last datetime difference from the database and comparing it with current time
 * @param  str $reference reference ID
 * @return object            amount of difference
 */
function sido_time_diff( $reference ) {
	//Runs a query to differentiate time between current call and last call in the database
	global $wpdb;
	global $table_name;

	$datediff = $wpdb->get_row(
			"SELECT TIMESTAMPDIFF(MINUTE,datetimeID,now()) as diff
                                 FROM  $table_name
                                 WHERE reference = '$reference'
                                 Order by datetimeID DESC
                                 LIMIT 1;" );

	sido_debug( $datediff->diff );
	return $datediff->diff;
}

// @TODO: Query the database for the order
// @TODO: PREVENT MULTIPLE CALLS FROM THE JAVA SERVER

/**
 * determines if the incoming data reference has already been inserted to the database to prevent further calls
 * @param  str $reference Reference ID
 * @return [type]            [description]
 */
function sido_check_controller( $reference ) {
	global $wpdb;
	global $table_name;

	//Check if the table does not exists
	if ( !sido_table_check( $table_name ) ) {
		sido_create_db( $table_name );
		sido_debug( 'table created' );
		//Insert row to the database after creating the database
		sido_insert_reference( $table_name, $reference );
		return true;
	} else {
		//table already exist, check database for reference number
		$results = sido_get_reference( $reference );
		if ( $wpdb->num_rows >= 1 ) {
			//Reference has been inserted already at the database check if this request has been simultaenous or not
			if ( intval( sido_time_diff( $reference ) ) < 1 ) {
				//simultaneous request with the same reference kill next calls
				//Probably eats a lot of read requests,but database reads are cheap
				sido_debug( 'Call prevented' );
				sido_debug( intval( sido_time_diff( $reference ) ) );
				return false;
			} else {
				//Not a simultaneous request accept and insert data
				sido_insert_reference( $table_name, $reference );
				sido_debug( 'Not a simultaneous request accept and insert data' );
				return true;
			}
		} else {
			//Reference has not been insert at the database insert the reference number and continue
			sido_insert_reference( $table_name, $reference );
			return true;
		}
	}
}

// SIDO Merchant Push API
// Reads in the JSON message and takes action dependent on the content
add_action( 'woocommerce_api_wc_gateway_sido', 'sido_api', 1, 1 );

function sido_api( $post ) {

	set_error_handler( 'sido_error_handler' );

	global $woocommerce;

	// Obtain the raw POST data
	$postdata = file_get_contents( "php://input" );

	if ( "" == $postdata )
		throw new Exception( "No post data found" );

	sido_debug( "POST data received: " . $postdata );
	// Remove the single quotes at the start and end of the string
	$unescaped = preg_replace( "/(^'|'$)/", "", $postdata );
	// Convert to an object
	$data = json_decode( $unescaped, true );
	if ( array_key_exists( 'items', $data ) ) {
		sido_shipping_calculator( $data );
	} else if ( $data['entity'] ) {

		// Supplementary data that may have been required
		$address = ( array_key_exists( "shippingAddress", $data ) ? $data['shippingAddress'] : null );
		$email = ( array_key_exists( "email", $data ) ? $data['email'] : null );
		$phoneNumber = ( array_key_exists( "phoneNumber", $data ) ? $data['phoneNumber'] : null );
		$shippingFee = ( array_key_exists( "shippingFee", $data ) ? $data['shippingFee'] : null );

		sido_debug( "API: Address: " . print_r( $address, true ) );
		$payment_ref = ( array_key_exists( "paymentReference", $data ) ? $data['paymentReference'] : null );
		switch ( $data['entity'] ) {
			case 'subscription':
				$order = sido_get_order_by_subscription_ref( $data['reference'] );

				switch ( $data['action'] ) {
					// case 'activated':
					// 	sido_subscription_activated( $data['reference'], $order, $address, $email, $phoneNumber );
					// 	break;
					case 'paid':
						sido_subscription_paid( $order, $payment_ref, $address, $email, $phoneNumber, $data['reference'] );
						break;
					case 'cancelled':
						sido_subscription_cancelled( $order );
						break;
					case 'expired':
						sido_subscription_expired( $order );
						break;
					// case 'failed':
					// 	sido_debug( "sub failed: $order->id" );
					// 	sido_subscription_payment_failed( $order );
					// 	break;
					default:
						sido_api_unknown( "Unrecognised subscription action " . $data['action'] . " in " . $postdata );
				}
				break;
			case 'transaction':
				$order = sido_get_order_by_tx_ref( $data['reference'] );

				sido_debug( 'transaction2' );
				switch ( $data['action'] ) {
					case 'paid':
						sido_order_paid( $data['reference'], $order, $address, $email, $phoneNumber );
						break;
					case 'cancelled':
						sido_order_cancelled( $order );
						break;
					case 'refunded':
						sido_order_refunded( $order );
						break;
					default:
						sido_api_unknown( "Unrecognised transaction action " . $data['action'] . " in " . $postdata );
				}
				break;
			default:
				sido_api_unknown( "Unrecognised entity " . $data['entity'] . " in " . $postdata );
		}
		sido_respond( 200, "OK", "Action processed" );
	} else {
		sido_api_unknown( "No entity found in " . $postdata );
	}
}

/**
 * Create the JSON
 * @param int $status
 * @param string $code
 * @param string $message
 */
function sido_respond( $status, $code, $message ) {
	http_response_code( $status );
	$report = array();
	$report["resultCode"] = $code;
	$report["message"] = $message;
	$json_report = json_encode( $report );
	sido_debug( "SIDO API: JSON Response: " . $json_report . "\n" );
	echo $json_report;
	die();
}

/**
 * Response for the unknown JSON
 * @param type $data
 */
function sido_api_unknown( $data ) {
	sido_respond( 501, "UNKNOWN", "Unknown in message: $data" );
}

/**
 * Responds to the gateway for shipping costs as set by the merchant
 * @global object $woocommerce
 * @param object $data
 * @throws Exception
 */
function sido_shipping_calculator( $data ) {

	global $woocommerce;
	sido_debug( "VVVVVVVVVVVV SIDO Shipping Calculator VVVVVVVVVVVV" );
	// Need to do this so cart->calculate_totals will calculate shipping
	define( 'WOOCOMMERCE_CART', true );
	try {
		/*		 * *************************************
		 *   POPULATE THE CART WITH ITEMS      *
		 * ************************************* */
		if ( !array_key_exists( "items", $data ) )
			throw new Exception( "No items found" );

		foreach ( $data['items'] as $item ) {
			sido_debug( "Obtaining item id for " . $item['itemName'] );
			$item_id = sido_get_item_id_from_name( html_entity_decode( $item['itemName'] ) );
			sido_debug( "Obtained item id of " . $item_id . " for " . $item['itemName'] );
			if ( -1 != $item_id ) {
				if ( !$woocommerce->cart->add_to_cart( $item_id, $item['quantity'] ) ) {
					sido_debug( "Failed to add item to cart: item_id=" . $item_id );
				}
			}
		}

		/**		 * ************************************
		 *   SET THE CUSTOMER ADDRESS          *
		 * ************************************* */
		if ( isset( $data['countryCode'] ) )
			$countryCode = $data['countryCode'];
		else
			throw new Exception( "No country found" );

		$state = "";
		if ( isset( $data['state'] ) )
			$state = $data['state'];

		$zipCode = "";
		if ( isset( $data['zipCode'] ) )
			$zipCode = $data['zipCode'];

		$woocommerce->customer->set_shipping_location( $countryCode, $state, $zipCode );
		sido_debug( "Set customer location to " . $countryCode . " " . $state . " " . $zipCode );

		/*		 * *************************************
		 *   CALCULATE SHIPPING COSTS          *
		 * ************************************* */
		$woocommerce->cart->calculate_shipping();

		/*		 * *************************************
		 *   SET THE SHIPPING METHOD           *
		 * ************************************* */
		sido_debug( "Shipping methods BEGIN >>>>\n" );
		if ( method_exists( $woocommerce->shipping, 'get_available_shipping_methods' ) ) {
			$methods = $woocommerce->shipping->get_available_shipping_methods();
		} else {
			$methods = $woocommerce->shipping->get_shipping_methods();
		}
		sido_debug( var_export( $methods, true ) );
		sido_debug( "<<<< END Shipping Methods" );

		$report = array();

		// If no Shipping Method is available, then the destination is not supported
		if ( is_null( $methods ) ) {

			http_response_code( 201 ); // Shipping Engine expects 201 not 200
			$report["resultCode"] = "FAIL-DNS";
			$report["message"] = "No Shipping Method available for destination";
			foreach ( $data['items'] as $item ) {
				$report["message"] = $report["message"] . " item=" . sido_get_item_id_from_name( html_entity_decode( $item['itemName'] ) );
			}
			$report["price"] = -1;
		} else { // At least one Shipping Method is available. Table Rate is the preferred method.
			// Select Table_Rate if it is available, or select the only method if there is only one
			foreach ( $methods as $id => $data ) {
				if ( "table_rate" == $data->method_id || count( $methods ) == 1 ) {
					sido_debug( "Setting shipping method to " . $id );
					$woocommerce->session->chosen_shipping_method = $id;
				}
			}

			$woocommerce->cart->calculate_totals();
			sido_debug( "Calculated shipping as " . $woocommerce->cart->shipping_total );

			/*			 * *************************************
			 *   RETURN CALCULATED SHIPPING COSTS  *
			 * ************************************* */
			http_response_code( 201 ); // Shipping Engine expects 201 not 200
			$report["resultCode"] = "OK";
			$report["message"] = "OK";
			$report["price"] = $woocommerce->cart->shipping_total;
		}
	} catch ( Exception $e ) {
		http_response_code( 200 ); // Shipping Engine expects 201 not 200
		$report["resultCode"] = "OK";
		$report["message"] = $e->getMessage();
		$report["price"] = -1;
	}

	$json_report = json_encode( $report );
	sido_debug( "JSON Response: " . $json_report . "\n" );
	echo $json_report;

	sido_debug( "^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^" );
	die();
}

/**
 * Obtain an Item ID when given its name
 * @global object $wpdb
 * @param  string $name
 * @return ID
 */
/*
  function sido_get_item_id_from_name($name) {

  global $wpdb;
  $line_items = $wpdb->get_results($wpdb->prepare("
  SELECT      id
  FROM        {$wpdb->prefix}posts
  WHERE       post_title = %s
  AND         post_type = 'product'
  AND         post_status IN ( 'inherit', 'publish' )
  LIMIT       1
  ", mysql_real_escape_string($name)));

  foreach ($line_items as $item) {
  return $item->id;
  }
  return -1;
  } */

/**
 * Obtain an Item ID when given its name
 * @global object $wpdb
 * @param  string $name
 * @return ID
 */
function sido_get_item_id_from_name( $name ) {
	global $wpdb;

	$name = preg_replace( '/ \//', '', $name );
	$sql = "SELECT      id
                FROM        {$wpdb->prefix}posts
                WHERE       post_title  =   '{$name}'
                AND         post_type   =   'product'
                AND         post_status IN ('inherit','publish')
                LIMIT       1";

	$sql = stripslashes( $sql );


	sido_debug( 'NAME : ' . $name );
	sido_debug( "SQL  " . $sql );



	$result = mysql_query( $sql );
	while ( $row = mysql_fetch_assoc( $result ) ) {
		return $row['id'];
	}



	return -1;
}

/**
 * Gets subscription by reference
 * @global object $wpdb
 * @param string $reference
 * @return \WC_Order
 */
function sido_get_subscription_by_ref( $reference ) {
	global $wpdb;

	$results = $wpdb->get_results( $wpdb->prepare( "
                            SELECT      post_id
                            FROM        {$wpdb->postmeta}
                            WHERE       meta_key = '_sido_tx_reference'
                AND         meta_value = %s
                LIMIT       1
                    ", mysql_real_escape_string( $reference ) ) );

	// Check and handle when no or multiple results are found
	if ( 1 > $wpdb->num_rows ) {
		return;
	} else {
		$order_id = $results[0]->id;
		return new WC_Order( $order_id );
	}
}

/**
 * Process a successful subscription
 * @param string $reference
 * @param string $order
 * @param string $address
 * @param string $email
 * @param string $phoneNumber
 */
function sido_subscription_activated( $reference, $order, $address, $email, $phoneNumber ) {

	$order->add_order_note( __( "SIDO subscription reference: $reference", 'woocommerce' ) );

	if ( $address ) {
		update_shipping_address( $order, $address );
	}
	if ( $email ) {
		update_email( $order, $email );
	}
	if ( $phoneNumber ) {
		update_phoneNumber( $order, $phoneNumber );
	}
	$order->payment_complete();
}

/**
 * Update subscription if its paid
 * @global object $wpdb
 * @param object $order
 * @param string $ref
 * @param string $address
 * @param string $email
 * @param string $phoneNumber
 * @param string $dref
 */
function sido_subscription_paid( $order, $ref, $address, $email, $phoneNumber, $dref ) {

	global $wpdb;
	$results = sido_get_reference( $dref );
	sido_debug( 'dref' . $dref );
	sido_debug( 'rows' . $wpdb->num_rows );
	if ( $wpdb->num_rows >= 1 ) {
		$subscription_key = WC_Subscriptions_Manager::get_subscription_key( $order->id );
		$renewal_order = new WC_Order( WC_Subscriptions_Renewal_Order::generate_paid_renewal_order( $order->customer_user, $subscription_key ) );
		set_tx_reference( $order, $ref );

		sido_debug( 'renewal payment' );
		if ( $address ) {
			sido_debug( "Updating address on renewal order: " . print_r( $address, true ) );
			update_shipping_address( $renewal_order, $address );
			update_shipping_address( $order, $address );
		}
		if ( $email ) {
			update_email( $renewal_order, $email );
			update_email( $order, $email );
		}
		if ( $phoneNumber ) {
			update_phoneNumber( $renewal_order, $phoneNumber );
			update_phoneNumber( $order, $phoneNumber );
		}
	} else {


		$order->add_order_note( __( "SIDO subscription reference: $ref", 'woocommerce' ) );
		if ( $address ) {
			update_shipping_address( $order, $address );
		}
		if ( $email ) {
			update_email( $order, $email );
		}
		if ( $phoneNumber ) {
			update_phoneNumber( $order, $phoneNumber );
		}
		$order->payment_complete();


		sido_insert_reference($dref);
		sido_debug( 'first payment' );

	}
}

/**
 * Cancels an order
 * @param object $order
 */
function sido_subscription_cancelled( $order ) {
	$order->cancel_order();
}

/**
 * Subscriptions expire when they have completed their duration
 * @param object $order
 */
function sido_subscription_expired( $order ) {
	sido_debug( "sido_subscription_expired called on $order->id" );
	// Expire the Subscription under the Order
	WC_Subscriptions_Manager::expire_subscriptions_for_order( $order );
	// Mark the Subscription as Failed
	$order->update_status( 'completed', sprintf( __( 'Subscription completed', 'woocommerce' ) ) );
}

/**
 * Guest Subscription payment failures do not automatically cancel the Subscription
 * Merchant is able to resubmit payment via the SIDO Portal
 * This function will generate a new, failed Order in WooCommerce, to notify
 * the Merchant and provide a record of the failed payment
 * @param object $order
 */
function sido_subscription_payment_failed( $order ) {
	$order->add_order_note( __( 'SIDO scheduled subscription payment failed for Guest user. Payment declined. See Merchant Portal to resubmit for approval.', 'woocommerce' ) );
	WC_Subscriptions_Manager::process_subscription_payment_failure_on_order( $order );
}

/**
 * Find the Order associated with a transaction reference
 * @global object $wpdb
 * @param string $reference
 * @return \WC_Order
 */
function sido_get_order_by_tx_ref( $reference ) {
	global $wpdb;

	$results = $wpdb->get_results( $wpdb->prepare( "
                            SELECT      post_id
                            FROM        {$wpdb->postmeta}
                            WHERE       meta_key = '_sido_tx_reference'
                AND         meta_value = %s
                LIMIT       1
                    ", mysql_real_escape_string( $reference ) ) );

	// Check and handle when no or multiple results are found
	if ( 1 > $wpdb->num_rows ) {
		return;
	} else {
		$order_id = $results[0]->post_id;
		return new WC_Order( $order_id );
	}
}

/**
 * Find the first Order, the one containing a given Subscription
 * Only the first Order has the Subscription meta
 * @global object $wpdb
 * @param  string $reference
 * @return \WC_Order
 */
function sido_get_order_by_subscription_ref( $reference ) {
	global $wpdb;

	$results = $wpdb->get_results( $wpdb->prepare( "
                            SELECT      post_id
                            FROM        {$wpdb->postmeta}
                            WHERE       meta_key = '_sido_subscription_reference'
                AND         meta_value = %s
                LIMIT       1
                    ", mysql_real_escape_string( $reference ) ) );

	// Check and handle when no or multiple results are found
	if ( 1 > $wpdb->num_rows ) {
		return;
	} else {
		$order_id = $results[0]->post_id;
		return new WC_Order( $order_id );
	}
}

/**
 * Process a successful transaction
 * @param string $reference
 * @param object $order
 * @param string $address
 * @param string $email
 * @param string $phoneNumber
 */
function sido_order_paid( $reference, $order, $address, $email, $phoneNumber ) {

	$order->add_order_note( __( "SIDO transaction reference: $reference", 'woocommerce' ) );

	sido_debug( "sido_order_paid: $order->id $email $phoneNumber" );
	if ( $address ) {
		update_shipping_address( $order, $address );
	}
	if ( $email ) {
		update_email( $order, $email );
	}
	if ( $phoneNumber ) {
		sido_debug( "starting phoneNumber update now" );
		update_phoneNumber( $order, $phoneNumber );
	}
	$order->payment_complete();
}

/**
 * Process a cancelled transaction
 * @param object $order
 */
function sido_order_cancelled( $order ) {
	$order->cancel_order();
}

/**
 * Process a refunded transaction
 * @param object $order
 */
function sido_order_refunded( $order ) {
	$order->update_status( 'refunded', sprintf( __( 'Order refunded through SIDO', 'woocommerce' ) ) );
}

/**
 * Update the phoneNumber number for the specified Order
 * @param object $order
 * @param string $phoneNumber
 */
function update_phoneNumber( $order, $phoneNumber ) {
	sido_debug( "Updating phoneNumber for $order->id" );
	if ( $phoneNumber && !update_post_meta( $order->id, '_billing_phone', woocommerce_clean( $phoneNumber ) ) ) {
		sido_debug( "Setting phoneNumber failed" );
	}
}

/**
 * Update the email number for the specified Order
 * @param object $order
 * @param string $email
 */
function update_email( $order, $email ) {
	sido_debug( "Updating email for $order->id" );
	if ( $email && !update_post_meta( $order->id, '_billing_email', woocommerce_clean( $email ) ) ) {
		sido_debug( "Setting email number failed" );
	}
}

/**
 * Update the Shipping Address for the specified Order
 * @param object $order
 * @param string $address
 */
function update_shipping_address( $order, $address ) {

	@sido_debug( "Updating shipping address for $order->id" );

	if (
			isset( $address['countryCode'] ) &&
			//"" != $address['countryCode'] &&
			!update_post_meta( $order->id, '_shipping_country', woocommerce_clean( $address['countryCode'] ) )
	) {
		sido_debug( "Setting _shipping_country failed" );
	}
	if (
			isset( $address['city'] ) &&
			"" != $address['city'] &&
			!update_post_meta( $order->id, '_shipping_city', woocommerce_clean( $address['city'] ) )
	) {
		sido_debug( "Setting _shipping_city failed" );
	}
	if (
			isset( $address['zipCode'] ) &&
			"" != $address['zipCode'] &&
			!update_post_meta( $order->id, '_shipping_postcode', woocommerce_clean( $address['zipCode'] ) )
	) {
		sido_debug( "Setting _shipping_postcode failed" );
	}
	if (
			isset( $address['address1'] ) &&
			"" != $address['address1'] &&
			!update_post_meta( $order->id, '_shipping_address_1', woocommerce_clean( $address['address1'] ) )
	) {
		sido_debug( "Setting _shipping_address_1 failed" );
	}
	if (
			isset( $address['address2'] ) &&
			"" != $address['address2'] &&
			!update_post_meta( $order->id, '_shipping_address_2', woocommerce_clean( $address['address2'] ) )
	) {
		sido_debug( "Setting _shipping_address_2 failed" );
	}

	if ( isset( $address['prefecture'] ) || isset( $address['state'] ) ) {
		//if prefecture is set use it, else use the state if it set or none
		$state = isset( $address['prefecture'] ) ?
				$address['prefecture'] :
				( isset( $address['state'] ) ? $address['state'] : '' );
		if (
				"" != $state &&
				!update_post_meta( $order->id, '_shipping_state', woocommerce_clean( $state ) )
		) {
			sido_debug( "Setting _shipping_state failed" );
		}
	}

	if ( isset( $address['recipientName'] ) ) {
		// Have recipient name as first name, last name instead of combined
		$recipientName = $address['recipientName'];
		// if( false != strpos( $recipientName, ' ' ) ) {
		if ( preg_match( '/\s/', $recipientName ) ) {
			list( $name, $surname ) = explode( " ", $recipientName, 2 );
			sido_debug( 'NAME' . $name );
			sido_debug( 'SURNAME' . $surname );
			if ( !update_post_meta( $order->id, '_shipping_last_name', woocommerce_clean( $surname ) ) ) {
				sido_debug( "Setting _shipping_last_name failed" );
			}
			if ( !update_post_meta( $order->id, '_shipping_first_name', woocommerce_clean( $name ) ) ) {
				sido_debug( "Setting _shipping_first_name failed" );
			}
		} else if ( 0 < strlen( $recipientName ) ) {
			if ( !update_post_meta( $order->id, '_shipping_first_name', woocommerce_clean( $recipientName ) ) ) {
				sido_debug( "Setting _shipping_first_name failed" );
			}
		}
	}
}
