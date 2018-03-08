<?php

declare(strict_types=1);

namespace ChrisHarrison\SnsClient;

final class ClientFactory
{
    private $regionEndpoints;

    public function __construct(?array $regionEndpoints = null)
    {
        $this->regionEndpoints = $regionEndpoints ?? $this->defaultRegionEndpoints();
    }

    public function fromAwsCredentials(string $accessKey, string $secretKey, string $region): SnsClient
    {
        return new DefaultSnsClient($accessKey, $secretKey, 'https://', $this->endpointForRegion($region));
    }

    private function endpointForRegion(string $region): string
    {
        if (isset($this->regionEndpoints[$region])) {
            return $this->regionEndpoints[$region];
        }

        return sprintf('sns.%s.amazonaws.com', $region);
    }

    private function defaultRegionEndpoints(): array
    {
        return [
            'cn-north-1' => 'sns.cn-north-1.amazonaws.com.cn',
            'cn-northwest-1' => 'sns.cn-northwest-1.amazonaws.com.cn',
        ];
    }
}
