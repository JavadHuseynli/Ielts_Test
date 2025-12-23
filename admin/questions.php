<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
require_once "../includes/db.php";
require_once "../includes/auth.php";

// Granular Permission Check
checkLogin();
if (!is_admin() && !is_kafedra() && !is_teacher()) {
    header("Location: dashboard.php?error=access_denied");
    exit();
}

$can_manage_questions = is_admin() || is_teacher();
$is_teacher_only = is_teacher() && !is_admin();

// Subject ID from URL
$subject_id = isset($_GET['subject']) ? intval($_GET['subject']) : 0;

// Teacher can only access their own subject
if (is_teacher()) {
    $teacher_subject_id = $_SESSION['subject_id'] ?? 0;
    if ($subject_id == 0) {
        header("Location: questions.php?subject=" . $teacher_subject_id);
        exit();
    }
    if ($subject_id != $teacher_subject_id) {
        header("Location: questions.php?subject=" . $teacher_subject_id . "&error=restricted");
        exit();
    }
}

// Admin/Kafedra must select a subject to view this page
if ($subject_id == 0 && (is_admin() || is_kafedra())) {
    header("Location: subjects.php?error=select_subject");
    exit();
}

$pageTitle = "Sual İdarəetməsi";
include_once "../includes/header.php";

// File uploads üçün qovluq yaratmaq
$uploads_dir = "../uploads";
if (!file_exists($uploads_dir)) {
    mkdir($uploads_dir, 0777, true);
}

$database = new Database();
$db = $database->getConnection();
$message = "";

// Fetch Subject details
$query = "SELECT id_subject, subjectname FROM subjects WHERE id_subject = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(":id", $subject_id);
$stmt->execute();
if ($stmt->rowCount() == 0) {
    header("Location: subjects.php");
    exit();
}
$subject = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch Question Types
$query = "SELECT id_quest_type, quest_type_name, question_var FROM question_types";
$stmt = $db->prepare($query);
$stmt->execute();
$question_types = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --- ACTION HANDLERS (with permission checks) ---

// Sual faylı əlavə etmək
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_file'])) {
    if (!$can_manage_questions) {
        $message = '<div class="alert alert-danger">Bu əməliyyatı etməyə icazəniz yoxdur!</div>';
    } else {
        $file_type = $_POST['file_type'];
        $file_title = trim($_POST['file_title']);
        $file_path = null;
        $upload_ok = true;
        $file_error = "";
        
        if (isset($_FILES['file_upload']) && $_FILES['file_upload']['error'] == 0) {
            $file_temp = $_FILES['file_upload']['tmp_name'];
            $file_name = basename($_FILES['file_upload']['name']);
            $file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            
            if (($file_type == 'reading' && $file_extension != 'txt') || ($file_type == 'listening' && !in_array($file_extension, ['mp3', 'wav', 'ogg']))) {
                $upload_ok = false;
                $file_error = "Yanlış fayl formatı!";
            }
            
            if ($_FILES['file_upload']['size'] > 25 * 1024 * 1024) { // Increased to 25MB
                $upload_ok = false;
                $file_error = "Fayl ölçüsü çox böyükdür (max: 25MB)!";
            }
            
            if ($upload_ok) {
                $unique_filename = uniqid() . '_' . preg_replace("/[^a-zA-Z0-9\-\._]/", "", $file_name);
                $upload_path = $uploads_dir . '/' . $unique_filename;
                if (move_uploaded_file($file_temp, $upload_path)) {
                    $file_path = 'uploads/' . $unique_filename; // Relative path for storage
                } else {
                    $upload_ok = false;
                    $file_error = "Fayl yüklənmə zamanı xəta baş verdi!";
                }
            }
        } else {
            $upload_ok = false;
            $file_error = "Fayl seçilməyib və ya yükləmə xətası: " . ($_FILES['file_upload']['error'] ?? 'Bilinməyən xəta');
        }
        
        if (!$upload_ok) {
            $message = '<div class="alert alert-danger">' . $file_error . '</div>';
        } else {
            try {
                $query = "INSERT INTO question_files (file_type, subject_id, file_title, file_path) VALUES (:file_type, :subject_id, :file_title, :file_path)";
                $stmt = $db->prepare($query);
                if ($stmt->execute([':file_type' => $file_type, ':subject_id' => $subject_id, ':file_title' => $file_title, ':file_path' => $file_path])) {
                    $message = '<div class="alert alert-success">Sual faylı uğurla əlavə edildi!</div>';
                } else {
                    $message = '<div class="alert alert-danger">Sual faylı əlavə edilərkən xəta baş verdi!</div>';
                }
            } catch (PDOException $e) {
                $message = '<div class="alert alert-danger">Verilənlər bazası xətası: ' . $e->getMessage() . '</div>';
            }
        }
    }
}

// Sual faylını redaktə etmək
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit_file'])) {
    if (!$can_manage_questions) {
        $message = '<div class="alert alert-danger">Bu əməliyyatı etməyə icazəniz yoxdur!</div>';
    } else {
        $file_id = $_POST['edit_file_id'];
        $file_title = trim($_POST['edit_file_title']);
        $new_file_type = isset($_POST['edit_file_type']) ? $_POST['edit_file_type'] : null;
        $update_path = false;

        // Fetch current file details
        $query = "SELECT file_path, file_type FROM question_files WHERE id_read_quest_file = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(":id", $file_id);
        $stmt->execute();
        $current_file = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$current_file) {
            $message = '<div class="alert alert-danger">Redaktə üçün fayl tapılmadı!</div>';
        } else {
            $file_path = $current_file['file_path'];
            $file_type = $new_file_type ? $new_file_type : $current_file['file_type'];

            // Check if a new file is uploaded
            if (isset($_FILES['edit_file_upload']) && $_FILES['edit_file_upload']['error'] == 0) {
                $file_temp = $_FILES['edit_file_upload']['tmp_name'];
                $file_name = basename($_FILES['edit_file_upload']['name']);
                $file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                $upload_ok = true;
                $file_error = "";

                if (($file_type == 'reading' && $file_extension != 'txt') || ($file_type == 'listening' && !in_array($file_extension, ['mp3', 'wav', 'ogg']))) {
                    $upload_ok = false;
                    $file_error = "Yanlış fayl formatı!";
                }
                
                if ($_FILES['edit_file_upload']['size'] > 25 * 1024 * 1024) {
                    $upload_ok = false;
                    $file_error = "Fayl ölçüsü çox böyükdür (max: 25MB)!";
                }

                if ($upload_ok) {
                    // Delete old file
                    if (!empty($file_path) && file_exists("../" . $file_path)) {
                        unlink("../" . $file_path);
                    }

                    // Upload new file
                    $unique_filename = uniqid() . '_' . preg_replace("/[^a-zA-Z0-9\-\._]/", "", $file_name);
                    $upload_path = $uploads_dir . '/' . $unique_filename;
                    if (move_uploaded_file($file_temp, $upload_path)) {
                        $file_path = 'uploads/' . $unique_filename;
                        $update_path = true;
                    } else {
                        $upload_ok = false;
                        $file_error = "Yeni fayl yüklənmə zamanı xəta baş verdi!";
                    }
                }

                if (!$upload_ok) {
                    $message = '<div class="alert alert-danger">' . $file_error . '</div>';
                }
            }

            // Update database if no upload error
            if (!isset($file_error) || empty($file_error)) {
                try {
                    if ($update_path) {
                        $query = "UPDATE question_files SET file_title = :file_title, file_path = :file_path, file_type = :file_type WHERE id_read_quest_file = :id";
                        $stmt = $db->prepare($query);
                        $stmt->execute([':file_title' => $file_title, ':file_path' => $file_path, ':file_type' => $file_type, ':id' => $file_id]);
                    } else {
                        $query = "UPDATE question_files SET file_title = :file_title, file_type = :file_type WHERE id_read_quest_file = :id";
                        $stmt = $db->prepare($query);
                        $stmt->execute([':file_title' => $file_title, ':file_type' => $file_type, ':id' => $file_id]);
                    }
                    $message = '<div class="alert alert-success">Sual faylı uğurla yeniləndi!</div>';
                } catch (PDOException $e) {
                    $message = '<div class="alert alert-danger">Verilənlər bazası xətası: ' . $e->getMessage() . '</div>';
                }
            }
        }
    }
}

