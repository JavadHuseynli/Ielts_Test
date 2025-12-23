<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
require_once "../includes/db.php";
require_once "../includes/auth.php";
checkAdminRights();

$pageTitle = "Sual İdarəetməsi";
include_once "../includes/header.php";

// File uploads üçün qovluq yaratmaq
$uploads_dir = "../uploads";
if (!file_exists($uploads_dir)) {
    mkdir($uploads_dir, 0777, true);
}

$database = new Database();
$db = $database->getConnection();

// Mesaj dəyişəni
$message = "";

// Fənn ID-si
$subject_id = isset($_GET['subject']) ? intval($_GET['subject']) : 0;

// Fənn məlumatlarını almaq
if ($subject_id > 0) {
    $query = "SELECT id_subject, subjectname FROM subjects WHERE id_subject = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":id", $subject_id);
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        header("Location: subjects.php");
        exit();
    }
    
    $subject = $stmt->fetch(PDO::FETCH_ASSOC);
} else {
    header("Location: subjects.php");
    exit();
}

// Sual tipləri
$query = "SELECT id_quest_type, quest_type_name, question_var FROM question_types";
$stmt = $db->prepare($query);
$stmt->execute();
$question_types = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Sual faylı əlavə etmək - title və file_path ilə
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_file'])) {
    $file_type = $_POST['file_type'];
    $file_title = trim($_POST['file_title']);
    
    // Fayl yüklənməsini yoxla
    $file_path = null;
    $upload_ok = true;
    $file_error = "";
    
    if (isset($_FILES['file_upload']) && $_FILES['file_upload']['error'] == 0) {
        $file_temp = $_FILES['file_upload']['tmp_name'];
        $file_name = basename($_FILES['file_upload']['name']);
        $file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        // Fayl tipini yoxla
        if ($file_type == 'reading' && $file_extension != 'txt') {
            $upload_ok = false;
            $file_error = "Oxu üçün yalnız .txt faylları qəbul edilir!";
        } elseif ($file_type == 'listening' && $file_extension != 'mp3') {
            $upload_ok = false;
            $file_error = "Dinləmə üçün yalnız .mp3 faylları qəbul edilir!";
        }
        
        // Fayl ölçüsünü yoxla - max 10MB
        if ($_FILES['file_upload']['size'] > 10 * 1024 * 1024) {
            $upload_ok = false;
            $file_error = "Fayl ölçüsü çox böyükdür (max: 10MB)!";
        }
        
        // Fayl yüklənməsi
        if ($upload_ok) {
            $unique_filename = uniqid() . '_' . $file_name;
            $upload_path = $uploads_dir . '/' . $unique_filename;
            
            if (move_uploaded_file($file_temp, $upload_path)) {
                $file_path = $upload_path;
            } else {
                $upload_ok = false;
                $file_error = "Fayl yüklənmə zamanı xəta baş verdi!";
            }
        }
    } else {
        $upload_ok = false;
        $file_error = "Fayl seçilməyib!";
    }
    
    // Əgər fayl yüklənmə xətası varsa
    if (!$upload_ok) {
        $message = '<div class="alert alert-danger">' . $file_error . '</div>';
    } else {
        try {
            // Title və file_path əlavə edildi
            $query = "INSERT INTO question_files (file_type, subject_id, file_title, file_path) 
                      VALUES (:file_type, :subject_id, :file_title, :file_path)";
            $stmt = $db->prepare($query);
            $stmt->bindParam(":file_type", $file_type);
            $stmt->bindParam(":subject_id", $subject_id);
            $stmt->bindParam(":file_title", $file_title);
            $stmt->bindParam(":file_path", $file_path);
            
            if ($stmt->execute()) {
                $message = '<div class="alert alert-success">Sual faylı uğurla əlavə edildi!</div>';
            } else {
                $message = '<div class="alert alert-danger">Sual faylı əlavə edilərkən xəta baş verdi!</div>';
            }
        } catch (PDOException $e) {
            $message = '<div class="alert alert-danger">Verilənlər bazası xətası: ' . $e->getMessage() . '</div>';
        }
    }
}

