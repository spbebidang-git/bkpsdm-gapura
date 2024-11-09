import App from "@/Layouts/Instructor/App";
import { Link, router } from "@inertiajs/react";
import { useState } from "react";

const Edit = ({ slug, activityId, questionId, question }) => {
    console.log(slug, activityId, questionId)
    // State untuk field yang diisi dengan data awal dari props
    const [description, setDescription] = useState(question.description);
    const [options, setOptions] = useState({
        option1: question.option1,
        option2: question.option2,
        option3: question.option3,
        option4: question.option4,
    });
    const [correctAnswer, setCorrectAnswer] = useState(question.is_correct); // Pertahankan ini sebagai integer
    const [score, setScore] = useState(question.score);

    const handleOptionChange = (e) => {
        const { id, value } = e.target;
        setOptions({
            ...options,
            [id]: value,
        });
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        // Siapkan data yang akan dikirimkan
        const formData = {
            description,
            options,
            correct_answer: correctAnswer, // Pastikan ini tetap integer
            score,
        };

        // Lakukan request PUT ke server untuk meng-update data
        router.put(`/instructor/course/${slug}/activity/variant/${activityId}/${questionId}/question-item/${question.id}`, formData);
    };

    return (
        <App>
            <div className="text-center text-2xl font-bold text-gray-700 mb-5">Edit Variant</div>

            <form onSubmit={handleSubmit}>
                <div className="max-w-2xl mx-auto shadow bg-white rounded-xl">
                    <div className="p-6">
                        <div>
                            <label htmlFor="description" className="font-semibold text-primary">Deskripsi</label>
                            <textarea
                                name="description"
                                id="description"
                                cols="30"
                                rows="5"
                                className="h-20 form-control p-3"
                                value={description}
                                onChange={(e) => setDescription(e.target.value)}
                            />
                        </div>

                        <div className="grid grid-cols-4 items-center gap-5 py-3 font-semibold text-primary">
                            <div className="col-span-3">Jawaban</div>
                            <div className="text-center">Jawaban Benar</div>
                        </div>

                        {['option1', 'option2', 'option3', 'option4'].map((option, index) => (
                            <div className="grid grid-cols-4 items-center justify-center mb-5" key={option}>
                                <div className="col-span-3">
                                    <input
                                        type="text"
                                        name="option[]"
                                        className="form-control"
                                        id={option}
                                        value={options[option]} // Mengakses nilai berdasarkan kunci option1, option2, dst.
                                        onChange={handleOptionChange}
                                    />
                                </div>
                                <div className="flex justify-center">
                                    <input
                                        type="radio"
                                        name="correct_answer"
                                        value={options[option]} // Nilai radio button adalah integer 1-4
                                        className="form-radio"
                                        checked={correctAnswer === options[option]} // Memastikan checked sesuai dengan nilai yang disimpan
                                        onChange={() => setCorrectAnswer(options[option])} // Mengupdate correctAnswer sesuai index
                                    />
                                </div>
                            </div>
                        ))}

                        <div className="flex items-center gap-2 justify-between">
                            <div className="flex gap-2 items-center">
                                <div className="font-semibold">Skor:</div>
                                <div>
                                    <input
                                        type="text"
                                        name="score"
                                        id="score"
                                        className="rounded-none h-8 w-14 text-center form-control font-semibold"
                                        value={score}
                                        onChange={(e) => setScore(e.target.value)}
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div className="max-w-80 mx-auto gap-3 flex justify-center mt-8">
                    <button className="button-primary">Save</button>
                    <Link className="w-full" href={route('instructor.course.activity.variant.question-item.index', [slug, activityId, questionId])}>
                        <button type="button" className="button-secondary">Back</button>
                    </Link>
                </div>
            </form>
        </App>
    );
};

export default Edit;
