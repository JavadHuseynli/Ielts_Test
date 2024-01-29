<?php
// submit.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "English";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $readingPassage = $_POST['readingPassage'];
    $readId = $_POST['read_id']; // Retrieve read_id

    if (isset($_POST['accept'])) {
        // Update the database to indicate that answers have been accepted
        $updateAcceptedSQL = "UPDATE questions SET read_accept = 'accepted' WHERE reading_id = $readId";
        $message = "Answers submitted successfully. Your answers have been accepted.";
    } elseif (isset($_POST['not_accept'])) {
        // Update the database to indicate that answers have NOT been accepted
        $updateAcceptedSQL = "UPDATE questions SET read_accept = 'notaccepted' WHERE reading_id = $readId";
        $message = "Answers submitted. Your answers have NOT been accepted.";
    } else {
        $message = "Invalid request.";
    }

    if ($conn->query($updateAcceptedSQL) === TRUE) {
        echo $message;
    } else {
        echo "Error updating status: " . $conn->error;
    }
} else {
    echo "Invalid request method.";
}

$conn->close();
?>
