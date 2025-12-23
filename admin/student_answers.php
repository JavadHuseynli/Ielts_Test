<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
require_once "../includes/db.php";
require_once "../includes/auth.php";

checkLogin();

$exam_id = intval($_GET['exam']);
$student_id = intval($_GET['student']);
$teacher_subject_id = $_SESSION['subject_id'] ?? null;

// Permission Check: All roles that can view exam results can view student answers
if (!is_admin() && !is_prorektor() && !is_kafedra() && !is_teacher()) {
    header("Location: dashboard.php?error=access_denied");
    exit();
}

if (!isset($_GET['exam']) || !is_numeric($_GET['exam']) || !isset($_GET['student']) || !is_numeric($_GET['student'])) {
    header("Location: exams.php?error=invalid_parameters"); // More specific error
    exit();
}


$database = new Database();
$db = $database->getConnection();

// Fetch Exam Information, including subject_id for teacher check
$query = "SELECT e.id_exam, e.date_exam, e.datetime, e.status, e.id_subject,
          s.subjectname, sg.group_number, u.f_name, u.username
          FROM exams e
          JOIN subjects s ON e.id_subject = s.id_subject
          JOIN student_group sg ON e.id_student_group = sg.id_student_group
          JOIN users u ON u.id_users = :student_id
          WHERE e.id_exam = :exam_id";

// Restrict for teacher: only show answers for exams in their subject
if (is_teacher()) {
    $query .= " AND e.id_subject = :teacher_subject_id";
}

$stmt = $db->prepare($query);
$stmt->bindParam(":exam_id", $exam_id);
$stmt->bindParam(":student_id", $student_id);
if (is_teacher()) {
    $stmt->bindParam(":teacher_subject_id", $teacher_subject_id);
}
$stmt->execute();

if ($stmt->rowCount() == 0) {
    header("Location: exams.php?error=exam_student_not_found_or_unauthorized");
    exit();
}

$exam = $stmt->fetch(PDO::FETCH_ASSOC);

// Redirect if the exam is not completed, unless admin override
if ($exam['status'] != 'completed' && !is_admin()) {
    header("Location: exams.php?error=exam_not_completed");
    exit();
}

$pageTitle = "Tələbə Cavabları";
include_once "../includes/header.php";

// Fetch only questions that the student answered
$query_results = "SELECT
                    qr.id_question_text, qr.question_text, qr.question_score,
                    qt.quest_type_name, qt.question_var,
                    a.user_answer, a.correct_var, a.is_correct, a.datetime
                  FROM answers a
                  JOIN question_read qr ON a.id_questions = qr.id_question_text
                  JOIN question_types qt ON qr.id_question_type = qt.id_quest_type
                  WHERE a.exam_id = :exam_id AND a.user_id = :student_id
                  ORDER BY qr.id_question_text";

$stmt_results = $db->prepare($query_results);
$stmt_results->bindParam(":exam_id", $exam_id);
$stmt_results->bindParam(":student_id", $student_id);
$stmt_results->execute();
$results = $stmt_results->fetchAll(PDO::FETCH_ASSOC);

// Calculate statistics
$totalQuestions = count($results);
$answeredQuestions = count($results); // All results are answered questions now
$correctAnswers = 0;
$totalScore = 0;
$maxPossibleScore = 0;

foreach ($results as $result) {
    $maxPossibleScore += $result['question_score'];
    if ($result['is_correct']) {
        $correctAnswers++;
        $totalScore += $result['question_score'];
    }
}

