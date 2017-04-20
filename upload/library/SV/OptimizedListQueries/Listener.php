<?php

class SV_OptimizedListQueries_Listener
{
    const AddonNameSpace = 'SV_OptimizedListQueries_';

    public static function load_class($class, array &$extend)
    {
        $extend[] = self::AddonNameSpace . $class;
    }
}