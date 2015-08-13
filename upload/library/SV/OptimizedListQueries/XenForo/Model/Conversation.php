<?php

class SV_OptimizedListQueries_XenForo_Model_Conversation extends XFCP_SV_OptimizedListQueries_XenForo_Model_Conversation
{
    public function getConversationMessages($conversationId, array $fetchOptions = array())
    {
        $limitOptions = $this->prepareLimitFetchOptions($fetchOptions);
        $sv_convquery_threshold = XenForo_Application::getOptions()->sv_convquery_threshold;
        if ($sv_convquery_threshold < 0 || $limitOptions['offset'] <= $sv_convquery_threshold)
        {
            return parent::getConversationMessages($conversationId, $fetchOptions);
        }
        $joinOptions = $this->prepareMessageFetchOptions($fetchOptions);

        $safe = true;
        $innerJoin =  $joinOptions['joinTables'];
        // look for constructs we know affect the inner join
        if (preg_match('/^\s*,/', $innerJoin))
        {
            $safe = false;
        }
        else
        {
            $escaped = preg_replace('/[\r\n]+/', " ", ' '.strtolower($innerJoin));
            $escaped = preg_replace('/[\s\)]left\s+join[\s\(]/', ' ', $escaped);
            if (strpos($escaped, 'join') !== false)
            {
                $safe = false;
            }
        }
        if ($safe)
        {
            $innerJoin = '';
        }

        try
        {
            return $this->fetchAllKeyed('
                    SELECT message.*,
                        user.*, IF(user.username IS NULL, message.username, user.username) AS username,
                        user_profile.*,
                        user_privacy.*
                        ' . $joinOptions['selectFields'] . '
                    FROM ( ' . $this->limitQueryResults('
                    SELECT message.message_id
                    FROM xf_conversation_message AS message
                        ' . $innerJoin . '
                    WHERE message.conversation_id = ?
                    ORDER BY message.message_date
                    ', $limitOptions['limit'], $limitOptions['offset']
                    ). ') ConvMessageId
                    JOIN xf_conversation_message AS message on message.message_id = ConvMessageId.message_id
                    LEFT JOIN xf_user AS user ON
                        (user.user_id = message.user_id)
                    LEFT JOIN xf_user_profile AS user_profile ON
                        (user_profile.user_id = message.user_id)
                    LEFT JOIN xf_user_privacy AS user_privacy ON
                        (user_privacy.user_id = message.user_id)
                    ' . $joinOptions['joinTables'] . '
                    ORDER BY message.message_date '
                , 'message_id', $conversationId);
        }
        catch(Exception $e)
        {
            // we choice poorly an generated an error
            XenForo_Error::logException($e, false, 'error running optimized query');
            return parent::getThreadsInForum($forumId, $conditions, $fetchOptions);
        }
    }
}