<?php

namespace Tnt\Giftcard\Revisions;

use Oak\Contracts\Migration\RevisionInterface;
use Tnt\Dbi\TableBuilder;

class CreateGiftcardTable extends DatabaseRevision implements RevisionInterface
{
    public function up()
    {
        $this->queryBuilder->table('ecommerce_giftcard')->create(function(TableBuilder $table) {
            $table->addColumn('id', 'int')->length(11)->primaryKey();
            $table->addColumn('created', 'int')->length(11);
            $table->addColumn('updated', 'int')->length(11);
            $table->addColumn('value', 'decimal')->length('10,2');
            $table->addColumn('current_value', 'decimal')->length('10,2');
            $table->addColumn('from', 'varchar')->length(255);
            $table->addColumn('to', 'varchar')->length(255);
            $table->addColumn('custom_message', 'text');
            $table->addColumn('status', 'int')->length(11);
            $table->addColumn('paper_copy', 'tinyint')->length(1);
            $table->addColumn('expiry_date', 'int')->length(11);
            $table->addColumn('file', 'int')->length(11)->null();

            $table->addForeignKey('file', 'dry_media_file');
        });

        $this->execute();
    }

    public function down()
    {
        $this->queryBuilder->table('ecommerce_giftcard')->drop();
        $this->execute();
    }

    public function describeUp(): string
    {
        return 'Table ecommerce_giftcard created';
    }

    public function describeDown(): string
    {
        return 'Table ecommerce_giftcard dropped';
    }
}