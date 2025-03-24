document.addEventListener("DOMContentLoaded", function () {
    const form = document.querySelector(".add-review-form");
    const commentField = document.getElementById("comment");
    const ratingFields = document.querySelectorAll('input[name="rating"]');
    const commentError = document.getElementById("commentError");
    const ratingError = document.getElementById("ratingError");

    commentError.classList.add("error-message", "text-danger");
    ratingError.classList.add("error-message", "text-danger");

    form.addEventListener("submit", function (event) {
        let isValid = true;
        commentError.textContent = "";
        ratingError.textContent = "";

        if (commentField.value.trim().length < 10) {
            commentError.textContent = "Comment must be at least 10 characters long.";
            isValid = false;
        }

        const selectedRating = document.querySelector('input[name="rating"]:checked');
        if (!selectedRating) {
            ratingError.textContent = "Please select a rating.";
            isValid = false;
        }

        if (!isValid) {
            event.preventDefault();
        }
    });

    commentField.addEventListener("input", function () {
        if (commentField.value.trim().length >= 10) {
            commentError.textContent = "";
        }
    });

    ratingFields.forEach((radio) => {
        radio.addEventListener("change", function () {
            ratingError.textContent = "";
        });
    });
});