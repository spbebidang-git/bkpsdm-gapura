import FormCheckbox from "@/Components/FormCheckbox";
import FormInput from "@/Components/FormInput";
import FormLabel from "@/Components/FormLabel";
import FormSelect from "@/Components/FormSelect";
import FormTextArea from "@/Components/FormTextArea";
import QuillEditor from "@/Components/QuillEditor";
import App from "@/Layouts/Instructor/App";
import { Link, router, usePage } from "@inertiajs/react";
import { useEffect, useState } from "react";
import { toast } from "react-toastify";
import ModalAddSchedule from "./Partials/ModalAddSchedule";

const Edit = ({ course, categories, employee_status, selected_employee_status, schedules = [] }) => {
    const [type, setType] = useState(course.type === "schedule" ? 1 : 0);
    const [isModalOpen, setIsModalOpen] = useState(false);
    const [data, setData] = useState(schedules);
    const { errors } = usePage().props;

    const toggleModal = () => setIsModalOpen(!isModalOpen);

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
        type: course.type || "",
        category: course.category_id || "",
        title: course.title || "",
        short_description: course.short_description || "",
        description: course.description || "",
        thumbnail: null,
        teaser_video: course.teaser_video || "",
        expired: course.expired || "",
        jam_pelajaran: course.jam_pelajaran || "",
        status: course.status || "",
        employee_status: selected_employee_status || [],
    });

    const handleChange = (e) => {
        const { id, type, value, checked, files } = e.target;

        setValues((prev) => ({
            ...prev,
            [id]:
                type === "checkbox"
                    ? checked
                    : type === "file"
                        ? files[0]
                        : value,
        }));

        if (id === "type") {
            setType(value === "schedule" ? 1 : 0);
        }
    };

    const handleSubmit = (e) => {
        e.preventDefault();

        const formData = new FormData();
        formData.append("_method", "put");
        formData.append("type", values.type);
        formData.append("category", values.category);

        values.employee_status.forEach((id) => {
            formData.append("employee_status[]", id);
        });

        formData.append("title", values.title);
        formData.append("short_description", values.short_description);
        formData.append("description", values.description);

        if (values.thumbnail) {
            formData.append("thumbnail", values.thumbnail);
        }

        formData.append("teaser_video", values.teaser_video);
        formData.append("expired", values.expired);
        formData.append("jam_pelajaran", values.jam_pelajaran);
        formData.append("status", values.status);

        // Selalu kirim jadwal dari state — localStorage dulu bisa nempel jadwal course lain
        if (values.type === "schedule" && data.length > 0) {
            formData.append("schedules", JSON.stringify(data));
        }

        router.post(`/instructor/course/${course.slug}`, formData);
    };

    useEffect(() => {
        if (errors.schedules) {
            toast.error("Jadwal tidak boleh kosong.", {
                position: "top-right",
                autoClose: 3000,
            });
        }
    }, [errors]);

    const handleScheduleAdded = (newSchedule) => {
        setData((prev) => [...prev, newSchedule]);
    };

    const handleDeleteSchedule = (id) => {
        setData((prev) => prev.filter((item) => item.id !== id));
    };

    const isTypeLocked = Boolean(course.type);

    return (
        <App>
            <form onSubmit={handleSubmit}>
                <div className="max-w-4xl mx-auto bg-white rounded-[2rem] shadow-sm border border-gray-100 overflow-hidden mb-8">
                    <h4 className="p-6 sm:p-8 bg-gray-50/50 border-b border-gray-100 text-center text-xl font-extrabold text-gray-800">
                        Edit Kursus
                    </h4>

                    <div className="p-6 sm:p-10">
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-x-6">
                            <FormSelect
                                title="type"
                                label="Tipe Kursus"
                                value={values.type}
                                onChange={handleChange}
                                options={typeOptions}
                                isRequired={true}
                                error={errors.type}
                                disabled={isTypeLocked}
                            />


                            <FormSelect
                                title="category"
                                label="Kategori"
                                value={values.category}
                                onChange={handleChange}
                                options={typeCategories}
                                isRequired={true}
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
                            isRequired={true}
                            error={errors.title}
                        />

                        <FormTextArea
                            title="short_description"
                            label="Deskripsi Singkat"
                            value={values.short_description}
                            onChange={handleChange}
                            isRequired={true}
                            error={errors.short_description}
                        />

                        <QuillEditor
                            title="description"
                            label="Deskripsi Lengkap"
                            value={values.description}
                            onChange={(content) => {
                                setValues((prev) => ({
                                    ...prev,
                                    description: content,
                                }));
                            }}
                            isRequired={true}
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
                            {!type ? (
                                <FormInput
                                    title="expired"
                                    label="Kedaluwarsa (Jam)"
                                    value={values.expired}
                                    onChange={handleChange}
                                    placeholder="Atur kedaluwarsa dalam jam"
                                    isRequired={true}
                                    error={errors.expired}
                                />
                            ) : null}

                            <FormInput
                                title="jam_pelajaran"
                                label="Jam Pelajaran"
                                value={values.jam_pelajaran}
                                onChange={handleChange}
                                placeholder="Atur jam pelajaran dalam jam"
                                isRequired={true}
                                error={errors.jam_pelajaran}
                            />
                        </div>

                        <div className="mb-3 mt-2">
                            <FormLabel title="Thumbnail" isRequired={false} />

                            {course.thumbnail_url && (
                                <div className="mb-2">
                                    <img
                                        src={course.thumbnail_url}
                                        className="h-32 rounded"
                                    />
                                </div>
                            )}

                            <div className="flex items-center justify-center w-full">
                                <label
                                    htmlFor="thumbnail"
                                    className={`flex flex-col items-center justify-center w-full h-64 border-2 ${errors.thumbnail ? "border-red-500" : "border-gray-300"
                                        } border-dashed rounded-lg cursor-pointer bg-gray-50 hover:bg-gray-100`}
                                >
                                    <div className="flex flex-col items-center justify-center pt-5 pb-6">
                                        <svg
                                            className="w-8 h-8 mb-4 text-gray-500"
                                            fill="none"
                                            viewBox="0 0 20 16"
                                        >
                                            <path
                                                stroke="currentColor"
                                                strokeWidth="2"
                                                d="M13 13h3a3 3 0 0 0 0-6h-.025A5.56 5.56 0 0 0 16 6.5 5.5 5.5 0 0 0 5.207 5.021C5.137 5.017 5.071 5 5 5a4 4 0 0 0 0 8h2.167M10 15V6m0 0L8 8m2-2 2 2"
                                            />
                                        </svg>
                                        <p className="mb-2 text-sm text-gray-500">
                                            <span className="font-semibold">Klik untuk unggah</span>{" "}
                                            atau seret dan lepaskan
                                        </p>
                                        <p className="text-xs text-gray-500">
                                            PNG atau JPG (MAKS. 1MB)
                                        </p>
                                    </div>
                                    <input
                                        id="thumbnail"
                                        type="file"
                                        onChange={handleChange}
                                        className="hidden"
                                    />
                                </label>
                            </div>

                            {values.thumbnail && (
                                <p className="mt-2 text-sm text-green-500">
                                    File dipilih: {values.thumbnail.name}
                                </p>
                            )}

                            {errors.thumbnail && (
                                <small className="text-red-500 text-sm mt-1">
                                    {errors.thumbnail}
                                </small>
                            )}
                        </div>

                        <FormSelect
                            title="status"
                            label="Status"
                            value={values.status}
                            onChange={handleChange}
                            options={statusOptions}
                            isRequired={true}
                            error={errors.status}
                        />
                    </div>
                </div>

                {type ? (
                    <div className="max-w-4xl mx-auto bg-white rounded-[2rem] shadow-sm border border-gray-100 overflow-hidden mb-8">
                        <div className="flex flex-col sm:flex-row justify-between border-b border-gray-100 bg-gray-50/50 p-6 sm:p-8 items-center gap-4">
                            <h4 className="text-lg font-extrabold text-gray-800 w-full sm:w-auto text-center sm:text-left">Atur Jadwal Kegiatan</h4>

                            <button
                                type="button"
                                className="button-primary w-full sm:w-auto"
                                onClick={toggleModal}
                            >
                                Tambah Jadwal
                            </button>
                        </div>

                        <div className="p-0 overflow-x-auto">
                            <table className="w-full text-sm text-center">
                                <thead className="text-xs text-gray-700 uppercase bg-gray-50 border-b border-gray-100">
                                    <tr>
                                        <th className="px-6 py-3 text-center">#</th>
                                        <th className="px-6 py-3 text-center">Tanggal</th>
                                        <th className="px-6 py-3 text-center">Rentang Waktu</th>
                                        <th className="px-6 py-3 text-center">Kuota</th>
                                        <th className="px-6 py-3 text-center">Aksi</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    {data.length > 0 ? (
                                        data.map((item, index) => (
                                            <tr key={index} className="border-b">
                                                <td className="px-6 py-4 text-center">{index + 1}</td>
                                                <td className="px-6 py-4 text-center">{item.date}</td>
                                                <td className="px-6 py-4 text-center">
                                                    {item.time_from} - {item.time_to}
                                                </td>
                                                <td className="px-6 py-4 text-center">{item.quota}</td>

                                                <td className="px-6 py-4 text-center">
                                                    <button
                                                        type="button"
                                                        className="text-red-600 hover:text-red-900"
                                                        onClick={() => handleDeleteSchedule(item.id)}
                                                    >
                                                        Hapus
                                                    </button>
                                                </td>
                                            </tr>
                                        ))
                                    ) : (
                                        <tr>
                                            <td colSpan="100%" className="text-center py-4">
                                                Tidak ada data
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>
                    </div>
                ) : null}

                <div className="max-w-4xl mx-auto flex flex-col sm:flex-row justify-end gap-4 mt-8 pb-12">
                    <Link href={`/instructor`} className="w-full sm:w-auto">
                        <button type="button" className="w-full sm:w-auto px-6 py-3 rounded-xl border border-gray-300 text-gray-700 bg-white hover:bg-gray-50 hover:text-gray-900 font-bold transition-all shadow-sm">
                            Kembali
                        </button>
                    </Link>
                    <button type="submit" className="w-full sm:w-auto px-8 py-3 rounded-xl bg-primary text-white hover:bg-primary/90 font-bold shadow-sm hover:shadow transition-all hover:-translate-y-0.5">
                        Simpan
                    </button>
                </div>
            </form>

            <ModalAddSchedule
                isOpen={isModalOpen}
                onClose={toggleModal}
                onScheduleAdded={handleScheduleAdded}
            />
        </App>
    );
};

export default Edit;
