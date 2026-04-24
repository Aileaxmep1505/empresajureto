<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Prueba Document AI</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 24px;
            max-width: 980px;
            margin: 0 auto;
            background: #f8fafc;
            color: #111827;
        }

        h1 {
            margin-bottom: 24px;
        }

        .card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 24px;
        }

        label {
            display: block;
            margin: 16px 0 8px;
            font-weight: 600;
        }

        input[type="file"],
        input[type="number"],
        button {
            width: 100%;
            padding: 12px 14px;
            font-size: 15px;
            border-radius: 10px;
            border: 1px solid #d1d5db;
            box-sizing: border-box;
        }

        input[type="file"] {
            background: #fff;
        }

        button {
            margin-top: 20px;
            background: #111827;
            color: #fff;
            cursor: pointer;
            border: none;
            font-weight: 600;
        }

        button:hover {
            opacity: .95;
        }

        button:disabled {
            opacity: .6;
            cursor: not-allowed;
        }

        .status {
            margin-top: 18px;
            padding: 12px 14px;
            border-radius: 10px;
            font-size: 14px;
            display: none;
        }

        .status.info {
            display: block;
            background: #eff6ff;
            color: #1d4ed8;
            border: 1px solid #bfdbfe;
        }

        .status.success {
            display: block;
            background: #ecfdf5;
            color: #047857;
            border: 1px solid #a7f3d0;
        }

        .status.error {
            display: block;
            background: #fef2f2;
            color: #b91c1c;
            border: 1px solid #fecaca;
        }

        pre {
            background: #0f172a;
            color: #e2e8f0;
            padding: 16px;
            overflow: auto;
            border-radius: 12px;
            min-height: 120px;
            white-space: pre-wrap;
            word-break: break-word;
        }

        .muted {
            color: #6b7280;
            font-size: 13px;
            margin-top: 8px;
        }
    </style>
</head>
<body>
    <h1>Probar Document AI</h1>

    <div class="card">
        <form id="form" enctype="multipart/form-data">
            @csrf

            <label for="file">Archivo PDF</label>
            <input id="file" type="file" name="file" accept="application/pdf" required>

            <label for="licitacion_pdf_id">Licitacion PDF ID</label>
            <input id="licitacion_pdf_id" type="number" name="licitacion_pdf_id" value="1" required>

            <label for="pages_per_chunk">Pages per chunk</label>
            <input id="pages_per_chunk" type="number" name="pages_per_chunk" value="5" min="1" max="10">

            <button id="submitBtn" type="submit">Enviar a Python AI</button>

            <div id="statusBox" class="status"></div>
            <div class="muted">Si tarda, revisa también que esté corriendo <strong>php artisan queue:work</strong>.</div>
        </form>
    </div>

    <h2 style="margin-top: 28px;">Respuesta</h2>
    <pre id="output">Esperando envío...</pre>

    <script>
        const form = document.getElementById('form');
        const output = document.getElementById('output');
        const submitBtn = document.getElementById('submitBtn');
        const statusBox = document.getElementById('statusBox');

        function setStatus(type, text) {
            statusBox.className = 'status ' + type;
            statusBox.textContent = text;
        }

        async function safeJson(response) {
            const text = await response.text();
            try {
                return JSON.parse(text);
            } catch (e) {
                return {
                    ok: false,
                    raw_text: text,
                    parse_error: e.message
                };
            }
        }

        async function pollRun(runId) {
            const res = await fetch(`/document-ai/${runId}`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json'
                }
            });

            const data = await safeJson(res);
            output.textContent = JSON.stringify(data, null, 2);

            if (!res.ok) {
                setStatus('error', 'Error consultando el estatus del job.');
                return;
            }

            const status = data?.run?.status;

            if (status === 'queued' || status === 'processing') {
                setStatus('info', `Procesando... estado actual: ${status}`);
                setTimeout(() => pollRun(runId), 5000);
                return;
            }

            if (status === 'completed') {
                setStatus('success', 'Procesamiento completado.');
                return;
            }

            if (status === 'failed') {
                setStatus('error', 'El procesamiento falló.');
                return;
            }

            setStatus('info', 'Estatus recibido.');
        }

        form.addEventListener('submit', async (e) => {
            e.preventDefault();

            statusBox.style.display = 'none';
            output.textContent = 'Enviando...';
            submitBtn.disabled = true;
            submitBtn.textContent = 'Enviando...';

            try {
                const formData = new FormData(form);

                const response = await fetch(`{{ route('document-ai.start') }}`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json'
                    },
                    body: formData
                });

                const data = await safeJson(response);
                output.textContent = JSON.stringify(data, null, 2);

                if (!response.ok) {
                    setStatus('error', data.message || 'Error al enviar el PDF.');
                    return;
                }

                if (!data.ok) {
                    setStatus('error', data.message || 'La respuesta no fue exitosa.');
                    return;
                }

                setStatus('success', 'PDF enviado correctamente. Consultando estatus...');

                if (data.document_ai_run_id) {
                    setTimeout(() => pollRun(data.document_ai_run_id), 3000);
                } else {
                    setStatus('error', 'No se recibió document_ai_run_id.');
                }

            } catch (error) {
                console.error(error);
                output.textContent = String(error?.stack || error?.message || error);
                setStatus('error', 'Ocurrió un error en JavaScript o en la petición.');
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Enviar a Python AI';
            }
        });
    </script>
</body>
</html>