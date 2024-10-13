import App from "@/Layouts/Instructor/App";
import { Link, router } from "@inertiajs/react";
import { useState } from "react";

const Create = ({ slug, activityId, questionId }) => {

    const [description, setDescription] = useState('');
    const [options, setOptions] = useState({
        option1: '',
        option2: '',
        option3: '',
        option4: ''
    });
    const [correct_answer, setCorrect_answer] = useState('');
    const [score, setScore] = useState('');

    const handleOptionChange = (e) => {
        const { id, value } = e.target;
        setOptions({
            ...options,
            [id]: value
        });
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        // Handle form submission logic here
        const formData = {
            description,
            options,
            correct_answer,
            score
        };

        router.post(`/instructor/course/${slug}/activity/variant/${activityId}/${questionId}/question-item`, formData)
        // console.log(formData);
    };


    return (
        <App>
            <div className="text-center text-2xl font-bold text-gray-700 mb-5">Add New Variant</div>

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
                                        value={options[option]}
                                        onChange={handleOptionChange}
                                    />
                                </div>
                                <div className="flex justify-center">
                                    <input
                                        type="radio"
                                        name="correct_answer"
                                        value={index + 1}
                                        className="form-radio"
                                        checked={correct_answer === (index + 1).toString()}
                                        onChange={() => setCorrect_answer((index + 1).toString())}
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

                <div className="flex gap-3 max-w-lg mx-auto pt-8">
                    <button className="button-primary">Save</button>
                    <Link className="w-full" href={`/instructor/course/${slug}/activity/variant/${activityId}`}>
                        <button className="button-secondary">Back</button>
                    </Link>
                </div>
            </form>
        </App>
    )
}

export default Create