import FlashMessageToast from "@/Components/FlashMessageToast";
import FormInput from "@/Components/FormInput";
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
    });

    useEffect(() => {
        fetchCaptcha();
    }, []);

    const fetchCaptcha = async () => {
        try {
            const res = await axios.get("/captcha-url");
            setCaptchaUrl(res.data.captcha);
            setCaptchaInput("");
            setData("captcha", "");
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
                    } else if (msg.includes("Username atau password salah")) {
                        toast.error("🔑 Username atau password yang Anda masukkan salah.", toastConfig);
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
        <section className="min-h-screen flex flex-col bg-gray-100">
            <FlashMessageToast />
            <div className="bg-primary h-2"></div>

            <div className="p-5 xl:p-0 flex-1 flex flex-col justify-center items-center">
                <Link href="/" className="flex items-center max-w-56 mb-6 text-2xl font-semibold text-gray-900">
                    <img src={site.logo} alt="Logo" className="w-full h-full object-fill" />
                </Link>

                <div className="w-full bg-white rounded-lg shadow sm:max-w-md xl:p-0">
                    <div className="p-6 space-y-4 md:space-y-6 sm:p-8">
                        <h1 className="text-xl font-bold leading-tight tracking-tight text-gray-900 md:text-2xl">
                            Sign In
                        </h1>

                        <form onSubmit={handleSubmit} className="space-y-4">
                            <FormInput
                                title="username"
                                id="username"
                                isRequired
                                placeholder="Masukkan email atau NIP"
                                value={data.username}
                                onChange={handleChange}
                                error={errors.username}
                            />

                            <div>
                                <label htmlFor="password" className="block mb-1 text-sm font-medium text-gray-900">
                                    Password <span className="text-red-500">*</span>
                                </label>
                                <div className="relative">
                                    <input
                                        id="password"
                                        type={showPassword ? "text" : "password"}
                                        value={data.password}
                                        onChange={handleChange}
                                        placeholder="********"
                                        className={`form-control w-full pr-10 ${errors.password ? "border-red-500" : ""
                                            }`}
                                        required
                                    />
                                    <button
                                        type="button"
                                        onClick={() => setShowPassword(!showPassword)}
                                        className="absolute right-2 top-1/2 transform -translate-y-1/2 text-gray-600 text-sm focus:outline-none"
                                    >
                                        {showPassword ? "Hide" : "Show"}
                                    </button>
                                </div>
                                {errors.password && (
                                    <div className="mt-1 text-red-500 text-sm">
                                        {errors.password}
                                    </div>
                                )}
                            </div>

                            <div>
                                <div className="flex items-center gap-3">
                                    <div className="flex-1 border border-gray-300 shadow flex items-center rounded-xl overflow-hidden">
                                        <img
                                            onClick={fetchCaptcha}
                                            src={captchaUrl}
                                            alt="captcha"
                                            className="w-full h-full cursor-pointer"
                                        />
                                    </div>
                                    <div className="w-1/3">
                                        <input
                                            type="text"
                                            id="captcha"
                                            value={captchaInput}
                                            onChange={handleCaptchaChange}
                                            placeholder="CAPTCHA"
                                            className={`form-control ${errors.captcha ? "border-red-500" : ""
                                                }`}
                                        />
                                    </div>
                                </div>
                                {errors.captcha && (
                                    <div className="mt-1 text-red-500 text-sm">
                                        {errors.captcha}
                                    </div>
                                )}
                                <small className="text-gray-600 italic">
                                    Klik gambar untuk refresh CAPTCHA.
                                </small>
                            </div>

                            <button
                                type="submit"
                                className="button-primary w-full mt-2"
                                disabled={processing}
                            >
                                {processing ? "Please wait..." : "Sign In"}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </section>
    );
};

export default Login;
