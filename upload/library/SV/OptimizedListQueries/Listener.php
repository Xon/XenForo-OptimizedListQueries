<?php

class SV_OptimizedListQueries_Listener
{
    public static function load_class($class, array &$extend)
    {
        $extend[] = 'SV_OptimizedListQueries_' . $class;
    }
}
