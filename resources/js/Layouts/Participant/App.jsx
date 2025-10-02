import FlashMessageToast from '@/Components/FlashMessageToast';
import { Link, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { ToastContainer } from 'react-toastify';

export default function App({ children }) {
    const [isOpen, setIsOpen] = useState(false);
    const toggleNavbar = () => setIsOpen(prev => !prev);

    const { auth, site } = usePage().props;
    const { url } = usePage();

    return (
        <div className="flex flex-col justify-between min-h-screen">
            <FlashMessageToast />

            {/* Header */}
            <header className="bg-gray-50 shadow border-b">
                <div className="bg-primary h-2" />
                <nav className="bg-white border-gray-200 dark:bg-gray-900">
                    <div className="max-w-screen-xl flex flex-wrap items-center justify-between mx-auto p-4">
                        {/* Logo */}
                        <Link href="/" className="flex items-center space-x-3 rtl:space-x-reverse">
                            <img src={site.logo} alt="Logo" className="w-full h-10 object-fill" />
                        </Link>

                        {/* Right menu */}
                        <div className="flex items-center md:order-2 space-x-3 md:space-x-0 rtl:space-x-reverse">
                            <Link href={route('logout')} method="post" as="button">
                                <button className="button-primary">Sign Out</button>
                            </Link>

                            <button
                                onClick={toggleNavbar}
                                type="button"
                                className="inline-flex items-center p-2 w-10 h-10 justify-center text-sm text-gray-500 rounded-lg md:hidden hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-gray-200 dark:text-gray-400 dark:hover:bg-gray-700 dark:focus:ring-gray-600"
                                aria-controls="navbar-user"
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

                        {/* Navigation links */}
                        <div
                            id="navbar-user"
                            className={`w-full md:flex md:w-auto md:order-1 items-center justify-between ${isOpen ? '' : 'hidden'}`}
                        >
                            <ul className="flex flex-col font-medium p-4 md:p-0 mt-4 border border-gray-100 rounded-lg bg-gray-50 md:space-x-8 rtl:space-x-reverse md:flex-row md:mt-0 md:border-0 md:bg-white dark:bg-gray-800 md:dark:bg-gray-900 dark:border-gray-700">
                                <li>
                                    <Link
                                        href="/dashboard"
                                        className={`block py-2 px-3 md:p-0 rounded md:bg-transparent ${url === '/dashboard' ? 'text-primary' : 'text-gray-900'
                                            }`}
                                    >
                                        Dashboard
                                    </Link>
                                </li>
                                <li>
                                    <Link
                                        href="/my-course"
                                        className={`block py-2 px-3 md:p-0 rounded md:bg-transparent ${url === '/my-course' ? 'text-primary' : 'text-gray-900'
                                            }`}
                                    >
                                        Kelas Saya
                                    </Link>
                                </li>
                                {/* <li>
                                    <Link
                                        href="/profile"
                                        className={`block py-2 px-3 md:p-0 rounded md:bg-transparent ${url === '/profile' ? 'text-primary' : 'text-gray-900'
                                            }`}
                                    >
                                        Profile
                                    </Link>
                                </li> */}
                            </ul>
                        </div>
                    </div>
                </nav>
            </header>

            {/* Main Content */}
            <main className="flex-1 bg-gray-50 p-8">
                <ToastContainer />
                {children}
            </main>

            {/* Footer */}
            <footer className="bg-primary h-12" />
        </div>
    );
}
