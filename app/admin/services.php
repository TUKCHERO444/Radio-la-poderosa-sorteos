<?php
declare(strict_types=1);

use App\Modules\PaymentMethods\PaymentMethodRepository;
use App\Modules\PaymentMethods\PaymentMethodService;
use App\Modules\Prizes\PrizeRepository;
use App\Modules\Prizes\PrizeService;
use App\Modules\Raffles\RaffleRepository;
use App\Modules\Raffles\RaffleService;
use App\Shared\Audit\AuditService;
use App\Shared\Storage\ImageStorage;

function admin_prize_service(): PrizeService
{
    static $service = null;

    if ($service instanceof PrizeService) {
        return $service;
    }

    $pdo = db();
    $service = new PrizeService(
        new PrizeRepository($pdo),
        new ImageStorage(),
        new AuditService($pdo),
        $pdo
    );

    return $service;
}

function admin_payment_method_service(): PaymentMethodService
{
    static $service = null;

    if ($service instanceof PaymentMethodService) {
        return $service;
    }

    $pdo = db();
    $service = new PaymentMethodService(
        new PaymentMethodRepository($pdo),
        new ImageStorage(),
        new AuditService($pdo),
        $pdo
    );

    return $service;
}

function admin_raffle_service(): RaffleService
{
    static $service = null;

    if ($service instanceof RaffleService) {
        return $service;
    }

    $pdo = db();
    $service = new RaffleService(
        new RaffleRepository($pdo),
        new PrizeRepository($pdo),
        new PaymentMethodRepository($pdo),
        new ImageStorage(),
        new AuditService($pdo),
        $pdo
    );

    return $service;
}
