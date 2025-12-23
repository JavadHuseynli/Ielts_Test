<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
require_once "../includes/db.php";
require_once "../includes/auth.php";

checkLogin();
if (!is_admin()) {
    header("Location: dashboard.php?error=access_denied");
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Archive group - MUST BE BEFORE header.php include to allow redirect
if (isset($_GET['archive']) && is_numeric($_GET['archive'])) {
    $id = $_GET['archive'];
    $filter = isset($_GET['filter']) ? $_GET['filter'] : 'active';

    try {
        $query = "UPDATE student_group SET is_archived = 1 WHERE id_student_group = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(":id", $id);

        if ($stmt->execute()) {
            header("Location: groups.php?filter=" . $filter . "&success=archived");
            exit();
        }
    } catch (PDOException $e) {
        // Will handle error after header.php include
    }
}

// Unarchive group - MUST BE BEFORE header.php include to allow redirect
if (isset($_GET['unarchive']) && is_numeric($_GET['unarchive'])) {
    $id = $_GET['unarchive'];
    $filter = isset($_GET['filter']) ? $_GET['filter'] : 'active';

    try {
        $query = "UPDATE student_group SET is_archived = 0 WHERE id_student_group = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(":id", $id);

        if ($stmt->execute()) {
            header("Location: groups.php?filter=" . $filter . "&success=unarchived");
            exit();
        }
    } catch (PDOException $e) {
        // Will handle error after header.php include
    }
}

// Bulk archive all active groups - MUST BE BEFORE header.php include to allow redirect
if (isset($_GET['archive_all']) && $_GET['archive_all'] == 'confirm') {
    try {
        $query = "UPDATE student_group SET is_archived = 1 WHERE is_archived = 0";
        $stmt = $db->prepare($query);

        if ($stmt->execute()) {
            $affected_rows = $stmt->rowCount();
            header("Location: groups.php?filter=active&success=bulk_archived&count=" . $affected_rows);
            exit();
        }
    } catch (PDOException $e) {
        // Will handle error after header.php include
    }
}

// Delete group (only archived groups) - MUST BE BEFORE header.php include to allow redirect
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = $_GET['delete'];
    $filter = isset($_GET['filter']) ? $_GET['filter'] : 'active';

    try {
        // Check if group is archived
        $query = "SELECT is_archived, (SELECT COUNT(*) FROM users WHERE group_id = :id) as student_count FROM student_group WHERE id_student_group = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();
        $group = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$group['is_archived']) {
            header("Location: groups.php?filter=" . $filter . "&error=not_archived");
            exit();
        } elseif ($group['student_count'] > 0) {
            header("Location: groups.php?filter=" . $filter . "&error=has_students");
            exit();
        } else {
            $query = "DELETE FROM student_group WHERE id_student_group = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(":id", $id);

            if ($stmt->execute()) {
                header("Location: groups.php?filter=" . $filter . "&success=deleted");
                exit();
            }
        }
    } catch (PDOException $e) {
        // Will handle error after header.php include
    }
}

$pageTitle = "Qrup İdarəetməsi";
include_once "../includes/header.php";

$message = "";

// Add group
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_group'])) {
    $group_number = trim($_POST['group_number']);

    if (!empty($group_number)) {
        try {
            $query = "INSERT INTO student_group (group_number) VALUES (:group_number)";
            $stmt = $db->prepare($query);
            $stmt->bindParam(":group_number", $group_number);

            if ($stmt->execute()) {
                $message = '<div class="bg-gradient-to-r from-green-900 to-emerald-900 border-l-4 border-green-400 text-green-100 p-4 rounded-lg mb-6 animate-fade-in font-bold"><span class="text-green-400">✓</span> Qrup uğurla əlavə edildi</div>';
            } else {
                $message = '<div class="bg-gradient-to-r from-red-900 to-pink-900 border-l-4 border-red-400 text-red-100 p-4 rounded-lg mb-6 animate-fade-in font-bold"><span class="text-red-400">✗</span> Xəta baş verdi</div>';
            }
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $message = '<div class="bg-gradient-to-r from-red-900 to-pink-900 border-l-4 border-red-400 text-red-100 p-4 rounded-lg mb-6 animate-fade-in font-bold"><span class="text-red-400">✗</span> Bu qrup artıq mövcuddur</div>';
            } else {
                $message = '<div class="bg-gradient-to-r from-red-900 to-pink-900 border-l-4 border-red-400 text-red-100 p-4 rounded-lg mb-6 animate-fade-in font-bold"><span class="text-red-400">✗</span> ' . $e->getMessage() . '</div>';
            }
        }
    }
}

