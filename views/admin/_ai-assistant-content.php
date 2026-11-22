<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

use MintLMS\Infrastructure\Admin\AiAssistantPage;
use MintLMS\Infrastructure\Ai\AiSettings;
use MintLMS\Infrastructure\Ai\McpAdapterNotice;

$aiSettings     = new AiSettings();
$aiStatus       = $aiSettings->status();
$aiUsesConstant = in_array( (string) $aiStatus['key_source'], array( 'constant', 'env' ), true );
$settingsSaved  = isset( $_GET['settings-updated'] ) && 'true' === sanitize_text_field( wp_unslash( (string) $_GET['settings-updated'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$stored_model   = sanitize_text_field( (string) get_option( AiSettings::OPTION_MODEL, '' ) );
$boot_model     = '' !== $stored_model ? AiSettings::migrateDeprecatedModel( $stored_model ) : '';

$boot = array(
	'aiEnabled'       => $aiSettings->isEnabled(),
	'aiProvider'      => $aiSettings->getProvider(),
	'aiModel'         => $boot_model,
	// Mask only on load (not the real key). Eye fetches plaintext via REST when opened.
	'aiKey'           => $aiSettings->hasApiKey() ? AiSettings::MASK_PLACEHOLDER : '',
	'aiKeyHint'       => (string) ( $aiStatus['key_hint'] ?? '' ),
	'hasStoredKey'    => $aiSettings->hasApiKey(),
	'aiKeyMask'       => AiSettings::MASK_PLACEHOLDER,
	'aiUsesConstant'  => $aiUsesConstant,
	'aiReady'         => $aiSettings->isReady(),
	'enrollment'      => 'free',
	'emailEnroll'     => false,
	'emailComplete'   => false,
	'pages'           => array(),
	'dashboardId'     => 0,
	'catalogId'       => 0,
	'playerId'        => 0,
	'certTemplate'    => '',
	'i18n'            => array(
		'openai'              => __( 'OpenAI (paid)', 'mint-lms' ),
		'anthropic'           => __( 'Anthropic (paid)', 'mint-lms' ),
		'groq'                => __( 'Groq (free tier)', 'mint-lms' ),
		'gemini'              => __( 'Google Gemini (free tier)', 'mint-lms' ),
		'testing'             => __( 'Testing…', 'mint-lms' ),
		'connectionOk'        => __( 'Connection OK', 'mint-lms' ),
		'testFailed'          => __( 'Test failed', 'mint-lms' ),
		'testFailedTitle'     => __( 'Test connection failed', 'mint-lms' ),
		'testFailedNote'      => __( 'Check your API key, provider, and model, then try Test connection again.', 'mint-lms' ),
		'testFailedNetwork'   => __( 'Could not reach the AI service. Check your network and try again.', 'mint-lms' ),
		'testFailedClose'     => __( 'OK', 'mint-lms' ),
		'aiDisabled'          => __( 'AI is turned off on the server.', 'mint-lms' ),
		'aiDisabledNote'      => __( 'Turn the switch ON, then click Save AI settings (or Save API key) and Test again.', 'mint-lms' ),
		'aiKeyMissing'        => __( 'API key is missing.', 'mint-lms' ),
		'aiKeyMissingNote'    => __( 'Paste your API key and click Save API key, then Test connection.', 'mint-lms' ),
		'aiKeyUnreadable'     => __( 'Saved API key cannot be read on this site.', 'mint-lms' ),
		'aiKeyUnreadableNote' => __( 'Paste your API key again and click Save API key. This often happens after copying a site to staging.', 'mint-lms' ),
		'keyCleared'          => __( 'Saved key will be removed on save.', 'mint-lms' ),
		'savingKey'           => __( 'Saving API key…', 'mint-lms' ),
		'removingKey'         => __( 'Removing API key…', 'mint-lms' ),
		'saveKey'             => __( 'Save API key', 'mint-lms' ),
		'keySaved'            => __( 'API key saved.', 'mint-lms' ),
		'keyRemoved'          => __( 'API key removed.', 'mint-lms' ),
		'keySaveFailed'       => __( 'Could not save API key.', 'mint-lms' ),
		'showKey'             => __( 'Show API key', 'mint-lms' ),
		'hideKey'             => __( 'Hide API key', 'mint-lms' ),
		'revealingKey'        => __( 'Showing key…', 'mint-lms' ),
		'revealFailed'        => __( 'Could not show API key.', 'mint-lms' ),
		'pasteToReplace'      => __( 'Paste a new API key to replace the saved one', 'mint-lms' ),
		'pasteYourKey'        => __( 'Paste your API key', 'mint-lms' ),
		'saveKeyNeedPaste'    => __( 'Paste a new API key first, then click Save API key.', 'mint-lms' ),
		'draftGenerating'     => __( 'Generating Mint LMS draft…', 'mint-lms' ),
		'draftReady'          => __( 'Draft ready — nothing was saved.', 'mint-lms' ),
		'draftFailed'         => __( 'Could not generate draft', 'mint-lms' ),
		'draftFailedTitle'    => __( 'Draft generation failed', 'mint-lms' ),
		'draftFailedNote'     => __( 'AI stays inside Mint LMS only. Check connection, then try a clearer prompt about a course, lesson, or quiz.', 'mint-lms' ),
		'draftNeedPrompt'     => __( 'Describe the Mint LMS content you want first.', 'mint-lms' ),
		'draftNeedReady'      => __( 'Turn AI on, save settings, and add an API key before generating.', 'mint-lms' ),
		'rootCourse'          => __( 'Course', 'mint-lms' ),
		'rootLesson'          => __( 'Lesson', 'mint-lms' ),
		'rootQuiz'            => __( 'Quiz', 'mint-lms' ),
	),
	'aiDefaultModels' => array(
		'openai'    => AiSettings::defaultModelFor( AiSettings::PROVIDER_OPENAI ),
		'anthropic' => AiSettings::defaultModelFor( AiSettings::PROVIDER_ANTHROPIC ),
		'groq'      => AiSettings::defaultModelFor( AiSettings::PROVIDER_GROQ ),
		'gemini'    => AiSettings::defaultModelFor( AiSettings::PROVIDER_GEMINI ),
	),
);

$boot_json = wp_json_encode( $boot );
if ( ! is_string( $boot_json ) ) {
	$boot_json = '{}';
}
?>
<div
	class="mint-settings mint-ai-page"
	x-data="mintSettings(<?php echo esc_attr( $boot_json ); ?>)"
>
	<?php if ( $settingsSaved ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'AI Assistant settings saved.', 'mint-lms' ); ?></p></div>
	<?php endif; ?>

	<?php
	$mcp_active    = McpAdapterNotice::isMcpAdapterActive();
	$mcp_install   = self_admin_url( 'plugin-install.php?s=MCP%20Adapter&tab=search&type=term' );
	$app_passwords = admin_url( 'profile.php#application-passwords-section' );
	$mcp_endpoint  = rest_url( 'mcp/mcp-adapter-default-server' );
	$mcp_sample    = __( 'Create a draft Mint LMS course about WordPress basics with 2 lessons and 1 quiz.', 'mint-lms' );
	?>

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
			<p class="mint-settings__sub"><?php esc_html_e( 'Create Mint LMS drafts from Claude Desktop (MCP). Items stay as drafts for review — never WordPress pages or posts. Optional API key below is only for Mint-side tests.', 'mint-lms' ); ?></p>
		</div>
	</div>

	<div class="mint-ai-page__primary">
		<section class="mint-settings-card mint-ai-page__mcp" id="mintlms-ai-mcp">
			<header class="mint-settings-card__head">
				<h2 class="mint-settings-card__title"><?php esc_html_e( 'Claude Desktop (MCP)', 'mint-lms' ); ?></h2>
				<p class="mint-settings-card__desc"><?php esc_html_e( 'Recommended path. Prompt in Claude Desktop; Mint LMS abilities create draft courses, lessons, quizzes, and questions on this site.', 'mint-lms' ); ?></p>
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
						<div
							class="mint-ai-page__endpoint"
							x-data="{ copied: false, copyTimer: null }"
						>
							<label class="mint-settings-field__label" for="mintlms_mcp_endpoint"><?php esc_html_e( 'MCP endpoint', 'mint-lms' ); ?></label>
							<div class="mint-ai-page__endpoint-row">
								<input
									type="text"
									id="mintlms_mcp_endpoint"
									class="mint-settings-input mint-ai-page__endpoint-input"
									readonly
									value="<?php echo esc_attr( $mcp_endpoint ); ?>"
									onclick="this.select();"
									x-ref="mcpEndpoint"
								/>
								<button
									type="button"
									class="mint-settings-btn mint-settings-btn--primary mint-ai-page__endpoint-copy"
									@click="
										const value = $refs.mcpEndpoint.value;
										const done = () => { copied = true; clearTimeout(copyTimer); copyTimer = setTimeout(() => copied = false, 2000); };
										if (navigator.clipboard && navigator.clipboard.writeText) {
											navigator.clipboard.writeText(value).then(done).catch(() => { $refs.mcpEndpoint.select(); document.execCommand('copy'); done(); });
										} else {
											$refs.mcpEndpoint.select();
											document.execCommand('copy');
											done();
										}
									"
								>
									<span x-text="copied ? '<?php echo esc_js( __( 'Copied', 'mint-lms' ) ); ?>' : '<?php echo esc_js( __( 'Copy', 'mint-lms' ) ); ?>'"></span>
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
					<li><?php esc_html_e( 'Optional API key (BYOK) below is only for Mint-side connection tests — not required for Claude Desktop MCP.', 'mint-lms' ); ?></li>
				</ul>
			</div>
		</section>
	</div>

	<form method="post" action="options.php" class="mint-settings__form mint-ai-page__byok" id="mintlms-ai-settings-form">
		<?php settings_fields( AiAssistantPage::OPTION_GROUP ); ?>

		<input type="hidden" name="<?php echo esc_attr( AiSettings::OPTION_ENABLED ); ?>" :value="aiEnabled ? '1' : '0'" />
		<input type="hidden" name="<?php echo esc_attr( AiSettings::OPTION_PROVIDER ); ?>" :value="aiProvider" />
		<?php // Key is saved only via "Save API key" (REST). Form keeps the existing encrypted key. ?>
		<input type="hidden" name="<?php echo esc_attr( AiSettings::OPTION_API_KEY_ENC ); ?>" value="<?php echo esc_attr( AiSettings::MASK_PLACEHOLDER ); ?>" />
		<input type="hidden" name="mintlms_ai_clear_key" value="0" />

		<div class="mint-ai-page__byok-head">
			<div>
				<h2 class="mint-ai-page__byok-title"><?php esc_html_e( 'Optional: Mint-side API key (BYOK)', 'mint-lms' ); ?></h2>
				<p class="mint-ai-page__byok-desc"><?php esc_html_e( 'Not required for Claude Desktop MCP. Use only if you want to test a provider connection inside Mint LMS.', 'mint-lms' ); ?></p>
			</div>
			<div class="mint-settings__footer mint-ai-page__savebar">
				<p class="mint-settings__footer-note"><?php esc_html_e( 'Save AI settings = on/off, provider, model. Save API key = credential only.', 'mint-lms' ); ?></p>
				<button type="submit" class="mint-settings-btn mint-settings-btn--primary">
					<?php esc_html_e( 'Save AI settings', 'mint-lms' ); ?>
				</button>
			</div>
		</div>

		<div class="mint-ai-page__grid">
			<div class="mint-ai-page__main">
				<section class="mint-settings-card" id="mintlms-ai-settings">
					<header class="mint-settings-card__head">
						<h2 class="mint-settings-card__title"><?php esc_html_e( 'Connection', 'mint-lms' ); ?></h2>
						<p class="mint-settings-card__desc"><?php esc_html_e( 'Turn AI on and pick a provider, then Save AI settings. Paste your key and use Save API key. Then Test connection.', 'mint-lms' ); ?></p>
					</header>
					<div class="mint-settings-card__body">
						<div class="mint-settings-toggle-row mint-settings-toggle-row--solo">
							<div>
								<div class="mint-settings-toggle-row__title"><?php esc_html_e( 'Turn on AI features', 'mint-lms' ); ?></div>
								<div class="mint-settings-toggle-row__help"><?php esc_html_e( 'Your key stays on the server, never in the browser.', 'mint-lms' ); ?></div>
							</div>
							<button
								type="button"
								class="mint-settings-switch"
								role="switch"
								:aria-checked="aiEnabled ? 'true' : 'false'"
								:class="{ 'is-on': aiEnabled }"
								@click="toggleAi()"
							>
								<span class="mint-settings-switch__knob" aria-hidden="true"></span>
							</button>
						</div>

						<div class="mint-settings-ai" x-show="aiEnabled" x-cloak>
							<div class="mint-settings-ai__row">
								<div class="mint-settings-field">
									<div class="mint-settings-field__label"><?php esc_html_e( 'Provider', 'mint-lms' ); ?></div>
									<div class="mint-settings-select" :class="{ 'is-open': openSelect === 'provider' }">
										<button
											type="button"
											class="mint-settings-select__trigger"
											@click="toggleSelect('provider')"
											:aria-expanded="openSelect === 'provider' ? 'true' : 'false'"
										>
											<span x-text="providerLabel()"></span>
											<svg width="13" height="13" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m5 8 5 5 5-5" /></svg>
										</button>
										<div class="mint-settings-select__menu" x-show="openSelect === 'provider'" x-cloak @click.stop>
											<button type="button" class="mint-settings-select__option" :class="{ 'is-active': aiProvider === 'groq' }" @click="pickProvider('groq')" x-text="i18n.groq"></button>
											<button type="button" class="mint-settings-select__option" :class="{ 'is-active': aiProvider === 'gemini' }" @click="pickProvider('gemini')" x-text="i18n.gemini"></button>
											<button type="button" class="mint-settings-select__option" :class="{ 'is-active': aiProvider === 'openai' }" @click="pickProvider('openai')" x-text="i18n.openai"></button>
											<button type="button" class="mint-settings-select__option" :class="{ 'is-active': aiProvider === 'anthropic' }" @click="pickProvider('anthropic')" x-text="i18n.anthropic"></button>
										</div>
									</div>
								</div>
								<div class="mint-settings-field">
									<label class="mint-settings-field__label" for="mintlms_ai_model"><?php esc_html_e( 'Model', 'mint-lms' ); ?></label>
									<input
										type="text"
										id="mintlms_ai_model"
										name="<?php echo esc_attr( AiSettings::OPTION_MODEL ); ?>"
										class="mint-settings-input"
										x-model="aiModel"
										placeholder="<?php echo esc_attr( $aiSettings->getModel() ); ?>"
									/>
								</div>
							</div>

							<div class="mint-settings-field">
								<label class="mint-settings-field__label" for="mintlms_ai_api_key"><?php esc_html_e( 'API key', 'mint-lms' ); ?></label>
								<?php if ( $aiUsesConstant ) : ?>
									<p class="mint-settings-field__help mint-m-0">
										<?php
										printf(
											/* translators: %s: key source (constant or env) */
											esc_html__( 'Using key from %s (database field ignored).', 'mint-lms' ),
											esc_html( (string) $aiStatus['key_source'] )
										);
										?>
									</p>
								<?php else : ?>
									<div class="mint-settings-secret" :class="{ 'is-revealed': aiKeyVisible && canRevealAiKey() }">
										<input
											:type="aiKeyVisible && canRevealAiKey() ? 'text' : 'password'"
											id="mintlms_ai_api_key"
											class="mint-settings-input mint-settings-secret__input"
											autocomplete="new-password"
											spellcheck="false"
											:placeholder="hasStoredKey ? (i18n.pasteToReplace || 'Paste a new API key to replace the saved one') : (i18n.pasteYourKey || 'Paste your API key')"
											x-model="aiKey"
											@input="onAiKeyInput()"
											@paste="$nextTick(() => onAiKeyInput())"
											:class="{ 'is-masked': !(aiKeyVisible && canRevealAiKey()) }"
											@copy="onAiKeyCopy($event)"
											@cut="onAiKeyCut($event)"
										/>
										<button
											type="button"
											class="mint-settings-secret__toggle"
											@click="toggleAiKeyVisible()"
											:disabled="aiKeyRevealing"
											:aria-pressed="aiKeyVisible && canRevealAiKey() ? 'true' : 'false'"
											:aria-label="aiKeyVisible && canRevealAiKey() ? (i18n.hideKey || 'Hide API key') : (i18n.showKey || 'Show API key')"
											:title="aiKeyVisible && canRevealAiKey() ? (i18n.hideKey || 'Hide API key') : (i18n.showKey || 'Show API key')"
										>
											<svg x-show="!(aiKeyVisible && canRevealAiKey())" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#0F0E1A" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
												<path d="M2.5 12s3.5-6.5 9.5-6.5S21.5 12 21.5 12s-3.5 6.5-9.5 6.5S2.5 12 2.5 12Z"/>
												<circle cx="12" cy="12" r="2.75"/>
												<path d="m4 4 16 16"/>
											</svg>
											<svg x-show="aiKeyVisible && canRevealAiKey()" x-cloak width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#0F0E1A" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
												<path d="M2.5 12s3.5-6.5 9.5-6.5S21.5 12 21.5 12s-3.5 6.5-9.5 6.5S2.5 12 2.5 12Z"/>
												<circle cx="12" cy="12" r="2.75"/>
											</svg>
										</button>
									</div>
									<p class="mint-settings-field__help" x-show="hasStoredKey && isAiKeyMask()" x-cloak>
										<template x-if="aiKeyHint">
											<span x-text="'<?php echo esc_js( __( 'Saved key ending in', 'mint-lms' ) ); ?> ' + aiKeyHint + '. <?php echo esc_js( __( 'Paste a new key to enable Save API key.', 'mint-lms' ) ); ?>'"></span>
										</template>
										<template x-if="!aiKeyHint">
											<span><?php esc_html_e( 'A key is already saved. Paste a new key to enable Save API key.', 'mint-lms' ); ?></span>
										</template>
									</p>
									<p class="mint-settings-field__help" x-show="aiKeyDirty && !aiKeyVisible" x-cloak><?php esc_html_e( 'New key ready — click Save API key. Eye only shows/hides; it does not save.', 'mint-lms' ); ?></p>
									<p class="mint-settings-field__help" x-show="aiKeyDirty && aiKeyVisible" x-cloak><?php esc_html_e( 'Key is visible — you can copy it, then click Save API key.', 'mint-lms' ); ?></p>
									<p class="mint-settings-field__help" x-show="!aiKeyDirty && aiKeyVisible && canRevealAiKey()" x-cloak><?php esc_html_e( 'Viewing saved key — edit or paste a new key to enable Save API key.', 'mint-lms' ); ?></p>
								<?php endif; ?>
							</div>

							<div class="mint-settings-ai__actions">
								<?php if ( ! $aiUsesConstant ) : ?>
									<button
										type="button"
										class="mint-settings-btn mint-settings-btn--primary"
										@click="saveAiKey()"
										:disabled="!canSaveAiKey()"
									>
										<span x-text="aiKeySaving ? (i18n.savingKey || 'Saving API key…') : (i18n.saveKey || 'Save API key')"></span>
									</button>
								<?php endif; ?>
								<button
									type="button"
									class="mint-settings-btn mint-settings-btn--outline"
									@click="testAi()"
									:disabled="aiTesting || aiKeySaving || aiKeyRevealing || (!aiReady && !hasStoredKey && !aiKeyDirty)"
								>
									<?php esc_html_e( 'Test connection', 'mint-lms' ); ?>
								</button>
								<?php if ( ! $aiUsesConstant ) : ?>
									<button
										type="button"
										class="mint-settings-btn mint-settings-btn--outline"
										@click="markClearKey()"
										x-show="hasStoredKey || aiKey"
									>
										<?php esc_html_e( 'Remove saved key', 'mint-lms' ); ?>
									</button>
								<?php endif; ?>
							</div>
							<p class="mint-settings-ai__result" x-text="aiTestResult" aria-live="polite"></p>
						</div>
					</div>
				</section>

				<section class="mint-settings-card mint-ai-page__get-key-card">
					<header class="mint-settings-card__head">
						<h2 class="mint-settings-card__title"><?php esc_html_e( 'Where to get a key', 'mint-lms' ); ?></h2>
						<p class="mint-settings-card__desc"><?php esc_html_e( 'Open these in a new tab. Sign in, create a key, copy it once.', 'mint-lms' ); ?></p>
					</header>
					<div class="mint-settings-card__body">
						<ul class="mint-ai-page__links">
							<li>
								<a class="mint-ai-page__link" href="https://console.groq.com/keys" target="_blank" rel="noopener noreferrer">
									<span><?php esc_html_e( 'Groq API keys', 'mint-lms' ); ?></span>
									<small><?php esc_html_e( 'Free tier · recommended for testing', 'mint-lms' ); ?></small>
								</a>
							</li>
							<li>
								<a class="mint-ai-page__link" href="https://aistudio.google.com/apikey" target="_blank" rel="noopener noreferrer">
									<span><?php esc_html_e( 'Google AI Studio (Gemini)', 'mint-lms' ); ?></span>
									<small><?php esc_html_e( 'Free tier · Gemini models', 'mint-lms' ); ?></small>
								</a>
							</li>
							<li>
								<a class="mint-ai-page__link" href="https://platform.openai.com/api-keys" target="_blank" rel="noopener noreferrer">
									<span><?php esc_html_e( 'OpenAI API keys', 'mint-lms' ); ?></span>
									<small><?php esc_html_e( 'Paid · usually needs billing credits', 'mint-lms' ); ?></small>
								</a>
							</li>
							<li>
								<a class="mint-ai-page__link" href="https://console.anthropic.com/" target="_blank" rel="noopener noreferrer">
									<span><?php esc_html_e( 'Anthropic console', 'mint-lms' ); ?></span>
									<small><?php esc_html_e( 'Paid · Claude API', 'mint-lms' ); ?></small>
								</a>
							</li>
						</ul>
					</div>
				</section>
			</div>

			<aside class="mint-ai-page__side">
				<section class="mint-settings-card">
					<header class="mint-settings-card__head">
						<h2 class="mint-settings-card__title"><?php esc_html_e( 'How to set BYOK up', 'mint-lms' ); ?></h2>
						<p class="mint-settings-card__desc"><?php esc_html_e( 'Skip this if you only use Claude Desktop MCP.', 'mint-lms' ); ?></p>
					</header>
					<div class="mint-settings-card__body">
						<ol class="mint-ai-page__steps">
							<li>
								<strong><?php esc_html_e( 'Turn AI on', 'mint-lms' ); ?></strong>
								<span><?php esc_html_e( 'Use the switch. Nothing is sent to any AI until this is on and a key is saved.', 'mint-lms' ); ?></span>
							</li>
							<li>
								<strong><?php esc_html_e( 'Pick free or paid', 'mint-lms' ); ?></strong>
								<span><?php esc_html_e( 'Groq or Google Gemini are good for free testing. OpenAI or Anthropic if you already pay for API access.', 'mint-lms' ); ?></span>
							</li>
							<li>
								<strong><?php esc_html_e( 'Get your own key', 'mint-lms' ); ?></strong>
								<span><?php esc_html_e( 'Create a key on the provider site, then paste it here. Mint never ships a shared key.', 'mint-lms' ); ?></span>
							</li>
							<li>
								<strong><?php esc_html_e( 'Save, then Test', 'mint-lms' ); ?></strong>
								<span><?php esc_html_e( 'Click Save API key to store the credential (Save AI settings only saves on/off, provider, model). Then click Test connection. You should see Connection OK.', 'mint-lms' ); ?></span>
							</li>
						</ol>
					</div>
				</section>
			</aside>
		</div>
	</form>

	<div
		class="mint-confirm mint-confirm--ai-fail"
		x-show="aiTestFail.open"
		x-cloak
		x-transition.opacity.duration.200ms
		role="presentation"
		@keydown.escape.window="closeAiTestFail()"
	>
		<div class="mint-confirm__backdrop" @click="closeAiTestFail()"></div>
		<div
			class="mint-confirm__panel"
			role="alertdialog"
			aria-modal="true"
			aria-labelledby="mint-ai-test-fail-title"
			aria-describedby="mint-ai-test-fail-body"
			@click.stop
		>
			<button
				type="button"
				class="mint-confirm__close"
				@click="closeAiTestFail()"
				aria-label="<?php echo esc_attr__( 'Close', 'mint-lms' ); ?>"
			>
				<svg width="14" height="14" viewBox="0 0 20 20" fill="none" stroke="#0F0E1A" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path d="M5 5l10 10M15 5L5 15" /></svg>
			</button>

			<div class="mint-confirm__top">
				<div class="mint-confirm__icon mint-confirm__icon--warn" aria-hidden="true">
					<svg width="20" height="20" viewBox="0 0 20 20" fill="none" stroke="#0F0E1A" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
						<path d="M10 3.5 17.2 16H2.8L10 3.5Z"/>
						<path d="M10 8v4.2"/>
						<path d="M10 14.6h.01"/>
					</svg>
				</div>
				<h2 id="mint-ai-test-fail-title" class="mint-confirm__title" x-text="aiTestFail.title"></h2>
			</div>

			<div class="mint-confirm__content">
				<p id="mint-ai-test-fail-body" class="mint-confirm__body">
					<span x-text="aiTestFail.message"></span>
				</p>
				<div class="mint-confirm__note" x-show="aiTestFail.note" x-cloak x-text="aiTestFail.note"></div>
			</div>

			<div class="mint-confirm__actions">
				<button
					type="button"
					class="mint-confirm__submit mint-confirm__submit--ok"
					@click="closeAiTestFail()"
				>
					<span x-text="i18n.testFailedClose || 'OK'"></span>
				</button>
			</div>
		</div>
	</div>
</div>
