
const BreadcrumbLearn = ({ title, activeTab, icon }) => {
    // const isActive = activeTab === title;
    return (
        <div
            className={`rounded-full border-2 w-fit px-6 py-1.5 font-semibold flex items-center gap-1 
          ${activeTab ? "bg-teal-50 text-primary border-primary" : "text-gray-500"}`}
        >
            <box-icon name={icon} color={`${!activeTab ? '#64748b' : '#018083'}`}></box-icon>
            {title}
        </div>
    );
};

export default BreadcrumbLearn;


