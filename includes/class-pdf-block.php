<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class REVAA_PDF_Block {

	public static function init() {
		add_action( 'init', [ __CLASS__, 'register_block' ] );
		add_action( 'rest_api_init', [ __CLASS__, 'register_rest_routes' ] );
		add_action( 'admin_menu', [ __CLASS__, 'add_admin_page' ] );
		add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_admin_assets' ] );
		add_action( 'wp_enqueue_scripts', [ __CLASS__, 'enqueue_frontend_assets' ] );
		add_filter( 'script_loader_tag', [ __CLASS__, 'add_module_type_to_viewer' ], 10, 3 );
		add_action( 'enqueue_block_editor_assets', [ __CLASS__, 'enqueue_editor_assets' ] );
	}

	public static function register_block() {
		if ( ! function_exists( 'register_block_type' ) ) {
			return;
		}
		register_block_type(
			REVAA_PDF_VIEWER_PATH . 'build',
			[
				'render_callback' => [ __CLASS__, 'render_block' ],
			]
		);
	}

	public static function enqueue_editor_assets() {
		wp_localize_script(
			'revaa-pdf-viewer-editor-script',
			'revaaPdfViewer',
			[
				'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
				'nonce'         => wp_create_nonce( 'revaa_pdf_nonce' ),
				'filesEndpoint' => rest_url( 'revaa-pdf/v1/files' ),
				'restNonce'     => wp_create_nonce( 'wp_rest' ),
			]
		);
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
		if ( ! has_block( 'revaa/pdf-viewer' ) ) {
			return;
		}
		wp_enqueue_script(
			'revaa-pdf-viewer',
			REVAA_PDF_VIEWER_URL . 'assets/viewer.js',
			[],
			'1.1.0',
			true
		);
		wp_localize_script(
			'revaa-pdf-viewer',
			'revaaPdfViewer',
			[
				'pdfJsUrl'     => REVAA_PDF_VIEWER_URL . 'assets/pdf.js/build/pdf.mjs',
				'pdfWorkerUrl' => REVAA_PDF_VIEWER_URL . 'assets/pdf.js/build/pdf.worker.mjs',
			]
		);
	}

	public static function add_module_type_to_viewer( string $tag, string $handle, string $src ): string {
		if ( $handle !== 'revaa-pdf-viewer' ) {
			return $tag;
		}
		return str_replace( '<script ', '<script type="module" ', $tag );
	}

	public static function render_block( array $attributes ): string {
		$slug  = $attributes['fileSlug'] ?? '';
		$label = $attributes['label'] ?? '';

		if ( empty( $slug ) ) return '';

		if ( ! is_user_logged_in() ) {
			return '<p class="revaa-pdf-login-required">Vous devez être connecté pour accéder à ce document.</p>';
		}

		$file_path = REVAA_PDF_Storage::get_private_dir() . sanitize_file_name( $slug ) . '.pdf';
		if ( ! file_exists( $file_path ) ) {
			return '<p class="revaa-pdf-not-found">Document introuvable.</p>';
		}

		$url        = REVAA_PDF_Endpoint::get_pdf_url( $slug );
		$safe_label = esc_html( $label ?: $slug );
		$safe_url   = esc_url( $url );

		// DEBUG TEMPORAIRE — à retirer après diagnostic
		error_log( '[REVAA DEBUG] slug=' . $slug . ' url=' . $url . ' safe_url=' . $safe_url );

		return sprintf(
			'<div class="revaa-pdf-viewer-container" data-pdf-url="%1$s" data-display-mode="modal">
				<button type="button" class="revaa-pdf-open-modal revaa-pdf-open-btn">
					<span class="revaa-pdf-icon">📄</span>
					<span class="revaa-pdf-label">%2$s</span>
				</button>
				<div class="revaa-pdf-modal" hidden>
					<div class="revaa-pdf-modal-overlay"></div>
					<div class="revaa-pdf-modal-content">
						<button type="button" class="revaa-pdf-close-modal" aria-label="Fermer">&times;</button>
						<div class="revaa-pdf-modal-inner">
							<p class="revaa-pdf-loading">Chargement du document…</p>
						</div>
					</div>
				</div>
			</div>',
			$safe_url,
			$safe_label
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
			'1.1.0'
		);
		wp_enqueue_script(
			'revaa-pdf-admin',
			REVAA_PDF_VIEWER_URL . 'assets/admin.js',
			[ 'jquery' ],
			'1.1.0',
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
				<div class="revaa-upload-field">
					<label for="revaa-pdf-label">Nom du document</label>
					<input type="text" name="label" id="revaa-pdf-label" placeholder="Ex : Guide du bénévole" style="width:300px;">
				</div>
				<div class="revaa-upload-field">
					<label for="revaa-pdf-file">Fichier PDF</label>
					<input type="file" name="pdf_file" id="revaa-pdf-file" accept=".pdf" required>
				</div>
				<input type="hidden" name="nonce" value="<?php echo esc_attr( $nonce ); ?>">
				<div class="revaa-upload-actions">
					<button type="submit" class="button button-primary">Uploader</button>
					<span id="revaa-upload-message"></span>
				</div>
			</form>

			<h2>Fichiers disponibles</h2>
			<?php if ( empty( $files ) ) : ?>
				<p>Aucun fichier PDF pour l'instant.</p>
			<?php else : ?>
				<table class="widefat striped">
					<thead>
						<tr>
							<th>Nom du document</th>
							<th>Taille</th>
							<th>Date</th>
							<th>Actions</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $files as $file ) : ?>
							<tr data-slug="<?php echo esc_attr( $file['slug'] ); ?>">
								<td>
									<strong class="revaa-file-label"><?php echo esc_html( $file['label'] ); ?></strong>
									<br><small style="color:#888;"><?php echo esc_html( 'Fichier : ' . $file['name'] ); ?></small>
									<br><small style="color:#888;"><?php echo esc_html( 'Slug : ' . $file['slug'] ); ?></small>
									<div class="revaa-rename-form" style="display:none;margin-top:6px;">
										<input type="text" class="revaa-rename-input" value="<?php echo esc_attr( $file['label'] ); ?>" style="width:220px;">
										<button class="button button-small revaa-rename-save">Enregistrer</button>
										<button class="button button-small revaa-rename-cancel">Annuler</button>
									</div>
								</td>
								<td><?php echo esc_html( size_format( $file['size'] ) ); ?></td>
								<td><?php echo esc_html( date_i18n( get_option( 'date_format' ), $file['date'] ) ); ?></td>
								<td>
									<button class="button revaa-rename-btn">Renommer</button>
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
