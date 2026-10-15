import FormInput from '@/Components/FormInput';
import { Dialog, DialogPanel, DialogTitle } from '@headlessui/react';
import { useState } from 'react';
import '@/../css/admin-course-form.css';

export default function ModalAddSchedule({ isOpen, onClose, onScheduleAdded }) {
    const [values, setValues] = useState({ date: '', time_from: '', time_to: '', quota: '' });
    const handleChange = (event) => setValues((current) => ({ ...current, [event.target.id]: event.target.value }));
    const handleSubmit = (event) => {
        event.preventDefault();
        onScheduleAdded({ id: Date.now(), ...values });
        setValues({ date: '', time_from: '', time_to: '', quota: '' });
        onClose();
    };

    return <Dialog open={isOpen} onClose={onClose} className="relative z-50">
        <div className="fixed inset-0 bg-[rgba(0,20,30,0.5)] backdrop-blur-sm" aria-hidden="true" />
        <div className="fixed inset-0 overflow-y-auto p-4">
            <div className="flex min-h-full items-center justify-center">
                <DialogPanel className="course-form course-schedule-modal w-full max-w-lg overflow-hidden rounded-[18px] bg-white shadow-xl">
                    <header className="course-form-section-header">
                        <span className="course-form-section-icon"><i className="bx bx-calendar-plus" aria-hidden /></span>
                        <div className="flex-1"><DialogTitle as="h2">Tambah jadwal</DialogTitle><p>Atur tanggal, waktu, dan kuota peserta.</p></div>
                        <button type="button" onClick={onClose} aria-label="Tutup dialog" className="flex h-8 w-8 items-center justify-center rounded-lg bg-[#F0F4FF] text-lg text-[#526176] hover:bg-[#E0E8F5]"><i className="bx bx-x" aria-hidden /></button>
                    </header>
                    <form onSubmit={handleSubmit}>
                        <div className="course-form-section-body">
                            <FormInput id="date" label="Tanggal kegiatan" type="date" isRequired required value={values.date} onChange={handleChange} />
                            <div className="grid grid-cols-2 gap-4">
                                <FormInput id="time_from" label="Waktu mulai" type="time" isRequired required value={values.time_from} onChange={handleChange} />
                                <FormInput id="time_to" label="Waktu selesai" type="time" isRequired required min={values.time_from || undefined} value={values.time_to} onChange={handleChange} />
                            </div>
                            <FormInput id="quota" label="Kuota peserta" type="number" min="1" step="1" isRequired required value={values.quota} onChange={handleChange} placeholder="Contoh: 30" />
                        </div>
                        <div className="course-form-actions justify-end border-t border-[#E8ECF1] p-5">
                            <button type="button" className="course-form-button" onClick={onClose}>Batal</button>
                            <button type="submit" className="course-form-button is-primary"><i className="bx bx-plus" aria-hidden />Tambah jadwal</button>
                        </div>
                    </form>
                </DialogPanel>
            </div>
        </div>
    </Dialog>;
}
