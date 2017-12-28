<?php

class SV_OptimizedListQueries_XenForo_ControllerPublic_Forum extends XFCP_SV_OptimizedListQueries_XenForo_ControllerPublic_Forum
{
    protected function _getSessionActivityList()
    {
        $cacheObject = XenForo_Application::getCache();
        $options = XenForo_Application::getOptions();
        $visitor = XenForo_Visitor::getInstance();
        $this->getModelFromCache('XenForo_Model_User');

        $expiry = false;
        if (!empty($cacheObject))
        {
            if ($visitor['permission_combination_id'] == XenForo_Model_User::$guestPermissionCombinationId)
            {
                $expiry = $options->sv_cache_membersonline_query_guests;
            }
        }

        if ($expiry)
        {
            $cacheId = 'sessionlist_0';
            if ($raw = $cacheObject->load($cacheId, true))
            {
                $data = @unserialize($raw, true);
                if ($data)
                {
                    return $data;
                }
            }
        }

        if (!XenForo_Application::getOptions()->sv_membersonline_query)
        {
            $data = parent::_getSessionActivityList();
        }
        else
        {
            /** @var SV_OptimizedListQueries_XenForo_Model_Session $sessionModel */
            $sessionModel = $this->getModelFromCache('XenForo_Model_Session');

            $data = $sessionModel->getSessionActivityQuickListFast(
                $visitor->toArray(),
                ['cutOff' => $sessionModel->getOnlineStatusTimeout()],
                ($visitor['user_id'] ? $visitor->toArray() : null)
            );
        }

        if ($expiry)
        {
            $raw = serialize($data);
            if (is_string($raw))
            {
                $cacheObject->save($raw, $cacheId, [], $expiry);
            }
        }

        return $data;
    }

    public function getGlobalForumRss()
    {
        if (XenForo_Application::getOptions()->sv_rssglobalquery)
        {
            SV_OptimizedListQueries_Globals::$globalRss = true;
        }
        return parent::getGlobalForumRss();
    }
}

// ******************** FOR IDE AUTO COMPLETE ********************
if (false)
{
    class XFCP_SV_OptimizedListQueries_XenForo_ControllerPublic_Forum extends XenForo_ControllerPublic_Forum {}
}
