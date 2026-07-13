<div class="pjd-pane" data-pane="borrador">
        <div class="pjd-editor-header">
          <div class="pjd-editor-header-left">
            <div class="pjd-editor-project">{{ $project->name }}</div>
            <button type="button" class="pjd-editor-mini" title="Editar nombre">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 1 1 3 3L7 19l-4 1 1-4 12.5-12.5z"/></svg>
            </button>
            <button type="button" class="pjd-editor-mini" title="Favorito">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m12 17.3-6.18 3.7 1.64-7.03L2 9.24l7.19-.61L12 2l2.81 6.63 7.19.61-5.46 4.73 1.64 7.03z"/></svg>
            </button>

            <div class="pjd-borrador-tabs">
              <button type="button" class="pjd-borrador-tab is-active" data-section="borrador">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg>
                Borrador
              </button>
              <button type="button" class="pjd-borrador-tab" data-section="reporte">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2zM22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
                Reporte
              </button>
            </div>
          </div>

          <div class="pjd-editor-header-right">
            <button type="button" class="pjd-editor-icon-btn" id="pjdDownloadDraft" title="Descargar Word">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 3v12"/><path d="m7 10 5 5 5-5"/><path d="M5 21h14"/></svg>
            </button>
            <button type="button" class="pjd-editor-icon-btn" id="pjdBorradorExpand" title="Expandir editor">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M8 3H3v5"/><path d="M3 3l6 6"/><path d="M16 3h5v5"/><path d="m21 3-6 6"/><path d="M8 21H3v-5"/><path d="m3 21 6-6"/><path d="M16 21h5v-5"/><path d="m21 21-6-6"/></svg>
            </button>
          </div>
        </div>

        <div class="pjd-borrador-section is-active" data-section-pane="borrador">
          <div class="pjd-borrador-actions">
            <span style="color:var(--muted);font-size:.78rem;" id="pjdDraftStatus">Guardado automático</span>
          </div>
          <div class="pjd-draft-shell">
            <div class="pjd-draft-toolbar" id="pjdDraftToolbar">
              <div class="pjd-draft-group">
                <button type="button" class="pjd-draft-btn" data-draft-cmd="undo" title="Deshacer"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M9 14 4 9l5-5"/><path d="M4 9h10a6 6 0 0 1 0 12h-2"/></svg></button>
                <button type="button" class="pjd-draft-btn" data-draft-cmd="redo" title="Rehacer"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="m15 14 5-5-5-5"/><path d="M20 9H10a6 6 0 0 0 0 12h2"/></svg></button>
              </div>

              <div class="pjd-draft-group">
                <select class="pjd-draft-select" data-draft-block title="Estilo">
                  <option value="P">Párrafo</option>
                  <option value="H1">Título 1</option>
                  <option value="H2">Título 2</option>
                  <option value="H3">Título 3</option>
                  <option value="BLOCKQUOTE">Cita</option>
                </select>
                <select class="pjd-draft-select" data-draft-font title="Fuente">
                  <option value="Quicksand">Quicksand</option>
                  <option value="Arial">Arial</option>
                  <option value="Georgia">Georgia</option>
                  <option value="Times New Roman">Times</option>
                  <option value="Courier New">Courier</option>
                </select>
                <select class="pjd-draft-select is-small" data-draft-size title="Tamaño">
                  <option value="2">12</option>
                  <option value="3" selected>16</option>
                  <option value="4">18</option>
                  <option value="5">24</option>
                  <option value="6">32</option>
                  <option value="7">48</option>
                </select>
              </div>

              <div class="pjd-draft-group">
                <button type="button" class="pjd-draft-btn" data-draft-cmd="bold" title="Negrita"><b>B</b></button>
                <button type="button" class="pjd-draft-btn" data-draft-cmd="italic" title="Cursiva"><i>I</i></button>
                <button type="button" class="pjd-draft-btn" data-draft-cmd="underline" title="Subrayado"><u>U</u></button>
                <button type="button" class="pjd-draft-btn" data-draft-cmd="strikeThrough" title="Tachado"><s>S</s></button>
                <label class="pjd-draft-color" title="Color de texto"><input type="color" data-draft-color="foreColor" value="#111111"></label>
                <label class="pjd-draft-color" title="Resaltado"><input type="color" data-draft-color="hiliteColor" value="#e6f0ff"></label>
              </div>

              <div class="pjd-draft-group">
                <button type="button" class="pjd-draft-btn" data-draft-cmd="justifyLeft" title="Alinear izquierda"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M4 6h14M4 10h10M4 14h14M4 18h10"/></svg></button>
                <button type="button" class="pjd-draft-btn" data-draft-cmd="justifyCenter" title="Centrar"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M5 6h14M8 10h8M5 14h14M8 18h8"/></svg></button>
                <button type="button" class="pjd-draft-btn" data-draft-cmd="justifyRight" title="Alinear derecha"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M6 6h14M10 10h10M6 14h14M10 18h10"/></svg></button>
                <button type="button" class="pjd-draft-btn" data-draft-cmd="justifyFull" title="Justificar"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg></button>
              </div>

              <div class="pjd-draft-group">
                <button type="button" class="pjd-draft-btn" data-draft-cmd="insertUnorderedList" title="Lista con viñetas"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M8 6h12M8 12h12M8 18h12"/><circle cx="4" cy="6" r="1"/><circle cx="4" cy="12" r="1"/><circle cx="4" cy="18" r="1"/></svg></button>
                <button type="button" class="pjd-draft-btn" data-draft-cmd="insertOrderedList" title="Lista numerada"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M10 6h10M10 12h10M10 18h10"/><path d="M4 6h1v4M3.8 10h2.4M4 14a1 1 0 1 1 2 0c0 .6-.8 1.1-2 2h2M4 19h2l-2 3h2"/></svg></button>
                <button type="button" class="pjd-draft-btn" data-draft-cmd="outdent" title="Disminuir sangría"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M20 6H10M20 12H10M20 18H10"/><path d="m4 12 4-4v8z"/></svg></button>
                <button type="button" class="pjd-draft-btn" data-draft-cmd="indent" title="Aumentar sangría"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M20 6H10M20 12H10M20 18H10"/><path d="m8 12-4-4v8z"/></svg></button>
              </div>

              <div class="pjd-draft-group">
                <button type="button" class="pjd-draft-btn" data-draft-action="link" title="Insertar link"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M10 13a5 5 0 0 0 7.1 0l2-2a5 5 0 0 0-7.1-7.1l-1.1 1.1"/><path d="M14 11a5 5 0 0 0-7.1 0l-2 2A5 5 0 0 0 12 20.1l1.1-1.1"/></svg></button>
                <button type="button" class="pjd-draft-btn" data-draft-cmd="unlink" title="Quitar link"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M15 7h2a5 5 0 0 1 0 10h-2"/><path d="M9 17H7A5 5 0 0 1 7 7h2"/><path d="m8 12 8 0"/><path d="M3 3l18 18"/></svg></button>
                <button type="button" class="pjd-draft-btn" data-draft-action="image" title="Insertar imagen por URL"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 21"/></svg></button>
                <button type="button" class="pjd-draft-btn" data-draft-action="table" title="Insertar tabla"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M3 10h18M9 4v16M15 4v16"/></svg></button>
                <button type="button" class="pjd-draft-btn" data-draft-cmd="insertHorizontalRule" title="Separador"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M5 12h14"/></svg></button>
                <button type="button" class="pjd-draft-btn" data-draft-cmd="removeFormat" title="Limpiar formato"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M4 7h10M10 7 6 19M14 19H6M13 13l6 6M19 13l-6 6"/></svg></button>
              </div>
            </div>
            <div id="pjdDraftEditor" class="pjd-draft-editor" contenteditable="true">{!! $project->draft_content ?? '' !!}</div>
          </div>
        </div>

        <div class="pjd-borrador-section" data-section-pane="reporte" style="display:none;"></div>
      </div>
