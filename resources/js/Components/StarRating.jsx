const StarRating = ({ value, size = "24px" }) => {
    const stars = Array(5).fill(0).map((_, index) => {
        return (
            <box-icon
                key={index}
                type="solid"
                name="star"
                color={index < value ? '#facc15' : '#d1d5db'} // Kuning jika index < value, abu-abu jika tidak
                size={size}
            />
        );
    });

    return <div className="flex gap-0.5">{stars}</div>;
}

export default StarRating