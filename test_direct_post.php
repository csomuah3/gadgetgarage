<?php
// Direct POST test for promo validation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "POST request received<br>";
    echo "Content-Type: " . ($_SERVER['CONTENT_TYPE'] ?? 'not set') . "<br>";

    $raw_input = file_get_contents('php://input');
    echo "Raw input length: " . strlen($raw_input) . "<br>";
    echo "Raw input: " . htmlspecialchars($raw_input) . "<br>";

    $decoded = json_decode($raw_input, true);
    echo "JSON decode result: " . var_export($decoded, true) . "<br>";
    echo "JSON error: " . json_last_error_msg() . "<br>";

    echo "POST data: " . var_export($_POST, true) . "<br>";
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Direct POST Test</title>
</head>
<body>
    <h1>Direct POST Test</h1>
    <button onclick="testDirect()">Send Test POST</button>
    <div id="result"></div>

    <script>
        async function testDirect() {
            const data = {
                promo_code: 'TEST',
                cart_total: 50.00
            };

            console.log('Sending data:', data);

            try {
                const response = await fetch('test_direct_post.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(data)
                });

                const result = await response.text();
                document.getElementById('result').innerHTML = '<pre>' + result + '</pre>';
                console.log('Response:', result);
            } catch (error) {
                console.error('Error:', error);
                document.getElementById('result').innerHTML = 'Error: ' + error.message;
            }
        }
    </script>
</body>
</html>