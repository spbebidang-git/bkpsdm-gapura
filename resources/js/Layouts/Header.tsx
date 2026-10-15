import Button from "@/Components/Button";
import { Link, usePage } from "@inertiajs/react";
import { useState } from "react";

const navigation = [
    { href: "/", label: "Beranda", isActive: (url: string) => url === "/" },
    {
        href: "/course",
        label: "Materi Pilihan",
        isActive: (url: string) => url.startsWith("/course"),
    },
    {
        href: "/help",
        label: "Pusat Bantuan",
        isActive: (url: string) => url === "/help",
    },
];

export default function Header() {
    const { auth, site } = usePage().props;
    const { url } = usePage();
    const [isOpen, setIsOpen] = useState(false);
    const authenticated = Boolean(auth.user?.name);

    return (
        <header className="fixed top-0 z-50 w-full bg-gray-100/85 backdrop-blur-sm">
            <div className="mx-auto w-full max-w-[1320px] overflow-hidden border-b border-gray-200/80 bg-white/95 shadow-[0_10px_30px_rgba(15,61,62,0.08)] backdrop-blur-xl transition-all duration-300">
                <div className="h-1.5 w-full bg-primary" />
                <nav aria-label="Navigasi utama" className="font-poppins">
                    <div className="mx-auto flex w-full flex-wrap items-center justify-between px-4 py-3 md:px-6">
                        <Link
                            href="/"
                            className="group flex items-center rounded-xl p-1 transition-all duration-300 hover:bg-primary-mist/60"
                            aria-label="GAPURA, beranda"
                        >
                            <img
                                src={site.logo}
                                alt="GAPURA"
                                className="h-9 w-auto object-contain transition-transform duration-300 group-hover:scale-[1.03] md:h-10"
                            />
                        </Link>

                        <div className="flex items-center gap-2.5 md:order-2 md:gap-3">
                            <AccountAction
                                authenticated={authenticated}
                                className="hidden md:inline-flex"
                            />
                            <button
                                onClick={() => setIsOpen((open) => !open)}
                                type="button"
                                className="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-gray-200 bg-gray-50/80 text-gray-500 transition hover:border-primary/30 hover:bg-primary-mist/70 hover:text-primary focus:outline-none focus-visible:ring-2 focus-visible:ring-primary/30 md:hidden"
                                aria-controls="public-navigation"
                                aria-expanded={isOpen}
                            >
                                <span className="sr-only">
                                    {isOpen ? "Tutup" : "Buka"} menu navigasi
                                </span>
                                <box-icon
                                    name={isOpen ? "x" : "menu"}
                                    color="#6b7280"
                                    size="22px"
                                />
                            </button>
                        </div>

                        <div
                            id="public-navigation"
                            className={`w-full md:order-1 md:flex md:w-auto ${isOpen ? "block" : "hidden"}`}
                        >
                            <ul className="mt-4 flex flex-col rounded-xl border border-gray-100 bg-gray-50/90 p-2.5 shadow-inner md:mt-0 md:flex-row md:gap-1.5 md:border-0 md:bg-transparent md:p-0 md:shadow-none">
                                {navigation.map((item) => (
                                    <li key={item.href}>
                                        <Link
                                            href={item.href}
                                            onClick={() => setIsOpen(false)}
                                            className={`block px-3.5 py-2.5 text-[15px] transition-colors duration-200 md:px-4 md:text-[17px] ${item.isActive(url) ? "font-semibold text-primary" : "font-light text-gray-600 hover:text-primary"}`}
                                        >
                                            <span className="relative inline-block">
                                                {item.label}
                                                {item.isActive(url) && (
                                                    <span
                                                        className="absolute -bottom-1 left-0 h-0.5 w-full rounded-full bg-primary"
                                                        aria-hidden="true"
                                                    />
                                                )}
                                            </span>
                                        </Link>
                                    </li>
                                ))}
                                <li className="mt-2 border-t border-gray-200 pt-2 md:hidden">
                                    <AccountAction
                                        authenticated={authenticated}
                                        className="flex w-full"
                                    />
                                </li>
                            </ul>
                        </div>
                    </div>
                </nav>
            </div>
        </header>
    );
}

function AccountAction({
    authenticated,
    className = "",
}: {
    authenticated: boolean;
    className?: string;
}) {
    const label = authenticated ? "Dashboard" : "Masuk";
    const href = authenticated ? "/dashboard" : "/sign-in";
    const icon = authenticated ? "grid-alt" : "log-in";

    return (
        <Button
            href={href}
            variant="primary"
            size="md"
            radius="lg"
            className={`!min-h-10 !px-6 !text-sm shadow-[0_2px_8px_rgba(1,128,131,0.10)] hover:-translate-y-0.5 hover:shadow-[0_4px_12px_rgba(1,128,131,0.16)] ${className}`}
        >
            {icon && <box-icon name={icon} color="white" size="19px" />}
            {label}
        </Button>
    );
}
