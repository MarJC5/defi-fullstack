<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Entity\Route;
use App\Domain\Exception\NoRouteFoundException;
use App\Domain\Exception\StationNotFoundException;

class RouteCalculator
{
    public function __construct(
        private array $graph
    ) {
    }

    public function calculate(string $from, string $to, string $analyticCode): Route
    {
        // Validate stations exist
        if (!isset($this->graph[$from])) {
            throw new StationNotFoundException($from);
        }

        if (!isset($this->graph[$to])) {
            throw new StationNotFoundException($to);
        }

        // Same station - no travel needed
        if ($from === $to) {
            return new Route($from, $to, $analyticCode, 0.0, [$from]);
        }

        // Dijkstra's algorithm
        $distances = [];
        $previous = [];
        $unvisited = [];

        // Initialize
        foreach ($this->graph as $station => $neighbors) {
            $distances[$station] = PHP_FLOAT_MAX;
            $previous[$station] = null;
            $unvisited[$station] = true;
        }

        $distances[$from] = 0.0;

        while (!empty($unvisited)) {
            // Find unvisited node with minimum distance
            $current = null;
            $minDistance = PHP_FLOAT_MAX;

            foreach ($unvisited as $station => $true) {
                if ($distances[$station] < $minDistance) {
                    $minDistance = $distances[$station];
                    $current = $station;
                }
            }

            // No reachable nodes left
            if ($current === null || $distances[$current] === PHP_FLOAT_MAX) {
                break;
            }

            // Reached destination
            if ($current === $to) {
                break;
            }

            unset($unvisited[$current]);

            // Update distances to neighbors
            foreach ($this->graph[$current] as $neighbor => $distance) {
                if (!isset($unvisited[$neighbor])) {
                    continue;
                }

                $newDistance = $distances[$current] + $distance;

                if ($newDistance < $distances[$neighbor]) {
                    $distances[$neighbor] = $newDistance;
                    $previous[$neighbor] = $current;
                }
            }
        }

        // No route found
        if ($distances[$to] === PHP_FLOAT_MAX) {
            throw new NoRouteFoundException($from, $to);
        }

        // Reconstruct path
        $path = [];
        $current = $to;

        while ($current !== null) {
            array_unshift($path, $current);
            $current = $previous[$current];
        }

        return new Route($from, $to, $analyticCode, $distances[$to], $path);
    }
}
