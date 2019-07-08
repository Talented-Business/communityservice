<?php
/**
 * Class CS_Email_New_Blog file
 *
 * @package CommunityService\Emails
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'CS_Email_New_Blog' ) ) :

	/**
	 * New Blog Email.
	 *
	 * An email sent to the admin when a new blog is received/paid for.
	 *
	 * @class       CS_Email_New_Blog
	 * @version     2.0.0
	 * @package     CommunityService/Classes/Emails
	 * @extends     CS_Email
	 */
	class CS_Email_New_Blog extends CS_Email {

		/**
		 * Constructor.
		 */
		public function __construct() {
			$this->id             = 'new_blog';
			$this->title          = __( 'New blog', 'communityservice' );
			$this->description    = __( 'New blog emails are sent to chosen recipient(s) when a new blog is received.', 'communityservice' );
			$this->template_html  = 'emails/admin-new-blog.php';
			$this->template_plain = 'emails/plain/admin-new-blog.php';
			$this->placeholders   = array(
				'{site_title}'   => $this->get_blogname(),
				'{blog_date}'   => '',
				'{blog_number}' => '',
			);

			// Triggers for this email.
			add_action( 'communityservice_blog_status_pending_to_approved_notification', array( $this, 'trigger' ), 10, 2 );
			add_action( 'communityservice_blog_status_pending_to_on-hold_notification', array( $this, 'trigger' ), 10, 2 );
			add_action( 'communityservice_blog_status_cancelled_to_pending_notification', array( $this, 'trigger' ), 10, 2 );

			// Call parent constructor.
			parent::__construct();

			// Other settings.
			$this->recipient = true;
		}

		/**
		 * Get email subject.
		 *
		 * @since  3.1.0
		 * @return string
		 */
		public function get_default_subject() {
			return __( '[{site_title}]: New blog #{blog_number}', 'communityservice' );
		}

		/**
		 * Get email heading.
		 *
		 * @since  3.1.0
		 * @return string
		 */
		public function get_default_heading() {
			return __( 'New Blog: #{blog_number}', 'communityservice' );
		}

		/**
		 * Trigger the sending of this email.
		 *
		 * @param int            $blog_id The blog ID.
		 * @param CS_Blog|false $blog Blog object.
		 */
		public function trigger( $blog_id, $blog = false ) {
			$this->setup_locale();
			

            $this->object                         = $blog;
            $this->placeholders['{blog_date}']   = cs_format_datetime( $this->object->post_date );
            $this->placeholders['{blog_number}'] = $this->object->ID;
            $users = get_users();
            $recipients = array();
            $admin_email = get_option('admin_email');
            foreach($users as $user){
                if($admin_email !== $user->user_email)$recipients[] = $user->user_email;
            }
			if ( $this->is_enabled() ) {
				$this->send( $recipients, $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
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
					'blog'         => $this->object,
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
					'blog'         => $this->object,
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
					'description' => sprintf( __( 'Available placeholders: %s', 'communityservice' ), '<code>{site_title}, {blog_date}, {blog_number}</code>' ),
					'placeholder' => $this->get_default_subject(),
					'default'     => '',
				),
				'heading'    => array(
					'title'       => __( 'Email heading', 'communityservice' ),
					'type'        => 'text',
					'desc_tip'    => true,
					/* translators: %s: list of placeholders */
					'description' => sprintf( __( 'Available placeholders: %s', 'communityservice' ), '<code>{site_title}, {blog_date}, {blog_number}</code>' ),
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

return new CS_Email_New_Blog();
