<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

use MintLMS\Infrastructure\Ai\AiSettings;
use MintLMS\Infrastructure\Setup\PageSettings;

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
$progressPct     = $totalCount > 0 ? (int) round( ( $doneCount / $totalCount ) * 100 ) : 0;
$pagesCreated    = isset( $_GET['mintlms_pages'] ) && '1' === sanitize_text_field( wp_unslash( (string) $_GET['mintlms_pages'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$settingsSaved   = isset( $_GET['settings-updated'] ) && 'true' === sanitize_text_field( wp_unslash( (string) $_GET['settings-updated'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

$wp_pages     = get_pages(
	array(
		'sort_column' => 'post_title',
		'sort_order'  => 'ASC',
	)
);
$page_options = array();
if ( is_array( $wp_pages ) ) {
	foreach ( $wp_pages as $page ) {
		if ( ! $page instanceof WP_Post ) {
			continue;
		}
		$page_options[] = array(
			'id'    => (int) $page->ID,
			'title' => get_the_title( $page ),
		);
	}
}

if ( '' === trim( $certTemplate ) ) {
	$certTemplate = "<div class=\"mint-certificate\">\n  <h1>Certificate of Completion</h1>\n  <p>This certifies that <strong>{{student_name}}</strong> has completed <strong>{{course_name}}</strong> on <strong>{{completion_date}}</strong>.</p>\n</div>";
}

$enrollment_modes = array(
	array(
		'value' => 'open',
		'title' => __( 'Open for everyone', 'mint-lms' ),
		'desc'  => __( 'Browse without signing up', 'mint-lms' ),
	),
	array(
		'value' => 'free',
		'title' => __( 'Free', 'mint-lms' ),
		'desc'  => __( 'Log in to join', 'mint-lms' ),
	),
	array(
		'value' => 'manual',
		'title' => __( 'Invite only', 'mint-lms' ),
		'desc'  => __( 'You add each student', 'mint-lms' ),
	),
	array(
		'value' => 'paid',
		'title' => __( 'Paid', 'mint-lms' ),
		'desc'  => __( 'Sell through WooCommerce', 'mint-lms' ),
	),
);

$cert_tags = array( 'student_name', 'course_name', 'completion_date' );

$boot = array(
	'enrollment'     => $pageSettings->getDefaultEnrollment(),
	'emailEnroll'    => $pageSettings->isEmailEnrollEnabled(),
	'emailComplete'  => $pageSettings->isEmailCompleteEnabled(),
	'aiEnabled'      => $aiSettings->isEnabled(),
	'aiProvider'     => $aiSettings->getProvider(),
	'aiModel'        => (string) get_option( AiSettings::OPTION_MODEL, '' ),
	'aiKey'          => $aiKeyFieldValue,
	'aiUsesConstant' => $aiUsesConstant,
	'aiReady'        => $aiSettings->isReady(),
	'pages'          => $page_options,
	'dashboardId'    => $dashboardId,
	'catalogId'      => $catalogId,
	'playerId'       => $playerId,
	'certTemplate'   => $certTemplate,
	'i18n'           => array(
		'selectPage'   => __( '— Select —', 'mint-lms' ),
		'openai'       => __( 'OpenAI', 'mint-lms' ),
		'anthropic'    => __( 'Anthropic', 'mint-lms' ),
		'testing'      => __( 'Testing…', 'mint-lms' ),
		'connectionOk' => __( 'Connection OK', 'mint-lms' ),
		'testFailed'   => __( 'Test failed', 'mint-lms' ),
		'keyCleared'   => __( 'Saved key will be removed on save.', 'mint-lms' ),
	),
);

$boot_json = wp_json_encode( $boot );
if ( ! is_string( $boot_json ) ) {
	$boot_json = '{}';
}

$checklist_subtitle = $doneCount === $totalCount && $totalCount > 0
	? __( 'Everything you need is ready.', 'mint-lms' )
	: __( 'Finish these steps so students can start learning.', 'mint-lms' );
?>
<div
	class="mint-settings"
	x-data="mintSettings(<?php echo esc_attr( $boot_json ); ?>)"
>
	<?php if ( $pagesCreated ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Student pages created or updated.', 'mint-lms' ); ?></p></div>
	<?php endif; ?>
	<?php if ( $settingsSaved ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Settings saved.', 'mint-lms' ); ?></p></div>
	<?php endif; ?>

	<div class="mint-settings__hero">
		<h1 class="mint-settings__title"><?php esc_html_e( 'Settings', 'mint-lms' ); ?></h1>
		<p class="mint-settings__sub"><?php esc_html_e( 'Set up the pages students see and the defaults for every new course.', 'mint-lms' ); ?></p>
	</div>

	<form id="mintlms-create-pages-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" hidden>
		<?php wp_nonce_field( 'mintlms_create_pages' ); ?>
		<input type="hidden" name="action" value="mintlms_create_pages" />
	</form>

	<form method="post" action="options.php" class="mint-settings__form" id="mintlms-settings-form">
		<?php settings_fields( 'mintlms_settings' ); ?>

		<input type="hidden" name="<?php echo esc_attr( PageSettings::OPTION_DASHBOARD ); ?>" :value="dashboardId" />
		<input type="hidden" name="<?php echo esc_attr( PageSettings::OPTION_CATALOG ); ?>" :value="catalogId" />
		<input type="hidden" name="<?php echo esc_attr( PageSettings::OPTION_PLAYER ); ?>" :value="playerId" />
		<input type="hidden" name="<?php echo esc_attr( PageSettings::OPTION_DEFAULT_ENROLLMENT ); ?>" :value="enrollment" />
		<input type="hidden" name="<?php echo esc_attr( PageSettings::OPTION_EMAIL_ENROLL ); ?>" :value="emailEnroll ? '1' : '0'" />
		<input type="hidden" name="<?php echo esc_attr( PageSettings::OPTION_EMAIL_COMPLETE ); ?>" :value="emailComplete ? '1' : '0'" />
		<input type="hidden" name="<?php echo esc_attr( AiSettings::OPTION_ENABLED ); ?>" :value="aiEnabled ? '1' : '0'" />
		<input type="hidden" name="<?php echo esc_attr( AiSettings::OPTION_PROVIDER ); ?>" :value="aiProvider" />
		<input type="hidden" name="mintlms_ai_clear_key" :value="clearKey ? '1' : '0'" />

		<div class="mint-settings__grid">
			<div class="mint-settings__col mint-settings__col--main">
				<section class="mint-settings-card">
					<header class="mint-settings-card__head">
						<h2 class="mint-settings-card__title"><?php esc_html_e( 'Student pages', 'mint-lms' ); ?></h2>
						<p class="mint-settings-card__desc"><?php esc_html_e( 'Choose which pages show your courses. We create them for you when you start.', 'mint-lms' ); ?></p>
					</header>
					<div class="mint-settings-card__body">
						<div class="mint-settings-pages">
							<div class="mint-settings-field">
								<div class="mint-settings-field__label"><?php esc_html_e( 'Dashboard page', 'mint-lms' ); ?></div>
								<div class="mint-settings-select" :class="{ 'is-open': openSelect === 'dashboard' }">
									<button
										type="button"
										class="mint-settings-select__trigger"
										@click="toggleSelect('dashboard')"
										:aria-expanded="openSelect === 'dashboard' ? 'true' : 'false'"
									>
										<span x-text="pageLabel(dashboardId)"></span>
										<svg width="13" height="13" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m5 8 5 5 5-5" /></svg>
									</button>
									<div class="mint-settings-select__menu" x-show="openSelect === 'dashboard'" x-cloak @click.stop>
										<button type="button" class="mint-settings-select__option" :class="{ 'is-active': dashboardId === 0 }" @click="pickPage('dashboard', 0)" x-text="i18n.selectPage"></button>
										<template x-for="p in pages" :key="'dash-' + p.id">
											<button type="button" class="mint-settings-select__option" :class="{ 'is-active': dashboardId === Number(p.id) }" @click="pickPage('dashboard', p.id)" x-text="p.title"></button>
										</template>
									</div>
								</div>
								<p class="mint-settings-field__help"><?php esc_html_e( 'Where students see their learning', 'mint-lms' ); ?></p>
							</div>

							<div class="mint-settings-field">
								<div class="mint-settings-field__label"><?php esc_html_e( 'Course catalog page', 'mint-lms' ); ?></div>
								<div class="mint-settings-select" :class="{ 'is-open': openSelect === 'catalog' }">
									<button
										type="button"
										class="mint-settings-select__trigger"
										@click="toggleSelect('catalog')"
										:aria-expanded="openSelect === 'catalog' ? 'true' : 'false'"
									>
										<span x-text="pageLabel(catalogId)"></span>
										<svg width="13" height="13" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m5 8 5 5 5-5" /></svg>
									</button>
									<div class="mint-settings-select__menu" x-show="openSelect === 'catalog'" x-cloak @click.stop>
										<button type="button" class="mint-settings-select__option" :class="{ 'is-active': catalogId === 0 }" @click="pickPage('catalog', 0)" x-text="i18n.selectPage"></button>
										<template x-for="p in pages" :key="'cat-' + p.id">
											<button type="button" class="mint-settings-select__option" :class="{ 'is-active': catalogId === Number(p.id) }" @click="pickPage('catalog', p.id)" x-text="p.title"></button>
										</template>
									</div>
								</div>
								<p class="mint-settings-field__help"><?php esc_html_e( 'Where students browse courses', 'mint-lms' ); ?></p>
							</div>

							<div class="mint-settings-field">
								<div class="mint-settings-field__label"><?php esc_html_e( 'Course player page', 'mint-lms' ); ?></div>
								<div class="mint-settings-select" :class="{ 'is-open': openSelect === 'player' }">
									<button
										type="button"
										class="mint-settings-select__trigger"
										@click="toggleSelect('player')"
										:aria-expanded="openSelect === 'player' ? 'true' : 'false'"
									>
										<span x-text="pageLabel(playerId)"></span>
										<svg width="13" height="13" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m5 8 5 5 5-5" /></svg>
									</button>
									<div class="mint-settings-select__menu" x-show="openSelect === 'player'" x-cloak @click.stop>
										<button type="button" class="mint-settings-select__option" :class="{ 'is-active': playerId === 0 }" @click="pickPage('player', 0)" x-text="i18n.selectPage"></button>
										<template x-for="p in pages" :key="'ply-' + p.id">
											<button type="button" class="mint-settings-select__option" :class="{ 'is-active': playerId === Number(p.id) }" @click="pickPage('player', p.id)" x-text="p.title"></button>
										</template>
									</div>
								</div>
								<p class="mint-settings-field__help"><?php esc_html_e( 'Where students take lessons', 'mint-lms' ); ?></p>
							</div>
						</div>
						<div class="mint-settings-card__actions">
							<button type="submit" form="mintlms-create-pages-form" class="mint-settings-btn mint-settings-btn--outline">
								<?php esc_html_e( 'Create or repair student pages', 'mint-lms' ); ?>
							</button>
						</div>
					</div>
				</section>

				<section class="mint-settings-card">
					<header class="mint-settings-card__head">
						<h2 class="mint-settings-card__title"><?php esc_html_e( 'Email notifications', 'mint-lms' ); ?></h2>
						<p class="mint-settings-card__desc"><?php esc_html_e( 'Automatic emails to your students.', 'mint-lms' ); ?></p>
					</header>
					<div class="mint-settings-card__body mint-settings-card__body--tight">
						<div class="mint-settings-toggle-row">
							<div>
								<div class="mint-settings-toggle-row__title"><?php esc_html_e( 'When a student joins', 'mint-lms' ); ?></div>
								<div class="mint-settings-toggle-row__help"><?php esc_html_e( 'Send a welcome email', 'mint-lms' ); ?></div>
							</div>
							<button
								type="button"
								class="mint-settings-switch"
								role="switch"
								:aria-checked="emailEnroll ? 'true' : 'false'"
								:class="{ 'is-on': emailEnroll }"
								@click="toggleEmailEnroll()"
							>
								<span class="mint-settings-switch__knob" aria-hidden="true"></span>
							</button>
						</div>
						<div class="mint-settings-toggle-row">
							<div>
								<div class="mint-settings-toggle-row__title"><?php esc_html_e( 'When a student finishes', 'mint-lms' ); ?></div>
								<div class="mint-settings-toggle-row__help"><?php esc_html_e( 'Send a congratulations email', 'mint-lms' ); ?></div>
							</div>
							<button
								type="button"
								class="mint-settings-switch"
								role="switch"
								:aria-checked="emailComplete ? 'true' : 'false'"
								:class="{ 'is-on': emailComplete }"
								@click="toggleEmailComplete()"
							>
								<span class="mint-settings-switch__knob" aria-hidden="true"></span>
							</button>
						</div>
					</div>
				</section>

				<section class="mint-settings-card mint-settings-card--grow">
					<header class="mint-settings-card__head">
						<h2 class="mint-settings-card__title"><?php esc_html_e( 'Certificate template', 'mint-lms' ); ?></h2>
						<p class="mint-settings-card__desc"><?php esc_html_e( 'What students get when they finish a course. Use the tags below to fill in their details.', 'mint-lms' ); ?></p>
					</header>
					<div class="mint-settings-card__body mint-settings-card__body--grow">
						<div class="mint-settings-tags">
							<span class="mint-settings-tags__label"><?php esc_html_e( 'Insert:', 'mint-lms' ); ?></span>
							<?php foreach ( $cert_tags as $tag ) : ?>
								<button
									type="button"
									class="mint-settings-tag"
									@click="insertCertTag(<?php echo esc_attr( wp_json_encode( $tag ) ); ?>)"
								>{<?php echo esc_html( $tag ); ?>}</button>
							<?php endforeach; ?>
						</div>
						<textarea
							id="mintlms_certificate_template"
							name="mintlms_certificate_template"
							class="mint-settings-textarea"
							rows="10"
							x-ref="certTextarea"
							x-model="certTemplate"
						></textarea>
					</div>
				</section>
			</div>

			<div class="mint-settings__col mint-settings__col--side">
				<section class="mint-settings-card">
					<header class="mint-settings-card__head">
						<h2 class="mint-settings-card__title"><?php esc_html_e( 'Setup checklist', 'mint-lms' ); ?></h2>
						<p class="mint-settings-card__desc"><?php echo esc_html( $checklist_subtitle ); ?></p>
					</header>
					<div class="mint-settings-card__body">
						<div class="mint-settings-progress">
							<div class="mint-settings-progress__track" aria-hidden="true">
								<div class="mint-settings-progress__fill" style="width: <?php echo esc_attr( (string) $progressPct ); ?>%;"></div>
							</div>
							<div class="mint-settings-progress__label">
								<?php
								printf(
									/* translators: 1: completed items, 2: total items */
									esc_html__( '%1$d / %2$d complete', 'mint-lms' ),
									absint( $doneCount ),
									absint( $totalCount )
								);
								?>
							</div>
						</div>
						<ul class="mint-settings-checklist">
							<?php foreach ( $checklist as $item ) : ?>
								<li class="mint-settings-checklist__item<?php echo ! empty( $item['done'] ) ? ' is-done' : ''; ?>" tabindex="0">
									<span class="mint-settings-checklist__icon" aria-hidden="true">
										<?php if ( ! empty( $item['done'] ) ) : ?>
											<svg width="14" height="14" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M4.5 10.5 8 14 15.5 5.5" /></svg>
										<?php else : ?>
											<svg width="14" height="14" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2"><circle cx="10" cy="10" r="7" /></svg>
										<?php endif; ?>
									</span>
									<span class="mint-settings-checklist__text">
										<span class="mint-settings-checklist__title"><?php echo esc_html( $item['label'] ); ?></span>
										<span class="mint-settings-checklist__detail"><?php echo esc_html( $item['detail'] ); ?></span>
									</span>
								</li>
							<?php endforeach; ?>
						</ul>
					</div>
				</section>

				<section class="mint-settings-card">
					<header class="mint-settings-card__head">
						<h2 class="mint-settings-card__title"><?php esc_html_e( 'Enrollment defaults', 'mint-lms' ); ?></h2>
						<p class="mint-settings-card__desc"><?php esc_html_e( 'How new courses let students in. Each course can change this.', 'mint-lms' ); ?></p>
					</header>
					<div class="mint-settings-card__body">
						<div class="mint-settings-enrolls" role="radiogroup" aria-label="<?php esc_attr_e( 'Default enrollment', 'mint-lms' ); ?>">
							<?php foreach ( $enrollment_modes as $mode ) : ?>
								<button
									type="button"
									class="mint-settings-enroll"
									role="radio"
									:aria-checked="enrollment === <?php echo esc_attr( wp_json_encode( $mode['value'] ) ); ?> ? 'true' : 'false'"
									:class="{ 'is-selected': enrollment === <?php echo esc_attr( wp_json_encode( $mode['value'] ) ); ?> }"
									@click="pickEnrollment(<?php echo esc_attr( wp_json_encode( $mode['value'] ) ); ?>)"
								>
									<span class="mint-settings-enroll__radio" aria-hidden="true"><span class="mint-settings-enroll__dot"></span></span>
									<span class="mint-settings-enroll__copy">
										<span class="mint-settings-enroll__title"><?php echo esc_html( $mode['title'] ); ?></span>
										<span class="mint-settings-enroll__desc"><?php echo esc_html( $mode['desc'] ); ?></span>
									</span>
								</button>
							<?php endforeach; ?>
						</div>
					</div>
				</section>

				<section class="mint-settings-card" id="mintlms-ai-settings">
					<header class="mint-settings-card__head">
						<h2 class="mint-settings-card__title"><?php esc_html_e( 'AI assistant (optional)', 'mint-lms' ); ?></h2>
						<p class="mint-settings-card__desc"><?php esc_html_e( 'Let AI help you draft lessons. It stays off until you switch it on.', 'mint-lms' ); ?></p>
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
			</div>
		</div>

		<div class="mint-settings__footer">
			<p class="mint-settings__footer-note"><?php esc_html_e( 'Changes apply to your whole site once saved.', 'mint-lms' ); ?></p>
			<button type="submit" class="mint-settings-btn mint-settings-btn--primary">
				<?php esc_html_e( 'Save settings', 'mint-lms' ); ?>
			</button>
		</div>
	</form>
</div>
