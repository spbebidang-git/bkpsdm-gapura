<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Konten SCORM</title>
    <style>
        html,
        body {
            margin: 0;
            padding: 0;
            height: 100%;
            background: #000;
            overflow: hidden;
        }

        #sco {
            display: block;
            width: 100%;
            height: 100%;
            border: 0;
        }

        .scorm-loading {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #e5e7eb;
            font-family: system-ui, sans-serif;
            font-size: 14px;
        }
    </style>
</head>

<body>
    <div id="scorm-loading" class="scorm-loading">Memuat konten SCORM...</div>
    <iframe id="sco" title="Konten SCORM"
        allow="autoplay; fullscreen; encrypted-media; microphone; camera"
        allowfullscreen></iframe>

    <script>
        (function () {
            var CONTENT_BASE = @json($contentBase);
            var LAUNCH = @json($launch);
            var RUNTIME_URL = @json($runtimeUrl ?? null);
            var CSRF_TOKEN = @json(csrf_token());
            var LEARNER_ID = @json((string) ($learnerId ?? 'participant'));
            var LEARNER_NAME = @json($learnerName ?? 'Peserta');

            var cmi = Object.assign({}, @json($runtimeState ?? []));
            var hasSavedState = Object.keys(cmi).length > 0;
            var lastError = '0';
            var completedSent = false;
            var saveTimer = null;
            var unloadPersisted = false;
            var stateDirty = false;
            var lastSavedSnapshot = JSON.stringify(cmi);
            var saveInFlight = null;
            var pendingSave = false;

            function persistState(onUnload) {
                if (!RUNTIME_URL) return;

                if (saveTimer) {
                    clearTimeout(saveTimer);
                    saveTimer = null;
                }

                var snapshot = JSON.stringify(cmi);
                if (!stateDirty && snapshot === lastSavedSnapshot) return;

                // Susun payload dari snapshot yang sama agar objek CMI besar tidak
                // diserialisasi dua kali pada setiap commit.
                var payload = '{"_token":' + JSON.stringify(CSRF_TOKEN) + ',"cmi":' + snapshot + '}';
                if (onUnload && navigator.sendBeacon && payload.length < 60000) {
                    navigator.sendBeacon(
                        RUNTIME_URL,
                        new Blob([payload], { type: 'application/json' })
                    );
                    lastSavedSnapshot = snapshot;
                    stateDirty = false;
                    return;
                }

                // Maksimal satu request aktif. Jika SCO melakukan commit berulang,
                // cukup kirim state terbaru setelah request aktif selesai.
                if (saveInFlight) {
                    pendingSave = true;
                    return;
                }

                stateDirty = false;
                saveInFlight = fetch(RUNTIME_URL, {
                    method: 'POST',
                    credentials: 'same-origin',
                    keepalive: Boolean(onUnload && payload.length < 60000),
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': CSRF_TOKEN
                    },
                    body: payload
                }).then(function (response) {
                    if (!response.ok) throw new Error('SCORM state save failed');
                    lastSavedSnapshot = snapshot;
                }).catch(function () {
                    stateDirty = true;
                }).finally(function () {
                    saveInFlight = null;
                    if (pendingSave || stateDirty) {
                        pendingSave = false;
                        schedulePersistence(250);
                    }
                });
            }

            function schedulePersistence(delay) {
                if (!RUNTIME_URL) return;
                if (saveTimer) clearTimeout(saveTimer);
                saveTimer = setTimeout(function () { persistState(false); }, delay || 2000);
            }

            function notifyCompleted() {
                if (completedSent) return;
                completedSent = true;
                parent.postMessage({ type: 'scorm-completed' }, '*');
            }

            function checkCompletion() {
                var s12 = (cmi['cmi.core.lesson_status'] || '').toLowerCase();
                var compl = (cmi['cmi.completion_status'] || '').toLowerCase();
                var succ = (cmi['cmi.success_status'] || '').toLowerCase();
                if (s12 === 'completed' || s12 === 'passed' ||
                    compl === 'completed' || succ === 'passed') {
                    notifyCompleted();
                }
            }

            function get(key, def) {
                return (key in cmi) ? cmi[key] : (def !== undefined ? def : '');
            }

            // Dibuat sekali; LMSGetValue dapat dipanggil ratusan kali oleh SCO.
            var cmiDefaults = {
                'cmi.core.lesson_status': 'not attempted',
                'cmi.core.entry': 'ab-initio',
                'cmi.core.credit': 'credit',
                'cmi.core.lesson_mode': 'normal',
                'cmi.core.student_name': LEARNER_NAME,
                'cmi.core.student_id': LEARNER_ID,
                'cmi.core.lesson_location': '',
                'cmi.core.score.raw': '',
                'cmi.core.total_time': '0000:00:00',
                'cmi.suspend_data': '',
                'cmi.launch_data': '',
                'cmi.student_data.mastery_score': '',
                'cmi.completion_status': 'unknown',
                'cmi.success_status': 'unknown',
                'cmi.entry': hasSavedState ? 'resume' : 'ab-initio',
                'cmi.credit': 'credit',
                'cmi.mode': 'normal',
                'cmi.learner_name': LEARNER_NAME,
                'cmi.learner_id': LEARNER_ID,
                'cmi.location': '',
                'cmi.score.raw': '',
                'cmi.total_time': 'PT0H0M0S'
            };

            // ---- SCORM 1.2 API (window.API) ----
            var API = {
                LMSInitialize: function () {
                    // Default minimal yang diharapkan konten (mis. Storyline).
                    if (!('cmi.core.lesson_status' in cmi)) cmi['cmi.core.lesson_status'] = 'not attempted';
                    cmi['cmi.core.entry'] = hasSavedState ? 'resume' : 'ab-initio';
                    if (!('cmi.core.credit' in cmi)) cmi['cmi.core.credit'] = 'credit';
                    if (!('cmi.core.lesson_mode' in cmi)) cmi['cmi.core.lesson_mode'] = 'normal';
                    if (!('cmi.core.student_name' in cmi)) cmi['cmi.core.student_name'] = LEARNER_NAME;
                    if (!('cmi.core.student_id' in cmi)) cmi['cmi.core.student_id'] = LEARNER_ID;
                    if (!('cmi.completion_status' in cmi)) cmi['cmi.completion_status'] = 'unknown';
                    cmi['cmi.entry'] = hasSavedState ? 'resume' : 'ab-initio';
                    if (!('cmi.credit' in cmi)) cmi['cmi.credit'] = 'credit';
                    if (!('cmi.mode' in cmi)) cmi['cmi.mode'] = 'normal';
                    if (!('cmi.learner_name' in cmi)) cmi['cmi.learner_name'] = LEARNER_NAME;
                    if (!('cmi.learner_id' in cmi)) cmi['cmi.learner_id'] = LEARNER_ID;
                    lastError = '0';
                    return 'true';
                },
                LMSFinish: function () {
                    checkCompletion();
                    persistState(false);
                    lastError = '0';
                    return 'true';
                },
                LMSGetValue: function (key) {
                    lastError = '0';
                    return get(key, cmiDefaults[key] !== undefined ? cmiDefaults[key] : '');
                },
                LMSSetValue: function (key, value) {
                    var stringValue = String(value);
                    if (cmi[key] === stringValue) {
                        lastError = '0';
                        return 'true';
                    }
                    cmi[key] = stringValue;
                    stateDirty = true;
                    lastError = '0';
                    if (key === 'cmi.core.lesson_status' ||
                        key === 'cmi.completion_status' ||
                        key === 'cmi.success_status') {
                        checkCompletion();
                    }
                    schedulePersistence();
                    return 'true';
                },
                LMSCommit: function () {
                    persistState(false);
                    lastError = '0';
                    return 'true';
                },
                LMSGetLastError: function () { return lastError; },
                LMSGetErrorString: function () { return 'No error'; },
                LMSGetDiagnostic: function () { return ''; }
            };

            // ---- SCORM 2004 API (window.API_1484_11) ----
            var API2004 = {
                Initialize: function () { return API.LMSInitialize(); },
                Terminate: function () { return API.LMSFinish(); },
                GetValue: function (key) { return API.LMSGetValue(key); },
                SetValue: function (key, value) { return API.LMSSetValue(key, value); },
                Commit: function () { return API.LMSCommit(); },
                GetLastError: function () { return lastError; },
                GetErrorString: function () { return 'No error'; },
                GetDiagnostic: function () { return ''; }
            };

            window.API = API;             // SCORM 1.2
            window.API_1484_11 = API2004; // SCORM 2004
            // Pulihkan status completion dari runtime sebelumnya agar launcher
            // langsung mengenali paket yang memang sudah tuntas.
            checkCompletion();

            function persistOnUnload() {
                if (unloadPersisted) return;
                unloadPersisted = true;
                persistState(true);
            }
            window.addEventListener('pagehide', persistOnUnload);
            window.addEventListener('beforeunload', persistOnUnload);
            document.addEventListener('visibilitychange', function () {
                if (document.hidden) persistState(false);
            });

            // Iframe yang dilepas dari DOM tidak memicu pagehide/unload, sehingga
            // commit terakhir tidak pernah terkirim saat popup ditutup. Parent
            // meminta penyimpanan secara eksplisit lalu menunggu konfirmasi ini.
            window.addEventListener('message', function (ev) {
                if (ev.origin !== window.location.origin) return;
                if (!ev.data || ev.data.type !== 'player-save-state') return;

                persistOnUnload();
                parent.postMessage({ type: 'player-state-saved' }, window.location.origin);
            });

            // Luncurkan SCO.
            var sco = document.getElementById('sco');
            var loading = document.getElementById('scorm-loading');
            sco.onload = function () { if (loading) loading.style.display = 'none'; };
            sco.src = CONTENT_BASE + '/' + LAUNCH;
        })();
    </script>
</body>

</html>
