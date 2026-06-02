# REVAA PDF Viewer

Plugin WordPress — Visionneuse PDF protégée avec bloc Gutenberg.

## Fonctionnalités

- Bloc Gutenberg **PDF Protégé** (catégorie *Médias*)
- Affichage via PDF.js (rendu canvas, sans bouton de téléchargement natif)
- Deux modes : **inline** (intégré à la page) et **modale** (fenêtre superposée)
- Accès réservé aux utilisateurs connectés
- Stockage des PDFs dans un dossier privé (`wp-content/revaa-private-pdfs/`) protégé par `.htaccess`
- Page d'administration dans *Médias → PDFs Protégés*

## Prérequis

- WordPress 6.0+
- PHP 8.0+
- PDF.js (voir ci-dessous)

## Installation de PDF.js

Les fichiers PDF.js ne sont **pas inclus** dans ce dépôt.  
Voir [`assets/pdf.js/INSTALL.md`](assets/pdf.js/INSTALL.md) pour les instructions détaillées.

**Version requise : PDF.js 6.0.227** (modules ES `.mjs`)

En résumé :

```bash
curl -L https://github.com/mozilla/pdf.js/releases/download/v6.0.227/pdfjs-6.0.227-dist.zip -o pdfjs.zip
unzip pdfjs.zip -d pdfjs-tmp/
mkdir -p assets/pdf.js/build/
cp pdfjs-tmp/build/pdf.mjs        assets/pdf.js/build/
cp pdfjs-tmp/build/pdf.worker.mjs assets/pdf.js/build/
rm -rf pdfjs-tmp/ pdfjs.zip
```

Fichiers requis après installation :
- `assets/pdf.js/build/pdf.mjs`
- `assets/pdf.js/build/pdf.worker.mjs`

> Les scripts sont chargés avec `type="module"` (implique `defer`). Tester en HTTP/HTTPS — les modules ES sont bloqués sur `file://`.

## Installation du plugin

1. Cloner ce dépôt dans `wp-content/plugins/revaa-pdf-viewer/`
2. Installer PDF.js (voir ci-dessus)
3. (Optionnel) Compiler le bloc Gutenberg :
   ```bash
   npm install
   npm run build
   ```
4. Activer le plugin dans *Extensions → Extensions installées*

## Utilisation

1. Dans l'éditeur Gutenberg, insérer le bloc **PDF Protégé**
2. Uploader un nouveau PDF ou sélectionner un fichier existant
3. Choisir le mode d'affichage (inline / modale) et la hauteur dans le panneau latéral
4. Publier la page — le PDF est visible uniquement pour les utilisateurs connectés

## Sécurité

- Les PDFs sont stockés hors du dossier `uploads/`, inaccessibles directement par HTTP
- Chaque requête de fichier passe par l'endpoint PHP qui vérifie l'authentification
- Toutes les actions AJAX sont protégées par des nonces WordPress
- Les noms de fichiers sont systématiquement passés par `sanitize_file_name()`
