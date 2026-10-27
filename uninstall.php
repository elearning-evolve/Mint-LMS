<?php
/**
 * Uninstall Mint LMS.
 *
 * @package MintLMS
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template partial variables.

require_once __DIR__ . '/vendor/autoload.php';

use MintLMS\Infrastructure\Database\Schema;
use MintLMS\Infrastructure\Setup\PageSettings;

$wpdb = function_exists( 'mintlms_wpdb' ) ? mintlms_wpdb() : null;
if ( null === $wpdb ) {
	return;
}

// phpcs:disable WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery -- Uninstall drops validated custom LMS tables.
foreach ( Schema::allTables( $wpdb->prefix ) as $mintlms_table ) {
	$mintlms_table = Schema::validateTable( $mintlms_table, $wpdb->prefix );
	$wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS %i', $mintlms_table ) );
}
// phpcs:enable WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery

delete_option( 'mintlms_db_version' );
delete_option( 'mintlms_cpt_content_migrated' );
delete_option( 'mintlms_first_run_complete' );
delete_option( 'mintlms_guided_course_complete' );
delete_option( 'mintlms_certificate_template' );

$pageIds = array(
	(int) get_option( PageSettings::OPTION_DASHBOARD, 0 ),
	(int) get_option( PageSettings::OPTION_CATALOG, 0 ),
	(int) get_option( PageSettings::OPTION_PLAYER, 0 ),
);

$deletePages = (bool) get_option( 'mintlms_uninstall_delete_pages', false );

delete_option( PageSettings::OPTION_DASHBOARD );
delete_option( PageSettings::OPTION_CATALOG );
delete_option( PageSettings::OPTION_PLAYER );
delete_option( PageSettings::OPTION_DEFAULT_ENROLLMENT );
delete_option( PageSettings::OPTION_EMAIL_ENROLL );
delete_option( PageSettings::OPTION_EMAIL_COMPLETE );
delete_option( 'mintlms_uninstall_delete_pages' );

if ( $deletePages ) {
	foreach ( array_unique( array_filter( $pageIds ) ) as $pageId ) {
		wp_delete_post( $pageId, true );
	}
}

// Remove CPT-backed LMS content.
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

$caps = array(
	'manage_mintlms',
	'edit_mintlms_courses',
	'edit_others_mintlms_courses',
	'enroll_mintlms_students',
	'view_mintlms_reports',
);

foreach ( array( 'administrator', 'mintlms_instructor' ) as $role_slug ) {
	$role = get_role( $role_slug );
	if ( null === $role ) {
		continue;
	}

	foreach ( $caps as $cap ) {
		$role->remove_cap( $cap );
	}
}
