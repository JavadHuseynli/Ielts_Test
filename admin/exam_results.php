<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
require_once "../includes/db.php";
require_once "../includes/auth.php";

checkLogin();

// Permission Check for all roles that can view exam results
if (!is_admin() && !is_prorektor() && !is_kafedra()) {
    header("Location: dashboard.php?error=access_denied");
    exit();
}

$database = new Database();
$db = $database->getConnection();

$exam_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$teacher_subject_id = $_SESSION['subject_id'] ?? null;

// Handle redirection if exam_id is not set
if ($exam_id == 0) {
    if (is_admin() || is_prorektor() || is_kafedra()) {
        header("Location: exams.php");
        exit();
    } elseif (is_teacher()) {
        if ($teacher_subject_id) {
            header("Location: dashboard.php?error=select_exam_for_teacher");
            exit();
        } else {
            header("Location: dashboard.php?error=no_subject_assigned");
            exit();
        }
    }
}

// Fetch Exam Information
$query = "SELECT e.id_exam, e.date_exam, e.datetime, e.status, e.confirmed_by, e.confirmed_at, s.subjectname, s.id_subject, sg.group_number
          FROM exams e
          JOIN subjects s ON e.id_subject = s.id_subject
          JOIN student_group sg ON e.id_student_group = sg.id_student_group
          WHERE e.id_exam = :exam_id";

if (is_teacher()) {
    $query .= " AND e.id_subject = :teacher_subject_id";
}
$stmt = $db->prepare($query);
$stmt->bindParam(":exam_id", $exam_id);
if (is_teacher()) {
    $stmt->bindParam(":teacher_subject_id", $teacher_subject_id);
}
$stmt->execute();

if ($stmt->rowCount() == 0) {
    header("Location: exams.php?error=exam_not_found_or_unauthorized");
    exit();
}

$exam = $stmt->fetch(PDO::FETCH_ASSOC);

if ($exam['status'] != 'completed' && !is_admin()) {
    header("Location: exams.php?error=exam_not_completed");
    exit();
}

// Handle Manual Scores Update (Only for Admin and Kafedra) - BEFORE header.php
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_manual_scores'])) {
    // Check if exam is confirmed - only admin can edit after confirmation
    $checkQuery = "SELECT confirmed_by FROM exams WHERE id_exam = :exam_id";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->bindParam(":exam_id", $exam_id);
    $checkStmt->execute();
    $examCheck = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if ($examCheck['confirmed_by'] && !is_admin()) {
        header("Location: exam_results.php?id=" . $exam_id . "&error=exam_confirmed");
        exit();
    }

    if (!is_admin() && !is_kafedra()) {
        header("Location: exam_results.php?id=" . $exam_id . "&error=no_permission");
        exit();
    } else {
        $user_id = intval($_POST['user_id']);
        $writing_score = floatval($_POST['writing_score']);
        $speaking_score = floatval($_POST['speaking_score']);

        try {
            $query = "INSERT INTO manual_scores (exam_id, user_id, writing_score, speaking_score, added_by)
                      VALUES (:exam_id, :user_id, :writing_score, :speaking_score, :added_by)
                      ON DUPLICATE KEY UPDATE
                      writing_score = :writing_score,
                      speaking_score = :speaking_score,
                      added_by = :added_by";
            $stmt = $db->prepare($query);
            $stmt->execute([
                ':exam_id' => $exam_id,
                ':user_id' => $user_id,
                ':writing_score' => $writing_score,
                ':speaking_score' => $speaking_score,
                ':added_by' => $_SESSION['user_id']
            ]);

            // Redirect to refresh the page with success message
            header("Location: exam_results.php?id=" . $exam_id . "&success=1");
            exit();
        } catch (PDOException $e) {
            header("Location: exam_results.php?id=" . $exam_id . "&error=db_error");
            exit();
        }
    }
}

