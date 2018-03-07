<?php

declare(strict_types=1);

namespace ChrisHarrison\SnsClient;

interface ExtendedSnsClient extends SnsClient
{
    /**
     * Publish a message to a topic - if the topic doesn't exist, create it.
     *
     * @param string $topicArn
     * @param string $message
     * @param string $subject [optional] Used when sending emails
     * @param string $messageStructure [optional] Used when you want to send a different message for each protocol.If you set MessageStructure to json, the value of the Message parameter must: be a syntactically valid JSON object; and contain at least a top-level JSON key of "default" with a value that is a string.
     * @return string
     */
    public function publishAndCreateTopicIfNeeded(string $topicArn, string $message, string $subject = '', string $messageStructure = ''): string;
}
