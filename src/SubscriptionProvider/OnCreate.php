<?php declare(strict_types=1);

namespace MateuszMesek\DocumentDataCatalogCategoryMview\SubscriptionProvider;

use MateuszMesek\DocumentDataCatalogProductMview\SubscriptionProvider\OnCreate\Generator;
use MateuszMesek\DocumentDataIndexMviewApi\SubscriptionProviderInterface;
use Traversable;

class OnCreate implements SubscriptionProviderInterface
{
    public function get(array $context): Traversable
    {
        yield '*' => [
            'onCreate' => [
                'id' => 'onCreate',
                'type' => Generator::class,
                'arguments' => []
            ]
        ];
    }
}
