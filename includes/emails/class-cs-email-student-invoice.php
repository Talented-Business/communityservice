<?php
/**
 * Class CS_Email_Customer_Invoice file.
 *
 * @package CommunityService\Emails
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'CS_Email_Customer_Invoice', false ) ) :

	/**
	 * Customer Invoice.
	 *
	 * An email sent to the customer via admin.
	 *
	 * @class       CS_Email_Customer_Invoice
	 * @version     3.5.0
	 * @package     CommunityService/Classes/Emails
	 * @extends     CS_Email
	 */
	class CS_Email_Customer_Invoice extends CS_Email {

		/**
		 * Constructor.
		 */
		public function __construct() {
			$this->id             = 'customer_invoice';
			$this->customer_email = true;
			$this->title          = __( 'Customer invoice / Activity details', 'communityservice' );
			$this->description    = __( 'Customer invoice emails can be sent to customers containing their activity information and payment links.', 'communityservice' );
			$this->template_html  = 'emails/customer-invoice.php';
			$this->template_plain = 'emails/plain/customer-invoice.php';
			$this->placeholders   = array(
				'{site_title}'   => $this->get_blogname(),
				'{activity_date}'   => '',
				'{activity_number}' => '',
			);

			// Call parent constructor.
			parent::__construct();

			$this->manual = true;
		}

		/**
		 * Get email subject.
		 *
		 * @param bool $paid Whether the activity has been paid or not.
		 * @since  3.1.0
		 * @return string
		 */
		public function get_default_subject( $paid = false ) {
			if ( $paid ) {
				return __( 'Invoice for activity #{activity_number} on {site_title}', 'communityservice' );
			} else {
				return __( 'Your latest {site_title} invoice', 'communityservice' );
			}
		}

		/**
		 * Get email heading.
		 *
		 * @param bool $paid Whether the activity has been paid or not.
		 * @since  3.1.0
		 * @return string
		 */
		public function get_default_heading( $paid = false ) {
			if ( $paid ) {
				return __( 'Invoice for activity #{activity_number}', 'communityservice' );
			} else {
				return __( 'Your invoice for activity #{activity_number}', 'communityservice' );
			}
		}

		/**
		 * Get email subject.
		 *
		 * @return string
		 */
		public function get_subject() {
			if ( $this->object->has_status( array( 'completed', 'processing' ) ) ) {
				$subject = $this->get_option( 'subject_paid', $this->get_default_subject( true ) );

				return apply_filters( 'communityservice_email_subject_customer_invoice_paid', $this->format_string( $subject ), $this->object );
			}

			$subject = $this->get_option( 'subject', $this->get_default_subject() );
			return apply_filters( 'communityservice_email_subject_customer_invoice', $this->format_string( $subject ), $this->object );
		}

		/**
		 * Get email heading.
		 *
		 * @return string
		 */
		public function get_heading() {
			if ( $this->object->has_status( cs_get_is_paid_statuses() ) ) {
				$heading = $this->get_option( 'heading_paid', $this->get_default_heading( true ) );
				return apply_filters( 'communityservice_email_heading_customer_invoice_paid', $this->format_string( $heading ), $this->object );
			}

			$heading = $this->get_option( 'heading', $this->get_default_heading() );
			return apply_filters( 'communityservice_email_heading_customer_invoice', $this->format_string( $heading ), $this->object );
		}

		/**
		 * Trigger the sending of this email.
		 *
		 * @param int      $activity_id The activity ID.
		 * @param CS_Activity $activity Activity object.
		 */
		public function trigger( $activity_id, $activity = false ) {
			$this->setup_locale();

			if ( $activity_id && ! is_a( $activity, 'CS_Activity' ) ) {
				$activity = cs_get_activity( $activity_id );
			}

			if ( is_a( $activity, 'CS_Activity' ) ) {
				$this->object                         = $activity;
				$this->recipient                      = $this->object->get_billing_email();
				$this->placeholders['{activity_date}']   = cs_format_datetime( $this->object->get_date_created() );
				$this->placeholders['{activity_number}'] = $this->object->get_activity_number();
			}

			if ( $this->get_recipient() ) {
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
				'subject'      => array(
					'title'       => __( 'Subject', 'communityservice' ),
					'type'        => 'text',
					'desc_tip'    => true,
					/* translators: %s: list of placeholders */
					'description' => sprintf( __( 'Available placeholders: %s', 'communityservice' ), '<code>{site_title}, {activity_date}, {activity_number}</code>' ),
					'placeholder' => $this->get_default_subject(),
					'default'     => '',
				),
				'heading'      => array(
					'title'       => __( 'Email heading', 'communityservice' ),
					'type'        => 'text',
					'desc_tip'    => true,
					/* translators: %s: list of placeholders */
					'description' => sprintf( __( 'Available placeholders: %s', 'communityservice' ), '<code>{site_title}, {activity_date}, {activity_number}</code>' ),
					'placeholder' => $this->get_default_heading(),
					'default'     => '',
				),
				'subject_paid' => array(
					'title'       => __( 'Subject (paid)', 'communityservice' ),
					'type'        => 'text',
					'desc_tip'    => true,
					/* translators: %s: list of placeholders */
					'description' => sprintf( __( 'Available placeholders: %s', 'communityservice' ), '<code>{site_title}, {activity_date}, {activity_number}</code>' ),
					'placeholder' => $this->get_default_subject( true ),
					'default'     => '',
				),
				'heading_paid' => array(
					'title'       => __( 'Email heading (paid)', 'communityservice' ),
					'type'        => 'text',
					'desc_tip'    => true,
					/* translators: %s: list of placeholders */
					'description' => sprintf( __( 'Available placeholders: %s', 'communityservice' ), '<code>{site_title}, {activity_date}, {activity_number}</code>' ),
					'placeholder' => $this->get_default_heading( true ),
					'default'     => '',
				),
				'email_type'   => array(
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

return new CS_Email_Customer_Invoice();
