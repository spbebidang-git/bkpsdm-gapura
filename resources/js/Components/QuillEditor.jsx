import ReactQuill from "react-quill";
import "react-quill/dist/quill.snow.css";
// Or if you only need the core build
// import Quill from 'quill/core';
const QuillEditor = ({ title, value, onChange, isRequired = false, error, type = null }) => {
    return (
        <div className="mb-3">
            <label htmlFor={title.replace(/\s+/g, '_')} className="form-label">
                {title} {isRequired && <span className="text-red-500">*</span>}
            </label>
            <div>
                <ReactQuill
                    theme="snow"
                    value={value}

                    className={`${error ? 'error' : ''} form-control`}
                />
            </div>
            {error && <small className="text-red-500 text-sm mt-1">{error}</small>}
        </div>
    );
};

export default QuillEditor