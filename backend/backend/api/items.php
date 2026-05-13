<?php
/**
 * Items CRUD API
 * GET    -> list items (filters: status, priority, category_id, search, page)
 * GET?id -> single item with comments
 * POST   -> create item
 * PUT?id -> update item
 * DELETE?id -> delete item
 * RENAME "items" to match your challenge entity!
 */
require_once '../config/db.php';
$authUser=requireAuth($conn);
$method=$_SERVER['REQUEST_METHOD'];

if($method==='GET'&&!isset($_GET['id'])){
  $where=['1=1'];$params=[];$types='';
  if(!empty($_GET['status'])){$where[]='i.status=?';$params[]=$_GET['status'];$types.='s';}
  if(!empty($_GET['priority'])){$where[]='i.priority=?';$params[]=$_GET['priority'];$types.='s';}
  if(!empty($_GET['category_id'])){$where[]='i.category_id=?';$params[]=(int)$_GET['category_id'];$types.='i';}
  if(!empty($_GET['search'])){
    $where[]='(i.title LIKE ? OR i.description LIKE ?)';
    $like='%'.$conn->real_escape_string($_GET['search']).'%';
    $params[]=$like;$params[]=$like;$types.='ss';
  }
  if($authUser['role']!=='admin'){$where[]='i.created_by=?';$params[]=$authUser['id'];$types.='i';}
  $wc=implode(' AND ',$where);
  $countStmt=$conn->prepare("SELECT COUNT(*) AS c FROM items i WHERE $wc");
  if(!empty($types))$countStmt->bind_param($types,...$params);
  $countStmt->execute();
  $total=$countStmt->get_result()->fetch_assoc()['c'];
  $page=max(1,(int)($_GET['page']??1));$limit=10;$offset=($page-1)*$limit;
  $sql="SELECT i.*,c.name AS category_name,c.color AS category_color,u.name AS created_by_name
        FROM items i
        LEFT JOIN categories c ON i.category_id=c.id
        LEFT JOIN users u ON i.created_by=u.id
        WHERE $wc ORDER BY i.created_at DESC LIMIT ? OFFSET ?";
  $params[]=$limit;$params[]=$offset;$types.='ii';
  $stmt=$conn->prepare($sql);
  $stmt->bind_param($types,...$params);
  $stmt->execute();
  $items=$stmt->get_result()->fetch_all(MYSQLI_ASSOC);
  respond(true,'OK',['items'=>$items,'total'=>(int)$total,'page'=>$page,'totalPages'=>ceil($total/$limit)]);
}

if($method==='GET'&&isset($_GET['id'])){
  $id=(int)$_GET['id'];
  $stmt=$conn->prepare("SELECT i.*,c.name AS category_name,u.name AS created_by_name FROM items i LEFT JOIN categories c ON i.category_id=c.id LEFT JOIN users u ON i.created_by=u.id WHERE i.id=?");
  $stmt->bind_param('i',$id);$stmt->execute();
  $item=$stmt->get_result()->fetch_assoc();
  if(!$item)respond(false,'Not found',null,404);
  $cs=$conn->prepare("SELECT cm.*,u.name AS user_name FROM comments cm JOIN users u ON cm.user_id=u.id WHERE cm.item_id=? ORDER BY cm.created_at ASC");
  $cs->bind_param('i',$id);$cs->execute();
  $item['comments']=$cs->get_result()->fetch_all(MYSQLI_ASSOC);
  respond(true,'OK',$item);
}

if($method==='POST'){
  $b=json_decode(file_get_contents('php://input'),true);
  $title=trim($b['title']??'');
  if(empty($title))respond(false,'Title required');
  $desc=trim($b['description']??'');
  $status=$b['status']??'pending';$priority=$b['priority']??'medium';
  $catId=!empty($b['category_id'])?(int)$b['category_id']:null;
  $assignTo=!empty($b['assigned_to'])?(int)$b['assigned_to']:null;
  $due=!empty($b['due_date'])?$b['due_date']:null;
  $stmt=$conn->prepare("INSERT INTO items(title,description,status,priority,category_id,assigned_to,created_by,due_date)VALUES(?,?,?,?,?,?,?,?)");
  $stmt->bind_param('ssssiiis',$title,$desc,$status,$priority,$catId,$assignTo,$authUser['id'],$due);
  if(!$stmt->execute())respond(false,'Failed',null,500);
  $newId=$conn->insert_id;
  logActivity($conn,$authUser['id'],'created item','item',$newId,$title);
  respond(true,'Created',['id'=>$newId]);
}

if($method==='PUT'){
  $id=(int)($_GET['id']??0);
  if(!$id)respond(false,'ID required');
  $chk=$conn->prepare("SELECT created_by FROM items WHERE id=?");$chk->bind_param('i',$id);$chk->execute();
  $ex=$chk->get_result()->fetch_assoc();
  if(!$ex)respond(false,'Not found',null,404);
  if($authUser['role']!=='admin'&&$ex['created_by']!==$authUser['id'])respond(false,'Forbidden',null,403);
  $b=json_decode(file_get_contents('php://input'),true);
  $title=trim($b['title']??'');$desc=trim($b['description']??'');
  $status=$b['status']??'pending';$priority=$b['priority']??'medium';
  $catId=!empty($b['category_id'])?(int)$b['category_id']:null;
  $assignTo=!empty($b['assigned_to'])?(int)$b['assigned_to']:null;
  $due=!empty($b['due_date'])?$b['due_date']:null;
  $stmt=$conn->prepare("UPDATE items SET title=?,description=?,status=?,priority=?,category_id=?,assigned_to=?,due_date=? WHERE id=?");
  $stmt->bind_param('ssssiisi',$title,$desc,$status,$priority,$catId,$assignTo,$due,$id);
  if(!$stmt->execute())respond(false,'Failed',null,500);
  logActivity($conn,$authUser['id'],'updated item','item',$id,$status);
  respond(true,'Updated');
}

if($method==='DELETE'){
  $id=(int)($_GET['id']??0);if(!$id)respond(false,'ID required');
  $chk=$conn->prepare("SELECT created_by FROM items WHERE id=?");$chk->bind_param('i',$id);$chk->execute();
  $ex=$chk->get_result()->fetch_assoc();if(!$ex)respond(false,'Not found',null,404);
  if($authUser['role']!=='admin'&&$ex['created_by']!==$authUser['id'])respond(false,'Forbidden',null,403);
  $stmt=$conn->prepare("DELETE FROM items WHERE id=?");$stmt->bind_param('i',$id);
  if(!$stmt->execute())respond(false,'Failed',null,500);
  logActivity($conn,$authUser['id'],'deleted item','item',$id);
  respond(true,'Deleted');
}