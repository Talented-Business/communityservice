<?php
/**
 * Class CS_Email_New_Activity file
 *
 * @package CommunityService\Emails
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'CS_Email_New_Activity' ) ) :

	/**
	 * New Activity Email.
	 *
	 * An email sent to the admin when a new activity is received/paid for.
	 *
	 * @class       CS_Email_New_Activity
	 * @version     2.0.0
	 * @package     CommunityService/Classes/Emails
	 * @extends     CS_Email
	 */
	class CS_Email_New_Activity extends CS_Email {

		/**
		 * Constructor.
		 */
		public function __construct() {
			$this->id             = 'new_activity';
			$this->title          = __( 'New activity', 'communityservice' );
			$this->description    = __( 'New activity emails are sent to chosen recipient(s) when a new activity is received.', 'communityservice' );
			$this->template_html  = 'emails/admin-new-activity.php';
			$this->template_plain = 'emails/plain/admin-new-activity.php';
			$this->placeholders   = array(
				'{site_title}'   => $this->get_blogname(),
				'{activity_date}'   => '',
				'{activity_number}' => '',
			);

			// Triggers for this email.
			add_action( 'communityservice_activity_status_pending_to_approved_notification', array( $this, 'trigger' ), 10, 2 );
			add_action( 'communityservice_activity_status_pending_to_on-hold_notification', array( $this, 'trigger' ), 10, 2 );
			add_action( 'communityservice_activity_status_cancelled_to_pending_notification', array( $this, 'trigger' ), 10, 2 );

			// Call parent constructor.
			parent::__construct();

			// Other settings.
			$this->recipient = $this->get_option( 'recipient', get_option( 'admin_email' ) );
		}

		/**
		 * Get email subject.
		 *
		 * @since  3.1.0
		 * @return string
		 */
		public function get_default_subject() {
			return __( '[{site_title}]: New activity #{activity_number}', 'communityservice' );
		}

		/**
		 * Get email heading.
		 *
		 * @since  3.1.0
		 * @return string
		 */
		public function get_default_heading() {
			return __( 'New Activity: #{activity_number}', 'communityservice' );
		}

		/**
		 * Trigger the sending of this email.
		 *
		 * @param int            $activity_id The activity ID.
		 * @param CS_Activity|false $activity Activity object.
		 */
		public function trigger( $activity_id, $activity = false ) {
			$this->setup_locale();
			
			if ( $activity_id && ! is_a( $activity, 'CS_Activity' ) ) {
				$activity = cs_get_activity( $activity_id );
			}

			if ( is_a( $activity, 'CS_Activity' ) ) {
				$this->object                         = $activity;
				$this->placeholders['{activity_date}']   = cs_format_datetime( $this->object->get_date_created() );
				$this->placeholders['{activity_number}'] = $this->object->get_activity_number();
			}

			if ( $this->is_enabled() && $this->get_recipient() ) {
				$this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
			}

			$this->restore_locale();
		}

		/**
		 * Get content html.
		 *
		 * @return string
		 */
		public function get_content_html() {
			return cs_get_template_html(
				$this->template_html, array(
					'activity'         => $this->object,
					'email_heading' => $this->get_heading(),
					'sent_to_admin' => true,
					'plain_text'    => false,
					'email'         => $this,
				)
			);
		}

		/**
		 * Get content plain.
		 *
		 * @return string
		 */
		public function get_content_plain() {
			return cs_get_template_html(
				$this->template_plain, array(
					'activity'         => $this->object,
					'email_heading' => $this->get_heading(),
					'sent_to_admin' => true,
					'plain_text'    => true,
					'email'         => $this,
				)
			);
		}

		/**
		 * Initialise settings form fields.
		 */
		public function init_form_fields() {
			$this->form_fields = array(
				'enabled'    => array(
					'title'   => __( 'Enable/Disable', 'communityservice' ),
					'type'    => 'checkbox',
					'label'   => __( 'Enable this email notification', 'communityservice' ),
					'default' => 'yes',
				),
				'recipient'  => array(
					'title'       => __( 'Recipient(s)', 'communityservice' ),
					'type'        => 'text',
					/* translators: %s: WP admin email */
					'description' => sprintf( __( 'Enter recipients (comma separated) for this email. Defaults to %s.', 'communityservice' ), '<code>' . esc_attr( get_option( 'admin_email' ) ) . '</code>' ),
					'placeholder' => '',
					'default'     => '',
					'desc_tip'    => true,
				),
				'subject'    => array(
					'title'       => __( 'Subject', 'communityservice' ),
					'type'        => 'text',
					'desc_tip'    => true,
					/* translators: %s: list of placeholders */
					'description' => sprintf( __( 'Available placeholders: %s', 'communityservice' ), '<code>{site_title}, {activity_date}, {activity_number}</code>' ),
					'placeholder' => $this->get_default_subject(),
					'default'     => '',
				),
				'heading'    => array(
					'title'       => __( 'Email heading', 'communityservice' ),
					'type'        => 'text',
					'desc_tip'    => true,
					/* translators: %s: list of placeholders */
					'description' => sprintf( __( 'Available placeholders: %s', 'communityservice' ), '<code>{site_title}, {activity_date}, {activity_number}</code>' ),
					'placeholder' => $this->get_default_heading(),
					'default'     => '',
				),
				'email_type' => array(
					'title'       => __( 'Email type', 'communityservice' ),
					'type'        => 'select',
					'description' => __( 'Choose which format of email to send.', 'communityservice' ),
					'default'     => 'html',
					'class'       => 'email_type cs-enhanced-select',
					'options'     => $this->get_email_type_options(),
					'desc_tip'    => true,
				),
			);
		}
	}

endif;

return new CS_Email_New_Activity();
