<?php
// stream_audio.php - PHP script to stream audio files, bypassing direct web server handling.

// Check if a file parameter is provided in the URL
if (isset($_GET['file'])) {
    $file_param = $_GET['file'];
    
    // Basic sanitization of the file parameter. This is crucial for security.
    // It expects a relative path like 'uploads/uniqueid_filename.mp3'.
    // Remove any characters that are not part of a safe filename path.
    $file_param = filter_var($file_param, FILTER_SANITIZE_URL);
    
    // Construct the absolute path to the file.
    // __DIR__ gives the directory where stream_audio.php resides (project root).
    $base_dir = __DIR__ . DIRECTORY_SEPARATOR;
    $full_path = $base_dir . $file_param;

    // --- SECURITY CHECK: Ensure the file path is within the allowed 'uploads' directory ---
    // This prevents directory traversal attacks, where a user might try to access
    // files outside the 'uploads' directory (e.g., ../../sensitive_file.txt).
    $uploads_dir_absolute = realpath($base_dir . 'uploads');
    $file_path_resolved = realpath($full_path);

    if ($uploads_dir_absolute === false || $file_path_resolved === false || strpos($file_path_resolved, $uploads_dir_absolute) !== 0) {
        // Path is outside uploads directory, or paths are invalid
        header("HTTP/1.0 403 Forbidden");
        exit("Invalid file path or access denied.");
    }

    // Check if the file exists and is readable
    if (file_exists($file_path_resolved) && is_readable($file_path_resolved)) {
        $file_extension = strtolower(pathinfo($file_path_resolved, PATHINFO_EXTENSION));
        $mime_type = '';

        // Determine the correct MIME type based on the file extension
        switch ($file_extension) {
            case 'mp3':
                $mime_type = 'audio/mpeg';
                break;
            case 'wav':
                $mime_type = 'audio/wav';
                break;
            case 'ogg':
                $mime_type = 'audio/ogg';
                break;
            default:
                // If an unsupported file type is requested, deny access
                header("HTTP/1.0 403 Forbidden");
                exit("Unsupported file type.");
        }

        // Set appropriate headers for audio streaming
        header('Content-Type: ' . $mime_type);
        header('Content-Length: ' . filesize($file_path_resolved));
        
        // Optimize for streaming and caching
        header('Cache-Control: public, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Accept-Ranges: bytes'); // Allows browsers to seek within the audio

        // Clear output buffer to ensure headers are sent immediately
        if (ob_get_level()) ob_end_clean();

        // Read the file directly to the output buffer
        readfile($file_path_resolved);
        exit(); // Terminate script after serving the file
    } else {
        // File not found or not readable
        header("HTTP/1.0 404 Not Found");
        exit("File not found or not readable.");
    }
} else {
    // No file parameter provided
    header("HTTP/1.0 400 Bad Request");
    exit("Missing file parameter.");
}
?>