<?php
/**
 * Uninstall Mint LMS.
 *
 * Data wipe is opt-in (Settings → Data & uninstall). Default keeps courses,
 * enrollments, and options so a delete/reinstall does not destroy learning data.
 *
 * @package MintLMS
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

$mintlms_autoload = __DIR__ . '/vendor/autoload.php';
if ( ! is_readable( $mintlms_autoload ) ) {
	return;
}

require_once $mintlms_autoload;

use MintLMS\Infrastructure\Admin\FirstRunState;
use MintLMS\Infrastructure\Database\Schema;
use MintLMS\Infrastructure\Setup\PageSettings;
use MintLMS\Infrastructure\WooCommerce\EnrollmentBridge;

/**
 * Whether this site opted into deleting all Mint LMS data on uninstall.
 */
function mintlms_uninstall_should_delete_data(): bool {
	return (bool) get_option( PageSettings::OPTION_DELETE_DATA_ON_UNINSTALL, false );
}

/**
 * Drop Mint LMS custom tables for the current blog prefix.
 *
 * @param \wpdb $wpdb WordPress database.
 */
function mintlms_uninstall_drop_tables( \wpdb $wpdb ): void {
	foreach ( Schema::allTables( $wpdb->prefix ) as $mintlms_table ) {
		try {
			$mintlms_table = Schema::validateTable( $mintlms_table, $wpdb->prefix );
		} catch ( \InvalidArgumentException $exception ) {
			continue;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange -- Uninstall drops validated custom LMS tables.
		$wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS %i', $mintlms_table ) );
	}
}

/**
 * Delete Mint LMS CPT posts (courses, lessons, quizzes, questions).
 */
function mintlms_uninstall_delete_content_posts(): void {
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
}

/**
 * Delete configured student pages when wiping data.
 */
function mintlms_uninstall_delete_student_pages(): void {
	$page_ids = array_unique(
		array_filter(
			array(
				(int) get_option( PageSettings::OPTION_DASHBOARD, 0 ),
				(int) get_option( PageSettings::OPTION_CATALOG, 0 ),
				(int) get_option( PageSettings::OPTION_PLAYER, 0 ),
			)
		)
	);

	foreach ( $page_ids as $page_id ) {
		wp_delete_post( $page_id, true );
	}
}

/**
 * Remove plugin options for the current site.
 */
function mintlms_uninstall_delete_options(): void {
	$options = array(
		'mintlms_db_version',
		'mintlms_cpt_content_migrated',
		FirstRunState::OPTION_FIRST_RUN_COMPLETE,
		FirstRunState::OPTION_GUIDED_COURSE_COMPLETE,
		'mintlms_certificate_template',
		'mintlms_ai_enabled',
		'mintlms_ai_provider',
		'mintlms_ai_model',
		'mintlms_ai_api_key_enc',
		'mintlms_ai_api_key_hint',
		PageSettings::OPTION_DASHBOARD,
		PageSettings::OPTION_CATALOG,
		PageSettings::OPTION_PLAYER,
		PageSettings::OPTION_DEFAULT_ENROLLMENT,
		PageSettings::OPTION_EMAIL_ENROLL,
		PageSettings::OPTION_EMAIL_COMPLETE,
		PageSettings::OPTION_DELETE_DATA_ON_UNINSTALL,
		'mintlms_uninstall_delete_pages', // Legacy unused flag.
		'mintlms_wc_cancel_on_refund',
		'mintlms_wc_enroll_statuses',
	);

	foreach ( $options as $option ) {
		delete_option( $option );
	}
}

/**
 * Remove Mint LMS post/user meta left outside CPT deletion (products, pages, WC orders).
 *
 * @param \wpdb $wpdb WordPress database.
 */
function mintlms_uninstall_delete_meta( \wpdb $wpdb ): void {
	$post_meta_keys = array(
		EnrollmentBridge::META_COURSE_ID,
		EnrollmentBridge::ORDER_META_ENROLLMENTS,
		EnrollmentBridge::ORDER_META_PROCESSED,
		'_mintlms_page_role',
	);

	foreach ( $post_meta_keys as $meta_key ) {
		delete_metadata( 'post', 0, $meta_key, '', true );
	}

	delete_metadata( 'user', 0, FirstRunState::USER_META_WELCOME_DISMISSED, '', true );

	// WooCommerce HPOS order meta (when present).
	$orders_meta_table = $wpdb->prefix . 'wc_orders_meta';
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Uninstall inspects optional WC table.
	$table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $orders_meta_table ) );
	if ( $orders_meta_table === $table_exists ) {
		$hpos_keys = array(
			EnrollmentBridge::ORDER_META_ENROLLMENTS,
			EnrollmentBridge::ORDER_META_PROCESSED,
		);
		foreach ( $hpos_keys as $meta_key ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Uninstall cleanup of HPOS meta.
			$wpdb->delete( $orders_meta_table, array( 'meta_key' => $meta_key ), array( '%s' ) );
		}
	}
}

/**
 * Remove Mint LMS roles and capabilities from remaining roles.
 */
function mintlms_uninstall_remove_roles_and_caps(): void {
	remove_role( 'mintlms_manager' );
	remove_role( 'mintlms_instructor' );
	remove_role( 'mintlms_student' );

	$caps = array(
		'manage_mintlms',
		'edit_mintlms_courses',
		'edit_others_mintlms_courses',
		'enroll_mintlms_students',
		'view_mintlms_reports',
		'take_mintlms_courses',
	);

	foreach ( array( 'administrator', 'editor' ) as $role_slug ) {
		$role = get_role( $role_slug );
		if ( null === $role ) {
			continue;
		}

		foreach ( $caps as $cap ) {
			$role->remove_cap( $cap );
		}
	}
}

/**
 * Full wipe for the current blog (only when opted in).
 */
function mintlms_uninstall_site(): void {
	if ( ! mintlms_uninstall_should_delete_data() ) {
		return;
	}

	global $wpdb;

	if ( ! $wpdb instanceof \wpdb ) {
		return;
	}

	mintlms_uninstall_delete_student_pages();
	mintlms_uninstall_delete_content_posts();
	mintlms_uninstall_drop_tables( $wpdb );
	mintlms_uninstall_delete_meta( $wpdb );
	mintlms_uninstall_delete_options();
	mintlms_uninstall_remove_roles_and_caps();
}

/**
 * Entry: run per-site on multisite, once on single site.
 */
function mintlms_run_uninstall(): void {
	if ( is_multisite() ) {
		$site_ids = get_sites(
			array(
				'fields' => 'ids',
				'number' => 0,
			)
		);

		foreach ( $site_ids as $site_id ) {
			switch_to_blog( (int) $site_id );
			mintlms_uninstall_site();
			restore_current_blog();
		}

		return;
	}

	mintlms_uninstall_site();
}

mintlms_run_uninstall();
