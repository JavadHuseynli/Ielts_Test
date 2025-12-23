<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
require_once "../includes/db.php";
require_once "../includes/auth.php";

checkLogin();

// Only admin can view archived subjects
if (!is_admin()) {
    header("Location: dashboard.php?error=access_denied");
    exit();
}

$database = new Database();
$db = $database->getConnection();

$pageTitle = "Arxivlənmiş Fənnlər";
include "../includes/header.php";

// Get archived subjects
$query = "SELECT * FROM archived_subjects ORDER BY archived_at DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$archived_subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="min-h-screen bg-gradient-to-br from-blue-50 via-white to-purple-50 py-8 px-4">
    <div class="max-w-7xl mx-auto">
        <!-- Header -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 mb-6">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 mb-2">Arxivlənmiş Fənnlər</h1>
                    <p class="text-gray-600">Silinmiş fənnlərin arxivi</p>
                </div>
                <div class="mt-4 lg:mt-0">
                    <a href="subjects.php" class="px-4 py-2 bg-gray-600 text-white rounded-xl font-semibold hover:bg-gray-700 transition-all inline-flex items-center space-x-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                        <span>Geriyə</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Statistics -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl p-6 text-white">
                <p class="text-sm font-medium opacity-90">Ümumi Fənnlər</p>
                <p class="text-4xl font-bold mt-2"><?php echo count($archived_subjects); ?></p>
            </div>
            <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-2xl p-6 text-white">
                <p class="text-sm font-medium opacity-90">Ümumi Suallar</p>
                <p class="text-4xl font-bold mt-2">
                    <?php echo array_sum(array_column($archived_subjects, 'total_questions')); ?>
                </p>
            </div>
            <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-2xl p-6 text-white">
                <p class="text-sm font-medium opacity-90">Ümumi İmtahanlar</p>
                <p class="text-4xl font-bold mt-2">
                    <?php echo array_sum(array_column($archived_subjects, 'total_exams')); ?>
                </p>
            </div>
        </div>

        <!-- Subjects List -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gradient-to-r from-blue-600 to-purple-600 text-white">
                        <tr>
                            <th class="px-6 py-4 text-left text-sm font-semibold">ID</th>
                            <th class="px-6 py-4 text-left text-sm font-semibold">Fənn Adı</th>
                            <th class="px-6 py-4 text-left text-sm font-semibold">Müddət (dəq)</th>
                            <th class="px-6 py-4 text-left text-sm font-semibold">Suallar</th>
                            <th class="px-6 py-4 text-left text-sm font-semibold">İmtahanlar</th>
                            <th class="px-6 py-4 text-left text-sm font-semibold">Arxivlənmə Tarixi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php if (count($archived_subjects) == 0): ?>
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                <svg class="w-16 h-16 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                                </svg>
                                <p class="text-lg font-medium">Arxivlənmiş fənn yoxdur</p>
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($archived_subjects as $subject): ?>
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 text-sm font-medium text-gray-900">
                                #<?php echo $subject['id_subject']; ?>
                            </td>
                            <td class="px-6 py-4 text-sm font-medium text-gray-900">
                                <?php echo htmlspecialchars($subject['subjectname']); ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-700">
                                <?php echo $subject['timer']; ?> dəqiqə
                            </td>
                            <td class="px-6 py-4 text-sm">
                                <span class="px-3 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-800">
                                    <?php echo $subject['total_questions']; ?> sual
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm">
                                <span class="px-3 py-1 rounded-full text-xs font-semibold bg-purple-100 text-purple-800">
                                    <?php echo $subject['total_exams']; ?> imtahan
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                <?php echo date('d.m.Y H:i', strtotime($subject['archived_at'])); ?>
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
