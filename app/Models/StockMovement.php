<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'refund_id',
        'invoice_id',
        'user_id',
        'type',
        'quantity',
        'quantity_before',
        'quantity_after',
        'notes',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function refund()
    {
        return $this->belongsTo(Refund::class);
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
}







