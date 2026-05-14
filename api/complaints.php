<?php
require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];
$input  = json_decode(file_get_contents('php://input'), true) ?? [];
$id     = $_GET['id'] ?? null;
$db     = getDB();

function generateCaseNumber($db) {
    $result = $db->query("SELECT case_number FROM complaints ORDER BY id DESC LIMIT 1");
    $row = $result->fetch_assoc();
    if (!$row) return 'C-001';
    $last = intval(substr($row['case_number'], 2));
    return 'C-' . str_pad($last + 1, 3, '0', STR_PAD_LEFT);
}

if ($method === 'GET') {
    if ($id) {
        $stmt = $db->prepare("SELECT * FROM complaints WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        echo json_encode($stmt->get_result()->fetch_assoc() ?: ['error' => 'Not found']);
    } else {
        $search = $_GET['search'] ?? '';
        $status = $_GET['status'] ?? '';
        $sql    = "SELECT * FROM complaints WHERE 1=1";
        $types  = ''; $params = [];
        if ($search) {
            $sql .= " AND (complainant_name LIKE ? OR respondent_name LIKE ? OR case_number LIKE ?)";
            $like = "%$search%";
            $params = [$like, $like, $like]; $types .= 'sss';
        }
        if ($status) { $sql .= " AND status = ?"; $params[] = $status; $types .= 's'; }
        $sql .= " ORDER BY id DESC";
        if ($params) {
            $stmt = $db->prepare($sql);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();
        } else { $result = $db->query($sql); }
        $rows = [];
        while ($row = $result->fetch_assoc()) $rows[] = $row;
        echo json_encode($rows);
    }

} elseif ($method === 'POST') {
    foreach (['complainant_name','respondent_name','incident_description','date_of_incident'] as $f) {
        if (empty($input[$f])) { echo json_encode(['success'=>false,'message'=>"$f is required."]); exit; }
    }
    $cn       = generateCaseNumber($db);
    $cname    = $input['complainant_name'];
    $caddr    = $input['complainant_address']   ?? '';
    $ccon     = $input['complainant_contact']   ?? '';
    $rname    = $input['respondent_name'];
    $raddr    = $input['respondent_address']    ?? '';
    $desc     = $input['incident_description'];
    $doi      = $input['date_of_incident'];
    $status   = $input['status']  ?? 'Pending';
    $notes    = $input['notes']   ?? '';
    $filed_by = $_SESSION['full_name'] ?? 'system';

    $stmt = $db->prepare("INSERT INTO complaints (case_number,complainant_name,complainant_address,complainant_contact,respondent_name,respondent_address,incident_description,date_of_incident,status,notes,filed_by) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
    $stmt->bind_param('sssssssssss', $cn,$cname,$caddr,$ccon,$rname,$raddr,$desc,$doi,$status,$notes,$filed_by);
    if ($stmt->execute()) echo json_encode(['success'=>true,'id'=>$db->insert_id,'case_number'=>$cn]);
    else echo json_encode(['success'=>false,'message'=>$db->error]);

} elseif ($method === 'PUT' && $id) {
    $cname  = $input['complainant_name']      ?? '';
    $caddr  = $input['complainant_address']   ?? '';
    $ccon   = $input['complainant_contact']   ?? '';
    $rname  = $input['respondent_name']       ?? '';
    $raddr  = $input['respondent_address']    ?? '';
    $desc   = $input['incident_description']  ?? '';
    $doi    = $input['date_of_incident']      ?? '';
    $status = $input['status'] ?? 'Pending';
    $notes  = $input['notes']  ?? '';
    $iid    = (int)$id;
    $stmt   = $db->prepare("UPDATE complaints SET complainant_name=?,complainant_address=?,complainant_contact=?,respondent_name=?,respondent_address=?,incident_description=?,date_of_incident=?,status=?,notes=? WHERE id=?");
    $stmt->bind_param('sssssssssi',$cname,$caddr,$ccon,$rname,$raddr,$desc,$doi,$status,$notes,$iid);
    echo json_encode($stmt->execute() ? ['success'=>true] : ['success'=>false,'message'=>$db->error]);

} elseif ($method === 'DELETE' && $id) {
    $iid  = (int)$id;
    $stmt = $db->prepare("DELETE FROM complaints WHERE id=?");
    $stmt->bind_param('i', $iid);
    echo json_encode($stmt->execute() ? ['success'=>true] : ['success'=>false,'message'=>$db->error]);

} else { http_response_code(405); echo json_encode(['error'=>'Method not allowed']); }
$db->close();
