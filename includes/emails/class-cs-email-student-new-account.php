<?php
/**
 * Class CS_Email_Student_New_Account file.
 *
 * @package CommunityService\Emails
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'CS_Email_Student_New_Account', false ) ) :

	/**
	 * Student New Account.
	 *
	 * An email sent to the student when they create an account.
	 *
	 * @class       CS_Email_Student_New_Account
	 * @version     1.0
	 * @package     CommunityService/Classes/Emails
	 * @extends     CS_Email
	 */
	class CS_Email_Student_New_Account extends CS_Email {

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
		 * User password.
		 *
		 * @var string
		 */
		public $user_pass;

		/**
		 * Is the password generated?
		 *
		 * @var bool
		 */
		public $password_generated;

		/**
		 * Constructor.
		 */
		public function __construct() {
			$this->id             = 'student_new_account';
			$this->student_email = true;
			$this->title          = __( 'New account', 'communityservice' );
			$this->description    = __( 'Student "new account" emails are sent to the student when a student signs up via checkout or account pages.', 'communityservice' );
			$this->template_html  = 'emails/student-new-account.php';
			$this->template_plain = 'emails/plain/student-new-account.php';

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
			return __( 'Your {site_title} account has been created!', 'communityservice' );
		}

		/**
		 * Get email heading.
		 *
		 * @since  3.1.0
		 * @return string
		 */
		public function get_default_heading() {
			return __( 'Welcome to {site_title}', 'communityservice' );
		}

		/**
		 * Trigger.
		 *
		 * @param int    $user_id User ID.
		 * @param string $user_pass User password.
		 * @param bool   $password_generated Whether the password was generated automatically or not.
		 */
		public function trigger( $user_id, $user_pass = '', $password_generated = false ) {
			$this->setup_locale();

			if ( $user_id ) {
				$this->object = new WP_User( $user_id );

				$this->user_pass          = $user_pass;
				$this->user_login         = stripslashes( $this->object->user_login );
				$this->user_email         = stripslashes( $this->object->user_email );
				$this->recipient          = $this->user_email;
				$this->password_generated = $password_generated;
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
					'email_heading'      => $this->get_heading(),
					'user_login'         => $this->user_login,
					'user_pass'          => $this->user_pass,
					'blogname'           => $this->get_blogname(),
					'password_generated' => $this->password_generated,
					'sent_to_admin'      => false,
					'plain_text'         => false,
					'email'              => $this,
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
					'email_heading'      => $this->get_heading(),
					'user_login'         => $this->user_login,
					'user_pass'          => $this->user_pass,
					'blogname'           => $this->get_blogname(),
					'password_generated' => $this->password_generated,
					'sent_to_admin'      => false,
					'plain_text'         => true,
					'email'              => $this,
				)
			);
		}
	}

endif;

return new CS_Email_Student_New_Account();
