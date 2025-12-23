<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
require_once "../includes/db.php";
require_once "../includes/auth.php";

checkLogin();
// Admins and Prorektors can manage exams
// Kafedra and Muellim can view exams, but not modify
$can_manage_exams = is_admin() || is_prorektor();
$can_view_results_for_all_exams = is_admin() || is_prorektor() || is_kafedra();
$can_view_own_subject_exams = is_teacher();

if (!$can_manage_exams && !$can_view_results_for_all_exams && !$can_view_own_subject_exams) {
    header("Location: dashboard.php?error=access_denied");
    exit();
}

$pageTitle = "İmtahan İdarəetməsi";
include_once "../includes/header.php";

$database = new Database();
$db = $database->getConnection();

// Mesaj dəyişəni
$message = "";
$message_type = "";

// Fənnləri almaq
$query = "SELECT id_subject, subjectname FROM subjects ORDER BY subjectname";
$stmt = $db->prepare($query);
$stmt->execute();
$subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Qrupları almaq (yalnız aktiv qruplar)
$query = "SELECT id_student_group, group_number FROM student_group WHERE is_archived = 0 ORDER BY group_number";
$stmt = $db->prepare($query);
$stmt->execute();
$groups = $stmt->fetchAll(PDO::FETCH_ASSOC);

// İmtahan əlavə etmək
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_exam'])) {
    if (!$can_manage_exams) {
        $message = 'Bu əməliyyatı etməyə icazəniz yoxdur!';
        $message_type = 'error';
    } else {
        $subject_id = $_POST['subject_id'];
        $group_id = $_POST['group_id'];
        $exam_date = $_POST['exam_date'];
        $exam_time = $_POST['exam_time'];

        // Tarix və vaxtı birləşdirmək
        $datetime = $exam_date . ' ' . $exam_time . ':00';

        try {
            $query = "INSERT INTO exams (date_exam, id_subject, id_student_group, datetime, status)
                      VALUES (:date_exam, :subject_id, :group_id, :datetime, 'pending')";
            $stmt = $db->prepare($query);
            $stmt->bindParam(":date_exam", $exam_date);
            $stmt->bindParam(":subject_id", $subject_id);
            $stmt->bindParam(":group_id", $group_id);
            $stmt->bindParam(":datetime", $datetime);

            if ($stmt->execute()) {
                $message = 'İmtahan uğurla əlavə edildi!';
                $message_type = 'success';
            } else {
                $message = 'İmtahan əlavə edilərkən xəta baş verdi!';
                $message_type = 'error';
            }
        } catch (PDOException $e) {
            $message = 'Verilənlər bazası xətası: ' . $e->getMessage();
            $message_type = 'error';
        }
    }
}

// İmtahan statusunu dəyişmək
if (isset($_GET['start']) && is_numeric($_GET['start'])) {
    if (!$can_manage_exams) {
        $message = 'Bu əməliyyatı etməyə icazəniz yoxdur!';
        $message_type = 'error';
    } else {
        $id = $_GET['start'];

        try {
            $query = "UPDATE exams SET status = 'in_progress' WHERE id_exam = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(":id", $id);

            if ($stmt->execute()) {
                $message = 'İmtahan başladıldı!';
                $message_type = 'success';
            } else {
                $message = 'İmtahan başladılarkən xəta baş verdi!';
                $message_type = 'error';
            }
        } catch (PDOException $e) {
            $message = 'Verilənlər bazası xətası: ' . $e->getMessage();
            $message_type = 'error';
        }
    }
}

if (isset($_GET['complete']) && is_numeric($_GET['complete'])) {
    if (!$can_manage_exams) {
        $message = 'Bu əməliyyatı etməyə icazəniz yoxdur!';
        $message_type = 'error';
    } else {
        $id = $_GET['complete'];

        try {
            $query = "UPDATE exams SET status = 'completed' WHERE id_exam = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(":id", $id);

            if ($stmt->execute()) {
                $message = 'İmtahan tamamlandı!';
                $message_type = 'success';
            } else {
                $message = 'İmtahan tamamlanarkən xəta baş verdi!';
                $message_type = 'error';
            }
        } catch (PDOException $e) {
            $message = 'Verilənlər bazası xətası: ' . $e->getMessage();
            $message_type = 'error';
        }
    }
}

