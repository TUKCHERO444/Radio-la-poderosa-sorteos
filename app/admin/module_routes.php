<?php
declare(strict_types=1);

use App\Modules\PaymentMethods\AdminPaymentMethodHandler;
use App\Modules\Prizes\AdminPrizeHandler;
use App\Modules\Raffles\AdminRaffleHandler;

return [
    ['GET', '/prizes', [AdminPrizeHandler::class, 'index']],
    ['GET', '/prizes/create', [AdminPrizeHandler::class, 'create']],
    ['POST', '/prizes', [AdminPrizeHandler::class, 'store']],
    ['GET', '/prizes/{id}/edit', [AdminPrizeHandler::class, 'edit']],
    ['POST', '/prizes/{id}', [AdminPrizeHandler::class, 'update']],
    ['POST', '/prizes/{id}/toggle', [AdminPrizeHandler::class, 'toggle']],
    ['POST', '/prizes/{id}/delete', [AdminPrizeHandler::class, 'delete']],

    ['GET', '/payment-methods', [AdminPaymentMethodHandler::class, 'index']],
    ['GET', '/payment-methods/create', [AdminPaymentMethodHandler::class, 'create']],
    ['POST', '/payment-methods', [AdminPaymentMethodHandler::class, 'store']],
    ['GET', '/payment-methods/{id}/edit', [AdminPaymentMethodHandler::class, 'edit']],
    ['POST', '/payment-methods/{id}', [AdminPaymentMethodHandler::class, 'update']],
    ['POST', '/payment-methods/{id}/toggle', [AdminPaymentMethodHandler::class, 'toggle']],
    ['POST', '/payment-methods/{id}/delete', [AdminPaymentMethodHandler::class, 'delete']],

    ['GET', '/raffles', [AdminRaffleHandler::class, 'index']],
    ['GET', '/raffles/create', [AdminRaffleHandler::class, 'create']],
    ['POST', '/raffles', [AdminRaffleHandler::class, 'store']],
    ['GET', '/raffles/{id}/edit', [AdminRaffleHandler::class, 'edit']],
    ['POST', '/raffles/{id}', [AdminRaffleHandler::class, 'update']],
    ['POST', '/raffles/{id}/cover', [AdminRaffleHandler::class, 'updateCover']],
    ['GET', '/raffles/{id}/publish', [AdminRaffleHandler::class, 'publishForm']],
    ['POST', '/raffles/{id}/publish', [AdminRaffleHandler::class, 'publish']],
    ['POST', '/raffles/{id}/close', [AdminRaffleHandler::class, 'close']],
    ['POST', '/raffles/{id}/cancel', [AdminRaffleHandler::class, 'cancel']],
    ['POST', '/raffles/{id}/delete', [AdminRaffleHandler::class, 'delete']],
];
