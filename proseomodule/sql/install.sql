/**
 * Pro SEO Module - SQL Installation
 *
 * @author      Pro SEO Team
 * @copyright   2024
 * @license     AFL-3.0
 */

-- Table for custom meta tags
CREATE TABLE IF NOT EXISTS `PREFIX_proseo_custom_meta` (
    `id_proseo_meta` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `id_entity` INT(11) UNSIGNED NOT NULL,
    `entity_type` VARCHAR(50) NOT NULL,
    `id_lang` INT(11) UNSIGNED NOT NULL,
    `id_shop` INT(11) UNSIGNED NOT NULL,
    `custom_title` VARCHAR(255) DEFAULT NULL,
    `custom_description` TEXT DEFAULT NULL,
    `custom_keywords` VARCHAR(255) DEFAULT NULL,
    `robots_index` TINYINT(1) DEFAULT 1,
    `robots_follow` TINYINT(1) DEFAULT 1,
    `canonical_url` VARCHAR(512) DEFAULT NULL,
    `og_title` VARCHAR(255) DEFAULT NULL,
    `og_description` TEXT DEFAULT NULL,
    `og_image` VARCHAR(512) DEFAULT NULL,
    `twitter_title` VARCHAR(255) DEFAULT NULL,
    `twitter_description` TEXT DEFAULT NULL,
    `twitter_image` VARCHAR(512) DEFAULT NULL,
    `date_add` DATETIME NOT NULL,
    `date_upd` DATETIME NOT NULL,
    PRIMARY KEY (`id_proseo_meta`),
    KEY `entity_idx` (`id_entity`, `entity_type`, `id_lang`, `id_shop`)
) ENGINE=ENGINE_TYPE DEFAULT CHARSET=utf8mb4;

-- Table for 301/302 redirects
CREATE TABLE IF NOT EXISTS `PREFIX_proseo_redirects` (
    `id_redirect` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `old_url` VARCHAR(512) NOT NULL,
    `new_url` VARCHAR(512) NOT NULL,
    `redirect_type` INT(3) DEFAULT 301,
    `hits` INT(11) DEFAULT 0,
    `active` TINYINT(1) DEFAULT 1,
    `date_add` DATETIME NOT NULL,
    PRIMARY KEY (`id_redirect`),
    UNIQUE KEY `old_url_idx` (`old_url`(255))
) ENGINE=ENGINE_TYPE DEFAULT CHARSET=utf8mb4;

-- Table for schema cache
CREATE TABLE IF NOT EXISTS `PREFIX_proseo_schema_cache` (
    `id_cache` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `cache_key` VARCHAR(255) NOT NULL,
    `schema_data` LONGTEXT NOT NULL,
    `date_add` DATETIME NOT NULL,
    `date_expiry` DATETIME NOT NULL,
    PRIMARY KEY (`id_cache`),
    UNIQUE KEY `cache_key_idx` (`cache_key`)
) ENGINE=ENGINE_TYPE DEFAULT CHARSET=utf8mb4;
