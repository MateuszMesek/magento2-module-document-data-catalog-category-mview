<?php declare(strict_types=1);

namespace MateuszMesek\DocumentDataCatalogCategoryMview\Model\SubscriptionProvider;

use MateuszMesek\DocumentDataCatalogCategoryMview\Model\SubscriptionProvider\Hierarchy\Generator;
use MateuszMesek\DocumentDataIndexMviewApi\Model\SubscriptionProviderInterface;
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
