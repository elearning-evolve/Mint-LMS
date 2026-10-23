<?php
declare(strict_types=1);

namespace MintLMS\Domain\Course;

/**
 * Per-course creator options (Course Settings).
 */
final readonly class CourseSettings {

	public const PROGRESSION_LINEAR   = 'linear';
	public const PROGRESSION_FREEFORM = 'freeform';
	public const PREREQ_ANY           = 'ANY';
	public const PREREQ_ALL           = 'ALL';

	/**
	 * @param list<int> $prerequisiteCourseIds
	 */
	public function __construct(
		public bool $emailOnPublish = true,
		public bool $studentComplete = true,
		public bool $certificate = false,
		public string $progression = self::PROGRESSION_LINEAR,
		public bool $expireAccess = false,
		public int $expireAccessDays = 30,
		public bool $prerequisitesEnabled = false,
		public array $prerequisiteCourseIds = array(),
		public string $prerequisiteCompare = self::PREREQ_ANY,
		public ?string $accessStartAt = null,
		public ?string $accessEndAt = null,
		public int $seatLimit = 0,
		public bool $materialsEnabled = false,
		public string $materialsContent = '',
		public string $lessonListVisibility = 'everyone',
		public bool $paginationEnabled = false,
		public int $lessonsPerPage = 20,
		public int $groupsPerPage = 20,
		public string $completionRedirect = '',
		public int $pointsOnComplete = 0,
	) {
	}

	public static function defaults(): self {
		return new self();
	}

	/**
	 * @param array<string, mixed>|null $data
	 */
	public static function fromArray( ?array $data ): self {
		if ( null === $data ) {
			return self::defaults();
		}

		$progression = isset( $data['progression'] ) && is_string( $data['progression'] )
			? $data['progression']
			: self::PROGRESSION_LINEAR;
		if ( ! in_array( $progression, array( self::PROGRESSION_LINEAR, self::PROGRESSION_FREEFORM ), true ) ) {
			$progression = self::PROGRESSION_LINEAR;
		}

		$compare = isset( $data['prerequisiteCompare'] ) && is_string( $data['prerequisiteCompare'] )
			? strtoupper( $data['prerequisiteCompare'] )
			: self::PREREQ_ANY;
		if ( ! in_array( $compare, array( self::PREREQ_ANY, self::PREREQ_ALL ), true ) ) {
			$compare = self::PREREQ_ANY;
		}

		$prereqIds = array();
		if ( isset( $data['prerequisiteCourseIds'] ) && is_array( $data['prerequisiteCourseIds'] ) ) {
			foreach ( $data['prerequisiteCourseIds'] as $id ) {
				$id = (int) $id;
				if ( $id > 0 ) {
					$prereqIds[] = $id;
				}
			}
			$prereqIds = array_values( array_unique( $prereqIds ) );
		}

		$start = self::normalizeDate( $data['accessStartAt'] ?? null );
		$end   = self::normalizeDate( $data['accessEndAt'] ?? null );

		$days = isset( $data['expireAccessDays'] ) ? (int) $data['expireAccessDays'] : 30;
		if ( $days < 1 ) {
			$days = 1;
		}
		if ( $days > 3650 ) {
			$days = 3650;
		}

		$seats = isset( $data['seatLimit'] ) ? (int) $data['seatLimit'] : 0;
		if ( $seats < 0 ) {
			$seats = 0;
		}

		$visibility = isset( $data['lessonListVisibility'] ) && is_string( $data['lessonListVisibility'] )
			? $data['lessonListVisibility']
			: 'everyone';
		if ( ! in_array( $visibility, array( 'everyone', 'enrolled' ), true ) ) {
			$visibility = 'everyone';
		}

		$lessonsPerPage = isset( $data['lessonsPerPage'] ) ? (int) $data['lessonsPerPage'] : 20;
		if ( $lessonsPerPage < 1 ) {
			$lessonsPerPage = 1;
		}
		if ( $lessonsPerPage > 100 ) {
			$lessonsPerPage = 100;
		}

		$groupsPerPage = isset( $data['groupsPerPage'] ) ? (int) $data['groupsPerPage'] : 20;
		if ( $groupsPerPage < 1 ) {
			$groupsPerPage = 1;
		}
		if ( $groupsPerPage > 100 ) {
			$groupsPerPage = 100;
		}

		$points = isset( $data['pointsOnComplete'] ) ? (int) $data['pointsOnComplete'] : 0;
		if ( $points < 0 ) {
			$points = 0;
		}

		$materials = isset( $data['materialsContent'] ) && is_string( $data['materialsContent'] )
			? $data['materialsContent']
			: '';
		$redirect  = isset( $data['completionRedirect'] ) && is_string( $data['completionRedirect'] )
			? $data['completionRedirect']
			: '';

		return new self(
			array_key_exists( 'emailOnPublish', $data ) ? (bool) $data['emailOnPublish'] : true,
			array_key_exists( 'studentComplete', $data ) ? (bool) $data['studentComplete'] : true,
			array_key_exists( 'certificate', $data ) ? (bool) $data['certificate'] : false,
			$progression,
			array_key_exists( 'expireAccess', $data ) ? (bool) $data['expireAccess'] : false,
			$days,
			array_key_exists( 'prerequisitesEnabled', $data ) ? (bool) $data['prerequisitesEnabled'] : false,
			$prereqIds,
			$compare,
			$start,
			$end,
			$seats,
			array_key_exists( 'materialsEnabled', $data ) ? (bool) $data['materialsEnabled'] : false,
			$materials,
			$visibility,
			array_key_exists( 'paginationEnabled', $data ) ? (bool) $data['paginationEnabled'] : false,
			$lessonsPerPage,
			$groupsPerPage,
			$redirect,
			$points,
		);
	}

	/**
	 * @return array{
	 *   emailOnPublish: bool,
	 *   studentComplete: bool,
	 *   certificate: bool,
	 *   progression: string,
	 *   expireAccess: bool,
	 *   expireAccessDays: int,
	 *   prerequisitesEnabled: bool,
	 *   prerequisiteCourseIds: list<int>,
	 *   prerequisiteCompare: string,
	 *   accessStartAt: ?string,
	 *   accessEndAt: ?string,
	 *   seatLimit: int
	 * }
	 */
	public function toArray(): array {
		return array(
			'emailOnPublish'         => $this->emailOnPublish,
			'studentComplete'        => $this->studentComplete,
			'certificate'            => $this->certificate,
			'progression'            => $this->progression,
			'expireAccess'           => $this->expireAccess,
			'expireAccessDays'       => $this->expireAccessDays,
			'prerequisitesEnabled'   => $this->prerequisitesEnabled,
			'prerequisiteCourseIds'  => $this->prerequisiteCourseIds,
			'prerequisiteCompare'    => $this->prerequisiteCompare,
			'accessStartAt'          => $this->accessStartAt,
			'accessEndAt'            => $this->accessEndAt,
			'seatLimit'              => $this->seatLimit,
			'materialsEnabled'       => $this->materialsEnabled,
			'materialsContent'       => $this->materialsContent,
			'lessonListVisibility'   => $this->lessonListVisibility,
			'paginationEnabled'      => $this->paginationEnabled,
			'lessonsPerPage'         => $this->lessonsPerPage,
			'groupsPerPage'          => $this->groupsPerPage,
			'completionRedirect'     => $this->completionRedirect,
			'pointsOnComplete'       => $this->pointsOnComplete,
		);
	}

	/**
	 * @param array<string, mixed> $patch
	 */
	public function merge( array $patch ): self {
		return self::fromArray( array_merge( $this->toArray(), $patch ) );
	}

	public function isLinear(): bool {
		return self::PROGRESSION_FREEFORM !== $this->progression;
	}

	public function with(
		?bool $emailOnPublish = null,
		?bool $studentComplete = null,
		?bool $certificate = null,
	): self {
		return $this->merge(
			array_filter(
				array(
					'emailOnPublish'  => $emailOnPublish,
					'studentComplete' => $studentComplete,
					'certificate'     => $certificate,
				),
				static fn( $v ) => null !== $v
			)
		);
	}

	private static function normalizeDate( mixed $value ): ?string {
		if ( ! is_string( $value ) ) {
			return null;
		}

		$value = trim( $value );
		if ( '' === $value ) {
			return null;
		}

		if ( preg_match( '/^\d{4}-\d{2}-\d{2}/', $value, $m ) ) {
			return $m[0];
		}

		$ts = strtotime( $value );
		if ( false === $ts ) {
			return null;
		}

		return gmdate( 'Y-m-d', $ts );
	}
}
