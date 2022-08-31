<?php

namespace Loader;

use Loader\Builder\Storage;
use Loader\Builder\Builder;
use \ReflectionClass;
use \ReflectionMethod;

class Process
{
  public const GROUP_MODULES = Storage::GROUP_MODULES;
  public const GROUP_COMMON = Storage::GROUP_COMMON;
  public const GROUP_SYSTEM = Storage::GROUP_SYSTEM;

  public static function callModule(
    string $name,
    array $param = [],
    string $group = '',
    bool $merge = false
  ): ?object {
    if (!Storage::$configIsset) {
      Storage::setDefault();
    }

    $info = Storage::get($name);

    if (empty($info['handler']) && !class_exists($info['handler'])) {
      return null;
    }

    $rc = self::getConstructor($info['handler']);

    if (!empty($rc)) {;
      $params = self::getParameters($rc->class);
    }

    if (count($params) > 0) {
      foreach ($params as $key => $value) {
        if (empty($value->getType())) {
          continue;
        }

        $class = $value->getType()->getName();

        if (!class_exists($class)) {
          continue;
        }

        $moduleName = (new ReflectionClass($class))->getShortName();

        if (Storage::has($moduleName)) {
          $info['param'][] = self::callModule($moduleName);
        } else {
          $rc = self::getConstructor($class);

          if (!empty($rc)) {
            if ($params = self::getParameters($rc->class)) {
              foreach ($params as $key => $value) {
                if (empty($value->getType())) {
                  $info['param'][] = Builder::createCommonObject($class);
                  continue;
                }

                $class = $value->getType()->getName();

                if (!class_exists($class)) {
                  continue;
                }

                $name = (new ReflectionClass($class))->getShortName();

                if (!Storage::has($name)) {
                  self::addModule(
                    $name,
                    Storage::GROUP_COMMON,
                    $class
                  );
                }

                $info['param'][] = self::callModule($name);
              }
            }
          } else {
            $info['param'][] = Builder::createCommonObject($class);
          }
        }
      }

      Storage::change($info['name'], ['param' => $info['param']]);
    }

    if (!empty($info['call'])) {
      if (is_array($info['call'])) {
        foreach ($info['call'] as $name) {
          $info['param'][] = self::callModule($info['call']);
        }
      } else {
        $info['param'][] = self::callModule($info['call']);
      }

      Storage::change($info['name'], ['param' => $info['param']]);
    }

    if (empty($info['group'])) {
      $info['group'] = Storage::GROUP_COMMON;
    }

    if (!empty($param)) {
      if ($merge) {
        $info['param'] = array_merge($info['param'], $param);
      } else {
        $info['param'] = $param;
      }
    }

    if ($group) {
      $info['group'] = $group;
    }

    if (self::issetGroup($info['group'])) {
      return Builder::create($info, $info['group']);
    }

    return null;
  }

  public static function getConstructor(string $class = ''): ?ReflectionMethod
  {
    if (empty($class)) {
      return null;
    }

    return (new ReflectionClass($class))->getConstructor();
  }

  public static function getParameters(string $class = ''): array
  {
    if (empty($class)) {
      return [];
    }

    return (new ReflectionMethod($class, '__construct'))->getParameters();
  }

  public static function issetGroup(string $name): bool
  {
    $groups = [
      Storage::GROUP_COMMON,
      Storage::GROUP_SYSTEM,
      Storage::GROUP_MODULES,
    ];

    return in_array($name, $groups);
  }

  public static function addModule(
    string $name,
    string $group,
    string $handler = '',
    array $param = [],
    string $path = ''
  ): void {
    $data = [
      'name' => $name,
      'group' => $group,
      'handler' => $handler,
      'param' => $param,
      'path' => $path
    ];

    Storage::add($name, Storage::GROUP_MODULES, $data);
  }

  public static function __callStatic($name, $arguments)
  {
    (new ReflectionMethod(Storage::class, $name))->invoke(null, ...$arguments);
  }
}
