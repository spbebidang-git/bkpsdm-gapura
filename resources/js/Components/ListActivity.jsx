import { useEffect, useState } from "react";

const ListActivity = ({ type, activity, isActive }) => {
    const [title, setTitle] = useState('');
    const [description, setDescription] = useState('');
    const [icon, setIcon] = useState('');

    useEffect(() => {
        switch (type) {
            case 'pre-test':
                setTitle('Pre Test');
                setDescription('Mengerjakan Quiz');
                setIcon('pencil');  // Misalnya ikon untuk quiz adalah 'pencil'
                break;
            case 'post-test':
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
        <div className="flex mb-2">
            <div className={`w-4 ${isActive ? 'bg-purple-500' : 'bg-white'}`}></div>
            <div className={`w-full px-8 border-gray-200 py-2 ${isActive ? 'bg-gray-100' : 'bg-white'}`}>
                <div className="w-fit mb-1 px-4 flex items-center justify-center text-[12px] font-extrabold bg-purple-500 text-white rounded-full shadow">
                    {title}
                </div>
                <li className="">
                    <div className={`mb-0.5 ${isActive ? 'text-purple-500' : ''}`}>{activity}</div>
                    <div className="flex items-center gap-1 text-xs text-gray-500 italic">
                        <box-icon name={icon} size="sm" color="gray" ></box-icon>
                        {description}
                    </div>
                </li>
            </div>
        </div>

    )
}

export default ListActivity