-- ==========================================================================
-- 007 — Esquema inicial del MVP de sorteos
-- Motor: MySQL/MariaDB · InnoDB · utf8mb4
-- Requiere: users y settings (app/database/schema.sql)
-- ==========================================================================

SET NAMES utf8mb4;

-- Catálogo de premios -------------------------------------------------------

CREATE TABLE IF NOT EXISTS prizes (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    public_code VARCHAR(20) NOT NULL,
    name VARCHAR(160) NOT NULL,
    description TEXT NULL,
    prize_type VARCHAR(30) NOT NULL DEFAULT 'product',
    estimated_value_cents BIGINT UNSIGNED NULL,
    image_path VARCHAR(255) NULL,
    delivery_method VARCHAR(60) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY prizes_public_code_unique (public_code),
    KEY prizes_active_idx (is_active),
    KEY prizes_type_idx (prize_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sorteos -------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS raffles (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    public_code VARCHAR(20) NOT NULL,
    slug VARCHAR(120) NOT NULL,
    name VARCHAR(160) NOT NULL,
    summary VARCHAR(255) NULL,
    description MEDIUMTEXT NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'draft',
    price_cents BIGINT UNSIGNED NOT NULL DEFAULT 0,
    currency CHAR(3) NOT NULL DEFAULT 'PEN',
    total_tickets INT UNSIGNED NOT NULL,
    max_tickets_per_participant INT UNSIGNED NOT NULL DEFAULT 1,
    min_age TINYINT UNSIGNED NOT NULL DEFAULT 18,
    sales_start_at DATETIME NULL,
    sales_end_at DATETIME NULL,
    draw_at DATETIME NULL,
    claim_deadline_at DATETIME NULL,
    current_rules_version_id INT UNSIGNED NULL,
    is_featured TINYINT(1) NOT NULL DEFAULT 0,
    featured_order TINYINT UNSIGNED NULL,
    cover_image_path VARCHAR(255) NULL,
    created_by INT UNSIGNED NULL,
    published_at DATETIME NULL,
    closed_at DATETIME NULL,
    drawn_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY raffles_public_code_unique (public_code),
    UNIQUE KEY raffles_slug_unique (slug),
    KEY raffles_status_idx (status),
    KEY raffles_sales_start_idx (sales_start_at),
    KEY raffles_sales_end_idx (sales_end_at),
    KEY raffles_featured_idx (is_featured, featured_order),
    KEY raffles_created_by_idx (created_by),
    CONSTRAINT fk_raffles_created_by
        FOREIGN KEY (created_by) REFERENCES users (id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bases versionadas ---------------------------------------------------------

CREATE TABLE IF NOT EXISTS raffle_rules_versions (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    raffle_id INT UNSIGNED NOT NULL,
    version INT UNSIGNED NOT NULL,
    content MEDIUMTEXT NOT NULL,
    content_hash CHAR(64) NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'draft',
    published_at DATETIME NULL,
    created_by INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY raffle_rules_version_unique (raffle_id, version),
    KEY raffle_rules_status_idx (status),
    KEY raffle_rules_created_by_idx (created_by),
    CONSTRAINT fk_raffle_rules_raffle
        FOREIGN KEY (raffle_id) REFERENCES raffles (id)
        ON DELETE RESTRICT,
    CONSTRAINT fk_raffle_rules_created_by
        FOREIGN KEY (created_by) REFERENCES users (id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE raffles
    ADD CONSTRAINT fk_raffles_current_rules
    FOREIGN KEY (current_rules_version_id) REFERENCES raffle_rules_versions (id)
    ON DELETE SET NULL;

-- Premios asignados al sorteo ----------------------------------------------

CREATE TABLE IF NOT EXISTS raffle_prizes (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    raffle_id INT UNSIGNED NOT NULL,
    prize_id INT UNSIGNED NOT NULL,
    position TINYINT UNSIGNED NOT NULL DEFAULT 1,
    conditions TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY raffle_prizes_position_unique (raffle_id, position),
    KEY raffle_prizes_prize_idx (prize_id),
    CONSTRAINT fk_raffle_prizes_raffle
        FOREIGN KEY (raffle_id) REFERENCES raffles (id)
        ON DELETE RESTRICT,
    CONSTRAINT fk_raffle_prizes_prize
        FOREIGN KEY (prize_id) REFERENCES prizes (id)
        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Métodos de pago -----------------------------------------------------------

CREATE TABLE IF NOT EXISTS payment_methods (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(120) NOT NULL,
    type VARCHAR(20) NOT NULL,
    provider VARCHAR(40) NOT NULL,
    holder_name VARCHAR(160) NOT NULL,
    holder_document VARCHAR(20) NULL,
    phone VARCHAR(20) NULL,
    bank_name VARCHAR(80) NULL,
    account_number VARCHAR(60) NULL,
    cci VARCHAR(40) NULL,
    qr_image_path VARCHAR(255) NULL,
    contact_phone VARCHAR(20) NULL,
    instructions TEXT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY payment_methods_type_idx (type),
    KEY payment_methods_provider_idx (provider),
    KEY payment_methods_active_idx (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS raffle_payment_methods (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    raffle_id INT UNSIGNED NOT NULL,
    payment_method_id INT UNSIGNED NOT NULL,
    instructions_override TEXT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    sort_order TINYINT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    UNIQUE KEY raffle_payment_methods_unique (raffle_id, payment_method_id),
    KEY raffle_payment_methods_method_idx (payment_method_id),
    CONSTRAINT fk_raffle_payment_methods_raffle
        FOREIGN KEY (raffle_id) REFERENCES raffles (id)
        ON DELETE RESTRICT,
    CONSTRAINT fk_raffle_payment_methods_method
        FOREIGN KEY (payment_method_id) REFERENCES payment_methods (id)
        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Participantes -------------------------------------------------------------

CREATE TABLE IF NOT EXISTS participants (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    public_code VARCHAR(20) NOT NULL,
    full_name VARCHAR(160) NOT NULL,
    document_type VARCHAR(10) NOT NULL DEFAULT 'DNI',
    document_number_hash CHAR(64) NOT NULL,
    document_number_encrypted VARBINARY(255) NULL,
    phone VARCHAR(20) NOT NULL,
    email VARCHAR(160) NOT NULL,
    is_adult_declared TINYINT(1) NOT NULL DEFAULT 0,
    status VARCHAR(20) NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY participants_public_code_unique (public_code),
    UNIQUE KEY participants_document_hash_unique (document_number_hash),
    KEY participants_email_idx (email),
    KEY participants_phone_idx (phone),
    KEY participants_status_idx (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Órdenes -------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS orders (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    public_code VARCHAR(20) NOT NULL,
    access_code_hash CHAR(64) NOT NULL,
    raffle_id INT UNSIGNED NOT NULL,
    participant_id INT UNSIGNED NOT NULL,
    quantity INT UNSIGNED NOT NULL,
    unit_price_cents BIGINT UNSIGNED NOT NULL,
    total_amount_cents BIGINT UNSIGNED NOT NULL,
    currency CHAR(3) NOT NULL DEFAULT 'PEN',
    status VARCHAR(30) NOT NULL DEFAULT 'pending_payment',
    expires_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY orders_public_code_unique (public_code),
    KEY orders_raffle_idx (raffle_id),
    KEY orders_participant_idx (participant_id),
    KEY orders_status_idx (status),
    KEY orders_expires_idx (expires_at),
    CONSTRAINT fk_orders_raffle
        FOREIGN KEY (raffle_id) REFERENCES raffles (id)
        ON DELETE RESTRICT,
    CONSTRAINT fk_orders_participant
        FOREIGN KEY (participant_id) REFERENCES participants (id)
        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tickets -------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS tickets (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    public_code VARCHAR(24) NOT NULL,
    raffle_id INT UNSIGNED NOT NULL,
    number INT UNSIGNED NOT NULL,
    participant_id INT UNSIGNED NOT NULL,
    order_id INT UNSIGNED NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'paid',
    issued_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY tickets_public_code_unique (public_code),
    UNIQUE KEY tickets_raffle_number_unique (raffle_id, number),
    KEY tickets_participant_idx (participant_id),
    KEY tickets_order_idx (order_id),
    KEY tickets_status_idx (status),
    CONSTRAINT fk_tickets_raffle
        FOREIGN KEY (raffle_id) REFERENCES raffles (id)
        ON DELETE RESTRICT,
    CONSTRAINT fk_tickets_participant
        FOREIGN KEY (participant_id) REFERENCES participants (id)
        ON DELETE RESTRICT,
    CONSTRAINT fk_tickets_order
        FOREIGN KEY (order_id) REFERENCES orders (id)
        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Números del sorteo --------------------------------------------------------

CREATE TABLE IF NOT EXISTS raffle_numbers (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    raffle_id INT UNSIGNED NOT NULL,
    number INT UNSIGNED NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'available',
    order_id INT UNSIGNED NULL,
    reserved_until DATETIME NULL,
    ticket_id INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY raffle_numbers_raffle_number_unique (raffle_id, number),
    KEY raffle_numbers_status_idx (status),
    KEY raffle_numbers_order_idx (order_id),
    KEY raffle_numbers_reserved_idx (reserved_until),
    KEY raffle_numbers_ticket_idx (ticket_id),
    CONSTRAINT fk_raffle_numbers_raffle
        FOREIGN KEY (raffle_id) REFERENCES raffles (id)
        ON DELETE RESTRICT,
    CONSTRAINT fk_raffle_numbers_order
        FOREIGN KEY (order_id) REFERENCES orders (id)
        ON DELETE SET NULL,
    CONSTRAINT fk_raffle_numbers_ticket
        FOREIGN KEY (ticket_id) REFERENCES tickets (id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Comprobantes y pagos ------------------------------------------------------

CREATE TABLE IF NOT EXISTS payment_proofs (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    order_id INT UNSIGNED NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    file_hash CHAR(64) NOT NULL,
    mime_type VARCHAR(60) NOT NULL,
    file_size INT UNSIGNED NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'received',
    uploaded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    reviewed_by INT UNSIGNED NULL,
    reviewed_at DATETIME NULL,
    review_notes TEXT NULL,
    PRIMARY KEY (id),
    KEY payment_proofs_order_idx (order_id),
    KEY payment_proofs_hash_idx (file_hash),
    KEY payment_proofs_status_idx (status),
    CONSTRAINT fk_payment_proofs_order
        FOREIGN KEY (order_id) REFERENCES orders (id)
        ON DELETE RESTRICT,
    CONSTRAINT fk_payment_proofs_reviewer
        FOREIGN KEY (reviewed_by) REFERENCES users (id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS payments (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    order_id INT UNSIGNED NOT NULL,
    payment_method_id INT UNSIGNED NOT NULL,
    amount_cents BIGINT UNSIGNED NOT NULL,
    currency CHAR(3) NOT NULL DEFAULT 'PEN',
    status VARCHAR(20) NOT NULL DEFAULT 'pending',
    approved_by INT UNSIGNED NULL,
    approved_at DATETIME NULL,
    reference VARCHAR(120) NULL,
    notes TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY payments_order_idx (order_id),
    KEY payments_method_idx (payment_method_id),
    KEY payments_status_idx (status),
    CONSTRAINT fk_payments_order
        FOREIGN KEY (order_id) REFERENCES orders (id)
        ON DELETE RESTRICT,
    CONSTRAINT fk_payments_method
        FOREIGN KEY (payment_method_id) REFERENCES payment_methods (id)
        ON DELETE RESTRICT,
    CONSTRAINT fk_payments_approved_by
        FOREIGN KEY (approved_by) REFERENCES users (id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sorteo ejecutado ----------------------------------------------------------

CREATE TABLE IF NOT EXISTS draws (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    raffle_id INT UNSIGNED NOT NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'scheduled',
    scheduled_at DATETIME NULL,
    executed_at DATETIME NULL,
    executed_by INT UNSIGNED NULL,
    algorithm_version VARCHAR(20) NOT NULL DEFAULT 'commit-reveal-v1',
    snapshot_hash CHAR(64) NULL,
    seed_hash CHAR(64) NULL,
    seed_encrypted VARBINARY(255) NULL,
    seed_revealed_at DATETIME NULL,
    total_entries INT UNSIGNED NULL,
    acta_path VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY draws_raffle_idx (raffle_id),
    KEY draws_status_idx (status),
    CONSTRAINT fk_draws_raffle
        FOREIGN KEY (raffle_id) REFERENCES raffles (id)
        ON DELETE RESTRICT,
    CONSTRAINT fk_draws_executed_by
        FOREIGN KEY (executed_by) REFERENCES users (id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS draw_results (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    draw_id INT UNSIGNED NOT NULL,
    position TINYINT UNSIGNED NOT NULL,
    result_type VARCHAR(20) NOT NULL DEFAULT 'winner',
    ticket_id INT UNSIGNED NOT NULL,
    participant_id INT UNSIGNED NOT NULL,
    published_at DATETIME NULL,
    PRIMARY KEY (id),
    UNIQUE KEY draw_results_position_unique (draw_id, position),
    KEY draw_results_ticket_idx (ticket_id),
    KEY draw_results_participant_idx (participant_id),
    CONSTRAINT fk_draw_results_draw
        FOREIGN KEY (draw_id) REFERENCES draws (id)
        ON DELETE RESTRICT,
    CONSTRAINT fk_draw_results_ticket
        FOREIGN KEY (ticket_id) REFERENCES tickets (id)
        ON DELETE RESTRICT,
    CONSTRAINT fk_draw_results_participant
        FOREIGN KEY (participant_id) REFERENCES participants (id)
        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Entrega de premios --------------------------------------------------------

CREATE TABLE IF NOT EXISTS deliveries (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    raffle_id INT UNSIGNED NOT NULL,
    draw_id INT UNSIGNED NOT NULL,
    draw_result_id INT UNSIGNED NOT NULL,
    prize_id INT UNSIGNED NOT NULL,
    participant_id INT UNSIGNED NOT NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'pending_contact',
    contacted_at DATETIME NULL,
    identity_verified_at DATETIME NULL,
    scheduled_at DATETIME NULL,
    delivered_at DATETIME NULL,
    delivered_by INT UNSIGNED NULL,
    notes TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY deliveries_result_prize_unique (draw_result_id, prize_id),
    KEY deliveries_raffle_idx (raffle_id),
    KEY deliveries_draw_idx (draw_id),
    KEY deliveries_participant_idx (participant_id),
    KEY deliveries_status_idx (status),
    CONSTRAINT fk_deliveries_raffle
        FOREIGN KEY (raffle_id) REFERENCES raffles (id)
        ON DELETE RESTRICT,
    CONSTRAINT fk_deliveries_draw
        FOREIGN KEY (draw_id) REFERENCES draws (id)
        ON DELETE RESTRICT,
    CONSTRAINT fk_deliveries_result
        FOREIGN KEY (draw_result_id) REFERENCES draw_results (id)
        ON DELETE RESTRICT,
    CONSTRAINT fk_deliveries_prize
        FOREIGN KEY (prize_id) REFERENCES prizes (id)
        ON DELETE RESTRICT,
    CONSTRAINT fk_deliveries_participant
        FOREIGN KEY (participant_id) REFERENCES participants (id)
        ON DELETE RESTRICT,
    CONSTRAINT fk_deliveries_delivered_by
        FOREIGN KEY (delivered_by) REFERENCES users (id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS delivery_evidence (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    delivery_id INT UNSIGNED NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    file_hash CHAR(64) NOT NULL,
    mime_type VARCHAR(60) NOT NULL,
    evidence_type VARCHAR(30) NOT NULL,
    uploaded_by INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY delivery_evidence_delivery_idx (delivery_id),
    KEY delivery_evidence_hash_idx (file_hash),
    CONSTRAINT fk_delivery_evidence_delivery
        FOREIGN KEY (delivery_id) REFERENCES deliveries (id)
        ON DELETE RESTRICT,
    CONSTRAINT fk_delivery_evidence_uploaded_by
        FOREIGN KEY (uploaded_by) REFERENCES users (id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Aceptaciones legales ------------------------------------------------------

CREATE TABLE IF NOT EXISTS legal_acceptances (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    participant_id INT UNSIGNED NOT NULL,
    order_id INT UNSIGNED NULL,
    document_type VARCHAR(20) NOT NULL,
    document_version VARCHAR(20) NOT NULL,
    document_hash CHAR(64) NOT NULL,
    accepted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ip VARCHAR(45) NULL,
    user_agent VARCHAR(255) NULL,
    PRIMARY KEY (id),
    KEY legal_acceptances_participant_idx (participant_id),
    KEY legal_acceptances_order_idx (order_id),
    KEY legal_acceptances_type_idx (document_type),
    CONSTRAINT fk_legal_acceptances_participant
        FOREIGN KEY (participant_id) REFERENCES participants (id)
        ON DELETE RESTRICT,
    CONSTRAINT fk_legal_acceptances_order
        FOREIGN KEY (order_id) REFERENCES orders (id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Auditoría y notificaciones -----------------------------------------------

CREATE TABLE IF NOT EXISTS audit_logs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    actor_type VARCHAR(20) NOT NULL,
    actor_id INT UNSIGNED NULL,
    action VARCHAR(60) NOT NULL,
    entity_type VARCHAR(60) NOT NULL,
    entity_id BIGINT UNSIGNED NULL,
    before_json JSON NULL,
    after_json JSON NULL,
    ip VARCHAR(45) NULL,
    user_agent VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    previous_hash CHAR(64) NULL,
    hash CHAR(64) NULL,
    PRIMARY KEY (id),
    KEY audit_logs_created_idx (created_at),
    KEY audit_logs_action_idx (action),
    KEY audit_logs_entity_idx (entity_type, entity_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS notification_logs (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    order_id INT UNSIGNED NULL,
    participant_id INT UNSIGNED NULL,
    channel VARCHAR(20) NOT NULL,
    template_key VARCHAR(60) NOT NULL,
    status VARCHAR(20) NOT NULL,
    sent_by INT UNSIGNED NULL,
    sent_at DATETIME NULL,
    notes TEXT NULL,
    PRIMARY KEY (id),
    KEY notification_logs_order_idx (order_id),
    KEY notification_logs_participant_idx (participant_id),
    KEY notification_logs_channel_idx (channel),
    KEY notification_logs_status_idx (status),
    CONSTRAINT fk_notification_logs_order
        FOREIGN KEY (order_id) REFERENCES orders (id)
        ON DELETE SET NULL,
    CONSTRAINT fk_notification_logs_participant
        FOREIGN KEY (participant_id) REFERENCES participants (id)
        ON DELETE SET NULL,
    CONSTRAINT fk_notification_logs_sent_by
        FOREIGN KEY (sent_by) REFERENCES users (id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
