<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Programs | FFCS</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css" rel="stylesheet">
  <link rel="stylesheet" href="css/common.css"> <link rel="stylesheet" href="css/programs.css" /> </head>
<body>

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
        <li class="nav-item"><a class="nav-link active" href="programs.php">Programs</a></li> <li class="nav-item"><a class="nav-link" href="enrollment.php">Admissions</a></li>
        <li class="nav-item"><a class="nav-link" href="results.php">Status</a></li>
        <li class="nav-item"><a class="nav-link" href="news&events.php">News & Events</a></li>
        <li class="nav-item"><a class="nav-link" href="contact.php">Contact Us</a></li>
      </ul>
    </div>
  </div>
</nav>

  <section class="programs-section py-5"> <div class="decorative-icon icon1">✦</div>
    <div class="decorative-icon icon2">❉</div>
    <div class="decorative-icon icon3">⬤</div>
    <div class="decorative-icon icon4">◆</div>
    <div class="decorative-icon icon5">✧</div>
    <div class="decorative-icon icon6">◯</div>
    <div class="decorative-icon icon7">✺</div>
    <div class="decorative-icon icon8">✱</div>
    <div class="decorative-icon icon9">⬟</div>
    <div class="decorative-icon icon10">★</div>

    <div class="container"> <h2 class="text-center mb-5" data-aos="fade-down">Our Programs</h2>

      <?php
      include 'api/db_connect.php'; // Your PDO connection file

      $programs = []; // Initialize to prevent errors if query fails
      try {
          // Fetch programs ordered by display_order
          $stmt = $pdo->query("SELECT * FROM programs ORDER BY display_order ASC");
          if ($stmt) {
              $programs = $stmt->fetchAll(PDO::FETCH_ASSOC);
          }
      } catch (PDOException $e) {
          error_log("Error fetching programs: " . $e->getMessage());
          // Optionally display a user-friendly error message on the page
          // echo "<p class='text-center text-danger'>Could not load programs at this time. Please try again later.</p>";
      }

      // Helper function to split features string into array
      // Ensure this function is defined only once if multiple includes might happen
      if (!function_exists('getFeaturesArrayPrograms')) {
          function getFeaturesArrayPrograms($featuresString) {
              return explode('|', $featuresString);
          }
      }
      ?>

      <?php if (!empty($programs)): ?>
        <?php foreach ($programs as $index => $program):
          $features = getFeaturesArrayPrograms($program['features'] ?? '');
          // Alternate flex direction for even/odd index
          $flexClass = ($index % 2 == 1) ? 'flex-md-row-reverse' : '';
        ?>
        <div class="row align-items-center mb-5 <?= $flexClass ?>" data-aos="<?= $index % 2 == 0 ? 'fade-right' : 'fade-left' ?>">
          <div class="col-md-6 text-center mb-3 mb-md-0">
            <img src="<?= htmlspecialchars($program['image_path'] ?? 'FFCS Pics/default_program.png') ?>" alt="<?= htmlspecialchars($program['title'] ?? 'Program Image') ?>" class="img-fluid rounded-image" style="max-height: 350px; object-fit: cover;">
          </div>
          <div class="col-md-6">
            <h4><?= htmlspecialchars($program['title'] ?? 'Program Title') ?></h4>
            <p><?= nl2br(htmlspecialchars($program['description'] ?? 'Program description coming soon.')) ?></p>
            <?php if (!empty($features) && !(count($features) === 1 && trim($features[0]) === '')): ?>
              <p><strong>Key features:</strong></p>
              <ul>
                <?php foreach ($features as $feature): ?>
                  <?php if (!empty(trim($feature))): ?>
                    <li><?= htmlspecialchars(trim($feature)) ?></li>
                  <?php endif; ?>
                <?php endforeach; ?>
              </ul>
            <?php endif; ?>
          </div>
        </div>
        <?php endforeach; ?>
      <?php else: ?>
          <p class="text-center">No programs to display at this time.</p>
      <?php endif; ?>
      </div> </section>

  <div class="scroll-section" id="scroll-section"> <img src="FFCS Pics/background.jpg" alt="DepEd Matatag Background" class="rectangular-image"> </div>

<?php include 'footer.php'; ?>
  <div class="floating-shape" style="top: 10%; left: 5%; animation-delay: 0s; opacity:0.2;"></div>
    <div class="floating-shape" style="top: 80%; left: 90%; animation-delay: 2s; opacity:0.2;"></div>

  <button onclick="topFunction()" id="backToTop" title="Go to top">
    <i class="bi bi-arrow-up-short"></i>
  </button>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
  <script src="js/programs.js"></script>
  <script>
    AOS.init({
        duration: 1000, // values from 100 to 3000, with step 50ms
        once: true, // whether animation should happen only once - while scrolling down
    });
  </script>
</body>
</html>