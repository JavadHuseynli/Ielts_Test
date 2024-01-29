<?php

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "English";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Search query
if(isset($_GET['search'])) {
    $search = $_GET['search'];
    $sql = "SELECT * FROM student WHERE student_name LIKE '%$search%'";
} else {
    $sql = "SELECT * FROM student";
}

$result = $conn->query($sql);
session_start();
?>


<!doctype html>
<html class="no-js h-100" lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <title>Shards Dashboard Lite - Free Bootstrap Admin Template – DesignRevision</title>
    <meta name="description" content="A high-quality &amp; free Bootstrap admin dashboard template pack that comes with lots of templates and components.">
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
        <!-- Main Sidebar -->
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
                <a class="nav-link " href="bilet_təsdiq.php">
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
                <a class="nav-link active" href="student_tabel.php">
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
        <!-- End Main Sidebar -->
        <main class="main-content col-lg-10 col-md-9 col-sm-12 p-0 offset-lg-2 offset-md-3">
          <div class="main-navbar sticky-top bg-white">
            <!-- Main Navbar -->
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
          <!-- / .main-navbar -->
          <div class="main-content-container container-fluid px-4">
            <!-- Page Header -->
            <div class="page-header row no-gutters py-4">
              <div class="col-12 col-sm-4 text-center text-sm-left mb-0">
                <span class="text-uppercase page-subtitle">Overview</span>
                <h3 class="page-title">Data Tables</h3>
             
              </div>
            </div>
            <!-- End Page Header -->
            <!-- Default Light Table -->
            <div class="row">
              <div class="col">
                <div  style=" max-height: 600px; overflow-y: auto;" class="card card-small mb-4 ">
                  <div class="card-header border-bottom">
                    <h6 class="m-0">Active Users</h6>
                  </div>
                  <div class="card-body p-0 pb-3 text-center">
                    <form class="form-inline mb-4" action="" method="GET">
                      <div style="padding : 10px;" class="input-group">
                        <input type="text" class="form-control" name="search" placeholder="Search...">
                        <div class="input-group-append ml-3">
                          <button class="btn btn-outline-primary" type="submit" name ="search ">Search</button>
                        </div>
        
                        <div  class="input-group-append   ml-3">
                          <button class="btn btn-outline-danger" type="submit"><i class="fas fa-sync-alt"></i> Refresh </button>
                      </div>
                    </div>
                    <div style="padding : 10px;" class="input-group">
               
                    </div>
                   </form>
                   <form action="export.php" class="form-inline mb-4 ml-3" method="Post">
                        <input type="text" class="form-control " name="qrup_no" placeholder="Export by group...">

                    <div  class="input-group-append   ml-3">
                      <button class="btn btn-outline-success" name="export_pdf"  type="submit"><i class="fas fa-file-excel"></i> Export to PDF</button>
                  </div>
                    </form>
                    <table class="table mb-0 ">
                      <thead class="bg-light">
                        <tr>
                          <th scope="col" class="border-0">id</th>
                          <th scope="col" class="border-0">Tam ad</th>
                          <th scope="col" class="border-0">Qrup</th>
                          <th scope="col" class="border-0">Username</th>
                          <th scope="col" class="border-0">Parol</th>
                         
                      
                        </tr>
                      </thead>
                      <tbody>
                      <?php
                        if ($result->num_rows > 0) {
                            while ($rows = $result->fetch_assoc()) {
                                echo "<tr>";
                                echo "<td>" . $rows['student_id'] . "</td>";
                                echo "<td>" . $rows['student_name'] . "</td>";
                                echo "<td>" . $rows['student_group'] . "</td>";
                                echo "<td>" . $rows['student_log'] . "</td>";
                                echo "<td>" . $rows['student_pass'] . "</td>";
                                
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='6'>No results found</td></tr>";
                        }
                        ?>
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
            </div>
            <!-- End Default Light Table -->
            <!-- Default Dark Table -->
            <div class="row">
              <div class="col">
                <div style=" max-height: 600px; overflow-y: auto;" class="card card-small overflow-hidden mb-4 ">
                  <div class="card-header bg-dark">
                    <h6 class="m-0 text-white">Tələbə Məlumatları</h6>
                  </div>
                  <div class="card-body p-0 pb-3 bg-dark text-center">
                    <table class="table table-dark mb-0">
                      <thead class="thead-dark">
                        <tr>
                          <th scope="col" class="border-bottom-0">id</th>
                          <th scope="col" class="border-bottom-0">Tam Ad</th>
                          <th scope="col" class="border-bottom-0">Giriş Bal</th>
                          <th scope="col" class="border-bottom-0">Reading Bal</th>
                          <th scope="col" class="border-bottom-0">Listening Bal </th>
                          <th scope="col" class="border-bottom-0">Cəmi</th>
                        </tr>
                      </thead>
                      <tbody>
                      <?php
                       $results = $conn->query($sql);
                        if ($results->num_rows > 0) {
                            while ($row = $results->fetch_assoc()) {
                                echo "<tr>";
                                echo "<td>" . $row['student_id'] . "</td>";
                                echo "<td>" . $row['student_name'] . "</td>";
                                echo "<td>" . $row['student_score'] . "</td>";
                                echo "<td>" . $row['Student_read_score'] . "</td>";
                                echo "<td>" . $row['Student_listen_score'] . "</td>";
                                $total =  $row['Student_read_score']+ $row['student_score'] + $row['Student_listen_score'];
                                echo "<td>" . $total . "</td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='6'>No results found</td></tr>";
                        }
                        ?>
                        
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
            </div>
            <!-- End Default Dark Table -->
          </div>
          
        </main>
      </div>
    </div>
   
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