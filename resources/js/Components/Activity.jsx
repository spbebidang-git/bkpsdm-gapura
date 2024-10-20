import { router } from "@inertiajs/react";
import { useState } from "react";
import { pdfjs } from 'react-pdf';
import 'videojs-youtube'; // Import plugin YouTube

pdfjs.GlobalWorkerOptions.workerSrc = `//unpkg.com/pdfjs-dist@${pdfjs.version}/build/pdf.worker.min.mjs`;

const Activity = ({ id, type, data, slug }) => {
    // console.log(type)
    if (type === 'pre-test') {
        // State untuk menyimpan jawaban
        const [answers, setAnswers] = useState({});

        const handleAnswerChange = (index, answer, questionId) => {
            setAnswers(prevAnswers => ({
                ...prevAnswers,
                [index]: {
                    question: data[index].question,
                    option_select: answer,
                    question_id: questionId
                }
            }));
        };

        const handleSubmit = () => {
            // console.log(answers)
            const formattedAnswers = Object.values(answers);
            router.post(`/learn/${slug}/1/${id}`, formattedAnswers)
        }

        return (
            <div>
                {data.map((item, index) => {
                    // console.log(item.question_id)
                    const option = Object.entries(item.option).map(([key, value], index2) => {
                        return (
                            <li key={index2}>
                                <input
                                    type="radio"
                                    id={`option-${index}-${index2}`}
                                    name={`option-${index}`}
                                    value={key}
                                    className="hidden peer"
                                    required
                                    onChange={() => handleAnswerChange(index, value, item.question_id)} // Tambahkan question_id ke jawaban
                                />
                                <label
                                    htmlFor={`option-${index}-${index2}`}
                                    className="flex gap-4 p-3 items-center w-full text-gray-500 bg-white border border-gray-200 rounded-xl cursor-pointer peer-checked:border-primary peer-checked:bg-primary peer-checked:text-white hover:text-gray-600 hover:bg-gray-100"
                                >
                                    <div className="font-bold border-2 rounded-full w-10 h-10 flex items-center justify-center uppercase">
                                        {key}
                                    </div>
                                    <div className="block">
                                        <div className="w-full">{value}</div>
                                    </div>
                                </label>
                            </li>
                        );
                    });

                    return (
                        <div className="mb-8 px-5" key={index}>
                            <h3 className="mb-5 text-lg font-medium text-gray-900 dark:text-white">
                                {index + 1 + '. ' + item.question}
                            </h3>
                            <ul className="grid w-full gap-4 md:grid-cols-1">
                                {option}
                            </ul>
                        </div>
                    );
                })}

                <div className="w-full flex justify-center mt-6">
                    <button onClick={handleSubmit} className="button-primary max-w-56">
                        Submit
                    </button>
                </div>
            </div>
        );
    }

    if (type === 'post-test') {
        // State untuk menyimpan jawaban
        const [answers, setAnswers] = useState({});

        const handleAnswerChange = (index, answer, questionId) => {
            setAnswers(prevAnswers => ({
                ...prevAnswers,
                [index]: {
                    question: data[index].question,
                    option_select: answer,
                    question_id: questionId
                }
            }));
        };

        const handleSubmit = () => {
            // console.log(answers)
            const formattedAnswers = Object.values(answers);
            router.post(`/learn/facilis-itaque-quia-id-assumenda-vero-sed/1/${id}`, formattedAnswers)
        }

        return (
            <div>
                {data.map((item, index) => {
                    // console.log(item.question_id)
                    const option = Object.entries(item.option).map(([key, value], index2) => {
                        return (
                            <li key={index2}>
                                <input
                                    type="radio"
                                    id={`option-${index}-${index2}`}
                                    name={`option-${index}`}
                                    value={key}
                                    className="hidden peer"
                                    required
                                    onChange={() => handleAnswerChange(index, value, item.question_id)} // Tambahkan question_id ke jawaban
                                />
                                <label
                                    htmlFor={`option-${index}-${index2}`}
                                    className="flex gap-4 p-4 items-center w-full text-gray-500 bg-white border border-gray-200 rounded-xl cursor-pointer peer-checked:border-primary peer-checked:bg-primary peer-checked:text-white hover:text-gray-600 hover:bg-gray-100"
                                >
                                    <div className="font-bold border-2 rounded-full w-10 h-10 flex items-center justify-center uppercase">
                                        {key}
                                    </div>
                                    <div className="block">
                                        <div className="w-full">{value}</div>
                                    </div>
                                </label>
                            </li>
                        );
                    });

                    return (
                        <div className="p-8" key={index}>
                            <h3 className="mb-5 text-lg font-medium text-gray-900 dark:text-white">
                                {item.question}
                            </h3>
                            <ul className="grid w-full gap-6 md:grid-cols-1">
                                {option}
                            </ul>
                        </div>
                    );
                })}

                <button onClick={handleSubmit} className="mt-6 bg-primary text-white p-4 rounded-lg">
                    Submit
                </button>
            </div>
        );
    }

    if (type === 'task') {
        return <div className="h-full p-8">
            <div className="flex items-center flex-col">
                <div className="text-xl font-bold text-gray-700 capitalize">title</div>
                <div className="text-gray-600">description</div>
            </div>
        </div>;
    }

    if (type === 'meeting') {
        return <div className="h-full">meeting</div>;
    }

    if (type === 'video') {
        const handleSubmit = () => {
            // console.log(answers)
            // const formattedAnswers = Object.values(answers);
            router.post(`/learn/${slug}/1/${id}`, [])
        }

        return (
            <div className="flex flex-col items-center rounded-xl overflow-hidden p-6">
                <iframe
                    width="1024px"
                    height="512px"
                    className="aspect-video"
                    src={`${data.src_video}`}
                    title="YouTube video player"
                    frameBorder="0"
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"

                ></iframe>

                <div className="w-full flex justify-center mt-6">
                    <button onClick={handleSubmit} className="button-primary max-w-56">
                        Submit
                    </button>
                </div>
            </div>
        )
    }

    if (type === 'literature') {

        return (
            <div className="pdf-container">
                <iframe
                    src={`${data.src_pdf}`}
                    width="100%"
                    height="600px"
                    title="PDF Viewer"
                ></iframe>
                {/* <div>
                    <Document file={data.src_pdf} onLoadSuccess={onDocumentLoadSuccess}>
                        <Page pageNumber={pageNumber} />
                    </Document> */}
                {/* <p>
                        Page {pageNumber} of {numPages}
                    </p> */}
                {/* </div> */}

            </div>
        )
    }

    return null;
};


export default Activity;
