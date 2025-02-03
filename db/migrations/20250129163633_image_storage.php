<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class ImageStorage extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('image_storage', ['id' => 'storage_id']);
        $table->addColumn('image_id', 'integer')
            ->addColumn('group', 'string', ['limit' => 100])
            ->addColumn('image_name', 'string', ['limit' => 255])
            ->addColumn('base_url', 'text')
            ->addColumn('base_size', 'integer', ['null' => true])
            ->addColumn('lazy_url', 'text')
            ->addColumn('lazy_size', 'integer', ['null' => true])
            ->addColumn('created_by', 'integer', ['null' => true])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_by', 'integer', ['null' => true])
            ->addColumn('updated_at', 'timestamp', ['null' => true, 'update' => 'CURRENT_TIMESTAMP'])
            ->create();
    }
}
