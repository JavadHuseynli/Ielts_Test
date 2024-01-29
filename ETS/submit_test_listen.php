<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

$servername = "localhost";
$username_db = "root";
$password_db = "";
$dbname = "ENGLISH";

// Create connection
$conn = new mysqli($servername, $username_db, $password_db, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $correctAnswers = $_POST['correct_answers'];
    $userAnswers = $_POST['answers'];

    $score = 0;

    foreach ($correctAnswers as $questionId => $correctAnswer) {
        if (isset($userAnswers[$questionId]) && $userAnswers[$questionId] === $correctAnswer) {
            $score++;
        }
    }

    // Update the Student_read_score column in the database
    if (isset($_SESSION['istifadeci']) && isset($score)) {
        $studentId = $_SESSION['istifadeci'];

        // Assuming your table structure has a column named Student_read_score
        $sql = "UPDATE student SET Student_listen_score = $score , listening='passed' WHERE student_id = $studentId";

        if ($conn->query($sql) === TRUE) {
            $_SESSION['update_success'] = true;
            $_SESSION['reading'] = true ; // Set a session variable to indicate success
            // Additional actions based on the score can be added here
        } else {
            echo "Error updating record: " . $conn->error;
        }
    }

    // Additional actions based on the score can be added here
} 

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz Result</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f8f9fa;
            margin: 0;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
        }

        .result-container {
            text-align: center;
            background-color: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        h2 {
            color: #007bff;
        }

        p {
            font-size: 1.2rem;
            margin-top: 10px;
            color: #555;
        }

        .score {
            font-size: 2rem;
            font-weight: bold;
            color: #28a745; /* Green color for the score */
        }

        .action {
            margin-top: 20px;
            font-size: 1.1rem;
            color: #007bff;
        }
        a.hover {
    color: red;
    text-decoration: none;
    }

    a.hover:hover {
        text-decoration: none;
    }
    </style>
</head>
<body>
    <div class="result-container">
        <h2>Your Quiz Result</h2>
        <img src="images/bbu.jpeg" alt=""/>
        <?php
        if (isset($score)) {
            echo "<p class='score'>You scored: $score out of " . count($correctAnswers) . "</p>";
            // Additional actions based on the score can be added here
            if ($score == count($correctAnswers)) {
                echo "<p class='action'>Congratulations! You got all the answers correct.</p>";
            } else {
                echo "<p class='action'>Better luck next time! Keep practicing.</p>";
            }
        } else {
            echo "<p>Invalid request.</p>";
        }
        if (isset($_SESSION['update_success']) && $_SESSION['update_success']) {
            echo "<p class='action'>Update successful. Proceed to the <a class='action'  href='choose_exam.php'> next step. </a></p>";
            // Clear the session variable
            unset($_SESSION['update_success']);
        }
        ?>
    </div>
</body>
</html>
