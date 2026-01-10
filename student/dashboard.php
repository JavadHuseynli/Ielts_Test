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

// Cari (aktiv) imtahanları almaq - hem pending hem in_progress
$query = "SELECT e.id_exam, e.date_exam, e.datetime, s.subjectname, s.timer, e.status
          FROM exams e
          JOIN subjects s ON e.id_subject = s.id_subject
          WHERE e.id_student_group = :group_id AND e.status IN ('pending', 'in_progress')
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
$query = "SELECT
            sc.id_score,
            e.id_exam,
            e.date_exam,
            sub.subjectname,
            sc.score as total_score,
            sc.datetime as submission_date,
            (SELECT COUNT(*) FROM answers a WHERE a.exam_id = e.id_exam AND a.user_id = sc.user_id) as total_questions,
            (SELECT SUM(is_correct) FROM answers a WHERE a.exam_id = e.id_exam AND a.user_id = sc.user_id) as correct_answers
          FROM scores sc
          JOIN exams e ON sc.exam_id = e.id_exam
          JOIN subjects sub ON e.id_subject = sub.id_subject
          WHERE sc.user_id = :user_id
          ORDER BY sc.datetime DESC
          LIMIT 10";
$stmt = $db->prepare($query);
$stmt->bindParam(":user_id", $user_id);
$stmt->execute();
$exam_results = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- Success Notification -->
<?php if (isset($_GET['success']) && $_GET['success'] == '1'): ?>
<div id="successNotification" class="fixed top-4 right-4 z-50 animate-fade-in-down">
    <div class="bg-gradient-to-r from-green-500 to-emerald-600 text-white px-6 py-4 rounded-xl shadow-2xl flex items-center space-x-3 max-w-md">
        <svg class="w-6 h-6 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
        <span class="font-semibold">İmtahan uğurla təsdiq edildi! Cavablarınız qeydə alındı.</span>
        <button onclick="closeNotification()" class="ml-auto hover:bg-white/20 rounded-lg p-1 transition-all">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </button>
    </div>
</div>
<?php endif; ?>

<!-- Error Notification - Already Completed -->
<?php if (isset($_GET['error']) && $_GET['error'] == 'already_completed'): ?>
<div id="errorNotification" class="fixed top-4 right-4 z-50 animate-fade-in-down">
    <div class="bg-gradient-to-r from-red-500 to-red-600 text-white px-6 py-4 rounded-xl shadow-2xl flex items-center space-x-3 max-w-md">
        <svg class="w-6 h-6 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
        <span class="font-semibold">Siz artıq bu imtahanı təsdiq etmisiniz!</span>
        <button onclick="closeNotification()" class="ml-auto hover:bg-white/20 rounded-lg p-1 transition-all">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </button>
    </div>
</div>
<?php endif; ?>

