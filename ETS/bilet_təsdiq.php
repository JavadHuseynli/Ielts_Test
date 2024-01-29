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

$sql = "SELECT Reading.*, questions.* FROM Reading
        Right JOIN questions ON Reading.read_id = questions.reading_id
        ORDER BY Reading.read_id ASC";

$result = $conn->query($sql);

if (!$result) {
    echo "Error: " . $sql . "<br>" . $conn->error;
}

$cardData = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $readingID = $row["reading_id"];

        if (!isset($cardData[$readingID])) {
            $cardData[$readingID]['writerName'] = $row["read_writer"];
            $cardData[$readingID]['Passage'] = $row["read_id"];
      
            $cardData[$readingID]['readp']= $row["Read_pass"]; 
            $cardData[$readingID]['questions'] = [];
        }

        if (!is_null($row["question_text"])) {
            $cardData[$readingID]['questions'][] = [
                'question_text' => $row["question_text"],
                'option_a' => $row["option_a"],
                'option_b' => $row["option_b"],
                'option_c' => $row["option_c"],
             
                'correct_option' => $row["correct_option"],
            ];
        }
    }
} else {
    $cardData[] = [
        'readp' => 'Boş',
        'writerName' => 'Boş',
        'Passage' => 'Boş',
        'questions' => [],
    ];
}
session_start();


if (!isset($_SESSION['istifadeci'])) {
  // Redirect to the login page or any other page as needed
  header("Location: index.html");
  exit();
}

// Logout logic
if (isset($_GET['logout'])) {
  // Unset all session variables (replace 'user_id' and 'istifadeci' with your actual session variable names)
  unset($_SESSION['user_id']);
  unset($_SESSION['istifadeci']);

  // Destroy the session data
  session_destroy();

  // Redirect to the login page or any other page you want (replace 'login.php' with your actual login page path)
  header("Location: index.html");
  exit();
}

$conn->close();
?>

<html class="no-js h-100" lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <title>Shards Dashboard Lite - Free Bootstrap Admin Template – DesignRevision</title>
    <meta name="description" content="A high-quality & free Bootstrap admin dashboard template pack that comes with lots of templates and components.">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link href="https://use.fontawesome.com/releases/v5.0.6/css/all.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css" integrity="sha384-MCw98/SFnGE8fJT3GXwEOngsV7Zt27NXFoaoApmYm81iuXoPkFOJwJ8ERdknLPMO" crossorigin="anonymous">
    <link rel="stylesheet" id="main-stylesheet" data-version="1.1.0" href="styles/shards-dashboards.1.1.0.min.css">
    <link rel="stylesheet" href="styles/extras.1.1.0.min.css">
    <script async defer src="https://buttons.github.io/buttons.js"></script>
</head>

