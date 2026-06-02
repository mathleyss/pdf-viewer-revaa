// assets/viewer.js
// Module ES — compatible PDF.js v6

import * as pdfjsLib from './pdf.js/build/pdf.mjs';

pdfjsLib.GlobalWorkerOptions.workerSrc =
	new URL('./pdf.js/build/pdf.worker.mjs', import.meta.url).href;

/**
 * Rend toutes les pages d'un PDF dans un conteneur donné.
 * @param {HTMLElement} container
 * @param {string} pdfUrl
 */
async function renderPdf(container, pdfUrl) {
	const loadingEl = container.querySelector('.revaa-pdf-loading');

	try {
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
function initViewers() {
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
						renderPdf(modal.querySelector('.revaa-pdf-modal-inner') || modal, pdfUrl);
					}
				});
			}

			if (closeBtn && modal) {
				closeBtn.addEventListener('click', () => {
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
			renderPdf(container, pdfUrl);
		}
	});
}

if (document.readyState === 'loading') {
	document.addEventListener('DOMContentLoaded', initViewers);
} else {
	initViewers();
}
