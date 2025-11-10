<?php
/**
 * Blockchain Integration Class
 * Simple blockchain implementation for order tracking
 */

class Blockchain {
    private $conn;
    
    public function __construct($connection) {
        $this->conn = $connection;
        $this->createBlockchainTable();
    }
    
    /**
     * Create blockchain table if not exists
     */
    private function createBlockchainTable() {
        $sql = "CREATE TABLE IF NOT EXISTS blockchain_orders (
            id INT AUTO_INCREMENT PRIMARY KEY,
            block_index INT NOT NULL,
            order_id INT NOT NULL,
            timestamp BIGINT NOT NULL,
            data TEXT NOT NULL,
            previous_hash VARCHAR(64) NOT NULL,
            hash VARCHAR(64) NOT NULL,
            nonce INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_order (order_id),
            INDEX idx_block (block_index),
            INDEX idx_hash (hash)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        $this->conn->query($sql);
    }
    
    /**
     * Create genesis block
     */
    public function createGenesisBlock() {
        $check = $this->conn->query("SELECT COUNT(*) as count FROM blockchain_orders");
        $row = $check->fetch_assoc();
        
        if ($row['count'] == 0) {
            $data = json_encode([
                'type' => 'genesis',
                'message' => 'Genesis Block - ShopHub Blockchain'
            ]);
            
            $block = [
                'block_index' => 0,
                'order_id' => 0,
                'timestamp' => time(),
                'data' => $data,
                'previous_hash' => '0'
            ];
            
            $hash = $this->calculateHash($block, 0);
            $nonce = 0;
            
            $stmt = $this->conn->prepare("INSERT INTO blockchain_orders 
                (block_index, order_id, timestamp, data, previous_hash, hash, nonce) 
                VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("iissssi", 
                $block['block_index'], 
                $block['order_id'], 
                $block['timestamp'], 
                $block['data'], 
                $block['previous_hash'], 
                $hash, 
                $nonce
            );
            $stmt->execute();
            $stmt->close();
        }
    }
    
    /**
     * Add order to blockchain
     */
    public function addOrderBlock($order_id, $order_data) {
        // Get last block
        $last_block = $this->getLastBlock();
        
        if (!$last_block) {
            $this->createGenesisBlock();
            $last_block = $this->getLastBlock();
        }
        
        // Create new block
        $block = [
            'block_index' => $last_block['block_index'] + 1,
            'order_id' => $order_id,
            'timestamp' => time(),
            'data' => json_encode($order_data),
            'previous_hash' => $last_block['hash']
        ];
        
        // Mine block (proof of work)
        $result = $this->mineBlock($block);
        
        // Save to database
        $stmt = $this->conn->prepare("INSERT INTO blockchain_orders 
            (block_index, order_id, timestamp, data, previous_hash, hash, nonce) 
            VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iissssi", 
            $result['block_index'], 
            $result['order_id'], 
            $result['timestamp'], 
            $result['data'], 
            $result['previous_hash'], 
            $result['hash'], 
            $result['nonce']
        );
        $stmt->execute();
        $stmt->close();
        
        return $result;
    }
    
    /**
     * Update order status in blockchain
     */
    public function updateOrderStatus($order_id, $new_status) {
        // Get existing block
        $stmt = $this->conn->prepare("SELECT * FROM blockchain_orders WHERE order_id = ?");
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $block = $result->fetch_assoc();
        $stmt->close();
        
        if ($block) {
            // Decode existing data
            $data = json_decode($block['data'], true);
            
            // Add status update to history
            if (!isset($data['status_history'])) {
                $data['status_history'] = [];
            }
            
            $data['status_history'][] = [
                'status' => $new_status,
                'timestamp' => time(),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            $data['current_status'] = $new_status;
            
            // Update block data
            $updated_data = json_encode($data);
            $stmt = $this->conn->prepare("UPDATE blockchain_orders SET data = ? WHERE order_id = ?");
            $stmt->bind_param("si", $updated_data, $order_id);
            $stmt->execute();
            $stmt->close();
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Get order blockchain data
     */
    public function getOrderBlock($order_id) {
        $stmt = $this->conn->prepare("SELECT * FROM blockchain_orders WHERE order_id = ?");
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $block = $result->fetch_assoc();
        $stmt->close();
        
        if ($block) {
            $block['data'] = json_decode($block['data'], true);
        }
        
        return $block;
    }
    
    /**
     * Get last block
     */
    private function getLastBlock() {
        $result = $this->conn->query("SELECT * FROM blockchain_orders ORDER BY block_index DESC LIMIT 1");
        return $result->fetch_assoc();
    }
    
    /**
     * Calculate hash for block
     */
    private function calculateHash($block, $nonce) {
        $data = $block['block_index'] . 
                $block['timestamp'] . 
                $block['data'] . 
                $block['previous_hash'] . 
                $nonce;
        return hash('sha256', $data);
    }
    
    /**
     * Mine block (Proof of Work)
     */
    private function mineBlock($block, $difficulty = 2) {
        $nonce = 0;
        $target = str_repeat('0', $difficulty);
        
        while (true) {
            $hash = $this->calculateHash($block, $nonce);
            
            if (substr($hash, 0, $difficulty) === $target) {
                $block['hash'] = $hash;
                $block['nonce'] = $nonce;
                return $block;
            }
            
            $nonce++;
            
            // Prevent infinite loop
            if ($nonce > 1000000) {
                break;
            }
        }
        
        // Fallback
        $block['hash'] = $this->calculateHash($block, $nonce);
        $block['nonce'] = $nonce;
        return $block;
    }
    
    /**
     * Verify blockchain integrity
     */
    public function verifyChain() {
        $result = $this->conn->query("SELECT * FROM blockchain_orders ORDER BY block_index ASC");
        $blocks = [];
        
        while ($row = $result->fetch_assoc()) {
            $blocks[] = $row;
        }
        
        for ($i = 1; $i < count($blocks); $i++) {
            $current = $blocks[$i];
            $previous = $blocks[$i - 1];
            
            // Verify hash
            $calculated_hash = $this->calculateHash([
                'block_index' => $current['block_index'],
                'timestamp' => $current['timestamp'],
                'data' => $current['data'],
                'previous_hash' => $current['previous_hash']
            ], $current['nonce']);
            
            if ($calculated_hash !== $current['hash']) {
                return [
                    'valid' => false,
                    'error' => "Invalid hash at block {$current['block_index']}"
                ];
            }
            
            // Verify chain link
            if ($current['previous_hash'] !== $previous['hash']) {
                return [
                    'valid' => false,
                    'error' => "Broken chain at block {$current['block_index']}"
                ];
            }
        }
        
        return [
            'valid' => true,
            'total_blocks' => count($blocks)
        ];
    }
    
    /**
     * Get blockchain statistics
     */
    public function getStats() {
        $result = $this->conn->query("SELECT 
            COUNT(*) as total_blocks,
            MIN(timestamp) as first_block_time,
            MAX(timestamp) as last_block_time
            FROM blockchain_orders");
        
        return $result->fetch_assoc();
    }
}
?>
