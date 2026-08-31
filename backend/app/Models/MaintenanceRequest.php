<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaintenanceRequest extends Model
{
    use HasFactory;

    /**
     * Status yang tersedia untuk maintenance request.
     */
    const STATUS_PENDING  = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    const STATUS_DONE     = 'done';

    /**
     * Tipe service yang tersedia.
     */
    const SERVICE_OIL_CHANGE       = 'oil_change';
    const SERVICE_TIRE_REPLACEMENT = 'tire_replacement';
    const SERVICE_SPAREPART        = 'sparepart';
    const SERVICE_GENERAL          = 'general';
    const SERVICE_OTHER            = 'other';

    /**
     * Urgency level.
     */
    const URGENCY_LOW    = 'low';
    const URGENCY_MEDIUM = 'medium';
    const URGENCY_HIGH   = 'high';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'request_code',          // 🔥 Kode unik pengajuan (auto-generated)
        'vehicle_id',
        'driver_id',
        'schedule_id',
        'request_date',
        'description',
        'service_type',
        'estimated_cost',
        'actual_cost',           // 🔥 Biaya aktual setelah selesai
        'urgency',
        'status',
        'is_executed',
        'created_by',
        'approved_by',
        'approved_at',
        'executed_by',
        'executed_at',
        'branch_id',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'request_date'   => 'date',
        'approved_at'    => 'datetime',
        'executed_at'    => 'datetime',
        'estimated_cost' => 'decimal:2',
        'actual_cost'    => 'decimal:2',
        'is_executed'    => 'boolean',
        'created_at'     => 'datetime',
        'updated_at'     => 'datetime',
    ];

    // ============================================================
    // RELASI
    // ============================================================

    /**
     * Kendaraan yang diajukan perawatannya.
     */
    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    /**
     * Driver yang mengajukan (jika ada).
     */
    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }

    /**
     * Jadwal perawatan terkait (jika dari schedule).
     */
    public function schedule()
    {
        return $this->belongsTo(MaintenanceSchedule::class, 'schedule_id');
    }

    /**
     * User yang membuat pengajuan.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * User yang menyetujui pengajuan.
     */
    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * User yang mengeksekusi/menyelesaikan pengajuan.
     */
    public function executor()
    {
        return $this->belongsTo(User::class, 'executed_by');
    }

    /**
     * Cabang tempat pengajuan dibuat.
     */
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Item spare part yang digunakan dalam maintenance ini.
     */
    public function items()
    {
        return $this->hasMany(MaintenanceRequestItem::class);
    }

    // ============================================================
    // SCOPES
    // ============================================================

    /**
     * Scope untuk pengajuan yang masih pending.
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope untuk pengajuan yang sudah disetujui.
     */
    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    /**
     * Scope untuk pengajuan yang sudah selesai.
     */
    public function scopeDone($query)
    {
        return $query->where('status', self::STATUS_DONE);
    }

    /**
     * Scope untuk pengajuan yang belum selesai (pending + approved).
     */
    public function scopeNotDone($query)
    {
        return $query->whereIn('status', [self::STATUS_PENDING, self::STATUS_APPROVED]);
    }

    // ============================================================
    // ATTRIBUTES / ACCESSORS
    // ============================================================

    /**
     * Mendapatkan label status dalam bahasa Indonesia.
     */
    public function getStatusLabelAttribute()
    {
        $labels = [
            self::STATUS_PENDING  => 'Pending',
            self::STATUS_APPROVED => 'Disetujui',
            self::STATUS_REJECTED => 'Ditolak',
            self::STATUS_DONE     => 'Selesai',
        ];

        return $labels[$this->status] ?? $this->status;
    }

    /**
     * Mendapatkan class badge untuk status.
     */
    public function getStatusBadgeClassAttribute()
    {
        $classes = [
            self::STATUS_PENDING  => 'badge-warning',
            self::STATUS_APPROVED => 'badge-info',
            self::STATUS_REJECTED => 'badge-danger',
            self::STATUS_DONE     => 'badge-success',
        ];

        return $classes[$this->status] ?? 'badge-secondary';
    }

    /**
     * Mendapatkan label urgency dalam bahasa Indonesia.
     */
    public function getUrgencyLabelAttribute()
    {
        $labels = [
            self::URGENCY_LOW    => 'Rendah',
            self::URGENCY_MEDIUM => 'Sedang',
            self::URGENCY_HIGH   => 'Tinggi',
        ];

        return $labels[$this->urgency] ?? $this->urgency;
    }

    /**
     * Mendapatkan label service type dalam bahasa Indonesia.
     */
    public function getServiceTypeLabelAttribute()
    {
        $labels = [
            self::SERVICE_OIL_CHANGE       => 'Ganti Oli',
            self::SERVICE_TIRE_REPLACEMENT => 'Ganti Ban',
            self::SERVICE_SPAREPART        => 'Sparepart',
            self::SERVICE_GENERAL          => 'General Service',
            self::SERVICE_OTHER            => 'Lainnya',
        ];

        return $labels[$this->service_type] ?? $this->service_type;
    }

    /**
     * Mendapatkan total biaya (estimasi atau aktual).
     * Jika actual_cost ada, gunakan itu; selain itu gunakan estimated_cost.
     */
    public function getCostAttribute()
    {
        return $this->actual_cost ?? $this->estimated_cost ?? 0;
    }

    /**
     * Mengecek apakah pengajuan sudah selesai (done).
     */
    public function getIsCompletedAttribute()
    {
        return $this->status === self::STATUS_DONE;
    }

    /**
     * Mengecek apakah pengajuan bisa diedit (hanya pending).
     */
    public function getIsEditableAttribute()
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Mengecek apakah pengajuan bisa disetujui.
     */
    public function getIsApprovableAttribute()
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Mengecek apakah pengajuan bisa dieksekusi (approved dan belum executed).
     */
    public function getIsExecutableAttribute()
    {
        return $this->status === self::STATUS_APPROVED && !$this->is_executed;
    }

    // ============================================================
    // HELPER METHODS
    // ============================================================

    /**
     * Generate kode request otomatis.
     */
    public static function generateRequestCode()
    {
        return 'MNT-' . strtoupper(uniqid());
    }

    /**
     * Boot method untuk auto-generate request_code.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->request_code)) {
                $model->request_code = self::generateRequestCode();
            }
        });
    }
}