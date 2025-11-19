<?php

declare(strict_types=1);

namespace App\Domain\Service;

class GraphBuilder
{
    /**
     * Build a bidirectional graph from distances data.
     *
     * @param array $distancesData Array of rail lines with distances
     * @return array<string, array<string, float>> Graph adjacency list
     */
    public function build(array $distancesData): array
    {
        $graph = [];

        foreach ($distancesData as $line) {
            foreach ($line['distances'] as $segment) {
                $parent = $segment['parent'];
                $child = $segment['child'];
                $distance = (float) $segment['distance'];

                // Initialize stations if not exist
                if (!isset($graph[$parent])) {
                    $graph[$parent] = [];
                }
                if (!isset($graph[$child])) {
                    $graph[$child] = [];
                }

                // Add bidirectional connection
                $graph[$parent][$child] = $distance;
                $graph[$child][$parent] = $distance;
            }
        }

        return $graph;
    }
}
