
const FormTextArea = ({ title, value, onChange, isRequired, rows = 3 }) => {
    return (
        <div className="mb-3">
            <label htmlFor={title.replace(/\s+/g, '_')} class="form-label">{title} {isRequired && <span className="text-red-500">*</span>}</label>
            <textarea id={title.replace(/\s+/g, '_')} placeholder={title} className="form-control" rows={rows} value={value} onChange={onChange}></textarea>
        </div>
    );
};

export default FormTextArea;