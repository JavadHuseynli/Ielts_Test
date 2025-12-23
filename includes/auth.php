<?php
session_start();

function checkLogin() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: ../login.php");
        exit();
    }
}

function checkAdminRights() {
    if (!isset($_SESSION['user_id']) || $_SESSION['status'] !== 'admin') {
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
            
            // Həm hash doğrulaması, həm də açıq şifrə üçün yoxlama edək
            if (password_verify($password, $row['password']) || $row['password'] === $password) {
                $_SESSION['user_id'] = $row['id_users'];
                $_SESSION['name'] = $row['f_name'];
                $_SESSION['username'] = $row['username'];
                $_SESSION['status'] = $row['status'];
                $_SESSION['group_id'] = $row['group_id'];
                
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
    session_start();
    session_unset();
    session_destroy();
    header("Location: ../login.php");
    exit();
}
?>