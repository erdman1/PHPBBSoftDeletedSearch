<?php
if (!defined('IN_PHPBB')) {
    exit;
}

if (empty($lang) || !is_array($lang)) {
    $lang = array();
}

$lang = array_merge($lang, array(
    'SEARCH_USER_DELETED_POSTS' => 'Search',
    'USER_POSTS_DELETED'=> 'Users Deleted Posts:',


)
);
