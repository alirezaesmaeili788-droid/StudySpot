<?php

require_once "helpers.php";
require "db.php";

$errors = [];
$success = "";

$name = "";
$email = "";
$subject = "";
$message = "";

if (is_post_request()) {
    verify_csrf();

    $name = trim($_POST["name"] ?? "");
    $email = strtolower(trim($_POST["email"] ?? ""));
    $subject = trim($_POST["subject"] ?? "");
    $message = trim($_POST["message"] ?? "");

    if ($name === "" || $email === "" || $subject === "" || $message === "") {
        $errors[] = "Bitte alle Felder ausfuellen.";
    }
    if ($email !== "" && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Bitte eine gueltige E-Mail-Adresse eingeben.";
    }
    if (mb_strlen($subject) > 120) {
        $errors[] = "Der Betreff darf hoechstens 120 Zeichen lang sein.";
    }

    if (!$errors) {
        $stmt = $conn->prepare("INSERT INTO contact_messages (name, email, subject, message) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $name, $email, $subject, $message);
        $stmt->execute();
        $stmt->close();

        $success = "Danke! Deine Nachricht wurde gesendet.";
        $name = "";
        $email = "";
        $subject = "";
        $message = "";
    }
}
?>
<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <title>StudySpot | Kontakt</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body class="kontakt-body">

<?php include "navbar.php"; ?>

<main class="mt-5 pt-4">
    <section class="contact-header py-5 text-center">
        <div class="container">
            <h1 class="fw-bold text-success">Kontakt</h1>
            <p class="text-muted mb-0">
                Schreib uns bei Fragen, Feedback oder Verbesserungsvorschlaegen rund um StudySpot.
            </p>
        </div>
    </section>

    <section class="py-4">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-12 col-md-8 col-lg-6">
                    <div class="contact-card p-4 rounded-4 shadow-sm bg-white">
                        <?php if ($success): ?>
                            <div class="alert alert-success"><?= e($success) ?></div>
                        <?php endif; ?>

                        <?php if ($errors): ?>
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?= e($error) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <form method="post" action="kontakt.php">
                            <?= csrf_field() ?>

                            <div class="mb-3">
                                <label class="form-label" for="name">Dein Name</label>
                                <input type="text" class="form-control" id="name" name="name" placeholder="Vorname Nachname" value="<?= e($name) ?>" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="email">E-Mail-Adresse</label>
                                <input type="email" class="form-control" id="email" name="email" placeholder="beispiel@mail.com" value="<?= e($email) ?>" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="subject">Betreff</label>
                                <input type="text" class="form-control" id="subject" name="subject" placeholder="Worum geht es?" value="<?= e($subject) ?>" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="message">Nachricht</label>
                                <textarea class="form-control" id="message" name="message" rows="5" placeholder="Schreibe deine Nachricht..." required><?= e($message) ?></textarea>
                            </div>

                            <button type="submit" class="btn btn-success w-100">Nachricht senden</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<footer class="footer mt-5 py-4">
    <div class="container">
        <div class="row g-4">
            <div class="col-12 col-md-4">
                <h5 class="fw-bold mb-2">StudySpot</h5>
                <p class="small text-muted">Finde passende Lernorte in Wien, vergleiche Ausstattung und entdecke neue Spots fuer konzentriertes Lernen.</p>
            </div>

            <div class="col-6 col-md-4">
                <h6 class="fw-semibold mb-2">Links</h6>
                <ul class="footer-links">
                    <li><a href="index.php">Home</a></li>
                    <li><a href="spots.php">Spots</a></li>
                    <li><a href="kontakt.php">Kontakt</a></li>
                </ul>
            </div>

            <div class="col-6 col-md-4">
                <h6 class="fw-semibold mb-2">Kontakt</h6>
                <ul class="footer-links">
                    <li>E-Mail: info@studyspot.at</li>
                    <li>Wien, Oesterreich</li>
                </ul>
            </div>
        </div>

        <hr class="mt-4">

        <p class="text-center small text-muted mb-0">© 2026 StudySpot - Alle Rechte vorbehalten.</p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
