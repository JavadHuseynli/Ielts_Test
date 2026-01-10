<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
require_once "../includes/db.php";
require_once "../includes/auth.php";

checkLogin();
if (!is_admin() && !is_kafedra() && !is_teacher()) {
    header("Location: dashboard.php?error=access_denied");
    exit();
}

$teacher_subject_ids = is_teacher() && isset($_SESSION['teacher_subjects']) ? $_SESSION['teacher_subjects'] : [];

$pageTitle = "Fənn İdarəetməsi";
include_once "../includes/header.php";

$database = new Database();
$db = $database->getConnection();

$message = "";
$message_type = "";

// Check for URL success/error messages
if (isset($_GET['success']) && $_GET['success'] == 'subject_archived') {
    $message = 'Fənn və bütün sualları uğurla arxivə atıldı!';
    $message_type = 'success';
} elseif (isset($_GET['error']) && $_GET['error'] == 'archive_failed') {
    $message = 'Arxivə atarkən xəta baş verdi: ' . (isset($_GET['message']) ? urldecode($_GET['message']) : '');
    $message_type = 'error';
}

// Add subject
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_subject'])) {
    if (!is_admin()) {
        $message = 'Bu əməliyyatı etməyə icazəniz yoxdur!';
        $message_type = 'error';
    } else {
        $subjectname = trim($_POST['subjectname']);
        $timer = trim($_POST['timer']);

        if (!empty($subjectname) && is_numeric($timer)) {
            try {
                $query = "INSERT INTO subjects (subjectname, timer) VALUES (:subjectname, :timer)";
                $stmt = $db->prepare($query);
                $stmt->bindParam(":subjectname", $subjectname);
                $stmt->bindParam(":timer", $timer);

                if ($stmt->execute()) {
                    $message = 'Fənn uğurla əlavə edildi!';
                    $message_type = 'success';
                } else {
                    $message = 'Fənn əlavə edilərkən xəta baş verdi!';
                    $message_type = 'error';
                }
            } catch (PDOException $e) {
                $message = 'Verilənlər bazası xətası: ' . $e->getMessage();
                $message_type = 'error';
            }
        } else {
            $message = 'Bütün məlumatları düzgün doldurun!';
            $message_type = 'error';
        }
    }
}

// Delete subject
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    if (!is_admin()) {
        $message = 'Bu əməliyyatı etməyə icazəniz yoxdur!';
        $message_type = 'error';
    } else {
        $id = $_GET['delete'];
        try {
            $query = "SELECT COUNT(*) as exam_count FROM exams WHERE id_subject = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(":id", $id);
            $stmt->execute();
            $exam_count = $stmt->fetch(PDO::FETCH_ASSOC)['exam_count'];

            if ($exam_count > 0) {
                $message = 'Bu fənn imtahanlarda istifadə olunub, silmək mümkün deyil!';
                $message_type = 'error';
            } else {
                $query = "DELETE FROM subjects WHERE id_subject = :id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(":id", $id);
                if ($stmt->execute()) {
                    $message = 'Fənn uğurla silindi!';
                    $message_type = 'success';
                } else {
                    $message = 'Fənn silinərkən xəta baş verdi!';
                    $message_type = 'error';
                }
            }
        } catch (PDOException $e) {
            $message = 'Verilənlər bazası xətası: ' . $e->getMessage();
            $message_type = 'error';
        }
    }
}

// Pagination
$items_per_page = 9;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $items_per_page;

// Get total count
$count_query = "SELECT COUNT(DISTINCT s.id_subject) as total FROM subjects s";
if (!empty($teacher_subject_ids)) {
    $placeholders = implode(',', array_fill(0, count($teacher_subject_ids), '?'));
    $count_query .= " WHERE s.id_subject IN ($placeholders)";
}
$count_stmt = $db->prepare($count_query);
if (!empty($teacher_subject_ids)) {
    foreach ($teacher_subject_ids as $index => $subject_id) {
        $count_stmt->bindValue($index + 1, $subject_id, PDO::PARAM_INT);
    }
}
$count_stmt->execute();
$total_subjects = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_subjects / $items_per_page);

// Fetch subjects with stats
$query = "SELECT s.id_subject, s.subjectname, s.timer,
          COUNT(DISTINCT qf.id_read_quest_file) as file_count,
          COUNT(DISTINCT qr.id_question_text) as question_count,
          COUNT(DISTINCT e.id_exam) as exam_count
          FROM subjects s
          LEFT JOIN question_files qf ON s.id_subject = qf.subject_id
          LEFT JOIN question_read qr ON qf.id_read_quest_file = qr.id_read_quest_file
          LEFT JOIN exams e ON s.id_subject = e.id_subject";
