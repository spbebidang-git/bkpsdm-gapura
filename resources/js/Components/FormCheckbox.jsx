// FormCheckbox.jsx

const FormCheckbox = ({ id, title, onChange, checked, isRequired }) => {
    return (
        <div className="flex items-center">
            <input
                id={id}
                type="checkbox"
                checked={checked}
                onChange={onChange}
                className="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 focus:ring-2"
            />
            <label htmlFor={id} className="ms-2 text-sm font-medium text-gray-900 dark:text-gray-300">
                {title} {isRequired && <span className="text-red-500">*</span>}
            </label>
        </div>
    );
};

export default FormCheckbox;
