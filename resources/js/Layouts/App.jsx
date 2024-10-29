import { Link, usePage } from "@inertiajs/react";

export default function App({ children }) {
    const { auth, site } = usePage().props
    const { url, component } = usePage()

    return (
        <div className="flex flex-col justify-between min-h-screen">
            <header>
                <div class="bg-primary h-2"></div>
                <nav class="bg-white border-gray-200 dark:bg-gray-900">
                    <div class="max-w-screen-xl flex flex-wrap items-center justify-between mx-auto p-4">
                        <Link href="/" class="flex items-center space-x-3 rtl:space-x-reverse">
                            <img src={site.logo} alt="" className='w-full h-10 object-fill' />
                        </Link>
                        <div class="flex md:order-2 space-x-3 md:space-x-0 rtl:space-x-reverse">
                            {auth.user.name ? (
                                <Link href="/dashboard">
                                    <button className="button-primary">Dashboard</button>
                                </Link>
                            ) : <Link href="/sign-in">
                                <button className="button-primary">Sign In</button>
                            </Link>}
                            <button data-collapse-toggle="navbar-cta" type="button" class="inline-flex items-center p-2 w-10 h-10 justify-center text-sm text-gray-500 rounded-lg md:hidden hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-gray-200 dark:text-gray-400 dark:hover:bg-gray-700 dark:focus:ring-gray-600" aria-controls="navbar-cta" aria-expanded="false">
                                <span class="sr-only">Open main menu</span>
                                <svg class="w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 17 14">
                                    <path stroke="currentColor" strokeLinecap="round" stroke-linejoin="round" stroke-width="2" d="M1 1h15M1 7h15M1 13h15" />
                                </svg>
                            </button>
                        </div>
                        <div class="items-center justify-between hidden w-full md:flex md:w-auto md:order-1" id="navbar-cta">
                            <ul class="flex flex-col font-medium p-4 md:p-0 mt-4 border border-gray-100 rounded-lg bg-gray-50 md:space-x-8 rtl:space-x-reverse md:flex-row md:mt-0 md:border-0 md:bg-white dark:bg-gray-800 md:dark:bg-gray-900 dark:border-gray-700">
                                <li>
                                    <Link href="/" className={`block py-2 px-3 md:p-0 bg-blue-700 rounded md:bg-transparent text-gray-900 ${url === '/' ? 'md:text-primary' : 'text-gray-900'}`} aria-current="page">Beranda</Link>
                                </li>
                                <li>
                                    <Link href="/about" className={`block py-2 px-3 md:p-0  rounded hover:bg-gray-100 md:hover:bg-transparent md:hover:text-primary ${url === '/about' ? 'text-primary' : 'text-gray-900'}`}>Tentang Kami</Link>
                                </li>
                                <li>
                                    <Link href="/course" className={`block py-2 px-3 md:p-0  rounded hover:bg-gray-100 md:hover:bg-transparent md:hover:text-primary ${url === '/course' ? 'text-primary' : 'text-gray-900'}`}>Materi Pilihan</Link>
                                </li>
                                <li>
                                    <Link href="/contact" className={`block py-2 px-3 md:p-0 text-gray-900 rounded hover:bg-gray-100 md:hover:bg-transparent md:hover:text-primary ${url === '/contact' ? 'text-primary' : 'text-gray-900'}`}>Kontak</Link>
                                </li>
                            </ul>
                        </div>
                    </div>
                </nav>

            </header>
            <main className="flex-1 bg-gray-50">
                {children}
            </main>

            <footer class="bg-primary p-5 xl:p-8">
                <div className="max-w-7xl mx-auto gap-5 border-b pb-5">
                    <div className="flex flex-col xl:flex-row gap-8">
                        <div className="flex w-full xl:w-1/2 flex-col">
                            <div className="w-56 mb-3 ">
                                <img src={site.logo} alt="" />
                            </div>
                            <div className="text-white font-medium">
                                GAPURA (Gerbang Pengembangan Kompetensi menuju Smart ASN) merupakan Sistem Pembelajaran untuk meningkatkan Kompetensi Aparatur Sipil Negera di lingkungan Pemerintah Kabupaten Gresik melalui metode pembelajaran berbasis e-learning yang dapat diakses oleh seluruh pegawai dimanapun dan kapanpun
                            </div>
                        </div>
                        <div className="w-full xl:w-1/2 text-white">
                            <div class="mb-2.5">
                                <h4 class="font-extrabold mb-2.5">Kantor BKPSDM Gresik - Jawa Timur</h4>
                                <h5>
                                    <div className="flex items-center gap-1 font-bold mb-1">
                                        <box-icon name='map' color='white' ></box-icon>
                                        Alamat
                                    </div>
                                    Lt. 3 Kantor Bupati Gresik Jl. Dr. Wahidin Sudirohusodo No.245
                                </h5>
                            </div>
                            <div className="flex flex-col xl:flex-row">
                                <div className="w-full xl:w-1/2">
                                    <div className="flex items-center gap-1 font-bold mb-1">
                                        <box-icon name='phone' color='white'></box-icon>
                                        Phone
                                    </div>
                                    <div>
                                        Telephone : 3952825-30 ext : 307
                                        <br />
                                        Fax : 3952825-30 ext : 307
                                    </div>
                                </div>
                                <div className="w-full xl:w-1/2">
                                    <div className="flex items-center gap-1 font-bold mb-1">
                                        <box-icon name='envelope' color='white'></box-icon>
                                        Email
                                    </div>
                                    <div>
                                        bkpsdm@gresikkab.go.id
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div className="max-w-7xl mx-auto text-center text-sm text-white font-medium pt-4 xl:pt-8">
                    Copyright © 2024 <Link href="/" className="font-bold">bkpsdmkabupatengresik</Link>. All Right Reserverd.
                </div>
            </footer>




        </div>
    );
}
