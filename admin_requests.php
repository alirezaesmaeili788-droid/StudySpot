<?php

require_once "helpers.php";
require "db.php";
require_role(['admin']);

$message = "";
$error = "";

if (is_post_request()) {
    verify_csrf();

    $requestId = (int)($_POST["request_id"] ?? 0);
    $action = $_POST["action"] ?? "";

    if ($requestId > 0 && in_array($action, ['approve', 'reject'], true)) {
        $stmt = $conn->prepare("SELECT * FROM place_requests WHERE id = ? AND status = 'pending'");
        $stmt->bind_param("i", $requestId);
        $stmt->execute();
        $request = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$request) {
            $error = "Die Anfrage ist nicht mehr offen oder existiert nicht.";
        } elseif ($action === 'reject') {
            $stmt = $conn->prepare("UPDATE place_requests SET status = 'rejected' WHERE id = ?");
            $stmt->bind_param("i", $requestId);
            $stmt->execute();
            $stmt->close();
            $message = "Die Anfrage wurde abgelehnt.";
        } else {
            $features = json_decode($request["features"] ?? "[]", true);
            $suitable = json_decode($request["suitable"] ?? "[]", true);
            $features = is_array($features) ? $features : [];
            $suitable = is_array($suitable) ? $suitable : [];

            $wifi = in_array("wifi", $features, true) ? 1 : 0;
            $power = in_array("steckdosen", $features, true) ? 1 : 0;
            $groupFriendly = in_array("gruppenarbeit", $suitable, true) ? 1 : 0;
            $quietLevel = "medium";

            if (in_array("einzellernen", $suitable, true) && !in_array("gruppenarbeit", $suitable, true)) {
                $quietLevel = "quiet";
            } elseif (in_array("gruppenarbeit", $suitable, true) && !in_array("einzellernen", $suitable, true)) {
                $quietLevel = "loud";
            }

            $mappedType = [
                "cafe" => "Cafe",
                "bibliothek" => "Bibliothek",
                "coworking" => "CoWorking",
                "sonstiges" => "Sonstiges",
            ][$request["place_type"] ?? ""] ?? "Sonstiges";

            $conn->begin_transaction();

            try {
                $stmt = $conn->prepare(
                    "INSERT INTO spots
                     (name, type, address, zip, city, opening_hours, description, image_url, wifi, power_outlets, quiet_level, group_friendly, created_by)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
                );
                $createdBy = (int)($request["created_by"] ?? 0);
                $stmt->bind_param(
                    "ssssssssiisii",
                    $request["place_name"],
                    $mappedType,
                    $request["street"],
                    $request["zip"],
                    $request["city"],
                    $request["hours"],
                    $request["description"],
                    $request["photo_url"],
                    $wifi,
                    $power,
                    $quietLevel,
                    $groupFriendly,
                    $createdBy
                );
                $stmt->execute();
                $newSpotId = $stmt->insert_id;
                $stmt->close();

                $stmt = $conn->prepare("UPDATE place_requests SET status = 'approved' WHERE id = ?");
                $stmt->bind_param("i", $requestId);
                $stmt->execute();
                $stmt->close();

                $conn->commit();
                redirect(build_url("spot.php", ["id" => $newSpotId]));
            } catch (Throwable $throwable) {
                $conn->rollback();
                $error = "Die Anfrage konnte nicht freigegeben werden.";
            }
        }
    }
}

$result = $conn->query(
    "SELECT id, place_name, place_type, email, phone, city, street, zip, website, hours, suitable, features, description, notes, photo_url, created_at
     FROM place_requests
     WHERE status = 'pending'
     ORDER BY created_at DESC, id DESC"
);
$requests = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
?>
<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>StudySpot | Admin Anfragen</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body class="Homepage-body">

<?php include "navbar.php"; ?>

