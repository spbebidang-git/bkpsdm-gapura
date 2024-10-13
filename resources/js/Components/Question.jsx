const Question = () => {
    return (
        <div className="p-8">
            <h3 className="mb-5 text-lg font-medium text-gray-900 dark:text-white">How much do you expect to use each month?</h3>
            <ul className="grid w-full gap-6 md:grid-cols-1">
                <li>
                    <input type="radio" id="hosting-small" name="hosting" value="hosting-small" className="hidden peer" required />
                    <label for="hosting-small" className="flex gap-4 p-4 items-center w-full text-gray-500 bg-white border border-gray-200 rounded-xl cursor-pointer peer-checked:border-primary peer-checked:bg-primary peer-checked:text-white hover:text-gray-600 hover:bg-gray-100 ">
                        <div className="font-bold border-2 rounded-full w-10 h-10 flex items-center justify-center">
                            A
                        </div>
                        <div className="block">
                            <div className="w-full">Good for small websites</div>
                        </div>

                    </label>
                </li>

            </ul>

        </div>
    )
}

export default Question