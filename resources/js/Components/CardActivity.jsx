const CardActivity = ({ title, description, onClick }) => {
    return (
        <div onClick={onClick} className="h-full border rounded-xl bg-white shadow hover:bg-purple-50 transition-all ease-in delay-75 cursor-pointer">
            <h4 className="text-xl font-bold text-gray-700 text-center py-2.5 border-b-2">{title}</h4>
            <p className="p-4 text-sm text-gray-600 text-center">
                {description}
            </p>
        </div>
    )
}

export default CardActivity;