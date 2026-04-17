<?php

require_once "helpers.php";
require "db.php";

$role = current_role();
$isLoggedIn = is_logged_in();

$stats = [
    "spots" => 0,
    "reviews" => 0,
    "owners" => 0,
];

$result = $conn->query(
    "SELECT
        (SELECT COUNT(*) FROM spots) AS total_spots,
        (SELECT COUNT(*) FROM reviews) AS total_reviews,
        (SELECT COUNT(*) FROM users WHERE role = 'owner') AS total_owners"
);
if ($result) {
    $statsRow = $result->fetch_assoc();
    $stats["spots"] = (int)($statsRow["total_spots"] ?? 0);
    $stats["reviews"] = (int)($statsRow["total_reviews"] ?? 0);
    $stats["owners"] = (int)($statsRow["total_owners"] ?? 0);
}

$topSpot = null;
$stmt = $conn->prepare(
    "SELECT s.id, s.name, s.type, s.city, s.image_url, ROUND(AVG(r.rating), 1) AS avg_rating, COUNT(r.id) AS review_count
     FROM spots s
     JOIN reviews r ON r.spot_id = s.id
     GROUP BY s.id
     HAVING COUNT(r.id) > 0
     ORDER BY avg_rating DESC, review_count DESC, s.name ASC
     LIMIT 1"
);
$stmt->execute();
$topSpot = $stmt->get_result()->fetch_assoc();
$stmt->close();

$latestSpots = [];
$stmt = $conn->prepare(
    "SELECT id, name, type, city, description, image_url
     FROM spots
     ORDER BY created_at DESC, id DESC
     LIMIT 3"
);
$stmt->execute();
$latestSpots = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudySpot | Home</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
</head>
<body class="Homepage-body">

<?php include "navbar.php"; ?>

