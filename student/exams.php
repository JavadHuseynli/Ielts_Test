<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
require_once "../includes/db.php";
require_once "../includes/auth.php";
checkLogin();

// Yalnız tələbələr girə bilər
if ($_SESSION['status'] != 'student') {
    header("Location: ../admin/dashboard.php");
    exit();
}

$pageTitle = "İmtahanlar";
include_once "../includes/header.php";

$database = new Database();
$db = $database->getConnection();
$user_id = $_SESSION['user_id'];
$group_id = $_SESSION['group_id'];

// Aktiv tabı müəyyən etmək
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'upcoming';

// Success mesajı
if (isset($_GET['success']) && $_GET['success'] == '1') {
    echo '<div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle me-2"></i>İmtahan uğurla tamamlandı!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>';
}

// Debug üçün
if (isset($_GET['debug'])) {
    echo "<div class='alert alert-info'><i class='fas fa-bug me-2'></i><strong>Debug Mode:</strong> Aktiv - User ID: $user_id, Group ID: $group_id</div>";
}
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="display-6 text-primary">
                    <i class="fas fa-graduation-cap me-3"></i>İmtahanlar
                </h1>
               </div>
        </div>
    </div>

    <div class="card border-0 shadow-lg">
        <div class="card-header bg-gradient" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
            <ul class="nav nav-tabs card-header-tabs border-0">
                <li class="nav-item">
                    <a class="nav-link text-white <?php echo $active_tab == 'upcoming' ? 'active bg-white text-dark' : ''; ?>" href="?tab=upcoming">
                        <i class="fas fa-clock me-2"></i>Gələcək İmtahanlar
                        <?php
                        // Upcoming exams count
                        $count_query = "SELECT COUNT(*) as count FROM exams e WHERE e.id_student_group = :group_id AND e.datetime > NOW() AND e.status = 'pending'";
                        $count_stmt = $db->prepare($count_query);
                        $count_stmt->bindParam(":group_id", $group_id);
                        $count_stmt->execute();
                        $upcoming_count = $count_stmt->fetch(PDO::FETCH_ASSOC)['count'];
                        if ($upcoming_count > 0) echo "<span class='badge bg-danger ms-1'>$upcoming_count</span>";
                        ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-white <?php echo $active_tab == 'ongoing' ? 'active bg-white text-dark' : ''; ?>" href="?tab=ongoing">
                        <i class="fas fa-play-circle me-2"></i>Davam Edən İmtahanlar
                        <?php
                        // Ongoing exams count
                        $count_query = "SELECT COUNT(*) as count FROM exams e WHERE e.id_student_group = :group_id AND e.status = 'in_progress'";
                        $count_stmt = $db->prepare($count_query);
                        $count_stmt->bindParam(":group_id", $group_id);
                        $count_stmt->execute();
                        $ongoing_count = $count_stmt->fetch(PDO::FETCH_ASSOC)['count'];
                        if ($ongoing_count > 0) echo "<span class='badge bg-warning ms-1'>$ongoing_count</span>";
                        ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-white <?php echo $active_tab == 'results' ? 'active bg-white text-dark' : ''; ?>" href="?tab=results">
                        <i class="fas fa-chart-bar me-2"></i>İmtahan Nəticələri
                        <?php
                        // Results count
                        $count_query = "SELECT COUNT(DISTINCT a.exam_id) as count FROM answers a JOIN exams e ON a.exam_id = e.id_exam WHERE a.user_id = :user_id AND e.id_student_group = :group_id";
                        $count_stmt = $db->prepare($count_query);
                        $count_stmt->bindParam(":user_id", $user_id);
                        $count_stmt->bindParam(":group_id", $group_id);
                        $count_stmt->execute();
                        $results_count = $count_stmt->fetch(PDO::FETCH_ASSOC)['count'];
                        if ($results_count > 0) echo "<span class='badge bg-success ms-1'>$results_count</span>";
                        ?>
                    </a>
                </li>
            </ul>
        </div>
        
        <div class="card-body p-4">
            <?php if ($active_tab == 'upcoming'): ?>
                <?php
                // Qarşıdakı imtahanları almaq
                $query = "SELECT e.id_exam, e.date_exam, e.datetime, s.subjectname, s.timer, s.id_subject
                          FROM exams e
                          JOIN subjects s ON e.id_subject = s.id_subject
                          WHERE e.id_student_group = :group_id AND e.datetime > NOW() AND e.status = 'pending'
                          ORDER BY e.datetime ASC";
                $stmt = $db->prepare($query);
                $stmt->bindParam(":group_id", $group_id);
                $stmt->execute();
                $upcoming_exams = $stmt->fetchAll(PDO::FETCH_ASSOC);
                ?>
                
                <?php if (empty($upcoming_exams)): ?>
                    <div class="text-center py-5">
                        <div class="mb-4">
                            <i class="fas fa-calendar-times text-muted" style="font-size: 5rem;"></i>
                        </div>
                        <h4 class="text-muted">Gələcək İmtahan Yoxdur</h4>
                        <p class="text-muted">Yaxın zamanda planlaşdırılmış imtahanınız yoxdur.</p>
                        <a href="?tab=results" class="btn btn-outline-primary">
                            <i class="fas fa-chart-bar me-2"></i>Keçmiş Nəticələrə Bax
                        </a>
                    </div>
                <?php else: ?>
                    <div class="row">
                        <?php foreach ($upcoming_exams as $exam): ?>
                            <?php
                            $now = new DateTime();
                            $exam_time = new DateTime($exam['datetime']);
                            $interval = $now->diff($exam_time);
                            
                            // Time remaining calculation
                            $time_remaining = '';
                            if ($interval->days > 0) {
                                $time_remaining = $interval->format('%a gün %h saat');
                            } elseif ($interval->h > 0) {
                                $time_remaining = $interval->format('%h saat %i dəqiqə');
                            } else {
                                $time_remaining = $interval->format('%i dəqiqə');
                            }
                            
                            // Urgency class
                            $urgency_class = 'border-primary';
                            if ($interval->days == 0 && $interval->h < 2) {
                                $urgency_class = 'border-danger';
                            } elseif ($interval->days == 0) {
                                $urgency_class = 'border-warning';
                            }
                            ?>
                            <div class="col-lg-6 col-xl-4 mb-4">
                                <div class="card h-100 border-0 shadow-sm <?php echo $urgency_class; ?>" style="border-left: 5px solid !important;">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start mb-3">
                                            <h5 class="card-title text-primary mb-0">
                                                <i class="fas fa-book me-2"></i><?php echo htmlspecialchars($exam['subjectname']); ?>
                                            </h5>
                                            <span class="badge bg-primary"><?php echo $exam['timer']; ?> dəq</span>
                                        </div>
                                        
                                        <div class="row text-center mb-3">
                                            <div class="col-6">
                                                <div class="border-end">
                                                    <h6 class="text-muted mb-1">Tarix</h6>
                                                    <strong><?php echo date('d.m.Y', strtotime($exam['date_exam'])); ?></strong>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <h6 class="text-muted mb-1">Vaxt</h6>
                                                <strong><?php echo date('H:i', strtotime($exam['datetime'])); ?></strong>
                                            </div>
                                        </div>
                                        
                                        <div class="text-center">
                                            <div class="countdown-timer mb-2">
                                                <i class="fas fa-clock text-warning me-1"></i>
                                                <span class="fw-bold text-dark"><?php echo $time_remaining; ?></span>
                                                <small class="text-muted d-block">qalıb</small>
                                            </div>
                                        </div>
                                        
                                        <?php if ($interval->days == 0 && $interval->h < 1): ?>
                                            <div class="alert alert-warning py-2 mb-0">
                                                <i class="fas fa-exclamation-triangle me-1"></i>
                                                <small>Tezliklə başlayacaq!</small>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Detailed Table -->
                    <div class="mt-4">
                        <h5 class="mb-3">
                            <i class="fas fa-list me-2"></i>Ətraflı Cədvəl
                        </h5>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th><i class="fas fa-book me-1"></i>Fənn</th>
                                        <th><i class="fas fa-calendar me-1"></i>Tarix</th>
                                        <th><i class="fas fa-clock me-1"></i>Vaxt</th>
                                        <th><i class="fas fa-hourglass-half me-1"></i>Müddət</th>
                                        <th><i class="fas fa-countdown me-1"></i>Qalan vaxt</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($upcoming_exams as $exam): ?>
                                        <?php
                                        $now = new DateTime();
                                        $exam_time = new DateTime($exam['datetime']);
                                        $interval = $now->diff($exam_time);
                                        
                                        $time_remaining = '';
                                        if ($interval->days > 0) {
                                            $time_remaining = $interval->format('%a gün %h saat');
                                        } else {
                                            $time_remaining = $interval->format('%h saat %i dəqiqə');
                                        }
                                        
                                        $row_class = '';
                                        if ($interval->days == 0 && $interval->h < 2) {
                                            $row_class = 'table-danger';
                                        } elseif ($interval->days == 0) {
                                            $row_class = 'table-warning';
                                        }
                                        ?>
                                        <tr class="<?php echo $row_class; ?>">
                                            <td>
                                                <strong><?php echo htmlspecialchars($exam['subjectname']); ?></strong>
                                            </td>
                                            <td><?php echo date('d.m.Y', strtotime($exam['date_exam'])); ?></td>
                                            <td><?php echo date('H:i', strtotime($exam['datetime'])); ?></td>
                                            <td>
                                                <span class="badge bg-info"><?php echo $exam['timer']; ?> dəqiqə</span>
                                            </td>
                                            <td>
                                                <strong class="text-primary"><?php echo $time_remaining; ?></strong>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>
                
            <?php elseif ($active_tab == 'ongoing'): ?>
                <?php
                // Davam edən imtahanları almaq
                $query = "SELECT e.id_exam, e.date_exam, e.datetime, s.subjectname, s.timer, s.id_subject
                          FROM exams e
                          JOIN subjects s ON e.id_subject = s.id_subject
                          WHERE e.id_student_group = :group_id AND e.status = 'in_progress'
                          ORDER BY e.datetime ASC";
                $stmt = $db->prepare($query);
                $stmt->bindParam(":group_id", $group_id);
                $stmt->execute();
                $ongoing_exams = $stmt->fetchAll(PDO::FETCH_ASSOC);
                ?>
                
                <?php if (empty($ongoing_exams)): ?>
                    <div class="text-center py-5">
                        <div class="mb-4">
                            <i class="fas fa-pause-circle text-muted" style="font-size: 5rem;"></i>
                        </div>
                        <h4 class="text-muted">Davam Edən İmtahan Yoxdur</h4>
                        <p class="text-muted">Hazırda aktiv imtahanınız yoxdur.</p>
                        <div class="d-flex justify-content-center gap-2">
                            <a href="?tab=upcoming" class="btn btn-outline-primary">
                                <i class="fas fa-clock me-2"></i>Gələcək İmtahanlar
                            </a>
                            <a href="?tab=results" class="btn btn-outline-success">
                                <i class="fas fa-chart-bar me-2"></i>Nəticələr
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="row">
                        <?php foreach ($ongoing_exams as $exam): ?>
                            <?php
                            // Tələbə bu imtahana başlayıbmı
                            $query = "SELECT COUNT(*) as count, MIN(datetime) as start_time FROM answers WHERE exam_id = :exam_id AND user_id = :user_id";
                            $stmt = $db->prepare($query);
                            $stmt->bindParam(":exam_id", $exam['id_exam']);
                            $stmt->bindParam(":user_id", $user_id);
                            $stmt->execute();
                            $answer_info = $stmt->fetch(PDO::FETCH_ASSOC);
                            $has_started = $answer_info['count'] > 0;
                            $start_time = $answer_info['start_time'];
                            
                            // Calculate remaining time if started
                            $remaining_minutes = null;
                            if ($has_started && $start_time) {
                                $start_timestamp = strtotime($start_time);
                                $elapsed_minutes = (time() - $start_timestamp) / 60;
                                $remaining_minutes = max(0, $exam['timer'] - $elapsed_minutes);
                            }
                            ?>
                            
                            <div class="col-lg-6 mb-4">
                                <div class="card h-100 border-0 shadow-lg <?php echo $has_started ? 'border-warning' : 'border-success'; ?>" style="border-left: 5px solid !important;">
                                    <div class="card-header <?php echo $has_started ? 'bg-warning' : 'bg-success'; ?> text-white">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <h5 class="mb-0">
                                                <i class="fas fa-graduation-cap me-2"></i>
                                                <?php echo htmlspecialchars($exam['subjectname']); ?>
                                            </h5>
                                            <span class="badge <?php echo $has_started ? 'bg-white text-warning' : 'bg-white text-success'; ?>">
                                                <?php echo $has_started ? 'Davam edir' : 'Hazır'; ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <div class="row mb-3">
                                            <div class="col-6">
                                                <div class="text-center border-end">
                                                    <h6 class="text-muted mb-1">Tarix</h6>
                                                    <strong class="text-primary"><?php echo date('d.m.Y', strtotime($exam['date_exam'])); ?></strong>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="text-center">
                                                    <h6 class="text-muted mb-1">Başlama vaxtı</h6>
                                                    <strong class="text-primary"><?php echo date('H:i', strtotime($exam['datetime'])); ?></strong>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="row mb-3">
                                            <div class="col-6">
                                                <div class="text-center border-end">
                                                    <h6 class="text-muted mb-1">Müddət</h6>
                                                    <span class="badge bg-info fs-6"><?php echo $exam['timer']; ?> dəqiqə</span>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="text-center">
                                                    <h6 class="text-muted mb-1">Cavab sayı</h6>
                                                    <span class="badge bg-secondary fs-6"><?php echo $answer_info['count']; ?></span>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <?php if ($has_started): ?>
                                            <div class="alert alert-warning d-flex align-items-center mb-3">
                                                <i class="fas fa-play-circle me-3 fa-2x"></i>
                                                <div>
                                                    <strong>İmtahan Davam Edir</strong>
                                                    <br><small>Başlama vaxtı: <?php echo date('d.m.Y H:i', strtotime($start_time)); ?></small>
                                                    <?php if ($remaining_minutes !== null): ?>
                                                        <br><small class="text-danger">Qalan vaxt: təxminən <?php echo max(0, round($remaining_minutes)); ?> dəqiqə</small>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            
                                            <div class="d-grid">
                                                <a href="exam.php?id=<?php echo $exam['id_exam']; ?>" class="btn btn-warning btn-lg">
                                                    <i class="fas fa-play me-2"></i>İmtahana Davam Et
                                                </a>
                                            </div>
                                        <?php else: ?>
                                            <div class="alert alert-success d-flex align-items-center mb-3">
                                                <i class="fas fa-rocket me-3 fa-2x"></i>
                                                <div>
                                                    <strong>İmtahan Hazırdır</strong>
                                                    <br><small>İmtahana başlamaq üçün düyməni sıxın</small>
                                                </div>
                                            </div>
                                            
                                            <div class="d-grid">
                                                <a href="exam.php?id=<?php echo $exam['id_exam']; ?>" class="btn btn-success btn-lg" 
                                                   onclick="return confirm('İmtahana başladıqdan sonra vaxt saymağa başlayacaq. Hazırsınız?')">
                                                    <i class="fas fa-play-circle me-2"></i>İmtahana Başla
                                                </a>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
            <?php elseif ($active_tab == 'results'): ?>
                <?php
                // İlk olaraq, tələbənin cavab verdiyi imtahanları yoxlayaq
                $debug_query = "SELECT DISTINCT a.exam_id, e.status, s.subjectname, a.user_id, e.date_exam
                               FROM answers a 
                               JOIN exams e ON a.exam_id = e.id_exam 
                               JOIN subjects s ON e.id_subject = s.id_subject
                               WHERE a.user_id = :user_id AND e.id_student_group = :group_id
                               ORDER BY e.date_exam DESC";
                
                $debug_stmt = $db->prepare($debug_query);
                $debug_stmt->bindParam(":user_id", $user_id);
                $debug_stmt->bindParam(":group_id", $group_id);
                $debug_stmt->execute();
                $debug_results = $debug_stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Debug məlumatları göstər
                if (isset($_GET['debug'])) {
                    echo "<div class='alert alert-warning'>";
                    echo "<strong><i class='fas fa-bug me-2'></i>Debug: Tələbənin cavab verdiyi imtahanlar:</strong><br>";
                    if (empty($debug_results)) {
                        echo "Heç bir cavab tapılmadı.";
                    } else {
                        foreach ($debug_results as $debug_result) {
                            echo "İmtahan ID: {$debug_result['exam_id']}, Status: {$debug_result['status']}, Fənn: {$debug_result['subjectname']}<br>";
                        }
                    }
                    echo "</div>";
                }
                
                // İmtahan nəticələrini almaq - YENİLƏNMİŞ SORĞU
                $query = "SELECT DISTINCT
                            e.id_exam, 
                            e.date_exam, 
                            e.datetime,
                            s.subjectname, 
                            s.id_subject,
                            e.status,
                            COUNT(DISTINCT a.id_questions) as answered_questions,
                            (SELECT COUNT(*) 
                             FROM question_read qr2
                             JOIN question_files qf2 ON qr2.id_read_quest_file = qf2.id_read_quest_file
                             WHERE qf2.subject_id = s.id_subject) as total_questions
                          FROM exams e
                          JOIN subjects s ON e.id_subject = s.id_subject
                          JOIN answers a ON e.id_exam = a.exam_id
                          WHERE a.user_id = :user_id 
                          AND e.id_student_group = :group_id
                          GROUP BY e.id_exam, e.date_exam, e.datetime, s.subjectname, s.id_subject, e.status
                          ORDER BY e.datetime DESC";
                
                $stmt = $db->prepare($query);
                $stmt->bindParam(":user_id", $user_id);
                $stmt->bindParam(":group_id", $group_id);
                $stmt->execute();
                $exam_results = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Debug məlumatları
                if (isset($_GET['debug'])) {
                    echo "<div class='alert alert-info'>";
                    echo "<strong><i class='fas fa-info-circle me-2'></i>Debug: Əsas sorğu nəticələri:</strong><br>";
                    echo "Tapılan nəticələr: " . count($exam_results) . "<br>";
                    echo "User ID: $user_id, Group ID: $group_id<br>";
                    echo "</div>";
                }
                ?>
                
                <?php if (empty($exam_results)): ?>
                    <div class="text-center py-5">
                        <div class="mb-4">
                            <i class="fas fa-chart-bar text-muted" style="font-size: 5rem;"></i>
                        </div>
                        <h4 class="text-muted">İmtahan Nəticəsi Yoxdur</h4>
                        <p class="text-muted">Hələ heç bir imtahana cavab verməmişsiniz.</p>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Məlumat:</strong> İmtahana başladığınız zaman nəticələr burada göstəriləcək.
                        </div>
                        
                        <!-- Debug linkləri -->
                        <div class="mt-3 d-flex justify-content-center gap-2">
                            <a href="?tab=ongoing" class="btn btn-primary">
                                <i class="fas fa-play me-2"></i>İmtahanlara Bax
                            </a>
                            <a href="?tab=results&debug=1" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-bug me-1"></i>Debug
                            </a>
                        </div>
                    </div>
                    
                <?php else: ?>
                    <!-- Summary Statistics -->
                    <?php
                    $total_exams = count($exam_results);
                    $completed_exams = 0;
                    $total_percentage = 0;
                    $total_correct = 0;
                    $total_questions_attempted = 0;
                    
                    foreach ($exam_results as $result) {
                        if ($result['status'] == 'completed') {
                            $completed_exams++;
                        }
                        $total_questions_attempted += $result['answered_questions'];
                    }
                    ?>
                    
                    <div class="row mb-4">
                        <div class="col-md-3 mb-3">
                            <div class="card bg-primary text-white border-0">
                                <div class="card-body text-center">
                                    <i class="fas fa-clipboard-list fa-2x mb-2"></i>
                                    <h3 class="mb-1"><?php echo $total_exams; ?></h3>
                                    <small>Toplam İmtahan</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card bg-success text-white border-0">
                                <div class="card-body text-center">
                                    <i class="fas fa-check-circle fa-2x mb-2"></i>
                                    <h3 class="mb-1"><?php echo $completed_exams; ?></h3>
                                    <small>Tamamlanmış</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card bg-warning text-white border-0">
                                <div class="card-body text-center">
                                    <i class="fas fa-play-circle fa-2x mb-2"></i>
                                    <h3 class="mb-1"><?php echo $total_exams - $completed_exams; ?></h3>
                                    <small>Davam Edən</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card bg-info text-white border-0">
                                <div class="card-body text-center">
                                    <i class="fas fa-question-circle fa-2x mb-2"></i>
                                    <h3 class="mb-1"><?php echo $total_questions_attempted; ?></h3>
                                    <small>Cavab Verilən</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Exam Results Cards -->
                    <div class="row">
                        <?php foreach ($exam_results as $result): ?>
                            <?php
                            // Hər imtahan üçün ətraflı nəticələri hesabla
                            $detail_query = "SELECT 
                                               a.id_questions,
                                               a.user_answer,
                                               a.correct_var,
                                               qr.question_text,
                                               qr.question_score,
                                               qt.question_var,
                                               CASE 
                                                 WHEN TRIM(LOWER(a.user_answer)) = TRIM(LOWER(a.correct_var)) THEN 1
                                                 ELSE 0
                                               END as is_correct
                                             FROM answers a
                                             JOIN question_read qr ON a.id_questions = qr.id_question_text
                                             JOIN question_types qt ON qr.id_question_type = qt.id_quest_type
                                             WHERE a.exam_id = :exam_id AND a.user_id = :user_id";
                            
                            $detail_stmt = $db->prepare($detail_query);
                            $detail_stmt->bindParam(":exam_id", $result['id_exam']);
                            $detail_stmt->bindParam(":user_id", $user_id);
                            $detail_stmt->execute();
                            $answer_details = $detail_stmt->fetchAll(PDO::FETCH_ASSOC);
                            
                            // Statistikaları hesabla
                            $correct_count = 0;
                            $total_score = 0;
                            $max_possible_score = 0;
                            
                            foreach ($answer_details as $detail) {
                                $max_possible_score += $detail['question_score'];
                                if ($detail['is_correct']) {
                                    $correct_count++;
                                    $total_score += $detail['question_score'];
                                }
                            }
                            
                            $percentage = $max_possible_score > 0 ? ($total_score / $max_possible_score) * 100 : 0;
                            
                            // Status rəngini təyin et
                            $status_class = 'bg-secondary';
                            $status_text = 'Naməlum';
                            $card_border = 'border-secondary';
                            
                            switch($result['status']) {
                                case 'completed':
                                    $status_class = 'bg-success';
                                    $status_text = 'Tamamlandı';
                                    $card_border = 'border-success';
                                    break;
                                case 'in_progress':
                                    $status_class = 'bg-warning';
                                    $status_text = 'Davam edir';
                                    $card_border = 'border-warning';
                                    break;
                                case 'pending':
                                    $status_class = 'bg-info';
                                    $status_text = 'Gözləyir';
                                    $card_border = 'border-info';
                                    break;
                            }
                            
                            // Qiymət rəngini təyin et
                            $grade_class = 'text-danger';
                            if ($percentage >= 80) $grade_class = 'text-success';
                            elseif ($percentage >= 60) $grade_class = 'text-warning';
                            elseif ($percentage >= 40) $grade_class = 'text-info';
                            ?>
                            
                            <div class="col-lg-6 col-xl-4 mb-4">
                                <div class="card h-100 border-0 shadow-sm <?php echo $card_border; ?>" style="border-left: 5px solid !important;">
                                    <div class="card-header bg-light border-0 d-flex justify-content-between align-items-center">
                                        <h6 class="mb-0 text-primary fw-bold">
                                            <i class="fas fa-graduation-cap me-2"></i>
                                            <?php echo htmlspecialchars($result['subjectname']); ?>
                                        </h6>
                                        <span class="badge <?php echo $status_class; ?> fs-7"><?php echo $status_text; ?></span>
                                    </div>
                                    <div class="card-body">
                                        <!-- Score Circle -->
                                        <div class="text-center mb-3">
                                            <div class="position-relative d-inline-block">
                                                <svg width="80" height="80" class="position-relative">
                                                    <circle cx="40" cy="40" r="35" stroke="#e9ecef" stroke-width="6" fill="transparent"/>
                                                    <circle cx="40" cy="40" r="35" stroke="<?php echo $percentage >= 60 ? '#28a745' : '#dc3545'; ?>" 
                                                            stroke-width="6" fill="transparent"
                                                            stroke-dasharray="<?php echo 2 * 3.14159 * 35; ?>"
                                                            stroke-dashoffset="<?php echo 2 * 3.14159 * 35 * (1 - $percentage/100); ?>"
                                                            transform="rotate(-90 40 40)"/>
                                                </svg>
                                                <div class="position-absolute top-50 start-50 translate-middle">
                                                    
                                                
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Statistics Row -->
                                        <div class="row text-center mb-3">
                                            <div class="col-4">
                                                <div class="border-end">
                                                    <h5 class="text-success mb-1"><?php echo $correct_count; ?></h5>
                                                    <small class="text-muted">Düzgün</small>
                                                </div>
                                            </div>
                                            <div class="col-4">
                                                <div class="border-end">
                                                    <h5 class="text-danger mb-1"><?php echo count($answer_details) - $correct_count; ?></h5>
                                                    <small class="text-muted">Səhv</small>
                                                </div>
                                            </div>
                                            <div class="col-4">
                                                <h5 class="text-primary mb-1"><?php echo count($answer_details); ?></h5>
                                                <small class="text-muted">Toplam</small>
                                            </div>
                                        </div>
                                        
                                        <!-- Progress Bar -->
                                        <div class="mb-3">
                                           
                                           
                                        </div>
                                        
                                        <!-- Date and Time -->
                                        <div class="d-flex justify-content-between text-muted small mb-2">
                                            <span>
                                                <i class="fas fa-calendar me-1"></i>
                                                <?php echo date('d.m.Y', strtotime($result['date_exam'])); ?>
                                            </span>
                                            <span>
                                                <i class="fas fa-clock me-1"></i>
                                                <?php echo date('H:i', strtotime($result['datetime'])); ?>
                                            </span>
                                        </div>
                                        
                                        <!-- Score Information -->
                                        <div class="text-muted small mb-3">
                                            <div class="d-flex justify-content-between">
                                                <span>
                                                    <i class="fas fa-chart-line me-1"></i>
                                                    Bal: <?php echo number_format($total_score, 1); ?> / <?php echo number_format($max_possible_score, 1); ?>
                                                </span>
                                                
                                            </div>
                                        </div>
                                        
                                        <!-- Action Buttons -->
                                        <?php if ($result['status'] == 'completed'): ?>
                                            <button class="btn btn-outline-primary btn-sm w-100" onclick="showExamDetails(<?php echo $result['id_exam']; ?>)">
                                                <i class="fas fa-eye me-1"></i>Ətraflı Nəticə
                                            </button>
                                        <?php elseif ($result['status'] == 'in_progress'): ?>
                                            <a href="exam.php?id=<?php echo $result['id_exam']; ?>" class="btn btn-warning btn-sm w-100">
                                                <i class="fas fa-play me-1"></i>İmtahana Davam Et
                                            </a>
                                        <?php else: ?>
                                            <button class="btn btn-outline-secondary btn-sm w-100" disabled>
                                                <i class="fas fa-hourglass-half me-1"></i>Gözləyir
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- Grade Badge -->
                                    <div class="card-footer bg-transparent border-0 text-center">
                                        <?php
                                        $grade = 'F';
                                        $grade_bg = 'bg-danger';
                                        if ($percentage >= 90) {
                                            $grade = 'A+';
                                            $grade_bg = 'bg-success';
                                        } elseif ($percentage >= 80) {
                                            $grade = 'A';
                                            $grade_bg = 'bg-success';
                                        } elseif ($percentage >= 70) {
                                            $grade = 'B';
                                            $grade_bg = 'bg-primary';
                                        } elseif ($percentage >= 60) {
                                            $grade = 'C';
                                            $grade_bg = 'bg-warning';
                                        } elseif ($percentage >= 40) {
                                            $grade = 'D';
                                            $grade_bg = 'bg-info';
                                        }
                                        ?>
                                       
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Detailed Table -->
                    <div class="mt-5">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0">
                                <i class="fas fa-table me-2"></i>Ətraflı Cədvəl
                            </h5>
                            <div>
                                <button class="btn btn-outline-primary btn-sm" onclick="exportResults()">
                                    <i class="fas fa-download me-1"></i>Export
                                </button>
                            </div>
                        </div>
                        
                        <div class="table-responsive">
                            <table class="table table-hover" id="resultsTable">
                                <thead class="table-dark">
                                    <tr>
                                        <th><i class="fas fa-hashtag me-1"></i>#</th>
                                        <th><i class="fas fa-book me-1"></i>Fənn</th>
                                        <th><i class="fas fa-calendar me-1"></i>Tarix</th>
                                        <th><i class="fas fa-info-circle me-1"></i>Status</th>
                                        <th><i class="fas fa-question-circle me-1"></i>Suallar</th>
                                        <th><i class="fas fa-check-circle me-1"></i>Düzgün</th>
                                        <th><i class="fas fa-chart-line me-1"></i>Bal</th>
                                        <th><i class="fas fa-percentage me-1"></i>Faiz</th>
                                        <th><i class="fas fa-medal me-1"></i>Qiymət</th>
                                        <th><i class="fas fa-cogs me-1"></i>Əməliyyat</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($exam_results as $index => $result): ?>
                                        <?php
                                        // Yenidən hesabla (cache üçün)
                                        $detail_query = "SELECT 
                                                           a.user_answer,
                                                           a.correct_var,
                                                           qr.question_score,
                                                           CASE 
                                                             WHEN TRIM(LOWER(a.user_answer)) = TRIM(LOWER(a.correct_var)) THEN 1
                                                             ELSE 0
                                                           END as is_correct
                                                         FROM answers a
                                                         JOIN question_read qr ON a.id_questions = qr.id_question_text
                                                         WHERE a.exam_id = :exam_id AND a.user_id = :user_id";
                                        
                                        $detail_stmt = $db->prepare($detail_query);
                                        $detail_stmt->bindParam(":exam_id", $result['id_exam']);
                                        $detail_stmt->bindParam(":user_id", $user_id);
                                        $detail_stmt->execute();
                                        $details = $detail_stmt->fetchAll(PDO::FETCH_ASSOC);
                                        
                                        $correct = 0;
                                        $score = 0;
                                        $max_score = 0;
                                        
                                        foreach ($details as $detail) {
                                            $max_score += $detail['question_score'];
                                            if ($detail['is_correct']) {
                                                $correct++;
                                                $score += $detail['question_score'];
                                            }
                                        }
                                        
                                        $percentage = $max_score > 0 ? ($score / $max_score) * 100 : 0;
                                        
                                        // Grade calculation
                                        $grade = 'F';
                                        $grade_class = 'text-danger';
                                        if ($percentage >= 90) {
                                            $grade = 'A+';
                                            $grade_class = 'text-success';
                                        } elseif ($percentage >= 80) {
                                            $grade = 'A';
                                            $grade_class = 'text-success';
                                        } elseif ($percentage >= 70) {
                                            $grade = 'B';
                                            $grade_class = 'text-primary';
                                        } elseif ($percentage >= 60) {
                                            $grade = 'C';
                                            $grade_class = 'text-warning';
                                        } elseif ($percentage >= 40) {
                                            $grade = 'D';
                                            $grade_class = 'text-info';
                                        }
                                        
                                        // Row class based on performance
                                        $row_class = '';
                                        if ($percentage >= 80) $row_class = 'table-success';
                                        elseif ($percentage >= 60) $row_class = 'table-warning';
                                        elseif ($percentage < 40) $row_class = 'table-danger';
                                        ?>
                                        <tr class="<?php echo $row_class; ?>">
                                            <td><strong><?php echo $index + 1; ?></strong></td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($result['subjectname']); ?></strong>
                                            </td>
                                            <td>
                                                <div>
                                                    <?php echo date('d.m.Y', strtotime($result['date_exam'])); ?>
                                                    <br><small class="text-muted"><?php echo date('H:i', strtotime($result['datetime'])); ?></small>
                                                </div>
                                            </td>
                                            <td>
                                                <?php
                                                switch($result['status']) {
                                                    case 'completed':
                                                        echo '<span class="badge bg-success"><i class="fas fa-check me-1"></i>Tamamlandı</span>';
                                                        break;
                                                    case 'in_progress':
                                                        echo '<span class="badge bg-warning"><i class="fas fa-play me-1"></i>Davam edir</span>';
                                                        break;
                                                    default:
                                                        echo '<span class="badge bg-secondary"><i class="fas fa-question me-1"></i>Naməlum</span>';
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-info"><?php echo count($details); ?></span>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <span class="badge bg-success me-2"><?php echo $correct; ?></span>
                                                    <small class="text-muted">/ <?php echo count($details); ?></small>
                                                </div>
                                            </td>
                                            <td>
                                                <div>
                                                    <strong><?php echo number_format($score, 1); ?></strong>
                                                    <br><small class="text-muted">/ <?php echo number_format($max_score, 1); ?></small>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="progress me-2" style="width: 50px; height: 8px;">
                                                        <div class="progress-bar <?php echo $percentage >= 60 ? 'bg-success' : 'bg-danger'; ?>" 
                                                             style="width: <?php echo $percentage; ?>%"></div>
                                                    </div>
                                                    <span class="<?php echo $percentage >= 60 ? 'text-success' : 'text-danger'; ?> fw-bold">
                                                        <?php echo number_format($percentage, 1); ?>%
                                                    </span>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge <?php echo $grade_class === 'text-success' ? 'bg-success' : ($grade_class === 'text-warning' ? 'bg-warning' : ($grade_class === 'text-primary' ? 'bg-primary' : 'bg-danger')); ?> fs-6">
                                                    <?php echo $grade; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($result['status'] == 'completed'): ?>
                                                    <button class="btn btn-outline-primary btn-sm" onclick="showExamDetails(<?php echo $result['id_exam']; ?>)" title="Ətraflı bax">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                <?php elseif ($result['status'] == 'in_progress'): ?>
                                                    <a href="exam.php?id=<?php echo $result['id_exam']; ?>" class="btn btn-warning btn-sm" title="Davam et">
                                                        <i class="fas fa-play"></i>
                                                    </a>
                                                <?php else: ?>
                                                    <button class="btn btn-outline-secondary btn-sm" disabled title="Gözləyir">
                                                        <i class="fas fa-hourglass-half"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Modal for Exam Details -->
<div class="modal fade" id="examDetailModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="fas fa-chart-line me-2"></i>İmtahan Təfərrüatları
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="examDetailContent">
                <!-- AJAX ilə yüklənəcək -->
            </div>
        </div>
    </div>
</div>

</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Exam details modal
function showExamDetails(examId) {
    const modal = new bootstrap.Modal(document.getElementById('examDetailModal'));
    
    // Loading göstər
    document.getElementById('examDetailContent').innerHTML = `
        <div class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Yüklənir...</span>
            </div>
            <p class="mt-3">İmtahan təfərrüatları yüklənir...</p>
        </div>
    `;
    
    modal.show();
    
    // AJAX sorğusu
    fetch(`exam_details.php?exam_id=${examId}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.text();
        })
        .then(data => {
            document.getElementById('examDetailContent').innerHTML = data;
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('examDetailContent').innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Xəta baş verdi:</strong> ${error.message}
                    <br><small>Səhifəni yeniləyib yenidən cəhd edin.</small>
                </div>
            `;
        });
}

