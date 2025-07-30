// resources/js/Components/UserProfileList.jsx


export default function UserProfileList({ title, description }) {
    return (
        <dl>
            <dt className="font-semibold text-gray-900 dark:text-white">
                {title}
            </dt>
            <dd className="text-gray-500 dark:text-gray-400">
                {description}
            </dd>
        </dl>
    );
}
