<?php
header('Content-Type: application/json; charset=utf-8');

// Replace with your real receiving email address
$receiving_email_address = 'dedeketimilehin@gmail.com';

function sanitize_header($str) {
  return trim(str_replace(array("\r", "\n"), '', $str));
}

$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$subject = trim($_POST['subject'] ?? 'New message');
$message = trim($_POST['message'] ?? '');

if ($name === '' || $message === '') {
  echo json_encode(['status' => 'error', 'message' => 'Name and message are required.']);
  exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
  echo json_encode(['status' => 'error', 'message' => 'Invalid email address.']);
  exit;
}

$subject = sanitize_header($subject);
$email = sanitize_header($email);
$name = strip_tags($name);

$php_library = __DIR__ . '/../assets/vendor/php-email-form/php-email-form.php';
if (file_exists($php_library)) {
  include $php_library;
  try {
    $contact = new PHP_Email_Form;
    $contact->ajax = true;
    $contact->to = $receiving_email_address;
    $contact->from_name = $name;
    $contact->from_email = $email;
    $contact->subject = $subject;
    $contact->add_message($name, 'From');
    $contact->add_message($email, 'Email');
    $contact->add_message($message, 'Message', 10);
    $result = $contact->send();
    if (is_string($result)) {
      echo json_encode(['status' => 'success', 'message' => $result]);
    } elseif ($result) {
      echo json_encode(['status' => 'success', 'message' => 'Message sent.']);
    } else {
      echo json_encode(['status' => 'error', 'message' => 'Failed to send message (library).']);
    }
  } catch (Exception $e) {
    error_log($e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'An error occurred while sending message.']);
  }
  exit;
}

// Fallback: use PHP mail() if the library isn't available
$site_domain = $_SERVER['SERVER_NAME'] ?? 'localhost';
$from_email = 'no-reply@' . preg_replace('/[^a-z0-9\.\-]/i', '', $site_domain);
$headers = 'From: ' . $from_email . "\r\n";
$headers .= 'Reply-To: ' . $email . "\r\n";
$headers .= 'Content-Type: text/plain; charset=UTF-8' . "\r\n";
$body = "From: $name\nEmail: $email\n\n$message";

$sent = mail($receiving_email_address, $subject, $body, $headers);

if ($sent) {
  echo json_encode(['status' => 'success', 'message' => 'Message sent (mail fallback).']);
} else {
  echo json_encode(['status' => 'error', 'message' => 'Failed to send message.']);
}
?>
