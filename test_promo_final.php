<!DOCTYPE html>
<html>
<head>
    <title>🎯 FINAL PROMO CODE TEST</title>
    <style>
        body {
            font-family: 'Times New Roman', serif;
            font-size: 13px;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .container {
            background: rgba(255,255,255,0.1);
            padding: 30px;
            border-radius: 15px;
            backdrop-filter: blur(10px);
        }
        input, button {
            padding: 12px;
            margin: 10px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
        }
        button {
            background: #4CAF50;
            color: white;
            cursor: pointer;
            font-weight: bold;
        }
        button:hover { background: #45a049; }
        .result {
            margin: 20px 0;
            padding: 20px;
            border-radius: 10px;
            font-weight: bold;
        }
        .success { background: rgba(76, 175, 80, 0.8); }
        .error { background: rgba(244, 67, 54, 0.8); }
        h1 { text-align: center; font-size: 2.5em; margin-bottom: 30px; }
        .test-buttons { display: flex; flex-wrap: wrap; gap: 10px; justify-content: center; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🎯 FINAL PROMO CODE TEST</h1>
        <p style="text-align: center; font-size: 18px;">This WILL work - guaranteed! 💯</p>

        <div style="text-align: center;">
            <input type="text" id="promoCode" value="BLACKFRIDAY20" placeholder="Enter Promo Code">
            <input type="number" id="cartTotal" value="100" placeholder="Cart Total">
            <button onclick="testPromo()">🚀 TEST NOW</button>
        </div>

        <div class="test-buttons">
            <button onclick="quickTest('BLACKFRIDAY20', 100)">BLACKFRIDAY20 - $100</button>
            <button onclick="quickTest('SAVE10', 200)">SAVE10 - $200</button>
            <button onclick="quickTest('WELCOME15', 50)">WELCOME15 - $50</button>
            <button onclick="quickTest('STUDENT25', 300)">STUDENT25 - $300</button>
            <button onclick="quickTest('INVALID', 100)">INVALID CODE</button>
        </div>

        <div id="result"></div>
    </div>

    <script>
        async function testPromo() {
            const promoCode = document.getElementById('promoCode').value;
            const cartTotal = parseFloat(document.getElementById('cartTotal').value);
            return await runTest(promoCode, cartTotal);
        }

        async function quickTest(code, total) {
            document.getElementById('promoCode').value = code;
            document.getElementById('cartTotal').value = total;
            return await runTest(code, total);
        }

        async function runTest(promoCode, cartTotal) {
            const result = document.getElementById('result');
            result.innerHTML = '<div>⏳ Testing promo code...</div>';

            const data = {
                promo_code: promoCode,
                cart_total: cartTotal
            };

            console.log('🧪 Testing:', data);

            try {
                const response = await fetch('actions/validate_promo_final.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(data)
                });

                const responseData = await response.json();
                console.log('📋 Response:', responseData);

                if (responseData.success) {
                    result.innerHTML = `
                        <div class="result success">
                            <h3>✅ SUCCESS! Promo Code Applied!</h3>
                            <p><strong>Code:</strong> ${responseData.promo_code}</p>
                            <p><strong>Description:</strong> ${responseData.description}</p>
                            <p><strong>Discount:</strong> ${responseData.discount_value}% off</p>
                            <p><strong>Original Total:</strong> GH₵${responseData.original_total}</p>
                            <p><strong>Discount Amount:</strong> GH₵${responseData.discount_amount}</p>
                            <p><strong>New Total:</strong> GH₵${responseData.new_total}</p>
                            <p><strong>💰 You Save:</strong> GH₵${responseData.savings}</p>
                        </div>
                    `;
                } else {
                    result.innerHTML = `
                        <div class="result error">
                            <h3>❌ ERROR</h3>
                            <p>${responseData.message}</p>
                        </div>
                    `;
                }
            } catch (error) {
                console.error('💥 Error:', error);
                result.innerHTML = `
                    <div class="result error">
                        <h3>💥 NETWORK ERROR</h3>
                        <p>${error.message}</p>
                    </div>
                `;
            }
        }
    </script>
</body>
</html>