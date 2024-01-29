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

// Fetch a single random reading passage with image path
$sqlReading = "SELECT Reading.* FROM Reading WHERE Read_pass IS NOT NULL AND heading_part = 'reading' ORDER BY RAND() LIMIT 1";
$resultReading = $conn->query($sqlReading);

if ($resultReading->num_rows > 0) {
    $rowReading = $resultReading->fetch_assoc();
    $readid =  $rowReading['read_id'];
    $readingPassage = $rowReading['Read_pass'];
    $imagePath = $rowReading['Read_pass'];
    $header = $rowReading['Reading_p_header'];
} else {
    echo "No reading passage found.";
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
       
        .container {
            display: flex;
            justify-content: space-between;
            padding: 20px;
        }

        .reading,
        .questions {
            font-family: 'Nunito', 'Helvetica Neue', Roboto, Helvetica, Arial, sans-serif;
 
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


<style>
       
        .container {
            display: flex;
            justify-content: space-between;
            padding: 20px;
        }

        .reading,
        .questions {
            font-family: 'Nunito', 'Helvetica Neue', Roboto, Helvetica, Arial, sans-serif;
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
            .choose{
                text-align :  left ; 
            }
            .chooses{
                margin-bottom:20px;
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
        .animated-menu {
            animation: fadeInDown 0.8s ease-out;
        }


        .full-screen-element {
            cursor: pointer;
        }
    </style>

<style>
        body {
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        
        }

        .realtest-header {
            background-color: #ffffff;
            color: #36854C;
            padding: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0px 2px 5px rgba(0, 0, 0, 0.2);
        }

        .realtest-header__logo {
            width: 80px;
            height: auto;
        }

        .realtest-header__time {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .realtest-header__time-clock {
            font-size: 24px;
            font-weight: bold;
            padding-left : 50px;
            margin-bottom: 5px;
        }

        .realtest-header__btn-group {
            display: flex;
            align-items: center;
        }

        .realtest-header__btn-group div {
            margin-right: 50px;
            cursor: pointer;
        }

        .realtest-header__btn-save span {
            margin-left: 50px;
        }

        .realtest-header__icon {
            width: 30px;
            height: 30px;
            background-image: url("images/icons8-fullscreen-30.png");
            
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            cursor: pointer;
        }

        .realtest-header__icon.-note {
            background-color: #28a745;
        }

        .realtest-header__icon.-full-screen {
            background-color: #ffcc00;
        }

        .realtest-header__bt-review,
        .realtest-header__bt-submit {
            background-color: #dc3545;
            color: #36854C;
            padding: 10px 20px;
            border: none;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .realtest-header__bt-review:hover,
        .realtest-header__bt-submit:hover {
            background-color: #c82333;
        }
    </style>

    
</head>
<body>
<header class="realtest-header">
        <div class="realtest-header__time" >
            <span class="realtest-header__time-clock" data-time="3600" data-duration-default="3600" id="time-clock">
                <span class="realtest-header__time-val" id="timerValue"></span>
                <span class="realtest-header__time-text">minutes remaining</span>
            </span>
        </div>
        <div class="realtest-header__btn-group">
                    </div>

                    <div class="realtest-header__icon full-screen-element" id="js-full-screen" 
             data-original-title="Full Screen Mode" data-placement="bottom" data-trigger="hover"></div>

    </header>
    <div > <span ></span></div>
    <div class="container">
        <div class="reading">
            <h1 class="">Reading Passage</h1>
            
            <?php
            // Specify the file path
            // $filePath = 'uploads/reading2.txt';

            // Read the content of the text file
            $fileContent = file_get_contents('uploads/' . $imagePath);

            // Output the content
            echo '<pre style="font-family: Roboto, Helvetica, Arial, sans-serif;
                font-size: 14px; white-space: pre-wrap;">' . htmlspecialchars($fileContent) . '</pre>';
            ?>
             <div class="chooses">
                <h2  style="text-align: center ; color :  darkred"> Selectable Question. <br> Find an answer in the reading passage </h2>
            <div class="choose">
            <?php  
                foreach($questionsData as $Choose_text){
                    if(strpos($Choose_text['question_text'], '*') !==false ){ 
                  ?>
                  <p class="mb-2"><?php echo  "\n". $Choose_text['question_text'] ; ?></p>

                    <?php } }?>
        </div>

            </div>
        </div>
            
        <div class="questions">

            <h2 class="mb-4">Questions</h2>
           
            <form action="submit_test.php" method="post">
                <?php
                $questionNumber = 1;
                foreach ($questionsData as $question) {
                    if(strpos($question['question_text'],'*')== false){

                    
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
        document.getElementById("timerValue").textContent = minutes  ;
        if (timeLeft > 0) {
            timeLeft--;
            setTimeout(updateTimer, 1000);
        } else {
            document.querySelector("form").submit();
        }
    }

    updateTimer();

    document.addEventListener("DOMContentLoaded", function () {
            var fullScreenElement = document.querySelector('.full-screen-element');

            // Function to toggle full-screen mode
            function toggleFullScreen() {
                if (!document.fullscreenElement &&    // alternative standard method
                    !document.mozFullScreenElement && !document.webkitFullscreenElement && !document.msFullscreenElement ) {  // current working methods
                    if (document.documentElement.requestFullscreen) {
                        document.documentElement.requestFullscreen();
                    } else if (document.documentElement.msRequestFullscreen) {
                        document.documentElement.msRequestFullscreen();
                    } else if (document.documentElement.mozRequestFullScreen) {
                        document.documentElement.mozRequestFullScreen();
                    } else if (document.documentElement.webkitRequestFullscreen) {
                        document.documentElement.webkitRequestFullscreen(Element.ALLOW_KEYBOARD_INPUT);
                    }
                } else {
                    if (document.exitFullscreen) {
                        document.exitFullscreen();
                    } else if (document.msExitFullscreen) {
                        document.msExitFullscreen();
                    } else if (document.mozCancelFullScreen) {
                        document.mozCancelFullScreen();
                    } else if (document.webkitExitFullscreen) {
                        document.webkitExitFullscreen();
                    }
                }
            }

            // Add click event listener to the full-screen element
            fullScreenElement.addEventListener('click', toggleFullScreen);
        });
</script>
<script>
        // Full screen function
        function toggleFullScreen() {
            if (!document.fullscreenElement &&
                !document.mozFullScreenElement && !document.webkitFullscreenElement && !document.msFullscreenElement) {
                if (document.documentElement.requestFullscreen) {
                    document.documentElement.requestFullscreen();
                } else if (document.documentElement.msRequestFullscreen) {
                    document.documentElement.msRequestFullscreen();
                } else if (document.documentElement.mozRequestFullScreen) {
                    document.documentElement.mozRequestFullScreen();
                } else if (document.documentElement.webkitRequestFullscreen) {
                    document.documentElement.webkitRequestFullscreen(Element.ALLOW_KEYBOARD_INPUT);
                }
            }
        }

        // Trigger full screen on document load
        document.addEventListener("DOMContentLoaded", function () {
            toggleFullScreen();
        });
    </script>
</body>
</html>

<?php
} else {
    echo "No questions found for the selected reading passage.";
}
?>
