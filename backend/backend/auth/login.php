<?php
/**
 * POST /auth/login.php
 * Body: { email, password }
 * Returns: { success, message, data: { token, user } }
 */
require_once '../config/db.php';
if($_SERVER['REQUEST_METHOD']!=='POST')respond(false,'Method not allowed',null,405);

$body=json_decode(file_get_contents('php://input'),true);
$email=trim($body['email'] ?? '');
$password=trim($body['password'] ?? '');
if(empty($email)||empty($password))respond(false,'Email and password required');

$stmt=$conn->prepare("SELECT id,name,email,password,role,is_active FROM users WHERE email=?");
$stmt->bind_param('s',$email);
$stmt->execute();
$user=$stmt->get_result()->fetch_assoc();

if(!$user||!$user['is_active']||!password_verify($password,$user['password'])){
  respond(false,'Invalid credentials');
}
$token=base64_encode("{$user['id']}|{$user['email']}");
logActivity($conn,$user['id'],'logged in','user',$user['id'],"IP: ".($_SERVER['REMOTE_ADDR']??''));
unset($user['password'],$user['is_active']);
respond(true,'Login successful',['token'=>$token,'user'=>$user]);