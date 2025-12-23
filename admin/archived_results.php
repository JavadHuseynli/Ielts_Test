<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
require_once "../includes/db.php";
require_once "../includes/auth.php";

checkLogin();

// Permission Check: Only Admin and Prorektor can view archived results
if (!is_admin() && !is_prorektor()) {
    header("Location: dashboard.php?error=access_denied");
    exit();
}

$pageTitle = "Arxivlənmiş İmtahan Nəticələri";
include_once "../includes/header.php";

$database = new Database();
$db = $database->getConnection();
$message = "";

// Handle deletion of archived results (Admin only)
if (isset($_GET['delete_archive']) && is_numeric($_GET['delete_archive'])) {
    if (!is_admin()) {
        $message = '<div class="alert alert-danger">Arxivlənmiş nəticələri silməyə icazəniz yoxdur!</div>';
    } else {
        $archive_id = intval($_GET['delete_archive']);
        try {
            $query = "DELETE FROM exam_results_archive WHERE id_archive = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(":id", $archive_id);
            if ($stmt->execute()) {
                $message = '<div class="alert alert-success">Arxivlənmiş nəticə uğurla silindi!</div>';
            } else {
                $message = '<div class="alert alert-danger">Arxivlənmiş nəticə silinərkən xəta baş verdi!</div>';
            }
        } catch (PDOException $e) {
            $message = '<div class="alert alert-danger">Verilənlər bazası xətası: ' . $e->getMessage() . '</div>';
        }
    }
}


// Fetch archived results
$query = "SELECT era.id_archive, era.exam_id, era.user_id, era.score_earned, era.archived_at,
          e.date_exam, e.datetime, s.subjectname, sg.group_number,
          u.f_name as student_name, u.username as student_username,
          ua.f_name as archived_by_name
          FROM exam_results_archive era
          JOIN exams e ON era.exam_id = e.id_exam
          JOIN subjects s ON e.id_subject = s.id_subject
          JOIN student_group sg ON e.id_student_group = sg.id_student_group
          JOIN users u ON era.user_id = u.id_users
          LEFT JOIN users ua ON era.archived_by_user_id = ua.id_users
          ORDER BY era.archived_at DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$archivedResults = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Group results by exam for better display
$groupedArchivedResults = [];
foreach ($archivedResults as $result) {
    $exam_key = $result['exam_id'] . '_' . $result['date_exam'];
    if (!isset($groupedArchivedResults[$exam_key])) {
        $groupedArchivedResults[$exam_key] = [
            'exam_id' => $result['exam_id'],
            'subjectname' => $result['subjectname'],
            'group_number' => $result['group_number'],
            'date_exam' => $result['date_exam'],
            'datetime' => $result['datetime'],
            'archived_at' => $result['archived_at'],
            'archived_by_name' => $result['archived_by_name'],
            'students' => []
        ];
    }
    $groupedArchivedResults[$exam_key]['students'][] = [
        'id_archive' => $result['id_archive'],
        'student_name' => $result['student_name'],
        'student_username' => $result['student_username'],
        'score_earned' => $result['score_earned']
    ];
}
?>

