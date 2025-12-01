const FormTextArea = ({
    title,
    label = null,
    value,
    onChange,
    isRequired,
    error,
    rows = 3
}) => {
    const renderLabel = label ?? title;
    const renderPlaceholder = `Masukkan ${renderLabel}`;

    return (
        <div className="mb-3">
            <label
                htmlFor={title.replace(/\s+/g, '_')}
                className="form-label"
            >
                {renderLabel} {isRequired && <span className="text-red-500">*</span>}
            </label>

            <textarea
                id={title.replace(/\s+/g, '_')}
                placeholder={renderPlaceholder}
                className={`${error ? 'error' : ''} form-control`}
                rows={rows}
                value={value}
                onChange={onChange}
            ></textarea>

            {error && (
                <small className="text-red-500 text-sm mt-1">
                    {error}
                </small>
            )}
        </div>
    );
};

export default FormTextArea;
