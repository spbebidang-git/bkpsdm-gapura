import {
    btnPrimary,
    card,
    fieldError,
    pageDescription,
    pageEyebrow,
    pageHeader,
    pageTitle,
    sectionDescription,
    sectionTitle,
} from '@/Components/Admin/ui';
import AdminLayout from '@/Layouts/AdminLayout';
import { Head, useForm } from '@inertiajs/react';
import { useEffect, useState } from 'react';

export default function Index({ settings }) {
    const { data, setData, post, processing, errors } = useForm({
        site_title: settings.site_title ?? '',
        site_description: settings.site_description ?? '',
        site_logo: null,
    });
    const [logoPreview, setLogoPreview] = useState(settings.site_logo_url || null);

    useEffect(() => {
        if (!data.site_logo) {
            setLogoPreview(settings.site_logo_url || null);
            return undefined;
        }
        const url = URL.createObjectURL(data.site_logo);
        setLogoPreview(url);
        return () => URL.revokeObjectURL(url);
    }, [data.site_logo, settings.site_logo_url]);

    const submit = (event) => {
        event.preventDefault();
        post(route('admin.management.setting.store'), { forceFormData: true });
    };

    return (
        <AdminLayout title="Pengaturan" hideTitle>
            <Head title="Pengaturan" />

            <div className="space-y-5">
                <div className={pageHeader}>
                    <div className="min-w-0">
                        <p className={pageEyebrow}>Sistem</p>
                        <h1 className={pageTitle}>Pengaturan situs</h1>
                        <p className={pageDescription}>Atur judul, deskripsi, dan logo yang tampil pada GAPURA.</p>
                    </div>
                </div>

                <form onSubmit={submit} className="grid gap-5 lg:grid-cols-[minmax(0,1fr)_320px]">
                    <section className={`${card} overflow-hidden`} aria-labelledby="site-identity-title">
                        <header className="border-b border-hairline px-5 py-4 sm:px-6">
                            <h2 id="site-identity-title" className={sectionTitle}>Identitas situs</h2>
                            <p className={sectionDescription}>Perubahan dipakai pada judul halaman dan navigasi.</p>
                        </header>

                        <div className="space-y-5 p-5 sm:p-6">
                            <div>
                                <label htmlFor="site_title" className="form-label">Judul situs</label>
                                <input
                                    id="site_title"
                                    className="form-control"
                                    value={data.site_title}
                                    onChange={(event) => setData('site_title', event.target.value)}
                                    placeholder="Contoh: GAPURA"
                                    aria-invalid={Boolean(errors.site_title)}
                                    required
                                />
                                {errors.site_title && <p className={fieldError}>{errors.site_title}</p>}
                            </div>

                            <div>
                                <label htmlFor="site_description" className="form-label">Deskripsi</label>
                                <textarea
                                    id="site_description"
                                    className="form-control min-h-28 resize-y"
                                    rows={4}
                                    value={data.site_description}
                                    onChange={(event) => setData('site_description', event.target.value)}
                                    placeholder="Jelaskan fungsi situs secara singkat"
                                    aria-invalid={Boolean(errors.site_description)}
                                    required
                                />
                                {errors.site_description && <p className={fieldError}>{errors.site_description}</p>}
                            </div>

                            <div>
                                <span className="form-label">Logo</span>
                                <label
                                    htmlFor="site_logo"
                                    className="group flex cursor-pointer flex-col items-center justify-center gap-3 rounded-lg border border-dashed border-hairline-strong bg-surface-soft px-4 py-8 text-center transition-colors hover:border-primary/50 hover:bg-primary-mist/35 focus-within:ring-2 focus-within:ring-primary"
                                >
                                    {logoPreview ? (
                                        <img src={logoPreview} alt="Pratinjau logo" className="h-14 w-auto max-w-[220px] object-contain" />
                                    ) : (
                                        <span className="flex h-12 w-12 items-center justify-center rounded-lg border border-hairline bg-canvas text-steel">
                                            <i className="bx bx-image-add text-2xl" aria-hidden />
                                        </span>
                                    )}
                                    <span className="text-sm font-semibold text-primary-pressed">
                                        {data.site_logo ? data.site_logo.name : 'Pilih file logo'}
                                    </span>
                                    <span className="text-xs text-steel">PNG atau JPG, maksimal 10 MB. Kosongkan jika tidak diubah.</span>
                                    <input
                                        id="site_logo"
                                        type="file"
                                        accept="image/png,image/jpeg"
                                        className="sr-only"
                                        onChange={(event) => setData('site_logo', event.target.files?.[0] ?? null)}
                                    />
                                </label>
                                {errors.site_logo && <p className={fieldError}>{errors.site_logo}</p>}
                            </div>
                        </div>

                        <footer className="flex justify-end border-t border-hairline bg-surface-soft px-5 py-4 sm:px-6">
                            <button type="submit" disabled={processing} className={btnPrimary}>
                                <i className="bx bx-save text-base" aria-hidden />
                                {processing ? 'Menyimpan...' : 'Simpan pengaturan'}
                            </button>
                        </footer>
                    </section>

                    <aside className={`${card} h-fit overflow-hidden lg:sticky lg:top-24`} aria-labelledby="preview-title">
                        <header className="border-b border-hairline px-4 py-4">
                            <h2 id="preview-title" className={sectionTitle}>Pratinjau</h2>
                            <p className={sectionDescription}>Tampilan ringkas identitas situs.</p>
                        </header>
                        <div className="space-y-4 p-4">
                            <div className="flex items-center gap-3 rounded-lg border border-hairline bg-surface-soft p-3">
                                {logoPreview ? (
                                    <img src={logoPreview} alt="" className="h-10 w-auto max-w-[100px] object-contain" />
                                ) : (
                                    <span className="flex h-10 w-10 items-center justify-center rounded-lg bg-primary text-sm font-semibold text-white">G</span>
                                )}
                                <div className="min-w-0">
                                    <p className="truncate text-sm font-semibold text-ink">{data.site_title || 'Judul situs'}</p>
                                    <p className="text-xs text-steel">Navigasi admin</p>
                                </div>
                            </div>
                            <div className="rounded-lg border border-hairline p-3">
                                <p className="text-[11px] font-semibold uppercase tracking-[0.12em] text-stone">Deskripsi</p>
                                <p className="mt-2 text-sm leading-6 text-charcoal">{data.site_description || 'Deskripsi situs akan tampil di sini.'}</p>
                            </div>
                        </div>
                    </aside>
                </form>
            </div>
        </AdminLayout>
    );
}
