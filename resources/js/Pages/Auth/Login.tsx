import { Link, useForm, usePage } from "@inertiajs/react";
import { useState } from "react";
import { useEffect } from "react";
import axios from "axios";

const Login = () => {
    const { site, errors } = usePage().props;
    const { data, setData, post, processing } = useForm({ email: '', password: '', captcha: '', remember: false });
    const [showPassword, setShowPassword] = useState(false);
    const [captchaUrl, setCaptchaUrl] = useState('');
    const [credentialEdited, setCredentialEdited] = useState(false);
    const [captchaEdited, setCaptchaEdited] = useState(false);
    const credentialError = credentialEdited ? null : errors.email || errors.password || errors.email_or_username;
    const captchaError = captchaEdited ? null : errors.captcha;

    useEffect(() => setCredentialEdited(false), [errors.email, errors.password, errors.email_or_username]);
    useEffect(() => setCaptchaEdited(false), [errors.captcha]);

    const fetchCaptcha = async () => {
        const response = await axios.get('/captcha-url');
        setCaptchaUrl(response.data.img);
        setData('captcha', '');
    };

    useEffect(() => {
        fetchCaptcha();
    }, []);

    const submit = (event) => {
        event.preventDefault();
        setCredentialEdited(false);
        setCaptchaEdited(false);
        post(route('sign-in.store'), { onError: () => fetchCaptcha() });
    };

    return (
        <main className="gapura-login relative min-h-screen overflow-hidden bg-[#F8FAFF] font-secondary text-[#1A2B3F]">
            <div className="pointer-events-none absolute -left-28 -top-32 h-80 w-80 rounded-full bg-[#D9F7FF]/70 blur-3xl" />
            <div className="pointer-events-none absolute -bottom-40 -right-28 h-96 w-96 rounded-full bg-[#DAF1FE]/80 blur-3xl" />
            <div className="grid min-h-screen w-full lg:grid-cols-2">
                <aside className="relative hidden overflow-hidden bg-[radial-gradient(circle_at_78%_20%,rgba(64,215,247,0.14),transparent_30%),linear-gradient(135deg,#003C52_0%,#0E536C_52%,#186D80_100%)] p-10 text-white lg:flex lg:flex-col lg:justify-between xl:p-14">
                    <div className="absolute -right-28 top-20 h-80 w-80 rounded-full border-[42px] border-[#40D7F7]/10" />
                    <div className="absolute -bottom-40 -left-40 h-[34rem] w-[34rem] rounded-full border-[70px] border-white/[0.05]" />
                    <Link href="/" aria-label="Kembali ke halaman utama" className="relative z-10 inline-flex w-fit items-center rounded-[15px] bg-white px-5 py-4 shadow-[0_16px_40px_rgba(0,20,30,0.22)] ring-1 ring-white/30 transition hover:-translate-y-0.5 hover:shadow-[0_20px_48px_rgba(0,20,30,0.28)] focus:outline-none focus:ring-4 focus:ring-[#95F2FF]/30">
                        <img src={site.logo} onError={(event) => { event.currentTarget.src = '/assets/media/images/static/site_logo.png'; }} alt="GAPURA" className="h-auto w-64 object-contain" />
                    </Link>
                    <div className="relative z-10 max-w-xl pb-4 xl:pb-10">
                        <h1 className="font-primary text-4xl font-semibold leading-[1.12] tracking-[-0.035em] xl:text-[52px]">Tumbuh bersama,<br /><span className="bg-gradient-to-r from-[#D9F7FF] to-[#95F2FF] bg-clip-text text-transparent">melayani lebih baik.</span></h1>
                        <p className="mt-6 max-w-md font-primary text-lg font-medium leading-7 text-white">Gerbang Pengembangan Kompetensi Menuju SMART ASN</p>
                        <p className="mt-3 max-w-md font-secondary text-sm leading-6 text-[#DAF1FE]">Platform pembelajaran untuk insan pembelajar di lingkungan Pemerintah Kabupaten Gresik.</p>
                        <div className="mt-10 flex items-center gap-4 font-secondary text-sm text-[#D9F7FF]"><span className="grid h-10 w-10 place-items-center rounded-[10px] border border-[#95F2FF]/25 bg-[#95F2FF]/10 text-lg text-[#95F2FF]">✦</span><span>Akses pembelajaran kapan saja<br /><strong className="font-semibold text-white">dari mana saja.</strong></span></div>
                    </div>
                    <p className="relative z-10 font-secondary text-xs font-medium tracking-wide text-[#DAF1FE]/65">BKPSDM Kabupaten Gresik • Jawa Timur</p>
                </aside>

                <section className="relative flex flex-col justify-center bg-[#F8FAFF] px-4 py-8 sm:px-8 lg:px-12 xl:px-14">
                    <div className="mx-auto w-full max-w-[480px]">
                        <div className="mb-8 flex items-center lg:hidden"><Link href="/" aria-label="Kembali ke halaman utama" className="inline-flex rounded-[15px] bg-white px-4 py-3 shadow-[0_2px_16px_rgba(14,83,108,0.08)] ring-1 ring-[#E8EDF2] transition hover:-translate-y-0.5 hover:shadow-[0_8px_24px_rgba(14,83,108,0.12)] focus:outline-none focus:ring-4 focus:ring-[#189AC9]/15"><img src={site.logo} onError={(event) => { event.currentTarget.src = '/assets/media/images/static/site_logo.png'; }} alt="GAPURA" className="h-auto w-56 object-contain" /></Link></div>
                        <div className="mb-7"><h2 className="font-primary text-3xl font-semibold leading-tight tracking-[-0.03em] text-[#003C52] sm:text-4xl">Selamat Datang Kembali</h2><p className="mt-2 font-secondary text-[15px] leading-6 text-[#526176]">Gunakan akun GAPURA untuk melanjutkan.</p></div>
                        {credentialError && <div className="mb-5 flex items-start gap-3 rounded-[12px] border border-[#FFCDD2] bg-[#FFF0F0] px-4 py-3.5 font-secondary text-sm font-medium text-[#E74C3C]"><box-icon name="error-circle" color="#E74C3C" size="20px" /><p>{credentialError}</p></div>}
                        <form onSubmit={submit} className="space-y-6">
                            <div><label htmlFor="email" className="mb-2 block font-secondary text-[13px] font-medium text-[#003C52]">Email atau Username</label><div className="relative"><span className="pointer-events-none absolute left-4 top-1/2 flex h-5 w-5 -translate-y-1/2 items-center justify-center leading-none text-[#8496B0]"><box-icon name="envelope" size="19px" className="block" /></span><input id="email" type="text" autoComplete="username" value={data.email} onChange={(e) => { setCredentialEdited(true); setData('email', e.target.value); }} placeholder="nama@email.com atau elearning" className={`h-14 w-full rounded-[10px] border bg-white px-11 py-3.5 font-secondary text-[15px] font-medium text-[#1A2B3F] shadow-[0_2px_16px_rgba(14,83,108,0.04)] outline-none transition placeholder:text-[#A8B8C8] hover:border-[#8496B0] focus:border-[#226F9D] focus:ring-4 focus:ring-[#226F9D]/10 ${credentialError ? 'border-red-500' : 'border-[#D0D9E6]'}`} aria-invalid={Boolean(credentialError)} required /></div></div>
                            <div><label htmlFor="password" className="mb-2 block font-secondary text-[13px] font-medium text-[#003C52]">Kata Sandi</label><div className="relative"><span className="pointer-events-none absolute left-4 top-1/2 flex h-5 w-5 -translate-y-1/2 items-center justify-center leading-none text-[#8496B0]"><box-icon name="lock-alt" size="19px" className="block" /></span><input id="password" type={showPassword ? 'text' : 'password'} autoComplete="current-password" value={data.password} onChange={(e) => { setCredentialEdited(true); setData('password', e.target.value); }} placeholder="************" className={`h-14 w-full rounded-[10px] border bg-white px-11 py-3.5 pr-12 font-secondary text-[15px] font-medium text-[#1A2B3F] shadow-[0_2px_16px_rgba(14,83,108,0.04)] outline-none transition placeholder:text-[#A8B8C8] hover:border-[#8496B0] focus:border-[#226F9D] focus:ring-4 focus:ring-[#226F9D]/10 ${credentialError ? 'border-red-500' : 'border-[#D0D9E6]'}`} aria-invalid={Boolean(credentialError)} required /><button type="button" onClick={() => setShowPassword((visible) => !visible)} className="absolute right-2.5 top-1/2 grid h-9 w-9 -translate-y-1/2 place-items-center rounded-lg text-[#8496B0] transition hover:bg-[#F0F4FA] hover:text-[#0E536C] focus:outline-none focus:ring-2 focus:ring-[#226F9D]/20" aria-label={showPassword ? 'Sembunyikan password' : 'Tampilkan password'}><box-icon name={showPassword ? 'show' : 'hide'} size="19px" className="block" /></button></div></div>
                            <div>
                                <div className="mb-2 flex items-center justify-between"><label htmlFor="captcha" className="font-secondary text-[13px] font-medium text-[#003C52]">Kode keamanan</label><button type="button" onClick={fetchCaptcha} className="inline-flex items-center gap-1 font-secondary text-xs font-semibold text-[#2D83B6] transition hover:text-[#0E536C]"><box-icon name="refresh" size="14px" className="block" />Ganti kode</button></div>
                                <div className="grid grid-cols-[180px_minmax(0,1fr)] gap-3">
                                    <button type="button" onClick={fetchCaptcha} className="h-14 w-[180px] overflow-hidden rounded-[10px] border border-[#D0D9E6] bg-[#F5FBFF] shadow-[0_2px_16px_rgba(14,83,108,0.04)] transition hover:border-[#2D83B6] focus:outline-none focus:ring-4 focus:ring-[#226F9D]/10" aria-label="Ganti kode keamanan"><img src={captchaUrl} alt="Kode keamanan" className="h-full w-full object-contain" /></button>
                                    <input id="captcha" type="text" autoComplete="off" value={data.captcha} onChange={(e) => { setCaptchaEdited(true); setData('captcha', e.target.value); }} placeholder="Ketik kode" className={`h-14 w-full rounded-[10px] border bg-white px-4 text-center font-secondary text-[15px] font-semibold uppercase tracking-[0.2em] text-[#1A2B3F] shadow-[0_2px_16px_rgba(14,83,108,0.04)] outline-none transition placeholder:normal-case placeholder:tracking-normal placeholder:text-[#A8B8C8] hover:border-[#8496B0] focus:border-[#226F9D] focus:ring-4 focus:ring-[#226F9D]/10 ${captchaError ? 'border-red-500' : 'border-[#D0D9E6]'}`} aria-invalid={Boolean(captchaError)} required />
                                </div>
                                {captchaError && <p className="mt-2 font-secondary text-xs font-medium text-[#E74C3C]">{captchaError}</p>}
                            </div>
                            <div className="flex items-center justify-between pt-0.5"><label className="flex cursor-pointer items-center gap-2.5 font-secondary text-sm text-[#526176]"><input type="checkbox" checked={data.remember} onChange={(e) => setData('remember', e.target.checked)} className="h-4 w-4 rounded border-[#C2C9D6] text-[#189AC9] focus:ring-[#226F9D]/20" /> Ingat saya</label><a href="#" className="font-secondary text-sm font-semibold text-[#2D83B6] hover:text-[#0E536C]">Lupa password?</a></div>
                            <button type="submit" disabled={processing} className="font-primary flex h-14 w-full items-center justify-center gap-2 rounded-[10px] bg-gradient-to-r from-[#189AC9] to-[#226F9D] px-4 text-[15px] font-semibold text-white shadow-[0_4px_16px_rgba(14,83,108,0.2)] transition hover:-translate-y-0.5 hover:shadow-[0_6px_24px_rgba(14,83,108,0.3)] focus:outline-none focus:ring-4 focus:ring-[#189AC9]/20 disabled:cursor-wait disabled:opacity-60">{processing ? 'Memproses...' : 'Masuk ke akun'}<box-icon name="right-arrow-alt" color="white" size="21px" /></button>
                        </form>
                        <div className="my-7 flex items-center gap-3 font-secondary text-xs font-semibold uppercase tracking-widest text-[#8496B0]"><span className="h-px flex-1 bg-[#E8EDF2]" />atau<span className="h-px flex-1 bg-[#E8EDF2]" /></div>
                        <a href={route('kedaton.redirect')} className="font-primary flex h-14 w-full items-center justify-center gap-2 rounded-[10px] border border-[#39A9A1] bg-white px-4 text-[15px] font-semibold text-[#018083] shadow-[0_2px_16px_rgba(1,128,131,0.06)] transition hover:border-[#018083] hover:bg-[#F0FAF8] focus:outline-none focus:ring-4 focus:ring-[#018083]/15"><box-icon name="shield-quarter" color="#018083" size="20px" />Masuk dengan KEDATON</a>
                        <p className="mt-8 text-center font-secondary text-sm text-[#66778C]">Mengalami kendala login? <a href="#" className="font-semibold text-[#2D83B6] hover:text-[#0E536C] hover:underline">Hubungi Admin</a></p>
                    </div>
                    <p className="mt-10 text-center font-secondary text-xs font-medium text-[#8496B0]">© {new Date().getFullYear()} <Link href="/" className="font-semibold text-[#526176] hover:text-[#0E536C]">GAPURA BKPSDM Gresik</Link></p>
                </section>
            </div>
        </main>
    );
};

export default Login;
