<?php
/**
 * CRM Configuration Class
 * M-EcommerceCRM - Complete CRM System
 */

class CRMConfig {
    private $db;
    
    public function __construct() {
        $this->db = getDB();
    }
    
    /**
     * Get all active email domains
     */
    public function getEmailDomains($activeOnly = true) {
        $sql = "SELECT * FROM email_domains";
        if ($activeOnly) {
            $sql .= " WHERE is_active = 1";
        }
        $sql .= " ORDER BY is_default DESC, domain_name ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    /**
     * Get default email domain
     */
    public function getDefaultEmailDomain() {
        $sql = "SELECT * FROM email_domains WHERE is_default = 1 AND is_active = 1 LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetch();
    }
    
    /**
     * Add new email domain
     */
    public function addEmailDomain($data) {
        // If this is set as default, unset others
        if ($data['is_default']) {
            $this->db->prepare("UPDATE email_domains SET is_default = 0")->execute();
        }
        
        // Encrypt passwords (simple base64 for demo - use proper encryption in production)
        $data['smtp_password'] = base64_encode($data['smtp_password']);
        if (!empty($data['imap_password'])) {
            $data['imap_password'] = base64_encode($data['imap_password']);
        }
        
        $sql = "INSERT INTO email_domains (domain_name, smtp_host, smtp_port, smtp_username, smtp_password, 
                smtp_encryption, imap_host, imap_port, imap_username, imap_password, imap_encryption, 
                from_name, from_email, reply_to_email, is_active, is_default, daily_limit) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $data['domain_name'], $data['smtp_host'], $data['smtp_port'], 
            $data['smtp_username'], $data['smtp_password'], $data['smtp_encryption'],
            $data['imap_host'] ?? null, $data['imap_port'] ?? 993, 
            $data['imap_username'] ?? null, $data['imap_password'] ?? null, 
            $data['imap_encryption'] ?? 'tls', $data['from_name'], $data['from_email'], 
            $data['reply_to_email'] ?? null, $data['is_active'] ?? 1, 
            $data['is_default'] ?? 0, $data['daily_limit'] ?? 500
        ]);
    }
    
    /**
     * Update email domain
     */
    public function updateEmailDomain($id, $data) {
        // If this is set as default, unset others
        if ($data['is_default']) {
            $this->db->prepare("UPDATE email_domains SET is_default = 0 WHERE id != ?")->execute([$id]);
        }
        
        // Encrypt passwords if provided
        if (!empty($data['smtp_password'])) {
            $data['smtp_password'] = base64_encode($data['smtp_password']);
        }
        if (!empty($data['imap_password'])) {
            $data['imap_password'] = base64_encode($data['imap_password']);
        }
        
        $sql = "UPDATE email_domains SET 
                domain_name = ?, smtp_host = ?, smtp_port = ?, smtp_username = ?, 
                smtp_encryption = ?, imap_host = ?, imap_port = ?, imap_username = ?, 
                imap_encryption = ?, from_name = ?, from_email = ?, reply_to_email = ?, 
                is_active = ?, is_default = ?, daily_limit = ?";
        
        $params = [
            $data['domain_name'], $data['smtp_host'], $data['smtp_port'], 
            $data['smtp_username'], $data['smtp_encryption'], $data['imap_host'], 
            $data['imap_port'], $data['imap_username'], $data['imap_encryption'], 
            $data['from_name'], $data['from_email'], $data['reply_to_email'], 
            $data['is_active'], $data['is_default'], $data['daily_limit']
        ];
        
        if (!empty($data['smtp_password'])) {
            $sql .= ", smtp_password = ?";
            $params[] = $data['smtp_password'];
        }
        
        if (!empty($data['imap_password'])) {
            $sql .= ", imap_password = ?";
            $params[] = $data['imap_password'];
        }
        
        $sql .= " WHERE id = ?";
        $params[] = $id;
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }
    
    /**
     * Delete email domain
     */
    public function deleteEmailDomain($id) {
        $stmt = $this->db->prepare("DELETE FROM email_domains WHERE id = ?");
        return $stmt->execute([$id]);
    }
    
    /**
     * Get decrypted email domain credentials
     */
    public function getEmailDomainCredentials($id) {
        $sql = "SELECT * FROM email_domains WHERE id = ? AND is_active = 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        $domain = $stmt->fetch();
        
        if ($domain) {
            // Decrypt passwords
            $domain['smtp_password'] = base64_decode($domain['smtp_password']);
            if (!empty($domain['imap_password'])) {
                $domain['imap_password'] = base64_decode($domain['imap_password']);
            }
        }
        
        return $domain;
    }
    
    /**
     * Check daily email limit
     */
    public function checkDailyLimit($domainId) {
        $sql = "SELECT COUNT(*) as sent_today FROM email_logs 
                WHERE email_domain_id = ? AND DATE(sent_at) = CURDATE() AND status = 'sent'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$domainId]);
        $result = $stmt->fetch();
        
        $domain = $this->getEmailDomainCredentials($domainId);
        
        return [
            'sent_today' => $result['sent_today'],
            'daily_limit' => $domain['daily_limit'],
            'can_send' => $result['sent_today'] < $domain['daily_limit']
        ];
    }
}
?>
