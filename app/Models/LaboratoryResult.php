<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LaboratoryResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'test_name',
        'test_type',
        'description',
        'test_date',
        'status',
        'results',
        'file_path',
        'notes',
        'price',
    ];

    protected $casts = [
        'results' => 'array',
        'test_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function assignedDoctor()
    {
        return $this->belongsTo(User::class, 'assigned_doctor_id');
    }

    public function save(array $options = [])
    {
        if ($this->exists && auth()->user()->type === 'professional') {
            $this->status = 'completed';
        }
        
        return parent::save($options);
    }
}