// Handle Archiving of Results - BEFORE header.php
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['archive_results'])) {
    if (!is_admin()) {
        header("Location: exam_results.php?id=" . $exam_id . "&error=no_permission_archive");
        exit();
    } else {
        try {
            $db->beginTransaction();

            $check_archive_query = "SELECT COUNT(*) FROM exam_results_archive WHERE exam_id = :exam_id";
            $stmt_check_archive = $db->prepare($check_archive_query);
            $stmt_check_archive->bindParam(":exam_id", $exam_id);
            $stmt_check_archive->execute();
            if ($stmt_check_archive->fetchColumn() > 0) {
                $db->rollBack();
                header("Location: exam_results.php?id=" . $exam_id . "&error=already_archived");
                exit();
            }

            // Get results for archiving
            $query = "SELECT u.id_users, SUM(qr.question_score * a.is_correct) as total_score
                      FROM users u
                      JOIN answers a ON u.id_users = a.user_id
                      JOIN question_read qr ON a.id_questions = qr.id_question_text
                      WHERE a.exam_id = :exam_id
                      GROUP BY u.id_users";
            $stmt = $db->prepare($query);
            $stmt->bindParam(":exam_id", $exam_id);
            $stmt->execute();
            $results_to_archive = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($results_to_archive as $result_item) {
                $query = "INSERT INTO exam_results_archive (exam_id, user_id, score_earned, archived_by_user_id)
                          VALUES (:exam_id, :user_id, :score_earned, :archived_by_user_id)";
                $stmt = $db->prepare($query);
                $stmt->execute([
                    ':exam_id' => $exam_id,
                    ':user_id' => $result_item['id_users'],
                    ':score_earned' => $result_item['total_score'],
                    ':archived_by_user_id' => $_SESSION['user_id']
                ]);
            }
            $db->commit();
            header("Location: exam_results.php?id=" . $exam_id . "&success=archived");
            exit();
        } catch (PDOException $e) {
            $db->rollBack();
            header("Location: exam_results.php?id=" . $exam_id . "&error=archive_failed");
            exit();
        }
    }
}

$pageTitle = "İmtahan Nəticələri";
include_once "../includes/header.php";

// Tələbə nəticələrini almaq
$query = "SELECT u.id_users, u.f_name, u.username, sg.group_number,
          COUNT(a.id_answer) as total_questions,
          SUM(a.is_correct) as correct_answers,
          SUM(qr.question_score * a.is_correct) as computer_score,
          COALESCE(ms.writing_score, 0) as writing_score,
          COALESCE(ms.speaking_score, 0) as speaking_score,
          (SUM(qr.question_score * a.is_correct) + COALESCE(ms.writing_score, 0) + COALESCE(ms.speaking_score, 0)) as total_score,
          (SELECT SUM(qr2.question_score)
           FROM question_read qr2
           JOIN question_files qf2 ON qr2.id_read_quest_file = qf2.id_read_quest_file
           WHERE qf2.subject_id = s.id_subject) as max_score
          FROM users u
          JOIN answers a ON u.id_users = a.user_id
          JOIN question_read qr ON a.id_questions = qr.id_question_text
          JOIN exams e ON a.exam_id = e.id_exam
          JOIN subjects s ON e.id_subject = s.id_subject
          LEFT JOIN student_group sg ON u.group_id = sg.id_student_group
          LEFT JOIN manual_scores ms ON ms.exam_id = a.exam_id AND ms.user_id = u.id_users
          WHERE a.exam_id = :exam_id
          GROUP BY u.id_users
          ORDER BY total_score DESC";
$stmt = $db->prepare($query);
$stmt->bindParam(":exam_id", $exam_id);
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Ümumi statistika
$totalStudents = count($results);
$totalScore = 0;
$totalCorrect = 0;
$totalQuestions = 0;
$maxScore = $totalStudents > 0 ? $results[0]['max_score'] : 0;

foreach ($results as $result) {
    $totalScore += $result['total_score'];
    $totalCorrect += $result['correct_answers'];
    $totalQuestions += $result['total_questions'];
}

$avgScore = $totalStudents > 0 ? $totalScore / $totalStudents : 0;
$avgCorrect = $totalStudents > 0 ? $totalCorrect / $totalStudents : 0;
$avgPercent = $maxScore > 0 ? ($avgScore / $maxScore) * 100 : 0;

// Check for messages from redirect
$message = "";
$message_type = "";

if (isset($_GET['success'])) {
    if ($_GET['success'] == 1) {
        $message = 'Ballar uğurla yeniləndi!';
        $message_type = 'success';
    } elseif ($_GET['success'] == 'archived') {
        $message = 'Nəticələr uğurla arxivləndi!';
        $message_type = 'success';
    }
}

