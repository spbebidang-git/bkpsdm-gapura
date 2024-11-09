import { usePage } from '@inertiajs/react';
import { useEffect } from 'react';
import { Bounce, toast } from 'react-toastify';

const FlashMessageToast = () => {
    const { flash } = usePage().props

    useEffect(() => {

        if (flash.success) {
            toast.success(flash.success, {
                position: "top-right",
                autoClose: 3000,
                hideProgressBar: false,
                closeOnClick: true,
                pauseOnHover: true,
                draggable: true,
                progress: undefined,
                theme: "colored",
                transition: Bounce,
            });
        }

        if (flash.danger) {
            toast.error(flash.danger, {
                position: "top-right",
                autoClose: 3000,
                hideProgressBar: false,
                closeOnClick: true,
                pauseOnHover: true,
                draggable: true,
                progress: undefined,
                theme: "colored",
                transition: Bounce,
            });
        }
    }, [flash]);

    return null;
};

export default FlashMessageToast;
