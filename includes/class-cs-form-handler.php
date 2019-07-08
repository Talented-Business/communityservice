<?php
/**
 * Handle frontend forms.
 *
 * @version	1.0
 * @package	CommunityService/Classes/
 */

defined( 'ABSPATH' ) || exit;

/**
 * CS_Form_Handler class.
 */
class CS_Form_Handler {

	/**
	 * Hook in methods.
	 */
	public static function init() {
		add_action( 'template_redirect', array( __CLASS__, 'redirect_reset_password_link' ) );
		add_action( 'template_redirect', array( __CLASS__, 'save_address' ) );
		add_action( 'template_redirect', array( __CLASS__, 'save_account_details' ) );
		add_action( 'wp_loaded', array( __CLASS__, 'process_login' ), 20 );
		add_action( 'wp_loaded', array( __CLASS__, 'submit_activity' ), 20 );//for student
		add_action( 'wp_loaded', array( __CLASS__, 'process_registration' ), 20 );
		add_action( 'wp_loaded', array( __CLASS__, 'process_lost_password' ), 20 );
		add_action( 'wp_loaded', array( __CLASS__, 'process_reset_password' ), 20 );
		add_action( 'wp_loaded', array( __CLASS__, 'cancel_activity' ), 20 );
		add_action( 'wp_loaded', array( __CLASS__, 'passport_activities' ), 20 );

		// May need $wp global to access query vars.
		add_action( 'wp', array( __CLASS__, 'pay_action' ), 20 );
	}

	/**
	 * Remove key and user ID (or user login, as a fallback) from query string, set cookie, and redirect to account page to show the form.
	 */
	public static function redirect_reset_password_link() {
		if ( is_account_page() && isset( $_GET['key'] ) && ( isset( $_GET['id'] ) || isset( $_GET['login'] ) ) ) {

			// If available, get $user_id from query string parameter for fallback purposes.
			if ( isset( $_GET['login'] ) ) {
				$user = get_user_by( 'login', sanitize_user( wp_unslash( $_GET['login'] ) ) );
				$user_id = $user ? $user->ID : 0;
			} else {
				$user_id = absint( $_GET['id'] );
			}

			$value = sprintf( '%d:%s', $user_id, wp_unslash( $_GET['key'] ) );
			CS_Shortcode_My_Account::set_reset_password_cookie( $value );
			wp_safe_redirect( add_query_arg( 'show-reset-form', 'true', cs_lostpassword_url() ) );
			exit;
		}
	}

