import Swal from 'sweetalert2';
import { router } from '@inertiajs/react';

export function confirmDelete(url, { message = 'Hapus data ini?', onSuccess } = {}) {
    return Swal.fire({
        title: 'Hapus data?',
        text: message,
        icon: 'warning',
        showCancelButton: true,
        reverseButtons: true,
        focusCancel: true,
        confirmButtonColor: '#A43B2F',
        cancelButtonColor: '#52615D',
        confirmButtonText: 'Hapus',
        cancelButtonText: 'Batalkan',
        customClass: {
            popup: 'admin-confirm-dialog',
            confirmButton: 'admin-confirm-delete',
            cancelButton: 'admin-confirm-cancel',
        },
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(url, { preserveScroll: true, onSuccess });
        }
    });
}
