<?php
declare(strict_types=1);

namespace MintLMS\Domain\Course;

enum CourseStatus: string {

	case Draft     = 'draft';
	case Published = 'published';
	case Archived  = 'archived';
	case Trashed   = 'trashed';
}
