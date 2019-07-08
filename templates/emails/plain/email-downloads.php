<?php
/**
 * Email Downloads.
 *
 * This template can be overridden by copying it to yourtheme/communityservice/emails/plain/email-downloads.php.
 *
 */

defined( 'ABSPATH' ) || exit;

echo esc_html( cs_strtoupper( __( 'Downloads', 'communityservice' ) ) ) . "\n\n";

foreach ( $downloads as $download ) {
	foreach ( $columns as $column_id => $column_name ) {
		echo wp_kses_post( $column_name ) . ': ';

		if ( has_action( 'communityservice_email_downloads_column_' . $column_id ) ) {
			do_action( 'communityservice_email_downloads_column_' . $column_id, $download, $plain_text );
		} else {
			switch ( $column_id ) {
				case 'download-task':
					echo esc_html( $download['task_name'] );
					break;
				case 'download-file':
					echo esc_html( $download['download_name'] ) . ' - ' . esc_url( $download['download_url'] );
					break;
				case 'download-expires':
					if ( ! empty( $download['access_expires'] ) ) {
						echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $download['access_expires'] ) ) );
					} else {
						esc_html_e( 'Never', 'communityservice' );
					}
					break;
			}
		}
		echo "\n";
	}
	echo "\n";
}
echo '=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=';
echo "\n\n";
