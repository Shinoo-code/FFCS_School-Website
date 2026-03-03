<?php
// footer.php
// This file can be included at the bottom of your PHP pages.
?>
<footer class="site-footer">
    <div class="container py-4">
        <div class="row gy-4">
            <div class="col-lg-4 col-md-12 footer-col-school-info">
                <h5 class="footer-school-name mb-2">FFCS Dasmariñas</h5>
                <p class="footer-description mb-3">Every moment in your child's early years are crucial for their growth and development.</p>
                <div class="d-flex gap-2 footer-social-icons">
                    <a href="https://www.facebook.com/FaithFamilyChristianSchool" class="btn btn-light btn-sm rounded-circle" target="_blank" rel="noopener noreferrer" aria-label="Facebook"><i class="bi bi-facebook"></i></a>
                    <a href="mailto:faithfamilychristianschool@gmail.com" class="btn btn-light btn-sm rounded-circle" aria-label="Email"><i class="bi bi-envelope"></i></a>
                </div>
            </div>

            <div class="col-lg-5 col-md-6 footer-col-quick-links">
                <div class="row">
                    <div class="col-6">
                        <h6 class="footer-heading mb-2">Quick Links</h6>
                        <ul class="list-unstyled mb-0 footer-links-list">
                            <li><a href="index.php">Home</a></li>
                            <li><a href="about.php">About Us</a></li>
                            <li><a href="programs.php">Programs</a></li>
                            <li><a href="enrollment.php">Admissions</a></li>
                            <li><a href="login.php">Login</a></li>
                        </ul>
                    </div>
                    <div class="col-6">
                        <h6 class="footer-heading mb-2 invisible">Quick Links Continued</h6>
                        <ul class="list-unstyled mb-0 footer-links-list">
                            <li><a href="news&events.php">News & Events</a></li>
                            <li><a href="contact.php">Contact</a></li>
                            <li><a href="results.php">Check Status</a></li>
                            <li><a href="quiz.php">Quiz</a></li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6 footer-col-legal-links">
                <h6 class="footer-heading mb-2">Legal</h6>
                <ul class="list-unstyled mb-0 footer-links-list">
                    <li><a href="privacy.php">Privacy Policy</a></li>
                    <li><a href="terms&conditions.php">Terms and Conditions</a></li>
                </ul>
            </div>
        </div>

        <hr class="footer-divider my-3" />

        <div class="text-center footer-copyright small">
            <span>&copy; <?php echo date("Y"); ?> FFCS. All rights reserved.</span>
        </div>
    </div>
</footer>

<div id="chatbot-widget">
    
    <div id="chat-window">
        <div class="chat-header">
            FFCS Info-Bot
        </div>
        <div id="chat-body">
            </div>
        <div class="chat-footer">
            <input type="text" id="chat-input" placeholder="Ask a question..." autocomplete="off">
            <button id="chat-send-btn"><i class="bi bi-send"></i></button>
        </div>
    </div>

    <div id="chatbot-toggle">
        <i class="bi bi-chat-dots-fill"></i>
    </div>
</div>
<script src="js/chatbot.js"></script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
<script>
    // Initialize AOS if used on the page
    if (typeof AOS !== 'undefined') {
        AOS.init({
            duration: 1000,
            once: true
        });
    }

    // All "Back to Top" functionality has been removed.
</script>