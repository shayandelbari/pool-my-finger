<?php

namespace App\Backend\Services;
use App\Backend\Models\PoolType;
use App\Backend\Repositories\PoolTypeRepository;
use InvalidArgumentException;
use RuntimeException;

class PoolTypeService 
{
  public static function getAllTypes(): array
  {
    return PoolTypeRepository::getAllTypes();
  }

  public static function getTypeById(int $id): ? PoolType 
  {
    if ($id <= 0) {
      throw new InvalidArgumentException("Pool type id must be a positive integer.");
    }
    return PoolTypeRepository::getTypeById($id);
  }

  public static function createType(array $payload): PoolType 
  { 
    //go through a loop for each element to check if they are empty
    foreach ($payload as $key=>$value) {
      self::validator($value);
    }

    $name = $payload['name'];
    $description = $payload['description'];
    $newPoolType = new PoolType(0, $name, $description);

    $poolTypeId = PoolTypeRepository::createType($newPoolType);
    if ($poolTypeId <= 0) {
      throw new RuntimeException("Failed to create pool type.");
    }

    $createdPoolType = PoolTypeRepository::getTypeById($poolTypeId);
    if ($createdPoolType === null) {
      throw new RuntimeException("Pool type was created but could not be loaded");
    }

    return $createdPoolType;
  }

  public static function updatePoolType(int $id, array $payload): ?PoolType {
    if ($id <= 0) {
      throw new InvalidArgumentException("Pool type id must be a positive integer.");
    }
    $existingType = PoolTypeRepository::getTypeById($id);
    if ($existingType === null) {
      return null;
    }

    //go through a loop for each element to check if they are empty
    foreach ($payload as $key=>$value) {
      self::validator($value);
    }

    $name = $payload['name'];
    $description = $payload['description'];

    $updatedPoolType = new PoolType($id, $name, $description);

    $isUpdated = PoolTypeRepository::updateType($id, $updatedPoolType);

    if (!$isUpdated) {
      return null;
    }

    return PoolTypeRepository::getTypeById($id);
  }

  public static function deletePoolType(int $id): bool {
    if ($id <= 0) {
      throw new InvalidArgumentException("Pool type id must be a positive integer.");
    }
    return PoolTypeRepository::deleteType($id);
  }

  private static function validator(string $data): void 
  {
    if ($data === '') {
      throw new InvalidArgumentException("Pool type name cannot be empty.");
    }
  }
}

\class_alias(__NAMESPACE__ . '\\PoolTypeService', 'PoolTypeService');
