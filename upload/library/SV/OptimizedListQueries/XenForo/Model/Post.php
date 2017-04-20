<?php

class SV_OptimizedListQueries_XenForo_Model_Post extends XFCP_SV_OptimizedListQueries_XenForo_Model_Post
{
    public function preparePostJoinOptions(array $fetchOptions)
    {
        if (SV_OptimizedListQueries_Globals::$slimPostFetchForSearch)
        {
            $fetchOptions['skip_wordcount'] = true;
            if (!empty($fetchOptions['join']) && class_exists('Sidane_Threadmarks_Model_Post', false))
            {
                $fetchOptions['join'] &= ~Sidane_Threadmarks_Model_Post::FETCH_THREADMARKS;
            }
        }

        $sqlOptions = parent::preparePostJoinOptions($fetchOptions);

        if (SV_OptimizedListQueries_Globals::$slimPostFetchForSearch)
        {
            $sqlOptions['selectFields'] .= ", '' as message ";
        }

        return $sqlOptions;
    }
}

// ******************** FOR IDE AUTO COMPLETE ********************
if (false)
{
    class XFCP_SV_OptimizedListQueries_XenForo_Model_Post extends XenForo_Model_Post {}
}