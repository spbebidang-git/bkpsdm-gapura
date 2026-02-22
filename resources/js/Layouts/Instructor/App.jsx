import Dropdown from "@/Components/Dropdown";
import FlashMessageToast from "@/Components/FlashMessageToast";
import { Link, usePage } from '@inertiajs/react';


export default function App({ children }) {
    const { auth, site } = usePage().props
    const { url, component } = usePage()

    return (
        <div className="flex flex-col justify-between min-h-screen">
            <FlashMessageToast />
            <header className="sticky top-0 z-50 bg-gray-50 shadow border-b">
                <div class="bg-primary h-2"></div>
                <nav class="bg-white border-gray-200 ">
                    <div class="max-w-screen-xl flex flex-wrap items-center justify-between mx-auto p-2">
                        <Link href="/" className="flex h-8 items-center text-2xl font-semibold text-gray-900">
                            <img src={site.logo} alt="" className='w-full h-full object-fill' />
                        </Link>
                        <div class="flex items-center md:order-2 space-x-3 md:space-x-0 rtl:space-x-reverse">
                            <div className="hidden md:flex">
                                <Dropdown.Link href={route('logout')} method="post" as="button">
                                    <button className="flex items-center justify-center gap-1 px-4 h-8 text-xs font-bold bg-primary text-white rounded-full shadow hover:bg-red-600 transition-colors">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={2} stroke="currentColor" className="w-3.5 h-3.5">
                                            <path strokeLinecap="round" strokeLinejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9" />
                                        </svg>
                                        Keluar
                                    </button>
                                </Dropdown.Link>
                            </div>
                            <button data-collapse-toggle="navbar-user" type="button" class="inline-flex items-center p-2 w-10 h-10 justify-center text-sm text-gray-500 rounded-lg md:hidden hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-gray-200  :bg-gray-700 :ring-gray-600" aria-controls="navbar-user" aria-expanded="false">
                                <span class="sr-only">Open main menu</span>
                                <svg class="w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 17 14">
                                    <path stroke="currentColor" strokeLinecap="round" stroke-linejoin="round" stroke-width="2" d="M1 1h15M1 7h15M1 13h15" />
                                </svg>
                            </button>
                        </div>
                        <div class="items-center justify-between hidden w-full md:flex md:w-auto md:order-1" id="navbar-user">
                            <ul class="flex flex-col font-medium p-4 md:p-0 mt-4 border border-gray-100 rounded-lg bg-gray-50 md:space-x-8 rtl:space-x-reverse md:flex-row md:mt-0 md:border-0 md:bg-white">
                                <li>
                                    <Link href="/instructor" className={`block py-2 px-3 md:p-0  rounded hover:bg-gray-100 md:hover:bg-transparent md:hover:text-primary ${url.startsWith('/instructor') && !url.startsWith('/instructor/profile') && !url.startsWith('/instructor/result') ? 'text-primary' : 'text-gray-900'}`} aria-current="page">Dashboard</Link>
                                </li>
                                {/* <li>
                                    <Link href="/instructor/result" className={`block py-2 px-3 md:p-0  rounded hover:bg-gray-100 md:hover:bg-transparent md:hover:text-primary ${url === '/instructor/result' ? 'text-primary' : 'text-gray-900'}`} aria-current="page">Results</Link>
                                </li> */}
                                <li>
                                    <Link href="/instructor/profile" className={`block py-2 px-3 md:p-0  rounded hover:bg-gray-100 md:hover:bg-transparent md:hover:text-primary ${url === '/instructor/profile' ? 'text-primary' : 'text-gray-900'}`}>Profile</Link>
                                </li>
                                <li className="md:hidden mt-4 pt-4 border-t border-gray-200">
                                    <Dropdown.Link href={route('logout')} method="post" as="button" className="w-full">
                                        <button className="flex w-full items-center justify-center gap-2 px-4 py-2.5 text-sm font-bold bg-red-50 text-red-600 hover:bg-red-100 hover:text-red-700 transition-colors rounded-xl">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={2} stroke="currentColor" className="w-4 h-4">
                                                <path strokeLinecap="round" strokeLinejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9" />
                                            </svg>
                                            Keluar
                                        </button>
                                    </Dropdown.Link>
                                </li>
                            </ul>
                        </div>
                    </div>
                </nav>


            </header>

            <main className="flex-1 bg-gray-50 p-8">
                {children}
            </main>
            <footer className="bg-primary h-12">

            </footer>
        </div>
    );
}
