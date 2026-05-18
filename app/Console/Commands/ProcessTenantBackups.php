<?php

namespace App\Console\Commands;

use App\Models\TenantBackup;
use App\Services\TenantBackupService;
use Illuminate\Console\Command;

class ProcessTenantBackups extends Command
{
    protected $signature = 'tenants:process-backups {--id=} {--limit=5}';

    protected $description = 'Process queued tenant backup jobs.';

    public function handle(TenantBackupService $tenantBackupService): int
    {
        $query = TenantBackup::query()
            ->where('status', TenantBackup::STATUS_QUEUED)
            ->orderBy('id');

        if ($this->option('id')) {
            $query->where('id', (int) $this->option('id'));
        } else {
            $query->limit((int) $this->option('limit'));
        }

        $backups = $query->get();

        if ($backups->isEmpty()) {
            $this->info('No queued tenant backups found.');
            return self::SUCCESS;
        }

        foreach ($backups as $backup) {
            $this->line("Processing tenant backup #{$backup->id} for tenant {$backup->tenant_id}...");
            $processed = $tenantBackupService->process($backup);
            $this->line("Backup #{$processed->id} finished with status {$processed->status}.");
        }

        return self::SUCCESS;
    }
}
