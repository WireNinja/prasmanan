<?php

declare(strict_types=1);

namespace WireNinja\Prasmanan\Support\Config;

use Rector\Config\RectorConfig;
use Rector\TypeDeclaration\Rector\ClassMethod\AddVoidReturnTypeWhereNoReturnRector;
use Rector\Php80\Rector\Class_\AnnotationToAttributeRector;
use Rector\Php80\ValueObject\AnnotationToAttribute;
use RectorLaravel\Set\LaravelLevelSetList;
use RectorLaravel\Set\LaravelSetList;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;
use RectorLaravel\Rector\ClassMethod\AddGenericReturnTypeToRelationsRector;
use RectorLaravel\Rector\FuncCall\RemoveDumpDataDeadCodeRector;
use RectorLaravel\Rector\MethodCall\RedirectRouteToToRouteHelperRector;

class RectorConfigFactory
{
    /**
     * @return \Rector\Config\RectorConfig
     */
    public static function configure(string $basePath)
    {
        return RectorConfig::configure()
            ->withPaths([
                $basePath . '/app',
            ])
            ->withPhpSets(php85: true)
            ->withSets([
                LaravelLevelSetList::UP_TO_LARAVEL_110,
                SetList::CODE_QUALITY,
                SetList::DEAD_CODE,
                SetList::EARLY_RETURN,
                SetList::TYPE_DECLARATION,
                SetList::PRIVATIZATION,
            ])
            ->withRules([
                AddVoidReturnTypeWhereNoReturnRector::class,
                AddGenericReturnTypeToRelationsRector::class,
                RemoveDumpDataDeadCodeRector::class,
                RedirectRouteToToRouteHelperRector::class,
            ])
            ->withSkip([
                AnnotationToAttributeRector::class => [
                    new AnnotationToAttribute('property'),
                ],
            ]);
    }
}
