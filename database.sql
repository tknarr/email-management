-- Copyright 2015 Todd Knarr
-- Licensed under the terms of the GPL v3.0 or any later version

-- Create schema and users

CREATE DATABASE email;

CREATE USER 'mailadmin'@'localhost' IDENTIFIED BY 'changeme';
REVOKE ALL PRIVILEGES, GRANT OPTION FROM 'mailadmin'@'localhost';
GRANT SELECT, INSERT, UPDATE, DELETE, EXECUTE on email.* TO 'mailadmin'@'localhost';

CREATE USER 'mailuser'@'localhost' IDENTIFIED BY 'changeme';
REVOKE ALL PRIVILEGES, GRANT OPTION FROM 'mailuser'@'localhost';
GRANT SELECT, EXECUTE on email.* TO 'mailuser'@'localhost';

USE email;

-- Create database tables and stored procedures

START TRANSACTION;

DROP FUNCTION IF EXISTS resolveAddress;
DROP VIEW IF EXISTS v_passwd;
DROP TABLE IF EXISTS mail_routing;
DROP TABLE IF EXISTS mail_users;
DROP TABLE IF EXISTS hosted_domains;
DROP TABLE IF EXISTS acct_types;

CREATE TABLE acct_types (
        code            CHAR(1) NOT NULL PRIMARY KEY,
        description     VARCHAR(50),
        abbreviation    VARCHAR(10),
        home_root       VARCHAR(50) NOT NULL,
        uid             VARCHAR(20),
        gid             VARCHAR(20),
        transport       VARCHAR(100)
);

CREATE TABLE hosted_domains (
        name    VARCHAR(50) NOT NULL PRIMARY KEY
);

CREATE TABLE mail_users (
        username        VARCHAR(50) NOT NULL PRIMARY KEY,
        password        VARCHAR(200) NOT NULL,
        change_attempts INT NOT NULL DEFAULT 0,
        acct_type       CHAR(1) NOT NULL,
        FOREIGN KEY ( acct_type ) REFERENCES acct_types ( code )
);

CREATE TABLE mail_routing (
        address_user    VARCHAR(50) NOT NULL,
        address_domain  VARCHAR(50) NOT NULL,
        recipient       VARCHAR(50) NOT NULL,
        PRIMARY KEY ( address_user, address_domain )
);

CREATE SQL SECURITY INVOKER VIEW v_passwd AS
SELECT u.username AS username, u.password AS password, u.acct_type AS acct_type,
          IFNULL( a.uid, u.username ) AS uid, IFNULL( a.gid, u.username ) AS gid,
          CONCAT( a.home_root, u.username) AS home, a.transport AS transport
        FROM mail_users u, acct_types a
        WHERE u.acct_type = a.code;

DELIMITER //
CREATE FUNCTION resolveAddress ( user VARCHAR(50), domain VARCHAR(100) )
        RETURNS VARCHAR(50)
        DETERMINISTIC
        READS SQL DATA
        SQL SECURITY INVOKER
BEGIN
        DECLARE r VARCHAR(50);
        DECLARE d VARCHAR(50);

        DECLARE CONTINUE HANDLER FOR SQLSTATE '02000' SET @garbage = 1;
        
        SELECT name INTO d FROM hosted_domains WHERE name = domain;
        IF d IS NULL
        THEN
                RETURN r;
        END IF;

        SELECT recipient INTO r FROM mail_routing WHERE address_user = user AND address_domain = domain;
        IF r IS NULL
        THEN
                SELECT recipient INTO r FROM mail_routing WHERE address_user = user AND address_domain = '*';
        END IF;
        IF r IS NULL
        THEN
                SELECT recipient INTO r FROM mail_routing WHERE address_user = '*' AND address_domain = domain;
        END IF;
        IF r IS NULL
        THEN
                SELECT recipient INTO r FROM mail_routing WHERE address_user = '*' AND address_domain = '*';
        END IF;

        RETURN r;
END
//
DELIMITER ;

COMMIT;

-- Populate database with initial data

START TRANSACTION;

INSERT INTO acct_types ( code, description, abbreviation, home_root, uid, gid, transport ) VALUES
        ( 'R', 'Root', 'Root', '/', NULL, NULL, NULL ),
        ( 'S', 'System user', 'Sys', '/home/', NULL, NULL, NULL ),
        ( 'V', 'Virtual user', '', '/home/vmail/', 'vmail', 'vmail', 'lmtp:unix:private/dovecot-lmtp' );

INSERT INTO hosted_domains ( name ) VALUES
        ( 'example.com' );

INSERT INTO mail_routing ( address_user, address_domain, recipient ) VALUES
        ( 'root',          '*', 'root' ),
        ( 'admin',         '*', 'root'),
        ( 'administrator', '*', 'root' ),
        ( 'postmaster',    '*', 'postmaster' ),
        ( 'hostmaster',    '*', 'hostmaster' ),
        ( 'webmaster',     '*', 'webmaster' ),
        ( 'abuse',         '*', 'abuse' ),
        ( 'noc',           '*', 'noc' ),
        ( 'security',      '*', 'security' ),
        ( 'myusername',    'example.com', 'myusername' ),
        ( '*',             'example.com', 'myusername' );

INSERT INTO mail_users ( username, password, acct_type ) VALUES
        ( 'root', ENCRYPT( 'changeme', CONCAT( '$6$', SUBSTRING( SHA( RAND() ), -16 ) ) ), 'R' ),
        ( 'myusername', ENCRYPT( 'changeme', CONCAT( '$6$', SUBSTRING( SHA( RAND() ), -16 ) ) ), 'R' );

COMMIT;
