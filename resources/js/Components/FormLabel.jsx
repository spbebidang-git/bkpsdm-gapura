const FormLabel = ({ title, isRequired }) => {
    return (
        <label htmlFor={title.replace(/\s+/g, '_')} class="form-label" > {title} {isRequired && <span className="text-red-500">*</span>}</label >
    )
}

export default FormLabel