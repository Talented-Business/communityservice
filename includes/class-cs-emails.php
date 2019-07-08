<?php
/**
 * Transactional Emails Controller
 *
 * CommunityService Emails Class which handles the sending on transactional emails and email templates. This class loads in available emails.
 *
 * @package CommunityService/Classes/Emails
 * @version 1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Emails class.
 */
class CS_Emails {

	/**
	 * Array of email notification classes
	 *
	 * @var array
	 */
	public $emails = array();

	/**
	 * The single instance of the class
	 *
	 * @var CS_Emails
	 */
	protected static $_instance = null;

	/**
	 * Background emailer class.
	 *
	 * @var CS_Background_Emailer
	 */
	protected static $background_emailer = null;

	/**
	 * Main CS_Emails Instance.
	 *
	 * Ensures only one instance of CS_Emails is loaded or can be loaded.
	 *
	 * @since 1.0
	 * @static
	 * @return CS_Emails Main instance
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Cloning is forbidden.
	 *
	 * @since 1.0
	 */
	public function __clone() {
		cs_doing_it_wrong( __FUNCTION__, __( 'Cloning is forbidden.', 'communityservice' ), '1.0' );
	}

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 1.0
	 */
	public function __wakeup() {
		cs_doing_it_wrong( __FUNCTION__, __( 'Unserializing instances of this class is forbidden.', 'communityservice' ), '1.0' );
	}

	/**
	 * Hook in all transactional emails.
	 */
	public static function init_transactional_emails() {
		$email_actions = apply_filters(
			'communityservice_email_actions', array(
				'communityservice_task_on_backactivity',
				'communityservice_activity_status_pending_to_approved',
				'communityservice_activity_status_pending_to_cancelled',
				'communityservice_activity_status_cancelled_to_pending',
				'communityservice_activity_status_cancelled_to_approved',
				'communityservice_activity_status_approved_to_cancelled',
				'communityservice_activity_status_approved',
				'communityservice_new_student_note',
				'communityservice_created_student',
			)
		);

		if ( apply_filters( 'communityservice_defer_transactional_emails', false ) ) {
			self::$background_emailer = new CS_Background_Emailer();

			foreach ( $email_actions as $action ) {
				add_action( $action, array( __CLASS__, 'queue_transactional_email' ), 10, 10 );
			}
		} else {
			foreach ( $email_actions as $action ) {
				add_action( $action, array( __CLASS__, 'send_transactional_email' ), 10, 10 );
			}
		}
	}

	/**
	 * Queues transactional email so it's not sent in current request if enabled,
	 * otherwise falls back to send now.
	 */
	public static function queue_transactional_email() {
		if ( is_a( self::$background_emailer, 'CS_Background_Emailer' ) ) {
			self::$background_emailer->push_to_queue(
				array(
					'filter' => current_filter(),
					'args'   => func_get_args(),
				)
			);
		} else {
			call_user_func_array( array( __CLASS__, 'send_transactional_email' ), func_get_args() );
		}
	}

	/**
	 * Init the mailer instance and call the notifications for the current filter.
	 *
	 * @internal
	 *
	 * @param string $filter Filter name.
	 * @param array  $args Email args (default: []).
	 */
	public static function send_queued_transactional_email( $filter = '', $args = array() ) {
		if ( apply_filters( 'communityservice_allow_send_queued_transactional_email', true, $filter, $args ) ) {
			self::instance(); // Init self so emails exist.

			do_action_ref_array( $filter . '_notification', $args );
		}
	}

