<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class REVAA_PDF_Storage {

	public static function init() {
		self::ensure_private_dir();
		add_action( 'wp_ajax_revaa_upload_pdf', [ __CLASS__, 'ajax_upload' ] );
		add_action( 'wp_ajax_revaa_delete_pdf', [ __CLASS__, 'ajax_delete' ] );
	}

	private static function ensure_private_dir() {
		$dir = self::get_private_dir();
		if ( ! file_exists( $dir ) ) {
			wp_mkdir_p( $dir );
		}
		$htaccess = $dir . '.htaccess';
		if ( ! file_exists( $htaccess ) ) {
			file_put_contents( $htaccess, "deny from all\n" );
		}
		$index = $dir . 'index.php';
		if ( ! file_exists( $index ) ) {
			file_put_contents( $index, '<?php // Silence is golden' );
		}
	}

	public static function get_private_dir(): string {
		return WP_CONTENT_DIR . '/revaa-private-pdfs/';
	}

	public static function get_files(): array {
		$dir   = self::get_private_dir();
		$files = glob( $dir . '*.pdf' );
		if ( ! $files ) {
			return [];
		}
		$result = [];
		foreach ( $files as $path ) {
			$filename = basename( $path );
			$result[] = [
				'name' => $filename,
				'slug' => pathinfo( $filename, PATHINFO_FILENAME ),
				'size' => filesize( $path ),
				'date' => filemtime( $path ),
			];
		}
		return $result;
	}

	public static function handle_upload( array $file ): array {
		if ( ! isset( $file['tmp_name'] ) || ! is_uploaded_file( $file['tmp_name'] ) ) {
			return [ 'success' => false, 'error' => 'Fichier invalide.' ];
		}

		$mime = mime_content_type( $file['tmp_name'] );
		if ( $mime !== 'application/pdf' ) {
			return [ 'success' => false, 'error' => 'Le fichier doit être un PDF.' ];
		}

		$filename    = sanitize_file_name( $file['name'] );
		$destination = self::get_private_dir() . $filename;

		if ( ! move_uploaded_file( $file['tmp_name'], $destination ) ) {
			return [ 'success' => false, 'error' => 'Impossible de déplacer le fichier.' ];
		}

		return [
			'success'  => true,
			'filename' => $filename,
			'slug'     => pathinfo( $filename, PATHINFO_FILENAME ),
		];
	}

	public static function ajax_upload() {
		check_ajax_referer( 'revaa_pdf_nonce', 'nonce' );

		if ( ! current_user_can( 'upload_files' ) ) {
			wp_send_json_error( [ 'error' => 'Permission refusée.' ], 403 );
		}

		if ( empty( $_FILES['pdf_file'] ) ) {
			wp_send_json_error( [ 'error' => 'Aucun fichier reçu.' ], 400 );
		}

		$result = self::handle_upload( $_FILES['pdf_file'] );
		if ( $result['success'] ) {
			wp_send_json_success( $result );
		} else {
			wp_send_json_error( $result, 400 );
		}
	}

	public static function ajax_delete() {
		check_ajax_referer( 'revaa_pdf_nonce', 'nonce' );

		if ( ! current_user_can( 'upload_files' ) ) {
			wp_send_json_error( [ 'error' => 'Permission refusée.' ], 403 );
		}

		$filename = sanitize_file_name( $_POST['filename'] ?? '' );
		if ( ! $filename ) {
			wp_send_json_error( [ 'error' => 'Nom de fichier manquant.' ], 400 );
		}

		$path = self::get_private_dir() . $filename;
		if ( ! file_exists( $path ) ) {
			wp_send_json_error( [ 'error' => 'Fichier introuvable.' ], 404 );
		}

		if ( unlink( $path ) ) {
			wp_send_json_success( [ 'deleted' => $filename ] );
		} else {
			wp_send_json_error( [ 'error' => 'Impossible de supprimer le fichier.' ], 500 );
		}
	}
}
