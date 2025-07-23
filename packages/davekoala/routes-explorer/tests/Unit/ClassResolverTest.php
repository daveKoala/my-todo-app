<?php

namespace DaveKoala\RoutesExplorer\Tests\Unit;

use DaveKoala\RoutesExplorer\Explorer\ClassResolver;
use DaveKoala\RoutesExplorer\Tests\TestCase;
use ReflectionClass;

class ClassResolverTest extends TestCase
{
    private ClassResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resolver = new ClassResolver();
    }

    /** @test */
    public function it_can_check_if_class_exists()
    {
        // Test with existing Laravel class
        $this->assertTrue($this->resolver->classExists('Illuminate\Http\Request'));
        
        // Test with non-existing class
        $this->assertFalse($this->resolver->classExists('App\NonExistentClass'));
    }

    /** @test */
    public function it_can_get_reflection_for_existing_class()
    {
        $reflection = $this->resolver->getReflection('Illuminate\Http\Request');
        
        $this->assertInstanceOf(ReflectionClass::class, $reflection);
        $this->assertEquals('Illuminate\Http\Request', $reflection->getName());
    }

    /** @test */
    public function it_returns_null_for_non_existing_class_reflection()
    {
        $reflection = $this->resolver->getReflection('App\NonExistentClass');
        
        $this->assertNull($reflection);
    }

    /** @test */
    public function it_can_skip_framework_classes()
    {
        // Framework classes should be skipped
        $this->assertTrue($this->resolver->shouldSkipClass('Illuminate\Http\Request'));
        
        // App classes should not be skipped
        $this->assertFalse($this->resolver->shouldSkipClass('App\Http\Controllers\TestController'));
    }

    /** @test */
    public function it_has_proper_namespace_configuration()
    {
        $namespaces = $this->resolver->getNamespaces();
        
        $this->assertIsArray($namespaces);
        $this->assertArrayHasKey('app', $namespaces);
        $this->assertArrayHasKey('controllers', $namespaces);
    }
}