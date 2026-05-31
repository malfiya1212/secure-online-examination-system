<?php
/**
 * Distributed Session Handler
 * Stores session data in the database rather than the local filesystem.
 * This allows multiple web servers to share login states.
 */

class DatabaseSessionHandler implements SessionHandlerInterface {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function open($savePath, $sessionName): bool {
        return true;
    }

    public function close(): bool {
        return true;
    }

    public function read($id): string|false {
        $stmt = $this->db->prepare("SELECT data FROM sessions WHERE id = ?");
        $stmt->bind_param("s", $id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            return $row['data'];
        }
        return '';
    }

    public function write($id, $data): bool {
        $access = time();
        $stmt = $this->db->prepare("REPLACE INTO sessions (id, access, data) VALUES (?, ?, ?)");
        $stmt->bind_param("sis", $id, $access, $data);
        return $stmt->execute();
    }

    public function destroy($id): bool {
        $stmt = $this->db->prepare("DELETE FROM sessions WHERE id = ?");
        $stmt->bind_param("s", $id);
        return $stmt->execute();
    }

    public function gc($maxlifetime): int|false {
        $old = time() - $maxlifetime;
        $stmt = $this->db->prepare("DELETE FROM sessions WHERE access < ?");
        $stmt->bind_param("i", $old);
        if ($stmt->execute()) {
            return $this->db->affected_rows;
        }
        return false;
    }
}

// Ensure database connection is available before initializing
if (isset($conn)) {
    // Only set the handler if headers haven't been sent yet (avoid CLI/manual setup warnings)
    if (!headers_sent() && session_status() === PHP_SESSION_NONE) {
        $handler = new DatabaseSessionHandler($conn);
        session_set_save_handler($handler, true);
    }
}
?>
