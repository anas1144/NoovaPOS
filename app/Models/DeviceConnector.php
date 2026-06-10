<?php

namespace App\Models;

use App\Traits\Multitenantable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * A no-code external device / service integration (biometric scanner, face
 * terminal, RFID/NFC reader, cloud API). See the migration for the field model.
 */
class DeviceConnector extends BaseModel
{
    use HasFactory, Multitenantable;

    protected $table = 'device_connectors';

    public const RUN_SERVER = 'server';
    public const RUN_CLIENT = 'client';

    protected $fillable = [
        'tenant_id', 'name', 'modality', 'transport', 'run_on',
        'base_url', 'verify_path', 'enroll_path', 'http_method',
        'auth_type', 'auth_header', 'auth_token', 'headers',
        'request_template', 'response_success_path', 'success_value',
        'response_employee_path', 'timeout', 'status', 'notes',
    ];

    protected $casts = [
        'headers'          => 'array',
        'request_template' => 'array',
        'status'           => 'boolean',
        'timeout'          => 'integer',
    ];

    /** Secrets stripped — safe to expose to the kiosk for client-run connectors. */
    public function toClientArray(): array
    {
        return [
            'id'            => $this->id,
            'name'          => $this->name,
            'modality'      => $this->modality,
            'run_on'        => $this->run_on,
            'base_url'      => $this->base_url,
            'verify_path'   => $this->verify_path,
            'http_method'   => $this->http_method,
            'request_template' => $this->request_template,
            'response_success_path'  => $this->response_success_path,
            'success_value'          => $this->success_value,
            'response_employee_path' => $this->response_employee_path,
        ];
    }
}
