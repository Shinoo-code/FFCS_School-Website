<?php
echo "cURL Status: ";
if (in_array('curl', get_loaded_extensions())) {
    echo "Enabled.<br>";

    $ch = curl_init('https://www.google.com/'); // Try a standard domain
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $result = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    echo "Google.com HTTP Code: " . $http_code . "<br>";
    if ($http_code !== 200) {
         echo "cURL Error: " . $error . "<br>";
         echo "Failure connecting to the outside internet!";
    } else {
         echo "Success connecting to the outside internet!";
    }

} else {
    echo "Disabled. **CRITICAL ERROR** - Chatbot will not work.<br>";
}
?>