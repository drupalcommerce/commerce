<?php

namespace Drupal\commerce_tax;

use CommerceGuys\Tax\Repository\TaxTypeRepositoryInterface;

/**
 * Manages tax types based on JSON definitions.
 */
class TaxTypeRepository implements TaxTypeRepositoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function get($id)
    {
        return entity_load('commerce_tax_type', $id);
    }

    /**
     * {@inheritdoc}
     */
    public function getAll()
    {
        return entity_load_multiple('commerce_tax_type');
    }

}
