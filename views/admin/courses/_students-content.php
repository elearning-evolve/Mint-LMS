<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template partial variables.

use MintLMS\Application\Exception\ForbiddenException;
use MintLMS\Application\Exception\NotFoundException;
use MintLMS\Infrastructure\Admin\ViewRenderer;
use MintLMS\Plugin;

/** @var ViewRenderer $renderer */
/** @var int $courseId */

$students      = array();
$pagination    = array(
	'current'  => 1,
	'total'    => 1,
	'base_url' => admin_url( 'admin.php?page=mint-lms-course-students&course_id=' . $courseId ),
);
$error         = '';
$totalStudents = 0;
$totalFinished = 0;

$hue_tile_classes = array(
	1 => 'mint-bg-hue-1 mint-text-hue-1i',
	2 => 'mint-bg-hue-2 mint-text-hue-2i',
	3 => 'mint-bg-hue-3 mint-text-hue-3i',
	4 => 'mint-bg-hue-4 mint-text-hue-4i',
	5 => 'mint-bg-hue-5 mint-text-hue-5i',
	6 => 'mint-bg-hue-6 mint-text-hue-6i',
);

if ( $courseId <= 0 ) {
	$error = '';
} else {
	try {
		$userId = get_current_user_id();
		$page   = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		$result = Plugin::enrollmentService()->listStudentsForCourse( $courseId, $page, 20, $userId );

		$totalStudents = $result->total;

		foreach ( $result->students as $index => $student ) {
			$user  = get_userdata( $student->userId );
			$name  = $user instanceof \WP_User ? $user->display_name : sprintf(
				/* translators: %d: user ID */
				__( 'User #%d', 'mint-lms' ),
				$student->userId
			);
			$email = $user instanceof \WP_User ? $user->user_email : '';

			$progressPct = (int) round( $student->progressPct );
			if ( 100 === $progressPct ) {
				++$totalFinished;
			}

			$hueIdx   = ( $index % 6 ) + 1;
			$initials = mb_strtoupper( mb_substr( $name, 0, 1 ) );

			$students[] = array(
				'name'         => esc_html( $name ),
				'email'        => esc_html( $email ),
				'initials'     => $initials,
				'hueIdx'       => $hueIdx,
				'progressPct'  => $progressPct,
				'enrolled'     => esc_html( wp_date( get_option( 'date_format' ), strtotime( $student->enrolledAt ) ) ),
				'enrollmentId' => $student->enrollmentId,
			);
		}

		$perPage    = max( 1, $result->perPage );
		$totalPages = max( 1, (int) ceil( $result->total / $perPage ) );
		$pagination = array(
			'current'  => $result->page,
			'total'    => $totalPages,
			'base_url' => admin_url( 'admin.php?page=mint-lms-course-students&course_id=' . $courseId ),
		);

	} catch ( NotFoundException $exception ) {
		$error = $exception->getMessage();
	} catch ( ForbiddenException $exception ) {
		$error = $exception->getMessage();
	}
}

$studentsPageUrl = admin_url( 'admin.php?page=mint-lms-course-students' );
$inputClass      = 'mint-block mint-w-full mint-rounded-lg mint-border-[1.5px] mint-border-[#B9B6CE] mint-bg-bg mint-px-[14px] mint-py-[11px] mint-text-base mint-text-ink hover:mint-border-mint-blue focus:mint-border-mint-blue focus:mint-shadow-focus focus:mint-outline-none';
?>

<?php if ( $courseId <= 0 ) : ?>
<div
	class="mint-mx-auto mint-max-w-[560px] mint-px-7 mint-pb-24 mint-pt-12"
	x-data="{
		courses: [],
		loading: true,
		search: '',
		get filtered() {
			if (!this.search) return this.courses;
			const q = this.search.toLowerCase();
			return this.courses.filter(c => c.title.toLowerCase().includes(q));
		}
	}"
	x-init="
		fetch(mintLmsAdmin.restBase + '/courses?per_page=100', {
			headers: { 'X-WP-Nonce': mintLmsAdmin.nonce }
		})
		.then(r => r.json())
		.then(json => { courses = json.data?.items || []; loading = false; })
		.catch(() => { loading = false; })
	"
