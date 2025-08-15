<?php
// Simple test script to verify database connection and basic functionality
// test.php

require_once 'database.php';

echo "<h1>BusBD System Test</h1>";

try {
    // Test database connection
    $database = new Database();
    $conn = $database->getConnection();
    
    if ($conn) {
        echo "<div style='color: green;'>✓ Database connection successful</div>";
        
        // Test basic queries
        $stmt = $conn->query("SELECT COUNT(*) as user_count FROM users");
        $userCount = $stmt->fetch(PDO::FETCH_ASSOC)['user_count'];
        echo "<div>Users in database: $userCount</div>";
        
        $stmt = $conn->query("SELECT COUNT(*) as route_count FROM routes");
        $routeCount = $stmt->fetch(PDO::FETCH_ASSOC)['route_count'];
        echo "<div>Routes in database: $routeCount</div>";
        
        $stmt = $conn->query("SELECT COUNT(*) as bus_count FROM buses");
        $busCount = $stmt->fetch(PDO::FETCH_ASSOC)['bus_count'];
        echo "<div>Buses in database: $busCount</div>";
        
        echo "<h2>API Endpoints Test</h2>";
        
        // Test tracking API
        echo "<h3>Tracking API</h3>";
        $trackingUrl = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/api/tracking.php?action=get_tracking";
        echo "<div>Testing: <a href='$trackingUrl' target='_blank'>$trackingUrl</a></div>";
        
        // Test recommendations API
        echo "<h3>Recommendations API</h3>";
        $recUrl = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/api/recommendations.php?action=get_popular_routes";
        echo "<div>Testing: <a href='$recUrl' target='_blank'>$recUrl</a></div>";
        
        // Test pricing API
        echo "<h3>Pricing API</h3>";
        $pricingUrl = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/api/pricing.php?action=get_routes";
        echo "<div>Testing: <a href='$pricingUrl' target='_blank'>$pricingUrl</a></div>";
        
        echo "<h2>Frontend Pages</h2>";
        $baseUrl = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/";
        
        $pages = [
            'Home' => 'index.html',
            'Tracking' => 'tracking.html',
            'Recommendations' => 'recommendations.html',
            'Pricing' => 'pricing.html',
            'Booking' => 'booking.html'
        ];
        
        foreach ($pages as $name => $file) {
            echo "<div><a href='$baseUrl$file' target='_blank'>$name</a></div>";
        }
        
    } else {
        echo "<div style='color: red;'>✗ Database connection failed</div>";
    }
    
} catch (Exception $e) {
    echo "<div style='color: red;'>✗ Error: " . $e->getMessage() . "</div>";
}

echo "<h2>System Requirements Check</h2>";

// Check PHP version
$phpVersion = PHP_VERSION;
echo "<div>PHP Version: $phpVersion - " . (version_compare($phpVersion, '7.4.0') >= 0 ? '✓' : '✗') . "</div>";

// Check MySQL extension
echo "<div>MySQL Extension: " . (extension_loaded('pdo_mysql') ? '✓' : '✗') . "</div>";

// Check JSON extension
echo "<div>JSON Extension: " . (extension_loaded('json') ? '✓' : '✗') . "</div>";

echo "<h2>File Structure Check</h2>";

$requiredFiles = [
    'config/database.php',
    'config/database.sql',
    'api/tracking.php',
    'api/recommendations.php',
    'api/pricing.php',
    'assets/css/style.css',
    'assets/js/main.js',
    'index.html',
    'tracking.html',
    'recommendations.html',
    'pricing.html',
    'booking.html'
];

foreach ($requiredFiles as $file) {
    $exists = file_exists($file);
    echo "<div>$file: " . ($exists ? '✓' : '✗') . "</div>";
}

echo "<h2>Test Complete</h2>";
echo "<p>If all checks pass, the BusBD system should be working correctly.</p>";
echo "<p>Make sure to import the database schema using: <code>mysql -u root -p busbd < config/database.sql</code></p>";
?>