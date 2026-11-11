<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __DIR__ ) . '/' );
}

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/../vendor/autoload.php';

define( 'MINTLMS_VERSION', '0.1.0' );
define( 'MINTLMS_FILE', __DIR__ . '/../mint-lms.php' );
define( 'MINTLMS_PATH', __DIR__ . '/../' );
define( 'MINTLMS_URL', 'http://example.com/wp-content/plugins/mint-lms/' );