if (!empty($teacher_subject_ids)) {
    $placeholders = implode(',', array_fill(0, count($teacher_subject_ids), '?'));
    $query .= " WHERE s.id_subject IN ($placeholders)";
}
$query .= " GROUP BY s.id_subject
          ORDER BY s.subjectname
          LIMIT ? OFFSET ?";
$stmt = $db->prepare($query);
$param_index = 1;
if (!empty($teacher_subject_ids)) {
    foreach ($teacher_subject_ids as $subject_id) {
        $stmt->bindValue($param_index++, $subject_id, PDO::PARAM_INT);
    }
}
$stmt->bindValue($param_index++, $items_per_page, PDO::PARAM_INT);
$stmt->bindValue($param_index++, $offset, PDO::PARAM_INT);
$stmt->execute();
$subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch overall totals for stats cards
$totals_query = "SELECT
    COUNT(DISTINCT s.id_subject) as total_subjects,
    COUNT(DISTINCT qr.id_question_text) as total_questions,
    COUNT(DISTINCT e.id_exam) as total_exams
    FROM subjects s
    LEFT JOIN question_files qf ON s.id_subject = qf.subject_id
    LEFT JOIN question_read qr ON qf.id_read_quest_file = qr.id_read_quest_file
    LEFT JOIN exams e ON s.id_subject = e.id_subject";
if (!empty($teacher_subject_ids)) {
    $placeholders = implode(',', array_fill(0, count($teacher_subject_ids), '?'));
    $totals_query .= " WHERE s.id_subject IN ($placeholders)";
}
$totals_stmt = $db->prepare($totals_query);
if (!empty($teacher_subject_ids)) {
    foreach ($teacher_subject_ids as $index => $subject_id) {
        $totals_stmt->bindValue($index + 1, $subject_id, PDO::PARAM_INT);
    }
}
$totals_stmt->execute();
$totals = $totals_stmt->fetch(PDO::FETCH_ASSOC);
?>

