<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Konten H5P</title>
    <style>
        html,
        body {
            margin: 0;
            padding: 0;
            background: #fff;
            overflow: hidden;
        }

        #h5p-container {
            width: 100%;
        }

        .h5p-loading {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 60vh;
            color: #6b7280;
            font-family: system-ui, sans-serif;
            font-size: 14px;
        }

        .h5p-error {
            color: #ef4444;
            text-align: center;
            padding: 24px;
            font-family: system-ui, sans-serif;
        }
    </style>
</head>

<body>
    <div id="h5p-loading" class="h5p-loading">Memuat konten interaktif...</div>
    <div id="h5p-container"></div>

    <script src="/vendor/h5p-standalone/main.bundle.js"></script>
    <script>
        (function () {
            var el = document.getElementById('h5p-container');
            var loading = document.getElementById('h5p-loading');

            var lastH = 0;
            var completionListenerBound = false;
            var resizeObserver = null;
            var resizeFrame = null;
            var runtimeRetryTimer = null;
            var autosaveTimer = null;
            var disposed = false;

            // Fallback untuk paket lama jika runtime tetap membuat iframe dalam.
            function tameInner() {
                var inner = el ? el.querySelector('iframe.h5p-iframe') : null;
                if (inner) {
                    inner.setAttribute('scrolling', 'no');
                    inner.style.overflow = 'hidden';
                    inner.style.display = 'block';
                }
                return inner;
            }

            function getRuntime() {
                if (window.H5P && window.H5P.instances) return window.H5P;
                var inner = tameInner();
                try {
                    return inner && inner.contentWindow ? inner.contentWindow.H5P : null;
                } catch (err) {
                    return null;
                }
            }

            // Simpan checkpoint hanya saat tab aktif. H5P sendiri tetap melakukan
            // penyimpanan terakhir saat pagehide/unload.
            function saveCurrentState(force) {
                if (!runtimeUrl || disposed || (!force && document.hidden)) return;
                var runtime = getRuntime();
                if (!runtime || !runtime.setUserData || !runtime.instances) return;

                runtime.instances.forEach(function (instance) {
                    if (!instance || typeof instance.getCurrentState !== 'function') return;
                    var state = instance.getCurrentState();
                    if (state === undefined) return;
                    runtime.setUserData(instance.contentId, 'state', state, {
                        deleteOnChange: true,
                        async: true
                    });
                });
            }

            // Ketika iframe popup dilepas, request AJAX biasa dapat dibatalkan oleh
            // browser. Kirim checkpoint terakhir lewat sendBeacon agar posisi paling
            // baru tetap sampai ke server tanpa menahan proses penutupan popup.
            function saveCurrentStateOnUnload() {
                if (!runtimeUrl || disposed) return;
                var runtime = getRuntime();
                if (!runtime || !runtime.instances) return;

                runtime.instances.forEach(function (instance) {
                    if (!instance || typeof instance.getCurrentState !== 'function') return;

                    var state = instance.getCurrentState();
                    if (state === undefined) return;

                    var serialized;
                    try {
                        serialized = JSON.stringify(state);
                    } catch (error) {
                        return;
                    }

                    var url = runtimeUrl
                        .replace(':contentId', instance.contentId)
                        .replace(':dataType', 'state')
                        .replace(':subContentId', '0');
                    var body = new URLSearchParams({
                        data: serialized,
                        preload: '1',
                        invalidate: '1'
                    });
                    var bodySize = body.toString().length;
                    var sent = false;

                    // Keepalive request umumnya dibatasi sekitar 64 KB.
                    if (navigator.sendBeacon && bodySize < 60000) {
                        sent = navigator.sendBeacon(url, body);
                    }

                    if (!sent && window.fetch && bodySize < 60000) {
                        fetch(url, {
                            method: 'POST',
                            credentials: 'same-origin',
                            keepalive: true,
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
                                'Accept': 'application/json'
                            },
                            body: body.toString()
                        }).catch(function () {});
                        sent = true;
                    }

                    // State besar tidak dapat memakai keepalive. Runtime H5P sudah
                    // mendukung request sinkron khusus saat unload sebagai fallback.
                    if (!sent && runtime.setUserData) {
                        runtime.setUserData(instance.contentId, 'state', state, {
                            deleteOnChange: true,
                            async: false
                        });
                    }
                });
            }

            function bindCompletionListener() {
                if (completionListenerBound) return true;
                var runtime = getRuntime();
                if (!runtime || !runtime.externalDispatcher) return false;

                runtime.externalDispatcher.on('xAPI', function (event) {
                    var st = event && event.data && event.data.statement;
                    var verb = st && st.verb && st.verb.id ? st.verb.id : '';
                    if (verb.indexOf('/completed') !== -1 || verb.indexOf('/passed') !== -1) {
                        saveCurrentState();
                        parent.postMessage({ type: 'h5p-completed' }, '*');
                    }
                });
                completionListenerBound = true;
                return true;
            }

            function waitForRuntime(attempt) {
                if (disposed || bindCompletionListener() || attempt >= 8) return;
                runtimeRetryTimer = setTimeout(function () {
                    waitForRuntime(attempt + 1);
                }, Math.min(250 * Math.pow(1.5, attempt), 2000));
            }

            // Kirim tinggi konten ke parent agar iframe otomatis menyesuaikan
            // (tanpa scrollbar di dalam iframe, konten tidak kepotong).
            function reportHeight() {
                resizeFrame = null;
                if (disposed) return;
                var inner = tameInner();
                var h = 0;

                // Utamakan tinggi yang di-set h5p-standalone pada iframe dalam.
                if (inner) {
                    var styleH = parseInt(inner.style.height, 10) || 0;
                    h = Math.max(styleH, inner.offsetHeight || 0);
                }
                if (h <= 0) {
                    h = Math.max(
                        el ? el.scrollHeight : 0,
                        document.body ? document.body.scrollHeight : 0
                    );
                }

                if (h > 0 && h !== lastH) {
                    lastH = h;
                    // +2px buffer agar tepi bawah tidak terpotong.
                    parent.postMessage({ type: 'h5p-resize', height: h + 2 }, '*');
                }
            }

            function scheduleHeightReport() {
                if (disposed || resizeFrame !== null) return;
                resizeFrame = requestAnimationFrame(reportHeight);
            }

            var options = {
                h5pJsonPath: @json($contentBase),
                frameJs: '/vendor/h5p-standalone/frame.bundle.js',
                frameCss: '/vendor/h5p-standalone/styles/h5p.css',
                // Halaman ini sudah berada dalam iframe terisolasi. Render langsung
                // menghindari dokumen/iframe kedua dan menghemat memori browser.
                embedType: 'div'
            };

            var runtimeUrl = @json($runtimeUrl ?? null);
            var h5pUser = @json($h5pUser ?? null);
            var contentUserData = @json($contentUserData ?? null);
            if (runtimeUrl && h5pUser) {
                options.id = @json($contentId ?? null);
                options.saveFreq = 15;
                options.ajax = { contentUserDataUrl: runtimeUrl };
                options.user = h5pUser;

                // Wajib di-preload: H5P core membuat instance kontennya tanpa
                // menunggu callback getUserData(), jadi state yang hanya tersedia
                // lewat AJAX selalu datang terlambat dan konten mulai dari nol.
                if (contentUserData) {
                    options.contentUserData = contentUserData;
                }
            }

            new H5PStandalone.H5P(el, options)
                .then(function () {
                    if (loading) loading.style.display = 'none';
                    reportHeight();

                    // Pantau perubahan ukuran konten H5P (video dimuat, interaksi, dsb).
                    if (window.ResizeObserver) {
                        resizeObserver = new ResizeObserver(scheduleHeightReport);
                        resizeObserver.observe(el);
                        var inner = el.querySelector('iframe.h5p-iframe');
                        if (inner) resizeObserver.observe(inner);
                    }
                    window.addEventListener('resize', scheduleHeightReport);
                    // Hanya ukur ulang beberapa kali selama fase awal pemuatan.
                    [250, 750, 1500, 3000].forEach(function (t) {
                        setTimeout(scheduleHeightReport, t);
                    });

                    waitForRuntime(0);
                    autosaveTimer = setInterval(saveCurrentState, 15000);
                })
                .catch(function (e) {
                    if (loading) {
                        loading.className = 'h5p-error';
                        loading.textContent = 'Konten H5P gagal dimuat.';
                    }
                    console.error('H5P load error:', e);
                });

            // Beberapa konten H5P mengirim pesan resize (xAPI/iframe resizer).
            window.addEventListener('message', function (ev) {
                if (ev.data && (ev.data.context === 'h5p' || ev.data.action === 'resize')) {
                    scheduleHeightReport();
                }
            });

            // Iframe yang dilepas dari DOM tidak memicu pagehide/unload, sehingga
            // checkpoint terakhir tidak pernah terkirim saat popup ditutup. Parent
            // meminta penyimpanan secara eksplisit lalu menunggu konfirmasi ini.
            window.addEventListener('message', function (ev) {
                if (ev.origin !== window.location.origin) return;
                if (!ev.data || ev.data.type !== 'player-save-state') return;

                saveCurrentStateOnUnload();
                parent.postMessage({ type: 'player-state-saved' }, window.location.origin);
            });

            document.addEventListener('visibilitychange', function () {
                if (document.hidden) saveCurrentState(true);
            });

            window.addEventListener('pagehide', function () {
                saveCurrentStateOnUnload();
                disposed = true;
                if (resizeObserver) resizeObserver.disconnect();
                if (resizeFrame !== null) cancelAnimationFrame(resizeFrame);
                if (runtimeRetryTimer) clearTimeout(runtimeRetryTimer);
                if (autosaveTimer) clearInterval(autosaveTimer);
                window.removeEventListener('resize', scheduleHeightReport);
            }, { once: true });
        })();
    </script>
</body>

</html>
