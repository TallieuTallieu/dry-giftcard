<?php

namespace Tnt\Giftcard\Model;

use dry\media\File;
use dry\orm\Model;
use Oak\Dispatcher\Facade\Dispatcher;
use Tnt\Ecommerce\Contracts\BuyableInterface;
use Tnt\Ecommerce\Contracts\CouponInterface;
use Tnt\Ecommerce\Contracts\StockWorkerInterface;
use Tnt\Ecommerce\Contracts\TaxRateInterface;
use Tnt\Ecommerce\Contracts\TotalingInterface;
use Tnt\Ecommerce\Model\DiscountCode;
use Tnt\Ecommerce\Model\Order;
use Tnt\Ecommerce\Model\OrderItem;
use Tnt\Ecommerce\Stock\NullStockWorker;
use Tnt\Ecommerce\TaxRate\NullTaxRate;
use Tnt\Giftcard\Events\Redeemed;

class Giftcard extends Model implements BuyableInterface, CouponInterface
{
    const TABLE = 'ecommerce_giftcard';

    const STATUS_AWAITING_ORDER_PAYMENT = 0;
    const STATUS_AWAITING_GENERATION = 1;
    const STATUS_READY = 2;

    public static $special_fields = [
        'order' => Order::class,
        'file' => File::class,
    ];

    // Relations

    public function get_discount(): DiscountCode
    {
        return DiscountCode::load_by([
            'coupon_id' => $this->id,
            'coupon_class' => get_class($this),
        ]);
    }

    public function getOrder()
    {
        if ($this->order) {

            $orderItem = OrderItem::load_by([
                'order' => $this->order,
                'item_id' => $this->id,
                'item_class' => get_class($this),
            ]);

            return $orderItem->order;
        }

        $orderItem = OrderItem::one([
            'item_id' => $this->id,
            'item_class' => get_class($this),
        ]);

        return $orderItem->order;

    }

    // BuyableInterface

    public function getId(): string
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return 'Cadeaubon met waarde € '.$this->getPrice();
    }

    public function getDescription(): string
    {
        return 'Voor '.$this->to.', van '.$this->from.($this->custom_message ? ' met persoonlijke boodschap.' : '');
    }

    public function getPrice(): float
    {
        return $this->current_value;
    }

    public function getThumbnailSource(): string
    {
        return 'assets/img/dummy-thumb.jpg';
    }

    public function getStockWorker(): StockWorkerInterface
    {
        return new NullStockWorker();
    }

    public function getTaxRate(): TaxRateInterface
    {
        return new NullTaxRate();
    }

    // CouponInterface

    public function isRedeemable(TotalingInterface $totaling): bool
    {
        return $this->current_value > 0;
    }

    public function getReduction(TotalingInterface $totaling): float
    {
        return min($totaling->getSubTotal(), $this->current_value);
    }

    public function redeem(Order $order)
    {
        $this->current_value = max(0, $this->current_value - $order->getSubTotal());
        $this->save();

        Dispatcher::dispatch(Redeemed::class, new Redeemed($this));
    }
}