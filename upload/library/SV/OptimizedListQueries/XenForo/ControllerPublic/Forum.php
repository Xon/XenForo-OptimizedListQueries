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
            if ($raw === false)
            {
                $jsonError = '';
                $val = json_last_error();
                switch ($val) 
                {
                    case JSON_ERROR_NONE:
                        $jsonError = 'No errors';
                        break;
                    case JSON_ERROR_DEPTH:
                        $jsonError = 'Maximum stack depth exceeded';
                        break;
                    case JSON_ERROR_STATE_MISMATCH:
                        $jsonError = 'Underflow or the modes mismatch';
                        break;
                    case JSON_ERROR_CTRL_CHAR:
                        $jsonError = 'Unexpected control character found';
                        break;
                    case JSON_ERROR_SYNTAX:
                        $jsonError = 'Syntax error, malformed JSON';
                        break;
                    case JSON_ERROR_UTF8:
                        $jsonError = 'Malformed UTF-8 characters, possibly incorrectly encoded';
                        break;
                    case JSON_ERROR_RECURSION:
                        $jsonError = 'One or more recursive references in the value to be encoded';
                        break;
                    case JSON_ERROR_INF_OR_NAN:
                        $jsonError = ' One or more NAN or INF values in the value to be encoded  ';
                        break;
                    case JSON_ERROR_UNSUPPORTED_TYPE:
                        $jsonError = 'A value of a type that cannot be encoded was given';
                        break;
/*
                    case JSON_ERROR_INVALID_PROPERTY_NAME:
                        $jsonError = 'A property name that cannot be encoded was given';
                        break;
                    case JSON_ERROR_UTF16:
                        $jsonError = 'Malformed UTF-16 characters, possibly incorrectly encoded';
                        break;
*/
                    default:
                        $jsonError = 'Unknown error:'.$val;
                        break;
                }
                XenForo_Error::logException(new Exception('Encoding failed: '.$jsonError), false);
            }
            else
            {        
                $cacheObject->save($raw, $cacheId, array(), $expiry);
            }
        }

        return $data;
    }
}