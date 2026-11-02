<?php
/**
 * Uninstall Mint LMS.
 *
 * @package MintLMS
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

require_once __DIR__ . '/vendor/autoload.php';

use MintLMS\Infrastructure\Database\Schema;
use MintLMS\Infrastructure\Setup\PageSettings;

/**
 * Run uninstall cleanup.
 */
function mintlms_run_uninstall(): void {
	$wpdb = function_exists( 'mintlms_wpdb' ) ? mintlms_wpdb() : null;
	if ( null === $wpdb ) {
		return;
	}

	foreach ( Schema::allTables( $wpdb->prefix ) as $mintlms_table ) {
		$mintlms_table = Schema::validateTable( $mintlms_table, $wpdb->prefix );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange -- Uninstall drops validated custom LMS tables.
		$wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS %i', $mintlms_table ) );
	}

	delete_option( 'mintlms_db_version' );
	delete_option( 'mintlms_cpt_content_migrated' );
	delete_option( 'mintlms_first_run_complete' );
	delete_option( 'mintlms_guided_course_complete' );
	delete_option( 'mintlms_certificate_template' );

	$mintlms_page_ids = array(
		(int) get_option( PageSettings::OPTION_DASHBOARD, 0 ),
		(int) get_option( PageSettings::OPTION_CATALOG, 0 ),
		(int) get_option( PageSettings::OPTION_PLAYER, 0 ),
	);

	$mintlms_delete_pages = (bool) get_option( 'mintlms_uninstall_delete_pages', false );

	delete_option( PageSettings::OPTION_DASHBOARD );
	delete_option( PageSettings::OPTION_CATALOG );
	delete_option( PageSettings::OPTION_PLAYER );
	delete_option( PageSettings::OPTION_DEFAULT_ENROLLMENT );
	delete_option( PageSettings::OPTION_EMAIL_ENROLL );
	delete_option( PageSettings::OPTION_EMAIL_COMPLETE );
	delete_option( 'mintlms_uninstall_delete_pages' );

	if ( $mintlms_delete_pages ) {
		foreach ( array_unique( array_filter( $mintlms_page_ids ) ) as $mintlms_page_id ) {
			wp_delete_post( $mintlms_page_id, true );
		}
	}

	$mintlms_cpt_ids = get_posts(
		array(
			'post_type'              => array( 'mint-course', 'mint-lesson', 'mint-quiz', 'mint-question' ),
			'post_status'            => 'any',
			'posts_per_page'         => -1,
			'fields'                 => 'ids',
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		)
	);

	foreach ( $mintlms_cpt_ids as $mintlms_post_id ) {
		wp_delete_post( (int) $mintlms_post_id, true );
	}

	remove_role( 'mintlms_instructor' );

	$mintlms_caps = array(
		'manage_mintlms',
		'edit_mintlms_courses',
		'edit_others_mintlms_courses',
		'enroll_mintlms_students',
		'view_mintlms_reports',
	);

	foreach ( array( 'administrator', 'mintlms_instructor' ) as $mintlms_role_slug ) {
		$mintlms_role = get_role( $mintlms_role_slug );
		if ( null === $mintlms_role ) {
			continue;
		}

		foreach ( $mintlms_caps as $mintlms_cap ) {
			$mintlms_role->remove_cap( $mintlms_cap );
		}
	}
}

mintlms_run_uninstall();