if (isset($_GET['error'])) {
    if ($_GET['error'] == 'no_permission') {
        $message = 'Bu əməliyyata icazəniz yoxdur!';
        $message_type = 'error';
    } elseif ($_GET['error'] == 'exam_confirmed') {
        $message = 'Bu imtahan təsdiqlənib! Yalnız admin dəyişiklik edə bilər.';
        $message_type = 'error';
    } elseif ($_GET['error'] == 'db_error') {
        $message = 'Balları yeniləyərkən xəta baş verdi!';
        $message_type = 'error';
    } elseif ($_GET['error'] == 'no_permission_archive') {
        $message = 'Nəticələri arxivləməyə icazəniz yoxdur!';
        $message_type = 'error';
    } elseif ($_GET['error'] == 'already_archived') {
        $message = 'Bu imtahan nəticələri artıq arxivlənib!';
        $message_type = 'error';
    } elseif ($_GET['error'] == 'archive_failed') {
        $message = 'Nəticələri arxivləyərkən xəta baş verdi!';
        $message_type = 'error';
    }
}
?>

<style>
.results-gradient {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.stat-card {
    background: white;
    border: 1px solid #e5e7eb;
    transition: all 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
}

.results-table {
    background: white;
    border: 1px solid #e5e7eb;
}

.result-row {
    border-bottom: 1px solid #f3f4f6;
    transition: all 0.2s ease;
    cursor: default;
}

.result-row.editable-row {
    cursor: pointer;
}

.result-row.editable-row:hover {
    background: #f9fafb;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    transform: translateX(2px);
}

.performance-excellent {
    background: linear-gradient(135deg, rgba(16, 185, 129, 0.1) 0%, rgba(5, 150, 105, 0.1) 100%);
}

.performance-good {
    background: linear-gradient(135deg, rgba(59, 130, 246, 0.1) 0%, rgba(37, 99, 235, 0.1) 100%);
}

.performance-average {
    background: linear-gradient(135deg, rgba(251, 191, 36, 0.1) 0%, rgba(245, 158, 11, 0.1) 100%);
}

.performance-poor {
    background: linear-gradient(135deg, rgba(239, 68, 68, 0.1) 0%, rgba(220, 38, 38, 0.1) 100%);
}
</style>

<div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50 p-6">
    <div class="max-w-7xl mx-auto">

        <?php if (!empty($message)): ?>
        <div class="mb-6">
            <div class="<?php echo $message_type === 'success' ? 'bg-gradient-to-r from-green-500 to-emerald-600' : 'bg-gradient-to-r from-red-500 to-pink-600'; ?> text-white px-6 py-4 rounded-xl shadow-2xl flex items-center space-x-3">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <?php if ($message_type === 'success'): ?>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    <?php else: ?>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    <?php endif; ?>
                </svg>
                <span class="font-semibold"><?php echo htmlspecialchars($message); ?></span>
            </div>
        </div>
        <?php endif; ?>

        <!-- Confirmation Status Banner -->
        <?php if ($exam['confirmed_by']): ?>
        <div class="mb-6">
            <div class="bg-gradient-to-r from-green-500 to-emerald-600 text-white px-6 py-4 rounded-xl shadow-lg flex items-center space-x-3">
                <div class="flex-shrink-0">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="flex-1">
                    <p class="font-bold text-lg">Kafedra tərəfindən təsdiqlənib</p>
                    <p class="text-sm text-green-100 mt-1">
                        Təsdiq tarixi: <?php echo $exam['confirmed_at'] ? date('d.m.Y H:i', strtotime($exam['confirmed_at'])) : 'N/A'; ?>
                    </p>
                </div>
                <div class="flex-shrink-0">
                    <span class="bg-white text-green-600 px-4 py-2 rounded-lg font-bold text-sm">✓ TƏSDİQLƏNİB</span>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Header -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 mb-6">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
                <div class="mb-4 lg:mb-0">
                    <h1 class="text-3xl font-bold text-gray-900 mb-2">İmtahan Nəticələri</h1>
                    <p class="text-lg text-gray-700 font-semibold"><?php echo htmlspecialchars($exam['subjectname']); ?> - <?php echo htmlspecialchars($exam['group_number']); ?></p>
                    <p class="text-sm text-gray-600 mt-1">
                        <span class="font-medium">Tarix:</span> <?php echo date('d.m.Y', strtotime($exam['date_exam'])); ?>,
                        <span class="font-medium">Saat:</span> <?php echo date('H:i', strtotime($exam['datetime'])); ?>
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <a href="exams.php" class="px-4 py-2 bg-gray-600 text-white rounded-xl font-semibold hover:bg-gray-700 transition-all flex items-center space-x-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                        <span>İmtahanlara qayıt</span>
                    </a>
                    <?php if (is_prorektor()): ?>
                    <a href="download_results.php?id=<?php echo $exam_id; ?>" class="px-4 py-2 bg-blue-600 text-white rounded-xl font-semibold hover:bg-blue-700 transition-all flex items-center space-x-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <span>DOCX Yüklə</span>
                    </a>
                    <?php endif; ?>
                    <?php if (is_kafedra()): ?>
                        <?php if (!$exam['confirmed_by']): ?>
                        <a href="confirm_exam_kafedra.php?id=<?php echo $exam_id; ?>" class="px-4 py-2 bg-gradient-to-r from-green-600 to-emerald-600 text-white rounded-xl font-semibold hover:from-green-700 hover:to-emerald-700 transition-all flex items-center space-x-2 shadow-md hover:shadow-lg">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span>Təsdiq edirəm</span>
                        </a>
                        <?php else: ?>
                        <a href="download_results_kafedra_doc.php?id=<?php echo $exam_id; ?>" class="px-4 py-2 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-xl font-semibold hover:from-indigo-700 hover:to-purple-700 transition-all flex items-center space-x-2 shadow-md hover:shadow-lg">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                            </svg>
                            <span>DOC Yüklə (Kafedra)</span>
                        </a>
                        <?php endif; ?>
                    <?php endif; ?>
                    <?php if (is_admin()): ?>
                    <button onclick="document.getElementById('archiveModal').classList.remove('hidden')" class="px-4 py-2 bg-amber-600 text-white rounded-xl font-semibold hover:bg-amber-700 transition-all flex items-center space-x-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path>
                        </svg>
                        <span>Nəticələri Arxivlə</span>
                    </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
            <!-- Total Students -->
            <div class="stat-card rounded-2xl p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm font-medium mb-1">Tələbə Sayı</p>
                        <p class="text-4xl font-bold text-blue-600"><?php echo $totalStudents; ?></p>
                    </div>
                    <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl flex items-center justify-center shadow-lg">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Average Score -->
            <div class="stat-card rounded-2xl p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm font-medium mb-1">Orta Bal</p>
                        <p class="text-4xl font-bold text-green-600"><?php echo number_format($avgScore, 2); ?></p>
                        <p class="text-sm text-gray-500 mt-1"><?php echo number_format($avgPercent, 2); ?>%</p>
                    </div>
                    <div class="w-16 h-16 bg-gradient-to-br from-green-500 to-emerald-600 rounded-2xl flex items-center justify-center shadow-lg">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Average Correct -->
            <div class="stat-card rounded-2xl p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm font-medium mb-1">Orta Düzgün</p>
                        <p class="text-4xl font-bold text-cyan-600"><?php echo number_format($avgCorrect, 2); ?></p>
                        <p class="text-sm text-gray-500 mt-1"><?php echo $totalStudents > 0 ? number_format(($totalCorrect / $totalQuestions) * 100, 2) : 0; ?>%</p>
                    </div>
                    <div class="w-16 h-16 bg-gradient-to-br from-cyan-500 to-blue-600 rounded-2xl flex items-center justify-center shadow-lg">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Max Score -->
            <div class="stat-card rounded-2xl p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm font-medium mb-1">Maksimum Bal</p>
                        <p class="text-4xl font-bold text-purple-600"><?php echo number_format($maxScore, 2); ?></p>
                    </div>
                    <div class="w-16 h-16 bg-gradient-to-br from-purple-500 to-indigo-600 rounded-2xl flex items-center justify-center shadow-lg">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"></path>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Results Table -->
        <div class="results-table rounded-2xl overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                <h3 class="text-lg font-bold text-gray-900">Tələbə Nəticələri</h3>
            </div>

            <?php if (empty($results)): ?>
                <div class="p-12 text-center">
                    <svg class="w-16 h-16 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                    </svg>
                    <p class="text-gray-500 text-lg font-medium">Bu imtahanda iştirak edən tələbə yoxdur</p>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-gray-200 bg-gray-50">
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">№</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Tələbə</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Qrup nömrəsi</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Kompüter balı</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Yazı balı</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Danışıq balı</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Yekun bal</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Əməliyyat</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $counter = 1;
                            foreach ($results as $result):
                                $percentage = ($result['total_score'] / $result['max_score']) * 100;
                                $performanceClass = '';

                                if ($percentage >= 85) {
                                    $performanceClass = 'performance-excellent';
                                } elseif ($percentage >= 70) {
                                    $performanceClass = 'performance-good';
                                } elseif ($percentage >= 50) {
                                    $performanceClass = 'performance-average';
                                } else {
                                    $performanceClass = 'performance-poor';
                                }

                                // Only admin and kafedra can edit scores
                                // If exam is confirmed, only admin can edit
                                $rowOnclick = '';
                                $canEdit = false;

                                if (is_admin()) {
                                    $canEdit = true;
                                } elseif (is_kafedra() && !$exam['confirmed_by']) {
                                    $canEdit = true;
                                }

                                if ($canEdit) {
                                    $rowOnclick = "onclick=\"showScorePopup(event, this, " . $result['id_users'] . ", '" . htmlspecialchars($result['f_name'], ENT_QUOTES) . "', " . $result['writing_score'] . ", " . $result['speaking_score'] . ")\"";
                                }
                            ?>
                                <tr class="result-row <?php echo $performanceClass; ?> <?php echo $canEdit ? 'editable-row' : ''; ?>" <?php echo $rowOnclick; ?>>
                                    <td class="px-6 py-4 text-sm font-mono text-gray-600"><?php echo $counter++; ?></td>
                                    <td class="px-6 py-4 text-sm font-semibold text-gray-900"><?php echo htmlspecialchars($result['f_name']); ?></td>
                                    <td class="px-6 py-4 text-sm text-gray-700">
                                        <span class="inline-flex items-center px-3 py-1 rounded-lg bg-indigo-50 text-indigo-700 text-xs font-medium border border-indigo-200">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                            </svg>
                                            <?php echo htmlspecialchars($result['group_number'] ?? 'N/A'); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm">
                                        <span class="inline-flex items-center px-3 py-1.5 rounded-lg bg-blue-50 text-blue-700 text-sm font-bold border border-blue-200">
                                            <?php echo number_format($result['computer_score'], 2); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm">
                                        <span class="inline-flex items-center px-3 py-1.5 rounded-lg bg-green-50 text-green-700 text-sm font-bold border border-green-200">
                                            <?php echo number_format($result['writing_score'], 2); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm">
                                        <span class="inline-flex items-center px-3 py-1.5 rounded-lg bg-purple-50 text-purple-700 text-sm font-bold border border-purple-200">
                                            <?php echo number_format($result['speaking_score'], 2); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm">
                                        <span class="inline-flex items-center px-4 py-2 rounded-lg bg-gradient-to-r from-amber-500 to-orange-500 text-white text-base font-bold shadow-lg">
                                            <?php echo number_format($result['total_score'], 2); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm" onclick="event.stopPropagation()">
                                        <a href="student_answers.php?exam=<?php echo $exam_id; ?>&student=<?php echo $result['id_users']; ?>"
                                           class="inline-flex items-center space-x-1 px-3 py-2 bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-lg font-semibold hover:from-blue-700 hover:to-indigo-700 transition-all">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                            </svg>
                                            <span>Detal</span>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

    </div>
</div>

<!-- Score Edit Popup Modal (Only for Admin and Kafedra) -->
<?php if (is_admin() || is_kafedra()): ?>
<div id="scorePopup" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl p-8 max-w-md w-full border-2 border-indigo-200" onclick="event.stopPropagation()">
        <form method="post" action="" id="scoreForm">
            <input type="hidden" name="update_manual_scores" value="1">
            <input type="hidden" name="user_id" id="popup_user_id">

            <div class="mb-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-2xl font-bold text-gray-900" id="popup_student_name"></h3>
                    <button type="button" onclick="hideScorePopup()" class="text-gray-400 hover:text-gray-600 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <p class="text-sm text-gray-600 flex items-center">
                    <svg class="w-4 h-4 mr-2 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                    </svg>
                    Yazı və danışıq ballarını daxil edin
                </p>
            </div>

            <div class="space-y-5">
                <div>
                    <label for="writing_score" class="block text-sm font-bold text-gray-700 mb-2 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path>
                        </svg>
                        Yazı balı
                    </label>
                    <input type="number" name="writing_score" id="writing_score" step="0.01" min="0" max="100" required
                           class="w-full px-4 py-3 border-2 border-green-300 rounded-xl focus:border-green-500 focus:ring-4 focus:ring-green-100 transition-all text-lg font-semibold"
                           placeholder="0.00">
                </div>

                <div>
                    <label for="speaking_score" class="block text-sm font-bold text-gray-700 mb-2 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"></path>
                        </svg>
                        Danışıq balı
                    </label>
                    <input type="number" name="speaking_score" id="speaking_score" step="0.01" min="0" max="100" required
                           class="w-full px-4 py-3 border-2 border-purple-300 rounded-xl focus:border-purple-500 focus:ring-4 focus:ring-purple-100 transition-all text-lg font-semibold"
                           placeholder="0.00">
                </div>
            </div>

            <div class="mt-8 flex space-x-3">
                <button type="button" onclick="hideScorePopup()"
                        class="flex-1 px-6 py-3 bg-gray-200 text-gray-700 rounded-xl font-bold hover:bg-gray-300 transition-all">
                    Ləğv et
                </button>
                <button type="submit"
                        class="flex-1 px-6 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-xl font-bold hover:from-indigo-700 hover:to-purple-700 transition-all shadow-lg hover:shadow-xl">
                    Yadda saxla
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function showScorePopup(event, row, userId, studentName, writingScore, speakingScore) {
    // Prevent event propagation
    if (event) event.stopPropagation();

    const popup = document.getElementById('scorePopup');
    document.getElementById('popup_user_id').value = userId;
    document.getElementById('popup_student_name').textContent = studentName;
    document.getElementById('writing_score').value = writingScore;
    document.getElementById('speaking_score').value = speakingScore;

    popup.classList.remove('hidden');

    // Focus on first input
    setTimeout(() => {
        document.getElementById('writing_score').focus();
        document.getElementById('writing_score').select();
    }, 100);
}

function hideScorePopup() {
    const popup = document.getElementById('scorePopup');
    popup.classList.add('hidden');
}

// Close popup when clicking outside
document.addEventListener('DOMContentLoaded', function() {
    const popup = document.getElementById('scorePopup');
    if (popup) {
        popup.addEventListener('click', function(event) {
            if (event.target === popup) {
                hideScorePopup();
            }
        });
    }

    // Close on Escape key
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            hideScorePopup();
        }
    });
});
</script>
<?php endif; ?>

<?php if (is_admin()): ?>
<!-- Archive Modal -->
<div id="archiveModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-xl font-bold text-gray-900">Nəticələri Arxivlə</h3>
            <button onclick="document.getElementById('archiveModal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600 transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        <form method="post" action="">
            <p class="text-gray-700 mb-4">Bu imtahanın nəticələrini arxivləşdirmək istədiyinizə əminsiniz?</p>
            <p class="text-red-600 text-sm mb-6">Arxivləndikdən sonra bu nəticələr cari imtahan nəticələri siyahısından çıxarılmayacaq, lakin arxivdə saxlanılacaq.</p>
            <div class="flex space-x-3">
                <button type="button" onclick="document.getElementById('archiveModal').classList.add('hidden')" class="flex-1 px-4 py-2 bg-gray-200 text-gray-700 rounded-xl font-semibold hover:bg-gray-300 transition-all">
                    Ləğv et
                </button>
                <button type="submit" name="archive_results" class="flex-1 px-4 py-2 bg-amber-600 text-white rounded-xl font-semibold hover:bg-amber-700 transition-all">
                    Arxivlə
                </button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<?php include_once "../includes/footer.php"; ?>
