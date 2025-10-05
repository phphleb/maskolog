<?php

declare(strict_types=1);

namespace MaskologLoggerTests\Monolog\Units;

use Maskolog\Enums\UrlMaskingStatus;
use Maskolog\Processors\Masking\Context\UrlMaskingProcessor;
use PHPUnit\Framework\TestCase;

final class UrlMaskingProcessorTest extends TestCase
{
    use CreateLogRecordTrait;

    /**
     * @dataProvider urlProvider
     */
    public function testAddMask(string $originalUrl, string $expectedUrl, array $maskingRules = null): void
    {
        $processor = new UrlMaskingProcessor(['url']);
        if ($maskingRules !== null) {
            $processor->useMaskedParams($maskingRules);
        }
        $record = $this->createLogRecord(['context' => ['url' => $originalUrl]]);
        $maskedData = ['context' => $processor($record)->context];
        $this->assertEquals(['context' => ['url' => $expectedUrl]], $maskedData);
    }

    public function urlProvider(): array
    {
        $replacement = UrlMaskingStatus::REPLACEMENT->value;

        return [
            'empty_url' => ['', ''],
            'url_without_params' => ['https://example.com', 'https://example.com'],
            'url_with_params' => [
                'https://example.com?param1=value1&param2=value2',
                "https://example.com?param1={$replacement}&param2={$replacement}",
            ],
            'masked_params' => [
                'https://example.com?password=123&token=456&other=data',
                "https://example.com?password={$replacement}&token={$replacement}&other={$replacement}",
                ['url' => ['password', 'token', 'other']]
            ],
            'partially_masked_params' => [
                'https://example.com?user=admin&password=secret',
                "https://example.com?user=admin&password={$replacement}",
                ['url' => ['password']]
            ],
            'parameters_with_label' => [
                'https://example.com?user=admin&password=secret#test_label',
                "https://example.com?user=admin&password={$replacement}#test_label",
                ['url' => ['password']]
            ],
            'url_with_encoded_params' => [
                'https://example.com?param%201=value%201&param2=value2',
                "https://example.com?param%201={$replacement}&param2={$replacement}",
                []
            ],
            'url_with_special_characters' => [
                'https://example.com?param=va!ue&param2=v@lue2',
                "https://example.com?param={$replacement}&param2={$replacement}",
                []
            ],
            'url_with_numeric_key' => [
                'https://example.com?value&param=va!ue&param2=v@lue2',
                "https://example.com?{$replacement}&param={$replacement}&param2={$replacement}",
                []
            ],
            'url_with_masked_numeric_key' => [
                'https://example.com?value1&value2&value3&param=va!ue&param2=v@lue2&value4',
                "https://example.com?{$replacement}&value2&{$replacement}&param=va!ue&param2={$replacement}&{$replacement}",
                ['url' => [0, 2, 'param2', 5]]
            ],
            'url_with_masked_numeric_key_and_tag' => [
                'https://example.com?value1&value2&value3&param=va!ue&param2=v@lue2&value4#test',
                "https://example.com?value1&{$replacement}&value3&param=va!ue&param2={$replacement}&{$replacement}#test",
                ['url' => [1, 'param2', 5]]
            ],
            'url_with_http' => [
                'http://example.com?value&param=va!ue&param2=v@lue2',
                "http://example.com?{$replacement}&param={$replacement}&param2={$replacement}",
                []
            ],
            'url_without_https' => [
                'example.com?value&param=va!ue&param2=v@lue2',
                "example.com?{$replacement}&param={$replacement}&param2={$replacement}",
                []
            ],
            'url_with_unicode' => [
                'https://пример.рф?параметр=значение&пароль=секрет12345',
                "https://пример.рф?параметр=значение&пароль={$replacement}",
                ['url' => ['пароль']]
            ],
            'url_with_ip' => [
                'http://127.0.0.1:8000?value&param=va!ue&param2=v@lue2',
                "http://127.0.0.1:8000?{$replacement}&param={$replacement}&param2={$replacement}",
                []
            ],
            'url_with_user_pass' => [
                'https://user:pass@host.com?value&param=va!ue&param2=v@lue2',
                "https://user:pass@host.com?{$replacement}&param={$replacement}&param2={$replacement}",
                []
            ],
            'url_with_socket' => [
                'ws://example.com/socket?value&param=va!ue&param2=v@lue2',
                "ws://example.com/socket?{$replacement}&param={$replacement}&param2={$replacement}",
                []
            ],
            'url_with_endpoint' => [
                'ws://2001:db8::1/endpoint?value&param=va!ue&param2=v@lue2',
                "ws://2001:db8::1/endpoint?{$replacement}&param={$replacement}&param2={$replacement}",
                []
            ],
            'url_with_data' => [
                'data:text/plain,Hello%20World!?value&param=va!ue&param2=Hello%20World',
                "data:text/plain,Hello%20World!?{$replacement}&param={$replacement}&param2={$replacement}",
                []
            ],
            'url_with_file' => [
                'file:///C:/Windows/param=file.txt?value&param=va!ue&param2=v@lue2',
                "file:///C:/Windows/param=file.txt?{$replacement}&param={$replacement}&param2={$replacement}",
                []
            ],
            'url_with_ftp' => [
                'ftp://ftp.example.com/resource.txt?value&param=va!ue&param2=v@lue2',
                "ftp://ftp.example.com/resource.txt?{$replacement}&param={$replacement}&param2={$replacement}",
                []
            ],
            'url_without_host' => [
                '?value&param=va!ue&param2=v@lue2',
                "?{$replacement}&param={$replacement}&param2={$replacement}",
                []
            ],
            'url_with_tag' => [
                'https://ex.com/path#frag?param=1',
                "https://ex.com/path#frag?param={$replacement}",
                []
            ],
        ];
    }

    public function testVariableSimpleLabels()
    {
        $replacement = UrlMaskingStatus::REPLACEMENT->value;
        $processor = new UrlMaskingProcessor(['url', 'other', 'undefined']);

        $originalUrl = 'https://example.com?param1=value1&param2=value2';
        $maskedUrl = "https://example.com?param1={$replacement}&param2={$replacement}";
        $record = $this->createLogRecord(['context' => ['url' => $originalUrl, 'other' => $originalUrl, 'unaltered' => $originalUrl]]);
        $maskedData = ['context' => $processor($record)->context];
        $this->assertEquals(['context' => ['url' => $maskedUrl, 'other' => $maskedUrl, 'unaltered' => $originalUrl]], $maskedData);
    }
}
