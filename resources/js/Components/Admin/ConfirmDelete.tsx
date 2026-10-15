import Swal from 'sweetalert2';
import { router } from '@inertiajs/react';
import '@/../css/admin-confirm-dialog.css';

export function confirmDelete(
    url: string,
    { message = 'Hapus data ini?', onSuccess }: { message?: string; onSuccess?: () => void } = {},
) {
    return Swal.fire({
        title: 'Hapus data?',
        text: message,
        icon: 'warning',
        iconColor: '#E74C3C',
        width: 440,
        padding: '28px 32px',
        backdrop: 'rgba(0, 20, 30, 0.5)',
        buttonsStyling: false,
        showCancelButton: true,
        reverseButtons: true,
        focusCancel: true,
        confirmButtonColor: '#A43B2F',
        cancelButtonColor: '#52615D',
        confirmButtonText: 'Hapus',
        cancelButtonText: 'Batalkan',
        customClass: {
            container: 'admin-confirm-container',
            popup: 'admin-confirm-dialog',
            title: 'admin-confirm-title',
            htmlContainer: 'admin-confirm-message',
            actions: 'admin-confirm-actions',
            confirmButton: 'admin-confirm-delete',
            cancelButton: 'admin-confirm-cancel',
        },
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(url, { preserveScroll: true, onSuccess });
        }
    });
}