// Sual əlavə etmək
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_question'])) {
    $file_id = $_POST['file_id'];
    $question_type = $_POST['question_type'];
    $question_text = trim($_POST['question_text']);
    $question_score = floatval($_POST['question_score']);
    
    if (empty($question_text) || $question_score <= 0) {
        $message = '<div class="alert alert-danger">Sual mətni boş ola bilməz və bal 0-dan böyük olmalıdır!</div>';
    } else {
        try {
            $db->beginTransaction();
            
            // Sualı əlavə etmək
            $query = "INSERT INTO question_read (id_read_quest_file, id_question_type, question_text, question_score) 
                      VALUES (:file_id, :question_type, :question_text, :question_score)";
            $stmt = $db->prepare($query);
            $stmt->bindParam(":file_id", $file_id);
            $stmt->bindParam(":question_type", $question_type);
            $stmt->bindParam(":question_text", $question_text);
            $stmt->bindParam(":question_score", $question_score);
            $stmt->execute();
            
            $question_id = $db->lastInsertId();
            
            // Sual tipinə görə variant əlavə etmək
            $question_var_query = "SELECT question_var FROM question_types WHERE id_quest_type = :id";
            $stmt = $db->prepare($question_var_query);
            $stmt->bindParam(":id", $question_type);
            $stmt->execute();
            $question_var = $stmt->fetch(PDO::FETCH_ASSOC)['question_var'];
            
            if ($question_var == 'multiple') {
                $var_a = trim($_POST['var_a']);
                $var_b = trim($_POST['var_b']);
                $var_c = trim($_POST['var_c']);
                $var_d = trim($_POST['var_d']);
                $correct_v = $_POST['correct_v'];
                
                $query = "INSERT INTO multiple_questions (id_question_text, var_a, var_b, var_c, var_d, correct_v) 
                          VALUES (:id_question_text, :var_a, :var_b, :var_c, :var_d, :correct_v)";
                $stmt = $db->prepare($query);
                $stmt->bindParam(":id_question_text", $question_id);
                $stmt->bindParam(":var_a", $var_a);
                $stmt->bindParam(":var_b", $var_b);
                $stmt->bindParam(":var_c", $var_c);
                $stmt->bindParam(":var_d", $var_d);
                $stmt->bindParam(":correct_v", $correct_v);
                $stmt->execute();
            } elseif ($question_var == 'open') {
                $corr_v = trim($_POST['corr_v']);
                
                if (empty($corr_v)) {
                    throw new Exception("Doğru cavab boş ola bilməz!");
                }
                
                $query = "INSERT INTO open_questions (id_question_text, corr_v) 
                          VALUES (:id_question_text, :corr_v)";
                $stmt = $db->prepare($query);
                $stmt->bindParam(":id_question_text", $question_id);
                $stmt->bindParam(":corr_v", $corr_v);
                $stmt->execute();
            } elseif ($question_var == 'matching') {
                $variants = trim($_POST['variants']);
                $corr_variant = trim($_POST['corr_variant']);
                
                if (empty($variants) || empty($corr_variant)) {
                    throw new Exception("Variantlar və doğru variant boş ola bilməz!");
                }
                
                $query = "INSERT INTO matching_questions (id_question_text, variants, corr_variant) 
                          VALUES (:id_question_text, :variants, :corr_variant)";
                $stmt = $db->prepare($query);
                $stmt->bindParam(":id_question_text", $question_id);
                $stmt->bindParam(":variants", $variants);
                $stmt->bindParam(":corr_variant", $corr_variant);
                $stmt->execute();
            }
            
            $db->commit();
            $message = '<div class="alert alert-success">Sual uğurla əlavə edildi!</div>';
        } catch (Exception $e) {
            $db->rollBack();
            $message = '<div class="alert alert-danger">Sual əlavə edilərkən xəta: ' . $e->getMessage() . '</div>';
        }
    }
}

