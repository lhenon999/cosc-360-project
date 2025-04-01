$(document).ready(function() {
    $("#add-review-form").submit(function(e) {
        e.preventDefault();

        var formData = $(this).serialize();

        $.ajax({
            url: 'add_review.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    $("#add-review-form")[0].reset();

                    var review = response.review;
                    var newReviewHtml = '<div class="review mt-2 d-flex flex-column">';
                    newReviewHtml += '<a href="user_profile.php?id=' + review.user_id + '" class="review-user">';
                    newReviewHtml += '<img src="default_profile.png" alt="Profile" class="review-user-img">';
                    newReviewHtml += '<strong>User ' + review.user_id + '</strong>'; 
                    newReviewHtml += '</a>';
                    newReviewHtml += '<div class="review-rating">';
                    for(var i = 1; i <= 5; i++) {
                        newReviewHtml += '<span class="star ' + (i <= review.rating ? 'filled' : '') + '">★</span>';
                    }
                    newReviewHtml += '</div>';
                    newReviewHtml += '<p class="review-comment">' + review.comment + '</p>';
                    newReviewHtml += '</div>';

                    $("#reviews-container").prepend(newReviewHtml);
                } else {
                    alert("Error: " + response.error);
                }
            },
            error: function(xhr, status, error) {
                console.error("AJAX error: " + status + " " + error);
            }
        });
    });
});
