<?php

namespace Loader\Builder;

class Storage
{
  public const GROUP_MODULES = 'modules';
  public const GROUP_COMMON = 'common';
  public const GROUP_SYSTEM = 'system';

  private static $data = [];
  private static $infoData = [];

  public static $configIsset = false;

  public static function setDefault(): void
  {
    $config = require_once 'config.php';

    if (is_array($config)) {
      foreach ($config as $key => $value) {
        $data = [
          'name' => $value['name'],
          'group' => $value['group'] ?: self::GROUP_COMMON,
          'handler' => $value['handler'],
          'param' => $value['param'],
          'path' => $value['path'],
          'package' => $value['package'],
          'create' => $value['create'],
          'call' => $value['call'],
          'object' => null
        ];

        self::add($value['name'], $value['group'] ?: self::GROUP_COMMON, $data);
      }

      self::$configIsset = true;
    }
  }

  public static function change(string $name, array $property): void
  {
    $group = self::getGroupByName($name);

    if (is_array($property)) {
      foreach ($property as $key => $value) {
        self::$data[$group][$name][$key] = $value;
      }
    }
  }

  public static function add(string $name, string $group, $data): void
  {
    self::$infoData[$name] = $group;
    self::$data[$group][$name] = $data;
  }

  public static function getGroupByName($name): string
  {
    return self::$infoData[$name] ?: '';
  }

  public static function delete(string $name): void
  {
    $item = self::$data[self::getGroupByName($name)][$name];

    if (!empty($item) && $item['type'] !== self::GROUP_SYSTEM) {
      unset(self::$data[$name]);
    }
  }

  public static function has(string $name): bool
  {
    return !empty(self::get($name));
  }

  public static function get(string $name): array
  {
    $group = self::getGroupByName($name);

    return self::$data[$group][$name] ?: [];
  }

  public static function getList(string $group): array
  {
    return self::$data[$group] ?: [];
  }
}
