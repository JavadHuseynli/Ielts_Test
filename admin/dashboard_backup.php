<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
require_once "../includes/db.php";
require_once "../includes/auth.php";

checkLogin(); // Ensure user is logged in
// Allow all admin roles to access dashboard
if (!is_admin() && !is_prorektor() && !is_kafedra() && !is_teacher()) {
    header("Location: ../login.php");
    exit();
}

$pageTitle = "Admin Paneli";
include_once "../includes/header_new.php";

$database = new Database();
$db = $database->getConnection();

// Determine user's role for dynamic content
$user_role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];
$user_subject_id = isset($_SESSION['subject_id']) ? $_SESSION['subject_id'] : null;
$user_group_id = isset($_SESSION['group_id']) ? $_SESSION['group_id'] : null; // For student view

// Initialize counts
$studentCount = 0;
$groupCount = 0;
$subjectCount = 0;
$examCount = 0;
$recentExams = [];

// Base queries for counts
$base_student_query = "SELECT COUNT(*) as total FROM users WHERE status = 'student'";
$base_group_query = "SELECT COUNT(*) as total FROM student_group";
$base_subject_query = "SELECT COUNT(*) as total FROM subjects";
$base_exam_query = "SELECT COUNT(*) as total FROM exams";

// Adjust queries based on role
if (is_admin() || is_prorektor() || is_kafedra()) {
    // Admin, Prorektor, Kafedra see all counts
    $stmt = $db->prepare($base_student_query); $stmt->execute(); $studentCount = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $stmt = $db->prepare($base_group_query); $stmt->execute(); $groupCount = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $stmt = $db->prepare($base_subject_query); $stmt->execute(); $subjectCount = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $stmt = $db->prepare($base_exam_query); $stmt->execute(); $examCount = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Recent exams for Admin, Prorektor, Kafedra
    $query = "SELECT e.id_exam, e.date_exam, e.datetime, s.subjectname, sg.group_number,
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

} elseif (is_teacher()) {
    // Teacher sees counts and exams related to their subject
    if ($user_subject_id) {
        $stmt = $db->prepare($base_subject_query . " WHERE id_subject = :subject_id"); $stmt->bindParam(":subject_id", $user_subject_id); $stmt->execute(); $subjectCount = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        $stmt = $db->prepare($base_exam_query . " WHERE id_subject = :subject_id"); $stmt->bindParam(":subject_id", $user_subject_id); $stmt->execute(); $examCount = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

        $query = "SELECT e.id_exam, e.date_exam, e.datetime, s.subjectname, sg.group_number,
                  CASE
                      WHEN e.status = 'pending' THEN 'Gözləyir'
                      WHEN e.status = 'in_progress' THEN 'Davam edir'
                      WHEN e.status = 'completed' THEN 'Tamamlanıb'
                  END as status_name
                  FROM exams e
                  JOIN subjects s ON e.id_subject = s.id_subject
                  JOIN student_group sg ON e.id_student_group = sg.id_student_group
                  WHERE e.id_subject = :subject_id
                  ORDER BY e.datetime DESC LIMIT 5";
        $stmt = $db->prepare($query);
        $stmt->bindParam(":subject_id", $user_subject_id);
        $stmt->execute();
        $recentExams = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>

<!-- Stats Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <?php if (is_admin() || is_prorektor() || is_kafedra()): ?>
    <!-- Students Card -->
    <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl shadow-lg p-6 text-white transform hover:scale-105 transition duration-300">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-blue-100 text-sm font-medium mb-1">Tələbələr</p>
                <p class="text-3xl font-bold"><?php echo $studentCount; ?></p>
            </div>
            <div class="bg-white bg-opacity-20 rounded-lg p-3">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                </svg>
            </div>
        </div>
        <?php if (is_admin()): ?>
        <a href="users.php" class="inline-block mt-4 text-sm font-medium hover:underline">İdarə et →</a>
        <?php endif; ?>
    </div>

    <!-- Groups Card -->
    <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl shadow-lg p-6 text-white transform hover:scale-105 transition duration-300">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-green-100 text-sm font-medium mb-1">Qruplar</p>
                <p class="text-3xl font-bold"><?php echo $groupCount; ?></p>
            </div>
            <div class="bg-white bg-opacity-20 rounded-lg p-3">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                </svg>
            </div>
        </div>
        <?php if (is_admin()): ?>
        <a href="groups.php" class="inline-block mt-4 text-sm font-medium hover:underline">İdarə et →</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if (is_admin() || is_kafedra() || is_teacher()): ?>
    <!-- Subjects Card -->
    <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl shadow-lg p-6 text-white transform hover:scale-105 transition duration-300">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-purple-100 text-sm font-medium mb-1">Fənlər</p>
                <p class="text-3xl font-bold"><?php echo $subjectCount; ?></p>
            </div>
            <div class="bg-white bg-opacity-20 rounded-lg p-3">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                </svg>
            </div>
        </div>
        <?php if (is_admin() || is_kafedra()): ?>
        <a href="subjects.php" class="inline-block mt-4 text-sm font-medium hover:underline">İdarə et →</a>
        <?php elseif (is_teacher() && $user_subject_id): ?>
        <a href="questions.php?subject=<?php echo $user_subject_id; ?>" class="inline-block mt-4 text-sm font-medium hover:underline">Suallara Bax →</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if (is_admin() || is_prorektor() || is_kafedra() || is_teacher()): ?>
    <!-- Exams Card -->
    <div class="bg-gradient-to-br from-amber-500 to-amber-600 rounded-xl shadow-lg p-6 text-white transform hover:scale-105 transition duration-300">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-amber-100 text-sm font-medium mb-1">İmtahanlar</p>
                <p class="text-3xl font-bold"><?php echo $examCount; ?></p>
            </div>
            <div class="bg-white bg-opacity-20 rounded-lg p-3">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                </svg>
            </div>
        </div>
        <?php if (is_admin() || is_prorektor()): ?>
        <a href="exams.php" class="inline-block mt-4 text-sm font-medium hover:underline">İdarə et →</a>
        <?php elseif (is_kafedra() || is_teacher()): ?>
        <a href="exam_results.php" class="inline-block mt-4 text-sm font-medium hover:underline">Nəticələrə Bax →</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<!-- Recent Exams Table -->
<div class="bg-white rounded-xl shadow-lg overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between bg-gradient-to-r from-gray-50 to-white">
        <h2 class="text-lg font-semibold text-gray-900">Son İmtahanlar</h2>
        <?php if (is_admin() || is_prorektor() || is_kafedra()): ?>
        <a href="exams.php" class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700 transition">
            Hamısını gör
            <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
            </svg>
        </a>
        <?php endif; ?>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fənn</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Qrup</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tarix</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Saat</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (empty($recentExams)): ?>
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                            </svg>
                            <p class="mt-2 text-sm text-gray-500">İmtahan tapılmadı</p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($recentExams as $exam): ?>
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo $exam['id_exam']; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo $exam['subjectname']; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $exam['group_number']; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo date('d.m.Y', strtotime($exam['date_exam'])); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo date('H:i', strtotime($exam['datetime'])); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php
                                $statusClass = 'bg-gray-100 text-gray-800';
                                if ($exam['status_name'] == 'Gözləyir') {
                                    $statusClass = 'bg-yellow-100 text-yellow-800';
                                } else if ($exam['status_name'] == 'Davam edir') {
                                    $statusClass = 'bg-green-100 text-green-800';
                                } else if ($exam['status_name'] == 'Tamamlanıb') {
                                    $statusClass = 'bg-blue-100 text-blue-800';
                                }
                                ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $statusClass; ?>">
                                    <?php echo $exam['status_name']; ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include_once "../includes/footer_new.php"; ?>
