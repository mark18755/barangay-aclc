<?php
require_once 'config.php';
$db = getDB();
$s  = [];
$s['total_complaints'] = $db->query("SELECT COUNT(*) as t FROM complaints")->fetch_assoc()['t'];
$s['total_blotter']    = $db->query("SELECT COUNT(*) as t FROM blotter_records")->fetch_assoc()['t'];
$s['resolved']         = $db->query("SELECT COUNT(*) as t FROM complaints WHERE status='Resolved'")->fetch_assoc()['t'];
$s['pending']          = $db->query("SELECT COUNT(*) as t FROM complaints WHERE status='Pending'")->fetch_assoc()['t'];
$s['ongoing']          = $db->query("SELECT COUNT(*) as t FROM complaints WHERE status='Ongoing'")->fetch_assoc()['t'];
$s['total_users']      = $db->query("SELECT COUNT(*) as t FROM users WHERE status='active'")->fetch_assoc()['t'];
$s['pending_accounts'] = $db->query("SELECT COUNT(*) as t FROM users WHERE status='pending'")->fetch_assoc()['t'];
$s['unread_messages']  = $db->query("SELECT COUNT(*) as t FROM messages WHERE is_read=0")->fetch_assoc()['t'];
$r = $db->query("SELECT * FROM complaints ORDER BY id DESC LIMIT 5");
$recent = []; while ($row = $r->fetch_assoc()) $recent[] = $row; $s['recent'] = $recent;
echo json_encode($s);
$db->close();
