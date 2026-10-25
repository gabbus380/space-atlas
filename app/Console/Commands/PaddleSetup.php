<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Laravel\Paddle\Cashier;

/** Creates the two ATLAS products and four prices in Paddle, then prints the lines for the environment. */
class PaddleSetup extends Command
{
    protected $signature = 'atlas:paddle-setup {--tax-category=standard : Paddle tax category (use saas once Paddle has approved it)} {--dry-run}';

    protected $description = 'Create the ATLAS Personal and Teams products and prices in Paddle';

    public function handle(): int
    {
        if (! config('cashier.api_key')) {
            $this->error('Set PADDLE_API_KEY (and PADDLE_SANDBOX=true for the sandbox) first.');

            return self::FAILURE;
        }
        $p = config('atlas.prices');
        $min = config('atlas.teams_min_seats');
        $max = config('atlas.teams_max_seats');
        $plan = [
            'personal' => ['name' => 'Sovereign Space ATLAS Personal', 'desc' => 'Every country file, the full daily briefing and every tracker, for one named user.', 'qty' => [1, 1]],
            'teams' => ['name' => 'Sovereign Space ATLAS Teams', 'desc' => 'Personal for every seat, plus a shared watchlist and team notes. Priced per user, at least '.$min.' users.', 'qty' => [$min, $max]],
        ];
        $env = [];
        $this->info('Paddle '.(config('cashier.sandbox') ? 'SANDBOX' : 'LIVE'));
        foreach ($plan as $key => $def) {
            if ($this->option('dry-run')) {
                $this->line("would create product {$def['name']} with yearly \${$p[$key]['year']} and monthly \${$p[$key]['month']}");

                continue;
            }
            $product = Cashier::api('POST', 'products', [
                'name' => $def['name'], 'tax_category' => $this->option('tax-category'), 'description' => $def['desc'],
            ])['data'];
            foreach (['year' => 'Yearly', 'month' => 'Monthly'] as $interval => $label) {
                $price = Cashier::api('POST', 'prices', [
                    'product_id' => $product['id'],
                    'name' => $label,
                    'description' => $def['name'].', '.strtolower($label).' billing',
                    'unit_price' => ['amount' => (string) ($p[$key][$interval] * 100), 'currency_code' => 'USD'],
                    'billing_cycle' => ['interval' => $interval, 'frequency' => 1],
                    'tax_mode' => 'external',
                    'quantity' => ['minimum' => $def['qty'][0], 'maximum' => $def['qty'][1]],
                ])['data'];
                $env['PADDLE_PRICE_'.strtoupper($key).'_'.strtoupper($interval)] = $price['id'];
            }
        }
        if ($env) {
            $this->newLine();
            $this->line('Add these to the environment in Laravel Cloud:');
            foreach ($env as $k => $v) {
                $this->line("$k=$v");
            }
        }

        return self::SUCCESS;
    }
}
