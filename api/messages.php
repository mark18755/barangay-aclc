<?php
require_once 'config.php';
$method = $_SERVER['REQUEST_METHOD'];
$input  = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $_GET['action'] ?? '';
$db     = getDB();

// GET all messages
if ($method === 'GET') {
    $result = $db->query("SELECT * FROM messages ORDER BY sent_at DESC");
    $rows   = [];
    while ($row = $result->fetch_assoc()) $rows[] = $row;
    echo json_encode($rows);

// POST - send new message (public)
} elseif ($method === 'POST' && $action === 'send') {
    $name    = trim($input['sender_name']  ?? '');
    $email   = trim($input['sender_email'] ?? '');
    $subject = trim($input['subject']      ?? '');
    $message = trim($input['message']      ?? '');

    if (!$name || !$email || !$subject || !$message) {
        echo json_encode(['success' => false, 'message' => 'All fields are required.']);
        exit;
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid email address.']);
        exit;
    }

    $stmt = $db->prepare("INSERT INTO messages (sender_name, sender_email, subject, message) VALUES (?,?,?,?)");
    $stmt->bind_param('ssss', $name, $email, $subject, $message);
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Message sent successfully!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to send message.']);
    }

// POST - mark as read
} elseif ($method === 'POST' && $action === 'read') {
    $id   = (int)($input['id'] ?? 0);
    $stmt = $db->prepare("UPDATE messages SET is_read = 1 WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    echo json_encode(['success' => true]);

// DELETE message
} elseif ($method === 'DELETE') {
    $id   = (int)($_GET['id'] ?? 0);
    $stmt = $db->prepare("DELETE FROM messages WHERE id = ?");
    $stmt->bind_param('i', $id);
    echo json_encode($stmt->execute() ? ['success'=>true] : ['success'=>false,'message'=>$db->error]);

} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
$db->close();