// Handle success and error messages from redirects
if (isset($_GET['success'])) {
    switch ($_GET['success']) {
        case 'archived':
            $message = '<div class="bg-gradient-to-r from-green-900 to-emerald-900 border-l-4 border-green-400 text-green-100 p-4 rounded-lg mb-6 animate-fade-in font-bold"><span class="text-green-400">✓</span> Qrup arxivləşdirildi</div>';
            break;
        case 'unarchived':
            $message = '<div class="bg-gradient-to-r from-green-900 to-emerald-900 border-l-4 border-green-400 text-green-100 p-4 rounded-lg mb-6 animate-fade-in font-bold"><span class="text-green-400">✓</span> Qrup aktiv edildi</div>';
            break;
        case 'deleted':
            $message = '<div class="bg-gradient-to-r from-green-900 to-emerald-900 border-l-4 border-green-400 text-green-100 p-4 rounded-lg mb-6 animate-fade-in font-bold"><span class="text-green-400">✓</span> Qrup silindi</div>';
            break;
        case 'bulk_archived':
            $count = isset($_GET['count']) ? intval($_GET['count']) : 0;
            $message = '<div class="bg-gradient-to-r from-green-900 to-emerald-900 border-l-4 border-green-400 text-green-100 p-4 rounded-lg mb-6 animate-fade-in font-bold"><span class="text-green-400">✓</span> ' . $count . ' qrup arxivləşdirildi</div>';
            break;
    }
}

if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'not_archived':
            $message = '<div class="bg-gradient-to-r from-amber-900 to-orange-900 border-l-4 border-amber-400 text-amber-100 p-4 rounded-lg mb-6 animate-fade-in font-bold"><span class="text-amber-400">⚠</span> Yalnız arxivləşdirilmiş qrupları silmək mümkündür</div>';
            break;
        case 'has_students':
            $message = '<div class="bg-gradient-to-r from-amber-900 to-orange-900 border-l-4 border-amber-400 text-amber-100 p-4 rounded-lg mb-6 animate-fade-in font-bold"><span class="text-amber-400">⚠</span> Qrupda tələbələr var, silmək mümkün deyil</div>';
            break;
    }
}

// Filter handling
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'active';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

$conditions = [];

// Archive status filter
if ($filter == 'archived') {
    $conditions[] = 'sg.is_archived = 1';
} elseif ($filter == 'active') {
    $conditions[] = 'sg.is_archived = 0';
}
// If 'all', no archive condition is added

// Search filter
if (!empty($search)) {
    $conditions[] = 'sg.group_number LIKE :search';
}

$filter_condition = '';
if (count($conditions) > 0) {
    $filter_condition = 'WHERE ' . implode(' AND ', $conditions);
}

// Pagination
$items_per_page = 10; // You can adjust this number
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $items_per_page;

// Count total groups with filter
$count_query = "SELECT COUNT(*) as total FROM student_group sg " . $filter_condition;
$count_stmt = $db->prepare($count_query);
if (!empty($search)) {
    $search_param = '%' . $search . '%';
    $count_stmt->bindParam(':search', $search_param);
}
$count_stmt->execute();
$total_groups_count = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_groups_count / $items_per_page);

