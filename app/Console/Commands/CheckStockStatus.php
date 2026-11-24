<?php

namespace App\Console\Commands;

use App\Services\Inventory\StockStatusMonitor;
use Illuminate\Console\Command;

class CheckStockStatus extends Command
{
    /**
     * @var string
     */
    protected $signature = 'inventory:check-stock-status';

    /**
     * @var string
     */
    protected $description = 'Recheck every active item\'s stock status and email alerts for items that got worse, catching batches that expired without any stock movement';

    public function handle(StockStatusMonitor $monitor): int
    {
        $alerted = $monitor->checkAll()->count();

        $this->info($alerted === 0
            ? 'No items got worse.'
            : "Sent stock alerts for {$alerted} ".str('item')->plural($alerted).'.');

        return self::SUCCESS;
    }
}
