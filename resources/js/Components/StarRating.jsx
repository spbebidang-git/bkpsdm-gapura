const StarRating = ({ value }) => {
    const stars = Array(5).fill(0).map((_, index) => {
        return (
            <box-icon
                key={index}
                type="solid"
                name="star"
                color={index < value ? '#facc15' : '#d1d5db'} // Kuning jika index < value, abu-abu jika tidak
            />
        );
    });

    return <div className="flex">{stars}</div>;
}

export default StarRating