-- Schema WhatsApp Gateway untuk JIBAS
CREATE DATABASE IF NOT EXISTS jbswa CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE jbswa;

CREATE TABLE IF NOT EXISTS wa_queue (
    id                BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sms_history_id    INT UNSIGNED NOT NULL,
    destination       VARCHAR(30) NOT NULL,
    message           TEXT NOT NULL,
    sender_id         VARCHAR(100) DEFAULT NULL,
    idsmsgeninfo      INT UNSIGNED DEFAULT NULL,
    status            TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '0=pending,1=sent,2=retry,3=failed',
    attempts          TINYINT UNSIGNED NOT NULL DEFAULT 0,
    last_error        VARCHAR(255) DEFAULT NULL,
    last_response     TEXT,
    wa_message_id     VARCHAR(50) DEFAULT NULL,
    wa_message_status VARCHAR(20) DEFAULT NULL,
    created_at        DATETIME NOT NULL,
    updated_at        DATETIME DEFAULT NULL,
    sent_at           DATETIME DEFAULT NULL,
    next_retry_at     DATETIME DEFAULT NULL,
    KEY idx_status (status, next_retry_at),
    KEY idx_destination (destination),
    UNIQUE KEY uk_sms_history (sms_history_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS wa_sync_marker (
    id                      TINYINT UNSIGNED NOT NULL PRIMARY KEY,
    last_outboxhistory_id   INT UNSIGNED NOT NULL,
    updated_at              DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO wa_sync_marker (id, last_outboxhistory_id, updated_at)
VALUES (1, 0, NOW())
ON DUPLICATE KEY UPDATE updated_at = VALUES(updated_at);
