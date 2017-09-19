<?php

namespace Kelunik\Retry;

use Amp\Delayed;
use Amp\Promise;
use function Amp\call;

function retry(int $maxAttempts, callable $actor, string $throwable = \Throwable::class, Backoff $backoff = null): Promise {
    if ($maxAttempts < 1) {
        throw new \Error("Argument 1 (maxAttempts) must be positive.");
    }

    return call(function () use ($maxAttempts, $actor, $throwable, $backoff) {
        $attempt = 0;
        $backoff = $backoff ?? new ConstantBackoff(0);

        retry:

        $attempt++;

        try {
            $result = yield call($actor);
        } catch (\Throwable $e) {
            if ($e instanceof $throwable && $attempt < $maxAttempts) {
                yield new Delayed($backoff->getTimeInMilliseconds($attempt));
                goto retry;
            }

            throw $e;
        }

        return $result;
    });
}
