<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
require_once "../includes/db.php";
require_once "../includes/auth.php";

checkLogin();
if (!is_admin() && !is_prorektor() && !is_dekan() && !is_kafedra() && !is_teacher()) {
    header("Location: ../login.php");
    exit();
}

$pageTitle = "Dashboard";
include_once "../includes/header.php";

$database = new Database();
$db = $database->getConnection();

$user_role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];
$teacher_subject_ids = isset($_SESSION['teacher_subjects']) ? $_SESSION['teacher_subjects'] : [];
$user_group_id = isset($_SESSION['group_id']) ? $_SESSION['group_id'] : null;

// Initialize counts
$studentCount = 0;
$groupCount = 0;
$subjectCount = 0;
$examCount = 0;
$recentExams = [];

// Base queries
$base_student_query = "SELECT COUNT(*) as total FROM users WHERE status = 'student'";
$base_group_query = "SELECT COUNT(*) as total FROM student_group";
$base_subject_query = "SELECT COUNT(*) as total FROM subjects";
$base_exam_query = "SELECT COUNT(*) as total FROM exams";

if (is_admin() || is_prorektor() || is_dekan() || is_kafedra()) {
    $stmt = $db->prepare($base_student_query); $stmt->execute(); $studentCount = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $stmt = $db->prepare($base_group_query); $stmt->execute(); $groupCount = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $stmt = $db->prepare($base_subject_query); $stmt->execute(); $subjectCount = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $stmt = $db->prepare($base_exam_query); $stmt->execute(); $examCount = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Get exam stats by status
    $examStatsQuery = "SELECT status, COUNT(*) as count FROM exams GROUP BY status";
    $stmt = $db->prepare($examStatsQuery);
    $stmt->execute();
    $examStats = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get recent exams
    $query = "SELECT e.id_exam, e.date_exam, e.datetime, e.status, s.subjectname, sg.group_number,
              CASE
                  WHEN e.status = 'pending' THEN 'Gözləyir'
                  WHEN e.status = 'in_progress' THEN 'Davam edir'
                  WHEN e.status = 'completed' THEN 'Tamamlanıb'
              END as status_name
              FROM exams e
              JOIN subjects s ON e.id_subject = s.id_subject
              JOIN student_group sg ON e.id_student_group = sg.id_student_group
              ORDER BY e.datetime DESC LIMIT 5";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $recentExams = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get student distribution by group
    $groupDistQuery = "SELECT sg.group_number, COUNT(u.id_users) as student_count
                      FROM student_group sg
                      LEFT JOIN users u ON sg.id_student_group = u.group_id
                      GROUP BY sg.id_student_group, sg.group_number
                      ORDER BY sg.group_number
                      LIMIT 5";
    $stmt = $db->prepare($groupDistQuery);
    $stmt->execute();
    $groupDist = $stmt->fetchAll(PDO::FETCH_ASSOC);

} elseif (is_teacher()) {
    if (!empty($teacher_subject_ids)) {
        $placeholders = implode(',', array_fill(0, count($teacher_subject_ids), '?'));

        $stmt = $db->prepare($base_subject_query . " WHERE id_subject IN ($placeholders)");
        foreach ($teacher_subject_ids as $index => $subject_id) {
            $stmt->bindValue($index + 1, $subject_id, PDO::PARAM_INT);
        }
        $stmt->execute();
        $subjectCount = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

        $stmt = $db->prepare($base_exam_query . " WHERE id_subject IN ($placeholders)");
        foreach ($teacher_subject_ids as $index => $subject_id) {
            $stmt->bindValue($index + 1, $subject_id, PDO::PARAM_INT);
        }
        $stmt->execute();
        $examCount = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

        $query = "SELECT e.id_exam, e.date_exam, e.datetime, e.status, s.subjectname, sg.group_number,
                  CASE
                      WHEN e.status = 'pending' THEN 'Gözləyir'
                      WHEN e.status = 'in_progress' THEN 'Davam edir'
                      WHEN e.status = 'completed' THEN 'Tamamlanıb'
                  END as status_name
                  FROM exams e
                  JOIN subjects s ON e.id_subject = s.id_subject
                  JOIN student_group sg ON e.id_student_group = sg.id_student_group
                  WHERE e.id_subject IN ($placeholders)
                  ORDER BY e.datetime DESC LIMIT 5";
        $stmt = $db->prepare($query);
        foreach ($teacher_subject_ids as $index => $subject_id) {
            $stmt->bindValue($index + 1, $subject_id, PDO::PARAM_INT);
        }
        $stmt->execute();
        $recentExams = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get question files for teacher's subjects
        $questionFilesQuery = "SELECT qf.id_read_quest_file, qf.file_title, qf.file_type,
                               COUNT(DISTINCT qr.id_question_text) as question_count
                               FROM question_files qf
                               LEFT JOIN question_read qr ON qf.id_read_quest_file = qr.id_read_quest_file
                               WHERE qf.subject_id IN ($placeholders)
                               GROUP BY qf.id_read_quest_file
                               ORDER BY qf.created_at DESC
                               LIMIT 10";
        $stmt = $db->prepare($questionFilesQuery);
        foreach ($teacher_subject_ids as $index => $subject_id) {
            $stmt->bindValue($index + 1, $subject_id, PDO::PARAM_INT);
        }
        $stmt->execute();
        $questionFiles = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get exam results summary for teacher's subjects
        $resultsQuery = "SELECT e.id_exam, e.date_exam, s.subjectname, sg.group_number,
                         COUNT(DISTINCT a.user_id) as total_students,
                         AVG(a.is_correct) * 100 as avg_percentage
                         FROM exams e
                         JOIN subjects s ON e.id_subject = s.id_subject
                         JOIN student_group sg ON e.id_student_group = sg.id_student_group
                         LEFT JOIN answers a ON e.id_exam = a.exam_id
                         WHERE e.id_subject IN ($placeholders) AND e.status = 'completed'
                         GROUP BY e.id_exam
                         ORDER BY e.date_exam DESC
                         LIMIT 5";
        $stmt = $db->prepare($resultsQuery);
        foreach ($teacher_subject_ids as $index => $subject_id) {
            $stmt->bindValue($index + 1, $subject_id, PDO::PARAM_INT);
        }
        $stmt->execute();
        $examResults = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get teacher's subjects with details
        $teacherSubjectsQuery = "SELECT s.id_subject, s.subjectname, s.timer,
                                 COUNT(DISTINCT qf.id_read_quest_file) as file_count,
                                 COUNT(DISTINCT qr.id_question_text) as question_count,
                                 SUM(CASE WHEN qr.approved = 0 THEN 1 ELSE 0 END) as pending_questions,
                                 SUM(CASE WHEN qr.approved = 1 THEN 1 ELSE 0 END) as approved_questions
                                 FROM subjects s
                                 LEFT JOIN question_files qf ON s.id_subject = qf.subject_id
                                 LEFT JOIN question_read qr ON qf.id_read_quest_file = qr.id_read_quest_file
                                 WHERE s.id_subject IN ($placeholders)
                                 GROUP BY s.id_subject
                                 ORDER BY s.subjectname";
        $stmt = $db->prepare($teacherSubjectsQuery);
        foreach ($teacher_subject_ids as $index => $subject_id) {
            $stmt->bindValue($index + 1, $subject_id, PDO::PARAM_INT);
        }
        $stmt->execute();
        $teacherSubjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

// Get Top Performers (Leaderboard)
$topPerformersQuery = "SELECT u.id_users, u.f_name, u.username, sg.group_number,
    COUNT(DISTINCT a.exam_id) as total_exams,
    SUM(qr.question_score * a.is_correct) as total_score,
    COUNT(a.id_answer) as total_questions,
    SUM(a.is_correct) as correct_answers,
    (SUM(a.is_correct) / COUNT(a.id_answer)) * 100 as success_rate
    FROM users u
    JOIN answers a ON u.id_users = a.user_id
    JOIN question_read qr ON a.id_questions = qr.id_question_text
    LEFT JOIN student_group sg ON u.group_id = sg.id_student_group
    WHERE u.status = 'student'";

if (is_teacher() && !empty($teacher_subject_ids)) {
    $placeholders = implode(',', array_fill(0, count($teacher_subject_ids), '?'));
    $topPerformersQuery .= " AND EXISTS (
        SELECT 1 FROM exams e
        WHERE e.id_exam = a.exam_id AND e.id_subject IN ($placeholders)
    )";
}

$topPerformersQuery .= " GROUP BY u.id_users
    HAVING total_exams > 0
    ORDER BY success_rate DESC, total_score DESC
    LIMIT 10";

$stmt = $db->prepare($topPerformersQuery);
if (is_teacher() && !empty($teacher_subject_ids)) {
    foreach ($teacher_subject_ids as $index => $subject_id) {
        $stmt->bindValue($index + 1, $subject_id, PDO::PARAM_INT);
    }
}
$stmt->execute();
$topPerformers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get Group Averages
$groupAveragesQuery = "SELECT sg.group_number, sg.id_student_group,
    COUNT(DISTINCT u.id_users) as student_count,
    COUNT(DISTINCT a.exam_id) as total_exams,
    COUNT(a.id_answer) as total_questions,
    SUM(a.is_correct) as total_correct,
    (SUM(a.is_correct) / COUNT(a.id_answer)) * 100 as avg_success_rate,
    SUM(qr.question_score * a.is_correct) / COUNT(DISTINCT u.id_users) as avg_score_per_student,
    SUM(qr.question_score * a.is_correct) as total_group_score
    FROM student_group sg
    LEFT JOIN users u ON sg.id_student_group = u.group_id AND u.status = 'student'
    LEFT JOIN answers a ON u.id_users = a.user_id
    LEFT JOIN question_read qr ON a.id_questions = qr.id_question_text";

if (is_teacher() && !empty($teacher_subject_ids)) {
    $placeholders = implode(',', array_fill(0, count($teacher_subject_ids), '?'));
    $groupAveragesQuery .= " LEFT JOIN exams e ON a.exam_id = e.id_exam
    WHERE e.id_subject IN ($placeholders)";
}

$groupAveragesQuery .= " GROUP BY sg.id_student_group
    HAVING student_count > 0 AND total_questions > 0
    ORDER BY avg_success_rate DESC";

$stmt = $db->prepare($groupAveragesQuery);
if (is_teacher() && !empty($teacher_subject_ids)) {
    foreach ($teacher_subject_ids as $index => $subject_id) {
        $stmt->bindValue($index + 1, $subject_id, PDO::PARAM_INT);
    }
}
$stmt->execute();
$groupAverages = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Prepare data for charts
$pendingExams = 0;
$inProgressExams = 0;
$completedExams = 0;
if(isset($examStats)) {
    foreach($examStats as $stat) {
        if($stat['status'] == 'pending') $pendingExams = $stat['count'];
        if($stat['status'] == 'in_progress') $inProgressExams = $stat['count'];
        if($stat['status'] == 'completed') $completedExams = $stat['count'];
    }
}
?>

<!-- Welcome Banner -->
<div class="mb-6 sm:mb-8 glass rounded-2xl sm:rounded-3xl p-4 sm:p-6 md:p-8 relative overflow-hidden animate-fade-in">
    <div class="absolute top-0 right-0 w-64 h-64 bg-gradient-to-br from-primary-500/20 to-cyan-500/20 rounded-full blur-3xl"></div>
    <div class="absolute bottom-0 left-0 w-48 h-48 bg-gradient-to-tr from-purple-500/20 to-pink-500/20 rounded-full blur-3xl"></div>

    <div class="relative z-10">
        <h1 class="text-2xl sm:text-3xl md:text-4xl font-bold text-gray-900 mb-2">
            Xoş gəldiniz, <?php echo $_SESSION['name']; ?>! 👋
        </h1>
        <p class="text-gray-600 text-sm sm:text-base md:text-lg">
            Bu gün <?php echo date('d F Y'); ?> tarixidir. Sistemə xoş gəlmisiniz!
        </p>
    </div>
</div>

<!-- Stats Cards with 3D Effect -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6 mb-8">
    <?php if (is_admin() || is_prorektor() || is_dekan() || is_kafedra()): ?>
    <!-- Students Card -->
    <div class="card-hover glass rounded-2xl sm:rounded-3xl p-4 sm:p-6 relative overflow-hidden group animate-scale-in" style="animation-delay: 0.1s">
        <div class="absolute inset-0 bg-gradient-to-br from-blue-500/10 to-cyan-500/10 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
        <div class="absolute top-0 right-0 w-24 sm:w-32 h-24 sm:h-32 bg-gradient-to-br from-blue-500/20 to-cyan-500/20 rounded-full blur-2xl group-hover:scale-150 transition-transform duration-500"></div>

        <div class="relative z-10">
            <div class="flex items-center justify-between mb-3 sm:mb-4">
                <div class="p-2 sm:p-3 bg-gradient-to-br from-blue-500 to-cyan-500 rounded-xl sm:rounded-2xl shadow-lg group-hover:scale-110 transition-transform duration-300 group-hover:rotate-6">
                    <svg class="w-6 h-6 sm:w-8 sm:h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                    </svg>
                </div>
                <span class="text-xs sm:text-sm font-semibold text-blue-600 px-2 sm:px-3 py-1 bg-blue-100 rounded-full">+12%</span>
            </div>

            <h3 class="text-gray-600 text-xs sm:text-sm font-medium mb-1">Tələbələr</h3>
            <p class="text-2xl sm:text-3xl md:text-4xl font-bold text-gray-900 mb-2 sm:mb-4"><?php echo $studentCount; ?></p>

            <?php if (is_admin()): ?>
            <a href="users.php" class="inline-flex items-center text-xs sm:text-sm font-medium text-blue-600 hover:text-blue-700 group-hover:translate-x-1 transition-transform">
                İdarə et
                <svg class="w-3 h-3 sm:w-4 sm:h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>
            </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Groups Card -->
    <div class="card-hover glass rounded-2xl sm:rounded-3xl p-4 sm:p-6 relative overflow-hidden group animate-scale-in" style="animation-delay: 0.2s">
        <div class="absolute inset-0 bg-gradient-to-br from-green-500/10 to-emerald-500/10 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
        <div class="absolute top-0 right-0 w-24 sm:w-32 h-24 sm:h-32 bg-gradient-to-br from-green-500/20 to-emerald-500/20 rounded-full blur-2xl group-hover:scale-150 transition-transform duration-500"></div>

        <div class="relative z-10">
            <div class="flex items-center justify-between mb-3 sm:mb-4">
                <div class="p-2 sm:p-3 bg-gradient-to-br from-green-500 to-emerald-500 rounded-xl sm:rounded-2xl shadow-lg group-hover:scale-110 transition-transform duration-300 group-hover:rotate-6">
                    <svg class="w-6 h-6 sm:w-8 sm:h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                </div>
                <span class="text-xs sm:text-sm font-semibold text-green-600 px-2 sm:px-3 py-1 bg-green-100 rounded-full">+5%</span>
            </div>

            <h3 class="text-gray-600 text-xs sm:text-sm font-medium mb-1">Qruplar</h3>
            <p class="text-2xl sm:text-3xl md:text-4xl font-bold text-gray-900 mb-2 sm:mb-4"><?php echo $groupCount; ?></p>

            <?php if (is_admin()): ?>
            <a href="groups.php" class="inline-flex items-center text-xs sm:text-sm font-medium text-green-600 hover:text-green-700 group-hover:translate-x-1 transition-transform">
                İdarə et
                <svg class="w-3 h-3 sm:w-4 sm:h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>
            </a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if (is_admin() || is_kafedra() || is_teacher()): ?>
    <!-- Subjects Card -->
    <div class="card-hover glass rounded-2xl sm:rounded-3xl p-4 sm:p-6 relative overflow-hidden group animate-scale-in" style="animation-delay: 0.3s">
        <div class="absolute inset-0 bg-gradient-to-br from-purple-500/10 to-pink-500/10 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
        <div class="absolute top-0 right-0 w-24 sm:w-32 h-24 sm:h-32 bg-gradient-to-br from-purple-500/20 to-pink-500/20 rounded-full blur-2xl group-hover:scale-150 transition-transform duration-500"></div>

        <div class="relative z-10">
            <div class="flex items-center justify-between mb-3 sm:mb-4">
                <div class="p-2 sm:p-3 bg-gradient-to-br from-purple-500 to-pink-500 rounded-xl sm:rounded-2xl shadow-lg group-hover:scale-110 transition-transform duration-300 group-hover:rotate-6">
                    <svg class="w-6 h-6 sm:w-8 sm:h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                    </svg>
                </div>
                <span class="text-xs sm:text-sm font-semibold text-purple-600 px-2 sm:px-3 py-1 bg-purple-100 rounded-full">Aktiv</span>
            </div>

            <h3 class="text-gray-600 text-xs sm:text-sm font-medium mb-1">Fənlər</h3>
            <p class="text-2xl sm:text-3xl md:text-4xl font-bold text-gray-900 mb-2 sm:mb-4"><?php echo $subjectCount; ?></p>

            <?php if (is_admin() || is_kafedra()): ?>
            <a href="subjects.php" class="inline-flex items-center text-xs sm:text-sm font-medium text-purple-600 hover:text-purple-700 group-hover:translate-x-1 transition-transform">
                İdarə et
                <svg class="w-3 h-3 sm:w-4 sm:h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>
            </a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if (is_admin() || is_prorektor() || is_kafedra() || is_teacher()): ?>
    <!-- Exams Card -->
    <div class="card-hover glass rounded-2xl sm:rounded-3xl p-4 sm:p-6 relative overflow-hidden group animate-scale-in" style="animation-delay: 0.4s">
        <div class="absolute inset-0 bg-gradient-to-br from-amber-500/10 to-orange-500/10 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
        <div class="absolute top-0 right-0 w-24 sm:w-32 h-24 sm:h-32 bg-gradient-to-br from-amber-500/20 to-orange-500/20 rounded-full blur-2xl group-hover:scale-150 transition-transform duration-500"></div>

        <div class="relative z-10">
            <div class="flex items-center justify-between mb-3 sm:mb-4">
                <div class="p-2 sm:p-3 bg-gradient-to-br from-amber-500 to-orange-500 rounded-xl sm:rounded-2xl shadow-lg group-hover:scale-110 transition-transform duration-300 group-hover:rotate-6">
                    <svg class="w-6 h-6 sm:w-8 sm:h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                    </svg>
                </div>
                <span class="text-xs sm:text-sm font-semibold text-amber-600 px-2 sm:px-3 py-1 bg-amber-100 rounded-full">Live</span>
            </div>

            <h3 class="text-gray-600 text-xs sm:text-sm font-medium mb-1">İmtahanlar</h3>
            <p class="text-2xl sm:text-3xl md:text-4xl font-bold text-gray-900 mb-2 sm:mb-4"><?php echo $examCount; ?></p>

            <?php if (is_admin() || is_prorektor()): ?>
            <a href="exams.php" class="inline-flex items-center text-xs sm:text-sm font-medium text-amber-600 hover:text-amber-700 group-hover:translate-x-1 transition-transform">
                İdarə et
                <svg class="w-3 h-3 sm:w-4 sm:h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>
            </a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Teacher Subjects Section -->
<?php if (is_teacher() && isset($teacherSubjects) && !empty($teacherSubjects)): ?>
<div class="glass rounded-2xl sm:rounded-3xl overflow-hidden card-hover animate-fade-in mb-6 sm:mb-8" style="animation-delay: 0.5s">
    <div class="px-4 sm:px-6 py-4 sm:py-5 border-b border-white/20 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 bg-gradient-to-r from-purple-500/10 via-pink-500/10 to-transparent">
        <h2 class="text-lg sm:text-xl font-bold text-gray-900 flex items-center">
            <span class="w-3 h-3 bg-purple-500 rounded-full mr-2 sm:mr-3 animate-pulse"></span>
            Mənim Fənnlərim
        </h2>
        <a href="subjects.php" class="inline-flex items-center px-4 sm:px-5 py-2 sm:py-2.5 text-xs sm:text-sm font-semibold text-white bg-gradient-to-r from-purple-600 to-pink-600 rounded-xl hover:shadow-xl hover:-translate-y-0.5 transition-all">
            Hamısı
            <svg class="w-3 h-3 sm:w-4 sm:h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
            </svg>
        </a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6 p-4 sm:p-6">
        <?php foreach ($teacherSubjects as $subject): ?>
        <div class="bg-white/80 backdrop-blur-sm rounded-xl shadow-md hover:shadow-xl transition-all duration-300 overflow-hidden border border-gray-200 hover:border-purple-300 group">
            <div class="p-5">
                <div class="flex justify-between items-start mb-3">
                    <h3 class="text-lg font-bold text-gray-900 group-hover:text-purple-600 transition-colors">
                        <?php echo htmlspecialchars($subject['subjectname']); ?>
                    </h3>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800">
                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <?php echo $subject['timer']; ?> dəq
                    </span>
                </div>

                <div class="grid grid-cols-2 gap-3 mb-4">
                    <div class="bg-gray-50 rounded-lg p-3 text-center">
                        <p class="text-2xl font-bold text-gray-900"><?php echo $subject['file_count'] ?? 0; ?></p>
                        <p class="text-xs text-gray-600">Fayl</p>
                    </div>
                    <div class="bg-gray-50 rounded-lg p-3 text-center">
                        <p class="text-2xl font-bold text-gray-900"><?php echo $subject['question_count'] ?? 0; ?></p>
                        <p class="text-xs text-gray-600">Sual</p>
                    </div>
                </div>

                <?php if ($subject['pending_questions'] > 0): ?>
                <div class="mb-4 px-3 py-2 bg-yellow-50 border-l-4 border-yellow-400 rounded">
                    <p class="text-xs font-semibold text-yellow-800 flex items-center">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                        <?php echo $subject['pending_questions']; ?> sual təsdiq gözləyir
                    </p>
                </div>
                <?php endif; ?>

                <a href="questions.php?subject=<?php echo $subject['id_subject']; ?>"
                   class="w-full inline-flex items-center justify-center px-4 py-2.5 bg-gradient-to-r from-purple-600 to-pink-600 text-white text-sm font-semibold rounded-lg hover:from-purple-700 hover:to-pink-700 transition-all shadow-md hover:shadow-lg group-hover:scale-105">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    Suallara Bax
                </a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- Charts and Tables Section -->
<div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6 mb-6 sm:mb-8">
    <!-- Exam Status Chart -->
    <?php if (is_admin() || is_prorektor() || is_dekan() || is_kafedra()): ?>
    <div class="glass rounded-2xl sm:rounded-3xl p-4 sm:p-6 card-hover animate-fade-in" style="animation-delay: 0.5s">
        <h3 class="text-base sm:text-lg font-bold text-gray-900 mb-3 sm:mb-4 flex items-center">
            <span class="w-2 h-2 bg-primary-500 rounded-full mr-2 animate-pulse"></span>
            İmtahan Statusu
        </h3>
        <canvas id="examStatusChart" class="max-h-48 sm:max-h-64"></canvas>
    </div>

    <!-- Student Distribution Chart -->
    <div class="glass rounded-2xl sm:rounded-3xl p-4 sm:p-6 card-hover animate-fade-in" style="animation-delay: 0.6s">
        <h3 class="text-base sm:text-lg font-bold text-gray-900 mb-3 sm:mb-4 flex items-center">
            <span class="w-2 h-2 bg-green-500 rounded-full mr-2 animate-pulse"></span>
            Qrup Paylanması
        </h3>
        <canvas id="groupDistChart" class="max-h-48 sm:max-h-64"></canvas>
    </div>
    <?php endif; ?>
</div>

<!-- Recent Exams Table -->
<div class="glass rounded-2xl sm:rounded-3xl overflow-hidden card-hover animate-fade-in mb-6 sm:mb-8" style="animation-delay: 0.7s">
    <div class="px-4 sm:px-6 py-4 sm:py-5 border-b border-white/20 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 bg-gradient-to-r from-white/50 to-transparent">
        <h2 class="text-lg sm:text-xl font-bold text-gray-900 flex items-center">
            <span class="w-3 h-3 bg-primary-500 rounded-full mr-2 sm:mr-3 animate-pulse"></span>
            Son İmtahanlar
        </h2>
        <?php if (is_admin() || is_prorektor() || is_dekan() || is_kafedra()): ?>
        <a href="exams.php" class="inline-flex items-center px-4 sm:px-5 py-2 sm:py-2.5 text-xs sm:text-sm font-semibold text-white bg-gradient-to-r from-primary-600 to-cyan-600 rounded-xl hover:shadow-xl hover:-translate-y-0.5 transition-all">
            Hamısını gör
            <svg class="w-3 h-3 sm:w-4 sm:h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
            </svg>
        </a>
        <?php endif; ?>
    </div>

    <div class="overflow-x-auto -mx-4 sm:mx-0">
        <table class="min-w-full divide-y divide-gray-200/50">
            <thead>
                <tr class="bg-gradient-to-r from-gray-50/50 to-transparent">
                    <th class="px-3 sm:px-6 py-3 sm:py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">ID</th>
                    <th class="px-3 sm:px-6 py-3 sm:py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Fənn</th>
                    <th class="px-3 sm:px-6 py-3 sm:py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider hidden sm:table-cell">Qrup</th>
                    <th class="px-3 sm:px-6 py-3 sm:py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider hidden md:table-cell">Tarix</th>
                    <th class="px-3 sm:px-6 py-3 sm:py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider hidden lg:table-cell">Saat</th>
                    <th class="px-3 sm:px-6 py-3 sm:py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200/30">
                <?php if (empty($recentExams)): ?>
                    <tr>
                        <td colspan="6" class="px-3 sm:px-6 py-12 sm:py-16 text-center">
                            <div class="flex flex-col items-center">
                                <svg class="w-12 h-12 sm:w-16 sm:h-16 text-gray-300 mb-3 sm:mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                </svg>
                                <p class="text-xs sm:text-sm font-medium text-gray-500">İmtahan tapılmadı</p>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($recentExams as $exam): ?>
                        <tr class="hover:bg-white/30 transition-colors">
                            <td class="px-3 sm:px-6 py-3 sm:py-4 whitespace-nowrap">
                                <span class="text-xs sm:text-sm font-bold text-gray-900">#<?php echo $exam['id_exam']; ?></span>
                            </td>
                            <td class="px-3 sm:px-6 py-3 sm:py-4">
                                <div class="flex items-center">
                                    <div class="w-8 h-8 sm:w-10 sm:h-10 bg-gradient-to-br from-primary-500 to-cyan-500 rounded-lg flex items-center justify-center mr-2 sm:mr-3 flex-shrink-0">
                                        <svg class="w-4 h-4 sm:w-5 sm:h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                                        </svg>
                                    </div>
                                    <span class="text-xs sm:text-sm font-medium text-gray-900 truncate"><?php echo $exam['subjectname']; ?></span>
                                </div>
                            </td>
                            <td class="px-3 sm:px-6 py-3 sm:py-4 whitespace-nowrap text-xs sm:text-sm text-gray-600 font-medium hidden sm:table-cell"><?php echo $exam['group_number']; ?></td>
                            <td class="px-3 sm:px-6 py-3 sm:py-4 whitespace-nowrap text-xs sm:text-sm text-gray-600 hidden md:table-cell"><?php echo date('d.m.Y', strtotime($exam['date_exam'])); ?></td>
                            <td class="px-3 sm:px-6 py-3 sm:py-4 whitespace-nowrap text-xs sm:text-sm text-gray-600 hidden lg:table-cell"><?php echo date('H:i', strtotime($exam['datetime'])); ?></td>
                            <td class="px-3 sm:px-6 py-3 sm:py-4 whitespace-nowrap">
                                <?php
                                $statusColors = [
                                    'Gözləyir' => 'from-yellow-500 to-amber-500',
                                    'Davam edir' => 'from-green-500 to-emerald-500',
                                    'Tamamlanıb' => 'from-blue-500 to-cyan-500',
                                ];
                                $statusColor = $statusColors[$exam['status_name']] ?? 'from-gray-500 to-gray-600';
                                ?>
                                <span class="inline-flex items-center px-2 sm:px-3 py-1 sm:py-1.5 rounded-full text-xs font-bold text-white bg-gradient-to-r <?php echo $statusColor; ?> shadow-lg">
                                    <span class="w-1.5 h-1.5 bg-white rounded-full mr-1 sm:mr-2 animate-pulse"></span>
                                    <span class="hidden sm:inline"><?php echo $exam['status_name']; ?></span>
                                    <span class="sm:hidden"><?php echo substr($exam['status_name'], 0, 3); ?></span>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Group Averages Section -->
<?php if (!empty($groupAverages)): ?>
<div class="mb-6 sm:mb-8 animate-fade-in" style="animation-delay: 0.7s">
    <!-- Group Averages Table -->
    <div class="glass rounded-2xl sm:rounded-3xl overflow-hidden card-hover mb-6">
        <div class="px-4 sm:px-6 py-4 sm:py-5 border-b border-white/20 bg-gradient-to-r from-indigo-500/20 to-purple-500/20">
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                <h2 class="text-lg sm:text-xl font-bold text-gray-900 flex items-center">
                    <svg class="w-5 h-5 sm:w-6 sm:h-6 text-indigo-600 mr-2 sm:mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                    Qruplar üzrə Ortalamalar
                </h2>
                <a href="group_averages_pdf.php" target="_blank" class="inline-flex items-center px-4 sm:px-5 py-2 sm:py-2.5 text-xs sm:text-sm font-semibold text-white bg-gradient-to-r from-red-600 to-pink-600 rounded-xl hover:shadow-xl hover:-translate-y-0.5 transition-all">
                    <svg class="w-3 h-3 sm:w-4 sm:h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                    </svg>
                    PDF Yüklə
                </a>
            </div>
        </div>

        <div class="overflow-x-auto -mx-4 sm:mx-0">
            <table class="min-w-full divide-y divide-gray-200/50">
                <thead>
                    <tr class="bg-gradient-to-r from-gray-50/50 to-transparent">
                        <th class="px-3 sm:px-6 py-3 sm:py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Qrup</th>
                        <th class="px-3 sm:px-6 py-3 sm:py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider hidden sm:table-cell">Tələbə</th>
                        <th class="px-3 sm:px-6 py-3 sm:py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Uğur</th>
                        <th class="px-3 sm:px-6 py-3 sm:py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Bal</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200/30">
                    <?php foreach ($groupAverages as $index => $group):
                        $avgSuccess = floatval($group['avg_success_rate'] ?? 0);
                        $avgScore = floatval($group['avg_score_per_student'] ?? 0);

                        // Performance color
                        if ($avgSuccess >= 90) {
                            $performanceColor = 'from-green-500 to-emerald-600';
                            $performanceLabel = 'Əla';
                        } elseif ($avgSuccess >= 80) {
                            $performanceColor = 'from-blue-500 to-cyan-600';
                            $performanceLabel = 'Yaxşı';
                        } elseif ($avgSuccess >= 70) {
                            $performanceColor = 'from-yellow-500 to-amber-600';
                            $performanceLabel = 'Orta';
                        } else {
                            $performanceColor = 'from-orange-500 to-red-600';
                            $performanceLabel = 'Zəif';
                        }
                    ?>
                    <tr class="hover:bg-white/30 transition-colors">
                        <td class="px-3 sm:px-6 py-3 sm:py-4">
                            <div class="flex items-center">
                                <div class="w-8 h-8 sm:w-10 sm:h-10 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-lg sm:rounded-xl flex items-center justify-center mr-2 sm:mr-3 shadow-lg flex-shrink-0">
                                    <svg class="w-4 h-4 sm:w-5 sm:h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                    </svg>
                                </div>
                                <div>
                                    <div class="text-xs sm:text-sm font-bold text-gray-900 truncate"><?php echo htmlspecialchars($group['group_number']); ?></div>
                                    <div class="text-xs text-gray-500"><?php echo $group['total_exams']; ?> İmt</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-3 sm:px-6 py-3 sm:py-4 whitespace-nowrap hidden sm:table-cell">
                            <span class="inline-flex items-center px-2 sm:px-3 py-1 rounded-full text-xs font-bold text-gray-900 bg-gray-100">
                                <?php echo $group['student_count']; ?> nəfər
                            </span>
                        </td>
                        <td class="px-3 sm:px-6 py-3 sm:py-4">
                            <div class="flex items-center space-x-1 sm:space-x-2">
                                <div class="hidden sm:block flex-1">
                                    <div class="w-full bg-gray-200 rounded-full h-2">
                                        <div class="bg-gradient-to-r <?php echo $performanceColor; ?> h-2 rounded-full transition-all duration-500" style="width: <?php echo min($avgSuccess, 100); ?>%"></div>
                                    </div>
                                </div>
                                <span class="text-xs sm:text-sm font-bold text-gray-900"><?php echo number_format($avgSuccess, 1); ?>%</span>
                            </div>
                        </td>
                        <td class="px-3 sm:px-6 py-3 sm:py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-2 sm:px-3 py-1 sm:py-1.5 rounded-full text-xs font-bold text-white bg-gradient-to-r <?php echo $performanceColor; ?> shadow-lg">
                                <?php echo number_format($avgScore, 1); ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Group Averages Chart -->
    <div class="glass rounded-2xl sm:rounded-3xl p-4 sm:p-6 card-hover">
        <h3 class="text-base sm:text-lg font-bold text-gray-900 mb-4 sm:mb-6 flex items-center">
            <span class="w-2 h-2 bg-indigo-500 rounded-full mr-2 animate-pulse"></span>
            Qrup Performansı Qrafiki
        </h3>
        <div class="relative h-64 sm:h-80 md:h-96">
            <canvas id="groupAveragesChart"></canvas>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Top Performers Leaderboard -->
<?php if (!empty($topPerformers)): ?>
<div class="glass rounded-2xl sm:rounded-3xl overflow-hidden card-hover animate-fade-in mt-6 sm:mt-8" style="animation-delay: 0.8s">
    <div class="px-4 sm:px-6 py-4 sm:py-5 border-b border-white/20 bg-gradient-to-r from-amber-500/20 to-orange-500/20">
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
            <h2 class="text-lg sm:text-xl font-bold text-gray-900 flex items-center">
                <svg class="w-5 h-5 sm:w-6 sm:h-6 text-amber-500 mr-2 sm:mr-3" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                </svg>
                <span class="hidden sm:inline">Ən Yaxşı Nəticələr - Reytinq</span>
                <span class="sm:hidden">Reytinq</span>
            </h2>
            <div class="flex items-center space-x-2 text-xs sm:text-sm text-gray-600">
                <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                </svg>
                <span class="font-semibold">Top 10 Tələbə</span>
            </div>
        </div>
    </div>

    <div class="p-4 sm:p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 sm:gap-4">
            <?php foreach ($topPerformers as $index => $performer):
                $rank = $index + 1;
                $successRate = floatval($performer['success_rate'] ?? 0);
                $totalScore = floatval($performer['total_score'] ?? 0);

                // Medal colors for top 3
                $medalColors = [
                    1 => 'from-yellow-400 to-amber-500',
                    2 => 'from-gray-300 to-gray-400',
                    3 => 'from-orange-400 to-amber-600'
                ];
                $bgColor = isset($medalColors[$rank]) ? $medalColors[$rank] : 'from-blue-500 to-indigo-500';

                // Performance level color
                if ($successRate >= 90) {
                    $performanceColor = 'from-green-500 to-emerald-600';
                    $performanceLabel = 'Əla';
                } elseif ($successRate >= 80) {
                    $performanceColor = 'from-blue-500 to-cyan-600';
                    $performanceLabel = 'Yaxşı';
                } elseif ($successRate >= 70) {
                    $performanceColor = 'from-yellow-500 to-amber-600';
                    $performanceLabel = 'Orta';
                } else {
                    $performanceColor = 'from-orange-500 to-red-600';
                    $performanceLabel = 'Zəif';
                }
            ?>
            <div class="flex items-center bg-gradient-to-r from-white/50 to-transparent rounded-xl sm:rounded-2xl p-3 sm:p-4 hover:shadow-xl hover:-translate-y-1 transition-all border border-gray-200/50 relative overflow-hidden">
                <!-- Rank Badge -->
                <div class="relative flex-shrink-0 mr-3 sm:mr-4">
                    <div class="w-12 h-12 sm:w-16 sm:h-16 bg-gradient-to-br <?php echo $bgColor; ?> rounded-xl sm:rounded-2xl flex items-center justify-center shadow-xl transform hover:scale-110 transition-transform">
                        <?php if ($rank <= 3): ?>
                            <svg class="w-6 h-6 sm:w-8 sm:h-8 text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                            </svg>
                        <?php else: ?>
                            <span class="text-xl sm:text-2xl font-black text-white"><?php echo $rank; ?></span>
                        <?php endif; ?>
                    </div>
                    <?php if ($rank <= 3): ?>
                        <div class="absolute -bottom-1 left-1/2 transform -translate-x-1/2 bg-white rounded-full px-1.5 sm:px-2 py-0.5 shadow-md">
                            <span class="text-xs font-black bg-gradient-to-r <?php echo $bgColor; ?> bg-clip-text text-transparent"><?php echo $rank; ?></span>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Student Info -->
                <div class="flex-grow min-w-0">
                    <div class="flex items-center justify-between mb-2">
                        <div class="flex items-center space-x-2 sm:space-x-3 min-w-0 flex-1">
                            <div class="w-8 h-8 sm:w-10 sm:h-10 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-lg sm:rounded-xl flex items-center justify-center text-white text-sm sm:text-base font-bold shadow-lg flex-shrink-0">
                                <?php echo strtoupper(substr($performer['f_name'], 0, 1)); ?>
                            </div>
                            <div class="min-w-0 flex-1">
                                <h3 class="text-xs sm:text-sm font-bold text-gray-900 truncate"><?php echo htmlspecialchars($performer['f_name']); ?></h3>
                                <div class="flex items-center space-x-1 sm:space-x-2 text-xs text-gray-500">
                                    <span class="truncate hidden sm:inline"><?php echo htmlspecialchars($performer['username']); ?></span>
                                    <?php if ($performer['group_number']): ?>
                                        <span class="flex-shrink-0"><?php echo htmlspecialchars($performer['group_number']); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Stats -->
                    <div class="grid grid-cols-4 gap-1 sm:gap-2 text-center">
                        <div class="bg-white/70 rounded-lg p-1 sm:p-2">
                            <div class="text-sm sm:text-lg font-bold text-gray-900"><?php echo number_format($successRate, 1); ?>%</div>
                            <div class="text-xs text-gray-500 font-medium hidden sm:block">Uğur</div>
                        </div>
                        <div class="bg-white/70 rounded-lg p-1 sm:p-2">
                            <div class="text-sm sm:text-lg font-bold text-gray-900"><?php echo number_format($totalScore, 0); ?></div>
                            <div class="text-xs text-gray-500 font-medium hidden sm:block">Bal</div>
                        </div>
                        <div class="bg-white/70 rounded-lg p-1 sm:p-2">
                            <div class="text-xs sm:text-lg font-bold text-gray-900"><?php echo $performer['correct_answers']; ?><span class="hidden sm:inline">/<?php echo $performer['total_questions']; ?></span></div>
                            <div class="text-xs text-gray-500 font-medium hidden sm:block">Düzgün</div>
                        </div>
                        <div class="bg-white/70 rounded-lg p-1 sm:p-2 flex items-center justify-center">
                            <div class="inline-flex items-center px-1.5 sm:px-2 py-0.5 sm:py-1 rounded-lg text-xs font-bold text-white bg-gradient-to-r <?php echo $performanceColor; ?> shadow-lg">
                                <?php echo $performanceLabel; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Decorative elements for top 3 -->
                <?php if ($rank <= 3): ?>
                    <div class="absolute top-0 right-0 w-24 sm:w-32 h-24 sm:h-32 bg-gradient-to-br <?php echo $bgColor; ?> rounded-full opacity-10 blur-3xl"></div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Exam Results Section -->
<?php if (isset($examResults) && !empty($examResults)): ?>
<div class="glass rounded-2xl sm:rounded-3xl overflow-hidden card-hover animate-fade-in mt-6 sm:mt-8" style="animation-delay: 0.9s">
    <div class="px-4 sm:px-6 py-4 sm:py-5 border-b border-white/20 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 bg-gradient-to-r from-white/50 to-transparent">
        <h2 class="text-lg sm:text-xl font-bold text-gray-900 flex items-center">
            <span class="w-3 h-3 bg-blue-500 rounded-full mr-2 sm:mr-3 animate-pulse"></span>
            İmtahan Nəticələri
        </h2>
        <a href="exam_results.php" class="inline-flex items-center px-4 sm:px-5 py-2 sm:py-2.5 text-xs sm:text-sm font-semibold text-white bg-gradient-to-r from-blue-600 to-cyan-600 rounded-xl hover:shadow-xl hover:-translate-y-0.5 transition-all">
            Hamısını gör
            <svg class="w-3 h-3 sm:w-4 sm:h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
            </svg>
        </a>
    </div>

    <div class="overflow-x-auto -mx-4 sm:mx-0">
        <table class="min-w-full divide-y divide-gray-200/50">
            <thead>
                <tr class="bg-gradient-to-r from-gray-50/50 to-transparent">
                    <th class="px-3 sm:px-6 py-3 sm:py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">ID</th>
                    <th class="px-3 sm:px-6 py-3 sm:py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider hidden md:table-cell">Tarix</th>
                    <th class="px-3 sm:px-6 py-3 sm:py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Qrup</th>
                    <th class="px-3 sm:px-6 py-3 sm:py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider hidden sm:table-cell">Sayı</th>
                    <th class="px-3 sm:px-6 py-3 sm:py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Bal</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200/30">
                <?php foreach ($examResults as $result): ?>
                    <tr class="hover:bg-white/30 transition-colors">
                        <td class="px-3 sm:px-6 py-3 sm:py-4 whitespace-nowrap">
                            <span class="text-xs sm:text-sm font-bold text-gray-900">#<?php echo $result['id_exam']; ?></span>
                        </td>
                        <td class="px-3 sm:px-6 py-3 sm:py-4 whitespace-nowrap text-xs sm:text-sm text-gray-600 hidden md:table-cell">
                            <?php echo date('d.m.Y', strtotime($result['date_exam'])); ?>
                        </td>
                        <td class="px-3 sm:px-6 py-3 sm:py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-2 sm:px-3 py-1 rounded-lg bg-green-50 text-green-700 text-xs font-medium border border-green-200">
                                <svg class="w-3 h-3 sm:w-4 sm:h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                </svg>
                                <span class="truncate"><?php echo $result['group_number']; ?></span>
                            </span>
                        </td>
                        <td class="px-3 sm:px-6 py-3 sm:py-4 whitespace-nowrap hidden sm:table-cell">
                            <span class="inline-flex items-center px-2 sm:px-3 py-1 rounded-full text-xs font-bold text-gray-900 bg-gray-100">
                                <?php echo $result['total_students']; ?>
                            </span>
                        </td>
                        <td class="px-3 sm:px-6 py-3 sm:py-4 whitespace-nowrap">
                            <?php
                            $avg = $result['avg_percentage'] ? round($result['avg_percentage'], 1) : 0;
                            $colorClass = 'from-red-500 to-red-600';
                            if ($avg >= 80) $colorClass = 'from-green-500 to-emerald-500';
                            elseif ($avg >= 60) $colorClass = 'from-yellow-500 to-amber-500';
                            ?>
                            <span class="inline-flex items-center px-2 sm:px-3 py-1 sm:py-1.5 rounded-full text-xs font-bold text-white bg-gradient-to-r <?php echo $colorClass; ?> shadow-lg">
                                <?php echo $avg; ?>%
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- Chart.js Scripts -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Exam Status Doughnut Chart
    <?php if (is_admin() || is_prorektor() || is_dekan() || is_kafedra()): ?>
    const examStatusCtx = document.getElementById('examStatusChart');
    if(examStatusCtx) {
        new Chart(examStatusCtx, {
            type: 'doughnut',
            data: {
                labels: ['Gözləyir', 'Davam edir', 'Tamamlanıb'],
                datasets: [{
                    data: [<?php echo $pendingExams; ?>, <?php echo $inProgressExams; ?>, <?php echo $completedExams; ?>],
                    backgroundColor: [
                        'rgba(251, 191, 36, 0.8)',
                        'rgba(34, 197, 94, 0.8)',
                        'rgba(59, 130, 246, 0.8)',
                    ],
                    borderColor: [
                        'rgba(251, 191, 36, 1)',
                        'rgba(34, 197, 94, 1)',
                        'rgba(59, 130, 246, 1)',
                    ],
                    borderWidth: 2,
                    hoverOffset: 10
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 15,
                            font: {
                                size: 12,
                                weight: 'bold'
                            }
                        }
                    }
                },
                animation: {
                    animateRotate: true,
                    animateScale: true
                }
            }
        });
    }

    // Student Distribution Bar Chart
    const groupDistCtx = document.getElementById('groupDistChart');
    if(groupDistCtx) {
        new Chart(groupDistCtx, {
            type: 'bar',
            data: {
                labels: [<?php if(isset($groupDist)) { foreach($groupDist as $g) { echo "'" . $g['group_number'] . "',"; } } ?>],
                datasets: [{
                    label: 'Tələbə sayı',
                    data: [<?php if(isset($groupDist)) { foreach($groupDist as $g) { echo $g['student_count'] . ","; } } ?>],
                    backgroundColor: 'rgba(14, 165, 233, 0.8)',
                    borderColor: 'rgba(14, 165, 233, 1)',
                    borderWidth: 2,
                    borderRadius: 10,
                    borderSkipped: false,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                },
                animation: {
                    duration: 1500,
                    easing: 'easeInOutQuart'
                }
            }
        });
    }
    <?php endif; ?>

    // Group Averages Chart
    const groupAvgCtx = document.getElementById('groupAveragesChart');
    if(groupAvgCtx) {
        new Chart(groupAvgCtx, {
            type: 'bar',
            data: {
                labels: [<?php if(isset($groupAverages)) { foreach($groupAverages as $g) { echo "'" . addslashes($g['group_number']) . "',"; } } ?>],
                datasets: [
                    {
                        label: 'Uğur Nisbəti (%)',
                        data: [<?php if(isset($groupAverages)) { foreach($groupAverages as $g) { echo number_format($g['avg_success_rate'], 1) . ","; } } ?>],
                        backgroundColor: 'rgba(99, 102, 241, 0.8)',
                        borderColor: 'rgba(99, 102, 241, 1)',
                        borderWidth: 2,
                        borderRadius: 8,
                        borderSkipped: false,
                        yAxisID: 'y',
                    },
                    {
                        label: 'Orta Bal',
                        data: [<?php if(isset($groupAverages)) { foreach($groupAverages as $g) { echo number_format($g['avg_score_per_student'], 1) . ","; } } ?>],
                        backgroundColor: 'rgba(168, 85, 247, 0.8)',
                        borderColor: 'rgba(168, 85, 247, 1)',
                        borderWidth: 2,
                        borderRadius: 8,
                        borderSkipped: false,
                        yAxisID: 'y1',
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        beginAtZero: true,
                        max: 100,
                        ticks: {
                            callback: function(value) {
                                return value + '%';
                            },
                            font: {
                                size: 11,
                                weight: 'bold'
                            }
                        },
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return value + ' bal';
                            },
                            font: {
                                size: 11,
                                weight: 'bold'
                            }
                        },
                        grid: {
                            drawOnChartArea: false,
                        }
                    },
                    x: {
                        ticks: {
                            font: {
                                size: 11,
                                weight: 'bold'
                            }
                        },
                        grid: {
                            display: false
                        }
                    }
                },
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            padding: 15,
                            font: {
                                size: 12,
                                weight: 'bold'
                            },
                            usePointStyle: true,
                            pointStyle: 'circle'
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        padding: 12,
                        titleFont: {
                            size: 14,
                            weight: 'bold'
                        },
                        bodyFont: {
                            size: 13
                        },
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                if (context.parsed.y !== null) {
                                    if (context.datasetIndex === 0) {
                                        label += context.parsed.y.toFixed(1) + '%';
                                    } else {
                                        label += context.parsed.y.toFixed(1) + ' bal';
                                    }
                                }
                                return label;
                            }
                        }
                    }
                },
                animation: {
                    duration: 1500,
                    easing: 'easeInOutQuart'
                }
            }
        });
    }
});
</script>

<?php include_once "../includes/footer.php"; ?>
