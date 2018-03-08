<?php

declare(strict_types=1);

namespace ChrisHarrison\SnsClient;

use ChrisHarrison\SnsClient\Exceptions\ApiException;
use ChrisHarrison\SnsClient\Exceptions\SnsException;
use function http_build_query;
use InvalidArgumentException;

final class DefaultSnsClient implements SnsClient
{
    private $accessKey;
    private $secretKey;
    private $protocol;
    private $endpoint;

    public function __construct(
        string $accessKey,
        string $secretKey,
        string $protocol,
        string $endpoint
    ) {
        $this->accessKey = $accessKey;
        $this->secretKey = $secretKey;
        $this->protocol = $protocol;
        $this->endpoint = $endpoint;
    }

    public function getAccessKey(): string
    {
        return $this->accessKey;
    }

    public function getSecretKey(): string
    {
        return $this->secretKey;
    }

    public function getProtocol(): string
    {
        return $this->protocol;
    }

    public function getEndpoint(): string
    {
        return $this->endpoint;
    }

    public function addPermission(string $topicArn, string $label, array $permissions = []): bool
    {
        if (empty($topicArn) || empty($label)) {
            throw new InvalidArgumentException('Must supply TopicARN and a Label for this permission');
        }
        // Add standard params as normal
        $params = [
            'TopicArn' => $topicArn,
            'Label' => $label,
        ];

        // Compile permissions into separate sequential arrays
        $memberFlatArray = [];
        $permissionFlatArray = [];
        foreach ($permissions as $member => $permission) {
            if (is_array($permission)) {
                // Array of permissions
                foreach ($permission as $singlePermission) {
                    $memberFlatArray[] = $member;
                    $permissionFlatArray[] = $singlePermission;
                }
            } else {
                // Just a single permission
                $memberFlatArray[] = $member;
                $permissionFlatArray[] = $permission;
            }
        }

        // Dummy check
        if (count($memberFlatArray) !== count($permissionFlatArray)) {
            // Something went wrong
            throw new InvalidArgumentException('Mismatch of permissions to users');
        }

        // Finally add to params
        for ($x = 1; $x <= count($memberFlatArray); $x++) {
            $params['ActionName.member.' . $x] = $permissionFlatArray[$x];
            $params['AWSAccountID.member.' . $x] = $memberFlatArray[$x];
        }

        // Finally send request
        $this->request('AddPermission', $params);
        return true;
    }

    public function confirmSubscription(
        string $topicArn,
        string $token,
        ?bool $authenticateOnUnsubscribe = null
    ): string {
        if (empty($topicArn) || empty($token)) {
            throw new InvalidArgumentException('Must supply a TopicARN and a Token to confirm subscription');
        }
        $params = [
            'TopicArn' => $topicArn,
            'Token' => $token,
        ];
        if (!is_null($authenticateOnUnsubscribe)) {
            $params['AuthenticateOnUnsubscribe'] = $authenticateOnUnsubscribe;
        }
        $resultXml = $this->request('ConfirmSubscription', $params);
        return strval($resultXml->ConfirmSubscriptionResult->SubscriptionArn);
    }

    public function createTopic(string $name): string
    {
        if (empty($name)) {
            throw new InvalidArgumentException('Must supply a Name to create topic');
        }
        $resultXml = $this->request('CreateTopic', ['Name' => $name]);
        return strval($resultXml->CreateTopicResult->TopicArn);
    }

    public function deleteTopic(string $topicArn): bool
    {
        if (empty($topicArn)) {
            throw new InvalidArgumentException('Must supply a TopicARN to delete a topic');
        }
        $this->request('DeleteTopic', ['TopicArn' => $topicArn]);
        return true;
    }

    public function getTopicAttributes(string $topicArn): array
    {
        if (empty($topicArn)) {
            throw new InvalidArgumentException('Must supply a TopicARN to get topic attributes');
        }
        $resultXml = $this->request('GetTopicAttributes', ['TopicArn' => $topicArn]);
        // Get attributes
        $attributes = $resultXml->GetTopicAttributesResult->Attributes->entry;
        // Unfortunately cannot use processXmlToArray here, so process manually
        $returnArray = [];
        // Process into array
        foreach ($attributes as $attribute) {
            // Store attribute key as array key
            $returnArray[strval($attribute->key)] = strval($attribute->value);
        }
        return $returnArray;
    }

