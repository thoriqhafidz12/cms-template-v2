<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Throwable;

class SalonSetting extends Model
{
    protected $guarded = ['id'];

    protected static ?self $cachedCurrent = null;

    protected function casts(): array
    {
        return [
            'tax_percentage' => 'decimal:2',
            'default_discount_percentage' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::saved(function (self $model) {
            static::$cachedCurrent = $model;
            if (function_exists('request') && request()) {
                request()->attributes->set('salon_setting_current', $model);
            }
        });
    }

    public static function defaults(): array
    {
        return [
            'salon_name' => 'E-SALON',
            'tagline' => 'Hair, Nail & Spa Studio',
            'address' => 'Jl. Melawai Raya No. 88, Jakarta Selatan',
            'phone' => '021-7208899',
            'email' => 'hello@esalon.id',
            'open_time' => '09:00',
            'close_time' => '20:00',
            'timezone' => 'Asia/Jakarta',
            'currency' => 'IDR',
            'tax_percentage' => 0,
            'default_discount_percentage' => 0,
            'booking_prefix' => 'BKG',
            'service_order_prefix' => 'SO',
            'transaction_prefix' => 'SAL',
            'receipt_width' => '80mm',
            'receipt_footer' => 'Terima kasih atas kunjungan Anda di E-SALON!',
            'low_stock_threshold_default' => 5,
        ];
    }

    public static function current(bool $fresh = false): self
    {
        $req = function_exists('request') ? request() : null;

        if (! $fresh && $req && $req->attributes->has('salon_setting_current')) {
            return $req->attributes->get('salon_setting_current');
        }

        try {
            if (! Schema::hasTable('salon_settings')) {
                return new static(static::defaults());
            }

            $instance = static::first() ?? static::create(static::defaults());
            static::$cachedCurrent = $instance;

            if ($req) {
                $req->attributes->set('salon_setting_current', $instance);
            }

            return $instance;
        } catch (Throwable) {
            return new static(static::defaults());
        }
    }

    public static function flushCache(): void
    {
        static::$cachedCurrent = null;
    }
}
