<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- View/template variables scoped to this file.

use MintLMS\Infrastructure\Ai\McpAdapterNotice;

$mcp_active    = McpAdapterNotice::isMcpAdapterActive();
$mcp_install   = self_admin_url( 'plugin-install.php?s=MCP%20Adapter&tab=search&type=term' );
$app_passwords = admin_url( 'profile.php#application-passwords-section' );
$mcp_endpoint  = rest_url( 'mcp/mcp-adapter-default-server' );
$mcp_sample    = __( 'Create a draft Mint LMS course about WordPress basics with 2 lessons and 1 quiz.', 'mint-lms' );
?>
<div class="mint-settings mint-ai-page">
	<div class="mint-settings__hero mint-ai-page__hero">
		<div class="mint-ai-page__hero-icon" aria-hidden="true">
			<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#0F0E1A" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
				<path d="M12 3v3M12 18v3M3 12h3M18 12h3"/>
				<path d="M7.5 7.5 9.5 9.5M14.5 14.5l2 2M16.5 7.5 14.5 9.5M9.5 14.5l-2 2"/>
				<circle cx="12" cy="12" r="3.2"/>
			</svg>
		</div>
		<div>
			<h1 class="mint-settings__title"><?php esc_html_e( 'AI Assistant', 'mint-lms' ); ?></h1>
			<p class="mint-settings__sub"><?php esc_html_e( 'Create Mint LMS drafts from Claude Desktop (MCP). Items stay as drafts for review — never WordPress pages or posts.', 'mint-lms' ); ?></p>
		</div>
	</div>

	<div class="mint-ai-page__primary">
		<section class="mint-settings-card mint-ai-page__mcp" id="mintlms-ai-mcp">
			<header class="mint-settings-card__head">
				<h2 class="mint-settings-card__title"><?php esc_html_e( 'Claude Desktop (MCP)', 'mint-lms' ); ?></h2>
				<p class="mint-settings-card__desc"><?php esc_html_e( 'Prompt in Claude Desktop; Mint LMS abilities create draft courses, lessons, quizzes, and questions on this site.', 'mint-lms' ); ?></p>
			</header>
			<div class="mint-settings-card__body">
				<?php if ( ! $mcp_active ) : ?>
					<div class="notice notice-error inline mint-ai-page__mcp-status">
						<p>
							<?php
							printf(
								/* translators: %s: MCP Adapter install URL */
								wp_kses_post( __( 'MCP Adapter is not active. <a href="%s">Install or activate MCP Adapter</a> to use AI Assistance with Claude Desktop. Mint LMS still works without it.', 'mint-lms' ) ),
								esc_url( $mcp_install )
							);
							?>
						</p>
					</div>
				<?php else : ?>
					<p class="mint-settings-field__help mint-ai-page__mcp-status"><?php esc_html_e( 'MCP Adapter is active. Connect Claude Desktop with an Application Password.', 'mint-lms' ); ?></p>
				<?php endif; ?>

				<ol class="mint-ai-page__steps">
					<li>
						<strong><?php esc_html_e( 'Install MCP Adapter', 'mint-lms' ); ?></strong>
						<span><?php esc_html_e( 'Plugins → Add New → search “MCP Adapter” (WordPress.org). Mint LMS works alone; MCP is only for external AI.', 'mint-lms' ); ?></span>
					</li>
					<li>
						<strong><?php esc_html_e( 'Create an Application Password', 'mint-lms' ); ?></strong>
						<span>
							<?php
							printf(
								/* translators: %s: profile URL */
								wp_kses_post( __( 'In your <a href="%s">WordPress profile</a>, create an Application Password for Claude. Use a user who can edit Mint LMS content.', 'mint-lms' ) ),
								esc_url( $app_passwords )
							);
							?>
						</span>
					</li>
					<li>
						<strong><?php esc_html_e( 'Point Claude Desktop at this site', 'mint-lms' ); ?></strong>
						<span><?php esc_html_e( 'Add an MCP server entry with this site’s MCP URL (copy below), your WordPress username, and the Application Password.', 'mint-lms' ); ?></span>
						<div class="mint-ai-page__endpoint">
							<label class="mint-settings-field__label" for="mintlms_mcp_endpoint"><?php esc_html_e( 'MCP endpoint', 'mint-lms' ); ?></label>
							<div class="mint-ai-page__endpoint-row">
								<input
									type="text"
									id="mintlms_mcp_endpoint"
									class="mint-settings-input mint-ai-page__endpoint-input"
									readonly
									value="<?php echo esc_attr( $mcp_endpoint ); ?>"
									onclick="this.select();"
								/>
								<button
									type="button"
									id="mintlms_mcp_endpoint_copy"
									class="mint-settings-btn mint-settings-btn--primary mint-ai-page__endpoint-copy"
									aria-label="<?php echo esc_attr__( 'Copy MCP endpoint', 'mint-lms' ); ?>"
									data-label-copy="<?php echo esc_attr__( 'Copy', 'mint-lms' ); ?>"
									data-label-copied="<?php echo esc_attr__( 'Copied', 'mint-lms' ); ?>"
									data-label-copy-aria="<?php echo esc_attr__( 'Copy MCP endpoint', 'mint-lms' ); ?>"
								>
									<?php esc_html_e( 'Copy', 'mint-lms' ); ?>
								</button>
							</div>
						</div>
					</li>
					<li>
						<strong><?php esc_html_e( 'Prompt in Claude Desktop', 'mint-lms' ); ?></strong>
						<span><?php esc_html_e( 'Ask Claude to create a Mint LMS course, standalone lesson, or quiz. Items appear as drafts in Mint LMS lists — not as pages/posts.', 'mint-lms' ); ?></span>
						<div class="mint-ai-page__sample">
							<span class="mint-ai-page__sample-label"><?php esc_html_e( 'Example prompt', 'mint-lms' ); ?></span>
							<p class="mint-ai-page__sample-text"><?php echo esc_html( $mcp_sample ); ?></p>
						</div>
					</li>
				</ol>
			</div>
		</section>

		<section class="mint-settings-card mint-ai-page__safety">
			<header class="mint-settings-card__head">
				<h2 class="mint-settings-card__title"><?php esc_html_e( 'What AI does here', 'mint-lms' ); ?></h2>
				<p class="mint-settings-card__desc"><?php esc_html_e( 'Mint-only drafts for review. Nothing publishes automatically.', 'mint-lms' ); ?></p>
			</header>
			<div class="mint-settings-card__body">
				<ul class="mint-ai-page__bullets">
					<li><?php esc_html_e( 'Claude Desktop creates Mint LMS drafts (courses, lessons, quizzes, questions) via MCP.', 'mint-lms' ); ?></li>
					<li><?php esc_html_e( 'Never creates or edits WordPress pages, posts, menus, media, or other plugins.', 'mint-lms' ); ?></li>
					<li><?php esc_html_e( 'New items are saved as drafts in the Mint LMS list tables for review.', 'mint-lms' ); ?></li>
				</ul>
			</div>
		</section>
	</div>
</div>
