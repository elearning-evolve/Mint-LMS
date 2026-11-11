<?php
declare(strict_types=1);

namespace MintLMS\Infrastructure\Admin;

use MintLMS\Infrastructure\View\ScopedPhpTemplate;

final class ViewRenderer {

	/**
	 * Render a view file and return the HTML string.
	 *
	 * @param array<string, mixed> $vars Variables available inside the view.
	 */
	public function render( string $view, array $vars = array() ): string {
		$viewPath = MINTLMS_PATH . 'views/' . ltrim( $view, '/' ) . '.php';

		return ScopedPhpTemplate::render( $viewPath, $vars );
	}

	/**
	 * Render a reusable component partial.
	 *
	 * @param array<string, mixed> $vars Variables passed to the component.
	 */
	public function component( string $name, array $vars = array() ): string {
		return $this->render( 'components/' . $name, $vars );
	}

	/**
	 * Render a view and echo the result.
	 *
	 * @param array<string, mixed> $vars Variables available inside the view.
	 */
	public function echo( string $view, array $vars = array() ): void {
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Views/components escape their own output.
		echo $this->render( $view, $vars );
	}
}
