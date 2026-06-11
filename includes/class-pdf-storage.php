<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class REVAA_PDF_Storage {

	public static function init() {
		self::ensure_private_dir();
		add_action( 'wp_ajax_revaa_upload_pdf', [ __CLASS__, 'ajax_upload' ] );
		add_action( 'wp_ajax_revaa_delete_pdf', [ __CLASS__, 'ajax_delete' ] );
		add_action( 'wp_ajax_revaa_update_pdf_label', [ __CLASS__, 'ajax_update_label' ] );
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

	public static function get_meta_path(): string {
		return self::get_private_dir() . 'meta.json';
	}

	public static function get_all_meta(): array {
		$path = self::get_meta_path();
		if ( ! file_exists( $path ) ) return [];
		$data = json_decode( file_get_contents( $path ), true );
		return is_array( $data ) ? $data : [];
	}

	private static function save_meta( array $meta ): void {
		file_put_contents( self::get_meta_path(), json_encode( $meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) );
	}

	public static function get_file_label( string $slug ): string {
		$meta = self::get_all_meta();
		return $meta[ $slug ]['label'] ?? $slug;
	}

	public static function set_file_label( string $slug, string $label ): void {
		$meta = self::get_all_meta();
		if ( isset( $meta[ $slug ] ) ) {
			$meta[ $slug ]['label'] = sanitize_text_field( $label );
			self::save_meta( $meta );
		}
	}

	public static function get_files(): array {
		$meta  = self::get_all_meta();
		$files = [];
		foreach ( glob( self::get_private_dir() . '*.pdf' ) ?: [] as $path ) {
			$filename = basename( $path );
			$slug     = pathinfo( $filename, PATHINFO_FILENAME );
			$files[]  = [
				'name'  => $filename,
				'slug'  => $slug,
				'label' => $meta[ $slug ]['label'] ?? $slug,
				'size'  => filesize( $path ),
				'date'  => filemtime( $path ),
			];
		}
		return $files;
	}

	public static function handle_upload( array $file, string $label = '' ): array {
		if ( ! isset( $file['tmp_name'] ) || ! is_uploaded_file( $file['tmp_name'] ) ) {
			return [ 'success' => false, 'error' => 'Fichier invalide.' ];
		}

		$mime = mime_content_type( $file['tmp_name'] );
		if ( $mime !== 'application/pdf' ) {
			return [ 'success' => false, 'error' => 'Le fichier doit être un PDF.' ];
		}

		$filename  = sanitize_file_name( $file['name'] );
		$ext       = pathinfo( $filename, PATHINFO_EXTENSION );
		$slug      = strtolower( pathinfo( $filename, PATHINFO_FILENAME ) );
		$filename  = $slug . '.' . $ext;

		// Gestion des collisions de casse
		$base_slug = $slug;
		$i         = 2;
		while ( file_exists( self::get_private_dir() . $filename ) ) {
			$slug     = $base_slug . '-' . $i;
			$filename = $slug . '.' . $ext;
			$i++;
		}

		$dest = self::get_private_dir() . $filename;

		if ( ! move_uploaded_file( $file['tmp_name'], $dest ) ) {
			return [ 'success' => false, 'error' => 'Déplacement du fichier impossible.' ];
		}

		$meta          = self::get_all_meta();
		$meta[ $slug ] = [
			'filename'    => $filename,
			'label'       => sanitize_text_field( $label ?: $slug ),
			'uploaded_at' => ( new DateTime() )->format( 'c' ),
		];
		self::save_meta( $meta );

		return [ 'success' => true, 'filename' => $filename, 'slug' => $slug, 'label' => $meta[ $slug ]['label'] ];
	}

	public static function ajax_upload() {
		check_ajax_referer( 'revaa_pdf_nonce', 'nonce' );

		if ( ! current_user_can( 'upload_files' ) ) {
			wp_send_json_error( 'Permission refusée.', 403 );
		}

		if ( empty( $_FILES['pdf_file'] ) ) {
			wp_send_json_error( 'Aucun fichier reçu.', 400 );
		}

		$label  = isset( $_POST['label'] ) ? sanitize_text_field( $_POST['label'] ) : '';
		$result = self::handle_upload( $_FILES['pdf_file'], $label );
		$result['success'] ? wp_send_json_success( $result ) : wp_send_json_error( $result['error'] );
	}

	public static function ajax_update_label() {
		check_ajax_referer( 'revaa_pdf_nonce', 'nonce' );

		if ( ! current_user_can( 'upload_files' ) ) {
			wp_send_json_error( 'Permission refusée.', 403 );
		}

		$slug  = sanitize_file_name( $_POST['slug'] ?? '' );
		$label = sanitize_text_field( $_POST['label'] ?? '' );

		if ( ! $slug || ! $label ) {
			wp_send_json_error( 'Paramètres manquants.' );
		}

		self::set_file_label( $slug, $label );
		wp_send_json_success();
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

		$slug = pathinfo( $filename, PATHINFO_FILENAME );
		$meta = self::get_all_meta();
		if ( isset( $meta[ $slug ] ) ) {
			unset( $meta[ $slug ] );
			self::save_meta( $meta );
		}

		if ( unlink( $path ) ) {
			wp_send_json_success( [ 'deleted' => $filename ] );
		} else {
			wp_send_json_error( [ 'error' => 'Impossible de supprimer le fichier.' ], 500 );
		}
	}
}
