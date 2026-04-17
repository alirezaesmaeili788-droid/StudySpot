<?php

require_once "helpers.php";
require "db.php";

if (is_logged_in()) {
    redirect("account.php");
}

$errors = [];
$success = "";

if (is_post_request()) {
    verify_csrf();

    $firstName = trim($_POST["first_name"] ?? "");
    $lastName = trim($_POST["last_name"] ?? "");
    $email = strtolower(trim($_POST["email"] ?? ""));
    $password = $_POST["password"] ?? "";
    $passwordRepeat = $_POST["password2"] ?? "";
    $acceptedTerms = isset($_POST["terms"]);
    $role = isset($_POST["is_owner"]) ? "owner" : "student";

    if ($firstName === "" || $lastName === "" || $email === "" || $password === "" || $passwordRepeat === "") {
        $errors[] = "Bitte alle Felder ausfuellen.";
    }
    if ($email !== "" && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Bitte eine gueltige E-Mail-Adresse eingeben.";
    }
    if (mb_strlen($firstName) > 80 || mb_strlen($lastName) > 80) {
        $errors[] = "Vorname und Nachname duerfen jeweils hoechstens 80 Zeichen lang sein.";
    }
    if (strlen($password) < 8) {
        $errors[] = "Das Passwort muss mindestens 8 Zeichen lang sein.";
    }
    if ($password !== $passwordRepeat) {
        $errors[] = "Die Passwoerter stimmen nicht ueberein.";
    }
    if (!$acceptedTerms) {
        $errors[] = "Bitte akzeptiere die Nutzungsbedingungen und Datenschutzhinweise.";
    }

    if (!$errors) {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $errors[] = "Diese E-Mail-Adresse ist bereits registriert.";
        }

        $stmt->close();
    }

    if (!$errors) {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare(
            "INSERT INTO users (first_name, last_name, email, password_hash, role) VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->bind_param("sssss", $firstName, $lastName, $email, $passwordHash, $role);
        $stmt->execute();
        $stmt->close();

        $success = "Registrierung erfolgreich. Du kannst dich jetzt einloggen.";
        $_POST = [];
    }
}
?>
<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>StudySpot | Registrieren</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="register.css">
</head>
<body>

<?php include "navbar.php"; ?>

<main class="page-space">
    <section class="wrap">
        <div class="card">
            <div class="left">
                <span class="badge">StudySpot</span>
                <h1>Konto erstellen</h1>
                <p>Registriere dich, um Lernorte zu bewerten, Favoriten aufzubauen und als Betreiber eigene Orte einzureichen.</p>
            </div>

            <div class="form">
                <h2>Registrieren</h2>
                <p class="sub">Es dauert nur einen Moment.</p>

                <?php if ($errors): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?= e($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <?= e($success) ?>
                        <div><a href="login.php">Zum Login</a></div>
                    </div>
                <?php endif; ?>

                <form method="post">
                    <?= csrf_field() ?>

                    <div class="form-grid-2">
                        <div>
                            <label for="first_name">Vorname</label>
                            <input id="first_name" name="first_name" type="text" required value="<?= old("first_name") ?>">
                        </div>
                        <div>
                            <label for="last_name">Nachname</label>
                            <input id="last_name" name="last_name" type="text" required value="<?= old("last_name") ?>">
                        </div>
                    </div>

                    <label for="email">E-Mail-Adresse</label>
                    <input id="email" name="email" type="email" required autocomplete="email" value="<?= old("email") ?>">

                    <label for="password">Passwort</label>
                    <input id="password" name="password" type="password" required autocomplete="new-password">

                    <label for="password2">Passwort bestaetigen</label>
                    <input id="password2" name="password2" type="password" required autocomplete="new-password">

                    <div class="form-check form-switch mb-3 mt-3">
                        <input class="form-check-input" type="checkbox" role="switch" id="is_owner" name="is_owner" value="1" <?= checked(isset($_POST["is_owner"])) ?>>
                        <label class="form-check-label fw-semibold" for="is_owner">
                            Ich bin Betreiber eines Lernortes
                        </label>
                    </div>

                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="terms" name="terms" value="1" required <?= checked(isset($_POST["terms"])) ?>>
                        <label class="form-check-label" for="terms" style="margin-left: 1.5em;">
                            Ich akzeptiere die
                            <a href="terms.php" target="_blank" rel="noopener">Nutzungsbedingungen</a>
                            und die
                            <a href="privacy.php" target="_blank" rel="noopener">Datenschutzerklaerung</a>.
                        </label>
                    </div>

                    <button class="btn btn-primary" type="submit">Registrieren</button>
                    <p class="foot">Schon registriert? <a href="login.php">Zum Login</a></p>
                </form>
            </div>
        </div>
    </section>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
