<?php
// Server Configuration Test
echo "<h1>IELTS Test System - Server Configuration</h1>";
echo "<hr>";

echo "<h2>PHP Version</h2>";
echo "<p>" . phpversion() . "</p>";

echo "<h2>Upload Settings</h2>";
echo "<table border='1' cellpadding='10'>";
echo "<tr><th>Setting</th><th>Current Value</th><th>Required</th><th>Status</th></tr>";

$settings = [
    'upload_max_filesize' => ['current' => ini_get('upload_max_filesize'), 'required' => '25M'],
    'post_max_size' => ['current' => ini_get('post_max_size'), 'required' => '30M'],
    'max_execution_time' => ['current' => ini_get('max_execution_time'), 'required' => '300'],
    'memory_limit' => ['current' => ini_get('memory_limit'), 'required' => '256M'],
    'file_uploads' => ['current' => ini_get('file_uploads') ? 'On' : 'Off', 'required' => 'On'],
];

foreach ($settings as $key => $value) {
    $status = "✅ OK";
    $color = "green";
    
    if ($key == 'upload_max_filesize' && intval($value['current']) < 25) {
        $status = "❌ LOW";
        $color = "red";
    } else if ($key == 'post_max_size' && intval($value['current']) < 30) {
        $status = "❌ LOW";
        $color = "red";
    } else if ($key == 'max_execution_time' && intval($value['current']) < 300) {
        $status = "⚠️ LOW";
        $color = "orange";
    }
    
    echo "<tr>";
    echo "<td><strong>$key</strong></td>";
    echo "<td>" . $value['current'] . "</td>";
    echo "<td>" . $value['required'] . "</td>";
    echo "<td style='color: $color;'><strong>$status</strong></td>";
    echo "</tr>";
}

echo "</table>";

echo "<h2>Server Software</h2>";
echo "<p>" . $_SERVER['SERVER_SOFTWARE'] . "</p>";

echo "<h2>Loaded Modules</h2>";
echo "<p><small>";
$modules = get_loaded_extensions();
sort($modules);
echo implode(", ", $modules);
echo "</small></p>";

echo "<hr>";
echo "<p><strong>Date:</strong> " . date('Y-m-d H:i:s') . "</p>";
?>
