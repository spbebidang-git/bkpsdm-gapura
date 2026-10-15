import type { LabelHTMLAttributes, ReactNode } from 'react';

interface InputLabelProps extends LabelHTMLAttributes<HTMLLabelElement> {
    value?: ReactNode;
    children?: ReactNode;
}

export default function InputLabel({ value, className = '', children, ...props }: InputLabelProps) {
    return (
        <label {...props} className={`block font-medium text-sm text-gray-700 dark:text-gray-300 ` + className}>
            {value ? value : children}
        </label>
    );
}
