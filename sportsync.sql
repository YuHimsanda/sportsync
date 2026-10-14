-- SportSync database schema
-- MariaDB / MySQL 8+
-- Import this file in phpMyAdmin or run:
-- mysql -u root -p < sportsync.sql

CREATE DATABASE IF NOT EXISTS sportsync_db
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE sportsync_db;

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS player_stats;
DROP TABLE IF EXISTS messages;
DROP TABLE IF EXISTS inter_academy_event_requests;
DROP TABLE IF EXISTS approvals;
DROP TABLE IF EXISTS team_join_requests;
DROP TABLE IF EXISTS event_teams;
DROP TABLE IF EXISTS events;
DROP TABLE IF EXISTS team_players;
DROP TABLE IF EXISTS teams;
DROP TABLE IF EXISTS venues;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS user_sports;
DROP TABLE IF EXISTS academy_sports;
DROP TABLE IF EXISTS sports;
DROP TABLE IF EXISTS academies;

SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE academies (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(150) NOT NULL,
    address VARCHAR(255) NULL,
    logo_path VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_academies_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE sports (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_sports_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE academy_sports (
    academy_id BIGINT UNSIGNED NOT NULL,
    sport_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (academy_id, sport_id),
    CONSTRAINT fk_academy_sports_academy
        FOREIGN KEY (academy_id) REFERENCES academies(id) ON DELETE CASCADE,
    CONSTRAINT fk_academy_sports_sport
        FOREIGN KEY (sport_id) REFERENCES sports(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE users (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    academy_id BIGINT UNSIGNED NULL,
    name VARCHAR(150) NOT NULL,
    first_name VARCHAR(80) NULL,
    last_name VARCHAR(80) NULL,
    birth_date DATE NULL,
    phone VARCHAR(30) NULL,
    email VARCHAR(255) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin','coach','player','athlete','referee') NOT NULL DEFAULT 'athlete',
    profile_status ENUM('pending','active','rejected') NOT NULL DEFAULT 'pending',
    profile_photo VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_email (email),
    KEY ix_users_academy_role_status (academy_id, role, profile_status),
    CONSTRAINT fk_users_academy
        FOREIGN KEY (academy_id) REFERENCES academies(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE user_sports (
    user_id BIGINT UNSIGNED NOT NULL,
    sport_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, sport_id),
    CONSTRAINT fk_user_sports_user
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_user_sports_sport
        FOREIGN KEY (sport_id) REFERENCES sports(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE venues (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    academy_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(150) NOT NULL,
    location VARCHAR(255) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_venues_academy_name (academy_id, name),
    CONSTRAINT fk_venues_academy
        FOREIGN KEY (academy_id) REFERENCES academies(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE teams (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    academy_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(150) NOT NULL,
    sport_id BIGINT UNSIGNED NOT NULL,
    coach_id BIGINT UNSIGNED NULL,
    captain_id BIGINT UNSIGNED NULL,
    vice_captain_id BIGINT UNSIGNED NULL,
    status ENUM('pending','active','rejected') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_teams_academy_name (academy_id, name),
    KEY ix_teams_academy_sport_status (academy_id, sport_id, status),
    CONSTRAINT fk_teams_academy
        FOREIGN KEY (academy_id) REFERENCES academies(id) ON DELETE CASCADE,
    CONSTRAINT fk_teams_sport
        FOREIGN KEY (sport_id) REFERENCES sports(id),
    CONSTRAINT fk_teams_coach
        FOREIGN KEY (coach_id) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_teams_captain
        FOREIGN KEY (captain_id) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_teams_vice_captain
        FOREIGN KEY (vice_captain_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE team_players (
    team_id BIGINT UNSIGNED NOT NULL,
    player_id BIGINT UNSIGNED NOT NULL,
    sport_role VARCHAR(80) NULL,
    joined_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (team_id, player_id),
    KEY ix_team_players_player (player_id),
    CONSTRAINT fk_team_players_team
        FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE,
    CONSTRAINT fk_team_players_player
        FOREIGN KEY (player_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE events (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    academy_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(200) NOT NULL,
    event_type ENUM('official','friendly','practice') NOT NULL DEFAULT 'official',
    sport_id BIGINT UNSIGNED NOT NULL,
    venue_id BIGINT UNSIGNED NOT NULL,
    opponent_team_id BIGINT UNSIGNED NULL,
    start_datetime DATETIME NOT NULL,
    end_datetime DATETIME NOT NULL,
    status ENUM('scheduled','ongoing','completed','cancelled') NOT NULL DEFAULT 'scheduled',
    home_score INT NULL,
    away_score INT NULL,
    created_by BIGINT UNSIGNED NOT NULL,
    referee_id BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY ix_events_schedule (venue_id, start_datetime, end_datetime),
    KEY ix_events_academy_start (academy_id, start_datetime),
    CONSTRAINT fk_events_academy
        FOREIGN KEY (academy_id) REFERENCES academies(id) ON DELETE CASCADE,
    CONSTRAINT fk_events_sport
        FOREIGN KEY (sport_id) REFERENCES sports(id),
    CONSTRAINT fk_events_venue
        FOREIGN KEY (venue_id) REFERENCES venues(id),
    CONSTRAINT fk_events_opponent_team
        FOREIGN KEY (opponent_team_id) REFERENCES teams(id) ON DELETE SET NULL,
    CONSTRAINT fk_events_creator
        FOREIGN KEY (created_by) REFERENCES users(id),
    CONSTRAINT fk_events_referee
        FOREIGN KEY (referee_id) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT chk_events_time CHECK (end_datetime > start_datetime)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE event_teams (
    event_id BIGINT UNSIGNED NOT NULL,
    team_id BIGINT UNSIGNED NOT NULL,
    team_side ENUM('home','away') NULL,
    PRIMARY KEY (event_id, team_id),
    KEY ix_event_teams_team (team_id),
    CONSTRAINT fk_event_teams_event
        FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    CONSTRAINT fk_event_teams_team
        FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE approvals (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    academy_id BIGINT UNSIGNED NOT NULL,
    type ENUM('user_registration','team_creation','team_join_request','match_creation','result_submission') NOT NULL,
    reference_id BIGINT UNSIGNED NOT NULL,
    requested_by BIGINT UNSIGNED NOT NULL,
    status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    reviewed_by BIGINT UNSIGNED NULL,
    reviewed_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY ix_approvals_reference (type, reference_id, status),
    CONSTRAINT fk_approvals_academy
        FOREIGN KEY (academy_id) REFERENCES academies(id) ON DELETE CASCADE,
    CONSTRAINT fk_approvals_requester
        FOREIGN KEY (requested_by) REFERENCES users(id),
    CONSTRAINT fk_approvals_reviewer
        FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE team_join_requests (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    academy_id BIGINT UNSIGNED NOT NULL,
    team_id BIGINT UNSIGNED NOT NULL,
    player_id BIGINT UNSIGNED NOT NULL,
    status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    reviewed_by BIGINT UNSIGNED NULL,
    reviewed_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_team_player_join_request (team_id, player_id),
    CONSTRAINT fk_join_requests_academy
        FOREIGN KEY (academy_id) REFERENCES academies(id) ON DELETE CASCADE,
    CONSTRAINT fk_join_requests_team
        FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE,
    CONSTRAINT fk_join_requests_player
        FOREIGN KEY (player_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_join_requests_reviewer
        FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE inter_academy_event_requests (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    event_id BIGINT UNSIGNED NOT NULL,
    invited_academy_id BIGINT UNSIGNED NOT NULL,
    invited_team_id BIGINT UNSIGNED NOT NULL,
    status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    reviewed_by BIGINT UNSIGNED NULL,
    reviewed_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_inter_academy_event_team (event_id, invited_team_id),
    CONSTRAINT fk_inter_event_request_event
        FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    CONSTRAINT fk_inter_event_request_academy
        FOREIGN KEY (invited_academy_id) REFERENCES academies(id) ON DELETE CASCADE,
    CONSTRAINT fk_inter_event_request_team
        FOREIGN KEY (invited_team_id) REFERENCES teams(id) ON DELETE CASCADE,
    CONSTRAINT fk_inter_event_request_reviewer
        FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE messages (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    academy_id BIGINT UNSIGNED NOT NULL,
    sender_id BIGINT UNSIGNED NOT NULL,
    recipient_id BIGINT UNSIGNED NULL,
    subject VARCHAR(200) NOT NULL,
    body TEXT NOT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY ix_messages_academy_created (academy_id, created_at),
    CONSTRAINT fk_messages_academy
        FOREIGN KEY (academy_id) REFERENCES academies(id) ON DELETE CASCADE,
    CONSTRAINT fk_messages_sender
        FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE
    ,
    CONSTRAINT fk_messages_recipient
        FOREIGN KEY (recipient_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE player_stats (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    event_id BIGINT UNSIGNED NOT NULL,
    player_id BIGINT UNSIGNED NOT NULL,
    score_or_runs DECIMAL(12,2) NULL,
    stats JSON NULL,
    performance_desc TEXT NULL,
    submitted_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_player_event_stats (event_id, player_id),
    CONSTRAINT fk_player_stats_event
        FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    CONSTRAINT fk_player_stats_player
        FOREIGN KEY (player_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_player_stats_submitter
        FOREIGN KEY (submitted_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO sports (name) VALUES
    ('Cricket'),
    ('Football'),
    ('Basketball'),
    ('Hockey'),
    ('Rugby');
