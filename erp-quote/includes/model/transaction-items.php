<?php
namespace WeDevs\ERP\Accounting\Model;

use WeDevs\ERP\Framework\Model;

class Transaction_Items_QUOTE extends Model {
    protected $primaryKey = 'id';
    protected $table = 'erp_ac_quote_transaction_items';
    public $timestamps = false;
    protected $fillable = [ 'transaction_id', 'journal_id', 'product_id', 'type', 'description', 'qty', 'unit_price', 'tax', 'tax_rate', 'line_total', 'tax_journal'];

}