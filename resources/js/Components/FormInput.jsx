const FormInput = ({ title, value, onChange, isRequired, placeholder = null, error, type = null }) => {
    return (
        <div className="mb-3">
            <label htmlFor={title.replace(/\s+/g, '_')} className="form-label">
                {title} {isRequired && <span className="text-red-500">*</span>}
            </label>
            <input
                type={type ? type : 'text'}
                id={title.replace(/\s+/g, '_')}
                value={value}
                className={`form-control ${error ? 'border-red-500' : ''}`}
                placeholder={placeholder ? placeholder : title}
                onChange={onChange}
            />
            {error && <small className="text-red-500 text-sm mt-1">{error}</small>}
        </div>
    );
};

export default FormInput;