// İmtahan silmək
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    if (!$can_manage_exams) {
        $message = 'Bu əməliyyatı etməyə icazəniz yoxdur!';
        $message_type = 'error';
    } else {
        $id = $_GET['delete'];

        try {
            // İmtahanda cavablar varmı yoxlamaq
            $query = "SELECT COUNT(*) as answer_count FROM answers WHERE exam_id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(":id", $id);
            $stmt->execute();
            $answer_count = $stmt->fetch(PDO::FETCH_ASSOC)['answer_count'];

            if ($answer_count > 0) {
                $message = 'Bu imtahanda cavablar var, silmək mümkün deyil!';
                $message_type = 'error';
            } else {
                $query = "DELETE FROM exams WHERE id_exam = :id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(":id", $id);

                if ($stmt->execute()) {
                    $message = 'İmtahan uğurla silindi!';
                    $message_type = 'success';
                } else {
                    $message = 'İmtahan silinərkən xəta baş verdi!';
                    $message_type = 'error';
                }
            }
        } catch (PDOException $e) {
            $message = 'Verilənlər bazası xətası: ' . $e->getMessage();
            $message_type = 'error';
        }
    }
}

// Filtrlər
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$subject_filter = isset($_GET['subject']) ? $_GET['subject'] : '';
$group_filter = isset($_GET['group']) ? $_GET['group'] : '';

// Prorektor cannot filter by status (always shows completed exams only)
if (is_prorektor()) {
    $status_filter = '';
}

// Pagination
$items_per_page = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $items_per_page;

// Sorğunu hazırlamaq - count
$count_query = "SELECT COUNT(*) as total
          FROM exams e
          JOIN subjects s ON e.id_subject = s.id_subject
          JOIN student_group sg ON e.id_student_group = sg.id_student_group
          WHERE 1=1";

$params = array();

// Prorektor only sees completed exams with answers
if (is_prorektor()) {
    $count_query .= " AND e.status = 'completed' AND EXISTS (
        SELECT 1 FROM answers a WHERE a.exam_id = e.id_exam LIMIT 1
    )";
}

if (is_teacher()) { // Teachers only see exams for their subject
    $count_query .= " AND e.id_subject = :teacher_subject_id";
    $params[':teacher_subject_id'] = $_SESSION['subject_id'];
}

if (!empty($status_filter)) {
    $count_query .= " AND e.status = :status";
    $params[':status'] = $status_filter;
}

if (!empty($subject_filter)) {
    $count_query .= " AND e.id_subject = :subject_id";
    $params[':subject_id'] = $subject_filter;
}

if (!empty($group_filter)) {
    $count_query .= " AND e.id_student_group = :group_id";
    $params[':group_id'] = $group_filter;
}

$count_stmt = $db->prepare($count_query);
foreach ($params as $key => $value) {
    $count_stmt->bindValue($key, $value);
}
$count_stmt->execute();
$total_exams = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_exams / $items_per_page);

// Main query with pagination
$query = "SELECT e.id_exam, e.date_exam, e.datetime, e.status, e.id_subject, s.subjectname, sg.group_number
          FROM exams e
          JOIN subjects s ON e.id_subject = s.id_subject
          JOIN student_group sg ON e.id_student_group = sg.id_student_group
          WHERE 1=1";

// Prorektor only sees completed exams with answers
if (is_prorektor()) {
    $query .= " AND e.status = 'completed' AND EXISTS (
        SELECT 1 FROM answers a WHERE a.exam_id = e.id_exam LIMIT 1
    )";
}

if (is_teacher()) {
    $query .= " AND e.id_subject = :teacher_subject_id";
}

if (!empty($status_filter)) {
    $query .= " AND e.status = :status";
}

if (!empty($subject_filter)) {
    $query .= " AND e.id_subject = :subject_id";
}

if (!empty($group_filter)) {
    $query .= " AND e.id_student_group = :group_id";
}

