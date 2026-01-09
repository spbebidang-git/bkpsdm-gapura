const FormSelect = ({
    title,
    label = null,
    options,
    onChange,
    isRequired,
    error,
    value,
    disabled = false,
}) => {
    const renderLabel = label ?? title;

    return (
        <div className="mb-3">
            <label
                htmlFor={title.replace(/\s+/g, "_")}
                className="form-label"
            >
                {renderLabel} {isRequired && <span className="text-red-500">*</span>}
            </label>

            <select
                id={title.replace(/\s+/g, "_")}
                className={`form-control ${error ? "error" : ""} ${disabled ? "bg-gray-100 cursor-not-allowed" : ""}`}
                onChange={onChange}
                value={value}
                disabled={disabled}
            >
                <option value="">Pilih Opsi</option>
                {options.map((option, index) => (
                    <option key={index} value={option.value}>
                        {option.label}
                    </option>
                ))}
            </select>

            {error && (
                <small className="text-red-500 text-sm mt-1">
                    {error}
                </small>
            )}
        </div>
    );
};

export default FormSelect;
