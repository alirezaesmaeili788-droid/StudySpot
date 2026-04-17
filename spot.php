<?php

require_once "helpers.php";
require "db.php";

$spotId = (int)($_GET["id"] ?? 0);
if ($spotId <= 0) {
    redirect("spots.php");
}

$userId = current_user_id();
$errors = [];

$stmt = $conn->prepare("SELECT * FROM spots WHERE id = ?");
$stmt->bind_param("i", $spotId);
$stmt->execute();
$spot = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$spot) {
    redirect("spots.php");
}

$existingReview = [
    "rating" => "",
    "comment" => "",
];

if ($userId > 0) {
    $stmt = $conn->prepare("SELECT rating, comment FROM reviews WHERE spot_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $spotId, $userId);
    $stmt->execute();
    $existingReview = $stmt->get_result()->fetch_assoc() ?: $existingReview;
    $stmt->close();
}

if (is_post_request() && isset($_POST["add_review"])) {
    verify_csrf();

    if ($userId <= 0) {
        redirect("login.php");
    }

    $rating = (int)($_POST["rating"] ?? 0);
    $comment = trim($_POST["comment"] ?? "");

    if ($rating < 1 || $rating > 5) {
        $errors[] = "Bitte waehle eine Bewertung zwischen 1 und 5 Sternen.";
    }
    if (mb_strlen($comment) > 1500) {
        $errors[] = "Der Kommentar darf hoechstens 1500 Zeichen lang sein.";
    }

    if (!$errors) {
        $stmt = $conn->prepare(
            "INSERT INTO reviews (spot_id, user_id, rating, comment)
             VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE rating = VALUES(rating), comment = VALUES(comment)"
        );
        $stmt->bind_param("iiis", $spotId, $userId, $rating, $comment);
        $stmt->execute();
        $stmt->close();

        redirect(build_url("spot.php", ["id" => $spotId]));
    }

    $existingReview = [
        "rating" => (string)$rating,
        "comment" => $comment,
    ];
}

$stmt = $conn->prepare("SELECT AVG(rating) AS avg_rating, COUNT(*) AS total_reviews FROM reviews WHERE spot_id = ?");
$stmt->bind_param("i", $spotId);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();
$stmt->close();

$averageRating = $stats["avg_rating"] !== null ? round((float)$stats["avg_rating"], 1) : null;
$reviewCount = (int)($stats["total_reviews"] ?? 0);

$stmt = $conn->prepare(
    "SELECT r.rating, r.comment, r.created_at, u.first_name, u.last_name
     FROM reviews r
     JOIN users u ON u.id = r.user_id
     WHERE r.spot_id = ?
     ORDER BY r.created_at DESC"
);
$stmt->bind_param("i", $spotId);
$stmt->execute();
$reviews = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>StudySpot | <?= e($spot["name"]) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="register.css">
    <style>
        .heroimg{height:320px;object-fit:cover;border-radius:20px;box-shadow:var(--shadow);}
        .info{background:#fff;border:1px solid var(--border);border-radius:20px;box-shadow:var(--shadow);padding:18px;}
        .tag{background:var(--mint);color:var(--primary-dark);border-radius:999px;padding:6px 10px;font-weight:800;font-size:13px;}
    </style>
</head>
<body>

<?php include "navbar.php"; ?>

<main class="page-space">
    <div class="wrap">
        <div class="mt-3">
            <?php if (!empty($spot["image_url"])): ?>
                <img class="w-100 heroimg" src="<?= e($spot["image_url"]) ?>" alt="<?= e($spot["name"]) ?>">
            <?php endif; ?>
        </div>

        <div class="d-flex flex-wrap align-items-center gap-2 mt-3">
            <h1 style="margin:0; color:#1F5E3B;"><?= e($spot["name"]) ?></h1>
            <span class="tag"><?= e($spot["type"]) ?></span>
        </div>

        <p class="sub" style="margin-top:8px;">
            <?= e(trim(($spot["address"] ?? "") . ", " . ($spot["zip"] ?? "") . " " . ($spot["city"] ?? ""))) ?>
        </p>

        <div class="mb-3">
            <?php if ($averageRating !== null): ?>
                <span class="badge bg-success">⭐ <?= e((string)$averageRating) ?> / 5</span>
                <small class="text-muted">(<?= $reviewCount ?> Bewertungen)</small>
            <?php else: ?>
                <small class="text-muted">Noch keine Bewertungen</small>
            <?php endif; ?>
        </div>

        <div class="row g-3 mt-2">
            <div class="col-lg-8">
                <div class="info">
                    <h3 style="margin:0 0 10px 0;">Beschreibung</h3>
                    <p style="margin:0; color:var(--text); line-height:1.6;">
                        <?= nl2br(e($spot["description"] ?? "Keine Beschreibung vorhanden.")) ?>
                    </p>
                </div>

                <div class="info my-4">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                        <h3 style="margin:0;">Bewertung schreiben</h3>

                        <?php if (can_manage_spot($spot["created_by"] ?? 0)): ?>
                            <div class="d-flex gap-2">
                                <a href="spot_edit.php?id=<?= (int)$spot["id"] ?>" class="btn btn-outline">Bearbeiten</a>
                                <a href="spot_delete.php?id=<?= (int)$spot["id"] ?>" class="btn btn-danger">Loeschen</a>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if ($errors): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?= e($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <?php if (!is_logged_in()): ?>
                        <div class="alert alert-info mb-0">
                            Bitte <a href="login.php">einloggen</a>, um diesen Spot zu bewerten.
                        </div>
                    <?php else: ?>
                        <form method="post" style="max-width:650px;">
                            <?= csrf_field() ?>
                            <input type="hidden" name="add_review" value="1">

                            <div class="mb-3">
                                <label class="form-label fw-bold" for="rating">Sterne</label>
                                <select id="rating" name="rating" class="form-select" required>
                                    <option value="">Bitte waehlen...</option>
                                    <option value="5" <?= selected("5", (string)($existingReview["rating"] ?? "")) ?>>⭐⭐⭐⭐⭐ (5) Top</option>
                                    <option value="4" <?= selected("4", (string)($existingReview["rating"] ?? "")) ?>>⭐⭐⭐⭐ (4) Gut</option>
                                    <option value="3" <?= selected("3", (string)($existingReview["rating"] ?? "")) ?>>⭐⭐⭐ (3) Okay</option>
                                    <option value="2" <?= selected("2", (string)($existingReview["rating"] ?? "")) ?>>⭐⭐ (2) Eher nicht</option>
                                    <option value="1" <?= selected("1", (string)($existingReview["rating"] ?? "")) ?>>⭐ (1) Schwach</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold" for="comment">Kommentar</label>
                                <textarea id="comment" name="comment" class="form-control" rows="4" placeholder="Wie war es vor Ort?"><?= e($existingReview["comment"] ?? "") ?></textarea>
                            </div>

                            <div class="d-flex justify-content-end">
                                <button class="btn btn-primary px-4" type="submit">Bewertung speichern</button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>

                <div class="mt-4">
                    <h5>Bewertungen</h5>

                    <?php if (!$reviews): ?>
                        <p class="text-muted">Noch keine Bewertungen vorhanden.</p>
                    <?php else: ?>
                        <?php foreach ($reviews as $review): ?>
                            <div class="border rounded p-3 mb-2">
                                <div class="d-flex justify-content-between gap-2">
                                    <strong><?= e(trim(($review["first_name"] ?? "") . " " . ($review["last_name"] ?? ""))) ?></strong>
                                    <small class="text-muted"><?= e($review["created_at"]) ?></small>
                                </div>
                                <div>⭐ <?= (int)$review["rating"] ?> / 5</div>
                                <?php if (!empty($review["comment"])): ?>
                                    <div class="mt-2"><?= nl2br(e($review["comment"])) ?></div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="info">
                    <h3 style="margin:0 0 10px 0;">Infos</h3>
                    <ul style="margin:0; padding-left:18px; color:var(--muted);">
                        <li>WLAN: <?= !empty($spot["wifi"]) ? "Ja" : "Nein" ?></li>
                        <li>Steckdosen: <?= !empty($spot["power_outlets"]) ? "Ja" : "Nein" ?></li>
                        <li>Lautstaerke: <?= e(quiet_label($spot["quiet_level"] ?? "")) ?></li>
                        <li>Gruppen: <?= !empty($spot["group_friendly"]) ? "Geeignet" : "Eher nicht" ?></li>
                        <?php if (!empty($spot["opening_hours"])): ?>
                            <li>Oeffnungszeiten: <?= e($spot["opening_hours"]) ?></li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>

        <a href="spots.php" class="btn btn-outline" style="border-radius:999px;">Zurueck zur Liste</a>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
