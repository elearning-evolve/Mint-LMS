<?php
declare(strict_types=1);

namespace MintLMS\Api\V1\Registry;

final class PaymentGatewayRegistry {

	/** @var array<string, PaymentGatewayInterface> */
	private static array $gateways = array();

	public static function register( PaymentGatewayInterface $gateway ): void {
		self::$gateways[ $gateway->slug() ] = $gateway;
	}

	public static function get( string $slug ): ?PaymentGatewayInterface {
		return self::$gateways[ $slug ] ?? null;
	}

	/**
	 * @return array<string, PaymentGatewayInterface>
	 */
	public static function all(): array {
		return self::$gateways;
	}

	public static function has( string $slug ): bool {
		return isset( self::$gateways[ $slug ] );
	}
}
