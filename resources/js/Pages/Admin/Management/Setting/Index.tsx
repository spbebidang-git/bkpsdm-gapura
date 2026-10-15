import { CrudFormSection, CrudPageHeader } from '@/Components/Admin/CrudPrimitives';
import Button from '@/Components/Button';
import ErrorMessage from '@/Components/ErrorMessage';
import FormInput from '@/Components/FormInput';
import FormTextArea from '@/Components/FormTextArea';
import useTransientFieldError from '@/Components/useTransientFieldError';
import AdminLayout from '@/Layouts/Admin/App';
import { Head, useForm } from '@inertiajs/react';
import '@/../css/admin-setting.css';

export default function Index({ settings }) {
    const { data, setData, post, processing, errors } = useForm({
        site_title: settings.site_title ?? '',
        site_description: settings.site_description ?? '',
        site_logo: null,
        home_banner: null,
    });
    const { containerRef: logoRef, visibleError: logoError, dismissError: dismissLogoError } = useTransientFieldError(errors.site_logo);
    const { containerRef: bannerRef, visibleError: bannerError, dismissError: dismissBannerError } = useTransientFieldError(errors.home_banner);

    const submit = (event) => {
        event.preventDefault();
        post(route('admin.management.setting.store'), { forceFormData: true });
    };

    return (
        <AdminLayout title="Pengaturan" hideTitle>
            <Head title="Pengaturan" />

            <div className="setting-admin">
                <CrudPageHeader title="Pengaturan situs" description="Atur identitas dan banner halaman utama GAPURA." actions={
                    <Button type="submit" form="setting-form" loading={processing} className="min-w-28">
                        {processing ? 'Menyimpan…' : 'Simpan'}
                    </Button>
                } />
                <form id="setting-form" noValidate onSubmit={submit} aria-busy={processing} className="admin-crud-form">
                    <CrudFormSection title="Identitas situs" description="Informasi yang ditampilkan kepada pengguna." icon="bx-buildings">
                        <FormInput
                            id="site_title"
                            label="Nama situs"
                            isRequired
                            value={data.site_title}
                            onChange={(event) => setData('site_title', event.target.value)}
                            error={errors.site_title}
                            placeholder="Contoh: GAPURA"
                        />

                        <FormTextArea
                            id="site_description"
                            label="Deskripsi"
                            isRequired
                            rows={4}
                            value={data.site_description}
                            onChange={(event) => setData('site_description', event.target.value)}
                            error={errors.site_description}
                            placeholder="Deskripsi singkat situs"
                            inputClassName="resize-y"
                        />

                        <div ref={logoRef}>
                            <label htmlFor="site_logo" className="form-label">Logo</label>
                            <label htmlFor="site_logo" className="setting-upload">
                                <span className="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-white text-[#018083] shadow-[0_1px_4px_rgba(7,61,75,0.08)]">
                                    <i className="bx bx-image-add text-xl" aria-hidden />
                                </span>
                                <span className="min-w-0 flex-1">
                                    <span className="block truncate text-sm font-semibold text-[#075D5E]">{data.site_logo?.name || 'Pilih file logo'}</span>
                                    <span className="mt-0.5 block text-xs text-[#8496B0]">PNG atau JPG · Maksimal 10 MB</span>
                                </span>
                                <span className="shrink-0 rounded-lg border border-[#D0D9E6] bg-white px-3 py-2 text-xs font-semibold text-[#526176]">Telusuri</span>
                                <input
                                    id="site_logo"
                                    type="file"
                                    accept="image/png,image/jpeg"
                                    className="sr-only"
                                    disabled={processing}
                                    aria-invalid={Boolean(logoError)}
                                    onChange={(event) => { dismissLogoError(); setData('site_logo', event.target.files?.[0] ?? null); }}
                                />
                            </label>
                            <p className="mt-1.5 text-xs text-[#8496B0]">Kosongkan jika logo tidak ingin diubah.</p>
                            <ErrorMessage message={logoError} />
                        </div>
                    </CrudFormSection>
                    <CrudFormSection title="Banner halaman utama" description="Gambar utama yang tampil di beranda." icon="bx-image">
                        <div ref={bannerRef}>
                            <label htmlFor="home_banner" className="form-label">Gambar banner</label>
                            <label htmlFor="home_banner" className="setting-upload">
                                <span className="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-white text-[#018083] shadow-[0_1px_4px_rgba(7,61,75,0.08)]">
                                    <i className="bx bx-image-add text-xl" aria-hidden />
                                </span>
                                <span className="min-w-0 flex-1">
                                    <span className="block truncate text-sm font-semibold text-[#075D5E]">{data.home_banner?.name || 'Pilih gambar banner'}</span>
                                    <span className="mt-0.5 block text-xs text-[#8496B0]">PNG, JPG, atau WebP · Maksimal 15 MB</span>
                                </span>
                                <span className="shrink-0 rounded-lg border border-[#D0D9E6] bg-white px-3 py-2 text-xs font-semibold text-[#526176]">Telusuri</span>
                                <input
                                    id="home_banner"
                                    type="file"
                                    accept="image/png,image/jpeg,image/webp"
                                    className="sr-only"
                                    disabled={processing}
                                    aria-invalid={Boolean(bannerError)}
                                    onChange={(event) => { dismissBannerError(); setData('home_banner', event.target.files?.[0] ?? null); }}
                                />
                            </label>
                            <p className="mt-1.5 text-xs text-[#8496B0]">Gunakan gambar landscape. Kosongkan jika banner tidak ingin diubah.</p>
                            <ErrorMessage message={bannerError} />
                        </div>
                    </CrudFormSection>
                </form>
            </div>
        </AdminLayout>
    );
}
