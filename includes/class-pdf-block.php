<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class REVAA_PDF_Block {

	public static function init() {
		add_action( 'init', [ __CLASS__, 'register_block' ] );
		add_action( 'rest_api_init', [ __CLASS__, 'register_rest_routes' ] );
		add_action( 'wp_enqueue_scripts', [ __CLASS__, 'enqueue_frontend_assets' ] );
		add_action( 'admin_menu', [ __CLASS__, 'add_admin_page' ] );
		add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_admin_assets' ] );
	}

	public static function register_block() {
		if ( ! function_exists( 'register_block_type' ) ) {
			return;
		}
		register_block_type(
			REVAA_PDF_VIEWER_PATH . 'blocks/pdf-viewer/block.json',
			[
				'render_callback' => [ __CLASS__, 'render_block' ],
			]
		);

		$nonce_data = [
			'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
			'nonce'         => wp_create_nonce( 'revaa_pdf_nonce' ),
			'filesEndpoint' => rest_url( 'revaa-pdf/v1/files' ),
			'restNonce'     => wp_create_nonce( 'wp_rest' ),
		];
		wp_localize_script( 'revaa-pdf-viewer-editor-script', 'revaaPdfViewer', $nonce_data );
	}

	public static function register_rest_routes() {
		register_rest_route(
			'revaa-pdf/v1',
			'/files',
			[
				'methods'             => 'GET',
				'callback'            => [ __CLASS__, 'rest_get_files' ],
				'permission_callback' => function () {
					return current_user_can( 'upload_files' );
				},
			]
		);
	}

	public static function rest_get_files(): WP_REST_Response {
		return new WP_REST_Response( REVAA_PDF_Storage::get_files(), 200 );
	}

	public static function enqueue_frontend_assets() {
		if ( ! is_singular() || ! has_block( 'revaa/pdf-viewer' ) ) {
			return;
		}

		$pdfjs_path = REVAA_PDF_VIEWER_PATH . 'assets/pdf.js/';
		$pdfjs_url  = REVAA_PDF_VIEWER_URL . 'assets/pdf.js/';

		if ( file_exists( $pdfjs_path . 'pdf.min.mjs' ) ) {
			wp_enqueue_script(
				'revaa-pdfjs',
				$pdfjs_url . 'pdf.min.mjs',
				[],
				'5.0.0',
				true
			);
		} elseif ( file_exists( $pdfjs_path . 'pdf.min.js' ) ) {
			wp_enqueue_script(
				'revaa-pdfjs',
				$pdfjs_url . 'pdf.min.js',
				[],
				'3.11.174',
				true
			);
		}

		wp_enqueue_script(
			'revaa-pdf-viewer',
			REVAA_PDF_VIEWER_URL . 'assets/viewer.js',
			[ 'revaa-pdfjs' ],
			'1.0.0',
			true
		);
		wp_localize_script(
			'revaa-pdf-viewer',
			'revaaPdfViewerConfig',
			[
				'workerSrc' => file_exists( $pdfjs_path . 'pdf.worker.min.mjs' )
					? $pdfjs_url . 'pdf.worker.min.mjs'
					: $pdfjs_url . 'pdf.worker.min.js',
			]
		);
	}

	public static function enqueue_admin_assets( string $hook ) {
		if ( $hook !== 'media_page_revaa-pdf-admin' ) {
			return;
		}
		wp_enqueue_style(
			'revaa-pdf-admin-style',
			REVAA_PDF_VIEWER_URL . 'assets/admin.css',
			[],
			'1.0.0'
		);
		wp_enqueue_script(
			'revaa-pdf-admin',
			REVAA_PDF_VIEWER_URL . 'assets/admin.js',
			[ 'jquery' ],
			'1.0.0',
			true
		);
		wp_localize_script(
			'revaa-pdf-admin',
			'revaaPdfAdmin',
			[
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'revaa_pdf_nonce' ),
			]
		);
	}

	public static function render_block( array $attributes ): string {
		$slug = sanitize_file_name( $attributes['fileSlug'] ?? '' );
		if ( ! $slug ) {
			return '';
		}

		if ( ! is_user_logged_in() ) {
			return '<p class="revaa-pdf-login-required">Vous devez être connecté pour accéder à ce document.</p>';
		}

		$file_path = REVAA_PDF_Storage::get_private_dir() . $slug . '.pdf';
		if ( ! file_exists( $file_path ) ) {
			return '';
		}

		$url          = REVAA_PDF_Endpoint::get_pdf_url( $slug );
		$display_mode = esc_attr( $attributes['displayMode'] ?? 'inline' );
		$height       = max( 300, min( 1200, intval( $attributes['height'] ?? 600 ) ) );

		if ( $display_mode === 'modal' ) {
			return sprintf(
				'<div class="revaa-pdf-viewer-container" data-display-mode="modal" data-pdf-url="%s">
					<button class="revaa-pdf-open-modal">&#128196; Ouvrir le document</button>
					<div class="revaa-pdf-modal" hidden>
						<div class="revaa-pdf-modal-inner">
							<button class="revaa-pdf-close-modal">&#x2715; Fermer</button>
							<canvas class="revaa-pdf-canvas"></canvas>
						</div>
					</div>
				</div>',
				esc_url( $url )
			);
		}

		return sprintf(
			'<div class="revaa-pdf-viewer-container" data-pdf-url="%s" data-display-mode="inline" style="height:%dpx;">
				<canvas class="revaa-pdf-canvas"></canvas>
				<p class="revaa-pdf-loading">Chargement du document&#8230;</p>
			</div>',
			esc_url( $url ),
			$height
		);
	}

	public static function add_admin_page() {
		add_media_page(
			'PDFs Protégés',
			'PDFs Protégés',
			'upload_files',
			'revaa-pdf-admin',
			[ __CLASS__, 'render_admin_page' ]
		);
	}

	public static function render_admin_page() {
		if ( ! current_user_can( 'upload_files' ) ) {
			wp_die( 'Permission refusée.' );
		}
		$files = REVAA_PDF_Storage::get_files();
		$nonce = wp_create_nonce( 'revaa_pdf_nonce' );
		?>
		<div class="wrap">
			<h1>PDFs Protégés</h1>

			<h2>Uploader un PDF</h2>
			<form id="revaa-upload-form" enctype="multipart/form-data">
				<input type="file" name="pdf_file" id="revaa-pdf-file" accept=".pdf" required>
				<input type="hidden" name="nonce" value="<?php echo esc_attr( $nonce ); ?>">
				<button type="submit" class="button button-primary">Uploader</button>
				<span id="revaa-upload-message"></span>
			</form>

			<h2>Fichiers disponibles</h2>
			<?php if ( empty( $files ) ) : ?>
				<p>Aucun fichier PDF pour l'instant.</p>
			<?php else : ?>
				<table class="widefat striped">
					<thead>
						<tr>
							<th>Nom</th>
							<th>Taille</th>
							<th>Date</th>
							<th>Action</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $files as $file ) : ?>
							<tr>
								<td><?php echo esc_html( $file['name'] ); ?></td>
								<td><?php echo esc_html( size_format( $file['size'] ) ); ?></td>
								<td><?php echo esc_html( date_i18n( get_option( 'date_format' ), $file['date'] ) ); ?></td>
								<td>
									<button class="button revaa-delete-btn"
										data-filename="<?php echo esc_attr( $file['name'] ); ?>"
										data-nonce="<?php echo esc_attr( $nonce ); ?>">
										Supprimer
									</button>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>
		<?php
	}
}
