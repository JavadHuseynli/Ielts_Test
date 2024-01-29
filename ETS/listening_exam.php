<?php
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

// Fetch a single random passage with image or audio path
$sqlPassage = "SELECT Reading.*, questions.* FROM Reading
                RIGHT JOIN questions ON Reading.read_id = questions.reading_id
                WHERE Reading.heading_part = 'listening'
                ORDER BY RAND() LIMIT 1";
$resultPassage = $conn->query($sqlPassage);

if ($resultPassage->num_rows > 0) {
    $rowPassage = $resultPassage->fetch_assoc();
    $readingPassage = $rowPassage['Read_pass'];
    $headingPart = $rowPassage['heading_part'];
} else {
    echo "No passage found.";
    exit();
}

// Fetch associated questions and randomized answer options
$sqlQuestions = "SELECT * FROM questions WHERE reading_id = (SELECT read_id FROM Reading WHERE Read_pass = '$readingPassage' LIMIT 1)";



$resultQuestions = $conn->query($sqlQuestions);

if ($resultQuestions->num_rows > 0) {
    $questionsData = array();

    while ($rowQuestions = $resultQuestions->fetch_assoc()) {
        $questionsData[] = $rowQuestions;
    }
    shuffle($questionsData); // Randomize questions order

    // Check if the form was submitted
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $correctAnswersCounter = 0;

        foreach ($questionsData as $question) {
            $questionId = $question['id'];
            $selectedOption = isset($_POST['answers'][$questionId]) ? $_POST['answers'][$questionId] : null;

            // Fetch the correct variant from the database
            $correctVariant = $question['correct_variant'];
            $sqlCorrectOptions = "SELECT correct_option FROM questions WHERE correct_variant = '$correctVariant'";
            $resultCorrectOptions = $conn->query($sqlCorrectOptions);

            if ($resultCorrectOptions->num_rows == 1) {
                $rowCorrectOptions = $resultCorrectOptions->fetch_assoc();
                $correctOptions = explode(',', $rowCorrectOptions['correct_option']);

                // Check if the submitted option is correct
                if (in_array($selectedOption, $correctOptions)) {
                    $correctAnswersCounter++;
                }
            }
        }

        // Store the result and correct answers in a session variable
        session_start();
        $_SESSION['exam_result'] = $correctAnswersCounter;
        $_SESSION['correct_answers'] = $_POST['correct_answers'];

        // Redirect to the result page
        header("Location: result.php");
        exit();
    }

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Examination Test</title>
    <style>
        /* Your existing styles remain unchanged */

        .question-buttons {
            display: flex;
            justify-content: space-around;
            margin-bottom: 10px;
        }

        .question-buttons button {
            padding: 5px 10px;
            cursor: pointer;
        }
    </style>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f8f9fa;
            margin: 0;
            padding: 0;
        }

        .container {
            display: flex;
            justify-content: space-between;
            padding: 20px;
        }

        .reading{
            width: 48%;
            border: 1px solid #ddd;
            padding: 20px;
            background-color: #fff;
            margin-left: 10px;
            margin-right: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            overflow-y: auto;
            max-height: 150px;
        }
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

        #timer {
            font-size: 1.5rem;
            color: darkred;
            position: fixed;
            top: 0;
            left: 70%;
            font-weight: bold;
            background-color: #fff;
            padding: 10px 20px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            z-index: 1000;
        }

        .btn-submit {
            background-color: #007bff;
            color: #fff;
            border: none;
            padding: 10px 20px;
            font-size: 1rem;
            cursor: pointer;
        }

        .btn-submit:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <div id="timer"> <span id="timerValue"></span></div>
    <div class="container">
        <div class="reading">
            <h2 style= "text-align : center ; color: darkred"class="">Listening Passage</h2>
            <?php
            
                // Display audio player for listening passage
                echo '<audio controls>';
                echo '<source src="uploads/' . $readingPassage . '" type="audio/mp3">';
                echo 'Your browser does not support the audio element.';
                echo '</audio>';
            
            ?>
        </div>

        <div class="questions">
            <h2 class="mb-4">Questions</h2>
           
            <form action="submit_test_listen.php" method="post">
                <?php
                $questionNumber = 1;
                foreach ($questionsData as $question) {
                    ?>
                    <div class="question">
                        <p class="mb-2"><?php echo 'Question ' . $questionNumber . ': ' . $question['question_text']; ?></p>

                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="answers[<?php echo $question['id']; ?>]" id="optionA<?php echo $question['id']; ?>" value="A">
                            <label class="form-check-label" for="optionA<?php echo $question['id']; ?>"><?php echo $question['option_a']; ?></label>
                        </div>

                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="answers[<?php echo $question['id']; ?>]" id="optionB<?php echo $question['id']; ?>" value="B">
                            <label class="form-check-label" for="optionB<?php echo $question['id']; ?>"><?php echo $question['option_b']; ?></label>
                        </div>

                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="answers[<?php echo $question['id']; ?>]" id="optionC<?php echo $question['id']; ?>" value="C">
                            <label class="form-check-label" for="optionC<?php echo $question['id']; ?>"><?php echo $question['option_c']; ?></label>
                        </div>

                      
                        <!-- Hidden field for correct answers -->
                        <input type="hidden" name="correct_answers[<?php echo $question['id']; ?>]" value="<?php echo $question['correct_option']; ?>">
                    </div>
                    <?php
                    $questionNumber++;
                }
                ?>
                <input type="hidden" name="readingPassage" value="<?php echo $readingPassage; ?>">
                <button type="submit" class="btn-submit">Finish Exam</button>
            </form>
        </div>
    </div>
    <script>
        // Capture the keydown event
        document.addEventListener('keydown', function(event) {
            // Check if the key combination is Ctrl + R (or Cmd + R on Mac)
            if ((event.ctrlKey || event.metaKey) && event.key === 'r') {
                // Prevent the default behavior (page refresh)
                event.preventDefault();
                // Optionally, you can show a message to the user
                alert("Refresh is disabled on this page.");
            }
        });

        window.addEventListener('popstate', function (event) {
            // When the back button is pressed, push another state to prevent going back
            history.pushState(null, document.title, location.href);
        });

        window.addEventListener('beforeunload', function (e) {
            var confirmationMessage = 'Are you sure you want to leave? Your progress will be lost.';
            e.returnValue = confirmationMessage; // Standard for most browsers
            return confirmationMessage; // For some older browsers
        });

        // Your existing script for the timer and other functionality
        var timeLeft = 3600;

        function updateTimer() {
            var minutes = Math.floor(timeLeft / 60);
            var seconds = timeLeft % 60;
            document.getElementById("timerValue").textContent = minutes + "m " + seconds + "s ";
            if (timeLeft > 0) {
                timeLeft--;
                setTimeout(updateTimer, 1000);
            } else {
                document.querySelector("form").submit();
            }
        }

        updateTimer();
    </script>

<script>
        // Your existing script for preventing page refresh, leaving page, and timer

        // Function to navigate to a specific question
        function goToQuestion(questionNumber) {
            var questionElement = document.getElementById('question' + questionNumber);
            if (questionElement) {
                questionElement.scrollIntoView({ behavior: 'smooth' });
            }
        }
    </script>
</body>
</html>

<?php
} else {
    echo "No questions found for the selected passage.";
}
?>
