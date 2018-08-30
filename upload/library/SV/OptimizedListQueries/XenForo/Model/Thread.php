<?php

class SV_OptimizedListQueries_XenForo_Model_Thread extends XFCP_SV_OptimizedListQueries_XenForo_Model_Thread
{
    public function getThreads(array $conditions, array $fetchOptions = array())
    {
        if (!SV_OptimizedListQueries_Globals::$globalRss)
        {
            return parent::getThreads($conditions, $fetchOptions);
        }

        $limitOptions = $this->prepareLimitFetchOptions($fetchOptions);
        $whereConditions = $this->prepareThreadConditions($conditions, $fetchOptions);
        $sqlClauses = $this->prepareThreadFetchOptions($fetchOptions);
        $forceIndex = (!empty($fetchOptions['forceThreadIndex']) ? 'FORCE INDEX (' . $fetchOptions['forceThreadIndex'] . ')' : '');

        $safe = true;
        $innerJoin = $sqlClauses['joinTables'];
        // look for constructs we know affect the inner join
        if (preg_match('/^\s*,/', $innerJoin))
        {
            $safe = false;
        }
        else
        {
            $whitespace_normalized = preg_replace('/[\r\n]+/', ' ', ' ' . strtolower($innerJoin));
            $escaped = preg_replace('/[\s\)]left\s+join[\s\(]/', ' ', $whitespace_normalized);
            if (strpos($escaped, 'join') !== false)
            {
                $safe = false;
            }
            else
            {
                $matches = [];
                if (preg_match_all('/[\s\)]left\s+join\s+[`\w]+\s+(as\s+){0,1}([`\w]+)/', $whitespace_normalized, $matches))
                {
                    foreach ($matches[2] as $match)
                    {
                        if (strpos($whereConditions, $match) !== false)
                        {
                            $safe = false;
                            break;
                        }
                    }
                }
            }
        }

        if ($safe)
        {
            $innerJoin = '';
        }

        try
        {
            return $this->fetchAllKeyed(
                '
                    SELECT thread.*
                        ' . $sqlClauses['selectFields'] . '
                    FROM (
                    ' . $this->limitQueryResults(
                    '
                        SELECT thread.thread_id
                        FROM xf_thread AS thread ' . $forceIndex . '
                        ' . $innerJoin . '
                        WHERE ' . $whereConditions . '
                        ' . $sqlClauses['orderClause'] . '
                    ', $limitOptions['limit'], $limitOptions['offset']
                ) . ') threadId
                    JOIN xf_thread AS thread on thread.thread_id = threadId.thread_id '
                . $sqlClauses['joinTables'] . '
                ' . $sqlClauses['orderClause']
                , 'thread_id'
            );
        }
        catch (Exception $e)
        {
            // we choice poorly an generated an error
            XenForo_Error::logException($e, false, 'error running optimized query');

            return parent::getThreads($conditions, $fetchOptions);
        }
    }

    public function getThreadsInForum($forumId, array $conditions = [], array $fetchOptions = [])
    {
        $limitOptions = $this->prepareLimitFetchOptions($fetchOptions);
        $sv_forumquery_threshold = XenForo_Application::getOptions()->sv_forumquery_threshold;
        if ($sv_forumquery_threshold < 0 || $limitOptions['offset'] <= $sv_forumquery_threshold)
        {
            return parent::getThreadsInForum($forumId, $conditions, $fetchOptions);
        }

        $conditions['forum_id'] = $forumId;
        $whereConditions = $this->prepareThreadConditions($conditions, $fetchOptions);
        $sqlClauses = $this->prepareThreadFetchOptions($fetchOptions);
        $forceIndex = (!empty($fetchOptions['forceThreadIndex']) ? 'FORCE INDEX (' . $fetchOptions['forceThreadIndex'] . ')' : '');

        $safe = true;
        $innerJoin = $sqlClauses['joinTables'];
        // look for constructs we know affect the inner join
        if (preg_match('/^\s*,/', $innerJoin))
        {
            $safe = false;
        }
        else
        {
            $whitespace_normalized = preg_replace('/[\r\n]+/', ' ', ' ' . strtolower($innerJoin));
            $escaped = preg_replace('/[\s\)]left\s+join[\s\(]/', ' ', $whitespace_normalized);
            if (strpos($escaped, 'join') !== false)
            {
                $safe = false;
            }
            else
            {
                $matches = [];
                if (preg_match_all('/[\s\)]left\s+join\s+[`\w]+\s+(as\s+){0,1}([`\w]+)/', $whitespace_normalized, $matches))
                {
                    foreach ($matches[2] as $match)
                    {
                        if (strpos($whereConditions, $match) !== false)
                        {
                            $safe = false;
                            break;
                        }
                    }
                }
            }
        }

        if ($safe)
        {
            $innerJoin = '';
        }

        try
        {
            return $this->fetchAllKeyed(
                '
                    SELECT thread.*
                        ' . $sqlClauses['selectFields'] . '
                    FROM (
                    ' . $this->limitQueryResults(
                    '
                        SELECT thread.thread_id
                        FROM xf_thread AS thread ' . $forceIndex . '
                        ' . $innerJoin . '
                        WHERE ' . $whereConditions . '
                        ' . $sqlClauses['orderClause'] . '
                    ', $limitOptions['limit'], $limitOptions['offset']
                ) . ') threadId
                    JOIN xf_thread AS thread on thread.thread_id = threadId.thread_id '
                . $sqlClauses['joinTables'] . '
                ' . $sqlClauses['orderClause']
                , 'thread_id'
            );
        }
        catch (Exception $e)
        {
            // we choice poorly an generated an error
            XenForo_Error::logException($e, false, 'error running optimized query');

            return parent::getThreadsInForum($forumId, $conditions, $fetchOptions);
        }
    }

    public function mergeThreads(array $threads, $targetThreadId, array $options = array())
    {
        SV_OptimizedListQueries_Globals::$replaceThreadCounterShim = true;
        try
        {
            return parent::mergeThreads($threads, $targetThreadId, $options);
        }
        finally
        {
            SV_OptimizedListQueries_Globals::$replaceThreadCounterShim = false;
        }
    }

    public function replaceThreadUserPostCounters($threadId, array $counters, $userId = null)
    {
        if (!SV_OptimizedListQueries_Globals::$replaceThreadCounterShim || $userId)
        {
            parent::replaceThreadUserPostCounters($threadId, $counters, $userId);

            return;
        }

        $db = $this->_getDb();

        XenForo_Db::beginTransaction($db);
        $db->beginTransaction();
        $db->query('delete from xf_thread_user_post where thread_id = ?', $threadId);
        $db->query("
			INSERT INTO xf_thread_user_post (thread_id, user_id, post_count)
			SELECT thread_id, user_id, COUNT(*)
			FROM xf_post
			WHERE thread_id = ?
				AND message_state = 'visible'
				AND user_id > 0
			GROUP BY user_id
		", $threadId);
        XenForo_Db::commit($db);
    }
}

// ******************** FOR IDE AUTO COMPLETE ********************
if (false)
{
    class XFCP_SV_OptimizedListQueries_XenForo_Model_Thread extends XenForo_Model_Thread {}
}