// Sual redaktə etmək - YENİ FUNKSİYA
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit_question'])) {
    $question_id = $_POST['edit_question_id'];
    $question_text = trim($_POST['edit_question_text']);
    $question_score = floatval($_POST['edit_question_score']);
    $question_type = $_POST['edit_question_type'];
    
    if (empty($question_text) || $question_score <= 0) {
        $message = '<div class="alert alert-danger">Sual mətni boş ola bilməz və bal 0-dan böyük olmalıdır!</div>';
    } else {
        try {
            $db->beginTransaction();
            
            // Sualı yeniləmək
            $query = "UPDATE question_read SET question_text = :question_text, question_score = :question_score 
                      WHERE id_question_text = :question_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(":question_text", $question_text);
            $stmt->bindParam(":question_score", $question_score);
            $stmt->bindParam(":question_id", $question_id);
            $stmt->execute();
            
            // Sual tipinə görə variant yeniləmək
            $question_var_query = "SELECT question_var FROM question_types WHERE id_quest_type = :id";
            $stmt = $db->prepare($question_var_query);
            $stmt->bindParam(":id", $question_type);
            $stmt->execute();
            $question_var = $stmt->fetch(PDO::FETCH_ASSOC)['question_var'];
            
            if ($question_var == 'multiple') {
                $var_a = trim($_POST['edit_var_a']);
                $var_b = trim($_POST['edit_var_b']);
                $var_c = trim($_POST['edit_var_c']);
                $var_d = trim($_POST['edit_var_d']);
                $correct_v = $_POST['edit_correct_v'];
                
                $query = "UPDATE multiple_questions SET var_a = :var_a, var_b = :var_b, var_c = :var_c, 
                          var_d = :var_d, correct_v = :correct_v WHERE id_question_text = :question_id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(":var_a", $var_a);
                $stmt->bindParam(":var_b", $var_b);
                $stmt->bindParam(":var_c", $var_c);
                $stmt->bindParam(":var_d", $var_d);
                $stmt->bindParam(":correct_v", $correct_v);
                $stmt->bindParam(":question_id", $question_id);
                $stmt->execute();
            } elseif ($question_var == 'open') {
                $corr_v = trim($_POST['edit_corr_v']);
                
                if (empty($corr_v)) {
                    throw new Exception("Doğru cavab boş ola bilməz!");
                }
                
                $query = "UPDATE open_questions SET corr_v = :corr_v WHERE id_question_text = :question_id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(":corr_v", $corr_v);
                $stmt->bindParam(":question_id", $question_id);
                $stmt->execute();
            } elseif ($question_var == 'matching') {
                $variants = trim($_POST['edit_variants']);
                $corr_variant = trim($_POST['edit_corr_variant']);
                
                if (empty($variants) || empty($corr_variant)) {
                    throw new Exception("Variantlar və doğru variant boş ola bilməz!");
                }
                
                $query = "UPDATE matching_questions SET variants = :variants, corr_variant = :corr_variant 
                          WHERE id_question_text = :question_id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(":variants", $variants);
                $stmt->bindParam(":corr_variant", $corr_variant);
                $stmt->bindParam(":question_id", $question_id);
                $stmt->execute();
            }
            
            $db->commit();
            $message = '<div class="alert alert-success">Sual uğurla yeniləndi!</div>';
        } catch (Exception $e) {
            $db->rollBack();
            $message = '<div class="alert alert-danger">Sual yenilənərkən xəta: ' . $e->getMessage() . '</div>';
        }
    }
}

// Sual silmək
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
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

