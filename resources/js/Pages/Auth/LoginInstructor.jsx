import FlashMessageToast from "@/Components/FlashMessageToast";
import FormInput from "@/Components/FormInput";
import { Link, useForm, usePage } from "@inertiajs/react";
import { useEffect, useState } from "react";
import { Bounce, toast } from 'react-toastify';

const LoginParticipant = () => {
    const { site } = usePage().props
    const [captchaUrl, setCaptchaUrl] = useState('');
    const [captchaInput, setCaptchaInput] = useState('');

    useEffect(() => {
        fetchCaptcha()
    }, []);
    const { data, setData, post, processing, errors } = useForm({
        email: "",
        password: "",
        captcha: "",  // Tambahkan recaptchaToken ke data
    })

    const fetchCaptcha = async () => {
        try {
            const response = await axios.get("/captcha-url");
            const data = response.data;
            setCaptchaInput("");
            setData("captcha", "");
            setCaptchaUrl(data.captcha);
            console.log('oke')
        } catch (error) {
            console.error("Error fetching captcha:", error);
        }
    };

    const handleCaptchaChange = (e) => {
        const value = e.target.value;
        setCaptchaInput(value);
        setData('captcha', value);  // Set nilai captcha di data form
    };

    const handleChange = (e) => {
        const key = e.target.id;
        const value = e.target.value;
        setData(key, value);
    }

    const handleSubmit = (e) => {
        e.preventDefault();
        post("/sign-in/instructor", {
            onError: (errors) => {
                console.log('Form Errors:', errors);
                toast.error(errors.danger, {
                    position: "top-right",
                    autoClose: 3000,
                    hideProgressBar: false,
                    closeOnClick: true,
                    pauseOnHover: true,
                    draggable: true,
                    progress: undefined,
                    theme: "colored",
                    transition: Bounce,
                });
                fetchCaptcha(); // Refresh CAPTCHA jika error login
            },
        });
    };

    return (
        <section className="min-h-screen flex flex-col bg-gray-100">
            <FlashMessageToast />

            <div className="bg-primary h-2"></div>

            <div className="flex-1 flex flex-col justify-center items-center">
                <Link href="/" className="flex items-center max-w-56 mb-6 text-2xl font-semibold text-gray-900">
                    <img src={site.logo} alt="" className='w-full h-full object-fill' />
                </Link>
                <div className="w-full bg-white rounded-lg shadow md:mt-0 sm:max-w-md xl:p-0">
                    <div className="p-6 space-y-4 md:space-y-6 sm:p-8">
                        <h1 className="text-xl font-bold leading-tight tracking-tight text-gray-900 md:text-2xl">
                            Welcome Back Instructor!
                        </h1>
                        <form onSubmit={handleSubmit}>
                            <div className="mb-1">
                                {/* email */}
                                <FormInput
                                    title={'email'}
                                    isRequired={true}
                                    placeholder={'Your Email'}
                                    value={data.email}
                                    onChange={handleChange}
                                    error={errors.email}
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
                                {/* CAPTCHA */}
                                <div className="py-2.5">
                                    <div className="flex flex-col sm:flex-row sm:items-center gap-3 -mt-1">
                                        <div className="w-full sm:flex-1 border border-gray-300 shadow flex items-center rounded-xl overflow-hidden">
                                            <img onClick={fetchCaptcha} src={captchaUrl} className="w-full h-full object-fill" alt="captcha" style={{ cursor: 'pointer' }} />
                                        </div>
                                        <div className="w-full sm:w-1/3">
                                            <input
                                                type="text"
                                                id="captcha"
                                                value={captchaInput}
                                                onChange={handleCaptchaChange}
                                                placeholder="CAPTCHA"
                                                className={`${errors.captcha ? 'error' : ''} form-control`}
                                            />
                                        </div>
                                    </div>
                                    {errors.captcha && <div className="mt-1 text-red-500 text-sm">{errors.captcha}
                                    </div>}
                                    <small className="text-gray-600 italic">Klik pada gambar untuk melakukan refresh CAPTCHA.</small>
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
