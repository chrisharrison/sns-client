<?php

declare(strict_types=1);

namespace ChrisHarrison\SnsClient;

use ChrisHarrison\SnsClient\Exceptions\ApiException;
use ChrisHarrison\SnsClient\Exceptions\SnsException;
use InvalidArgumentException;

final class DefaultSnsClient implements SnsClient
{
    private $accessKey;
    private $secretKey;
    private $endpoint;

    public function __construct(
        string $accessKey,
        string $secretKey,
        string $endpoint
    ) {
        $this->accessKey = $accessKey;
        $this->secretKey = $secretKey;
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
        $this->_request('AddPermission', $params);
        return true;
    }

    public function confirmSubscription(string $topicArn, string $token, ?bool $authenticateOnUnsubscribe = null): string
    {
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
        $resultXml = $this->_request('ConfirmSubscription', $params);
        return strval($resultXml->ConfirmSubscriptionResult->SubscriptionArn);
    }

    public function createTopic(string $name): string
    {
        if (empty($name)) {
            throw new InvalidArgumentException('Must supply a Name to create topic');
        }
        $resultXml = $this->_request('CreateTopic', ['Name' => $name]);
        return strval($resultXml->CreateTopicResult->TopicArn);
    }

    public function deleteTopic(string $topicArn): bool
    {
        if (empty($topicArn)) {
            throw new InvalidArgumentException('Must supply a TopicARN to delete a topic');
        }
        $this->_request('DeleteTopic', ['TopicArn' => $topicArn]);
        return true;
    }

    public function getTopicAttributes(string $topicArn): array
    {
        if (empty($topicArn)) {
            throw new InvalidArgumentException('Must supply a TopicARN to get topic attributes');
        }
        $resultXml = $this->_request('GetTopicAttributes', ['TopicArn' => $topicArn]);
        // Get attributes
        $attributes = $resultXml->GetTopicAttributesResult->Attributes->entry;
        // Unfortunately cannot use _processXmlToArray here, so process manually
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
        $resultXml = $this->_request('ListSubscriptions', $params);
        // Get subscriptions
        $subs = $resultXml->ListSubscriptionsResult->Subscriptions->member;
        $return = ['members' => $this->_processXmlToArray($subs)];
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
        $resultXml = $this->_request('ListSubscriptionsByTopic', $params);
        // Get subscriptions
        $subs = $resultXml->ListSubscriptionsByTopicResult->Subscriptions->member;
        $return = ['members' => $this->_processXmlToArray($subs)];
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
        $resultXml = $this->_request('ListTopics', $params);
        // Get Topics
        $topics = $resultXml->ListTopicsResult->Topics->member;
        return $this->_processXmlToArray($topics);
    }

    public function publish(string $topicArn, string$message, string $subject = '', string $messageStructure = ''): string
    {
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
        $resultXml = $this->_request('Publish', $params);
        return strval($resultXml->PublishResult->MessageId);
    }

    public function removePermission(string $topicArn, string $label): bool
    {
        if (empty($topicArn) || empty($label)) {
            throw new InvalidArgumentException('Must supply a TopicARN and Label to remove a permission');
        }
        $this->_request('RemovePermission', ['Label' => $label]);
        return true;
    }

    public function setTopicAttributes(string $topicArn, string $attrName, $attrValue): bool
    {
        if (empty($topicArn) || empty($attrName) || empty($attrValue)) {
            throw new InvalidArgumentException('Must supply a TopicARN, AttributeName and AttributeValue to set a topic attribute');
        }
        $this->_request('SetTopicAttributes', [
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
        $response = $this->_request('Subscribe', [
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
        $this->_request('Unsubscribe', ['SubscriptionArn' => $subscriptionArn]);
        return true;
    }

    public function createPlatformEndpoint(string $platformApplicationArn, string $token, ?string $userData = null): string
    {
        if (empty($platformApplicationArn) || empty($token)) {
            throw new InvalidArgumentException('Must supply a PlatformApplicationArn & Token to create platform endpoint');
        }
        $attributes = [
            'PlatformApplicationArn' => $platformApplicationArn,
            'Token' => $token,
        ];
        if (!empty($userData)) {
            $attributes['CustomUserData'] = $userData;
        }
        $response = $this->_request('CreatePlatformEndpoint', $attributes);
        return strval($response->CreatePlatformEndpointResult->EndpointArn);
    }

    public function deleteEndpoint(string $deviceArn): bool
    {
        if (empty($deviceArn)) {
            throw new InvalidArgumentException('Must supply a DeviceARN to remove platform endpoint');
        }
        $this->_request('DeleteEndpoint', [
            'EndpointArn' => $deviceArn,
        ]);
        return true;
    }

    public function publishToEndpoint(string $deviceArn, string $message): string
    {
        if (empty($deviceArn) || empty($message)) {
            throw new InvalidArgumentException('Must supply DeviceArn and Message');
        }
        $resultXml = $this->_request('Publish', [
                'TargetArn' => $deviceArn,
                'Message' => $message,
        ]);
        return strval($resultXml->PublishResult->MessageId);
    }

    private function _request(string $action, array $params = [])
    {
        // Add in required params
        $params['Action'] = $action;
        $params['AWSAccessKeyId'] = $this->accessKey;
        $params['Timestamp'] = gmdate('Y-m-d\TH:i:s.000\Z');
        $params['SignatureVersion'] = 2;
        $params['SignatureMethod'] = 'HmacSHA256';

        // Sort and encode into string
        uksort($params, 'strnatcmp');
        $queryString = '';
        foreach ($params as $key => $val) {
            $queryString .= "&{$key}=".rawurlencode($val);
        }
        $queryString = substr($queryString, 1);

        // Form request string
        $requestString = "GET\n"
            . $this->endpoint."\n"
            . "/\n"
            . $queryString;

        // Create signature - Version 2
        $params['Signature'] = base64_encode(
            hash_hmac('sha256', $requestString, $this->secretKey, true)
        );

        // Finally create request
        $request = $this->endpoint . '/?' . http_build_query($params, '', '&');

        // Instantiate cUrl and perform request
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $request);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $output = curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);

        // Load XML response
        $xmlResponse = simplexml_load_string($output);

        // Check return code
        if ($this->_checkGoodResponse($info['http_code']) === false) {
            // Response not in 200 range
            if (isset($xmlResponse->Error)) {
                // Amazon returned an XML error
                throw new SnsException(strval($xmlResponse->Error->Code) . ': ' . strval($xmlResponse->Error->Message), $info['http_code']);
            } else {
                // Some other problem
                throw new ApiException('There was a problem executing this request', $info['http_code']);
            }
        } else {
            // All good
            return $xmlResponse;
        }
    }

    /**
     * Check the curl response code - anything in 200 range
     *
     * @param int $code
     * @return bool
     */
    private function _checkGoodResponse($code): bool
    {
        return floor($code / 100) == 2;
    }

    /**
     * Transform the standard AmazonSNS XML array format into a normal array
     *
     * @param SimpleXMLElement $xmlArray
     * @return array
     */
    private function _processXmlToArray(SimpleXMLElement $xmlArray): array
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
