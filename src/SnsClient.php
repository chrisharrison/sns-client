<?php

declare(strict_types=1);

namespace ChrisHarrison\SnsClient;

interface SnsClient
{
    /**
     * @return string
     */
    public function getAccessKey(): string;

    /**
     * @return string
     */
    public function getSecretKey(): string;

    /**
     * @return string
     */
    public function getProtocol(): string;

    /**
     * @return string
     */
    public function getEndpoint(): string;

    /**
     * Add permissions to a topic
     **
     * @link http://docs.amazonwebservices.com/sns/latest/api/API_AddPermission.html
     * @param string $topicArn
     * @param string $label Unique name of permissions
     * @param array $permissions [optional] Array of permissions - member ID as keys, actions as values
     * @return bool
     */
    public function addPermission(string $topicArn, string $label, array $permissions = []): bool;

    /**
     * Confirm a subscription to a topic
     *
     * @link http://docs.amazonwebservices.com/sns/latest/api/API_ConfirmSubscription.html
     * @param string $topicArn
     * @param string $token
     * @param bool|null $authenticateOnUnsubscribe [optional]
     * @return string - SubscriptionARN
     */
    public function confirmSubscription(
        string $topicArn,
        string $token,
        ?bool $authenticateOnUnsubscribe = null
    ): string;

    /**
     * Create an SNS topic
     *
     * @link http://docs.amazonwebservices.com/sns/latest/api/API_CreateTopic.html
     * @param string $name
     * @return string - TopicARN
     */
    public function createTopic(string $name): string;

    /**
     * Delete an SNS topic
     *
     * @link http://docs.amazonwebservices.com/sns/latest/api/API_DeleteTopic.html
     * @param string $topicArn
     * @return bool
     */
    public function deleteTopic(string $topicArn): bool;

    /**
     * Get the attributes of a topic like owner, ACL, display name
     *
     * @link http://docs.amazonwebservices.com/sns/latest/api/API_GetTopicAttributes.html
     * @param string $topicArn
     * @return array
     */
    public function getTopicAttributes(string $topicArn): array;

    /**
     * List subscriptions that user is subscribed to
     *
     * @link http://docs.amazonwebservices.com/sns/latest/api/API_ListSubscriptions.html
     * @param string|null $nextToken [optional] Token to retrieve next page of results
     * @return array
     */
    public function listSubscriptions(?string $nextToken = null): array;

    /**
     * List subscribers to a topic
     *
     * @link http://docs.amazonwebservices.com/sns/latest/api/API_ListSubscriptionsByTopic.html
     * @param string $topicArn
     * @param string|null $nextToken [optional] Token to retrieve next page of results
     * @return array
     */
    public function listSubscriptionsByTopic(string $topicArn, ?string $nextToken = null): array;

    /**
     * List SNS topics
     *
     * @link http://docs.amazonwebservices.com/sns/latest/api/API_ListTopics.html
     * @param string|null $nextToken [optional] Token to retrieve next page of results
     * @return array
     */
    public function listTopics(?string $nextToken = null): array;

    /**
     * Publish a message to a topic
     *
     * @link http://docs.amazonwebservices.com/sns/latest/api/API_Publish.html
     * @param string $topicArn
     * @param string $message
     * @param string $subject [optional] Used when sending emails
     * @param string $messageStructure [optional] Used when you want to send a different message for each protocol.If you set MessageStructure to json, the value of the Message parameter must: be a syntactically valid JSON object; and contain at least a top-level JSON key of "default" with a value that is a string.
     * @return string
     */
    public function publish(
        string $topicArn,
        string$message,
        string $subject = '',
        string $messageStructure = ''
    ): string;

    /**
     * Remove a set of permissions identified by topic and label that was used when creating permissions
     *
     * @link http://docs.amazonwebservices.com/sns/latest/api/API_RemovePermission.html
     * @param string $topicArn
     * @param string $label
     * @return bool
     */
    public function removePermission(string $topicArn, string $label): bool;

    /**
     * Set a single attribute on a topic
     *
     * @link http://docs.amazonwebservices.com/sns/latest/api/API_SetTopicAttributes.html
     * @param string $topicArn
     * @param string $attrName
     * @param mixed $attrValue
     * @return bool
     */
    public function setTopicAttributes(string $topicArn, string $attrName, $attrValue): bool;

    /**
     * Subscribe to a topic
     *
     * @link http://docs.amazonwebservices.com/sns/latest/api/API_Subscribe.html
     * @param string $topicArn
     * @param string $protocol - http/https/email/email-json/sms/sqs
     * @param string $endpoint
     * @return string $SubscriptionArn
     */
    public function subscribe(string $topicArn, string $protocol, string $endpoint): string;

    /**
     * Unsubscribe a user from a topic
     *
     * @link http://docs.amazonwebservices.com/sns/latest/api/API_Unsubscribe.html
     * @param string $subscriptionArn
     * @return bool
     */
    public function unsubscribe(string $subscriptionArn): bool;

    /**
     * Create Platform endpoint
     *
     * @link http://docs.aws.amazon.com/sns/latest/api/API_CreatePlatformEndpoint.html
     * @param string $platformApplicationArn
     * @param string $token
     * @param string $userData
     * @return string
     */
    public function createPlatformEndpoint(
        string $platformApplicationArn,
        string $token,
        ?string $userData = null
    ): string;

    /**
     * Delete endpoint
     *
     * @link http://docs.aws.amazon.com/sns/latest/api/API_DeleteEndpoint.html
     * @param string $deviceArn
     * @return bool
     */
    public function deleteEndpoint(string $deviceArn): bool;

    /**
     * Publish a message to an Endpoint
     *
     * @link http://docs.amazonwebservices.com/sns/latest/api/API_Publish.html
     * @param string $deviceArn
     * @param string $message
     * @return string
     */
    public function publishToEndpoint(string $deviceArn, string $message): string;
}
