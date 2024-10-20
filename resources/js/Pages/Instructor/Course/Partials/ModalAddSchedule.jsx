import FormInput from '@/Components/FormInput';
import FormLabel from '@/Components/FormLabel';
import { Modal } from 'flowbite';
import { useEffect, useRef, useState } from 'react';

const ModalAddSchedule = ({ isOpen, onClose, onScheduleAdded }) => {
    const modalRef = useRef(null);

    const [values, setValues] = useState({
        date: "",
        time_from: "",
        time_to: "",
        quota: "",
    })

    const handleChange = (e) => {
        const key = e.target.id;
        const value = e.target.value
        setValues(values => ({
            ...values,
            [key]: value,
        }))
    }

    const handleSubmit = (e) => {
        e.preventDefault(); // Mencegah reload halaman
        console.log(values)

        // Ambil data yang sudah ada dari localStorage
        const existingData = JSON.parse(localStorage.getItem('scheduleData')) || [];

        // Tambahkan data baru
        const newSchedule = {
            id: Date.now(),
            date: values.date,
            time_from: values.time_from,
            time_to: values.time_to,
            quota: values.quota,
        };

        // Tampilkan data yang ada dan data baru untuk debugging
        console.log("Existing Data:", existingData);
        console.log("New Schedule:", newSchedule);

        // Simpan data baru ke array
        existingData.push(newSchedule);

        // Simpan kembali ke localStorage
        localStorage.setItem('scheduleData', JSON.stringify(existingData));

        onScheduleAdded(newSchedule);
        // // Misalkan ingin menutup modal setelah submit
        onClose();
    };

    useEffect(() => {
        const $targetEl = modalRef.current;
        const options = {
            placement: 'center-center',
            backdrop: 'dynamic',
            backdropClasses: 'bg-gray-900/50 dark:bg-gray-900/80 fixed inset-0 z-40',
            closable: true,
            onHide: () => {
                console.log('modal is hidden');
            },
            onShow: () => {
                console.log('modal is shown');
            },
        };

        const modal = new Modal($targetEl, options);

        if (isOpen) {
            modal.show();
        } else {
            modal.hide();
        }

        return () => {
            modal.hide();
        };

    }, [isOpen, onClose]);

    return (
        <div
            id="modalEl"
            ref={modalRef}
            tabIndex="-1"
            aria-hidden="true"
            className={`fixed inset-0 z-50 overflow-y-auto overflow-x-hidden p-4 ${!isOpen ? 'hidden' : ''}`}
        >
            <div className="flex w-full items-center justify-center h-full">
                <div className="relative max-h-full w-full max-w-2xl">
                    <div className="relative rounded-lg bg-white shadow">
                        <div className="flex items-start justify-between rounded-t border-b p-5">
                            <h4 className="text-lg font-semibold text-gray-900 flex items-center">
                                Set Schedules
                            </h4>
                            <button
                                type="button"
                                onClick={onClose}
                                className="ms-auto inline-flex h-8 w-8 items-center justify-center rounded-lg bg-transparent text-sm text-gray-400 hover:bg-gray-200 hover:text-gray-900"
                            >
                                <svg
                                    className="h-3 w-3"
                                    aria-hidden="true"
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 14 14"
                                >
                                    <path
                                        stroke="currentColor"
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                        strokeWidth="2"
                                        d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"
                                    />
                                </svg>
                                <span className="sr-only">Close modal</span>
                            </button>
                        </div>
                        <div className="space-y-6 p-6">
                            <p className="text-base leading-relaxed text-gray-500">
                                <form onSubmit={handleSubmit}>
                                    {/* date */}
                                    <div className="mb-3">
                                        <FormLabel title={'date'} />
                                        <input
                                            type="date"
                                            id="date"
                                            className='form-control'
                                            value={values.date}
                                            onChange={handleChange}
                                        />
                                    </div>
                                    {/* <TimePicker /> */}
                                    <div className='flex gap-5 w-full'>
                                        <div className="w-full">
                                            <FormInput placeholder={'09:00'} title={'time from'} value={values.time_from} onChange={handleChange} />
                                        </div>
                                        <div className="w-full">
                                            <FormInput placeholder={'11:00'} title={'time to'} value={values.time_to} onChange={handleChange} />
                                        </div>
                                    </div>
                                    {/* quota */}
                                    <FormInput title={'quota'} value={values.quota} onChange={handleChange} isRequired={true} />
                                    <button type='submit' className='button-primary'>Add Schedule</button>
                                </form>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>


    );
};

export default ModalAddSchedule;
