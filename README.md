# REVAA PDF Viewer

Plugin WordPress — Accès sécurisé à des PDFs via bouton Gutenberg.

## Fonctionnalités

- Bloc Gutenberg **PDF Protégé** (catégorie *Médias*)
- Upload de PDF avec champ "Nom du document" (label personnalisé)
- Sélecteur de fichiers déjà uploadés avec leurs noms lisibles
- Bouton `📄 Nom du document` en frontend → clic → PDF s'ouvre dans un nouvel onglet
- Accès réservé aux utilisateurs connectés
- Stockage des PDFs dans un dossier privé (`wp-content/revaa-private-pdfs/`) protégé par `.htaccess`
- Page d'administration dans *Médias → PDFs Protégés* avec renommage inline
- Labels stockés dans `revaa-private-pdfs/meta.json`

## Prérequis

- WordPress 6.0+
- PHP 8.0+
- Node.js (pour le build)

## Développement

```bash
npm install        # installe les dépendances et télécharge PDF.js automatiquement
npm run build      # compile le bloc Gutenberg dans build/
```

Le script `postinstall` télécharge automatiquement PDF.js v6.0.227 dans `assets/pdf.js/build/`.

## Déploiement

```bash
bash scripts/build-zip.sh
```

Cette commande :
1. Télécharge PDF.js si nécessaire
2. Compile le bloc Gutenberg
3. Crée `revaa-pdf-viewer.zip` dans le dossier **parent** du plugin

Ensuite :
1. Déposer le zip sur o2switch
2. Extraire dans `wp-content/plugins/`
3. Activer le plugin dans *Extensions → Extensions installées*
4. Flusher les permaliens (*Réglages → Permaliens → Enregistrer*)
5. Exclure `/revaa-pdf/` du cache LiteSpeed

## Utilisation

1. Dans l'éditeur Gutenberg, insérer le bloc **PDF Protégé**
2. Saisir un nom de document, puis uploader un PDF ou sélectionner un fichier existant
3. Un aperçu du bouton s'affiche dans l'éditeur
4. Le label est modifiable dans le panneau latéral (Paramètres du PDF)
5. Publier la page — le bouton est visible uniquement pour les utilisateurs connectés

## Sécurité

- Les PDFs sont stockés hors du dossier `uploads/`, inaccessibles directement par HTTP
- Chaque requête de fichier passe par l'endpoint PHP qui vérifie l'authentification
- Toutes les actions AJAX sont protégées par des nonces WordPress
- Les noms de fichiers sont systématiquement passés par `sanitize_file_name()`
