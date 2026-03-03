<?php
// submit_feedback.php
require_once 'api/db_connect.php'; // For navbar, footer, and later processing if combined
$page_title = "Submit Parent Feedback"; // You can use this in a common header if you have one

// Message handling (from GET parameters after submission attempt)
$feedback_submission_message = '';
$feedback_submission_type = ''; // 'success' or 'error'

if (isset($_GET['status'])) {
    if ($_GET['status'] === 'success') {
        $feedback_submission_message = "Thank you! Your feedback has been submitted and is awaiting review.";
        $feedback_submission_type = 'success';
    } elseif ($_GET['status'] === 'error') {
        $feedback_submission_message = "There was an error submitting your feedback. Please try again. " . ($_GET['message'] ?? '');
        $feedback_submission_type = 'danger';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= $page_title ?> - FFCS</title>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;700;900&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="css/common.css">
    <link rel="stylesheet" href="css/index.css"> <style>
        .feedback-form-container {
            max-width: 700px;
            margin: 40px auto;
            padding: 30px;
            background-color: #f7fbff; /* Light accent bg from enrollment.css */
            border-radius: 15px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
            border: 1px solid #d0d9e8; /* Border color from enrollment.css */
        }
        .feedback-form-container h2 {
            color: #001133; /* Dark blue from index.css */
            margin-bottom: 1.5rem;
        }
        .rating-stars label {
            font-size: 2rem;
            color: #ddd; /* Light grey for empty stars */
            cursor: pointer;
            transition: color 0.2s;
        }
        .rating-stars input[type="radio"] {
            display: none; /* Hide the actual radio buttons */
        }
        .rating-stars input[type="radio"]:checked ~ label, /* Style checked star and those before it */
        .rating-stars label:hover,
        .rating-stars label:hover ~ label { /* Style hovered star and those before it */
            color: #ffc843 !important; /* Accent yellow from enrollment.css */
        }
        /* To make stars fill from right to left on hover/check */
        .rating-stars {
            display: inline-block;
            direction: rtl;
        }
        .rating-stars label:hover,
        .rating-stars label:hover ~ label,
        .rating-stars input[type="radio"]:checked ~ label {
            color: #ffc843 !important;
        }
        .btn-submit-feedback {
            background-color: #00c853; /* Green from enrollment.css */
            color: white;
            font-weight: bold;
            border-radius: 50px;
            padding: 10px 30px;
        }
        .btn-submit-feedback:hover {
            background-color: #00a040;
        }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand" href="index.php">FFCS</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNavContent" aria-controls="navbarNavContent" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNavContent">
                <ul class="navbar-nav mx-auto">
                    <li class="nav-item"><a class="nav-link" href="about.php">About Us</a></li>
                    <li class="nav-item"><a class="nav-link" href="programs.php">Programs</a></li>
                    <li class="nav-item"><a class="nav-link" href="enrollment.php">Admissions</a></li>
                    <li class="nav-item"><a class="nav-link" href="results.php">Status</a></li>
                    <li class="nav-item"><a class="nav-link" href="news&events.php">News & Events</a></li>
                    <li class="nav-item"><a class="nav-link" href="contact.php">Contact Us</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="feedback-form-container">
            <h2 class="text-center">Share Your Feedback</h2>
            <p class="text-center text-muted mb-4">We value your opinion! Please let us know about your experience with FFCS.</p>

            <?php if ($feedback_submission_message): ?>
                <div class="alert alert-<?= htmlspecialchars($feedback_submission_type) ?> text-center" role="alert">
                    <?= htmlspecialchars($feedback_submission_message) ?>
                </div>
            <?php endif; ?>

            <form action="api/feedback/submit_parent_feedback.php" method="POST" enctype="multipart/form-data">
                <div class="mb-3">
                    <label for="parent_name" class="form-label">Your Name (Parent/Guardian) <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="parent_name" name="parent_name" required>
                </div>

                <div class="mb-3">
                    <label for="feedback_text" class="form-label">Your Feedback <span class="text-danger">*</span></label>
                    <textarea class="form-control" id="feedback_text" name="feedback_text" rows="5" required></textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label d-block">Overall Rating (Optional)</label>
                    <div class="rating-stars">
                        <input type="radio" id="star5" name="rating" value="5" /><label for="star5" title="5 stars"><i class="fas fa-star"></i></label>
                        <input type="radio" id="star4" name="rating" value="4" /><label for="star4" title="4 stars"><i class="fas fa-star"></i></label>
                        <input type="radio" id="star3" name="rating" value="3" /><label for="star3" title="3 stars"><i class="fas fa-star"></i></label>
                        <input type="radio" id="star2" name="rating" value="2" /><label for="star2" title="2 stars"><i class="fas fa-star"></i></label>
                        <input type="radio" id="star1" name="rating" value="1" /><label for="star1" title="1 star"><i class="fas fa-star"></i></label>
                    </div>
                </div>

                <div class="mb-4">
                    <label for="profile_image" class="form-label">Upload Profile Picture (Optional)</label>
                    <input type="file" class="form-control" id="profile_image" name="profile_image" accept="image/jpeg, image/png">
                    <small class="form-text text-muted">Max file size: 2MB. Accepted formats: JPG, PNG.</small>
                </div>

                <div class="text-center">
                    <button type="submit" class="btn btn-submit-feedback">Submit Feedback</button>
                </div>
            </form>
            <div class="text-center mt-3">
                <a href="index.php#parents-feedback-section">View Other Feedback</a> |
                <a href="index.php">Back to Home</a>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    </body>
</html>