<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

class KovSpace_Function
{
    // Получаем защищенное свойство
    public static function getProtectedProperty($obj, $prop) {
        $reflection = new ReflectionClass($obj);
        $property = $reflection->getProperty($prop);
        return $property->getValue($obj);
    }

    // Получить значение свойства по ID
    public static function getItemPropertyValue($oItem, $propertyId): mixed
    {
        $aProperties = $oItem->getPropertyValues(false, array($propertyId));
        if ($aProperties) {
            $oProperty = $aProperties[0];
            return $oProperty->value;
        }
        return null;
    }

    // Поменять GET-параметр
    public static function urlParam($param, $value)
    {
        $url_parts = parse_url($_SERVER['REQUEST_URI']);
        if (isset($url_parts['query'])) {
            parse_str($url_parts['query'], $params);
        }
        $params[$param] = $value;
        $url_parts['query'] = http_build_query($params);
        if ($url_parts['query']) {
            return $url_parts['path'] . '?' . $url_parts['query'];
        } else {
            return $url_parts['path'];
        }
    }

    // Редирект
    public static function redirect(string $url): void
    {
        header('Location:' . $url);
        die();
    }

    // Url Param Redirect
    public static function urlParamRedirect($param, $value): void
    {
        $url = self::urlParam($param, $value);
        self::redirect($url);
    }

    // Удаление устаревших сессий из базы
    public static function removeOldSessions(): void
    {
        // Empty sessions
        Core_QueryBuilder::delete('sessions')
            ->where('time + maxlifetime', '<', time())
            ->where('value', '=', '')
            ->execute();

        // Older than 1 year
        Core_QueryBuilder::delete('sessions')
            ->where('time', '<', time() - 31556926)
            ->execute();
    }

    // Получить отсортированные элементы
    public static function getSortedItems($object, $sortField = 'sorting', $sortDirection = 'asc'): array
    {
        $object->queryBuilder()
            ->where('active', '=', 1)
            ->orderBy($sortField, $sortDirection);

        return $object->findAll();
    }

    // Форматирование чисел
    public static function format($num): string
    {
        return number_format($num, 0, '', ' ');
    }

    // Получение пользовательского поля
    public static function getFieldValue(int $fieldId, int $entityId): mixed
    {
        $oField = Core_Entity::factory('Field', $fieldId);
        $aField_Values = $oField->getValues($entityId);
        return $aField_Values
            ? $aField_Values[0]->value
            : null;
    }

    // Сохранение пользовательского поля
    public static function setFieldValue(int $fieldId, int $entityId, mixed $value): void
    {
        $oField = Core_Entity::factory('Field', $fieldId);
        $aField_Values = $oField->getValues($entityId);

        $oField_Value = $aField_Values
            ? $aField_Values[0]
            : $oField->createNewValue($entityId);

        $oField_Value->value = $value;
        $oField_Value->save();
    }
}