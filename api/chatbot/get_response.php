<?php
header('Content-Type: application/json');

$apiKey = 'AIzaSyBtPgfRnnJGnPq3dHoTFxaUF4C6V6Y08-I'; 

$input = json_decode(file_get_contents('php://input'), true);
$user_message = $input['message'] ?? '';

if (empty($user_message)) {
    echo json_encode(['reply' => 'I did not receive a message.']);
    exit;
}

$system_prompt = "You are an AI assistant for a school named 'Faith Family Christian School' (FFCS).
Your name is 'FFCS Chat Bot'. You are kind, helpful, polite, and professional.
Your goal is to answer questions for students and parents.

Here is key information about the school:
- School Name: Faith Family Christian School (FFCS)
- Programs: We offer programs from Playschool, Kinder 1 & 2 and Elementary. (Details on 'programs.php')
- Enrollment: The admission process is on the 'Admissions' page. ('enrollment.php')
- Status Check: Check application status on the 'Status' page. ('results.php')
- Contact Info: Found on the 'Contact Us' page. ('contact.php')
- About Us: Mission and vision are on the 'About Us' page. ('about.php')

Rules:
- If a user asks a question that is not regarding about the school, kindly refuse to answer.
- If a user asks a question you can answer with the links above, provide a helpful answer AND the link.
- Example: 'You can find all about our programs from Kindergarten to SHS on our <a href=\"programs.php\">Programs page</a>.'
- If the user asks a general knowledge question about school, answer it.
- If the user asks for your opinion, politely state that you are an AI assistant and do not have personal opinions.
- Be conversational and friendly.
";

$full_prompt = $system_prompt . "\n\n--- END OF INSTRUCTIONS ---\n\nHere is the user's question: \"" . $user_message . "\"";

$url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=' . $apiKey;

$data = [
    'contents' => [
        [
            'role' => 'user',
            'parts' => [['text' => $full_prompt]]
        ]
    ],
];
$jsonData = json_encode($data);

try {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if (curl_errno($ch)) {
        throw new Exception('cURL Error: ' . curl_error($ch));
    }
    
    curl_close($ch);

    if ($http_code === 200) {
        $result = json_decode($response, true);
        
        $reply_text = $result['candidates'][0]['content']['parts'][0]['text'] ?? 'I apologize, I received an empty response from the AI.';
        
        echo json_encode(['reply' => nl2br($reply_text)]);

    } else {
            error_log("Gemini API Error (HTTP $http_code): $response");
            
            $detailed_reply = "AI Connection Error: HTTP Code $http_code. Check the server log for details. Raw API Response: " . substr($response, 0, 150) . "...";
            echo json_encode(['reply' => $detailed_reply]);
    }

} catch (Exception $e) {
    error_log('Chatbot cURL Exception: ' . $e->getMessage());
    echo json_encode(['reply' => 'I apologize, but there was a server error. Please try again in a moment.']);
}
?>