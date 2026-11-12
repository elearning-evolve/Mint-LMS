<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

use MintLMS\Api\V1\Registry\CertificateTemplateInterface;
use MintLMS\Api\V1\Registry\CertificateTemplateRegistry;
use MintLMS\Api\V1\Registry\LessonContentTypeInterface;
use MintLMS\Api\V1\Registry\LessonContentTypeRegistry;
use MintLMS\Api\V1\Registry\PaymentGatewayInterface;
use MintLMS\Api\V1\Registry\PaymentGatewayRegistry;
use MintLMS\Api\V1\Registry\ProgressRuleInterface;
use MintLMS\Api\V1\Registry\ProgressRuleRegistry;
use MintLMS\Api\V1\Registry\QuestionTypeInterface;
use MintLMS\Api\V1\Registry\QuestionTypeRegistry;

/**
 * WordPress DB API handle (single access point — prefer over scattered `global $wpdb`).
 *
 * Core still owns `$wpdb`; plugins should not invent a second connection.
 */
function mintlms_wpdb(): ?\wpdb {
	global $wpdb;

	return $wpdb instanceof \wpdb ? $wpdb : null;
}

function mintlms_register_question_type( QuestionTypeInterface $type ): void {
	QuestionTypeRegistry::register( $type );
}

function mintlms_register_lesson_content_type( LessonContentTypeInterface $type ): void {
	LessonContentTypeRegistry::register( $type );
}

function mintlms_register_certificate_template( CertificateTemplateInterface $template ): void {
	CertificateTemplateRegistry::register( $template );
}

function mintlms_register_payment_gateway( PaymentGatewayInterface $gateway ): void {
	PaymentGatewayRegistry::register( $gateway );
}

function mintlms_register_progress_rule( ProgressRuleInterface $rule ): void {
	ProgressRuleRegistry::register( $rule );
}
