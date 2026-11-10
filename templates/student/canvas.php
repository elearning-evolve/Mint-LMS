<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

$content = isset( $args['content'] ) && is_string( $args['content'] ) ? $args['content'] : '';
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
	<style id="mint-lms-canvas-chrome">
		html, body.mint-lms-canvas { margin: 0; background: #f3f1fb; }
		body.mint-lms-canvas { min-height: 100vh; }
	</style>
</head>
<body <?php body_class(); ?>>
<?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built from escaped student templates. ?>
<?php wp_footer(); ?>
</body>
</html>
