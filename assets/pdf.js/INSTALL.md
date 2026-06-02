# Installation de PDF.js

## Version utilisée

**PDF.js 4.x** (dernière version stable au moment du développement).  
Version recommandée : `pdfjs-4.10.38-dist` ou ultérieure.

## Procédure d'installation

1. Télécharger le fichier `pdfjs-X.X.X-dist.zip` depuis :
   https://github.com/mozilla/pdf.js/releases

2. Extraire l'archive et copier les fichiers suivants dans ce dossier (`assets/pdf.js/`) :

   - `build/pdf.min.mjs` → `assets/pdf.js/pdf.min.mjs`
   - `build/pdf.worker.min.mjs` → `assets/pdf.js/pdf.worker.min.mjs`

   Pour les versions 3.x (si nécessaire) :
   - `build/pdf.min.js` → `assets/pdf.js/pdf.min.js`
   - `build/pdf.worker.min.js` → `assets/pdf.js/pdf.worker.min.js`

3. Ces fichiers ne sont **pas versionnés** (exclus par `.gitignore`).
   Chaque développeur doit les télécharger manuellement.

## Remarque

Le fichier worker doit être servi depuis la même origine que le script principal.
Le plugin configure automatiquement `pdfjsLib.GlobalWorkerOptions.workerSrc` via
`wp_localize_script` (objet `revaaPdfViewerConfig.workerSrc`).
