<?php declare(strict_types=1);

namespace MateuszMesek\DocumentDataCatalogCategoryMview\SubscriptionProvider\OnCreate;

use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Framework\DB\Ddl\Trigger;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Store\Model\ResourceModel\Store as StoreResource;
use Magento\Store\Model\StoreDimensionProvider;
use MateuszMesek\DocumentDataIndexMview\Data\SubscriptionFactory;
use Traversable;

class Generator
{
    private MetadataPool $metadataPool;
    private StoreResource $storeResource;
    private SubscriptionFactory $subscriptionFactory;

    public function __construct(
        MetadataPool        $metadataPool,
        StoreResource       $storeResource,
        SubscriptionFactory $subscriptionFactory
    )
    {
        $this->metadataPool = $metadataPool;
        $this->storeResource = $storeResource;
        $this->subscriptionFactory = $subscriptionFactory;
    }

    public function generate(): Traversable
    {
        $metadata = $this->metadataPool->getMetadata(CategoryInterface::class);

        $storeTable = $this->storeResource->getMainTable();
        $storeDimensionName = StoreDimensionProvider::DIMENSION_NAME;

        foreach (Trigger::getListOfEvents() as $event) {
            if ($event !== Trigger::EVENT_INSERT) {
                continue;
            }

            yield $this->subscriptionFactory->create([
                'tableName' => $metadata->getEntityTable(),
                'triggerEvent' => $event,
                'rows' => <<<SQL
                    SELECT NEW.{$metadata->getIdentifierField()} AS document_id,
                           NULL AS node_path,
                           JSON_SET('{}', '$.$storeDimensionName', store.store_id) AS dimensions
                    FROM $storeTable AS store
                    WHERE store.store_id != 0
                SQL
            ]);
        }
    }
}
