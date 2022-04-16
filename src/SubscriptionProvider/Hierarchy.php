<?php declare(strict_types=1);

namespace MateuszMesek\DocumentDataCatalogCategoryMview\SubscriptionProvider;

use MateuszMesek\DocumentDataCatalogCategoryMview\SubscriptionProvider\Hierarchy\Generator;
use MateuszMesek\DocumentDataIndexMviewApi\SubscriptionProviderInterface;
use Traversable;

class Hierarchy implements SubscriptionProviderInterface
{
    public function get(array $context): Traversable
    {
        yield '*' => [
            'category_hierarchy' => [
                'id' => 'category_hierarchy',
                'type' => Generator::class,
                'arguments' => []
            ]
        ];
    }
}
