<?php

function parseRetry(callable $callback, int $attempts = 3, int $delayMs = 350)
{
    $lastException = null;

    for ($i = 1; $i <= $attempts; $i++) {
        try {
            return $callback();
        } catch (Exception $e) {
            $lastException = $e;

            if ($i < $attempts) {
                usleep($delayMs * 1000);
                $delayMs *= 2;
            }
        }
    }

    throw $lastException;
}

function formatDate($date)
{
    if (!$date) return '';
    return $date->format('d.m.Y H:i');
}
