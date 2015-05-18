-- Copyright 2015 Todd Knarr
-- Licensed under the terms of the GPL v3.0

-- Create schema and users

CREATE DATABASE email;

CREATE USER 'mailadmin'@'127.0.0.1' IDENTIFIED BY 'changeme';
REVOKE ALL PRIVILEGES, GRANT OPTION FROM 'mailadmin'@'127.0.0.1';
GRANT SELECT, INSERT, UPDATE, DELETE, EXECUTE on email.* TO 'mailadmin'@'127.0.0.1';

CREATE USER 'mailuser'@'127.0.0.1' IDENTIFIED BY 'changeme';
REVOKE ALL PRIVILEGES, GRANT OPTION FROM 'mailuser'@'127.0.0.1';
GRANT SELECT, EXECUTE on email.* TO 'mailuser'@'127.0.0.1';

USE email;

-- Create database tables and stored procedures

START TRANSACTION;

DROP FUNCTION IF EXISTS getVirtualAlias;
DROP TABLE IF EXISTS virtual_aliases;
DROP TABLE IF EXISTS virtual_users;
DROP TABLE IF EXISTS virtual_domains;

CREATE TABLE virtual_domains (
        name    VARCHAR(50) NOT NULL PRIMARY KEY
);

CREATE TABLE virtual_users (
        username        VARCHAR(50) NOT NULL PRIMARY KEY,
        password        VARCHAR(200) NOT NULL,
        change_attempts INT NOT NULL DEFAULT 0,
        acct_type       CHAR(1) NOT NULL
);

CREATE TABLE virtual_aliases (
        address_user    VARCHAR(50) NOT NULL,
        address_domain  VARCHAR(50) NOT NULL,
        recipient       VARCHAR(50) NOT NULL,
        PRIMARY KEY ( address_user, address_domain )
);

DELIMITER //
CREATE FUNCTION getVirtualAlias ( user VARCHAR(50), domain VARCHAR(100) )
        RETURNS VARCHAR(50)
        DETERMINISTIC
        READS SQL DATA
        SQL SECURITY INVOKER
BEGIN
        DECLARE r VARCHAR(50);
        DECLARE d VARCHAR(50);

        DECLARE CONTINUE HANDLER FOR SQLSTATE '02000' SET @garbage = 1;
        
        SELECT name INTO d FROM virtual_domains WHERE name = domain;
        IF d IS NULL
        THEN
                RETURN r;
        END IF;

        SELECT recipient INTO r FROM virtual_aliases WHERE address_user = user AND address_domain = domain;
        IF r IS NULL
        THEN
                SELECT recipient INTO r FROM virtual_aliases WHERE address_user = user AND address_domain = '*';
        END IF;
        IF r IS NULL
        THEN
                SELECT recipient INTO r FROM virtual_aliases WHERE address_user = '*' AND address_domain = domain;
        END IF;
        IF r IS NULL
        THEN
                SELECT recipient INTO r FROM virtual_aliases WHERE address_user = '*' AND address_domain = '*';
        END IF;

        RETURN r;
END
//
DELIMITER ;

COMMIT;

-- Populate database with initial data

START TRANSACTION;

INSERT INTO virtual_domains ( name ) VALUES
        ( 'example.com' );

INSERT INTO virtual_aliases ( address_user, address_domain, recipient ) VALUES
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

INSERT INTO virtual_users ( username, password, acct_type ) VALUES
        ( 'root', ENCRYPT( 'changeme', CONCAT( '$6$', SUBSTRING( SHA( RAND() ), -16 ) ) ), 'S' );

COMMIT;