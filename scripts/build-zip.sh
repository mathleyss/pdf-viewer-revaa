#!/bin/bash
set -e

PLUGIN_NAME="revaa-pdf-viewer"
ZIP_NAME="${PLUGIN_NAME}.zip"
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
PLUGIN_DIR="$(dirname "$SCRIPT_DIR")"
PARENT_DIR="$(dirname "$PLUGIN_DIR")"

echo "[REVAA] Nettoyage zip précédent..."
rm -f "${PARENT_DIR}/${ZIP_NAME}"

echo "[REVAA] Installation PDF.js si nécessaire..."
node "${SCRIPT_DIR}/install-pdfjs.js"

echo "[REVAA] Build Gutenberg..."
cd "${PLUGIN_DIR}"
npm run build

echo "[REVAA] Création du zip..."
cd "${PARENT_DIR}"
zip -r "${ZIP_NAME}" "${PLUGIN_NAME}/" \
	--exclude="*/.git/*" \
	--exclude="*/.git" \
	--exclude="*/node_modules/*" \
	--exclude="*/.gitignore" \
	--exclude="*/scripts/*" \
	--exclude="*/blocks/*" \
	--exclude="*/webpack.config.js" \
	--exclude="*/package.json" \
	--exclude="*/package-lock.json" \
	--exclude="*/pdfjs-tmp*" \
	--exclude="*/.DS_Store"

echo ""
echo "[REVAA] Zip créé : ${PARENT_DIR}/${ZIP_NAME}"
echo "[REVAA] Contenu inclus :"
unzip -l "${PARENT_DIR}/${ZIP_NAME}" | grep -v "/$" | awk '{print $NF}' | sort