<div class="bg-gray-100 min-h-screen p-4 sm:p-6 lg:p-8">
    <div class="max-w-7xl mx-auto">

        <!-- Header Card -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                <div class="mb-4 md:mb-0">
                    <h1 class="text-2xl font-bold text-gray-800">Fənn İdarəetməsi</h1>
                    <p class="text-md text-gray-600 mt-1">Fənləri, sualları və imtahanları idarə edin.</p>
                </div>
                <?php if (is_admin()): ?>
                <button onclick="document.getElementById('addSubjectModal').classList.remove('hidden')" class="px-4 py-2 bg-indigo-600 text-white rounded-md font-semibold hover:bg-indigo-700 transition-all text-sm flex items-center space-x-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                    <span>Yeni Fənn Əlavə Et</span>
                </button>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!empty($message)): ?>
        <div class="mb-6">
            <?php
            $colors = [
                'success' => 'bg-green-100 border-green-400 text-green-700',
                'error' => 'bg-red-100 border-red-400 text-red-700',
            ];
            $icon = [
                'success' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>',
                'error' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>',
            ];
            ?>
            <div class="border-l-4 <?php echo $colors[$message_type]; ?> p-4 rounded-md shadow-sm" role="alert">
                <div class="flex">
                    <div class="py-1">
                        <svg class="w-6 h-6 text-<?php echo explode('-', $colors[$message_type])[1]; ?>-500 mr-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><?php echo $icon[$message_type]; ?></svg>
                    </div>
                    <div>
                        <p class="font-bold"><?php echo strip_tags($message); ?></p>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 mb-6">
            <div class="bg-white rounded-lg shadow-md p-5">
                <h3 class="text-sm font-medium text-gray-500">Ümumi Fənn</h3>
                <p class="mt-1 text-3xl font-semibold text-gray-900"><?php echo $totals['total_subjects']; ?></p>
            </div>
            <div class="bg-white rounded-lg shadow-md p-5">
                <h3 class="text-sm font-medium text-gray-500">Ümumi Sual</h3>
                <p class="mt-1 text-3xl font-semibold text-gray-900"><?php echo $totals['total_questions']; ?></p>
            </div>
            <div class="bg-white rounded-lg shadow-md p-5">
                <h3 class="text-sm font-medium text-gray-500">Ümumi İmtahan</h3>
                <p class="mt-1 text-3xl font-semibold text-gray-900"><?php echo $totals['total_exams']; ?></p>
            </div>
        </div>

        <!-- Subjects Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php if (empty($subjects)): ?>
                <div class="col-span-full bg-white rounded-lg shadow-md p-12 text-center">
                    <h3 class="text-lg font-medium text-gray-900">Fənn Tapılmadı</h3>
                    <p class="mt-1 text-sm text-gray-500">Sistemdə heç bir fənn qeydiyyatdan keçməyib.</p>
                </div>
            <?php else: ?>
                <?php foreach ($subjects as $subject): ?>
                <div class="bg-white rounded-lg shadow-md overflow-hidden flex flex-col">
                    <div class="p-5 flex-grow">
                        <div class="flex justify-between items-start">
                            <h3 class="text-lg font-bold text-gray-800 mb-2"><?php echo htmlspecialchars($subject['subjectname']); ?></h3>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800">
                                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                <?php echo $subject['timer']; ?> dəq
                            </span>
                        </div>
                        <p class="text-sm text-gray-500 mb-4">ID: #<?php echo $subject['id_subject']; ?></p>
                        
                        <div class="grid grid-cols-3 gap-4 text-center">
                            <div>
                                <p class="text-2xl font-semibold text-gray-800"><?php echo $subject['file_count']; ?></p>
                                <p class="text-xs text-gray-500">Fayl</p>
                            </div>
                            <div>
                                <p class="text-2xl font-semibold text-gray-800"><?php echo $subject['question_count']; ?></p>
                                <p class="text-xs text-gray-500">Sual</p>
                            </div>
                            <div>
                                <p class="text-2xl font-semibold text-gray-800"><?php echo $subject['exam_count']; ?></p>
                                <p class="text-xs text-gray-500">İmtahan</p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 p-4 flex items-center justify-between">
                        <a href="questions.php?subject=<?php echo $subject['id_subject']; ?>" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">Suallara Bax</a>
                        <div class="flex items-center gap-3">
                            <?php if (is_admin() && $subject['exam_count'] == 0): ?>
                                <a href="?delete=<?php echo $subject['id_subject']; ?>" class="text-sm font-medium text-red-600 hover:text-red-800" onclick="return confirm('Bu fənni silmək istədiyinizə əminsiniz?')">Sil</a>
                            <?php elseif (is_admin()): ?>
                                <form method="POST" action="archive_subject.php" style="display: inline;">
                                    <input type="hidden" name="subject_id" value="<?php echo $subject['id_subject']; ?>">
                                    <button type="submit" class="text-sm font-medium text-amber-600 hover:text-amber-800" onclick="return confirm('Bu fənni və bütün suallarını arxivə atmaq istədiyinizə əminsiniz?')">
                                        Arxivlə
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <div class="mt-6 py-4 flex items-center justify-center">
            <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="?page=<?php echo $i; ?>" class="<?php echo $i == $page ? 'z-10 bg-indigo-50 border-indigo-500 text-indigo-600' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'; ?> relative inline-flex items-center px-4 py-2 border text-sm font-medium">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
            </nav>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Add Subject Modal -->
<div id="addSubjectModal" class="hidden fixed inset-0 bg-gray-500 bg-opacity-75 z-50 flex items-center justify-center p-4" onclick="if(event.target === this) this.classList.add('hidden')">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-md mx-auto" onclick="event.stopPropagation()">
        <form method="post" action="">
            <div class="p-6">
                <h3 class="text-lg font-medium text-gray-900">Yeni Fənn Yarat</h3>
                <div class="mt-4 space-y-4">
                    <div>
                        <label for="subjectname" class="block text-sm font-medium text-gray-700">Fənn adı</label>
                        <input type="text" name="subjectname" id="subjectname" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" required>
                    </div>
                    <div>
                        <label for="timer" class="block text-sm font-medium text-gray-700">İmtahan vaxtı (dəqiqə)</label>
                        <input type="number" name="timer" id="timer" value="60" min="1" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" required>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button type="submit" name="add_subject" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm">
                    Əlavə Et
                </button>
                <button type="button" onclick="document.getElementById('addSubjectModal').classList.add('hidden')" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:mt-0 sm:w-auto sm:text-sm">
                    Ləğv Et
                </button>
            </div>
        </form>
    </div>
</div>

<?php include_once "../includes/footer.php"; ?>