// Sual əlavə etmək
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_question'])) {
    if (!$can_manage_questions) {
        $message = '<div class="alert alert-danger">Bu əməliyyatı etməyə icazəniz yoxdur!</div>';
    } else {
        $file_id = $_POST['file_id'];
        $question_type = $_POST['question_type'];
        $question_text = trim($_POST['question_text']);
        $question_score = floatval($_POST['question_score']);
        
        if (empty($question_text) || $question_score <= 0) {
            $message = '<div class="alert alert-danger">Sual mətni boş ola bilməz və bal 0-dan böyük olmalıdır!</div>';
        } else {
            try {
                $db->beginTransaction();
                
                $query = "INSERT INTO question_read (id_read_quest_file, id_question_type, question_text, question_score) 
                          VALUES (:file_id, :question_type, :question_text, :question_score)";
                $stmt = $db->prepare($query);
                $stmt->execute([':file_id' => $file_id, ':question_type' => $question_type, ':question_text' => $question_text, ':question_score' => $question_score]);
                $question_id = $db->lastInsertId();
                
                $question_var_query = "SELECT question_var FROM question_types WHERE id_quest_type = :id";
                $stmt = $db->prepare($question_var_query);
                $stmt->bindParam(":id", $question_type);
                $stmt->execute();
                $question_var = $stmt->fetch(PDO::FETCH_ASSOC)['question_var'];

                if ($question_var == 'multiple') {
                    // Validate multiple choice fields
                    if (empty($_POST['var_a']) || empty($_POST['var_b']) || empty($_POST['var_c']) || empty($_POST['var_d'])) {
                        throw new Exception('Bütün variantlar doldurulmalıdır!');
                    }
                    $query = "INSERT INTO multiple_questions (id_question_text, var_a, var_b, var_c, var_d, correct_v)
                              VALUES (:id, :var_a, :var_b, :var_c, :var_d, :correct_v)";
                    $stmt = $db->prepare($query);
                    $stmt->execute([
                        ':id' => $question_id,
                        ':var_a' => trim($_POST['var_a']),
                        ':var_b' => trim($_POST['var_b']),
                        ':var_c' => trim($_POST['var_c']),
                        ':var_d' => trim($_POST['var_d']),
                        ':correct_v' => $_POST['correct_v']
                    ]);
                } elseif ($question_var == 'open') {
                    // Validate open question field
                    if (empty($_POST['corr_v'])) {
                        throw new Exception('Düzgün cavab doldurulmalıdır!');
                    }
                    $query = "INSERT INTO open_questions (id_question_text, corr_v) VALUES (:id, :corr_v)";
                    $stmt = $db->prepare($query);
                    $stmt->execute([':id' => $question_id, ':corr_v' => trim($_POST['corr_v'])]);
                } elseif ($question_var == 'matching') {
                    // Validate matching question fields
                    if (empty($_POST['variants']) || empty($_POST['corr_variant'])) {
                        throw new Exception('Variantlar və düzgün variant doldurulmalıdır!');
                    }
                    $query = "INSERT INTO matching_questions (id_question_text, variants, corr_variant) VALUES (:id, :variants, :corr_variant)";
                    $stmt = $db->prepare($query);
                    $stmt->execute([':id' => $question_id, ':variants' => trim($_POST['variants']), ':corr_variant' => trim($_POST['corr_variant'])]);
                }

                $db->commit();
                $message = '<div class="alert alert-success">Sual uğurla əlavə edildi!</div>';
            } catch (Exception $e) {
                $db->rollBack();
                $message = '<div class="alert alert-danger">Sual əlavə edilərkən xəta: ' . $e->getMessage() . '</div>';
            }
        }
    }
}

// Sual redaktə etmək
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit_question'])) {
    if (!$can_manage_questions) {
        $message = '<div class="alert alert-danger">Bu əməliyyatı etməyə icazəniz yoxdur!</div>';
    } else {
        $question_id = $_POST['edit_question_id'];
        $question_text = trim($_POST['edit_question_text']);
        $question_score = floatval($_POST['edit_question_score']);
        $question_type = $_POST['edit_question_type'];
        
        if (empty($question_text) || $question_score <= 0) {
            $message = '<div class="alert alert-danger">Sual mətni boş ola bilməz və bal 0-dan böyük olmalıdır!</div>';
        } else {
            try {
                $db->beginTransaction();
                
                $query = "UPDATE question_read SET question_text = :question_text, question_score = :question_score 
                          WHERE id_question_text = :question_id";
                $stmt = $db->prepare($query);
                $stmt->execute([':question_text' => $question_text, ':question_score' => $question_score, ':question_id' => $question_id]);
                
                $question_var_query = "SELECT question_var FROM question_types WHERE id_quest_type = :id";
                $stmt = $db->prepare($question_var_query);
                $stmt->bindParam(":id", $question_type);
                $stmt->execute();
                $question_var = $stmt->fetch(PDO::FETCH_ASSOC)['question_var'];
                
                if ($question_var == 'multiple') {
                    $query = "UPDATE multiple_questions SET var_a = :var_a, var_b = :var_b, var_c = :var_c, 
                              var_d = :var_d, correct_v = :correct_v WHERE id_question_text = :question_id";
                    $stmt = $db->prepare($query);
                    $stmt->execute([
                        ':var_a' => $_POST['edit_var_a'],
                        ':var_b' => $_POST['edit_var_b'],
                        ':var_c' => $_POST['edit_var_c'],
                        ':var_d' => $_POST['edit_var_d'],
                        ':correct_v' => $_POST['edit_correct_v'],
                        ':question_id' => $question_id
                    ]);
                } elseif ($question_var == 'open') {
                    $query = "UPDATE open_questions SET corr_v = :corr_v WHERE id_question_text = :question_id";
                    $stmt = $db->prepare($query);
                    $stmt->execute([':corr_v' => $_POST['edit_corr_v'], ':question_id' => $question_id]);
                } elseif ($question_var == 'matching') {
                    $query = "UPDATE matching_questions SET variants = :variants, corr_variant = :corr_variant 
                              WHERE id_question_text = :question_id";
                    $stmt = $db->prepare($query);
                    $stmt->execute([
                        ':variants' => $_POST['edit_variants'],
                        ':corr_variant' => $_POST['edit_corr_variant'],
                        ':question_id' => $question_id
                    ]);
                }
                
                $db->commit();
                $message = '<div class="alert alert-success">Sual uğurla yeniləndi!</div>';
            } catch (Exception $e) {
                $db->rollBack();
                $message = '<div class="alert alert-danger">Sual yenilənərkən xəta: ' . $e->getMessage() . '</div>';
            }
        }
    }
}

// Sual silmək
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    if (!$can_manage_questions) {
        $message = '<div class="alert alert-danger">Bu əməliyyatı etməyə icazəniz yoxdur!</div>';
    } else {
        $question_id = $_GET['delete'];

        try {
            $query = "DELETE FROM question_read WHERE id_question_text = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(":id", $question_id);

            if ($stmt->execute()) {
                $message = '<div class="alert alert-success">Sual uğurla silindi!</div>';
            } else {
                $message = '<div class="alert alert-danger">Sual silinərkən xəta baş verdi!</div>';
            }
        } catch (PDOException $e) {
            $message = '<div class="alert alert-danger">Verilənlər bazası xətası: ' . $e->getMessage() . '</div>';
        }
    }
}

