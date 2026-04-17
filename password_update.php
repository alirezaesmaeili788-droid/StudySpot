<?php

require "auth.php";
require "db.php";

if (!is_post_request()) {
    redirect("account.php");
}

verify_csrf();

$errors = [];
$userId = current_user_id();
$oldPassword = $_POST["old_password"] ?? "";
$newPassword = $_POST["new_password"] ?? "";
$newPasswordRepeat = $_POST["new_password2"] ?? "";

$stmt = $conn->prepare("SELECT password_hash FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    redirect("account.php?err=" . urlencode("Benutzer konnte nicht geladen werden."));
}

$currentHash = $user["password_hash"] ?? "";

if (strlen($newPassword) < 8) {
    $errors[] = "Das neue Passwort muss mindestens 8 Zeichen lang sein.";
}
if ($newPassword !== $newPasswordRepeat) {
    $errors[] = "Die neuen Passwoerter stimmen nicht ueberein.";
}
if ($currentHash !== "") {
    if ($oldPassword === "") {
        $errors[] = "Bitte gib dein altes Passwort ein.";
    } elseif (!password_verify($oldPassword, $currentHash)) {
        $errors[] = "Das alte Passwort ist falsch.";
    }
}

if ($errors) {
    redirect("account.php?err=" . urlencode(implode(" | ", $errors)));
}

$newHash = password_hash($newPassword, PASSWORD_DEFAULT);
$stmt = $conn->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
$stmt->bind_param("si", $newHash, $userId);
$stmt->execute();
$stmt->close();

redirect("account.php?ok=pw");
