<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once "includes/db.php";
require_once "includes/auth.php";

$pageTitle = "Giriş";
$loginError = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login'])) {
    $database = new Database();
    $db = $database->getConnection();

    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (loginUser($username, $password, $db)) {
        if ($_SESSION['role'] != 'student') {
            header("Location: admin/dashboard.php");
        } else {
            header("Location: student/dashboard.php");
        }
        exit();
    } else {
        $loginError = "İstifadəçi adı və ya şifrə yanlışdır!";
    }
}
?>

<!DOCTYPE html>
<html lang="az">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - ETS</title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Material Icons -->
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Inter', sans-serif;
        }

        .material-input {
            position: relative;
            margin-bottom: 1.5rem;
        }

        .material-input input {
            width: 100%;
            padding: 12px 16px 12px 48px;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 15px;
            transition: all 0.3s ease;
            background-color: #f9fafb;
        }

        .material-input input:focus {
            outline: none;
            border-color: #6366f1;
            background-color: white;
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
        }

        .material-input label {
            position: absolute;
            left: 48px;
            top: 13px;
            color: #6b7280;
            font-size: 15px;
            transition: all 0.3s ease;
            pointer-events: none;
        }

        .material-input input:focus + label,
        .material-input input:not(:placeholder-shown) + label {
            top: -10px;
            left: 12px;
            font-size: 12px;
            color: #6366f1;
            background: white;
            padding: 0 6px;
            font-weight: 600;
        }

        .material-input .icon {
            position: absolute;
            left: 16px;
            top: 12px;
            color: #9ca3af;
            transition: all 0.3s ease;
        }

        .material-input input:focus ~ .icon {
            color: #6366f1;
        }

        .material-button {
            position: relative;
            overflow: hidden;
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            color: white;
            border: none;
            border-radius: 12px;
            padding: 14px 24px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
            box-shadow: 0 4px 14px rgba(99, 102, 241, 0.4);
        }

        .material-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(99, 102, 241, 0.5);
        }

        .material-button:active {
            transform: translateY(0);
        }

        .material-button::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.3);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }

        .material-button:active::before {
            width: 300px;
            height: 300px;
        }

        .glass-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        .gradient-bg::before {
            content: '';
            position: absolute;
            width: 600px;
            height: 600px;
            background: radial-gradient(circle, rgba(139, 92, 246, 0.3) 0%, transparent 70%);
            top: -200px;
            right: -200px;
            animation: float 6s ease-in-out infinite;
        }

        .gradient-bg::after {
            content: '';
            position: absolute;
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, rgba(99, 102, 241, 0.3) 0%, transparent 70%);
            bottom: -150px;
            left: -150px;
            animation: float 8s ease-in-out infinite reverse;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(5deg); }
        }

        .logo-container {
            animation: fadeInDown 0.8s ease-out;
        }

        .form-container {
            animation: fadeInUp 0.8s ease-out;
        }

        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body>
    <div class="gradient-bg">
        <div class="container mx-auto px-4 relative z-10">
            <div class="max-w-md mx-auto">
                <!-- Logo and Title -->
                <div class="text-center mb-8 logo-container">
                    <div class="flex justify-center mb-6">
                        <img src="images/logobbu.jpg" alt="BBU Logo" class="w-32 h-32 rounded-full shadow-2xl border-4 border-white">
                    </div>
                    <h1 class="text-4xl font-bold text-white mb-2">İngilis Dili Test Sistemi</h1>
                    <p class="text-xl text-indigo-100 font-semibold tracking-wider">ETS</p>
                    <div class="w-20 h-1 bg-white mx-auto mt-4 rounded-full"></div>
                </div>

                <!-- Login Card -->
                <div class="glass-card rounded-3xl p-8 shadow-2xl form-container">
                    <h2 class="text-2xl font-bold text-gray-800 mb-6 text-center">ETS</h2>

                    <?php if($loginError): ?>
                        <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6 rounded-lg">
                            <div class="flex items-center">
                                <span class="material-icons text-red-500 mr-3">error</span>
                                <p class="text-red-700 font-medium"><?php echo $loginError; ?></p>
                            </div>
                        </div>
                    <?php endif; ?>

                    <form method="post" action="">
                        <!-- Username Input -->
                        <div class="material-input">
                            <span class="material-icons icon">person</span>
                            <input
                                type="text"
                                id="username"
                                name="username"
                                placeholder=" "
                                required
                            >
                            <label for="username">İstifadəçi adı</label>
                        </div>

                        <!-- Password Input -->
                        <div class="material-input">
                            <span class="material-icons icon">lock</span>
                            <input
                                type="password"
                                id="password"
                                name="password"
                                placeholder=" "
                                required
                            >
                            <label for="password">Şifrə</label>
                        </div>

                        <!-- Submit Button -->
                        <button type="submit" name="login" class="material-button">
                            <span class="flex items-center justify-center">
                                <span class="material-icons mr-2">login</span>
                                Daxil Ol
                            </span>
                        </button>
                    </form>

                    <!-- Footer -->
                    <div class="mt-6 text-center">
                        <p class="text-sm text-gray-500">
                            <span class="material-icons text-xs align-middle">shield</span>
                            Təhlükəsiz giriş sistemi
                        </p>
                    </div>
                </div>

                <!-- Copyright -->
                <div class="text-center mt-6">
                    <p class="text-white text-sm opacity-80 mb-2">
                        © <?php echo date('Y'); ?> Bakı Biznes Universiteti. Bütün hüquqlar qorunur.
                    </p>
                    <p class="text-white text-xs opacity-70">
                        Qurucu:
                        <a href="https://javadhuseynli.github.io" target="_blank" class="font-semibold hover:text-indigo-200 transition-colors duration-300 underline decoration-dotted">
                            Javad Hüseynli
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
