<?php
// A simple script to load and stream an image file to the browser.
// This helps bypass potential file system permission issues where the web server
// user can execute PHP but cannot directly read certain image assets.

// Whitelist of allowed images to prevent security risks.
$allowed_images = [
    'logo_bbu.jpg',
    'logobbu.jpg'
];

// Get the requested image file from the query parameter.
$file = isset($_GET['file']) ? $_GET['file'] : '';

// Check if the requested file is in the whitelist and exists.
if (in_array($file, $allowed_images) && file_exists($file)) {
    // Determine the MIME type based on the file extension.
    $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    $mime_type = 'image/jpeg'; // Default
    if ($extension === 'png') {
        $mime_type = 'image/png';
    } elseif ($extension === 'gif') {
        $mime_type = 'image/gif';
    }

    // Set the appropriate content type header.
    header('Content-Type: ' . $mime_type);
    
    // Output the image file content.
    readfile($file);
    
    // Stop the script.
    exit;
} else {
    // If the file is not found or not allowed, return a 404 error.
    header("HTTP/1.0 404 Not Found");
    exit;
}
?>
