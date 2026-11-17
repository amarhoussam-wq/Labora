<?php
include_once("../config.php");

header("Content-Type: text/plain; charset=UTF-8");

$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$message = trim($_POST['message'] ?? '');

if ($name === '' || $email === '' || $message === '') {
  http_response_code(422);
  echo "Please complete all required fields.";
  exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
  http_response_code(422);
  echo "Please enter a valid email address.";
  exit;
}

$sql = "INSERT INTO reviews (name, email, message) VALUES (:name, :email, :message)";
$insertMessage = $conn->prepare($sql);
$insertMessage->bindParam(':name', $name);
$insertMessage->bindParam(':email', $email);
$insertMessage->bindParam(':message', $message);

if ($insertMessage->execute()) {
  echo "OK";
  exit;
}

http_response_code(500);
echo "Unable to send your message. Please try again.";
