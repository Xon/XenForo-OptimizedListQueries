<?php

class SV_OptimizedListQueries_XenForo_Model_Node extends XFCP_SV_OptimizedListQueries_XenForo_Model_Node
{
    public function getUniqueNodeTypeIdsFromNodeGrouped(array $nodes)
    {
        $output = array();
        foreach ($nodes AS $group)
        {
            foreach ($group AS $node)
            {
                $output[$node['node_type_id']] = true;
            }
        }

        return array_keys($output);
    }

    public function getNodeDataForListDisplay($parentNode, $displayDepth, array $nodePermissions = null)
    {
        // fast path only if custom permisions aren't being used or a cache isn't defined
        $cacheObject = $this->_getCache(true);
        if ($nodePermissions !== null || empty($cacheObject))
        {
            return parent::getNodeDataForListDisplay($parentNode, $displayDepth, $nodePermissions);
        }

        $options = XenForo_Application::getOptions();
        $visitor = XenForo_Visitor::getInstance();
        $this->getModelFromCache('XenForo_Model_User');
        if ($visitor['permission_combination_id'] == XenForo_Model_User::$guestPermissionCombinationId)
        {
            $expiry = $options->sv_cache_nodes_guests;
        }
        else
        {
            $expiry = $options->sv_cache_nodes_members;
        }

        // not caching for this type of user, or if we are only caching the root node
        if (!$expiry || ($parentNode !== false && $options->sv_cache_nodes_root))
        {
            return parent::getNodeDataForListDisplay($parentNode, $displayDepth, $nodePermissions);
        }

        if (is_array($parentNode))
        {
            $parentNodeId = $parentNode['node_id'];
        }
        else if ($parentNode === false)
        {
            $parentNodeId = 0;
        }
        else
        {
            throw new XenForo_Exception('Unexpected parent node parameter passed to getNodeDataForListDisplay');
        }

        $cacheId = 'nodelist_'.$parentNodeId.'_'.$displayDepth.'_'.$visitor['permission_combination_id'];
        if ($raw = $cacheObject->load($cacheId, true))
        {
            $nodeList = @unserialize($raw);
            if ($nodeList !== false)
            {
                if (isset($nodeList['nodesGrouped']))
                {
                    $nodeList['nodeHandlers'] = $this->getNodeHandlersForNodeTypes($this->getUniqueNodeTypeIdsFromNodeGrouped($nodeList['nodesGrouped']));
                }
                return $nodeList;
            }
        }

        if ($parentNodeId)
        {
            $nodes = $this->getChildNodes($parentNode, true);
        }
        else
        {
            $nodes = $this->getAllNodes(false, true);
        }

        $nodeList = $this->getNodeListDisplayData($nodes, $parentNodeId, $displayDepth, $nodePermissions);

        if ($cacheObject)
        {
            // nodeHandlers are objects, so remove them from being cached.
            $nodeHandlers = null;
            if (isset($nodeList['nodeHandlers']) )
            {
                $nodeHandlers = $nodeList['nodeHandlers'];
                $nodeList['nodeHandlers'] = null;
            }
            $raw = serialize($nodeList);
            if ($nodeHandlers)
            {
                $nodeList['nodeHandlers'] = $nodeHandlers;
            }
            $cacheObject->save($raw, $cacheId, array(), $expiry);
        }

        return $nodeList;
    }
}