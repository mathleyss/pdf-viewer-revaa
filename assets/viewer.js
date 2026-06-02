/* global pdfjsLib, revaaPdfViewerConfig */
(function () {
	'use strict';

	if (typeof pdfjsLib === 'undefined') {
		console.error('REVAA PDF Viewer: PDF.js non chargé.');
		return;
	}

	if (window.revaaPdfViewerConfig && revaaPdfViewerConfig.workerSrc) {
		pdfjsLib.GlobalWorkerOptions.workerSrc = revaaPdfViewerConfig.workerSrc;
	}

	function renderError(container, message) {
		container.innerHTML = '<p class="revaa-pdf-error">' + message + '</p>';
	}

	async function renderInline(container, url) {
		const canvas = container.querySelector('.revaa-pdf-canvas');
		const loading = container.querySelector('.revaa-pdf-loading');

		let pdfDoc;
		try {
			pdfDoc = await pdfjsLib.getDocument(url).promise;
		} catch (e) {
			renderError(container, 'Impossible de charger le document PDF.');
			return;
		}

		if (loading) loading.remove();

		for (let pageNum = 1; pageNum <= pdfDoc.numPages; pageNum++) {
			const page = await pdfDoc.getPage(pageNum);
			const viewport = page.getViewport({ scale: 1.5 });

			const pageCanvas = pageNum === 1 ? canvas : document.createElement('canvas');
			pageCanvas.className = 'revaa-pdf-canvas';
			if (pageNum > 1) container.appendChild(pageCanvas);

			pageCanvas.height = viewport.height;
			pageCanvas.width = viewport.width;

			const ctx = pageCanvas.getContext('2d');
			await page.render({ canvasContext: ctx, viewport }).promise;

			pageCanvas.addEventListener('contextmenu', function (e) {
				e.preventDefault();
			});
		}
	}

	async function renderModal(container, url) {
		const openBtn = container.querySelector('.revaa-pdf-open-modal');
		const modal = container.querySelector('.revaa-pdf-modal');
		const closeBtn = container.querySelector('.revaa-pdf-close-modal');
		const canvas = container.querySelector('.revaa-pdf-canvas');

		if (!openBtn || !modal || !canvas) return;

		let rendered = false;

		openBtn.addEventListener('click', async function () {
			modal.removeAttribute('hidden');
			document.body.style.overflow = 'hidden';

			if (!rendered) {
				rendered = true;
				let pdfDoc;
				try {
					pdfDoc = await pdfjsLib.getDocument(url).promise;
				} catch (e) {
					renderError(modal.querySelector('.revaa-pdf-modal-inner'), 'Impossible de charger le document PDF.');
					return;
				}

				for (let pageNum = 1; pageNum <= pdfDoc.numPages; pageNum++) {
					const page = await pdfDoc.getPage(pageNum);
					const viewport = page.getViewport({ scale: 1.5 });

					const pageCanvas = pageNum === 1 ? canvas : document.createElement('canvas');
					pageCanvas.className = 'revaa-pdf-canvas';
					if (pageNum > 1) modal.querySelector('.revaa-pdf-modal-inner').appendChild(pageCanvas);

					pageCanvas.height = viewport.height;
					pageCanvas.width = viewport.width;

					const ctx = pageCanvas.getContext('2d');
					await page.render({ canvasContext: ctx, viewport }).promise;

					pageCanvas.addEventListener('contextmenu', function (e) {
						e.preventDefault();
					});
				}
			}
		});

		if (closeBtn) {
			closeBtn.addEventListener('click', function () {
				modal.setAttribute('hidden', '');
				document.body.style.overflow = '';
			});
		}

		modal.addEventListener('click', function (e) {
			if (e.target === modal) {
				modal.setAttribute('hidden', '');
				document.body.style.overflow = '';
			}
		});
	}

	function initViewers() {
		const containers = document.querySelectorAll('.revaa-pdf-viewer-container');
		containers.forEach(function (container) {
			const url = container.dataset.pdfUrl;
			const mode = container.dataset.displayMode || 'inline';

			if (!url) {
				renderError(container, 'URL du document manquante.');
				return;
			}

			if (mode === 'modal') {
				renderModal(container, url);
			} else {
				renderInline(container, url);
			}
		});
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', initViewers);
	} else {
		initViewers();
	}
})();