>
	<h1 class="mint-m-0 mint-mb-2 mint-text-[48px] mint-font-semibold mint-leading-[52px] mint-tracking-[-0.04em] mint-text-ink">
		<?php echo esc_html__( 'Students', 'mint-lms' ); ?>
	</h1>
	<p class="mint-m-0 mint-mb-8 mint-text-[19px] mint-leading-7 mint-text-ink-2">
		<?php echo esc_html__( 'Pick a course to view its students.', 'mint-lms' ); ?>
	</p>

	<input
		type="text"
		class="<?php echo esc_attr( $inputClass ); ?> mint-mb-5 mint-max-w-[380px]"
		placeholder="<?php echo esc_attr__( 'Search courses…', 'mint-lms' ); ?>"
		x-model="search"
	/>

	<div x-show="loading" class="mint-py-6 mint-text-sm mint-text-ink-3">
		<?php echo esc_html__( 'Loading courses…', 'mint-lms' ); ?>
	</div>

	<div x-show="!loading && filtered.length === 0" x-cloak class="mint-py-6 mint-text-sm mint-text-ink-3">
		<?php echo esc_html__( 'No courses found.', 'mint-lms' ); ?>
	</div>

	<div x-show="!loading" x-cloak class="mint-flex mint-flex-col mint-gap-1">
		<template x-for="course in filtered" :key="course.id">
			<a
				:href="'<?php echo esc_url( $studentsPageUrl ); ?>&course_id=' + course.id"
				class="mint-flex mint-items-center mint-gap-[14px] mint-rounded-md mint-px-[14px] mint-py-3 mint-text-ink mint-no-underline hover:mint-bg-bg-subtle"
			>
				<span
					class="mint-flex mint-h-11 mint-w-11 mint-shrink-0 mint-items-center mint-justify-center mint-rounded-xl mint-text-lg mint-font-bold"
					:class="{
						'mint-bg-hue-1 mint-text-hue-1i': (course.id % 6) === 1,
						'mint-bg-hue-2 mint-text-hue-2i': (course.id % 6) === 2,
						'mint-bg-hue-3 mint-text-hue-3i': (course.id % 6) === 3,
						'mint-bg-hue-4 mint-text-hue-4i': (course.id % 6) === 4,
						'mint-bg-hue-5 mint-text-hue-5i': (course.id % 6) === 5,
						'mint-bg-hue-6 mint-text-hue-6i': (course.id % 6) === 0
					}"
					x-text="course.title ? course.title.charAt(0).toUpperCase() : '?'"
				></span>
				<span class="mint-text-base mint-font-semibold mint-tracking-tight" x-text="course.title"></span>
			</a>
		</template>
	</div>
</div>

<?php elseif ( '' !== $error ) : ?>
<div class="mint-px-7 mint-py-12">
	<?php
		echo $renderer->component( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ViewRenderer returns escaped component HTML.
			'error-state',
			array(
				'title'   => esc_html__( 'Unable to load students', 'mint-lms' ),
				'message' => esc_html( $error ),
			)
		);
	?>
</div>

