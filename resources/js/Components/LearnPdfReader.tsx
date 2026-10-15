import { useEffect, useRef, useState } from 'react';
import type { PDFDocumentProxy } from 'pdfjs-dist';
import workerUrl from 'pdfjs-dist/build/pdf.worker.min.mjs?url';
import 'pdfjs-dist/web/pdf_viewer.css';

export default function LearnPdfReader({ src, title }: { src?: string; title: string }) {
    const [document, setDocument] = useState<PDFDocumentProxy | null>(null);
    const [page, setPage] = useState(1);
    const [zoom, setZoom] = useState(1);
    const [width, setWidth] = useState(0);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState('');
    const [isFullscreen, setIsFullscreen] = useState(false);
    const [fullscreenError, setFullscreenError] = useState('');
    const readerRef = useRef<HTMLElement>(null);
    const fullscreenButtonRef = useRef<HTMLButtonElement>(null);
    const viewportRef = useRef<HTMLDivElement>(null);
    const sheetRef = useRef<HTMLDivElement>(null);

    useEffect(() => {
        const handleFullscreenChange = () => {
            const active = window.document.fullscreenElement === readerRef.current;
            setIsFullscreen(active);
            setFullscreenError('');
            fullscreenButtonRef.current?.focus();
        };
        window.document.addEventListener('fullscreenchange', handleFullscreenChange);
        return () => window.document.removeEventListener('fullscreenchange', handleFullscreenChange);
    }, []);

    const toggleFullscreen = async () => {
        try {
            setFullscreenError('');
            if (window.document.fullscreenElement === readerRef.current) {
                await window.document.exitFullscreen();
            } else {
                await readerRef.current?.requestFullscreen();
            }
        } catch {
            setFullscreenError('Layar penuh tidak tersedia di browser ini. Anda tetap dapat memperbesar halaman dengan tombol zoom.');
        }
    };

    useEffect(() => {
        const element = viewportRef.current;
        if (!element) return;
        const observer = new ResizeObserver(([entry]) => setWidth(Math.max(1, Math.min(960, entry.contentRect.width - 40))));
        observer.observe(element);
        return () => observer.disconnect();
    }, []);

    useEffect(() => {
        let disposed = false;
        let task: ReturnType<typeof import('pdfjs-dist').getDocument> | undefined;
        setDocument(null);
        setPage(1);
        setZoom(1);
        setError('');
        setLoading(true);
        (async () => {
            try {
                if (!src) throw new Error('Missing PDF');
                const pdfjs = await import('pdfjs-dist');
                if (disposed) return;
                pdfjs.GlobalWorkerOptions.workerSrc = workerUrl;
                task = pdfjs.getDocument(src);
                const pdf = await task.promise;
                if (!disposed) setDocument(pdf);
            } catch {
                if (!disposed) { setError('Dokumen belum dapat dimuat. Silakan buka file PDF melalui tautan di bawah.'); setLoading(false); }
            }
        })();
        return () => { disposed = true; void task?.destroy(); };
    }, [src]);

    useEffect(() => {
        if (!document || !width || !sheetRef.current) return;
        let disposed = false;
        let renderTask: ReturnType<Awaited<ReturnType<PDFDocumentProxy['getPage']>>['render']> | undefined;
        let textLayer: InstanceType<typeof import('pdfjs-dist').TextLayer> | undefined;
        const sheet = sheetRef.current;
        setLoading(true);
        setError('');
        (async () => {
            try {
                const pdfPage = await document.getPage(page);
                if (disposed) return;
                const viewport = pdfPage.getViewport({ scale: width / pdfPage.getViewport({ scale: 1 }).width * zoom });
                const canvas = window.document.createElement('canvas');
                const text = window.document.createElement('div');
                text.className = 'textLayer';
                const ratio = Math.min(window.devicePixelRatio || 1, 2);
                canvas.width = Math.ceil(viewport.width * ratio);
                canvas.height = Math.ceil(viewport.height * ratio);
                canvas.style.width = `${viewport.width}px`;
                canvas.style.height = `${viewport.height}px`;
                canvas.setAttribute('aria-label', `${title}, halaman ${page}`);
                sheet.style.width = `${viewport.width}px`;
                sheet.style.height = `${viewport.height}px`;
                sheet.style.setProperty('--scale-factor', String(viewport.scale));
                sheet.replaceChildren(canvas, text);
                renderTask = pdfPage.render({ canvasContext: canvas.getContext('2d')!, viewport, transform: [ratio, 0, 0, ratio, 0, 0] });
                await renderTask.promise;
                if (disposed) return;
                const { TextLayer } = await import('pdfjs-dist');
                if (disposed) return;
                textLayer = new TextLayer({ textContentSource: pdfPage.streamTextContent(), container: text, viewport });
                await textLayer.render();
                if (!disposed) { setLoading(false); viewportRef.current?.scrollTo({ top: 0, left: 0 }); }
            } catch {
                if (!disposed) { setError('Halaman tidak dapat ditampilkan. Silakan buka file PDF.'); setLoading(false); }
            }
        })();
        return () => { disposed = true; renderTask?.cancel(); textLayer?.cancel(); };
    }, [document, page, width, zoom, title]);

    return <section ref={readerRef} className="learn-pdf" aria-label={`Pembaca dokumen: ${title}`}>
        <div className="learn-pdf__toolbar">
            <span className="learn-pdf__label"><i className="bx bxs-file-pdf" aria-hidden="true" />{isFullscreen ? title : 'Dokumen materi'}</span>
            <div className="learn-pdf__controls">
                <button type="button" aria-label="Halaman sebelumnya" disabled={!document || page <= 1} onClick={() => setPage(value => value - 1)}><i className="bx bx-chevron-left" /></button>
                <span className="learn-pdf__count" aria-live="polite">{document ? `${page} / ${document.numPages}` : '— / —'}</span>
                <button type="button" aria-label="Halaman berikutnya" disabled={!document || page >= document.numPages} onClick={() => setPage(value => value + 1)}><i className="bx bx-chevron-right" /></button>
            </div>
            <div className="learn-pdf__controls">
                <button type="button" aria-label="Perkecil" disabled={!document || zoom <= 0.75} onClick={() => setZoom(value => Math.max(0.75, value - 0.25))}><i className="bx bx-minus" /></button>
                <button type="button" className="learn-pdf__zoom" aria-label="Sesuaikan lebar halaman" disabled={!document} onClick={() => setZoom(1)}>{Math.round(zoom * 100)}%</button>
                <button type="button" aria-label="Perbesar" disabled={!document || zoom >= 2} onClick={() => setZoom(value => Math.min(2, value + 0.25))}><i className="bx bx-plus" /></button>
                <button ref={fullscreenButtonRef} type="button" className="learn-pdf__fullscreen" onClick={toggleFullscreen} aria-pressed={isFullscreen} aria-label={isFullscreen ? 'Keluar dari layar penuh' : 'Baca layar penuh'} title={isFullscreen ? 'Keluar dari layar penuh (Esc)' : 'Baca layar penuh'}>
                    <i className={`bx ${isFullscreen ? 'bx-exit-fullscreen' : 'bx-fullscreen'}`} aria-hidden="true" />
                    <span>{isFullscreen ? 'Keluar' : 'Layar penuh'}</span>
                </button>
                {src && <a href={src} download target="_blank" rel="noreferrer" aria-label="Unduh PDF"><i className="bx bx-download" /></a>}
            </div>
        </div>
        {fullscreenError && <p className="learn-pdf__status" role="status">{fullscreenError}</p>}
        {loading && <p className="learn-pdf__status" role="status">Menyiapkan halaman…</p>}
        {error && <div className="learn-pdf__status" role="alert"><p>{error}</p>{src && <a href={src} target="_blank" rel="noreferrer">Buka file PDF <i className="bx bx-link-external" /></a>}</div>}
        <div ref={viewportRef} className="learn-pdf__viewport" tabIndex={0} aria-label="Halaman dokumen, gulir untuk membaca" aria-busy={loading}>
            <div ref={sheetRef} className="learn-pdf__sheet" hidden={!!error} />
        </div>
    </section>;
}
