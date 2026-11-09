<?php
declare(strict_types=1);

namespace MintLMS\Infrastructure\View;

defined( 'ABSPATH' ) || exit;

/**
 * Include a PHP template with named locals — no extract().
 *
 * Callers keep passing array( 'course' => $course ); templates still use $course.
 */
final class ScopedPhpTemplate {

	/**
	 * @param array<string, mixed> $vars
	 */
	public static function render( string $path, array $vars = array() ): string {
		if ( ! is_readable( $path ) ) {
			return '';
		}

		return ( static function ( string $mintlms_template_path, array $mintlms_template_vars ): string {
			$mintlms_reserved = array(
				'mintlms_template_path',
				'mintlms_template_vars',
				'mintlms_var_name',
				'mintlms_var_value',
				'mintlms_reserved',
			);

			foreach ( $mintlms_template_vars as $mintlms_var_name => $mintlms_var_value ) {
				if ( ! is_string( $mintlms_var_name ) ) {
					continue;
				}

				if ( 1 !== preg_match( '/^[a-zA-Z_][a-zA-Z0-9_]*$/', $mintlms_var_name ) ) {
					continue;
				}

				if ( in_array( $mintlms_var_name, $mintlms_reserved, true ) ) {
					continue;
				}

				// phpcs:ignore Squiz.PHP.DisallowMultipleAssignments.Found -- Intentional symbol table bind for templates.
				$$mintlms_var_name = $mintlms_var_value;
			}

			unset( $mintlms_template_vars, $mintlms_var_name, $mintlms_var_value, $mintlms_reserved );

			ob_start();
			include $mintlms_template_path;

			return (string) ob_get_clean();
		} )( $path, $vars );
	}
}
