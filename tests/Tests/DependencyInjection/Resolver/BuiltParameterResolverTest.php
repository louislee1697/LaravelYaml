<?php

/*
 * This file is part of the LaravelYaml package.
 *
 * (c) Théo FIDRY <theo.fidry@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fidry\LaravelYaml\Tests\DependencyInjection\Resolver;

use Fidry\LaravelYaml\DependencyInjection\Resolver\BuiltParameterResolver;
use Illuminate\Contracts\Config\Repository as ConfigRepositoryInterface;
use Prophecy\Argument;

/**
 * @covers Fidry\LaravelYaml\DependencyInjection\Resolver\BuiltParameterResolver
 *
 * @author Théo FIDRY <theo.fidry@gmail.com>
 */
class BuiltParameterResolverTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ConfigRepositoryInterface
     */
    private $config;

    public function setUp()
    {
        $configRepositoryProphecy = $this->prophesize(ConfigRepositoryInterface::class);
        $configRepositoryProphecy->has(Argument::any())->willReturn(false);
        $this->config = $configRepositoryProphecy->reveal();
    }

    /**
     * @dataProvider provideParameters
     */
    public function testResolveParameters($config, $parameters, $expected)
    {
        $resolver = new BuiltParameterResolver($parameters, $config);

        foreach ($expected as $parameterName => $expectedValue) {
            $actual = $resolver->resolve($parameters[$parameterName]);
            $this->assertEquals(
                $expectedValue,
                $actual,
                sprintf(
                    '"%s" did not match "%s" for parameter "%s"',
                    var_export($actual, true),
                    var_export($expected[$parameterName], true),
                    $parameterName
                )
            );
        }
    }

    /**
     * @expectedException \Fidry\LaravelYaml\Exception\ParameterNotFoundException
     */
    public function testResolveParametersWithUnexistingParameter()
    {
        $resolver = new BuiltParameterResolver([], $this->config);
        $resolver->resolve('%hello.world%');
    }

    public function provideParameters()
    {
        $configRepositoryProphecy = $this->prophesize(ConfigRepositoryInterface::class);
        $configRepositoryProphecy->has('locale.default')->willReturn(true);
        $configRepositoryProphecy->get('locale.default')->willReturn('en-GB');
        $configRepositoryProphecy->has(Argument::any())->willReturn(false);

        yield [
            $configRepositoryProphecy->reveal(),
            [
                'boolParam' => true,
                'intParam' => 2000,
                'floatParam' => -.89,
                'objectParam' => new \stdClass(),
                'closureParam' => function () { },
                'class' => 'App\Test\Dummy',
                'lang' => [
                    'en',
                    'fr' => [
                        200,
                        '%class%',
                        '%%foo%%',
                    ],
                ],
                'refToClass' => 'App\Test\Dummy',
                'escapedVal1' => '%%dummy%%',
                'escapedVal2' => '%dummy%%',
                'escapedVal3' => '%%dummy%',
                'configValue' => '%locale.default%',
                'envValue' => '%env.test.value%',
            ],
            [
                'boolParam' => true,
                'intParam' => 2000,
                'floatParam' => -.89,
                'objectParam' => new \stdClass(),
                'closureParam' => function () { },
                'class' => 'App\Test\Dummy',
                'lang' => [
                    'en',
                    'fr' => [
                        200,
                        'App\Test\Dummy',
                        '%%foo%%',
                    ],
                ],
                'refToClass' => 'App\Test\Dummy',
                'escapedVal1' => '%%dummy%%',
                'escapedVal2' => '%dummy%%',
                'escapedVal3' => '%%dummy%',
                'configValue' => 'en-GB',
                'envValue' => 'DummyEnvValue',
            ]
        ];
    }
}
