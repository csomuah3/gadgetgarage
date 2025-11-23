<!DOCTYPE html>
<html>
<head>
    <title>Direct Promo Code Test</title>
</head>
<body>
    <h1>Direct Promo Code Test</h1>

    <form onsubmit="testPromo(event)">
        <label for="promoCode">Promo Code:</label>
        <input type="text" id="promoCode" value="BLACKFRIDAY20" required>
        <br><br>

        <label for="cartTotal">Cart Total:</label>
        <input type="number" id="cartTotal" value="9000" step="0.01" required>
        <br><br>

        <button type="submit">Test Promo Code</button>
    </form>

    <div id="result" style="margin-top: 20px; padding: 10px; border: 1px solid #ccc;"></div>

    <script>
        async function testPromo(event) {
            event.preventDefault();

            const promoCode = document.getElementById('promoCode').value.trim();
            const cartTotal = parseFloat(document.getElementById('cartTotal').value);
            const resultDiv = document.getElementById('result');

            console.log('Testing promo code:', promoCode);
            console.log('Cart total:', cartTotal);

            const requestData = {
                promo_code: promoCode,
                cart_total: cartTotal
            };

            console.log('Request data:', requestData);
            console.log('JSON string:', JSON.stringify(requestData));

            resultDiv.innerHTML = '<p>Testing...</p>';

            try {
                const response = await fetch('actions/validate_promo_code.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(requestData)
                });

                console.log('Response status:', response.status);
                console.log('Response headers:', [...response.headers.entries()]);

                const responseText = await response.text();
                console.log('Raw response:', responseText);

                try {
                    const data = JSON.parse(responseText);
                    console.log('Parsed response:', data);

                    if (data.success) {
                        resultDiv.innerHTML = `
                            <div style="color: green;">
                                <h3>Success!</h3>
                                <p><strong>Message:</strong> ${data.message}</p>
                                <p><strong>Promo Code:</strong> ${data.promo_code}</p>
                                <p><strong>Discount Type:</strong> ${data.discount_type}</p>
                                <p><strong>Discount Value:</strong> ${data.discount_value}%</p>
                                <p><strong>Original Total:</strong> GH₵${data.original_total}</p>
                                <p><strong>Discount Amount:</strong> GH₵${data.discount_amount}</p>
                                <p><strong>New Total:</strong> GH₵${data.new_total}</p>
                                <p><strong>Savings:</strong> GH₵${data.savings}</p>
                            </div>
                        `;
                    } else {
                        resultDiv.innerHTML = `
                            <div style="color: red;">
                                <h3>Error!</h3>
                                <p><strong>Message:</strong> ${data.message}</p>
                                ${data.debug ? '<p><strong>Debug:</strong> <pre>' + JSON.stringify(data.debug, null, 2) + '</pre></p>' : ''}
                            </div>
                        `;
                    }
                } catch (parseError) {
                    console.error('JSON parse error:', parseError);
                    resultDiv.innerHTML = `
                        <div style="color: red;">
                            <h3>Parse Error!</h3>
                            <p><strong>Raw Response:</strong></p>
                            <pre>${responseText}</pre>
                        </div>
                    `;
                }
            } catch (error) {
                console.error('Request error:', error);
                resultDiv.innerHTML = `
                    <div style="color: red;">
                        <h3>Request Failed!</h3>
                        <p><strong>Error:</strong> ${error.message}</p>
                    </div>
                `;
            }
        }
    </script>
</body>
</html>