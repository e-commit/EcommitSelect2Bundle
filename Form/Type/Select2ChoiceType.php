<?php
/**
 * This file is part of the EcommitSelect2Bundle package.
 *
 * (c) E-commit <contact@e-commit.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ecommit\Select2Bundle\Form\Type;

class Select2ChoiceType extends AbstractSelect2Type
{
    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'choice';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'ecommit_javascript_select2choice';
    }
} 