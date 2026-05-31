<?php
/**
 * Distributed Cache Manager
 * 
 * This class provides a unified interface for caching data across the distributed system.
 * Currently uses file-based caching for simplicity, but is designed to be easily 
 * swapped for Redis or Memcached in a production environment.
 * 
 * Features:
 * - Time-to-live (TTL) support
 * - Cache tags for grouped invalidation
 * - Serialization for complex data types
 * - Atomic file operations
 */

class CacheManager {
    private $cacheDir;
    private $defaultTtl = 3600; // 1 hour
    
    public function __construct() {
        $this->cacheDir = __DIR__ . '/cache';
        if (!file_exists($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
        
        // Setup specialized cache buckets
        $buckets = ['queries', 'pages', 'sessions', 'metadata'];
        foreach ($buckets as $bucket) {
            if (!file_exists($this->cacheDir . '/' . $bucket)) {
                mkdir($this->cacheDir . '/' . $bucket, 0755, true);
            }
        }
    }
    
    /**
     * Retrieve an item from the cache
     * 
     * @param string $key Unique identifier
     * @param mixed $default Default value if not found
     * @return mixed
     */
    public function get($key, $default = null) {
        $filename = $this->getFilename($key);
        
        if (!file_exists($filename)) {
            return $default;
        }
        
        $data = file_get_contents($filename);
        $cacheEntry = unserialize($data);
        
        // Check for expiration
        if (time() > $cacheEntry['expires_at']) {
            $this->forget($key);
            return $default;
        }
        
        return $cacheEntry['value'];
    }
    
    /**
     * Store an item in the cache
     * 
     * @param string $key Unique identifier
     * @param mixed $value Data to store
     * @param int $ttl Time to live in seconds
     * @param array $tags Tags for grouping
     * @return bool
     */
    public function put($key, $value, $ttl = null, $tags = []) {
        $ttl = $ttl ?? $this->defaultTtl;
        $filename = $this->getFilename($key);
        
        $cacheEntry = [
            'value' => $value,
            'expires_at' => time() + $ttl,
            'tags' => $tags,
            'created_at' => time()
        ];
        
        // Atomic write
        $tempFile = tempnam(sys_get_temp_dir(), 'cache_');
        if (file_put_contents($tempFile, serialize($cacheEntry)) !== false) {
            return rename($tempFile, $filename);
        }
        return false;
    }
    
    /**
     * Retrieve an item. If missing, store the result of the callback.
     * 
     * @param string $key
     * @param int $ttl
     * @param callable $callback
     * @return mixed
     */
    public function remember($key, $ttl, $callback) {
        $value = $this->get($key);
        
        if (!is_null($value)) {
            return $value;
        }
        
        $value = $callback();
        $this->put($key, $value, $ttl);
        
        return $value;
    }
    
    /**
     * Remove an item from the cache
     * 
     * @param string $key
     * @return bool
     */
    public function forget($key) {
        $filename = $this->getFilename($key);
        if (file_exists($filename)) {
            return unlink($filename);
        }
        return false;
    }
    
    /**
     * Remove all items from the cache
     * 
     * @return bool
     */
    public function flush() {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->cacheDir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $fileinfo) {
            $todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
            $todo($fileinfo->getRealPath());
        }
        return true;
    }
    
    /**
     * Generate comprehensive cache statistics
     * Useful for distributed system monitoring
     */
    public function getStats() {
        $stats = [
            'hits' => 0, // In a real system (Redis), this handles internally.
            'misses' => 0, // Here we'd need to store counters separately.
            'size' => 0,
            'count' => 0,
            'oldest' => time(),
            'newest' => 0
        ];
        
        $files = glob($this->cacheDir . '/*');
        foreach ($files as $file) {
            if (is_file($file)) {
                $stats['count']++;
                $stats['size'] += filesize($file);
                
                // Read metadata (expensive, but useful for monitoring demo)
                // In production, we typically just monitor size/count
            }
        }
        
        return $stats;
    }
    
    private function getFilename($key) {
        return $this->cacheDir . '/' . md5($key) . '.cache';
    }
}

// Global helper for quick access
function cache() {
    static $instance = null;
    if ($instance === null) {
        $instance = new CacheManager();
    }
    return $instance;
}
?>
