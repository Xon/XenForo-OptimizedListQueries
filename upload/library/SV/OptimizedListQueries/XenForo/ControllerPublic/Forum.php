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
            else
            {
                // todo ...
            }
        }

        if ($expiry)
        {
            $cacheId = 'sessionlist_0';
            if ($raw = $cacheObject->load($cacheId, true))
            {
                $data = @json_decode($raw, true);
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
            /** @var $sessionModel XenForo_Model_Session */
            $sessionModel = $this->getModelFromCache('XenForo_Model_Session');

            $data = $sessionModel->getSessionActivityQuickListFast(
                $visitor->toArray(),
                array('cutOff' => $sessionModel->getOnlineStatusTimeout()),
                ($visitor['user_id'] ? $visitor->toArray() : null)
            );
        }
        
        if ($expiry)
        {
            $raw = json_encode($data);
            $cacheObject->save($raw, $cacheId, array(), $expiry);
        }

        return $data;
    }
}