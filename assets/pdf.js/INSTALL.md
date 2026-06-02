# Installation de PDF.js

## Version utilisée

**PDF.js 6.0.227** — modules ES (`.mjs`).

## Procédure d'installation

1. Télécharger le fichier `pdfjs-6.0.227-dist.zip` depuis :
   https://github.com/mozilla/pdf.js/releases/tag/v6.0.227

   ```bash
   curl -L https://github.com/mozilla/pdf.js/releases/download/v6.0.227/pdfjs-6.0.227-dist.zip -o pdfjs.zip
   ```

2. Extraire et copier les fichiers dans `assets/pdf.js/build/` :

   ```bash
   unzip pdfjs.zip -d pdfjs-tmp/
   mkdir -p assets/pdf.js/build/
   cp pdfjs-tmp/build/pdf.mjs        assets/pdf.js/build/
   cp pdfjs-tmp/build/pdf.worker.mjs assets/pdf.js/build/
   rm -rf pdfjs-tmp/ pdfjs.zip
   ```

3. Vérifier la présence des deux fichiers :
   - `assets/pdf.js/build/pdf.mjs`
   - `assets/pdf.js/build/pdf.worker.mjs`

## Remarques

- Ces fichiers ne sont **pas versionnés** (exclus par `.gitignore`).
- PDF.js v6 utilise des modules ES — le plugin les charge avec `type="module"`.
- Le chemin du worker est injecté via `wp_localize_script` (objet `revaaPdfViewer.pdfWorkerUrl`).
- Les modules ES sont bloqués sur `file://` : tester impérativement en HTTP/HTTPS.
