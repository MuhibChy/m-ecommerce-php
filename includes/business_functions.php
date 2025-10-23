<?php
/**
 * Business Management Functions
 * Enhanced E-commerce Platform - Sales, Purchase, Stock, Finance & Support
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';


// Helper function to format date
function formatDate($date, $format = 'M d, Y') {
    return date($format, strtotime($date));
}

// Helper function to get status badge class
function getStatusBadgeClass($status) {
    $classes = [
        'pending' => 'bg-yellow-100 text-yellow-800',
        'processing' => 'bg-blue-100 text-blue-800',
        'completed' => 'bg-green-100 text-green-800',
        'cancelled' => 'bg-red-100 text-red-800',
        'active' => 'bg-green-100 text-green-800',
        'inactive' => 'bg-gray-100 text-gray-800',
        'open' => 'bg-blue-100 text-blue-800',
        'in_progress' => 'bg-yellow-100 text-yellow-800',
        'resolved' => 'bg-green-100 text-green-800',
        'closed' => 'bg-gray-100 text-gray-800',
        'ordered' => 'bg-blue-100 text-blue-800',
        'received' => 'bg-green-100 text-green-800',
        'delivered' => 'bg-green-100 text-green-800',
        'shipped' => 'bg-purple-100 text-purple-800',
        'paid' => 'bg-green-100 text-green-800',
        'partial' => 'bg-yellow-100 text-yellow-800',
        'refunded' => 'bg-red-100 text-red-800'
    ];
    
    return $classes[$status] ?? 'bg-gray-100 text-gray-800';
}

// Helper function to get priority badge class
function getPriorityBadgeClass($priority) {
    $classes = [
        'low' => 'bg-green-100 text-green-800',
        'medium' => 'bg-yellow-100 text-yellow-800',
        'high' => 'bg-orange-100 text-orange-800',
        'urgent' => 'bg-red-100 text-red-800'
    ];
    
    return $classes[$priority] ?? 'bg-gray-100 text-gray-800';
}


class SupplierManager {
    private $db;
    
    public function __construct() {
        $this->db = getDB();
    }
    
    public function getAllSuppliers($status = null) {
        $sql = "SELECT * FROM suppliers WHERE 1=1";
        $params = [];
        
        if ($status) {
            $sql .= " AND status = ?";
            $params[] = $status;
        }
        
        $sql .= " ORDER BY name ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    public function getSupplierById($id) {
        $stmt = $this->db->prepare("SELECT * FROM suppliers WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    public function createSupplier($data) {
        $stmt = $this->db->prepare("
            INSERT INTO suppliers (name, contact_person, email, phone, address, status) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        return $stmt->execute([
            $data['name'],
            $data['contact_person'],
            $data['email'],
            $data['phone'],
            $data['address'],
            $data['status'] ?? 'active'
        ]);
    }
    
    public function updateSupplier($id, $data) {
        $stmt = $this->db->prepare("
            UPDATE suppliers 
            SET name = ?, contact_person = ?, email = ?, phone = ?, address = ?, status = ?
            WHERE id = ?
        ");
        return $stmt->execute([
            $data['name'],
            $data['contact_person'],
            $data['email'],
            $data['phone'],
            $data['address'],
            $data['status'],
            $id
        ]);
    }
    
    public function deleteSupplier($id) {
        $stmt = $this->db->prepare("DELETE FROM suppliers WHERE id = ?");
        return $stmt->execute([$id]);
    }
}

class PurchaseManager {
    private $db;
    
    public function __construct() {
        $this->db = getDB();
    }
    
    public function getAllPurchaseOrders($status = null, $limit = null) {
        $sql = "SELECT po.*, s.name as supplier_name, u.name as created_by_name 
                FROM purchase_orders po 
                LEFT JOIN suppliers s ON po.supplier_id = s.id 
                LEFT JOIN users u ON po.created_by = u.id 
                WHERE 1=1";
        $params = [];
        
        if ($status) {
            $sql .= " AND po.status = ?";
            $params[] = $status;
        }
        
        $sql .= " ORDER BY po.created_at DESC";
        
        if ($limit) {
            $sql .= " LIMIT " . (int)$limit;
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    public function getPurchaseOrderById($id) {
        $stmt = $this->db->prepare("
            SELECT po.*, s.name as supplier_name, s.email as supplier_email, s.phone as supplier_phone
            FROM purchase_orders po 
            LEFT JOIN suppliers s ON po.supplier_id = s.id 
            WHERE po.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    public function getPurchaseOrderItems($purchaseOrderId) {
        $stmt = $this->db->prepare("
            SELECT poi.*, p.name as product_name, p.image as product_image
            FROM purchase_order_items poi 
            LEFT JOIN products p ON poi.product_id = p.id 
            WHERE poi.purchase_order_id = ?
        ");
        $stmt->execute([$purchaseOrderId]);
        return $stmt->fetchAll();
    }
    
    public function createPurchaseOrder($data, $items, $userId) {
        try {
            $this->db->beginTransaction();
            
            // Generate order number
            $orderNumber = 'PO' . date('Y') . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
            
            // Create purchase order
            $stmt = $this->db->prepare("
                INSERT INTO purchase_orders (supplier_id, order_number, total_amount, status, order_date, expected_delivery, notes, created_by) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $data['supplier_id'],
                $orderNumber,
                $data['total_amount'],
                $data['status'] ?? 'pending',
                $data['order_date'],
                $data['expected_delivery'],
                $data['notes'],
                $userId
            ]);
            
            $purchaseOrderId = $this->db->lastInsertId();
            
            // Add items
            foreach ($items as $item) {
                $stmt = $this->db->prepare("
                    INSERT INTO purchase_order_items (purchase_order_id, product_id, quantity, unit_cost, total_cost) 
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $purchaseOrderId,
                    $item['product_id'],
                    $item['quantity'],
                    $item['unit_cost'],
                    $item['total_cost']
                ]);
            }
            
            $this->db->commit();
            return $purchaseOrderId;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
    
    public function updatePurchaseOrderStatus($id, $status, $receivedDate = null) {
        $sql = "UPDATE purchase_orders SET status = ?";
        $params = [$status];
        
        if ($receivedDate && $status === 'received') {
            $sql .= ", received_date = ?";
            $params[] = $receivedDate;
        }
        
        $sql .= " WHERE id = ?";
        $params[] = $id;
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }
}

class InventoryManager {
    private $db;
    
    public function __construct() {
        $this->db = getDB();
    }
    
    public function getAllInventory($lowStock = false) {
        $sql = "SELECT i.*, p.name as product_name, p.image as product_image, p.price, c.name as category_name
                FROM inventory i 
                LEFT JOIN products p ON i.product_id = p.id 
                LEFT JOIN categories c ON p.category_id = c.id 
                WHERE 1=1";
        
        if ($lowStock) {
            $sql .= " AND i.quantity_in_stock <= i.reorder_level";
        }
        
        $sql .= " ORDER BY p.name ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    public function getInventoryByProduct($productId) {
        $stmt = $this->db->prepare("
            SELECT i.*, p.name as product_name 
            FROM inventory i 
            LEFT JOIN products p ON i.product_id = p.id 
            WHERE i.product_id = ?
        ");
        $stmt->execute([$productId]);
        return $stmt->fetch();
    }
    
    public function updateStock($productId, $quantity, $movementType, $referenceType, $referenceId = null, $notes = '', $userId = 1) {
        try {
            $this->db->beginTransaction();
            
            // Update inventory
            if ($movementType === 'in') {
                $stmt = $this->db->prepare("UPDATE inventory SET quantity_in_stock = quantity_in_stock + ? WHERE product_id = ?");
            } else {
                $stmt = $this->db->prepare("UPDATE inventory SET quantity_in_stock = quantity_in_stock - ? WHERE product_id = ?");
            }
            $stmt->execute([$quantity, $productId]);
            
            // Record stock movement
            $stmt = $this->db->prepare("
                INSERT INTO stock_movements (product_id, movement_type, quantity, reference_type, reference_id, notes, created_by) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$productId, $movementType, $quantity, $referenceType, $referenceId, $notes, $userId]);
            
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
    
    public function getStockMovements($productId = null, $limit = 50) {
        $sql = "SELECT sm.*, p.name as product_name, u.name as created_by_name
                FROM stock_movements sm 
                LEFT JOIN products p ON sm.product_id = p.id 
                LEFT JOIN users u ON sm.created_by = u.id 
                WHERE 1=1";
        $params = [];
        
        if ($productId) {
            $sql .= " AND sm.product_id = ?";
            $params[] = $productId;
        }
        
        $sql .= " ORDER BY sm.created_at DESC LIMIT " . (int)$limit;
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    public function adjustStock($productId, $newQuantity, $notes, $userId) {
        $currentStock = $this->getInventoryByProduct($productId);
        if (!$currentStock) return false;
        
        $difference = $newQuantity - $currentStock['quantity_in_stock'];
        if ($difference == 0) return true;
        
        $movementType = $difference > 0 ? 'in' : 'out';
        $quantity = abs($difference);
        
        return $this->updateStock($productId, $quantity, $movementType, 'adjustment', null, $notes, $userId);
    }
}

class SalesManager {
    private $db;
    
    public function __construct() {
        $this->db = getDB();
    }
    
    public function getAllSales($dateFrom = null, $dateTo = null, $limit = null) {
        $sql = "SELECT s.*, u.name as customer_name, u.email as customer_email, ub.name as created_by_name
                FROM sales s 
                LEFT JOIN users u ON s.customer_id = u.id 
                LEFT JOIN users ub ON s.created_by = ub.id 
                WHERE 1=1";
        $params = [];
        
        if ($dateFrom) {
            $sql .= " AND s.sale_date >= ?";
            $params[] = $dateFrom;
        }
        
        if ($dateTo) {
            $sql .= " AND s.sale_date <= ?";
            $params[] = $dateTo;
        }
        
        $sql .= " ORDER BY s.created_at DESC";
        
        if ($limit) {
            $sql .= " LIMIT " . (int)$limit;
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    public function getSaleById($id) {
        $stmt = $this->db->prepare("
            SELECT s.*, u.name as customer_name, u.email as customer_email, o.shipping_address
            FROM sales s 
            LEFT JOIN users u ON s.customer_id = u.id 
            LEFT JOIN orders o ON s.order_id = o.id
            WHERE s.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    public function createSaleFromOrder($orderId, $userId) {
        try {
            $this->db->beginTransaction();
            
            // Get order details
            $stmt = $this->db->prepare("SELECT * FROM orders WHERE id = ?");
            $stmt->execute([$orderId]);
            $order = $stmt->fetch();
            
            if (!$order) {
                throw new Exception("Order not found");
            }
            
            // Generate sale number
            $saleNumber = 'S' . date('Y') . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
            
            // Create sale record
            $stmt = $this->db->prepare("
                INSERT INTO sales (order_id, sale_number, customer_id, subtotal, total_amount, sale_date, created_by) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $orderId,
                $saleNumber,
                $order['user_id'],
                $order['total_amount'],
                $order['total_amount'],
                date('Y-m-d'),
                $userId
            ]);
            
            $saleId = $this->db->lastInsertId();
            
            // Update stock for each item
            $stmt = $this->db->prepare("SELECT * FROM order_items WHERE order_id = ?");
            $stmt->execute([$orderId]);
            $orderItems = $stmt->fetchAll();
            
            $inventoryManager = new InventoryManager();
            foreach ($orderItems as $item) {
                $inventoryManager->updateStock(
                    $item['product_id'], 
                    $item['quantity'], 
                    'out', 
                    'sale', 
                    $saleId, 
                    "Sale #$saleNumber", 
                    $userId
                );
            }
            
            $this->db->commit();
            return $saleId;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
    
    public function getSalesReport($dateFrom, $dateTo) {
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(*) as total_sales,
                SUM(total_amount) as total_revenue,
                AVG(total_amount) as average_sale,
                SUM(CASE WHEN payment_status = 'paid' THEN total_amount ELSE 0 END) as paid_amount,
                SUM(CASE WHEN payment_status = 'pending' THEN total_amount ELSE 0 END) as pending_amount
            FROM sales 
            WHERE sale_date BETWEEN ? AND ?
        ");
        $stmt->execute([$dateFrom, $dateTo]);
        return $stmt->fetch();
    }
    
    public function createDirectSale($customerData, $items, $paymentMethod, $userId) {
        try {
            $this->db->beginTransaction();
            
            // Calculate totals
            $subtotal = 0;
            foreach ($items as $item) {
                $subtotal += $item['price'] * $item['quantity'];
            }
            
            $tax = $subtotal * 0.15; // 15% VAT for Bangladesh
            $total = $subtotal + $tax;
            
            // Generate sale number
            $saleNumber = 'R' . date('Ymd') . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
            
            // Create sale record - conditionally include created_by
            if ($userId !== null) {
                $stmt = $this->db->prepare("
                    INSERT INTO sales (sale_number, customer_name, customer_email, customer_phone, 
                                     subtotal, tax_amount, total_amount, payment_method, payment_status, 
                                     sale_date, created_by, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'paid', ?, ?, NOW())
                ");
                $stmt->execute([
                    $saleNumber,
                    $customerData['name'],
                    $customerData['email'] ?? null,
                    $customerData['phone'] ?? null,
                    $subtotal,
                    $tax,
                    $total,
                    $paymentMethod,
                    date('Y-m-d'),
                    $userId
                ]);
            } else {
                $stmt = $this->db->prepare("
                    INSERT INTO sales (sale_number, customer_name, customer_email, customer_phone, 
                                     subtotal, tax_amount, total_amount, payment_method, payment_status, 
                                     sale_date, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'paid', ?, NOW())
                ");
                $stmt->execute([
                    $saleNumber,
                    $customerData['name'],
                    $customerData['email'] ?? null,
                    $customerData['phone'] ?? null,
                    $subtotal,
                    $tax,
                    $total,
                    $paymentMethod,
                    date('Y-m-d')
                ]);
            }
            
            $saleId = $this->db->lastInsertId();
            
            // Create sale items and update stock
            $inventoryManager = new InventoryManager();
            foreach ($items as $item) {
                // Insert sale item
                $stmt = $this->db->prepare("
                    INSERT INTO sale_items (sale_id, product_id, product_name, quantity, unit_price, total_price) 
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $saleId,
                    $item['product_id'],
                    $item['product_name'],
                    $item['quantity'],
                    $item['price'],
                    $item['price'] * $item['quantity']
                ]);
                
                // Update stock
                $inventoryManager->updateStock(
                    $item['product_id'], 
                    $item['quantity'], 
                    'out', 
                    'sale', 
                    $saleId, 
                    "Sale Receipt #$saleNumber", 
                    $userId ?? null
                );
            }
            
            $this->db->commit();
            return ['success' => true, 'sale_id' => $saleId, 'sale_number' => $saleNumber];
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    public function getSaleWithItems($saleId) {
        // Get sale details
        $stmt = $this->db->prepare("
            SELECT s.*, u.name as created_by_name
            FROM sales s 
            LEFT JOIN users u ON s.created_by = u.id 
            WHERE s.id = ?
        ");
        $stmt->execute([$saleId]);
        $sale = $stmt->fetch();
        
        if (!$sale) {
            return null;
        }
        
        // Get sale items
        $stmt = $this->db->prepare("
            SELECT si.*, p.image
            FROM sale_items si
            LEFT JOIN products p ON si.product_id = p.id
            WHERE si.sale_id = ?
            ORDER BY si.id
        ");
        $stmt->execute([$saleId]);
        $sale['items'] = $stmt->fetchAll();
        
        return $sale;
    }
}

class FinancialManager {
    private $db;
    
    public function __construct() {
        $this->db = getDB();
    }
    
    public function getAllCategories($type = null) {
        $sql = "SELECT * FROM financial_categories WHERE is_active = 1";
        $params = [];
        
        if ($type) {
            $sql .= " AND type = ?";
            $params[] = $type;
        }
        
        $sql .= " ORDER BY name ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    public function getAllTransactions($type = null, $dateFrom = null, $dateTo = null, $limit = null) {
        $sql = "SELECT ft.*, fc.name as category_name, u.name as created_by_name
                FROM financial_transactions ft 
                LEFT JOIN financial_categories fc ON ft.category_id = fc.id 
                LEFT JOIN users u ON ft.created_by = u.id 
                WHERE 1=1";
        $params = [];
        
        if ($type) {
            $sql .= " AND ft.type = ?";
            $params[] = $type;
        }
        
        if ($dateFrom) {
            $sql .= " AND ft.transaction_date >= ?";
            $params[] = $dateFrom;
        }
        
        if ($dateTo) {
            $sql .= " AND ft.transaction_date <= ?";
            $params[] = $dateTo;
        }
        
        $sql .= " ORDER BY ft.transaction_date DESC, ft.created_at DESC";
        
        if ($limit) {
            $sql .= " LIMIT " . (int)$limit;
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    public function createTransaction($data, $userId) {
        $stmt = $this->db->prepare("
            INSERT INTO financial_transactions (category_id, type, amount, description, transaction_date, payment_method, reference_number, created_by) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        return $stmt->execute([
            $data['category_id'],
            $data['type'],
            $data['amount'],
            $data['description'],
            $data['transaction_date'],
            $data['payment_method'],
            $data['reference_number'],
            $userId
        ]);
    }
    
    public function getFinancialSummary($dateFrom, $dateTo) {
        $stmt = $this->db->prepare("
            SELECT 
                type,
                SUM(amount) as total_amount,
                COUNT(*) as transaction_count
            FROM financial_transactions 
            WHERE transaction_date BETWEEN ? AND ?
            GROUP BY type
        ");
        $stmt->execute([$dateFrom, $dateTo]);
        $results = $stmt->fetchAll();
        
        $summary = ['income' => 0, 'expense' => 0, 'profit' => 0];
        foreach ($results as $result) {
            $summary[$result['type']] = $result['total_amount'];
        }
        $summary['profit'] = $summary['income'] - $summary['expense'];
        
        return $summary;
    }
}

class SupportManager {
    private $db;
    
    public function __construct() {
        $this->db = getDB();
    }
    
    public function getAllTickets($status = null, $priority = null, $assignedTo = null, $limit = null) {
        $sql = "SELECT st.*, u.name as customer_name, u.email as customer_email, ua.name as assigned_to_name
                FROM support_tickets st 
                LEFT JOIN users u ON st.customer_id = u.id 
                LEFT JOIN users ua ON st.assigned_to = ua.id 
                WHERE 1=1";
        $params = [];
        
        if ($status) {
            $sql .= " AND st.status = ?";
            $params[] = $status;
        }
        
        if ($priority) {
            $sql .= " AND st.priority = ?";
            $params[] = $priority;
        }
        
        if ($assignedTo) {
            $sql .= " AND st.assigned_to = ?";
            $params[] = $assignedTo;
        }
        
        $sql .= " ORDER BY st.created_at DESC";
        
        if ($limit) {
            $sql .= " LIMIT " . (int)$limit;
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    public function getTicketById($id) {
        $stmt = $this->db->prepare("
            SELECT st.*, u.name as customer_name, u.email as customer_email, ua.name as assigned_to_name
            FROM support_tickets st 
            LEFT JOIN users u ON st.customer_id = u.id 
            LEFT JOIN users ua ON st.assigned_to = ua.id 
            WHERE st.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    public function getTicketReplies($ticketId) {
        $stmt = $this->db->prepare("
            SELECT str.*, u.name as user_name, u.is_admin
            FROM support_ticket_replies str 
            LEFT JOIN users u ON str.user_id = u.id 
            WHERE str.ticket_id = ? 
            ORDER BY str.created_at ASC
        ");
        $stmt->execute([$ticketId]);
        return $stmt->fetchAll();
    }
    
    public function createTicket($data, $customerId) {
        // Generate ticket number
        $ticketNumber = 'T' . date('Y') . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        
        $stmt = $this->db->prepare("
            INSERT INTO support_tickets (ticket_number, customer_id, subject, description, priority, category) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        return $stmt->execute([
            $ticketNumber,
            $customerId,
            $data['subject'],
            $data['description'],
            $data['priority'] ?? 'medium',
            $data['category'] ?? 'general'
        ]);
    }
    
    public function addReply($ticketId, $userId, $message, $isInternal = false) {
        $stmt = $this->db->prepare("
            INSERT INTO support_ticket_replies (ticket_id, user_id, message, is_internal) 
            VALUES (?, ?, ?, ?)
        ");
        return $stmt->execute([$ticketId, $userId, $message, $isInternal]);
    }
    
    public function updateTicketStatus($id, $status, $assignedTo = null) {
        $sql = "UPDATE support_tickets SET status = ?";
        $params = [$status];
        
        if ($assignedTo !== null) {
            $sql .= ", assigned_to = ?";
            $params[] = $assignedTo;
        }
        
        if ($status === 'resolved' || $status === 'closed') {
            $sql .= ", resolved_at = NOW()";
        }
        
        $sql .= " WHERE id = ?";
        $params[] = $id;
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }
    
    public function getSupportStats() {
        $stmt = $this->db->prepare("
            SELECT 
                status,
                COUNT(*) as count
            FROM support_tickets 
            GROUP BY status
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
?>
