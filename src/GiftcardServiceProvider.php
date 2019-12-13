<?php

namespace Tnt\Giftcard;

use Oak\Contracts\Console\KernelInterface;
use Oak\Contracts\Container\ContainerInterface;
use Oak\Contracts\Dispatcher\DispatcherInterface;
use Oak\Migration\MigrationManager;
use Oak\Migration\Migrator;
use Oak\ServiceProvider;
use Tnt\Ecommerce\Events\Order\BuyableAdded;
use Tnt\Ecommerce\Events\Order\Paid;
use Tnt\Ecommerce\Model\DiscountCode;
use Tnt\Giftcard\Console\GenerateGiftcards;
use Tnt\Giftcard\Model\Giftcard;
use Tnt\Giftcard\Revisions\OrderAddedToGiftcardTable;
use Tnt\Giftcard\Revisions\CreateGiftcardTable;

class GiftcardServiceProvider extends ServiceProvider
{
    public function boot(ContainerInterface $app)
    {
        if ($app->isRunningInConsole()) {

            $this->bootConsole($app);
            $this->bootMigrator($app);
        }

        $this->bootEventListeners($app);
    }

    public function register(ContainerInterface $app)
    {
        //
    }

    private function bootMigrator(ContainerInterface $app)
    {
        $migrator = $app->getWith(Migrator::class, [
            'name' => 'giftcard',
        ]);

        $migrator->setRevisions([
            CreateGiftcardTable::class,
            OrderAddedToGiftcardTable::class,
        ]);

        $app->get(MigrationManager::class)
            ->addMigrator($migrator);
    }

    private function bootConsole(ContainerInterface $app)
    {
        $app->get(KernelInterface::class)
            ->registerCommand(GenerateGiftcards::class)
        ;
    }

    private function bootEventListeners(ContainerInterface $app)
    {
        $dispatcher = $app->get(DispatcherInterface::class);

        // Listen to BuyableAddedToOrder event
        $dispatcher->addListener(BuyableAdded::class, function ($buyableAdded) {

            $order = $buyableAdded->getOrder();
            $buyable = $buyableAdded->getBuyable();

            // Only target gift cards
            if (! $buyable || ! ($buyable instanceof Giftcard)) {
                return;
            }

            $buyable->order = $order;
            $buyable->save();
        });

        // Listen to Paid event
        $dispatcher->addListener(Paid::class, function ($paidEvent) {

            $order = $paidEvent->getOrder();
            $items = $order->getItems();

            foreach ($items as $item) {

                $buyable = $item->getBuyable();

                // Only target gift cards
                if (! $buyable || ! ($buyable instanceof Giftcard)) {
                    continue;
                }

                $discount = new DiscountCode();
                $discount->created = time();
                $discount->updated = time();
                $discount->coupon_id = $buyable->id;
                $discount->coupon_class = get_class($buyable);
                $discount->code = \dry\util\string\random(12);
                $discount->save();

                $buyable->status = Giftcard::STATUS_AWAITING_GENERATION;
                $buyable->save();
            }
        });
    }
}