// Export results function
function exportResults() {
    const table = document.getElementById('resultsTable');
    if (!table) {
        alert('Cədvəl tapılmadı');
        return;
    }
    
    // Simple CSV export
    let csv = '';
    const rows = table.querySelectorAll('tr');
    
    for (let i = 0; i < rows.length; i++) {
        const cols = rows[i].querySelectorAll('td, th');
        const rowData = [];
        
        for (let j = 0; j < cols.length - 1; j++) { // Exclude last column (actions)
            let cellText = cols[j].innerText.replace(/\s+/g, ' ').trim();
            rowData.push('"' + cellText + '"');
        }
        
        csv += rowData.join(',') + '\n';
    }
    
    // Download CSV
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    link.setAttribute('href', url);
    link.setAttribute('download', 'imtahan_neticeleri.csv');
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

// Page load animations
document.addEventListener('DOMContentLoaded', function() {
    // Progress bar animasiyaları
    const progressBars = document.querySelectorAll('.progress-bar');
    progressBars.forEach((bar, index) => {
        const width = bar.style.width;
        bar.style.width = '0%';
        setTimeout(() => {
            bar.style.transition = 'width 1.5s ease-in-out';
            bar.style.width = width;
        }, 200 + (index * 100));
    });
    
    // Card hover effects
    const cards = document.querySelectorAll('.card');
    cards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-8px)';
            this.style.transition = 'transform 0.3s ease';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });
    
    // Auto refresh for ongoing exams (every 5 minutes)
    if (window.location.search.includes('tab=ongoing')) {
        setInterval(() => {
            location.reload();
        }, 300000); // 5 minutes
    }
    
    // Countdown timer for upcoming exams
    const countdownElements = document.querySelectorAll('.countdown-timer span');
    if (countdownElements.length > 0) {
        setInterval(updateCountdowns, 60000); // Update every minute
    }
});

