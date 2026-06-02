<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class REVAA_PDF_Endpoint {

	public static function init() {
		self::register_rewrite_rule();
		add_filter( 'query_vars', [ __CLASS__, 'add_query_vars' ] );
		add_action( 'template_redirect', [ __CLASS__, 'handle_request' ] );
	}

	public static function register_rewrite_rule() {
		add_rewrite_rule(
			'revaa-pdf/([^/]+)/?$',
			'index.php?revaa_pdf_file=$matches[1]',
			'top'
		);
	}

	public static function add_query_vars( array $vars ): array {
		$vars[] = 'revaa_pdf_file';
		return $vars;
	}

	public static function handle_request() {
		$slug = get_query_var( 'revaa_pdf_file' );
		if ( ! $slug ) {
			return;
		}

		if ( ! is_user_logged_in() ) {
			wp_die( 'Accès refusé', 'Accès refusé', [ 'response' => 403 ] );
		}

		$filename = sanitize_file_name( $slug ) . '.pdf';
		$path     = REVAA_PDF_Storage::get_private_dir() . $filename;

		if ( ! file_exists( $path ) ) {
			wp_die( 'Document introuvable', 'Introuvable', [ 'response' => 404 ] );
		}

		header( 'Content-Type: application/pdf' );
		header( 'Content-Disposition: inline; filename="document.pdf"' );
		header( 'X-Content-Type-Options: nosniff' );
		header( 'Cache-Control: private, no-store' );
		header( 'Content-Length: ' . filesize( $path ) );

		readfile( $path );
		exit;
	}

	public static function get_pdf_url( string $slug ): string {
		return home_url( '/revaa-pdf/' . urlencode( $slug ) . '/' );
	}
}