	/**
	 * Save and and update a billing or shipping address if the
	 * form was submitted through the user account page.
	 */
	public static function save_address() {
		global $wp;
		
		if ( 'POST' !== strtoupper( $_SERVER['REQUEST_METHOD'] ) ) {
			return;
		}

		if ( empty( $_POST['action'] ) || 'edit_address' !== $_POST['action'] ) {
			return;
		}
		
		cs_nocache_headers();

		$nonce_value = cs_get_var( $_REQUEST['communityservice-edit-address-nonce'], cs_get_var( $_REQUEST['_wpnonce'], '' ) ); // @codingStandardsIgnoreLine.

		if ( ! wp_verify_nonce( $nonce_value, 'communityservice-edit_address' ) ) {
			return;
		}

		$user_id = get_current_user_id();

		if ( $user_id <= 0 ) {
			return;
		}

		$load_address = isset( $wp->query_vars['edit-address'] ) ? cs_edit_address_i18n( sanitize_title( $wp->query_vars['edit-address'] ), true ) : 'billing';

		$address = CS()->countries->get_address_fields( esc_attr( $_POST[ $load_address . '_country' ] ), $load_address . '_' );

		foreach ( $address as $key => $field ) {

			if ( ! isset( $field['type'] ) ) {
				$field['type'] = 'text';
			}

			// Get Value.
			switch ( $field['type'] ) {
				case 'checkbox' :
					$_POST[ $key ] = (int) isset( $_POST[ $key ] );
					break;
				default :
					$_POST[ $key ] = isset( $_POST[ $key ] ) ? cs_clean( $_POST[ $key ] ) : '';
					break;
			}

			// Hook to allow modification of value.
			$_POST[ $key ] = apply_filters( 'communityservice_process_myaccount_field_' . $key, $_POST[ $key ] );

			// Validation: Required fields.
			if ( ! empty( $field['required'] ) && empty( $_POST[ $key ] ) ) {
				cs_add_notice( sprintf( __( '%s is a required field.', 'communityservice' ), $field['label'] ), 'error' );
			}

			if ( ! empty( $_POST[ $key ] ) ) {

				// Validation rules.
				if ( ! empty( $field['validate'] ) && is_array( $field['validate'] ) ) {
					foreach ( $field['validate'] as $rule ) {
						switch ( $rule ) {
							case 'postcode' :
								$_POST[ $key ] = strtoupper( str_replace( ' ', '', $_POST[ $key ] ) );

								if ( ! CS_Validation::is_postcode( $_POST[ $key ], $_POST[ $load_address . '_country' ] ) ) {
									cs_add_notice( __( 'Please enter a valid postcode / ZIP.', 'communityservice' ), 'error' );
								} else {
									$_POST[ $key ] = cs_format_postcode( $_POST[ $key ], $_POST[ $load_address . '_country' ] );
								}
								break;
							case 'phone' :
								if ( ! CS_Validation::is_phone( $_POST[ $key ] ) ) {
									cs_add_notice( sprintf( __( '%s is not a valid phone number.', 'communityservice' ), '<strong>' . $field['label'] . '</strong>' ), 'error' );
								}
								break;
							case 'email' :
								$_POST[ $key ] = strtolower( $_POST[ $key ] );

								if ( ! is_email( $_POST[ $key ] ) ) {
									cs_add_notice( sprintf( __( '%s is not a valid email address.', 'communityservice' ), '<strong>' . $field['label'] . '</strong>' ), 'error' );
								}
								break;
						}
					}
				}
			}
		}

		do_action( 'communityservice_after_save_address_validation', $user_id, $load_address, $address );

		if ( 0 === cs_notice_count( 'error' ) ) {

			$student = new CS_Student( $user_id );

			if ( $student ) {
				foreach ( $address as $key => $field ) {
					if ( is_callable( array( $student, "set_$key" ) ) ) {
						$student->{"set_$key"}( cs_clean( $_POST[ $key ] ) );
					} else {
						$student->update_meta_data( $key, cs_clean( $_POST[ $key ] ) );
					}

					if ( CS()->student && is_callable( array( CS()->student, "set_$key" ) ) ) {
						CS()->student->{"set_$key"}( cs_clean( $_POST[ $key ] ) );
					}
				}
				$student->save();
			}

			cs_add_notice( __( 'Address changed successfully.', 'communityservice' ) );

			do_action( 'communityservice_student_save_address', $user_id, $load_address );

			wp_safe_redirect( cs_get_endpoint_url( 'edit-address', '', cs_get_page_permalink( 'myaccount' ) ) );
			exit;
		}
	}

