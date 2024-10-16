<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

class KovSpace_Function
{
    // Получаем защищенное свойство
    public static function getProtectedProperty(object $obj, string $prop): mixed
    {
        try {
            $reflection = new ReflectionClass($obj);
            $property = $reflection->getProperty($prop);
            if (PHP_VERSION_ID < 80100) {
                $property->setAccessible(true);
            }
            $value = $property->getValue($obj);
        } catch (Exception) {
            $value = null;
        }
        return $value;
    }

    // Получить файлы в директории
    public static function getFilesInDir(string $dir): array
    {
        $files = [];
        if (file_exists($dir)) {
            foreach (scandir($dir) as $file) {
                if ($file === '.' || $file === '..') {
                    continue;
                }
                $filepath = $dir . '/' . $file;
                $files[] = $filepath;
            }
        }
        return $files;
    }

    // Получить значение свойства по ID
    public static function getItemPropertyValue(object $oItem, int $propertyId): mixed
    {
        $aProperties = $oItem->getPropertyValues(false, array($propertyId));
        if ($aProperties) {
            $oProperty = $aProperties[0];
            return $oProperty->value;
        }
        return null;
    }

    // Поменять GET-параметр
    public static function urlParam(string $param, ?string $value)
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
    public static function urlParamRedirect(string $param, ?string $value): void
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

        // Older than 3 months
        Core_QueryBuilder::delete('sessions')
            ->where('time', '<', time() - 7884000)
            ->execute();
    }

    // Получить отсортированные элементы
    public static function getSortedItems(object $object, string $sortField = 'sorting', string $sortDirection = 'asc', ?int $limit = null): array
    {
        $object->queryBuilder()
            ->where('active', '=', 1)
            ->orderBy($sortField, $sortDirection);

        if ($limit) {
            $object->queryBuilder()->limit($limit);
        }

        return $object->findAll();
    }

    // Форматирование чисел
    public static function format(float|int $num): string
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

    // Заголовки для отдачи CSV
    public static function csvHeaders(string $filename): void
    {
        header("Pragma: public");
        header("Content-Description: File Transfer");
        header("Content-Type: application/force-download");
        header("Content-Disposition: attachment; filename=" . $filename);
        header("Content-Transfer-Encoding: binary");
        echo "\xEF\xBB\xBF"; // UTF-8 BOM
    }
}
