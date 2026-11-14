# RusStart

A website for international students who are new to Russia.

- **Articles**: students write about their experience (visa, dormitory, SIM cards, winter) and newcomers search and read them by city and topic.
- **Marketplace**: people already in Russia post advertisements; newcomers contact them by phone, WhatsApp or Telegram.
- **Accounts**: sign up with name, username, email and password; log in with email or username, with "Keep me logged in".
- **Account pages**: My articles, My advertisements, Profile (details and photo) and Security (password, 2-step verification with an authenticator app, signed-in devices, deactivate account).

- **Two languages**: English at `/`, Russian at `/ru/`. Visitors whose browser is set to Russian are sent to `/ru/` on their first visit, and anyone can switch with the selector in the header.

Built with plain PHP 8 and MySQL/MariaDB, so it runs on XAMPP and on ordinary PHP hosting. No Composer or build step.

## Run it locally (XAMPP)

1. Start **Apache** and **MySQL** in the XAMPP control panel.
2. Put this folder in `C:\xampp\htdocs\russtart` (or link it there).
3. Create an empty database called `russtart` (phpMyAdmin > New). The tables, default cities and categories, and the demo content are created automatically on the first visit.
4. Check the database settings in `config/config.php`. To change them without touching that file, copy `config/config.local.example.php` to `config/config.local.php`.
5. Open <http://localhost/russtart/>.

## Database migrations

Every file in `database/migrations` runs once, in order, and is recorded in the `migrations` table. They run automatically on the first page visit after a new file appears, or by hand with `php database/migrate.php`.

- `0001_initial_schema.sql`: all tables, default cities and categories.
- `0002_demo_content.php`: demo students, articles and advertisements (only while `'demo_data' => true`). Demo accounts use `@russtart.demo` emails and random passwords. Remove all demo content with `DELETE FROM users WHERE email LIKE '%@russtart.demo';`

To change the database later, add a new file such as `0003_add_something.sql`.

## Password reset emails

"Forgot password?" is switched off for now (`'password_reset' => false` in `config/config.php`). When it is on, it sends a link that works for one hour. On your PC the emails are not sent: they are saved as text files in `storage/mail`, and the page shows the link so you can test it.

To send real emails, set `mail` in `config/config.local.php` (see `config.local.example.php`). With Gmail: turn on 2-step verification, create an App Password, and use host `smtp.gmail.com`, port `587`, secure `tls`.

## Updating the live site

Pushing to `main` on GitHub uploads the changed files to InfinityFree automatically (`.github/workflows/deploy.yml`). One-time setup: in the GitHub repository open Settings > Secrets and variables > Actions and add `FTP_SERVER` (ftpupload.net), `FTP_USERNAME` and `FTP_PASSWORD` from InfinityFree > FTP Details. Progress shows in the repository's Actions tab. Files on the server that are not in git (`config/config.local.php`, uploaded photos) are never touched, and database changes run on the first visit after the upload.

## Put it online

1. Upload everything except `.git` into the host's `htdocs` folder.
2. Create an empty database in the host's control panel. The tables are created on the first visit.
3. Create `config/config.local.php` on the host with its database details, the mail settings and `'debug' => false`.
4. Updates to the database run by themselves: upload the new files and open the site once.

## Folders

| Folder | What is in it |
| --- | --- |
| `index.php` | Front controller and list of routes |
| `app/pages` | One file per page: reads input, talks to the database |
| `app/views` | HTML templates |
| `app/helpers.php` | Shared functions (forms, validation, uploads) |
| `assets` | CSS, JavaScript, logo |
| `lang` | Russian translations (`ru.php`). Text missing there shows in English |
| `config` | Settings (database, email, main city) |
| `database` | Migrations (tables, default data, demo content) and `migrate.php` |
| `uploads` | Images uploaded by members |
