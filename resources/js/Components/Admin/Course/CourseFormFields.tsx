import { CrudFormSection } from '@/Components/Admin/CrudPrimitives';
import FormInput from '@/Components/FormInput';
import FormSelect from '@/Components/FormSelect';
import FormTextArea from '@/Components/FormTextArea';
import FormLabel from '@/Components/FormLabel';
import FormFile from '@/Components/FormFile';
import { checkboxGroupErrorClass } from '@/Components/FormCheckboxGroup';
import ErrorMessage from '@/Components/ErrorMessage';
import useTransientFieldError from '@/Components/useTransientFieldError';
import { useEffect } from 'react';
import ReactQuill from 'react-quill-new';
import 'react-quill-new/dist/quill.snow.css';
import '@/../css/admin-course-form.css';

export default function CourseFormFields({ values, setValues, handleChange, errors, categories, employeeStatuses, trainers, opds = [], isTrainerAccount, currentTrainer = null, thumbnailPreview, editing = false, typeLocked = false, hasSchedules = true }: {
    values: any; setValues: any; handleChange: any; errors: any; categories: any[]; employeeStatuses: any[]; trainers: any[]; opds?: any[];
    isTrainerAccount: boolean; currentTrainer?: any; thumbnailPreview: string | null; editing?: boolean; typeLocked?: boolean; hasSchedules?: boolean;
}) {
    const selectedTrainers = values.trainers.map((id) => trainers.find((trainer) => String(trainer.id) === String(id)) ?? { id, name: 'Pemateri tidak tersedia', organization_name: null });
    const selectedTrainerOpds = selectedTrainers.flatMap((trainer) => trainer.organization_options || []);
    const eligibleTrainerOpds = isTrainerAccount && selectedTrainerOpds.length === 0
        ? (currentTrainer?.organization_options || [])
        : selectedTrainerOpds;
    const eligibleOpdIds = new Set(eligibleTrainerOpds.map((opd) => String(opd.id)));
    const targetOpds = opds.filter((opd) => eligibleOpdIds.has(String(opd.id)));
    const targetOpdKey = [...eligibleOpdIds].sort().join(',');

    useEffect(() => {
        if (values.visibility !== 'private') return;
        setValues((current) => {
            const nextOpdIds = current.opd_ids.filter((id) => eligibleOpdIds.has(String(id)));
            return nextOpdIds.length === current.opd_ids.length ? current : { ...current, opd_ids: nextOpdIds };
        });
    }, [targetOpdKey, values.visibility]);
    const availableTrainers = trainers.filter((trainer) => !values.trainers.some((id) => String(id) === String(trainer.id)));
    const addTrainer = (event) => {
        const trainerId = event.target.value;
        if (!trainerId) return;
        setValues((current) => ({
            ...current,
            trainers: current.trainers.some((id) => String(id) === String(trainerId))
                ? current.trainers
                : [...current.trainers, trainerId],
        }));
    };
    const removeTrainer = (trainerId) => setValues((current) => {
        const nextTrainers = current.trainers.filter((id) => String(id) !== String(trainerId));
        return { ...current, trainers: nextTrainers };
    });
    const selectedStatuses = new Set((values.employee_status || []).map(String));
    const statusOptions = employeeStatuses.map((status) => ({ id: status?.id ?? status, title: status?.title ?? String(status) }));
    const { containerRef: descriptionRef, visibleError: descriptionError, dismissError: dismissDescriptionError } = useTransientFieldError(errors.description);
    const { containerRef: statusRef, visibleError: statusError, dismissError: dismissStatusError } = useTransientFieldError(errors.employee_status);
    return <div className="course-form-grid">
        <div className="course-form-main">
            <CrudFormSection title="Informasi kelas" description="Bantu peserta mengenal tujuan dan manfaat kelas." icon="bx-book-open">
                <div className="course-form-fields-grid">
                    <FormInput id="title" label="Judul kelas" isRequired value={values.title} onChange={handleChange} error={errors.title} placeholder="Contoh: Dasar Manajemen Risiko" />
                    <FormSelect id="category" label="Kategori" isRequired value={values.category} onChange={handleChange} error={errors.category} disabled={editing} options={categories.map((c) => ({ value: c.id, label: c.title }))} emptyOption="Pilih kategori" small={editing ? 'Kategori ditetapkan saat kelas dibuat.' : null} />
                </div>
                <FormTextArea id="short_description" label="Deskripsi singkat" isRequired value={values.short_description} onChange={handleChange} error={errors.short_description} rows={3} placeholder="Ringkas manfaat dan materi utama kelas dalam beberapa kalimat." />
                <div ref={descriptionRef}>
                    <FormLabel title="Deskripsi lengkap" id="description" isRequired />
                    <div className={`course-description-field ${descriptionError ? 'is-invalid' : ''}`} aria-invalid={Boolean(descriptionError)}>
                        <ReactQuill theme="snow" value={values.description} onChange={(description) => { dismissDescriptionError(); setValues((current) => ({ ...current, description })); }} placeholder="Jelaskan tujuan, materi, metode pembelajaran, dan hasil yang diharapkan." />
                    </div>
                    <ErrorMessage message={descriptionError} />
                </div>
            </CrudFormSection>
            <CrudFormSection title="Media kelas" description="Tambahkan sampul dan video pengantar untuk peserta." icon="bx-image-alt">
                <div className="course-form-fields-grid course-form-media-grid">
                    <FormFile title="thumbnail" label="Gambar sampul" isRequired={!editing} error={errors.thumbnail} previewUrl={thumbnailPreview} hasFile={Boolean(values.thumbnail)} previewBadge={values.thumbnail ? 'Pratinjau baru' : 'Sampul saat ini'} heading={values.thumbnail ? 'Sampul baru siap disimpan' : thumbnailPreview ? 'Sampul kelas saat ini' : 'Belum ada sampul'} helperText="Gambar lanskap 16:9 tampil lebih rapi pada daftar dan detail kelas. PNG atau JPG, maksimal 10 MB." buttonLabel={thumbnailPreview ? 'Ganti sampul' : 'Pilih sampul'} selectedFileName={values.thumbnail?.name} variant="course" accept="image/png,image/jpeg" onChange={handleChange} />
                    <div className="course-media-video">
                        <div className="course-media-video-intro">
                            <span className="course-media-video-icon"><i className="bx bx-play-circle" aria-hidden /></span>
                            <div><strong>Video pengantar</strong><p>Berikan gambaran singkat sebelum peserta memulai kelas.</p></div>
                        </div>
                        <FormInput id="teaser_video" label="Tautan video" type="url" value={values.teaser_video} onChange={handleChange} error={errors.teaser_video} placeholder="https://www.youtube.com/embed/VIDEO_ID" small="Opsional. Gunakan tautan video yang dapat diakses peserta." />
                        {/^https?:\/\//i.test(values.teaser_video || '') ? <a className="course-media-video-link" href={values.teaser_video} target="_blank" rel="noopener noreferrer"><i className="bx bx-link-external" aria-hidden />Buka tautan video</a> : <div className="course-media-video-empty"><i className="bx bx-info-circle" aria-hidden />Tautan video dapat ditambahkan kapan saja.</div>}
                    </div>
                </div>
            </CrudFormSection>
            <CrudFormSection title="Pengaturan kelas" description="Tentukan tipe, durasi, dan publikasi." icon="bx-slider-alt">
                <FormSelect id="visibility" label="Visibilitas kelas" isRequired value={values.visibility} onChange={handleChange} error={errors.visibility} options={[{ value: 'public', label: 'Publik — semua OPD' }, { value: 'private', label: 'Privat — OPD tertentu' }]} small="Informasi kelas publik dapat dilihat tanpa login. Mengikuti kegiatan tetap memerlukan login dan status pegawai yang sesuai." />
                {values.visibility === 'private' && <fieldset className="course-opd-picker">
                    <legend className="form-label">OPD sasaran <span aria-hidden="true">*</span></legend>
                    {targetOpds.length > 0 ? <>
                        <div className="course-opd-picker-grid" role="group" aria-label="Pilih OPD sasaran kelas">
                            {targetOpds.map((opd) => {
                                const checked = values.opd_ids.some((id) => String(id) === String(opd.id));
                                const disabled = opd.is_active === false && !checked;
                                return <label key={opd.id} className="course-opd-option">
                                    <input type="checkbox" checked={checked} disabled={disabled} onChange={(event) => setValues((current) => ({ ...current, opd_ids: event.target.checked ? [...current.opd_ids, String(opd.id)] : current.opd_ids.filter((id) => String(id) !== String(opd.id)) }))} />
                                    <span>{opd.name}</span>
                                </label>;
                            })}
                        </div>
                    </> : <p className="course-opd-picker-empty">Pilih pemateri dengan OPD aktif untuk menampilkan pilihan sasaran.</p>}
                    <ErrorMessage message={errors.opd_ids || Object.entries(errors).find(([key]) => key.startsWith('opd_ids.'))?.[1] as string} />
                </fieldset>}
                <div className="course-form-fields-grid course-form-settings-grid">
                <FormSelect id="type" label="Tipe kelas" isRequired value={values.type} onChange={handleChange} error={errors.type} disabled={typeLocked} options={[{ value: 'unschedule', label: 'Mandiri' }, { value: 'schedule', label: 'Terjadwal' }]} emptyOption="Pilih tipe kelas" small={typeLocked ? 'Tipe kelas ditetapkan saat kelas dibuat.' : values.type === 'schedule' ? 'Peserta mengikuti kelas sesuai jadwal kegiatan.' : values.type === 'unschedule' ? 'Peserta belajar sesuai waktu masing-masing.' : null} />
                <FormInput id="jam_pelajaran" label="Jam pelajaran (JP)" type="number" min="0" isRequired value={values.jam_pelajaran} onChange={handleChange} error={errors.jam_pelajaran} placeholder="Contoh: 8" />
                {values.type !== 'schedule' && <FormInput id="expired" label="Masa akses (jam)" type="number" min="0" isRequired value={values.expired} onChange={handleChange} error={errors.expired} placeholder="Contoh: 72" small="Dihitung sejak peserta memulai kelas." />}
                </div>
                <div className="course-form-divider">
                    <FormSelect id="status" label="Status publikasi" isRequired value={values.status} onChange={handleChange} error={errors.status} emptyOption="Pilih status">
                        <option value="draft">Draft</option>
                        <option value="publish" disabled={values.type === 'schedule' && (!editing || !hasSchedules)}>Publikasi</option>
                    </FormSelect>
                    <p className="course-form-status-note"><i className={`bx ${values.status === 'publish' ? 'bx-show' : 'bx-file'}`} aria-hidden />{values.status === 'publish' ? 'Kelas dipublikasikan sesuai visibilitas, OPD sasaran, dan status pegawai.' : values.type === 'schedule' && (!editing || !hasSchedules) ? 'Tambahkan jadwal terlebih dahulu sebelum memublikasikan kelas.' : 'Simpan sebagai draft untuk melanjutkan penyusunan kelas.'}</p>
                </div>
            </CrudFormSection>
            <CrudFormSection title="Peserta & pemateri" description="Pilih status peserta dan pemateri pengampu kelas." icon="bx-group">
                <div ref={statusRef} className={`course-employee-status ${statusError ? 'has-error' : ''}`} role="group" aria-labelledby="employee-status-title" aria-describedby="employee-status-help">
                    <div className="course-employee-status-header">
                        <div>
                            <h3 id="employee-status-title" className="course-employee-status-title">Status pegawai <span aria-hidden="true">*</span></h3>
                            <p id="employee-status-help">Pilih status pegawai yang boleh mengikuti kelas ini. Minimal satu status.</p>
                        </div>
                        <span className="course-employee-status-count" aria-live="polite">{selectedStatuses.size} dari {statusOptions.length} dipilih</span>
                    </div>
                    {statusOptions.length > 0 ? <>
                        <div className="course-employee-status-actions">
                            <button type="button" onClick={() => { dismissStatusError(); setValues((current) => ({ ...current, employee_status: statusOptions.map((status) => String(status.id)) })); }} disabled={selectedStatuses.size === statusOptions.length}>Pilih semua</button>
                            <button type="button" onClick={() => { dismissStatusError(); setValues((current) => ({ ...current, employee_status: [] })); }} disabled={selectedStatuses.size === 0}>Hapus pilihan</button>
                        </div>
                        <div className="course-employee-status-options">
                            {statusOptions.map((status) => {
                                const checked = selectedStatuses.has(String(status.id));
                                return <label key={status.id} className={`course-employee-status-option ${checked ? 'is-selected' : ''}`}>
                                    <input type="checkbox" id={`employee_status_${status.id}`} name="employee_status" value={status.id} checked={checked} onChange={(event) => { dismissStatusError(); handleChange(event); }} />
                                    <span className="course-employee-status-option-label">{status.title}</span>
                                    <i className={`bx ${checked ? 'bx-check-circle' : 'bx-circle'}`} aria-hidden="true" />
                                </label>;
                            })}
                        </div>
                    </> : <p className="course-employee-status-empty">Belum ada status pegawai. Tambahkan status di Master Status Pegawai.</p>}
                    {statusError && <p role="alert" className={checkboxGroupErrorClass}><i className="bx bx-error-circle text-base" aria-hidden />{statusError}</p>}
                </div>
                {isTrainerAccount && <p className="course-form-divider text-sm text-steel">Anda otomatis menjadi pemateri kelas ini. OPD afiliasi Anda tidak membatasi OPD sasaran peserta.</p>}
                {!isTrainerAccount && <div className="course-form-divider course-trainer-picker">
                    <div className="course-trainer-picker-heading">
                        <div>
                            <h3>Pemateri <span aria-hidden="true">*</span></h3>
                            <p>Pilih pemateri. OPD sasaran kelas privat dibatasi pada OPD aktif pemateri yang dipilih.</p>
                        </div>
                        <span>{selectedTrainers.length} dipilih</span>
                    </div>
                    <FormSelect id="trainer_select" label="Tambahkan pemateri" value="" onChange={addTrainer} error={errors.trainers} emptyOption={availableTrainers.length ? 'Pilih pemateri' : 'Tidak ada pemateri lain yang tersedia'} disabled={availableTrainers.length === 0} wrapperClassName="course-trainer-select">
                        {trainers.map((trainer) => {
                            const alreadySelected = values.trainers.some((id) => String(id) === String(trainer.id));
                            const trainerOpdIds = trainer.organization_ids || [trainer.organization_id];
                            return <option key={trainer.id} value={trainer.id} disabled={alreadySelected || !trainerOpdIds.length}>
                                {trainer.name} — {(trainer.organization_options || []).map((opd) => opd.name).join(', ') || 'OPD belum tersedia'}{alreadySelected ? ' (dipilih)' : ''}
                            </option>;
                        })}
                    </FormSelect>
                    {selectedTrainers.length > 0 ? <ul className="course-trainer-selected" aria-label="Pemateri terpilih">
                        {selectedTrainers.map((trainer) => <li key={trainer.id}>
                            <span className="course-trainer-selected-avatar" aria-hidden="true">{String(trainer.name || '?').charAt(0).toUpperCase()}</span>
                            <span className="course-trainer-selected-text"><strong>{trainer.name}</strong><small>{(trainer.organization_options || []).map((opd) => opd.name).join(', ') || trainer.organization_name || 'OPD belum tersedia'}</small></span>
                            <button type="button" onClick={() => removeTrainer(trainer.id)} aria-label={`Hapus ${trainer.name} dari kelas`} title="Hapus pemateri"><i className="bx bx-x" aria-hidden="true" /></button>
                        </li>)}
                    </ul> : <p className="course-trainer-empty">Belum ada pemateri dipilih.</p>}
                    {trainers.length === 0 && <p className="course-trainer-empty">Tambahkan pemateri dengan OPD aktif di Master Pemateri.</p>}
                </div>}
            </CrudFormSection>
        </div>
    </div>;
}
