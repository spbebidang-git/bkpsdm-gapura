
const BreadcrumbLearn = ({ title, activeTab, icon, size = 'md' }: { title: string; activeTab?: boolean; icon?: string; size?: 'md' | 'lg' }) => {
    const isLg = size === 'lg';
    return (
        <span
            className={`flex items-center gap-2 transition-colors duration-300 capitalize
          ${isLg ? 'text-base md:text-[17px]' : 'text-sm md:text-[15px]'}
          ${activeTab
                    ? "text-primary font-bold tracking-wide"
                    : "text-gray-500 font-medium hover:text-primary"}`}
        >
            {icon && <box-icon name={icon} color={activeTab ? '#018083' : '#9ca3af'} size={isLg ? '22px' : '20px'}></box-icon>}
            {title}
        </span>
    );
};

export default BreadcrumbLearn;
