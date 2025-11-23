<!DOCTYPE html>
<html>
<head>
    <title>Simple Promo Test</title>
</head>
<body>
    <h1>Simple Promo Code Test</h1>

    <button onclick="testPromo()">Test BLACKFRIDAY20 with 100.00</button>
    <div id="result"></div>

    <script>
        async function testPromo() {
            const result = document.getElementById('result');
            result.innerHTML = '<p>Testing...</p>';

            const data = {
                promo_code: 'BLACKFRIDAY20',
                cart_total: 100.00
            };

            console.log('Sending:', data);
            console.log('JSON:', JSON.stringify(data));

            try {
                const response = await fetch('actions/validate_promo_code.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(data)
                });

                console.log('Response status:', response.status);
                const text = await response.text();
                console.log('Response text:', text);

                const responseData = JSON.parse(text);

                if (responseData.success) {
                    result.innerHTML = `<div style="color: green;">SUCCESS: ${responseData.message}</div>`;
                } else {
                    result.innerHTML = `<div style="color: red;">ERROR: ${responseData.message}<br>Debug: <pre>${JSON.stringify(responseData.debug, null, 2)}</pre></div>`;
                }
            } catch (error) {
                console.error('Error:', error);
                result.innerHTML = `<div style="color: red;">Request failed: ${error.message}</div>`;
            }
        }
    </script>
</body>
</html>