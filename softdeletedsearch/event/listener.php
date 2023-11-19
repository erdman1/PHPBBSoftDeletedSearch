<?php
namespace erdman\softdeletedsearch\event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class listener implements EventSubscriberInterface
{

    protected $auth;
    protected $template;
    protected $db;
    protected $request;
    protected $config;

    protected $deleted_posts_count = null;

    protected $member_id;

    public function __construct(\phpbb\auth\auth $auth, \phpbb\template\template $template, \phpbb\db\driver\factory $db, \phpbb\request\request $request, \phpbb\config\config $config)
    {
        $this->auth = $auth;
        $this->template = $template;
        $this->db = $db;
        $this->request = $request;
        $this->config = $config;
    }


    static public function getSubscribedEvents()
    {
        return array(
            'core.user_setup' => 'load_language_on_setup',
            'core.memberlist_view_profile' => 'add_profile_link',
            'core.pagination_generate_page_link' => 'on_pagination_generate_page_link',
            'core.search_get_posts_data' => 'modify_search_query_per_page',


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

    public function count_deleted_posts()
    {
        global $phpbb_root_path, $phpEx, $user, $db;
        if ($this->deleted_posts_count !== null) {
            return; // Count already calculated
        }

        $allowed_forums = $this->get_allowed_forums();

        if (empty($allowed_forums)) {
            $this->deleted_posts_count = 0;
            return;
        }

        $forum_list = implode(',', $allowed_forums);
        $sql = 'SELECT COUNT(post_id) AS deleted_posts_count FROM ' . POSTS_TABLE . '
        WHERE poster_id = ' . (int) $this->member_id . ' 
        AND post_visibility = 2
        AND forum_id IN (' . $db->sql_escape($forum_list) . ')';
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);

        $this->deleted_posts_count = (int) $row['deleted_posts_count'];
    }

    public function modify_search_query_per_page($event)
    {
        $show_deleted = $this->request->variable('show_deleted', 0);


        if ($show_deleted) {
            $author_id = $this->request->variable('author_id', 0);
            $sql_ary = $event['sql_array'];
            $sql_array = $event['sql_array'];
            $start = $event['start'];
            $id_ary = array();

            $allowed_forums = $this->get_allowed_forums();
            if (empty($allowed_forums)) {
                return; // If user has no m_softdelete permissions in any forum, exit early
            }


            $forum_list = implode(',', $allowed_forums);


            $sql_where = "p.poster_id = $author_id AND p.post_visibility = 2 AND p.forum_id IN ($forum_list)";
            $sql_array['WHERE'] = $sql_where;

            $sql_found_rows = $this->db->sql_build_query('SELECT', $sql_array);
            $field = 'post_id';

            $result = $this->db->sql_query($sql_found_rows);
            $result_count = count($this->db->sql_fetchrowset($result));
            $this->db->sql_freeresult($result);
            $per_page = floor(($result_count - 1) / $this->config['posts_per_page']) * $this->config['posts_per_page'];

            if ($start >= $result_count) {
                $start = floor(($result_count - 1) / $per_page) * $per_page;
            }

            $result = $this->db->sql_query_limit($sql_found_rows, $this->config['search_block_size'], $start);
            while ($row = $this->db->sql_fetchrow($result)) {
                $id_ary[] = (int) $row[$field];
            }
            $this->db->sql_freeresult($result);

            $id_ary = array_unique($id_ary);
            $id_ary = array_slice($id_ary, 0, 25);

            $sql_where = ($result_count ?  $this->db->sql_in_set('p.post_id', $id_ary) : '');
            $sql_where .=  ($result_count ? ' AND ' : '')."p.poster_id = $author_id AND p.post_visibility = 2 AND p.forum_id IN ($forum_list)";

            if ($result_count) {
                $event['total_match_count'] = $result_count;
            } else {
                $event['total_match_count'] = 0;
            }
            $sql_ary['WHERE'] = $sql_where;
            $event['sql_array'] = $sql_ary;
        }
    }

    public function add_profile_link($event)
    {
        global $phpbb_root_path, $phpEx, $user, $db;
        $this->member_id = $event['member']['user_id'];
        $this->template->assign_var('U_SEARCH_USER_DELETED_POSTS', append_sid("{$phpbb_root_path}search.$phpEx", 'author_id=' . $this->member_id . '&show_deleted=1'));
        $this->count_deleted_posts();
        $this->template->assign_var('U_DELETED_POSTS_COUNT', $this->deleted_posts_count);

    }



    private function get_allowed_forums()
    {
        // Fetch forums where the user has m_softdelete permissions
        $allowed_forums = [];
        $forums = $this->auth->acl_getf('m_softdelete');
        foreach ($forums as $forum_id => $allowed) {
            if ($allowed['m_softdelete']) {
                $allowed_forums[] = $forum_id;
            }
        }
        return $allowed_forums;
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
