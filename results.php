<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Check Enrollment Status - FFCS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="css/common.css">
    <link rel="stylesheet" href="css/results.css">
    <link href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;900&display=swap" rel="stylesheet">
</head>
<body class="kinderly-page-body">

<nav class="navbar navbar-expand-lg">
  <div class="container"> <a class="navbar-brand" href="index.php">
  <img src="FFCS Pics/logo_monte_cristo.jpg" alt="Monte Cristo Logo">
</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNavContent" aria-controls="navbarNavContent" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNavContent">
      <ul class="navbar-nav mx-auto">
        <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
        <li class="nav-item"><a class="nav-link" href="about.php">About Us</a></li>
        <li class="nav-item"><a class="nav-link" href="programs.php">Programs</a></li>
        <li class="nav-item"><a class="nav-link" href="enrollment.php">Admissions</a></li>
        <li class="nav-item"><a class="nav-link active" href="results.php">Status</a></li>
        <li class="nav-item"><a class="nav-link" href="news&events.php">News & Events</a></li>
        <li class="nav-item"><a class="nav-link" href="contact.php">Contact Us</a></li>
        </ul>
      </div>
  </div>
</nav>

    <main class="status-check-main-section py-5">
        <div class="container">
            <div class="status-check-container card-style p-4 p-md-5">
                <h2 class="kinderly-main-title text-center mb-4">Check Enrollment Status</h2>
                
                <!-- Payment Status Message Area -->
                <div id="payment-status-message" class="mb-4"></div>

                <p class="status-check-instructions text-center mb-4">
                    Enter your Learner Reference Number (LRN) or Temporary Reference Number to check your application status.
                </p>

                <form class="status-check-form" id="statusForm">
                    <div class="form-group mb-3">
                        <label for="lrnToFind" class="form-label">Learner Reference Number (LRN)</label>
                        <input type="text" id="lrnToFind" name="lrn" class="form-control form-control-lg" placeholder="Enter your LRN or Temporary Number..." required>
                    </div>
                    <div class="text-center">
                        <button type="submit" id="check-status-btn" class="btn btn-custom-kinderly explore btn-lg px-5">Check Status</button>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <!-- Updated Modal Structure -->
    <div id="statusModal" class="modal-overlay">
        <div class="modal-content-viewer">
            <button id="closeStatusModal" class="modal-close-button" aria-label="Close modal">&times;</button>
            <div id="modalStatusContent">
                <!-- Dynamic Content (Pages) will be injected here by JavaScript -->
            </div>
            <div id="modal-navigation" class="modal-navigation" style="display: none;">
                <button id="modal-prev-btn" class="btn btn-secondary">Previous</button>
                <span id="modal-page-indicator"></span>
                <button id="modal-next-btn" class="btn btn-primary">Next</button>
            </div>
        </div>
    </div>
    <!-- End of Modal Structure -->


<?php include 'footer.php'; ?>
        <div class="floating-shape" style="top: 10%; left: 5%; animation-delay: 0s;"></div>
        <div class="floating-shape" style="top: 30%; left: 20%; animation-delay: 1s;"></div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
    <script src="js/results.js"></script>

    <script>
      AOS.init();
    </script>
</body>
</html>
