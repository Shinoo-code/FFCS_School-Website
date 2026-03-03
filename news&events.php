<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>News & Events</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link rel="stylesheet" href="css/common.css">
  <link rel="stylesheet" href="css/news&events.css" />
</head>
<body>
  <!-- Decorative Shapes -->
  <div class="decorative-icon icon1">✦</div>
  <div class="decorative-icon icon2">❉</div>
  <div class="decorative-icon icon3">⬤</div>
  <div class="decorative-icon icon4">◆</div>
  <div class="decorative-icon icon5">✧</div>
  <div class="decorative-icon icon6">✶</div>
  <div class="decorative-icon icon7">✹</div>
  <div class="decorative-icon icon8">⯁</div>
  <div class="decorative-icon icon9">★</div>
  <div class="decorative-icon icon10">⬟</div>

  <!-- Navbar -->
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
        <li class="nav-item"><a class="nav-link" href="results.php">Status</a></li>
        <li class="nav-item"><a class="nav-link" href="news&events.php">News & Events</a></li>
        <li class="nav-item"><a class="nav-link" href="contact.php">Contact Us</a></li>
      </ul>
      </div>
  </div>
</nav>

  <!-- Main Title -->
  <h1 class="main-title" data-aos="fade-down">News & Events</h1>

 <?php
include 'api/db_connect.php'; // This assumes your PDO object is created in this file

$stmt = $pdo->query("SELECT * FROM news ORDER BY date DESC LIMIT 6");
?>
<div class="container pb-5" data-aos="fade-down">
  <div class="row g-4 justify-content-center">
    <?php while ($row = $stmt->fetch()): ?>
      <div class="col-md-4">
        <div class="news-card">
          <img src="<?= htmlspecialchars($row['image_path']) ?>" alt="News">
          <div class="date"><?= date('F j, Y', strtotime($row['date'])) ?></div>
          <h5><?= htmlspecialchars($row['title']) ?></h5>
          <p><?= htmlspecialchars($row['description']) ?></p>
        </div>
      </div>
    <?php endwhile; ?>
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
  <script src="js/news&events.js"></script>
</body>
</html>
