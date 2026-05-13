<?php
/**
 * Categories CRUD API
 * GET -> list all | POST -> create (admin) | PUT?id -> update (admin) | DELETE?id -> delete (admin)
 */
require_once '../config/db.php';
$authUser=requireAuth($conn);
$method=$_SERVER['REQUEST_METHOD'];

if($method==='GET'){
  $stmt=$conn->prepare("SELECT c.*,u.name AS created_by_name,(SELECT COUNT(*) FROM items WHERE category_id=c.id) AS item_count FROM categories c LEFT JOIN users u ON c.created_by=u.id ORDER BY c.name ASC");
  $stmt->execute();
  respond(true,'OK',$stmt->get_result()->fetch_all(MYSQLI_ASSOC));
}
if($method==='POST'){
  if($authUser['role']!=='admin')respond(false,'Admin only',null,403);
  $b=json_decode(file_get_contents('php://input'),true);
  $name=trim($b['name']??'');
  if(empty($name))respond(false,'Name required');
  $desc=trim($b['description']??'');$color=$b['color']??'#3B82F6';
  $stmt=$conn->prepare("INSERT INTO categories(name,description,color,created_by)VALUES(?,?,?,?)");
  $stmt->bind_param('sssi',$name,$desc,$color,$authUser['id']);
  if(!$stmt->execute())respond(false,'Failed',null,500);
  respond(true,'Created',['id'=>$conn->insert_id]);
}
if($method==='PUT'){
  if($authUser['role']!=='admin')respond(false,'Admin only',null,403);
  $id=(int)($_GET['id']??0);
  $b=json_decode(file_get_contents('php://input'),true);
  $name=trim($b['name']??'');
  if(!$id||empty($name))respond(false,'ID and name required');
  $desc=trim($b['description']??'');$color=$b['color']??'#3B82F6';
  $stmt=$conn->prepare("UPDATE categories SET name=?,description=?,color=? WHERE id=?");
  $stmt->bind_param('sssi',$name,$desc,$color,$id);
  if(!$stmt->execute())respond(false,'Failed',null,500);
  respond(true,'Updated');
}
if($method==='DELETE'){
  if($authUser['role']!=='admin')respond(false,'Admin only',null,403);
  $id=(int)($_GET['id']??0);if(!$id)respond(false,'ID required');
  $stmt=$conn->prepare("DELETE FROM categories WHERE id=?");$stmt->bind_param('i',$id);
  if(!$stmt->execute())respond(false,'Failed',null,500);
  respond(true,'Deleted');
}