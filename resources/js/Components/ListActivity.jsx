import { Link } from "@inertiajs/react";
import { useEffect, useState } from "react";

const ListActivity = ({ type, activity, status, isActive, slug, scheduleId = 1, activityId }) => {
    const [title, setTitle] = useState('');
    const [description, setDescription] = useState('');
    const [icon, setIcon] = useState('');
    // const [isActive, setIsActive] = useState(0);

    useEffect(() => {
        switch (type) {
            case 'pre-test':
                localStorage.removeItem('countdownStartTime');
                setTitle('Pre Test');
                setDescription('Mengerjakan Quiz');
                setIcon('pencil');  // Misalnya ikon untuk quiz adalah 'pencil'
                break;
            case 'post-test':
                localStorage.removeItem('countdownStartTime');
                setTitle('Post Test');
                setDescription('Mengerjakan Quiz');
                setIcon('pencil');  // Ikon yang sama dengan pre-test
                break;
            case 'task':
                setTitle('Task');
                setDescription('Mengerjakan Tugas');
                setIcon('task');  // Misalnya ikon untuk tugas adalah 'task'
                break;
            case 'meeting':
                setTitle('Online Meeting');
                setDescription('Meeting Online');
                setIcon('video');  // Misalnya ikon untuk meeting online adalah 'video'
                break;
            case 'video':
                setTitle('Presentation Video');
                setDescription('Menonton Video');
                setIcon('play-circle');  // Misalnya ikon untuk video adalah 'play-circle'
                break;
            case 'literature':
                setTitle('Literature');
                setDescription('Membaca Materi');
                setIcon('book');  // Misalnya ikon untuk materi adalah 'book'
                break;
            default:
                setTitle('');
                setDescription('');
                setIcon('');
        }
    }, [type]);

    return (
        <div className="mb-4 rounded-xl cursor-pointer shadow">
            <Link href={route('participant.learn.instruction.index', { course: slug, schedule: scheduleId, activity: activityId })}>
                <div className="flex rounded-xl overflow-hidden">
                    <div className={`w-4 ${isActive ? 'bg-primary' : 'bg-white'}`}></div>
                    <div className={`w-full px-8 border-gray-200 py-3 ${isActive ? 'bg-teal-50' : 'bg-white'}`}>
                        <div className="flex gap-3">
                            {status === 'done' ? (
                                <div className="w-fit mb-1 px-4 flex items-center justify-center text-[12px] font-extrabold bg-green-500 text-white rounded-full shadow">
                                    Selesai
                                </div>
                            ) : status === 'in_progress' ? (
                                <div className="w-fit mb-1 px-4 flex items-center justify-center text-[12px] font-extrabold bg-yellow-500 text-white rounded-full shadow">
                                    Sedang Berlangsung
                                </div>
                            ) : (
                                <div className="w-fit mb-1 px-4 flex items-center justify-center text-[12px] font-extrabold bg-gray-500 text-white rounded-full shadow">
                                    Belum Selesai
                                </div>
                            )}

                            <div className="w-fit mb-1 px-4 flex items-center justify-center text-[12px] font-extrabold bg-primary text-white rounded-full shadow">
                                {title}
                            </div>
                        </div>
                        <li className="">
                            <div className={`mb-0.5 ${isActive ? 'text-primary' : ''}`}>{activity}</div>
                            <div className="flex items-center gap-1 text-xs text-gray-500 italic">
                                <box-icon name={icon} size="sm" color="gray" ></box-icon>
                                {description}
                            </div>
                        </li>
                    </div>
                </div>
            </Link>
        </div>
    )
}

export default ListActivity