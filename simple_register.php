<?php
// Simple PHP-only registration form for testing
error_reporting(E_ALL);
ini_set('display_errors', 1);

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        require_once 'controllers/user_controller.php';

        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $phone_number = trim($_POST['phone_number'] ?? '');
        $country = trim($_POST['country'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $role = 1;

        if (empty($name) || empty($email) || empty($password) || empty($phone_number) || empty($country) || empty($city)) {
            throw new Exception('Please fill in all fields');
        }

        $result = register_user_ctr($name, $email, $password, $phone_number, $country, $city, $role);

        if (is_array($result) && $result['status'] === 'success') {
            $message = 'Registration successful! You can now login.';
        } else {
            $error = $result['message'] ?? 'Registration failed';
        }

    } catch (Exception $e) {
        $error = 'Error: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Simple Registration Test</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 500px; margin: 50px auto; padding: 20px; }
        .form-group { margin: 15px 0; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input, select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; }
        button { background: #007bff; color: white; padding: 12px 20px; border: none; border-radius: 5px; cursor: pointer; }
        button:hover { background: #0056b3; }
        .success { color: green; padding: 10px; background: #d4edda; border-radius: 5px; margin: 10px 0; }
        .error { color: red; padding: 10px; background: #f8d7da; border-radius: 5px; margin: 10px 0; }
    </style>
</head>
<body>
    <h2>Simple Registration Test (PHP Only)</h2>

    <?php if ($message): ?>
        <div class="success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label>Name:</label>
            <input type="text" name="name" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
        </div>

        <div class="form-group">
            <label>Email:</label>
            <input type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
        </div>

        <div class="form-group">
            <label>Phone:</label>
            <input type="tel" name="phone_number" value="<?= htmlspecialchars($_POST['phone_number'] ?? '') ?>" required>
        </div>

        <div class="form-group">
            <label>Country:</label>
            <select name="country" required>
                <option value="">Select Country</option>
                <option value="Ghana" <?= ($_POST['country'] ?? '') === 'Ghana' ? 'selected' : '' ?>>Ghana</option>
            </select>
        </div>

        <div class="form-group">
            <label>City:</label>
            <input type="text" name="city" value="<?= htmlspecialchars($_POST['city'] ?? '') ?>" required>
        </div>

        <div class="form-group">
            <label>Password:</label>
            <input type="password" name="password" required>
        </div>

        <button type="submit">Register</button>
    </form>

    <p><a href="login/register.php">← Back to main registration form</a></p>
</body>
</html>