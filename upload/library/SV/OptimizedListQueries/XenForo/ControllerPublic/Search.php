<?php

class SV_OptimizedListQueries_XenForo_ControllerPublic_Search extends XFCP_SV_OptimizedListQueries_XenForo_ControllerPublic_Search
{
    public function actionSearch()
    {
        SV_OptimizedListQueries_Globals::$possibleNewSearch = true;
        return parent::actionSearch();
    }

    public function actionMember()
    {
        SV_OptimizedListQueries_Globals::$possibleNewSearch = true;
        return parent::actionMember();
    }

    public function rerunSearch(array $search, array $constraints)
    {
        SV_OptimizedListQueries_Globals::$possibleNewSearch = true;
        return parent::rerunSearch($search, $constraints);
    }
}