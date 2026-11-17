<?php
/**
 * Registers REST API endpoints for consent management.
 *
 * @package LCACC
 */

namespace LCACC;

defined( 'ABSPATH' ) || exit;

/**
 * Handles consent submissions and secure cookie storage.
 */
final class REST {

	/**
	 * Consent service.
	 *
	 * @var Consent
	 */
	private Consent $consent;

	/**
	 * Cookie name.
	 *
	 * @var string
	 */
	private const COOKIE_NAME = 'lcacc_consent';

	/**
	 * Consent retention period in seconds.
	 *
	 * @var int
	 */
	private const COOKIE_LIFETIME = 15552000; // 180 days.

	/**
	 * Constructor.
	 *
	 * @param Consent $consent Consent service.
	 */
	public function __construct( Consent $consent ) {
		$this->consent = $consent;
	}

	/**
	 * Register REST API hooks.
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_action(
			'rest_api_init',
			array( $this, 'register_routes' )
		);
	}

	/**
	 * Register consent endpoints.
	 *
	 * @return void
	 */
	public function register_routes(): void {
		register_rest_route(
			'lcacc/v1',
			'/consent',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'save_consent' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			'lcacc/v1',
			'/consent',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_consent' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * Validate, sign and store a consent decision.
	 *
	 * @param \WP_REST_Request $request REST request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function save_consent( \WP_REST_Request $request ) {
		$params = $request->get_json_params();

		if ( ! is_array( $params ) ) {
			return new \WP_Error(
				'lcacc_invalid_request',
				__( 'A valid JSON request is required.', 'lc-advanced-cookie-consent' ),
				array( 'status' => 400 )
			);
		}

		$decision = isset( $params['decision'] ) && is_string( $params['decision'] )
			? sanitize_key( $params['decision'] )
			: '';

		$allowed_decisions = array(
			'accepted',
			'rejected',
			'customized',
		);

		if ( ! in_array( $decision, $allowed_decisions, true ) ) {
			return new \WP_Error(
				'lcacc_invalid_decision',
				__( 'The consent decision is invalid.', 'lc-advanced-cookie-consent' ),
				array( 'status' => 400 )
			);
		}

		if (
			! isset( $params['preferences'] )
			|| ! is_array( $params['preferences'] )
		) {
			return new \WP_Error(
				'lcacc_invalid_preferences',
				__( 'Valid consent preferences are required.', 'lc-advanced-cookie-consent' ),
				array( 'status' => 400 )
			);
		}

		$record = $this->consent->create_record(
			$params['preferences'],
			$decision
		);

		$json = wp_json_encode( $record );

		if ( false === $json ) {
			return new \WP_Error(
				'lcacc_encoding_failed',
				__( 'The consent record could not be created.', 'lc-advanced-cookie-consent' ),
				array( 'status' => 500 )
			);
		}

		$payload = rtrim(
			strtr( base64_encode( $json ), '+/', '-_' ),
			'='
		);

		$signature = hash_hmac(
			'sha256',
			$payload,
			wp_salt( 'auth' )
		);

		$value = $payload . '.' . $signature;

		$cookie_options = array(
			'expires'  => time() + self::COOKIE_LIFETIME,
			'path'     => defined( 'COOKIEPATH' ) && COOKIEPATH
				? COOKIEPATH
				: '/',
			'secure'   => is_ssl(),
			'httponly' => true,
			'samesite' => 'Lax',
		);

		if ( defined( 'COOKIE_DOMAIN' ) && COOKIE_DOMAIN ) {
			$cookie_options['domain'] = COOKIE_DOMAIN;
		}

		$cookie_set = setcookie(
			self::COOKIE_NAME,
			$value,
			$cookie_options
		);

		if ( ! $cookie_set ) {
			return new \WP_Error(
				'lcacc_cookie_failed',
				__( 'The consent cookie could not be saved.', 'lc-advanced-cookie-consent' ),
				array( 'status' => 500 )
			);
		}

		$response = new \WP_REST_Response(
			array(
				'success'    => true,
				'decision'   => $record['decision'],
				'expires_in' => self::COOKIE_LIFETIME,
			),
			200
		);

		$response->header( 'Cache-Control', 'no-store, private' );

		return $response;
	}

	/**
	 * Retrieve and verify the visitor's saved consent.
	 *
	 * @return \WP_REST_Response
	 */
	public function get_consent(): \WP_REST_Response {
		$record = $this->read_signed_cookie();

		$response = new \WP_REST_Response(
			array(
				'has_consent' => null !== $record,
				'consent'     => $record,
			),
			200
		);

		$response->header( 'Cache-Control', 'no-store, private' );

		return $response;
	}

	/**
	 * Verify and decode the signed consent cookie.
	 *
	 * @return array<string, mixed>|null
	 */
	private function read_signed_cookie(): ?array {
		if (
			! isset( $_COOKIE[ self::COOKIE_NAME ] )
			|| ! is_string( $_COOKIE[ self::COOKIE_NAME ] )
		) {
			return null;
		}

		$cookie = wp_unslash( $_COOKIE[ self::COOKIE_NAME ] );

		if (
			! preg_match(
				'/\A[A-Za-z0-9_-]+\.[a-f0-9]{64}\z/',
				$cookie
			)
		) {
			return null;
		}

		$parts = explode( '.', $cookie, 2 );

		if ( 2 !== count( $parts ) ) {
			return null;
		}

		$payload   = $parts[0];
		$signature = $parts[1];

		$expected_signature = hash_hmac(
			'sha256',
			$payload,
			wp_salt( 'auth' )
		);

		if ( ! hash_equals( $expected_signature, $signature ) ) {
			return null;
		}

		$base64  = strtr( $payload, '-_', '+/' );
		$base64 .= str_repeat(
			'=',
			( 4 - strlen( $base64 ) % 4 ) % 4
		);

		$json = base64_decode( $base64, true );

		if ( false === $json ) {
			return null;
		}

		$record = json_decode( $json, true );

		if (
			! is_array( $record )
			|| ! isset(
				$record['version'],
				$record['decision'],
				$record['preferences'],
				$record['consented_at']
			)
			|| ! is_string( $record['version'] )
			|| '1.0' !== $record['version']
			|| ! is_array( $record['preferences'] )
			|| ! is_string( $record['decision'] )
			|| ! is_string( $record['consented_at'] )
		) {
			return null;
		}

		if (
			! in_array(
				$record['decision'],
				array( 'accepted', 'rejected', 'customized' ),
				true
			)
		) {
			return null;
		}

		$record['preferences'] = $this->consent->normalize_preferences(
			$record['preferences']
		);

		return $record;
	}
}

