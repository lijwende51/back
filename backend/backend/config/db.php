<?php
/**
 * Database config + helper functions
 * Edit DB_NAME, DB_USER, DB_PASS to match your XAMPP setup
 */
define('DB_HOST','localhost');
define('DB_USER','root');
define('DB_PASS','');
define('DB_NAME','hackathon_db');

$conn = new mysqli(DB_HOST,DB_USER,DB_PASS,DB_NAME);
if($conn->connect_error){
  http_response_code(500);
  die(json_encode(['success'=>false,'message'=>'DB error: '.$conn->connect_error]));
}
$conn->set_charset('utf8mb4');

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$allowed = ['http://localhost:5173','http://127.0.0.1:5173'];
if(in_array($origin,$allowed)){
  header("Access-Control-Allow-Origin: $origin");
}
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET,POST,PUT,DELETE,OPTIONS');
header('Access-Control-Allow-Headers: Content-Type,Authorization');
header('Content-Type: application/json; charset=utf-8');
if($_SERVER['REQUEST_METHOD']==='OPTIONS'){http_response_code(200);exit();}

/** Log user action to activity_log table */
function logActivity($conn,$userId,$action,$entityType,$entityId=null,$details=null){
  $stmt=$conn->prepare("INSERT INTO activity_log(user_id,action,entity_type,entity_id,details)VALUES(?,?,?,?,?)");
  $stmt->bind_param('issis',$userId,$action,$entityType,$entityId,$details);
  $stmt->execute();
}

/** Send JSON response and exit */
function respond($success,$message,$data=null,$code=200){
  http_response_code($code);
  $res=['success'=>$success,'message'=>$message];
  if($data!==null)$res['data']=$data;
  echo json_encode($res);
  exit();
}

/** Validate Authorization token, return user array */
function requireAuth($conn){
  $headers=getallheaders();
  $token=str_replace('Bearer ','', $headers['Authorization'] ?? '');
  if(empty($token))respond(false,'Unauthorized',null,401);
  $decoded=base64_decode($token);
  $parts=explode('|',$decoded);
  if(count($parts)!==2)respond(false,'Invalid token',null,401);
  [$userId,$email]=$parts;
  $stmt=$conn->prepare("SELECT id,name,email,role FROM users WHERE id=? AND email=? AND is_active=1");
  $stmt->bind_param('is',$userId,$email);
  $stmt->execute();
  $user=$stmt->get_result()->fetch_assoc();
  if(!$user)respond(false,'User not found',null,401);
  return $user;
}