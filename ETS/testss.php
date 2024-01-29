<?php
// exam.php
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

// Get reading ID from the URL parameter
$readingId = isset($_GET['readingId']) ? $_GET['readingId'] : null;

if (!$readingId) {
    echo "Reading ID is missing in the URL.";
    exit();
}

// Fetch a single random reading passage with image path and file path based on the provided reading ID
$sqlReading = "SELECT Read_pass FROM Reading WHERE Read_pass IS NOT NULL AND read_id = $readingId";
$resultReading = $conn->query($sqlReading);

if ($resultReading->num_rows > 0) {
    $rowReading = $resultReading->fetch_assoc();
    $readingPassage = $rowReading['Read_pass'];
    $imagePath = $rowReading['Read_pass'];

    // Fetch associated questions and randomized answer options
    $sqlQuestions = "SELECT * FROM questions WHERE reading_id = $readingId";
    $resultQuestions = $conn->query($sqlQuestions);

    if ($resultQuestions->num_rows > 0) {
        $questionsData = array();

        while ($rowQuestions = $resultQuestions->fetch_assoc()) {
            $questionsData[] = $rowQuestions;
        }
        shuffle($questionsData); // Randomize questions order
    }
} else {
    echo "No reading passage found for the provided reading ID.";
    exit();
}

// Initialize success message variable
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reading Passage and Questions</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f8f9fa;
            margin: 0;
            padding: 0;
        }

        header {
            background-color: #fff;
            color: #fff;
            text-align: center;
            padding: 10px 0;
            font-weight: 700;
        }

        .container {
            display: flex;
            justify-content: space-between;
            padding: 20px;
        }

        .reading-passage,
        .questions-container {
            padding: 20px;
        }

        .reading-passage {
            border-bottom: 1px solid #ddd;
            white-space: pre-wrap;
            word-wrap: break-word;
        }

       

       
        .reading,
        .questions {
            width: 48%;
            border: 1px solid #ddd;
            padding: 20px;
            background-color: #fff;
            margin-left: 10px;
            margin-right: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            overflow-y: auto;
            max-height: 550px;
        }

        .questions {
            text-align: left;
        }

        .question {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 10px;
            
            transition: color 0.3s ease;
            font-size: 18px;
        }

        input[type="radio"] {
            margin-right: 5px;
        }
       
        input[type="submit"] {
            background-color: #007bff;
            color: #fff;
            border: none;
            padding: 10px 20px;
            font-size: 1rem;
            cursor: pointer;
        }

        input[type="submit"]:hover {
            background-color: #0056b3;
        }

        .correct-answer {
            color: green;
            font-weight: bold;
            transition: color 0.3s ease;
            font-size: 16px;
        }

        /* .reject-btn {
            background-color: #fff;
            color: black;
            transition: background-color 0.3s ease;
        }

        .reject-btn:hover {
            background-color: #0056b3;
        } */

        /* Added styles for the form */
        form {
            margin-top: 10px;
        }

        select {
            margin-right: 10px;
            font-size: 16px;
        }

        .success-message {
            color: green;
            margin-top: 10px;
            font-size: 18px;
        }

        .danger-message {
            color: darkred;
            margin-top: 10px;
            font-weight: bold;
            transition: color 0.3s ease;
            font-size: 18px;
        }

        /* Add this style to your existing CSS styles */
        .navigation-link {
            margin: 10px;
            text-align: left;
        }

        .navigation-link a {
            display: inline-block;
            padding: 10px 20px;
            background-color: #fff;
            color: black;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s ease;
        }

        .navigation-link a:hover {
            color:white;
            background-color: #0056b3;
        }
        /* Add this style to your existing CSS styles */

/* Custom styling for select element */


select {
    width: 50%;
    padding: 12px;
    font-size: 16px;
    border: none;
   
    outline: none;
    background-color: #f0f0fc;
    cursor: pointer;
    transition: border-color 0.3s ease;
    appearance: none;
}

/* Highlight on hover */
select:hover {
    border-bottom-color: #45a049;
}

/* Style the options */
select option {
    font-size: 16px;
}

/* Style the dropdown arrow */
select::-ms-expand {
    display: none;
}