<?php else : ?>
<div class="mint-mx-auto mint-max-w-content mint-px-7 mint-pb-24 mint-pt-12">

	<h1 class="mint-m-0 mint-mb-[6px] mint-text-[48px] mint-font-semibold mint-leading-[52px] mint-tracking-[-0.04em] mint-text-ink">
		<?php echo esc_html__( 'Students', 'mint-lms' ); ?>
	</h1>

	<p class="mint-m-0 mint-mb-8 mint-text-[19px] mint-leading-7 mint-text-ink-2">
		<?php
		printf(
			/* translators: 1: total count, 2: finished count */
			esc_html__( '%1$s students in this course · %2$s have finished', 'mint-lms' ),
			'<span class="mint-tabular-nums">' . esc_html( (string) $totalStudents ) . '</span>',
			'<span class="mint-tabular-nums">' . esc_html( (string) $totalFinished ) . '</span>'
		);
		?>
	</p>

	<div
		class="mint-mb-7 mint-flex mint-flex-wrap mint-items-center mint-gap-3"
		x-data="{ courses: [], loading: true }"
		x-init="
			fetch(mintLmsAdmin.restBase + '/courses?per_page=100', {
				headers: { 'X-WP-Nonce': mintLmsAdmin.nonce }
			})
			.then(r => r.json())
			.then(json => { courses = json.data?.items || []; loading = false; })
			.catch(() => { loading = false; })
		"
	>
		<div class="mint-relative mint-min-w-[290px]">
			<select
				class="<?php echo esc_attr( $inputClass ); ?> mint-h-control-lg mint-pr-10"
				onchange="if(this.value) window.location='<?php echo esc_url( $studentsPageUrl ); ?>&course_id='+this.value"
			>
				<option value="" disabled><?php esc_html_e( 'Select a course…', 'mint-lms' ); ?></option>
				<template x-for="course in courses" :key="course.id">
					<option :value="course.id" :selected="course.id === <?php echo (int) $courseId; ?>" x-text="course.title"></option>
				</template>
			</select>
		</div>
	</div>

	<div
		class="mint-mb-8 mint-flex mint-flex-wrap mint-items-end mint-gap-3 mint-rounded-[14px] mint-border mint-border-tint-line mint-bg-tint mint-p-[22px]"
		x-data="{ enrollEmail: '' }"
	>
		<div class="mint-min-w-[200px] mint-flex-1">
			<label for="mint-enroll-email" class="mint-mb-2 mint-block mint-text-sm mint-font-medium mint-text-ink">
				<?php echo esc_html__( 'Add a student', 'mint-lms' ); ?>
			</label>
			<input
				id="mint-enroll-email"
				type="text"
				class="<?php echo esc_attr( $inputClass ); ?> mint-bg-bg"
				placeholder="<?php echo esc_attr__( 'Email or user ID', 'mint-lms' ); ?>"
				x-model="enrollEmail"
			/>
		</div>
		<button
			type="button"
			class="mint-cta-lime mint-inline-flex mint-h-control-lg mint-items-center mint-justify-center mint-rounded-lg mint-border-0 mint-px-[18px] mint-text-base mint-font-semibold"
			id="mint-enroll-student"
			data-mint-course="<?php echo esc_attr( (string) $courseId ); ?>"
		>
			<?php echo esc_html__( 'Add to course', 'mint-lms' ); ?>
		</button>
	</div>

	<?php if ( empty( $students ) ) : ?>
		<?php
		echo $renderer->component( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ViewRenderer returns escaped component HTML.
			'empty-state',
			array(
				'title'       => esc_html__( 'No students enrolled', 'mint-lms' ),
				'description' => esc_html__( 'Enroll students manually or share an open enrollment link.', 'mint-lms' ),
			)
		);
		?>
	<?php else : ?>
		<div class="mint-w-full">

			<div class="mint-grid mint-grid-cols-[minmax(0,1fr)_132px_120px_84px_84px] mint-items-center mint-rounded-lg mint-px-4 mint-py-[13px] mint-text-sm mint-font-semibold mint-text-ink-2">
				<span><?php echo esc_html__( 'Student', 'mint-lms' ); ?></span>
				<span><?php echo esc_html__( 'Where they are', 'mint-lms' ); ?></span>
				<span><?php echo esc_html__( 'Progress', 'mint-lms' ); ?></span>
				<span><?php echo esc_html__( 'Joined', 'mint-lms' ); ?></span>
				<span></span>
			</div>

			<?php foreach ( $students as $s ) :
				$hueClass = $hue_tile_classes[ (int) $s['hueIdx'] ];
			?>
			<div
				class="mint-grid mint-grid-cols-[minmax(0,1fr)_132px_120px_84px_84px] mint-items-center mint-border-b mint-border-rule-soft mint-px-4 mint-py-[14px]"
				data-mint-student-row
				data-mint-student-name="<?php echo esc_attr( wp_strip_all_tags( $s['name'] ) ); ?>"
				data-mint-student-email="<?php echo esc_attr( wp_strip_all_tags( $s['email'] ) ); ?>"
				data-mint-student-progress="<?php echo (int) $s['progressPct']; ?>%"
				data-mint-student-joined="<?php echo esc_attr( wp_strip_all_tags( $s['enrolled'] ) ); ?>"
			>
				<div class="mint-flex mint-min-w-0 mint-items-center mint-gap-3">
					<span class="mint-flex mint-h-10 mint-w-10 mint-shrink-0 mint-items-center mint-justify-center mint-rounded-full mint-text-sm mint-font-bold <?php echo esc_attr( $hueClass ); ?>">
						<?php echo esc_html( $s['initials'] ); ?>
					</span>
					<div class="mint-min-w-0">
						<span class="mint-block mint-truncate mint-text-row mint-font-semibold mint-text-ink">
							<?php echo esc_html( $s['name'] ); ?>
						</span>
						<span class="mint-block mint-truncate mint-text-sm mint-leading-5 mint-text-ink-2">
							<?php echo esc_html( $s['email'] ); ?>
						</span>
					</div>
				</div>

				<span class="mint-text-sm mint-text-ink-2">–</span>

				<div class="mint-flex mint-items-center mint-gap-2">
					<div class="mint-h-[9px] mint-flex-1 mint-overflow-hidden mint-rounded-full mint-bg-bg-track">
						<div class="mint-h-full mint-rounded-full mint-bg-accent mint-w-[<?php echo (int) $s['progressPct']; ?>%]"></div>
					</div>
					<span class="mint-min-w-[30px] mint-tabular-nums mint-text-right mint-text-sm mint-font-semibold mint-text-ink-2">
						<?php echo (int) $s['progressPct']; ?>%
					</span>
				</div>

				<span class="mint-tabular-nums mint-text-sm mint-text-ink-2">
					<?php echo esc_html( $s['enrolled'] ); ?>
				</span>

				<div class="mint-text-right">
					<button
						type="button"
						class="mint-cursor-pointer mint-border-0 mint-bg-transparent mint-p-1 mint-text-base mint-font-semibold mint-text-danger hover:mint-text-danger-hover focus:mint-outline-none"
						data-mint-unenroll="<?php echo esc_attr( (string) $s['enrollmentId'] ); ?>"
						data-mint-course="<?php echo esc_attr( (string) $courseId ); ?>"
					>
						<?php echo esc_html__( 'Remove', 'mint-lms' ); ?>
					</button>
				</div>
			</div>
			<?php endforeach; ?>
		</div>

		<?php if ( $pagination['total'] > 1 ) : ?>
		<div class="mint-mt-7 mint-flex mint-items-center mint-justify-center mint-gap-2">
			<?php for ( $p = 1; $p <= $pagination['total']; $p++ ) : ?>
				<?php if ( $p === $pagination['current'] ) : ?>
					<span class="mint-inline-flex mint-h-control-md mint-w-control-md mint-items-center mint-justify-center mint-rounded-md mint-bg-accent mint-text-sm mint-font-semibold mint-text-neutral-50 mint-pointer-events-none">
						<?php echo (int) $p; ?>
					</span>
				<?php else : ?>
					<a
						href="<?php echo esc_url( $pagination['base_url'] . '&paged=' . $p ); ?>"
						class="mint-inline-flex mint-h-control-md mint-min-w-control-md mint-items-center mint-justify-center mint-rounded-md mint-bg-transparent mint-px-[14px] mint-text-sm mint-font-semibold mint-text-ink-2 mint-no-underline hover:mint-bg-bg-subtle"
					>
						<?php echo (int) $p; ?>
					</a>
				<?php endif; ?>
			<?php endfor; ?>
		</div>
		<?php endif; ?>
	<?php endif; ?>

	<div class="mint-hidden" aria-hidden="true">
		<?php for ( $i = 0; $i <= 100; $i++ ) : ?>
			<span class="mint-w-[<?php echo esc_attr( (string) $i ); ?>%]"></span>
		<?php endfor; ?>
	</div>
</div>
<?php endif; ?>