	/**
	 * Save the password/account details and redirect back to the my account page.
	 */
	public static function save_account_details() {
		if ( 'POST' !== strtoupper( $_SERVER['REQUEST_METHOD'] ) ) {
			return;
		}

		if ( empty( $_POST['action'] ) || 'save_account_details' !== $_POST['action'] ) {
			return;
		}
		
		cs_nocache_headers();
		
		$nonce_value = cs_get_var( $_REQUEST['save-account-details-nonce'], cs_get_var( $_REQUEST['_wpnonce'], '' ) ); // @codingStandardsIgnoreLine.

		if ( ! wp_verify_nonce( $nonce_value, 'save_account_details' ) ) {
			return;
		}

		$user_id = get_current_user_id();

		if ( $user_id <= 0 ) {
			return;
		}

		$account_first_name   = ! empty( $_POST['account_first_name'] ) ? cs_clean( $_POST['account_first_name'] ): '';
		$account_last_name    = ! empty( $_POST['account_last_name'] ) ? cs_clean( $_POST['account_last_name'] ) : '';
		$account_display_name = ! empty( $_POST['account_display_name'] ) ? cs_clean( $_POST['account_display_name'] ) : '';
		$account_email        = ! empty( $_POST['account_email'] ) ? cs_clean( $_POST['account_email'] ) : '';
		$pass_cur             = ! empty( $_POST['password_current'] ) ? $_POST['password_current'] : '';
		$pass1                = ! empty( $_POST['password_1'] ) ? $_POST['password_1'] : '';
		$pass2                = ! empty( $_POST['password_2'] ) ? $_POST['password_2'] : '';
		$save_pass            = true;

		// Current user data.
		$current_user       = get_user_by( 'id', $user_id );
		$current_first_name = $current_user->first_name;
		$current_last_name  = $current_user->last_name;
		$current_email      = $current_user->user_email;

		// New user data.
		$user                = new stdClass();
		$user->ID            = $user_id;
		$user->first_name    = $account_first_name;
		$user->last_name     = $account_last_name;
		$user->display_name  = $account_display_name;

		// Prevent display name to be changed to email.
		if ( is_email( $account_display_name ) ) {
			cs_add_notice( __( 'Display name cannot be changed to email address due to privacy concern.', 'communityservice' ), 'error' );
		}

		// Handle required fields.
		$required_fields = apply_filters( 'communityservice_save_account_details_required_fields', array(
			'account_first_name'    => __( 'First name', 'communityservice' ),
			'account_last_name'     => __( 'Last name', 'communityservice' ),
			'account_display_name'  => __( 'Display name', 'communityservice' ),
			'account_email'         => __( 'Email address', 'communityservice' ),
		) );

		foreach ( $required_fields as $field_key => $field_name ) {
			if ( empty( $_POST[ $field_key ] ) ) {
				cs_add_notice( sprintf( __( '%s is a required field.', 'communityservice' ), '<strong>' . esc_html( $field_name ) . '</strong>' ), 'error' );
			}
		}

		if ( $account_email ) {
			$account_email = sanitize_email( $account_email );
			if ( ! is_email( $account_email ) ) {
				cs_add_notice( __( 'Please provide a valid email address.', 'communityservice' ), 'error' );
			} elseif ( email_exists( $account_email ) && $account_email !== $current_user->user_email ) {
				cs_add_notice( __( 'This email address is already registered.', 'communityservice' ), 'error' );
			}
			$user->user_email = $account_email;
		}

		if ( ! empty( $pass_cur ) && empty( $pass1 ) && empty( $pass2 ) ) {
			cs_add_notice( __( 'Please fill out all password fields.', 'communityservice' ), 'error' );
			$save_pass = false;
		} elseif ( ! empty( $pass1 ) && empty( $pass_cur ) ) {
			cs_add_notice( __( 'Please enter your current password.', 'communityservice' ), 'error' );
			$save_pass = false;
		} elseif ( ! empty( $pass1 ) && empty( $pass2 ) ) {
			cs_add_notice( __( 'Please re-enter your password.', 'communityservice' ), 'error' );
			$save_pass = false;
		} elseif ( ( ! empty( $pass1 ) || ! empty( $pass2 ) ) && $pass1 !== $pass2 ) {
			cs_add_notice( __( 'New passwords do not match.', 'communityservice' ), 'error' );
			$save_pass = false;
		} elseif ( ! empty( $pass1 ) && ! wp_check_password( $pass_cur, $current_user->user_pass, $current_user->ID ) ) {
			cs_add_notice( __( 'Your current password is incorrect.', 'communityservice' ), 'error' );
			$save_pass = false;
		}

		if ( $pass1 && $save_pass ) {
			$user->user_pass = $pass1;
		}

		// Allow plugins to return their own errors.
		$errors = new WP_Error();
		do_action_ref_array( 'communityservice_save_account_details_errors', array( &$errors, &$user ) );

		if ( $errors->get_error_messages() ) {
			foreach ( $errors->get_error_messages() as $error ) {
				cs_add_notice( $error, 'error' );
			}
		}

		if ( cs_notice_count( 'error' ) === 0 ) {
			wp_update_user( $user );

			// Update student object to keep data in sync.
			$student = new CS_Student( $user->ID );

			if ( $student && false ) {
				$student->save();
			}

			cs_add_notice( __( 'Account details changed successfully.', 'communityservice' ) );

			do_action( 'communityservice_save_account_details', $user->ID );

			wp_safe_redirect( cs_get_page_permalink( 'myaccount' ) );
			exit;
		}
	}
	/**
	 * Add to external activity submit action.
	 *
	 * Checks for a valid request, does validation (via hooks) and then redirects if valid.
	 *
	 * @param bool $url (default: false)
	 */
	public static function submit_activity( $url = false ) {
		if ( empty( $_REQUEST['submit-activity'] )) {
			return;
		}
		try {
			$nonce_value = cs_get_var( $_REQUEST['communityservice-submit-activity-nonce'], cs_get_var( $_REQUEST['_wpnonce'], '' ) );

			if ( empty( $nonce_value ) || ! wp_verify_nonce( $nonce_value, 'communityservice-submit-activity' ) ) {
				throw new Exception( __( 'We were unable to submit your activity, please try again.', 'communityservice' ) );
			}
			$activity = new CS_Activity;
			$activity_id = $activity->process_submit();
			if($_POST['submit-activity']=='submit-activity-ajax'&&$activity_id){
				echo "success";
				die;
			}

		} catch ( Exception $e ) {
			cs_add_notice( $e->getMessage(), 'error' );
		}
	}



