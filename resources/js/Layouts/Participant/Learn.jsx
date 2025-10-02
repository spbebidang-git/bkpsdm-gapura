import BreadcrumbLearn from "@/Components/BreadcrumbLearn";
import CountdownTimer from "@/Components/CountDownTimer";
import ListActivity from "@/Components/ListActivity";
import { Link, router, usePage } from "@inertiajs/react";
import React, { useState } from "react";

const Learn = ({
    type,
    course,
    progress,
    activity,
    children,
    activities,
    timer,
    activity_id,
    formattedAnswers,
    schedule
}) => {
    const { site } = usePage().props;
    const { errors } = usePage().props;

    const [isSidebarVisible, setSidebarVisible] = useState(true);
    const [isSidebarContentVisible, setSidebarContentVisible] = useState(true);

    const toggleSidebar = () => {
        if (isSidebarVisible) {
            setSidebarVisible(false);
            setTimeout(() => {
                setSidebarContentVisible(false);
            }, 300); // harus sama dengan durasi animasi
        } else {
            setSidebarContentVisible(true);
            setSidebarVisible(true);
        }
    };

    const handleTimeout = () => {
        router.post(
            route("participant.learn.store", {
                course: course.slug,
                schedule: schedule.id,
                activity: activity_id,
            }),
            formattedAnswers,
            {
                onError: (errors) => {
                    toast.error(errors.error, {
                        position: "top-center",
                        autoClose: 5000,
                        hideProgressBar: false,
                        closeOnClick: true,
                        pauseOnHover: true,
                        draggable: true,
                        theme: "colored",
                    });
                },
            }
        );
    };

    return (
        <div className="flex h-screen min-h-screen flex-col">
            <div className="flex flex-grow flex-col lg:flex-row">
                {/* Bagian Kiri */}
                <div
                    className={`flex h-screen flex-grow flex-col bg-teal-50 transition-all duration-1000 ease-in-out ${isSidebarVisible ? "lg:w-3/4" : "w-full"
                        }`}
                >
                    <div className="h-14 p-5 bg-white shadow flex items-center justify-between">
                        <div className="flex gap-5 items-center">
                            <div className="flex items-center mt-1 xl:mt-0">
                                <div className="w-8 h-8 flex items-center justify-center">
                                    <Link href={`/my-course`} className="flex">
                                        <box-icon
                                            name="left-arrow-circle"
                                            size="md"
                                        ></box-icon>
                                    </Link>
                                </div>
                            </div>
                            <div className="h-9 flex-1">
                                <img
                                    src={site.logo}
                                    alt=""
                                    className="w-full h-full object-contain"
                                />
                            </div>
                            <div className="text-xl font-bold text-gray-700 hidden xl:block">
                                {course.title}
                            </div>
                        </div>
                        <div className="text-xl font-bold text-gray-800 flex items-center gap-1">
                            {/* Tombol Toggle */}
                            <div className="p-2">
                                <button
                                    className="bg-primary text-sm shadow text-white font-medium  px-5 py-2 rounded-full w-full"
                                    onClick={toggleSidebar}
                                >
                                    {isSidebarVisible
                                        ? "Sembunyikan Kegiatan →"
                                        : "← Tampilkan Kegiatan"}
                                </button>
                            </div>

                            <box-icon
                                name="hourglass-top"
                                type="solid"
                                color="#018083"
                            ></box-icon>
                            {progress}
                        </div>
                    </div>


                    {/* Konten */}
                    <div className="flex-1 m-5 xl:m-5 rounded-xl shadow bg-white overflow-hidden">
                        <div className="h-[74px] bg-orange-50 flex items-center px-6 justify-between">
                            <div className="xl:text-xl font-bold text-gray-800 capitalize">
                                {activity.title}
                            </div>
                            {(activity.type === "pre-test" || activity.type === "post-test") &&
                                type === "material" ? (
                                <CountdownTimer
                                    initialSeconds={timer}
                                    onTimeout={handleTimeout}
                                />
                            ) : (
                                ""
                            )}
                        </div>

                        <div className="mx-2 my-4 xl:m-6 h-full flex flex-col border-b">
                            <div className="flex gap-2.5 mb-4">
                                <BreadcrumbLearn
                                    title="Instruksi"
                                    activeTab={type === "instruction"}
                                    icon="book-bookmark"
                                />
                                <BreadcrumbLearn
                                    title="Materi"
                                    activeTab={type === "material"}
                                    icon="book-bookmark"
                                />
                                <BreadcrumbLearn
                                    title="Hasil"
                                    activeTab={type === "result"}
                                    icon="book-bookmark"
                                />
                            </div>

                            <div className="flex-1">
                                {React.Children.map(children, (child) =>
                                    child
                                        ? React.cloneElement(child, {
                                            onTimeout: handleTimeout,
                                        })
                                        : child
                                )}
                            </div>
                        </div>
                    </div>
                </div>

                {/* Sidebar Kegiatan */}
                <div
                    className={`transition-all duration-300 ease-in-out bg-white border-l ${isSidebarVisible ? "max-w-sm w-full" : "max-w-0 w-0"
                        } overflow-hidden`}
                >
                    {isSidebarContentVisible && (
                        <>
                            <div className="text-gray-800 bg-white flex justify-center items-center font-bold text-xl h-14 cursor-pointer">
                                Agenda Kegiatan
                            </div>
                            <div
                                className="flex-1 overflow-y-auto border-t"
                                style={{ maxHeight: "calc(100vh - 56px)" }}
                            >
                                <div className="bg-white p-5">
                                    <div className="text-lg font-medium">
                                        <ul className="list-outside list-decimal">
                                            {activities &&
                                                activities.map((item, index) => (
                                                    <ListActivity
                                                        key={index}
                                                        type={item.type}
                                                        slug={course.slug}
                                                        activityId={item.id}
                                                        activity={item.title}
                                                        status={item.status}
                                                        isActive={
                                                            item.id === activity.activity_id
                                                                ? 1
                                                                : 0
                                                        }
                                                        value={item.value}
                                                        schedule={schedule}
                                                    />
                                                ))}
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </>
                    )}
                </div>
            </div>
        </div>
    );
};

export default Learn;
