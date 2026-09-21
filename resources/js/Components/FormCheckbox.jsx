import React from "react";

export default function FormCheckbox({ id, title, checked, onChange, error }) {
    return (
        <div className="flex items-center mb-2">
            <input
                id={id}
                type="checkbox"
                checked={checked}
                onChange={onChange}
                className="h-4 w-4 rounded border-hairline bg-white text-primary focus:ring-2 focus:ring-primary/30"
            />
            <label
                htmlFor={id}
                className="ms-2 text-sm font-medium text-charcoal"
            >
                {title}
            </label>
            {error && <p className="ml-2 text-sm text-red-600">{error}</p>}
        </div>
    );
}
