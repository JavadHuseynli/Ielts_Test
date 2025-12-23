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

$pageTitle = "Tələbə Paneli";
include_once "../includes/header.php";

$database = new Database();
$db = $database->getConnection();
$user_id = $_SESSION['user_id'];
$group_id = $_SESSION['group_id'];

// Tələbə məlumatlarını almaq
$query = "SELECT u.f_name, u.username, sg.group_number
          FROM users u
          LEFT JOIN student_group sg ON u.group_id = sg.id_student_group
          WHERE u.id_users = :user_id";
$stmt = $db->prepare($query);
$stmt->bindParam(":user_id", $user_id);
$stmt->execute();
$student = $stmt->fetch(PDO::FETCH_ASSOC);

// Cari (aktiv) imtahanları almaq
$query = "SELECT e.id_exam, e.date_exam, e.datetime, s.subjectname, s.timer, e.status
          FROM exams e
          JOIN subjects s ON e.id_subject = s.id_subject
          WHERE e.id_student_group = :group_id AND e.status = 'in_progress'
          ORDER BY e.datetime ASC";
$stmt = $db->prepare($query);
$stmt->bindParam(":group_id", $group_id);
$stmt->execute();
$active_exams = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Qarşıdakı imtahanları almaq
$query = "SELECT e.id_exam, e.date_exam, e.datetime, s.subjectname, s.timer
          FROM exams e
          JOIN subjects s ON e.id_subject = s.id_subject
          WHERE e.id_student_group = :group_id AND e.datetime >= NOW() AND e.status = 'pending'
          ORDER BY e.datetime ASC
          LIMIT 5";
$stmt = $db->prepare($query);
$stmt->bindParam(":group_id", $group_id);
$stmt->execute();
$upcoming_exams = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Keçmiş imtahan nəticələrini almaq
$query = "SELECT e.id_exam, e.date_exam, s.subjectname, 
          COUNT(a.id_answer) as total_questions,
          SUM(a.is_correct) as correct_answers,
          SUM(qr.question_score * a.is_correct) as score
          FROM exams e
          JOIN subjects s ON e.id_subject = s.id_subject
          JOIN answers a ON e.id_exam = a.exam_id
          JOIN question_read qr ON a.id_questions = qr.id_question_text
          WHERE e.id_student_group = :group_id AND a.user_id = :user_id AND e.status = 'completed'
          GROUP BY e.id_exam
          ORDER BY e.datetime DESC
          LIMIT 5";
