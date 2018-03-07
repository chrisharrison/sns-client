<?php

declare(strict_types=1);

namespace ChrisHarrison\SnsClient;

use ChrisHarrison\SnsClient\Exceptions\SnsException;

final class DefaultExtendedSnsClient implements ExtendedSnsClient
{
    private $client;

    public function __construct(SnsClient $client)
    {
        $this->client = $client;
    }

    public function publishAndCreateTopicIfNeeded(
        string $topicArn,
        string $message,
        string $subject = '',
        string $messageStructure = ''
    ): string {
        try {
            return $this->publish($topicArn, $message, $subject, $messageStructure);
        } catch (SnsException $e) {
            if ($e->getCode() !== 404) {
                throw $e;
            }
            $arn = $this->createTopic($this->endOfArn($topicArn));
            return $this->publish($arn, $message, $subject, $messageStructure);
        }
    }

    private function endOfArn(string $arn): string
    {
        $arnArray = explode(':', $arn);
        return $arnArray[count($arnArray)-1];
    }

    public function getAccessKey(): string
    {
        return $this->client->getAccessKey();
    }

    public function getSecretKey(): string
    {
        return $this->client->getSecretKey();
    }

    public function getEndpoint(): string
    {
        return $this->client->getEndpoint();
    }

    public function addPermission(string $topicArn, string $label, array $permissions = []): bool
    {
        return $this->client->addPermission($topicArn, $label, $permissions);
    }

    public function confirmSubscription(
        string $topicArn,
        string $token,
        ?bool $authenticateOnUnsubscribe = null
    ): string {
        return $this->client->confirmSubscription($topicArn, $token, $authenticateOnUnsubscribe);
    }

    public function createTopic(string $name): string
    {
        return $this->client->createTopic($name);
    }

    public function deleteTopic(string $topicArn): bool
    {
        return $this->client->deleteTopic($topicArn);
    }

    public function getTopicAttributes(string $topicArn): array
    {
        return $this->client->getTopicAttributes($topicArn);
    }

    public function listSubscriptions(?string $nextToken = null): array
    {
        return $this->client->listSubscriptions($nextToken);
    }

    public function listSubscriptionsByTopic(string $topicArn, ?string $nextToken = null): array
    {
        return $this->client->listSubscriptionsByTopic($topicArn, $nextToken);
    }

    public function listTopics(?string $nextToken = null): array
    {
        return $this->client->listTopics($nextToken);
    }

    public function publish(
        string $topicArn,
        string $message,
        string $subject = '',
        string $messageStructure = ''
    ): string {
        return $this->client->publish($topicArn, $message, $subject, $messageStructure);
    }

    public function removePermission(string $topicArn, string $label): bool
    {
        return $this->client->removePermission($topicArn, $label);
    }

    public function setTopicAttributes(string $topicArn, string $attrName, $attrValue): bool
    {
        return $this->client->setTopicAttributes($topicArn, $attrName, $attrValue);
    }

    public function subscribe(string $topicArn, string $protocol, string $endpoint): string
    {
        return $this->client->subscribe($topicArn, $protocol, $endpoint);
    }

    public function unsubscribe(string $subscriptionArn): bool
    {
        return $this->client->unsubscribe($subscriptionArn);
    }

    public function createPlatformEndpoint(
        string $platformApplicationArn,
        string $token,
        ?string $userData = null
    ): string {
        return $this->client->createPlatformEndpoint($platformApplicationArn, $token, $userData);
    }

    public function deleteEndpoint(string $deviceArn): bool
    {
        return $this->client->deleteEndpoint($deviceArn);
    }

    public function publishToEndpoint(string $deviceArn, string $message): string
    {
        return $this->client->publishToEndpoint($deviceArn, $message);
    }
}
