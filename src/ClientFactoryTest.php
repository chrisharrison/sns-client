<?php

// @codingStandardsIgnoreFile

declare(strict_types=1);

namespace ChrisHarrison\SnsClient;

use PHPUnit\Framework\TestCase;

final class ClientFactoryTest extends TestCase
{
    public function test_constructs_with_correct_endpoint_from_region()
    {
        $factory = new ClientFactory(['test-region' => 'test-endpoint']);
        $test = $factory->fromAwsCredentials('access-key', 'secret-key', 'test-region');
        $this->assertEquals('test-endpoint', $test->getEndpoint());
    }

    public function test_constructs_with_correct_endpoint_from_unknown_region()
    {
        $factory = new ClientFactory();
        $test = $factory->fromAwsCredentials('access-key', 'secret-key', 'test-region');
        $this->assertEquals('sns.test-region.amazonaws.com', $test->getEndpoint());
    }
}
