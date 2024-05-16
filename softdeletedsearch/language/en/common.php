<?php
/**
*
* @package phpBB Extension - Soft Deleted Search
* @copyright (c) 2024 [Author Name]
* @license [License Name], see [url]
*
*/

if (!defined('IN_PHPBB')) 
{
    exit;
}

if (empty($lang) || !is_array($lang))
{
    $lang = array();
}

$lang = array_merge($lang, array(
    'SOFT_DELETED_SEARCH_SEARCH_USER_DELETED_POSTS' => 'Search',
    'SOFT_DELETED_SEARCH_USER_POSTS_DELETED' => 'User\'s deleted posts',

));
