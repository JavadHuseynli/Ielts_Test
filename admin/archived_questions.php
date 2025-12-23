<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
require_once "../includes/db.php";
require_once "../includes/auth.php";

checkLogin();

// Only admin can view archived questions
if (!is_admin()) {
    header("Location: dashboard.php?error=access_denied");
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Get filter parameters
$subject_filter = isset($_GET['subject']) ? intval($_GET['subject']) : 0;
$type_filter = isset($_GET['type']) ? $_GET['type'] : '';

// Build query
$query = "SELECT * FROM archived_questions WHERE 1=1";
$params = [];

if ($subject_filter > 0) {
    $query .= " AND subject_id = :subject_id";
    $params[':subject_id'] = $subject_filter;
}

if (!empty($type_filter)) {
    $query .= " AND question_var = :question_var";
    $params[':question_var'] = $type_filter;
}

$query .= " ORDER BY archived_at DESC";

$stmt = $db->prepare($query);
$stmt->execute($params);
$archived_questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all subjects for filter
$subjectsQuery = "SELECT DISTINCT subject_id, subject_name FROM archived_questions ORDER BY subject_name";
$subjectsStmt = $db->prepare($subjectsQuery);
$subjectsStmt->execute();
$subjects = $subjectsStmt->fetchAll(PDO::FETCH_ASSOC);

include "../includes/header.php";
?>

<div class="min-h-screen bg-gradient-to-br from-blue-50 via-white to-purple-50 py-8 px-4">
    <div class="max-w-7xl mx-auto">
        <!-- Header -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 mb-6">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 mb-2">Arxivlənmiş Suallar</h1>
                    <p class="text-gray-600">Silinmiş sualların arxivi</p>
                </div>
                <div class="mt-4 lg:mt-0">
                    <a href="dashboard.php" class="px-4 py-2 bg-gray-600 text-white rounded-xl font-semibold hover:bg-gray-700 transition-all inline-flex items-center space-x-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                        <span>Geriyə</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 mb-6">
            <form method="GET" class="flex flex-wrap gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Fənn</label>
                    <select name="subject" class="px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500">
                        <option value="0">Hamısı</option>
                        <?php foreach ($subjects as $subject): ?>
                        <option value="<?php echo $subject['subject_id']; ?>" <?php echo $subject_filter == $subject['subject_id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($subject['subject_name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Növ</label>
                    <select name="type" class="px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500">
                        <option value="">Hamısı</option>
                        <option value="multiple" <?php echo $type_filter == 'multiple' ? 'selected' : ''; ?>>Çoxseçimli</option>
                        <option value="open" <?php echo $type_filter == 'open' ? 'selected' : ''; ?>>Açıq cavab</option>
                        <option value="matching" <?php echo $type_filter == 'matching' ? 'selected' : ''; ?>>Uyğunlaşdırma</option>
                    </select>
                </div>
                <div class="flex items-end">
                    <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-xl font-semibold hover:bg-blue-700 transition-all">
                        Filtrələ
                    </button>
                </div>
            </form>
        </div>

        <!-- Statistics -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl p-6 text-white">
                <p class="text-sm font-medium opacity-90">Ümumi Suallar</p>
                <p class="text-4xl font-bold mt-2"><?php echo count($archived_questions); ?></p>
            </div>
            <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-2xl p-6 text-white">
                <p class="text-sm font-medium opacity-90">Fənn Sayı</p>
                <p class="text-4xl font-bold mt-2"><?php echo count($subjects); ?></p>
            </div>
            <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-2xl p-6 text-white">
                <p class="text-sm font-medium opacity-90">Bu Ay</p>
                <p class="text-4xl font-bold mt-2">
                    <?php
                    $thisMonth = array_filter($archived_questions, function($q) {
                        return date('Y-m', strtotime($q['archived_at'])) == date('Y-m');
                    });
                    echo count($thisMonth);
                    ?>
                </p>
            </div>
        </div>

        <!-- Questions List -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gradient-to-r from-blue-600 to-purple-600 text-white">
                        <tr>
                            <th class="px-6 py-4 text-left text-sm font-semibold">ID</th>
                            <th class="px-6 py-4 text-left text-sm font-semibold">Sual Mətni</th>
                            <th class="px-6 py-4 text-left text-sm font-semibold">Fənn</th>
                            <th class="px-6 py-4 text-left text-sm font-semibold">Növ</th>
                            <th class="px-6 py-4 text-left text-sm font-semibold">Bal</th>
                            <th class="px-6 py-4 text-left text-sm font-semibold">Arxivlənmə Tarixi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php if (count($archived_questions) == 0): ?>
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                <svg class="w-16 h-16 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                                </svg>
                                <p class="text-lg font-medium">Arxivlənmiş sual yoxdur</p>
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($archived_questions as $question): ?>
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 text-sm font-medium text-gray-900">
                                #<?php echo $question['id_question_text']; ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-700">
                                <?php echo htmlspecialchars(substr($question['question_text'], 0, 100)) . (strlen($question['question_text']) > 100 ? '...' : ''); ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-700">
                                <?php echo htmlspecialchars($question['subject_name']); ?>
                            </td>
                            <td class="px-6 py-4 text-sm">
                                <?php
                                $typeColors = [
                                    'multiple' => 'bg-blue-100 text-blue-800',
                                    'open' => 'bg-green-100 text-green-800',
                                    'matching' => 'bg-purple-100 text-purple-800'
                                ];
                                $typeNames = [
                                    'multiple' => 'Çoxseçimli',
                                    'open' => 'Açıq cavab',
                                    'matching' => 'Uyğunlaşdırma'
                                ];
                                ?>
                                <span class="px-3 py-1 rounded-full text-xs font-semibold <?php echo $typeColors[$question['question_var']]; ?>">
                                    <?php echo $typeNames[$question['question_var']]; ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm font-medium text-gray-900">
                                <?php echo $question['question_score']; ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                <?php echo date('d.m.Y H:i', strtotime($question['archived_at'])); ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include "../includes/footer.php"; ?>
