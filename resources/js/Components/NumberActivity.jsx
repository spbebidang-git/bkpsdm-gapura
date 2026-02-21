const NumberActivity = ({ number }) => {
    return (
        <div className="max-w-md mx-auto flex justify-between">
            <div className={`px-6 py-3 ${number == 1 ? 'text-white bg-primary' : 'bg-gray-200 text-black'} rounded-xl text-xl font-bold shadow`}>1</div>
            <div className={`px-6 py-3 ${number == 2 ? 'text-white bg-primary' : 'bg-gray-200 text-black'} rounded-xl text-xl font-bold shadow`}>2</div>
            <div className={`px-6 py-3 ${number == 3 ? 'text-white bg-primary' : 'bg-gray-200 text-black'} rounded-xl text-xl font-bold shadow`}>3</div>
        </div>
    )
}

export default NumberActivity