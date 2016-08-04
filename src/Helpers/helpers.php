<?php

/*
 * This file is part of Softerize TableList
 *
 * (c) Oscar Dias <oscar.dias@softerize.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */

if (!function_exists('headerTableList')) {
    /**
     * Generates the header html for the table
     *
     * @param  string  $field
     * @param  string  $sortField
     * @param  string  $sortOrder
     * @return string
     */
    function headerTableList($field, $sortField, $sortOrder)
    {
        if(is_array($field))
        {
            if(isset($field['sorting']) && $field['sorting'] === false)
            {
                // Do not sort field
                $sorting = '';
            }

            $name = $field['text'];
        }
        else
        {
            $name = $field;
        }

        // Get correct value for the label
        $label = trans('validation.attributes.'.$name);
        if($label == 'validation.attributes.'.$name) {
            $label = $name;
        }
        $label = ucfirst($label);

        // Sorting
        if($sortField == $name)
        {
            if($sortOrder == 'desc')
            {
                $sorting = 'sort-column sort-desc';
            }
            else
            {
                $sorting = 'sort-column sort-asc';
            }
        }
        else
        {
            $sorting = 'sort-column';
        }

        return "<th class=\"table-list-field {$sorting}\">{$label}</th>";
    }
}

if (!function_exists('fieldTableList')) {
    /**
     * Generates the header html for the field
     *
     * @param  mixed   $row
     * @param  string  $field
     * @return string
     */
    function fieldTableList($row, $field)
    {
        if(is_array($field)) {
            if(isset($field['method']) && $field['method']) {
                return $row->{$field['method']}();
            } else {
                return $row->{$field['name']};
            }
        } else {
            return $row->{$field};
        }
    }
}