<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
if (!isset($pageTitle)) {
    $pageTitle = "Təhsil Sistemi";
}
?>
<!DOCTYPE html>
<html lang="az" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.js"></script>

    <!-- Animate.css -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>

    <!-- Custom Config -->
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#f0f9ff',
                            100: '#e0f2fe',
                            200: '#bae6fd',
                            300: '#7dd3fc',
                            400: '#38bdf8',
                            500: '#0ea5e9',
                            600: '#0284c7',
                            700: '#0369a1',
                            800: '#075985',
                            900: '#0c4a6e',
                        },
                        accent: {
                            pink: '#ec4899',
                            purple: '#a855f7',
                            orange: '#f97316',
                            teal: '#14b8a6',
                        }
                    },
                    backgroundImage: {
                        'gradient-radial': 'radial-gradient(var(--tw-gradient-stops))',
                        'gradient-mesh': 'radial-gradient(at 40% 20%, hsla(28,100%,74%,1) 0px, transparent 50%), radial-gradient(at 80% 0%, hsla(189,100%,56%,1) 0px, transparent 50%), radial-gradient(at 0% 50%, hsla(355,100%,93%,1) 0px, transparent 50%), radial-gradient(at 80% 50%, hsla(340,100%,76%,1) 0px, transparent 50%), radial-gradient(at 0% 100%, hsla(22,100%,77%,1) 0px, transparent 50%), radial-gradient(at 80% 100%, hsla(242,100%,70%,1) 0px, transparent 50%), radial-gradient(at 0% 0%, hsla(343,100%,76%,1) 0px, transparent 50%)',
                    },
                    animation: {
                        'float': 'float 6s ease-in-out infinite',
                        'slide-up': 'slideUp 0.5s ease-out',
                        'fade-in': 'fadeIn 0.6s ease-out',
                        'scale-in': 'scaleIn 0.3s ease-out',
                        'spin-slow': 'spin 8s linear infinite',
                        'pulse-slow': 'pulse 4s cubic-bezier(0.4, 0, 0.6, 1) infinite',
                        'gradient': 'gradient 8s ease infinite',
                    },
                    keyframes: {
                        float: {
                            '0%, 100%': { transform: 'translateY(0px)' },
                            '50%': { transform: 'translateY(-20px)' },
                        },
                        slideUp: {
                            '0%': { transform: 'translateY(100px)', opacity: '0' },
                            '100%': { transform: 'translateY(0)', opacity: '1' },
                        },
                        fadeIn: {
                            '0%': { opacity: '0' },
                            '100%': { opacity: '1' },
                        },
                        scaleIn: {
                            '0%': { transform: 'scale(0.9)', opacity: '0' },
                            '100%': { transform: 'scale(1)', opacity: '1' },
                        },
                        gradient: {
                            '0%, 100%': { backgroundPosition: '0% 50%' },
                            '50%': { backgroundPosition: '100% 50%' },
                        },
                    }
                }
            }
        }
    </script>

    <style>
        [x-cloak] { display: none !important; }

        /* Glassmorphism */
        .glass {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .glass-dark {
            background: rgba(15, 23, 42, 0.7);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        /* Animated gradient background */
        .animated-gradient {
            background: linear-gradient(-45deg, #ee7752, #e73c7e, #23a6d5, #23d5ab);
            background-size: 400% 400%;
            animation: gradient 15s ease infinite;
        }

        /* Smooth transitions */
        * {
            transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f5f9;
        }

        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

        /* Hover glow effect */
        .hover-glow {
            transition: all 0.3s ease;
        }

        .hover-glow:hover {
            box-shadow: 0 0 30px rgba(14, 165, 233, 0.5);
            transform: translateY(-2px);
        }

        /* Sidebar styles */
        .sidebar-item {
            position: relative;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .sidebar-item::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 0;
            height: 70%;
            background: linear-gradient(90deg, #0ea5e9, #06b6d4);
            border-radius: 0 10px 10px 0;
            transition: width 0.3s ease;
        }

        .sidebar-item:hover::before,
        .sidebar-item.active::before {
            width: 4px;
        }

        .sidebar-item:hover {
            background: linear-gradient(90deg, rgba(14, 165, 233, 0.1), transparent);
            padding-left: 1.5rem;
        }

        /* Card hover effect */
        .card-hover {
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        .card-hover:hover {
            transform: translateY(-8px) scale(1.02);
        }

        /* Gradient text */
        .gradient-text {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /* Shimmer effect */
        @keyframes shimmer {
            0% { background-position: -1000px 0; }
            100% { background-position: 1000px 0; }
        }

        .shimmer {
            animation: shimmer 2s infinite;
            background: linear-gradient(to right, transparent 0%, rgba(255,255,255,0.3) 50%, transparent 100%);
            background-size: 1000px 100%;
        }

        /* Floating particles background */
        .particles {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: -1;
        }

        .particle {
            position: absolute;
            width: 4px;
            height: 4px;
            background: rgba(14, 165, 233, 0.3);
            border-radius: 50%;
            animation: float-particle 20s infinite;
        }

        @keyframes float-particle {
            0%, 100% { transform: translateY(0) translateX(0); opacity: 0; }
            10% { opacity: 1; }
            90% { opacity: 1; }
            100% { transform: translateY(-100vh) translateX(100px); opacity: 0; }
        }
    </style>
</head>
<body class="h-full bg-gradient-to-br from-slate-50 via-blue-50 to-cyan-50" x-data="{
    sidebarOpen: true,
    mobileMenuOpen: false,
    darkMode: false
}">
    <!-- Floating Particles Background -->
    <div class="particles">
        <?php for($i = 0; $i < 15; $i++): ?>
        <div class="particle" style="left: <?php echo rand(0, 100); ?>%; animation-delay: <?php echo rand(0, 20); ?>s;"></div>
        <?php endfor; ?>
    </div>

    <div class="min-h-screen flex">
        <!-- Modern Sidebar with Glassmorphism -->
        <aside
            x-show="sidebarOpen || mobileMenuOpen"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="-translate-x-full opacity-0"
            x-transition:enter-end="translate-x-0 opacity-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="translate-x-0 opacity-100"
            x-transition:leave-end="-translate-x-full opacity-0"
            class="fixed inset-y-0 left-0 z-50 w-72 glass backdrop-blur-xl lg:translate-x-0 shadow-2xl"
            :class="{ 'translate-x-0': mobileMenuOpen, '-translate-x-full': !mobileMenuOpen }"
        >
            <div class="flex flex-col h-full">
                <!-- Logo with Animation -->
                <div class="flex items-center justify-between h-20 px-6 border-b border-white/20 animated-gradient">
                    <div class="flex items-center space-x-3 animate-fade-in">
                        <div class="relative">
                            <div class="absolute inset-0 bg-white/30 rounded-xl blur animate-pulse-slow"></div>
                            <svg class="w-10 h-10 text-white relative z-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                            </svg>
                        </div>
                        <span class="text-xl font-bold text-white tracking-wide">Təhsil Sistemi</span>
                    </div>
                    <button @click="mobileMenuOpen = false" class="lg:hidden text-white hover:bg-white/20 p-2 rounded-lg transition">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <!-- Navigation -->
                <nav class="flex-1 overflow-y-auto py-6 px-4 space-y-2">
                    <?php if(isset($_SESSION['user_id'])): ?>

                        <?php if(is_admin() || is_prorektor() || is_kafedra() || is_teacher()): ?>
                        <a href="../admin/dashboard.php" class="sidebar-item flex items-center px-4 py-3 text-gray-700 rounded-xl hover:text-primary-600 group">
                            <div class="p-2 bg-gradient-to-br from-blue-500 to-cyan-500 rounded-lg mr-3 group-hover:scale-110 transition-transform shadow-lg">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                                </svg>
                            </div>
                            <span class="font-medium">Əsas Səhifə</span>
                        </a>
                        <?php endif; ?>

                        <?php if(is_admin()): ?>
                        <a href="../admin/groups.php" class="sidebar-item flex items-center px-4 py-3 text-gray-700 rounded-xl hover:text-primary-600 group">
                            <div class="p-2 bg-gradient-to-br from-green-500 to-emerald-500 rounded-lg mr-3 group-hover:scale-110 transition-transform shadow-lg">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                </svg>
                            </div>
                            <span class="font-medium">Qruplar</span>
                        </a>

                        <a href="../admin/users.php" class="sidebar-item flex items-center px-4 py-3 text-gray-700 rounded-xl hover:text-primary-600 group">
                            <div class="p-2 bg-gradient-to-br from-purple-500 to-pink-500 rounded-lg mr-3 group-hover:scale-110 transition-transform shadow-lg">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                                </svg>
                            </div>
                            <span class="font-medium">İstifadəçilər</span>
                        </a>
                        <?php endif; ?>

                        <?php if(is_admin() || is_kafedra()): ?>
                        <a href="../admin/subjects.php" class="sidebar-item flex items-center px-4 py-3 text-gray-700 rounded-xl hover:text-primary-600 group">
                            <div class="p-2 bg-gradient-to-br from-orange-500 to-red-500 rounded-lg mr-3 group-hover:scale-110 transition-transform shadow-lg">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                                </svg>
                            </div>
                            <span class="font-medium">Fənlər</span>
                        </a>
                        <?php endif; ?>

                        <?php if(is_teacher() && isset($_SESSION['subject_id'])): ?>
                        <a href="../admin/questions.php?subject=<?php echo $_SESSION['subject_id']; ?>" class="sidebar-item flex items-center px-4 py-3 text-gray-700 rounded-xl hover:text-primary-600 group">
                            <div class="p-2 bg-gradient-to-br from-indigo-500 to-purple-500 rounded-lg mr-3 group-hover:scale-110 transition-transform shadow-lg">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <span class="font-medium">Suallarım</span>
                        </a>
                        <?php elseif(is_admin() || is_kafedra()): ?>
                        <a href="../admin/questions.php" class="sidebar-item flex items-center px-4 py-3 text-gray-700 rounded-xl hover:text-primary-600 group">
                            <div class="p-2 bg-gradient-to-br from-indigo-500 to-purple-500 rounded-lg mr-3 group-hover:scale-110 transition-transform shadow-lg">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <span class="font-medium">Suallar</span>
                        </a>
                        <?php endif; ?>

                        <?php if(is_admin() || is_prorektor()): ?>
                        <a href="../admin/exams.php" class="sidebar-item flex items-center px-4 py-3 text-gray-700 rounded-xl hover:text-primary-600 group">
                            <div class="p-2 bg-gradient-to-br from-amber-500 to-orange-500 rounded-lg mr-3 group-hover:scale-110 transition-transform shadow-lg">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                                </svg>
                            </div>
                            <span class="font-medium">İmtahanlar</span>
                        </a>
                        <?php endif; ?>

                        <?php if(is_admin() || is_prorektor() || is_kafedra()): ?>
                        <a href="../admin/exam_results.php" class="sidebar-item flex items-center px-4 py-3 text-gray-700 rounded-xl hover:text-primary-600 group">
                            <div class="p-2 bg-gradient-to-br from-teal-500 to-cyan-500 rounded-lg mr-3 group-hover:scale-110 transition-transform shadow-lg">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 00-2-2m0 0h2a2 2 0 012 2v6a2 2 0 01-2 2h-2a2 2 0 01-2-2v-6z"></path>
                                </svg>
                            </div>
                            <span class="font-medium">İmtahan Nəticələri</span>
                        </a>
                        <?php endif; ?>

                        <?php if(is_admin() || is_prorektor()): ?>
                        <a href="../admin/archived_results.php" class="sidebar-item flex items-center px-4 py-3 text-gray-700 rounded-xl hover:text-primary-600 group">
                            <div class="p-2 bg-gradient-to-br from-slate-500 to-gray-600 rounded-lg mr-3 group-hover:scale-110 transition-transform shadow-lg">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path>
                                </svg>
                            </div>
                            <span class="font-medium">Arxivlənmiş Nəticələr</span>
                        </a>
                        <?php endif; ?>

                        <?php if(is_admin()): ?>
                        <a href="../admin/archived_questions.php" class="sidebar-item flex items-center px-4 py-3 text-gray-700 rounded-xl hover:text-primary-600 group">
                            <div class="p-2 bg-gradient-to-br from-amber-500 to-orange-600 rounded-lg mr-3 group-hover:scale-110 transition-transform shadow-lg">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <span class="font-medium">Arxivlənmiş Suallar</span>
                        </a>

                        <a href="../admin/archived_subjects.php" class="sidebar-item flex items-center px-4 py-3 text-gray-700 rounded-xl hover:text-primary-600 group">
                            <div class="p-2 bg-gradient-to-br from-rose-500 to-pink-600 rounded-lg mr-3 group-hover:scale-110 transition-transform shadow-lg">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                                </svg>
                            </div>
                            <span class="font-medium">Arxivlənmiş Fənnlər</span>
                        </a>
                        <?php endif; ?>

                    <?php endif; ?>
                </nav>

                <!-- User Profile Card with Glass Effect -->
                <?php if(isset($_SESSION['user_id'])): ?>
                <div class="p-4 border-t border-white/20" x-data="{ dropdownOpen: false }">
                    <div class="relative">
                        <button @click="dropdownOpen = !dropdownOpen" class="w-full glass hover:bg-white/80 rounded-2xl p-4 transition-all duration-300 hover:shadow-xl">
                            <div class="flex items-center">
                                <div class="relative">
                                    <div class="w-12 h-12 bg-gradient-to-br from-primary-500 to-cyan-500 rounded-xl flex items-center justify-center text-white font-bold text-lg shadow-lg animate-float">
                                        <?php echo strtoupper(substr($_SESSION['name'], 0, 1)); ?>
                                    </div>
                                    <div class="absolute -bottom-1 -right-1 w-4 h-4 bg-green-500 border-2 border-white rounded-full animate-pulse"></div>
                                </div>
                                <div class="ml-3 text-left flex-1">
                                    <p class="text-sm font-bold text-gray-900"><?php echo $_SESSION['name']; ?></p>
                                    <p class="text-xs text-gray-500 capitalize"><?php echo $_SESSION['role']; ?></p>
                                </div>
                                <svg class="w-5 h-5 text-gray-400 transition-transform" :class="{ 'rotate-180': dropdownOpen }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </div>
                        </button>

                        <div x-show="dropdownOpen"
                             @click.away="dropdownOpen = false"
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="opacity-0 scale-95"
                             x-transition:enter-end="opacity-100 scale-100"
                             x-transition:leave="transition ease-in duration-150"
                             x-transition:leave-start="opacity-100 scale-100"
                             x-transition:leave-end="opacity-0 scale-95"
                             class="absolute bottom-full left-0 right-0 mb-2 glass rounded-2xl shadow-2xl overflow-hidden">
                            <a href="../includes/logout.php" class="block px-4 py-3 text-sm text-red-600 hover:bg-red-50 transition flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                                </svg>
                                <span class="font-medium">Çıxış</span>
                            </a>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </aside>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col min-h-screen transition-all duration-300" :class="{ 'lg:ml-72': sidebarOpen }">
            <!-- Top Header with Glass Effect -->
            <header class="glass sticky top-0 z-40 shadow-lg">
                <div class="px-4 sm:px-6 lg:px-8">
                    <div class="flex items-center justify-between h-16">
                        <div class="flex items-center space-x-4">
                            <button @click="sidebarOpen = !sidebarOpen" class="hidden lg:block p-2 rounded-xl text-gray-600 hover:bg-white/50 transition-all hover:shadow-lg hover:scale-110">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                                </svg>
                            </button>
                            <button @click="mobileMenuOpen = true" class="lg:hidden p-2 rounded-xl text-gray-600 hover:bg-white/50 transition-all hover:shadow-lg">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                                </svg>
                            </button>
                            <h1 class="text-xl font-bold gradient-text"><?php echo $pageTitle; ?></h1>
                        </div>

                        <div class="flex items-center space-x-4">
                            <div class="hidden sm:flex items-center space-x-2 px-4 py-2 glass rounded-xl">
                                <svg class="w-5 h-5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                                <span class="text-sm font-medium text-gray-700"><?php echo date('d.m.Y'); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <main class="flex-1 overflow-y-auto">
                <div class="px-4 sm:px-6 lg:px-8 py-8 animate-slide-up">
