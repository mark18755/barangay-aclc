<?php
require_once 'config.php';
$method = $_SERVER['REQUEST_METHOD'];
$input  = json_decode(file_get_contents('php://input'), true) ?? [];
$id     = $_GET['id'] ?? null;
$db     = getDB();

function genBlotter($db) {
    $r   = $db->query("SELECT blotter_number FROM blotter_records ORDER BY id DESC LIMIT 1");
    $row = $r->fetch_assoc();
    if (!$row) return 'B-001';
    return 'B-' . str_pad(intval(substr($row['blotter_number'],2))+1,3,'0',STR_PAD_LEFT);
}

if ($method === 'GET') {
    $search = $_GET['search'] ?? '';
    $sql    = "SELECT br.*, c.complainant_name, c.respondent_name, c.status, c.date_of_incident, c.case_number
               FROM blotter_records br JOIN complaints c ON br.complaint_id = c.id";
    if ($search) {
        $like = $db->real_escape_string("%$search%");
        $sql .= " WHERE c.complainant_name LIKE '$like' OR c.respondent_name LIKE '$like' OR br.blotter_number LIKE '$like' OR c.case_number LIKE '$like'";
    }
    $sql   .= " ORDER BY br.id DESC";
    $result = $db->query($sql);
    $rows   = [];
    while ($row = $result->fetch_assoc()) $rows[] = $row;
    echo json_encode($rows);

} elseif ($method === 'POST') {
    if (empty($input['complaint_id'])) { echo json_encode(['success'=>false,'message'=>'complaint_id required']); exit; }
    $cid   = (int)$input['complaint_id'];
    $check = $db->prepare("SELECT id FROM blotter_records WHERE complaint_id = ?");
    $check->bind_param('i', $cid);
    $check->execute();
    if ($check->get_result()->num_rows > 0) { echo json_encode(['success'=>false,'message'=>'Blotter already exists for this complaint.']); exit; }
    $bn   = genBlotter($db);
    $hd   = $input['hearing_date'] ?? null;
    $set  = $input['settlement']   ?? '';
    $rem  = $input['remarks']      ?? '';
    $stmt = $db->prepare("INSERT INTO blotter_records (complaint_id,blotter_number,hearing_date,settlement,remarks) VALUES (?,?,?,?,?)");
    $stmt->bind_param('issss',$cid,$bn,$hd,$set,$rem);
    echo json_encode($stmt->execute() ? ['success'=>true,'blotter_number'=>$bn] : ['success'=>false,'message'=>$db->error]);

} elseif ($method === 'PUT' && $id) {
    $iid  = (int)$id;
    $hd   = $input['hearing_date'] ?? null;
    $set  = $input['settlement']   ?? '';
    $rem  = $input['remarks']      ?? '';
    $stmt = $db->prepare("UPDATE blotter_records SET hearing_date=?,settlement=?,remarks=? WHERE id=?");
    $stmt->bind_param('sssi',$hd,$set,$rem,$iid);
    echo json_encode($stmt->execute() ? ['success'=>true] : ['success'=>false,'message'=>$db->error]);

} elseif ($method === 'DELETE' && $id) {
    $iid  = (int)$id;
    $stmt = $db->prepare("DELETE FROM blotter_records WHERE id=?");
    $stmt->bind_param('i', $iid);
    echo json_encode($stmt->execute() ? ['success'=>true] : ['success'=>false,'message'=>$db->error]);

} else { http_response_code(405); echo json_encode(['error'=>'Not allowed']); }
$db->close();