// Sual təsdiq etmək/ləğv etmək
if (isset($_GET['approve']) && is_numeric($_GET['approve'])) {
    if (!$can_manage_questions) {
        $message = '<div class="alert alert-danger">Bu əməliyyatı etməyə icazəniz yoxdur!</div>';
    } else {
        $question_id = $_GET['approve'];
        $approve_status = isset($_GET['status']) ? intval($_GET['status']) : 1;

        try {
            $query = "UPDATE question_read SET approved = :approved WHERE id_question_text = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(":approved", $approve_status);
            $stmt->bindParam(":id", $question_id);

            if ($stmt->execute()) {
                $status_text = $approve_status == 1 ? 'təsdiqləndi' : 'təsdiq ləğv edildi';
                $message = '<div class="alert alert-success">Sual uğurla ' . $status_text . '!</div>';
            } else {
                $message = '<div class="alert alert-danger">Sual təsdiq edilərkən xəta baş verdi!</div>';
            }
        } catch (PDOException $e) {
            $message = '<div class="alert alert-danger">Verilənlər bazası xətası: ' . $e->getMessage() . '</div>';
        }
    }
}

// Bütün sualları təsdiq etmək
if (isset($_GET['approve_all']) && is_numeric($_GET['approve_all'])) {
    if (!$can_manage_questions) {
        $message = '<div class="alert alert-danger">Bu əməliyyatı etməyə icazəniz yoxdur!</div>';
    } else {
        $file_id = $_GET['approve_all'];

        try {
            $query = "UPDATE question_read SET approved = 1 WHERE id_read_quest_file = :file_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(":file_id", $file_id);

            if ($stmt->execute()) {
                $affected = $stmt->rowCount();
                $message = '<div class="alert alert-success">Bütün suallar təsdiqləndi! (' . $affected . ' sual)</div>';
            } else {
                $message = '<div class="alert alert-danger">Suallar təsdiq edilərkən xəta baş verdi!</div>';
            }
        } catch (PDOException $e) {
            $message = '<div class="alert alert-danger">Verilənlər bazası xətası: ' . $e->getMessage() . '</div>';
        }
    }
}

// Fayl silmək
if (isset($_GET['delete_file']) && is_numeric($_GET['delete_file'])) {
    if (!$can_manage_questions) {
        $message = '<div class="alert alert-danger">Bu əməliyyatı etməyə icazəniz yoxdur!</div>';
    } else {
        $file_id = $_GET['delete_file'];
        
        try {
            $query = "SELECT file_path FROM question_files WHERE id_read_quest_file = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(":id", $file_id);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $file_path = $uploads_dir . '/' . basename($stmt->fetch(PDO::FETCH_ASSOC)['file_path']);
                
                if (!empty($file_path) && file_exists($file_path)) {
                    unlink($file_path);
                }
                
                $query = "DELETE FROM question_files WHERE id_read_quest_file = :id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(":id", $file_id);
                
                if ($stmt->execute()) {
                    $message = '<div class="alert alert-success">Sual faylı uğurla silindi!</div>';
                } else {
                    $message = '<div class="alert alert-danger">Sual faylı silinərkən xəta baş verdi!</div>';
                }
            } else {
                $message = '<div class="alert alert-danger">Sual faylı tapılmadı!</div>';
            }
        } catch (PDOException $e) {
            $message = '<div class="alert alert-danger">Verilənlər bazası xətası: ' . $e->getMessage() . '</div>';
        }
    }
}


// Data Fetching for display
$query = "SELECT qf.id_read_quest_file, qf.file_type, qf.file_title, qf.file_path, COUNT(qr.id_question_text) as question_count
          FROM question_files qf
          LEFT JOIN question_read qr ON qf.id_read_quest_file = qr.id_read_quest_file
          WHERE qf.subject_id = :subject_id
          GROUP BY qf.id_read_quest_file
          ORDER BY qf.id_read_quest_file";
$stmt = $db->prepare($query);
$stmt->bindParam(":subject_id", $subject_id);
$stmt->execute();
$files = $stmt->fetchAll(PDO::FETCH_ASSOC);

$active_file = isset($_GET['file']) && !empty($_GET['file']) ? intval($_GET['file']) : (count($files) > 0 ? $files[0]['id_read_quest_file'] : 0);

$active_file_data = null;
if ($active_file > 0) {
    foreach ($files as $file) {
        if ($file['id_read_quest_file'] == $active_file) {
            $active_file_data = $file;
            break;
        }
    }
}

