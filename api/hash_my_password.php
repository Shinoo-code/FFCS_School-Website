    <?php
    // hash_my_password.php
    ini_set('display_errors', 1); // Good to have for any script
    error_reporting(E_ALL);

    // IMPORTANT: Change this password to what you want to use for testing.
    $plainPassword = 'FacultyPassword123!'; 

    // Hash the password using PHP's default strong hashing algorithm
    $hashedPassword = password_hash($plainPassword, PASSWORD_DEFAULT);

    if ($hashedPassword === false) {
        echo "Password hashing failed. Check PHP version and configuration.";
    } else {
        echo "Plain Password: " . htmlspecialchars($plainPassword) . "<br><br>";
        echo "Hashed Password (COPY THIS into your database 'password_hash' column): <br>";
        echo "<strong>" . htmlspecialchars($hashedPassword) . "</strong>";
    }
    ?>
    