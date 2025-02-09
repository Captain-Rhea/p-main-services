<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateBlogTables extends AbstractMigration
{
    public function change(): void
    {
        // ✅ 1. blog_articles
        $this->table('blog_articles', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'uuid', ['null' => false])
            ->addColumn('title_th', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('title_en', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('slug', 'string', ['limit' => 255])
            ->addColumn('content_th', 'json', ['null' => true, 'comment' => 'Block-based content'])
            ->addColumn('content_en', 'json', ['null' => true, 'comment' => 'Block-based content'])
            ->addColumn('summary_th', 'text', ['null' => true])
            ->addColumn('summary_en', 'text', ['null' => true])
            ->addColumn('cover_image', 'json', ['null' => true])
            ->addColumn('status', 'enum', ['values' => ['draft', 'published', 'archived'], 'default' => 'draft'])
            ->addColumn('published_by', 'integer', ['null' => true])
            ->addColumn('published_at', 'timestamp', ['null' => true])
            ->addColumn('locked_by', 'integer', ['null' => true])
            ->addColumn('locked_at', 'timestamp', ['null' => true])
            ->addColumn('created_by', 'integer')
            ->addColumn('updated_by', 'integer', ['null' => true])
            ->addColumn('deleted_by', 'integer', ['null' => true])
            ->addColumn('deleted_at', 'timestamp', ['null' => true])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
            ->addIndex(['slug'], ['unique' => true])
            ->addIndex(['title_th', 'title_en', 'summary_th', 'summary_en'], ['type' => 'fulltext'])
            ->addIndex(['created_at'])
            ->addIndex(['updated_at'])
            ->create();

        // ✅ 2. blog_categories
        $this->table('blog_categories', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'uuid', ['null' => false])
            ->addColumn('name_th', 'string', ['limit' => 100])
            ->addColumn('name_en', 'string', ['limit' => 100])
            ->addColumn('slug', 'string', ['limit' => 100])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
            ->addIndex(['name_th'], ['unique' => true])
            ->addIndex(['name_en'], ['unique' => true])
            ->addIndex(['slug'], ['unique' => true])
            ->create();

        // ✅ 3. blog_article_categories (Many-to-Many)
        $this->table('blog_article_categories', ['id' => false, 'primary_key' => ['article_id', 'category_id']])
            ->addColumn('article_id', 'uuid', ['null' => false])
            ->addColumn('category_id', 'uuid', ['null' => false])
            ->addForeignKey('article_id', 'blog_articles', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
            ->addForeignKey('category_id', 'blog_categories', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addIndex(['article_id'])
            ->addIndex(['category_id'])
            ->create();

        // ✅ 4. blog_tags
        $this->table('blog_tags', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'uuid', ['null' => false])
            ->addColumn('name_th', 'string', ['limit' => 100])
            ->addColumn('name_en', 'string', ['limit' => 100])
            ->addColumn('slug', 'string', ['limit' => 100])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
            ->addIndex(['name_th'], ['unique' => true])
            ->addIndex(['name_en'], ['unique' => true])
            ->addIndex(['slug'], ['unique' => true])
            ->addIndex(['created_at'])
            ->addIndex(['updated_at'])
            ->create();

        // ✅ 5. blog_article_tags (Many-to-Many)
        $this->table('blog_article_tags', ['id' => false, 'primary_key' => ['article_id', 'tag_id']])
            ->addColumn('article_id', 'uuid', ['null' => false])
            ->addColumn('tag_id', 'uuid', ['null' => false])
            ->addForeignKey('article_id', 'blog_articles', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
            ->addForeignKey('tag_id', 'blog_tags', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
            ->addIndex(['article_id'])
            ->addIndex(['tag_id'])
            ->create();

        // ✅ 6. blog_activity_logs
        $this->table('blog_activity_logs', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'uuid', ['null' => false])
            ->addColumn('user_id', 'integer', ['null' => false])
            ->addColumn('article_id', 'uuid', ['null' => true])
            ->addColumn('action', 'enum', ['values' => ['created', 'updated', 'deleted', 'published', 'permanently_deleted']])
            ->addColumn('details', 'json', ['null' => true])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addForeignKey('article_id', 'blog_articles', 'id', ['delete' => 'SET NULL', 'update' => 'CASCADE'])
            ->addIndex(['user_id'])
            ->addIndex(['article_id'])
            ->addIndex(['created_at'])
            ->create();

        // ✅ 8. blog_authors (เก็บข้อมูลผู้เขียน)
        $this->table('blog_authors', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'uuid', ['null' => false])
            ->addColumn('name_th', 'string', ['limit' => 255])
            ->addColumn('name_en', 'string', ['limit' => 255])
            ->addColumn('profile_image', 'json', ['null' => true])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
            ->addIndex(['name_th'], ['unique' => true])
            ->addIndex(['name_en'], ['unique' => true])
            ->addIndex(['created_at'])
            ->addIndex(['updated_at'])
            ->create();

        // ✅ 9. blog_article_authors (เชื่อม article กับ author)
        $this->table('blog_article_authors', ['id' => false, 'primary_key' => ['article_id', 'author_id']])
            ->addColumn('article_id', 'uuid', ['null' => false])
            ->addColumn('author_id', 'uuid', ['null' => false])
            ->addForeignKey('article_id', 'blog_articles', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
            ->addForeignKey('author_id', 'blog_authors', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addIndex(['article_id'])
            ->addIndex(['author_id'])
            ->addIndex(['created_at'])
            ->create();

        // ✅ GENERATED COLUMN
        $this->execute("
        ALTER TABLE blog_articles 
        ADD COLUMN content_text_th TEXT GENERATED ALWAYS AS 
        (JSON_UNQUOTE(JSON_EXTRACT(content_th, '$[*].content'))) STORED;
    ");

        $this->execute("
        ALTER TABLE blog_articles 
        ADD COLUMN content_text_en TEXT GENERATED ALWAYS AS 
        (JSON_UNQUOTE(JSON_EXTRACT(content_en, '$[*].content'))) STORED;
    ");
    }
}
