<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

use MintLMS\Infrastructure\Admin\AiAssistantPage;
use MintLMS\Infrastructure\Ai\AiSettings;

$aiSettings      = new AiSettings();
$aiStatus        = $aiSettings->status();
$aiKeyFieldValue = $aiSettings->hasApiKey() ? AiSettings::MASK_PLACEHOLDER : '';
$aiUsesConstant  = in_array( (string) $aiStatus['key_source'], array( 'constant', 'env' ), true );
$settingsSaved   = isset( $_GET['settings-updated'] ) && 'true' === sanitize_text_field( wp_unslash( (string) $_GET['settings-updated'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

$boot = array(
	'aiEnabled'       => $aiSettings->isEnabled(),
	'aiProvider'      => $aiSettings->getProvider(),
	'aiModel'         => (string) get_option( AiSettings::OPTION_MODEL, '' ),
	'aiKey'           => $aiKeyFieldValue,
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
		'openai'       => __( 'OpenAI (paid)', 'mint-lms' ),
		'anthropic'    => __( 'Anthropic (paid)', 'mint-lms' ),
		'groq'         => __( 'Groq (free tier)', 'mint-lms' ),
		'gemini'       => __( 'Google Gemini (free tier)', 'mint-lms' ),
		'testing'      => __( 'Testing…', 'mint-lms' ),
		'connectionOk' => __( 'Connection OK', 'mint-lms' ),
		'testFailed'   => __( 'Test failed', 'mint-lms' ),
		'keyCleared'   => __( 'Saved key will be removed on save.', 'mint-lms' ),
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
			<p class="mint-settings__sub"><?php esc_html_e( 'Connect your own AI key so Mint can help draft quiz questions. Free-tier or paid — you choose. Off by default.', 'mint-lms' ); ?></p>
		</div>
	</div>

	<form method="post" action="options.php" class="mint-settings__form" id="mintlms-ai-settings-form">
		<?php settings_fields( AiAssistantPage::OPTION_GROUP ); ?>

		<input type="hidden" name="<?php echo esc_attr( AiSettings::OPTION_ENABLED ); ?>" :value="aiEnabled ? '1' : '0'" />
		<input type="hidden" name="<?php echo esc_attr( AiSettings::OPTION_PROVIDER ); ?>" :value="aiProvider" />
		<input type="hidden" name="mintlms_ai_clear_key" :value="clearKey ? '1' : '0'" />

		<div class="mint-settings__footer mint-ai-page__savebar">
			<p class="mint-settings__footer-note"><?php esc_html_e( 'Save stores your AI choices on this site. Test connection talks to the provider.', 'mint-lms' ); ?></p>
			<button type="submit" class="mint-settings-btn mint-settings-btn--primary">
				<?php esc_html_e( 'Save AI settings', 'mint-lms' ); ?>
			</button>
		</div>

		<div class="mint-ai-page__grid">
			<div class="mint-ai-page__main">
				<section class="mint-settings-card" id="mintlms-ai-settings">
					<header class="mint-settings-card__head">
						<h2 class="mint-settings-card__title"><?php esc_html_e( 'Connection', 'mint-lms' ); ?></h2>
						<p class="mint-settings-card__desc"><?php esc_html_e( 'Turn AI on, pick a provider, paste your key, then save. After saving, use Test connection.', 'mint-lms' ); ?></p>
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
									<input
										type="password"
										id="mintlms_ai_api_key"
										name="<?php echo esc_attr( AiSettings::OPTION_API_KEY_ENC ); ?>"
										class="mint-settings-input"
										autocomplete="new-password"
										placeholder="<?php esc_attr_e( 'Paste your API key', 'mint-lms' ); ?>"
										x-model="aiKey"
									/>
									<?php if ( $aiSettings->hasApiKey() && '' !== (string) $aiStatus['key_hint'] ) : ?>
										<p class="mint-settings-field__help" x-show="!clearKey">
											<?php
											printf(
												/* translators: %s: last four characters of API key */
												esc_html__( 'Saved key ending in %s', 'mint-lms' ),
												esc_html( (string) $aiStatus['key_hint'] )
											);
											?>
										</p>
									<?php endif; ?>
								<?php endif; ?>
							</div>

							<div class="mint-settings-ai__actions">
								<button
									type="button"
									class="mint-settings-btn mint-settings-btn--outline"
									@click="testAi()"
									:disabled="aiTesting || (!aiReady && !aiKey)"
								>
									<?php esc_html_e( 'Test connection', 'mint-lms' ); ?>
								</button>
								<?php if ( ! $aiUsesConstant ) : ?>
									<button
										type="button"
										class="mint-settings-btn mint-settings-btn--outline"
										@click="markClearKey()"
										x-show="aiReady || aiKey"
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
						<h2 class="mint-settings-card__title"><?php esc_html_e( 'How to set this up', 'mint-lms' ); ?></h2>
						<p class="mint-settings-card__desc"><?php esc_html_e( 'Follow these steps once. You can change provider anytime.', 'mint-lms' ); ?></p>
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
								<span><?php esc_html_e( 'Click Save AI settings first. After the page reloads, click Test connection. You should see Connection OK.', 'mint-lms' ); ?></span>
							</li>
						</ol>
					</div>
				</section>

				<section class="mint-settings-card">
					<header class="mint-settings-card__head">
						<h2 class="mint-settings-card__title"><?php esc_html_e( 'What AI does here', 'mint-lms' ); ?></h2>
					</header>
					<div class="mint-settings-card__body">
						<ul class="mint-ai-page__bullets">
							<li><?php esc_html_e( 'Can draft quiz questions from a topic (review before you save them).', 'mint-lms' ); ?></li>
							<li><?php esc_html_e( 'Does not auto-publish or change student data.', 'mint-lms' ); ?></li>
							<li><?php esc_html_e( 'Prompts may be sent to the provider you selected — check their privacy terms.', 'mint-lms' ); ?></li>
						</ul>
					</div>
				</section>
			</aside>
		</div>
	</form>
</div>
