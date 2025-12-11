<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PayrollPeriod extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'name',
        'type',
        'start_date',
        'end_date',
        'pay_date',
        'status',
        'notes',
        'total_gross',
        'total_deductions',
        'total_net',
        'total_employees',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'pay_date' => 'date',
        'total_gross' => 'decimal:2',
        'total_deductions' => 'decimal:2',
        'total_net' => 'decimal:2',
        'total_employees' => 'integer',
    ];

    public function payrolls()
    {
        return $this->hasMany(Payroll::class);
    }

    // Alias for payrollItems if the controller uses it (it shouldn't, but let's check)
    // The controller uses payrollItems.employee. This suggests it expects PayrollPeriod to have payrollItems.
    // But logically PayrollPeriod -> Payroll -> PayrollItems.
    // Maybe the controller meant PayrollPeriod -> Payrolls (with items).

    // Let's check the controller again.
    // $period = PayrollPeriod::with(['payrollItems.employee'])->find($id);
    // This implies PayrollPeriod hasMany PayrollItem directly? That would be weird.
    // Or maybe 'payrollItems' is a relationship name for 'payrolls'?

    // Let's assume 'payrollItems' in the controller actually refers to the 'payrolls' relationship (list of employee payrolls).
    // I will define 'payrollItems' as an alias for 'payrolls' to match the controller code without changing it too much.
    public function payrollItems()
    {
        return $this->hasMany(Payroll::class, 'payroll_period_id');
    }
}
