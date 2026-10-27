import FormCheckbox from "@/Components/FormCheckbox";
import FormInput from "@/Components/FormInput";
import FormLabel from "@/Components/FormLabel";
import FormSelect from "@/Components/FormSelect";
import FormTextArea from "@/Components/FormTextArea";
import QuillEditor from "@/Components/QuillEditor";
import App from "@/Layouts/Instructor/App";
import { Link, router, usePage } from "@inertiajs/react";
import { useMemo, useState } from "react";
import { toast } from "react-toastify";

const TYPE_LABEL = {
    "pre-test": "Pre-Test",
    "post-test": "Post-Test",
    task: "Tugas",
    video: "Video",
    meeting: "Pertemuan",
    literature: "Literatur",
    h5p: "H5P Interaktif",
    scorm: "SCORM",
    questionnaire: "Kuesioner",
};

const typeBadgeClass = (type) =>
    ({
        literature: "bg-blue-100 text-blue-700",
        "pre-test": "bg-amber-100 text-amber-700",
        "post-test": "bg-amber-100 text-amber-700",
        task: "bg-purple-100 text-purple-700",
        meeting: "bg-teal-100 text-teal-700",
        video: "bg-indigo-100 text-indigo-700",
        h5p: "bg-emerald-100 text-emerald-700",
        scorm: "bg-rose-100 text-rose-700",
        questionnaire: "bg-cyan-100 text-cyan-700",
    }[type] || "bg-gray-100 text-gray-600");

