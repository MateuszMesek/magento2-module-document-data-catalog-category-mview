<?php declare(strict_types=1);

namespace MateuszMesek\DocumentDataCatalogCategoryMview\SubscriptionProvider\Attribute;

use InvalidArgumentException;
use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Framework\DB\Ddl\Trigger;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Store\Model\ResourceModel\Store as StoreResource;
use Magento\Store\Model\StoreDimensionProvider;
use MateuszMesek\DocumentDataIndexMview\Data\SubscriptionFactory;
use Traversable;

class Generator
{
    private MetadataPool $metadataPool;
    private EavConfig $eavConfig;
    private StoreResource $storeResource;
    private SubscriptionFactory $subscriptionFactory;

    public function __construct(
        MetadataPool        $metadataPool,
        EavConfig           $eavConfig,
        StoreResource       $storeResource,
        SubscriptionFactory $subscriptionFactory
    )
    {
        $this->metadataPool = $metadataPool;
        $this->eavConfig = $eavConfig;
        $this->storeResource = $storeResource;
        $this->subscriptionFactory = $subscriptionFactory;
    }

    public function generate(string $code): Traversable
    {
        $metadata = $this->metadataPool->getMetadata(CategoryInterface::class);

        $attribute = $this->eavConfig->getAttribute($metadata->getEavEntityType(), $code);

        if (!$attribute) {
            throw new InvalidArgumentException("Attribute '$code' not found");
        }

        $storeTable = $this->storeResource->getMainTable();
        $storeDimensionName = StoreDimensionProvider::DIMENSION_NAME;

        foreach (Trigger::getListOfEvents() as $event) {
            switch ($event) {
                case Trigger::EVENT_INSERT:
                case Trigger::EVENT_UPDATE:
                    $prefix = 'NEW';
                    break;

                case Trigger::EVENT_DELETE:
                    $prefix = 'OLD';
                    break;

                default:
                    throw new InvalidArgumentException("Trigger event '$event' is unsupported");
            }

            if ($attribute->isStatic()) {
                $condition = null;
                $rows = <<<SQL
                    SELECT $prefix.{$metadata->getIdentifierField()} AS document_id,
                           NULL AS node_path,
                           JSON_SET('{}', '$.$storeDimensionName', store.store_id) AS dimensions
                    FROM $storeTable AS store
                    WHERE store.store_id != 0
                SQL;
            } else {
                $condition = "$prefix.attribute_id = {$attribute->getAttributeId()}";
                $rows = <<<SQL
                    SELECT product.{$metadata->getIdentifierField()} AS document_id,
                           NULL AS node_path,
                           JSON_SET('{}', '$.$storeDimensionName', store.store_id) AS dimensions
                    FROM {$metadata->getEntityTable()} AS product
                    CROSS JOIN $storeTable AS store
                        ON store.store_id != 0
                    WHERE product.{$metadata->getLinkField()} = $prefix.{$metadata->getLinkField()}
                      AND IF($prefix.store_id = 0, 1, store.store_id = $prefix.store_id)
                SQL;
            }

            yield $this->subscriptionFactory->create([
                'tableName' => $attribute->getBackendTable(),
                'triggerEvent' => $event,
                'condition' => $condition,
                'rows' => $rows
            ]);
        }
    }
}