<section class="py-5 hero-wrapper">
    <div class="container">
        <div class="hero-card rounded-4 p-4 p-md-5">
            <div class="row gy-4 align-items-stretch">
                <div class="col-12 col-lg-7">
                    <div class="cafe-box bg-white rounded-4 shadow-sm h-100">
                        <img src="Images/cafe-find.png" alt="Lernort finden" class="img-fluid hero-image-c">

                        <div class="cafe-s-box p-4">
                            <div class="badge-soft mb-3">Lernorte einfach finden</div>
                            <h1 class="fw-bold mb-3">Wir machen Lernen planbarer.</h1>
                            <p class="lead mb-4">
                                StudySpot hilft Studierenden und Schuelerinnen, passende Lernorte in Wien zu entdecken:
                                Cafes, Bibliotheken und ruhige Plaetze mit den Infos, die im Alltag wirklich zaehlen.
                            </p>

                            <form class="hero-search" method="get" action="spots.php">
                                <div class="input-group mb-3">
                                    <input type="text" class="form-control" name="q" placeholder="Bezirk, Adresse oder Namen eingeben">
                                    <button class="btn btn-success" type="submit">Suchen</button>
                                </div>
                            </form>

                            <div class="d-flex flex-wrap gap-2">
                                <a href="spots.php" class="btn btn-outline-success Spotsbtn">Alle Spots anzeigen</a>

                                <?php if (!$isLoggedIn): ?>
                                    <a href="login.php" class="btn btn-success">Login</a>
                                    <a href="register.php" class="btn btn-outline-success">Registrieren</a>
                                <?php elseif ($role === "owner" || $role === "admin"): ?>
                                    <a href="ort_anmelden.php" class="btn btn-success">Ort anmelden</a>
                                    <a href="owner_home.php" class="btn btn-outline-success">Meine Orte</a>
                                <?php else: ?>
                                    <a href="account.php" class="btn btn-success">Mein Account</a>
                                <?php endif; ?>
                            </div>

                            <div class="row g-3 mt-3">
                                <div class="col-4">
                                    <div class="feature-stat">
                                        <strong><?= $stats["spots"] ?></strong>
                                        <span>Spots</span>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="feature-stat">
                                        <strong><?= $stats["reviews"] ?></strong>
                                        <span>Bewertungen</span>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="feature-stat">
                                        <strong><?= $stats["owners"] ?></strong>
                                        <span>Owner</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-lg-5 d-flex flex-column gap-3">
                    <?php if ($role === "owner" || $role === "admin"): ?>
                        <div class="cafe-box bg-white rounded-4 shadow-sm">
                            <img src="Images/cafe-reg.png" alt="Ort anmelden" class="img-fluid hero-image-c">
                            <div class="cafe-info-box p-4">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="cafe-icon me-3">🏪</div>
                                    <h4 class="fw-bold mb-0">Fuer Betreiber</h4>
                                </div>
                                <p class="small text-muted mb-3">
                                    Reiche neue Lernorte ein, verwalte deine Spots und verfolge den Status offener Anfragen.
                                </p>
                                <div class="d-grid gap-2">
                                    <a href="ort_anmelden.php" class="btn btn-outline-success">Ort einreichen</a>
                                    <a href="owner_home.php" class="btn btn-success">Zum Owner-Bereich</a>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="cafe-box bg-white rounded-4 shadow-sm flex-fill">
                            <div class="cafe-info-box p-4">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="cafe-icon me-3">ℹ️</div>
                                    <h4 class="fw-bold mb-0">Was StudySpot kann</h4>
                                </div>
                                <p class="small text-muted mb-0">
                                    Filtere nach WLAN, Steckdosen, Lautstaerke und Gruppentauglichkeit, sieh dir Bewertungen an
                                    und entdecke Lernorte fuer spontane oder lange Sessions.
                                </p>
                                <a href="spots.php" class="btn btn-outline-success w-100 mt-3">Spots entdecken</a>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="cafe-box bg-white rounded-4 shadow-sm">
                        <div class="cafe-info-box p-4">
                            <div class="d-flex align-items-center mb-3">
                                <div class="cafe-icon me-3">⭐</div>
                                <h4 class="fw-bold mb-0">Top bewertet</h4>
                            </div>

                            <?php if ($topSpot): ?>
                                <div class="d-flex gap-3 align-items-center">
                                    <?php if (!empty($topSpot["image_url"])): ?>
                                        <img src="<?= e($topSpot["image_url"]) ?>" alt="<?= e($topSpot["name"]) ?>" class="img-fluid top-spot-thumb">
                                    <?php else: ?>
                                        <div class="top-spot-thumb top-spot-empty"></div>
                                    <?php endif; ?>

                                    <div>
                                        <div class="fw-bold"><?= e($topSpot["name"]) ?></div>
                                        <div class="small text-muted"><?= e($topSpot["type"]) ?> · <?= e($topSpot["city"]) ?></div>
                                        <div class="small mt-1 text-success fw-semibold">⭐ <?= e((string)$topSpot["avg_rating"]) ?>/5</div>
                                        <div class="small text-muted"><?= (int)$topSpot["review_count"] ?> Bewertungen</div>
                                    </div>
                                </div>

                                <a href="spot.php?id=<?= (int)$topSpot["id"] ?>" class="btn btn-success w-100 mt-3">Zum Spot</a>
                            <?php else: ?>
                                <p class="small text-muted mb-0">Noch keine Bewertungen vorhanden. Der erste Eindruck wartet noch auf seine erste Stimme.</p>
                                <a href="spots.php" class="btn btn-outline-success w-100 mt-3">Spots ansehen</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="about-section py-2 mb-4">
    <div class="container">
        <div class="row g-4 rounded-4 mt-0 mb-4 shadow-sm p-4 about-d">
            <div class="col-12 text-center">
                <h2 class="fw-bold mb-3">Passende Lernorte statt langem Suchen</h2>
                <p class="text-muted mb-0">
                    Die Projektspezifikation von StudySpot fokussiert auf verlassliche Lerninfos:
                    Oeffnungszeiten, Ausstattung, Bewertungen und Einreichungen durch Betreiber.
                    Genau darauf ist die aktuelle Version jetzt sichtbar ausgerichtet.
                </p>
            </div>

            <div class="col-12">
                <div class="row g-3">
                    <div class="col-12 col-md-4">
                        <div class="about-card h-100 p-3 rounded-4 shadow-sm">
                            <div class="about-icon">📍</div>
                            <h5 class="fw-semibold mb-1">Gezielt suchen</h5>
                            <p class="small text-muted mb-0">Filtere nach Typ, WLAN, Steckdosen, Gruppenfreundlichkeit und Lautstaerke.</p>
                        </div>
                    </div>

                    <div class="col-12 col-md-4">
                        <div class="about-card h-100 p-3 rounded-4 shadow-sm">
                            <div class="about-icon">📝</div>
                            <h5 class="fw-semibold mb-1">Echte Eindruecke</h5>
                            <p class="small text-muted mb-0">Angemeldete Nutzer koennen Lernorte bewerten und Erfahrungen teilen.</p>
                        </div>
                    </div>

                    <div class="col-12 col-md-4">
                        <div class="about-card h-100 p-3 rounded-4 shadow-sm">
                            <div class="about-icon">🏢</div>
                            <h5 class="fw-semibold mb-1">Owner-Workflow</h5>
                            <p class="small text-muted mb-0">Betreiber reichen Orte ein, Admins pruefen sie und Spots werden sauber uebernommen.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php if ($latestSpots): ?>
    <section class="pb-5">
        <div class="container">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <div>
                    <h2 class="fw-bold mb-1">Neu im Verzeichnis</h2>
                    <p class="text-muted mb-0">Die zuletzt hinzugefuegten Lernorte.</p>
                </div>
                <a href="spots.php" class="btn btn-outline-success">Alle Spots</a>
            </div>

            <div class="row g-4">
                <?php foreach ($latestSpots as $spot): ?>
                    <div class="col-12 col-lg-4">
                        <div class="about-card h-100 p-3 rounded-4 shadow-sm bg-white">
                            <?php if (!empty($spot["image_url"])): ?>
                                <img src="<?= e($spot["image_url"]) ?>" alt="<?= e($spot["name"]) ?>" class="img-fluid latest-spot-image mb-3">
                            <?php endif; ?>
                            <div class="badge-soft mb-2"><?= e($spot["type"]) ?></div>
                            <h5 class="fw-bold"><?= e($spot["name"]) ?></h5>
                            <p class="small text-muted mb-2"><?= e($spot["city"]) ?></p>
                            <p class="small text-muted"><?= e(mb_strimwidth($spot["description"] ?? "", 0, 120, "...")) ?></p>
                            <a href="spot.php?id=<?= (int)$spot["id"] ?>" class="btn btn-outline-success mt-auto">Mehr erfahren</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
<?php endif; ?>

<footer class="footer mt-5 py-4">
    <div class="container">
        <div class="row g-4">
            <div class="col-12 col-md-4">
                <h5 class="fw-bold mb-2">StudySpot</h5>
                <p class="small text-muted">Finde die besten Lernorte in Wien - Cafes, Bibliotheken und weitere ruhige Orte fuer produktive Sessions.</p>
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