$stmt = $db->prepare($query);
$stmt->bindParam(":group_id", $group_id);
$stmt->bindParam(":user_id", $user_id);
$stmt->execute();
$exam_results = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50 p-4 sm:p-6 lg:p-8">
    <div class="max-w-7xl mx-auto">

        <!-- Welcome Banner -->
        <div class="mb-8 bg-white rounded-2xl shadow-lg border border-gray-200 p-8 relative overflow-hidden">
            <div class="absolute top-0 right-0 w-64 h-64 bg-gradient-to-br from-blue-500/20 to-purple-500/20 rounded-full blur-3xl"></div>
            <div class="absolute bottom-0 left-0 w-48 h-48 bg-gradient-to-tr from-cyan-500/20 to-pink-500/20 rounded-full blur-3xl"></div>

            <div class="relative z-10">
                <h1 class="text-4xl font-bold bg-gradient-to-r from-blue-600 via-indigo-600 to-purple-600 bg-clip-text text-transparent mb-2">
                    Xoş gəlmisiniz, <?php echo htmlspecialchars($student['f_name']); ?>! 👋
                </h1>
                <div class="flex flex-wrap items-center gap-4 mt-4">
                    <div class="flex items-center space-x-2">
                        <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                        <span class="text-gray-700 font-medium"><?php echo htmlspecialchars($student['username']); ?></span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                        <span class="text-gray-700 font-medium">Qrup: <?php echo htmlspecialchars($student['group_number']); ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Active Exams (Cari İmtahanlar) -->
        <?php if (!empty($active_exams)): ?>
        <div class="mb-8">
            <h2 class="text-2xl font-bold text-gray-900 mb-4 flex items-center">
                <span class="w-3 h-3 bg-red-500 rounded-full mr-3 animate-pulse"></span>
                CARİ İMTAHANLAR
            </h2>
            <div class="grid grid-cols-1 gap-6">
                <?php foreach ($active_exams as $exam): ?>
                <div class="bg-gradient-to-r from-red-500 to-pink-600 rounded-2xl shadow-2xl p-6 text-white transform hover:scale-105 transition-all">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                        <div class="mb-4 md:mb-0">
                            <div class="flex items-center space-x-2 mb-2">
                                <span class="px-3 py-1 bg-white/20 rounded-full text-xs font-bold uppercase">Aktiv</span>
                                <span class="px-3 py-1 bg-white/20 rounded-full text-xs font-bold flex items-center">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    <?php echo $exam['timer']; ?> dəqiqə
                                </span>
                            </div>
                            <h3 class="text-3xl font-bold mb-2"><?php echo htmlspecialchars($exam['subjectname']); ?></h3>
                            <p class="text-white/80">
                                <span class="font-medium">Tarix:</span> <?php echo date('d.m.Y', strtotime($exam['date_exam'])); ?> |
                                <span class="font-medium">Saat:</span> <?php echo date('H:i', strtotime($exam['datetime'])); ?>
                            </p>
                        </div>
                        <div>
                            <a href="exam.php?id=<?php echo $exam['id_exam']; ?>"
                               class="inline-flex items-center px-8 py-4 bg-white text-red-600 rounded-xl font-bold text-lg hover:bg-gray-100 transition-all shadow-xl hover:shadow-2xl">
                                <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                                İmtahana Başla
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Grid Layout for Cards -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

            <!-- Upcoming Exams -->
            <div class="bg-white rounded-2xl shadow-lg border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 bg-gradient-to-r from-amber-500 to-orange-500 flex items-center justify-between">
                    <h2 class="text-xl font-bold text-white flex items-center">
                        <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                        Qarşıdakı İmtahanlar
                    </h2>
                    <a href="exams.php" class="px-3 py-1 bg-white/20 text-white rounded-lg text-sm font-semibold hover:bg-white/30 transition-all">
                        Hamısı
                    </a>
                </div>
                <div class="p-6">
                    <?php if (empty($upcoming_exams)): ?>
                        <div class="text-center py-8">
                            <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                            <p class="text-gray-500">Yaxın zamanda imtahanınız yoxdur</p>
                        </div>
                    <?php else: ?>
                        <div class="space-y-3">
                            <?php foreach ($upcoming_exams as $exam): ?>
                            <div class="border border-gray-200 rounded-xl p-4 hover:border-amber-500 hover:bg-amber-50 transition-all">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <h3 class="font-bold text-gray-900"><?php echo htmlspecialchars($exam['subjectname']); ?></h3>
                                        <p class="text-sm text-gray-600 mt-1">
                                            <?php echo date('d.m.Y', strtotime($exam['date_exam'])); ?> |
                                            <?php echo date('H:i', strtotime($exam['datetime'])); ?>
                                        </p>
                                    </div>
                                    <div class="flex items-center space-x-2 px-3 py-1 bg-amber-100 text-amber-700 rounded-lg text-sm font-bold">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                        <span><?php echo $exam['timer']; ?> dəq</span>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Exam Results -->
            <div class="bg-white rounded-2xl shadow-lg border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 bg-gradient-to-r from-green-500 to-emerald-500 flex items-center justify-between">
                    <h2 class="text-xl font-bold text-white flex items-center">
                        <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        Son Nəticələr
                    </h2>
                    <a href="exams.php?tab=results" class="px-3 py-1 bg-white/20 text-white rounded-lg text-sm font-semibold hover:bg-white/30 transition-all">
                        Hamısı
                    </a>
                </div>
                <div class="p-6">
                    <?php if (empty($exam_results)): ?>
                        <div class="text-center py-8">
                            <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                            <p class="text-gray-500">Hələ imtahan nəticəsi yoxdur</p>
                        </div>
                    <?php else: ?>
                        <div class="space-y-3">
                            <?php foreach ($exam_results as $result):
                                $total_questions = $result['total_questions'] ?? 0;
                                $correct_answers = $result['correct_answers'] ?? 0;
                                $score = $result['score'] ?? 0;

                                $percentage = $total_questions > 0 ? ($correct_answers / $total_questions) * 100 : 0;
                                $colorClass = $percentage >= 70 ? 'from-green-500 to-emerald-500' : ($percentage >= 50 ? 'from-yellow-500 to-amber-500' : 'from-red-500 to-pink-500');
                            ?>
                            <div class="border border-gray-200 rounded-xl p-4 hover:border-green-500 hover:bg-green-50 transition-all">
                                <div class="flex items-center justify-between mb-2">
                                    <h3 class="font-bold text-gray-900"><?php echo htmlspecialchars($result['subjectname']); ?></h3>
                                    <span class="text-xs text-gray-600"><?php echo date('d.m.Y', strtotime($result['date_exam'])); ?></span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-4 text-sm">
                                        <span class="text-gray-600">
                                            <span class="font-bold text-gray-900"><?php echo $correct_answers; ?></span> / <?php echo $total_questions; ?> düzgün
                                        </span>
                                        <span class="text-gray-600">
                                            Bal: <span class="font-bold text-gray-900"><?php echo number_format($score, 2); ?></span>
                                        </span>
                                    </div>
                                    <div class="px-3 py-1 bg-gradient-to-r <?php echo $colorClass; ?> text-white rounded-lg text-sm font-bold">
                                        <?php echo number_format($percentage, 0); ?>%
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div>
</div>

<?php include_once "../includes/footer.php"; ?>