// Fayl silmək
if (isset($_GET['delete_file']) && is_numeric($_GET['delete_file'])) {
    $file_id = $_GET['delete_file'];
    
    try {
        // Əvvəlcə fayl yolunu almaq
        $query = "SELECT file_path FROM question_files WHERE id_read_quest_file = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(":id", $file_id);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $file_path = $stmt->fetch(PDO::FETCH_ASSOC)['file_path'];
            
            // Əgər fayl mövcuddursa, silmək
            if (!empty($file_path) && file_exists($file_path)) {
                unlink($file_path);
            }
            
            // Verilənlər bazasından silmək
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

// Sual fayllarını almaq - title ilə birlikdə
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

// Aktiv fayl
$active_file = isset($_GET['file']) ? intval($_GET['file']) : (count($files) > 0 ? $files[0]['id_read_quest_file'] : 0);

// Aktiv fayl məlumatları
$active_file_data = null;
if ($active_file > 0) {
    foreach ($files as $file) {
        if ($file['id_read_quest_file'] == $active_file) {
            $active_file_data = $file;
            break;
        }
    }
}

// Sualları almaq
$questions = [];
if ($active_file > 0) {
    $query = "SELECT qr.id_question_text, qr.question_text, qr.question_score, 
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

// Verilənlər bazasına title sütunu əlavə etmək üçün SQL kodu - əgər yoxdursa
try {
    $query = "SHOW COLUMNS FROM question_files LIKE 'file_title'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        $query = "ALTER TABLE question_files ADD COLUMN file_title VARCHAR(255) NULL AFTER file_type";
        $db->exec($query);
    }
} catch (PDOException $e) {
    // Xəta kontrolu - fayl artıq mövcud ola bilər
}
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h1>Sual İdarəetməsi: <?php echo $subject['subjectname']; ?></h1>
    </div>
    <div class="col-md-6 text-end">
        <a href="subjects.php" class="btn btn-secondary me-2">
            <i class="bi bi-arrow-left"></i> Fənnlərə qayıt
        </a>
        <button type="button" class="btn btn-success me-2" data-bs-toggle="modal" data-bs-target="#addFileModal">
            <i class="bi bi-file-earmark-plus"></i> Yeni Fayl
        </button>
        <?php if ($active_file > 0): ?>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addQuestionModal">
                <i class="bi bi-plus-circle"></i> Yeni Sual
            </button>
        <?php endif; ?>
    </div>
</div>

<?php echo $message; ?>

<!-- Aktiv fayl məlumatları -->
<?php if ($active_file_data): ?>
<div class="card mb-4">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0">Aktiv fayl: <?php echo $active_file_data['file_title']; ?></h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <p><strong>Fayl növü:</strong> <?php echo $active_file_data['file_type'] == 'reading' ? 'Oxu' : 'Dinləmə'; ?></p>
                <p><strong>Sual sayı:</strong> <?php echo $active_file_data['question_count']; ?></p>
            </div>
            <div class="col-md-6">
                <?php if (!empty($active_file_data['file_path']) && file_exists($active_file_data['file_path'])): ?>
                    <?php if ($active_file_data['file_type'] == 'reading'): ?>
                        <div class="mb-3">
                            <a href="<?php echo $active_file_data['file_path']; ?>" target="_blank" class="btn btn-sm btn-info">
                                <i class="bi bi-file-text"></i> Mətni Aç
                            </a>
                            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#viewFileModal">
                                <i class="bi bi-eye"></i> Mətni Bax
                            </button>
                        </div>
                    <?php else: ?>
                        <div class="mb-3">
                            <audio controls>
                                <source src="<?php echo $active_file_data['file_path']; ?>" type="audio/mpeg">
                                Sizin brauzeriniz audio tag-ı dəstəkləmir.
                            </audio>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <p class="text-muted">Fayl tapılmadı.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Fayllar tabs -->
<ul class="nav nav-tabs mb-4">
    <?php foreach ($files as $file): ?>
        <li class="nav-item">
            <a class="nav-link <?php echo $file['id_read_quest_file'] == $active_file ? 'active' : ''; ?>" 
               href="?subject=<?php echo $subject_id; ?>&file=<?php echo $file['id_read_quest_file']; ?>">
                <?php echo !empty($file['file_title']) ? $file['file_title'] : ($file['file_type'] == 'reading' ? 'Oxu' : 'Dinləmə'); ?> 
                (<?php echo $file['question_count']; ?> sual)
                <?php if ($file['id_read_quest_file'] == $active_file): ?>
                    <a href="?subject=<?php echo $subject_id; ?>&delete_file=<?php echo $file['id_read_quest_file']; ?>" 
                      class="text-danger ms-2" onclick="return confirm('Bu faylı silmək istədiyinizə əminsiniz?')">
                        <i class="bi bi-x-circle"></i>
                    </a>
                <?php endif; ?>
            </a>
        </li>
    <?php endforeach; ?>
</ul>

<?php if (empty($files)): ?>
    <div class="alert alert-info">
        Bu fənn üçün hələ heç bir sual faylı əlavə edilməyib. Sual əlavə etmək üçün əvvəlcə "Yeni Fayl" düyməsini sıxın.
    </div>
<?php elseif (empty($questions)): ?>
    <div class="alert alert-info">
        Bu fayl üçün hələ heç bir sual əlavə edilməyib. Sual əlavə etmək üçün "Yeni Sual" düyməsini sıxın.
    </div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Sual</th>
                    <th>Tip</th>
                    <th>Bal</th>
                    <th>Əməliyyatlar</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($questions as $question): ?>
                    <tr>
                        <td><?php echo $question['id_question_text']; ?></td>
                        <td><?php echo nl2br(substr($question['question_text'], 0, 100)) . (strlen($question['question_text']) > 100 ? '...' : ''); ?></td>
                        <td><?php echo $question['quest_type_name']; ?></td>
                        <td><?php echo $question['question_score']; ?></td>
                        <td>
                            <div class="btn-group" role="group">
                                <a href="#" class="btn btn-sm btn-info view-question" data-bs-toggle="modal" data-bs-target="#viewQuestionModal" 
                                   data-id="<?php echo $question['id_question_text']; ?>" 
                                   data-text="<?php echo htmlspecialchars($question['question_text']); ?>"
                                   data-type="<?php echo $question['question_var']; ?>"
                                   data-score="<?php echo $question['question_score']; ?>">
                                    <i class="bi bi-eye"></i> Bax
                                </a>
                                <a href="#" class="btn btn-sm btn-warning edit-question" data-bs-toggle="modal" data-bs-target="#editQuestionModal"
                                   data-id="<?php echo $question['id_question_text']; ?>"
                                   data-text="<?php echo htmlspecialchars($question['question_text']); ?>"
                                   data-type="<?php echo $question['question_var']; ?>"
                                   data-type-id="<?php echo $question['id_quest_type']; ?>"
                                   data-score="<?php echo $question['question_score']; ?>">
                                    <i class="bi bi-pencil"></i> Redaktə
                                </a>
                                <a href="?subject=<?php echo $subject_id; ?>&file=<?php echo $active_file; ?>&delete=<?php echo $question['id_question_text']; ?>" 
                                   class="btn btn-sm btn-danger" onclick="return confirm('Bu sualı silmək istədiyinizə əminsiniz?')">
                                    <i class="bi bi-trash"></i> Sil
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<!-- Fayl Əlavə Et Modal -->
<div class="modal fade" id="addFileModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Yeni Sual Faylı Əlavə Et</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" action="" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="file_title" class="form-label">Fayl başlığı</label>
                        <input type="text" class="form-control" id="file_title" name="file_title" required>
                    </div>
                    <div class="mb-3">
                        <label for="file_type" class="form-label">Fayl növü</label>
                        <select class="form-select" id="file_type" name="file_type" required onchange="toggleFileExtension()">
                            <option value="reading">Oxu</option>
                            <option value="listening">Dinləmə</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="file_upload" class="form-label">Fayl yüklə</label>
                        <input type="file" class="form-control" id="file_upload" name="file_upload" required>
                        <div class="form-text" id="file_extension_help">Oxu üçün yalnız .txt faylları qəbul edilir.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Ləğv et</button>
                    <button type="submit" name="add_file" class="btn btn-primary">Əlavə et</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Sual Əlavə Et Modal -->
<div class="modal fade" id="addQuestionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Yeni Sual Əlavə Et</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" action="">
                <div class="modal-body">
                    <input type="hidden" name="file_id" value="<?php echo $active_file; ?>">
                    
                    <div class="mb-3">
                        <label for="question_type" class="form-label">Sual tipi</label>
                        <select class="form-select" id="question_type" name="question_type" required onchange="toggleQuestionFields()">
                            <option value="">Seçin</option>
                            <?php foreach ($question_types as $type): ?>
                                <option value="<?php echo $type['id_quest_type']; ?>" data-var="<?php echo $type['question_var']; ?>">
                                    <?php echo $type['quest_type_name']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="question_text" class="form-label">Sual mətni</label>
                        <textarea class="form-control" id="question_text" name="question_text" rows="3" required></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="question_score" class="form-label">Bal</label>
                        <input type="number" class="form-control" id="question_score" name="question_score" min="0.5" step="0.5" value="1" required>
                    </div>
                    
                    <!-- Çoxseçimli sual variantları -->
                    <div id="multiple_fields" style="display: none;">
                        <div class="mb-3">
                            <label for="var_a" class="form-label">Variant A</label>
                            <input type="text" class="form-control" id="var_a" name="var_a">
                        </div>
                        <div class="mb-3">
                            <label for="var_b" class="form-label">Variant B</label>
                            <input type="text" class="form-control" id="var_b" name="var_b">
                        </div>
                        <div class="mb-3">
                            <label for="var_c" class="form-label">Variant C</label>
                            <input type="text" class="form-control" id="var_c" name="var_c">
                        </div>
                        <div class="mb-3">
                            <label for="var_d" class="form-label">Variant D</label>
                            <input type="text" class="form-control" id="var_d" name="var_d">
                        </div>
                        <div class="mb-3">
                            <label for="correct_v" class="form-label">Düzgün variant</label>
                            <select class="form-select" id="correct_v" name="correct_v">
                                <option value="a">A</option>
                                <option value="b">B</option>
                                <option value="c">C</option>
                                <option value="d">D</option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Açıq sual variantları -->
                    <div id="open_fields" style="display: none;">
                        <div class="mb-3">
                            <label for="corr_v" class="form-label">Düzgün cavab</label>
                            <input type="text" class="form-control" id="corr_v" name="corr_v">
                        </div>
                    </div>
                    
                    <!-- Uyğunlaşdırma sual variantları -->
                    <div id="matching_fields" style="display: none;">
                        <div class="mb-3">
                            <label for="variants" class="form-label">Variantlar (vergüllə ayırın)</label>
                            <input type="text" class="form-control" id="variants" name="variants" placeholder="variant1,variant2,variant3">
                            <div class="form-text">Məsələn: alma,armud,banan</div>
                        </div>
                        <div class="mb-3">
                            <label for="corr_variant" class="form-label">Düzgün variant</label>
                            <input type="text" class="form-control" id="corr_variant" name="corr_variant" placeholder="düzgün variantı qeyd edin">
                            <div class="form-text">Yuxarıdakı variantlardan biri olmalıdır</div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Ləğv et</button>
                    <button type="submit" name="add_question" class="btn btn-primary">Əlavə et</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Sual Redaktə Et Modal - YENİ MODAL -->
<div class="modal fade" id="editQuestionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Sualı Redaktə Et</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" action="">
                <div class="modal-body">
                    <input type="hidden" name="edit_question_id" id="edit_question_id">
                    <input type="hidden" name="edit_question_type" id="edit_question_type">
                    
                    <div class="mb-3">
                        <label for="edit_question_text" class="form-label">Sual mətni</label>
                        <textarea class="form-control" id="edit_question_text" name="edit_question_text" rows="3" required></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_question_score" class="form-label">Bal</label>
                        <input type="number" class="form-control" id="edit_question_score" name="edit_question_score" min="0.5" step="0.5" required>
                    </div>
                    
                    <!-- Çoxseçimli sual variantları - Redaktə -->
                    <div id="edit_multiple_fields" style="display: none;">
                        <div class="mb-3">
                            <label for="edit_var_a" class="form-label">Variant A</label>
                            <input type="text" class="form-control" id="edit_var_a" name="edit_var_a">
                        </div>
                        <div class="mb-3">
                            <label for="edit_var_b" class="form-label">Variant B</label>
                            <input type="text" class="form-control" id="edit_var_b" name="edit_var_b">
                        </div>
                        <div class="mb-3">
                            <label for="edit_var_c" class="form-label">Variant C</label>
                            <input type="text" class="form-control" id="edit_var_c" name="edit_var_c">
                        </div>
                        <div class="mb-3">
                            <label for="edit_var_d" class="form-label">Variant D</label>
                            <input type="text" class="form-control" id="edit_var_d" name="edit_var_d">
                        </div>
                        <div class="mb-3">
                            <label for="edit_correct_v" class="form-label">Düzgün variant</label>
                            <select class="form-select" id="edit_correct_v" name="edit_correct_v">
                                <option value="a">A</option>
                                <option value="b">B</option>
                                <option value="c">C</option>
                                <option value="d">D</option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Açıq sual variantları - Redaktə -->
                    <div id="edit_open_fields" style="display: none;">
                        <div class="mb-3">
                            <label for="edit_corr_v" class="form-label">Düzgün cavab</label>
                            <input type="text" class="form-control" id="edit_corr_v" name="edit_corr_v">
                        </div>
                    </div>
                    
                    <!-- Uyğunlaşdırma sual variantları - Redaktə -->
                    <div id="edit_matching_fields" style="display: none;">
                        <div class="mb-3">
                            <label for="edit_variants" class="form-label">Variantlar (vergüllə ayırın)</label>
                            <input type="text" class="form-control" id="edit_variants" name="edit_variants" placeholder="variant1,variant2,variant3">
                            <div class="form-text">Məsələn: alma,armud,banan</div>
                        </div>
                        <div class="mb-3">
                            <label for="edit_corr_variant" class="form-label">Düzgün variant</label>
                            <input type="text" class="form-control" id="edit_corr_variant" name="edit_corr_variant" placeholder="düzgün variantı qeyd edin">
                            <div class="form-text">Yuxarıdakı variantlardan biri olmalıdır</div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Ləğv et</button>
                    <button type="submit" name="edit_question" class="btn btn-warning">Yenilə</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Sual Baxış Modal -->
<div class="modal fade" id="viewQuestionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Sual məlumatları</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <h5>Sual mətni:</h5>
                <p id="view_question_text"></p>
                
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Bal:</strong> <span id="view_question_score"></span></p>
                    </div>
                </div>
                
                <div id="view_multiple_fields" style="display: none;">
                    <h5>Variantlar:</h5>
                    <ul id="multiple_options_list" class="list-group"></ul>
                </div>
                
                <div id="view_open_fields" style="display: none;">
                    <h5>Düzgün cavab:</h5>
                    <p id="view_correct_answer"></p>
                </div>
                
                <div id="view_matching_fields" style="display: none;">
                    <h5>Variantlar:</h5>
                    <p id="view_variants"></p>
                    <h5>Düzgün variant:</h5>
                    <p id="view_correct_variant"></p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Bağla</button>
            </div>
        </div>
    </div>
</div>

<!-- Fayl Baxış Modal -->
<div class="modal fade" id="viewFileModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Fayl Məzmunu: <?php echo isset($active_file_data['file_title']) ? $active_file_data['file_title'] : ''; ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <?php if (isset($active_file_data['file_path']) && file_exists($active_file_data['file_path'])): ?>
                    <?php if ($active_file_data['file_type'] == 'reading'): ?>
                        <pre class="p-3 bg-light"><?php echo htmlspecialchars(file_get_contents($active_file_data['file_path'])); ?></pre>
                    <?php else: ?>
                        <div class="text-center">
                            <audio controls>
                                <source src="<?php echo $active_file_data['file_path']; ?>" type="audio/mpeg">
                                Sizin brauzeriniz audio tag-ı dəstəkləmir.
                            </audio>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <p class="text-muted">Fayl tapılmadı.</p>
                <?php endif; ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Bağla</button>
            </div>
        </div>
    </div>
</div>

<script>
// Sual növünə görə lazım olan sahələri göstərmək
function toggleQuestionFields() {
    var questionType = document.getElementById('question_type');
    var selectedOption = questionType.options[questionType.selectedIndex];
    
    if (!selectedOption.value) {
        document.getElementById('multiple_fields').style.display = 'none';
        document.getElementById('open_fields').style.display = 'none';
        document.getElementById('matching_fields').style.display = 'none';
        return;
    }
    
    var questionVar = selectedOption.getAttribute('data-var');
    
    document.getElementById('multiple_fields').style.display = 'none';
    document.getElementById('open_fields').style.display = 'none';
    document.getElementById('matching_fields').style.display = 'none';
    
    if (questionVar === 'multiple') {
        document.getElementById('multiple_fields').style.display = 'block';
    } else if (questionVar === 'open') {
        document.getElementById('open_fields').style.display = 'block';
    } else if (questionVar === 'matching') {
        document.getElementById('matching_fields').style.display = 'block';
    }
}

// Redaktə modalı üçün sahələri göstərmək - YENİ FUNKSİYA
function toggleEditQuestionFields(questionVar) {
    document.getElementById('edit_multiple_fields').style.display = 'none';
    document.getElementById('edit_open_fields').style.display = 'none';
    document.getElementById('edit_matching_fields').style.display = 'none';
    
    if (questionVar === 'multiple') {
        document.getElementById('edit_multiple_fields').style.display = 'block';
    } else if (questionVar === 'open') {
        document.getElementById('edit_open_fields').style.display = 'block';
    } else if (questionVar === 'matching') {
        document.getElementById('edit_matching_fields').style.display = 'block';
    }
}

// Fayl növünə görə fayl uzantısını göstərmək
function toggleFileExtension() {
    var fileType = document.getElementById('file_type');
    var fileExtensionHelp = document.getElementById('file_extension_help');
    
    if (fileType.value === 'reading') {
        fileExtensionHelp.textContent = 'Oxu üçün yalnız .txt faylları qəbul edilir.';
    } else if (fileType.value === 'listening') {
        fileExtensionHelp.textContent = 'Dinləmə üçün yalnız .mp3 faylları qəbul edilir.';
    }
}

// Sual baxışı və redaktəsi
document.addEventListener('DOMContentLoaded', function() {
    var viewButtons = document.querySelectorAll('.view-question');
    var editButtons = document.querySelectorAll('.edit-question');
    
    // Sual baxışı
    viewButtons.forEach(function(button) {
        button.addEventListener('click', function() {
            var questionId = this.getAttribute('data-id');
            var questionText = this.getAttribute('data-text');
            var questionType = this.getAttribute('data-type');
            var questionScore = this.getAttribute('data-score');
            
            document.getElementById('view_question_text').textContent = questionText;
            document.getElementById('view_question_score').textContent = questionScore;
            
            document.getElementById('view_multiple_fields').style.display = 'none';
            document.getElementById('view_open_fields').style.display = 'none';
            document.getElementById('view_matching_fields').style.display = 'none';
            
            // AJAX ilə sual məlumatlarını almaq
            var xhr = new XMLHttpRequest();
            xhr.open('GET', 'get_question_details.php?id=' + questionId, true);
            xhr.onload = function() {
                if (this.status === 200) {
                    var response = JSON.parse(this.responseText);
                    
                    if (questionType === 'multiple') {
                        document.getElementById('view_multiple_fields').style.display = 'block';
                        var optionsList = document.getElementById('multiple_options_list');
                        optionsList.innerHTML = '';
                        
                        var options = [
                            { label: 'A', value: response.var_a, correct: response.correct_v === 'a' },
                            { label: 'B', value: response.var_b, correct: response.correct_v === 'b' },
                            { label: 'C', value: response.var_c, correct: response.correct_v === 'c' },
                            { label: 'D', value: response.var_d, correct: response.correct_v === 'd' }
                        ];
                        
                        options.forEach(function(option) {
                            var li = document.createElement('li');
                            li.className = 'list-group-item' + (option.correct ? ' list-group-item-success' : '');
                            li.innerHTML = '<strong>' + option.label + ')</strong> ' + option.value + 
                                          (option.correct ? ' <span class="badge bg-success">Düzgün</span>' : '');
                            optionsList.appendChild(li);
                        });
                    } else if (questionType === 'open') {
                        document.getElementById('view_open_fields').style.display = 'block';
                        document.getElementById('view_correct_answer').textContent = response.corr_v;
                    } else if (questionType === 'matching') {
                        document.getElementById('view_matching_fields').style.display = 'block';
                        document.getElementById('view_variants').textContent = response.variants;
                        document.getElementById('view_correct_variant').textContent = response.corr_variant;
                    }
                }
            };
            xhr.send();
        });
    });
    
    // Sual redaktəsi - YENİ EVENT LISTENER
    editButtons.forEach(function(button) {
        button.addEventListener('click', function() {
            var questionId = this.getAttribute('data-id');
            var questionText = this.getAttribute('data-text');
            var questionType = this.getAttribute('data-type');
            var questionTypeId = this.getAttribute('data-type-id');
            var questionScore = this.getAttribute('data-score');
            
            // Əsas məlumatları doldur
            document.getElementById('edit_question_id').value = questionId;
            document.getElementById('edit_question_type').value = questionTypeId;
            document.getElementById('edit_question_text').value = questionText;
            document.getElementById('edit_question_score').value = questionScore;
            
            // Sual tipinə görə sahələri göstər
            toggleEditQuestionFields(questionType);
            
            // AJAX ilə mövcud sual məlumatlarını al və doldur
            var xhr = new XMLHttpRequest();
            xhr.open('GET', 'get_question_details.php?id=' + questionId, true);
            xhr.onload = function() {
                if (this.status === 200) {
                    var response = JSON.parse(this.responseText);
                    
                    if (questionType === 'multiple') {
                        document.getElementById('edit_var_a').value = response.var_a || '';
                        document.getElementById('edit_var_b').value = response.var_b || '';
                        document.getElementById('edit_var_c').value = response.var_c || '';
                        document.getElementById('edit_var_d').value = response.var_d || '';
                        document.getElementById('edit_correct_v').value = response.correct_v || 'a';
                    } else if (questionType === 'open') {
                        document.getElementById('edit_corr_v').value = response.corr_v || '';
                    } else if (questionType === 'matching') {
                        document.getElementById('edit_variants').value = response.variants || '';
                        document.getElementById('edit_corr_variant').value = response.corr_variant || '';
                    }
                }
            };
            xhr.send();
        });
    });
    
    // Səhifə yükləndikdə fayl uzantısı mətnini qur
    toggleFileExtension();
});
</script>

<?php 
// get_question_details.php faylı üçün kod da lazım olacaq
if (!file_exists('get_question_details.php')) {
    file_put_contents('get_question_details.php', '<?php
error_reporting(E_ALL);
ini_set("display_errors", "1");
require_once "../includes/db.php";
require_once "../includes/auth.php";
checkAdminRights();

header("Content-Type: application/json");

if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    echo json_encode(["error" => "Invalid question ID"]);
    exit();
}

$question_id = $_GET["id"];
$database = new Database();
$db = $database->getConnection();

try {
    // Sual tipini təyin et
    $query = "SELECT qt.question_var FROM question_read qr
              JOIN question_types qt ON qr.id_question_type = qt.id_quest_type
              WHERE qr.id_question_text = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":id", $question_id);
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        echo json_encode(["error" => "Question not found"]);
        exit();
    }
    
    $question_var = $stmt->fetch(PDO::FETCH_ASSOC)["question_var"];
    
    // Sual tipinə görə məlumatları al
    if ($question_var == "multiple") {
        $query = "SELECT * FROM multiple_questions WHERE id_question_text = :id";
    } elseif ($question_var == "open") {
        $query = "SELECT * FROM open_questions WHERE id_question_text = :id";
    } elseif ($question_var == "matching") {
        $query = "SELECT * FROM matching_questions WHERE id_question_text = :id";
    } else {
        echo json_encode(["error" => "Unknown question type"]);
        exit();
    }
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(":id", $question_id);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        echo json_encode($stmt->fetch(PDO::FETCH_ASSOC));
    } else {
        echo json_encode(["error" => "Question details not found"]);
    }
    
} catch (PDOException $e) {
    echo json_encode(["error" => "Database error: " . $e->getMessage()]);
}
?>');
}

include_once "../includes/footer.php"; 
?>