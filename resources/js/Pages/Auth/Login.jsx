import { Link, useForm, usePage } from "@inertiajs/react";
import axios from "axios";
import { useEffect, useState } from "react";
import { Bounce, toast } from "react-toastify";

const Login = () => {
    const { site } = usePage().props;

    const [showPassword, setShowPassword] = useState(false);
    const [captchaUrl, setCaptchaUrl] = useState("");
    const [captchaInput, setCaptchaInput] = useState("");

    const { data, setData, post, processing, errors } = useForm({
        username: "",
        password: "",
        captcha: "",
        captcha_key: "",
    });

    useEffect(() => {
        fetchCaptcha();
    }, []);

    const fetchCaptcha = async () => {
        try {
            const res = await axios.get("/captcha-url");
            setCaptchaUrl(res.data.img);
            setCaptchaInput("");
            setData("captcha", "");
            setData("captcha_key", res.data.key);
        } catch (err) {
            console.error("Error fetching captcha:", err);
        }
    };

    const handleChange = (e) => {
        const key = e.target.id;
        const value = e.target.value;
        setData(key, value);
    };

    const handleCaptchaChange = (e) => {
        const value = e.target.value;
        setCaptchaInput(value);
        setData("captcha", value);
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        post("/sign-in", {
            onSuccess: (page) => {
                // Cek apakah server memberi URL redirect ke luar Inertia
                if (page.props.redirect_url) {
                    window.location.href = page.props.redirect_url;
                }
            },
            onError: (errors) => {
                const toastConfig = {
                    position: "top-right",
                    autoClose: 4000,
                    theme: "colored",
                    transition: Bounce,
                };

                // Captcha error (dari validasi Laravel)
                if (errors.captcha) {
                    toast.error("❌ CAPTCHA salah. Silakan coba lagi.", toastConfig);
                    fetchCaptcha();
                    return;
                }

                // Username / password kosong (validasi required)
                if (errors.username) {
                    toast.warning("⚠️ Username atau NIP wajib diisi.", toastConfig);
                    return;
                }

                if (errors.password) {
                    toast.warning("⚠️ Password wajib diisi.", toastConfig);
                    return;
                }

                // Error dari logika login controller
                if (errors.email_or_username) {
                    const msg = errors.email_or_username;

                    if (msg.includes("Kredensial tidak ditemukan") || msg.includes("akses ditolak")) {
                        toast.error("🚫 Akun tidak ditemukan atau akses ditolak.", toastConfig);
                    } else if (msg.includes("Username atau password salah") || msg.includes("Email atau password salah")) {
                        toast.error("🔑 Email atau password salah.", toastConfig);
                    } else if (msg.includes("hak akses berbeda")) {
                        toast.error("⛔ NIP ini terdaftar dengan hak akses yang berbeda.", toastConfig);
                    } else if (msg.includes("SSO tidak merespon") || msg.includes("Kredensial salah")) {
                        toast.error("🌐 Kredensial salah atau server SSO tidak merespon. Coba beberapa saat lagi.", {
                            ...toastConfig,
                            autoClose: 6000,
                        });
                    } else if (msg.includes("Gagal autentikasi SSO") || msg.includes("data NIP tidak ditemukan")) {
                        toast.error("❓ NIP tidak ditemukan di sistem. Pastikan NIP Anda benar.", toastConfig);
                    } else {
                        // Fallback untuk pesan lainnya
                        toast.error(`❌ ${msg}`, toastConfig);
                    }

                    fetchCaptcha();
                    return;
                }

                // Fallback umum
                toast.error(
                    errors.danger || "Terjadi kesalahan. Pastikan username dan password benar, lalu coba lagi.",
                    { ...toastConfig, autoClose: 5000 }
                );
                fetchCaptcha();
            },
        });
    };

    return (
        <section className="min-h-screen flex text-gray-900 bg-white">

            {/* Left Column - Image/Branding (Hidden on mobile) */}
            <div className="hidden lg:flex lg:w-1/2 bg-primary items-center justify-center relative overflow-hidden">
                <div className="absolute inset-0 bg-gradient-to-br from-primary via-primary text-primary-content to-blue-900 opacity-95"></div>

                {/* Decorative circles */}
                <div className="absolute -top-32 -left-32 w-96 h-96 bg-white/10 rounded-full blur-3xl"></div>
                <div className="absolute -bottom-32 -right-32 w-96 h-96 bg-white/10 rounded-full blur-3xl"></div>
                <div className="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-[120%] h-[120%] border-[40px] border-white/5 rounded-full pointer-events-none"></div>

                <div className="relative z-10 p-12 text-center text-white max-w-xl flex flex-col items-center">
                    <div className="bg-white/95 p-6 rounded-3xl shadow-2xl mb-10 backdrop-blur-sm transform transition-transform hover:scale-105 duration-300">
                        <img src={site.logo} alt="Logo" className="h-24 w-auto object-contain" />
                    </div>
                    <div className="mb-6">
                        <p className="text-base font-medium text-blue-200/80 uppercase tracking-widest mb-2">
                            Selamat Datang di
                        </p>
                        <div className="text-4xl md:text-5xl font-black tracking-[0.1em] text-white mb-3">
                            GAPURA
                        </div>
                        <h1 className="text-2xl md:text-3xl font-extrabold leading-snug tracking-tight text-white">
                            Gerbang Pengembangan Kompetensi Menuju SMART ASN
                        </h1>
                    </div>
                </div>
            </div>

            {/* Right Column - Login Form */}
            <div className="w-full lg:w-1/2 flex flex-col justify-center items-center p-6 sm:p-12 xl:p-24 bg-gray-50 relative">

                {/* Mobile Header / Logo */}
                <div className="lg:hidden w-full max-w-md mb-8 flex flex-col items-center">
                    <div className="bg-white p-4 rounded-2xl shadow-sm border border-gray-100 mb-6">
                        <img src={site.logo} alt="Logo" className="h-16 w-auto object-contain drop-shadow-sm" />
                    </div>
                    <h1 className="text-2xl font-bold text-gray-800 text-center">Masuk ke Akun</h1>
                </div>

                <div className="w-full max-w-md bg-white rounded-[2rem] shadow-xl shadow-gray-200/50 border border-gray-100 overflow-hidden transform transition-all">
                    <div className="p-8 sm:p-10">
                        <div className="hidden lg:block text-center mb-8">
                            <h2 className="text-3xl font-extrabold text-gray-800 mb-2 tracking-tight">Masuk</h2>
                            <p className="text-gray-500 font-medium">Silakan login untuk melanjutkan</p>
                        </div>

                        {errors.email_or_username && (
                            <div className="mb-6 p-4 bg-red-50 border border-red-100 rounded-2xl flex items-center gap-3 text-red-700 text-sm font-semibold animate-pulse">
                                <box-icon name='error-circle' color='#ef4444' size='24px'></box-icon>
                                <p>{errors.email_or_username}</p>
                            </div>
                        )}

                        <form onSubmit={handleSubmit} className="space-y-6">

                            {/* Username / NIP */}
                            <div>
                                <label htmlFor="username" className="block mb-2 text-sm font-bold text-gray-700">
                                    Email / NIP <span className="text-red-500">*</span>
                                </label>
                                <div className="relative group">
                                    <div className="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                        <box-icon name='user' color='#9ca3af' size='20px'></box-icon>
                                    </div>
                                    <input
                                        id="username"
                                        type="text"
                                        value={data.username}
                                        onChange={handleChange}
                                        placeholder="user@example.com / NIP"
                                        className={`block w-full pl-11 pr-4 py-3.5 bg-gray-50/50 border border-gray-200 text-gray-900 rounded-xl focus:bg-white focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all duration-200 ${errors.username ? "border-red-500 bg-red-50 ring-red-100" : "hover:border-gray-300"}`}
                                        required
                                    />
                                </div>
                                {errors.username && (
                                    <div className="mt-1.5 text-red-500 text-sm font-medium flex items-center gap-1">
                                        <box-icon name='error-circle' color='#ef4444' size='16px'></box-icon>
                                        {errors.username}
                                    </div>
                                )}
                            </div>

                            {/* Password */}
                            <div>
                                <label htmlFor="password" className="block mb-2 text-sm font-bold text-gray-700">
                                    Password <span className="text-red-500">*</span>
                                </label>
                                <div className="relative group">
                                    <div className="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                        <box-icon name='lock-alt' color='#9ca3af' size='20px'></box-icon>
                                    </div>
                                    <input
                                        id="password"
                                        type={showPassword ? "text" : "password"}
                                        value={data.password}
                                        onChange={handleChange}
                                        placeholder="••••••••"
                                        className={`block w-full pl-11 pr-12 py-3.5 bg-gray-50/50 border border-gray-200 text-gray-900 rounded-xl focus:bg-white focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all duration-200 ${errors.password ? "border-red-500 bg-red-50 ring-red-100" : "hover:border-gray-300"}`}
                                        required
                                    />
                                    <button
                                        type="button"
                                        onClick={() => setShowPassword(!showPassword)}
                                        className="absolute inset-y-0 right-0 pr-4 flex items-center text-gray-400 hover:text-primary transition-colors focus:outline-none"
                                    >
                                        <box-icon name={showPassword ? "hide" : "show"} color="currentColor" size="22px"></box-icon>
                                    </button>
                                </div>
                                {errors.password && (
                                    <div className="mt-1.5 text-red-500 text-sm font-medium flex items-center gap-1">
                                        <box-icon name='error-circle' color='#ef4444' size='16px'></box-icon>
                                        {errors.password}
                                    </div>
                                )}
                            </div>

                            {/* Captcha */}
                            <div className="pt-2">
                                <label className="block mb-2 text-sm font-bold text-gray-700">
                                    Verifikasi Keamanan <span className="text-red-500">*</span>
                                </label>
                                <div className="flex flex-row items-center gap-3">
                                    <div className="relative w-[55%] sm:w-[200px] flex-shrink-0 bg-gray-50 border border-gray-200 shadow-sm flex items-center justify-center rounded-xl overflow-hidden group cursor-pointer transition-all h-[52px] ring-2 ring-transparent hover:ring-primary/30" onClick={fetchCaptcha}>
                                        <img
                                            src={captchaUrl}
                                            alt="captcha"
                                            className="w-full h-full object-fill opacity-90 group-hover:opacity-100 transition-opacity"
                                            onError={(e) => {
                                                e.target.style.display = 'none';
                                                e.target.nextSibling.style.display = 'flex';
                                            }}
                                            onLoad={(e) => {
                                                e.target.style.display = 'block';
                                                if (e.target.nextSibling) e.target.nextSibling.style.display = 'none';
                                            }}
                                        />
                                        <div className="absolute inset-0 hidden flex-col items-center justify-center bg-gray-100 gap-1">
                                            <box-icon name='image-alt' color='#9ca3af' size='28px'></box-icon>
                                            <span className="text-[10px] text-gray-400 font-medium">Klik muat ulang</span>
                                        </div>
                                        <div className="absolute inset-0 bg-black/10 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                                            <box-icon name='refresh' color='white' size='26px'></box-icon>
                                        </div>
                                    </div>
                                    <input
                                        type="text"
                                        id="captcha"
                                        value={captchaInput}
                                        onChange={handleCaptchaChange}
                                        placeholder="Captcha"
                                        style={{ height: '52px' }}
                                        className={`flex-1 min-w-0 px-4 bg-gray-50/50 border border-gray-200 text-gray-900 font-mono tracking-widest text-center rounded-xl focus:bg-white focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all duration-200 ${errors.captcha ? "border-red-500 bg-red-50 ring-red-100" : "hover:border-gray-300"}`}
                                        required
                                    />
                                </div>
                                {errors.captcha && (
                                    <div className="mt-1.5 text-red-500 text-sm font-medium flex items-center gap-1">
                                        <box-icon name='error-circle' color='#ef4444' size='16px'></box-icon>
                                        {errors.captcha}
                                    </div>
                                )}
                                <p className="mt-2 text-xs font-medium text-gray-500 text-center sm:text-left flex items-center justify-center sm:justify-start gap-1">
                                    <box-icon name='info-circle' color='#6b7280' size='14px'></box-icon>
                                    Klik gambar untuk mengganti kode
                                </p>
                            </div>

                            <div className="pt-4">
                                <button
                                    type="submit"
                                    className="w-full flex items-center justify-center gap-2 py-3.5 px-4 border border-transparent rounded-xl shadow-sm text-base font-bold text-white bg-primary hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary/50 transition-all transform hover:-translate-y-0.5"
                                    disabled={processing}
                                >
                                    {processing ? (
                                        <>
                                            <box-icon name='loader-alt' animation='spin' color='white'></box-icon>
                                            Memproses...
                                        </>
                                    ) : (
                                        <>
                                            Sign In
                                            <box-icon name='right-arrow-alt' color='white' size='24px'></box-icon>
                                        </>
                                    )}
                                </button>
                            </div>
                        </form>
                    </div>

                    <div className="px-8 py-5 bg-gray-50/80 border-t border-gray-100 text-center">
                        <p className="text-sm font-medium text-gray-500">
                            Mengalami kendala login? <a href="#" className="font-semibold text-primary hover:underline transition-all">Hubungi Admin</a>
                        </p>
                    </div>
                </div>

                {/* Footer simple on mobile/desktop right column */}
                <div className="mt-8 text-center text-sm font-medium text-gray-400">
                    Copyright © {new Date().getFullYear()}{" "}
                    <Link href="/" className="font-bold hover:text-primary transition-colors">
                        bkpsdmkabupatengresik
                    </Link>. All Rights Reserved.
                </div>
            </div>
        </section>
    );
};

export default Login;