$percentage = $maxPossibleScore > 0 ? ($totalScore / $maxPossibleScore) * 100 : 0;
?>
<div class="bg-gray-100 min-h-screen p-4 sm:p-6 lg:p-8">
    <div class="max-w-7xl mx-auto">

        <!-- Header Card -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                <div class="mb-4 md:mb-0">
                    <h1 class="text-2xl font-bold text-gray-800">Tələbə Cavabları</h1>
                    <p class="text-md text-gray-600 mt-1">
                        <strong>Tələbə:</strong> <?php echo htmlspecialchars($exam['f_name'] . ' (' . $exam['username'] . ')'); ?>
                    </p>
                    <p class="text-sm text-gray-500 mt-1">
                        <strong>İmtahan:</strong> <?php echo htmlspecialchars($exam['subjectname'] . ' - ' . $exam['group_number']); ?> |
                        <strong>Tarix:</strong> <?php echo date('d.m.Y H:i', strtotime($exam['datetime'])); ?>
                    </p>
                </div>
                <div class="flex items-center space-x-2">
                    <a href="exam_results.php?id=<?php echo $exam_id; ?>" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md font-semibold hover:bg-gray-300 transition-all text-sm flex items-center space-x-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                        <span>Nəticələrə Qayıt</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
            <div class="bg-white rounded-lg shadow-md p-5">
                <h3 class="text-sm font-medium text-gray-500">Ümumi Bal</h3>
                <p class="mt-1 text-3xl font-semibold text-gray-900">
                    <?php echo number_format($totalScore, 2); ?> / <span class="text-xl text-gray-500"><?php echo number_format($maxPossibleScore, 2); ?></span>
                </p>
            </div>
            <div class="bg-white rounded-lg shadow-md p-5">
                <h3 class="text-sm font-medium text-gray-500">Faiz</h3>
                <p class="mt-1 text-3xl font-semibold text-indigo-600">
                    <?php echo number_format($percentage, 2); ?>%
                </p>
            </div>
            <div class="bg-white rounded-lg shadow-md p-5">
                <h3 class="text-sm font-medium text-gray-500">Düzgün Cavablar</h3>
                <p class="mt-1 text-3xl font-semibold text-green-600">
                    <?php echo $correctAnswers; ?>
                </p>
            </div>
            <div class="bg-white rounded-lg shadow-md p-5">
                <h3 class="text-sm font-medium text-gray-500">Cavablanmış Suallar</h3>
                <p class="mt-1 text-3xl font-semibold text-gray-900">
                    <?php echo $answeredQuestions; ?> / <span class="text-xl text-gray-500"><?php echo $totalQuestions; ?></span>
                </p>
            </div>
        </div>

        <!-- Answers List -->
        <div class="space-y-4">
            <?php if (empty($results)): ?>
                <div class="bg-white text-center rounded-lg shadow-md p-12">
                    <h3 class="text-lg font-medium text-gray-900">Nəticə Tapılmadı</h3>
                    <p class="mt-2 text-sm text-gray-500">Bu imtahan üçün heç bir sual və ya cavab tapılmadı.</p>
                </div>
            <?php else: ?>
                <?php 
                $counter = 1;
                foreach ($results as $result):
                    $status = 'unanswered';
                    if ($result['datetime'] !== null) {
                        $status = $result['is_correct'] ? 'correct' : 'incorrect';
                    }

                    $status_styles = [
                        'correct'    => ['border' => 'border-green-400', 'bg' => 'bg-green-50', 'text' => 'text-green-700', 'label' => 'Düzgün'],
                        'incorrect'  => ['border' => 'border-red-400', 'bg' => 'bg-red-50', 'text' => 'text-red-700', 'label' => 'Yanlış'],
                        'unanswered' => ['border' => 'border-gray-300', 'bg' => 'bg-gray-50', 'text' => 'text-gray-600', 'label' => 'Cavabsız']
                    ];
                    $style = $status_styles[$status];
                ?>
                <div class="bg-white rounded-lg shadow-sm border-l-4 <?php echo $style['border']; ?>">
                    <div class="p-5">
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <p class="text-sm font-bold text-gray-800">Sual <?php echo $counter++; ?></p>
                                <p class="text-xs font-medium <?php echo $style['text']; ?> uppercase"><?php echo $style['label']; ?></p>
                            </div>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-indigo-100 text-indigo-800">
                                <?php echo htmlspecialchars($result['question_score']); ?> bal
                            </span>
                        </div>

                        <div class="prose prose-sm max-w-none text-gray-700 mb-4">
                            <?php echo nl2br(htmlspecialchars($result['question_text'])); ?>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Student's Answer -->
                            <div class="<?php echo $style['bg']; ?> p-4 rounded-md">
                                <h4 class="font-semibold text-sm text-gray-800 mb-2">Tələbənin Cavabı</h4>
                                <?php if ($status === 'unanswered'): ?>
                                    <p class="text-gray-500 italic text-sm">Cavab verilməyib</p>
                                <?php else: ?>
                                    <p class="text-gray-900"><?php echo htmlspecialchars($result['user_answer']); ?></p>
                                <?php endif; ?>
                            </div>

                            <!-- Correct Answer -->
                            <div class="bg-gray-50 p-4 rounded-md">
                                <h4 class="font-semibold text-sm text-gray-800 mb-2">Düzgün Cavab</h4>
                                <?php if ($result['question_var'] == 'multiple'): ?>
                                    <?php
                                    $options_stmt = $db->prepare("SELECT var_a, var_b, var_c, var_d FROM multiple_questions WHERE id_question_text = :id");
                                    $options_stmt->execute([':id' => $result['id_question_text']]);
                                    $options = $options_stmt->fetch(PDO::FETCH_ASSOC);
                                    $correct_var = $result['correct_var'] ?? '';
                                    $correct_option_text = $correct_var ? ($options['var_' . $correct_var] ?? 'N/A') : 'N/A';
                                    ?>
                                    <p class="text-gray-900"><strong><?php echo $correct_var ? strtoupper($correct_var) : 'N/A'; ?>)</strong> <?php echo htmlspecialchars($correct_option_text); ?></p>
                                <?php else: ?>
                                    <p class="text-gray-900"><?php echo htmlspecialchars($result['correct_var'] ?? 'N/A'); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <?php if($result['datetime']): ?>
                        <div class="mt-4 text-right">
                            <p class="text-xs text-gray-400">
                                Cavablandı: <?php echo date('d.m.Y H:i:s', strtotime($result['datetime'])); ?>
                            </p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include_once "../includes/footer.php"; ?>