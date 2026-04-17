<?php

require "auth.php";
require "db.php";

$userId = current_user_id();

$stmt = $conn->prepare("SELECT first_name, last_name, email, created_at, password_hash, role FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

$name = trim(($user["first_name"] ?? "") . " " . ($user["last_name"] ?? ""));
$email = $user["email"] ?? ($_SESSION["user_email"] ?? "");
$createdAt = $user["created_at"] ?? null;
$hasPassword = (($user["password_hash"] ?? "") !== "");
$role = $user["role"] ?? current_role();

$errorMessage = $_GET["err"] ?? "";
$ok = $_GET["ok"] ?? "";
?>
<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>StudySpot | Mein Account</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="register.css">
</head>
<body>

<?php include "navbar.php"; ?>

<main class="page-space">
    <section class="wrap">
        <div class="card">
            <div class="left">
                <span class="badge">Mein Account</span>
                <h1>Hallo, <?= e($name ?: ($_SESSION["user_name"] ?? "StudySpot")) ?>!</h1>
                <p>Hier verwaltest du dein Konto, aenderst dein Passwort und springst direkt in die wichtigsten Bereiche der Plattform.</p>

                <div class="mt-3" style="color: var(--muted);">
                    <div><strong>E-Mail:</strong> <?= e($email) ?></div>
                    <div><strong>Rolle:</strong> <?= e($role) ?></div>
                    <?php if ($createdAt): ?>
                        <div><strong>Dabei seit:</strong> <?= e(substr($createdAt, 0, 10)) ?></div>
                    <?php endif; ?>
                </div>

                <div class="mt-4 d-grid gap-2">
                    <a class="btn btn-primary" href="spots.php">Zu den Spots</a>

                    <?php if ($role === 'owner' || $role === 'admin'): ?>
                        <a class="btn btn-outline" href="owner_home.php">Owner-Bereich</a>
                    <?php endif; ?>

                    <?php if ($role === 'admin'): ?>
                        <a class="btn btn-outline" href="admin_requests.php">Admin-Anfragen</a>
                    <?php endif; ?>

                    <a class="btn btn-outline" href="logout.php">Logout</a>
                </div>
            </div>

            <div class="form">
                <h2>Account-Einstellungen</h2>
                <p class="sub">Sicherheit, Passwort und Zugangsdaten.</p>

                <?php if ($errorMessage): ?>
                    <div class="alert alert-danger"><?= e($errorMessage) ?></div>
                <?php endif; ?>

                <?php if ($ok === "pw"): ?>
                    <div class="alert alert-success">Dein Passwort wurde erfolgreich aktualisiert.</div>
                <?php endif; ?>

                <div class="mini-card">
                    <h3 class="mini-title">Passwort aendern</h3>

                    <?php if (!$hasPassword): ?>
                        <div class="alert alert-info">
                            Dieses Konto hat aktuell kein Passwort gesetzt. Du kannst jetzt eines hinterlegen.
                        </div>
                    <?php endif; ?>

                    <form method="post" action="password_update.php">
                        <?= csrf_field() ?>

                        <?php if ($hasPassword): ?>
                            <label for="old_password">Altes Passwort</label>
                            <input id="old_password" name="old_password" type="password" placeholder="Altes Passwort" required>
                        <?php endif; ?>

                        <label for="new_password">Neues Passwort</label>
                        <input id="new_password" name="new_password" type="password" placeholder="Mindestens 8 Zeichen" required>

                        <label for="new_password2">Neues Passwort bestaetigen</label>
                        <input id="new_password2" name="new_password2" type="password" placeholder="Wiederholen" required>

                        <button class="btn btn-primary" type="submit">Passwort speichern</button>
                    </form>
                </div>

                <div class="mini-card" style="margin-top:14px;">
                    <h3 class="mini-title">Sicherheitshinweis</h3>
                    <p style="margin:0; color:var(--muted); font-size:14px;">
                        Gute Passwoerter sind lang, einzigartig und nicht fuer mehrere Dienste wiederverwendet.
                    </p>
                </div>
            </div>
        </div>
    </section>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
