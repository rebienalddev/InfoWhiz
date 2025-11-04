<?php
// api.php - Handle Gemini AI requests
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// InfoWhiz: School Bot 🤖
$apiKey = "AIzaSyBMqLHAjRLoYZK1vmYqNAOfe4A8uf_Z3-8";
$url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent";
// Start session for chat history
session_start();
if (!isset($_SESSION['chat'])) {
    $_SESSION['chat'] = [];
}

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $userInput = trim($input['prompt'] ?? '');

    if (empty($userInput)) {
        echo json_encode(['success' => false, 'error' => 'Empty prompt']);
        exit;
    }

    // Add user message to session
    $_SESSION['chat'][] = ['role' => 'user', 'text' => htmlspecialchars($userInput)];

    // Prepare data for Gemini API
    $data = [
        "contents" => [
            [
                "parts" => [
                    ["text" => $userInput]
                ]
            ]
        ]
    ];

    // cURL request with timeout for faster response
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "$url?key=$apiKey");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($response === false) {
        $botResponse = "⚠️ Error connecting to Gemini API: " . $error;
        $success = false;
    } else {
        $result = json_decode($response, true);
        if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
            $botResponse = $result['candidates'][0]['content']['parts'][0]['text'];
            $success = true;
        } else {
            $botResponse = "⚠️ Error: Unable to get response from AI. API returned: " . $httpCode;
            if (isset($result['error']['message'])) {
                $botResponse .= " - " . $result['error']['message'];
            }
            $success = false;
        }
    }

    // Add bot reply to session
    $_SESSION['chat'][] = ['role' => 'bot', 'text' => $botResponse];

    // Return JSON response
    echo json_encode([
        'success' => $success,
        'response' => $botResponse,
        'history' => $_SESSION['chat']
    ]);
    exit;
}

// If not POST request, return error
echo json_encode(['success' => false, 'error' => 'Invalid request method']);
?>