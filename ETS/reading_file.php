<?php
$uploadDir = 'uploads/';

$uploadFile = $uploadDir . basename($_FILES['file']['name']);
$audioFile = $uploadDir . basename($_FILES['file']['name']);

// Check if the file is a text or MP3 file
$fileType = pathinfo($uploadFile, PATHINFO_EXTENSION);

// Get the selected exam type
$examType = isset($_POST['exam_type']) ? $_POST['exam_type'] : '';
$T_name  = $_POST['t_name'];
$T_Header = $_POST['header'];
if ($fileType == 'txt' || $fileType == 'mp3') {
    if (move_uploaded_file($_FILES['file']['tmp_name'], $uploadFile)) {
        echo "File is valid and was successfully uploaded.";
        echo "<br>";

        // Read the content of the uploaded text file
        $fileContent = file_get_contents($uploadFile);

        // Connect to the database (replace with your actual credentials)
        $servername = "localhost";
        $username = "root";
        $password = "";
        $dbname = "English";

        $conn = new mysqli($servername, $username, $password, $dbname);

        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }

        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $T_name = $_POST['t_name'];

            // Insert the file details into the database along with the selected exam type
            $filename = $_FILES['file']['name'];
            $sql = "INSERT INTO Reading (Read_pass, heading_part, read_writer,Reading_p_header) VALUES ('$filename', '$examType','$T_name','$T_Header')";

            if ($conn->query($sql) === TRUE) {
                echo "File details inserted into the database.";
            } else {
                echo "Error: " . $sql . "<br>" . $conn->error;
            }
        }

        // Move the MP3 file to the 'audio' folder
        if ($fileType == 'mp3') {
            if (rename($uploadFile, $audioFile)) {
                echo "MP3 file moved to the 'audio' folder.";
            } else {
                echo "Error moving MP3 file to the 'audio' folder.";
            }
        }

        $conn->close();
    } else {
        echo "File upload failed. Check move_uploaded_file error: " . $_FILES['file']['error'];
    }
} else {
    echo "Only text (txt) and MP3 files are allowed.";
    exit();
}
?>
