<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $servername = "localhost";
    $username_db = "root";
    $password_db = "";
    $dbname = "ENGLISH";

    $conn = new mysqli($servername, $username_db, $password_db, $dbname);

    if ($conn->connect_error) {
        echo "Error: " . $conn->connect_error;
        die("Connection failed: " . $conn->connect_error);
    }

    $conn->set_charset("utf8mb4"); // Set the character set

    // Sanitize user input to prevent SQL injection
    $username = $conn->real_escape_string($_POST['username']);
    $password = $conn->real_escape_string($_POST['password']);

    // Example of selecting user data based on username
    // $sqlSelect = "SELECT * FROM user WHERE u_username = '$username'";
    // $resultSelect = $conn->query($sqlSelect);
    $sqlJoin = "SELECT t.* , u.* FROM user u Left join teacher t on t.teach_userid = u.u_id where u.u_username = '$username';" ; 
             
    $resultSelect = $conn->query($sqlJoin);
    if ($resultSelect->num_rows > 0) {
        // Fetch the user data
        $userData = $resultSelect->fetch_assoc();
        // Verify the hashed password
        if ( $userData['u_password']== $password  ) {
              if($userData['u_status']== "admin"){
                session_start();
                $_SESSION["istifadeci"] = $userData['teach_fname'];
                
                // Redirect to admin_panel.html
                header("Location: admin_panel.php");
                exit();
              }else if($userData['u_status'] == "student"){
                session_start();
                $_SESSION["istifadeci"] = $userData['teach_fname'];
                
                // Redirect to admin_panel.html
                header("Location: choose_exam.php");
                exit();
              }
           
        } else {
            echo "Invalid password.";
        }
    } else {
        echo "User not found.";
    }

    $conn->close();
}
?>
