
const Captcha = ({ captchaUrl, fetchCaptcha, captchaInput, handleCaptchaChange, errors }) => {
    return (
        <div className="max-w-xs mx-auto">
            <div>
                <div className="mb-3">
                    <div className="flex-1 flex items-center rounded-sm shadow overflow-hidden h-10">
                        <img
                            onClick={fetchCaptcha}
                            src={captchaUrl}
                            className="w-full h-full"
                            alt="captcha"
                            style={{ cursor: 'pointer' }}
                        />
                    </div>
                    <small className="text-gray-600 italic">Klik pada gambar untuk refresh Captcha.</small>
                </div>
                <div>
                    <input
                        type="text"
                        id="captcha"
                        value={captchaInput}
                        onChange={handleCaptchaChange}
                        placeholder="Masukan CAPTCHA"
                        className={`${errors.captcha ? 'error' : ''} form-control text-center`}
                    />
                    {errors.captcha && (
                        <div className="mt-1 text-red-500 text-sm">
                            {errors.captcha}
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
};

export default Captcha;