$query .= " ORDER BY e.datetime DESC LIMIT :limit OFFSET :offset";

$stmt = $db->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':limit', $items_per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$exams = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate status counts
$stats_query = "SELECT
    COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_count,
    COUNT(CASE WHEN status = 'in_progress' THEN 1 END) as in_progress_count,
    COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_count
    FROM exams" . (is_teacher() ? " WHERE id_subject = :teacher_subject_id" : "");
$stats_stmt = $db->prepare($stats_query);
if (is_teacher()) {
    $stats_stmt->bindValue(':teacher_subject_id', $_SESSION['subject_id']);
}
$stats_stmt->execute();
$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
?>

<style>
@import url('https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&display=swap');

.exam-gradient {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
}

.filter-card {
    background: rgba(255, 255, 255, 0.03);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.stat-box {
    background: linear-gradient(135deg, rgba(240, 147, 251, 0.1) 0%, rgba(245, 87, 108, 0.1) 100%);
    border: 1px solid rgba(240, 147, 251, 0.2);
    transition: all 0.3s ease;
}

.stat-box:hover {
    background: linear-gradient(135deg, rgba(240, 147, 251, 0.2) 0%, rgba(245, 87, 108, 0.2) 100%);
    transform: translateY(-2px);
}

.exam-table {
    background: white;
    border: 1px solid #e5e7eb;
}

.exam-row {
    border-bottom: 1px solid #f3f4f6;
    transition: all 0.2s ease;
}

.exam-row:hover {
    background: #fdf2f8;
}

.status-pending {
    background: linear-gradient(135deg, rgba(251, 191, 36, 0.2) 0%, rgba(245, 158, 11, 0.2) 100%);
    color: #fbbf24;
    border: 1px solid rgba(251, 191, 36, 0.3);
}

.status-in-progress {
    background: linear-gradient(135deg, rgba(34, 197, 94, 0.2) 0%, rgba(21, 128, 61, 0.2) 100%);
    color: #22c55e;
    border: 1px solid rgba(34, 197, 94, 0.3);
}

.status-completed {
    background: linear-gradient(135deg, rgba(59, 130, 246, 0.2) 0%, rgba(37, 99, 235, 0.2) 100%);
    color: #3b82f6;
    border: 1px solid rgba(59, 130, 246, 0.3);
}

.action-button {
    transition: all 0.2s ease;
}

.action-button:hover {
    transform: scale(1.05);
}

.notification {
    animation: slideInDown 0.5s ease-out;
}

@keyframes slideInDown {
    from {
        opacity: 0;
        transform: translateY(-100%);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>

<div class="p-6" x-data="{ showModal: false, showNotification: <?php echo !empty($message) ? 'true' : 'false'; ?> }">

    <!-- Notification -->
    <?php if (!empty($message)): ?>
    <div x-show="showNotification"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 transform -translate-y-2"
         x-transition:enter-end="opacity-100 transform translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="notification fixed top-4 right-4 z-50 max-w-md">
        <div class="<?php echo $message_type === 'success' ? 'bg-gradient-to-r from-green-500 to-emerald-600' : 'bg-gradient-to-r from-red-500 to-pink-600'; ?> text-white px-6 py-4 rounded-xl shadow-2xl flex items-center space-x-3">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <?php if ($message_type === 'success'): ?>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                <?php else: ?>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                <?php endif; ?>
            </svg>
            <span class="font-medium"><?php echo $message; ?></span>
            <button @click="showNotification = false" class="ml-auto">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
    </div>
    <?php endif; ?>

    <!-- Header -->
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between mb-8">
        <div>
            <h1 class="text-4xl font-extrabold bg-gradient-to-r from-pink-400 via-purple-400 to-indigo-400 bg-clip-text text-transparent mb-2" style="font-family: 'Space Grotesk', sans-serif;">
                İmtahanların İdarə Edilməsi
            </h1>
            <p class="text-gray-400 font-medium">İmtahanları yaradın, idarə edin və nəticələrə baxın</p>
        </div>
        <?php if ($can_manage_exams): ?>
        <button @click="showModal = true"
                class="mt-4 lg:mt-0 exam-gradient text-white px-6 py-3 rounded-xl font-semibold shadow-lg hover:shadow-pink-500/50 transition-all duration-300 hover:scale-105 flex items-center space-x-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
            </svg>
            <span>Yeni İmtahan</span>
        </button>
        <?php endif; ?>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="stat-box rounded-2xl p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-400 text-sm font-medium mb-1">Gözləyir</p>
                    <p class="text-4xl font-bold text-yellow-400"><?php echo $stats['pending_count']; ?></p>
                </div>
                <div class="w-16 h-16 bg-gradient-to-br from-yellow-500 to-orange-500 rounded-2xl flex items-center justify-center">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="stat-box rounded-2xl p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-400 text-sm font-medium mb-1">Davam edir</p>
                    <p class="text-4xl font-bold text-green-400"><?php echo $stats['in_progress_count']; ?></p>
                </div>
                <div class="w-16 h-16 bg-gradient-to-br from-green-500 to-emerald-500 rounded-2xl flex items-center justify-center">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="stat-box rounded-2xl p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-400 text-sm font-medium mb-1">Tamamlanıb</p>
                    <p class="text-4xl font-bold text-blue-400"><?php echo $stats['completed_count']; ?></p>
                </div>
                <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-indigo-500 rounded-2xl flex items-center justify-center">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="filter-card rounded-2xl p-6 mb-8">
        <h3 class="text-lg font-semibold text-white mb-4">Filtrlər</h3>
        <form method="get" action="" class="grid grid-cols-1 md:grid-cols-<?php echo is_prorektor() ? '3' : '4'; ?> gap-4">
            <?php if (!is_prorektor()): ?>
            <div>
                <label for="status" class="block text-sm font-medium text-gray-400 mb-2">Status</label>
                <select name="status" id="status" class="w-full bg-gray-800/50 border border-gray-700 rounded-xl px-4 py-2.5 text-white focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-transparent transition-all">
                    <option value="">Hamısı</option>
                    <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Gözləyir</option>
                    <option value="in_progress" <?php echo $status_filter == 'in_progress' ? 'selected' : ''; ?>>Davam edir</option>
                    <option value="completed" <?php echo $status_filter == 'completed' ? 'selected' : ''; ?>>Tamamlanıb</option>
                </select>
            </div>
            <?php endif; ?>

            <div>
                <label for="subject" class="block text-sm font-medium text-gray-400 mb-2">Fənn</label>
                <select name="subject" id="subject" class="w-full bg-gray-800/50 border border-gray-700 rounded-xl px-4 py-2.5 text-white focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-transparent transition-all" <?php echo is_teacher() ? 'disabled' : ''; ?>>
                    <option value="">Hamısı</option>
                    <?php foreach ($subjects as $subject_item): ?>
                        <option value="<?php echo $subject_item['id_subject']; ?>" <?php echo $subject_filter == $subject_item['id_subject'] ? 'selected' : ''; ?> <?php echo (is_teacher() && $subject_item['id_subject'] != $_SESSION['subject_id']) ? 'disabled' : ''; ?>>
                            <?php echo $subject_item['subjectname']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (is_teacher()): ?>
                    <input type="hidden" name="subject" value="<?php echo $_SESSION['subject_id']; ?>">
                <?php endif; ?>
            </div>

            <div>
                <label for="group" class="block text-sm font-medium text-gray-400 mb-2">Qrup</label>
                <select name="group" id="group" class="w-full bg-gray-800/50 border border-gray-700 rounded-xl px-4 py-2.5 text-white focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-transparent transition-all">
                    <option value="">Hamısı</option>
                    <?php foreach ($groups as $group_item): ?>
                        <option value="<?php echo $group_item['id_student_group']; ?>" <?php echo $group_filter == $group_item['id_student_group'] ? 'selected' : ''; ?>>
                            <?php echo $group_item['group_number']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="flex items-end space-x-2">
                <button type="submit" class="flex-1 exam-gradient text-white px-6 py-2.5 rounded-xl font-semibold hover:shadow-pink-500/50 transition-all">
                    Filtr
                </button>
                <a href="exams.php" class="px-6 py-2.5 bg-gray-700 text-white rounded-xl font-semibold hover:bg-gray-600 transition-all">
                    Sıfırla
                </a>
            </div>
        </form>
    </div>

    <!-- Exams Table -->
    <div class="exam-table rounded-2xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-gray-200 bg-gray-50">
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">ID</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Fənn</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Qrup</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Tarix</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Saat</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Əməliyyatlar</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($exams)): ?>
                        <tr class="exam-row">
                            <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                <svg class="w-16 h-16 mx-auto mb-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                </svg>
                                İmtahan tapılmadı
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($exams as $exam): ?>
                            <tr class="exam-row">
                                <td class="px-6 py-4 text-sm font-mono text-gray-600">#<?php echo $exam['id_exam']; ?></td>
                                <td class="px-6 py-4 text-sm font-semibold text-gray-900"><?php echo htmlspecialchars($exam['subjectname']); ?></td>
                                <td class="px-6 py-4 text-sm text-gray-700"><?php echo htmlspecialchars($exam['group_number']); ?></td>
                                <td class="px-6 py-4 text-sm text-gray-700"><?php echo date('d.m.Y', strtotime($exam['date_exam'])); ?></td>
                                <td class="px-6 py-4 text-sm font-mono text-gray-700"><?php echo date('H:i', strtotime($exam['datetime'])); ?></td>
                                <td class="px-6 py-4">
                                    <?php
                                    $statusText = '';
                                    $statusClass = '';

                                    if ($exam['status'] == 'pending') {
                                        $statusText = 'Gözləyir';
                                        $statusClass = 'status-pending';
                                    } elseif ($exam['status'] == 'in_progress') {
                                        $statusText = 'Davam edir';
                                        $statusClass = 'status-in-progress';
                                    } elseif ($exam['status'] == 'completed') {
                                        $statusText = 'Tamamlanıb';
                                        $statusClass = 'status-completed';
                                    }
                                    ?>
                                    <span class="px-3 py-1.5 rounded-lg text-xs font-semibold <?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex space-x-2">
                                        <?php if ($can_manage_exams): ?>
                                            <?php if ($exam['status'] == 'pending'): ?>
                                                <a href="?start=<?php echo $exam['id_exam']; ?><?php echo !empty($status_filter) ? '&status=' . $status_filter : ''; ?><?php echo !empty($subject_filter) ? '&subject=' . $subject_filter : ''; ?><?php echo !empty($group_filter) ? '&group=' . $group_filter : ''; ?><?php echo $page > 1 ? '&page=' . $page : ''; ?>"
                                                   class="action-button bg-gradient-to-r from-green-600 to-emerald-600 text-white px-3 py-2 rounded-lg text-xs font-semibold hover:from-green-700 hover:to-emerald-700 transition-all flex items-center space-x-1"
                                                   onclick="return confirm('İmtahanı başlatmaq istədiyinizə əminsiniz?')">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"></path>
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                    </svg>
                                                    <span>Başlat</span>
                                                </a>
                                                <a href="?delete=<?php echo $exam['id_exam']; ?><?php echo !empty($status_filter) ? '&status=' . $status_filter : ''; ?><?php echo !empty($subject_filter) ? '&subject=' . $subject_filter : ''; ?><?php echo !empty($group_filter) ? '&group=' . $group_filter : ''; ?><?php echo $page > 1 ? '&page=' . $page : ''; ?>"
                                                   class="action-button bg-gradient-to-r from-red-600 to-pink-600 text-white px-3 py-2 rounded-lg text-xs font-semibold hover:from-red-700 hover:to-pink-700 transition-all"
                                                   onclick="return confirm('Bu imtahanı silmək istədiyinizə əminsiniz?')">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                    </svg>
                                                </a>
                                            <?php elseif ($exam['status'] == 'in_progress'): ?>
                                                <a href="?complete=<?php echo $exam['id_exam']; ?><?php echo !empty($status_filter) ? '&status=' . $status_filter : ''; ?><?php echo !empty($subject_filter) ? '&subject=' . $subject_filter : ''; ?><?php echo !empty($group_filter) ? '&group=' . $group_filter : ''; ?><?php echo $page > 1 ? '&page=' . $page : ''; ?>"
                                                   class="action-button bg-gradient-to-r from-blue-600 to-indigo-600 text-white px-3 py-2 rounded-lg text-xs font-semibold hover:from-blue-700 hover:to-indigo-700 transition-all flex items-center space-x-1"
                                                   onclick="return confirm('İmtahanı tamamlamaq istədiyinizə əminsiniz?')">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                    </svg>
                                                    <span>Tamamla</span>
                                                </a>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                        <?php if ($exam['status'] == 'completed' && ($can_view_results_for_all_exams || (is_teacher() && $exam['id_subject'] == $_SESSION['subject_id']))): ?>
                                            <a href="exam_results.php?id=<?php echo $exam['id_exam']; ?>"
                                               class="action-button bg-gradient-to-r from-cyan-600 to-blue-600 text-white px-3 py-2 rounded-lg text-xs font-semibold hover:from-cyan-700 hover:to-blue-700 transition-all flex items-center space-x-1">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                                </svg>
                                                <span>Nəticələr</span>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
    <div class="flex justify-center items-center space-x-2 mt-8">
        <?php if ($page > 1): ?>
            <a href="?page=<?php echo $page - 1; ?><?php echo !empty($status_filter) ? '&status=' . $status_filter : ''; ?><?php echo !empty($subject_filter) ? '&subject=' . $subject_filter : ''; ?><?php echo !empty($group_filter) ? '&group=' . $group_filter : ''; ?>"
               class="px-4 py-2 exam-gradient text-white rounded-lg hover:shadow-pink-500/50 transition-all font-medium">
                ← Əvvəlki
            </a>
        <?php endif; ?>

        <div class="flex space-x-1">
            <?php
            $start_page = max(1, $page - 2);
            $end_page = min($total_pages, $page + 2);

            if ($start_page > 1): ?>
                <a href="?page=1<?php echo !empty($status_filter) ? '&status=' . $status_filter : ''; ?><?php echo !empty($subject_filter) ? '&subject=' . $subject_filter : ''; ?><?php echo !empty($group_filter) ? '&group=' . $group_filter : ''; ?>" class="px-4 py-2 bg-gray-800 text-gray-300 rounded-lg hover:bg-gray-700 transition-all font-medium">1</a>
                <?php if ($start_page > 2): ?>
                    <span class="px-2 py-2 text-gray-500">...</span>
                <?php endif; ?>
            <?php endif; ?>

            <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                <a href="?page=<?php echo $i; ?><?php echo !empty($status_filter) ? '&status=' . $status_filter : ''; ?><?php echo !empty($subject_filter) ? '&subject=' . $subject_filter : ''; ?><?php echo !empty($group_filter) ? '&group=' . $group_filter : ''; ?>"
                   class="px-4 py-2 <?php echo $i == $page ? 'exam-gradient text-white' : 'bg-gray-800 text-gray-300 hover:bg-gray-700'; ?> rounded-lg transition-all font-medium">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>

            <?php if ($end_page < $total_pages): ?>
                <?php if ($end_page < $total_pages - 1): ?>
                    <span class="px-2 py-2 text-gray-500">...</span>
                <?php endif; ?>
                <a href="?page=<?php echo $total_pages; ?><?php echo !empty($status_filter) ? '&status=' . $status_filter : ''; ?><?php echo !empty($subject_filter) ? '&subject=' . $subject_filter : ''; ?><?php echo !empty($group_filter) ? '&group=' . $group_filter : ''; ?>" class="px-4 py-2 bg-gray-800 text-gray-300 rounded-lg hover:bg-gray-700 transition-all font-medium"><?php echo $total_pages; ?></a>
            <?php endif; ?>
        </div>

        <?php if ($page < $total_pages): ?>
            <a href="?page=<?php echo $page + 1; ?><?php echo !empty($status_filter) ? '&status=' . $status_filter : ''; ?><?php echo !empty($subject_filter) ? '&subject=' . $subject_filter : ''; ?><?php echo !empty($group_filter) ? '&group=' . $group_filter : ''; ?>"
               class="px-4 py-2 exam-gradient text-white rounded-lg hover:shadow-pink-500/50 transition-all font-medium">
                Növbəti →
            </a>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Add Exam Modal -->
    <?php if ($can_manage_exams): ?>
    <div x-show="showModal"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-y-auto"
         style="display: none; backdrop-filter: blur(8px); background: rgba(0, 0, 0, 0.7);">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div @click="showModal = false" class="fixed inset-0"></div>

            <div x-show="showModal"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 transform scale-95"
                 x-transition:enter-end="opacity-100 transform scale-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 transform scale-100"
                 x-transition:leave-end="opacity-0 transform scale-95"
                 class="relative bg-gradient-to-br from-gray-900 to-gray-800 rounded-2xl shadow-2xl max-w-md w-full p-8 border border-pink-500/30">

                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-2xl font-bold bg-gradient-to-r from-pink-400 to-purple-400 bg-clip-text text-transparent">
                        Yeni İmtahan Əlavə Et
                    </h3>
                    <button @click="showModal = false" class="text-gray-400 hover:text-white transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <form method="post" action="" class="space-y-5">
                    <div>
                        <label for="subject_id" class="block text-sm font-semibold text-gray-300 mb-2">Fənn</label>
                        <select id="subject_id" name="subject_id" required <?php echo is_teacher() ? 'disabled' : ''; ?>
                                class="w-full bg-gray-800/50 border border-gray-700 rounded-xl px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-transparent transition-all">
                            <option value="">Seçin</option>
                            <?php foreach ($subjects as $subject_item): ?>
                                <option value="<?php echo $subject_item['id_subject']; ?>" <?php echo (is_teacher() && $subject_item['id_subject'] == $_SESSION['subject_id']) ? 'selected' : ''; ?>>
                                    <?php echo $subject_item['subjectname']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (is_teacher()): ?>
                            <input type="hidden" name="subject_id" value="<?php echo $_SESSION['subject_id']; ?>">
                        <?php endif; ?>
                    </div>

                    <div>
                        <label for="group_id" class="block text-sm font-semibold text-gray-300 mb-2">Qrup</label>
                        <select id="group_id" name="group_id" required
                                class="w-full bg-gray-800/50 border border-gray-700 rounded-xl px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-transparent transition-all">
                            <option value="">Seçin</option>
                            <?php foreach ($groups as $group_item): ?>
                                <option value="<?php echo $group_item['id_student_group']; ?>">
                                    <?php echo $group_item['group_number']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="exam_date" class="block text-sm font-semibold text-gray-300 mb-2">Tarix</label>
                            <input type="date" id="exam_date" name="exam_date" required
                                   class="w-full bg-gray-800/50 border border-gray-700 rounded-xl px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-transparent transition-all">
                        </div>

                        <div>
                            <label for="exam_time" class="block text-sm font-semibold text-gray-300 mb-2">Saat</label>
                            <input type="time" id="exam_time" name="exam_time" required
                                   class="w-full bg-gray-800/50 border border-gray-700 rounded-xl px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-transparent transition-all">
                        </div>
                    </div>

                    <div class="flex space-x-3 pt-4">
                        <button type="button" @click="showModal = false"
                                class="flex-1 bg-gray-700 text-white px-6 py-3 rounded-xl font-semibold hover:bg-gray-600 transition-all">
                            Ləğv et
                        </button>
                        <button type="submit" name="add_exam"
                                class="flex-1 exam-gradient text-white px-6 py-3 rounded-xl font-semibold shadow-lg hover:shadow-pink-500/50 transition-all hover:scale-105">
                            Əlavə et
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
    // Auto-hide notification after 5 seconds
    setTimeout(() => {
        const notification = document.querySelector('[x-data] [x-show="showNotification"]');
        if (notification && notification.__x) {
            notification.__x.$data.showNotification = false;
        }
    }, 5000);
</script>

<?php include_once "../includes/footer.php"; ?>
