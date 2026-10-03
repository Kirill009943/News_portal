<?php
// load_posts.php — выдача карточек постов (используется и как include, и как AJAX-эндпоинт)
require_once dirname(__DIR__, 2) . '/src/bootstrap.php';
use Parse\ParseQuery;

$skip = isset($_GET['skip']) ? intval($_GET['skip']) : 0;
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
$sortBy = $_GET['sort'] ?? 'new';
$pageload = $_GET['pageload'] ?? 'feed';

function failPostsLoad(Exception $e)
{
    http_response_code(503);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Temporary database error';
    error_log('load_posts.php: ' . $e->getMessage());
    exit;
}

if ($pageload === 'favorites') {
    if (!$currentUser) {
        exit;
    }
    $relation = $currentUser->getRelation("favorites");
    $favoritesQuery = $relation->getQuery();
    $favoritesQuery->includeKey("author");
    try {
        $posts = parseRetry(function () use ($favoritesQuery) {
            return $favoritesQuery->find();
        });
        foreach ($posts as $post) {
            include dirname(__DIR__, 2) . '/templates/post_card.php';
        }
    } catch (Exception $e) {
        failPostsLoad($e);
    }
} else {

    $query = new ParseQuery("Article");
    $query->includeKey("author");

    if ($pageload === 'admin') {
        if (!$currentUser || $currentUser->get('role') != 'admin') {
            header("Location: index.php");
            exit;
        }
        $query->descending("createdAt");
        $query->equalTo("status", "pending");

        try {
            $posts = parseRetry(function () use ($query) {
                return $query->find();
            });
        } catch (Exception $e) {
            failPostsLoad($e);
        }
        foreach ($posts as $post) {
            include dirname(__DIR__, 2) . '/templates/post_card_admin.php';
        }
        exit;
    }

    if ($pageload === 'profile')
        $query->equalTo("author", $currentUser);
    elseif ($pageload === 'feed')
        $query->equalTo("status", "approved");

    if ($sortBy === 'new')
        $query->descending("createdAt");
    elseif ($sortBy === 'old')
        $query->ascending("createdAt");
    elseif ($sortBy === 'popular')
        $query->descending("views");
    elseif ($sortBy === 'hot')
        $query->descending("score");

    $query->skip($skip);
    $query->limit($limit);

    try {
        $posts = parseRetry(function () use ($query) {
            return $query->find();
        });
        if (count($posts) === 0) {
            exit;
        }
        foreach ($posts as $post) {
            include dirname(__DIR__, 2) . '/templates/post_card.php';
        }
    } catch (Exception $e) {
        failPostsLoad($e);
    }
}
