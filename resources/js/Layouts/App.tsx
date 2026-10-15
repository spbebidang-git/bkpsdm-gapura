import type { ReactNode } from 'react';
import { usePage } from '@inertiajs/react';
import '../../css/public-home-layout.css';
import Header from './Header';
import Footer from './Footer';

export default function App({ children }: { children?: ReactNode }) {
    const isHome = usePage().component === 'Home/Index';
    return (
        <div className={`flex min-h-screen flex-col justify-between ${isHome ? 'public-home-layout' : 'bg-gray-100'}`}>
            <Header />
            <main className="mx-auto flex-1 w-full max-w-[1320px] bg-gray-100 pt-20">{children}</main>
            <Footer />
        </div>
    );
}
