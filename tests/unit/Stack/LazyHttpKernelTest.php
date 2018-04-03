<?php

namespace Stack;

if (!class_exists('PHPUnit_Framework_TestCase') && class_exists('PHPUnit\Framework\TestCase')) {
    class_alias('PHPUnit\Framework\TestCase', 'PHPUnit_Framework_TestCase');
}

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class LazyHttpKernelTest extends \PHPUnit_Framework_TestCase
{
    /** @test */
    public function handleShouldInvokeFactory()
    {
        $factory = function () {
            return $this->createHelloKernel();
        };

        $kernel = new LazyHttpKernel($factory);

        $request = Request::create('/');
        $response = $kernel->handle($request);
        $this->assertEquals(new Response('Hello World!'), $response);
    }

    /** @test */
    public function handleShouldInvokeFactoryJustOnce()
    {
        $factoryCalled = 0;

        $factory = function () use (&$factoryCalled) {
            $factoryCalled++;
            return $this->createHelloKernel();
        };

        $kernel = new LazyHttpKernel($factory);

        $request = Request::create('/');
        $response = $kernel->handle($request);

        $this->assertSame(1, $factoryCalled);
    }

    /** @test */
    public function handleShouldReuseCreatedApp()
    {
        $factoryCalled = 0;

        $factory = function () use (&$factoryCalled) {
            $factoryCalled++;
            return $this->createHelloKernel();
        };

        $kernel = new LazyHttpKernel($factory);

        $request = Request::create('/');
        $response = $kernel->handle($request);
        $response = $kernel->handle($request);

        $this->assertSame(1, $factoryCalled);
    }

    public function testWithUrlMap()
    {
        $fooFactoryCalled = 0;
        $barFactoryCalled = 0;

        $foo = new LazyHttpKernel(function () use (&$fooFactoryCalled) {
            $fooFactoryCalled++;
            return $this->createKernel('foo');
        });

        $bar = new LazyHttpKernel(function () use (&$barFactoryCalled) {
            $barFactoryCalled++;
            return $this->createKernel('bar');
        });

        $app = $this->createHelloKernel();
        $kernel = new UrlMap($app, [
            '/foo' => $foo,
            '/bar' => $bar,
        ]);

        $request = Request::create('/foo');
        $response = $kernel->handle($request);

        $this->assertSame(1, $fooFactoryCalled);
        $this->assertSame(0, $barFactoryCalled);
    }

    public function testShortcutFunction()
    {
        $kernel = lazy(function () {
            return $this->createHelloKernel();
        });

        $this->assertInstanceOf('Stack\LazyHttpKernel', $kernel);
        $this->assertSame('Hello World!', $kernel->handle(Request::create('/'))->getContent());
    }

    private function createHelloKernel()
    {
        return $this->createKernel('Hello World!');
    }

    private function createKernel($body)
    {
        return new CallableHttpKernel(function (Request $request) use ($body) {
            return new Response($body);
        });
    }
}
