<?php
declare(strict_types=1);

namespace MintLMS\Infrastructure\Capability;

defined( 'ABSPATH' ) || exit;

final class CapabilityRegistrar {

	public function register(): void {
		foreach ( Roles::customRoleSlugs() as $slug ) {
			$this->ensureRole( $slug, Roles::displayName( $slug ), array( 'read' => true ) );
		}

		$this->syncRoleCaps( Roles::ADMINISTRATOR, Roles::administratorCaps() );
		$this->syncRoleCaps( Roles::MANAGER, Roles::managerCaps() );
		$this->syncRoleCaps( Roles::INSTRUCTOR, Roles::instructorCaps() );
		$this->syncRoleCaps( Roles::STUDENT, Roles::studentCaps() );
	}

	/**
	 * @param array<string, bool> $caps
	 */
	private function ensureRole( string $slug, string $label, array $caps ): void {
		if ( null === get_role( $slug ) ) {
			add_role( $slug, $label, $caps );
		}

		$this->syncRoleLabel( $slug, $label );
	}

	private function syncRoleLabel( string $slug, string $label ): void {
		$wp_roles = wp_roles();
		if ( ! isset( $wp_roles->roles[ $slug ] ) ) {
			return;
		}

		$stored = (string) ( $wp_roles->roles[ $slug ]['name'] ?? '' );
		if ( $stored === $label && ( $wp_roles->role_names[ $slug ] ?? '' ) === $label ) {
			return;
		}

		$wp_roles->roles[ $slug ]['name'] = $label;
		$wp_roles->role_names[ $slug ]    = $label;

		if ( $wp_roles->use_db ) {
			update_option( $wp_roles->role_key, $wp_roles->roles );
		}
	}

	/**
	 * @param array<string, bool> $granted
	 */
	private function syncRoleCaps( string $slug, array $granted ): void {
		$role = get_role( $slug );
		if ( null === $role ) {
			return;
		}

		foreach ( Roles::mintCapabilities() as $cap ) {
			if ( ! empty( $granted[ $cap ] ) ) {
				$role->add_cap( $cap );
			} else {
				$role->remove_cap( $cap );
			}
		}

		foreach ( $granted as $cap => $allow ) {
			if ( $allow ) {
				$role->add_cap( $cap );
			}
		}
	}
}
