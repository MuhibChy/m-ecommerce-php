<?php
/**
 * Customer Manager Class
 * M-EcommerceCRM - Customer Management System
 */

class CustomerManager {
    private $db;
    
    public function __construct() {
        $this->db = getDB();
    }
    
    /**
     * Get all customers with pagination and filtering
     */
    public function getCustomers($page = 1, $limit = 20, $filters = []) {
        $offset = ($page - 1) * $limit;
        $where = [];
        $params = [];
        
        // Build WHERE clause based on filters
        if (!empty($filters['search'])) {
            $where[] = "(first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR company LIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
        }
        
        if (!empty($filters['customer_type'])) {
            $where[] = "customer_type = ?";
            $params[] = $filters['customer_type'];
        }
        
        if (!empty($filters['status'])) {
            $where[] = "status = ?";
            $params[] = $filters['status'];
        }
        
        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        
        // Get total count
        $countSql = "SELECT COUNT(*) as total FROM crm_customers $whereClause";
        $countStmt = $this->db->prepare($countSql);
        $countStmt->execute($params);
        $total = $countStmt->fetch()['total'];
        
        // Get customers
        $limitInt = (int)$limit;
        $offsetInt = (int)$offset;
        
        $sql = "SELECT c.*, u.name as user_name, 
                (SELECT COUNT(*) FROM crm_activities WHERE customer_id = c.id) as activity_count,
                (SELECT activity_date FROM crm_activities WHERE customer_id = c.id ORDER BY activity_date DESC LIMIT 1) as last_activity
                FROM crm_customers c 
                LEFT JOIN users u ON c.user_id = u.id 
                $whereClause 
                ORDER BY c.created_at DESC 
                LIMIT $limitInt OFFSET $offsetInt";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $customers = $stmt->fetchAll();
        
        return [
            'customers' => $customers,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'total_pages' => ceil($total / $limit)
        ];
    }
    
    /**
     * Get single customer by ID
     */
    public function getCustomer($id) {
        $sql = "SELECT c.*, u.name as user_name, u.avatar 
                FROM crm_customers c 
                LEFT JOIN users u ON c.user_id = u.id 
                WHERE c.id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    /**
     * Get customer by email
     */
    public function getCustomerByEmail($email) {
        $sql = "SELECT * FROM crm_customers WHERE email = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$email]);
        return $stmt->fetch();
    }
    
    /**
     * Add new customer
     */
    public function addCustomer($data) {
        // Check if customer already exists
        if ($this->getCustomerByEmail($data['email'])) {
            throw new Exception('Customer with this email already exists');
        }
        
        $sql = "INSERT INTO crm_customers (user_id, first_name, last_name, email, phone, company, 
                address, city, state, country, postal_code, customer_type, status, source, notes) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute([
            $data['user_id'] ?? null,
            $data['first_name'],
            $data['last_name'],
            $data['email'],
            $data['phone'] ?? null,
            $data['company'] ?? null,
            $data['address'] ?? null,
            $data['city'] ?? null,
            $data['state'] ?? null,
            $data['country'] ?? null,
            $data['postal_code'] ?? null,
            $data['customer_type'] ?? 'lead',
            $data['status'] ?? 'active',
            $data['source'] ?? null,
            $data['notes'] ?? null
        ]);
        
        if ($result) {
            $customerId = $this->db->lastInsertId();
            
            // Log activity
            $this->logActivity($customerId, 'note', 'Customer Created', 'New customer added to CRM');
            
            return $customerId;
        }
        
        return false;
    }
    
