<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>About Us</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="css/common.css">
  <link rel="stylesheet" href="css/about.css" />
</head>
<body>

  <!-- Decorative Icons -->
  <div class="decorative-icon icon1">✦</div>
  <div class="decorative-icon icon2">❉</div>
  <div class="decorative-icon icon3">⬤</div>
  <div class="decorative-icon icon4">◆</div>
  <div class="decorative-icon icon5">✧</div>
  

  <!-- Navbar -->
<nav class="navbar navbar-expand-lg">
  <div class="container">
    <a class="navbar-brand" href="index.php">
  <img src="FFCS Pics/logo_monte_cristo.jpg" alt="Monte Cristo Logo">
</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNavContent" aria-controls="navbarNavContent" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNavContent">
      <ul class="navbar-nav mx-auto">
        <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
        <li class="nav-item"><a class="nav-link active" href="about.php">About Us</a></li>
        <li class="nav-item"><a class="nav-link" href="programs.php">Programs</a></li>
        <li class="nav-item"><a class="nav-link" href="enrollment.php">Admissions</a></li>
        <li class="nav-item"><a class="nav-link" href="results.php">Status</a></li>
        <li class="nav-item"><a class="nav-link" href="news&events.php">News & Events</a></li>
        <li class="nav-item"><a class="nav-link" href="contact.php">Contact Us</a></li>
        <li class="nav-item d-lg-none">
        </li>
      </ul>
    </div>
  </div>
</nav>

  <!-- Main Title -->
  <h1 class="main-title" data-aos="fade-down">About us</h1>

  <?php
include 'api/db_connect.php';

$stmt = $pdo->query("SELECT * FROM mission_vision");
$data = [];
while ($row = $stmt->fetch()) {
    $data[$row['type']] = $row['content'];
}
?>
<div class="container" data-aos="fade-down">
  <div class="row text-center mb-5">
    <div class="col-md-6 mb-4">
      <div class="card-style h-100">
        <h6>Our</h6>
        <h3 class="fw-bold">Mission</h3>
        <p><?= htmlspecialchars($data['mission'] ?? 'Mission content coming soon.') ?></p>
      </div>
    </div>
    <div class="col-md-6 mb-4">
      <div class="card-style h-100">
        <h6>Our</h6>
        <h3 class="fw-bold">Vision</h3>
        <p><?= htmlspecialchars($data['vision'] ?? 'Vision content coming soon.') ?></p>
      </div>
    </div>
  </div>
</div>


  <!-- Learning Paths Section -->
 <?php
$stmt = $pdo->query("SELECT * FROM learning_paths ORDER BY year ASC");
?>
<div class="highlight-section" data-aos="fade-down">
  <div class="container text-center">
    <h6>Our</h6>
    <h3 class="fw-bold mb-5">Learning Paths</h3>
    <div class="row g-4">
      <?php while ($row = $stmt->fetch()): ?>
        <div class="col-md-3 col-sm-6">
          <div class="milestone-card h-100">
            <div class="milestone-year"><?= htmlspecialchars($row['year']) ?></div>
            <div class="icon"><?= htmlspecialchars($row['icon']) ?></div>
            <h5 class="fw-boldd"><?= htmlspecialchars($row['title']) ?></h5>
            <p><strong>Milestone:</strong><br><?= htmlspecialchars($row['description']) ?></p>
          </div>
        </div>
      <?php endwhile; ?>
    </div>
  </div>
</div>

    </div>
  </div>

  <link href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css" rel="stylesheet">

<!-- Before </body> -->
<script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>

  <!-- DEPED MATATAG -->
<div class="scroll-section" id="scroll-section">
    <img src="/FFCS Pics/background.jpg" alt="Rectangular Image" class="rectangular-image">
  </div>
  
    <!-- Footer -->
<?php include 'footer.php'; ?>

    <!-- Floating Decorative Shapes -->
<div class="floating-shape" style="top: 10%; left: 5%; animation-delay: 0s;"></div>
<div class="floating-shape" style="top: 30%; left: 20%; animation-delay: 1s;"></div>
<div class="floating-shape" style="top: 50%; left: 80%; animation-delay: 2s;"></div>
<div class="floating-shape" style="top: 70%; left: 40%; animation-delay: 3s;"></div>
<div class="floating-shape" style="top: 20%; left: 60%; animation-delay: 4s;"></div>
<div class="floating-shape" style="top: 60%; left: 15%; animation-delay: 5s;"></div>
<div class="floating-shape" style="top: 80%; left: 75%; animation-delay: 6s;"></div>
<div class="floating-shape" style="top: 25%; left: 35%; animation-delay: 7s;"></div>
<div class="floating-shape" style="top: 45%; left: 90%; animation-delay: 8s;"></div>
<div class="floating-shape" style="top: 65%; left: 10%; animation-delay: 9s;"></div>
  
  <!-- Back to Top Button -->
  <button onclick="topFunction()" id="backToTop" title="Go to top">
    <i class="bi bi-arrow-up-short"></i>
  </button>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="js/about.js"></script>

</body>
</html>
