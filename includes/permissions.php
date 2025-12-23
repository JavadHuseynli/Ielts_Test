<?php
// This file should be included after session_start()

function is_admin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function is_prorektor() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'prorektor';
}

function is_kafedra() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'kafedra';
}

function is_teacher() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'muellim';
}

function is_student() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'student';
}

// More granular permissions
function can_view_all_questions() {
    return is_admin() || is_kafedra();
}

function can_view_own_questions() {
    return is_teacher();
}

function can_create_exams() {
    return is_admin() || is_prorektor();
}

function can_view_exam_results() {
    return is_admin() || is_prorektor() || is_kafedra();
}

function can_manage_users() {
    return is_admin();
}

function can_archive_results() {
    return is_admin();
}

function can_delete_archives() {
    return is_admin();
}

function can_download_results_docx() {
    return is_prorektor();
}

?>