<?php
declare(strict_types=1);

namespace MintLMS\Application\Contract;

defined( 'ABSPATH' ) || exit;

interface CertificateTemplateProviderInterface {

	public function getTemplate(): string;

	public function getDefaultTemplate(): string;

	public function getDateFormat(): string;
}
