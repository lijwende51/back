<?php
/**
 * Dashboard stats — GET /api/dashboard.php
 * Returns totals, status breakdown, recent activity, weekly chart
 */
require_once '../config/db.php';
$authUser=requireAuth($conn);

$totalItems=$conn->query("SELECT COUNT(*) AS c FROM items")->fetch_assoc()['c'];
$totalUsers=$conn->query("SELECT COUNT(*) AS c FROM users")->fetch_assoc()['c'];
$totalCats=$conn->query("SELECT COUNT(*) AS c FROM categories")->fetch_assoc()['c'];

$byStatus=[];
$r=$conn->query("SELECT status,COUNT(*) AS c FROM items GROUP BY status");
while($row=$r->fetch_assoc())$byStatus[$row['status']]=(int)$row['c'];

$byPriority=[];
$r=$conn->query("SELECT priority,COUNT(*) AS c FROM items GROUP BY priority");
while($row=$r->fetch_assoc())$byPriority[$row['priority']]=(int)$row['c'];

$rs=$conn->prepare("SELECT al.*,u.name AS user_name FROM activity_log al JOIN users u ON al.user_id=u.id ORDER BY al.created_at DESC LIMIT 8");
$rs->execute();$recent=$rs->get_result()->fetch_all(MYSQLI_ASSOC);

$ws=$conn->prepare("SELECT DATE(created_at) AS day,COUNT(*) AS count FROM items WHERE created_at>=DATE_SUB(NOW(),INTERVAL 7 DAY) GROUP BY DATE(created_at) ORDER BY day ASC");
$ws->execute();$weekly=$ws->get_result()->fetch_all(MYSQLI_ASSOC);

respond(true,'OK',[
  'totalItems'=>(int)$totalItems,'totalUsers'=>(int)$totalUsers,'totalCategories'=>(int)$totalCats,
  'byStatus'=>$byStatus,'byPriority'=>$byPriority,
  'recentActivity'=>$recent,'weeklyChart'=>$weekly
]);