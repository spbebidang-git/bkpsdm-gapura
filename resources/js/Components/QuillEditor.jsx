import ReactQuill from "react-quill";
import "react-quill/dist/quill.snow.css";
// Or if you only need the core build
// import Quill from 'quill/core';
const QuillEditor = ({ title, label = null, value, onChange, isRequired = false, error, type = null }) => {
    const renderLabel = label ?? title;
    return (
        <div className="mb-4">
            <label htmlFor={title.replace(/\s+/g, '_')} className="block mb-2 text-sm font-bold text-gray-700">
                {renderLabel} {isRequired && <span className="text-red-500">*</span>}
            </label>
            <div className="quill-editor-container">
                <ReactQuill
                    theme="snow"
                    value={value}
                    onChange={onChange}
                    className={`${error ? 'border-red-500' : 'border-gray-200'} rounded-xl overflow-hidden`}
                />
            </div>
            {error && <small className="text-red-500 text-sm mt-1">{error}</small>}
        </div>
    );
};

export default QuillEditor