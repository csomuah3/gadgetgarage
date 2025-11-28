<?php
require_once 'settings/core.php';

try {
    $pdo = new PDO("mysql:host=" . SERVER . ";dbname=" . DATABASE, USERNAME, PASSWD);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "<h2>Database Connection: SUCCESS</h2>";

    // Check if orders table exists and get its structure
    $stmt = $pdo->prepare("DESCRIBE orders");
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<h2>Orders Table Structure:</h2>";
    echo "<table border='1'>";
    echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td>{$col['Field']}</td>";
        echo "<td>{$col['Type']}</td>";
        echo "<td>{$col['Null']}</td>";
        echo "<td>{$col['Key']}</td>";
        echo "<td>{$col['Default']}</td>";
        echo "<td>{$col['Extra']}</td>";
        echo "</tr>";
    }
    echo "</table>";

    // Check if there are any orders
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM orders");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<h2>Total Orders: " . $result['count'] . "</h2>";

    if ($result['count'] > 0) {
        $stmt = $pdo->prepare("SELECT * FROM orders LIMIT 3");
        $stmt->execute();
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<h2>Sample Orders:</h2>";
        echo "<table border='1'>";
        if (!empty($orders)) {
            echo "<tr>";
            foreach (array_keys($orders[0]) as $header) {
                echo "<th>$header</th>";
            }
            echo "</tr>";
            foreach ($orders as $order) {
                echo "<tr>";
                foreach ($order as $value) {
                    echo "<td>$value</td>";
                }
                echo "</tr>";
            }
        }
        echo "</table>";
    }

} catch (Exception $e) {
    echo "<h2>Error: " . $e->getMessage() . "</h2>";
}
?>