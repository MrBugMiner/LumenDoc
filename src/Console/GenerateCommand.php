<?php

namespace MrBugMiner\LumenDoc\Console;

use Illuminate\Console\Command;
use MrBugMiner\LumenDoc\Classes\Generate;

class GenerateCommand extends Command
{

    /** @var string $signature */
    protected $signature = 'lumen-doc:generate';

    /** @var string $description */
    protected $description = 'Load "scan.json" File From "public/lumen-doc/" Folder , Call All Routes , Generate Document And Create "doc.json" File In "public/lumen-doc/" Folder.';

    public function handle()
    {
        (new Generate())->handle($this);
    }

}