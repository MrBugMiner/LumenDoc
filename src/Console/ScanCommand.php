<?php

namespace MrBugMiner\LumenDoc\Console;

use Illuminate\Console\Command;
use MrBugMiner\LumenDoc\Classes\Scan;

class ScanCommand extends Command
{

    /** @var string $signature */
    protected $signature = 'lumen-doc:scan';

    /** @var string $description */
    protected $description = 'Scan All Routes And Create "scan.json" File In "public/lumen-doc/" Folder.';

    public function handle()
    {
        (new Scan())->handle($this);
    }

}