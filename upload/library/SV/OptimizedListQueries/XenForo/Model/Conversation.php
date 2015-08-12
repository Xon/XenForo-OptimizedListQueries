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

        return $this->fetchAllKeyed('
                SELECT message.*,
                    user.*, IF(user.username IS NULL, message.username, user.username) AS username,
                    user_profile.*,
                    user_privacy.*
                    ' . $joinOptions['selectFields'] . '
                FROM ( ' . $this->limitQueryResults('
                SELECT message.message_id
                FROM xf_conversation_message AS message
                ' . $joinOptions['joinTables'] . '
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
                ' . $joinOptions['joinTables']
            , 'message_id', $conversationId);
    }
}