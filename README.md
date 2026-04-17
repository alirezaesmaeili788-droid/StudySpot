# StudySpot

StudySpot is a PHP and MySQL web application designed to help students discover, review, and manage study-friendly places such as cafes, libraries, and coworking spaces. The platform supports multiple user roles, including students, location owners, and administrators.

## Overview

The goal of StudySpot is to make it easier to find suitable places for studying by providing clear information about learning conditions, available facilities, and community feedback. In addition to search and review features, the system also includes owner submissions and an admin workflow for approving new locations.

## Key Features

- Homepage with search entry point and dynamic platform statistics
- Spot listing with filters for type, Wi-Fi, power outlets, group friendliness, and noise level
- Detailed location pages with ratings and user reviews
- User registration, login, and account management
- Owner area for submitting and managing study place requests
- Admin area for reviewing new place submissions
- Contact inbox for administrative message handling

## User Roles

### Guest

- Browse public pages
- View available study spots

### Student

- Register and log in
- Review and rate study spots
- Access personal account features

### Owner

- Submit new study places
- Manage owned locations
- Track submitted requests

### Admin

- Review and approve owner submissions
- Manage incoming contact messages
- Access all administrative workflows

## Tech Stack

- PHP
- MySQL / MariaDB
- Bootstrap 5
- CSS

## Project Structure

- `index.php` - Homepage
- `spots.php` - Spot listing and filtering
- `spot.php` - Spot details and reviews
- `register.php` - User registration
- `login.php` - User login
- `account.php` - User account page
- `ort_anmelden.php` - Owner submission form
- `owner_home.php` - Owner and admin overview
- `admin_requests.php` - Admin approval workflow for new places
- `admin_contacts.php` - Admin inbox for contact messages
- `database/studyspot.sql` - Database schema

## Local Setup with XAMPP

### Requirements

- XAMPP
- PHP 8+
- MySQL or MariaDB
- A browser

### Installation Steps

1. Start XAMPP.
2. Enable `Apache` and `MySQL`.
3. Place the project in:

```text
C:\xampp\htdocs\web
```

4. Open `phpMyAdmin`.
5. Create a database named `studyspot`.
6. Import the SQL schema from:

```text
database/studyspot.sql
```

7. Optionally copy `db.local.example.php` to `db.local.php` and adjust database settings.
8. Open the project in your browser:

```text
http://localhost/web/index.php
```

## Database Configuration

The application resolves database configuration in the following order:

1. `db.local.php`
2. Environment variables such as `DB_HOST`, `DB_USER`, `DB_PASS`, `DB_NAME`, and `DB_PORT`
3. Local XAMPP defaults

Example `db.local.php`:

```php
<?php

return [
    "DB_HOST" => "localhost",
    "DB_PORT" => "3306",
    "DB_USER" => "root",
    "DB_PASS" => "",
    "DB_NAME" => "studyspot",
];
```

## Running the Project

Once Apache and MySQL are running and the database has been imported, the main application entry point is:

```text
http://localhost/web/index.php
```

Useful routes:

- `http://localhost/web/index.php`
- `http://localhost/web/spots.php`
- `http://localhost/web/login.php`
- `http://localhost/web/register.php`
- `http://localhost/web/admin_requests.php`

## Git and GitHub Workflow

StudySpot is well suited for version control with Git and hosting on GitHub as a source code repository.

Typical workflow:

1. Edit files locally in VS Code
2. Save your changes
3. Review them in GitHub Desktop or Git
4. Commit your changes
5. Push to GitHub

Example:

```bash
git add .
git commit -m "Update StudySpot documentation"
git push
```

## Deployment Note

GitHub Pages cannot run this project because it does not support PHP or MySQL. To deploy StudySpot online, you need a hosting provider that supports both PHP and a database.

Possible options:

- Render
- Railway
- InfinityFree
- 000webhost
- Traditional PHP/MySQL hosting

## Repository Notes

- The `uploads/requests` and `uploads/spots` directories remain in the repository structure.
- Uploaded files themselves are excluded through `.gitignore`.
- Local credentials should never be committed to GitHub.
- Use `db.local.php` only for local or private environment configuration.

## Future Improvements

Potential next steps for the project include:

- Favorites and saved study spots
- Group study planning features
- Media sharing or vlog integration
- Special offers and promotions for partner locations
- Improved analytics and moderation tools

## License

No license has been assigned yet. If you plan to publish the repository publicly, consider adding an open-source license such as MIT.
