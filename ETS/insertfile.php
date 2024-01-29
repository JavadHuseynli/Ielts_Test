<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
// Database connection parameters
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "English";

// Create a connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check the connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if the form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["submit"])) {
    // Check if a file was selected
    $selectedReading = $_POST['selectedReading'];
    if ($_FILES["questionsFile"]["error"] == 0) {
        // Read questions from the uploaded file
        $questionsFile = $_FILES["questionsFile"]["tmp_name"];
        $lines = file($questionsFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        // Initialize variables
        $questionText = '';
        $options = [];
        $correctOption = '';

        // Process each line in the file
        foreach ($lines as $line) {
            // Check if the line contains a question or an answer
            if (strpos($line, '?') !== false) {
                // This line is part of the question
                $questionText .= trim($line);
            } elseif (preg_match('/^[a-d]\./i', $line)) {
                // This line is an answer
                $options[] = trim($line);
            } elseif (strpos($line, 'Correct Answer:') !== false) {
                // This line is the correct answer
                $correctOption = trim(str_replace('Correct Answer:', '', $line));

                // Insert into the database
                $sql = "INSERT INTO questions (question_text, option_a, option_b, option_c, option_d, correct_option,reading_id	) 
                        VALUES ('$questionText', '{$options[0]}', '{$options[1]}', '{$options[2]}', '{$options[3]}', '$correctOption','$selectedReading')";

                $result = $conn->query($sql);

                // Reset variables for the next question
                $questionText = '';
                $options = [];
                $correctOption = '';
            }
        }

        echo "Questions uploaded successfully.";

    }else{
        echo "Not Questions uploaded successfully.";
    } 
}

// Close the database connection
$conn->close();
?>
