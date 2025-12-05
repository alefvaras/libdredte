<?php

declare(strict_types=1);

/**
 * Derafu: Twig - UI Component and Extension Library.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Twig\Provider;

use Derafu\Twig\Cache\CacheItemPool;
use Derafu\Twig\Contract\ComponentProviderInterface;
use InvalidArgumentException;
use Psr\Cache\CacheItemPoolInterface;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use ReflectionException;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

class DirectoryComponentProvider implements ComponentProviderInterface
{
    public function __construct(
        private readonly string $directory,
        private ?CacheItemPoolInterface $cache = null,
        private string $cacheKey = 'derafu_twig_components_dir_%s'
    ) {
        if (!is_dir($directory)) {
            throw new InvalidArgumentException(sprintf(
                'Directory "%s" does not exist.',
                $directory
            ));
        }
        $this->cache = $cache ?? new CacheItemPool();
        $this->cacheKey = sprintf($this->cacheKey, hash('sha256', $directory));
    }

    public function getComponents(): array
    {
        // Si no hay cache, escanear directamente.
        if (!$this->cache) {
            return $this->scanDirectory();
        }

        // Intentar obtener de cache.
        $item = $this->cache->getItem($this->cacheKey);
        if ($item->isHit()) {
            return $item->get();
        }

        // Si no está en cache, escanear y guardar.
        $components = $this->scanDirectory();
        $item->set($components);
        $this->cache->save($item);

        return $components;
    }

    private function scanDirectory(): array
    {
        $components = [];
        $files = $this->findPhpFiles();

        foreach ($files as $file) {
            $class = $this->getClassFromFile($file);
            if ($class && $this->isTwigComponent($class)) {
                $components[] = $class;
            }
        }

        return $components;
    }

    private function findPhpFiles(): array
    {
        $files = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->directory)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $files[] = $file->getRealPath();
            }
        }

        return $files;
    }

    private function getClassFromFile(string $file): ?string
    {
        $content = file_get_contents($file);

        // Obtener namespace.
        $namespace = '';
        if (preg_match('/namespace\s+([^;]+);/', $content, $matches)) {
            $namespace = $matches[1];
        }

        // Obtener nombre de la clase.
        if (preg_match('/class\s+(\w+)/', $content, $matches)) {
            $className = $matches[1];
            $fullClassName = $namespace ? $namespace . '\\' . $className : $className;

            // Verificar que la clase existe y se puede cargar.
            if (class_exists($fullClassName)) {
                return $fullClassName;
            }
        }

        return null;
    }

    private function isTwigComponent(string $class): bool
    {
        try {
            $reflection = new ReflectionClass($class);
            return !empty($reflection->getAttributes(AsTwigComponent::class));
        } catch (ReflectionException $e) {
            return false;
        }
    }
}