    public function listSubscriptions(?string $nextToken = null): array
    {
        $params = [];
        if (!is_null($nextToken)) {
            $params['NextToken'] = $nextToken;
        }
        $resultXml = $this->request('ListSubscriptions', $params);
        // Get subscriptions
        $subs = $resultXml->ListSubscriptionsResult->Subscriptions->member;
        $return = ['members' => $this->processXmlToArray($subs)];
        if (isset($resultXml->ListSubscriptionsResult->NextToken)) {
            $return['nextToken'] = strval($resultXml->ListSubscriptionsResult->NextToken);
        }
        return $return;
    }

    public function listSubscriptionsByTopic(string $topicArn, ?string $nextToken = null): array
    {
        if (empty($topicArn)) {
            throw new InvalidArgumentException('Must supply a TopicARN to show subscriptions to a topic');
        }
        $params = [
            'TopicArn' => $topicArn,
        ];
        if (!is_null($nextToken)) {
            $params['NextToken'] = $nextToken;
        }
        $resultXml = $this->request('ListSubscriptionsByTopic', $params);
        // Get subscriptions
        $subs = $resultXml->ListSubscriptionsByTopicResult->Subscriptions->member;
        $return = ['members' => $this->processXmlToArray($subs)];
        if (isset($resultXml->ListSubscriptionsByTopicResult->NextToken)) {
            $return['nextToken'] = strval($resultXml->ListSubscriptionsByTopicResult->NextToken);
        }
        return $return;
    }

    public function listTopics(?string $nextToken = null): array
    {
        $params = [];
        if (!is_null($nextToken)) {
            $params['NextToken'] = $nextToken;
        }
        $resultXml = $this->request('ListTopics', $params);
        // Get Topics
        $topics = $resultXml->ListTopicsResult->Topics->member;
        return $this->processXmlToArray($topics);
    }

    public function publish(
        string $topicArn,
        string$message,
        string $subject = '',
        string $messageStructure = ''
    ): string {
        if (empty($topicArn) || empty($message)) {
            throw new InvalidArgumentException('Must supply a TopicARN and Message to publish to a topic');
        }
        $params = [
            'TopicArn' => $topicArn,
            'Message' => $message,
        ];
        if (!empty($subject)) {
            $params['Subject'] = $subject;
        }
        if (!empty($messageStructure)) {
            $params['MessageStructure'] = $messageStructure;
        }
        $resultXml = $this->request('Publish', $params);
        return strval($resultXml->PublishResult->MessageId);
    }

    public function removePermission(string $topicArn, string $label): bool
    {
        if (empty($topicArn) || empty($label)) {
            throw new InvalidArgumentException('Must supply a TopicARN and Label to remove a permission');
        }
        $this->request('RemovePermission', ['Label' => $label]);
        return true;
    }

    public function setTopicAttributes(string $topicArn, string $attrName, $attrValue): bool
    {
        if (empty($topicArn) || empty($attrName) || empty($attrValue)) {
            throw new InvalidArgumentException(
                'Must supply a TopicARN, AttributeName and AttributeValue to set a topic attribute'
            );
        }
        $this->request('SetTopicAttributes', [
            'TopicArn' => $topicArn,
            'AttributeName' => $attrName,
            'AttributeValue' => $attrValue,
        ]);
        return true;
    }

    public function subscribe(string $topicArn, string $protocol, string $endpoint): string
    {
        if (empty($topicArn) || empty($protocol) || empty($endpoint)) {
            throw new InvalidArgumentException('Must supply a TopicARN, Protocol and Endpoint to subscribe to a topic');
        }
        $response = $this->request('Subscribe', [
            'TopicArn' => $topicArn,
            'Protocol' => $protocol,
            'Endpoint' => $endpoint,
        ]);
        return strval($response->SubscribeResult->SubscriptionArn);
    }

    public function unsubscribe(string $subscriptionArn): bool
    {
        if (empty($subscriptionArn)) {
            throw new InvalidArgumentException('Must supply a SubscriptionARN to unsubscribe from a topic');
        }
        $this->request('Unsubscribe', ['SubscriptionArn' => $subscriptionArn]);
        return true;
    }

