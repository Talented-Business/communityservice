<?php
/**
 * Class CS_Email_Student_Completed_Activity file.
 *
 * @package CommunityService\Emails
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'CS_Email_Student_Completed_Activity', false ) ) :

	/**
	 * Student Completed Activity Email.
	 *
	 * Activity complete emails are sent to the student when the activity is marked complete and usual indicates that the activity has been shipped.
	 *
	 * @class       CS_Email_Student_Completed_Activity
	 * @version     2.0.0
	 * @package     CommunityService/Classes/Emails
	 * @extends     CS_Email
	 */
	class CS_Email_Student_Completed_Activity extends CS_Email {

		/**
		 * Constructor.
		 */
		public function __construct() {
			$this->id             = 'student_approved_activity';
			$this->student_email = true;
			$this->title          = __( 'Completed activity', 'communityservice' );
			$this->description    = __( 'Activity complete emails are sent to students when their activities are marked approved and usually indicate that their activities have been shipped.', 'communityservice' );
			$this->template_html  = 'emails/student-approved-activity.php';
			$this->template_plain = 'emails/plain/student-approved-activity.php';
			$this->placeholders   = array(
				'{site_title}'   => $this->get_blogname(),
				'{activity_date}'   => '',
				'{activity_number}' => '',
			);

			// Triggers for this email.
			add_action( 'communityservice_activity_status_approved_notification', array( $this, 'trigger' ), 10, 2 );
			add_action( 'communityservice_activity_status_cancelled_to_approved_notification', array( $this, 'trigger' ), 10, 2 );

			// Call parent constructor.
			parent::__construct();
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
				$this->recipient                      = $this->object->get_student()->user_email;
				$this->placeholders['{activity_date}']   = cs_format_datetime( $this->object->get_date_created() );
				$this->placeholders['{activity_number}'] = $this->object->get_activity_number();
			}

			if ( $this->is_enabled() && $this->get_recipient() ) {
				$this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
			}

			$this->restore_locale();
		}

		/**
		 * Get email subject.
		 *
		 * @since  3.1.0
		 * @return string
		 */
		public function get_default_subject() {
			return __( 'Your {site_title} activity is now complete', 'communityservice' );
		}

		/**
		 * Get email heading.
		 *
		 * @since  3.1.0
		 * @return string
		 */
		public function get_default_heading() {
			return __( 'Your Activity has been approved', 'communityservice' );
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
					'sent_to_admin' => false,
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
					'sent_to_admin' => false,
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

return new CS_Email_Student_Completed_Activity();
