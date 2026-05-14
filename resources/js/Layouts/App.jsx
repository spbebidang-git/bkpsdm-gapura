import Copyright from "@/Components/Copyright";
import { Link, usePage } from "@inertiajs/react";
import { useState } from "react";

export default function App({ children }) {
    const { auth, site } = usePage().props;
    const { url } = usePage();
    const [isOpen, setIsOpen] = useState(false);

    const toggleNavbar = () => setIsOpen(prev => !prev);

    return (
        <div className="flex flex-col justify-between min-h-screen">

            {/* Header */}
            <header className="fixed w-full top-0 z-50 bg-white/90 backdrop-blur-md shadow-sm border-b dark:bg-gray-900/90 transition-all duration-300">
                <div className="bg-primary h-2 w-full"></div>
                <nav className="border-gray-200">
                    <div className="max-w-screen-xl flex flex-wrap items-center justify-between mx-auto p-4">
                        {/* Logo */}
                        <Link href="/" className="flex items-center space-x-3 rtl:space-x-reverse transition-transform hover:scale-105 duration-300">
                            <img src={site.logo} alt="Logo" className="h-10 w-auto object-contain" />
                        </Link>

                        {/* User auth & hamburger */}
                        <div className="flex md:order-2 space-x-3 md:space-x-0 rtl:space-x-reverse">
                            <div className="hidden md:flex gap-3">
                                {auth.user?.name ? (
                                    <a href="/dashboard">
                                        <button className="button-primary flex items-center gap-2">
                                            <box-icon name="grid-alt" color="white" size="18px"></box-icon>
                                            Dashboard
                                        </button>
                                    </a>
                                ) : (
                                    <Link href="/sign-in">
                                        <button className="button-primary flex items-center gap-2">
                                            <box-icon name="log-in" color="white" size="18px"></box-icon>
                                            Sign In
                                        </button>
                                    </Link>
                                )}
                            </div>

                            <button
                                onClick={toggleNavbar}
                                type="button"
                                className="inline-flex items-center p-2 w-10 h-10 justify-center text-sm text-gray-500 rounded-lg md:hidden hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-gray-200 dark:text-gray-400 dark:hover:bg-gray-700 dark:focus:ring-gray-600"
                                aria-controls="navbar-cta"
                                aria-expanded={isOpen}
                            >
                                <span className="sr-only">Open main menu</span>
                                <svg
                                    className="w-5 h-5"
                                    aria-hidden="true"
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 17 14"
                                >
                                    <path
                                        stroke="currentColor"
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                        strokeWidth="2"
                                        d="M1 1h15M1 7h15M1 13h15"
                                    />
                                </svg>
                            </button>
                        </div>

                        {/* Navbar links */}
                        <div
                            className={`items-center justify-between w-full md:flex md:w-auto md:order-1 ${isOpen ? 'block' : 'hidden'}`}
                            id="navbar-cta"
                        >
                            <ul className="flex flex-col font-medium p-4 md:p-0 mt-4 border border-gray-100 rounded-lg bg-gray-50 md:space-x-8 rtl:space-x-reverse md:flex-row md:mt-0 md:border-0 md:bg-transparent dark:bg-gray-800 md:dark:bg-transparent dark:border-gray-700">
                                <li>
                                    <Link
                                        href="/"
                                        className={`block py-2 px-3 md:p-0 rounded transition-colors duration-200 md:bg-transparent ${url === '/' ? 'text-primary font-semibold md:text-primary' : 'text-gray-600 hover:text-primary font-medium'}`}
                                    >
                                        Beranda
                                    </Link>
                                </li>
                                <li>
                                    <Link
                                        href="/course"
                                        className={`block py-2 px-3 md:p-0 rounded transition-colors duration-200 md:bg-transparent ${url.startsWith('/course') ? 'text-primary font-semibold' : 'text-gray-600 hover:text-primary font-medium'}`}
                                    >
                                        Materi Pilihan
                                    </Link>
                                </li>
                                <li>
                                    <Link
                                        href="/help"
                                        className={`block py-2 px-3 md:p-0 rounded transition-colors duration-200 md:bg-transparent ${url === '/help' ? 'text-primary font-semibold' : 'text-gray-600 hover:text-primary font-medium'}`}
                                    >
                                        Bantuan
                                    </Link>
                                </li>
                                {/* Mobile Auth Buttons */}
                                <li className="md:hidden mt-4 pt-4 border-t border-gray-200">
                                    {auth.user?.name ? (
                                        <a href="/dashboard" className="block w-full">
                                            <button className="button-primary w-full flex justify-center items-center gap-2">
                                                <box-icon name="grid-alt" color="white" size="18px"></box-icon>
                                                Dashboard
                                            </button>
                                        </a>
                                    ) : (
                                        <Link href="/sign-in" className="block w-full">
                                            <button className="button-primary w-full flex justify-center items-center gap-2">
                                                <box-icon name="log-in" color="white" size="18px"></box-icon>
                                                Sign In
                                            </button>
                                        </Link>
                                    )}
                                </li>
                            </ul>
                        </div>
                    </div>
                </nav>
            </header>

            {/* Main */}
            <main className="flex-1 bg-gray-50 pt-20">
                {children}
            </main>

            {/* Footer */}
            <footer className="bg-gradient-to-br from-primary to-[#015f61] pt-16 pb-8 px-6 lg:px-12 relative overflow-hidden">
                {/* Decorative blob or subtle pattern */}
                <div className="absolute top-0 right-0 w-96 h-96 bg-white/5 rounded-full blur-3xl -translate-y-1/2 translate-x-1/3"></div>
                <div className="absolute bottom-0 left-0 w-80 h-80 bg-white/5 rounded-full blur-3xl translate-y-1/3 -translate-x-1/3"></div>

                <div className="max-w-7xl mx-auto border-b border-white/10 pb-12 relative z-10">
                    <div className="flex flex-col xl:flex-row gap-12 lg:gap-16">
                        {/* Left section */}
                        <div className="flex w-full xl:w-5/12 flex-col">
                            <div className="w-56 mb-6 bg-white/10 p-4 rounded-2xl backdrop-blur-sm border border-white/10 shadow-lg">
                                <img src={site.logo} alt="Footer Logo" className="object-contain" />
                            </div>
                            <p className="text-teal-50/80 font-medium leading-relaxed pr-0 xl:pr-8 text-sm md:text-[15px] text-justify">
                                GAPURA (Gerbang Pengembangan Kompetensi menuju Smart ASN) merupakan Sistem Pembelajaran untuk meningkatkan Kompetensi Aparatur Sipil Negera di lingkungan Pemerintah Kabupaten Gresik melalui metode pembelajaran berbasis e-learning yang dapat diakses oleh seluruh pegawai dimanapun dan kapanpun.
                            </p>
                        </div>

                        {/* Right section */}
                        <div className="w-full xl:w-7/12 text-white flex flex-col justify-center">
                            <div className="mb-8 p-6 bg-white/5 border border-white/10 rounded-2xl backdrop-blur-sm hover:bg-white/10 transition-colors duration-300">
                                <h4 className="text-xl font-extrabold mb-5 text-white tracking-wide flex items-center gap-3">
                                    <div className="w-2 h-6 bg-teal-400 rounded-full"></div>
                                    Kantor BKPSDM Gresik - Jawa Timur
                                </h4>
                                <div className="flex gap-4 items-start">
                                    <div className="bg-white/10 p-2.5 rounded-xl shrink-0 border border-white/10 backdrop-blur-sm shadow-sm">
                                        <box-icon name="map" color="white" size="22px"></box-icon>
                                    </div>
                                    <div>
                                        <div className="font-bold mb-1 text-teal-200 uppercase tracking-widest text-[11px] opacity-80">Alamat</div>
                                        <div className="text-teal-50/90 leading-relaxed font-medium sm:text-base text-sm">
                                            Lt. 3 Kantor Bupati Gresik<br className="hidden sm:block" />
                                            Jl. Dr. Wahidin Sudirohusodo No.245
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div className="flex flex-col md:flex-row gap-6">
                                <div className="w-full md:w-1/2 flex gap-4 items-start p-5 bg-white/5 border border-white/10 rounded-2xl backdrop-blur-sm hover:bg-white/10 transition-colors duration-300">
                                    <div className="bg-white/10 p-2.5 rounded-xl shrink-0 border border-white/10 backdrop-blur-sm shadow-sm">
                                        <box-icon name="phone-call" color="white" size="22px"></box-icon>
                                    </div>
                                    <div>
                                        <div className="font-bold mb-1 text-teal-200 uppercase tracking-widest text-[11px] opacity-80">Kontak</div>
                                        <div className="text-teal-50/90 leading-relaxed font-medium sm:text-base text-sm">
                                            Telp: 3952825-30 ext 307<br />
                                            Fax: 3952825-30 ext 307
                                        </div>
                                    </div>
                                </div>

                                <div className="w-full md:w-1/2 flex gap-4 items-start p-5 bg-white/5 border border-white/10 rounded-2xl backdrop-blur-sm hover:bg-white/10 transition-colors duration-300">
                                    <div className="bg-white/10 p-2.5 rounded-xl shrink-0 border border-white/10 backdrop-blur-sm shadow-sm">
                                        <box-icon name="envelope" color="white" size="22px"></box-icon>
                                    </div>
                                    <div>
                                        <div className="font-bold mb-1 text-teal-200 uppercase tracking-widest text-[11px] opacity-80">Email</div>
                                        <div className="text-teal-50/90 leading-relaxed font-medium sm:text-base text-sm mt-0.5 break-all">
                                            bkpsdm@gresikkab.go.id
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Copyright */}
                <div className="pt-6 relative z-10 w-full flex justify-center max-w-7xl mx-auto opacity-75 hover:opacity-100 transition-opacity">
                    <Copyright />
                </div>
            </footer>
        </div>
    );
}
