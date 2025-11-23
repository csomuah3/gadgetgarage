<!DOCTYPE html>
<html>
<head>
    <title>FINAL Promo Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        button { padding: 10px 20px; margin: 10px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer; }
        button:hover { background: #0056b3; }
        .result { margin: 20px 0; padding: 15px; border-radius: 5px; }
        .success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; }
        .error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; }
    </style>
</head>
<body>
    <h1>FINAL Promo Code Test</h1>

    <div>
        <h3>Test with Different Cart Values:</h3>
        <button onclick="testPromo(50)">Test with $50</button>
        <button onclick="testPromo(100)">Test with $100</button>
        <button onclick="testPromo(500)">Test with $500</button>
        <button onclick="testPromo(1000)">Test with $1000</button>
    </div>

    <div>
        <h3>Custom Test:</h3>
        <input type="text" id="customCode" placeholder="Promo Code" value="BLACKFRIDAY20">
        <input type="number" id="customTotal" placeholder="Cart Total" value="100" step="0.01">
        <button onclick="testCustom()">Test Custom</button>
    </div>

    <div id="result"></div>

    <script>
        async function testPromo(total) {
            const result = document.getElementById('result');
            result.innerHTML = '<div>Testing BLACKFRIDAY20 with $' + total + '...</div>';

            try {
                const response = await fetch('actions/validate_promo_code_fixed.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        promo_code: 'BLACKFRIDAY20',
                        cart_total: total
                    })
                });

                const data = await response.json();

                if (data.success) {
                    result.innerHTML = `
                        <div class="result success">
                            <h4>✅ SUCCESS!</h4>
                            <p><strong>Message:</strong> ${data.message}</p>
                            <p><strong>Promo:</strong> ${data.promo_code} (${data.description})</p>
                            <p><strong>Discount:</strong> ${data.discount_type} - ${data.discount_value}%</p>
                            <p><strong>Original Total:</strong> GH₵${data.original_total}</p>
                            <p><strong>Discount Amount:</strong> GH₵${data.discount_amount}</p>
                            <p><strong>New Total:</strong> GH₵${data.new_total}</p>
                            <p><strong>You Save:</strong> GH₵${data.savings}</p>
                        </div>
                    `;
                } else {
                    result.innerHTML = `
                        <div class="result error">
                            <h4>❌ ERROR</h4>
                            <p>${data.message}</p>
                        </div>
                    `;
                }
            } catch (error) {
                result.innerHTML = `
                    <div class="result error">
                        <h4>❌ NETWORK ERROR</h4>
                        <p>${error.message}</p>
                    </div>
                `;
            }
        }

        async function testCustom() {
            const code = document.getElementById('customCode').value;
            const total = parseFloat(document.getElementById('customTotal').value);

            if (!code || total <= 0) {
                document.getElementById('result').innerHTML = '<div class="result error">Please enter valid code and total</div>';
                return;
            }

            const result = document.getElementById('result');
            result.innerHTML = `<div>Testing ${code} with $${total}...</div>`;

            try {
                const response = await fetch('actions/validate_promo_code_fixed.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        promo_code: code,
                        cart_total: total
                    })
                });

                const data = await response.json();

                if (data.success) {
                    result.innerHTML = `
                        <div class="result success">
                            <h4>✅ SUCCESS!</h4>
                            <p><strong>Message:</strong> ${data.message}</p>
                            <p><strong>Promo:</strong> ${data.promo_code}</p>
                            <p><strong>Original Total:</strong> GH₵${data.original_total}</p>
                            <p><strong>New Total:</strong> GH₵${data.new_total}</p>
                            <p><strong>Savings:</strong> GH₵${data.savings}</p>
                        </div>
                    `;
                } else {
                    result.innerHTML = `
                        <div class="result error">
                            <h4>❌ ERROR</h4>
                            <p>${data.message}</p>
                        </div>
                    `;
                }
            } catch (error) {
                result.innerHTML = `
                    <div class="result error">
                        <h4>❌ NETWORK ERROR</h4>
                        <p>${error.message}</p>
                    </div>
                `;
            }
        }
    </script>
</body>
</html>