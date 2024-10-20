import FormInput from "@/Components/FormInput";
import App from "@/Layouts/Instructor/App";

const Index = ({ instructor }) => {
    return (
        <App>
            <div className="text-center text-2xl font-bold text-gray-700 mb-5">Profile</div>

            <div className="grid grid-cols-12 gap-8 max-w-5xl mx-auto">
                <div className="col-span-3">
                    <div className="w-full h-36 border bg-white rounded-lg">
                        <img src={instructor.profile_picture} className="w-full h-full object-cover" alt="" />
                    </div>
                </div>
                <div className="col-span-9 bg-white rounded-lg shadow p-5">
                    <FormInput title={'name'} value={instructor.name} />
                    <FormInput title={'email'} value={instructor.email} />
                </div>
            </div>
        </App>
    )
}

export default Index;