$questions = [];
if ($active_file > 0) {
    $query = "SELECT qr.id_question_text, qr.question_text, qr.question_score, qr.approved,
              qt.quest_type_name, qt.question_var, qt.id_quest_type
              FROM question_read qr
              JOIN question_types qt ON qr.id_question_type = qt.id_quest_type
              WHERE qr.id_read_quest_file = :file_id
              ORDER BY qr.id_question_text";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":file_id", $active_file);
    $stmt->execute();
    $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<div x-data="{
    addFileModal: false,
    editFileModal: false,
    addQuestionModal: false,
    editQuestionModal: false,
    viewQuestionModal: false,
    fileContentVisible: false
}" class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50 p-4 sm:p-6 lg:p-8">
    <div class="max-w-7xl mx-auto">

        <!-- Header Card -->
        <div class="bg-white rounded-2xl shadow-lg border border-gray-200 p-6 mb-6">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                <div class="mb-4 md:mb-0">
                    <h1 class="text-3xl font-bold bg-gradient-to-r from-blue-600 via-indigo-600 to-purple-600 bg-clip-text text-transparent mb-2">Sual İdarəetməsi</h1>
                    <p class="text-lg text-gray-700 font-semibold flex items-center">
                        <svg class="w-5 h-5 mr-2 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                        <?php echo htmlspecialchars($subject['subjectname']); ?>
                    </p>
                </div>
                <div class="flex items-center space-x-2">
                    <a href="subjects.php" class="px-4 py-2.5 bg-gray-600 text-white rounded-xl font-semibold hover:bg-gray-700 transition-all text-sm flex items-center space-x-2 shadow-md hover:shadow-lg">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                        <span>Geri</span>
                    </a>
                    <?php if ($can_manage_questions): ?>
                        <button type="button" onclick="document.getElementById('addFileModal').classList.remove('hidden')" class="px-4 py-2.5 bg-gradient-to-r from-green-600 to-emerald-600 text-white rounded-xl font-semibold hover:from-green-700 hover:to-emerald-700 transition-all text-sm flex items-center space-x-2 shadow-md hover:shadow-lg">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                            <span>Yeni Fayl</span>
                        </button>
                        <?php if ($active_file > 0): ?>
                            <button type="button" @click="addQuestionModal = true" class="px-4 py-2.5 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-xl font-semibold hover:from-indigo-700 hover:to-purple-700 transition-all text-sm flex items-center space-x-2 shadow-md hover:shadow-lg">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                <span>Yeni Sual</span>
                            </button>
                            <?php if ($is_teacher_only): ?>
                            <a href="?subject=<?php echo $subject_id; ?>&file=<?php echo $active_file; ?>&approve_all=<?php echo $active_file; ?>"
                               class="px-4 py-2.5 bg-gradient-to-r from-green-500 to-emerald-500 text-white rounded-xl font-semibold hover:from-green-600 hover:to-emerald-600 transition-all text-sm flex items-center space-x-2 shadow-md hover:shadow-lg"
                               onclick="return confirm('Bu faylın bütün suallarını təsdiq etmək istədiyinizə əminsiniz?')">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                <span>Hamısını Təsdiq Et</span>
                            </a>
                            <?php endif; ?>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php if (!empty($message)): ?>
        <div class="mb-6">
            <?php
            $message_type = 'info';
            if (strpos($message, 'success') !== false) $message_type = 'success';
            if (strpos($message, 'danger') !== false) $message_type = 'error';

            $icon = [
                'success' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>',
                'error' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>',
                'info' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>',
            ];
            ?>
            <div class="bg-gradient-to-r <?php echo $message_type === 'success' ? 'from-green-500 to-emerald-600' : ($message_type === 'error' ? 'from-red-500 to-pink-600' : 'from-blue-500 to-indigo-600'); ?> text-white p-4 rounded-2xl shadow-xl" role="alert">
                <div class="flex items-center space-x-3">
                    <svg class="w-6 h-6 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><?php echo $icon[$message_type]; ?></svg>
                    <p class="font-semibold"><?php echo strip_tags($message); ?></p>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- File Tabs -->
        <?php if (!empty($files)): ?>
        <div class="mb-6">
            <div class="bg-white rounded-2xl shadow-lg border border-gray-200 p-2">
                <nav class="flex flex-wrap gap-2" aria-label="Tabs">
                    <?php foreach ($files as $file): ?>
                        <div class="group relative <?php echo $file['id_read_quest_file'] == $active_file ? 'bg-gradient-to-r from-indigo-500 to-purple-500 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?> rounded-xl py-3 px-4 font-medium text-sm flex items-center transition-all shadow-sm hover:shadow-md">
                            <a href="?subject=<?php echo $subject_id; ?>&file=<?php echo $file['id_read_quest_file']; ?>" class="flex items-center space-x-2 <?php echo $file['id_read_quest_file'] == $active_file ? 'text-white' : 'text-gray-700'; ?>">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                <span><?php echo htmlspecialchars(!empty($file['file_title']) ? $file['file_title'] : 'Adsız Fayl'); ?></span>
                            </a>
                            <span class="ml-3 <?php echo $file['id_read_quest_file'] == $active_file ? 'bg-white/20 text-white' : 'bg-gray-200 text-gray-700'; ?> inline-flex items-center justify-center py-0.5 px-2.5 rounded-full text-xs font-bold">
                                <?php echo $file['question_count']; ?>
                            </span>
                            <?php if ($can_manage_questions && $file['id_read_quest_file'] == $active_file): ?>
                                <div class="flex items-center ml-2 space-x-1">
                                    <button type="button"
                                       onclick="event.stopPropagation(); openFileEditModal(<?php echo $file['id_read_quest_file']; ?>, '<?php echo htmlspecialchars($file['file_title'], ENT_QUOTES); ?>', '<?php echo $active_file_data['file_type']; ?>');"
                                       class="p-1 rounded-lg hover:bg-white/20 transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.5L15.232 5.232z"></path></svg>
                                    </button>
                                    <a href="?subject=<?php echo $subject_id; ?>&file=<?php echo $active_file; ?>&delete_file=<?php echo $file['id_read_quest_file']; ?>"
                                      class="p-1 rounded-lg hover:bg-white/20 transition-colors flex items-center" onclick="event.stopPropagation(); return confirm('Bu faylı və ona aid bütün sualları silmək istədiyinizə əminsiniz?')">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </nav>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($active_file_data): ?>
        <div class="bg-white rounded-2xl shadow-lg border border-gray-200 p-6 mb-6">
            <div class="flex flex-col space-y-4">
                <!-- File Header -->
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <div class="w-14 h-14 bg-gradient-to-br <?php echo $active_file_data['file_type'] == 'reading' ? 'from-blue-500 to-cyan-500' : 'from-purple-500 to-pink-500'; ?> rounded-xl flex items-center justify-center shadow-lg">
                            <?php if ($active_file_data['file_type'] == 'reading'): ?>
                                <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                            <?php else: ?>
                                <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z"></path></svg>
                            <?php endif; ?>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-gray-900"><?php echo htmlspecialchars($active_file_data['file_title']); ?></h3>
                            <div class="flex items-center space-x-3 mt-2">
                                <span class="inline-flex items-center px-3 py-1 <?php echo $active_file_data['file_type'] == 'reading' ? 'bg-blue-100 text-blue-700 border border-blue-200' : 'bg-purple-100 text-purple-700 border border-purple-200'; ?> rounded-lg text-xs font-semibold">
                                    <?php echo $active_file_data['file_type'] == 'reading' ? '📖 Oxu' : '🎧 Dinləmə'; ?>
                                </span>
                                <div class="flex items-center text-sm text-gray-600">
                                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    <span class="font-medium"><?php echo $active_file_data['question_count']; ?> sual</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- File Content Display -->
                <?php if (!empty($active_file_data['file_path']) && file_exists("../" . $active_file_data['file_path'])): ?>
                    <?php if ($active_file_data['file_type'] == 'reading'): ?>
                        <!-- Reading File Content -->
                        <div class="border-t border-gray-200 pt-4">
                            <div class="flex items-center justify-between mb-3">
                                <h4 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">Mətn Məzmunu</h4>
                                <button type="button" onclick="toggleFileContent()" class="text-sm text-blue-600 hover:text-blue-800 font-medium flex items-center space-x-1">
                                    <span id="toggleText">Göstər</span>
                                    <svg id="toggleIcon" class="w-4 h-4 transform transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                </button>
                            </div>
                            <div id="fileContent" class="hidden bg-gradient-to-br from-gray-50 to-blue-50 rounded-xl p-6 border border-gray-200 max-h-96 overflow-y-auto">
                                <pre class="whitespace-pre-wrap text-gray-800 text-sm leading-relaxed font-mono"><?php echo htmlspecialchars(file_get_contents("../" . $active_file_data['file_path'])); ?></pre>
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- Audio Player -->
                        <div class="border-t border-gray-200 pt-4">
                            <h4 class="text-sm font-semibold text-gray-700 uppercase tracking-wide mb-3">Audio Oynadıcı</h4>
                            <div class="bg-gradient-to-br from-gray-50 to-purple-50 rounded-xl p-6 border border-gray-200">
                                <audio controls class="w-full" style="filter: saturate(1.5) hue-rotate(290deg);">
                                    <source src="<?php echo htmlspecialchars("../" . $active_file_data['file_path']); ?>" type="audio/mpeg">
                                    <source src="<?php echo htmlspecialchars("../" . $active_file_data['file_path']); ?>" type="audio/wav">
                                    <source src="<?php echo htmlspecialchars("../" . $active_file_data['file_path']); ?>" type="audio/ogg">
                                    Brauzeriniz audio dəstəkləmir.
                                </audio>
                                <div class="mt-3 flex items-center justify-center space-x-2 text-sm text-gray-600">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    <span>Audio faylı yuxarıdakı oynadıcıdan dinləyin</span>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="border-t border-gray-200 pt-4">
                        <button
                            type="button"
                            onclick="openFileEditModal(<?php echo $active_file_data['id_read_quest_file']; ?>, '<?php echo htmlspecialchars($active_file_data['file_title'], ENT_QUOTES); ?>', '<?php echo $active_file_data['file_type']; ?>');"
                            class="w-full bg-red-50 border border-red-200 rounded-xl p-4 flex items-center space-x-3 text-left hover:bg-red-100 transition-colors cursor-pointer">
                            <svg class="w-6 h-6 text-red-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                            <div>
                                <p class="text-red-700 font-semibold">Fayl tapılmadı və ya yüklənməyib.</p>
                                <p class="text-red-600 text-sm mt-1">Yeni fayl yükləmək və ya başlığı redaktə etmək üçün klikləyin.</p>
                            </div>
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <script>
        function toggleFileContent() {
            const content = document.getElementById('fileContent');
            const icon = document.getElementById('toggleIcon');
            const text = document.getElementById('toggleText');

            if (content.classList.contains('hidden')) {
                content.classList.remove('hidden');
                icon.style.transform = 'rotate(180deg)';
                text.textContent = 'Gizlət';
            } else {
                content.classList.add('hidden');
                icon.style.transform = 'rotate(0deg)';
                text.textContent = 'Göstər';
            }
        }
        </script>
        <?php endif; ?>

        <?php if (empty($files)): ?>
            <div class="bg-white text-center rounded-lg shadow-md p-12">
                <h3 class="text-lg font-medium text-gray-900">Fayl Tapılmadı</h3>
                <p class="mt-2 text-sm text-gray-500">Bu fənn üçün hələ heç bir sual faylı əlavə edilməyib. Başlamaq üçün "Yeni Fayl" düyməsini sıxın.</p>
            </div>
        <?php elseif (empty($questions)): ?>
             <div class="bg-white text-center rounded-lg shadow-md p-12">
                <h3 class="text-lg font-medium text-gray-900">Sual Tapılmadı</h3>
                <p class="mt-2 text-sm text-gray-500">Bu fayl üçün hələ heç bir sual əlavə edilməyib. Başlamaq üçün "Yeni Sual" düyməsini sıxın.</p>
            </div>
        <?php else: ?>
            <!-- Questions List -->
            <div class="space-y-4">
                <?php foreach ($questions as $question): ?>
                <div class="bg-white rounded-lg shadow-sm hover:shadow-md transition-shadow duration-300 border border-gray-200 <?php echo $question['approved'] == 1 ? 'border-l-4 border-l-green-500' : 'border-l-4 border-l-yellow-500'; ?>">
                    <div class="p-5">
                        <div class="flex justify-between items-start">
                            <div class="flex-grow pr-4">
                                <div class="flex items-center gap-2 mb-1">
                                    <p class="text-sm text-gray-600 font-medium"><?php echo htmlspecialchars($question['quest_type_name']); ?></p>
                                    <?php if ($question['approved'] == 1): ?>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-green-100 text-green-800 border border-green-200">
                                            ✓ Təsdiqlənib
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-yellow-100 text-yellow-800 border border-yellow-200">
                                            ⏳ Gözləyir
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <p class="text-gray-800 mt-1"><?php echo nl2br(htmlspecialchars($question['question_text'])); ?></p>
                            </div>
                            <div class="flex-shrink-0 flex flex-col sm:flex-row sm:items-center sm:space-x-3">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-indigo-100 text-indigo-800 mb-2 sm:mb-0">
                                    <?php echo htmlspecialchars($question['question_score']); ?> bal
                                </span>
                                <div class="flex items-center space-x-1">
                                    <button type="button" class="p-2 text-gray-500 hover:text-blue-600 bg-gray-100 hover:bg-blue-50 rounded-full view-question"
                                       onclick="fetchQuestionDetails(<?php echo $question['id_question_text']; ?>, 'view')">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                    </button>
                                    <?php if ($is_teacher_only): ?>
                                        <?php if ($question['approved'] == 0): ?>
                                            <a href="?subject=<?php echo $subject_id; ?>&file=<?php echo $active_file; ?>&approve=<?php echo $question['id_question_text']; ?>&status=1"
                                               class="p-2 text-gray-500 hover:text-green-600 bg-gray-100 hover:bg-green-50 rounded-full"
                                               title="Təsdiq et">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                            </a>
                                        <?php else: ?>
                                            <a href="?subject=<?php echo $subject_id; ?>&file=<?php echo $active_file; ?>&approve=<?php echo $question['id_question_text']; ?>&status=0"
                                               class="p-2 text-gray-500 hover:text-yellow-600 bg-gray-100 hover:bg-yellow-50 rounded-full"
                                               title="Təsdiqi ləğv et">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                            </a>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    <?php if ($can_manage_questions): ?>
                                        <button type="button" class="p-2 text-gray-500 hover:text-blue-600 bg-gray-100 hover:bg-blue-50 rounded-full edit-question"
                                           onclick="fetchQuestionDetails(<?php echo $question['id_question_text']; ?>, 'edit')">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.5L15.232 5.232z"></path></svg>
                                        </button>
                                        <a href="?subject=<?php echo $subject_id; ?>&file=<?php echo $active_file; ?>&delete=<?php echo $question['id_question_text']; ?>"
                                           class="p-2 text-gray-500 hover:text-red-600 bg-gray-100 hover:bg-red-50 rounded-full" onclick="return confirm('Bu sualı silmək istədiyinizə əminsiniz?')">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

