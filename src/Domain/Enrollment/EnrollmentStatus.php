<?php
declare(strict_types=1);

namespace MintLMS\Domain\Enrollment;

enum EnrollmentStatus: string {

	case Active    = 'active';
	case Cancelled = 'cancelled';
	case Completed = 'completed';
}
