<?php

class SV_OptimizedListQueries_XenForo_Model_Search extends XFCP_SV_OptimizedListQueries_XenForo_Model_Search
{
    public function getViewableSearchResults(array $results, array $viewingUser = null)
    {
        if (SV_OptimizedListQueries_Globals::$possibleNewSearch)
        {
            SV_OptimizedListQueries_Globals::$slimPostFetchForSearch = true;
            SV_OptimizedListQueries_Globals::removeHardEnabledHooks();
        }

        return parent::getViewableSearchResults($results, $viewingUser);
    }
}