<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

$showHeader    = $showHeader ?? true;
$pageLabel     = $pageLabel ?? '';
$headerActions = $headerActions ?? '';
$newCourseUrl  = $newCourseUrl ?? \MintLMS\Infrastructure\Admin\CourseBuilderPage::newCourseUrl();
$maxWidthClass = $maxWidthClass ?? 'mint-max-w-content';
$content       = $content ?? '';
$renderer      = new MintLMS\Infrastructure\Admin\ViewRenderer();
?>
<div class="wrap mint-lms-admin-wrap">
	<?php echo MintLMS\Infrastructure\Ui\UiRoot::open( 'admin' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<?php if ( $showHeader ) : ?>
			<?php
			include MINTLMS_PATH . 'views/admin/partials/admin-header.php';
			?>
		<?php endif; ?>
		<main class="mint-mx-auto mint-px-10 <?php echo esc_attr( $maxWidthClass ); ?>">
			<?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</main>
		<?php echo $renderer->component( 'toast' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	<?php echo MintLMS\Infrastructure\Ui\UiRoot::close(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
</div>
