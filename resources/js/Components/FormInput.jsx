const FormInput = ({
    title,
    label = null,
    value,
    onChange,
    isRequired,
    placeholder = null,
    error,
    small = null,
    type = null,
    readOnly = false
}) => {
    const renderLabel = label ?? title;

    const capitalize = (str) =>
        str.charAt(0).toUpperCase() + str.slice(1);

    const finalPlaceholder = placeholder
        ? capitalize(placeholder)
        : capitalize(renderLabel);

    return (
        <div className="mb-3">
            <label
                htmlFor={title.replace(/\s+/g, '_')}
                className="form-label"
            >
                {renderLabel} {isRequired && <span className="text-red-500">*</span>}
            </label>

            <input
                type={type ?? 'text'}
                id={title.replace(/\s+/g, '_')}
                value={value}
                className={`${error ? 'error' : ''} form-control ${readOnly ? 'bg-gray-300 text-gray-700 cursor-not-allowed' : ''
                    }`}
                placeholder={finalPlaceholder}
                onChange={onChange}
                readOnly={readOnly}
            />

            {error && (
                <small className="text-red-500 text-sm mt-1 block">
                    {error}
                </small>
            )}

            {small && (
                <small className="text-gray-500 text-xs mt-1 block">
                    {small}
                </small>
            )}
        </div>
    );
};

export default FormInput;
