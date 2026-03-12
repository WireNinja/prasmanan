<?php

declare(strict_types=1);

namespace WireNinja\Prasmanan\Support\Config;

use Rector\Config\RectorConfig;
use Rector\Php80\Rector\Class_\AnnotationToAttributeRector;
use Rector\Php80\ValueObject\AnnotationToAttribute;
use Rector\Set\ValueObject\SetList;
use Rector\TypeDeclaration\Rector\ClassMethod\AddVoidReturnTypeWhereNoReturnRector;
use RectorLaravel\Rector\ClassMethod\AddGenericReturnTypeToRelationsRector;
use RectorLaravel\Rector\FuncCall\RemoveDumpDataDeadCodeRector;
use RectorLaravel\Rector\MethodCall\RedirectRouteToToRouteHelperRector;
use RectorLaravel\Set\LaravelLevelSetList;

class RectorConfigFactory
{
    /**
     * @return RectorConfig
     */
    public static function configure(string $basePath)
    {
        return RectorConfig::configure()
            ->withPaths([
                $basePath.'/app',
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
