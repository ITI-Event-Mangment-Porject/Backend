<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ArchiveCompletedEvents extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'events:archive-completed';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Archive events that were completed more than 7 days ago.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Archiving completed events...');

        $archivedCount = 0;
        $events = \App\Models\Event\Event::where('status', 'completed')
            ->where('end_date', '<=', now()->subDays(7))
            ->get();

        foreach ($events as $event) {
            $event->status = 'archived';
            $event->archived_at = now();
            $event->save();
            $archivedCount++;
        }

        $this->info("Archived {$archivedCount} events.");
    }
}
