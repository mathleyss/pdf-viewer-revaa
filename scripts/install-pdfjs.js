const https = require('https');
const fs    = require('fs');
const path  = require('path');
const { execSync } = require('child_process');

const VERSION  = '6.0.227';
const URL      = `https://github.com/mozilla/pdf.js/releases/download/v${VERSION}/pdfjs-${VERSION}-dist.zip`;
const ZIP_PATH = path.resolve(__dirname, '../pdfjs-tmp.zip');
const TMP_DIR  = path.resolve(__dirname, '../pdfjs-tmp');
const DEST_DIR = path.resolve(__dirname, '../assets/pdf.js/build');

if (
	fs.existsSync(path.join(DEST_DIR, 'pdf.mjs')) &&
	fs.existsSync(path.join(DEST_DIR, 'pdf.worker.mjs'))
) {
	console.log(`[REVAA] PDF.js v${VERSION} déjà installé.`);
	process.exit(0);
}

console.log(`[REVAA] Téléchargement PDF.js v${VERSION}...`);
fs.mkdirSync(DEST_DIR, { recursive: true });

const file = fs.createWriteStream(ZIP_PATH);

function download(url, dest, cb) {
	https.get(url, (res) => {
		if (res.statusCode === 301 || res.statusCode === 302) {
			download(res.headers.location, dest, cb);
			return;
		}
		res.pipe(dest);
		dest.on('finish', () => dest.close(cb));
	}).on('error', (err) => {
		fs.unlink(dest.path, () => {});
		console.error('[REVAA] Erreur :', err.message);
		process.exit(1);
	});
}

download(URL, file, () => {
	console.log('[REVAA] Extraction...');
	try {
		execSync(`unzip -o "${ZIP_PATH}" "build/pdf.mjs" "build/pdf.worker.mjs" -d "${TMP_DIR}"`);
		fs.copyFileSync(path.join(TMP_DIR, 'build', 'pdf.mjs'),        path.join(DEST_DIR, 'pdf.mjs'));
		fs.copyFileSync(path.join(TMP_DIR, 'build', 'pdf.worker.mjs'), path.join(DEST_DIR, 'pdf.worker.mjs'));
		fs.rmSync(ZIP_PATH, { force: true });
		fs.rmSync(TMP_DIR,  { recursive: true, force: true });
		console.log(`[REVAA] PDF.js v${VERSION} installé dans assets/pdf.js/build/`);
	} catch (e) {
		console.error('[REVAA] Erreur extraction :', e.message);
		process.exit(1);
	}
});
