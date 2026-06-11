import { registerBlockType } from "@wordpress/blocks";
import { useBlockProps, InspectorControls } from "@wordpress/block-editor";
import { PanelBody, TextControl, Button } from "@wordpress/components";
import { useState, useEffect, useRef } from "@wordpress/element";
import { __ } from "@wordpress/i18n";

registerBlockType("revaa/pdf-viewer", {
  edit: ({ attributes, setAttributes }) => {
    const { fileSlug, fileName, label } = attributes;
    const blockProps = useBlockProps();

    const [files, setFiles] = useState([]);
    const [uploading, setUploading] = useState(false);
    const [uploadError, setUploadError] = useState("");
    const [labelInput, setLabelInput] = useState(label);
    const fileInputRef = useRef(null);

    useEffect(() => {
      if (!window.revaaPdfViewer) return;
      fetch(revaaPdfViewer.filesEndpoint, {
        headers: { "X-WP-Nonce": revaaPdfViewer.restNonce },
      })
        .then((res) => res.json())
        .then((data) => {
          if (Array.isArray(data)) setFiles(data);
        })
        .catch(() => {});
    }, []);

    function handleFileSelect(e) {
      const file = e.target.files[0];
      if (!file) return;
      setUploading(true);
      setUploadError("");

      const formData = new FormData();
      formData.append("action", "revaa_upload_pdf");
      formData.append("nonce", revaaPdfViewer.nonce);
      formData.append("pdf_file", file);
      formData.append("label", labelInput || file.name.replace(/\.pdf$/i, ""));

      fetch(revaaPdfViewer.ajaxUrl, { method: "POST", body: formData })
        .then((res) => res.json())
        .then((data) => {
          setUploading(false);
          if (data.success) {
            setAttributes({
              fileSlug: data.data.slug,
              fileName: data.data.filename,
              label: data.data.label,
            });
          } else {
            setUploadError(
              data.data ||
                __("Erreur lors du téléversement.", "revaa-pdf-viewer"),
            );
          }
        })
        .catch(() => {
          setUploading(false);
          setUploadError(__("Erreur réseau.", "revaa-pdf-viewer"));
        });
    }

    function handleExistingSelect(e) {
      const slug = e.target.value;
      if (!slug) return;
      const found = files.find((f) => f.slug === slug);
      if (found) {
        setAttributes({
          fileSlug: found.slug,
          fileName: found.name,
          label: found.label,
        });
      }
    }

    function saveLabel(newLabel) {
      if (!newLabel || !fileSlug) return;
      setAttributes({ label: newLabel });
      const formData = new FormData();
      formData.append("action", "revaa_update_pdf_label");
      formData.append("nonce", revaaPdfViewer.nonce);
      formData.append("slug", fileSlug);
      formData.append("label", newLabel);
      fetch(revaaPdfViewer.ajaxUrl, { method: "POST", body: formData }).catch(
        () => {},
      );
    }

    if (!fileSlug) {
      return (
        <div {...blockProps}>
          <div className="revaa-pdf-block-placeholder">
            <h3>{__("Document REVAA", "revaa-pdf-viewer")}</h3>

            <div className="revaa-pdf-label-field">
              <TextControl
                label={__("Nom du document", "revaa-pdf-viewer")}
                value={labelInput}
                onChange={setLabelInput}
                placeholder={__("Ex : Guide du bénévole", "revaa-pdf-viewer")}
              />
            </div>

            <div className="revaa-pdf-upload-section">
              <Button
                variant="primary"
                onClick={() => fileInputRef.current?.click()}
                disabled={uploading}
              >
                {uploading
                  ? __("Upload en cours…", "revaa-pdf-viewer")
                  : __("Uploader un PDF", "revaa-pdf-viewer")}
              </Button>
              <input
                ref={fileInputRef}
                type="file"
                accept=".pdf"
                style={{ display: "none" }}
                onChange={handleFileSelect}
              />
              {uploadError && <p className="revaa-pdf-error">{uploadError}</p>}
            </div>

            {files.length > 0 && (
              <div className="revaa-pdf-select-section">
                <p>
                  {__(
                    "Ou sélectionner un fichier existant :",
                    "revaa-pdf-viewer",
                  )}
                </p>
                <select onChange={handleExistingSelect} defaultValue="">
                  <option value="">
                    {__("— Choisir —", "revaa-pdf-viewer")}
                  </option>
                  {files.map((f) => (
                    <option key={f.slug} value={f.slug}>
                      {f.label}
                    </option>
                  ))}
                </select>
              </div>
            )}
          </div>
        </div>
      );
    }

    return (
      <>
        <InspectorControls>
          <PanelBody title={__("Paramètres du PDF", "revaa-pdf-viewer")}>
            <TextControl
              label={__("Nom du document", "revaa-pdf-viewer")}
              value={label}
              onChange={(val) => saveLabel(val)}
            />
          </PanelBody>
        </InspectorControls>

        <div {...blockProps}>
          <div className="revaa-pdf-block-preview">
            <a
              className="revaa-pdf-open-btn"
              href="#"
              onClick={(e) => e.preventDefault()}
            >
              <span className="revaa-pdf-icon">📄</span>
              <span className="revaa-pdf-label">{label || fileSlug}</span>
            </a>
            <div style={{ marginTop: "12px" }}>
              <Button
                variant="secondary"
                onClick={() =>
                  setAttributes({ fileSlug: "", fileName: "", label: "" })
                }
              >
                {__("Changer de fichier", "revaa-pdf-viewer")}
              </Button>
            </div>
          </div>
        </div>
      </>
    );
  },

  save: () => null,
});