<style>
@keyframes fade-in-down {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
.animate-fade-in-down {
    animation: fade-in-down 0.5s ease-out;
}
</style>

<script>
function closeNotification() {
    const successNotification = document.getElementById('successNotification');
    const errorNotification = document.getElementById('errorNotification');

    if (successNotification) {
        successNotification.style.opacity = '0';
        successNotification.style.transform = 'translateY(-20px)';
        setTimeout(() => successNotification.remove(), 300);
    }

    if (errorNotification) {
        errorNotification.style.opacity = '0';
        errorNotification.style.transform = 'translateY(-20px)';
        setTimeout(() => errorNotification.remove(), 300);
    }
}

// Auto-close after 5 seconds
setTimeout(() => {
    closeNotification();
}, 5000);
</script>

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
                            <a href="exam_v2.php?id=<?php echo $exam['id_exam']; ?>"
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
                        <div class="space-y-4">
                            <?php
                            $isFirstResult = true;
                            foreach ($exam_results as $result):
                                $total_questions = $result['total_questions'] ?? 0;
                                $correct_answers = $result['correct_answers'] ?? 0;
                                $total_score = $result['total_score'] ?? 0;
                                $wrong_answers = $total_questions - $correct_answers;

                                $percentage = $total_questions > 0 ? ($correct_answers / $total_questions) * 100 : 0;
                                $colorClass = $percentage >= 70 ? 'green' : ($percentage >= 50 ? 'yellow' : 'red');

                                // İlk nəticə (ən yeni) - BÖYÜK KART
                                if ($isFirstResult && isset($_GET['success'])):
                                    $isFirstResult = false;
                            ?>
                            <!-- SON İMTAHAN NƏTİCƏSİ - BÖYÜK KART -->
                            <div class="relative overflow-hidden rounded-2xl border-2 border-<?php echo $colorClass; ?>-500 bg-gradient-to-br from-<?php echo $colorClass; ?>-50 to-white p-6 shadow-xl">
                                <!-- Background decoration -->
                                <div class="absolute top-0 right-0 -mt-4 -mr-4 h-32 w-32 rounded-full bg-<?php echo $colorClass; ?>-200 opacity-20 blur-3xl"></div>
                                <div class="absolute bottom-0 left-0 -mb-4 -ml-4 h-32 w-32 rounded-full bg-<?php echo $colorClass; ?>-300 opacity-20 blur-3xl"></div>

                                <div class="relative">
                                    <!-- Badge -->
                                    <div class="mb-4 inline-flex items-center space-x-2 rounded-full bg-<?php echo $colorClass; ?>-500 px-4 py-2 text-sm font-bold text-white shadow-lg">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"></path></svg>
                                        <span>SON NƏTİCƏ</span>
                                    </div>

                                    <!-- Başlıq -->
                                    <div class="mb-6 flex items-center justify-between">
                                        <div>
                                            <h3 class="text-2xl font-bold text-gray-900"><?php echo htmlspecialchars($result['subjectname']); ?></h3>
                                            <p class="mt-1 text-sm text-gray-600"><?php echo date('d.m.Y, H:i', strtotime($result['submission_date'])); ?></p>
                                        </div>
                                        <div class="flex h-24 w-24 items-center justify-center rounded-full bg-gradient-to-br from-<?php echo $colorClass; ?>-500 to-<?php echo $colorClass; ?>-600 shadow-lg">
                                            <div class="text-center">
                                                <div class="text-3xl font-bold text-white"><?php echo number_format($percentage, 0); ?>%</div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Statistika -->
                                    <div class="grid grid-cols-3 gap-4 mb-6">
                                        <!-- Ümumi suallar -->
                                        <div class="rounded-xl bg-white p-4 text-center shadow-md border border-gray-200">
                                            <div class="text-3xl font-bold text-gray-900"><?php echo $total_questions; ?></div>
                                            <div class="mt-1 text-xs font-semibold uppercase tracking-wide text-gray-500">Ümumi Sual</div>
                                        </div>

                                        <!-- Düzgün cavablar -->
                                        <div class="rounded-xl bg-gradient-to-br from-green-500 to-emerald-600 p-4 text-center shadow-md">
                                            <div class="text-3xl font-bold text-white"><?php echo $correct_answers; ?></div>
                                            <div class="mt-1 text-xs font-semibold uppercase tracking-wide text-green-100">Düzgün</div>
                                        </div>

                                        <!-- Səhv cavablar -->
                                        <div class="rounded-xl bg-gradient-to-br from-red-500 to-pink-600 p-4 text-center shadow-md">
                                            <div class="text-3xl font-bold text-white"><?php echo $wrong_answers; ?></div>
                                            <div class="mt-1 text-xs font-semibold uppercase tracking-wide text-red-100">Səhv</div>
                                        </div>
                                    </div>

                                    <!-- Bal -->
                                    <div class="rounded-xl bg-gradient-to-r from-indigo-500 to-purple-600 p-4 text-center shadow-lg">
                                        <div class="text-sm font-semibold uppercase tracking-wide text-indigo-100">Ümumi Bal</div>
                                        <div class="mt-2 text-4xl font-bold text-white"><?php echo number_format($total_score, 2); ?></div>
                                    </div>

                                    <!-- Progress bar -->
                                    <div class="mt-6">
                                        <div class="mb-2 flex items-center justify-between text-sm font-semibold text-gray-700">
                                            <span>Uğur dərəcəsi</span>
                                            <span><?php echo number_format($percentage, 1); ?>%</span>
                                        </div>
                                        <div class="h-3 overflow-hidden rounded-full bg-gray-200">
                                            <div class="h-full rounded-full bg-gradient-to-r from-<?php echo $colorClass; ?>-500 to-<?php echo $colorClass; ?>-600 shadow-sm transition-all duration-500" style="width: <?php echo $percentage; ?>%"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <?php else:
                                $isFirstResult = false;
                            ?>
                            <!-- DİGƏR NƏTİCƏLƏR - KİÇİK KART -->
                            <div class="border border-gray-200 rounded-xl p-4 hover:border-<?php echo $colorClass; ?>-500 hover:bg-<?php echo $colorClass; ?>-50 transition-all">
                                <div class="flex items-center justify-between mb-2">
                                    <h3 class="font-bold text-gray-900"><?php echo htmlspecialchars($result['subjectname']); ?></h3>
                                    <span class="text-xs text-gray-600"><?php echo date('d.m.Y', strtotime($result['submission_date'])); ?></span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-4 text-sm">
                                        <span class="text-gray-600">
                                            <span class="font-bold text-gray-900"><?php echo $correct_answers; ?></span> / <?php echo $total_questions; ?> düzgün
                                        </span>
                                        <span class="text-gray-600">
                                            Bal: <span class="font-bold text-gray-900"><?php echo number_format($total_score, 2); ?></span>
                                        </span>
                                    </div>
                                    <div class="px-3 py-1 bg-gradient-to-r from-<?php echo $colorClass; ?>-500 to-<?php echo $colorClass; ?>-600 text-white rounded-lg text-sm font-bold">
                                        <?php echo number_format($percentage, 0); ?>%
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div>
</div>

<?php include_once "../includes/footer.php"; ?>