	/**
	 * Process the pay form.
	 */
	public static function pay_action() {
		global $wp;

		if ( isset( $_POST['communityservice_pay'] ) ) {
			cs_nocache_headers();

			$nonce_value = cs_get_var( $_REQUEST['communityservice-pay-nonce'], cs_get_var( $_REQUEST['_wpnonce'], '' ) ); // @codingStandardsIgnoreLine.

			if ( ! wp_verify_nonce( $nonce_value, 'communityservice-pay' ) ) {
				return;
			}

			ob_start();

			// Pay for existing activity
			$activity_key  = $_GET['key'];
			$activity_id   = absint( $wp->query_vars['activity-pay'] );
			$activity      = cs_get_activity( $activity_id );

			if ( $activity_id === $activity->get_id() && hash_equals( $activity->get_activity_key(), $activity_key ) && $activity->needs_payment() ) {

				do_action( 'communityservice_before_pay_action', $activity );

				CS()->student->set_props( array(
					'billing_country'  => $activity->get_billing_country() ? $activity->get_billing_country()   : null,
					'billing_state'    => $activity->get_billing_state() ? $activity->get_billing_state()       : null,
					'billing_postcode' => $activity->get_billing_postcode() ? $activity->get_billing_postcode() : null,
					'billing_city'     => $activity->get_billing_city() ? $activity->get_billing_city()         : null,
				) );
				CS()->student->save();

				// Terms
				if ( ! empty( $_POST['terms-field'] ) && empty( $_POST['terms'] ) ) {
					cs_add_notice( __( 'Please read and accept the terms and conditions to proceed with your activity.', 'communityservice' ), 'error' );
					return;
				}

				// Update payment method
				if ( $activity->needs_payment() ) {
					$payment_method     = isset( $_POST['payment_method'] ) ? cs_clean( $_POST['payment_method'] ) : false;
					$available_gateways = CS()->payment_gateways->get_available_payment_gateways();

					if ( ! $payment_method ) {
						cs_add_notice( __( 'Invalid payment method.', 'communityservice' ), 'error' );
						return;
					}

					// Update meta
					update_post_meta( $activity_id, '_payment_method', $payment_method );

					if ( isset( $available_gateways[ $payment_method ] ) ) {
						$payment_method_title = $available_gateways[ $payment_method ]->get_title();
					} else {
						$payment_method_title = '';
					}

					update_post_meta( $activity_id, '_payment_method_title', $payment_method_title );

					// Validate
					$available_gateways[ $payment_method ]->validate_fields();

					// Process
					if ( 0 === cs_notice_count( 'error' ) ) {

						$result = $available_gateways[ $payment_method ]->process_payment( $activity_id );

						// Redirect to success/confirmation/payment page
						if ( 'success' === $result['result'] ) {
							wp_redirect( $result['redirect'] );
							exit;
						}
					}
				} else {
					// No payment was required for activity
					$activity->payment_complete();
					wp_safe_redirect( $activity->get_checkout_activity_received_url() );
					exit;
				}

				do_action( 'communityservice_after_pay_action', $activity );

			}
		}
	}