	/**
	 * Init the mailer instance and call the notifications for the current filter.
	 *
	 * @internal
	 *
	 * @param array $args Email args (default: []).
	 */
	public static function send_transactional_email( $args = array() ) {
		try {
			$args = func_get_args();
			self::instance(); // Init self so emails exist.
			do_action_ref_array( current_filter() . '_notification', $args );
		} catch ( Exception $e ) {
			$error  = 'Transactional email triggered fatal error for callback ' . current_filter();
			$logger = cs_get_logger();
			$logger->critical(
				$error . PHP_EOL,
				array(
					'source' => 'transactional-emails',
				)
			);
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				trigger_error( $error, E_USER_WARNING ); // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped, WordPress.PHP.DevelopmentFunctions.error_log_trigger_error
			}
		}
	}

	/**
	 * Constructor for the email class hooks in all emails that can be sent.
	 */
	public function __construct() {
		$this->init();

		// Email Header, Footer and content hooks.
		add_action( 'communityservice_email_header', array( $this, 'email_header' ) );
		add_action( 'communityservice_email_footer', array( $this, 'email_footer' ) );
		add_action( 'communityservice_email_activity_details', array( $this, 'activity_details' ), 10, 4 );
		add_action( 'communityservice_email_student_details', array( $this, 'student_details' ), 10, 3 );
		add_action( 'communityservice_email_student_details', array( $this, 'email_addresses' ), 20, 3 );

		// Hooks for sending emails during store events.
		add_action( 'communityservice_task_on_backactivity_notification', array( $this, 'backactivity' ) );
		add_action( 'communityservice_created_student_notification', array( $this, 'student_new_account' ), 10, 3 );

		// Hook for replacing {site_title} in email-footer.
		add_filter( 'communityservice_email_footer_text', array( $this, 'email_footer_replace_site_title' ) );

		// Let 3rd parties unhook the above via this hook.
		do_action( 'communityservice_email', $this );
	}

	/**
	 * Init email classes.
	 */
	public function init() {
		// Include email classes.
		include_once dirname( __FILE__ ) . '/emails/class-cs-email.php';

		$this->emails['CS_Email_New_Activity']                 = include 'emails/class-cs-email-new-activity.php';
		$this->emails['CS_Email_Cancelled_Activity']           = include 'emails/class-cs-email-cancelled-activity.php';
		//$this->emails['CS_Email_Student_Processing_Activity'] = include 'emails/class-cs-email-student-processing-activity.php';
		$this->emails['CS_Email_Student_Approved_Activity']  = include 'emails/class-cs-email-student-approved-activity.php';
		$this->emails['CS_Email_Student_Reset_Password']   = include 'emails/class-cs-email-student-reset-password.php';
		$this->emails['CS_Email_Student_New_Account']      = include 'emails/class-cs-email-student-new-account.php';
		$this->emails['CS_Email_New_Blog']                 = include 'emails/class-cs-email-new-blog.php';

		$this->emails = apply_filters( 'communityservice_email_classes', $this->emails );
	}

	/**
	 * Return the email classes - used in admin to load settings.
	 *
	 * @return array
	 */
	public function get_emails() {
		return $this->emails;
	}

	/**
	 * Get from name for email.
	 *
	 * @return string
	 */
	public function get_from_name() {
		return wp_specialchars_decode( get_option( 'communityservice_email_from_name' ), ENT_QUOTES );
	}

	/**
	 * Get from email address.
	 *
	 * @return string
	 */
	public function get_from_address() {
		return sanitize_email( get_option( 'communityservice_email_from_address' ) );
	}

	/**
	 * Get the email header.
	 *
	 * @param mixed $email_heading Heading for the email.
	 */
	public function email_header( $email_heading ) {
		cs_get_template( 'emails/email-header.php', array( 'email_heading' => $email_heading ) );
	}

	/**
	 * Get the email footer.
	 */
	public function email_footer() {
		cs_get_template( 'emails/email-footer.php' );
	}

	/**
	 * Filter callback to replace {site_title} in email footer
	 *
	 * @since  3.3.0
	 * @param  string $string Email footer text.
	 * @return string         Email footer text with any replacements done.
	 */
	public function email_footer_replace_site_title( $string ) {
		return str_replace( '{site_title}', $this->get_blogname(), $string );
	}

	/**
	 * Wraps a message in the communityservice mail template.
	 *
	 * @param string $email_heading Heading text.
	 * @param string $message       Email message.
	 * @param bool   $plain_text    Set true to send as plain text. Default to false.
	 *
	 * @return string
	 */
	public function wrap_message( $email_heading, $message, $plain_text = false ) {
		// Buffer.
		ob_start();

		do_action( 'communityservice_email_header', $email_heading, null );

		echo wpautop( wptexturize( $message ) ); // WPCS: XSS ok.

		do_action( 'communityservice_email_footer', null );

		// Get contents.
		$message = ob_get_clean();

		return $message;
	}

	/**
	 * Send the email.
	 *
	 * @param mixed  $to          Receiver.
	 * @param mixed  $subject     Email subject.
	 * @param mixed  $message     Message.
	 * @param string $headers     Email headers (default: "Content-Type: text/html\r\n").
	 * @param string $attachments Attachments (default: "").
	 * @return bool
	 */
	public function send( $to, $subject, $message, $headers = "Content-Type: text/html\r\n", $attachments = '' ) {
		// Send.
		$email = new CS_Email();
		return $email->send( $to, $subject, $message, $headers, $attachments );
	}

	/**
	 * Prepare and send the student invoice email on demand.
	 *
	 * @param int|CS_Activity $activity Activity instance or ID.
	 */
	public function student_invoice( $activity ) {
		$email = $this->emails['CS_Email_Student_Invoice'];

		if ( ! is_object( $activity ) ) {
			$activity = cs_get_activity( absint( $activity ) );
		}

		$email->trigger( $activity->get_id(), $activity );
	}

	/**
	 * Student new account welcome email.
	 *
	 * @param int   $student_id        Student ID.
	 * @param array $new_student_data  New student data.
	 * @param bool  $password_generated If password is generated.
	 */
	public function student_new_account( $student_id, $new_student_data = array(), $password_generated = false ) {
		if ( ! $student_id ) {
			return;
		}

		$user_pass = ! empty( $new_student_data['user_pass'] ) ? $new_student_data['user_pass'] : '';

		$email = $this->emails['CS_Email_Student_New_Account'];
		$email->trigger( $student_id, $user_pass, $password_generated );
	}

	/**
	 * Show the activity details table
	 *
	 * @param CS_Activity $activity         Activity instance.
	 * @param bool     $sent_to_admin If should sent to admin.
	 * @param bool     $plain_text    If is plain text email.
	 * @param string   $email         Email address.
	 */
	public function activity_details( $activity, $sent_to_admin = false, $plain_text = false, $email = '' ) {
		if ( $plain_text ) {
			cs_get_template(
				'emails/plain/email-activity-details.php', array(
					'activity'         => $activity,
					'sent_to_admin' => $sent_to_admin,
					'plain_text'    => $plain_text,
					'email'         => $email,
				)
			);
		} else {
			cs_get_template(
				'emails/email-activity-details.php', array(
					'activity'         => $activity,
					'sent_to_admin' => $sent_to_admin,
					'plain_text'    => $plain_text,
					'email'         => $email,
				)
			);
		}
	}

	/**
	 * Is student detail field valid?
	 *
	 * @param  array $field Field data to check if is valid.
	 * @return boolean
	 */
	public function student_detail_field_is_valid( $field ) {
		return isset( $field['label'] ) && ! empty( $field['value'] );
	}

	/**
	 * Allows developers to add additional student details to templates.
	 *
	 *
	 * @param CS_Activity $activity         Activity instance.
	 * @param bool     $sent_to_admin If should sent to admin.
	 * @param bool     $plain_text    If is plain text email.
	 */
	public function student_details( $activity, $sent_to_admin = false, $plain_text = false ) {
		if ( ! is_a( $activity, 'CS_Activity' ) ) {
			return;
		}

		$fields = array_filter( apply_filters( 'communityservice_email_student_details_fields', array(), $sent_to_admin, $activity ), array( $this, 'student_detail_field_is_valid' ) );

		if ( ! empty( $fields ) ) {
			if ( $plain_text ) {
				cs_get_template( 'emails/plain/email-student-details.php', array( 'fields' => $fields ) );
			} else {
				cs_get_template( 'emails/email-student-details.php', array( 'fields' => $fields ) );
			}
		}
	}

	/**
	 * Get the email addresses.
	 *
	 * @param CS_Activity $activity         Activity instance.
	 * @param bool     $sent_to_admin If should sent to admin.
	 * @param bool     $plain_text    If is plain text email.
	 */
	public function email_addresses( $activity, $sent_to_admin = false, $plain_text = false ) {
		if ( ! is_a( $activity, 'CS_Activity' ) ) {
			return;
		}
		if ( $plain_text ) {
			cs_get_template(
				'emails/plain/email-addresses.php', array(
					'activity'         => $activity,
					'sent_to_admin' => $sent_to_admin,
				)
			);
		} else {
			cs_get_template(
				'emails/email-addresses.php', array(
					'activity'         => $activity,
					'sent_to_admin' => $sent_to_admin,
				)
			);
		}
	}

	/**
	 * Get blog name formatted for emails.
	 *
	 * @return string
	 */
	private function get_blogname() {
		return wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
	}

	/**
	 * Backactivity notification email.
	 *
	 * @param array $args Arguments.
	 */
	public function backactivity( $args ) {
		$args = wp_parse_args(
			$args, array(
				'task'  => '',
				'quantity' => '',
				'activity_id' => '',
			)
		);

		$activity = cs_get_activity( $args['activity_id'] );
		if (
			! $args['task'] ||
			! is_object( $args['task'] ) ||
			! $args['quantity'] ||
			! $activity
		) {
			return;
		}

		$subject = sprintf( '[%s] %s', $this->get_blogname(), __( 'Task backactivity', 'communityservice' ) );
		/* translators: 1: task quantity 2: task name 3: activity number */
		$message = sprintf( __( '%1$s units of %2$s have been backactivityed in activity #%3$s.', 'communityservice' ), $args['quantity'], html_entity_decode( strip_tags( $args['task']->get_formatted_name() ), ENT_QUOTES, get_bloginfo( 'charset' ) ), $activity->get_activity_number() );

		wp_mail(
			apply_filters( 'communityservice_email_recipient_backactivity', get_option( 'communityservice_stock_email_recipient' ), $args ),
			apply_filters( 'communityservice_email_subject_backactivity', $subject, $args ),
			apply_filters( 'communityservice_email_content_backactivity', $message, $args ),
			apply_filters( 'communityservice_email_headers', '', 'backactivity', $args ),
			apply_filters( 'communityservice_email_attachments', array(), 'backactivity', $args )
		);
	}
}