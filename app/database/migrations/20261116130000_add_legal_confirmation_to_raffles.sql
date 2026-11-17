-- ==========================================================================
-- 007.1 — Confirmación legal del sorteo
-- Permite registrar el checklist legal y quién lo confirmó antes de publicar.
-- ==========================================================================

SET NAMES utf8mb4;

ALTER TABLE raffles
    ADD COLUMN legal_checklist JSON NULL AFTER drawn_at,
    ADD COLUMN legal_confirmed_at DATETIME NULL AFTER legal_checklist,
    ADD COLUMN legal_confirmed_by INT UNSIGNED NULL AFTER legal_confirmed_at,
    ADD CONSTRAINT fk_raffles_legal_confirmed_by
        FOREIGN KEY (legal_confirmed_by) REFERENCES users (id)
        ON DELETE SET NULL;