<div class="bg-gray-100 min-h-screen p-4 sm:p-6 lg:p-8">
    <div class="max-w-7xl mx-auto">

        <!-- Header Card -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                <div class="mb-4 md:mb-0">
                    <h1 class="text-2xl font-bold text-gray-800">Arxivlənmiş İmtahan Nəticələri</h1>
                    <p class="text-md text-gray-600 mt-1">Silinmiş imtahan nəticələrinin siyahısı.</p>
                </div>
                <a href="dashboard.php" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md font-semibold hover:bg-gray-300 transition-all text-sm flex items-center space-x-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    <span>Geri</span>
                </a>
            </div>
        </div>

        <?php if (!empty($message)): ?>
        <div class="mb-6">
            <?php
            $message_type = 'info';
            if (strpos($message, 'success') !== false) $message_type = 'success';
            if (strpos($message, 'danger') !== false) $message_type = 'error';
            
            $colors = [
                'success' => 'bg-green-100 border-green-400 text-green-700',
                'error' => 'bg-red-100 border-red-400 text-red-700',
                'info' => 'bg-blue-100 border-blue-400 text-blue-700',
            ];
            $icon = [
                'success' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>',
                'error' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>',
                'info' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>',
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

        <?php if (empty($groupedArchivedResults)): ?>
            <div class="bg-white text-center rounded-lg shadow-md p-12">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                    <path vector-effect="non-scaling-stroke" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <h3 class="mt-2 text-lg font-medium text-gray-900">Arxiv Tapılmadı</h3>
                <p class="mt-1 text-sm text-gray-500">Hələ heç bir imtahan nəticəsi arxivlənməyib.</p>
            </div>
        <?php else: ?>
            <div class="space-y-2" id="accordion-container">
            <?php foreach ($groupedArchivedResults as $exam_key => $exam_data): ?>
                <div class="bg-white rounded-lg shadow-md">
                    <button type="button" data-accordion-header class="w-full p-4 text-left hover:bg-gray-50 focus:outline-none">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                            <div class="mb-2 sm:mb-0 text-left">
                                <h3 class="text-lg font-semibold text-gray-800">
                                    <?php echo htmlspecialchars($exam_data['subjectname']); ?> - <?php echo htmlspecialchars($exam_data['group_number']); ?>
                                </h3>
                                <p class="text-sm text-gray-500">
                                    İmtahan: <?php echo date('d.m.Y H:i', strtotime($exam_data['datetime'])); ?>
                                </p>
                            </div>
                            <div class="flex items-center space-x-4">
                                <div class="text-sm text-gray-500 text-left sm:text-right">
                                    <p>Arxivləndi: <?php echo date('d.m.Y H:i', strtotime($exam_data['archived_at'])); ?></p>
                                    <?php if ($exam_data['archived_by_name']): ?>
                                        <p>Arxivləyən: <?php echo htmlspecialchars($exam_data['archived_by_name']); ?></p>
                                    <?php endif; ?>
                                </div>
                                <svg data-accordion-icon class="w-6 h-6 text-gray-500 transition-transform transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                            </div>
                        </div>
                    </button>
                    <div data-accordion-content class="hidden">
                        <div class="p-4 border-t border-gray-200">
                             <a href="export_archived_result.php?exam_id=<?php echo $exam_data['exam_id']; ?>" target="_blank" class="inline-flex items-center px-3 py-2 mb-4 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                                Nəticəni Çıxart (PDF)
                            </a>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">№</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tələbə</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Bal</th>
                                            <?php if (is_admin()): ?>
                                                <th scope="col" class="relative px-6 py-3">
                                                    <span class="sr-only">Əməliyyatlar</span>
                                                </th>
                                            <?php endif; ?>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <?php $counter = 1; foreach ($exam_data['students'] as $student): ?>
                                            <tr>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo $counter++; ?></td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($student['student_name']); ?></div>
                                                    <div class="text-sm text-gray-500"><?php echo htmlspecialchars($student['student_username']); ?></div>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800 font-semibold"><?php echo number_format($student['score_earned'], 2); ?></td>
                                                <?php if (is_admin()): ?>
                                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                        <a href="?delete_archive=<?php echo $student['id_archive']; ?>" class="text-red-600 hover:text-red-900" onclick="return confirm('Bu arxivlənmiş nəticəni BİRDƏFƏLİK silmək istədiyinizə əminsiniz? Bu əməliyyat geri alına bilməz!')">
                                                            Sil
                                                        </a>
                                                    </td>
                                                <?php endif; ?>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const accordionContainer = document.getElementById('accordion-container');
    if (accordionContainer) {
        accordionContainer.addEventListener('click', function (e) {
            const header = e.target.closest('[data-accordion-header]');
            if (!header) return;

            const content = header.nextElementSibling;
            const icon = header.querySelector('[data-accordion-icon]');

            if (content.classList.contains('hidden')) {
                content.classList.remove('hidden');
                if(icon) icon.classList.add('rotate-180');
            } else {
                content.classList.add('hidden');
                if(icon) icon.classList.remove('rotate-180');
            }
        });
    }
});
</script>
<?php include_once "../includes/footer.php"; ?>