	/**
	 * Cancel a pending activity.
	 */
	public static function cancel_activity() {
		if (
			isset( $_GET['cancel_activity'] ) &&
			isset( $_GET['activity'] ) &&
			isset( $_GET['activity_id'] ) &&
			( isset( $_GET['_wpnonce'] ) && wp_verify_nonce( $_GET['_wpnonce'], 'communityservice-cancel_activity' ) )
		) {
			cs_nocache_headers();

			$activity_key        = $_GET['activity'];
			$activity_id         = absint( $_GET['activity_id'] );
			$activity            = cs_get_activity( $activity_id );
			$user_can_cancel  = current_user_can( 'cancel_activity', $activity_id );
			$activity_can_cancel = $activity->has_status( apply_filters( 'communityservice_valid_activity_statuses_for_cancel', array( 'pending', 'failed' ) ) );
			$redirect         = $_GET['redirect'];

			if ( $activity->has_status( 'cancelled' ) ) {
				// Already cancelled - take no action
			} elseif ( $user_can_cancel && $activity_can_cancel && $activity->get_id() === $activity_id && hash_equals( $activity->get_activity_key(), $activity_key ) ) {

				// Cancel the activity + restore stock
				CS()->session->set( 'activity_awaiting_payment', false );
				$activity->update_status( 'cancelled', __( 'Order cancelled by student.', 'communityservice' ) );

				// Message
				cs_add_notice( apply_filters( 'communityservice_activity_cancelled_notice', __( 'Your activity was cancelled.', 'communityservice' ) ), apply_filters( 'communityservice_activity_cancelled_notice_type', 'notice' ) );

				do_action( 'communityservice_cancelled_activity', $activity->get_id() );

			} elseif ( $user_can_cancel && ! $activity_can_cancel ) {
				cs_add_notice( __( 'Your activity can no longer be cancelled. Please contact us if you need assistance.', 'communityservice' ), 'error' );
			} else {
				cs_add_notice( __( 'Invalid activity.', 'communityservice' ), 'error' );
			}

			if ( $redirect ) {
				wp_safe_redirect( $redirect );
				exit;
			}
		}
	}

