<!DOCTYPE html>
<html>
<head>
    <title>Simple Promo Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        button { padding: 10px 20px; margin: 10px; background: #28a745; color: white; border: none; border-radius: 5px; cursor: pointer; }
        button:hover { background: #218838; }
        .result { margin: 20px 0; padding: 15px; border-radius: 5px; }
        .success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; }
        .error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; }
        input { padding: 8px; margin: 5px; border: 1px solid #ddd; border-radius: 3px; }
    </style>
</head>
<body>
    <h1>🎯 SIMPLE PROMO TEST</h1>
    <p>This will work regardless of cart contents!</p>

    <div>
        <input type="text" id="promoCode" value="BLACKFRIDAY20" placeholder="Promo Code">
        <input type="number" id="cartTotal" value="100" placeholder="Cart Total" step="0.01">
        <button onclick="testPromo()">🚀 TEST PROMO</button>
    </div>

    <div>
        <h3>Quick Tests:</h3>
        <button onclick="quickTest('BLACKFRIDAY20', 50)">Test $50</button>
        <button onclick="quickTest('BLACKFRIDAY20', 100)">Test $100</button>
        <button onclick="quickTest('INVALID', 100)">Test Invalid Code</button>
        <button onclick="quickTest('BLACKFRIDAY20', 0)">Test $0 Cart</button>
    </div>

    <div id="result"></div>

    <script>
        async function testPromo() {
            const promoCode = document.getElementById('promoCode').value;
            const cartTotal = parseFloat(document.getElementById('cartTotal').value) || 0;

            return await runTest(promoCode, cartTotal);
        }

        async function quickTest(code, total) {
            document.getElementById('promoCode').value = code;
            document.getElementById('cartTotal').value = total;
            return await runTest(code, total);
        }

        async function runTest(promoCode, cartTotal) {
            const result = document.getElementById('result');
            result.innerHTML = `<div>Testing "${promoCode}" with $${cartTotal}...</div>`;

            console.log('=== PROMO TEST START ===');
            console.log('Code:', promoCode);
            console.log('Total:', cartTotal);

            const data = {
                promo_code: promoCode,
                cart_total: cartTotal
            };

            console.log('Sending data:', data);
            console.log('JSON:', JSON.stringify(data));

            try {
                const response = await fetch('actions/validate_promo_code_simple.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(data)
                });

                console.log('Response status:', response.status);
                console.log('Response ok:', response.ok);

                const responseText = await response.text();
                console.log('Raw response:', responseText);

                const responseData = JSON.parse(responseText);
                console.log('Parsed response:', responseData);

                if (responseData.success) {
                    result.innerHTML = `
                        <div class="result success">
                            <h3>✅ SUCCESS!</h3>
                            <p><strong>Message:</strong> ${responseData.message}</p>
                            <p><strong>Promo Code:</strong> ${responseData.promo_code}</p>
                            <p><strong>Description:</strong> ${responseData.description}</p>
                            <p><strong>Discount:</strong> ${responseData.discount_value}% off</p>
                            <p><strong>Original Total:</strong> GH₵${responseData.original_total}</p>
                            <p><strong>Discount Amount:</strong> GH₵${responseData.discount_amount}</p>
                            <p><strong>New Total:</strong> GH₵${responseData.new_total}</p>
                            <p><strong>You Save:</strong> GH₵${responseData.savings}</p>
                        </div>
                    `;
                } else {
                    result.innerHTML = `
                        <div class="result error">
                            <h3>❌ ERROR</h3>
                            <p><strong>Message:</strong> ${responseData.message}</p>
                            ${responseData.debug ? '<pre>' + JSON.stringify(responseData.debug, null, 2) + '</pre>' : ''}
                        </div>
                    `;
                }
            } catch (error) {
                console.error('Request failed:', error);
                result.innerHTML = `
                    <div class="result error">
                        <h3>❌ NETWORK ERROR</h3>
                        <p>${error.message}</p>
                    </div>
                `;
            }

            console.log('=== PROMO TEST END ===');
        }
    </script>
</body>
</html>