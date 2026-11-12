<?php
declare(strict_types=1);

namespace MintLMS\Domain\Course;

enum EnrollmentType: string {

	/** Anyone can browse lessons without joining. Logged-in students may still join to track progress. */
	case Open = 'open';

	/** Students must log in and join (free). */
	case Free = 'free';

	/** Only staff can enroll students. */
	case Manual = 'manual';

	/** Closed to self-enroll; access via WooCommerce purchase (or staff enroll). */
	case Paid = 'paid';

	public function allowsSelfEnroll(): bool {
		return self::Open === $this || self::Free === $this;
	}

	public function allowsPublicBrowse(): bool {
		return self::Open === $this;
	}
}
