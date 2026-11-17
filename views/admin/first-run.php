<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

use MintLMS\Infrastructure\Admin\FirstRunRedirect;
?>
<style>
	/* First-run fills the admin content pane; design keeps chrome footer out of the composition. */
	body.toplevel_page_mint-lms #wpcontent { padding-bottom: 0; }
	body.toplevel_page_mint-lms #wpbody-content { padding-bottom: 0 !important; float: none; }
	body.toplevel_page_mint-lms #wpfooter { display: none; }
	body.toplevel_page_mint-lms .mint-lms-admin-wrap.wrap {
		margin: 0 !important;
		padding: 0 !important;
		max-width: none;
	}
</style>
<div class="wrap mint-lms-admin-wrap mint-m-0 mint-p-0">
	<?php echo MintLMS\Infrastructure\Ui\UiRoot::open( 'admin' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<?php
		require MINTLMS_PATH . 'views/admin/partials/welcome-split.php';
		?>
	<?php echo MintLMS\Infrastructure\Ui\UiRoot::close(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
</div>
