import { router } from '@inertiajs/react';
import { useEffect } from 'react';
import { Bounce, toast } from 'react-toastify';

const showFlashToast = (flash = {}) => {
    if (flash.success) {
        toast.success(flash.success, {
            position: 'top-right',
            autoClose: 3000,
            hideProgressBar: false,
            closeOnClick: true,
            pauseOnHover: true,
            draggable: true,
            progress: undefined,
            theme: 'colored',
            transition: Bounce,
        });
    }

    if (flash.danger) {
        toast.error(flash.danger, {
            position: 'top-right',
            autoClose: 3000,
            hideProgressBar: false,
            closeOnClick: true,
            pauseOnHover: true,
            draggable: true,
            progress: undefined,
            theme: 'colored',
            transition: Bounce,
        });
    }
};

const FlashMessageToast = ({ initialFlash = {} }) => {
    useEffect(() => {
        showFlashToast(initialFlash);

        const removeSuccessListener = router.on('success', (event) => {
            showFlashToast(event.detail.page.props.flash ?? {});
        });

        return () => {
            removeSuccessListener();
        };
    }, [initialFlash]);

    return null;
};

export default FlashMessageToast;
