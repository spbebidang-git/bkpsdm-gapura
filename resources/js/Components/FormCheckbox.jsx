import React from "react";

export default function FormCheckbox({ id, title, checked, onChange, error }) {
    return (
        <div className="flex items-center mb-2">
            <input
                id={id}
                type="checkbox"
                checked={checked}
                onChange={onChange}
                className="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded-sm focus:ring-blue-500 focus:ring-2"
            />
            <label
                htmlFor={id}
                className="ms-2 text-sm font-medium text-gray-900"
            >
                {title}
            </label>
            {error && <p className="ml-2 text-sm text-red-600">{error}</p>}
        </div>
    );
}
