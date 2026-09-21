import { Link, usePage } from "@inertiajs/react";

const Login = () => {
    const { site, errors } = usePage().props;

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
                        <div className="text-center mb-8">
                            <h2 className="text-3xl font-extrabold text-gray-800 mb-2 tracking-tight">Masuk</h2>
                            <p className="text-gray-500 font-medium">Gunakan akun Kedaton untuk masuk ke GAPURA.</p>
                        </div>

                        {errors.email_or_username && (
                            <div className="mb-6 p-4 bg-red-50 border border-red-100 rounded-2xl flex items-center gap-3 text-red-700 text-sm font-semibold animate-pulse">
                                <box-icon name='error-circle' color='#ef4444' size='24px'></box-icon>
                                <p>{errors.email_or_username}</p>
                            </div>
                        )}

                        <a
                            href="/auth/kedaton/redirect"
                            className="w-full flex items-center justify-center gap-2 py-3.5 px-4 border border-transparent rounded-xl shadow-sm text-base font-bold text-white bg-primary hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary/50 transition-all"
                        >
                            Masuk dengan Kedaton
                            <box-icon name='log-in-circle' color='white' size='22px'></box-icon>
                        </a>
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
