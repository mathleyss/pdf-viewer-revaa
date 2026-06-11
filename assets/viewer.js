// assets/viewer.js
// Module ES — compatible PDF.js v6
// Les URLs sont injectées par wp_localize_script via window.revaaPdfViewer

/* global revaaPdfViewer */

/**
 * Rend toutes les pages d'un PDF dans un conteneur donné.
 * @param {object} pdfjsLib
 * @param {HTMLElement} container
 * @param {string} pdfUrl
 */
async function renderPdf(pdfjsLib, container, pdfUrl) {
	const loadingEl = container.querySelector('.revaa-pdf-loading');

	try {
		// DEBUG — à retirer après diagnostic
		console.log('[REVAA DEBUG] pdfUrl length =', pdfUrl.length);
		console.log('[REVAA DEBUG] pdfUrl chars =', JSON.stringify(pdfUrl));
		console.log('[REVAA DEBUG] pdfUrl first/last char codes =', pdfUrl.charCodeAt(0), pdfUrl.charCodeAt(pdfUrl.length - 1));
		const pdfDoc = await pdfjsLib.getDocument(pdfUrl).promise;

		if (loadingEl) loadingEl.remove();

		for (let pageNum = 1; pageNum <= pdfDoc.numPages; pageNum++) {
			const page = await pdfDoc.getPage(pageNum);
			const viewport = page.getViewport({ scale: 1.5 });

			const canvas = document.createElement('canvas');
			canvas.className = 'revaa-pdf-canvas';
			canvas.height = viewport.height;
			canvas.width = viewport.width;

			canvas.addEventListener('contextmenu', (e) => e.preventDefault());

			container.appendChild(canvas);

			const ctx = canvas.getContext('2d');
			await page.render({ canvasContext: ctx, viewport }).promise;
		}
	} catch (err) {
		console.error('[REVAA PDF Viewer] Erreur de chargement :', err);
		if (loadingEl) {
			loadingEl.textContent = 'Impossible de charger le document.';
			loadingEl.classList.add('revaa-pdf-error');
		} else {
			const errEl = document.createElement('p');
			errEl.className = 'revaa-pdf-error';
			errEl.textContent = 'Impossible de charger le document.';
			container.appendChild(errEl);
		}
	}
}

/**
 * Initialise tous les blocs PDF de la page.
 */
async function initViewers() {
	if (!window.revaaPdfViewer?.pdfJsUrl) {
		console.error('[REVAA PDF Viewer] Configuration manquante (revaaPdfViewer).');
		return;
	}

	const pdfjsLib = await import(window.revaaPdfViewer.pdfJsUrl);
	pdfjsLib.GlobalWorkerOptions.workerSrc = window.revaaPdfViewer.pdfWorkerUrl;

	const containers = document.querySelectorAll('.revaa-pdf-viewer-container');

	containers.forEach((container) => {
		const pdfUrl = container.dataset.pdfUrl;
		const displayMode = container.dataset.displayMode || 'inline';

		if (!pdfUrl) return;

		if (displayMode === 'modal') {
			const openBtn = container.querySelector('.revaa-pdf-open-modal');
			const modal = container.querySelector('.revaa-pdf-modal');
			const closeBtn = container.querySelector('.revaa-pdf-close-modal');
			let loaded = false;

			if (openBtn && modal) {
				openBtn.addEventListener('click', () => {
					modal.removeAttribute('hidden');
					document.body.style.overflow = 'hidden';
					if (!loaded) {
						loaded = true;
						renderPdf(pdfjsLib, modal.querySelector('.revaa-pdf-modal-inner') || modal, pdfUrl);
					}
				});
			}

			if (closeBtn && modal) {
				closeBtn.addEventListener('click', () => {
					modal.setAttribute('hidden', '');
					document.body.style.overflow = '';
				});
			}

			const overlay = container.querySelector('.revaa-pdf-modal-overlay');
			if (overlay && modal) {
				overlay.addEventListener('click', () => {
					modal.setAttribute('hidden', '');
					document.body.style.overflow = '';
				});
			}

			if (modal) {
				modal.addEventListener('click', (e) => {
					if (e.target === modal) {
						modal.setAttribute('hidden', '');
						document.body.style.overflow = '';
					}
				});
			}
		} else {
			renderPdf(pdfjsLib, container, pdfUrl);
		}
	});
}

if (document.readyState === 'loading') {
	document.addEventListener('DOMContentLoaded', initViewers);
} else {
	initViewers();
}
