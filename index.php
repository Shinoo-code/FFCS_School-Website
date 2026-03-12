<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Faith Family Christian School</title>
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;900&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <link href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css" rel="stylesheet">
  <link rel="stylesheet" href="css/common.css">
  <link rel="stylesheet" href="css/index.css" />
</head>
<body>
<div class="decorative-icon icon1">⬤</div>
<div class="decorative-icon icon2">✶</div>
<div class="decorative-icon icon3">◆</div>
<div class="decorative-icon icon4">✧</div>
<div class="decorative-icon icon5">⬥</div>


  <nav class="navbar navbar-expand-lg">
      <div class="container">
          <a class="navbar-brand" href="index.php">
              <img src="FFCS Pics/FFCS_Logo(clean).png" alt="FFCS Logo">
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

  <div class="container-fluid py-0" data-aos="fade-down">
    <?php
    // DATABASE CONNECTION
    require_once 'api/db_connect.php'; // Ensures $pdo is available

    // Initialize arrays to prevent errors if queries fail
    $hero = [];
    $welcome = [];
    $activities = [];
    $categories = [];
    $announcements = [];
    $faqs = [];
    $all_feedbacks = []; // Changed variable name for clarity

    try {
        // FETCH HERO SECTION DATA
        $stmt_hero = $pdo->query("SELECT * FROM hero_section LIMIT 1");
        $hero_data = $stmt_hero->fetch(PDO::FETCH_ASSOC);
        $hero = $hero_data ?: [
            'heading' => 'Welcome to <strong>FFCS</strong>', // Adjusted default
            'subheading' => 'Your Child\'s Bright Future Starts Here. Nurturing young minds for a better tomorrow.',
            'years_excellence' => '0+',
            'students_enrolled' => '0+',
            'image_path' => 'FFCS Pics/default_hero.jpg'
        ];

        // FETCH WELCOME SECTION DATA
        $stmt_welcome = $pdo->query("SELECT * FROM welcome_section WHERE id = 1");
        $welcome_data = $stmt_welcome->fetch(PDO::FETCH_ASSOC);
        $welcome = $welcome_data ?: [
            'welcome_image_path' => 'FFCS Pics/default_welcome.png',
            'welcome_title' => 'Welcome!',
            'welcome_subtitle' => 'To Our Amazing School',
            'welcome_paragraph' => 'Learn more about our programs and community.',
            'reason1_title' => 'Quality Education', 'reason1_desc' => 'Experienced teachers and a robust curriculum.',
            'reason2_title' => 'Supportive Community', 'reason2_desc' => 'A nurturing environment for every child.',
            'reason3_title' => 'Holistic Development', 'reason3_desc' => 'Focusing on academic, social, and emotional growth.',
        ];

        // FETCH ACTIVITIES & CATEGORIES (Using new date columns)
        $stmt_activities = $pdo->query("SELECT a.id, a.title, a.description, a.start_date, a.category, a.image_path, c.name as category_name
                                          FROM activities a
                                          LEFT JOIN categories c ON a.category = c.slug
                                          ORDER BY a.start_date DESC, a.id DESC"); // Order by date
        $activities = $stmt_activities->fetchAll(PDO::FETCH_ASSOC);

        $stmt_categories = $pdo->query("SELECT * FROM categories WHERE slug != 'all' ORDER BY name ASC");
        $categories = $stmt_categories->fetchAll(PDO::FETCH_ASSOC);

  // FETCH ANNOUNCEMENTS (load all - no artificial limit)
  $stmt_announcements = $pdo->query("SELECT * FROM announcements ORDER BY date_posted DESC");
        $announcements = $stmt_announcements->fetchAll(PDO::FETCH_ASSOC);

        // FETCH FAQS
        $stmt_faqs = $pdo->query("SELECT * FROM faqs ORDER BY id DESC"); // Changed order
        $faqs = $stmt_faqs->fetchAll(PDO::FETCH_ASSOC);

        // FETCH ALL APPROVED PARENT FEEDBACK
        $stmt_feedback_all = $pdo->query("SELECT * FROM parent_feedback WHERE is_approved = 1 ORDER BY date_submitted DESC");
        $all_feedbacks = $stmt_feedback_all->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        error_log("Database connection/query failed in index.php: " . $e->getMessage());
        // Default values are already set for $hero and $welcome.
        // Other arrays will remain empty, and their display loops will handle this gracefully.
    }
    ?>
    <div class="container py-4"> <section class="row align-items-center mb-5">
          <div class="col-lg-6 hero-text">
            <h1><?= $hero['heading'] // Output raw HTML for heading ?></h1>
            <p><?= htmlspecialchars($hero['subheading'] ?? '') // Added ?? '' for safety ?></p>
            <div class="d-flex flex-wrap gap-2 mb-4">
              <button class="btn-custom explore" onclick="window.location.href='programs.php';">Explore Programs <i class="bi bi-chevron-right"></i></button>
              <button class="btn-custom apply" onclick="window.location.href='enrollment.php';">Apply for Admission</button>
            </div>

            <div class="stats-section-custom">
              <div class="row">
                <div class="col-auto">
                  <div class="stat-item">
                    <i class="fas fa-book-open icon"></i> <div class="stat-text-block">
                      <span><strong><?= htmlspecialchars($hero['years_excellence'] ?? '0+') ?></strong></span>
                      <span class="stat-label">Years of Excellence</span>
                    </div>
                  </div>
                </div>
                <div class="col-auto">
                  <div class="stat-item">
                    <i class="fas fa-user-graduate icon"></i> <div class="stat-text-block">
                      <span><strong><?= htmlspecialchars($hero['students_enrolled'] ?? '0+') ?></strong></span>
                      <span class="stat-label">Students Enrolled</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="col-lg-6 mt-4 mt-lg-0 text-center">
            <?php
                $heroImagePath = $hero['image_path'] ?? 'FFCS Pics/default_hero.jpg';
                // Basic check if file exists, otherwise show default
                if (!file_exists($heroImagePath)) {
                    $heroImagePath = 'FFCS Pics/default_hero.jpg';
                }
            ?>
            <img src="<?= htmlspecialchars($heroImagePath) ?>?t=<?= time() // Cache buster ?>" alt="Hero Image" class="img-fluid rounded-4" />
          </div>
        </section>
    </div>
  </div> <div class="section-peach">
    <div class="scroll-banner">
      <div class="scroll-text-wrapper">
        <div class="scroll-text">  
          ⭐ STUDENT SUCCESS! &nbsp; 🧩 FUN ACTIVITIES! &nbsp; 📖 ENROLL TODAY! &nbsp; 🎉 JOIN US! &nbsp; 🧠 GROW WITH US! &nbsp;
        </div>
        <div class="scroll-text">
          ⭐ STUDENT SUCCESS! &nbsp; 🧩 FUN ACTIVITIES! &nbsp; 📖 ENROLL TODAY! &nbsp; 🎉 JOIN US! &nbsp; 🧠 GROW WITH US! &nbsp;
        </div>
      </div>
    </div>
  </div>

<section class="section-welcome-area py-5">
  <div class="container">
    <div class="welcome-block-exact" data-aos="fade-up">
      <div class="welcome-image-container">
         <?php
            $welcomeImagePath = $welcome['welcome_image_path'] ?? 'FFCS Pics/default_welcome.png';
            if (!file_exists($welcomeImagePath)) {
                $welcomeImagePath = 'FFCS Pics/default_welcome.png';
            }
         ?>
        <img src="<?= htmlspecialchars($welcomeImagePath) ?>?t=<?= time() ?>" alt="Welcome Image" class="welcome-person-image-exact" />
      </div>
      <div class="welcome-text-content-exact">
        <i class="bi bi-lightning-charge-fill welcome-icon-exact"></i>
        <h5><?= htmlspecialchars($welcome['welcome_title'] ?? '') ?></h5>
        <h2><?= htmlspecialchars($welcome['welcome_subtitle'] ?? '') ?></h2>
        <p><?= nl2br(htmlspecialchars($welcome['welcome_paragraph'] ?? '')) ?></p>
        <a href="about.php" class="learn-more-link-exact">Learn more &rarr;</a>
      </div>
    </div>

    <div class="learning-paths text-center mt-5" data-aos="fade-up">
      <h2 class="fw-bolddd mb-4">Why Choose Monte Cristo Research & Educational Institute?</h2>
      <div class="row g-4 justify-content-center">
        <div class="col-md-4">
          <div class="learning-card text-center h-100">
            <div style="font-size: 2rem;">🏆</div>
            <h5><?= htmlspecialchars($welcome['reason1_title'] ?? '') ?></h5>
            <p><?= htmlspecialchars($welcome['reason1_desc'] ?? '') ?></p>
          </div>
        </div>
        <div class="col-md-4">
          <div class="learning-card text-center h-100">
            <div style="font-size: 2rem;">👨‍👩‍👧‍👦</div>
            <h5><?= htmlspecialchars($welcome['reason2_title'] ?? '') ?></h5>
            <p><?= htmlspecialchars($welcome['reason2_desc'] ?? '') ?></p>
          </div>
        </div>
        <div class="col-md-4">
          <div class="learning-card text-center h-100">
            <div style="font-size: 2rem;">🌱</div>
            <h5><?= htmlspecialchars($welcome['reason3_title'] ?? '') ?></h5>
            <p><?= htmlspecialchars($welcome['reason3_desc'] ?? '') ?></p>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<div class="container my-5" data-aos="fade-up">
  <h2 class="fw-boldddd text-center mb-4">Our Activities</h2>

  <div class="d-flex flex-wrap justify-content-center gap-2 mb-4">
    <button class="btn btn-sm rounded-pill px-3 py-1 fw-bold text-white" style="background-color:#6e0977;" onclick="filterCards('all')">All Activities</button>
    <?php foreach ($categories as $cat): ?>
      <button class="btn btn-outline-secondary btn-sm rounded-pill px-3 py-1" onclick="filterCards('<?= htmlspecialchars($cat['slug'] ?? '') ?>')">
        <?= htmlspecialchars($cat['name'] ?? '') ?>
      </button>
    <?php endforeach; ?>
  </div>

  <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 g-3 align-items-stretch">
    <?php if (!empty($activities)): ?>
        <?php foreach ($activities as $activity): ?>
          <?php
            // Format the start date for display
            $displayDate = 'Date N/A';
            if (!empty($activity['start_date'])) {
                try {
                    $dateObj = new DateTime($activity['start_date']);
                    $displayDate = $dateObj->format('F j, Y'); // e.g., October 23, 2025
                } catch (Exception $e) {
                    // Handle potential date format errors if needed
                    $displayDate = htmlspecialchars($activity['start_date']); // Show raw if format fails
                }
            }

            // Image path check
            $activityImagePath = 'uploads/activities/' . ($activity['image_path'] ?? '');
            if (empty($activity['image_path']) || !file_exists($activityImagePath)) {
                 $activityImagePath = 'FFCS Pics/placeholder_activity.png'; // Path to a default placeholder image
            }
          ?>
          <div class="col activity-card" data-category="<?= htmlspecialchars($activity['category'] ?? 'uncategorized') ?>">
            <div class="card h-100 border-0 shadow-sm rounded-4 overflow-hidden">
              <img src="<?= htmlspecialchars($activityImagePath) ?>?t=<?= time() // Cache buster ?>" class="card-img-top img-fluid" alt="<?= htmlspecialchars($activity['title'] ?? 'Activity') ?>" style="height: 200px; object-fit: cover;">
              <div class="card-body p-3">
                <small class="text-muted d-block mb-1">
                    <i class="fas fa-calendar-alt me-1"></i><?= $displayDate ?>
                 </small>
                <h6 class="fw-bold mb-2"><?= htmlspecialchars($activity['title'] ?? 'No Title') ?></h6>
                <p class="small mb-0 text-secondary"><?= nl2br(htmlspecialchars($activity['description'] ?? 'No Description')) ?></p>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p class="text-center col-12">No activities to display at the moment.</p>
    <?php endif; ?>
  </div>

    <div class="text-center mt-5 pt-3">
        <a href="quiz.php" class="btn btn-lg btn-custom explore" style="background-color: var(--kinderly-yellow-primary,rgb(247, 234, 183)); color: var(--kinderly-text-dark, #001133); border-color: var(--kinderly-yellow-primary, #ffd24c);">
            <i class="fas fa-gamepad me-2"></i>Try our Fun Quiz Challenge!
        </a>
    </div>
</div>
  <div class="container-fluid px-0" style="background-color:#6e0977; border-top-left-radius: 60px; border-top-right-radius: 60px;">
  <div class="container py-5" data-aos="fade-zoom-in">
    <h2 class="fw-bold text-center mb-4">📢 Announcements</h2>

    <div class="announcements-carousel-container position-relative">
      <button class="announce-nav announce-prev" aria-label="Previous announcement">‹</button>

      <div class="announcements-viewport overflow-hidden">
        <div class="announcements-track d-flex align-items-stretch">
          <?php if (!empty($announcements)): ?>
              <?php foreach ($announcements as $a): ?>
                <div class="announcement-item flex-shrink-0">
                  <div class="p-4 rounded-4 shadow-sm h-100 w-100" style="background-color: #fff8e1;">
                    <h5 class="fw-bold mb-2" style="color:#000000;"><?= htmlspecialchars($a['title'] ?? '') ?></h5>
                    <small class="text-muted d-block mb-2">Posted on: <?= isset($a['date_posted']) ? htmlspecialchars(date('F j, Y', strtotime($a['date_posted']))) : 'N/A' ?></small>
                    <p class="small mb-0 text-secondary"><?= nl2br(htmlspecialchars($a['content'] ?? '')) ?></p>
                  </div>
                </div>
              <?php endforeach; ?>
          <?php else: ?>
              <p class="text-center" style="color: white;">No announcements at the moment.</p>
          <?php endif; ?>
        </div>
      </div>

      <button class="announce-nav announce-next" aria-label="Next announcement">›</button>
    </div>

  </div>
</div>

 <div class="container py-5" data-aos="fade-up">
    <h2 class="fw-bold text-center mb-4" style="color: #000000;">
        📖 Frequently Asked Questions
    </h2>
    <div class="accordion accordion-flush" id="faqAccordion">
        <?php if (!empty($faqs)): ?>
            <?php foreach ($faqs as $index => $faq_item): ?>
                <div class="accordion-item border rounded-4 mb-3">
                    <h2 class="accordion-header" id="faqHeading<?= $index ?>">
                        <button class="accordion-button collapsed rounded-4 fw-semibold" type="button"
                            data-bs-toggle="collapse" data-bs-target="#faqCollapse<?= $index ?>"
                            aria-expanded="false" aria-controls="faqCollapse<?= $index ?>">
                            <?= htmlspecialchars($faq_item["question"] ?? '') ?>
                        </button>
                    </h2>
                    <div id="faqCollapse<?= $index ?>" class="accordion-collapse collapse"
                         aria-labelledby="faqHeading<?= $index ?>" data-bs-parent="#faqAccordion">
                        <div class="accordion-body text-secondary">
                            <?= nl2br(htmlspecialchars($faq_item["answer"] ?? '')) ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="text-center text-muted">No FAQs available at the moment.</p>
        <?php endif; ?>
    </div>
</div>

<div class="container py-5" data-aos="fade-up" id="parents-feedback-section">
    <h2 class="fw-bold text-center mb-4" style="color: #000000;">
        💬 Parent's Feedback
    </h2>

    <?php if (!empty($all_feedbacks)): ?>
        <div class="feedback-carousel-container position-relative">
            <div class="feedback-slider-viewport overflow-hidden">
                <div class="feedback-slider-track d-flex">
                    <?php foreach ($all_feedbacks as $feedback_item): ?>
                        <div class="feedback-card-item">
                            <div class="card h-100 shadow-sm feedback-card mx-2">
                                <div class="card-body text-center">
                                    <?php
                                        $fbImagePath = $feedback_item['profile_image_path'] ?? '';
                                        $fbImageExists = !empty($fbImagePath) && file_exists($fbImagePath);
                                    ?>
                                    <?php if ($fbImageExists): ?>
                                        <img src="<?= htmlspecialchars($fbImagePath) ?>?t=<?= time() ?>" alt="<?= htmlspecialchars($feedback_item['parent_name'] ?? '') ?>" class="feedback-profile-img rounded-circle mb-3">
                                    <?php else: ?>
                                        <div class="feedback-profile-placeholder mb-3">
                                            <i class="fas fa-user-circle fa-3x text-secondary"></i>
                                        </div>
                                    <?php endif; ?>
                                    <p class="card-text feedback-text">"<?= nl2br(htmlspecialchars($feedback_item['feedback_text'] ?? '')) ?>"</p>
                                    <footer class="blockquote-footer mt-3 feedback-author"><?= htmlspecialchars($feedback_item['parent_name'] ?? '') ?></footer>
                                    <?php if (isset($feedback_item['rating']) && is_numeric($feedback_item['rating'])): ?>
                                        <div class="feedback-rating mt-2">
                                            <?php for ($i = 0; $i < 5; $i++): ?>
                                                <i class="fas fa-star <?= $i < intval($feedback_item['rating']) ? 'text-warning' : 'text-muted' ?>"></i>
                                            <?php endfor; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <?php if (count($all_feedbacks) > 3): // Logic to show buttons based on count ?>
                <button class="carousel-control-prev feedback-prev-btn" type="button">
                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">Previous</span>
                </button>
                <button class="carousel-control-next feedback-next-btn" type="button">
                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">Next</span>
                </button>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <p class="text-center text-muted">No parent feedback available at the moment.</p>
    <?php endif; ?>

    <div class="text-center mt-4 pt-3">
        <a href="submit_feedback.php" class="btn btn-custom explore" style="font-size: 1rem; padding: 12px 25px;">
            <i class="fas fa-pencil-alt me-2"></i>Share Your Feedback
        </a>
    </div>
</div>

<div class="scroll-section" id="scroll-section">
  <img src="/FFCS Pics/background.jpg" alt="Rectangular Image" class="rectangular-image">
</div>


<?php include 'footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
  <script src="js/index.js"></script>
  <script>
    // AOS Initialization moved to js/index.js
  </script>

</body>
</html>