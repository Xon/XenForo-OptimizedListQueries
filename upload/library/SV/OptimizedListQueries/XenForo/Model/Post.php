<?php

class SV_OptimizedListQueries_XenForo_Model_Post extends XFCP_SV_OptimizedListQueries_XenForo_Model_Post
{
    public function preparePostJoinOptions(array $fetchOptions)
    {
        if (SV_OptimizedListQueries_Globals::$slimPostFetchForSearch)
        {
            $fetchOptions['skip_wordcount'] = true;
            if (class_exists('Sidane_Threadmarks_XenForo_Model_Post', false))
            {
                $fetchOptions['includeThreadmark'] = false;
            }
            else if (!empty($fetchOptions['join']) && class_exists('Sidane_Threadmarks_Model_Post', false))
            {
                /** @noinspection PhpUndefinedClassInspection */
                $fetchOptions['join'] &= ~Sidane_Threadmarks_Model_Post::FETCH_THREADMARKS;
            }
        }

        $sqlOptions = parent::preparePostJoinOptions($fetchOptions);

        if (SV_OptimizedListQueries_Globals::$slimPostFetchForSearch)
        {
            $sqlOptions['selectFields'] .= ", '' as message ";
        }

        return $sqlOptions;
    }

    public function recalculatePostPositionsInThread($threadId)
    {
        if (!SV_OptimizedListQueries_Globals::$replaceThreadCounterShim)
        {
            return parent::recalculatePostPositionsInThread($threadId);
        }

        $db = $this->_getDb();

        $db->query('SET @position := -1');
        $db->query("
			UPDATE xf_post
			SET position = (@position := IF(message_state = 'visible', @position + 1, GREATEST(@position, 0)))
			WHERE thread_id = ?
			ORDER BY post_date
		", $threadId);

        $firstPost = $db->fetchRow("
			SELECT post_id, user_id, username, post_date, message_state, `position`
			FROM xf_post
			WHERE thread_id = ?
			ORDER BY post_date
			LIMIT 1
		", $threadId);
        if (!$firstPost)
        {
            return false;
        }

        $lastPost = $db->fetchRow("
			SELECT post_id, post_date, user_id, username
			FROM xf_post
			WHERE thread_id = ?
				AND message_state = 'visible'
			ORDER BY post_date DESC
			LIMIT 1
		", $threadId);

        $visiblePosts = $db->fetchOne("
			SELECT COUNT(*)
			FROM xf_post
			WHERE thread_id = ?
				AND message_state = 'visible'
		", $threadId);

        $userPosts = [];

        return array(
            'firstPostId' => $firstPost['post_id'],
            'firstPostDate' => $firstPost['post_date'],
            'firstPostState' => $firstPost['message_state'],
            'firstPost' => $firstPost,

            'lastPostId' => $lastPost['post_id'],
            'lastPost' => $lastPost,

            'visibleCount' => $visiblePosts,
            'userPosts' => $userPosts
        );
    }
}

// ******************** FOR IDE AUTO COMPLETE ********************
if (false)
{
    class XFCP_SV_OptimizedListQueries_XenForo_Model_Post extends XenForo_Model_Post {}
}
