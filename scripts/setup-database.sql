-- =====================================================
-- Search Engine Database Setup Script
-- PostgreSQL 17
-- =====================================================

-- Note: Run this as postgres superuser first to create database and user:
-- sudo -u postgres psql -f scripts/setup-database.sql

-- Create database (uncomment when running as superuser)
-- DROP DATABASE IF EXISTS search_engine;
-- CREATE DATABASE search_engine
--     WITH 
--     ENCODING = 'UTF8'
--     LC_COLLATE = 'en_US.UTF-8'
--     LC_CTYPE = 'en_US.UTF-8'
--     TEMPLATE = template0;

-- Create user (uncomment when running as superuser)
-- DROP USER IF EXISTS search_user;
-- CREATE USER search_user WITH ENCRYPTED PASSWORD 'your_secure_password';
-- GRANT ALL PRIVILEGES ON DATABASE search_engine TO search_user;

-- Connect to the database
-- \c search_engine

-- Enable required extensions
CREATE EXTENSION IF NOT EXISTS pg_trgm;  -- Trigram similarity for fuzzy matching
CREATE EXTENSION IF NOT EXISTS unaccent; -- Remove accents for better search

-- Drop existing table if exists (careful in production!)
DROP TABLE IF EXISTS search_items CASCADE;

-- Main search items table
CREATE TABLE search_items (
    id BIGSERIAL PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    page_name VARCHAR(255) NOT NULL,
    page_fav_icon_path VARCHAR(255) NOT NULL,
    page_url VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Full-text search vectors (weighted) - PostgreSQL 17 generated columns
    title_tsv TSVECTOR GENERATED ALWAYS AS (
        to_tsvector('english', COALESCE(title, ''))
    ) STORED,
    
    description_tsv TSVECTOR GENERATED ALWAYS AS (
        to_tsvector('english', COALESCE(description, ''))
    ) STORED,
    
    combined_tsv TSVECTOR GENERATED ALWAYS AS (
        setweight(to_tsvector('english', COALESCE(title, '')), 'A') ||
        setweight(to_tsvector('english', COALESCE(description, '')), 'B')
    ) STORED
);

-- Create indexes for optimal search performance
-- GIN indexes for full-text search
CREATE INDEX idx_title_tsv ON search_items USING GIN(title_tsv);
CREATE INDEX idx_description_tsv ON search_items USING GIN(description_tsv);
CREATE INDEX idx_combined_tsv ON search_items USING GIN(combined_tsv);

-- Trigram index for fuzzy/partial matching
CREATE INDEX idx_title_trgm ON search_items USING GIN(title gin_trgm_ops);
CREATE INDEX idx_description_trgm ON search_items USING GIN(description gin_trgm_ops);

-- Index for sorting by date
CREATE INDEX idx_created_at ON search_items(created_at DESC);

-- Composite index for common search patterns
CREATE INDEX idx_title_description ON search_items 
    USING GIN(to_tsvector('english', title || ' ' || COALESCE(description, '')));

-- Grant permissions to search_user (uncomment when running as superuser)
-- GRANT ALL PRIVILEGES ON TABLE search_items TO search_user;
-- GRANT USAGE, SELECT ON SEQUENCE search_items_id_seq TO search_user;

-- Update statistics for query planner
ANALYZE search_items;

-- Display table information
\d search_items

-- Success message
SELECT 'Database setup completed successfully!' AS status;
SELECT 'Total tables created: ' || COUNT(*) AS info 
FROM information_schema.tables 
WHERE table_schema = 'public' AND table_name = 'search_items';
