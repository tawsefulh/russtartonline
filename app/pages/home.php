<?php
$articles = find_posts('articles', [], 5);
$ads = find_posts('ads', [], 6);

$topics = q("SELECT " . name_sql('k') . " AS name, k.slug, COUNT(a.id) AS total
             FROM categories k LEFT JOIN articles a ON a.category_id = k.id
                  AND EXISTS (SELECT 1 FROM users u WHERE u.id = a.user_id AND u.deactivated_at IS NULL)
             WHERE k.type = 'article'
             GROUP BY k.id ORDER BY k.is_default DESC, total DESC, k.id LIMIT 20")->fetchAll();

render('home', [
    'title'    => t('Student guides and marketplace for life in Russia'),
    'articles' => $articles,
    'ads'      => $ads,
    'topics'   => $topics,
    'stations' => all_cities(),
    'bodyClass' => 'is-home',
]);
