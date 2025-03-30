-- SQLiDetect Database Schema
-- Based on the thesis documentation

-- Users table for admin access
CREATE TABLE USERS (
    id INT NOT NULL AUTO_INCREMENT,
    first_name VARCHAR(15),
    last_name VARCHAR(15),
    user_name VARCHAR(15),
    pass_word VARCHAR(40),
    email VARCHAR(40),
    last_login TIMESTAMP,
    PRIMARY KEY (id)
);

-- Configuration settings types
CREATE TABLE CONFIG_SETTINGS (
    id INT NOT NULL AUTO_INCREMENT,
    type_name VARCHAR(30),
    PRIMARY KEY (id)
);

-- Setting types (time, count, etc)
CREATE TABLE SETTING_TYPES (
    id INT NOT NULL AUTO_INCREMENT,
    setting_type_name VARCHAR(50),
    PRIMARY KEY (id)
);

-- Actual configuration values
CREATE TABLE SETTING_VALUES (
    id INT NOT NULL AUTO_INCREMENT,
    settings_value INT,
    settings_type INT,
    config_type INT,
    PRIMARY KEY (id),
    FOREIGN KEY (config_type) REFERENCES CONFIG_SETTINGS(id),
    FOREIGN KEY (settings_type) REFERENCES SETTING_TYPES(id)
);

-- Known injection types
CREATE TABLE INJECTIONS (
    id INT NOT NULL AUTO_INCREMENT,
    injection_name VARCHAR(30),
    PRIMARY KEY (id)
);

-- Blacklisted IPs
CREATE TABLE BLACK_LIST (
    id INT NOT NULL AUTO_INCREMENT,
    ip VARCHAR(30),
    last_attack_time TIMESTAMP,
    block_status INT,
    blk_count INT,
    reset_cnt INT,
    PRIMARY KEY (id),
    FOREIGN KEY (block_status) REFERENCES CONFIG_SETTINGS(id)
);

-- Attack records
CREATE TABLE ATTACK (
    id INT NOT NULL AUTO_INCREMENT,
    black_list_id INT,
    injection_id INT,
    attack_query TEXT,
    PRIMARY KEY (id),
    FOREIGN KEY (black_list_id) REFERENCES BLACK_LIST(id),
    FOREIGN KEY (injection_id) REFERENCES INJECTIONS(id)
);

-- Insert default configuration settings
INSERT INTO CONFIG_SETTINGS VALUES 
(1,'neverblocked'),
(2,'temporary'),
(3,'reset'),
(4,'permanent'),
(5,'autoblocked');

-- Insert setting types
INSERT INTO SETTING_TYPES VALUES 
(1,'time'),
(2,'number');

-- Insert default injection types
INSERT INTO INJECTIONS (injection_name) VALUES 
('comment'),
('and-or'),
('union'),
('multiple queries'),
('String concatination'),
('ASCII'),
('multi line comments');

-- Insert default configuration values
INSERT INTO SETTING_VALUES VALUES 
(1,3,2,2),  -- Temporary block after 3 attempts
(2,480,1,2), -- Reset after 480 minutes (8 hours)
(3,2,2,3);   -- Permanent block after 2 resets