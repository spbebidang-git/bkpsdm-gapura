import FormInput from "@/Components/FormInput";
import { Link, useForm, usePage } from "@inertiajs/react";
import ReCAPTCHA from 'react-google-recaptcha';

const LoginParticipant = () => {
    const { site } = usePage().props

    const { data, setData, post, processing, errors } = useForm({
        username: "",
        password: "",
        recaptchaToken: "",  // Tambahkan recaptchaToken ke data
    });

    const handleChange = (e) => {
        const key = e.target.id;
        const value = e.target.value;
        setData(key, value);
    };

    const handleRecaptchaChange = (token) => {
        setData('recaptchaToken', token);  // Set token ke data
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        post('/sign-in');  // Kirim data termasuk token ke backend
    };


    return (
        <section className="min-h-screen flex flex-col bg-gray-100">
            <div className="bg-primary h-2"></div>

            <div className="flex-1 flex flex-col justify-center items-center">
                <Link href="/" className="flex items-center max-w-56 mb-6 text-2xl font-semibold text-gray-900">
                    <img src={site.logo} alt="" className='w-full h-full object-fill' />
                </Link>
                <div className="w-full bg-white rounded-lg shadow md:mt-0 sm:max-w-md xl:p-0">
                    <div className="p-6 space-y-4 md:space-y-6 sm:p-8">
                        <h1 className="text-xl font-bold leading-tight tracking-tight text-gray-900 md:text-2xl">
                            Sign in to your account
                        </h1>
                        <form onSubmit={handleSubmit}>
                            <div className="mb-1">
                                {/* username */}
                                <FormInput
                                    title={'username'}
                                    isRequired={true}
                                    placeholder={'Username'}
                                    value={data.username}
                                    onChange={handleChange}
                                    error={errors.username}
                                />
                                {/* password */}
                                <FormInput
                                    title={'password'}
                                    isRequired={true}
                                    placeholder={'********'}
                                    type="password"
                                    value={data.password}
                                    onChange={handleChange}
                                    error={errors.password}
                                />
                                {/* Recaptha */}
                                <div className="w-full">
                                    <ReCAPTCHA
                                        sitekey={import.meta.env.VITE_RECAPTCHA_SITE_KEY}
                                        onChange={handleRecaptchaChange}
                                    />
                                </div>
                                {/* submit */}
                                <button type="submit" className="button-primary my-2.5" disabled={processing}>
                                    {processing ? 'Please Wait...' : 'Sign In'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </section>
    )
}

export default LoginParticipant
