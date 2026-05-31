<?php
/**
 * Distributed Lock Manager
 * 
 * Implements locking to prevent race conditions in a distributed node environment.
 * Uses MySQL as the coordination point since it's the shared source of truth.
 * 
 * Crucial for:
 * - Preventing double result submissions
 * - Ensuring exam scheduling consistency
 * - Managing shared resource updates
 */

require_once 'db_connect.php';

class DistributedLock {
    private $conn;
    private $lockTimeout = 10; // seconds
    
    public function __construct($dbConnection) {
        $this->conn = $dbConnection;
        $this->ensureLockTable();
    }
    
    /**
     * Create the locks table if it doesn't exist
     */
    private function ensureLockTable() {
        $sql = "CREATE TABLE IF NOT EXISTS distributed_locks (
            lock_key VARCHAR(255) PRIMARY KEY,
            locked_by VARCHAR(255) NOT NULL,
            locked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            expires_at TIMESTAMP NULL
        ) ENGINE=InnoDB";
        
        $this->conn->query($sql);
    }
    
    /**
     * Attempt to acquire a lock
     * 
     * @param string $key Unique lock identifier
     * @param int $ttl Time to keep lock in seconds
     * @return bool True if acquired, False if blocked
     */
    public function acquire($key, $ttl = 10) {
        $nodeId = defined('SYSTEM_NODE_ID') ? SYSTEM_NODE_ID : gethostname();
        $expiresAt = date('Y-m-d H:i:s', time() + $ttl);
        
        // Clean up any stale locks for this key first
        $this->cleanup($key);
        
        try {
            $stmt = $this->conn->prepare("INSERT INTO distributed_locks (lock_key, locked_by, expires_at) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $key, $nodeId, $expiresAt);
            return $stmt->execute();
        } catch (Exception $e) {
            // Duplicate key means query failed -> Lock exists
            return false;
        }
    }
    
    /**
     * Wait and acquire lock (Blocking)
     * 
     * @param string $key
     * @param int $ttl
     * @param int $maxWait Max time to wait in seconds
     * @return bool
     */
    public function blockFor($key, $ttl = 10, $maxWait = 5) {
        $start = time();
        
        while (time() - $start < $maxWait) {
            if ($this->acquire($key, $ttl)) {
                return true;
            }
            usleep(200000); // Wait 200ms
        }
        
        return false;
    }
    
    /**
     * Release a lock
     * 
     * @param string $key
     * @return bool
     */
    public function release($key) {
        $nodeId = defined('SYSTEM_NODE_ID') ? SYSTEM_NODE_ID : gethostname();
        
        // Only release if WE own it (safety mechanism)
        $stmt = $this->conn->prepare("DELETE FROM distributed_locks WHERE lock_key = ? AND locked_by = ?");
        $stmt->bind_param("ss", $key, $nodeId);
        $stmt->execute();
        
        return $stmt->affected_rows > 0;
    }
    
    /**
     * Remove expired locks
     */
    private function cleanup($key) {
        $now = date('Y-m-d H:i:s');
        $stmt = $this->conn->prepare("DELETE FROM distributed_locks WHERE lock_key = ? AND expires_at < ?");
        $stmt->bind_param("ss", $key, $now);
        $stmt->execute();
    }
}

// Helper function
function get_lock_manager($conn) {
    return new DistributedLock($conn);
}
?>