<main class="mt-5 pt-4">
    <div class="container py-4" style="max-width: 1100px;">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
            <div>
                <h1 class="h3 fw-bold mb-1 text-success">Ort-Anfragen</h1>
                <p class="text-muted mb-0">Hier pruefst du neue Einreichungen von Ownern und uebernimmst sie in die Spot-Liste.</p>
            </div>
            <a href="admin_contacts.php" class="btn btn-outline-success">Kontakt-Inbox</a>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success"><?= e($message) ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= e($error) ?></div>
        <?php endif; ?>

        <?php if (!$requests): ?>
            <div class="alert alert-info">Keine offenen Anfragen vorhanden.</div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($requests as $request): ?>
                    <?php
                    $requestFeatures = json_decode($request["features"] ?? "[]", true);
                    $requestSuitable = json_decode($request["suitable"] ?? "[]", true);
                    $requestFeatures = is_array($requestFeatures) ? $requestFeatures : [];
                    $requestSuitable = is_array($requestSuitable) ? $requestSuitable : [];
                    ?>
                    <div class="col-12">
                        <div class="bg-white rounded-4 shadow-sm p-4">
                            <div class="row g-4">
                                <div class="col-12 col-lg-8">
                                    <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                                        <h2 class="h4 fw-bold mb-0"><?= e($request["place_name"]) ?></h2>
                                        <span class="badge text-bg-light"><?= e(request_place_type_options()[$request["place_type"]] ?? $request["place_type"]) ?></span>
                                    </div>

                                    <p class="text-muted mb-3">
                                        <?= e(trim(($request["street"] ?? "") . ", " . ($request["zip"] ?? "") . " " . ($request["city"] ?? ""))) ?>
                                    </p>

                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <strong>Kontakt</strong>
                                            <div class="small text-muted"><?= e($request["email"]) ?></div>
                                            <?php if (!empty($request["phone"])): ?>
                                                <div class="small text-muted"><?= e($request["phone"]) ?></div>
                                            <?php endif; ?>
                                        </div>

                                        <div class="col-md-6">
                                            <strong>Eingereicht am</strong>
                                            <div class="small text-muted"><?= e($request["created_at"]) ?></div>
                                            <?php if (!empty($request["website"])): ?>
                                                <div class="small"><a href="<?= e($request["website"]) ?>" target="_blank" rel="noopener">Website ansehen</a></div>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <div class="mt-3">
                                        <strong>Oeffnungszeiten</strong>
                                        <p class="small text-muted mb-0"><?= nl2br(e($request["hours"])) ?></p>
                                    </div>

                                    <div class="mt-3">
                                        <strong>Beschreibung</strong>
                                        <p class="small text-muted mb-0"><?= nl2br(e($request["description"])) ?></p>
                                    </div>

                                    <?php if (!empty($requestSuitable)): ?>
                                        <div class="mt-3">
                                            <strong>Geeignet fuer</strong>
                                            <div class="d-flex flex-wrap gap-2 mt-2">
                                                <?php foreach ($requestSuitable as $value): ?>
                                                    <span class="badge text-bg-light"><?= e(STUDYSPOT_REQUEST_SUITABLE[$value] ?? $value) ?></span>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (!empty($requestFeatures)): ?>
                                        <div class="mt-3">
                                            <strong>Ausstattung</strong>
                                            <div class="d-flex flex-wrap gap-2 mt-2">
                                                <?php foreach ($requestFeatures as $value): ?>
                                                    <span class="badge text-bg-light"><?= e(STUDYSPOT_REQUEST_FEATURES[$value] ?? $value) ?></span>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (!empty($request["notes"])): ?>
                                        <div class="mt-3">
                                            <strong>Zusatzhinweise</strong>
                                            <p class="small text-muted mb-0"><?= nl2br(e($request["notes"])) ?></p>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="col-12 col-lg-4">
                                    <?php if (!empty($request["photo_url"])): ?>
                                        <img src="<?= e($request["photo_url"]) ?>" alt="<?= e($request["place_name"]) ?>" class="img-fluid rounded-4 mb-3">
                                    <?php endif; ?>

                                    <div class="d-grid gap-2">
                                        <form method="post">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="request_id" value="<?= (int)$request["id"] ?>">
                                            <button type="submit" name="action" value="approve" class="btn btn-success w-100">Freigeben und als Spot anlegen</button>
                                        </form>

                                        <form method="post">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="request_id" value="<?= (int)$request["id"] ?>">
                                            <button type="submit" name="action" value="reject" class="btn btn-outline-danger w-100">Ablehnen</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
