<?php

class SV_OptimizedListQueries_XenForo_DataWriter_Discussion_Thread extends XFCP_XenForo_DataWriter_Discussion_Thread
{
    public function rebuildDiscussion()
    {
        SV_OptimizedListQueries_Globals::$replaceThreadCounterShim = true;
        try
        {
            return parent::rebuildDiscussion();
        }
        finally
        {
            SV_OptimizedListQueries_Globals::$replaceThreadCounterShim = false;
        }
    }
}

if (false)
{
    class XFCP_XenForo_DataWriter_Discussion_Thread extends XenForo_DataWriter_Discussion_Thread {}
}