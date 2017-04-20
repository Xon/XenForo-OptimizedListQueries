<?php

class SV_OptimizedListQueries_XenForo_Model_Like extends XFCP_SV_OptimizedListQueries_XenForo_Model_Like
{
    public function getLikesForContentUser($userId, array $fetchOptions = array())
    {
        $limitOptions = $this->prepareLimitFetchOptions($fetchOptions);
        $sv_likequery_threshold = XenForo_Application::getOptions()->sv_likequery_threshold;
        if ($sv_likequery_threshold < 0 || $limitOptions['offset'] <= $sv_likequery_threshold)
        {
            return parent::getLikesForContentUser($userId, $fetchOptions);
        }

        return $this->fetchAllKeyed('SELECT liked_content.*, user.*
            FROM 
            (
                ' . $this->limitQueryResults('SELECT like_id
                FROM xf_liked_content
                WHERE content_user_id = ?
                ORDER BY like_date DESC
            ', $limitOptions['limit'], $limitOptions['offset']) . ') Ids
            JOIN xf_liked_content AS liked_content ON (liked_content.like_id = Ids.like_id)
            LEFT JOIN xf_user AS user ON (user.user_id = liked_content.like_user_id)
            ', 'like_id', $userId);
    }
}

// ******************** FOR IDE AUTO COMPLETE ********************
if (false)
{
    class XFCP_SV_OptimizedListQueries_XenForo_Model_Like extends XenForo_Model_Like {}
}