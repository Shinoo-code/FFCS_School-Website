<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Contact Us - FFCS</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="css/common.css">
  <link rel="stylesheet" href="css/contact.css" />
</head>
<body>
<nav class="navbar navbar-expand-lg">
  <div class="container"> <a class="navbar-brand" href="index.php">
  <img src="FFCS Pics/FFCS_Logo(clean).png" alt="Monte Cristo Logo">
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
        <li class="nav-item"><a class="nav-link active" href="contact.php">Contact Us</a></li>
        </ul>
      </div>
  </div>
</nav>

  <?php
include 'api/db_connect.php';

$contactStmt = $pdo->query("SELECT * FROM contact_info");


?>

<section class="contact-us-section py-5 position-relative" data-aos="fade-down">
  <div class="container">
    <h2 class="text-center mb-5">Contact us</h2>
    <div class="row justify-content-center g-4 align-items-stretch">
    <?php while ($row = $contactStmt->fetch()): ?>
      <div class="col-md-4 d-flex">
        <div class="info-box w-100">
          <i class="<?= htmlspecialchars($row['icon_class']) ?>"></i>
          <h5 class="fw-bold"><?= htmlspecialchars($row['label']) ?></h5>
          <p class="mb-0"><?= nl2br(htmlspecialchars($row['value'])) ?></p>
        </div>
      </div>
    <?php endwhile; ?>
  </div>
  </div>
</section>

  <!-- Locations -->
  <section class="locations-section position-relative" data-aos="fade-down">
    <div class="container">
      <h3 class="text-center mb-5">School <strong>Locations</strong></h3>
      <div class="row g-4 justify-content-center">
        <div class="col-md-6">
          <div class="map-card">
            <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3866.5016792341594!2d120.9785604!3d14.282258800000001!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3397d5ae212db4f7%3A0xef76dd35b084075d!2sFaith%20Family%20Christian%20School!5e0!3m2!1sen!2sph!4v1772507915195!5m2!1sen!2sph" width="400" height="300" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
            <h5 class="mt-3">Faith Family Christian School</h5>
            <p>Blk 1 Lot 2, City View, Piela Bridge, Sampaloc 3, Dasmariñas, 4114 Cavite</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Floating Shapes -->
    <div class="floating-shape" style="top: 10%; left: 5%; color: red;">★</div>
    <div class="floating-shape" style="top: 20%; left: 20%; color: blue;">✿</div>
    <div class="floating-shape" style="top: 30%; left: 75%; color: green;">◆</div>
    <div class="floating-shape" style="top: 40%; left: 10%; color: orange;">✧</div>
    <div class="floating-shape" style="top: 50%; left: 50%; color: pink;">✹</div>
    <div class="floating-shape" style="top: 60%; left: 80%; color: teal;">✶</div>
    <div class="floating-shape" style="top: 70%; left: 15%; color: purple;">❖</div>
    <div class="floating-shape" style="top: 75%; left: 65%; color: coral;">✺</div>
    <div class="floating-shape" style="top: 80%; left: 35%; color: lime;">✾</div>
    <div class="floating-shape" style="top: 85%; left: 90%; color: brown;">⚝</div>
  </section>


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
  <script src="js/contact.js"></script>

</body>
</html>
