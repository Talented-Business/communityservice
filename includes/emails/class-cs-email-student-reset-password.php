<?php
/**
 * Class CS_Email_Student_Reset_Password file.
 *
 * @package CommunityService\Emails
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'CS_Email_Student_Reset_Password', false ) ) :

	/**
	 * Student Reset Password.
	 *
	 * An email sent to the student when they reset their password.
	 *
	 * @class       CS_Email_Student_Reset_Password
	 * @version     3.5.0
	 * @package     CommunityService/Classes/Emails
	 * @extends     CS_Email
	 */
	class CS_Email_Student_Reset_Password extends CS_Email {

		/**
		 * User ID.
		 *
		 * @var integer
		 */
		public $user_id;

		/**
		 * User login name.
		 *
		 * @var string
		 */
		public $user_login;

		/**
		 * User email.
		 *
		 * @var string
		 */
		public $user_email;

		/**
		 * Reset key.
		 *
		 * @var string
		 */
		public $reset_key;

		/**
		 * Constructor.
		 */
		public function __construct() {

			$this->id             = 'student_reset_password';
			$this->student_email = true;

			$this->title       = __( 'Reset password', 'communityservice' );
			$this->description = __( 'Student "reset password" emails are sent when students reset their passwords.', 'communityservice' );

			$this->template_html  = 'emails/student-reset-password.php';
			$this->template_plain = 'emails/plain/student-reset-password.php';

			// Trigger.
			add_action( 'communityservice_reset_password_notification', array( $this, 'trigger' ), 10, 2 );

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
			return __( 'Password reset request for {site_title}', 'communityservice' );
		}

		/**
		 * Get email heading.
		 *
		 * @since  3.1.0
		 * @return string
		 */
		public function get_default_heading() {
			return __( 'Password reset request', 'communityservice' );
		}

		/**
		 * Trigger.
		 *
		 * @param string $user_login User login.
		 * @param string $reset_key Password reset key.
		 */
		public function trigger( $user_login = '', $reset_key = '' ) {
			$this->setup_locale();

			if ( $user_login && $reset_key ) {
				$this->object     = get_user_by( 'login', $user_login );
				$this->user_id    = $this->object->ID;
				$this->user_login = $user_login;
				$this->reset_key  = $reset_key;
				$this->user_email = stripslashes( $this->object->user_email );
				$this->recipient  = $this->user_email;
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
					'email_heading' => $this->get_heading(),
					'user_id'       => $this->user_id,
					'user_login'    => $this->user_login,
					'reset_key'     => $this->reset_key,
					'blogname'      => $this->get_blogname(),
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
					'email_heading' => $this->get_heading(),
					'user_id'       => $this->user_id,
					'user_login'    => $this->user_login,
					'reset_key'     => $this->reset_key,
					'blogname'      => $this->get_blogname(),
					'sent_to_admin' => false,
					'plain_text'    => true,
					'email'         => $this,
				)
			);
		}
	}

endif;

return new CS_Email_Student_Reset_Password();
