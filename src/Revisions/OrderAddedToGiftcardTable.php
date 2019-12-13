<?php

namespace Tnt\Giftcard\Revisions;

use Oak\Contracts\Migration\RevisionInterface;
use Tnt\Dbi\TableBuilder;

class OrderAddedToGiftcardTable extends DatabaseRevision implements RevisionInterface
{
    public function up()
    {
        $this->queryBuilder->table('ecommerce_giftcard')->alter(function(TableBuilder $table) {

            $table->addColumn('order', 'int')->length(11)->null();
            $table->addForeignKey('order', 'ecommerce_order');

        });

        $this->execute();
    }

    public function down()
    {
        $this->queryBuilder->table('ecommerce_giftcard')->alter(function(TableBuilder $table) {

            $table->dropForeignKey('order', 'ecommerce_order');
            $table->dropColumn('order');

        });

        $this->execute();
    }

    public function describeUp(): string
    {
        return 'Table ecommerce_giftcard altered, added order';
    }

    public function describeDown(): string
    {
        return 'Table ecommerce_giftcard altered, dropped order';
    }
}