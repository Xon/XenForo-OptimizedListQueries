<?php

class SV_OptimizedListQueries_XenForo_Model_Thread extends XFCP_SV_OptimizedListQueries_XenForo_Model_Thread
{
    public function getThreadsInForum($forumId, array $conditions = array(), array $fetchOptions = array())
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

        return $this->fetchAllKeyed('
                SELECT thread.*
                    ' . $sqlClauses['selectFields'] . '
                FROM (
                '. $this->limitQueryResults('
                    SELECT thread.thread_id
                    FROM xf_thread AS thread ' . $forceIndex . '
                    ' . $sqlClauses['joinTables'] . '
                    WHERE ' . $whereConditions . '
                    ' . $sqlClauses['orderClause'] . '
                ', $limitOptions['limit'], $limitOptions['offset']
                ) . ') threadId
                JOIN xf_thread AS thread on thread.thread_id = threadId.thread_id '
            . $sqlClauses['joinTables']. '
            ' . $sqlClauses['orderClause']
            , 'thread_id');
    }

}