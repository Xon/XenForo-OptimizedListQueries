<?php

class SV_OptimizedListQueries_XenForo_Model_NewsFeed extends XFCP_SV_OptimizedListQueries_XenForo_Model_NewsFeed
{

    /**
     * Gets news feed items matching the given conditions.
     *
     * @param array        $conditions
     * @param array        $viewingUser
     * @param integer|null $maxItems
     * @return array
     * @throws XenForo_Exception
     * @throws Zend_Exception
     */
    public function getNewsFeedItems(/** @noinspection PhpOptionalBeforeRequiredParametersInspection */ array $conditions = array(), array $viewingUser, $maxItems = null)
    {
        $db = $this->_getDb();
        $sqlConditions = array();

        if (isset($conditions['news_feed_id']) && is_array($conditions['news_feed_id']))
        {
            list($operator, $newsFeedId) = $conditions['news_feed_id'];

            $this->assertValidCutOffOperator($operator);

            // ----------------------
            switch($operator)
            {
                case '>=':
                case '>':
                case '<=':
                case '<':
                    $eventDateOperator = $operator[0] . '=';
                    $eventDate = $this->_db->fetchOne("select event_date from xf_news_feed where news_feed_id $eventDateOperator " . $db->quote($newsFeedId) . ' limit 1');
                    if (!$eventDate)
                    {
                        return [];
                    }

                    $sqlConditions[] = "news_feed.event_date $operator " . $db->quote($eventDate);
                    break;
            }
            $sqlConditions[] = "news_feed.news_feed_id $operator " . $db->quote($newsFeedId);
            // ----------------------
        }

        if (isset($conditions['user_id']))
        {
            if (is_array($conditions['user_id']))
            {
                $sqlConditions[] = 'news_feed.user_id IN (' . $db->quote($conditions['user_id']) . ')';
            }
            else
            {
                $sqlConditions[] = 'news_feed.user_id = ' . $db->quote($conditions['user_id']);
            }
            // ----------------------
            $forceIndex = 'use index (userId_eventDate)';
            // ----------------------
        }
        else
        {
            $forceIndex = 'FORCE INDEX (event_date)';

            if ($viewingUser['user_id'] && !empty($viewingUser['ignored']))
            {
                $ignored = XenForo_Helper_Php::safeUnserialize($viewingUser['ignored']);
                if ($ignored)
                {
                    $ignored = array_map('intval', array_keys($ignored));
                    $sqlConditions[] = 'news_feed.user_id NOT IN (' . $db->quote($ignored) . ')';
                }
            }

            $sqlConditions[] = "user.user_state IN ('valid', 'email_confirm_edit')";
            $sqlConditions[] = "user.is_banned = 0";
        }

        $whereClause = $this->getConditionsForClause($sqlConditions);

        if ($maxItems === null)
        {
            $maxItems = XenForo_Application::get('options')->newsFeedMaxItems;
        }

        $viewingUserIdQuoted = $db->quote($viewingUser['user_id']);
        $isRegistered = ($viewingUser['user_id'] > 0 ? 1 : 0);
        $bypassPrivacy = $this->getModelFromCache('XenForo_Model_User')->canBypassUserPrivacy($errorPhraseKey, $viewingUser);

        // TODO: restore user_id = 0 announcements functionality down the line
        return $this->fetchAllKeyed($this->limitQueryResults(
            '
				SELECT
					user.*,
					user_profile.*,
					user_privacy.*,
					news_feed.*
				FROM xf_news_feed AS news_feed ' . $forceIndex . '
				INNER JOIN xf_user AS user ON
					(user.user_id = news_feed.user_id)
				INNER JOIN xf_user_profile AS user_profile ON
					(user_profile.user_id = user.user_id)
				LEFT JOIN xf_user_follow AS user_follow ON
					(user_follow.user_id = user.user_id
					AND user_follow.follow_user_id = ' . $viewingUserIdQuoted . ')
				INNER JOIN xf_user_privacy AS user_privacy ON
					(user_privacy.user_id = user.user_id
						' . ($bypassPrivacy ? '' : '
							AND (user.user_id = ' . $viewingUserIdQuoted . '
								OR (
									user_privacy.allow_receive_news_feed <> \'none\'
									AND IF(user_privacy.allow_receive_news_feed = \'members\', ' . $isRegistered . ', 1)
									AND IF(user_privacy.allow_receive_news_feed = \'followed\', user_follow.user_id IS NOT NULL, 1)
								)
							)
						') . '
					)
				WHERE ' . $whereClause . '
				ORDER BY news_feed.event_date DESC
			', $maxItems
        ), 'news_feed_id');
    }
}

// ******************** FOR IDE AUTO COMPLETE ********************
if (false)
{
    class XFCP_SV_OptimizedListQueries_XenForo_Model_NewsFeed extends XenForo_Model_NewsFeed {}
}
