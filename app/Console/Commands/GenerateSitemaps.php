<?php

namespace App\Console\Commands;

use App\Services\SitemapGenerator;
use Illuminate\Console\Command;

class GenerateSitemaps extends Command
{
    protected $signature = 'sitemap:generate';

    protected $description = 'Write the XML sitemap set to public/ (index + orase + servicii + clinici chunks).';

    public function handle(SitemapGenerator $generator): int
    {
        $files = $generator->write();

        $this->info('Generated '.count($files).' sitemap file(s): '.implode(', ', $files));

        return self::SUCCESS;
    }
}