/* Add animation for dropdown appearance */
select:focus {
    border-bottom-color: #45a049;
    animation: fadeIn 0.5s ease;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

    </style>
</head>
<body>

<header>
    <div class="navigation-link">
        <a href="admin_panel.php">Go to Home</a>
    </div>
</header>

<div class="container">
    <div class="reading">
        <h2>Reading Passage</h2>
        <?php
        // Read the content of the file
        $filePath = 'uploads/' . $imagePath;
        $fileType = pathinfo($filePath, PATHINFO_EXTENSION);

        if ($fileType === 'txt') {
            $fileContent = file_get_contents($filePath);

            if ($fileContent === false) {
                // Handle the case where the file cannot be read
                echo "Error: Unable to read the file '$imagePath'.";
                exit();
            }

            // Display the reading passage
            echo '<pre style="font-size: 14px; white-space: pre-wrap;">' . htmlspecialchars($fileContent) . '</pre>';
        } elseif ($fileType === 'mp3') {
            // Display an HTML5 audio player for MP3 files
            echo '<audio controls>';
            echo '<source src="' . $filePath . '" type="audio/mp3">';
            echo 'Your browser does not support the audio element.';
            echo '</audio>';
        } else {
            // Handle other file types if needed
            echo "Unsupported file type.";
        }
        ?>
    </div>

    <div class="questions">
        <h2>Questions</h2>
        <form action="fetch_questions.php?readingId=<?php echo $readingId; ?>" method="post" id="quizForm">
            <?php
            if (!empty($questionsData)) {
                $questionNumber = 1;
                foreach ($questionsData as $question) {
                    ?>
                    <div class="question" data-question-id="<?php echo $question['id']; ?>">
                        <p style="font-weight: bold;">  Question <?php echo $questionNumber++;?> : <?php echo   $question['question_text']; ?></p>

                        <label for="status">Update Status:
                        <select name="updateStatus[<?php echo $question['id']; ?>]" id="status"></label>
                        <option value="">NOT ACCEPTED</option>
                        <option value="accepted">Accepted</option>
                            <option value="rejected">Rejected</option>
                        </select>

                        <label style="padding-top:20px">
                            <input type="radio" name="answers[<?php echo $question['id']; ?>]" value="A"/>
                            <?php echo $question['option_a']; ?>
                        </label>
                        <label>
                            <input type="radio" name="answers[<?php echo $question['id']; ?>]" value="B"/>
                            <?php echo $question['option_b']; ?>
                        </label>
                        <label>
                            <input type="radio" name="answers[<?php echo $question['id']; ?>]" value="C"/>
                            <?php echo $question['option_c']; ?>
                        </label>
                        <label>
                            <input type="radio" name="answers[<?php echo $question['id']; ?>]" value="D"/>
                            <?php echo $question['option_d']; ?>
                        </label>

                        <!-- Hidden field for the question ID -->
                        <input type="hidden" name="updateQuestionId[]" value="<?php echo $question['id']; ?>">

                        <p class="correct-answer">Correct answer is: <?php echo $question['correct_option']; ?></p>
                    </div>
                    <?php
                }
            } else {
                echo "No questions found for the selected reading passage.";
            }
            ?>
            <input type="submit" value="Update Status">
        </form>
        <?php
        $successMessage = '';

        // Handle the form submission for updating question status
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!empty($_POST['updateQuestionId'])) {
                foreach ($_POST['updateQuestionId'] as $updateQuestionId) {
                    $updateStatus = $_POST['updateStatus'][$updateQuestionId];

                    // Perform the database update based on the received parameters
                    $sqlUpdate = "UPDATE questions SET read_accept = '$updateStatus' WHERE id = $updateQuestionId";
                    $conn->query($sqlUpdate);
                }
                $successMessage = 'Your status is successfully added.';
                if (!empty($successMessage)) {
                    echo '<div class="success-message"> <p class="correct-answer">' . $successMessage . '</p></div>';
                }
            } else {
                echo '<div class="danger-message"> <p class="danger-answer"> Please Choose Your Update Status </p></div>';
            }
        }

        // Display success message if any
        ?>
    </div>
</div>

</body>
</html>
