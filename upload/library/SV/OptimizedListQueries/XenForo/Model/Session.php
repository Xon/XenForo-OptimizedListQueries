<?php

class SV_OptimizedListQueries_XenForo_Model_Session extends XFCP_SV_OptimizedListQueries_XenForo_Model_Session
{
    public function getSessionActivityQuickListFast(array $viewingUser, array $conditions = [], array $forceInclude = null)
    {
        /** @var XenForo_Model_User $userModel */
        $userModel = $this->getModelFromCache('XenForo_Model_User');
        $canBypassUserPrivacy = $userModel->canBypassUserPrivacy();
        $forceIncludeUserId = ($forceInclude ? $forceInclude['user_id'] : 0);

        $db = $this->_getDb();
        $joins = '';
        $orWhereClause = '';
        $andWhereClause = '';
        $select = '';
        if ($forceIncludeUserId)
        {
            $select .= " ,(follow.user_id is not null) as followed";
            $joins .= "
            LEFT JOIN xf_user_follow AS follow ON
                (follow.follow_user_id = session_activity.user_id and follow.user_id = '" . $db->quote($forceIncludeUserId) . "' )
            ";
            $orWhereClause .= " or follow.user_id is not null or user.user_id = '" . $db->quote($forceIncludeUserId) . "'";
        }
        else
        {
            $select .= " ,0 as followed";
        }
        // enforce privacy
        if (!$canBypassUserPrivacy)
        {
            $andWhereClause .= " AND ( user_state = 'valid' and visible = 1 ";
            if ($forceIncludeUserId)
            {
                $andWhereClause .= " or user.user_id = '" . $db->quote($forceIncludeUserId) . "'";
            }
            $andWhereClause .= ") ";
        }
        // get the minimum information required to list active users that should be seen in the 'online now' list
        $records = $db->fetchAll(
            "
            SELECT user.user_id, user.username, user.is_staff, user.gender, user.avatar_date, user.avatar_width, user.avatar_height, user.gravatar
                   ,user.custom_title, user.display_style_group_id, user.user_group_id, user.secondary_group_ids
                " . $select . "
            FROM xf_session_activity AS session_activity
            JOIN xf_user AS user ON
                (user.user_id = session_activity.user_id)
            " . $joins . "
            WHERE (session_activity.view_date > ?) AND (user.is_staff = 1 " . $orWhereClause . ") " . $andWhereClause . "
            ORDER BY session_activity.view_date DESC
        ", $conditions['cutOff']
        );

        $limit = XenForo_Application::get('options')->membersOnlineLimit;
        $output = $this->getOnlineStats($conditions['cutOff']);
        $totalRecords = $output['members'];

        // maxiumum user online
        if ($limit == 0 || $totalRecords < $limit)
        {
            $output['limit'] = $totalRecords;
        }
        else
        {
            $output['limit'] = $limit;
        }

        // total members online subtract max members to show (minimum 0)
        $output['recordsUnseen'] = ($limit ? max($totalRecords - $limit, 0) : 0);

        // total visitors
        $output['total'] = $output['guests'] + $output['members'] + $output['robots'];

        // visitor records
        $output['records'] = $records;

        return $output;
    }

    protected function getOnlineStats($cutOff)
    {
        $db = $this->_getDb();
        $totals = $db->fetchAll(
            "
            SELECT is_robot, is_guest, COUNT(*) AS count
            FROM (
                SELECT (robot_key <> '') AS is_robot, (user_id = 0) AS is_guest
                FROM xf_session_activity AS session_activity
                WHERE (session_activity.view_date > ?)
            ) a
            GROUP BY is_robot, is_guest
        ", $cutOff
        );

        $guests = 0;
        $robots = 0;
        $members = 0;
        foreach ($totals as $total)
        {
            if ($total['is_robot'])
            {
                $robots = $total['count'];
            }
            else if ($total['is_guest'])
            {
                $guests = $total['count'];
            }
            else
            {
                $members = $total['count'];
            }
        }

        return [
            'guests'  => $guests,
            'robots'  => $robots,
            'members' => $members,
        ];
    }

    public function updateUserLastActivityFromSessions($cutOffDate = null)
    {
        if ($cutOffDate === null)
        {
            $cutOffDate = XenForo_Application::$time;
        }

        $userSessions = $this->getSessionActivityRecords(
            [
                'userLimit'      => 'registered',
                'getInvisible'   => true,
                'getUnconfirmed' => true,
                'cutOff'         => ['<=', $cutOffDate],
            ]
        );

        $db = $this->_getDb();

        foreach ($userSessions AS $userSession)
        {
            if (isset($userSession['last_activity']) && $userSession['last_activity'] >= $userSession['view_date'])
            {
                continue;
            }
            $db->query(
                '
                UPDATE xf_user
                SET last_activity = ?
                WHERE user_id = ? AND last_activity < ?
            ', [$userSession['view_date'], $userSession['user_id'], $userSession['view_date']]
            );
        }
    }
}

// ******************** FOR IDE AUTO COMPLETE ********************
if (false)
{
    class XFCP_SV_OptimizedListQueries_XenForo_Model_Session extends XenForo_Model_Session {}
}
