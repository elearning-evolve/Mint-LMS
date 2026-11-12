<?php
declare(strict_types=1);

namespace MintLMS\Api\V1\Registry;

interface PaymentGatewayInterface {

	public function slug(): string;

	public function label(): string;
}
