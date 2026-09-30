-- =====================================================================
-- LUNATIC MOBILE SECURITY LAB — Database schema
-- Simulation-only training platform. ALL telecom identifiers are
-- synthetic (is_simulated = 1). No real IMSI/IMEI/subscriber data ever.
-- Engine: InnoDB / utf8mb4
-- =====================================================================

SET FOREIGN_KEY_CHECKS = 0;
SET NAMES utf8mb4;

-- ---------------------------------------------------------------------
-- RBAC: roles & users
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS roles (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name        VARCHAR(50)  NOT NULL,
    label       VARCHAR(100) NOT NULL,
    permissions JSON         NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_roles_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS users (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    username      VARCHAR(80)  NOT NULL,
    email         VARCHAR(190) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role_id       INT UNSIGNED NOT NULL,
    is_active     TINYINT(1)   NOT NULL DEFAULT 1,
    last_login_at DATETIME     NULL,
    created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_username (username),
    UNIQUE KEY uq_users_email (email),
    KEY idx_users_role (role_id),
    CONSTRAINT fk_users_role FOREIGN KEY (role_id) REFERENCES roles (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- Virtual telecom infrastructure
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS operators (
    id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    code         VARCHAR(40)  NOT NULL,           -- e.g. OPERATOR-LAB-01
    name         VARCHAR(120) NOT NULL,
    mcc          CHAR(3)      NOT NULL,           -- fictional MCC
    mnc          CHAR(3)      NOT NULL,           -- fictional MNC
    country      VARCHAR(80)  NOT NULL DEFAULT 'Labland',
    is_simulated TINYINT(1)   NOT NULL DEFAULT 1,
    created_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_operators_code (code),
    KEY idx_operators_plmn (mcc, mnc)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS bts (
    id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    code         VARCHAR(40)  NOT NULL,           -- BTS-001
    operator_id  INT UNSIGNED NOT NULL,
    lat          DECIMAL(9,6) NOT NULL,           -- artificial coordinates
    lng          DECIMAL(9,6) NOT NULL,
    coverage_m   INT UNSIGNED NOT NULL DEFAULT 1500,
    is_legitimate TINYINT(1)  NOT NULL DEFAULT 1, -- 0 = simulated rogue
    is_simulated TINYINT(1)   NOT NULL DEFAULT 1,
    created_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_bts_code (code),
    KEY idx_bts_operator (operator_id),
    CONSTRAINT fk_bts_operator FOREIGN KEY (operator_id) REFERENCES operators (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS cells (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    code          VARCHAR(40)  NOT NULL,          -- CELL-001 / CELL-ROGUE-001
    bts_id        INT UNSIGNED NOT NULL,
    operator_id   INT UNSIGNED NOT NULL,
    cell_id_num   INT UNSIGNED NOT NULL,          -- fictional Cell ID
    lac           INT UNSIGNED NOT NULL,          -- fictional LAC/TAC
    technology    ENUM('2G','3G','4G','5G') NOT NULL DEFAULT '4G',
    frequency_mhz DECIMAL(8,2) NOT NULL,          -- fictional frequency
    power_dbm     INT          NOT NULL DEFAULT -70, -- simulated tx power
    is_legitimate TINYINT(1)   NOT NULL DEFAULT 1, -- 0 = rogue cell
    is_rogue      TINYINT(1)   NOT NULL DEFAULT 0,
    is_simulated  TINYINT(1)   NOT NULL DEFAULT 1,
    created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_cells_code (code),
    KEY idx_cells_bts (bts_id),
    KEY idx_cells_operator (operator_id),
    KEY idx_cells_tech (technology),
    KEY idx_cells_rogue (is_rogue),
    CONSTRAINT fk_cells_bts FOREIGN KEY (bts_id) REFERENCES bts (id),
    CONSTRAINT fk_cells_operator FOREIGN KEY (operator_id) REFERENCES operators (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- Virtual subscribers: SIMs & devices (all fictional)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS sims (
    id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    code           VARCHAR(40)  NOT NULL,         -- SIM-0001
    operator_id    INT UNSIGNED NOT NULL,
    imsi_fake      VARCHAR(20)  NOT NULL,         -- FICTIONAL IMSI (marked)
    ki_placeholder VARCHAR(40)  NOT NULL DEFAULT 'SIMULATED-NO-KEY',
    is_simulated   TINYINT(1)   NOT NULL DEFAULT 1,
    created_at     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_sims_code (code),
    UNIQUE KEY uq_sims_imsi (imsi_fake),
    KEY idx_sims_operator (operator_id),
    CONSTRAINT fk_sims_operator FOREIGN KEY (operator_id) REFERENCES operators (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS devices (
    id               INT UNSIGNED NOT NULL AUTO_INCREMENT,
    code             VARCHAR(40)  NOT NULL,       -- DEVICE-0001
    sim_id           INT UNSIGNED NULL,
    imei_fake        VARCHAR(20)  NOT NULL,       -- FICTIONAL IMEI (marked)
    model            VARCHAR(80)  NOT NULL DEFAULT 'VirtualPhone',
    current_cell_id  INT UNSIGNED NULL,
    signal_dbm       INT          NOT NULL DEFAULT -75,
    connection_state ENUM('idle','connected','searching','denied') NOT NULL DEFAULT 'idle',
    lat              DECIMAL(9,6) NULL,           -- artificial position
    lng              DECIMAL(9,6) NULL,
    is_simulated     TINYINT(1)   NOT NULL DEFAULT 1,
    created_at       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_devices_code (code),
    UNIQUE KEY uq_devices_imei (imei_fake),
    KEY idx_devices_sim (sim_id),
    KEY idx_devices_cell (current_cell_id),
    CONSTRAINT fk_devices_sim FOREIGN KEY (sim_id) REFERENCES sims (id) ON DELETE SET NULL,
    CONSTRAINT fk_devices_cell FOREIGN KEY (current_cell_id) REFERENCES cells (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Simulated mobility history for each virtual device
CREATE TABLE IF NOT EXISTS device_mobility (
    id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    device_id  INT UNSIGNED NOT NULL,
    lat        DECIMAL(9,6) NOT NULL,
    lng        DECIMAL(9,6) NOT NULL,
    cell_id    INT UNSIGNED NULL,
    recorded_at DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_mobility_device (device_id, recorded_at),
    CONSTRAINT fk_mobility_device FOREIGN KEY (device_id) REFERENCES devices (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- Simulated network activity
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS connection_events (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    device_id   INT UNSIGNED NOT NULL,
    cell_id     INT UNSIGNED NULL,
    sim_id      INT UNSIGNED NULL,
    event_type  ENUM('attach','detach','handover','location_update',
                     'identity_request','downgrade','paging','reject') NOT NULL,
    technology  ENUM('2G','3G','4G','5G') NULL,
    signal_dbm  INT NULL,
    risk_level  ENUM('LOW','MEDIUM','HIGH','CRITICAL') NOT NULL DEFAULT 'LOW',
    detail      VARCHAR(255) NULL,
    is_simulated TINYINT(1)  NOT NULL DEFAULT 1,
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_events_device (device_id),
    KEY idx_events_cell (cell_id),
    KEY idx_events_type (event_type),
    KEY idx_events_risk (risk_level),
    KEY idx_events_time (created_at),
    CONSTRAINT fk_events_device FOREIGN KEY (device_id) REFERENCES devices (id) ON DELETE CASCADE,
    CONSTRAINT fk_events_cell FOREIGN KEY (cell_id) REFERENCES cells (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS handovers (
    id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    device_id    INT UNSIGNED NOT NULL,
    from_cell_id INT UNSIGNED NULL,
    to_cell_id   INT UNSIGNED NULL,
    reason       VARCHAR(120) NULL,
    is_simulated TINYINT(1)   NOT NULL DEFAULT 1,
    created_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_ho_device (device_id, created_at),
    CONSTRAINT fk_ho_device FOREIGN KEY (device_id) REFERENCES devices (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- Detection: anomalies & alerts
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS anomalies (
    id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    event_id     BIGINT UNSIGNED NULL,
    device_id    INT UNSIGNED NULL,
    cell_id      INT UNSIGNED NULL,
    anomaly_type VARCHAR(60)  NOT NULL,          -- unknown_cell, downgrade, ...
    risk_level   ENUM('LOW','MEDIUM','HIGH','CRITICAL') NOT NULL,
    score        INT UNSIGNED NOT NULL DEFAULT 0,
    reason       VARCHAR(255) NOT NULL,
    is_simulated TINYINT(1)   NOT NULL DEFAULT 1,
    created_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_anom_type (anomaly_type),
    KEY idx_anom_risk (risk_level),
    KEY idx_anom_cell (cell_id),
    KEY idx_anom_time (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS alerts (
    id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    anomaly_id   BIGINT UNSIGNED NULL,
    device_id    INT UNSIGNED NULL,
    cell_id      INT UNSIGNED NULL,
    event_type   VARCHAR(60)  NOT NULL,          -- ROGUE_CELL_DETECTED, ...
    severity     ENUM('LOW','MEDIUM','HIGH','CRITICAL') NOT NULL,
    status       ENUM('open','investigating','contained','closed') NOT NULL DEFAULT 'open',
    message      VARCHAR(255) NOT NULL,
    soc_exported TINYINT(1)   NOT NULL DEFAULT 0,
    simulation   TINYINT(1)   NOT NULL DEFAULT 1, -- mandatory marker
    created_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_alerts_status (status),
    KEY idx_alerts_sev (severity),
    KEY idx_alerts_time (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- Scenarios & replay steps
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS scenarios (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    code        VARCHAR(40)  NOT NULL,           -- SCENARIO-ROGUE-CELL
    name        VARCHAR(120) NOT NULL,
    category    VARCHAR(60)  NOT NULL,           -- rogue_cell, downgrade, ...
    description TEXT         NULL,
    difficulty  ENUM('beginner','intermediate','advanced') NOT NULL DEFAULT 'beginner',
    max_score   INT UNSIGNED NOT NULL DEFAULT 100,
    is_simulated TINYINT(1)  NOT NULL DEFAULT 1,
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_scenarios_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS scenario_steps (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    scenario_id INT UNSIGNED NOT NULL,
    step_order  INT UNSIGNED NOT NULL,
    title       VARCHAR(120) NOT NULL,
    description TEXT         NULL,
    step_type   VARCHAR(60)  NOT NULL,           -- device, legit_cell, rogue_cell...
    payload     JSON         NULL,
    PRIMARY KEY (id),
    KEY idx_steps_scenario (scenario_id, step_order),
    CONSTRAINT fk_steps_scenario FOREIGN KEY (scenario_id) REFERENCES scenarios (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- Training: quizzes & scoring
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS lab_scores (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id     INT UNSIGNED NOT NULL,
    scenario_id INT UNSIGNED NULL,
    points      INT NOT NULL DEFAULT 0,
    max_points  INT NOT NULL DEFAULT 100,
    category    VARCHAR(60) NOT NULL DEFAULT 'general',
    detail      VARCHAR(255) NULL,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_scores_user (user_id),
    CONSTRAINT fk_scores_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS quiz_results (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id     INT UNSIGNED NOT NULL,
    module      VARCHAR(60)  NOT NULL,           -- imsi, imei, ss7 ...
    score       INT NOT NULL DEFAULT 0,
    total       INT NOT NULL DEFAULT 0,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_quiz_user (user_id, module),
    CONSTRAINT fk_quiz_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- Audit log (never stores real telecom identifiers)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS audit_logs (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id     INT UNSIGNED NULL,
    username    VARCHAR(80)  NULL,
    action      VARCHAR(120) NOT NULL,
    scenario    VARCHAR(120) NULL,
    ip_address  VARCHAR(45)  NULL,
    result      VARCHAR(60)  NULL,
    metadata    JSON         NULL,
    simulation  TINYINT(1)   NOT NULL DEFAULT 1,
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_audit_user (user_id),
    KEY idx_audit_action (action),
    KEY idx_audit_time (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Rate limiting store
CREATE TABLE IF NOT EXISTS rate_limits (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    bucket      VARCHAR(190) NOT NULL,
    hits        INT UNSIGNED NOT NULL DEFAULT 1,
    window_start INT UNSIGNED NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_rate_bucket (bucket)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;
