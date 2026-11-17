<?php
include_once("../config.php");

header("Content-Type: text/plain; charset=UTF-8");

$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$number = trim($_POST['number'] ?? ($_POST['phone'] ?? ''));
$date = trim($_POST['date'] ?? '');
$time = trim($_POST['time'] ?? '09:00');

if ($name === '' || $email === '' || $number === '' || $date === '') {
  http_response_code(422);
  echo "Please complete all required appointment fields.";
  exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
  http_response_code(422);
  echo "Please enter a valid email address.";
  exit;
}

$sql = "INSERT INTO appointments (name, email, number, date, time) VALUES (:name, :email, :number, :date, :time)";
$insertAppointment = $conn->prepare($sql);
$insertAppointment->bindParam(':name', $name);
$insertAppointment->bindParam(':email', $email);
$insertAppointment->bindParam(':number', $number);
$insertAppointment->bindParam(':date', $date);
$insertAppointment->bindParam(':time', $time);

if ($insertAppointment->execute()) {
  echo "OK";
  exit;
}

http_response_code(500);
echo "Unable to submit your appointment. Please try again.";
