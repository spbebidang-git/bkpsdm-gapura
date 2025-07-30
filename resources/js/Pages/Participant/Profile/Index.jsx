import UserProfileList from "@/Components/UserProfileList";
import App from "@/Layouts/Participant/App";
import { usePage } from "@inertiajs/react";
import { useState } from "react";

const Index = () => {
    const { errors } = usePage().props

    const [values, setValues] = useState({
        title: "",
    })

    function handleChange(e) {
        const { id, type, value, checked } = e.target;
        setValues((values) => ({
            ...values,
            [id]: type === 'checkbox' ? checked : value,
        }));
    }

    return (
        <App>
            <div className="max-w-5xl mx-auto">
                <div className="border bg-white rounded-xl shadow p-5 mb-6 text-xl font-bold text-center">
                    Profile
                </div>

                <div class="py-4 md:py-8 bg-white p-8">
                    <div class="mb-4 grid gap-4 sm:grid-cols-2 sm:gap-8 lg:gap-16">
                        <div class="space-y-4">
                            <div className="flex justify-between">
                                <div class="flex space-x-4">
                                    <img class="h-16 w-16 rounded-lg" src="https://flowbite.s3.amazonaws.com/blocks/marketing-ui/avatars/helene-engels.png" alt="Helene avatar" />
                                    <div>
                                        <span class="mb-2 inline-block rounded bg-primary-100 px-2.5 py-0.5 text-xs font-medium text-white bg-secondary">Verified</span>
                                        <h2 class="flex items-center text-xl font-bold leading-none text-gray-900 dark:text-white sm:text-2xl">Helene Engels</h2>
                                    </div>
                                </div>
                                <div>
                                    <div className="max-w-72">
                                        <button className="bg-primary text-xs shadow text-white font-medium  px-4 py-2 rounded-lg w-full">
                                            <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24" transform="" id="injected-svg">
                                                <path d="M19.07 4.93a9.9 9.9 0 0 0-3.18-2.14 10.12 10.12 0 0 0-7.79 0c-1.19.5-2.26 1.23-3.18 2.14S3.28 6.92 2.78 8.11A9.95 9.95 0 0 0 1.99 12h2c0-1.08.21-2.13.63-3.11.4-.95.98-1.81 1.72-2.54.73-.74 1.59-1.31 2.54-1.71 1.97-.83 4.26-.83 6.23 0 .95.4 1.81.98 2.54 1.72.17.17.33.34.48.52L16 9.01h6V3l-2.45 2.45c-.15-.18-.31-.36-.48-.52M19.37 15.11c-.4.95-.98 1.81-1.72 2.54-.73.74-1.59 1.31-2.54 1.71-1.97.83-4.26.83-6.23 0-.95-.4-1.81-.98-2.54-1.72-.17-.17-.33-.34-.48-.52l2.13-2.13H2v6l2.45-2.45c.15.18.31.36.48.52.92.92 1.99 1.64 3.18 2.14 1.23.52 2.54.79 3.89.79s2.66-.26 3.89-.79c1.19-.5 2.26-1.23 3.18-2.14s1.64-1.99 2.14-3.18c.52-1.23.79-2.54.79-3.89h-2c0 1.08-.21 2.13-.63 3.11Z" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <UserProfileList title="Email Address" description="helene@example.com" />
                            <UserProfileList title="Email Address" description="helene@example.com" />
                            <UserProfileList title="Email Address" description="helene@example.com" />

                        </div>
                        <div class="space-y-4">
                            <UserProfileList title="Email Address" description="helene@example.com" />
                            <UserProfileList title="Email Address" description="helene@example.com" />
                            <UserProfileList title="Email Address" description="helene@example.com" />
                            <UserProfileList title="Email Address" description="helene@example.com" />


                        </div>
                    </div>

                </div>
            </div>
        </App>
    )
}

export default Index