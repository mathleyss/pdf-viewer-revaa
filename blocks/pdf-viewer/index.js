import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, SelectControl, RangeControl, Button } from '@wordpress/components';
import { useState, useEffect, useRef } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

registerBlockType( 'revaa/pdf-viewer', {
	edit: ( { attributes, setAttributes } ) => {
		const { fileSlug, fileName, displayMode, height } = attributes;
		const blockProps = useBlockProps();

		const [ files, setFiles ] = useState( [] );
		const [ uploading, setUploading ] = useState( false );
		const [ uploadError, setUploadError ] = useState( '' );
		const fileInputRef = useRef( null );

		useEffect( () => {
			if ( ! window.revaaPdfViewer ) return;
			fetch( revaaPdfViewer.filesEndpoint, {
				headers: {
					'X-WP-Nonce': revaaPdfViewer.restNonce,
				},
			} )
				.then( ( res ) => res.json() )
				.then( ( data ) => {
					if ( Array.isArray( data ) ) setFiles( data );
				} )
				.catch( () => {} );
		}, [] );

		function handleFileSelect( e ) {
			const file = e.target.files[ 0 ];
			if ( ! file ) return;
			setUploading( true );
			setUploadError( '' );

			const formData = new FormData();
			formData.append( 'action', 'revaa_upload_pdf' );
			formData.append( 'nonce', revaaPdfViewer.nonce );
			formData.append( 'pdf_file', file );

			fetch( revaaPdfViewer.ajaxUrl, {
				method: 'POST',
				body: formData,
			} )
				.then( ( res ) => res.json() )
				.then( ( data ) => {
					setUploading( false );
					if ( data.success ) {
						setAttributes( {
							fileSlug: data.data.slug,
							fileName: data.data.filename,
						} );
					} else {
						setUploadError(
							data.data?.error || __( 'Erreur lors de l\'upload.', 'revaa-pdf-viewer' )
						);
					}
				} )
				.catch( () => {
					setUploading( false );
					setUploadError( __( 'Erreur réseau.', 'revaa-pdf-viewer' ) );
				} );
		}

		if ( ! fileSlug ) {
			return (
				<div { ...blockProps }>
					<div className="revaa-pdf-block-placeholder">
						<h3>{ __( 'PDF Protégé', 'revaa-pdf-viewer' ) }</h3>

						<div className="revaa-pdf-upload-section">
							<Button
								variant="primary"
								onClick={ () => fileInputRef.current?.click() }
								disabled={ uploading }
							>
								{ uploading
									? __( 'Upload en cours…', 'revaa-pdf-viewer' )
									: __( 'Uploader un PDF', 'revaa-pdf-viewer' ) }
							</Button>
							<input
								ref={ fileInputRef }
								type="file"
								accept=".pdf"
								style={ { display: 'none' } }
								onChange={ handleFileSelect }
							/>
							{ uploadError && (
								<p className="revaa-pdf-error">{ uploadError }</p>
							) }
						</div>

						{ files.length > 0 && (
							<div className="revaa-pdf-select-section">
								<p>{ __( 'Ou sélectionner un fichier existant :', 'revaa-pdf-viewer' ) }</p>
								<SelectControl
									value=""
									options={ [
										{ label: __( '— Choisir —', 'revaa-pdf-viewer' ), value: '' },
										...files.map( ( f ) => ( {
											label: f.name,
											value: f.slug,
										} ) ),
									] }
									onChange={ ( slug ) => {
										if ( ! slug ) return;
										const found = files.find( ( f ) => f.slug === slug );
										setAttributes( {
											fileSlug: slug,
											fileName: found ? found.name : slug + '.pdf',
										} );
									} }
								/>
							</div>
						) }
					</div>
				</div>
			);
		}

		return (
			<>
				<InspectorControls>
					<PanelBody title={ __( 'Paramètres du PDF', 'revaa-pdf-viewer' ) }>
						<SelectControl
							label={ __( 'Mode d\'affichage', 'revaa-pdf-viewer' ) }
							value={ displayMode }
							options={ [
								{ label: __( 'Inline', 'revaa-pdf-viewer' ), value: 'inline' },
								{ label: __( 'Modale', 'revaa-pdf-viewer' ), value: 'modal' },
							] }
							onChange={ ( val ) => setAttributes( { displayMode: val } ) }
						/>
						{ displayMode === 'inline' && (
							<RangeControl
								label={ __( 'Hauteur (px)', 'revaa-pdf-viewer' ) }
								value={ height }
								min={ 300 }
								max={ 1200 }
								onChange={ ( val ) => setAttributes( { height: val } ) }
							/>
						) }
					</PanelBody>
				</InspectorControls>

				<div { ...blockProps }>
					<div className="revaa-pdf-block-preview">
						<span className="revaa-pdf-block-icon dashicons dashicons-media-document"></span>
						<p className="revaa-pdf-block-filename">{ fileName || fileSlug }</p>
						<Button
							variant="secondary"
							onClick={ () => setAttributes( { fileSlug: '', fileName: '' } ) }
						>
							{ __( 'Changer de fichier', 'revaa-pdf-viewer' ) }
						</Button>
					</div>
				</div>
			</>
		);
	},

	save: () => null,
} );
