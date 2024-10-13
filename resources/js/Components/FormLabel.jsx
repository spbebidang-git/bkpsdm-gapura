const FormLabel = ({ title }) => {
    return (
        <label htmlFor={title.replace(/\s+/g, '_')} class="form-label" > {title}</label >
    )
}

export default FormLabel