<!doctype html>
<?php
// Enable error reporting for debugging purposes
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start the session (if not already started)
session_start();
?>
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
    <style>
      /* Style for the custom file input button */
      .custom-file-input-btn {
          display: inline-block;
          padding: 8px 16px;
          font-size: 14px;
          font-weight: bold;
          color: #fff;
          background-color: #007bff;
          border: 1px solid #007bff;
          border-radius: 4px;
          cursor: pointer;
      }

      /* Hide the default file input */
      input[type="file"] {
          display: none;
      }

      /* Style for displaying the selected file name */
      #selectedFileName {
          margin-top: 8px;
          font-size: 14px;
          color: #555;
      }
  </style>
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
                <a class="nav-link active" href="addblt.php">
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
                      <i class="material-icons text-danger">&#xE879;</i> Çıxış </a>
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
       
          <div style="margin-top: 20px;" class="main-content-container  px-4">
                 
            <div class="row">
              <div class="col-lg-10 mb">
                <div class="card  mb-4">
                  <div class="card-header border-bottom">
                    <h3 class="">Imtahan Faylları</h3>
                  </div>
                  <ul class="list-group" >
                    
                         <li class="list-group-item p-3">
                      <!-- <div class="row"> -->
                        <!-- <div class="col-sm-12  col-md-6"> -->
                          
                          <form action="reading_file.php" method="post" enctype="multipart/form-data">
                            
                            
                              <div class="form-group ">
                                <label style="font-size: 16px; color: red;" class=" d-block mb-2">* Text və ya audio faylın seçin ! </label>
                                <div class="form-row">
                                
                                <div class="form-group col-md-5" style="margin-top: 0.45% ;;">
                                  <select name="exam_type" id="inputState"  class="form-control">
                                    <option selected>Imtahan novunu secin</option>
                                    <option value="listening">Listening</option>
                                    <option value="reading">Reading</option>
                                    
                                  </select>
                                </div>

                                <div class="form-group col-md-5">
                                  
                                  <div class="input-group-prepend">
                                    
                                    
                                    <input type="text"  placeholder="Mövzunun başlığı"  class="form-control" id="" name="header"/>
                                                                      </div>
                                  
                                </div>
                                <div class="form-group col-md-5">
                                  
                                  <div class="input-group-prepend">
                                    
                                    <label for="fileInput" class="custom-file-input-btn btn btn-sm align-bottom btn-outline-success ">Browse</label>
                                    <input type="file"  accept=".txt ,.mp3" class="form-control" id="fileInput" name="file"/>
                                    <div style="margin-left: 20px; font-size: 18px; font-style: inherit;"  id="selectedFileName"></div>    
                                  </div>
                                  
                                </div>
                                 <div class="form-group col-md-5">
                                  
                                  <div class="input-group-prepend">
                                    
                                    <input type="text" placeholder="müəllimin adı"  class="form-control" id="fileInput" name="t_name"/>
                                    </div>
                                   
                                </div>
                              </div>
                              </div>

                             
                          
                           
                              <div class="form-group">
                                <div class="input-group-prepend">
                               
                                  <div class="col ">
                                    <input type="submit" class="mb-2 btn btn-outline-success mr-2" style="box-shadow: inset 0 0 5px rgba(0,0,0,.2);" value="Faylı bazaya yüklə">
                                  </div>
                                </div>
                              </div>
                          </form>
                        <!-- </div> -->
                        
                      <!-- </div> -->
                    </li></ul>
                  </div>
                </div>
              </div>
         </div>

                  <div class="main-content-container  px-4">
                      <div class="row">
                        <div class="col-lg-10 mb">
                          <div class="card  mb-4">
                            <div class="card-header border-bottom">
                              <h3 class="">Imtahan Sualları</h3>
                            </div>  
                      <ul class="list-group">
                    <li class="list-group-item pt-3">
                    <form action="insert_file.php" method="post" enctype="multipart/form-data">
                    <div class="form-group ">
                                <label style="font-size: 16px; color: red;" class=" d-block mb-2">* Mətn və ya audio faylın seçin ! </label>
        <div class="form-row">
          <div class="form-group col-md-5" style="margin-top: 0.45% ;">
                                
        <label for="inputState">Select Reading:</label>
        <select id="inputState" name="selectedReading" class="form-control">
            <?php
            $servername = "localhost";
            $username = "root";
            $password = "";
            $dbname = "English";

            $conn = new mysqli($servername, $username, $password, $dbname);

            if ($conn->connect_error) {
                die("Connection failed: " . $conn->connect_error);
            }

            $sql = "SELECT read_id, read_pass FROM reading";
            $result = $conn->query($sql);

            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $readingId = $row['read_id'];
                    $readingText = $row['read_pass'];
                    
                    echo "<option value=\"$readingId\"> Id  : $readingId   file is: $readingText</option>";
                }
            } else {
                echo "<option value=\"\">No readings found</option>";
            }

            $conn->close();
            ?>
        </select>
        
        <br>




        <div class="form-group col-md-5">
        <div class="input-group-prepend">
            <label for="fileInputs" class="custom-file-input-btn btn btn-sm align-bottom btn-outline-danger">Select File</label>
            <input type="file" class="form-control" id="fileInputs" name="questionsFile" />
            <div style="margin-left: 20px; font-size: 18px; font-style: inherit;" id="selectedFileNames"></div>
          </div>
          </div>

        <br>

        <input type="submit" name="submit" class="mb-2 btn btn-outline-primary mr-2" style="box-shadow: inset 0 0 5px rgba(0,0,0,.2);" value="Upload Questions">
  
          </div></div>
      </form>
                      </li>
                  </ul>
                </div>
              </div>
              
            </div>
          </div>
         
        </main>
      </div>
    </div>
    <script>
      // JavaScript to trigger the file input click event when the custom button is clicked
      document.getElementById('fileInput').addEventListener('change', handleFileSelect);

      function handleFileSelect(event) {
          const files = event.target.files;
          // Check if any file is selected
          if (files.length > 0) {
              // Display the selected file name
              document.getElementById('selectedFileName').innerText = ` File: ${files[0].name}`;
          } else {
              // No file selected, clear the displayed file name
              document.getElementById('selectedFileName').innerText = '';
          }
      }
  </script>
   <script>
    // JavaScript to trigger the file input click event when the custom button is clicked
    document.getElementById('fileInputs').addEventListener('change', handleFileSelect);

    function handleFileSelect(event) {
        const files = event.target.files;
        // Check if any file is selected
        if (files.length > 0) {
            // Display the selected file name
            document.getElementById('selectedFileNames').innerText = ` File: ${files[0].name}`;
        } else {
            // No file selected, clear the displayed file name
            document.getElementById('selectedFileNames').innerText = '';
        }
    }
</script>
    <script src="https://code.jquery.com/jquery-3.3.1.min.js" integrity="sha256-FgpCb/KJQlLNfOu91ta32o/NMZxltwRo8QtmkMRdAu8=" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.3/umd/popper.min.js" integrity="sha384-ZMP7rVo3mIykV+2+9J3UJ46jBk0WLaUAdn689aCwoqbBJiSnjAK/l8WvCWPIPm49" crossorigin="anonymous"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/js/bootstrap.min.js" integrity="sha384-ChfqqxuZUCnJSK3+MXmPNIyE6ZbWh2IMqE241rYiqJxyMiZ6OW/JmZQ5stwEULTy" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.7.1/Chart.min.js"></script>
    <script src="https://unpkg.com/shards-ui@latest/dist/js/shards.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Sharrre/2.0.1/jquery.sharrre.min.js"></script>
    <script src="scripts/extras.1.1.0.min.js"></script>
    <script src="scripts/shards-dashboards.1.1.0.min.js"></script>
    <script src="scripts/app/app-components-overview.1.1.0.js"></script>
  </body>
</html>