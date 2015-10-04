<?php

class SV_OptimizedListQueries_XenForo_ControllerPublic_Forum extends XFCP_SV_OptimizedListQueries_XenForo_ControllerPublic_Forum
{
    protected function _getSessionActivityList()
    {
        if (!XenForo_Application::getOptions()->sv_membersonline_query)
        {
            return parent::_getSessionActivityList();
        }
        $visitor = XenForo_Visitor::getInstance();

        /** @var $sessionModel XenForo_Model_Session */
        $sessionModel = $this->getModelFromCache('XenForo_Model_Session');

        return $sessionModel->getSessionActivityQuickListFast(
            $visitor->toArray(),
            array('cutOff' => $sessionModel->getOnlineStatusTimeout()),
            ($visitor['user_id'] ? $visitor->toArray() : null)
        );
    }
}