function updateCountdowns() {
    // Bu funksiya gələcək imtahanlar üçün geri sayım təze edir
    // Real-time update üçün istifadə olunur
    location.reload();
}

// Notification system
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    notification.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : (type === 'danger' ? 'exclamation-triangle' : 'info-circle')} me-2"></i>
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(notification);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        if (notification.parentNode) {
            notification.remove();
        }
    }, 5000);
}

// Error handling for images
document.addEventListener('DOMContentLoaded', function() {
    const images = document.querySelectorAll('img');
    images.forEach(img => {
        img.addEventListener('error', function() {
            this.style.display = 'none';
        });
    });
});
</script>

<style>
.card {
    transition: all 0.3s ease;
    border-radius: 15px;
}

.card:hover {
    box-shadow: 0 8px 25px rgba(0,0,0,0.15) !important;
}

.progress-bar {
    transition: width 0.6s ease;
}

.badge {
    font-size: 0.75em;
}

.border-end {
    border-right: 1px solid #dee2e6 !important;
}

.bg-gradient {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
}

.nav-tabs .nav-link.active {
    background-color: white !important;
    color: #495057 !important;
    border-color: #dee2e6 #dee2e6 #fff !important;
}

.table th {
    font-weight: 600;
    font-size: 0.875rem;
}

.countdown-timer {
    font-size: 1.1rem;
}

@media (max-width: 576px) {
    .border-end {
        border-right: none !important;
        border-bottom: 1px solid #dee2e6 !important;
        padding-bottom: 10px;
        margin-bottom: 10px;
    }
    
    .card-body {
        padding: 1rem;
    }
    
    .table-responsive {
        font-size: 0.875rem;
    }
}

/* Custom scrollbar */
.table-responsive::-webkit-scrollbar {
    height: 8px;
}

.table-responsive::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 10px;
}

.table-responsive::-webkit-scrollbar-thumb {
    background: #888;
    border-radius: 10px;
}

.table-responsive::-webkit-scrollbar-thumb:hover {
    background: #555;
}

/* Animations */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.card {
    animation: fadeInUp 0.6s ease-out;
}

.alert {
    animation: fadeInUp 0.4s ease-out;
}
</style>

<?php include_once "../includes/footer.php"; ?>