	/**
	 * Process the login form.
	 */
	public static function process_login() {
		// The global form-login.php template used `_wpnonce` in template versions < 3.3.0.
		$nonce_value = cs_get_var( $_REQUEST['communityservice-login-nonce'], cs_get_var( $_REQUEST['_wpnonce'], '' ) ); // @codingStandardsIgnoreLine.
		
		if ( ! empty( $_POST['login'] ) && wp_verify_nonce( $nonce_value, 'communityservice-login' ) ) {
			
			try {
				$creds = array(
					'user_login'    => trim( $_POST['email'] ),
					'user_password' => $_POST['password'],
					'remember'      => isset( $_POST['rememberme'] ),
				);
				
				$validation_error = new WP_Error();
				$validation_error = apply_filters( 'communityservice_process_login_errors', $validation_error, $_POST['username'], $_POST['password'] );

				if ( $validation_error->get_error_code() ) {
					throw new Exception( '<strong>' . __( 'Error:', 'communityservice' ) . '</strong> ' . $validation_error->get_error_message() );
				}

				if ( empty( $creds['user_login'] ) ) {
					throw new Exception( '<strong>' . __( 'Error:', 'communityservice' ) . '</strong> ' . __( 'Username is required.', 'communityservice' ) );
				}

				// On multisite, ensure user exists on current site, if not add them before allowing login.
				if ( is_multisite() ) {
					$user_data = get_user_by( is_email( $creds['user_login'] ) ? 'email' : 'login', $creds['user_login'] );

					if ( $user_data && ! is_user_member_of_blog( $user_data->ID, get_current_blog_id() ) ) {
						add_user_to_blog( get_current_blog_id(), $user_data->ID, 'student' );
					}
				}

				// Perform the login
				$user = wp_signon( apply_filters( 'communityservice_login_credentials', $creds ), is_ssl() );

				if ( is_wp_error( $user ) ) {
					$message = $user->get_error_message();
					$message = str_replace( '<strong>' . esc_html( $creds['user_login'] ) . '</strong>', '<strong>' . esc_html( $creds['user_login'] ) . '</strong>', $message );
					throw new Exception( $message );
				} else {

					if ( ! empty( $_POST['redirect'] ) ) {
						$redirect = $_POST['redirect'];
					} elseif ( cs_get_raw_referer() ) {
						$redirect = cs_get_raw_referer();
					} else {
						$redirect = cs_get_page_permalink( 'myaccount' );
					}

					wp_redirect( wp_validate_redirect( apply_filters( 'communityservice_login_redirect', remove_query_arg( 'cs_error', $redirect ), $user ), cs_get_page_permalink( 'myaccount' ) ) );
					exit;
				}
			} catch ( Exception $e ) {
				cs_add_notice( apply_filters( 'login_errors', $e->getMessage() ), 'error' );
				do_action( 'communityservice_login_failed' );
			}
		}
	}

	/**
	 * Handle lost password form.
	 */
	public static function process_lost_password() {
		if ( isset( $_POST['cs_reset_password'], $_POST['user_login'] ) ) {
			$nonce_value = cs_get_var( $_REQUEST['communityservice-lost-password-nonce'], cs_get_var( $_REQUEST['_wpnonce'], '' ) ); // @codingStandardsIgnoreLine.

			if ( ! wp_verify_nonce( $nonce_value, 'lost_password' ) ) {
				return;
			}

			$success = CS_Shortcode_My_Account::retrieve_password();

			// If successful, redirect to my account with query arg set.
			if ( $success ) {
				wp_redirect( add_query_arg( 'reset-link-sent', 'true', cs_get_account_endpoint_url( 'lost-password' ) ) );
				exit;
			}
		}
	}

