<?php

require "auth.php";
require "db.php";

require_role(['admin', 'owner']);

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    redirect("spots.php");
}

$stmt = $conn->prepare("SELECT * FROM spots WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$spot = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$spot) {
    http_response_code(404);
    exit('404 - Spot nicht gefunden');
}

if (!can_manage_spot($spot['created_by'] ?? 0)) {
    http_response_code(403);
    exit('403 - Kein Zugriff');
}

$errors = [];
$success = "";

if (is_post_request()) {
    verify_csrf();

    $name = trim($_POST['name'] ?? '');
    $type = trim($_POST['type'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $zip = trim($_POST['zip'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $openingHours = trim($_POST['opening_hours'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $wifi = isset($_POST['wifi']) ? 1 : 0;
    $powerOutlets = isset($_POST['power_outlets']) ? 1 : 0;
    $groupFriendly = isset($_POST['group_friendly']) ? 1 : 0;
    $quietLevel = trim($_POST['quiet_level'] ?? 'medium');

    if ($name === '' || $address === '' || $zip === '' || $city === '') {
        $errors[] = "Bitte Name, Adresse, PLZ und Stadt ausfuellen.";
    }
    if (!array_key_exists($type, spot_type_options())) {
        $errors[] = "Bitte waehle einen gueltigen Typ.";
    }
    if (!array_key_exists($quietLevel, STUDYSPOT_QUIET_LEVELS)) {
        $errors[] = "Bitte waehle eine gueltige Lautstaerke.";
    }

    $imagePath = $spot['image_url'] ?? '';
    if (!$errors) {
        [$uploadOk, $newImagePath, $uploadError] = upload_image($_FILES['image'] ?? [], "spots", "spot_", 2 * 1024 * 1024);
        if (!$uploadOk) {
            $errors[] = $uploadError;
        } elseif ($newImagePath !== null) {
            $imagePath = $newImagePath;
        }
    }

    if (!$errors) {
        $stmt = $conn->prepare(
            "UPDATE spots
             SET name = ?, type = ?, address = ?, zip = ?, city = ?, opening_hours = ?, description = ?, image_url = ?,
                 wifi = ?, power_outlets = ?, quiet_level = ?, group_friendly = ?
             WHERE id = ?"
        );
        $stmt->bind_param(
            "ssssssssiisii",
            $name,
            $type,
            $address,
            $zip,
            $city,
            $openingHours,
            $description,
            $imagePath,
            $wifi,
            $powerOutlets,
            $quietLevel,
            $groupFriendly,
            $id
        );
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare("SELECT * FROM spots WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $spot = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $success = "Spot wurde gespeichert.";
    }
}
?>
<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>StudySpot | Spot bearbeiten</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="register.css">
</head>
<body>

<?php include "navbar.php"; ?>

<main class="page-space">
    <div class="wrap" style="max-width: 900px;">
        <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
            <h1 style="margin:0; color:#1F5E3B;">Spot bearbeiten</h1>
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

        <?php if ($success): ?>
            <div class="alert alert-success"><?= e($success) ?></div>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data" class="bg-white p-4" style="border-radius:20px; border:1px solid var(--border); box-shadow:var(--shadow);">
            <?= csrf_field() ?>

            <div class="row g-3">
                <div class="col-md-8">
                    <label class="form-label" for="name">Name</label>
                    <input id="name" class="form-control" name="name" value="<?= e($spot['name'] ?? '') ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="type">Typ</label>
                    <select id="type" class="form-select" name="type" required>
                        <?php foreach (spot_type_options() as $value => $label): ?>
                            <option value="<?= e($value) ?>" <?= selected($value, (string)($spot['type'] ?? '')) ?>><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-8">
                    <label class="form-label" for="address">Adresse</label>
                    <input id="address" class="form-control" name="address" value="<?= e($spot['address'] ?? '') ?>" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="zip">PLZ</label>
                    <input id="zip" class="form-control" name="zip" value="<?= e($spot['zip'] ?? '') ?>" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="city">Stadt</label>
                    <input id="city" class="form-control" name="city" value="<?= e($spot['city'] ?? '') ?>" required>
                </div>

                <div class="col-md-12">
                    <label class="form-label" for="opening_hours">Oeffnungszeiten</label>
                    <input id="opening_hours" class="form-control" name="opening_hours" value="<?= e($spot['opening_hours'] ?? '') ?>" placeholder="z.B. Mo-Fr 08:00-18:00">
                </div>

                <div class="col-md-12">
                    <label class="form-label" for="description">Beschreibung</label>
                    <textarea id="description" class="form-control" name="description" rows="5"><?= e($spot['description'] ?? '') ?></textarea>
                </div>

                <div class="col-md-6">
                    <label class="form-label" for="quiet_level">Lautstaerke</label>
                    <select id="quiet_level" class="form-select" name="quiet_level">
                        <?php foreach (STUDYSPOT_QUIET_LEVELS as $value => $label): ?>
                            <option value="<?= e($value) ?>" <?= selected($value, (string)($spot['quiet_level'] ?? 'medium')) ?>><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label" for="image">Bild</label>
                    <input id="image" class="form-control" type="file" name="image" accept="image/png,image/jpeg,image/webp">
                    <div class="form-text">Maximal 2 MB, JPG, PNG oder WEBP.</div>
                </div>

                <div class="col-md-12">
                    <div class="d-flex flex-wrap gap-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="wifi" name="wifi" <?= checked(!empty($spot['wifi'])) ?>>
                            <label class="form-check-label" for="wifi">WLAN</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="power_outlets" name="power_outlets" <?= checked(!empty($spot['power_outlets'])) ?>>
                            <label class="form-check-label" for="power_outlets">Steckdosen</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="group_friendly" name="group_friendly" <?= checked(!empty($spot['group_friendly'])) ?>>
                            <label class="form-check-label" for="group_friendly">Gruppenfreundlich</label>
                        </div>
                    </div>
                </div>

                <div class="col-md-12 d-flex gap-2">
                    <button class="btn btn-primary" type="submit">Speichern</button>
                    <a class="btn btn-outline" href="spot.php?id=<?= (int)$spot['id'] ?>">Zurueck</a>
                </div>
            </div>
        </form>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
