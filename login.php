<?php

require_once "helpers.php";
require "db.php";

if (is_logged_in()) {
    redirect("account.php");
}

$errors = [];

if (is_post_request()) {
    verify_csrf();

    $email = strtolower(trim($_POST["email"] ?? ""));
    $password = $_POST["password"] ?? "";

    if ($email === "" || $password === "") {
        $errors[] = "Bitte E-Mail und Passwort eingeben.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Bitte eine gueltige E-Mail-Adresse eingeben.";
    } else {
        $stmt = $conn->prepare("SELECT id, first_name, last_name, email, password_hash, role FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$user || !password_verify($password, $user["password_hash"])) {
            $errors[] = "E-Mail oder Passwort ist falsch.";
        } else {
            session_regenerate_id(true);
            $_SESSION["user_id"] = (int)$user["id"];
            $_SESSION["user_name"] = trim(($user["first_name"] ?? "") . " " . ($user["last_name"] ?? ""));
            $_SESSION["user_email"] = $user["email"];
            $_SESSION["role"] = $user["role"] ?: "student";

            redirect("account.php");
        }
    }
}
?>
<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>StudySpot | Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="register.css">
</head>
<body>

<?php include "navbar.php"; ?>

<main class="page-space">
    <section class="wrap">
        <div class="card">
            <div class="left">
                <span class="badge">Login</span>
                <h1>Willkommen zurueck</h1>
                <p>Melde dich an, um Lernorte zu bewerten, eigene Einreichungen zu verwalten und deinen Account zu nutzen.</p>
            </div>

            <div class="form">
                <h2>Anmelden</h2>
                <p class="sub">Mit deinem StudySpot-Konto.</p>

                <?php if ($errors): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?= e($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="post">
                    <?= csrf_field() ?>

                    <label for="email">E-Mail-Adresse</label>
                    <input id="email" name="email" type="email" required autocomplete="email" value="<?= old("email") ?>">

                    <label for="password">Passwort</label>
                    <input id="password" name="password" type="password" required autocomplete="current-password">

                    <button class="btn btn-primary" type="submit">Login</button>

                    <p class="foot">Noch kein Konto? <a href="register.php">Jetzt registrieren</a></p>
                </form>
            </div>
        </div>
    </section>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