const Import = ({ categories, employee_status, local_samples = [] }) => {
    const { errors } = usePage().props;

    const [step, setStep] = useState(1);
    const [files, setFiles] = useState([]);
    const [analyzing, setAnalyzing] = useState(false);
    const [uploadPct, setUploadPct] = useState(0);
    const [submitting, setSubmitting] = useState(false);
    const [result, setResult] = useState(null); // { groups, errors }
    const [selectedGroup, setSelectedGroup] = useState(0);

    const typeOptions = [
        { value: "unschedule", label: "Tidak Terjadwal" },
        { value: "schedule", label: "Terjadwal" },
    ];
    const statusOptions = [
        { value: "publish", label: "Publikasi" },
        { value: "draft", label: "Draft" },
    ];
    const typeCategories = categories.map((item) => ({
        value: item.id,
        label: item.title,
    }));

    const [values, setValues] = useState({
        type: "",
        category: "",
        title: "",
        short_description: "",
        description: "",
        thumbnail: null,
        teaser_video: "",
        expired: "",
        jam_pelajaran: "",
        status: "draft",
        employee_status: [],
    });

    const group = result?.groups?.[selectedGroup] ?? null;

    const summary = useMemo(() => {
        if (!group) return { total: 0, supported: 0, skipped: 0 };
        const supported = group.total_supported;
        return {
            total: group.total_activities,
            supported,
            skipped: group.total_activities - supported,
        };
    }, [group]);

    const handleChange = (e) => {
        const { id, type, value, files: f } = e.target;
        setValues((prev) => ({
            ...prev,
            [id]: type === "file" ? f[0] : value,
        }));
    };

    const onPickFiles = (e) => {
        setFiles(Array.from(e.target.files || []));
    };

    const analyze = async () => {
        if (files.length === 0) {
            toast.error("Pilih minimal satu file .mbz.");
            return;
        }
        const invalid = files.find((f) => !f.name.toLowerCase().endsWith(".mbz"));
        if (invalid) {
            toast.error(`"${invalid.name}" bukan file .mbz.`);
            return;
        }

        const formData = new FormData();
        files.forEach((f) => formData.append("files[]", f));

        setAnalyzing(true);
        setUploadPct(0);
        try {
            const { data } = await window.axios.post(
                "/instructor/course/import/analyze",
                formData,
                {
                    headers: { "Content-Type": "multipart/form-data" },
                    onUploadProgress: (e) => {
                        if (e.total) {
                            setUploadPct(Math.round((e.loaded / e.total) * 100));
                        }
                    },
                }
            );
            setResult(data);
            setSelectedGroup(0);
            if (!data.groups || data.groups.length === 0) {
                toast.error("Tidak ada kursus yang terbaca dari file tersebut.");
            } else {
                // Prefill judul dari nama kursus Moodle.
                setValues((prev) => ({
                    ...prev,
                    title: prev.title || cleanTitle(data.groups[0].fullname),
                }));
                setStep(2);
            }
            (data.errors || []).forEach((err) => toast.error(err));
        } catch (e) {
            const msg =
                e?.response?.data?.message || "Gagal menganalisis file .mbz.";
            toast.error(msg);
        } finally {
            setAnalyzing(false);
        }
    };

    const cleanTitle = (name) =>
        (name || "").replace(/\s*\[ARCHIVE\]\s*$/i, "").trim();

    // Jalur pintas testing: analisis dari file .mbz contoh di server (tanpa unggah).
    const analyzeLocal = async (key) => {
        setAnalyzing(true);
        setUploadPct(100);
        try {
            const { data } = await window.axios.post(
                "/instructor/course/import/analyze-local",
                { key }
            );
            setResult(data);
            setSelectedGroup(0);
            if (!data.groups || data.groups.length === 0) {
                toast.error("Tidak ada kursus yang terbaca dari file contoh.");
            } else {
                setValues((prev) => ({
                    ...prev,
                    title: prev.title || cleanTitle(data.groups[0].fullname),
                }));
                setStep(2);
            }
            (data.errors || []).forEach((err) => toast.error(err));
        } catch (e) {
            toast.error(e?.response?.data?.message || "Gagal memakai file contoh.");
        } finally {
            setAnalyzing(false);
        }
    };

    const goConfigure = () => {
        setValues((prev) => ({
            ...prev,
            title: prev.title || cleanTitle(group.fullname),
        }));
        setStep(3);
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        if (!group) return;

        const formData = new FormData();
        group.modules.forEach((m) => formData.append("tokens[]", m.token));
        formData.append("type", values.type);
        formData.append("category", values.category);
        values.employee_status.forEach((id) =>
            formData.append("employee_status[]", id)
        );
        formData.append("title", values.title);
        formData.append("short_description", values.short_description);
        formData.append("description", values.description);
        if (values.thumbnail) formData.append("thumbnail", values.thumbnail);
        formData.append("teaser_video", values.teaser_video || "");
        formData.append("expired", values.expired || "");
        formData.append("jam_pelajaran", values.jam_pelajaran);
        formData.append("status", values.status);

        router.post("/instructor/course/import", formData, {
            onStart: () => setSubmitting(true),
            onFinish: () => setSubmitting(false),
        });
    };

    const Stepper = () => (
        <div className="max-w-4xl mx-auto flex items-center justify-center gap-3 mb-8 text-sm">
            {[
                { n: 1, t: "Unggah" },
                { n: 2, t: "Tinjau" },
                { n: 3, t: "Konfigurasi" },
            ].map((s, i) => (
                <div key={s.n} className="flex items-center gap-3">
                    <div
                        className={`flex items-center gap-2 px-3 py-1.5 rounded-full font-bold ${
                            step === s.n
                                ? "bg-primary text-white"
                                : step > s.n
                                ? "bg-green-100 text-green-700"
                                : "bg-gray-100 text-gray-500"
                        }`}
                    >
                        <span className="w-5 h-5 rounded-full bg-white/30 flex items-center justify-center text-xs">
                            {s.n}
                        </span>
                        {s.t}
                    </div>
                    {i < 2 && <span className="text-gray-300">—</span>}
                </div>
            ))}
        </div>
    );

    return (
        <App>
            <Stepper />

            {/* STEP 1 — Upload */}
            {step === 1 && (
                <div className="max-w-4xl mx-auto bg-white rounded-[2rem] shadow-sm border border-gray-100 overflow-hidden mb-8">
                    <h4 className="p-6 sm:p-8 bg-gray-50/50 border-b border-gray-100 text-center text-xl font-extrabold text-gray-800">
                        Impor Kursus dari Moodle (.mbz)
                    </h4>
                    <div className="p-6 sm:p-10">
                        <p className="text-sm text-gray-600 mb-6">
                            Unggah satu atau beberapa file Moodle Backup (.mbz).
                            File parsial dari kursus yang sama (mis. per modul)
                            akan otomatis digabung menjadi satu kursus.
                        </p>

                        <label
                            htmlFor="mbz"
                            className="flex flex-col items-center justify-center w-full h-56 border-2 border-gray-300 border-dashed rounded-lg cursor-pointer bg-gray-50 hover:bg-gray-100"
                        >
                            <div className="flex flex-col items-center justify-center pt-5 pb-6 text-center px-4">
                                <svg className="w-8 h-8 mb-4 text-gray-500" fill="none" viewBox="0 0 20 16">
                                    <path stroke="currentColor" strokeWidth="2" d="M13 13h3a3 3 0 0 0 0-6h-.025A5.56 5.56 0 0 0 16 6.5 5.5 5.5 0 0 0 5.207 5.021C5.137 5.017 5.071 5 5 5a4 4 0 0 0 0 8h2.167M10 15V6m0 0L8 8m2-2 2 2" />
                                </svg>
                                <p className="mb-2 text-sm text-gray-500">
                                    <span className="font-semibold">Klik untuk unggah</span> atau seret & lepas
                                </p>
                                <p className="text-xs text-gray-500">File .mbz (bisa lebih dari satu)</p>
                            </div>
                            <input id="mbz" type="file" accept=".mbz" multiple onChange={onPickFiles} className="hidden" />
                        </label>

                        {files.length > 0 && (
                            <ul className="mt-4 space-y-2">
                                {files.map((f, i) => (
                                    <li key={i} className="flex items-center justify-between text-sm bg-gray-50 rounded-lg px-4 py-2">
                                        <span className="truncate text-gray-700">{f.name}</span>
                                        <span className="text-gray-400 ml-3 shrink-0">
                                            {(f.size / 1048576).toFixed(1)} MB
                                        </span>
                                    </li>
                                ))}
                            </ul>
                        )}

                        {analyzing && (
                            <div className="mt-6">
                                <div className="flex justify-between text-xs text-gray-500 mb-1">
                                    <span>
                                        {uploadPct < 100
                                            ? "Mengunggah file..."
                                            : "Menganalisis isi file..."}
                                    </span>
                                    <span>{uploadPct}%</span>
                                </div>
                                <div className="w-full h-2 bg-gray-100 rounded-full overflow-hidden">
                                    <div
                                        className={`h-full bg-primary transition-all duration-200 ${
                                            uploadPct >= 100 ? "animate-pulse" : ""
                                        }`}
                                        style={{ width: `${Math.max(uploadPct, 3)}%` }}
                                    />
                                </div>
                                {uploadPct >= 100 && (
                                    <p className="text-xs text-gray-400 mt-2">
                                        File besar bisa butuh beberapa detik untuk dibaca. Mohon tunggu.
                                    </p>
                                )}
                            </div>
                        )}

                        <div className="flex justify-end mt-8">
                            <button
                                type="button"
                                onClick={analyze}
                                disabled={analyzing || files.length === 0}
                                className="px-8 py-3 rounded-xl bg-primary text-white hover:bg-primary/90 font-bold shadow-sm transition-all disabled:opacity-50"
                            >
                                {analyzing
                                    ? uploadPct < 100
                                        ? `Mengunggah ${uploadPct}%`
                                        : "Menganalisis..."
                                    : "Analisis File"}
                            </button>
                        </div>

                        {/* Jalur pintas testing — pakai file contoh di server tanpa unggah */}
                        {local_samples.length > 0 && (
                            <div className="mt-6 pt-6 border-t border-dashed border-gray-200">
                                <p className="text-xs font-semibold text-gray-400 mb-2">
                                    PINTASAN TESTING (file contoh di server)
                                </p>
                                <div className="flex flex-wrap gap-3">
                                    {local_samples.map((sample) => (
                                        <button
                                            key={sample.key}
                                            type="button"
                                            onClick={() => analyzeLocal(sample.key)}
                                            disabled={analyzing}
                                            className="px-4 py-2 rounded-lg border border-gray-300 text-sm text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50"
                                        >
                                            {sample.label}
                                            {sample.files > 1 && (
                                                <span className="ml-2 text-xs text-gray-400">
                                                    {sample.files} file
                                                </span>
                                            )}
                                        </button>
                                    ))}
                                </div>
                                <p className="text-xs text-gray-400 mt-3">
                                    File besar disalin lalu dibaca langsung di server — paket
                                    full bisa butuh satu sampai beberapa menit.
                                </p>
                            </div>
                        )}
                    </div>
                </div>
            )}

            {/* STEP 2 — Review */}
            {step === 2 && group && (
                <div className="max-w-4xl mx-auto mb-8">
                    {result.groups.length > 1 && (
                        <div className="mb-6">
                            <FormSelect
                                title="group"
                                label="Pilih Kursus (terdeteksi beberapa kursus berbeda)"
                                value={String(selectedGroup)}
                                onChange={(e) => setSelectedGroup(Number(e.target.value))}
                                options={result.groups.map((g, i) => ({
                                    value: String(i),
                                    label: `${cleanTitle(g.fullname)} (${g.modules.length} file)`,
                                }))}
                            />
                        </div>
                    )}

                    <div className="bg-white rounded-[2rem] shadow-sm border border-gray-100 overflow-hidden">
                        <div className="p-6 sm:p-8 bg-gray-50/50 border-b border-gray-100">
                            <h4 className="text-xl font-extrabold text-gray-800">
                                {cleanTitle(group.fullname)}
                            </h4>
                            <p className="text-xs text-gray-500 mt-1">
                                ID kursus Moodle: {group.original_course_id || "-"} ·{" "}
                                {group.moodle_release} · {group.modules.length} file
                            </p>
                            <div className="flex gap-3 mt-4 text-sm">
                                <span className="px-3 py-1 rounded-full bg-green-100 text-green-700 font-semibold">
                                    {summary.supported} akan diimpor
                                </span>
                                <span className="px-3 py-1 rounded-full bg-gray-100 text-gray-600 font-semibold">
                                    {summary.skipped} dilewati
                                </span>
                            </div>
                        </div>

                        <div className="p-6 sm:p-8 space-y-6">
                            {group.modules.map((m, mi) => (
                                <div key={m.token}>
                                    <p className="text-sm font-bold text-gray-700 mb-2">
                                        [{m.module_label}]{" "}
                                        <span className="font-normal text-gray-400">
                                            — {m.original_name}
                                        </span>
                                    </p>
                                    <ul className="space-y-2">
                                        {m.activities.map((a, ai) => (
                                            <li
                                                key={ai}
                                                className={`flex items-start justify-between gap-3 rounded-lg px-4 py-3 border ${
                                                    a.supported
                                                        ? "border-gray-100 bg-white"
                                                        : "border-gray-100 bg-gray-50 opacity-70"
                                                }`}
                                            >
                                                <div className="min-w-0">
                                                    <p className="text-sm text-gray-800 truncate">
                                                        {a.title}
                                                    </p>
                                                    {a.note && (
                                                        <p className="text-xs text-gray-400 mt-0.5">
                                                            {a.note}
                                                        </p>
                                                    )}
                                                </div>
                                                <span
                                                    className={`shrink-0 text-xs px-2.5 py-1 rounded-full font-semibold ${
                                                        a.supported
                                                            ? typeBadgeClass(a.app_type)
                                                            : "bg-red-50 text-red-500"
                                                    }`}
                                                >
                                                    {a.supported
                                                        ? TYPE_LABEL[a.app_type] || a.app_type
                                                        : "Dilewati"}
                                                </span>
                                            </li>
                                        ))}
                                    </ul>
                                </div>
                            ))}
                        </div>
                    </div>

                    <div className="flex flex-col sm:flex-row justify-between gap-4 mt-8">
                        <button
                            type="button"
                            onClick={() => setStep(1)}
                            className="px-6 py-3 rounded-xl border border-gray-300 text-gray-700 bg-white hover:bg-gray-50 font-bold shadow-sm"
                        >
                            Kembali
                        </button>
                        <button
                            type="button"
                            onClick={goConfigure}
                            disabled={summary.supported === 0}
                            className="px-8 py-3 rounded-xl bg-primary text-white hover:bg-primary/90 font-bold shadow-sm disabled:opacity-50"
                        >
                            Lanjut Konfigurasi
                        </button>
                    </div>
                </div>
            )}

            {/* STEP 3 — Configure */}
            {step === 3 && group && (
                <form onSubmit={handleSubmit}>
                    <div className="max-w-4xl mx-auto bg-white rounded-[2rem] shadow-sm border border-gray-100 overflow-hidden mb-8">
                        <h4 className="p-6 sm:p-8 bg-gray-50/50 border-b border-gray-100 text-center text-xl font-extrabold text-gray-800">
                            Lengkapi Data Kursus
                        </h4>
                        <div className="p-6 sm:p-10">
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-x-6">
                                <FormSelect
                                    title="type"
                                    label="Tipe Kursus"
                                    value={values.type}
                                    onChange={handleChange}
                                    isRequired
                                    options={typeOptions}
                                    error={errors.type}
                                />
                                <FormSelect
                                    title="category"
                                    label="Kategori"
                                    value={values.category}
                                    onChange={handleChange}
                                    isRequired
                                    options={typeCategories}
                                    error={errors.category}
                                />
                            </div>

                            <div className="mb-4">
                                <p className="mb-3 text-sm font-bold text-gray-700">
                                    Status Pegawai <span className="text-red-500">*</span>
                                </p>
                                <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4 sm:gap-6">
                                    {employee_status.map((item) => (
                                        <FormCheckbox
                                            key={item.id}
                                            id={`employee-${item.id}`}
                                            title={item.title}
                                            checked={values.employee_status.includes(item.id)}
                                            onChange={(e) => {
                                                const checked = e.target.checked;
                                                setValues((prev) => ({
                                                    ...prev,
                                                    employee_status: checked
                                                        ? [...prev.employee_status, item.id]
                                                        : prev.employee_status.filter((id) => id !== item.id),
                                                }));
                                            }}
                                            error={errors.employee_status}
                                        />
                                    ))}
                                </div>
                            </div>

                            <FormInput
                                title="title"
                                label="Judul"
                                value={values.title}
                                onChange={handleChange}
                                isRequired
                                error={errors.title}
                            />

                            <FormTextArea
                                title="short_description"
                                label="Deskripsi Singkat"
                                value={values.short_description}
                                onChange={handleChange}
                                isRequired
                                error={errors.short_description}
                            />

                            <QuillEditor
                                title="description"
                                label="Deskripsi Lengkap"
                                value={values.description}
                                onChange={(content) =>
                                    setValues((prev) => ({ ...prev, description: content }))
                                }
                                isRequired
                                error={errors.description}
                            />

                            <FormInput
                                title="teaser_video"
                                label="Video Teaser"
                                value={values.teaser_video}
                                onChange={handleChange}
                                placeholder="Contoh: https://www.youtube.com/embed/a3ICNMQW7Ok"
                                error={errors.teaser_video}
                            />

                            <div className="grid grid-cols-1 md:grid-cols-2 gap-x-6">
                                {values.type === "unschedule" && (
                                    <FormInput
                                        title="expired"
                                        label="Kedaluwarsa (Jam)"
                                        value={values.expired}
                                        placeholder="Atur kedaluwarsa dalam jam"
                                        onChange={handleChange}
                                        isRequired
                                        error={errors.expired}
                                    />
                                )}
                                <FormInput
                                    title="jam_pelajaran"
                                    label="Jam Pelajaran"
                                    value={values.jam_pelajaran}
                                    placeholder="Atur jam pelajaran dalam jam"
                                    onChange={handleChange}
                                    isRequired
                                    error={errors.jam_pelajaran}
                                />
                            </div>

                            <div className="mb-3 mt-2">
                                <FormLabel title="Thumbnail" />
                                <div className="flex items-center justify-center w-full">
                                    <label
                                        htmlFor="thumbnail"
                                        className={`flex flex-col items-center justify-center w-full h-56 border-2 ${
                                            errors.thumbnail ? "border-red-500" : "border-gray-300"
                                        } border-dashed rounded-lg cursor-pointer bg-gray-50 hover:bg-gray-100`}
                                    >
                                        <div className="flex flex-col items-center justify-center pt-5 pb-6">
                                            <svg className="w-8 h-8 mb-4 text-gray-500" fill="none" viewBox="0 0 20 16">
                                                <path stroke="currentColor" strokeWidth="2" d="M13 13h3a3 3 0 0 0 0-6h-.025A5.56 5.56 0 0 0 16 6.5 5.5 5.5 0 0 0 5.207 5.021C5.137 5.017 5.071 5 5 5a4 4 0 0 0 0 8h2.167M10 15V6m0 0L8 8m2-2 2 2" />
                                            </svg>
                                            <p className="mb-2 text-sm text-gray-500">
                                                <span className="font-semibold">Klik untuk unggah</span> (opsional)
                                            </p>
                                            <p className="text-xs text-gray-500">PNG atau JPG (MAKS. 10MB)</p>
                                        </div>
                                        <input id="thumbnail" type="file" accept=".png,.jpg,.jpeg" onChange={handleChange} className="hidden" />
                                    </label>
                                </div>
                                {values.thumbnail && (
                                    <p className="mt-2 text-sm text-green-500">
                                        File dipilih: {values.thumbnail.name}
                                    </p>
                                )}
                                {errors.thumbnail && (
                                    <small className="text-red-500 text-sm mt-1">{errors.thumbnail}</small>
                                )}
                            </div>

                            <FormSelect
                                title="status"
                                label="Status"
                                value={values.status}
                                onChange={handleChange}
                                isRequired
                                options={statusOptions}
                                error={errors.status}
                            />

                            <div className="mt-4 rounded-xl bg-blue-50 border border-blue-100 p-4 text-sm text-blue-700">
                                {summary.supported} aktivitas akan dibuat dari{" "}
                                {group.modules.length} file. Konten dokumen (PDF)
                                diekstrak otomatis; kuis & tugas dibuat kosong untuk
                                Anda lengkapi setelah impor.
                            </div>
                        </div>
                    </div>

                    <div className="max-w-4xl mx-auto flex flex-col sm:flex-row justify-between gap-4 mt-8 pb-12">
                        <button
                            type="button"
                            onClick={() => setStep(2)}
                            className="px-6 py-3 rounded-xl border border-gray-300 text-gray-700 bg-white hover:bg-gray-50 font-bold shadow-sm"
                        >
                            Kembali
                        </button>
                        <button
                            type="submit"
                            disabled={submitting}
                            className="px-8 py-3 rounded-xl bg-primary text-white hover:bg-primary/90 font-bold shadow-sm disabled:opacity-50"
                        >
                            {submitting ? "Mengimpor..." : "Impor Kursus"}
                        </button>
                    </div>
                </form>
            )}
        </App>
    );
};

export default Import;
