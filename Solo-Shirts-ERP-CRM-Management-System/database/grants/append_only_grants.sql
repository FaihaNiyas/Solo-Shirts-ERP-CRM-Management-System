-- Production / staging DB grant policy (Phase 18).
--
-- Append-only tables are enforced two ways: BEFORE UPDATE/DELETE triggers (applied
-- in every environment via migrations) AND these grants (applied ONLY in staging
-- and production by the deploy script). The grants are intentionally NOT applied in
-- local/CI, because the test framework must be able to reset the database.
--
-- Two database identities:
--   * solo_app   — the application runtime user (no DDL; restricted on ledgers).
--   * solo_owner — the migration/DDL user (full rights), used only during deploys.
--
-- Replace 'app_host' with the real host/% as appropriate for your network.

-- ---------------------------------------------------------------------------
-- Application runtime user: full CRUD on most tables…
-- ---------------------------------------------------------------------------
GRANT SELECT, INSERT, UPDATE, DELETE ON solo_shirts_erp.* TO 'solo_app'@'app_host';

-- …but SELECT/INSERT only on the append-only ledgers (no UPDATE/DELETE).
REVOKE UPDATE, DELETE ON solo_shirts_erp.activity_log           FROM 'solo_app'@'app_host';
REVOKE UPDATE, DELETE ON solo_shirts_erp.fabric_movements       FROM 'solo_app'@'app_host';
REVOKE UPDATE, DELETE ON solo_shirts_erp.production_transitions FROM 'solo_app'@'app_host';
REVOKE UPDATE, DELETE ON solo_shirts_erp.payments               FROM 'solo_app'@'app_host';
REVOKE UPDATE, DELETE ON solo_shirts_erp.qc_inspections         FROM 'solo_app'@'app_host';
REVOKE UPDATE, DELETE ON solo_shirts_erp.delivery_attempts      FROM 'solo_app'@'app_host';
REVOKE UPDATE, DELETE ON solo_shirts_erp.credit_notes           FROM 'solo_app'@'app_host';

-- ---------------------------------------------------------------------------
-- Owner / migration user: full DDL + DML. Used only to run migrations on deploy.
-- ---------------------------------------------------------------------------
GRANT ALL PRIVILEGES ON solo_shirts_erp.* TO 'solo_owner'@'app_host';

FLUSH PRIVILEGES;
