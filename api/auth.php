<?php
require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];
$input  = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $_GET['action'] ?? '';
$db     = getDB();

function verifyRecaptcha($token, &$errors = null) {
    $errors = [];
    if (!$token) {
        $errors[] = 'missing-input-response';
        return false;
    }
    $secret = RECAPTCHA_SECRET;
    $url = 'https://www.google.com/recaptcha/api/siteverify';
    $payload = http_build_query(['secret' => $secret, 'response' => $token]);

    if (function_exists('curl_version')) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        $response = curl_exec($ch);
        if ($response === false) {
            $errors[] = 'curl-error:' . curl_error($ch);
        }
        curl_close($ch);
    } else {
        $response = @file_get_contents($url . '?' . $payload);
        if ($response === false) {
            $errors[] = 'http-error';
        }
    }

    if (empty($response)) {
        return false;
    }
    $result = json_decode($response, true);
    if (isset($result['error-codes']) && is_array($result['error-codes'])) {
        $errors = array_merge($errors, $result['error-codes']);
    }
    return !empty($result['success']);
}

// LOGIN
if ($action === 'login' && $method === 'POST') {
    $email            = trim($input['email'] ?? '');
    $password         = $input['password']   ?? '';
    $recaptcha_response = trim($input['recaptcha_response'] ?? '');

    if (!$email || !$password || !$recaptcha_response) {
        echo json_encode(['success' => false, 'message' => 'Email, password, and captcha are required.']);
        exit;
    }
    if (!verifyRecaptcha($recaptcha_response, $errors)) {
        echo json_encode(['success' => false, 'message' => 'Captcha verification failed.', 'errors' => $errors]);
        exit;
    }

    $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'No account found with that email.']);
        exit;
    }
    if ($user['status'] === 'pending') {
        echo json_encode(['success' => false, 'message' => 'Your account is pending approval by the administrator.']);
        exit;
    }
    if ($user['status'] === 'disabled') {
        echo json_encode(['success' => false, 'message' => 'Your account has been disabled. Contact the administrator.']);
        exit;
    }

    $valid = password_verify($password, $user['password']);
    if ($valid) {
        $_SESSION['user_id']   = $user['id'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['role']      = $user['role'];
        $_SESSION['email']     = $user['email'];
        echo json_encode(['success' => true, 'user' => [
            'id'        => $user['id'],
            'full_name' => $user['full_name'],
            'role'      => $user['role'],
            'email'     => $user['email'],
        ]]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Incorrect password.']);
    }

// REGISTER
} elseif ($action === 'register' && $method === 'POST') {
    $full_name          = trim($input['full_name'] ?? '');
    $email              = trim($input['email']     ?? '');
    $password           = $input['password']       ?? '';
    $address            = trim($input['address']   ?? '');
    $contact            = trim($input['contact']   ?? '');
    $recaptcha_response = trim($input['recaptcha_response'] ?? '');

    if (!$full_name || !$email || !$password || !$contact || !$recaptcha_response) {
        echo json_encode(['success' => false, 'message' => 'Full name, email, contact number, password, and captcha are required.']);
        exit;
    }
    if (!verifyRecaptcha($recaptcha_response, $errors)) {
        echo json_encode(['success' => false, 'message' => 'Captcha verification failed.', 'errors' => $errors]);
        exit;
    }
    if (strlen($password) < 6) {
        echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters.']);
        exit;
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid email address.']);
        exit;
    }

    $check = $db->prepare("SELECT id FROM users WHERE email = ?");
    $check->bind_param('s', $email);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'An account with this email already exists.']);
        exit;
    }

    $hashed = password_hash($password, PASSWORD_BCRYPT);
    $stmt   = $db->prepare("INSERT INTO users (email, password, full_name, address, contact, role, status) VALUES (?,?,?,?,?,'resident','pending')");
    $stmt->bind_param('sssss', $email, $hashed, $full_name, $address, $contact);
    if ($stmt->execute()) {
        $newUserId = $db->insert_id;
        $_SESSION['user_id']   = $newUserId;
        $_SESSION['full_name'] = $full_name;
        $_SESSION['role']      = 'resident';
        $_SESSION['email']     = $email;
        echo json_encode(['success' => true, 'message' => 'Account created successfully! Logging you in...', 'user' => [
            'id'        => $newUserId,
            'full_name' => $full_name,
            'role'      => 'resident',
            'email'     => $email,
        ]]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Registration failed. Please try again.']);
    }

// VERIFY IDENTITY (forgot password step 1)
} elseif ($action === 'verify_identity' && $method === 'POST') {
    $email   = trim($input['email']   ?? '');
    $contact = trim($input['contact'] ?? '');

    if (!$email || !$contact) {
        echo json_encode(['success' => false, 'message' => 'Email and contact number are required.']);
        exit;
    }

    $stmt = $db->prepare("SELECT id, full_name, contact FROM users WHERE email = ? AND status = 'active'");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    $normalize = fn($s) => preg_replace('/[\s\-\+]/', '', $s);

    if ($user && $normalize($user['contact']) === $normalize($contact)) {
        $_SESSION['reset_user_id'] = $user['id'];
        echo json_encode(['success' => true, 'full_name' => $user['full_name']]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Email or contact number does not match our records.']);
    }

// RESET PASSWORD (forgot password step 2)
} elseif ($action === 'reset_password' && $method === 'POST') {
    if (empty($_SESSION['reset_user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Session expired. Please start over.']);
        exit;
    }
    $new_password     = $input['new_password']     ?? '';
    $confirm_password = $input['confirm_password'] ?? '';

    if (strlen($new_password) < 6) {
        echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters.']);
        exit;
    }
    if ($new_password !== $confirm_password) {
        echo json_encode(['success' => false, 'message' => 'Passwords do not match.']);
        exit;
    }

    $hashed = password_hash($new_password, PASSWORD_BCRYPT);
    $uid    = (int) $_SESSION['reset_user_id'];
    $stmt   = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
    $stmt->bind_param('si', $hashed, $uid);
    if ($stmt->execute()) {
        unset($_SESSION['reset_user_id']);
        echo json_encode(['success' => true, 'message' => 'Password reset successfully! You can now log in.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Reset failed. Please try again.']);
    }

// CHANGE PASSWORD (while logged in)
} elseif ($action === 'change_password' && $method === 'POST') {
    if (empty($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Not logged in.']);
        exit;
    }
    $old = $input['old_password'] ?? '';
    $new = $input['new_password'] ?? '';
    if (strlen($new) < 6) {
        echo json_encode(['success' => false, 'message' => 'New password must be at least 6 characters.']);
        exit;
    }
    $uid  = (int) $_SESSION['user_id'];
    $stmt = $db->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->bind_param('i', $uid);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if (!$row || !password_verify($old, $row['password'])) {
        echo json_encode(['success' => false, 'message' => 'Current password is incorrect.']);
        exit;
    }
    $hashed = password_hash($new, PASSWORD_BCRYPT);
    $upd    = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
    $upd->bind_param('si', $hashed, $uid);
    $upd->execute();
    echo json_encode(['success' => true, 'message' => 'Password changed successfully.']);

// LOGOUT
} elseif ($action === 'logout' && $method === 'POST') {
    session_destroy();
    echo json_encode(['success' => true]);

// CHECK SESSION
} elseif ($action === 'check') {
    if (!empty($_SESSION['user_id'])) {
        echo json_encode(['logged_in' => true, 'user' => [
            'id'        => $_SESSION['user_id'],
            'full_name' => $_SESSION['full_name'],
            'role'      => $_SESSION['role'],
            'email'     => $_SESSION['email'] ?? '',
        ]]);
    } else {
        echo json_encode(['logged_in' => false]);
    }

// GET ALL USERS
} elseif ($action === 'users' && $method === 'GET') {
    $result = $db->query("SELECT id, email, full_name, role, status, address, contact, created_at FROM users ORDER BY status ASC, id DESC");
    $rows = [];
    while ($row = $result->fetch_assoc()) $rows[] = $row;
    echo json_encode($rows);

// UPDATE USER
} elseif ($action === 'update_user' && $method === 'POST') {
    $uid    = (int) ($input['id']     ?? 0);
    $status = $input['status'] ?? '';
    $role   = $input['role']   ?? '';
    if (!$uid) { echo json_encode(['success' => false, 'message' => 'Invalid user.']); exit; }
    $stmt = $db->prepare("UPDATE users SET status = ?, role = ? WHERE id = ?");
    $stmt->bind_param('ssi', $status, $role, $uid);
    $stmt->execute();
    echo json_encode(['success' => true]);

// DELETE USER
} elseif ($action === 'delete_user' && $method === 'POST') {
    $uid  = (int) ($input['id'] ?? 0);
    $stmt = $db->prepare("DELETE FROM users WHERE id = ? AND role != 'admin'");
    $stmt->bind_param('i', $uid);
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Cannot delete this account.']);
    }

} else {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid action.']);
}

$db->close();