    public function createPlatformEndpoint(
        string $platformApplicationArn,
        string $token,
        ?string $userData = null
    ): string {
        if (empty($platformApplicationArn) || empty($token)) {
            throw new InvalidArgumentException(
                'Must supply a PlatformApplicationArn & Token to create platform endpoint'
            );
        }
        $attributes = [
            'PlatformApplicationArn' => $platformApplicationArn,
            'Token' => $token,
        ];
        if (!empty($userData)) {
            $attributes['CustomUserData'] = $userData;
        }
        $response = $this->request('CreatePlatformEndpoint', $attributes);
        return strval($response->CreatePlatformEndpointResult->EndpointArn);
    }

    public function deleteEndpoint(string $deviceArn): bool
    {
        if (empty($deviceArn)) {
            throw new InvalidArgumentException('Must supply a DeviceARN to remove platform endpoint');
        }
        $this->request('DeleteEndpoint', [
            'EndpointArn' => $deviceArn,
        ]);
        return true;
    }

    public function publishToEndpoint(string $deviceArn, string $message): string
    {
        if (empty($deviceArn) || empty($message)) {
            throw new InvalidArgumentException('Must supply DeviceArn and Message');
        }
        $resultXml = $this->request('Publish', [
                'TargetArn' => $deviceArn,
                'Message' => $message,
        ]);
        return strval($resultXml->PublishResult->MessageId);
    }

    private function request(string $action, array $params = [])
    {
        $params['Action'] = $action;
        $request = $this->signedRequest('GET', '', $params);

        // Instantiate cUrl and perform request
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $request);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $output = curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);

        // Load XML response
        $xmlResponse = simplexml_load_string((string) $output);

        // Check return code
        if ($this->checkGoodResponse($info['http_code']) === false) {
            $this->parseXmlErrors($xmlResponse, $info['http_code']);
        }

        return $xmlResponse;
    }

    private function parseXmlErrors(\SimpleXMLElement $xml, int $errorCode)
    {
        if (isset($xml->Errors) && isset($xml->Errors->Error)) {
            $error = $xml->Errors->Error;
        }

        if (isset($xml->Error)) {
            $error = $xml->Error;
        }

        if (empty($error)) {
            throw new ApiException('There was a problem executing this request', $errorCode);
        }

        throw new SnsException(
            strval($error->Code) . ': ' . strval($error->Message),
            $errorCode
        );
    }

    private function signedRequest(string $httpMethod, string $uri, array $payload): string
    {
        $payload['AWSAccessKeyId'] = $this->accessKey;
        $payload['Timestamp'] = gmdate('Y-m-d\TH:i:s.000\Z');
        $payload['SignatureVersion'] = 2;
        $payload['SignatureMethod'] = 'HmacSHA256';

        uksort($payload, 'strnatcmp');
        $queryString = '';
        foreach ($payload as $key => $val) {
            $queryString .= "&{$key}=".rawurlencode((string) $val);
        }
        $queryString = substr($queryString, 1);

        $requestString = $httpMethod . "\n"
            . $this->endpoint . "\n"
            . $uri . "/\n"
            . $queryString;

        $payload['Signature'] = base64_encode(
            hash_hmac('sha256', $requestString, $this->secretKey, true)
        );

        return $this->protocol . $this->endpoint . '/?' . http_build_query($payload);
    }

    /**
     * Check the curl response code - anything in 200 range
     *
     * @param int $code
     * @return bool
     */
    private function checkGoodResponse($code): bool
    {
        return floor($code / 100) == 2;
    }

    /**
     * Transform the standard AmazonSNS XML array format into a normal array
     *
     * @param \SimpleXMLElement $xmlArray
     * @return array
     */
    private function processXmlToArray(\SimpleXMLElement $xmlArray): array
    {
        $returnArray = [];
        // Process into array
        foreach ($xmlArray as $xmlElement) {
            $elementArray = [];
            // Loop through each element
            foreach ($xmlElement as $key => $element) {
                // Use strval() to make sure no SimpleXMLElement objects remain
                $elementArray[$key] = strval($element);
            }
            // Store array of elements
            $returnArray[] = $elementArray;
        }
        return $returnArray;
    }
}
