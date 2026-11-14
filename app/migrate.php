<?php
/**
 * Database migrations.
 *
 * Every file in database/migrations runs once, in name order, and is recorded in the
 * `migrations` table. They run automatically on the first page request after a new file
 * appears (on your PC and online), or by hand with:  php database/migrate.php
 *
 *   NNNN_name.sql  plain SQL; statements end with ";" at the end of a line
 *   NNNN_name.php  returns function (array $config): bool
 *                  (return false to skip for now; it is tried again later)
 */

function migration_files(): array
{
    $files = glob(ROOT . '/database/migrations/*.{sql,php}', GLOB_BRACE) ?: [];
    sort($files, SORT_STRING);
    return $files;
}

/** Run every migration that has not run yet. Returns the names of the ones that ran. */
function run_migrations(bool $force = false): array
{
    global $config;
    $files = migration_files();

    // Cheap check on every request: nothing to do while the list of files (and the demo switch) is unchanged
    $signature = md5(implode('|', array_map('basename', $files)) . '|demo=' . (int) !empty($config['demo_data']));
    $stamp = ROOT . '/storage/migrations.stamp';
    if (!$force && is_file($stamp) && trim((string) file_get_contents($stamp)) === $signature) {
        return [];
    }

    $db = db();
    // two visitors at the same moment must not run the same migration twice
    if (!(int) $db->query("SELECT GET_LOCK('russtart_migrations', 30)")->fetchColumn()) {
        return [];
    }

    try {
        $db->exec('CREATE TABLE IF NOT EXISTS migrations (
            name   VARCHAR(190) PRIMARY KEY,
            ran_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
        $done = $db->query('SELECT name FROM migrations')->fetchAll(PDO::FETCH_COLUMN);

        $ran = [];
        foreach ($files as $file) {
            $name = basename($file);
            if (in_array($name, $done, true)) {
                continue;
            }
            if (str_ends_with($name, '.sql')) {
                $sql = preg_replace('/^\s*--.*$/m', '', (string) file_get_contents($file));
                foreach (preg_split('/;\s*(?:\R|$)/', $sql) as $statement) {
                    if (trim($statement) !== '') {
                        $db->exec($statement);
                    }
                }
                $applied = true;
            } else {
                $applied = (require $file)($config) !== false;
            }
            if ($applied) {
                q('INSERT INTO migrations (name) VALUES (?)', [$name]);
                $ran[] = $name;
            }
        }

        if (is_dir(dirname($stamp)) || @mkdir(dirname($stamp), 0775, true)) {
            @file_put_contents($stamp, $signature);
        }
        return $ran;
    } finally {
        $db->query("SELECT RELEASE_LOCK('russtart_migrations')");
    }
}
