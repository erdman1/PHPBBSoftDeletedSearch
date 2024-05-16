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
    protected $phpbb_root_path;
    protected $phpEx; 

    protected $deleted_posts_count = null;

    protected $member_id;

    public function __construct(\phpbb\auth\auth $auth, \phpbb\template\template $template, \phpbb\db\driver\factory $db, \phpbb\request\request $request, \phpbb\config\config $config, $phpbb_root_path, $phpEx)
    {
        $this->auth = $auth;
        $this->template = $template;
        $this->db = $db;
        $this->request = $request;
        $this->config = $config;
        $this->phpbb_root_path = $phpbb_root_path;
        $this->phpEx = $phpEx;
    }


    public static function getSubscribedEvents()
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
        $this->template->assign_vars(array(
            'S_IS_MODERATOR' => $this->auth->acl_getf_global('m_')
        ));        
        $lang_set_ext = $event['lang_set_ext'];
        $lang_set_ext[] = [
            'ext_name' => 'erdman/softdeletedsearch',
            'lang_set' => 'common',
        ];
        $event['lang_set_ext'] = $lang_set_ext;
    }

    public function count_deleted_posts()
    {
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
                AND forum_id IN (' . $this->db->sql_escape($forum_list) . ')';
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);

        $this->deleted_posts_count = (int) $row['deleted_posts_count'];
    }

    public function add_profile_link($event)
    {
        $this->member_id = $event['member']['user_id'];
        $search_url = append_sid("{$this->phpbb_root_path}search.{$this->phpEx}", 'author_id=' . $this->member_id . '&show_deleted=1');
        $this->count_deleted_posts();
        $this->template->assign_vars(array(
            'U_SEARCH_USER_DELETED_POSTS' => $search_url,
            'U_DELETED_POSTS_COUNT' => $this->deleted_posts_count,
        ));
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

    public function modify_search_query_per_page($event)
    {
        if (!$this->shouldShowDeletedPosts()) {
            return;
        }
    
        $authorId = $this->getAuthorIdFromRequest();
        $allowedForums = $this->get_allowed_forums();
    
        if (empty($allowedForums)) {
            return;
        }
    
        $sqlArray = $event['sql_array'];
    
        $this->modifySqlWhereClause($sqlArray, $authorId, $allowedForums);
    
        $this->updateTotalMatchCount($event, $sqlArray);
    
        $this->updateSqlArray($event, $sqlArray);
    }
    
    private function shouldShowDeletedPosts()
    {
        return $this->request->variable('show_deleted', 0);
    }
    
    private function getAuthorIdFromRequest()
    {
        return (int) $this->request->variable('author_id', 0);
    }
    
    private function modifySqlWhereClause(&$sqlArray, $authorId, $allowedForums)
    {
        $forumList = implode(',', $allowedForums);
        $sqlArray['WHERE'] = "p.poster_id = $authorId AND p.post_visibility = 2 AND p.forum_id IN ($forumList)";
    }
    
    private function updateTotalMatchCount(&$event, $sqlArray)
    {
        $sqlFoundRows = $this->db->sql_build_query('SELECT', $sqlArray);
        $result = $this->db->sql_query($sqlFoundRows);
        $resultCount = count($this->db->sql_fetchrowset($result));
        $this->db->sql_freeresult($result);
    
        if ($resultCount) {
            $event['total_match_count'] = $resultCount;
        } else {
            $event['total_match_count'] = 0;
        }
    }
    
    
    private function updateSqlArray(&$event, $sqlArray)
    {
        $event['sql_array'] = $sqlArray;
    }
    




}
