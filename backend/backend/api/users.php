<?php
/**
 * Users API
 * Admin: see all users, delete any
 * Regular user: see only self
 */
require_once '../config/db.php';
$authUser=requireAuth($conn);
$method=$_SERVER['REQUEST_METHOD'];

if($method==='GET'){
  if($authUser['role']==='admin'){
    $r=$conn->query("SELECT id,name,email,role,is_active,created_at FROM users ORDER BY created_at DESC");
    respond(true,'OK',$r->fetch_all(MYSQLI_ASSOC));
  }
  $s=$conn->prepare("SELECT id,name,email,role,created_at FROM users WHERE id=?");
  $s->bind_param('i',$authUser['id']);$s->execute();
  respond(true,'OK',$s->get_result()->fetch_all(MYSQLI_ASSOC));
}
if($method==='PUT'){
  $id=(int)($_GET['id']??$authUser['id']);
  if($id!==$authUser['id']&&$authUser['role']!=='admin')respond(false,'Forbidden',null,403);
  $b=json_decode(file_get_contents('php://input'),true);
  $name=trim($b['name']??'');$email=trim($b['email']??'');
  if(empty($name)||empty($email))respond(false,'Name and email required');
  $s=$conn->prepare("UPDATE users SET name=?,email=? WHERE id=?");
  $s->bind_param('ssi',$name,$email,$id);
  if(!$s->execute())respond(false,'Failed',null,500);
  respond(true,'Updated');
}
if($method==='DELETE'){
  if($authUser['role']!=='admin')respond(false,'Admin only',null,403);
  $id=(int)($_GET['id']??0);if(!$id)respond(false,'ID required');
  $s=$conn->prepare("DELETE FROM users WHERE id=?");$s->bind_param('i',$id);
  if(!$s->execute())respond(false,'Failed',null,500);
  respond(true,'Deleted');
}