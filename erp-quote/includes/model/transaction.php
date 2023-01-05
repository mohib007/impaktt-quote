<?php
namespace WeDevs\ERP\Accounting\Model;

use WeDevs\ERP\Framework\Model;

class Transaction_QUOTE extends Model {
    protected $primaryKey = 'id';
    protected $table      = 'erp_ac_quote_transactions';
    public $timestamps    = false;
	// Adding Invoice titles, order_type & order_id For Break A Part Report - TC Altered
    protected $fillable   = [ 'type', 'form_type', 'status', 'user_id', 'inv_title', 'order_type', 'order_id', 'billing_address', 'ref', 'summary', 'issue_date', 'due_date', 'currency', 'conversion_rate', 'sub_total', 'total', 'due', 'trans_total', 'quote_number', 'quote_format', 'files', 'created_by', 'created_at'];

    public function items() {
        return $this->hasMany( 'WeDevs\ERP\Accounting\Model\Transaction_Items_QUOTE', 'transaction_id' );
    }

    public function journals() {
        return $this->hasMany( 'WeDevs\ERP\Accounting\Model\Journal_QUOTE', 'transaction_id' );
    }

    public function payments() {
        return $this->hasMany( 'WeDevs\ERP\Accounting\Model\Payment', 'transaction_id' );
    }

    public function user() {
        return $this->hasOne( 'WeDevs\ERP\Accounting\Model\User', 'id', 'user_id' );
    }

    public function scopeType( $query, $type = 'quote' ) {
        if ( is_array( $type ) ) {
            return $query->whereIn( 'type', $type );
        } else {
            return $query->where( 'type', '=', $type );
        }
    }

    public function scopeOfUser( $query, $user_id = 0 ) {
        return $query->where( 'user_id', '=', $user_id );
    }
}
