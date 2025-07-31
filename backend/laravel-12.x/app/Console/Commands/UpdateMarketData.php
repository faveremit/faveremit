<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ExchangeService;

class UpdateMarketData extends Command
{
    protected $signature = 'market:update';
    protected $description = 'Update cryptocurrency market data from external APIs';

    protected $exchangeService;

    public function __construct(ExchangeService $exchangeService)
    {
        parent::__construct();
        $this->exchangeService = $exchangeService;
    }

    public function handle()
    {
        $this->info('Starting market data update...');

        try {
            $this->exchangeService->updateMarketData();
            $this->info('Market data updated successfully!');
        } catch (\Exception $e) {
            $this->error('Failed to update market data: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