// Fetch groups with pagination and filter
$query = "SELECT sg.id_student_group, sg.group_number, sg.is_archived, COUNT(u.id_users) as student_count
          FROM student_group sg
          LEFT JOIN users u ON sg.id_student_group = u.group_id
          " . $filter_condition . "
          GROUP BY sg.id_student_group
          ORDER BY sg.group_number
          LIMIT :limit OFFSET :offset";
$stmt = $db->prepare($query);
if (!empty($search)) {
    $search_param = '%' . $search . '%';
    $stmt->bindParam(':search', $search_param);
}
$stmt->bindParam(':limit', $items_per_page, PDO::PARAM_INT);
$stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$groups = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate stats (only for active groups, excluding archived)
$all_groups_query = "SELECT sg.id_student_group, sg.group_number, COUNT(u.id_users) as student_count
                     FROM student_group sg
                     LEFT JOIN users u ON sg.id_student_group = u.group_id
                     WHERE sg.is_archived = 0
                     GROUP BY sg.id_student_group";
$all_groups_stmt = $db->prepare($all_groups_query);
$all_groups_stmt->execute();
$all_groups = $all_groups_stmt->fetchAll(PDO::FETCH_ASSOC);

$totalGroups = count($all_groups);
$totalStudents = array_sum(array_column($all_groups, 'student_count'));
$avgStudents = $totalGroups > 0 ? round($totalStudents / $totalGroups, 1) : 0;
?>

