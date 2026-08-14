<?php

namespace App\Console\Commands;

use App\Models\Clinic;
use Illuminate\Console\Command;

class PurgeDemoClinics extends Command
{
    protected $signature = 'clinics:purge-demo {--force : Skip the confirmation prompt}';

    protected $description = 'Delete every fictional demo clinic (is_demo = true). Run as a pre-launch step.';

    public function handle(): int
    {
        // Count without the production global scope, which would otherwise hide
        // the very rows we are trying to delete.
        $count = Clinic::withoutGlobalScope('excludeDemo')->where('is_demo', true)->count();

        if ($count === 0) {
            $this->info('No demo clinics found. Nothing to purge.');

            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm("Delete {$count} demo clinic(s)? This cannot be undone.")) {
            $this->comment('Aborted.');

            return self::FAILURE;
        }

        Clinic::withoutGlobalScope('excludeDemo')->where('is_demo', true)->delete();

        $this->info("Purged {$count} demo clinic(s).");

        return self::SUCCESS;
    }
}
