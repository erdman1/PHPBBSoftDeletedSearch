<?php
namespace erdman\softdeletedsearch\event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class listener implements EventSubscriberInterface
{

    protected $auth;
    protected $template;
    protected $db;
    protected $request;

    public function __construct(\phpbb\auth\auth $auth, \phpbb\template\template $template, \phpbb\db\driver\factory $db, \phpbb\request\request $request)
    {
        $this->auth = $auth;
        $this->template = $template;
        $this->db = $db;
        $this->request = $request;
    }


    static public function getSubscribedEvents()
    {
        return array(
            'core.user_setup' => 'load_language_on_setup',
            'core.memberlist_view_profile' => 'add_profile_link',
            'core.search_modify_url_parameters' => 'modify_search_query_combined',
            'core.pagination_generate_page_link' => 'on_pagination_generate_page_link',


        );
    }
    public function load_language_on_setup($event)
    {
        $this->template->assign_var('U_SEARCH_USER_DELETED_POSTS', "abc");
        $this->template->assign_var('S_IS_MODERATOR', $this->auth->acl_getf_global('m_'));
        $lang_set_ext = $event['lang_set_ext'];
        $lang_set_ext[] = [
            'ext_name' => 'erdman/softdeletedsearch',
            'lang_set' => 'common',
        ];
        $event['lang_set_ext'] = $lang_set_ext;
    }
    public function add_profile_link($event)
    {
        global $phpbb_root_path, $phpEx, $user;
        $member_id = $event['member']['user_id'];
        $this->template->assign_var('U_SEARCH_USER_DELETED_POSTS', append_sid("{$phpbb_root_path}search.$phpEx", 'author_id=' . $member_id . '&show_deleted=1'));
    }


    public function modify_search_query_combined($event)
    {
        $show_deleted = $this->request->variable('show_deleted', 0);
        $sql_where = $event['sql_where'];

        if ($show_deleted) {
            $author_id = $this->request->variable('author_id', 0);

            // Fetch forums where the user has m_softdelete permissions
            $allowed_forums = [];
            $forums = $this->auth->acl_getf('m_softdelete');
            foreach ($forums as $forum_id => $allowed) {
                if ($allowed['m_softdelete']) {
                    $allowed_forums[] = $forum_id;
                }
            }

            if (empty($allowed_forums)) {
                return; // If user has no m_softdelete permissions in any forum, exit early
            }

            $forum_list = implode(',', $allowed_forums);

            // Modify the SQL WHERE clause
            if ($author_id) {
                // If an author_id is provided, show only that user's soft deleted posts in allowed forums
                $sql_where .= ($sql_where ? ' AND ' : '') . "p.poster_id = $author_id AND p.post_visibility = 2 AND p.forum_id IN ($forum_list)";
            }

            $event['sql_where'] = $sql_where;
            $this->template->assign_var('S_SHOW_DELETED', 1); //we need this for overall_footer_after.html to know when to load the js

        }
    }
    public function on_pagination_generate_page_link($event)
    {
        $show_deleted = $this->request->variable('show_deleted', 0);
        if ($show_deleted) {
            // Get the current event data
            $base_url = $event['base_url'];
            $on_page = $event['on_page'];
            $start_name = $event['start_name'];
            $per_page = $event['per_page'];

            // Check if we are dealing with an array or a string for the base URL
            if (is_array($base_url)) {
                // If it's an array, assume the first element is the URL and the rest are parameters
                $url = array_shift($base_url);
                $params = $base_url;
            } else {
                // If it's a string, the URL is the base URL and there are no parameters
                $url = $base_url;
                $params = array();
            }

            // Add the 'show_deleted' parameter to the URL parameters array
            $params['show_deleted'] = 1;

            // Rebuild the base URL with the additional parameter
            $base_url = $url . '&' . http_build_query($params);

            // If we are not on the first page, add the start parameter
            if ($on_page > 1) {
                $start_value = ($on_page - 1) * $per_page;
                $base_url .= (strpos($base_url, '&') === false ? '?' : '&') . "$start_name=$start_value";
            }

            // Override the event's generate_page_link value with our modified base URL
            $event['generate_page_link_override'] = $base_url;
        }
    }



}