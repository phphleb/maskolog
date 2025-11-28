<?php

declare(strict_types=1);

namespace MaskologLoggerTests\Monolog\Units;

use ArrayObject;
use Maskolog\Enums\ClassType;
use stdClass;
use WeakReference;

trait MixedValuesProviderTrait
{
    public static function mixedValues(): array
    {
        $fh       = fopen('php://memory', 'r+');
        $closed   = (function () { $r = fopen('php://memory', 'r'); fclose($r); return $r; })();
        $gen      = (function () { yield 1; })();
        $ao       = new ArrayObject([1,2,3]);
        $std      = new stdClass();
        $invokable = new class {
            public function __invoke(): string
            { return 'ok'; }
            public function __toString() { return 'str'; }
        };

        $values = [
            'null'                 => null,
            'false'                => false,
            'true'                 => true,
            'int 0'                => 0,
            'int pos'              => 123,
            'int neg'              => -7,
            'float 0'              => 0.0,
            'float'                => 3.14,
            'INF'                  => INF,
            'empty string'         => '',
            'string 0'             => '0',
            'numeric string'       => '1234567890',
            'alpha string'         => 'abc',
            'empty array'          => [],
            'indexed array'        => [1,2,3],
            'assoc array'          => ['a'=>1,'b'=>2],
            'nested array'         => ['x'=>[1,2]],
            'resource'             => $fh,
            'closed resource'      => $closed,
            'stdClass'             => $std,
            'ArrayObject'          => $ao,
            'Generator'            => $gen,
            'Closure'              => function () { return true; },
            'Invokable object'     => $invokable,
            'callable string'      => 'strlen',
            'callable array'       => [$ao, 'getArrayCopy'],
        ];

        if (class_exists('WeakReference')) {
            $o = new stdClass();
            $values['WeakReference'] = WeakReference::create($o);
        }

        $values['enum'] = ClassType::ANONYMOUS;

        return array_map(function ($v) { return [$v]; }, $values);
    }
}