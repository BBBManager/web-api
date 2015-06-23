<?php
class IMDT_Util_Cache{
    
    private static function initCache($cacheDataInstance){
        $frontend= array(
            'lifetime'                  => $cacheDataInstance->getCacheLifeTime(),
            'automatic_serialization'   => true
        );
        
        IMDT_Util_File::checkPath($cacheDataInstance->getCacheDir(), true);

        $backend= array(
            'cache_dir' => $cacheDataInstance->getCacheDir()
        );

        $cache = Zend_Cache::factory(
            'Core',
            'File',
            $frontend,
            $backend
        );
	
	return $cache;
    }
    
    public static function getFromCache($cacheDataInstance){
	$zendCache = self::initCache($cacheDataInstance);
        $cacheData = $zendCache->load($cacheDataInstance->getCacheStorageKey());
        
        if($cacheData == null){
            $cacheData = $cacheDataInstance->generateData();
            $zendCache->save($cacheData, $cacheDataInstance->getCacheStorageKey());
        }
        
        return $cacheData;
    }
    
    public static function clean($cacheDataInstance){
	$zendCache = self::initCache($cacheDataInstance);
	$zendCache->save(null, $cacheDataInstance->getCacheStorageKey());
    }

    public static function setInCache($cacheDataInstance, $value){
        $zendCache = self::initCache($cacheDataInstance);
        $zendCache->save($value, $cacheDataInstance->getCacheStorageKey());
    }
}
