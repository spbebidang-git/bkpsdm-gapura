import { Datepicker } from 'flowbite';
import { useEffect, useRef } from 'react';

const DatePicker = ({ title, isRequired, handleChange, value }) => {
    const datepickerRef = useRef(null);

    useEffect(() => {
        const $datepickerEl = datepickerRef.current;

        // Options for the datepicker
        const options = {
            autohide: true,
            format: 'mm/dd/yyyy',
            orientation: 'bottom',
            autoSelectToday: false,
            title: null,
            rangePicker: false,
            onShow: () => { },
            onHide: () => { },
        };

        const instanceOptions = {
            id: title.replace(/\s+/g, '_'),
            override: true,
        };

        // Initialize datepicker
        const datepicker = new Datepicker($datepickerEl, options, instanceOptions);

        // Handle date change
        const handleDateChange = (event) => {
            if (handleChange) {
                console.log('Date Changed:', event.target.value);
                handleChange({ target: { id: title.replace(/\s+/g, '_'), value: event.target.value } });
            }
        };

        // Add event listener for change event
        $datepickerEl.addEventListener('change', handleDateChange);

        // Clean up event listener and destroy datepicker instance
        return () => {
            $datepickerEl.removeEventListener('change', handleDateChange);
            datepicker.destroy();
        };
    }, [handleChange, title]);

    return (
        <div className='mb-3'>
            <label htmlFor={title.replace(/\s+/g, '_')} className="form-label">
                {title} {isRequired && <span className="text-red-500">*</span>}
            </label>
            <div className="relative">
                <div className="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
                    <svg
                        className="w-4 h-4 text-gray-500"
                        aria-hidden="true"
                        xmlns="http://www.w3.org/2000/svg"
                        fill="currentColor"
                        viewBox="0 0 20 20"
                    >
                        <path d="M20 4a2 2 0 0 0-2-2h-2V1a1 1 0 0 0-2 0v1h-3V1a1 1 0 0 0-2 0v1H6V1a1 1 0 0 0-2 0v1H2a2 2 0 0 0-2 2v2h20V4ZM0 18a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V8H0v10Zm5-8h10a1 1 0 0 1 0 2H5a1 1 0 0 1 0-2Z" />
                    </svg>
                </div>
                <input
                    ref={datepickerRef}
                    datepicker
                    datepicker-autohide
                    type="text"
                    className="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full ps-10 p-2.5"
                    placeholder="Select date"
                    id={title.replace(/\s+/g, '_')}
                    value={value} // Set value from state
                    readOnly // Prevent manual input changes, only through datepicker
                />
            </div>
        </div>
    );
};

export default DatePicker;
