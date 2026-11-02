<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

use MintLMS\Infrastructure\Admin\ViewRenderer;
use MintLMS\Infrastructure\Ai\AiSettings;
use MintLMS\Infrastructure\Setup\PageSettings;

/** @var ViewRenderer $renderer */
/** @var PageSettings $pageSettings */
/** @var list<array{key: string, label: string, done: bool, detail: string}> $checklist */

$dashboardId     = $pageSettings->getDashboardPageId();
$catalogId       = $pageSettings->getCatalogPageId();
$playerId        = $pageSettings->getPlayerPageId();
$certTemplate    = (string) get_option( 'mintlms_certificate_template', '' );
$aiSettings      = new AiSettings();
$aiStatus        = $aiSettings->status();
$aiKeyFieldValue = $aiSettings->hasApiKey() ? AiSettings::MASK_PLACEHOLDER : '';
$aiUsesConstant  = in_array( (string) $aiStatus['key_source'], array( 'constant', 'env' ), true );
$doneCount       = count( array_filter( $checklist, static fn( array $item ): bool => $item['done'] ) );
$totalCount      = count( $checklist );
$pagesCreated    = isset( $_GET['mintlms_pages'] ) && '1' === sanitize_text_field( wp_unslash( (string) $_GET['mintlms_pages'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

$cardClass   = 'mint-rounded-xl mint-border mint-border-tint-line mint-bg-tint mint-px-7 mint-py-6';
$fieldSelect = 'mint-block mint-w-full mint-rounded-lg mint-border mint-border-control mint-bg-bg mint-px-[14px] mint-py-[11px] mint-text-base mint-text-ink hover:mint-border-mint-blue focus:mint-border-mint-blue focus:mint-shadow-focus focus:mint-outline-none';
?>
<div class="mint-py-10 mint-pb-24">
	<?php if ( $pagesCreated ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Student pages created or updated.', 'mint-lms' ); ?></p></div>
	<?php endif; ?>
	<div class="mint-mb-2">
		<h1 class="mint-m-0 mint-text-[48px] mint-font-semibold mint-leading-[52px] mint-tracking-[-0.04em] mint-text-ink"><?php esc_html_e( 'Settings', 'mint-lms' ); ?></h1>
		<p class="mint-mt-[10px] mint-text-[19px] mint-leading-7 mint-text-ink-2">
			<?php esc_html_e( 'Configure student-facing pages and LMS defaults.', 'mint-lms' ); ?>
		</p>
	</div>

	<div class="<?php echo esc_attr( $cardClass ); ?> mint-mb-0">
		<div class="mint-mb-1 mint-flex mint-items-center mint-justify-between mint-gap-4">
			<h2 class="mint-m-0 mint-text-[20px] mint-font-semibold mint-leading-7 mint-tracking-tight mint-text-ink"><?php esc_html_e( 'Setup checklist', 'mint-lms' ); ?></h2>
			<span class="mint-inline-flex mint-items-center mint-rounded-md mint-bg-bg-subtle mint-px-[11px] mint-py-[5px] mint-text-sm mint-font-bold mint-text-ink-2">
				<?php
				printf(
					/* translators: 1: completed items, 2: total items */
					esc_html__( '%1$d / %2$d complete', 'mint-lms' ),
					absint( $doneCount ),
					absint( $totalCount )
				);
				?>
			</span>
		</div>
		<ul class="mint-mt-2 mint-flex mint-flex-col">
			<?php foreach ( $checklist as $item ) : ?>
				<li class="mint-flex mint-items-start mint-gap-3 mint-border-t mint-border-rule-soft mint-py-[14px] first:mint-border-t-0 first:mint-pt-1">
					<span class="mint-mt-px mint-h-5 mint-w-5 mint-shrink-0 <?php echo $item['done'] ? 'mint-text-success' : 'mint-text-ink-3'; ?>" aria-hidden="true">
						<?php if ( $item['done'] ) : ?>
							<svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
						<?php else : ?>
							<svg width="16" height="16" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2"><circle cx="10" cy="10" r="8"/></svg>
						<?php endif; ?>
					</span>
					<div class="mint-min-w-0">
						<div class="mint-text-sm mint-font-semibold mint-text-ink"><?php echo esc_html( $item['label'] ); ?></div>
						<div class="mint-mt-[2px] mint-text-sm mint-leading-5 mint-text-ink-3"><?php echo esc_html( $item['detail'] ); ?></div>
					</div>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>

	<form id="mintlms-create-pages-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" hidden>
		<?php wp_nonce_field( 'mintlms_create_pages' ); ?>
		<input type="hidden" name="action" value="mintlms_create_pages" />
	</form>

	<form method="post" action="options.php" class="mint-mt-6 mint-flex mint-flex-col mint-gap-5">
		<?php settings_fields( 'mintlms_settings' ); ?>

		<div class="<?php echo esc_attr( $cardClass ); ?>">
			<h2 class="mint-m-0 mint-text-[20px] mint-font-semibold mint-leading-7 mint-tracking-tight mint-text-ink"><?php esc_html_e( 'Student pages', 'mint-lms' ); ?></h2>
			<p class="mint-mt-2 mint-text-sm mint-leading-[22px] mint-text-ink-2"><?php esc_html_e( 'Choose which WordPress pages host Mint LMS shortcodes. Pages are created automatically on activation.', 'mint-lms' ); ?></p>

			<div class="mint-mt-5 mint-flex mint-flex-col mint-gap-5">
				<div class="mint-flex mint-flex-col mint-gap-2">
					<label for="mintlms_page_dashboard" class="mint-text-sm mint-font-semibold mint-text-ink"><?php esc_html_e( 'Dashboard page', 'mint-lms' ); ?></label>
					<?php
					wp_dropdown_pages(
						array(
							'name'              => esc_attr( PageSettings::OPTION_DASHBOARD ),
							'id'                => 'mintlms_page_dashboard',
							'selected'          => absint( $dashboardId ),
							'show_option_none'  => esc_html__( '— Select —', 'mint-lms' ),
							'option_none_value' => '0',
							'class'             => esc_attr( $fieldSelect ),
						)
					);
					?>
					<p class="mint-text-sm mint-text-ink-3"><?php esc_html_e( 'Uses [mint_lms_dashboard] — student home and continue learning.', 'mint-lms' ); ?></p>
				</div>

				<div class="mint-flex mint-flex-col mint-gap-2">
					<label for="mintlms_page_catalog" class="mint-text-sm mint-font-semibold mint-text-ink"><?php esc_html_e( 'Course catalog page', 'mint-lms' ); ?></label>
					<?php
					wp_dropdown_pages(
						array(
							'name'              => esc_attr( PageSettings::OPTION_CATALOG ),
							'id'                => 'mintlms_page_catalog',
							'selected'          => absint( $catalogId ),
							'show_option_none'  => esc_html__( '— Select —', 'mint-lms' ),
							'option_none_value' => '0',
							'class'             => esc_attr( $fieldSelect ),
						)
					);
					?>
					<p class="mint-text-sm mint-text-ink-3"><?php esc_html_e( 'Uses [mint_lms_catalog] — browse courses and view course details.', 'mint-lms' ); ?></p>
				</div>

				<div class="mint-flex mint-flex-col mint-gap-2">
					<label for="mintlms_page_player" class="mint-text-sm mint-font-semibold mint-text-ink"><?php esc_html_e( 'Course player page', 'mint-lms' ); ?></label>
					<?php
					wp_dropdown_pages(
						array(
							'name'              => esc_attr( PageSettings::OPTION_PLAYER ),
							'id'                => 'mintlms_page_player',
							'selected'          => absint( $playerId ),
							'show_option_none'  => esc_html__( '— Select —', 'mint-lms' ),
							'option_none_value' => '0',
							'class'             => esc_attr( $fieldSelect ),
						)
					);
					?>
					<p class="mint-text-sm mint-text-ink-3"><?php esc_html_e( 'Uses [mint_lms_player] — lesson viewer for enrolled students.', 'mint-lms' ); ?></p>
				</div>
			</div>
			<p class="mint-mt-4 mint-text-sm mint-text-ink-3">
				<button type="submit" form="mintlms-create-pages-form" class="button button-secondary"><?php esc_html_e( 'Create or repair student pages', 'mint-lms' ); ?></button>
			</p>
		</div>

		<div class="<?php echo esc_attr( $cardClass ); ?>">
			<h2 class="mint-m-0 mint-text-[20px] mint-font-semibold mint-leading-7 mint-tracking-tight mint-text-ink"><?php esc_html_e( 'Certificate template', 'mint-lms' ); ?></h2>
			<p class="mint-mt-2 mint-text-sm mint-leading-[22px] mint-text-ink-2"><?php esc_html_e( 'HTML shown when a student downloads their certificate. Placeholders: {{student_name}}, {{course_name}}, {{completion_date}}.', 'mint-lms' ); ?></p>
			<div class="mint-mt-5 mint-flex mint-flex-col mint-gap-2">
				<label for="mintlms_certificate_template" class="mint-text-sm mint-font-semibold mint-text-ink"><?php esc_html_e( 'Template HTML', 'mint-lms' ); ?></label>
				<textarea
					id="mintlms_certificate_template"
					name="mintlms_certificate_template"
					class="<?php echo esc_attr( $fieldSelect ); ?> large-text code mint-min-h-[200px] mint-resize-y"
					rows="12"
				><?php echo esc_textarea( $certTemplate ); ?></textarea>
			</div>
		</div>

		<div class="<?php echo esc_attr( $cardClass ); ?>">
			<h2 class="mint-m-0 mint-text-[20px] mint-font-semibold mint-leading-7 mint-tracking-tight mint-text-ink"><?php esc_html_e( 'Enrollment defaults', 'mint-lms' ); ?></h2>
			<p class="mint-mt-2 mint-text-sm mint-leading-[22px] mint-text-ink-2"><?php esc_html_e( 'Default behavior for new courses. Individual courses can override this.', 'mint-lms' ); ?></p>

			<fieldset class="mint-mt-5 mint-flex mint-flex-col mint-gap-3">
				<legend class="screen-reader-text"><?php esc_html_e( 'Default enrollment', 'mint-lms' ); ?></legend>
				<label class="mint-flex mint-items-center mint-gap-3 mint-text-sm mint-text-ink">
					<input
						type="radio"
						name="<?php echo esc_attr( PageSettings::OPTION_DEFAULT_ENROLLMENT ); ?>"
						value="open"
						<?php checked( $pageSettings->getDefaultEnrollment(), 'open' ); ?>
					/>
					<span><?php esc_html_e( 'Open for everyone — browse without signing up', 'mint-lms' ); ?></span>
				</label>
				<label class="mint-flex mint-items-center mint-gap-3 mint-text-sm mint-text-ink">
					<input
						type="radio"
						name="<?php echo esc_attr( PageSettings::OPTION_DEFAULT_ENROLLMENT ); ?>"
						value="free"
						<?php checked( $pageSettings->getDefaultEnrollment(), 'free' ); ?>
					/>
					<span><?php esc_html_e( 'Free — login to join', 'mint-lms' ); ?></span>
				</label>
				<label class="mint-flex mint-items-center mint-gap-3 mint-text-sm mint-text-ink">
					<input
						type="radio"
						name="<?php echo esc_attr( PageSettings::OPTION_DEFAULT_ENROLLMENT ); ?>"
						value="manual"
						<?php checked( $pageSettings->getDefaultEnrollment(), 'manual' ); ?>
					/>
					<span><?php esc_html_e( 'Invite only — admin must enroll students', 'mint-lms' ); ?></span>
				</label>
				<label class="mint-flex mint-items-center mint-gap-3 mint-text-sm mint-text-ink">
					<input
						type="radio"
						name="<?php echo esc_attr( PageSettings::OPTION_DEFAULT_ENROLLMENT ); ?>"
						value="paid"
						<?php checked( $pageSettings->getDefaultEnrollment(), 'paid' ); ?>
					/>
					<span><?php esc_html_e( 'Paid — sell via WooCommerce product', 'mint-lms' ); ?></span>
				</label>
			</fieldset>
		</div>

		<div class="<?php echo esc_attr( $cardClass ); ?>">
			<h2 class="mint-m-0 mint-text-[20px] mint-font-semibold mint-leading-7 mint-tracking-tight mint-text-ink"><?php esc_html_e( 'Email notifications', 'mint-lms' ); ?></h2>
			<p class="mint-mt-2 mint-text-sm mint-leading-[22px] mint-text-ink-2"><?php esc_html_e( 'Toggle automated emails. Email templates are managed separately.', 'mint-lms' ); ?></p>

			<div class="mint-mt-5 mint-flex mint-flex-col mint-gap-3">
				<input type="hidden" name="<?php echo esc_attr( PageSettings::OPTION_EMAIL_ENROLL ); ?>" value="0" />
				<label class="mint-flex mint-items-center mint-gap-3 mint-text-sm mint-text-ink">
					<input
						type="checkbox"
						name="<?php echo esc_attr( PageSettings::OPTION_EMAIL_ENROLL ); ?>"
						value="1"
						<?php checked( $pageSettings->isEmailEnrollEnabled() ); ?>
					/>
					<span><?php esc_html_e( 'Send email when a student enrolls', 'mint-lms' ); ?></span>
				</label>
				<input type="hidden" name="<?php echo esc_attr( PageSettings::OPTION_EMAIL_COMPLETE ); ?>" value="0" />
				<label class="mint-flex mint-items-center mint-gap-3 mint-text-sm mint-text-ink">
					<input
						type="checkbox"
						name="<?php echo esc_attr( PageSettings::OPTION_EMAIL_COMPLETE ); ?>"
						value="1"
						<?php checked( $pageSettings->isEmailCompleteEnabled() ); ?>
					/>
					<span><?php esc_html_e( 'Send email when a student completes a course', 'mint-lms' ); ?></span>
				</label>
			</div>
		</div>

		<div class="<?php echo esc_attr( $cardClass ); ?>" id="mintlms-ai-settings">
			<h2 class="mint-m-0 mint-text-[20px] mint-font-semibold mint-leading-7 mint-tracking-tight mint-text-ink"><?php esc_html_e( 'AI assistant (optional)', 'mint-lms' ); ?></h2>
			<p class="mint-mt-2 mint-text-sm mint-leading-[22px] mint-text-ink-2">
				<?php esc_html_e( 'Opt in to send drafting prompts to your chosen AI provider. Off by default. Keys stay on the server — never in the browser. Prefer defining MINTLMS_AI_API_KEY in wp-config.php.', 'mint-lms' ); ?>
			</p>
			<p class="mint-mt-2 mint-text-sm mint-leading-[22px] mint-text-ink-3">
				<?php
				echo wp_kses(
					sprintf(
						/* translators: 1: OpenAI privacy link, 2: Anthropic privacy link */
						__( 'Providers: OpenAI (%1$s), Anthropic (%2$s). Data leaves this site only after you enable AI and save a key.', 'mint-lms' ),
						'<a href="https://openai.com/policies/privacy-policy" target="_blank" rel="noopener noreferrer">' . esc_html__( 'privacy policy', 'mint-lms' ) . '</a>',
						'<a href="https://www.anthropic.com/privacy" target="_blank" rel="noopener noreferrer">' . esc_html__( 'privacy policy', 'mint-lms' ) . '</a>'
					),
					array(
						'a' => array(
							'href'   => true,
							'target' => true,
							'rel'    => true,
						),
					)
				);
				?>
			</p>

			<div class="mint-mt-5 mint-flex mint-flex-col mint-gap-4">
				<input type="hidden" name="<?php echo esc_attr( AiSettings::OPTION_ENABLED ); ?>" value="0" />
				<label class="mint-flex mint-items-center mint-gap-3 mint-text-sm mint-text-ink">
					<input
						type="checkbox"
						name="<?php echo esc_attr( AiSettings::OPTION_ENABLED ); ?>"
						value="1"
						<?php checked( $aiSettings->isEnabled() ); ?>
					/>
					<span><?php esc_html_e( 'Enable AI features (opt-in)', 'mint-lms' ); ?></span>
				</label>

				<label class="mint-flex mint-flex-col mint-gap-2 mint-text-sm mint-font-semibold mint-text-ink">
					<span><?php esc_html_e( 'Provider', 'mint-lms' ); ?></span>
					<select name="<?php echo esc_attr( AiSettings::OPTION_PROVIDER ); ?>" class="<?php echo esc_attr( $fieldSelect ); ?>">
						<option value="<?php echo esc_attr( AiSettings::PROVIDER_OPENAI ); ?>" <?php selected( $aiSettings->getProvider(), AiSettings::PROVIDER_OPENAI ); ?>><?php esc_html_e( 'OpenAI', 'mint-lms' ); ?></option>
						<option value="<?php echo esc_attr( AiSettings::PROVIDER_ANTHROPIC ); ?>" <?php selected( $aiSettings->getProvider(), AiSettings::PROVIDER_ANTHROPIC ); ?>><?php esc_html_e( 'Anthropic', 'mint-lms' ); ?></option>
					</select>
				</label>

				<label class="mint-flex mint-flex-col mint-gap-2 mint-text-sm mint-font-semibold mint-text-ink">
					<span><?php esc_html_e( 'Model', 'mint-lms' ); ?></span>
					<input
						type="text"
						name="<?php echo esc_attr( AiSettings::OPTION_MODEL ); ?>"
						value="<?php echo esc_attr( (string) get_option( AiSettings::OPTION_MODEL, '' ) ); ?>"
						class="<?php echo esc_attr( $fieldSelect ); ?>"
						placeholder="<?php echo esc_attr( $aiSettings->getModel() ); ?>"
					/>
				</label>

				<label class="mint-flex mint-flex-col mint-gap-2 mint-text-sm mint-font-semibold mint-text-ink">
					<span><?php esc_html_e( 'API key', 'mint-lms' ); ?></span>
					<?php if ( $aiUsesConstant ) : ?>
						<p class="mint-m-0 mint-text-sm mint-font-normal mint-text-ink-2">
							<?php
							printf(
								/* translators: %s: key source (constant or env) */
								esc_html__( 'Using key from %s (database field ignored).', 'mint-lms' ),
								esc_html( (string) $aiStatus['key_source'] )
							);
							?>
						</p>
					<?php else : ?>
						<input
							type="password"
							name="<?php echo esc_attr( AiSettings::OPTION_API_KEY_ENC ); ?>"
							value="<?php echo esc_attr( $aiKeyFieldValue ); ?>"
							class="<?php echo esc_attr( $fieldSelect ); ?>"
							autocomplete="new-password"
							placeholder="<?php esc_attr_e( 'Paste API key', 'mint-lms' ); ?>"
						/>
						<?php if ( $aiSettings->hasApiKey() && '' !== (string) $aiStatus['key_hint'] ) : ?>
							<span class="mint-text-sm mint-font-normal mint-text-ink-3">
								<?php
								printf(
									/* translators: %s: last four characters of API key */
									esc_html__( 'Saved key ending in %s', 'mint-lms' ),
									esc_html( (string) $aiStatus['key_hint'] )
								);
								?>
							</span>
						<?php endif; ?>
						<label class="mint-flex mint-items-center mint-gap-3 mint-text-sm mint-font-normal mint-text-ink">
							<input type="checkbox" name="mintlms_ai_clear_key" value="1" />
							<span><?php esc_html_e( 'Remove saved API key', 'mint-lms' ); ?></span>
						</label>
					<?php endif; ?>
				</label>

				<div class="mint-flex mint-flex-wrap mint-items-center mint-gap-3">
					<button
						type="button"
						id="mintlms-ai-test"
						class="button"
						<?php disabled( ! $aiSettings->isReady() ); ?>
					>
						<?php esc_html_e( 'Test connection', 'mint-lms' ); ?>
					</button>
					<span id="mintlms-ai-test-result" class="mint-text-sm mint-text-ink-3" aria-live="polite"></span>
				</div>
			</div>
		</div>

		<?php submit_button( __( 'Save settings', 'mint-lms' ) ); ?>
	</form>
</div>
<script>
(function () {
	var btn = document.getElementById('mintlms-ai-test');
	var out = document.getElementById('mintlms-ai-test-result');
	if (!btn || !out || !window.mintLmsAdmin) return;
	btn.addEventListener('click', function () {
		out.textContent = <?php echo wp_json_encode( __( 'Testing…', 'mint-lms' ) ); ?>;
		btn.disabled = true;
		fetch(String(mintLmsAdmin.restBase).replace(/\/?$/, '') + '/ai/test', {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
				'X-WP-Nonce': mintLmsAdmin.nonce || ''
			},
			body: '{}'
		}).then(function (r) { return r.json(); }).then(function (json) {
			btn.disabled = false;
			if (json && json.success) {
				out.textContent = <?php echo wp_json_encode( __( 'Connection OK', 'mint-lms' ) ); ?> +
					(json.data && json.data.latency_ms ? ' (' + json.data.latency_ms + 'ms)' : '');
			} else {
				out.textContent = (json && json.error && json.error.message) ? json.error.message : <?php echo wp_json_encode( __( 'Test failed', 'mint-lms' ) ); ?>;
			}
		}).catch(function () {
			btn.disabled = false;
			out.textContent = <?php echo wp_json_encode( __( 'Test failed', 'mint-lms' ) ); ?>;
		});
	});
})();
</script>
