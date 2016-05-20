<?php

abstract class SV_OptimizedListQueries_CodeEvent extends XenForo_CodeEvent
{
    public static function getListeners()
    {
        return static::$_listeners;
    }
}

// This class is used to encapsulate global state between layers without using $GLOBAL[] or
// relying on the consumer being loaded correctly by the dynamic class autoloader
class SV_OptimizedListQueries_Globals
{    
    public static $possibleNewSearch = false;
    public static $slimPostFetchForSearch = false;


    public static function removeHardEnabledHooks()
    {
        // post rating hooks add overhead which can't be avoided kill the reference to them.

        $listeners  = SV_OptimizedListQueries_CodeEvent::getListeners();

        foreach ($listeners['load_class_model'] AS &$callbacks)
        {
            foreach ($callbacks AS $key => $callback)
            {
                if ($callback[0] == 'Dark_PostRating_EventListener' || $callback[0] == 'LiquidPro_SimpleForms_Listener_Proxy')
                {
                    unset($callbacks[$key]);
                    $changed = true;
                }
            }
        }
        if ($changed)
        {
            XenForo_CodeEvent::setListeners($listeners, false);
        }
    }

    private function __construct() {}
}
