import { Link, usePage } from "@inertiajs/react";
import { useState } from "react";
import { toast } from "react-toastify";

const Index = ({ course, progress, initial_activity }) => {
    const { site } = usePage().props;
    const [rating, setRating] = useState(0);
    const [description, setDescription] = useState("");

    const handleRating = (index) => {
        setRating(index); // Mengubah nilai rating saat bintang diklik
    };

    // const handleSubmit = (e) => {
    //     e.preventDefault();

    //     router.post(
    //         route("participant.rating.store", [course.slug, 1]),
    //         {
    //             rating,
    //             description,
    //         },
    //         {
    //             onSuccess: (page) => {
    //                 toast.success("Data Saved Successfully.", {
    //                     position: "top-center",
    //                     autoClose: 5000,
    //                     hideProgressBar: false,
    //                     closeOnClick: true,
    //                     pauseOnHover: true,
    //                     draggable: true,
    //                     progress: undefined,
    //                     theme: "light",
    //                 });
    //             },
    //             onError: (errors) => {
    //                 toast.error(errors.message || "Gagal menyimpan data.", {
    //                     position: "top-center",
    //                     autoClose: 5000,
    //                     hideProgressBar: false,
    //                     closeOnClick: true,
    //                     pauseOnHover: true,
    //                     draggable: true,
    //                     progress: undefined,
    //                     theme: "colored",
    //                 });
    //             },
    //         }
    //     );
    // };

    const handleSubmit = async (e) => {
        e.preventDefault();

        try {
            const response = await axios.post(
                route("participant.rating.store", [course.slug, 1]),
                {
                    rating,
                    description,
                }
            );

            toast.success("Data Saved Successfully.", {
                position: "top-center",
                autoClose: 5000,
                theme: "light",
            });

            // Redirect manual jika perlu
            window.location.href = route("participant.survey.index", [
                course.slug,
                1,
            ]);
        } catch (error) {
            const message =
                error.response?.data?.message || "Gagal menyimpan data.";
            toast.error(message, {
                position: "top-center",
                autoClose: 5000,
                theme: "colored",
            });
        }
    };
    return (
        <div className="grid grid-cols-12 max-h-screen min-h-screen">
            <div className="col-span-12 bg-purple-50 flex flex-col">
                <div className="h-14 p-5 bg-white shadow flex items-center justify-between">
                    <div className="flex gap-8 items-center">
                        <div className="flex items-center">
                            <Link href={`/my-course`}>
                                <box-icon
                                    name="left-arrow-circle"
                                    size="md"
                                ></box-icon>
                            </Link>
                        </div>
                        {/* <div className="text-2xl font-bold uppercase border-r border-gray-300 pr-8">Logo</div> */}
                        <div className="h-9">
                            <img
                                src={site.logo}
                                alt=""
                                className="w-full h-full object-contain"
                            />
                        </div>
                        <div className="text-xl font-bold">{course.title}</div>
                    </div>
                    <div className="text-xl font-bold text-gray-800 flex items-center gap-1">
                        <box-icon
                            name="hourglass-top"
                            type="solid"
                            color="#018083"
                        ></box-icon>
                        {progress}
                    </div>
                </div>
                <div className="flex-1 m-8 rounded-xl shadow-sm bg-white overflow-hidden">
                    <div className="h-14 bg-purple-100 flex items-center px-6">
                        <div className="text-xl font-bold">
                            Penilaian & Kirim Jawaban
                        </div>
                    </div>

                    {/* Detail Activity */}
                    <div className="m-8 text-gray-700 flex flex-col items-center">
                        <div className="mb-5">
                            <h4 className="text-3xl font-bold text-center mb-1">
                                Beri Penilaian untuk Kelas Ini
                            </h4>
                            <p className="max-w-2xl text-center">
                                Kami sangat menghargai masukan Anda untuk
                                meningkatkan kualitas kelas online kami. Silakan
                                beri penilaian dan berikan komentar tentang
                                pengalaman Anda selama mengikuti kelas ini.
                            </p>
                        </div>
                        <div className="flex flex-col items-center text-gray-500 text-sm italic mb-5">
                            <div className="mb-2">
                                {[1, 2, 3, 4, 5].map((star, index) => (
                                    <box-icon
                                        key={index}
                                        name="star"
                                        type="solid"
                                        size="lg"
                                        color={
                                            rating >= star
                                                ? "#fbbf24"
                                                : "#e2e8f0"
                                        } // Warna kuning jika rating tercapai
                                        onClick={() => handleRating(star)} // Mengubah rating saat bintang diklik
                                        style={{ cursor: "pointer" }} // Mengubah pointer saat di-hover
                                    />
                                ))}
                            </div>
                            (Klik pada bintang untuk memberikan penilaian Anda,
                            dari 1 hingga 5 bintang)
                        </div>
                        <div className="w-full max-w-xl mb-5">
                            <textarea
                                id="description"
                                placeholder={"Tulis komentar Anda di sini..."}
                                onChange={(e) => setDescription(e.target.value)}
                                className={`form-control`}
                                rows={5}
                            >
                                {description}
                            </textarea>
                        </div>
                        <p className="font-medium mb-5">
                            Terima kasih atas masukan Anda!
                        </p>
                        <div className="max-w-sm w-full">
                            <button
                                onClick={handleSubmit}
                                className="button-primary"
                            >
                                Nilai & Kirim Jawaban
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
};

export default Index;
