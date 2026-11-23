<?php

declare(strict_types=1);

use App\Core\Kernel;
use Slim\App;

return static function (App $app): void {
    /** @var Kernel $kernel */
    $kernel = $app->getContainer()->get(Kernel::class);
    $kernel->boot($app);
};