<!-- Add Question Modal -->
    <div x-show="addQuestionModal" @keydown.escape.window="addQuestionModal = false" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4" x-cloak>
        <div @click.away="addQuestionModal = false" class="bg-white rounded-2xl max-w-2xl w-full shadow-2xl" x-show="addQuestionModal" x-transition>
            <div class="flex items-center justify-between p-6 border-b border-gray-200">
                <h3 class="text-xl font-bold text-gray-900">Yeni Sual Əlavə Et</h3>
                <button @click="addQuestionModal = false" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            <form method="post" action="">
                <div class="p-6 space-y-4 max-h-[70vh] overflow-y-auto">
                    <input type="hidden" name="file_id" value="<?php echo $active_file; ?>">
                    <div>
                        <label for="question_type" class="block text-sm font-semibold text-gray-700 mb-2">Sual tipi</label>
                        <select class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition duration-200 ease-in-out" id="question_type" name="question_type" required onchange="toggleQuestionFields()">
                            <option value="">Seçin</option>
                            <?php foreach ($question_types as $type): ?>
                                <option value="<?php echo $type['id_quest_type']; ?>" data-var="<?php echo $type['question_var']; ?>"><?php echo $type['quest_type_name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="question_text" class="block text-sm font-semibold text-gray-700 mb-2">Sual mətni</label>
                        <textarea class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition duration-200 ease-in-out" id="question_text" name="question_text" rows="4" required></textarea>
                    </div>
                    <div>
                        <label for="question_score" class="block text-sm font-semibold text-gray-700 mb-2">Bal</label>
                        <input type="number" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition duration-200 ease-in-out" id="question_score" name="question_score" min="0.5" step="0.5" value="1" required>
                    </div>

                    <div id="multiple_fields" style="display: none;" class="space-y-4 p-4 border-t border-gray-200">
                        <h4 class="font-semibold text-gray-800">Çoxseçimli Variantlar</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="var_a" class="block text-sm font-medium text-gray-700">Variant A <span class="text-red-500">*</span></label>
                                <input type="text" class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 transition duration-200 ease-in-out" id="var_a" name="var_a">
                            </div>
                            <div>
                                <label for="var_b" class="block text-sm font-medium text-gray-700">Variant B <span class="text-red-500">*</span></label>
                                <input type="text" class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 transition duration-200 ease-in-out" id="var_b" name="var_b">
                            </div>
                            <div>
                                <label for="var_c" class="block text-sm font-medium text-gray-700">Variant C <span class="text-red-500">*</span></label>
                                <input type="text" class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 transition duration-200 ease-in-out" id="var_c" name="var_c">
                            </div>
                            <div>
                                <label for="var_d" class="block text-sm font-medium text-gray-700">Variant D <span class="text-red-500">*</span></label>
                                <input type="text" class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 transition duration-200 ease-in-out" id="var_d" name="var_d">
                            </div>
                        </div>
                        <div>
                            <label for="correct_v" class="block text-sm font-medium text-gray-700">Düzgün variant <span class="text-red-500">*</span></label>
                            <select class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 transition duration-200 ease-in-out" id="correct_v" name="correct_v">
                                <option value="a">A</option>
                                <option value="b">B</option>
                                <option value="c">C</option>
                                <option value="d">D</option>
                            </select>
                        </div>
                    </div>

                    <div id="open_fields" style="display: none;" class="space-y-4 p-4 border-t border-gray-200">
                         <h4 class="font-semibold text-gray-800">Açıq Sual</h4>
                        <div>
                            <label for="corr_v" class="block text-sm font-medium text-gray-700">Düzgün cavab <span class="text-red-500">*</span></label>
                            <input type="text" class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 transition duration-200 ease-in-out" id="corr_v" name="corr_v">
                        </div>
                    </div>

                    <div id="matching_fields" style="display: none;" class="space-y-4 p-4 border-t border-gray-200">
                        <h4 class="font-semibold text-gray-800">Uyğunlaşdırma</h4>
                        <div>
                            <label for="variants" class="block text-sm font-medium text-gray-700">Variantlar (vergüllə ayırın) <span class="text-red-500">*</span></label>
                            <input type="text" class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 transition duration-200 ease-in-out" id="variants" name="variants" placeholder="variant1,variant2,variant3">
                            <p class="mt-2 text-xs text-gray-500">Məsələn: alma,armud,banan</p>
                        </div>
                        <div>
                            <label for="corr_variant" class="block text-sm font-medium text-gray-700">Düzgün variant <span class="text-red-500">*</span></label>
                            <input type="text" class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 transition duration-200 ease-in-out" id="corr_variant" name="corr_variant" placeholder="düzgün variantı qeyd edin">
                            <p class="mt-2 text-xs text-gray-500">Yuxarıdakı variantlardan biri olmalıdır</p>
                        </div>
                    </div>
                </div>
                <div class="flex space-x-3 p-6 bg-gray-50 rounded-b-2xl">
                    <button type="button" @click="addQuestionModal = false" class="flex-1 px-4 py-2.5 bg-gray-200 text-gray-800 rounded-xl font-semibold hover:bg-gray-300 transition-all duration-200 ease-in-out">Ləğv et</button>
                    <button type="submit" name="add_question" class="flex-1 px-4 py-2.5 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-xl font-semibold hover:from-indigo-700 hover:to-purple-700 hover:shadow-lg transition-all duration-200 ease-in-out">Əlavə et</button>
                </div>
            </form>
        </div>
    </div>

    </div>
</div>

<?php if ($can_manage_questions): ?>
<!-- Add File Modal -->
<div id="addFileModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4" onclick="if(event.target === this) this.classList.add('hidden')">
    <div class="bg-white rounded-2xl max-w-md w-full shadow-2xl" onclick="event.stopPropagation()">
        <div class="flex items-center justify-between p-6 border-b border-gray-200">
            <h3 class="text-xl font-bold text-gray-900">Yeni Sual Faylı Əlavə Et</h3>
            <button onclick="document.getElementById('addFileModal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600 transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        <form method="post" action="" enctype="multipart/form-data">
            <div class="p-6 space-y-4">
                <div>
                    <label for="file_title" class="block text-sm font-semibold text-gray-700 mb-2">Fayl başlığı</label>
                    <input type="text" id="file_title" name="file_title" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                </div>
                <div>
                    <label for="file_type" class="block text-sm font-semibold text-gray-700 mb-2">Fayl növü</label>
                    <select id="file_type" name="file_type" required onchange="toggleFileExtension()" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                        <option value="reading">Oxu</option>
                        <option value="listening">Dinləmə</option>
                    </select>
                </div>
                <div>
                    <label for="file_upload" class="block text-sm font-semibold text-gray-700 mb-2">Fayl yüklə</label>
                    <input type="file" id="file_upload" name="file_upload" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                    <p class="mt-2 text-xs text-gray-500" id="file_extension_help">Oxu üçün yalnız .txt faylları qəbul edilir.</p>
                </div>
            </div>
            <div class="flex space-x-3 p-6 border-t border-gray-200">
                <button type="button" onclick="document.getElementById('addFileModal').classList.add('hidden')" class="flex-1 px-4 py-2 bg-gray-200 text-gray-700 rounded-xl font-semibold hover:bg-gray-300 transition-all">
                    Ləğv et
                </button>
                <button type="submit" name="add_file" class="flex-1 px-4 py-2 question-gradient text-white rounded-xl font-semibold hover:shadow-lg transition-all">
                    Əlavə et
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Edit File Modal -->
<div x-show="editFileModal" @keydown.escape.window="editFileModal = false" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4" x-cloak>
    <div @click.away="editFileModal = false" class="bg-white rounded-2xl max-w-md w-full shadow-2xl" x-show="editFileModal" x-transition>
        <div class="flex items-center justify-between p-6 border-b border-gray-200">
            <h3 class="text-xl font-bold text-gray-900">Faylı Redaktə Et</h3>
            <button @click="editFileModal = false" class="text-gray-400 hover:text-gray-600 transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
        <form method="post" action="" enctype="multipart/form-data">
            <div class="p-6 space-y-4">
                <input type="hidden" name="edit_file_id" id="edit_file_id">
                <div>
                    <label for="edit_file_title" class="block text-sm font-semibold text-gray-700 mb-2">Fayl başlığı</label>
                    <input type="text" id="edit_file_title" name="edit_file_title" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-transparent">
                </div>
                <div>
                    <label for="edit_file_type" class="block text-sm font-semibold text-gray-700 mb-2">Fayl növü</label>
                    <select id="edit_file_type" name="edit_file_type" required onchange="toggleEditFileExtension()" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-transparent">
                        <option value="reading">Oxu</option>
                        <option value="listening">Dinləmə</option>
                    </select>
                </div>
                <div>
                    <label for="edit_file_upload" class="block text-sm font-semibold text-gray-700 mb-2">Yeni fayl yüklə (istəyə bağlı)</label>
                    <input type="file" id="edit_file_upload" name="edit_file_upload" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-transparent">
                    <p class="mt-2 text-xs text-gray-500" id="edit_file_extension_help">Faylı dəyişdirmək istəmirsinizsə, bu sahəni boş buraxın.</p>
                </div>
            </div>
            <div class="flex space-x-3 p-6 border-t border-gray-200">
                <button type="button" @click="editFileModal = false" class="flex-1 px-4 py-2 bg-gray-200 text-gray-700 rounded-xl font-semibold hover:bg-gray-300 transition-all">
                    Ləğv et
                </button>
                <button type="submit" name="edit_file" class="flex-1 px-4 py-2 bg-gradient-to-r from-yellow-500 to-amber-500 text-white rounded-xl font-semibold hover:shadow-lg transition-all">
                    Yenilə
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Question Modal -->
    <div x-show="editQuestionModal" @keydown.escape.window="editQuestionModal = false" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4" x-cloak>
        <div @click.away="editQuestionModal = false" class="bg-white rounded-2xl max-w-2xl w-full shadow-2xl" x-show="editQuestionModal" x-transition>
            <div class="flex items-center justify-between p-6 border-b border-gray-200">
                <h5 class="text-xl font-bold text-gray-900">Sualı Redaktə Et</h5>
                <button @click="editQuestionModal = false" class="text-gray-400 hover:text-gray-600 transition-colors">
                     <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            <form method="post" action="">
                <div class="p-6 space-y-4 max-h-[70vh] overflow-y-auto">
                    <input type="hidden" name="edit_question_id" id="edit_question_id">
                    <input type="hidden" name="edit_question_type" id="edit_question_type">
                    
                    <div>
                        <label for="edit_question_text" class="block text-sm font-semibold text-gray-700 mb-2">Sual mətni</label>
                        <textarea class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-transparent transition duration-200 ease-in-out" id="edit_question_text" name="edit_question_text" rows="4" required></textarea>
                    </div>
                    
                    <div>
                        <label for="edit_question_score" class="block text-sm font-semibold text-gray-700 mb-2">Bal</label>
                        <input type="number" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-transparent transition duration-200 ease-in-out" id="edit_question_score" name="edit_question_score" min="0.5" step="0.5" required>
                    </div>
                    
                    <div id="edit_multiple_fields" style="display: none;" class="space-y-4 p-4 border-t border-gray-200">
                        <h4 class="font-semibold text-gray-800">Çoxseçimli Variantlar</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="edit_var_a" class="block text-sm font-medium text-gray-700">Variant A</label>
                                <input type="text" class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-yellow-500 focus:border-yellow-500 transition duration-200 ease-in-out" id="edit_var_a" name="edit_var_a">
                            </div>
                            <div>
                                <label for="edit_var_b" class="block text-sm font-medium text-gray-700">Variant B</label>
                                <input type="text" class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-yellow-500 focus:border-yellow-500 transition duration-200 ease-in-out" id="edit_var_b" name="edit_var_b">
                            </div>
                            <div>
                                <label for="edit_var_c" class="block text-sm font-medium text-gray-700">Variant C</label>
                                <input type="text" class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-yellow-500 focus:border-yellow-500 transition duration-200 ease-in-out" id="edit_var_c" name="edit_var_c">
                            </div>
                            <div>
                                <label for="edit_var_d" class="block text-sm font-medium text-gray-700">Variant D</label>
                                <input type="text" class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-yellow-500 focus:border-yellow-500 transition duration-200 ease-in-out" id="edit_var_d" name="edit_var_d">
                            </div>
                        </div>
                        <div>
                            <label for="edit_correct_v" class="block text-sm font-medium text-gray-700">Düzgün variant</label>
                            <select class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-yellow-500 focus:border-yellow-500 transition duration-200 ease-in-out" id="edit_correct_v" name="edit_correct_v">
                                <option value="a">A</option>
                                <option value="b">B</option>
                                <option value="c">C</option>
                                <option value="d">D</option>
                            </select>
                        </div>
                    </div>
                    
                    <div id="edit_open_fields" style="display: none;" class="space-y-4 p-4 border-t border-gray-200">
                        <h4 class="font-semibold text-gray-800">Açıq Sual</h4>
                        <div>
                            <label for="edit_corr_v" class="block text-sm font-medium text-gray-700">Düzgün cavab</label>
                            <input type="text" class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-yellow-500 focus:border-yellow-500 transition duration-200 ease-in-out" id="edit_corr_v" name="edit_corr_v">
                        </div>
                    </div>
                    
                    <div id="edit_matching_fields" style="display: none;" class="space-y-4 p-4 border-t border-gray-200">
                        <h4 class="font-semibold text-gray-800">Uyğunlaşdırma</h4>
                        <div>
                            <label for="edit_variants" class="block text-sm font-medium text-gray-700">Variantlar (vergüllə ayırın)</label>
                            <input type="text" class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-yellow-500 focus:border-yellow-500 transition duration-200 ease-in-out" id="edit_variants" name="edit_variants" placeholder="variant1,variant2,variant3">
                        </div>
                        <div>
                            <label for="edit_corr_variant" class="block text-sm font-medium text-gray-700">Düzgün variant</label>
                            <input type="text" class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-yellow-500 focus:border-yellow-500 transition duration-200 ease-in-out" id="edit_corr_variant" name="edit_corr_variant">
                        </div>
                    </div>
                </div>
                <div class="flex space-x-3 p-6 bg-gray-50 rounded-b-2xl">
                    <button type="button" @click="editQuestionModal = false" class="flex-1 px-4 py-2.5 bg-gray-200 text-gray-800 rounded-xl font-semibold hover:bg-gray-300 transition-all duration-200 ease-in-out">Ləğv et</button>
                    <button type="submit" name="edit_question" class="flex-1 px-4 py-2.5 bg-gradient-to-r from-yellow-500 to-amber-500 text-white rounded-xl font-semibold hover:from-yellow-600 hover:to-amber-600 hover:shadow-lg transition-all duration-200 ease-in-out">Yenilə</button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<!-- View Question Modal -->
    <div x-show="viewQuestionModal" @keydown.escape.window="viewQuestionModal = false" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4" x-cloak>
        <div @click.away="viewQuestionModal = false" class="bg-white rounded-2xl max-w-2xl w-full shadow-2xl" x-show="viewQuestionModal" x-transition>
            <div class="flex items-center justify-between p-6 border-b border-gray-200">
                <h5 class="text-xl font-bold text-gray-900">Sual Məlumatları</h5>
                <button @click="viewQuestionModal = false" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            <div class="p-6 space-y-4 max-h-[70vh] overflow-y-auto">
                <div>
                    <h5 class="text-sm font-semibold text-gray-500 uppercase tracking-wider">Sual Mətni</h5>
                    <p id="view_question_text" class="mt-1 text-gray-800 text-base leading-relaxed bg-gray-50 p-3 rounded-lg"></p>
                </div>
                
                <div>
                    <h5 class="text-sm font-semibold text-gray-500 uppercase tracking-wider">Bal</h5>
                    <p class="mt-1 text-gray-800 text-base bg-gray-50 p-3 rounded-lg"><span id="view_question_score"></span></p>
                </div>
                
                <div id="view_multiple_fields" style="display: none;" class="space-y-3 pt-4 border-t border-gray-200">
                    <h5 class="text-sm font-semibold text-gray-500 uppercase tracking-wider">Variantlar</h5>
                    <ul id="multiple_options_list" class="space-y-2"></ul>
                </div>
                
                <div id="view_open_fields" style="display: none;" class="space-y-2 pt-4 border-t border-gray-200">
                    <h5 class="text-sm font-semibold text-gray-500 uppercase tracking-wider">Düzgün Cavab</h5>
                    <p id="view_correct_answer" class="mt-1 text-green-700 text-base bg-green-50 p-3 rounded-lg"></p>
                </div>
                
                <div id="view_matching_fields" style="display: none;" class="space-y-4 pt-4 border-t border-gray-200">
                    <div>
                        <h5 class="text-sm font-semibold text-gray-500 uppercase tracking-wider">Variantlar</h5>
                        <p id="view_variants" class="mt-1 text-gray-800 text-base bg-gray-50 p-3 rounded-lg"></p>
                    </div>
                    <div>
                        <h5 class="text-sm font-semibold text-gray-500 uppercase tracking-wider">Düzgün Variant</h5>
                        <p id="view_correct_variant" class="mt-1 text-green-700 text-base bg-green-50 p-3 rounded-lg"></p>
                    </div>
                </div>
            </div>
            <div class="flex justify-end p-6 bg-gray-50 rounded-b-2xl">
                <button type="button" @click="viewQuestionModal = false" class="px-6 py-2.5 bg-gray-600 text-white rounded-xl font-semibold hover:bg-gray-700 transition-all duration-200 ease-in-out">Bağla</button>
            </div>
        </div>
    </div>

<!-- Old Bootstrap modal removed - file content now displays inline -->

<script>
function toggleFileExtension() {
    var fileType = document.getElementById('file_type');
    if (!fileType) return;
    var fileExtensionHelp = document.getElementById('file_extension_help');

    if (fileType.value === 'reading') {
        fileExtensionHelp.textContent = 'Oxu üçün yalnız .txt faylları qəbul edilir.';
    } else if (fileType.value === 'listening') {
        fileExtensionHelp.textContent = 'Dinləmə üçün yalnız .mp3, .wav, .ogg faylları qəbul edilir.';
    }
}

function toggleEditFileExtension() {
    var editFileType = document.getElementById('edit_file_type');
    if (!editFileType) return;
    var editFileExtensionHelp = document.getElementById('edit_file_extension_help');

    if (editFileType.value === 'reading') {
        editFileExtensionHelp.textContent = 'Oxu üçün yalnız .txt faylları qəbul edilir.';
    } else if (editFileType.value === 'listening') {
        editFileExtensionHelp.textContent = 'Dinləmə üçün yalnız .mp3, .wav, .ogg faylları qəbul edilir.';
    }
}

function openFileEditModal(fileId, fileTitle, fileType) {
    // Set form values
    document.getElementById('edit_file_id').value = fileId;
    document.getElementById('edit_file_title').value = fileTitle;
    document.getElementById('edit_file_type').value = fileType;
    document.getElementById('edit_file_upload').value = '';

    // Update the help text
    toggleEditFileExtension();

    // Open modal using Alpine.js
    const alpineEl = document.querySelector('[x-data]');
    if (alpineEl && alpineEl._x_dataStack) {
        alpineEl._x_dataStack[0].editFileModal = true;
    }
}

function toggleQuestionFields() {
    var questionType = document.getElementById('question_type');
    var selectedOption = questionType.options[questionType.selectedIndex];
    var questionVar = selectedOption ? selectedOption.getAttribute('data-var') : null;

    // Toggle display
    document.getElementById('multiple_fields').style.display = questionVar === 'multiple' ? 'block' : 'none';
    document.getElementById('open_fields').style.display = questionVar === 'open' ? 'block' : 'none';
    document.getElementById('matching_fields').style.display = questionVar === 'matching' ? 'block' : 'none';

    // Toggle required attributes for multiple choice fields
    ['var_a', 'var_b', 'var_c', 'var_d'].forEach(function(id) {
        var field = document.getElementById(id);
        if (field) {
            if (questionVar === 'multiple') {
                field.setAttribute('required', 'required');
            } else {
                field.removeAttribute('required');
            }
        }
    });

    // Toggle required attribute for open question field
    var corrV = document.getElementById('corr_v');
    if (corrV) {
        if (questionVar === 'open') {
            corrV.setAttribute('required', 'required');
        } else {
            corrV.removeAttribute('required');
        }
    }

    // Toggle required attributes for matching question fields
    ['variants', 'corr_variant'].forEach(function(id) {
        var field = document.getElementById(id);
        if (field) {
            if (questionVar === 'matching') {
                field.setAttribute('required', 'required');
            } else {
                field.removeAttribute('required');
            }
        }
    });
}

function toggleEditQuestionFields(questionVar) {
    document.getElementById('edit_multiple_fields').style.display = questionVar === 'multiple' ? 'block' : 'none';
    document.getElementById('edit_open_fields').style.display = questionVar === 'open' ? 'block' : 'none';
    document.getElementById('edit_matching_fields').style.display = questionVar === 'matching' ? 'block' : 'none';
}

document.addEventListener('DOMContentLoaded', function() {
    toggleFileExtension();
    toggleQuestionFields();
});

function fetchQuestionDetails(questionId, mode) {
    const xhr = new XMLHttpRequest();
    xhr.open('GET', `get_question_details.php?id=${questionId}`, true);
    xhr.onload = function() {
        if (this.status === 200) {
            try {
                const response = JSON.parse(this.responseText);
                if (response.error) {
                    console.error("Error fetching details:", response.error);
                    alert("Xəta: " + response.error);
                    return;
                }
                if (mode === 'view') {
                    populateViewModal(response);
                    // Open view modal using Alpine.js
                    const alpineEl = document.querySelector('[x-data]');
                    if (alpineEl && alpineEl._x_dataStack) {
                        alpineEl._x_dataStack[0].viewQuestionModal = true;
                    }
                } else if (mode === 'edit') {
                    populateEditModal(response);
                    // Open edit modal using Alpine.js
                    const alpineEl = document.querySelector('[x-data]');
                    if (alpineEl && alpineEl._x_dataStack) {
                        alpineEl._x_dataStack[0].editQuestionModal = true;
                    }
                }
            } catch (e) {
                console.error("Error parsing JSON:", e, this.responseText);
                alert("JSON xətası: " + e.message);
            }
        } else {
            console.error("HTTP Error:", this.status);
            alert("Server xətası: " + this.status);
        }
    };
    xhr.onerror = function() {
        console.error("Request failed");
        alert("Sorğu uğursuz oldu!");
    };
    xhr.send();
}

function populateViewModal(data) {
    document.getElementById('view_question_text').innerHTML = data.question_text.replace(/\n/g, '<br>');
    document.getElementById('view_question_score').textContent = data.question_score;

    // Hide all fields first
    document.getElementById('view_multiple_fields').style.display = 'none';
    document.getElementById('view_open_fields').style.display = 'none';
    document.getElementById('view_matching_fields').style.display = 'none';

    if (data.question_var === 'multiple') {
        document.getElementById('view_multiple_fields').style.display = 'block';
        const optionsList = document.getElementById('multiple_options_list');
        optionsList.innerHTML = '';
        
        const options = [
            { label: 'A', value: data.var_a },
            { label: 'B', value: data.var_b },
            { label: 'C', value: data.var_c },
            { label: 'D', value: data.var_d }
        ];
        
        options.forEach(option => {
            const isCorrect = option.label.toLowerCase() === data.correct_v;
            const li = document.createElement('li');
            li.className = `p-3 rounded-lg flex items-center justify-between ${isCorrect ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'}`;
            li.innerHTML = `<span><strong>${option.label})</strong> ${option.value || ''}</span> ${isCorrect ? '<span class="text-xs font-bold px-2 py-1 bg-green-500 text-white rounded-full">DÜZGÜN</span>' : ''}`;
            optionsList.appendChild(li);
        });
    } else if (data.question_var === 'open') {
        document.getElementById('view_open_fields').style.display = 'block';
        document.getElementById('view_correct_answer').textContent = data.corr_v || '';
    } else if (data.question_var === 'matching') {
        document.getElementById('view_matching_fields').style.display = 'block';
        document.getElementById('view_variants').textContent = data.variants || '';
        document.getElementById('view_correct_variant').textContent = data.corr_variant || '';
    }
}

function populateEditModal(data) {
    document.getElementById('edit_question_id').value = data.id_question_text;
    document.getElementById('edit_question_type').value = data.id_quest_type;
    document.getElementById('edit_question_text').value = data.question_text;
    document.getElementById('edit_question_score').value = data.question_score;
    
    toggleEditQuestionFields(data.question_var);
    
    if (data.question_var === 'multiple') {
        document.getElementById('edit_var_a').value = data.var_a || '';
        document.getElementById('edit_var_b').value = data.var_b || '';
        document.getElementById('edit_var_c').value = data.var_c || '';
        document.getElementById('edit_var_d').value = data.var_d || '';
        document.getElementById('edit_correct_v').value = data.correct_v || 'a';
    } else if (data.question_var === 'open') {
        document.getElementById('edit_corr_v').value = data.corr_v || '';
    } else if (data.question_var === 'matching') {
        document.getElementById('edit_variants').value = data.variants || '';
        document.getElementById('edit_corr_variant').value = data.corr_variant || '';
    }
}
</script>

<?php
include_once "../includes/footer.php"; 
?>