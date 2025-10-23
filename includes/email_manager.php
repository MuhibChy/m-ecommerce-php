<?php
/**
 * Email Manager Class
 * M-EcommerceCRM - Email Management System
 * Requires PHPMailer: composer require phpmailer/phpmailer
 */

// Try to load PHPMailer classes if available
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

// Import PHPMailer classes at top level
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class EmailManager {
    private $db;
    private $crmConfig;
    
    public function __construct() {
        $this->db = getDB();
        $this->crmConfig = new CRMConfig();
    }
    
    /**
     * Send single email
     */
    public function sendEmail($data) {
        try {
            // Check if PHPMailer is available
            if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
                throw new Exception('PHPMailer is not installed. Please run: composer require phpmailer/phpmailer');
            }
            
            // Get email domain configuration
            $domainId = $data['email_domain_id'] ?? null;
            if (!$domainId) {
                $domain = $this->crmConfig->getDefaultEmailDomain();
                $domainId = $domain['id'];
            } else {
                $domain = $this->crmConfig->getEmailDomainCredentials($domainId);
            }
            
            if (!$domain) {
                throw new Exception('No email domain configured');
            }
            
            // Check daily limit
            $limitCheck = $this->crmConfig->checkDailyLimit($domainId);
            if (!$limitCheck['can_send']) {
                throw new Exception('Daily email limit reached for this domain');
            }
            
            // Create tracking ID
            $trackingId = $this->generateTrackingId();
            
            // Log email attempt
            $logId = $this->logEmail([
                'campaign_id' => $data['campaign_id'] ?? null,
                'customer_id' => $data['customer_id'] ?? null,
                'email_domain_id' => $domainId,
                'recipient_email' => $data['to_email'],
                'recipient_name' => $data['to_name'] ?? null,
                'subject' => $data['subject'],
                'body_html' => $data['body_html'] ?? null,
                'body_text' => $data['body_text'] ?? null,
                'tracking_id' => $trackingId,
                'status' => 'pending'
            ]);
            
            // Setup PHPMailer
            $mail = new PHPMailer(true);
            
            // Server settings
            $mail->isSMTP();
            $mail->Host = $domain['smtp_host'];
            $mail->SMTPAuth = true;
            $mail->Username = $domain['smtp_username'];
            $mail->Password = $domain['smtp_password'];
            $mail->SMTPSecure = $domain['smtp_encryption'] === 'ssl' ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = $domain['smtp_port'];
            
            // Recipients
            $mail->setFrom($domain['from_email'], $domain['from_name']);
            $mail->addAddress($data['to_email'], $data['to_name'] ?? '');
            
            if (!empty($domain['reply_to_email'])) {
                $mail->addReplyTo($domain['reply_to_email'], $domain['from_name']);
            }
            
            // Content
            $mail->isHTML(true);
            $mail->Subject = $data['subject'];
            
            // Add tracking pixel and unsubscribe link to HTML body
            if (!empty($data['body_html'])) {
                $htmlBody = $data['body_html'];
                
                // Add tracking pixel
                $trackingPixel = "<img src='" . $this->getBaseUrl() . "/crm/track.php?t=" . $trackingId . "' width='1' height='1' style='display:none;' />";
                $htmlBody .= $trackingPixel;
                
                // Add unsubscribe link
                $unsubscribeLink = $this->getBaseUrl() . "/crm/unsubscribe.php?t=" . $trackingId;
                $htmlBody .= "<br><br><small><a href='" . $unsubscribeLink . "'>Unsubscribe</a></small>";
                
                $mail->Body = $htmlBody;
            }
            
            if (!empty($data['body_text'])) {
                $mail->AltBody = $data['body_text'];
            }
            
            // Add attachments if any
            if (!empty($data['attachments'])) {
                foreach ($data['attachments'] as $attachment) {
                    $mail->addAttachment($attachment['path'], $attachment['name'] ?? '');
                }
            }
            
            // Send email
            $mail->send();
            
            // Update log as sent
            $this->updateEmailLog($logId, [
                'status' => 'sent',
                'sent_at' => date('Y-m-d H:i:s')
            ]);
            
            // Log customer activity if customer_id provided
            if (!empty($data['customer_id'])) {
                $customerManager = new CustomerManager();
                $customerManager->logActivity(
                    $data['customer_id'], 
                    'email_sent', 
                    'Email Sent: ' . $data['subject'],
                    'Email sent to ' . $data['to_email']
                );
            }
            
            return [
                'success' => true,
                'tracking_id' => $trackingId,
                'log_id' => $logId
            ];
            
        } catch (Exception $e) {
            // Update log as failed
            if (isset($logId)) {
                $this->updateEmailLog($logId, [
                    'status' => 'failed',
                    'error_message' => $e->getMessage()
                ]);
            }
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Send bulk emails (campaign)
     */
    public function sendCampaign($campaignId) {
        try {
            // Get campaign details
            $campaign = $this->getCampaign($campaignId);
            if (!$campaign) {
                throw new Exception('Campaign not found');
            }
            
            // Get recipients
            $recipients = $this->getCampaignRecipients($campaignId);
            if (empty($recipients)) {
                throw new Exception('No recipients found for campaign');
            }
            
            $sent = 0;
            $failed = 0;
            
            foreach ($recipients as $recipient) {
                // Replace template variables
                $subject = $this->replaceTemplateVariables($campaign['subject'], $recipient);
                $bodyHtml = $this->replaceTemplateVariables($campaign['body_html'], $recipient);
                $bodyText = $this->replaceTemplateVariables($campaign['body_text'], $recipient);
                
                $emailData = [
                    'campaign_id' => $campaignId,
                    'customer_id' => $recipient['customer_id'],
                    'email_domain_id' => $campaign['email_domain_id'],
                    'to_email' => $recipient['email'],
                    'to_name' => $recipient['first_name'] . ' ' . $recipient['last_name'],
                    'subject' => $subject,
                    'body_html' => $bodyHtml,
                    'body_text' => $bodyText
                ];
                
                $result = $this->sendEmail($emailData);
                
                if ($result['success']) {
                    $sent++;
                    // Update recipient status
                    $this->updateCampaignRecipient($campaignId, $recipient['customer_id'], 'sent');
                } else {
                    $failed++;
                    $this->updateCampaignRecipient($campaignId, $recipient['customer_id'], 'failed');
                }
                
                // Small delay to avoid overwhelming SMTP server
                usleep(100000); // 0.1 second
            }
            
            // Update campaign statistics
            $this->updateCampaignStats($campaignId, $sent, $failed);
            
            return [
                'success' => true,
                'sent' => $sent,
                'failed' => $failed,
                'total' => count($recipients)
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Receive emails using IMAP
     */
    public function receiveEmails($domainId) {
        try {
            $domain = $this->crmConfig->getEmailDomainCredentials($domainId);
            if (!$domain || empty($domain['imap_host'])) {
                throw new Exception('IMAP not configured for this domain');
            }
            
            // Connect to IMAP server
            $imapString = '{' . $domain['imap_host'] . ':' . $domain['imap_port'] . '/imap';
            if ($domain['imap_encryption'] === 'ssl') {
                $imapString .= '/ssl';
            }
            $imapString .= '}INBOX';
            
            $inbox = imap_open($imapString, $domain['imap_username'], $domain['imap_password']);
            
            if (!$inbox) {
                throw new Exception('Cannot connect to IMAP server: ' . imap_last_error());
            }
            
            // Get unread emails
            $emails = imap_search($inbox, 'UNSEEN');
            $newEmails = 0;
            
            if ($emails) {
                foreach ($emails as $emailNumber) {
                    $header = imap_headerinfo($inbox, $emailNumber);
                    $body = imap_fetchbody($inbox, $emailNumber, 1);
                    
                    // Check if email already exists
                    $messageId = $header->message_id;
                    if (!$this->emailExists($messageId)) {
                        // Save email to database
                        $this->saveReceivedEmail([
                            'email_domain_id' => $domainId,
                            'message_id' => $messageId,
                            'from_email' => $header->from[0]->mailbox . '@' . $header->from[0]->host,
                            'from_name' => $header->from[0]->personal ?? null,
                            'to_email' => $domain['from_email'],
                            'subject' => $header->subject,
                            'body_text' => $body,
                            'received_at' => date('Y-m-d H:i:s', $header->udate)
                        ]);
                        
                        $newEmails++;
                    }
                }
            }
            
            imap_close($inbox);
            
            return [
                'success' => true,
                'new_emails' => $newEmails
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get email templates
     */
    public function getEmailTemplates($activeOnly = true) {
        $sql = "SELECT * FROM email_templates";
        if ($activeOnly) {
            $sql .= " WHERE is_active = 1";
        }
        $sql .= " ORDER BY name ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    /**
     * Save email template
     */
    public function saveEmailTemplate($data) {
        if (!empty($data['id'])) {
            // Update existing template
            $sql = "UPDATE email_templates SET name = ?, subject = ?, body_html = ?, body_text = ?, 
                    template_type = ?, variables = ?, is_active = ? WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                $data['name'], $data['subject'], $data['body_html'], $data['body_text'],
                $data['template_type'], json_encode($data['variables'] ?? []), 
                $data['is_active'] ?? 1, $data['id']
            ]);
        } else {
            // Create new template
            $sql = "INSERT INTO email_templates (name, subject, body_html, body_text, template_type, 
                    variables, is_active, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                $data['name'], $data['subject'], $data['body_html'], $data['body_text'],
                $data['template_type'], json_encode($data['variables'] ?? []), 
                $data['is_active'] ?? 1, $_SESSION['user_id'] ?? 1
            ]);
        }
    }
    
    /**
     * Track email open
     */
    public function trackOpen($trackingId) {
        $sql = "UPDATE email_logs SET status = 'opened', opened_at = NOW(), 
                ip_address = ?, user_agent = ? WHERE tracking_id = ? AND opened_at IS NULL";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null,
            $trackingId
        ]);
    }
    
    /**
     * Track email click
     */
    public function trackClick($trackingId) {
        $sql = "UPDATE email_logs SET status = 'clicked', clicked_at = NOW(), 
                ip_address = ?, user_agent = ? WHERE tracking_id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null,
            $trackingId
        ]);
    }
    
    /**
     * Handle unsubscribe
     */
    public function unsubscribe($trackingId, $reason = null) {
        // Get email log
        $sql = "SELECT * FROM email_logs WHERE tracking_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$trackingId]);
        $emailLog = $stmt->fetch();
        
        if ($emailLog) {
            // Add to unsubscribe list
            $sql = "INSERT INTO email_unsubscribes (email, customer_id, campaign_id, reason, ip_address) 
                    VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE reason = ?, unsubscribed_at = NOW()";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $emailLog['recipient_email'],
                $emailLog['customer_id'],
                $emailLog['campaign_id'],
                $reason,
                $_SERVER['REMOTE_ADDR'] ?? null,
                $reason
            ]);
            
            return true;
        }
        
        return false;
    }
    
    // Helper methods
    private function generateTrackingId() {
        return uniqid('track_', true);
    }
    
    private function getBaseUrl() {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $path = dirname($_SERVER['SCRIPT_NAME'] ?? '');
        return $protocol . '://' . $host . $path;
    }
    
    private function logEmail($data) {
        $sql = "INSERT INTO email_logs (campaign_id, customer_id, email_domain_id, recipient_email, 
                recipient_name, subject, body_html, body_text, status, tracking_id) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $data['campaign_id'], $data['customer_id'], $data['email_domain_id'],
            $data['recipient_email'], $data['recipient_name'], $data['subject'],
            $data['body_html'], $data['body_text'], $data['status'], $data['tracking_id']
        ]);
        return $this->db->lastInsertId();
    }
    
    private function updateEmailLog($logId, $data) {
        $setParts = [];
        $params = [];
        
        foreach ($data as $key => $value) {
            $setParts[] = "$key = ?";
            $params[] = $value;
        }
        
        $params[] = $logId;
        $sql = "UPDATE email_logs SET " . implode(', ', $setParts) . " WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }
    
    private function replaceTemplateVariables($content, $data) {
        foreach ($data as $key => $value) {
            $content = str_replace('{{' . $key . '}}', $value, $content);
        }
        return $content;
    }
    
    private function getCampaign($campaignId) {
        $sql = "SELECT * FROM email_campaigns WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$campaignId]);
        return $stmt->fetch();
    }
    
    private function getCampaignRecipients($campaignId) {
        $sql = "SELECT cr.*, c.first_name, c.last_name, c.email, c.company 
                FROM campaign_recipients cr 
                JOIN crm_customers c ON cr.customer_id = c.id 
                WHERE cr.campaign_id = ? AND cr.status = 'pending'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$campaignId]);
        return $stmt->fetchAll();
    }
    
    private function updateCampaignRecipient($campaignId, $customerId, $status) {
        $sql = "UPDATE campaign_recipients SET status = ?, sent_at = NOW() 
                WHERE campaign_id = ? AND customer_id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$status, $campaignId, $customerId]);
    }
    
    private function updateCampaignStats($campaignId, $sent, $failed) {
        $sql = "UPDATE email_campaigns SET total_sent = total_sent + ?, 
                status = CASE WHEN status = 'sending' THEN 'sent' ELSE status END 
                WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$sent, $campaignId]);
    }
    
    private function emailExists($messageId) {
        $sql = "SELECT COUNT(*) as count FROM received_emails WHERE message_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$messageId]);
        return $stmt->fetch()['count'] > 0;
    }
    
    private function saveReceivedEmail($data) {
        $sql = "INSERT INTO received_emails (email_domain_id, message_id, from_email, from_name, 
                to_email, subject, body_text, received_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $data['email_domain_id'], $data['message_id'], $data['from_email'],
            $data['from_name'], $data['to_email'], $data['subject'],
            $data['body_text'], $data['received_at']
        ]);
    }
}
?>