    /**
     * Update customer
     */
    public function updateCustomer($id, $data) {
        $sql = "UPDATE crm_customers SET 
                first_name = ?, last_name = ?, email = ?, phone = ?, company = ?, 
                address = ?, city = ?, state = ?, country = ?, postal_code = ?, 
                customer_type = ?, status = ?, source = ?, notes = ?, 
                last_contact_date = ?, updated_at = CURRENT_TIMESTAMP 
                WHERE id = ?";
        
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute([
            $data['first_name'],
            $data['last_name'],
            $data['email'],
            $data['phone'] ?? null,
            $data['company'] ?? null,
            $data['address'] ?? null,
            $data['city'] ?? null,
            $data['state'] ?? null,
            $data['country'] ?? null,
            $data['postal_code'] ?? null,
            $data['customer_type'] ?? 'lead',
            $data['status'] ?? 'active',
            $data['source'] ?? null,
            $data['notes'] ?? null,
            $data['last_contact_date'] ?? null,
            $id
        ]);
        
        if ($result) {
            // Log activity
            $this->logActivity($id, 'note', 'Customer Updated', 'Customer information updated');
        }
        
        return $result;
    }
    
    /**
     * Delete customer
     */
    public function deleteCustomer($id) {
        $stmt = $this->db->prepare("DELETE FROM crm_customers WHERE id = ?");
        return $stmt->execute([$id]);
    }
    
    /**
     * Import customers from existing users
     */
    public function importFromUsers() {
        $sql = "INSERT INTO crm_customers (user_id, first_name, last_name, email, customer_type, status, source)
                SELECT u.id, 
                       SUBSTRING_INDEX(u.name, ' ', 1) as first_name,
                       SUBSTRING_INDEX(u.name, ' ', -1) as last_name,
                       u.email,
                       CASE WHEN u.is_admin = 1 THEN 'customer' ELSE 'customer' END,
                       'active',
                       'website_registration'
                FROM users u 
                LEFT JOIN crm_customers c ON u.id = c.user_id 
                WHERE c.id IS NULL";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute();
    }
    
    /**
     * Get customer activities
     */
    public function getCustomerActivities($customerId, $limit = 50) {
        $limitInt = (int)$limit;
        $sql = "SELECT a.*, u.name as created_by_name 
                FROM crm_activities a 
                LEFT JOIN users u ON a.created_by = u.id 
                WHERE a.customer_id = ? 
                ORDER BY a.activity_date DESC 
                LIMIT $limitInt";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$customerId]);
        return $stmt->fetchAll();
    }
    
    /**
     * Log customer activity
     */
    public function logActivity($customerId, $type, $subject, $description = null, $metadata = null, $userId = null) {
        if (!$userId && isset($_SESSION['user_id'])) {
            $userId = $_SESSION['user_id'];
        }
        
        $sql = "INSERT INTO crm_activities (customer_id, activity_type, subject, description, 
                activity_date, created_by, metadata) VALUES (?, ?, ?, ?, NOW(), ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $customerId, 
            $type, 
            $subject, 
            $description, 
            $userId, 
            $metadata ? json_encode($metadata) : null
        ]);
    }
    
    /**
     * Get customer statistics
     */
    public function getCustomerStats() {
        $stats = [];
        
        // Total customers
        $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM crm_customers");
        $stmt->execute();
        $stats['total'] = $stmt->fetch()['total'];
        
        // By type
        $stmt = $this->db->prepare("SELECT customer_type, COUNT(*) as count FROM crm_customers GROUP BY customer_type");
        $stmt->execute();
        $stats['by_type'] = $stmt->fetchAll();
        
        // By status
        $stmt = $this->db->prepare("SELECT status, COUNT(*) as count FROM crm_customers GROUP BY status");
        $stmt->execute();
        $stats['by_status'] = $stmt->fetchAll();
        
        // New this month
        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM crm_customers WHERE MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW())");
        $stmt->execute();
        $stats['new_this_month'] = $stmt->fetch()['count'];
        
        return $stats;
    }
    
    /**
     * Search customers for autocomplete
     */
    public function searchCustomers($query, $limit = 10) {
        $limitInt = (int)$limit;
        $sql = "SELECT id, CONCAT(first_name, ' ', last_name) as name, email, company 
                FROM crm_customers 
                WHERE (first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR company LIKE ?) 
                AND status = 'active' 
                ORDER BY first_name, last_name 
                LIMIT $limitInt";
        
        $searchTerm = '%' . $query . '%';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
        return $stmt->fetchAll();
    }
}
?>
