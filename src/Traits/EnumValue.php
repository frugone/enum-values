<?php

namespace Frugone\EnumValue\Traits;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

/**
 * @author P.Frugone <frugone@gmail.com>
 */
trait EnumValue
{
    /**
     * Get all fields of type enum,
     * with their corresponding values
     *
     * @return Array
     */
    public static function getEnumValues()
    {
        $fields = self::getFields();
        $result = [];
        foreach ($fields as $field) {
            $result[$field->field_name] = explode(',', $field->value);
        }
        return $result;
    }

    /**
     * Get the possible values of an "enum" field
     *
     * @param String $fieldName
     * @param Function defautl:null $function FilterFuncion para filtrar los textos (debe pertenecer a la clase )
     * @return Array
     */
    public static function getEnumValue($fieldName, $functionFilter = null)
    {
        $values = self::getFieldValue($fieldName);
        return ($functionFilter) ? array_map([__CLASS__, $functionFilter ], $values) : $values;
    }

    /**
     * Returns an array with the possible values of an "enum" field
     *  in the format for select [$value=> filter( $value), ... ]
     * @param String $fieldName
     * @param Function defautl:null $functionFilter  filter to apply to possible values (must belong to class)
     * @return Array
     */
    public static function getEnumForSelect($fieldName, $functionFilter = null)
    {
        $values = self::getFieldValue($fieldName);
        $arr = [];
        foreach ($values as $value) {
            $arr[$value] = ($functionFilter) ? call_user_func([__CLASS__, $functionFilter ], $value) : $value;
        }
        return $arr;
    }

    /**
     * Get the possible values for the indicated field
     * @param String $fieldName
     * @return Array
     */
    private static function getFieldValue($fieldName)
    {
        $res = $fields = self::getFields();
        return explode(',', $res[0]->value);
    }


    /**
     * get the fields of the DB of type enum
     *
     * @param String $fieldName
     * @return Array of stdClass
     */
    private static function getFields($fieldName = null)
    {
        $connection = static::resolveConnection();

        $query = "SELECT COLUMN_NAME AS field_name,
                        replace(
                            replace(
                                replace( col.column_type,
                                    'enum(', ''
                                ),
                                ')', ''
                            )
                            , '\'' , ''
                        ) AS value
                    FROM information_schema.columns col
                    WHERE col.data_type IN ('enum')
                        AND col.table_schema = '" . $connection->getDatabaseName() . "'
                        AND table_name = '" . (new static)->getTable() . "'
                ";
        $query .= ($fieldName) ? " AND COLUMN_NAME = '$fieldName';" : ';';

        return $connection->select(
            DB::raw($query)
        );
    }
}
