<?php

namespace App\Console\Commands;

use App\Models\PagespeedMonitor;
use App\Services\PagespeedService;
use Illuminate\Console\Command;

class CheckPagespeed extends Command
{
    protected $signature = 'pagespeed:check {--id= : Check specific monitor ID}';
    protected $description = 'Run PageSpeed Insights checks for active monitors';

    public function handle(PagespeedService $service): int
    {
        $query = PagespeedMonitor::where('is_active', true);

        if ($id = $this->option('id')) {
            $query->where('id', $id);
        } else {
            // Only check monitors whose interval has elapsed since last check
            $query->where(function ($q) {
                $q->whereDoesntHave('checks')
                  ->orWhereHas('checks', function ($c) {
                      $c->where('checked_at', '<=', now()->subMinutes(
                          \DB::raw('pagespeed_monitors.interval_minutes')
                      ));
                  });
            });
        }

        $monitors = $query->get();

        if ($monitors->isEmpty()) {
            $this->info('No monitors due for check.');
            return 0;
        }

        foreach ($monitors as $monitor) {
            // Check if interval elapsed
            $latest = $monitor->checks()->latest('checked_at')->first();
            if ($latest && $latest->checked_at->diffInMinutes(now()) < $monitor->interval_minutes) {
                continue;
            }

            $this->line("Checking: {$monitor->name} ({$monitor->url})");
            $check = $service->check($monitor);

            if ($check->error_message) {
                $this->warn("  Error: {$check->error_message}");
            } else {
                $this->info("  Performance: {$check->performance_score} | A11y: {$check->accessibility_score} | BP: {$check->best_practices_score} | SEO: {$check->seo_score}");
            }
        }

        return 0;
    }
}
