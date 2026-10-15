import { useState } from 'react';
import { usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
import Header from './Header';
import Footer from './Footer';
import '../../../css/participant.css';

export default function App({ children }: { children?: ReactNode }) {
    const [isOpen, setIsOpen] = useState(false);
    const { component } = usePage();
    const useGrayContent = ['Participant/Dashboard', 'Participant/Course/Index', 'Participant/Course/Show', 'Participant/Profile/Index'].includes(component);
    const contentBackground = useGrayContent ? 'bg-[#F8F9F8]' : 'bg-white';
    return <div className="flex min-h-screen flex-col justify-between bg-[#EEF1EF]"><Header isOpen={isOpen} setIsOpen={setIsOpen} /><main id="participant-content" className={`participant-content-frame mx-auto w-full max-w-[1320px] flex-1 ${contentBackground} px-4 pb-8 pt-[77px] md:px-8 md:pt-[150px]`}><div className={`participant-shell ${useGrayContent ? 'participant-shell--gray' : ''} min-h-[calc(100vh-12rem)] w-full py-4 md:py-8`}>{children}</div></main><Footer /></div>;
}
