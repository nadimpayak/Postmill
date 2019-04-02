<?php

namespace App\DBAL\Type;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

final class TsvectorType extends Type {
    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform) {
        return 'TSVECTOR';
    }

    public function getName() {
        return 'tsvector';
    }

    public function convertToDatabaseValueSQL($sqlExpr, AbstractPlatform $platform): string {
        return sprintf('to_tsvector(%s)', $sqlExpr);
    }
}