	/**
	 * Handle reset password form.
	 */
	public static function process_reset_password() {
		$posted_fields = array( 'cs_reset_password', 'password_1', 'password_2', 'reset_key', 'reset_login' );

		foreach ( $posted_fields as $field ) {
			if ( ! isset( $_POST[ $field ] ) ) {
				return;
			}
			$posted_fields[ $field ] = $_POST[ $field ];
		}

		$nonce_value = cs_get_var( $_REQUEST['communityservice-reset-password-nonce'], cs_get_var( $_REQUEST['_wpnonce'], '' ) ); // @codingStandardsIgnoreLine.

		if ( ! wp_verify_nonce( $nonce_value, 'reset_password' ) ) {
			return;
		}

		$user = CS_Shortcode_My_Account::check_password_reset_key( $posted_fields['reset_key'], $posted_fields['reset_login'] );

		if ( $user instanceof WP_User ) {
			if ( empty( $posted_fields['password_1'] ) ) {
				cs_add_notice( __( 'Please enter your password.', 'communityservice' ), 'error' );
			}

			if ( $posted_fields['password_1'] !== $posted_fields['password_2'] ) {
				cs_add_notice( __( 'Passwords do not match.', 'communityservice' ), 'error' );
			}

			$errors = new WP_Error();

			do_action( 'validate_password_reset', $errors, $user );

			cs_add_wp_error_notices( $errors );

			if ( 0 === cs_notice_count( 'error' ) ) {
				CS_Shortcode_My_Account::reset_password( $user, $posted_fields['password_1'] );

				do_action( 'communityservice_student_reset_password', $user );

				wp_redirect( add_query_arg( 'password-reset', 'true', cs_get_page_permalink( 'myaccount' ) ) );
				exit;
			}
		}
	}
	public static function passport_activities(){
		if(isset($_GET['activity_index'])){
			if(!is_user_logged_in()){
				return;
			}
			$page = $_GET['activity_index'] - 4;
			$args = array(
				'type'=>'cs-activity',
				'author'=>get_current_user_id(),
				'status'=>'cs-approved',
				'limit'=>1,
				'page' =>$page
			);
			$activities = cs_get_activities($args);
			cs_get_template('activity/passport.php',array('activities'=>$activities));
			die;
		}
	}
	private static function register_user_data($new_student,$first_name,$last_name,$year, $house){
		update_user_meta($new_student,'first_name',$first_name);
		update_user_meta($new_student,'last_name',$last_name);
		update_year_user($new_student,$year);
		update_house_user($new_student,$house);
	}
	/**
	 * Process the registration form.
	 */
	public static function process_registration() {
		$nonce_value = isset( $_POST['_wpnonce'] ) ? $_POST['_wpnonce'] : '';
		$nonce_value = isset( $_POST['communityservice-register-nonce'] ) ? $_POST['communityservice-register-nonce'] : $nonce_value;

		if ( ! empty( $_POST['register'] ) && wp_verify_nonce( $nonce_value, 'communityservice-register' ) ) {
			$username = $_POST['username'];
			$password = $_POST['password'];
			$password_confirm = $_POST['password1'];
			$email    = $_POST['email'];

			try {
				$validation_error = new WP_Error();
				$validation_error = apply_filters( 'communityservice_process_registration_errors', $validation_error, $username, $password, $email );

				if ( $validation_error->get_error_code() ) {
					throw new Exception( $validation_error->get_error_message() );
				}
				if($password !=$password_confirm){
					throw new Exception( "Please check password and password confirm." );
				}
				$new_student = cs_create_new_student( sanitize_email( $email ), cs_clean( $username ), $password );

				if ( is_wp_error( $new_student ) ) {
					throw new Exception( $new_student->get_error_message() );
				}
				$first_name = $_POST['firstname'];
				$last_name = $_POST['lastname'];
				$year = $_POST['year'];
				$house = $_POST['house'];
				self::register_user_data($new_student,$first_name,$last_name,$year, $house);
				if ( apply_filters( 'communityservice_registration_auth_new_student', true, $new_student ) ) {
					cs_set_student_auth_cookie( $new_student );
				}

				if ( ! empty( $_POST['redirect'] ) ) {
					$redirect = wp_sanitize_redirect( $_POST['redirect'] );
				} elseif ( cs_get_raw_referer() ) {
					$redirect = cs_get_raw_referer();
				} else {
					$redirect = cs_get_page_permalink( 'myaccount' );
				}

				wp_redirect( wp_validate_redirect( apply_filters( 'communityservice_registration_redirect', $redirect ), cs_get_page_permalink( 'myaccount' ) ) );
				exit;

			} catch ( Exception $e ) {
				cs_add_notice( '<strong>' . __( 'Error:', 'communityservice' ) . '</strong> ' . $e->getMessage(), 'error' );
			}
		}
	}
}

CS_Form_Handler::init();
