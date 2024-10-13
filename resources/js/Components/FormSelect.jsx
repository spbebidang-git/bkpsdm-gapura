const FormSelect = ({ title, options, onChange, isRequired, selectedValue }) => {
    return (
        <div className="mb-3">
            <label htmlFor={title.replace(/\s+/g, '_')} className="form-label">
                {title} {isRequired && <span className="text-red-500">*</span>}
            </label>
            <select
                id={title.replace(/\s+/g, '_')}
                className="form-control"
                onChange={onChange}
                value={selectedValue} // Menambahkan value untuk preselected option
            >
                {options.map((option, index) => (
                    <option
                        key={index}
                        value={option.value}
                    >
                        {option.label}
                    </option>
                ))}
            </select>
        </div>
    );
};

export default FormSelect;
