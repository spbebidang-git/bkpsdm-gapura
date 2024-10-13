const TitleActivity = ({ number, title }) => {
    return (
        <div className="max-w-lg mx-auto flex flex-col items-center py-10">
            <div className="text-lg text-gray-700">Nomor Kegiatan : {number}</div>
            <div className="text-2xl text-gray-700 font-extrabold">{title}</div>
        </div>
    )
}

export default TitleActivity