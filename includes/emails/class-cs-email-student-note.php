<?php
/**
 * Class CS_Email_Student_Note file.
 *
 * @package CommunityService\Emails
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'CS_Email_Student_Note', false ) ) :

	/**
	 * Student Note Activity Email.
	 *
	 * Student note emails are sent when you add a note to an activity.
	 *
	 * @class       CS_Email_Student_Note
	 * @version     3.5.0
	 * @package     CommunityService/Classes/Emails
	 * @extends     CS_Email
	 */
	class CS_Email_Student_Note extends CS_Email {

		/**
		 * Student note.
		 *
		 * @var string
		 */
		public $student_note;

		/**
		 * Constructor.
		 */
		public function __construct() {
			$this->id             = 'student_note';
			$this->student_email = true;
			$this->title          = __( 'Student note', 'communityservice' );
			$this->description    = __( 'Student note emails are sent when you add a note to an activity.', 'communityservice' );
			$this->template_html  = 'emails/student-note.php';
			$this->template_plain = 'emails/plain/student-note.php';
			$this->placeholders   = array(
				'{site_title}'   => $this->get_blogname(),
				'{activity_date}'   => '',
				'{activity_number}' => '',
			);

			// Triggers.
			add_action( 'communityservice_new_student_note_notification', array( $this, 'trigger' ) );

			// Call parent constructor.
			parent::__construct();
		}

		/**
		 * Get email subject.
		 *
		 * @since  3.1.0
		 * @return string
		 */
		public function get_default_subject() {
			return __( 'Note added to your {site_title} activity from {activity_date}', 'communityservice' );
		}

		/**
		 * Get email heading.
		 *
		 * @since  3.1.0
		 * @return string
		 */
		public function get_default_heading() {
			return __( 'A note has been added to your activity', 'communityservice' );
		}

		/**
		 * Trigger.
		 *
		 * @param array $args Email arguments.
		 */
		public function trigger( $args ) {
			$this->setup_locale();

			if ( ! empty( $args ) ) {
				$defaults = array(
					'activity_id'      => '',
					'student_note' => '',
				);

				$args = wp_parse_args( $args, $defaults );

				$activity_id      = $args['activity_id'];
				$student_note = $args['student_note'];

				if ( $activity_id ) {
					$this->object = cs_get_activity( $activity_id );

					if ( $this->object ) {
						$this->recipient                      = $this->object->get_billing_email();
						$this->student_note                  = $student_note;
						$this->placeholders['{activity_date}']   = cs_format_datetime( $this->object->get_date_created() );
						$this->placeholders['{activity_number}'] = $this->object->get_activity_number();
					}
				}
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
					'student_note' => $this->student_note,
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
					'student_note' => $this->student_note,
					'sent_to_admin' => false,
					'plain_text'    => true,
					'email'         => $this,
				)
			);
		}
	}

endif;

return new CS_Email_Student_Note();
