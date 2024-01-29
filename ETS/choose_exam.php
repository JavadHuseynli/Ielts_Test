<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exam Options</title>
    <!-- Add Font Awesome CSS (you can include it from a CDN) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">

    <style>
        body {
            font-family: Helvetica, Arial, sans-serif;
            background-color: #f8f9fa;
            margin: 0;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
        }

        .button-container {
            text-align: center;
        }

        .exam-button {
            background-color: #0000;
            color: #0056b3;
            border: none;
            padding: 15px 30px;
            font-size: 1.2rem;
            margin: 10px;
            cursor: pointer;
            border-radius: 8px;
            transition: background-color 0.3s;
        }

        .exam-button:hover {
            background-color: #0056b3;
            color: #fff;
        }

        .examr-button {
            background-color: transparent   ;
            color: red;
            border: none;
            padding: 15px 30px;
            font-size: 1.2rem;
            margin: 10px;
            cursor: pointer;
            border-radius: 8px;
            transition: background-color 0.3s;
        }

        .examr-button:hover {
            background-color: red;
            color: white;
        }

        .typing-animation::after {
            content: "|";
            color:black;    
            animation: blink-caret 0.7s infinite;
        }

        .icon {
            margin-right: 8px;
        }

        @keyframes blink-caret {
            50% {
                opacity: 0;
            }
        }
    </style>
</head>
<body>
    <div class="button-container">
        
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
        session_start();
        $studentId = $_SESSION['istifadeci'];
        // Assuming $_SESSION['student_id'] is set after login
        $sql = "SELECT * FROM student WHERE student_id = $studentId";
        $result = $conn->query($sql);
        
        if ($result === FALSE) {
            echo "Error: " . $conn->error;
        } else {
            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $studentReadScore = $row['reading'];
                $studentListScore = $row['listening'];
                $stdReadingscore =  $row['Student_read_score'];
                $stdListeningscore =  $row['Student_listen_score'];
                $stdentencescore = $row['student_score'];
                $stdname =  $row['student_name'];
                // Output the studentReadScore for debugging
                echo "<h1 id='dynamicText' style='color : #0056b3;' class='typing-animation'>".$stdname. " </h1>";
                // Conditionally render buttons based on Student_read_score
                if ($studentReadScore !='passed' && $studentListScore !='passed' ) {
                    // Both buttons are visible
                    echo '<button class="exam-button" onclick="openReadingExam()">
                            <i class="fas fa-book icon"></i> Reading Exam
                          </button>';
        
                    echo '<button class="examr-button" onclick="openListeningExam()">
                            <i class="fas fa-headphones icon"></i> Listening Exam
                          </button>';
                } else if($studentReadScore == 'passed' && $studentListScore != 'passed') {
                    // Only one of the buttons is visible
                    echo '<button class="examr-button" onclick="openListeningExam()">
                    <i class="fas fa-headphones icon"></i> Listening Exam
                  </button>';
                }
                else if($studentReadScore != 'passed' && $studentListScore == 'passed'){
                    echo '<button class="exam-button" onclick="openReadingExam()">
                    <i class="fas fa-book icon"></i> Reading Exam
                  </button>';
                }else{
                    echo "<p style = 'color: darkred ; font-weight: bold;'> Reading score : "
                       .$stdReadingscore.
                    "</p>
                    <p style = 'color: green ; font-weight: bold;'> Listening score : "
                       .$stdListeningscore.
                    "</p>";
                    $total_score =  $stdListeningscore+$stdReadingscore+$stdentencescore;
                    echo 'you are passed all exam and your total  score : ' . $total_score  ;
                }
            } else {
                echo "No rows found in the result.";
            }
        }
        
        $conn->close();
        ?>

    </div>

    <script>
        function openReadingExam() {
            window.location.href = 'reading_exam.php'; // Replace with the actual URL
        }

        function openListeningExam() {
            window.location.href = 'listening_exam.php'; // Replace with the actual URL
        }

        function typeTextWithDelay(text, elementId) {
            var textElement = document.getElementById(elementId);
            var index = 0;

            function typeOneLetter() {
                if (index < text.length) {
                    textElement.innerHTML += text.charAt(index);
                    index++;
                } else {
                    clearInterval(typeInterval);
                    setTimeout(clearTextLetterByLetter, 1000); // Wait for 1 second before clearing
                }
            }

            var typeInterval = setInterval(typeOneLetter, 100); // Adjust the interval as needed
        }

        function clearTextLetterByLetter() {
            var textElement = document.getElementById('dynamicText');
            var text = textElement.innerHTML;
            var currentIndex = text.length;

            function clearOneLetter() {
                if (currentIndex > 0) {
                    currentIndex--;
                    textElement.innerHTML = text.substring(0, currentIndex);
                } else {
                    clearInterval(backspaceInterval);
                    typeTextWithDelay("Please choose your exam section", 'dynamicText'); // Type the next text
                }
            }

            var backspaceInterval = setInterval(clearOneLetter, 100); // Adjust the interval as needed
        }

        typeTextWithDelay("Hi dear, How are you? ", 'dynamicText');
    </script>
</body>
</html>
