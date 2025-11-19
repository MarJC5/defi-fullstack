<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Service;

use App\Domain\Service\GraphBuilder;
use PHPUnit\Framework\TestCase;

class GraphBuilderTest extends TestCase
{
    public function testBuildGraphFromDistancesData(): void
    {
        $distancesData = [
            [
                'name' => 'TEST',
                'distances' => [
                    ['parent' => 'A', 'child' => 'B', 'distance' => 1.0],
                    ['parent' => 'B', 'child' => 'C', 'distance' => 2.0],
                ],
            ],
        ];

        $builder = new GraphBuilder();
        $graph = $builder->build($distancesData);

        // Graph should be bidirectional
        $this->assertArrayHasKey('A', $graph);
        $this->assertArrayHasKey('B', $graph);
        $this->assertArrayHasKey('C', $graph);

        // A connects to B
        $this->assertEquals(1.0, $graph['A']['B']);
        // B connects to A and C
        $this->assertEquals(1.0, $graph['B']['A']);
        $this->assertEquals(2.0, $graph['B']['C']);
        // C connects to B
        $this->assertEquals(2.0, $graph['C']['B']);
    }

    public function testBuildGraphFromMultipleLines(): void
    {
        $distancesData = [
            [
                'name' => 'LINE1',
                'distances' => [
                    ['parent' => 'A', 'child' => 'B', 'distance' => 1.0],
                ],
            ],
            [
                'name' => 'LINE2',
                'distances' => [
                    ['parent' => 'B', 'child' => 'C', 'distance' => 2.0],
                ],
            ],
        ];

        $builder = new GraphBuilder();
        $graph = $builder->build($distancesData);

        // All stations connected
        $this->assertEquals(1.0, $graph['A']['B']);
        $this->assertEquals(1.0, $graph['B']['A']);
        $this->assertEquals(2.0, $graph['B']['C']);
        $this->assertEquals(2.0, $graph['C']['B']);
    }

    public function testBuildGraphWithSharedStation(): void
    {
        // CABY is shared between MOB and MVR-ce
        $distancesData = [
            [
                'name' => 'MOB',
                'distances' => [
                    ['parent' => 'SONZ', 'child' => 'CABY', 'distance' => 1.68],
                ],
            ],
            [
                'name' => 'MVR-ce',
                'distances' => [
                    ['parent' => 'CABY', 'child' => 'BLON', 'distance' => 3.5],
                ],
            ],
        ];

        $builder = new GraphBuilder();
        $graph = $builder->build($distancesData);

        // CABY connects to both SONZ and BLON
        $this->assertArrayHasKey('SONZ', $graph['CABY']);
        $this->assertArrayHasKey('BLON', $graph['CABY']);
    }

    public function testBuildEmptyGraph(): void
    {
        $builder = new GraphBuilder();
        $graph = $builder->build([]);

        $this->assertEmpty($graph);
    }
}