<div class="bg-gray-100 min-h-screen p-4 sm:p-6 lg:p-8">
    <div class="max-w-7xl mx-auto">

        <!-- Header Card -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                <div class="mb-4 md:mb-0">
                    <h1 class="text-2xl font-bold text-gray-800">Qrup İdarəetməsi</h1>
                    <p class="text-md text-gray-600 mt-1">Tələbə qruplarını idarə edin və yenilərini yaradın.</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <button onclick="document.getElementById('addGroupModal').classList.remove('hidden')" class="px-4 py-2 bg-indigo-600 text-white rounded-md font-semibold hover:bg-indigo-700 transition-all text-sm flex items-center space-x-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                        <span>Yeni Qrup Əlavə Et</span>
                    </button>
                    <?php if ($filter == 'active'): ?>
                        <?php
                        // Count active groups for the button
                        $active_count_query = "SELECT COUNT(*) as total FROM student_group WHERE is_archived = 0";
                        $active_count_stmt = $db->prepare($active_count_query);
                        $active_count_stmt->execute();
                        $active_count = $active_count_stmt->fetch(PDO::FETCH_ASSOC)['total'];

                        if ($active_count > 0):
                        ?>
                            <a href="?archive_all=confirm&filter=active" onclick="return confirm('Bütün aktiv qrupları (<?php echo $active_count; ?> qrup) arxivləşdirmək istədiyinizə əminsiniz?')" class="px-4 py-2 bg-yellow-600 text-white rounded-md font-semibold hover:bg-yellow-700 transition-all text-sm flex items-center space-x-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path></svg>
                                <span>Hamısını Arxivləşdir</span>
                            </a>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Filter Tabs and Search -->
        <div class="bg-white rounded-lg shadow-md p-4 mb-6">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div class="flex flex-wrap gap-2">
                    <a href="?filter=active<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="px-4 py-2 rounded-md text-sm font-medium <?php echo $filter == 'active' ? 'bg-indigo-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?> transition-all">
                        Aktiv Qruplar
                    </a>
                    <a href="?filter=archived<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="px-4 py-2 rounded-md text-sm font-medium <?php echo $filter == 'archived' ? 'bg-indigo-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?> transition-all">
                        Arxivləşdirilmiş Qruplar
                    </a>
                    <a href="?filter=all<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="px-4 py-2 rounded-md text-sm font-medium <?php echo $filter == 'all' ? 'bg-indigo-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?> transition-all">
                        Bütün Qruplar
                    </a>
                </div>
                <div class="flex-1 md:max-w-xs">
                    <form method="get" action="" class="flex gap-2">
                        <input type="hidden" name="filter" value="<?php echo htmlspecialchars($filter); ?>">
                        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Qrup nömrəsi axtar..." class="flex-1 px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm">
                        <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 transition-all text-sm">
                            Axtar
                        </button>
                        <?php if (!empty($search)): ?>
                            <a href="?filter=<?php echo $filter; ?>" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 transition-all text-sm">
                                Təmizlə
                            </a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        </div>

        <?php if (!empty($message)): ?>
        <div class="mb-6">
            <?php
            // Simplified message display based on content
            $is_success = strpos($message, 'uğurla') !== false || strpos($message, 'silindi') !== false;
            $is_warning = strpos($message, 'mümkün deyil') !== false;
            
            if ($is_success) {
                $message_type = 'success';
            } elseif ($is_warning) {
                $message_type = 'info'; // Or a 'warning' type if you add one
            } else {
                $message_type = 'error';
            }
            
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

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 mb-6">
            <div class="bg-white rounded-lg shadow-md p-5">
                <h3 class="text-sm font-medium text-gray-500">Ümumi Qruplar</h3>
                <p class="mt-1 text-3xl font-semibold text-gray-900"><?php echo $totalGroups; ?></p>
            </div>
            <div class="bg-white rounded-lg shadow-md p-5">
                <h3 class="text-sm font-medium text-gray-500">Ümumi Tələbələr</h3>
                <p class="mt-1 text-3xl font-semibold text-gray-900"><?php echo $totalStudents; ?></p>
            </div>
            <div class="bg-white rounded-lg shadow-md p-5">
                <h3 class="text-sm font-medium text-gray-500">Qrup Başına Orta Say</h3>
                <p class="mt-1 text-3xl font-semibold text-gray-900"><?php echo $avgStudents; ?></p>
            </div>
        </div>

        <!-- Groups Table Card -->
        <div class="bg-white rounded-lg shadow-md">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Qrup Nömrəsi</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tələbə Sayı</th>
                            <th scope="col" class="relative px-6 py-3">
                                <span class="sr-only">Əməliyyatlar</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (empty($groups)): ?>
                            <tr>
                                <td colspan="4" class="px-6 py-12 text-center">
                                    <h3 class="text-lg font-medium text-gray-900">Qrup Tapılmadı</h3>
                                    <p class="mt-1 text-sm text-gray-500">Sistemdə heç bir qrup qeydiyyatdan keçməyib.</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($groups as $group): ?>
                                <tr class="<?php echo $group['is_archived'] ? 'bg-gray-50' : ''; ?>">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-500">#<?php echo $group['id_student_group']; ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">
                                        <?php echo htmlspecialchars($group['group_number']); ?>
                                        <?php if ($group['is_archived']): ?>
                                            <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-200 text-gray-800">Arxiv</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo $group['student_count']; ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                                        <a href="users.php?group=<?php echo $group['id_student_group']; ?>" class="text-indigo-600 hover:text-indigo-900">Tələbələrə Bax</a>

                                        <?php if ($group['student_count'] > 0): ?>
                                            <a href="export_group.php?group=<?php echo $group['id_student_group']; ?>" class="text-green-600 hover:text-green-900">PDF Export</a>
                                        <?php endif; ?>

                                        <?php if (!$group['is_archived']): ?>
                                            <a href="?archive=<?php echo $group['id_student_group']; ?>&filter=<?php echo $filter; ?>" class="text-yellow-600 hover:text-yellow-900" onclick="return confirm('Bu qrupu arxivləşdirmək istədiyinizə əminsiniz?')">Arxivləşdir</a>
                                        <?php else: ?>
                                            <a href="?unarchive=<?php echo $group['id_student_group']; ?>&filter=<?php echo $filter; ?>" class="text-green-600 hover:text-green-900" onclick="return confirm('Bu qrupu aktiv etmək istədiyinizə əminsiniz?')">Aktiv Et</a>
                                        <?php endif; ?>

                                        <?php if ($group['is_archived'] && $group['student_count'] == 0): ?>
                                            <a href="?delete=<?php echo $group['id_student_group']; ?>&filter=<?php echo $filter; ?>" class="text-red-600 hover:text-red-900" onclick="return confirm('Bu qrupu silmək istədiyinizə əminsiniz?')">Sil</a>
                                        <?php elseif ($group['is_archived']): ?>
                                            <span class="text-gray-400 cursor-not-allowed" title="Qrupda tələbə olduğu üçün silmək olmaz">Sil</span>
                                        <?php endif; ?>
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
        <?php
        $base_url = '?filter=' . $filter;
        if (!empty($search)) {
            $base_url .= '&search=' . urlencode($search);
        }
        ?>
        <div class="py-4 flex items-center justify-between">
            <div class="flex-1 flex justify-between sm:hidden">
                <a href="<?php echo $base_url; ?>&page=<?php echo max(1, $page-1); ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"> Əvvəlki </a>
                <a href="<?php echo $base_url; ?>&page=<?php echo min($total_pages, $page+1); ?>" class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"> Növbəti </a>
            </div>
            <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                <div>
                    <p class="text-sm text-gray-700">
                        <span class="font-medium"><?php echo ($page - 1) * $items_per_page + 1; ?></span> -
                        <span class="font-medium"><?php echo min($page * $items_per_page, $total_groups_count); ?></span> göstərilir
                        cəmi <span class="font-medium"><?php echo $total_groups_count; ?></span> qrupdan
                    </p>
                </div>
                <div>
                    <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                        <?php if ($page > 1): ?>
                            <a href="<?php echo $base_url; ?>&page=<?php echo $page - 1; ?>" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                <span class="sr-only">Əvvəlki</span>
                                <!-- Heroicon name: solid/chevron-left -->
                                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                                </svg>
                            </a>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <a href="<?php echo $base_url; ?>&page=<?php echo $i; ?>" class="<?php echo $i == $page ? 'z-10 bg-indigo-50 border-indigo-500 text-indigo-600' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'; ?> relative inline-flex items-center px-4 py-2 border text-sm font-medium">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>

                        <?php if ($page < $total_pages): ?>
                            <a href="<?php echo $base_url; ?>&page=<?php echo $page + 1; ?>" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                <span class="sr-only">Növbəti</span>
                                <!-- Heroicon name: solid/chevron-right -->
                                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10l-3.293-3.293a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                                </svg>
                            </a>
                        <?php endif; ?>
                    </nav>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Add Group Modal -->
<div id="addGroupModal" class="hidden fixed inset-0 bg-gray-500 bg-opacity-75 z-50 flex items-center justify-center p-4" onclick="if(event.target === this) this.classList.add('hidden')">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-md mx-auto" onclick="event.stopPropagation()">
        <form method="post" action="">
            <div class="p-6">
                <h3 class="text-lg font-medium text-gray-900">Yeni Qrup Yarat</h3>
                <div class="mt-4">
                    <label for="group_number" class="block text-sm font-medium text-gray-700">Qrup Nömrəsi</label>
                    <input type="text" name="group_number" id="group_number" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" placeholder="Məsələn, 642a1" required>
                </div>
            </div>
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button type="submit" name="add_group" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm">
                    Əlavə Et
                </button>
                <button type="button" onclick="document.getElementById('addGroupModal').classList.add('hidden')" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:mt-0 sm:w-auto sm:text-sm">
                    Ləğv Et
                </button>
            </div>
        </form>
    </div>
</div>

<?php include_once "../includes/footer.php"; ?>
