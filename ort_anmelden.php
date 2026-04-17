<?php

require_once "helpers.php";
require "db.php";
require_role(['owner', 'admin']);

$errors = [];
$success = false;

if (!isset($_POST["email"]) && !empty($_SESSION["user_email"])) {
    $_POST["email"] = (string)$_SESSION["user_email"];
}

if (is_post_request()) {
    verify_csrf();

    $placeType = trim($_POST["place_type"] ?? "");
    $placeName = trim($_POST["place_name"] ?? "");
    $contactPerson = trim($_POST["contact_person"] ?? "");
    $email = strtolower(trim($_POST["email"] ?? ""));
    $phone = trim($_POST["phone"] ?? "");
    $street = trim($_POST["street"] ?? "");
    $zip = trim($_POST["zip"] ?? "");
    $city = trim($_POST["city"] ?? "");
    $district = trim($_POST["district"] ?? "");
    $website = trim($_POST["website"] ?? "");
    $hours = trim($_POST["hours"] ?? "");
    $description = trim($_POST["description"] ?? "");
    $notes = trim($_POST["notes"] ?? "");
    $consent = isset($_POST["consent"]);

    $suitable = post_checkbox_values("suitable", STUDYSPOT_REQUEST_SUITABLE);
    $features = post_checkbox_values("features", STUDYSPOT_REQUEST_FEATURES);

    if (!array_key_exists($placeType, request_place_type_options())) {
        $errors[] = "Bitte waehle eine gueltige Art des Ortes.";
    }
    if ($placeName === "" || $email === "" || $street === "" || $zip === "" || $city === "" || $hours === "" || $description === "") {
        $errors[] = "Bitte alle Pflichtfelder ausfuellen.";
    }
    if ($email !== "" && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Bitte eine gueltige E-Mail-Adresse eingeben.";
    }
    if ($website !== "" && !filter_var($website, FILTER_VALIDATE_URL)) {
        $errors[] = "Bitte gib eine vollstaendige Website-URL inklusive http:// oder https:// an.";
    }
    if ($district !== "") {
        $districtNumber = (int)$district;
        if ($districtNumber < 1 || $districtNumber > 23) {
            $errors[] = "Der Bezirk muss zwischen 1 und 23 liegen.";
        }
    } else {
        $districtNumber = null;
    }
    if (!$consent) {
        $errors[] = "Bitte bestaetige die Einwilligung zur Pruefung der Angaben.";
    }

    $photoUrl = null;
    if (!$errors) {
        [$uploadOk, $photoUrl, $uploadError] = upload_image($_FILES["photo"] ?? [], "requests", "req_", 3 * 1024 * 1024);
        if (!$uploadOk) {
            $errors[] = $uploadError;
        }
    }

    if (!$errors) {
        $suitableJson = json_encode($suitable, JSON_UNESCAPED_UNICODE);
        $featuresJson = json_encode($features, JSON_UNESCAPED_UNICODE);
        $createdBy = current_user_id();

        $stmt = $conn->prepare(
            "INSERT INTO place_requests
             (place_type, place_name, contact_person, email, phone, street, zip, city, district, website, hours, suitable, features, description, notes, photo_url, created_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param(
            "ssssssssisssssssi",
            $placeType,
            $placeName,
            $contactPerson,
            $email,
            $phone,
            $street,
            $zip,
            $city,
            $districtNumber,
            $website,
            $hours,
            $suitableJson,
            $featuresJson,
            $description,
            $notes,
            $photoUrl,
            $createdBy
        );
        $stmt->execute();
        $stmt->close();

        $success = true;
        $_POST = ["email" => (string)($_SESSION["user_email"] ?? "")];
    }
}

$selectedSuitable = post_checkbox_values("suitable", STUDYSPOT_REQUEST_SUITABLE);
$selectedFeatures = post_checkbox_values("features", STUDYSPOT_REQUEST_FEATURES);
?>
<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <title>StudySpot | Ort anmelden</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
</head>
<body class="spots-body">

<?php include "navbar.php"; ?>

<div class="container py-5" style="max-width: 960px;">
    <h1 class="h3 fw-bold mb-3 text-center">Lernort bei StudySpot einreichen</h1>
    <p class="small text-muted text-center mb-4">
        Reiche dein Cafe, deine Bibliothek oder einen anderen Lernort ein.
        Das Admin-Team prueft die Angaben und uebernimmt den Ort danach in die Spot-Liste.
    </p>

    <?php if ($success): ?>
        <div class="alert alert-success">Danke! Deine Anfrage wurde erfolgreich gesendet.</div>
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

    <form method="post" enctype="multipart/form-data" class="bg-white p-4 rounded-4 shadow-sm">
        <?= csrf_field() ?>

        <div class="mb-3">
            <label class="form-label" for="place_type">Art des Ortes *</label>
            <select name="place_type" id="place_type" class="form-select" required>
                <option value="">Bitte auswaehlen</option>
                <?php foreach (request_place_type_options() as $value => $label): ?>
                    <option value="<?= e($value) ?>" <?= selected($value, (string)($_POST["place_type"] ?? "")) ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label" for="place_name">Name des Ortes *</label>
                <input type="text" id="place_name" name="place_name" class="form-control" required value="<?= old("place_name") ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label" for="contact_person">Ansprechperson</label>
                <input type="text" id="contact_person" name="contact_person" class="form-control" value="<?= old("contact_person") ?>">
            </div>
        </div>

        <div class="row g-3 mt-1">
            <div class="col-md-6">
                <label class="form-label" for="email">E-Mail-Adresse *</label>
                <input type="email" id="email" name="email" class="form-control" required value="<?= old("email", (string)($_SESSION["user_email"] ?? "")) ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label" for="phone">Telefonnummer</label>
                <input type="text" id="phone" name="phone" class="form-control" value="<?= old("phone") ?>">
            </div>
        </div>

        <div class="mt-3 mb-1">
            <label class="form-label">Adresse *</label>
        </div>

        <div class="row g-3">
            <div class="col-md-6">
                <input type="text" name="street" class="form-control" placeholder="Strasse und Hausnummer" required value="<?= old("street") ?>">
            </div>
            <div class="col-md-3">
                <input type="text" name="zip" class="form-control" placeholder="PLZ" required value="<?= old("zip") ?>">
            </div>
            <div class="col-md-3">
                <input type="text" name="city" class="form-control" placeholder="Ort" required value="<?= old("city", "Wien") ?>">
            </div>
        </div>

        <div class="row g-3 mt-1">
            <div class="col-md-4">
                <input type="number" name="district" min="1" max="23" class="form-control" placeholder="Bezirk (1-23)" value="<?= old("district") ?>">
            </div>
            <div class="col-md-8">
                <input type="url" name="website" class="form-control" placeholder="Website oder Instagram-Link" value="<?= old("website") ?>">
            </div>
        </div>

        <div class="mt-3">
            <label class="form-label" for="hours">Oeffnungszeiten *</label>
            <textarea id="hours" name="hours" class="form-control" rows="3" required><?= old("hours") ?></textarea>
        </div>

        <div class="mt-3">
            <label class="form-label" for="photo">Foto hochladen</label>
            <input type="file" id="photo" name="photo" class="form-control" accept="image/png,image/jpeg,image/webp">
            <div class="form-text">JPG, PNG oder WEBP. Maximal 3 MB.</div>
        </div>

        <div class="mt-4">
            <label class="form-label d-block">Wofuer ist der Ort besonders geeignet?</label>
            <div class="row g-2">
                <?php foreach (STUDYSPOT_REQUEST_SUITABLE as $value => $label): ?>
                    <div class="col-md-6">
                        <div class="form-check border rounded-3 p-3 h-100">
                            <input class="form-check-input" type="checkbox" name="suitable[]" id="suitable_<?= e($value) ?>" value="<?= e($value) ?>" <?= checked(in_array($value, $selectedSuitable, true)) ?>>
                            <label class="form-check-label" for="suitable_<?= e($value) ?>"><?= e($label) ?></label>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="mt-4">
            <label class="form-label d-block">Ausstattung</label>
            <div class="row g-2">
                <?php foreach (STUDYSPOT_REQUEST_FEATURES as $value => $label): ?>
                    <div class="col-md-6">
                        <div class="form-check border rounded-3 p-3 h-100">
                            <input class="form-check-input" type="checkbox" name="features[]" id="feature_<?= e($value) ?>" value="<?= e($value) ?>" <?= checked(in_array($value, $selectedFeatures, true)) ?>>
                            <label class="form-check-label" for="feature_<?= e($value) ?>"><?= e($label) ?></label>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="mt-3">
            <label class="form-label" for="description">Kurzbeschreibung *</label>
            <textarea id="description" name="description" class="form-control" rows="4" required><?= old("description") ?></textarea>
        </div>

        <div class="mt-3">
            <label class="form-label" for="notes">Zusaetzliche Hinweise</label>
            <textarea id="notes" name="notes" class="form-control" rows="3"><?= old("notes") ?></textarea>
        </div>

        <div class="mt-3 mb-4">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="consent" id="consent" required <?= checked(isset($_POST["consent"])) ?>>
                <label class="form-check-label small" for="consent">
                    Ich bestaetige, dass ich diesen Ort einreichen darf und dass StudySpot meine Angaben zur Pruefung und Kontaktaufnahme speichern darf.
                </label>
            </div>
        </div>

        <button type="submit" class="btn btn-success w-100">Anfrage absenden</button>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
