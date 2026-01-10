<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'permissions.php';

function checkLogin() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: ../login.php");
        exit();
    }
}

function checkAdminRights() {
    if (!is_admin()) {
        header("Location: ../login.php");
        exit();
    }
}

function loginUser($username, $password, $db) {
    if ($db === null) {
        error_log("Verilənlər bazası əlaqəsi yaradılmadı");
        return false;
    }

    try {
        $query = "SELECT id_users, f_name, username, password, status, group_id FROM users WHERE username = :username";
        $stmt = $db->prepare($query);
        $stmt->bindParam(":username", $username);
        $stmt->execute();

        if ($stmt->rowCount() == 1) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (password_verify($password, $row['password']) || $row['password'] === $password) {
                $_SESSION['user_id'] = $row['id_users'];
                $_SESSION['name'] = $row['f_name'];
                $_SESSION['username'] = $row['username'];
                $_SESSION['role'] = $row['status'];
                $_SESSION['status'] = $row['status'];
                $_SESSION['group_id'] = $row['group_id'];

                // If user is a teacher, fetch their subjects from teacher_subjects table
                if ($row['status'] == 'muellim') {
                    $subject_query = "SELECT subject_id FROM teacher_subjects WHERE teacher_id = :teacher_id";
                    $subject_stmt = $db->prepare($subject_query);
                    $subject_stmt->bindParam(":teacher_id", $row['id_users']);
                    $subject_stmt->execute();
                    $teacher_subjects = $subject_stmt->fetchAll(PDO::FETCH_COLUMN);
                    $_SESSION['teacher_subjects'] = $teacher_subjects;
                } else {
                    $_SESSION['teacher_subjects'] = [];
                }

                return true;
            }
        }

        return false;
    } catch (PDOException $e) {
        error_log("Giriş xətası: " . $e->getMessage());
        return false;
    }
}

function logout() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    session_unset();
    session_destroy();
    header("Location: ../login.php");
    exit();
}
?>