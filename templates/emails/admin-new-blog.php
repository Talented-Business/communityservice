<?php
/**
 * Admin new blog email
 *
 * This template can be overridden by copying it to yourtheme/communityservice/emails/admin-new-blog.php.
 *
 * HOWEVER, on occasion CommunityService will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
 * @hooked CS_Emails::email_header() Output the email header
 */
do_action( 'communityservice_email_header', $email_heading, $email ); ?>

<?php /* translators: %s: Student billing full name */ ?>
<p><?php printf( __( 'New blog just has been published from administrator', 'communityservice' ) ); ?></p>
<p>You can read new blog <a href="<?= get_permalink( $blog->ID );?>">here</a></p>
<?php

/*
 * @hooked CS_Emails::blog_details() Shows the blog details table.
 * @hooked CS_Structured_Data::generate_blog_data() Generates structured data.
 * @hooked CS_Structured_Data::output_structured_data() Outputs structured data.
 * @since 2.5.0
 */
do_action( 'communityservice_email_blog_details', $blog, $sent_to_admin, $plain_text, $email );

/*
 * @hooked CS_Emails::blog_meta() Shows blog meta data.
 */
do_action( 'communityservice_email_blog_meta', $blog, $sent_to_admin, $plain_text, $email );

/*
 * @hooked CS_Emails::student_details() Shows student details
 * @hooked CS_Emails::email_address() Shows email address
 */
do_action( 'communityservice_email_student_details', $blog, $sent_to_admin, $plain_text, $email );

/*
 * @hooked CS_Emails::email_footer() Output the email footer
 */
do_action( 'communityservice_email_footer', $email );
