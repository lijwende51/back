<?php
/**
 * POST /auth/register.php
 * Body: { name, email, password }
 * Returns: { success, message, data: { token, user } }
 */
require_once '../config/db.php';
if($_SERVER['REQUEST_METHOD']!=='POST')respond(false,'Method not allowed',null,405);

$body=json_decode(file_get_contents('php://input'),true);
$name=trim($body['name'] ?? '');
$email=trim($body['email'] ?? '');
$password=trim($body['password'] ?? '');

if(empty($name)||empty($email)||empty($password))respond(false,'All fields required');
if(!filter_var($email,FILTER_VALIDATE_EMAIL))respond(false,'Invalid email');
if(strlen($password)<6)respond(false,'Password min 6 chars');

$check=$conn->prepare("SELECT id FROM users WHERE email=?");
$check->bind_param('s',$email);
$check->execute();
if($check->get_result()->num_rows>0)respond(false,'Email already registered');

$hashed=password_hash($password,PASSWORD_BCRYPT);
$stmt=$conn->prepare("INSERT INTO users(name,email,password)VALUES(?,?,?)");
$stmt->bind_param('sss',$name,$email,$hashed);
if(!$stmt->execute())respond(false,'Registration failed',null,500);

$userId=$conn->insert_id;
$token=base64_encode("$userId|$email");
logActivity($conn,$userId,'registered','user',$userId,"New: $email");
respond(true,'Registration successful',[
  'token'=>$token,
  'user'=>['id'=>$userId,'name'=>$name,'email'=>$email,'role'=>'user']
]);