<body class="h-100">

    <div class="container-fluid">
        <div class="row">
        <aside class="main-sidebar col-12 col-md-3 col-lg-2 px-0">
          <div class="main-navbar">
            <nav class="navbar align-items-stretch navbar-light bg-white flex-md-nowrap border-bottom p-0">
              <a class="navbar-brand w-100 mr-0" href="#" style="line-height: 25px;">
                <div class="d-table m-auto">
                  <img id="main-logo" class="d-inline-block align-top mr-1" style="max-width: 25px;" src="images/bbu.jpeg" alt="Shards Dashboard">
                  <span class="d-none d-md-inline ml-1">ETS</span>
                </div>
              </a>
              <a class="toggle-sidebar d-sm-inline d-md-none d-lg-none">
                <i class="material-icons">&#xE5C4;</i>
              </a>
            </nav>
          </div>
          <form action="#" class="main-sidebar__search w-100 border-right d-sm-flex d-md-none d-lg-none">
            <div class="input-group input-group-seamless ml-3">
              <div class="input-group-prepend">
                <div class="input-group-text">
                  <i class="fas fa-search"></i>
                </div>
              </div>
              <input class="navbar-search form-control" type="text" placeholder="Search for something..." aria-label="Search"> </div>
          </form>
          <div class="nav-wrapper">
            <ul class="nav flex-column">
              <li class="nav-item">
                <a class="nav-link " href="admin_panel.php">
                  <i class="material-icons">edit</i>
                  <span>Ana Səhifə</span>
                </a>
              </li>
              <li class="nav-item">
                <a class="nav-link active" href="bilet_təsdiq.php">
                  <i class="material-icons">vertical_split</i>
                  <span>Test İmtahanı Yoxla</span>
                </a>
              </li>
              <li class="nav-item">
                <a class="nav-link " href="addblt.php">
                  <i class="material-icons">view_module</i>
                  <span>Biletlərin  hazırlanması</span>
                </a>
              </li>
             
              
              <li class="nav-item">
                <a class="nav-link " href="student_tabel.php">
                  <i class="material-icons">table_chart</i>
                  <span>Sualları Dəyiş</span>
                </a>
              </li>
              <li class="nav-item">
                <a class="nav-link " href="student_tabel.php">
                  <i class="material-icons">table_chart</i>
                  <span>Tələbə məlumatları</span>
                </a>
              </li>
              <li class="nav-item">
                <a class="nav-link " href="add-new-post.html">
                  <i class="material-icons">note_add</i>
                  <span>Yeniliklər</span>
                </a>
              </li>
              <li class="nav-item">
                <a class="nav-link " href="user-profile-lite.html">
                  <i class="material-icons">person</i>
                  <span>Şəxsi  səhifə</span>
                </a>
              </li>
              
            </ul>
          </div>
        </aside>

            <main class="main-content col-lg-10 col-md-9 col-sm-12 p-0 offset-lg-2 offset-md-3">
                <div class="main-navbar sticky-top bg-white">
                    <nav class="navbar align-items-stretch navbar-light flex-md-nowrap p-0">
                        <form action="#" class="main-navbar__search w-100 d-none d-md-flex d-lg-flex">
                            <div class="input-group input-group-seamless ml-3">
                                <div class="input-group-prepend">
                                    <div class="input-group-text">
                                        <i class="fas fa-search"></i>
                                    </div>
                                </div>
                                <input class="navbar-search form-control" type="text" placeholder="Search for something..." aria-label="Search"> </div>
                        </form>
                        <ul class="navbar-nav border-left flex-row ">

                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle text-nowrap px-3" data-toggle="dropdown" href="#" role="button" aria-haspopup="true" aria-expanded="false">
                                    <img class="user-avatar rounded-circle mr-2" src="images/bbu.jpeg" alt="User Avatar">
                                    
                                    <?php
                                    // Start the session (if not already started)
                                    
                                    
                                    if (isset($_SESSION['istifadeci'])) {
                                        echo '<span class="d-none d-md-inline-block"> ' . $_SESSION['istifadeci'] . ' </span>';
                                    } else {
                                        echo '<span class="d-none d-md-inline-block"> Your Logo </span>';
                                    }
                                ?>
                                </a>
                                <div class="dropdown-menu dropdown-menu-small">

                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item text-danger" href="#">
                                        <i class="material-icons text-danger">&#xE879;</i> Logout </a>
                                </div>
                            </li>
                        </ul>
                        <nav class="nav">
                            <a href="#" class="nav-link nav-link-icon toggle-sidebar d-md-inline d-lg-none text-center border-left" data-toggle="collapse" data-target=".header-navbar" aria-expanded="false" aria-controls="header-navbar">
                                <i class="material-icons">&#xE5D2;</i>
                            </a>
                        </nav>
                    </nav>
                </div>

                <div class="main-content-container container-fluid px-4">
                    <div class="page-header row no-gutters py-4">
                        <div class="col-12 col-sm-4 text-center text-sm-left mb-0">
                            <span class="text-uppercase page-subtitle">Müəllim </span>
                            <h3 class="page-title">İmtahan Biletləri</h3>
                        </div>
                    </div>

                    <div class="row">
                    <?php foreach ($cardData as $data): ?>
        <div class="col-lg-3 col-md-6 col-sm-12 mb-4">
        <form action="fetch_questions.php?readingId=<?php echo $data['Passage'];; ?>" method="post">

                <div class="card card-small card-post card-post--1">
                    <div class="card-post__image d-inline-block align-center mr-1" style="width: 90% ;height:30%; background-image: url('images/bbu.jpeg');">
                    <button class="card-post__category badge badge-pill btn btn-danger" data-reading-id="<?php echo $readingID; ?>" data-passage="<?php echo $data['Passage']; ?>"><?php echo $data['Passage']; ?> Təsdiq et</button>
                        <div class="card-post__author d-flex">
                            <a href="#" class="card-post__author-avatar card-post__author-avatar--small" style="background-image: url('images/bbu.jpeg');">
                               
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <h5 class="card-title">
                            <a class="text-fiord-blue" href="#"> <?php echo $data['writerName']; ?></a>
                        </h5>
                        <p class="card-text d-inline-block mb-3" value="<?php echo $data['Passage']; ?>">
                            <?php   
                                     echo $data['readp']; ?>
                        </p><br>
                        <span class="text-muted"></span>
                    </div>
                </div>
            </form>
        </div>
    <?php endforeach; ?>
                    </div>
                </div>
                
            </main>
        </div>
    </div>

    <script>
 $(document).ready(function () {
    $(".show-questions-btn").click(function () {
        var readingId = $(this).data("reading-id");

        // Make an AJAX request to fetch questions based on the reading ID
        $.ajax({
            type: "POST",
            url: "fetch_questions.php",
            data: { readingId: readingId }, // Pass 'readingId' as part of the data
            success: function (response) {
                // Display the questions (you may need to update this part based on your layout)
                alert(JSON.stringify(response)); // Replace with code to display questions in a modal, for example
            },
            error: function (error) {
                console.error(error);
            }
        });
    });
});

</script>


    <script src="https://code.jquery.com/jquery-3.3.1.min.js" integrity="sha256-FgpCb/KJQlLNfOu91ta32o/NMZxltwRo8QtmkMRdAu8=" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.3/umd/popper.min.js" integrity="sha384-ZMP7rVo3mIykV+2+9J3UJ46jBk0WLaUAdn689aCwoqbBJiSnjAK/l8WvCWPIPm49" crossorigin="anonymous"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/js/bootstrap.min.js" integrity="sha384-ChfqqxuZUCnJSK3+MXmPNIyE6ZbWh2IMqE241rYiqJxyMiZ6OW/JmZQ5stwEULTy" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.7.1/Chart.min.js"></script>
    <script src="https://unpkg.com/shards-ui@latest/dist/js/shards.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Sharrre/2.0.1/jquery.sharrre.min.js"></script>
    <script src="scripts/extras.1.1.0.min.js"></script>
    <script src="scripts/shards-dashboards.1.1.0.min.js"></script>
</body>

</html>